<?php

namespace App\Services;

use App\Interfaces\NotificationInterface;
use App\Interfaces\UserInterface;
use App\Models\User;
use App\Notifications\RhEvenementNotification;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Collection;
use Spatie\Permission\Exceptions\RoleDoesNotExist;

class NotificationService
{
    public function __construct(
        private readonly NotificationInterface $notificationRepository,
        private readonly UserInterface $userRepository,
    ) {}

    public function lister(User $user, array $filters = []): LengthAwarePaginator
    {
        return $this->notificationRepository->getForUser((int) $user->id, $filters);
    }

    public function nonLues(User $user): Collection
    {
        return $this->notificationRepository->getUnreadForUser((int) $user->id);
    }

    public function countNonLues(User $user): int
    {
        return $this->notificationRepository->countUnread((int) $user->id);
    }

    public function marquerLu(User $user, string $id): void
    {
        $this->notificationRepository->findForUser((int) $user->id, $id);
        $this->notificationRepository->markAsRead($id);
    }

    public function toutLire(User $user): int
    {
        return $this->notificationRepository->markAllAsRead((int) $user->id);
    }

    public function envoyer(User $user, Notification $notification): void
    {
        $user->notify($notification);
    }

    /**
     * @param  iterable<User|null>  $destinataires
     */
    public function envoyerGroupe(iterable $destinataires, Notification $notification): void
    {
        collect($destinataires)
            ->filter(fn ($user) => $user instanceof User)
            ->unique('id')
            ->each(fn (User $user) => $this->envoyer($user, $notification));
    }

    /**
     * @param  array<string, mixed>  $meta
     */
    public function notifierEvenement(
        User $user,
        string $domaine,
        string $action,
        string $message,
        array $meta = [],
    ): void {
        $this->envoyer($user, new RhEvenementNotification($domaine, $action, $message, $meta));
    }

    /**
     * @param  iterable<User|null>  $destinataires
     * @param  array<string, mixed>  $meta
     */
    public function notifierEvenementGroupe(
        iterable $destinataires,
        string $domaine,
        string $action,
        string $message,
        array $meta = [],
    ): void {
        $this->envoyerGroupe(
            $destinataires,
            new RhEvenementNotification($domaine, $action, $message, $meta)
        );
    }

    /**
     * @param  array<string, mixed>  $meta
     */
    public function notifierRole(string $role, string $domaine, string $action, string $message, array $meta = []): void
    {
        try {
            $users = $this->userRepository->getByRole($role);
        } catch (RoleDoesNotExist) {
            return;
        }

        $this->notifierEvenementGroupe($users, $domaine, $action, $message, $meta);
    }

    public function destinatairesAuteurEtAgent(?int $createdBy, ?int $agentId): Collection
    {
        $destinataires = collect();

        if ($createdBy) {
            $auteur = $this->userRepository->findOptional($createdBy);
            if ($auteur instanceof User) {
                $destinataires->push($auteur);
            }
        }

        if ($agentId) {
            $compte = $this->userRepository->findByAgentId($agentId);
            if ($compte instanceof User) {
                $destinataires->push($compte);
            }
        }

        return $destinataires->unique('id')->values();
    }
}
