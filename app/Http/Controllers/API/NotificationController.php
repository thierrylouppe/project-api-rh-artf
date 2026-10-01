<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\NotificationResource;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class NotificationController extends Controller
{
    public function __construct(private readonly NotificationService $notificationService) {}

    #[OA\Get(
        path: '/api/notifications',
        operationId: 'listerNotifications',
        tags: ['Notifications'],
        summary: 'Inbox de l\'utilisateur connecté',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'non_lues', in: 'query', schema: new OA\Schema(type: 'boolean')),
            new OA\Parameter(name: 'per_page', in: 'query', schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'page', in: 'query', schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Liste paginée'),
            new OA\Response(response: 401, description: 'Non authentifié'),
        ]
    )]
    public function index(Request $request): JsonResponse
    {
        $user   = $request->user();
        $items  = $this->notificationService->lister($user, $request->query());
        $nonLues = $this->notificationService->countNonLues($user);

        return response()->json([
            'data' => NotificationResource::collection($items->items()),
            'meta' => [
                'current_page' => $items->currentPage(),
                'last_page'    => $items->lastPage(),
                'per_page'     => $items->perPage(),
                'total'        => $items->total(),
                'non_lues'     => $nonLues,
            ],
            'message' => 'Notifications récupérées',
        ]);
    }

    #[OA\Get(
        path: '/api/notifications/non-lues',
        operationId: 'notificationsNonLues',
        tags: ['Notifications'],
        summary: 'Notifications non lues',
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(response: 200, description: 'Liste'),
            new OA\Response(response: 401, description: 'Non authentifié'),
        ]
    )]
    public function nonLues(Request $request): JsonResponse
    {
        $user  = $request->user();
        $items = $this->notificationService->nonLues($user);

        return response()->json([
            'data'    => NotificationResource::collection($items),
            'meta'    => ['non_lues' => $items->count()],
            'message' => 'Notifications non lues',
        ]);
    }

    #[OA\Post(
        path: '/api/notifications/{id}/lu',
        operationId: 'marquerNotificationLue',
        tags: ['Notifications'],
        summary: 'Marquer une notification comme lue',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Marquée lue'),
            new OA\Response(response: 401, description: 'Non authentifié'),
            new OA\Response(response: 404, description: 'Introuvable'),
        ]
    )]
    public function marquerLu(Request $request, string $id): JsonResponse
    {
        $this->notificationService->marquerLu($request->user(), $id);

        return response()->json(['message' => 'Notification marquée comme lue']);
    }

    #[OA\Post(
        path: '/api/notifications/tout-lire',
        operationId: 'marquerToutesNotificationsLues',
        tags: ['Notifications'],
        summary: 'Marquer toutes les notifications comme lues',
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(response: 200, description: 'Toutes lues'),
            new OA\Response(response: 401, description: 'Non authentifié'),
        ]
    )]
    public function toutLire(Request $request): JsonResponse
    {
        $count = $this->notificationService->toutLire($request->user());

        return response()->json([
            'data'    => ['mises_a_jour' => $count],
            'message' => 'Toutes les notifications ont été marquées comme lues',
        ]);
    }
}
