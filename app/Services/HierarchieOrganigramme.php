<?php

namespace App\Services;

use App\Models\Bureau;
use App\Models\Direction;
use App\Models\Service;

/**
 * Déduit nominations et N+1 à partir de la fonction et du placement courant.
 *
 * Le dump gestRHdb ne porte pas de supérieur : la fonction (agent, chef de
 * bureau, chef de service, directeur, directeur général) et la structure
 * courante suffisent. Une nomination n'est créée que si la fonction correspond
 * au niveau de la structure et qu'une seule personne occupe ce niveau.
 */
class HierarchieOrganigramme
{
    /** @var list<int> */
    private array $retenus = [];

    public const RANG_AGENT = 1;

    public const RANG_CHEF_BUREAU = 2;

    public const RANG_CHEF_SERVICE = 3;

    public const RANG_DIRECTEUR = 4;

    public const RANG_DG = 5;

    /**
     * @param  list<array{
     *     agent_id: int,
     *     affectation_id: int,
     *     rang: int,
     *     poste: string,
     *     type: class-string,
     *     structure_id: int,
     *     service_id: int|null,
     *     direction_id: int|null,
     *     date_debut: string
     * }>  $personnes
     * @param  list<int>  $chefsRetenus  Agent retenu quand plusieurs occupent le même niveau.
     * @return array{
     *     nominations: list<array{agent_id: int, poste: string, type: class-string, structure_id: int, date_debut: string}>,
     *     liens: list<array{affectation_id: int, agent_id: int, superieur_id: int|null}>,
     *     ambigus: list<array{type: class-string, structure_id: int, rang: int, agent_ids: list<int>}>
     * }
     */
    public function calculer(array $personnes, array $chefsRetenus = []): array
    {
        $this->retenus = array_values(array_unique($chefsRetenus));
        $ambigus = [];
        $nominations = [];

        foreach ($this->structures($personnes) as $structure) {
            $tete = $this->tete($personnes, $structure['type'], $structure['id'], $structure['rang'], $ambigus);
            if ($tete === null) {
                continue;
            }

            $nominations[] = [
                'agent_id' => $tete['agent_id'],
                'poste' => $tete['poste'],
                'type' => $structure['type'],
                'structure_id' => $structure['id'],
                'date_debut' => $tete['date_debut'],
            ];
        }

        $liens = [];
        foreach ($personnes as $personne) {
            $liens[] = [
                'affectation_id' => $personne['affectation_id'],
                'agent_id' => $personne['agent_id'],
                'superieur_id' => $this->superieur($personnes, $personne, $ambigus),
            ];
        }

        return [
            'nominations' => $nominations,
            'liens' => $liens,
            'ambigus' => $ambigus,
        ];
    }

    public function rang(string $fonction): int
    {
        $nom = $this->norm($fonction);

        if (str_contains($nom, 'GENERAL')) {
            return self::RANG_DG;
        }
        if (str_contains($nom, 'DIRECTEUR')) {
            return self::RANG_DIRECTEUR;
        }
        if (str_contains($nom, 'SERVICE')) {
            return self::RANG_CHEF_SERVICE;
        }
        if (str_contains($nom, 'BUREAU')) {
            return self::RANG_CHEF_BUREAU;
        }

        return self::RANG_AGENT;
    }

    /**
     * @param  list<array<string, mixed>>  $personnes
     * @return list<array{type: class-string, id: int, rang: int}>
     */
    private function structures(array $personnes): array
    {
        $vues = [];
        foreach ($personnes as $personne) {
            $cle = $personne['type'].'#'.$personne['structure_id'];
            if (isset($vues[$cle])) {
                continue;
            }
            $vues[$cle] = [
                'type' => $personne['type'],
                'id' => $personne['structure_id'],
                'rang' => $this->rangDeStructure($personne['type']),
            ];
        }

        return array_values($vues);
    }

    /**
     * @param  class-string  $type
     */
    private function rangDeStructure(string $type): int
    {
        return match ($type) {
            Bureau::class => self::RANG_CHEF_BUREAU,
            Service::class => self::RANG_CHEF_SERVICE,
            Direction::class => self::RANG_DIRECTEUR,
            default => self::RANG_AGENT,
        };
    }

    /**
     * Chef unique du niveau attendu. Un directeur placé sur un service sans
     * chef de service est la tête de ce service. Le directeur général est la
     * tête de sa direction.
     *
     * @param  list<array<string, mixed>>  $personnes
     * @param  list<array{type: class-string, structure_id: int, rang: int, agent_ids: list<int>}>  $ambigus
     * @return array<string, mixed>|null
     */
    private function tete(array $personnes, string $type, int $id, int $rang, array &$ambigus): ?array
    {
        $trouves = $this->auNiveau($personnes, $type, $id, $rang);
        if (count($trouves) > 1) {
            $elu = $this->elu($trouves);
            if ($elu !== null) {
                return $elu;
            }
            $this->noterAmbigu($ambigus, $type, $id, $rang, $trouves);

            return null;
        }
        if (count($trouves) === 1) {
            return $trouves[0];
        }

        if ($type === Service::class) {
            $directeurs = $this->auNiveau($personnes, $type, $id, self::RANG_DIRECTEUR);
            if (count($directeurs) === 1) {
                return $directeurs[0];
            }
            if (count($directeurs) > 1) {
                $this->noterAmbigu($ambigus, $type, $id, self::RANG_DIRECTEUR, $directeurs);
            }
        }

        if ($type === Direction::class) {
            $dgs = $this->auNiveau($personnes, $type, $id, self::RANG_DG);
            if (count($dgs) === 1) {
                return $dgs[0];
            }
        }

        return null;
    }

    /**
     * @param  list<array<string, mixed>>  $personnes
     * @param  list<array{type: class-string, structure_id: int, rang: int, agent_ids: list<int>}>  $ambigus
     */
    private function superieur(array $personnes, array $personne, array &$ambigus): ?int
    {
        $rang = $personne['rang'];

        if ($personne['type'] === Bureau::class && $rang === self::RANG_CHEF_BUREAU) {
            $retenu = $this->retenuId($personnes, Bureau::class, $personne['structure_id'], self::RANG_CHEF_BUREAU, $personne['agent_id']);
            if ($retenu !== null) {
                return $retenu;
            }
        }

        if ($personne['type'] === Bureau::class && $rang < self::RANG_CHEF_BUREAU) {
            $chef = $this->unique($personnes, Bureau::class, $personne['structure_id'], self::RANG_CHEF_BUREAU, $ambigus, $personne['agent_id']);
            if ($chef !== null) {
                return $chef;
            }
        }

        if ($rang < self::RANG_CHEF_SERVICE && $personne['service_id'] !== null) {
            $chef = $this->unique($personnes, Service::class, $personne['service_id'], self::RANG_CHEF_SERVICE, $ambigus, $personne['agent_id']);
            if ($chef !== null) {
                return $chef;
            }

            $directeurLocal = $this->unique($personnes, Service::class, $personne['service_id'], self::RANG_DIRECTEUR, $ambigus, $personne['agent_id']);
            if ($directeurLocal !== null) {
                return $directeurLocal;
            }
        }

        if ($rang === self::RANG_CHEF_SERVICE && $personne['type'] === Service::class) {
            $directeurLocal = $this->unique($personnes, Service::class, $personne['structure_id'], self::RANG_DIRECTEUR, $ambigus, $personne['agent_id']);
            if ($directeurLocal !== null) {
                return $directeurLocal;
            }
        }

        if ($rang < self::RANG_DIRECTEUR && $personne['direction_id'] !== null) {
            $directeur = $this->unique($personnes, Direction::class, $personne['direction_id'], self::RANG_DIRECTEUR, $ambigus, $personne['agent_id']);
            if ($directeur !== null) {
                return $directeur;
            }
        }

        if ($rang < self::RANG_DG) {
            return $this->directeurGeneral($personnes, $personne['agent_id']);
        }

        return null;
    }

    /**
     * @param  list<array<string, mixed>>  $personnes
     * @param  list<array{type: class-string, structure_id: int, rang: int, agent_ids: list<int>}>  $ambigus
     */
    private function unique(array $personnes, string $type, int $id, int $rang, array &$ambigus, int $saufAgentId): ?int
    {
        $tous = $this->auNiveau($personnes, $type, $id, $rang);
        $elu = $this->elu($tous);
        if ($elu !== null) {
            return $elu['agent_id'] === $saufAgentId ? null : $elu['agent_id'];
        }

        $trouves = array_values(array_filter(
            $tous,
            fn (array $personne) => $personne['agent_id'] !== $saufAgentId,
        ));

        if (count($trouves) > 1) {
            $this->noterAmbigu($ambigus, $type, $id, $rang, $trouves);

            return null;
        }

        return count($trouves) === 1 ? $trouves[0]['agent_id'] : null;
    }

    /**
     * @param  list<array<string, mixed>>  $personnes
     */
    private function retenuId(array $personnes, string $type, int $id, int $rang, int $saufAgentId): ?int
    {
        $elu = $this->elu($this->auNiveau($personnes, $type, $id, $rang));
        if ($elu === null || $elu['agent_id'] === $saufAgentId) {
            return null;
        }

        return $elu['agent_id'];
    }

    /**
     * @param  list<array<string, mixed>>  $trouves
     * @return array<string, mixed>|null
     */
    private function elu(array $trouves): ?array
    {
        $preferes = array_values(array_filter(
            $trouves,
            fn (array $personne) => in_array($personne['agent_id'], $this->retenus, true),
        ));

        return count($preferes) === 1 ? $preferes[0] : null;
    }

    /**
     * @param  list<array<string, mixed>>  $personnes
     */
    private function directeurGeneral(array $personnes, int $saufAgentId): ?int
    {
        $dgs = [];
        foreach ($personnes as $personne) {
            if ($personne['rang'] === self::RANG_DG && $personne['agent_id'] !== $saufAgentId) {
                $dgs[$personne['agent_id']] = $personne['agent_id'];
            }
        }

        return count($dgs) === 1 ? array_values($dgs)[0] : null;
    }

    /**
     * @param  list<array<string, mixed>>  $personnes
     * @return list<array<string, mixed>>
     */
    private function auNiveau(array $personnes, string $type, int $id, int $rang): array
    {
        $trouves = [];
        foreach ($personnes as $personne) {
            if ($personne['type'] === $type && $personne['structure_id'] === $id && $personne['rang'] === $rang) {
                $trouves[$personne['agent_id']] = $personne;
            }
        }

        return array_values($trouves);
    }

    /**
     * @param  list<array{type: class-string, structure_id: int, rang: int, agent_ids: list<int>}>  $ambigus
     * @param  list<array<string, mixed>>  $trouves
     */
    private function noterAmbigu(array &$ambigus, string $type, int $id, int $rang, array $trouves): void
    {
        foreach ($ambigus as $deja) {
            if ($deja['type'] === $type && $deja['structure_id'] === $id && $deja['rang'] === $rang) {
                return;
            }
        }

        $ambigus[] = [
            'type' => $type,
            'structure_id' => $id,
            'rang' => $rang,
            'agent_ids' => array_map(fn (array $personne) => $personne['agent_id'], $trouves),
        ];
    }

    private function norm(string $valeur): string
    {
        if (class_exists(\Normalizer::class)) {
            $decompose = \Normalizer::normalize($valeur, \Normalizer::FORM_D);
            if (is_string($decompose)) {
                $valeur = preg_replace('/\p{Mn}/u', '', $decompose) ?? $valeur;
            }
        }

        $valeur = mb_strtoupper($valeur);
        $valeur = preg_replace('/[^A-Z0-9]+/u', ' ', $valeur) ?? $valeur;

        return trim(preg_replace('/\s+/', ' ', $valeur) ?? $valeur);
    }
}
