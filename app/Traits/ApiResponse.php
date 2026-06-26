<?php

namespace App\Traits;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;

trait ApiResponse
{
    /**
     * Réponse succès avec une ressource unique.
     */
    protected function successResponse(
        mixed $data,
        ?string $message = null,
        int $status = 200
    ): JsonResponse {
        $payload = ['success' => true];

        if ($message !== null) {
            $payload['message'] = $message;
        }

        if ($data instanceof JsonResource) {
            $payload['data'] = $data;
        } else {
            $payload['data'] = $data;
        }

        return response()->json($payload, $status);
    }

    /**
     * Réponse succès pour une collection.
     */
    protected function collectionResponse(
        mixed $collection,
        ?string $message = null,
        int $status = 200
    ): JsonResponse {
        $payload = ['success' => true];

        if ($message !== null) {
            $payload['message'] = $message;
        }

        $payload['data'] = $collection;

        return response()->json($payload, $status);
    }

    /**
     * Réponse succès sans données (ex. suppression).
     */
    protected function messageResponse(string $message, int $status = 200): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
        ], $status);
    }

    /**
     * Réponse d'erreur métier.
     */
    protected function errorResponse(
        string $message,
        int $status = 422,
        ?array $errors = null
    ): JsonResponse {
        $payload = [
            'success' => false,
            'message' => $message,
        ];

        if ($errors !== null) {
            $payload['errors'] = $errors;
        }

        return response()->json($payload, $status);
    }
}
