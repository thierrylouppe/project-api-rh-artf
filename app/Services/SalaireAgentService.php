<?php

namespace App\Services;

use App\Enums\StatutSalaireAgent;
use App\Enums\TypeChangementSalaireAgent;
use App\Interfaces\AgentInterface;
use App\Interfaces\ClassegrillesalarialeInterface;
use App\Interfaces\ContratInterface;
use App\Interfaces\EchelonInterface;
use App\Interfaces\ParametregrileInterface;
use App\Interfaces\SalaireAgentInterface;
use App\Interfaces\SalaireInterface;
use App\Models\Agent;
use App\Models\Classegrillesalariale;
use App\Models\Contrat;
use App\Models\Salaire;
use App\Models\SalaireAgent;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

/** @property SalaireAgentInterface $repository */
class SalaireAgentService extends BaseService
{
    private const SIGLES_ELIGIBLES = ['CDI', 'CDD'];

    public function __construct(
        SalaireAgentInterface $repository,
        private readonly AgentInterface $agentRepository,
        private readonly ContratInterface $contratRepository,
        private readonly ClassegrillesalarialeInterface $classeRepository,
        private readonly SalaireInterface $salaireRepository,
        private readonly EchelonInterface $echelonRepository,
        private readonly ParametregrileInterface $parametreGrilleRepository,
    ) {
        parent::__construct($repository);
    }

    /**
     * Point d'entrée HTTP : résout agent / contrat puis délègue à creerSalaireInitial.
     *
     * @param  array{agent_id: int, contrat_id?: int, date_debut?: string, motif?: string}  $data
     */
    public function creerDepuisRequest(array $data): ?SalaireAgent
    {
        $agent = $this->agentRepository->findById($data['agent_id']);
        $contrat = isset($data['contrat_id'])
            ? $this->contratRepository->findById($data['contrat_id'])
            : null;

        $salaireAgent = $this->creerSalaireInitial(
            $agent,
            $contrat,
            $data['motif'] ?? null
        );

        if ($salaireAgent !== null && ! empty($data['date_debut'])) {
            return $this->repository->update($salaireAgent->id, ['date_debut' => $data['date_debut']]);
        }

        return $salaireAgent;
    }

    public function getByAgent(int $agentId): Collection
    {
        return $this->repository->getByAgent($agentId);
    }

    /**
     * Timeline carrière : ordre chronologique + variations échelon / montant.
     */
    public function getHistorique(int $agentId): Collection
    {
        $items = $this->repository->getHistoriqueByAgent($agentId);
        $precedent = null;

        return $items->map(function (SalaireAgent $item) use (&$precedent) {
            if ($precedent !== null) {
                $item->setAttribute('echelon_precedent', $precedent->echelon);
                $item->setAttribute('montant_precedent', $precedent->montant_net ?? $precedent->montant_base);
                $item->setAttribute('variation_echelon', $item->echelon - $precedent->echelon);
                $item->setAttribute(
                    'variation_montant',
                    ($item->montant_net ?? $item->montant_base)
                        - ($precedent->montant_net ?? $precedent->montant_base)
                );
            } else {
                $item->setAttribute('echelon_precedent', null);
                $item->setAttribute('montant_precedent', null);
                $item->setAttribute('variation_echelon', null);
                $item->setAttribute('variation_montant', null);
            }

            $precedent = $item;

            return $item;
        });
    }

    public function getActuel(int $agentId): ?SalaireAgent
    {
        return $this->repository->getActuel($agentId);
    }

    public function getMontantActuel(int $agentId): ?float
    {
        $actuel = $this->repository->getActuel($agentId);

        return $actuel?->montant_net ?? $actuel?->montant_base;
    }

    /**
     * Crée le salaire initial d'un agent à partir de sa classe / échelon et de la grille.
     * Réservé aux contrats CDI / CDD. Retourne null si non applicable.
     */
    public function creerSalaireInitial(
        Agent $agent,
        ?Contrat $contrat = null,
        ?string $motif = null,
    ): ?SalaireAgent {
        if ($contrat !== null) {
            $contrat->loadMissing('typeContrat');
            $sigle = $contrat->typeContrat?->sigle;

            if (! in_array($sigle, self::SIGLES_ELIGIBLES, true)) {
                return null;
            }
        }

        return DB::transaction(function () use ($agent, $contrat, $motif) {
            $agent = $this->agentRepository->findById($agent->id);
            $agent->loadMissing('echelon');

            $classe = $this->resoudreClasse($agent);
            $echelonNumero = $this->resoudreEchelonNumero($agent);
            $ligneGrille = $this->trouverLigneGrille($classe->id, $echelonNumero);

            $dateDebut = $contrat?->date_debut?->toDateString()
                ?? $agent->date_prise_service?->toDateString()
                ?? now()->toDateString();

            $this->repository->cloturerActifs($agent->id, $dateDebut);

            $avaitDejaUnSalaire = $this->repository->getByAgent($agent->id)->isNotEmpty();

            return $this->repository->create([
                'agent_id'                 => $agent->id,
                'salaire_id'               => $ligneGrille->id,
                'classegrillesalariale_id' => $classe->id,
                'echelon'                  => $echelonNumero,
                'montant_base'             => $ligneGrille->salaire,
                'montant_net'              => $ligneGrille->salaire,
                'date_debut'               => $dateDebut,
                'date_fin'                 => null,
                'statut'                   => StatutSalaireAgent::ACTIF,
                'type_changement'          => $avaitDejaUnSalaire
                    ? TypeChangementSalaireAgent::CORRECTION
                    : TypeChangementSalaireAgent::INITIAL,
                'motif'                    => $motif,
            ]);
        });
    }

    public function cloturer(int $id, ?string $dateFin = null, ?string $motif = null): SalaireAgent
    {
        $salaireAgent = $this->repository->findById($id);

        abort_unless(
            $salaireAgent->statut === StatutSalaireAgent::ACTIF,
            422,
            'Seul un salaire actif peut être clôturé.'
        );

        $data = [
            'statut'   => StatutSalaireAgent::CLOTURE,
            'date_fin' => $dateFin ?? now()->toDateString(),
        ];

        if ($motif !== null) {
            $data['motif'] = $motif;
        }

        return $this->repository->update($id, $data);
    }

    public function cloturerActuel(int $agentId, ?string $dateFin = null, ?string $motif = null): ?SalaireAgent
    {
        $actuel = $this->repository->getActuel($agentId);

        if ($actuel === null) {
            return null;
        }

        return $this->cloturer($actuel->id, $dateFin, $motif);
    }

    /**
     * Bulletin PDF simplifié du salaire actif (ou d'une période précise).
     */
    public function genererBulletinPdf(int $agentId, ?int $salaireAgentId = null): Response
    {
        $salaireAgent = $salaireAgentId !== null
            ? $this->repository->findById($salaireAgentId)
            : $this->repository->getActuel($agentId);

        abort_if($salaireAgent === null, 404, 'Aucun salaire trouvé pour générer le bulletin.');

        abort_unless(
            (int) $salaireAgent->agent_id === $agentId,
            422,
            'Ce salaire n\'appartient pas à cet agent.'
        );

        $salaireAgent->load([
            'agent.grade',
            'agent.categorie',
            'agent.echelon',
            'agent.fonction',
            'classe.categorie',
            'classe.grade',
            'salaire',
        ]);

        $pdf = Pdf::loadView('pdf.bulletin-salaire-agent', [
            'salaireAgent' => $salaireAgent,
            'agent'        => $salaireAgent->agent,
        ]);

        $matricule = $salaireAgent->agent->matricule ?? $salaireAgent->agent_id;
        $periode = $salaireAgent->date_debut?->format('Y-m') ?? now()->format('Y-m');

        return $pdf->stream("bulletin-salaire-{$matricule}-{$periode}.pdf");
    }

    /**
     * Passe l'agent à l'échelon suivant de sa classe (plafonné par echelon_fin).
     * Clôture le salaire actuel et en crée un nouveau.
     */
    public function avancerEchelon(int $agentId, ?string $motif = null): SalaireAgent
    {
        return DB::transaction(function () use ($agentId, $motif) {
            $actuel = $this->repository->getActuel($agentId);

            abort_if($actuel === null, 422, 'Aucun salaire actif pour cet agent.');

            $echelonFin = (int) $this->parametreGrilleRepository->getCurrent()->echelon_fin;
            $nouvelEchelon = $actuel->echelon + 1;

            abort_if(
                $nouvelEchelon > $echelonFin,
                422,
                "L'agent est déjà au dernier échelon ({$actuel->echelon})."
            );

            $ligneGrille = $this->trouverLigneGrille($actuel->classegrillesalariale_id, $nouvelEchelon);
            $dateDebut = now()->toDateString();

            $this->repository->cloturerActifs($agentId, $dateDebut);

            $echelonModel = $this->echelonRepository->findByNumero($nouvelEchelon);
            if ($echelonModel) {
                $this->agentRepository->update($agentId, ['echelon_id' => $echelonModel->id]);
            }

            return $this->repository->create([
                'agent_id'                 => $agentId,
                'salaire_id'               => $ligneGrille->id,
                'classegrillesalariale_id' => $actuel->classegrillesalariale_id,
                'echelon'                  => $nouvelEchelon,
                'montant_base'             => $ligneGrille->salaire,
                'montant_net'              => $ligneGrille->salaire,
                'date_debut'               => $dateDebut,
                'date_fin'                 => null,
                'statut'                   => StatutSalaireAgent::ACTIF,
                'type_changement'          => TypeChangementSalaireAgent::AVANCEMENT_ECHELON,
                'motif'                    => $motif,
            ]);
        });
    }

    private function resoudreClasse(Agent $agent): Classegrillesalariale
    {
        abort_unless(
            $agent->categorie_id && $agent->grade_id,
            422,
            "Impossible de déterminer la classe salariale : l'agent doit avoir une catégorie et un grade."
        );

        $classe = $this->classeRepository->findByCategorieAndGrade(
            (int) $agent->categorie_id,
            (int) $agent->grade_id
        );

        abort_if(
            $classe === null,
            422,
            "Aucune classe de grille pour catégorie #{$agent->categorie_id} / grade #{$agent->grade_id}."
        );

        return $classe;
    }

    private function resoudreEchelonNumero(Agent $agent): int
    {
        $params = $this->parametreGrilleRepository->getCurrent();
        $depart = max(1, (int) $params->echelon_depart);
        $fin = max($depart, (int) $params->echelon_fin);

        $numero = $agent->echelon?->numero;

        if ($numero !== null && $numero >= $depart && $numero <= $fin) {
            return (int) $numero;
        }

        return $depart;
    }

    private function trouverLigneGrille(int $classeId, int $echelon): Salaire
    {
        $ligne = $this->salaireRepository->findByClasseAndEchelon($classeId, $echelon);

        abort_if(
            $ligne === null,
            422,
            "Ligne de grille introuvable (classe #{$classeId}, échelon {$echelon}). Générez d'abord la grille salariale."
        );

        return $ligne;
    }
}
