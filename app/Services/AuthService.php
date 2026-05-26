<?php

namespace App\Services;

use App\Interfaces\UserInterface;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthService
{
    public function __construct(private UserInterface $userRepository) {}

    public function login(string $email, string $password): array
    {
        $user = $this->userRepository->findByEmail($email);

        if (! $user || ! Hash::check($password, $user->password)) {
            throw ValidationException::withMessages(['email' => ['Identifiants invalides.']]);
        }

        if (! $user->is_active) {
            throw ValidationException::withMessages(['email' => ['Compte désactivé.']]);
        }

        $token = $user->createToken('api-token')->plainTextToken;

        return ['user' => $user->load('roles.permissions'), 'token' => $token];
    }

    public function logout(User $user): void
    {
        $user->currentAccessToken()?->delete();
    }

    public function me(User $user): User
    {
        return $user->load('roles.permissions');
    }
}
