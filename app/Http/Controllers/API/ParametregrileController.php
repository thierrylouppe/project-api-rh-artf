<?php

namespace App\Http\Controllers\API;

use App\Http\Requests\Parametregrile\UpdateRequest;
use App\Http\Resources\ParametregrileResource;
use App\Services\ParametregrileService;
use Illuminate\Http\JsonResponse;

class ParametregrileController extends BaseController
{
    public function __construct(ParametregrileService $service)
    {
        parent::__construct($service);
    }

    protected function resource(): string
    {
        return ParametregrileResource::class;
    }

    /** Retourne l'unique ligne de paramètres (ou la crée avec les valeurs par défaut). */
    public function current(): JsonResponse
    {
        return $this->respond($this->service->getCurrent());
    }

    /** Met à jour les paramètres courants de la grille. */
    public function update(UpdateRequest $request, int $parametregrile): JsonResponse
    {
        return $this->respond($this->service->update($parametregrile, $request->validated()), 'Paramètres mis à jour avec succès');
    }
}
