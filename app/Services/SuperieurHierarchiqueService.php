<?php

namespace App\Services;

use App\Interfaces\AffectationInterface;
use App\Interfaces\UserInterface;
use App\Models\Affectation;
use App\Models\SessionEvaluation;
use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

class SuperieurHierarchiqueService
{
    public function __construct(
        private readonly AffectationInterface $affectationRepository,
        private readonly UserInterface $userRepository,
    ) {}

    public function estN1(User $user, int $agentId): bool
    {
        if ($user->hasRole('admin')) {
            return true;
        }

        $compte = $this->trouverCompteN1($agentId);

        return $compte instanceof User && (int) $compte->id === (int) $user->id;
    }

    public function assertEstN1(User $user, int $agentId): void
    {
        if ($user->hasRole('admin')) {
            return;
        }

        $n1 = $this->compteN1($agentId);
        abort_unless(
            (int) $n1->id === (int) $user->id,
            403,
            'Seul le supérieur hiérarchique de l\'agent (affectation active) peut valider au niveau N+1.'
        );
    }

    public function compteN1(int $agentId): User
    {
        $compte = $this->trouverCompteN1($agentId);
        abort_unless(
            $compte instanceof User,
            422,
            $this->affectationRepository->getActive($agentId)?->superieur_hierarchique_id
                ? 'Le supérieur hiérarchique n\'a pas de compte utilisateur.'
                : 'Aucune affectation active avec supérieur hiérarchique : le N+1 ne peut pas être déterminé.'
        );

        return $compte;
    }

    public function trouverCompteN1(int $agentId): ?User
    {
        $affectation = $this->affectationRepository->getActive($agentId);
        if (! $affectation?->superieur_hierarchique_id) {
            return null;
        }

        return $this->userRepository->findByAgentId((int) $affectation->superieur_hierarchique_id);
    }

    /**
     * Période de notation art. 62 : [debut_session − 24 mois, debut_session).
     *
     * @return array{0: Carbon, 1: Carbon}
     */
    public function periodeNotation(SessionEvaluation $session): array
    {
        $fin   = Carbon::parse($session->debut_session)->startOfDay();
        $debut = $fin->copy()->subMonths(24);

        return [$debut, $fin];
    }

    /**
     * Affectation où l'agent a servi le plus longtemps sur la période.
     * Égalité : date d'affectation la plus récente, puis id max.
     */
    public function affectationDominante(int $agentId, CarbonInterface $debut, CarbonInterface $fin): ?Affectation
    {
        $classees = $this->affectationsClasseesParDuree($agentId, $debut, $fin);

        return $classees->first()?->get('affectation');
    }

    /**
     * Notateur CCN art. 62 : N+1 du poste dominant, jamais l'agent lui-même.
     *
     * @return array{superieur_id: int, affectation: Affectation, duree_jours: int}|null
     */
    public function resoudreNotateurPourPeriode(int $agentId, CarbonInterface $debut, CarbonInterface $fin): ?array
    {
        foreach ($this->affectationsClasseesParDuree($agentId, $debut, $fin) as $item) {
            /** @var Affectation $affectation */
            $affectation = $item['affectation'];
            $superieurId = $this->notateurDeLAffectation($affectation);

            if ($superieurId === null || $superieurId === $agentId) {
                continue;
            }

            return [
                'superieur_id'  => $superieurId,
                'affectation'   => $affectation,
                'duree_jours'   => $item['duree_jours'],
            ];
        }

        $active = $this->affectationRepository->getActive($agentId);
        if ($active) {
            $superieurId = $this->notateurDeLAffectation($active);
            if ($superieurId !== null && $superieurId !== $agentId) {
                return [
                    'superieur_id' => $superieurId,
                    'affectation'  => $active,
                    'duree_jours'  => $this->dureeJours($active, $debut, $fin),
                ];
            }
        }

        return null;
    }

    public function notateurDeLAffectation(Affectation $affectation): ?int
    {
        if ($affectation->superieur_hierarchique_id) {
            return (int) $affectation->superieur_hierarchique_id;
        }

        if (! $affectation->structurable_type || ! $affectation->structurable_id) {
            return null;
        }

        return $this->affectationRepository->resoudreSuperiorParStructure(
            $affectation->structurable_type,
            (int) $affectation->structurable_id
        );
    }

    /**
     * @return Collection<int, array{affectation: Affectation, duree_jours: int}>
     */
    private function affectationsClasseesParDuree(int $agentId, CarbonInterface $debut, CarbonInterface $fin): Collection
    {
        return $this->affectationRepository
            ->getPourAgentSurPeriode($agentId, $debut->toDateString(), $fin->toDateString())
            ->map(fn (Affectation $affectation) => [
                'affectation' => $affectation,
                'duree_jours' => $this->dureeJours($affectation, $debut, $fin),
            ])
            ->filter(fn (array $item) => $item['duree_jours'] > 0)
            ->sort(function (array $a, array $b) {
                if ($a['duree_jours'] !== $b['duree_jours']) {
                    return $b['duree_jours'] <=> $a['duree_jours'];
                }

                $dateA = $a['affectation']->date_affectation?->timestamp ?? 0;
                $dateB = $b['affectation']->date_affectation?->timestamp ?? 0;
                if ($dateA !== $dateB) {
                    return $dateB <=> $dateA;
                }

                return $b['affectation']->id <=> $a['affectation']->id;
            })
            ->values();
    }

    private function dureeJours(Affectation $affectation, CarbonInterface $debut, CarbonInterface $fin): int
    {
        if (! $affectation->date_affectation) {
            return 0;
        }

        $affDebut = Carbon::parse($affectation->date_affectation)->startOfDay();
        $affFin   = $affectation->date_fin
            ? Carbon::parse($affectation->date_fin)->startOfDay()->addDay()
            : Carbon::parse($fin)->startOfDay();

        $chevaucheDebut = $affDebut->greaterThan($debut) ? $affDebut : Carbon::parse($debut)->startOfDay();
        $chevaucheFin   = $affFin->lessThan($fin) ? $affFin : Carbon::parse($fin)->startOfDay();

        if ($chevaucheFin->lte($chevaucheDebut)) {
            return 0;
        }

        return (int) $chevaucheDebut->diffInDays($chevaucheFin);
    }
}
