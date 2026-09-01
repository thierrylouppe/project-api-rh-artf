<?php

namespace App\Services;

use App\Interfaces\AgentInterface;
use App\Interfaces\DossierIntegrationInterface;
use App\Interfaces\UserInterface;
use App\Models\Agent;
use App\Models\Diplome;
use App\Models\DossierIntegration;
use App\Models\Echelon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/** @property AgentInterface $repository */
class AgentService extends BaseService
{
    public function __construct(
        AgentInterface $repository,
        private readonly DossierIntegrationInterface $dossierRepository,
        private readonly UserInterface $userRepository,
    ) {
        parent::__construct($repository);
    }

    /**
     * Crée l'agent puis initialise automatiquement son dossier d'intégration en arrière-plan.
     * Le dossier est créé au statut BROUILLON avec type_integration_id, agent_id et date_demande.
     *
     * @return array{agent: Agent, dossier: DossierIntegration}
     */
    public function creerAvecDossier(array $data): array
    {
        return DB::transaction(function () use ($data) {
            $data = $this->resoudreInfosDepuisDiplome($data);

            $agent = $this->repository->create($data);

            $dossier = $this->dossierRepository->create([
                'type_integration_id' => $data['type_integration_id'],
                'agent_id'            => $agent->id,
                'demandeur_id'        => Auth::id(),
                'date_demande'        => now()->toDateString(),
                'statut'              => 'BROUILLON',
                'reference'           => $this->genererReferenceDossier(),
            ]);

            return compact('agent', 'dossier');
        });
    }

    /**
     * Si un diplome_id est fourni et que categorie_id / grade_id ne sont pas déjà saisis,
     * les résout automatiquement depuis la classe grille du diplôme.
     * L'échelon de départ est toujours Échelon 1.
     */
    private function resoudreInfosDepuisDiplome(array $data): array
    {
        if (empty($data['diplome_id'])) {
            return $data;
        }

        $diplome = Diplome::with('classeGrille')->find($data['diplome_id']);

        if (! $diplome?->classeGrille) {
            return $data;
        }

        $classe = $diplome->classeGrille;

        $data['categorie_id'] = $data['categorie_id'] ?? $classe->categorie_id;
        $data['grade_id']     = $data['grade_id']     ?? $classe->grade_id;

        if (empty($data['echelon_id'])) {
            $echelon1 = Echelon::where('numero', 1)->first();
            $data['echelon_id'] = $echelon1?->id;
        }

        return $data;
    }

    private function genererReferenceDossier(): string
    {
        $annee   = now()->year;
        $dernier = $this->dossierRepository->dernierNumeroReference($annee);
        $seq     = str_pad($dernier + 1, 6, '0', STR_PAD_LEFT);

        return "ARTF-INT-{$annee}-{$seq}";
    }

    /**
     * Assigne un matricule externe à un agent (matricule fourni par le système RH externe).
     */
    public function assignerMatricule(int $agentId, string $matricule): Agent
    {
        return $this->repository->assignerMatricule($agentId, $matricule);
    }

    /**
     * Modifie le matricule d'un agent déjà existant.
     * Vérifie que le nouveau matricule n'est pas déjà utilisé par un autre agent.
     */
    public function modifierMatricule(int $agentId, string $nouveauMatricule): Agent
    {
        abort_if(
            $this->repository->matriculeEstPris($nouveauMatricule, $agentId),
            422,
            "Le matricule « {$nouveauMatricule} » est déjà attribué à un autre agent."
        );

        return $this->repository->modifierMatricule($agentId, $nouveauMatricule);
    }

    public function mettreAJourStatut(int $id, string $statut): Model
    {
        return $this->update($id, ['statut' => $statut]);
    }

    public function mettreAJourDatePriseService(int $id, string $date): Model
    {
        return $this->update($id, ['date_prise_service' => $date]);
    }

    public function getByStatut(string $statut)
    {
        return $this->repository->getByStatut($statut);
    }

    public function listerIntegres(array $filters = [])
    {
        return $this->repository->getIntegres($filters);
    }

    public function listerStagiaires(array $filters = [])
    {
        return $this->repository->getStagiaires($filters);
    }

    public function findByMatricule(string $matricule): ?Agent
    {
        return $this->repository->findByMatricule($matricule);
    }

    public function syntheseCarriere(int $id): Agent
    {
        /** @var Agent $agent */
        $agent = $this->repository->findById($id);
        $agent->load([
            'contratActif.typeContrat',
            'affectationActive.structure',
            'nominationActive.structure',
            'salaireActuel',
        ]);

        return $agent;
    }

    public function fichePersonnel(int $id): Agent
    {
        /** @var Agent $agent */
        $agent = $this->repository->findById($id);
        $agent->load([
            'grade',
            'categorie',
            'echelon',
            'fonction',
            'typeIntegration',
            'informationsPersonnelles',
            'informationsProfessionnelles.diplome',
            'contactsUrgence',
            'situationFamiliale',
            'documents.typeDocument',
            'affectationActive',
            'nominationActive',
            'contratActif',
        ]);

        return $agent;
    }

    public function archiver(int $id, string $motif): Agent
    {
        /** @var Agent $agent */
        $agent = $this->repository->findById($id);

        abort_if($agent->statut === 'archive', 422, 'Cet agent est déjà archivé.');
        abort_if($agent->statut === 'stagiaire', 422, 'Un stagiaire se clôture via le module stage, pas par archivage RH.');

        $agent = $this->repository->update($id, [
            'statut'          => 'archive',
            'archived_at'     => now(),
            'archived_by'     => Auth::id(),
            'motif_archivage' => $motif,
        ]);

        $compte = $this->userRepository->findByAgentId($id);
        if ($compte) {
            $this->userRepository->update($compte->id, ['is_active' => false]);
        }

        return $agent->fresh();
    }

    public function desarchiver(int $id): Agent
    {
        /** @var Agent $agent */
        $agent = $this->repository->findById($id);

        abort_unless($agent->statut === 'archive', 422, 'Cet agent n\'est pas archivé.');

        $agent = $this->repository->update($id, [
            'statut'          => 'inactif',
            'archived_at'     => null,
            'archived_by'     => null,
            'motif_archivage' => null,
        ]);

        $compte = $this->userRepository->findByAgentId($id);
        if ($compte) {
            $this->userRepository->update($compte->id, ['is_active' => true]);
        }

        return $agent->fresh();
    }
}
