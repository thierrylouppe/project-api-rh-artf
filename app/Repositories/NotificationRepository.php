<?php

namespace App\Repositories;

use App\Interfaces\NotificationInterface;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Collection;

class NotificationRepository implements NotificationInterface
{
    private function queryForUser(int $userId)
    {
        return DatabaseNotification::query()
            ->where('notifiable_type', User::class)
            ->where('notifiable_id', $userId);
    }

    public function getForUser(int $userId, array $filters = []): LengthAwarePaginator
    {
        $query = $this->queryForUser($userId)->latest();

        if (! empty($filters['non_lues'])) {
            $query->whereNull('read_at');
        }

        $perPage = (int) ($filters['per_page'] ?? 20);

        return $query->paginate(max(1, min($perPage, 100)));
    }

    public function getUnreadForUser(int $userId): Collection
    {
        return $this->queryForUser($userId)
            ->whereNull('read_at')
            ->latest()
            ->get();
    }

    public function countUnread(int $userId): int
    {
        return $this->queryForUser($userId)->whereNull('read_at')->count();
    }

    public function findForUser(int $userId, string $id): DatabaseNotification
    {
        return $this->queryForUser($userId)->where('id', $id)->firstOrFail();
    }

    public function markAsRead(string $id): DatabaseNotification
    {
        $notification = DatabaseNotification::query()->findOrFail($id);

        if ($notification->read_at === null) {
            $notification->markAsRead();
        }

        return $notification->fresh();
    }

    public function markAllAsRead(int $userId): int
    {
        return $this->queryForUser($userId)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);
    }
}
