<?php

namespace App\Http\Controllers\API;

use App\Http\Resources\AuditLogResource;
use App\Services\AuditLogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuditLogController extends BaseController
{
    public function __construct(AuditLogService $service)
    {
        parent::__construct($service);
    }

    protected function resource(): string
    {
        return AuditLogResource::class;
    }

    public function index(Request $request): JsonResponse
    {
        $items = $this->service->getAll($request->query());
        $items->load('user');

        return response()->json(['data' => AuditLogResource::collection($items)]);
    }
}
