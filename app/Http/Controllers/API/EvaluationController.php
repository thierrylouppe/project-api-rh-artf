<?php

namespace App\Http\Controllers\API;

use App\Http\Requests\Evaluation\AvisEtSignerRequest;
use App\Http\Requests\Evaluation\NoterRequest;
use App\Http\Requests\Evaluation\ReclamationRequest;
use App\Http\Requests\Evaluation\UpdateContexteRequest;
use App\Http\Requests\Evaluation\ValidationRhRequest;
use App\Http\Resources\EvaluationResource;
use App\Http\Resources\NoteEvaluationResource;
use App\Services\EvaluationService;
use App\Services\EvaluationStatutService;
use App\Services\NoteCalculationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

/**
 * Gestion des fiches d'évaluation individuelles.
 *
 * Endpoints Phase 1 :
 *   – RH        : GET liste, show, validation/rejet, annulation
 *   – N+1 (notateur) : mes fiches à noter, noter, signer
 *   – Agent     : mes fiches, signer
 *
 * CCN ARTF art. 63–70.
 */
class EvaluationController extends BaseController
{
    public function __construct(
        EvaluationService                       $evaluationService,
        private readonly NoteCalculationService $noteService,
        private readonly EvaluationStatutService $statutService,
    ) {
        parent::__construct($evaluationService);
    }

    protected function resource(): string
    {
        return EvaluationResource::class;
    }

    protected function showRelations(): array
    {
        return ['agent', 'superieur', 'session', 'notes.question', 'reclamation.agent', 'avisHierarchiques.signePar'];
    }

    // ----------------------------------------------------------------
    // Vues RH
    // ----------------------------------------------------------------

    #[OA\Get(
        path: '/api/avancements/evaluations',
        operationId: 'listEvaluations',
        tags: ['Évaluation'],
        summary: 'Liste de toutes les fiches (vue RH)',
        security: [['bearerAuth' => []]],
        responses: [new OA\Response(response: 200, description: 'Liste des fiches')]
    )]
    public function index(Request $request): JsonResponse
    {
        return parent::index($request);
    }

    #[OA\Get(
        path: '/api/avancements/evaluations/{id}',
        operationId: 'showEvaluation',
        tags: ['Évaluation'],
        summary: 'Détail d\'une fiche avec les notes',
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [new OA\Response(response: 200, description: 'Fiche complète')]
    )]
    public function show(Request $request): JsonResponse
    {
        return parent::show($request);
    }

    // ----------------------------------------------------------------
    // Vues notateur (N+1)
    // ----------------------------------------------------------------

    #[OA\Get(
        path: '/api/avancements/evaluations/superieur/mes-evaluations',
        operationId: 'mesEvaluationsSuperieur',
        tags: ['Évaluation'],
        summary: 'Fiches à noter par le notateur connecté',
        security: [['bearerAuth' => []]],
        responses: [new OA\Response(response: 200, description: 'Fiches à noter')]
    )]
    public function mesEvaluationsSuperieur(Request $request): JsonResponse
    {
        $agentId = $request->user()->agent_id;

        abort_unless($agentId, 403, 'Aucun agent associé à ce compte utilisateur.');

        /** @var EvaluationService $svc */
        $svc   = $this->service;
        $items = $svc->getBySuperieur((int) $agentId);

        return $this->collectionResponse(EvaluationResource::collection($items));
    }

    #[OA\Post(
        path: '/api/avancements/evaluations/{id}/noter',
        operationId: 'noterEvaluation',
        tags: ['Évaluation'],
        summary: 'Saisir ou modifier la note d\'un critère',
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [new OA\Response(response: 200, description: 'Note enregistrée, fiche recalculée')]
    )]
    public function noter(NoterRequest $request, int $id): JsonResponse
    {
        $data       = $request->validated();
        $evaluation = $this->noteService->noter(
            $id,
            (int) $data['question_id'],
            (float) $data['note_obtenue'],
            $data['commentaire'] ?? null,
        );

        $evaluation->load(['notes.question', 'agent', 'superieur', 'session']);

        return $this->respond($evaluation, 'Note enregistrée.');
    }

    #[OA\Put(
        path: '/api/avancements/evaluations/{id}/contexte',
        operationId: 'updateContexteEvaluation',
        tags: ['Évaluation'],
        summary: 'Mettre à jour le contexte de la fiche (absences, sanctions, avis)',
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [new OA\Response(response: 200, description: 'Contexte mis à jour')]
    )]
    public function updateContexte(UpdateContexteRequest $request, int $id): JsonResponse
    {
        $evaluation = $this->service->update($id, $request->validated());

        return $this->respond($evaluation, 'Contexte mis à jour.');
    }

    #[OA\Post(
        path: '/api/avancements/evaluations/{id}/signer-evaluateur',
        operationId: 'signerEvaluateur',
        tags: ['Évaluation'],
        summary: 'Le notateur signe la fiche (art. 63) — Phase 1 (sans avis). Utiliser avis-et-signer en Phase 2.',
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [new OA\Response(response: 200, description: 'Fiche signée par le notateur')]
    )]
    public function signerEvaluateur(int $id): JsonResponse
    {
        $evaluation = $this->statutService->signerEvaluateur($id);

        return $this->respond($evaluation, 'Fiche signée par le notateur.');
    }

    // ----------------------------------------------------------------
    // Phase 2 — Avis + Signature notateur, réclamation, envoi RH
    // ----------------------------------------------------------------

    #[OA\Post(
        path: '/api/avancements/evaluations/{id}/avis-et-signer',
        operationId: 'avisEtSigner',
        tags: ['Évaluation'],
        summary: 'Le notateur donne son avis (min 10 chars) ET signe la fiche (art. 63)',
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [new OA\Response(response: 200, description: 'Fiche signée avec avis')]
    )]
    public function avisEtSigner(AvisEtSignerRequest $request, int $id): JsonResponse
    {
        $evaluation = $this->statutService->signerEvaluateurAvecAvis(
            $id,
            $request->validated('avis_superieur')
        );

        return $this->respond($evaluation, 'Avis enregistré. Fiche signée par le notateur.');
    }

    // ----------------------------------------------------------------
    // Vues agent évalué
    // ----------------------------------------------------------------

    #[OA\Get(
        path: '/api/avancements/evaluations/agent/mes-evaluations',
        operationId: 'mesEvaluationsAgent',
        tags: ['Évaluation'],
        summary: 'Historique des évaluations de l\'agent connecté',
        security: [['bearerAuth' => []]],
        responses: [new OA\Response(response: 200, description: 'Fiches de l\'agent')]
    )]
    public function mesEvaluationsAgent(Request $request): JsonResponse
    {
        $agentId = $request->user()->agent_id;

        abort_unless($agentId, 403, 'Aucun agent associé à ce compte utilisateur.');

        /** @var EvaluationService $svc */
        $svc   = $this->service;
        $items = $svc->getByAgent((int) $agentId);

        return $this->collectionResponse(EvaluationResource::collection($items));
    }

    #[OA\Post(
        path: '/api/avancements/evaluations/{id}/signer-evalue',
        operationId: 'signerEvalue',
        tags: ['Évaluation'],
        summary: 'L\'agent signe sa fiche (art. 63)',
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [new OA\Response(response: 200, description: 'Fiche signée par l\'agent')]
    )]
    public function signerEvalue(int $id): JsonResponse
    {
        $evaluation = $this->statutService->signerEvalue($id);

        return $this->respond($evaluation, 'Fiche signée par l\'agent.');
    }

    #[OA\Post(
        path: '/api/avancements/evaluations/{id}/reclamer',
        operationId: 'reclamer',
        tags: ['Évaluation'],
        summary: 'L\'agent dépose une réclamation après avoir signé (art. 65)',
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [new OA\Response(response: 200, description: 'Réclamation déposée')]
    )]
    public function reclamer(ReclamationRequest $request, int $id): JsonResponse
    {
        $evaluation = $this->statutService->reclamer(
            $id,
            $request->user()->agent_id,
            $request->validated('motif')
        );

        $evaluation->load(['agent', 'superieur', 'session', 'notes.question', 'reclamation.agent']);

        return $this->respond($evaluation, 'Réclamation déposée. La RH va traiter votre contestation.');
    }

    #[OA\Post(
        path: '/api/avancements/evaluations/{id}/envoyer-rh',
        operationId: 'envoyerRh',
        tags: ['Évaluation'],
        summary: 'Envoyer la fiche en validation RH (depuis signee_evalue)',
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [new OA\Response(response: 200, description: 'Fiche transmise à la RH')]
    )]
    public function envoyerRh(int $id): JsonResponse
    {
        $evaluation = $this->statutService->envoyerEnValidationRh($id);

        return $this->respond($evaluation, 'Fiche transmise à la RH pour validation.');
    }

    // ----------------------------------------------------------------
    // Validation RH
    // ----------------------------------------------------------------

    #[OA\Post(
        path: '/api/avancements/evaluations/{id}/valider-rh',
        operationId: 'validerEvaluationRh',
        tags: ['Évaluation'],
        summary: 'La RH valide la fiche conforme (art. 66)',
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [new OA\Response(response: 200, description: 'Fiche finalisée')]
    )]
    public function validerRh(ValidationRhRequest $request, int $id): JsonResponse
    {
        $data = $request->validated();

        if ($data['conforme']) {
            $evaluation = $this->statutService->validerRh($id, $request->user(), $data['commentaire'] ?? null);
            $message    = 'Fiche validée conforme. Avancement programmable.';
        } else {
            $evaluation = $this->statutService->rejeterRh($id, $request->user(), $data['commentaire'] ?? null);
            $message    = 'Fiche rejetée. Retour au notateur pour correction.';
        }

        return $this->respond($evaluation, $message);
    }

    #[OA\Post(
        path: '/api/avancements/evaluations/{id}/annuler',
        operationId: 'annulerEvaluation',
        tags: ['Évaluation'],
        summary: 'La RH annule une fiche',
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [new OA\Response(response: 200, description: 'Fiche annulée')]
    )]
    public function annulerFiche(Request $request, int $id): JsonResponse
    {
        $evaluation = $this->statutService->annuler($id, $request->user(), $request->input('commentaire'));

        return $this->respond($evaluation, 'Fiche annulée.');
    }

    #[OA\Put(
        path: '/api/avancements/evaluations/{id}/superieur',
        operationId: 'reattribuerSuperieur',
        tags: ['Évaluation'],
        summary: 'Réattribuer le notateur (N+1) d\'une fiche — RH uniquement, session ouverte',
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [new OA\Response(response: 200, description: 'Notateur réattribué')]
    )]
    public function reattribuerSuperieur(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'superieur_id' => ['required', 'integer', 'exists:agents,id'],
        ]);

        /** @var \App\Models\Evaluation $evaluation */
        $evaluation = $this->service->findById($id);

        abort_unless(
            $evaluation->session->statut === \App\Enums\StatutSessionEvaluation::OUVERTE,
            422,
            'La réattribution n\'est possible que tant que la session est ouverte.'
        );

        abort_unless(
            ! $evaluation->statut->estTerminee(),
            422,
            'Impossible de réattribuer le notateur d\'une fiche terminée ou annulée.'
        );

        $evaluation = $this->service->update($id, ['superieur_id' => $request->input('superieur_id')]);

        return $this->respond($evaluation, 'Notateur réattribué.');
    }
}
