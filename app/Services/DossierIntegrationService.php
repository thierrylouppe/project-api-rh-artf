<?php

namespace App\Services;

use App\Enums\StatutDossier;
use App\Enums\TypeStage;
use App\Interfaces\ActeAdministratifInterface;
use App\Interfaces\AgentInterface;
use App\Interfaces\ConventionStageInterface;
use App\Interfaces\DossierIntegrationInterface;
use App\Interfaces\HistoriqueIntegrationInterface;
use App\Interfaces\ValidationWorkflowInterface;
use App\Models\ActeAdministratif;
use App\Models\DossierIntegration;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/** @property DossierIntegrationInterface $repository */
class DossierIntegrationService extends BaseService
{
    public function __construct(
        DossierIntegrationInterface $repository,
        private readonly ValidationWorkflowInterface $workflowRepository,
        private readonly HistoriqueIntegrationInterface $historiqueRepository,
        private readonly ActeAdministratifInterface $acteRepository,
        private readonly AgentInterface $agentRepository,
        private readonly ConventionStageInterface $conventionStageRepository,
        private readonly DocumentDossierService $documentDossierService,
    ) {
        parent::__construct($repository);
    }

    protected function beforeCreate(array $data): array
    {
        $data['demandeur_id'] = $data['demandeur_id'] ?? Auth::id();
        $data['date_demande'] = $data['date_demande'] ?? now()->toDateString();
        $data['statut']       = StatutDossier::BROUILLON->value;
        $data['reference']    = $this->genererReference();

        return $data;
    }

    public function genererReference(): string
    {
        return DB::transaction(function () {
            $annee   = now()->year;
            $dernier = $this->repository->dernierNumeroReference($annee);
            $seq     = str_pad($dernier + 1, 6, '0', STR_PAD_LEFT);

            return "ARTF-INT-{$annee}-{$seq}";
        });
    }

    public function soumettre(int $id): DossierIntegration
    {
        return $this->transitionner($id, StatutDossier::SOUMIS, 'Dossier soumis pour étude RH');
    }

    public function passerEnEtudeRH(int $id): DossierIntegration
    {
        return $this->transitionner($id, StatutDossier::EN_ETUDE_RH, 'Dossier pris en charge par les RH');
    }

    public function marquerIncomplet(int $id, string $commentaire): DossierIntegration
    {
        return $this->transitionner($id, StatutDossier::DOSSIER_INCOMPLET, $commentaire);
    }

    public function marquerComplet(int $id): DossierIntegration
    {
        if (! $this->documentDossierService->tousObligatoiresDeposes($id)) {
            $manquants = $this->documentDossierService->getDocumentsObligatoiresManquants($id)
                ->pluck('type_document.nom')
                ->implode(', ');

            abort(422, "Impossible de marquer le dossier complet : documents obligatoires manquants ({$manquants}).");
        }

        $nonValides = $this->documentDossierService->getDocumentsObligatoiresNonValides($id);

        if ($nonValides->isNotEmpty()) {
            $noms = $nonValides->pluck('typeDocument.nom')->implode(', ');

            abort(422, "Impossible de marquer le dossier complet : documents obligatoires non validés ({$noms}).");
        }

        return $this->transitionner($id, StatutDossier::DOSSIER_COMPLET, 'Dossier complet — toutes les pièces obligatoires validées');
    }

    public function validerRH(int $id): DossierIntegration
    {
        $dossier = $this->transitionner($id, StatutDossier::VALIDE_RH, 'Validation RH effectuée');
        $this->workflowRepository->initialiserCircuit(DossierIntegration::class, $id);

        return $dossier;
    }

    public function rejeterRH(int $id, string $commentaire): DossierIntegration
    {
        return $this->transitionner($id, StatutDossier::REJETE, $commentaire);
    }

    public function passerEnAttenteDG(int $id): DossierIntegration
    {
        return $this->transitionner($id, StatutDossier::EN_ATTENTE_DG, 'Dossier transmis au Directeur Général');
    }

    public function validerDG(int $id): DossierIntegration
    {
        return $this->transitionner($id, StatutDossier::VALIDE_DG, 'Validation DG accordée');
    }

    public function marquerActeGenere(int $id): DossierIntegration
    {
        return $this->transitionner($id, StatutDossier::ACTE_GENERE, 'Acte administratif généré');
    }

    public function marquerContratSigne(int $id): DossierIntegration
    {
        return $this->transitionner($id, StatutDossier::CONTRAT_SIGNE, 'Contrat signé');
    }

    /**
     * Assigne le matricule fourni par le système externe à l'agent du dossier,
     * puis fait passer le dossier au statut MATRICULE_CREE.
     *
     * Le dossier doit déjà avoir un agent_id lié.
     * Le statut courant doit être ACTE_GENERE ou CONTRAT_SIGNE.
     */
    public function assignerMatricule(int $id, string $matricule): DossierIntegration
    {
        return DB::transaction(function () use ($id, $matricule) {
            $dossier = $this->repository->findById($id);

            abort_if(
                $dossier->agent_id === null,
                422,
                'Aucun agent lié au dossier — veuillez créer la fiche agent avant d\'assigner le matricule'
            );

            $this->agentRepository->assignerMatricule($dossier->agent_id, $matricule);

            return $this->transitionner($id, StatutDossier::MATRICULE_CREE, "Matricule {$matricule} assigné (source : système externe)");
        });
    }

    /** @deprecated Utiliser assignerMatricule() */
    public function marquerMatriculeCree(int $id, int $agentId): DossierIntegration
    {
        $dossier = $this->repository->findById($id);
        $dossier->update(['agent_id' => $agentId]);

        return $this->transitionner($id, StatutDossier::MATRICULE_CREE, 'Matricule créé');
    }

    public function marquerAffecte(int $id): DossierIntegration
    {
        return $this->transitionner($id, StatutDossier::AFFECTE, 'Agent affecté');
    }

    public function marquerNomme(int $id): DossierIntegration
    {
        return $this->transitionner($id, StatutDossier::NOMME, 'Agent nommé');
    }

    public function marquerCompteCree(int $id): DossierIntegration
    {
        return $this->transitionner($id, StatutDossier::COMPTE_CREE, 'Compte utilisateur créé');
    }

    public function marquerPriseDeService(int $id): DossierIntegration
    {
        return $this->transitionner($id, StatutDossier::PRISE_DE_SERVICE, 'Prise de service confirmée');
    }

    public function integrer(int $id): DossierIntegration
    {
        return DB::transaction(function () use ($id) {
            $dossier = $this->transitionner($id, StatutDossier::INTEGRE, 'Intégration administrative finalisée');
            $dossier->load('typeIntegration', 'agent.contratActif');

            if ($dossier->typeIntegration?->estUnStage() && $dossier->agent_id) {
                $this->agentRepository->update($dossier->agent_id, ['statut' => 'stagiaire']);
                $this->creerConventionStage($dossier);
            }

            return $dossier;
        });
    }

    private function creerConventionStage(DossierIntegration $dossier): void
    {
        $nom = $dossier->typeIntegration->nom;
        $typeStage = match (true) {
            str_contains($nom, 'académique')    => TypeStage::ACADEMIQUE->value,
            str_contains($nom, 'qualification') => TypeStage::QUALIFICATION->value,
            default                             => TypeStage::PROFESSIONNEL->value,
        };

        $contrat = $dossier->agent?->contratActif;
        $debut   = $contrat?->date_debut ?? now()->toDateString();
        $fin     = $contrat?->date_fin   ?? now()->addMonths(6)->toDateString();

        $this->conventionStageRepository->create([
            'agent_id'              => $dossier->agent_id,
            'contrat_id'            => $contrat?->id,
            'dossier_integration_id' => $dossier->id,
            'type_stage'            => $typeStage,
            'etablissement'         => $dossier->notes ?? 'Non renseigné',
            'date_debut'            => $debut,
            'date_fin'              => $fin,
            'statut_stage'          => 'EN_COURS',
        ]);
    }

    public function suspendre(int $id, string $commentaire): DossierIntegration
    {
        return $this->transitionner($id, StatutDossier::SUSPENDU, $commentaire);
    }

    public function annuler(int $id, string $commentaire): DossierIntegration
    {
        return $this->transitionner($id, StatutDossier::ANNULE, $commentaire);
    }

    /**
     * Génère automatiquement l'acte administratif correspondant au type d'intégration du dossier.
     *
     * - Le type d'acte est déterminé par TypeIntegration::type_acte_administratif.
     * - Si le type nécessite un contrat, le statut reste ACTE_GENERE (étape CONTRAT_SIGNE à suivre).
     * - Sinon, le statut passe directement à MATRICULE_CREE (le contrat n'est pas requis).
     *
     * @return array{acte: ActeAdministratif, dossier: DossierIntegration, necessite_contrat: bool}
     */
    public function genererActeAdministratif(int $id): array
    {
        return DB::transaction(function () use ($id) {
            $dossier = $this->repository->findById($id);
            $dossier->load('typeIntegration');

            abort_unless(
                $dossier->statut === StatutDossier::VALIDE_DG,
                422,
                "L'acte ne peut être généré qu'après la validation DG (statut actuel : {$dossier->statut->label()})"
            );

            $typeIntegration = $dossier->typeIntegration;
            $typeActe = $typeIntegration->acteAdministratifEnum();

            abort_if(
                $typeActe === null,
                422,
                "Aucun acte administratif configuré pour le type d'intégration « {$typeIntegration->nom} »"
            );

            $numero = $this->acteRepository->genererNumero($typeActe);
            $acte   = $this->acteRepository->create([
                'dossier_integration_id' => $id,
                'type_acte'              => $typeActe->value,
                'numero'                 => $numero,
            ]);

            $dossier = $this->transitionner($id, StatutDossier::ACTE_GENERE, "Acte {$typeActe->label()} généré automatiquement (n° {$numero})");

            $necessite_contrat = (bool) $typeIntegration->necessite_contrat;

            if (! $necessite_contrat) {
                $dossier = $this->transitionner($id, StatutDossier::MATRICULE_CREE, 'Pas de contrat requis — passage direct à la création du matricule');
            }

            return compact('acte', 'dossier', 'necessite_contrat');
        });
    }

    private function transitionner(int $id, StatutDossier $cible, string $commentaire): DossierIntegration
    {
        return DB::transaction(function () use ($id, $cible, $commentaire) {
            $dossier = $this->repository->findById($id);
            $ancienStatut = $dossier->statut;

            if (! $ancienStatut->peutTransitionnerVers($cible)) {
                abort(422, "Transition invalide : {$ancienStatut->label()} → {$cible->label()}");
            }

            $dossier = $this->repository->changerStatut($id, $cible);

            $this->historiqueRepository->enregistrer(
                DossierIntegration::class,
                $id,
                Auth::id() ?? 1,
                "transition_statut",
                ['statut' => $ancienStatut->value],
                ['statut' => $cible->value],
                $commentaire
            );

            return $dossier;
        });
    }

    public function getByStatut(StatutDossier $statut)
    {
        return $this->repository->getByStatut($statut);
    }

    public function findByReference(string $reference): ?DossierIntegration
    {
        return $this->repository->findByReference($reference);
    }
}
