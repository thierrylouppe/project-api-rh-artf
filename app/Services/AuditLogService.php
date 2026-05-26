<?php

namespace App\Services;

use App\Interfaces\AuditLogInterface;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class AuditLogService extends BaseService
{
    public function __construct(AuditLogInterface $repository)
    {
        parent::__construct($repository);
    }

    public function log(string $action, Model $entity, User $user, ?array $details = null, ?string $ip = null): AuditLog
    {
        return $this->repository->create([
            'action' => $action,
            'user_id' => $user->id,
            'loggable_type' => $entity->getMorphClass(),
            'loggable_id' => $entity->getKey(),
            'details' => $details,
            'ip_address' => $ip,
        ]);
    }
}
