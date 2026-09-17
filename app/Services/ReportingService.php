<?php

namespace App\Services;

use App\Enums\AxeRepartitionReporting;
use App\Enums\StatutAbsence;
use App\Enums\StatutAgent;
use App\Enums\StatutDemandeConge;
use App\Enums\StatutEvaluation;
use App\Enums\StatutSessionEvaluation;
use App\Interfaces\AffiliationSocialeInterface;
use App\Interfaces\NominationInterface;
use App\Interfaces\ReportingInterface;
use App\Models\Absence;
use App\Models\Agent;
use App\Models\Bureau;
use App\Models\Contrat;
use App\Models\DemandeConge;
use App\Models\Direction;
use App\Models\Evaluation;
use App\Models\PaieLot;
use App\Models\Service;
use App\Models\SessionEvaluation;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class ReportingService
{
    public const ALERTE_LIMITE = 50;

    public function __construct(
        private readonly ReportingInterface $repository,
        private readonly AffiliationSocialeInterface $affiliationRepository,
        private readonly NominationInterface $nominationRepository,
    ) {}

    public function dashboard(array $filters): array
    {
        $annee = $this->annee($filters);
        $tous = $this->repository->agents($filters, false);
        $presents = $tous->filter(fn (Agent $agent) => in_array($agent->statut, StatutAgent::effectifPresent(), true));

        $debut = Carbon::create($annee, 1, 1)->startOfDay();
        $fin = Carbon::create($annee, 12, 31)->endOfDay();

        return [
            'annee' => $annee,
            'effectif' => [
                'total' => $presents->count(),
                'stagiaires' => $presents->where('statut', StatutAgent::STAGIAIRE->value)->count(),
                'suspendus' => $presents->where('statut', StatutAgent::SUSPENDU->value)->count(),
                'actifs' => $presents->where('statut', StatutAgent::ACTIF->value)->count(),
            ],
            'repartition_statuts' => $this->compterParClef($tous, fn (Agent $agent) => $agent->statut ?: 'inconnu', StatutAgent::cases()),
            'mouvements' => [
                'entrees' => $tous->filter(function (Agent $agent) use ($debut, $fin) {
                    return $agent->date_prise_service !== null
                        && $agent->date_prise_service->betweenIncluded($debut, $fin);
                })->count(),
                'sorties' => $tous->filter(function (Agent $agent) use ($debut, $fin) {
                    return $agent->archived_at !== null
                        && $agent->archived_at->betweenIncluded($debut, $fin);
                })->count(),
            ],
            'masse_salariale' => $this->carteMasse($this->repository->dernierLotCloture()),
            'repartitions' => $this->toutesRepartitions($presents),
        ];
    }

    public function effectifs(array $filters): LengthAwarePaginator
    {
        if (! empty($filters['statut']) && in_array($filters['statut'], StatutAgent::effectifPresent(), true)) {
            return $this->repository->paginerAgents($filters, true);
        }

        return $this->repository->paginerAgents($filters, empty($filters['statut']));
    }

    public function repartition(string $axe, array $filters): array
    {
        $enum = AxeRepartitionReporting::from($axe);
        $presentOnly = $enum !== AxeRepartitionReporting::STATUT;
        $agents = $this->repository->agents($filters, $presentOnly);

        return [
            'axe' => $enum->value,
            'libelle' => $enum->label(),
            'items' => $this->repartitionSelonAxe($enum, $agents),
        ];
    }

    public function statsConges(array $filters): array
    {
        $annee = $this->annee($filters);
        $demandes = $this->repository->demandesConge($annee);
        $absences = $this->repository->absences($annee);
        $aujourdHui = now()->startOfDay();

        $parStatut = [];
        foreach (StatutDemandeConge::cases() as $statut) {
            $parStatut[$statut->value] = $demandes->where('statut', $statut)->count();
        }

        $accordees = $demandes->filter(fn (DemandeConge $demande) => $demande->typeConge?->estAccordee($demande->statut) ?? false);

        $parType = $demandes->groupBy(fn (DemandeConge $demande) => $demande->typeConge?->nom ?? 'inconnu')
            ->map(fn (Collection $groupe, string $libelle) => [
                'cle' => $libelle,
                'libelle' => $libelle,
                'total' => $groupe->count(),
                'jours' => (int) $groupe->sum('nb_jours'),
            ])
            ->values();

        $enCoursAujourdhui = $accordees->filter(function (DemandeConge $demande) use ($aujourdHui) {
            return $demande->date_debut !== null
                && $demande->date_fin !== null
                && $aujourdHui->betweenIncluded($demande->date_debut->startOfDay(), $demande->date_fin->endOfDay());
        });

        $absencesParStatut = [];
        foreach (StatutAbsence::cases() as $statut) {
            $absencesParStatut[$statut->value] = $absences->where('statut', $statut)->count();
        }

        $absencesParType = $absences->groupBy(fn (Absence $absence) => $absence->typeAbsence?->nom ?? 'inconnu')
            ->map(fn (Collection $groupe, string $libelle) => [
                'cle' => $libelle,
                'libelle' => $libelle,
                'total' => $groupe->count(),
                'jours' => (int) $groupe->sum('nb_jours'),
            ])
            ->values();

        return [
            'annee' => $annee,
            'demandes' => [
                'total' => $demandes->count(),
                'par_statut' => $parStatut,
                'jours_poses' => (int) $demandes->sum('nb_jours'),
                'jours_accordes' => (int) $accordees->sum('nb_jours'),
                'par_type' => $parType,
                'en_conge_aujourd_hui' => $enCoursAujourdhui->count(),
            ],
            'absences' => [
                'total' => $absences->count(),
                'par_statut' => $absencesParStatut,
                'par_type' => $absencesParType,
            ],
        ];
    }

    public function statsEvaluations(array $filters): array
    {
        $annee = $this->annee($filters);
        $sessions = $this->repository->sessions($annee);
        $fiches = $this->repository->evaluations($annee);
        $courante = $this->repository->sessionCourante();

        return [
            'annee' => $this->blocEvaluations($annee, $sessions, $fiches),
            'session_courante' => $courante instanceof SessionEvaluation
                ? $this->blocSession($courante, $this->repository->evaluationsDeSession($courante->id))
                : null,
        ];
    }

    public function alertes(): array
    {
        $sansN1 = $this->repository->agentsSansN1();
        $incomplets = $this->repository->dossiersIncomplets();
        $sansCnss = $this->affiliationRepository->agentsSansAffiliationCnss();
        $contrats30 = $this->repository->contratsEcheance(30);
        $contrats60 = $this->repository->contratsEcheance(60);
        $postes = $this->nominationRepository->postesVacants();

        return [
            $this->alerte('sans_n1', 'Agents sans supérieur hiérarchique', $sansN1, fn (Agent $agent) => $this->ligneAgent($agent)),
            $this->alerte('dossier_incomplet', 'Dossiers incomplets (infos perso / pro / contact d\'urgence)', $incomplets, fn (Agent $agent) => $this->ligneAgent($agent)),
            $this->alerte('sans_affiliation_cnss', 'Agents sans affiliation CNSS active', $sansCnss, fn (Agent $agent) => $this->ligneAgent($agent)),
            $this->alerte('contrat_echeance_30', 'Contrats à échéance dans 30 jours', $contrats30, fn (Contrat $contrat) => $this->ligneContrat($contrat)),
            $this->alerte('contrat_echeance_60', 'Contrats à échéance dans 60 jours', $contrats60, fn (Contrat $contrat) => $this->ligneContrat($contrat)),
            $this->alerte('poste_vacant', 'Postes de responsabilité vacants', $postes, function (mixed $poste) {
                return [
                    'structurable_type' => $poste['structurable_type'] ?? null,
                    'structurable_id' => $poste['structurable_id'] ?? null,
                    'nom' => $poste['nom'] ?? null,
                    'type' => $poste['type'] ?? null,
                    'postes_possibles' => $poste['postes_possibles'] ?? [],
                ];
            }),
        ];
    }

    public function effectifsPourExport(array $filters): Collection
    {
        return $this->repository->agents($filters, true);
    }

    public function demandesCongePourExport(int $annee): Collection
    {
        return $this->repository->demandesConge($annee);
    }

    public function evaluationsPourExport(array $filters): array
    {
        $portee = $filters['portee'] ?? 'annee';

        if ($portee === 'session') {
            $session = $this->repository->sessionCourante(
                isset($filters['session_id']) ? (int) $filters['session_id'] : null
            );
            abort_unless($session instanceof SessionEvaluation, 422, 'Aucune session d\'évaluation à exporter.');

            return [
                'portee' => 'session',
                'session' => $session,
                'fiches' => $this->repository->evaluationsDeSession($session->id),
            ];
        }

        $annee = $this->annee($filters);

        return [
            'portee' => 'annee',
            'annee' => $annee,
            'session' => null,
            'fiches' => $this->repository->evaluations($annee),
        ];
    }

    public function annee(array $filters): int
    {
        return (int) ($filters['annee'] ?? now()->year);
    }

    public function ligneEffectif(Agent $agent): array
    {
        $structure = $this->structureAgent($agent);

        return [
            'id' => $agent->id,
            'matricule' => $agent->matricule,
            'nom' => $agent->nom,
            'prenom' => $agent->prenom,
            'nom_complet' => $agent->nom_complet,
            'statut' => $agent->statut,
            'genre' => $agent->genre ?: 'inconnu',
            'age' => $agent->date_naissance?->age,
            'date_naissance' => $agent->date_naissance?->format('Y-m-d'),
            'date_prise_service' => $agent->date_prise_service?->format('Y-m-d'),
            'grade' => $agent->grade?->nom,
            'fonction' => $agent->fonction?->nom,
            'type_integration' => $agent->typeIntegration?->nom,
            'direction' => $structure['direction'],
            'service' => $structure['service'],
            'bureau' => $structure['bureau'],
        ];
    }

    /** @param  Collection<int, mixed>  $items */
    private function alerte(string $code, string $libelle, Collection $items, callable $mapper): array
    {
        return [
            'code' => $code,
            'libelle' => $libelle,
            'total' => $items->count(),
            'items' => $items->take(self::ALERTE_LIMITE)->map($mapper)->values(),
        ];
    }

    private function ligneAgent(Agent $agent): array
    {
        return [
            'id' => $agent->id,
            'matricule' => $agent->matricule,
            'nom' => $agent->nom,
            'prenom' => $agent->prenom,
            'nom_complet' => $agent->nom_complet,
            'statut' => $agent->statut,
        ];
    }

    private function ligneContrat(Contrat $contrat): array
    {
        return [
            'id' => $contrat->id,
            'date_fin' => $contrat->date_fin?->format('Y-m-d'),
            'type' => $contrat->typeContrat?->nom,
            'agent' => $contrat->agent ? $this->ligneAgent($contrat->agent) : null,
        ];
    }

    private function carteMasse(?PaieLot $lot): ?array
    {
        if (! $lot instanceof PaieLot) {
            return null;
        }

        return [
            'lot_id' => $lot->id,
            'annee' => $lot->annee,
            'mois' => $lot->mois,
            'periode' => $lot->periodeLabel(),
            'total_gains' => (float) $lot->total_gains,
            'total_retenues' => (float) $lot->total_retenues,
            'total_net' => (float) $lot->total_net,
            'nb_lignes' => (int) $lot->nb_lignes,
        ];
    }

    private function toutesRepartitions(Collection $presents): array
    {
        $result = [];
        foreach (AxeRepartitionReporting::cases() as $axe) {
            if ($axe === AxeRepartitionReporting::STATUT) {
                continue;
            }
            $result[$axe->value] = $this->repartitionSelonAxe($axe, $presents);
        }

        return $result;
    }

    private function repartitionSelonAxe(AxeRepartitionReporting $axe, Collection $agents): array
    {
        return match ($axe) {
            AxeRepartitionReporting::GENRE => $this->bucketsFixes($agents, fn (Agent $agent) => match ($agent->genre) {
                'M' => 'M',
                'F' => 'F',
                default => 'inconnu',
            }, [
                ['cle' => 'M', 'libelle' => 'Hommes'],
                ['cle' => 'F', 'libelle' => 'Femmes'],
                ['cle' => 'inconnu', 'libelle' => 'Non renseigné'],
            ]),
            AxeRepartitionReporting::AGE => $this->bucketsFixes($agents, fn (Agent $agent) => $this->trancheAge($agent), [
                ['cle' => 'moins_25', 'libelle' => 'Moins de 25 ans'],
                ['cle' => '25_34', 'libelle' => '25–34 ans'],
                ['cle' => '35_44', 'libelle' => '35–44 ans'],
                ['cle' => '45_54', 'libelle' => '45–54 ans'],
                ['cle' => '55_plus', 'libelle' => '55 ans et plus'],
                ['cle' => 'inconnu', 'libelle' => 'Âge inconnu'],
            ]),
            AxeRepartitionReporting::STATUT => $this->compterParClef($agents, fn (Agent $agent) => $agent->statut ?: 'inconnu', StatutAgent::cases()),
            AxeRepartitionReporting::DIRECTION => $this->compterStructures($agents, 'direction'),
            AxeRepartitionReporting::GRADE => $this->compterRelation($agents, fn (Agent $agent) => [
                'cle' => (string) ($agent->grade_id ?? 'inconnu'),
                'libelle' => $agent->grade?->nom ?? 'Non renseigné',
            ]),
            AxeRepartitionReporting::FONCTION => $this->compterRelation($agents, fn (Agent $agent) => [
                'cle' => (string) ($agent->fonction_id ?? 'inconnu'),
                'libelle' => $agent->fonction?->nom ?? 'Non renseigné',
            ]),
            AxeRepartitionReporting::TYPE_INTEGRATION => $this->compterRelation($agents, fn (Agent $agent) => [
                'cle' => (string) ($agent->type_integration_id ?? 'inconnu'),
                'libelle' => $agent->typeIntegration?->nom ?? 'Non renseigné',
            ]),
        };
    }

    /**
     * @param  list<\BackedEnum>  $cases
     */
    private function compterParClef(Collection $agents, callable $clef, array $cases): array
    {
        $items = [];
        foreach ($cases as $case) {
            $items[] = [
                'cle' => $case->value,
                'libelle' => $case->label(),
                'total' => $agents->where('statut', $case->value)->count(),
            ];
        }

        return $items;
    }

    /** @param  list<array{cle: string, libelle: string}>  $ordre */
    private function bucketsFixes(Collection $agents, callable $clef, array $ordre): array
    {
        $groupes = $agents->groupBy($clef);

        return array_map(fn (array $bucket) => [
            'cle' => $bucket['cle'],
            'libelle' => $bucket['libelle'],
            'total' => $groupes->get($bucket['cle'], collect())->count(),
        ], $ordre);
    }

    private function compterRelation(Collection $agents, callable $extracteur): array
    {
        return $agents
            ->groupBy(fn (Agent $agent) => $extracteur($agent)['cle'])
            ->map(function (Collection $groupe) use ($extracteur) {
                $meta = $extracteur($groupe->first());

                return [
                    'cle' => $meta['cle'],
                    'libelle' => $meta['libelle'],
                    'total' => $groupe->count(),
                ];
            })
            ->sortByDesc('total')
            ->values()
            ->all();
    }

    private function compterStructures(Collection $agents, string $niveau): array
    {
        return $agents
            ->groupBy(fn (Agent $agent) => $this->structureAgent($agent)[$niveau] ?? 'Non affecté')
            ->map(fn (Collection $groupe, string $libelle) => [
                'cle' => $libelle,
                'libelle' => $libelle,
                'total' => $groupe->count(),
            ])
            ->sortByDesc('total')
            ->values()
            ->all();
    }

    /** @return array{direction: string, service: ?string, bureau: ?string} */
    private function structureAgent(Agent $agent): array
    {
        $structure = $agent->affectationActive?->structure;

        if ($structure instanceof Direction) {
            return ['direction' => $structure->nom, 'service' => null, 'bureau' => null];
        }

        if ($structure instanceof Service) {
            return [
                'direction' => $structure->direction?->nom ?? 'Non affecté',
                'service' => $structure->nom,
                'bureau' => null,
            ];
        }

        if ($structure instanceof Bureau) {
            return [
                'direction' => $structure->service?->direction?->nom ?? 'Non affecté',
                'service' => $structure->service?->nom,
                'bureau' => $structure->nom,
            ];
        }

        return ['direction' => 'Non affecté', 'service' => null, 'bureau' => null];
    }

    private function trancheAge(Agent $agent): string
    {
        $age = $agent->date_naissance?->age;
        if ($age === null) {
            return 'inconnu';
        }
        if ($age < 25) {
            return 'moins_25';
        }
        if ($age <= 34) {
            return '25_34';
        }
        if ($age <= 44) {
            return '35_44';
        }
        if ($age <= 54) {
            return '45_54';
        }

        return '55_plus';
    }

    private function blocEvaluations(int $annee, Collection $sessions, Collection $fiches): array
    {
        $parStatutSession = [];
        foreach (StatutSessionEvaluation::cases() as $statut) {
            $parStatutSession[$statut->value] = $sessions->where('statut', $statut)->count();
        }

        return [
            'annee' => $annee,
            'sessions' => [
                'total' => $sessions->count(),
                'par_statut' => $parStatutSession,
            ],
            ...$this->resumeFiches($fiches),
        ];
    }

    private function blocSession(SessionEvaluation $session, Collection $fiches): array
    {
        return [
            'id' => $session->id,
            'statut' => $session->statut?->value,
            'debut_session' => $session->debut_session?->format('Y-m-d'),
            'fin_session' => $session->fin_session?->format('Y-m-d'),
            ...$this->resumeFiches($fiches),
        ];
    }

    private function resumeFiches(Collection $fiches): array
    {
        $parStatut = [];
        foreach (StatutEvaluation::cases() as $statut) {
            $parStatut[$statut->value] = $fiches->where('statut', $statut)->count();
        }

        $notees = $fiches->filter(fn (Evaluation $fiche) => $fiche->note_globale !== null);
        $mentions = $notees->groupBy(fn (Evaluation $fiche) => $fiche->mention ?: 'sans_mention')
            ->map(fn (Collection $groupe, string $mention) => [
                'cle' => $mention,
                'libelle' => $mention,
                'total' => $groupe->count(),
            ])
            ->values();

        return [
            'fiches' => [
                'total' => $fiches->count(),
                'par_statut' => $parStatut,
                'moyenne' => $notees->isEmpty() ? null : round((float) $notees->avg('note_globale'), 2),
                'mentions' => $mentions,
            ],
        ];
    }
}
