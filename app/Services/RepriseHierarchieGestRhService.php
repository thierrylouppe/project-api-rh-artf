<?php

namespace App\Services;

use App\Enums\StatutNomination;
use App\Enums\TypeActeNomination;
use App\Interfaces\AffectationInterface;
use App\Interfaces\NominationInterface;
use App\Models\Affectation;
use App\Models\Bureau;
use App\Models\Direction;
use App\Models\Service;
use Illuminate\Support\Facades\DB;

/**
 * Pose les nominations actives et les N+1 des affectations reprises du dump gestRHdb.
 */
class RepriseHierarchieGestRhService extends BaseService
{
    public function __construct(
        private readonly AffectationInterface $affectations,
        private readonly NominationInterface $nominations,
        private readonly HierarchieOrganigramme $organigramme,
    ) {
        parent::__construct($affectations);
    }

    /**
     * @return array{
     *     personnes: int,
     *     nominations_creees: int,
     *     nominations_deja: int,
     *     liens_mis_a_jour: int,
     *     liens_deja: int,
     *     sans_superieur: list<string>,
     *     ambigus: list<string>
     * }
     */
    public function appliquer(int $createdBy): array
    {
        $affectations = $this->affectations->getActivesPourHierarchie();
        $personnes = [];
        $libelles = [];
        $structures = [];

        foreach ($affectations as $affectation) {
            if (! $affectation instanceof Affectation || $affectation->agent === null || $affectation->structure === null) {
                continue;
            }

            $personne = $this->personne($affectation);
            if ($personne === null) {
                continue;
            }

            $personnes[$personne['agent_id']] = $personne;
            $libelles[$personne['agent_id']] = trim($affectation->agent->nom.' '.$affectation->agent->prenom);
            $structures[$personne['type'].'#'.$personne['structure_id']] = $affectation->structure->nom;
        }

        $plan = $this->organigramme->calculer(array_values($personnes));

        $nominationsCreees = 0;
        $nominationsDeja = 0;
        $liensMisAJour = 0;
        $liensDeja = 0;

        DB::transaction(function () use ($plan, $createdBy, &$nominationsCreees, &$nominationsDeja, &$liensMisAJour, &$liensDeja) {
            foreach ($plan['nominations'] as $nomination) {
                if ($this->nominationDejaActive($nomination)) {
                    $nominationsDeja++;
                    continue;
                }

                $this->nominations->create([
                    'agent_id' => $nomination['agent_id'],
                    'poste' => $nomination['poste'],
                    'structurable_type' => $nomination['type'],
                    'structurable_id' => $nomination['structure_id'],
                    'date_debut' => $nomination['date_debut'],
                    'type_acte' => TypeActeNomination::DECISION,
                    'statut' => StatutNomination::ACTIVE,
                    'created_by' => $createdBy,
                ]);
                $nominationsCreees++;
            }

            foreach ($plan['liens'] as $lien) {
                $affectation = $this->affectations->findById($lien['affectation_id']);
                if ((int) $affectation->superieur_hierarchique_id === (int) ($lien['superieur_id'] ?? 0)
                    || ($lien['superieur_id'] === null && $affectation->superieur_hierarchique_id === null)) {
                    $liensDeja++;
                    continue;
                }

                $this->affectations->update($lien['affectation_id'], [
                    'superieur_hierarchique_id' => $lien['superieur_id'],
                ]);
                $liensMisAJour++;
            }
        });

        $sansSuperieur = [];
        foreach ($plan['liens'] as $lien) {
            if ($lien['superieur_id'] === null) {
                $sansSuperieur[] = $libelles[$lien['agent_id']] ?? (string) $lien['agent_id'];
            }
        }

        $ambigus = [];
        foreach ($plan['ambigus'] as $ambigu) {
            $noms = array_map(
                fn (int $id) => $libelles[$id] ?? (string) $id,
                $ambigu['agent_ids'],
            );
            $structure = $structures[$ambigu['type'].'#'.$ambigu['structure_id']] ?? (string) $ambigu['structure_id'];
            $ambigus[] = $structure.' : '.implode(', ', $noms);
        }

        return [
            'personnes' => count($personnes),
            'nominations_creees' => $nominationsCreees,
            'nominations_deja' => $nominationsDeja,
            'liens_mis_a_jour' => $liensMisAJour,
            'liens_deja' => $liensDeja,
            'sans_superieur' => $sansSuperieur,
            'ambigus' => $ambigus,
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function personne(Affectation $affectation): ?array
    {
        $structure = $affectation->structure;
        $serviceId = null;
        $directionId = null;

        if ($structure instanceof Bureau) {
            $serviceId = $structure->service_id ? (int) $structure->service_id : null;
            $directionId = $structure->service?->direction_id ? (int) $structure->service->direction_id : null;
            $type = Bureau::class;
        } elseif ($structure instanceof Service) {
            $serviceId = (int) $structure->id;
            $directionId = $structure->direction_id ? (int) $structure->direction_id : null;
            $type = Service::class;
        } elseif ($structure instanceof Direction) {
            $directionId = (int) $structure->id;
            $type = Direction::class;
        } else {
            return null;
        }

        $poste = $affectation->agent->fonction?->nom ?? 'Agent';

        return [
            'agent_id' => (int) $affectation->agent_id,
            'affectation_id' => (int) $affectation->id,
            'rang' => $this->organigramme->rang($poste),
            'poste' => $poste,
            'type' => $type,
            'structure_id' => (int) $structure->id,
            'service_id' => $serviceId,
            'direction_id' => $directionId,
            'date_debut' => $affectation->date_affectation?->toDateString() ?? '2023-10-04',
        ];
    }

    /**
     * @param  array{agent_id: int, type: class-string, structure_id: int}  $nomination
     */
    private function nominationDejaActive(array $nomination): bool
    {
        return $this->nominations->getByAgent($nomination['agent_id'])->contains(
            fn ($existante) => $existante->statut === StatutNomination::ACTIVE
                && $existante->structurable_type === $nomination['type']
                && (int) $existante->structurable_id === $nomination['structure_id']
        );
    }
}
