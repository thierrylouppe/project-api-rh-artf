<?php

namespace App\Services;

use App\Enums\CodePaieElement;
use App\Enums\NaturePaieElement;
use App\Interfaces\PaieElementAffectationInterface;
use App\Interfaces\PaieElementInterface;
use App\Models\PaieElement;

/** @property PaieElementInterface $repository */
class PaieElementService extends BaseService
{
    public function __construct(
        PaieElementInterface $repository,
        private readonly PaieElementAffectationInterface $affectationRepository,
    ) {
        parent::__construct($repository);
    }

    protected function beforeCreate(array $data): array
    {
        unset($data['systeme'], $data['sens']);

        $code = (string) $data['code'];

        abort_if(
            CodePaieElement::tryFrom($code) !== null,
            422,
            'Ce code est réservé à la convention collective.'
        );

        abort_if(
            $this->repository->findByCode($code) !== null,
            422,
            'Un élément de paie porte déjà ce code.'
        );

        $nature = $data['nature'] instanceof NaturePaieElement
            ? $data['nature']
            : NaturePaieElement::from((string) $data['nature']);

        $data['sens'] = $nature->sens()->value;
        $data['systeme'] = false;
        $data['actif'] = $data['actif'] ?? true;

        return $data;
    }

    protected function beforeUpdate(int $id, array $data): array
    {
        $element = $this->repository->findById($id);

        abort_if(! $element instanceof PaieElement, 404, 'Élément de paie introuvable.');

        unset($data['systeme']);

        if ($element->systeme) {
            return $this->sanitizeSystemeUpdate($element, $data);
        }

        if (isset($data['nature'])) {
            $nature = $data['nature'] instanceof NaturePaieElement
                ? $data['nature']
                : NaturePaieElement::from((string) $data['nature']);
            $data['sens'] = $nature->sens()->value;
        }

        if (isset($data['code']) && $data['code'] !== $element->code) {
            abort_if(
                CodePaieElement::tryFrom((string) $data['code']) !== null,
                422,
                'Ce code est réservé à la convention collective.'
            );
            abort_if(
                $this->repository->findByCode((string) $data['code']) !== null,
                422,
                'Un élément de paie porte déjà ce code.'
            );
        }

        return $data;
    }

    public function delete(int $id): bool
    {
        $element = $this->repository->findById($id);

        abort_if(
            $element instanceof PaieElement && $element->systeme,
            422,
            'Impossible de supprimer un élément prévu par la convention collective.'
        );

        abort_if(
            $this->affectationRepository->existsByElement($id),
            422,
            'Impossible de supprimer un élément déjà affecté à un agent.'
        );

        return $this->repository->delete($id);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function sanitizeSystemeUpdate(PaieElement $element, array $data): array
    {
        $immutables = [
            'code' => $element->code,
            'nature' => $element->nature->value,
            'sens' => $element->sens->value,
            'periodicite' => $element->periodicite->value,
            'mode_calcul' => $element->mode_calcul->value,
            'article_ccn' => $element->article_ccn,
        ];

        foreach ($immutables as $field => $current) {
            if (! array_key_exists($field, $data)) {
                continue;
            }

            $incoming = $data[$field];
            if ($incoming instanceof \BackedEnum) {
                $incoming = $incoming->value;
            }

            abort_if(
                (string) $incoming !== (string) $current,
                422,
                sprintf('Le champ %s d\'un élément CCN ne peut pas être modifié.', $field)
            );

            unset($data[$field]);
        }

        unset($data['sens']);

        return $data;
    }
}
