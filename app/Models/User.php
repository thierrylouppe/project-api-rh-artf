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

    // ─── Helpers cloisonnement ────────────────────────────────────────────────

    /** Retourne l'ID du bureau de rattachement ou null. */
    public function bureauId(): ?int
    {
        return $this->bureau_id;
    }

    /**
     * Indique si l'utilisateur est cloisonné (a un bureau de rattachement).
     * Un admin ou directeur-général sans bureau voit tout.
     */
    public function estCloisonne(): bool
    {
        return $this->bureau_id !== null;
    }
}
