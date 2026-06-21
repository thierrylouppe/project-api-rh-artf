<?php

namespace App\Services;

use App\Interfaces\CompteIntegrationInterface;
use App\Models\Agent;
use App\Models\CompteIntegration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/** @property CompteIntegrationInterface $repository */
class CompteIntegrationService extends BaseService
{
    public function __construct(CompteIntegrationInterface $repository)
    {
        parent::__construct($repository);
    }

    public function provisionner(Agent $agent): CompteIntegration
    {
        return DB::transaction(function () use ($agent) {
            $login           = $this->genererLogin($agent->prenom, $agent->nom);
            $emailPro        = $this->genererEmail($login);
            $motDePasseTemp  = Str::random(10);
            $badgeNumero     = 'ARTF-BADGE-' . str_pad($agent->id, 5, '0', STR_PAD_LEFT);

            $user = \App\Models\User::create([
                'name'      => $agent->nom_complet,
                'email'     => $emailPro,
                'password'  => Hash::make($motDePasseTemp),
                'agent_id'  => $agent->id,
                'is_active' => true,
            ]);

            $agent->update([
                'email_professionnel' => $emailPro,
                'badge_numero'        => $badgeNumero,
            ]);

            $compte = $this->repository->create([
                'agent_id'            => $agent->id,
                'user_id'             => $user->id,
                'login'               => $login,
                'email_professionnel' => $emailPro,
                'badge_numero'        => $badgeNumero,
                'date_creation'       => now(),
            ]);

            return $compte;
        });
    }

    public function genererLogin(string $prenom, string $nom): string
    {
        $base = strtolower(substr($prenom, 0, 1) . $nom);
        $base = Str::ascii($base);
        $base = preg_replace('/[^a-z0-9]/', '', $base);

        $login = $base;
        $i = 1;
        while (\App\Models\User::where('email', $login . '@artf.cg')->exists()) {
            $login = $base . $i;
            $i++;
        }

        return $login;
    }

    public function genererEmail(string $login): string
    {
        return "{$login}@artf.cg";
    }

    public function findByAgent(int $agentId): ?CompteIntegration
    {
        return $this->repository->findByAgent($agentId);
    }

    public function marquerMotDePasseEnvoye(int $id): CompteIntegration
    {
        return $this->repository->marquerMotDePasseEnvoye($id);
    }
}
