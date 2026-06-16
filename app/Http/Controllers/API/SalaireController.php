<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\Salaire\GenerateRequest;
use App\Http\Resources\SalaireResource;
use App\Services\SalaireService;
use Illuminate\Http\JsonResponse;

class SalaireController extends Controller
{
    public function __construct(private readonly SalaireService $service) {}

    /** Liste la grille salariale complète avec classes, catégories et grades. */
    public function index(): JsonResponse
    {
        $grille = $this->service->getGrille();

        return response()->json([
            'data'  => SalaireResource::collection($grille),
            'total' => $grille->count(),
        ]);
    }

    /**
     * Génère (ou régénère) la grille salariale complète.
     * Valeur du point d'indice : fournie dans le body ou lue depuis parametregrilles.
     */
    public function generate(GenerateRequest $request): JsonResponse
    {
        $total = $this->service->generateGrille(
            $request->validated('valeur_point_indice')
                ? (float) $request->validated('valeur_point_indice')
                : null
        );

        return response()->json([
            'success' => true,
            'message' => "Grille générée avec succès ({$total} lignes).",
            'data'    => null,
        ]);
    }
}
