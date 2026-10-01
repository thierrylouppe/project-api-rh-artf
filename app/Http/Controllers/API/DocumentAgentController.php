<?php

namespace App\Http\Controllers\API;

use App\Http\Requests\DocumentAgent\CreateRequest;
use App\Http\Resources\DocumentAgentResource;
use App\Services\DocumentAgentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DocumentAgentController extends BaseController
{
    public function __construct(DocumentAgentService $service)
    {
        parent::__construct($service);
    }

    protected function resource(): string
    {
        return DocumentAgentResource::class;
    }

    public function lister(Request $request, int $agent): JsonResponse
    {
        $items = $this->service->getByAgent($agent, $request->query());

        return $this->collectionResponse(DocumentAgentResource::collection($items));
    }

    public function arborescence(int $agent): JsonResponse
    {
        $groupes = collect($this->service->arborescence($agent))->map(fn (array $groupe) => [
            'sous_dossier' => $groupe['sous_dossier'],
            'documents'    => DocumentAgentResource::collection($groupe['documents']),
        ]);

        return response()->json([
            'data'    => $groupes,
            'message' => 'Arborescence récupérée',
        ]);
    }

    public function store(CreateRequest $request, int $agent): JsonResponse
    {
        $document = $this->service->upload(
            $agent,
            $request->safe()->except('fichier'),
            $request->file('fichier')
        );

        return $this->respond($document, 'Document ajouté', 201);
    }

    public function afficher(int $agent, int $id): JsonResponse
    {
        return $this->respond($this->service->findForAgent($agent, $id));
    }

    public function telecharger(int $agent, int $id): StreamedResponse
    {
        return $this->service->telecharger($agent, $id);
    }

    public function supprimer(int $agent, int $id): JsonResponse
    {
        $this->service->supprimer($agent, $id);

        return $this->messageResponse('Document archivé');
    }
}
