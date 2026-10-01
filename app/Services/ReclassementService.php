<?php

namespace App\Services;

use App\Enums\MotifReconversion;
use App\Enums\StatutReclassement;
use App\Enums\TypeReclassement;
use App\Interfaces\AgentInterface;
use App\Interfaces\ClassegrillesalarialeInterface;
use App\Interfaces\DiplomeInterface;
use App\Interfaces\FonctionInterface;
use App\Interfaces\InformationsProfessionnelleInterface;
use App\Interfaces\ParametregrileInterface;
use App\Interfaces\ReclassementInterface;
use App\Interfaces\SalaireAgentInterface;
use App\Interfaces\SalaireInterface;
use App\Models\Agent;
use App\Models\Classegrillesalariale;
use App\Models\Reclassement;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Validation\ValidationException;

/**
 * Reclassement / hors classe / reconversion (CCN ARTF art. 73–75).
 *
 * D11 : nouvel échelon = echelon_depart de la grille (pas conservation du n°).
 * D12 : hors classe = classe grille « Hors Classe » (Classe X). 422 si pas de ligne de salaire.
 * D13 : RH crée ; art. 73 RH approuve ; 74/75 DG approuve ; RH applique.
 * D14 : permissions existantes consulter/gerer-salaires (pas valider-evaluations).
 */
class ReclassementService extends BaseService
{
    private const GRADE_INSPECTEUR_PRINCIPAL = 'Inspecteur Principal';
    private const GRADE_HORS_CLASSE          = 'Hors Classe';
    private const ECHELON_HORS_CLASSE        = 8;

    public function __construct(
        ReclassementInterface                          $repository,
        private readonly AgentInterface                $agentRepository,
        private readonly SalaireAgentInterface         $salaireAgentRepository,
        private readonly ClassegrillesalarialeInterface $classeRepository,
        private readonly DiplomeInterface              $diplomeRepository,
        private readonly InformationsProfessionnelleInterface $infosProRepository,
        private readonly FonctionInterface             $fonctionRepository,
        private readonly ParametregrileInterface       $parametreGrilleRepository,
        private readonly SalaireInterface              $salaireRepository,
        private readonly SalaireAgentService           $salaireService,
    ) {
        parent::__construct($repository);
    }

    public function parAgent(int $agentId)
    {
        return $this->repository->parAgent($agentId);
    }

    public function detail(int $id): Reclassement
    {
        /** @var Reclassement $dossier */
        $dossier = $this->repository->findById($id);
        $dossier->load([
            'agent.grade',
            'agent.categorie',
            'classeOrigine.grade',
            'classeOrigine.categorie',
            'classeCible.grade',
            'classeCible.categorie',
            'fonctionCible',
            'diplome',
        ]);
        $dossier->setAttribute('eligibilite', $this->snapshotEligibilite($dossier));
        $dossier->setAttribute('prochaine_etape', $this->prochaineEtape($dossier));

        return $dossier;
    }

    /**
     * @param  array{
     *     agent_id: int,
     *     type: string,
     *     motif: string,
     *     diplome_id?: int|null,
     *     classe_cible_id?: int|null,
     *     fonction_cible_id?: int|null,
     *     motif_reconversion?: string|null,
     *     piece_path?: string|null
     * }  $data
     *
     * @throws ValidationException
     */
    public function soumettre(array $data, User $rh): Reclassement
    {
        $type  = TypeReclassement::from($data['type']);
        $agent = $this->agentRepository->findById((int) $data['agent_id']);
        $agent->loadMissing(['grade', 'categorie', 'informationsProfessionnelles']);

        if ($this->repository->enCoursPourAgent((int) $agent->id)->isNotEmpty()) {
            throw ValidationException::withMessages([
                'agent_id' => 'Un reclassement est déjà en cours pour cet agent.',
            ]);
        }

        $audit   = $this->collecterAudit($agent);
        $cible   = $this->resoudreClasseCible($type, $data);
        $echelon = $this->echelonDepart();

        $this->assertEligible($type, $agent, $data, $cible, $audit);

        if ($cible && $type->changeLaClasse()) {
            $this->assertLigneGrille($cible->id, $echelon);
        }

        if ($type === TypeReclassement::RECONVERSION && ! empty($data['classe_cible_id']) && $cible) {
            $this->assertLigneGrille($cible->id, $echelon);
        }

        return $this->repository->create([
            'agent_id'           => $agent->id,
            'type'               => $type->value,
            'statut'             => StatutReclassement::SOUMIS->value,
            'classe_origine_id'  => $audit['classe_id'],
            'classe_cible_id'    => $cible?->id,
            'fonction_cible_id'  => $data['fonction_cible_id'] ?? null,
            'diplome_id'         => $data['diplome_id'] ?? null,
            'motif'              => $data['motif'],
            'motif_reconversion' => $data['motif_reconversion'] ?? null,
            'piece_path'         => $data['piece_path'] ?? null,
            'age_ans'            => $audit['age_ans'],
            'anciennete_ans'     => $audit['anciennete_ans'],
            'annees_dans_classe' => $audit['annees_dans_classe'],
            'echelon_origine'    => $audit['echelon'],
            'echelon_cible'      => ($cible || ($type === TypeReclassement::RECONVERSION && ! empty($data['classe_cible_id'])))
                ? $echelon
                : null,
            'created_by'         => $rh->id,
        ]);
    }

    /** @throws ValidationException */
    public function approuver(int $id, User $user, ?string $commentaire = null): Reclassement
    {
        $dossier = $this->trouverSoumis($id);
        $this->assertPeutApprouver($user, $dossier->type);

        return $this->repository->update($id, [
            'statut'     => StatutReclassement::APPROUVE->value,
            'valide_par' => $user->id,
            'valide_at'  => now(),
            'motif'      => $commentaire ? $dossier->motif."\n\n[Approbation] ".$commentaire : $dossier->motif,
        ]);
    }

    /** @throws ValidationException */
    public function rejeter(int $id, User $user, ?string $commentaire = null): Reclassement
    {
        $dossier = $this->trouverSoumis($id);
        $this->assertPeutApprouver($user, $dossier->type);

        return $this->repository->update($id, [
            'statut'     => StatutReclassement::REJETE->value,
            'valide_par' => $user->id,
            'valide_at'  => now(),
            'motif'      => $commentaire ? $dossier->motif."\n\n[Rejet] ".$commentaire : $dossier->motif,
        ]);
    }

    /**
     * Applique en paie / fiche agent. Idempotent si déjà appliqué.
     *
     * @return array{applique: bool, message: string, data: Reclassement}
     *
     * @throws ValidationException
     */
    public function appliquer(int $id, User $rh): array
    {
        /** @var Reclassement $dossier */
        $dossier = $this->repository->findById($id);

        if ($dossier->estApplique()) {
            $dossier = $this->detail($id);

            return [
                'applique' => false,
                'message'  => 'Reclassement déjà appliqué (idempotent).',
                'data'     => $dossier,
            ];
        }

        if ($dossier->statut !== StatutReclassement::APPROUVE) {
            throw ValidationException::withMessages([
                'statut' => 'Seuls les reclassements approuvés peuvent être appliqués.',
            ]);
        }

        $agent = $this->agentRepository->findById((int) $dossier->agent_id);
        $agent->loadMissing(['grade', 'categorie']);
        $audit = $this->collecterAudit($agent);
        $cible = $dossier->classe_cible_id
            ? $this->classeRepository->findById((int) $dossier->classe_cible_id)->loadMissing(['grade', 'categorie'])
            : null;

        $this->assertEligible(
            $dossier->type,
            $agent,
            [
                'diplome_id'         => $dossier->diplome_id,
                'fonction_cible_id'  => $dossier->fonction_cible_id,
                'motif_reconversion' => $dossier->motif_reconversion?->value,
                'piece_path'         => $dossier->piece_path,
                'classe_cible_id'    => $dossier->classe_cible_id,
            ],
            $cible instanceof Classegrillesalariale ? $cible : null,
            $audit,
        );

        $motifPaie = sprintf('Art. %s — %s', $dossier->type->article(), $dossier->type->label());

        if ($dossier->classe_cible_id) {
            $echelon = $dossier->echelon_cible ?? $this->echelonDepart();
            $this->salaireService->changerClasse(
                (int) $dossier->agent_id,
                (int) $dossier->classe_cible_id,
                (int) $echelon,
                $dossier->type->typeChangementSalaire(),
                $motifPaie,
            );
        }

        if ($dossier->fonction_cible_id) {
            $this->agentRepository->update((int) $dossier->agent_id, [
                'fonction_id' => (int) $dossier->fonction_cible_id,
            ]);
        }

        $this->repository->update($id, [
            'statut'       => StatutReclassement::APPLIQUE->value,
            'applique_par' => $rh->id,
            'applique_at'  => now(),
        ]);

        return [
            'applique' => true,
            'message'  => 'Reclassement appliqué.',
            'data'     => $this->detail($id),
        ];
    }

    // ----------------------------------------------------------------
    // Règles CCN
    // ----------------------------------------------------------------

    /**
     * @param  array<string, mixed>  $data
     * @param  array{classe_id: ?int, classe: ?Classegrillesalariale, echelon: ?int, age_ans: int, anciennete_ans: int, annees_dans_classe: int, grade_nom: ?string}  $audit
     *
     * @throws ValidationException
     */
    private function assertEligible(
        TypeReclassement $type,
        Agent $agent,
        array $data,
        ?Classegrillesalariale $cible,
        array $audit,
    ): void {
        match ($type) {
            TypeReclassement::RECLASSEMENT_FORMATION    => $this->assertArt73($agent, $data, $cible, $audit),
            TypeReclassement::RECLASSEMENT_EXCEPTIONNEL => $this->assertArt74a($cible, $audit),
            TypeReclassement::HORS_CLASSE               => $this->assertArt74b($cible, $audit),
            TypeReclassement::RECONVERSION              => $this->assertArt75($data),
        };
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  array{classe_id: ?int, classe: ?Classegrillesalariale, echelon: ?int, age_ans: int, anciennete_ans: int, annees_dans_classe: int, grade_nom: ?string}  $audit
     */
    private function assertArt73(Agent $agent, array $data, ?Classegrillesalariale $cible, array $audit): void
    {
        if (empty($data['diplome_id'])) {
            throw ValidationException::withMessages([
                'diplome_id' => 'Un diplôme reconnu (art. 73) est obligatoire.',
            ]);
        }

        $infos = $this->infosProRepository->findByAgent((int) $agent->id);
        if (! $infos || (int) $infos->diplome_id !== (int) $data['diplome_id']) {
            throw ValidationException::withMessages([
                'diplome_id' => 'Le diplôme doit figurer au dossier professionnel de l\'agent.',
            ]);
        }

        if ($cible === null) {
            throw ValidationException::withMessages([
                'classe_cible_id' => 'Impossible de déterminer la classe cible à partir du diplôme.',
            ]);
        }

        $this->assertClasseSuperieure($audit['classe'] ?? null, $cible, 'classe_cible_id');
    }

    /**
     * @param  array{classe_id: ?int, classe: ?Classegrillesalariale, echelon: ?int, age_ans: int, anciennete_ans: int, annees_dans_classe: int, grade_nom: ?string}  $audit
     */
    private function assertArt74a(?Classegrillesalariale $cible, array $audit): void
    {
        $errors = [];
        if ($audit['age_ans'] < 50) {
            $errors['age'] = 'L\'agent doit avoir au moins 50 ans (art. 74).';
        }
        if ($audit['anciennete_ans'] < 15) {
            $errors['anciennete'] = 'L\'agent doit justifier d\'au moins 15 ans d\'ancienneté (art. 74).';
        }
        if ($audit['annees_dans_classe'] < 3) {
            $errors['classe'] = 'L\'agent doit compter au moins 3 ans dans la même classe (art. 74).';
        }
        if ($cible === null) {
            $errors['classe_cible_id'] = 'La classe supérieure cible est obligatoire.';
        }
        if ($cible && $this->estHorsClasse($cible)) {
            $errors['classe_cible_id'] = 'Le hors classe relève de l\'art. 74 (type hors_classe), pas du reclassement exceptionnel.';
        }
        if ($cible && ($audit['classe'] ?? null)) {
            try {
                $this->assertClasseSuperieure($audit['classe'], $cible, 'classe_cible_id');
            } catch (ValidationException $e) {
                $errors = array_merge($errors, $e->errors());
            }
        }
        if ($errors) {
            throw ValidationException::withMessages($errors);
        }
    }

    /**
     * @param  array{classe_id: ?int, classe: ?Classegrillesalariale, echelon: ?int, age_ans: int, anciennete_ans: int, annees_dans_classe: int, grade_nom: ?string}  $audit
     */
    private function assertArt74b(?Classegrillesalariale $cible, array $audit): void
    {
        $errors = [];
        if ($audit['anciennete_ans'] < 25) {
            $errors['anciennete'] = 'L\'agent doit justifier d\'au moins 25 ans d\'ancienneté (art. 74 hors classe).';
        }
        if (($audit['grade_nom'] ?? null) !== self::GRADE_INSPECTEUR_PRINCIPAL) {
            $errors['grade'] = 'Le hors classe est réservé au grade d\'inspecteur principal.';
        }
        if ((int) ($audit['echelon'] ?? 0) !== self::ECHELON_HORS_CLASSE) {
            $errors['echelon'] = 'L\'agent doit être au 8ᵉ échelon.';
        }
        if ($cible === null || ! $this->estHorsClasse($cible)) {
            $errors['classe_cible_id'] = 'La classe cible doit être la classe « Hors Classe ».';
        }
        if ($errors) {
            throw ValidationException::withMessages($errors);
        }
    }

    /** @param  array<string, mixed>  $data */
    private function assertArt75(array $data): void
    {
        $motif = MotifReconversion::tryFrom((string) ($data['motif_reconversion'] ?? ''));
        if ($motif === null) {
            throw ValidationException::withMessages([
                'motif_reconversion' => 'Motif requis : baisse_activite, reorganisation ou maladie.',
            ]);
        }
        if ($motif->exigePiece() && empty($data['piece_path'])) {
            throw ValidationException::withMessages([
                'piece_path' => 'Un certificat (médecin agréé) est obligatoire pour une reconversion pour maladie.',
            ]);
        }
        if (empty($data['fonction_cible_id'])) {
            throw ValidationException::withMessages([
                'fonction_cible_id' => 'La reconversion (art. 75) exige un nouvel emploi (fonction_cible_id).',
            ]);
        }
        $this->fonctionRepository->findById((int) $data['fonction_cible_id']);
    }

    private function assertClasseSuperieure(?Classegrillesalariale $origine, Classegrillesalariale $cible, string $field): void
    {
        $origine?->loadMissing('grade');
        $cible->loadMissing('grade');
        $niveauOrigine = (int) ($origine?->grade?->niveau ?? 0);
        $niveauCible   = (int) ($cible->grade?->niveau ?? 0);

        if ($niveauCible <= $niveauOrigine) {
            throw ValidationException::withMessages([
                $field => 'La classe cible doit être supérieure à la classe actuelle.',
            ]);
        }
    }

    private function assertLigneGrille(int $classeId, int $echelon): void
    {
        if ($this->salaireRepository->findByClasseAndEchelon($classeId, $echelon) === null) {
            throw ValidationException::withMessages([
                'classe_cible_id' => 'Aucune ligne de grille pour cette classe / échelon. Générez la grille (coefficient hors classe à saisir par la RH si besoin).',
            ]);
        }
    }

    private function assertPeutApprouver(User $user, TypeReclassement $type): void
    {
        if ($user->hasRole('admin')) {
            return;
        }

        if ($type->exigeApprobationDg()) {
            abort_unless(
                $user->hasRole('directeur-general'),
                403,
                'Seuls le directeur général ou un administrateur peuvent approuver ce reclassement (art. 74–75).'
            );

            return;
        }

        abort_unless(
            $user->hasRole('rh'),
            403,
            'Seule la RH peut approuver un reclassement après formation (art. 73).'
        );
    }

    /** @throws ValidationException */
    private function trouverSoumis(int $id): Reclassement
    {
        /** @var Reclassement $dossier */
        $dossier = $this->repository->findById($id);
        if ($dossier->statut !== StatutReclassement::SOUMIS) {
            throw ValidationException::withMessages([
                'statut' => 'Seule une demande soumise peut être approuvée ou rejetée.',
            ]);
        }

        return $dossier;
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  array{classe_id: ?int, classe: ?Classegrillesalariale, echelon: ?int, age_ans: int, anciennete_ans: int, annees_dans_classe: int, grade_nom: ?string}  $audit
     */
    private function resoudreClasseCible(TypeReclassement $type, array $data): ?Classegrillesalariale
    {
        if ($type === TypeReclassement::HORS_CLASSE) {
            $hors = $this->classeRepository->findByGradeNom(self::GRADE_HORS_CLASSE);
            if (! $hors) {
                throw ValidationException::withMessages([
                    'classe_cible_id' => 'La classe grille « Hors Classe » est absente des référentiels.',
                ]);
            }
            if (! empty($data['classe_cible_id']) && (int) $data['classe_cible_id'] !== (int) $hors->id) {
                throw ValidationException::withMessages([
                    'classe_cible_id' => 'Le type hors_classe doit cibler la classe « Hors Classe ».',
                ]);
            }

            return $hors->loadMissing(['grade', 'categorie']);
        }

        if ($type === TypeReclassement::RECLASSEMENT_FORMATION) {
            $diplomeId = (int) ($data['diplome_id'] ?? 0);
            if ($diplomeId < 1) {
                return null;
            }
            $diplome = $this->diplomeRepository->findById($diplomeId);
            $diplome->loadMissing('classeGrille.grade');
            if (! $diplome->classeGrille) {
                throw ValidationException::withMessages([
                    'diplome_id' => 'Ce diplôme n\'est pas rattaché à une classe de grille (diplôme non reconnu pour le reclassement).',
                ]);
            }
            if (! empty($data['classe_cible_id']) && (int) $data['classe_cible_id'] !== (int) $diplome->classegrillesalariale_id) {
                throw ValidationException::withMessages([
                    'classe_cible_id' => 'La classe cible doit correspondre à celle du diplôme.',
                ]);
            }

            return $diplome->classeGrille;
        }

        if ($type === TypeReclassement::RECONVERSION) {
            if (empty($data['classe_cible_id'])) {
                return null;
            }

            return $this->classeRepository
                ->findById((int) $data['classe_cible_id'])
                ->loadMissing(['grade', 'categorie']);
        }

        if (! empty($data['classe_cible_id'])) {
            return $this->classeRepository
                ->findById((int) $data['classe_cible_id'])
                ->loadMissing(['grade', 'categorie']);
        }

        return null;
    }

    /**
     * @return array{classe_id: ?int, classe: ?Classegrillesalariale, echelon: ?int, age_ans: int, anciennete_ans: int, annees_dans_classe: int, grade_nom: ?string}
     */
    private function collecterAudit(Agent $agent): array
    {
        $actuel = $this->salaireAgentRepository->getActuel((int) $agent->id);
        $classe = null;
        if ($actuel?->classegrillesalariale_id) {
            $classe = $this->classeRepository->findById((int) $actuel->classegrillesalariale_id);
            $classe->loadMissing(['grade', 'categorie']);
        }

        $debutClasse = $actuel?->date_debut;
        if ($actuel) {
            $premier = $this->salaireAgentRepository->getHistoriqueByAgent((int) $agent->id)
                ->first(fn ($s) => (int) $s->classegrillesalariale_id === (int) $actuel->classegrillesalariale_id);
            $debutClasse = $premier?->date_debut ?? $actuel->date_debut;
        }

        return [
            'classe_id'          => $actuel?->classegrillesalariale_id,
            'classe'             => $classe,
            'echelon'            => $actuel?->echelon,
            'age_ans'            => $this->anneesEntieres($agent->date_naissance),
            'anciennete_ans'     => $this->anneesEntieres($agent->date_prise_service),
            'annees_dans_classe' => $this->anneesEntieres($debutClasse),
            'grade_nom'          => $agent->grade?->nom ?? $classe?->grade?->nom,
        ];
    }

    private function anneesEntieres(mixed $depuis): int
    {
        if ($depuis === null) {
            return 0;
        }

        return (int) Carbon::parse($depuis)->startOfDay()->diffInYears(now()->startOfDay());
    }

    private function echelonDepart(): int
    {
        return max(1, (int) $this->parametreGrilleRepository->getCurrent()->echelon_depart);
    }

    private function estHorsClasse(Classegrillesalariale $classe): bool
    {
        $classe->loadMissing('grade');

        return $classe->grade?->nom === self::GRADE_HORS_CLASSE;
    }

    private function snapshotEligibilite(Reclassement $dossier): array
    {
        $agent = $this->agentRepository->findById((int) $dossier->agent_id);
        $agent->loadMissing(['grade', 'categorie']);
        $audit = $this->collecterAudit($agent);
        $cible = $dossier->classe_cible_id
            ? $this->classeRepository->findById((int) $dossier->classe_cible_id)->loadMissing(['grade'])
            : null;

        $ok = true;
        $messages = [];
        try {
            $this->assertEligible(
                $dossier->type,
                $agent,
                [
                    'diplome_id'         => $dossier->diplome_id,
                    'fonction_cible_id'  => $dossier->fonction_cible_id,
                    'motif_reconversion' => $dossier->motif_reconversion?->value,
                    'piece_path'         => $dossier->piece_path,
                    'classe_cible_id'    => $dossier->classe_cible_id,
                ],
                $cible instanceof Classegrillesalariale ? $cible : null,
                $audit,
            );
        } catch (ValidationException $e) {
            $ok = false;
            $messages = collect($e->errors())->flatten()->values()->all();
        }

        return [
            'ok'                 => $ok,
            'age_ans'            => $audit['age_ans'],
            'anciennete_ans'     => $audit['anciennete_ans'],
            'annees_dans_classe' => $audit['annees_dans_classe'],
            'messages'           => $messages,
        ];
    }

    private function prochaineEtape(Reclassement $dossier): ?string
    {
        return match ($dossier->statut) {
            StatutReclassement::SOUMIS   => 'approuver',
            StatutReclassement::APPROUVE => 'appliquer',
            default                      => null,
        };
    }
}
