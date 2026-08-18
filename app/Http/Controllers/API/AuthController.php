<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Resources\UserResource;
use App\Services\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class AuthController extends Controller
{
    public function __construct(private AuthService $authService) {}

    #[OA\Post(
        path: '/api/login',
        operationId: 'login',
        tags: ['Auth'],
        summary: 'Connexion',
        description: 'Retourne un token Sanctum. Utiliser ensuite Authorize → Bearer {token}.',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/LoginRequest')
        ),
        responses: [
            new OA\Response(response: 200, description: 'Connexion réussie', content: new OA\JsonContent(ref: '#/components/schemas/LoginResponse')),
            new OA\Response(response: 422, description: 'Données invalides', content: new OA\JsonContent(ref: '#/components/schemas/Error422')),
        ]
    )]
    public function login(LoginRequest $request): JsonResponse
    {
        $result = $this->authService->login(
            $request->validated('email'),
            $request->validated('password')
        );

        return response()->json([
            'data' => [
                'user' => new UserResource($result['user']),
                'token' => $result['token'],
            ],
            'message' => 'Connexion réussie',
        ]);
    }

    #[OA\Post(
        path: '/api/logout',
        operationId: 'logout',
        tags: ['Auth'],
        summary: 'Déconnexion',
        description: 'Révoque le token Sanctum courant.',
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(response: 200, description: 'Déconnexion réussie', content: new OA\JsonContent(ref: '#/components/schemas/MessageResponse')),
            new OA\Response(response: 401, description: 'Non authentifié', content: new OA\JsonContent(ref: '#/components/schemas/Error401')),
        ]
    )]
    public function logout(Request $request): JsonResponse
    {
        $this->authService->logout($request->user());

        return response()->json(['message' => 'Déconnexion réussie']);
    }

    #[OA\Get(
        path: '/api/user',
        operationId: 'me',
        tags: ['Auth'],
        summary: 'Profil utilisateur connecté',
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(response: 200, description: 'Profil', content: new OA\JsonContent(ref: '#/components/schemas/UserResponse')),
            new OA\Response(response: 401, description: 'Non authentifié', content: new OA\JsonContent(ref: '#/components/schemas/Error401')),
        ]
    )]
    public function me(Request $request): JsonResponse
    {
        $user = $this->authService->me($request->user());

        return response()->json(['data' => new UserResource($user)]);
    }
}
