<?php

namespace App\Services;

use App\Interfaces\AffectationInterface;
use App\Interfaces\UserInterface;
use App\Models\User;

class SuperieurHierarchiqueService
{
    public function __construct(
        private readonly AffectationInterface $affectationRepository,
        private readonly UserInterface $userRepository,
    ) {}

    public function estN1(User $user, int $agentId): bool
    {
        if ($user->hasRole('admin')) {
            return true;
        }

        $compte = $this->trouverCompteN1($agentId);

        return $compte instanceof User && (int) $compte->id === (int) $user->id;
    }

    public function assertEstN1(User $user, int $agentId): void
    {
        if ($user->hasRole('admin')) {
            return;
        }

        $n1 = $this->compteN1($agentId);
        abort_unless(
            (int) $n1->id === (int) $user->id,
            403,
            'Seul le supérieur hiérarchique de l\'agent (affectation active) peut valider au niveau N+1.'
        );
    }

    public function compteN1(int $agentId): User
    {
        $compte = $this->trouverCompteN1($agentId);
        abort_unless(
            $compte instanceof User,
            422,
            $this->affectationRepository->getActive($agentId)?->superieur_hierarchique_id
                ? 'Le supérieur hiérarchique n\'a pas de compte utilisateur.'
                : 'Aucune affectation active avec supérieur hiérarchique : le N+1 ne peut pas être déterminé.'
        );

        return $compte;
    }

    public function trouverCompteN1(int $agentId): ?User
    {
        $affectation = $this->affectationRepository->getActive($agentId);
        if (! $affectation?->superieur_hierarchique_id) {
            return null;
        }

        return $this->userRepository->findByAgentId((int) $affectation->superieur_hierarchique_id);
    }
}
