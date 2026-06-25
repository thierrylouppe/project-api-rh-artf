<?php

namespace App\Services;

use App\Interfaces\TypeIntegrationInterface;
use Illuminate\Database\Eloquent\Model;

class TypeIntegrationService extends BaseService
{
    private ?array $pendingDocumentIds = null;

    public function __construct(TypeIntegrationInterface $repository)
    {
        parent::__construct($repository);
    }

    protected function beforeCreate(array $data): array
    {
        $this->pendingDocumentIds = $data['documents_ids'] ?? null;
        unset($data['documents_ids']);
        return $data;
    }

    protected function afterCreate(Model $model): Model
    {
        if ($this->pendingDocumentIds !== null) {
            $model->documentsObligatoires()->sync($this->pendingDocumentIds);
            $model->load('documentsObligatoires');
            $this->pendingDocumentIds = null;
        }
        return $model;
    }

    protected function beforeUpdate(int $id, array $data): array
    {
        $this->pendingDocumentIds = array_key_exists('documents_ids', $data)
            ? $data['documents_ids']
            : null;
        unset($data['documents_ids']);
        return $data;
    }

    protected function afterUpdate(Model $model): Model
    {
        if ($this->pendingDocumentIds !== null) {
            $model->documentsObligatoires()->sync($this->pendingDocumentIds);
            $model->load('documentsObligatoires');
            $this->pendingDocumentIds = null;
        }
        return $model;
    }
}
