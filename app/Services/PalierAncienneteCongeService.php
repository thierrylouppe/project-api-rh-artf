<?php

namespace App\Services;

use App\Interfaces\PalierAncienneteCongeInterface;
use App\Models\PalierAncienneteConge;

class PalierAncienneteCongeService extends BaseService
{
    public function __construct(PalierAncienneteCongeInterface $repository)
    {
        parent::__construct($repository);
    }

    public function create(array $data): PalierAncienneteConge
    {
        $this->assertPlageValide($data);
        $this->assertSansChevauchement($data);

        return $this->repository->create($data);
    }

    public function update(int $id, array $data): PalierAncienneteConge
    {
        $actuel = $this->repository->findById($id);
        $fusion = array_merge($actuel->only(['anciennete_min', 'anciennete_max', 'jours_bonus']), $data);

        $this->assertPlageValide($fusion);
        $this->assertSansChevauchement($fusion, $id);

        return $this->repository->update($id, $data);
    }

    private function assertPlageValide(array $data): void
    {
        $min = (int) $data['anciennete_min'];
        $max = $data['anciennete_max'] ?? null;

        abort_if(
            $max !== null && (int) $max < $min,
            422,
            'anciennete_max doit être supérieur ou égal à anciennete_min.'
        );
    }

    private function assertSansChevauchement(array $data, ?int $exclureId = null): void
    {
        $minA = (int) $data['anciennete_min'];
        $maxA = $this->borneHaute($data['anciennete_max'] ?? null);

        foreach ($this->repository->getAll() as $palier) {
            if ($exclureId !== null && (int) $palier->id === $exclureId) {
                continue;
            }

            $minB = (int) $palier->anciennete_min;
            $maxB = $this->borneHaute($palier->anciennete_max);

            abort_if(
                $minA <= $maxB && $minB <= $maxA,
                422,
                'Ce palier chevauche un palier existant.'
            );
        }
    }

    private function borneHaute(mixed $max): int
    {
        return $max === null ? PHP_INT_MAX : (int) $max;
    }
}
