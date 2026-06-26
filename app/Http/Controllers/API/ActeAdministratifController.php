<?php

namespace App\Http\Controllers\API;

use App\Enums\TypeActeAdministratif;
use App\Http\Requests\ActeAdministratif\GenererRequest;
use App\Http\Resources\ActeAdministratifResource;
use App\Services\ActeAdministratifService;
use Illuminate\Http\JsonResponse;

/** @property ActeAdministratifService $service */
class ActeAdministratifController extends BaseController
{
    public function __construct(ActeAdministratifService $service)
    {
        parent::__construct($service);
    }

    protected function resource(): string
    {
        return ActeAdministratifResource::class;
    }

    public function generer(GenererRequest $request, int $dossierId): JsonResponse
    {
        $typeActe = TypeActeAdministratif::from($request->input('type_acte'));
        $acte     = $this->service->generer($dossierId, $typeActe, $request->input('contenu'));

        return $this->respond($acte, "Acte {$typeActe->label()} généré avec le numéro {$acte->numero}", 201);
    }

    public function signer(int $id): JsonResponse
    {
        $acte = $this->service->signer($id);

        return $this->respond($acte, 'Acte signé');
    }

    public function byDossier(int $dossierId): JsonResponse
    {
        $actes = $this->service->getByDossier($dossierId);

        return response()->json(['data' => ActeAdministratifResource::collection($actes)]);
    }
}
