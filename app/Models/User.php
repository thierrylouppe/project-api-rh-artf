<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

#[Fillable(['name', 'email', 'password', 'agent_id', 'bureau_id', 'is_active'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, HasRoles, Notifiable;

    protected $guard_name = 'api';

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password'          => 'hashed',
            'is_active'         => 'boolean',
        ];
    }

    // ─── Relations ───────────────────────────────────────────────────────────

    /** Vague F — bureau de rattachement DRHL (nullable pour admin / DG). */
    public function bureau(): BelongsTo
    {
        return $this->belongsTo(Bureau::class);
    }

    public function agent(): BelongsTo
    {
        return $this->belongsTo(Agent::class);
    }

    // ─── Helpers cloisonnement ────────────────────────────────────────────────

    /** Retourne l'ID du bureau de rattachement ou null. */
    public function bureauId(): ?int
    {
        return $this->bureau_id;
    }

    /**
     * Indique si l'utilisateur est cloisonné (a un bureau de rattachement).
     * Un admin, DG ou Directeur DRHL sans bureau voit tout.
     */
    public function estCloisonne(): bool
    {
        return $this->bureau_id !== null;
    }

    /**
     * Vue globale du personnel : métier RH transverse, DG, admin,
     * ou compte sans bureau de rattachement.
     *
     * Les chefs (directeur / CS / CB) sans cette permission restent
     * limités à leur direction / service / bureau.
     */
    public function voitPersonnelGlobal(): bool
    {
        if (! $this->estCloisonne()) {
            return true;
        }

        if ($this->hasRole('admin', 'api')) {
            return true;
        }

        return $this->getAllPermissions()->contains('name', 'consulter-agents-global');
    }

    /**
     * Périmètre exposé au FE : globale | direction | service | bureau
     */
    public function vuePersonnel(): string
    {
        return $this->voitPersonnelGlobal() ? 'globale' : $this->niveauCloisonnement();
    }

    /**
     * Niveau de cloisonnement dérivé du rôle de fonction.
     * agent / chef-bureau → bureau · chef-service → service · directeur → direction
     */
    public function niveauCloisonnement(): string
    {
        if ($this->hasRole('directeur', 'api') || $this->hasRole('directeur-general', 'api')) {
            return 'direction';
        }

        if ($this->hasRole('chef-service', 'api')) {
            return 'service';
        }

        return 'bureau';
    }
}
