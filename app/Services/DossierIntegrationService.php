<?php

namespace App\Services;

use App\Enums\NiveauValidation;
use App\Enums\StatutDossier;
use App\Enums\TypeStage;
use App\Interfaces\AgentInterface;
use App\Interfaces\CircuitValidationInterface;
use App\Interfaces\CompteIntegrationInterface;
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
        private readonly CircuitValidationInterface $circuitValidationRepository,
        private readonly HistoriqueIntegrationInterface $historiqueRepository,
        private readonly ActeAdministratifService $acteService,
        private readonly AgentInterface $agentRepository,
        private readonly ConventionStageInterface $conventionStageRepository,
        private readonly CompteIntegrationInterface $compteRepository,
        private readonly DocumentDossierService $documentDossierService,
        private readonly CompteIntegrationService $compteService,
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
        $dossier->load('typeIntegration');

        $niveaux = $this->resoudreCircuitPourType($dossier);

        // Aucun niveau restant (ex. type sans DG et sans circuit configuré) → prêt pour la suite.
        if ($niveaux === []) {
            return $this->transitionner(
                $id,
                StatutDossier::VALIDE_DG,
                'Circuit hiérarchique / validation DG non requis pour ce type — dossier prêt'
            );
        }

        $this->workflowRepository->initialiserCircuit(
            DossierIntegration::class,
            $id,
            $niveaux
        );

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
        $dossier = $this->repository->findById($id);

        // Mode post-intégration : pas de rejeu du workflow de statuts.
        if ($dossier->statut === StatutDossier::INTEGRE) {
            return $dossier;
        }

        return $this->transitionner($id, StatutDossier::CONTRAT_SIGNE, 'Contrat signé');
    }

    /**
     * Assigne le matricule fourni par le système externe à l'agent du dossier,
     * puis fait passer le dossier au statut MATRICULE_CREE si ce n'est pas déjà le cas.
     *
     * Le dossier peut être en ACTE_GENERE, CONTRAT_SIGNE ou déjà MATRICULE_CREE
     * (cas d'un type sans contrat où genererActeAdministratif() a auto-transitionné).
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

            $agent = $this->agentRepository->findById($dossier->agent_id);

            abort_if(
                $agent->matricule !== null,
                422,
                "Un matricule est déjà assigné à cet agent ({$agent->matricule}). Il ne peut pas être modifié."
            );

            $this->agentRepository->assignerMatricule($dossier->agent_id, $matricule);

            // Statut déjà MATRICULE_CREE (acte généré sans contrat) : pas de transition à refaire
            // Statut INTEGRE : mode post-intégration — on assigne le matricule sans rejouer le workflow
            if (in_array($dossier->statut, [StatutDossier::MATRICULE_CREE, StatutDossier::INTEGRE], true)) {
                return $dossier->fresh(['agent']);
            }

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

    /**
     * Intègre le dossier en un seul appel depuis VALIDE_DG (flux simplifié)
     * ou depuis PRISE_DE_SERVICE (flux complet legacy).
     *
     * Depuis VALIDE_DG : crée automatiquement le compte utilisateur si le type
     * le requiert (`necessite_compte_utilisateur`), puis retourne les tâches post-intégration.
     *
     * @return array{dossier: DossierIntegration, compte: ?object, taches_post_integration: array}
     */
    public function integrer(int $id): array
    {
        return DB::transaction(function () use ($id) {
            $dossier = $this->repository->findById($id);
            $dossier->load('typeIntegration', 'agent.contratActif');

            $depuisValideeDG = $dossier->statut === StatutDossier::VALIDE_DG;

            $dossier = $this->transitionner($id, StatutDossier::INTEGRE, 'Intégration administrative finalisée');
            $dossier->load('typeIntegration', 'agent.contratActif');

            $compte = null;
            $necessiteCompte = (bool) ($dossier->typeIntegration?->necessite_compte_utilisateur ?? true);

            if ($depuisValideeDG && $necessiteCompte && $dossier->agent_id) {
                $agent = $this->agentRepository->findById($dossier->agent_id);

                if ($this->compteRepository->findByAgent($dossier->agent_id) === null) {
                    $compte = $this->compteService->provisionner($agent);
                }
            }

            if ($dossier->typeIntegration?->estUnStage() && $dossier->agent_id) {
                $this->agentRepository->update($dossier->agent_id, ['statut' => 'stagiaire']);
                $this->creerConventionStage($dossier);
            }

            // Recharger l'agent (ex. statut stagiaire) après les automatismes post-intégration
            $dossier->load('typeIntegration', 'agent.contratActif');

            return [
                'dossier'                 => $dossier,
                'compte'                  => $compte,
                'taches_post_integration' => $this->tachesPostIntegration($id),
            ];
        });
    }

    /**
     * Retourne la liste des tâches post-intégration avec leur statut (fait / non_fait).
     *
     * La liste est filtrée selon les flags du type d'intégration
     * (`necessite_contrat`, `necessite_compte_utilisateur`, stage…).
     * Les clés FE existantes (`etape`, `label`, `endpoint`, `statut`, `obligatoire`) sont préservées.
     */
    public function tachesPostIntegration(int $id): array
    {
        $dossier = $this->repository->findById($id);
        $dossier->load(
            'typeIntegration',
            'agent.affectations',
            'agent.nominations',
            'agent.remisesMateriel',
            'agent.salaireActuel',
            'agent.contratActif',
            'priseDeService',
            'actes'
        );

        $agent             = $dossier->agent;
        $type              = $dossier->typeIntegration;
        $necessiteContrat  = (bool) ($type?->necessite_contrat);
        $necessiteCompte   = (bool) ($type?->necessite_compte_utilisateur ?? true);
        $estStage          = (bool) ($type?->estUnStage());

        $compteExistant = $agent
            ? $this->compteRepository->findByAgent($agent->id) !== null
            : false;

        $taches = [];

        $taches[] = [
            'etape'       => 11,
            'label'       => 'Générer l\'acte administratif',
            'endpoint'    => "POST /integration/dossiers/{$id}/generer-acte",
            'statut'      => $dossier->actes->isNotEmpty() ? 'fait' : 'non_fait',
            'obligatoire' => true,
        ];

        if ($necessiteContrat) {
            $taches[] = [
                'etape'       => 12,
                'label'       => 'Marquer le contrat signé',
                'endpoint'    => "POST /integration/dossiers/{$id}/marquer-contrat-signe",
                'statut'      => $agent?->contratActif ? 'fait' : 'non_fait',
                'obligatoire' => false,
            ];

            if (! $estStage) {
                $taches[] = [
                    'etape'       => 12,
                    'label'       => 'Salaire initial (auto à la création du contrat CDI/CDD)',
                    'endpoint'    => "GET /integration/agents/{$agent?->id}/salaires/actuel",
                    'statut'      => $agent?->salaireActuel ? 'fait' : 'non_fait',
                    'obligatoire' => false,
                ];
            }
        }

        $taches[] = [
            'etape'       => 13,
            'label'       => 'Assigner le matricule',
            'endpoint'    => "POST /integration/dossiers/{$id}/assigner-matricule",
            'statut'      => $agent?->matricule ? 'fait' : 'non_fait',
            'obligatoire' => true,
        ];

        $taches[] = [
            'etape'       => 14,
            'label'       => 'Affecter l\'agent (module carrière)',
            'endpoint'    => 'POST /carriere/affectations',
            'statut'      => $agent?->affectations?->isNotEmpty() ? 'fait' : 'non_fait',
            'obligatoire' => false,
        ];

        if (! $estStage) {
            $taches[] = [
                'etape'       => 15,
                'label'       => 'Nommer l\'agent (module carrière)',
                'endpoint'    => 'POST /carriere/nominations',
                'statut'      => $agent?->nominations?->isNotEmpty() ? 'fait' : 'non_fait',
                'obligatoire' => false,
            ];
        }

        if ($necessiteCompte) {
            $taches[] = [
                'etape'       => 16,
                'label'       => 'Compte utilisateur',
                'endpoint'    => 'POST /integration/comptes/provisionner',
                'statut'      => $compteExistant ? 'fait' : 'non_fait',
                'obligatoire' => true,
            ];
        }

        $taches[] = [
            'etape'       => 17,
            'label'       => 'Remettre le matériel',
            'endpoint'    => 'POST /integration/remises-materiel',
            'statut'      => $agent?->remisesMateriel?->isNotEmpty() ? 'fait' : 'non_fait',
            'obligatoire' => false,
        ];

        $taches[] = [
            'etape'       => 18,
            'label'       => 'Confirmer la prise de service',
            'endpoint'    => 'POST /integration/prises-de-service',
            'statut'      => $dossier->priseDeService ? 'fait' : 'non_fait',
            'obligatoire' => false,
        ];

        return $taches;
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
     * Enregistre l'acte d'entrée (délègue au service actes). Idempotent.
     * Chemin A (VALIDE_DG, premier enregistrement) : transition ACTE_GENERE / MATRICULE_CREE.
     * Chemin B (INTEGRE) : le dossier ne change pas de statut.
     *
     * @return array{acte: ActeAdministratif, dossier: DossierIntegration, necessite_contrat: bool, cree: bool}
     */
    public function genererActeAdministratif(int $id): array
    {
        return DB::transaction(function () use ($id) {
            $result            = $this->acteService->enregistrerPourDossier($id);
            $dossier           = $result['dossier'];
            $necessite_contrat = (bool) $dossier->typeIntegration?->necessite_contrat;

            if (
                $result['cree']
                && $dossier->statut === StatutDossier::VALIDE_DG
            ) {
                $dossier = $this->transitionner(
                    $id,
                    StatutDossier::ACTE_GENERE,
                    "Acte {$result['acte']->type_acte->label()} enregistré (n° {$result['acte']->numero})"
                );

                if (! $necessite_contrat) {
                    $dossier = $this->transitionner(
                        $id,
                        StatutDossier::MATRICULE_CREE,
                        'Pas de contrat requis — passage direct à la création du matricule'
                    );
                }
            }

            return [
                'acte'              => $result['acte'],
                'dossier'           => $dossier->fresh(['typeIntegration', 'actes', 'agent']),
                'necessite_contrat' => $necessite_contrat,
                'cree'              => $result['cree'],
            ];
        });
    }

    /**
     * Résout le circuit hiérarchique applicable à un dossier selon son type.
     *
     * - Circuit configuré sur le type, sinon circuit complet par défaut.
     * - Si `necessite_validation_dg = false`, le niveau Directeur Général est retiré.
     *
     * @return list<array{niveau: string, ordre: int}>
     */
    private function resoudreCircuitPourType(DossierIntegration $dossier): array
    {
        $niveaux = $this->circuitValidationRepository->getCircuitPourType($dossier->type_integration_id);

        if ($niveaux === []) {
            $niveaux = array_map(
                fn (NiveauValidation $n) => ['niveau' => $n->value, 'ordre' => $n->ordre()],
                NiveauValidation::circuitComplet()
            );
        }

        $necessiteDg = (bool) ($dossier->typeIntegration?->necessite_validation_dg ?? true);

        if (! $necessiteDg) {
            $niveaux = array_values(array_filter(
                $niveaux,
                fn (array $step) => ($step['niveau'] ?? null) !== NiveauValidation::DIRECTEUR_GENERAL->value
            ));
        }

        return $niveaux;
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
