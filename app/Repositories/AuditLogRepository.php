<?php

namespace App\Repositories;

use App\Interfaces\AuditLogInterface;
use App\Models\AuditLog;

class AuditLogRepository extends BaseRepository implements AuditLogInterface
{
    protected function model(): string
    {
        return AuditLog::class;
    }
}
