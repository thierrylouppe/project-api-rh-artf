<?php

namespace App\Repositories;

use App\Interfaces\CompteFusionInterface;
use App\Models\Agent;
use App\Models\CompteIntegration;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CompteFusionRepository implements CompteFusionInterface
{
    /** Tables jamais repointées par la détection générique. */
    private const TABLES_IGNOREES = [
        'users',
        'sessions',
        'personal_access_tokens',
        'model_has_roles',
        'model_has_permissions',
        'migrations',
    ];

    /** @var list<array{table: string, colonne: string}>|null */
    private ?array $colonnes = null;

    /** @var list<array{table: string, type: string, id: string}>|null */
    private ?array $polymorphes = null;

    public function comptesSansFiche(array $exclus): Collection
    {
        return User::query()
            ->whereDoesntHave('agent')
            ->whereNotIn('email', $exclus)
            ->orderBy('id')
            ->get();
    }

    public function comptesAvecFiche(array $exclus): Collection
    {
        return User::query()
            ->whereHas('agent')
            ->with('agent')
            ->whereNotIn('email', $exclus)
            ->orderBy('id')
            ->get();
    }

    public function trouver(int $id): ?User
    {
        return User::query()->with('agent')->find($id);
    }

    public function identitesAgents(): Collection
    {
        return Agent::query()->get(['id', 'nom', 'prenom']);
    }

    public function compteIntegrationUserId(int $agentId): ?int
    {
        $id = CompteIntegration::query()->where('agent_id', $agentId)->value('user_id');

        return $id === null ? null : (int) $id;
    }

    public function colonnesUtilisateur(): array
    {
        if ($this->colonnes !== null) {
            return $this->colonnes;
        }

        $colonnes = [];
        foreach ($this->tables() as $table) {
            foreach (Schema::getForeignKeys($table) as $cle) {
                if ($cle['foreign_table'] !== 'users' || count($cle['columns']) !== 1) {
                    continue;
                }
                $colonnes[] = ['table' => $table, 'colonne' => $cle['columns'][0]];
            }
        }

        if (Schema::hasTable('sessions')) {
            $colonnes[] = ['table' => 'sessions', 'colonne' => 'user_id'];
        }

        return $this->colonnes = $colonnes;
    }

    public function colonnesPolymorphes(): array
    {
        if ($this->polymorphes !== null) {
            return $this->polymorphes;
        }

        $paires = [];
        foreach ($this->tables() as $table) {
            $liste = Schema::getColumnListing($table);
            foreach ($liste as $colonne) {
                if (! str_ends_with($colonne, '_type')) {
                    continue;
                }
                $id = substr($colonne, 0, -5).'_id';
                if (in_array($id, $liste, true)) {
                    $paires[] = ['table' => $table, 'type' => $colonne, 'id' => $id];
                }
            }
        }

        return $this->polymorphes = $paires;
    }

    public function repointer(string $table, string $colonne, int $ancien, int $nouveau): int
    {
        return DB::table($table)->where($colonne, $ancien)->update([$colonne => $nouveau]);
    }

    public function repointerPolymorphe(string $table, string $type, string $id, int $ancien, int $nouveau): int
    {
        return DB::table($table)
            ->where($type, User::class)
            ->where($id, $ancien)
            ->update([$id => $nouveau]);
    }

    public function nombreReferences(int $userId): int
    {
        $total = 0;

        foreach ($this->colonnesUtilisateur() as ['table' => $table, 'colonne' => $colonne]) {
            if ($table !== 'sessions') {
                $total += DB::table($table)->where($colonne, $userId)->count();
            }
        }

        foreach ($this->colonnesPolymorphes() as ['table' => $table, 'type' => $type, 'id' => $id]) {
            if ($table !== 'notifications') {
                $total += DB::table($table)->where($type, User::class)->where($id, $userId)->count();
            }
        }

        return $total;
    }

    public function supprimerNotifications(int $userId): void
    {
        DB::table('notifications')
            ->where('notifiable_type', User::class)
            ->where('notifiable_id', $userId)
            ->delete();
    }

    public function revoquerAcces(int $userId): void
    {
        DB::table('personal_access_tokens')
            ->where('tokenable_type', User::class)
            ->where('tokenable_id', $userId)
            ->delete();

        if (Schema::hasTable('sessions')) {
            DB::table('sessions')->where('user_id', $userId)->delete();
        }
    }

    public function adresseIntegrationPrise(string $email, int $userIdExclu): bool
    {
        return CompteIntegration::query()
            ->where(fn ($query) => $query->whereNull('user_id')->orWhere('user_id', '!=', $userIdExclu))
            ->where(fn ($query) => $query->where('login', $email)->orWhere('email_professionnel', $email))
            ->exists();
    }

    public function renommerAdresse(int $userId, ?int $agentId, string $ancienne, string $nouvelle): void
    {
        CompteIntegration::query()
            ->where('user_id', $userId)
            ->where('login', $ancienne)
            ->update(['login' => $nouvelle]);

        CompteIntegration::query()
            ->where('user_id', $userId)
            ->where('email_professionnel', $ancienne)
            ->update(['email_professionnel' => $nouvelle]);

        if ($agentId !== null) {
            Agent::query()
                ->whereKey($agentId)
                ->where('email_professionnel', $ancienne)
                ->update(['email_professionnel' => $nouvelle]);
        }
    }

    /** @return list<string> */
    private function tables(): array
    {
        // Base de l'application seulement : sans schéma, MySQL liste toutes les bases du serveur.
        return collect(Schema::getTables(Schema::getCurrentSchemaName()))
            ->pluck('name')
            ->reject(fn (string $nom) => in_array($nom, self::TABLES_IGNOREES, true) || str_starts_with($nom, 'sqlite_'))
            ->values()
            ->all();
    }
}
