<?php

namespace App\Interfaces;

use App\Models\Agent;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Accès bas niveau nécessaires à la fusion de comptes utilisateurs en double :
 * lecture des comptes et repointage de toutes les références à `users.id`.
 */
interface CompteFusionInterface
{
    /**
     * Comptes sans fiche agent valide (agent_id NULL ou agent supprimé).
     *
     * @param  list<string>  $exclus  e-mails à ignorer (comptes système)
     * @return Collection<int, User>
     */
    public function comptesSansFiche(array $exclus): Collection;

    /**
     * Comptes rattachés à une fiche agent existante, avec l'agent chargé.
     *
     * @param  list<string>  $exclus
     * @return Collection<int, User>
     */
    public function comptesAvecFiche(array $exclus): Collection;

    public function trouver(int $id): ?User;

    /**
     * Identité de toutes les fiches agents (id, nom, prénom), pour repérer les homonymes.
     *
     * @return Collection<int, Agent>
     */
    public function identitesAgents(): Collection;

    /** Le compte cité par la fiche d'intégration de cet agent, s'il existe. */
    public function compteIntegrationUserId(int $agentId): ?int;

    /**
     * Colonnes qui contiennent un id utilisateur : clés étrangères vers `users`
     * détectées dans le schéma, plus `sessions.user_id` (sans clé étrangère).
     *
     * @return list<array{table: string, colonne: string}>
     */
    public function colonnesUtilisateur(): array;

    /**
     * Paires de colonnes polymorphes (`*_type` / `*_id`) susceptibles de citer
     * un utilisateur. Tokens Sanctum et tables Spatie exclus : traités à part.
     *
     * @return list<array{table: string, type: string, id: string}>
     */
    public function colonnesPolymorphes(): array;

    /** Remplace `$ancien` par `$nouveau` dans une colonne ; renvoie le nombre de lignes. */
    public function repointer(string $table, string $colonne, int $ancien, int $nouveau): int;

    public function repointerPolymorphe(string $table, string $type, string $id, int $ancien, int $nouveau): int;

    /**
     * Lignes d'historique qui citent ce compte (clés étrangères et colonnes
     * polymorphes), hors sessions et notifications reçues.
     */
    public function nombreReferences(int $userId): int;

    /** Notifications reçues par le compte. */
    public function supprimerNotifications(int $userId): void;

    /** Tokens Sanctum et sessions du compte. */
    public function revoquerAcces(int $userId): void;

    /** Un autre compte d'intégration utilise-t-il déjà cette adresse (login ou e-mail pro) ? */
    public function adresseIntegrationPrise(string $email, int $userIdExclu): bool;

    /** Remplace l'ancienne adresse par la nouvelle sur la fiche d'intégration et la fiche agent. */
    public function renommerAdresse(int $userId, ?int $agentId, string $ancienne, string $nouvelle): void;
}
