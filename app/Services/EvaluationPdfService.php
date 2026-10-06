<?php

namespace App\Services;

use App\Enums\NiveauAvisHierarchique;
use App\Enums\StatutEvaluation;
use App\Interfaces\AbsenceInterface;
use App\Interfaces\CommissionPreparatoireInterface;
use App\Interfaces\EvaluationInterface;
use App\Interfaces\SanctionInterface;
use App\Models\Affectation;
use App\Models\AvisHierarchique;
use App\Models\Bureau;
use App\Models\CommissionPreparatoire;
use App\Models\Direction;
use App\Models\Evaluation;
use App\Models\Sanction;
use App\Models\Service;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

class EvaluationPdfService
{
    /** @var array<string, string> */
    private const BLOCS = [
        'competence_pro'   => 'Compétences professionnelles et techniques',
        'assiduite'        => 'Assiduité au travail',
        'relation_sociale' => 'Relations sociales',
    ];

    public function __construct(
        private readonly EvaluationInterface             $evaluationRepository,
        private readonly CommissionPreparatoireInterface $preparatoireRepository,
        private readonly AbsenceInterface                $absenceRepository,
        private readonly SanctionInterface               $sanctionRepository,
    ) {}

    public function fichePdf(int $evaluationId, User $user): Response
    {
        $fiche = $this->evaluationRepository->findById($evaluationId);
        $fiche->load([
            'agent.grade',
            'agent.fonction',
            'superieur.grade',
            'superieur.fonction',
            'session',
            'affectationNotation.structure' => function (MorphTo $morphTo) {
                $morphTo->morphWith([
                    Bureau::class  => ['service.direction'],
                    Service::class => ['direction'],
                ]);
            },
            'notes.question',
            'avisHierarchiques.signePar.agent',
            'reclamation',
            'connaissancesComplementaires',
        ]);

        $this->assertPeutLireFiche($user, $fiche);

        if (! $fiche->statut->peutTelechargerFichePdf()) {
            throw ValidationException::withMessages([
                'statut' => 'La fiche PDF n\'est disponible qu\'après signature de l\'agent (prise de connaissance).',
            ]);
        }

        $matricule = $fiche->agent?->matricule ?? $fiche->agent_id;
        $sessionId = $fiche->session_id;

        return Pdf::loadView('pdf.fiche-evaluation', [
            'doc' => $this->donneesFiche($fiche),
        ])->setPaper('a4', 'portrait')
            ->stream("fiche-evaluation-{$sessionId}-{$matricule}.pdf");
    }

    public function synthesePdf(int $commissionId, User $user): Response
    {
        $this->assertPeutLireSynthese($user);

        /** @var CommissionPreparatoire $commission */
        $commission = $this->preparatoireRepository->findById($commissionId);
        $commission->load('session');

        if (! $commission->statut->estClose()) {
            throw ValidationException::withMessages([
                'statut' => 'La note de synthèse PDF n\'est disponible qu\'après clôture de la commission préparatoire.',
            ]);
        }

        $fiches = $this->evaluationRepository->getBySession((int) $commission->session_id)
            ->filter(fn (Evaluation $e) => $e->statut === StatutEvaluation::FINALISEE)
            ->values();

        return Pdf::loadView('pdf.note-synthese-preparatoire', [
            'commission' => $commission,
            'fiches'     => $fiches,
        ])->stream("note-synthese-session-{$commission->session_id}.pdf");
    }

    /**
     * Données d'affichage de la fiche officielle.
     * Absences et sanctions sont lues sur la période de la session d'évaluation.
     *
     * @return array<string, mixed>
     */
    private function donneesFiche(Evaluation $fiche): array
    {
        $au = $fiche->date_evaluation ?? $fiche->session?->fin_session ?? now();
        $poste = $this->posteNotation($fiche->affectationNotation);
        $suivi = $this->suiviAdministratif($fiche);

        return [
            'titre'            => "FICHE INDIVIDUELLE D'EVALUATION",
            'session'          => $this->libelleSession($fiche),
            'date_evaluation'  => $fiche->date_evaluation?->format('d/m/Y')
                ?? $fiche->created_at?->format('d/m/Y')
                ?? '—',
            'photo'            => $this->cheminPhoto($fiche->agent?->photo_path),
            'agent'            => [
                'nom'        => $fiche->agent?->nom ?? '—',
                'prenom'     => $fiche->agent?->prenom ?? '—',
                'matricule'  => $fiche->agent?->matricule ?? '—',
                'grade'      => $fiche->agent?->grade?->nom ?? '—',
                'fonction'   => $fiche->agent?->fonction?->nom ?? '—',
                'anciennete' => $this->libelleAnciennete($fiche->agent?->date_prise_service, $au),
                'direction'  => $poste['direction'],
                'service'    => $poste['service'],
                'bureau'     => $poste['bureau'],
            ],
            'superieur'        => [
                'nom_complet'    => trim(($fiche->superieur?->nom ?? '').' '.($fiche->superieur?->prenom ?? '')) ?: '—',
                'fonction_grade' => trim(
                    ($fiche->superieur?->fonction?->nom ?? '—').' / '.($fiche->superieur?->grade?->nom ?? '—'),
                    ' /'
                ),
            ],
            'jours_absence'    => $suivi['jours_absence'],
            'sanctions'        => $suivi['sanctions'],
            'blocs'            => $this->blocsNotation($fiche),
            'note_globale'     => $fiche->note_globale !== null ? $this->formatNote((float) $fiche->note_globale) : '—',
            'mention'          => $fiche->mention,
            'connaissances'    => $fiche->connaissancesComplementaires
                ->map(fn ($item) => $item->description ?: $item->domaine)
                ->filter()
                ->values()
                ->all(),
            'avis_notateur'    => filled($fiche->avis_superieur) ? [
                'texte' => $fiche->avis_superieur,
                'date'  => $fiche->signe_par_evaluateur_at?->format('d/m/Y'),
            ] : null,
            'avis'             => $fiche->avisHierarchiques
                ->map(fn (AvisHierarchique $avis) => $this->ligneAvis($avis))
                ->all(),
            'reclamation'      => $fiche->reclamation?->motif,
            'signature_evaluateur' => $fiche->signe_par_evaluateur_at?->format('d/m/Y'),
            'signature_evalue' => $fiche->signe_par_evalue_at?->format('d/m/Y'),
            'commission'       => $this->commission($fiche),
        ];
    }

    /**
     * @return array{jours_absence: int, sanctions: list<array{nature: string, nombre: int}>}
     */
    private function suiviAdministratif(Evaluation $fiche): array
    {
        $debut = $fiche->session?->debut_session?->toDateString();
        $fin = $fiche->session?->fin_session?->toDateString();
        $agentId = $fiche->agent_id ? (int) $fiche->agent_id : null;

        if ($agentId && $debut && $fin) {
            $sanctions = $this->sanctionRepository
                ->getPrononceesEntre($agentId, $debut, $fin)
                ->groupBy(fn (Sanction $sanction) => $sanction->typeSanction?->nom ?? 'Sanction')
                ->map(fn (Collection $groupe, string $nature) => [
                    'nature' => $nature,
                    'nombre' => $groupe->count(),
                ])
                ->values()
                ->all();

            return [
                'jours_absence' => $this->absenceRepository->sommeJoursNonJustifiesEntre($agentId, $debut, $fin),
                'sanctions'     => $sanctions,
            ];
        }

        return [
            'jours_absence' => (int) $fiche->jours_absence_non_justifiee,
            'sanctions'     => filled($fiche->sanctions)
                ? [['nature' => (string) $fiche->sanctions, 'nombre' => 1]]
                : [],
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function blocsNotation(Evaluation $fiche): array
    {
        $notes = $fiche->notes->sortBy(fn ($note) => $note->question?->ordre ?? PHP_INT_MAX);
        $blocs = [];
        $numero = 1;

        foreach (self::BLOCS as $type => $titre) {
            $lignes = $notes
                ->filter(fn ($note) => $note->question?->type_critere === $type)
                ->map(fn ($note) => [
                    'libelle' => $note->question?->libelle ?? ('Critère #'.$note->question_id),
                    'note'    => $this->formatNote((float) $note->note_obtenue),
                    'bareme'  => '0 à '.$this->formatNote((float) ($note->question?->bareme_max ?? 0)),
                ])
                ->values();

            $total = (float) $notes
                ->filter(fn ($note) => $note->question?->type_critere === $type)
                ->sum('note_obtenue');
            $bareme = (float) $notes
                ->filter(fn ($note) => $note->question?->type_critere === $type)
                ->sum(fn ($note) => (float) ($note->question?->bareme_max ?? 0));

            $blocs[] = [
                'numero' => (string) $numero,
                'titre'  => $titre,
                'saut'   => false,
                'lignes' => $lignes->all(),
                'total'  => $this->formatNote($total),
                'bareme' => $this->formatNote($bareme),
            ];
            $numero++;
        }

        return $blocs;
    }

    /**
     * @return array{direction: string, service: string, bureau: string}
     */
    private function posteNotation(?Affectation $affectation): array
    {
        $vide = ['direction' => '—', 'service' => '—', 'bureau' => '—'];
        $structure = $affectation?->structure;

        if ($structure instanceof Bureau) {
            return [
                'direction' => $structure->service?->direction?->nom ?? '—',
                'service'   => $structure->service?->sigle ?: ($structure->service?->nom ?? '—'),
                'bureau'    => $structure->sigle ?: $structure->nom,
            ];
        }

        if ($structure instanceof Service) {
            return [
                'direction' => $structure->direction?->nom ?? '—',
                'service'   => $structure->sigle ?: $structure->nom,
                'bureau'    => '—',
            ];
        }

        if ($structure instanceof Direction) {
            return [
                'direction' => $structure->nom,
                'service'   => '—',
                'bureau'    => '—',
            ];
        }

        return $vide;
    }

    /**
     * @return array{titre: string, texte: string, signataire: string, date: ?string}
     */
    private function ligneAvis(AvisHierarchique $avis): array
    {
        $agent = $avis->signePar?->agent;
        $signataire = $agent
            ? trim($agent->nom.' '.$agent->prenom)
            : (string) ($avis->signePar?->name ?? '');

        $texte = trim((string) $avis->avis);
        if (filled($avis->observations)) {
            $texte = trim($texte."\n".$avis->observations);
        }

        return [
            'titre'       => $this->titreAvis($avis->niveau),
            'texte'       => $texte !== '' ? $texte : '—',
            'signataire'  => $signataire !== '' ? $signataire : '—',
            'date'        => $avis->date_signature?->format('d/m/Y'),
        ];
    }

    private function titreAvis(?NiveauAvisHierarchique $niveau): string
    {
        return match ($niveau) {
            NiveauAvisHierarchique::CHEF_BUREAU       => 'Le Chef de bureau',
            NiveauAvisHierarchique::CHEF_SERVICE      => 'Le Chef de service',
            NiveauAvisHierarchique::DIRECTEUR         => 'Le Directeur',
            NiveauAvisHierarchique::DIRECTEUR_GENERAL => 'Le Directeur général',
            default                                   => 'Avis hiérarchique',
        };
    }

    /**
     * @return array<string, mixed>|null
     */
    private function commission(Evaluation $fiche): ?array
    {
        $aUneNote = $fiche->commission_note !== null || $fiche->note_avancement !== null;
        $aUneSynthese = filled($fiche->note_synthese);
        $aUneDecision = $fiche->commission_decision !== null;

        if (! $aUneNote && ! $aUneSynthese && ! $aUneDecision) {
            return null;
        }

        $lignes = [];
        if ($fiche->commission_note !== null) {
            $lignes[] = [
                'libelle' => 'Note harmonisée de la commission préparatoire',
                'note'    => $this->formatNote((float) $fiche->commission_note),
                'bareme'  => '/ 20',
            ];
        }
        if ($fiche->note_avancement !== null) {
            $lignes[] = [
                'libelle' => 'Note à l\'avancement',
                'note'    => $this->formatNote((float) $fiche->note_avancement),
                'bareme'  => '/ 20',
            ];
        }

        $decision = $fiche->commission_decision?->label();
        if ($decision && $fiche->nombre_echelons) {
            $decision .= ' — '.$fiche->nombre_echelons.' échelon(s)';
        }

        return [
            'lignes'    => $lignes,
            'synthese'  => $aUneSynthese ? (string) $fiche->note_synthese : null,
            'decision'  => $decision,
        ];
    }

    private function libelleSession(Evaluation $fiche): ?string
    {
        $debut = $fiche->session?->debut_session?->format('d/m/Y');
        $fin = $fiche->session?->fin_session?->format('d/m/Y');

        if (! $debut && ! $fin) {
            return null;
        }

        return 'Session du '.($debut ?? '—').' au '.($fin ?? '—');
    }

    private function libelleAnciennete(?CarbonInterface $prise, CarbonInterface $au): string
    {
        if ($prise === null) {
            return '—';
        }

        $diff = $prise->copy()->startOfDay()->diff($au->copy()->startOfDay());
        $morceaux = [];

        if ($diff->y > 0) {
            $morceaux[] = $diff->y.' an'.($diff->y > 1 ? 's' : '');
        }
        if ($diff->m > 0) {
            $morceaux[] = $diff->m.' mois';
        }
        if ($morceaux === []) {
            $morceaux[] = $diff->d.' jour'.($diff->d > 1 ? 's' : '');
        }

        return implode(' ', $morceaux);
    }

    private function formatNote(float $valeur): string
    {
        $formate = number_format($valeur, 2, ',', ' ');

        return rtrim(rtrim($formate, '0'), ',');
    }

    private function cheminPhoto(?string $photoPath): ?string
    {
        if ($photoPath === null || $photoPath === '') {
            return null;
        }

        foreach ([
            public_path($photoPath),
            public_path('storage/'.$photoPath),
            storage_path('app/public/'.$photoPath),
            storage_path('app/'.$photoPath),
        ] as $chemin) {
            if (is_file($chemin)) {
                return $chemin;
            }
        }

        return null;
    }

    private function assertPeutLireFiche(User $user, Evaluation $fiche): void
    {
        if ($this->estRhOuDg($user)) {
            return;
        }

        $agentId = $user->agent_id ? (int) $user->agent_id : null;
        if ($agentId && ($agentId === (int) $fiche->agent_id || $agentId === (int) $fiche->superieur_id)) {
            return;
        }

        abort(403, 'Vous n\'êtes pas autorisé à télécharger cette fiche.');
    }

    private function assertPeutLireSynthese(User $user): void
    {
        if ($this->estRhOuDg($user)) {
            return;
        }

        abort(403, 'Seule la RH ou le directeur général peut télécharger la note de synthèse.');
    }

    private function estRhOuDg(User $user): bool
    {
        return $user->hasRole('admin')
            || $user->hasRole('rh')
            || $user->hasRole('directeur-general');
    }
}
