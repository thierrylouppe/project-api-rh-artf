<?php

namespace App\Interfaces;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Collection;

interface NotificationInterface
{
    public function getForUser(int $userId, array $filters = []): LengthAwarePaginator;

    public function getUnreadForUser(int $userId): Collection;

    public function countUnread(int $userId): int;

    public function findForUser(int $userId, string $id): DatabaseNotification;

    public function markAsRead(string $id): DatabaseNotification;

    public function markAllAsRead(int $userId): int;
}
