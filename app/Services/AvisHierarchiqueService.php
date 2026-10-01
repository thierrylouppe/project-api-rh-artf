<?php

namespace App\Services;

use App\Enums\NiveauAvisHierarchique;
use App\Interfaces\AvisHierarchiqueInterface;
use App\Interfaces\EvaluationInterface;
use App\Models\AvisHierarchique;
use App\Models\Evaluation;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

/**
 * Gère le cycle de vie des avis hiérarchiques (CCN ARTF art. 64).
 *
 * Chaîne standard  : chef_bureau(1) → chef_service(2) → directeur(3) → directeur_general(4)
 * Chaîne DG        : chef_bureau(1) → chef_service(2) → directeur_general(3)
 *   → activée quand la direction de l'évalué a `rattache_dg = true`.
 *
 * Séquentialité : le niveau N ne peut être posé / signé que si le niveau N−1 est **signé**.
 * Signature définitive : un avis signé n'est plus modifiable.
 */
class AvisHierarchiqueService extends BaseService
{
    public function __construct(
        AvisHierarchiqueInterface            $repository,
        private readonly EvaluationInterface $evaluationRepository,
    ) {
        parent::__construct($repository);
    }

    // ----------------------------------------------------------------
    // Niveaux requis selon la position de l'évalué
    // ----------------------------------------------------------------

    /**
     * Détermine les niveaux d'avis requis pour une fiche, dans l'ordre.
     *
     * Logique :
     *   - Affectation dans un Bureau  → [chef_bureau, chef_service, directeur*, directeur_general]
     *   - Affectation dans un Service → [chef_service, directeur*, directeur_general]
     *   - Affectation dans une Direction → [directeur, directeur_general]
     *   - Pas d'affectation active   → [] (aucun avis requis, géré par sans-superieur)
     *
     *   * sauté si `direction.rattache_dg = true`
     *
     * @return NiveauAvisHierarchique[]
     */
    public function niveauxRequis(Evaluation $evaluation): array
    {
        $agent       = $evaluation->agent;
        $affectation = $agent?->affectationActive;

        if (! $affectation || ! $affectation->structurable_type || ! $affectation->structurable_id) {
            return [];
        }

        $structureType = class_basename($affectation->structurable_type);

        // Accès lazy à la structure polymorphe (Eloquent résout automatiquement)
        $structure = $affectation->structure;

        if (! $structure) {
            return [];
        }

        // Déterminer si la direction est rattachée à la DG (skip niveau directeur)
        $rattacheDg = $this->estRattacheDg($structureType, $structure);

        return match ($structureType) {
            'Bureau'    => $this->chaineDepuisBureau($rattacheDg),
            'Service'   => $this->chaineDepuisService($rattacheDg),
            'Direction' => $this->chaineDepuisDirection(),
            default     => [],
        };
    }

    /** Retourne les niveaux requis sous forme de strings (pour la DB). */
    public function niveauxRequisStrings(Evaluation $evaluation): array
    {
        return array_map(fn (NiveauAvisHierarchique $n) => $n->value, $this->niveauxRequis($evaluation));
    }

    // ----------------------------------------------------------------
    // Actions
    // ----------------------------------------------------------------

    /**
     * Poster ou mettre à jour un avis (non signé uniquement).
     *
     * Validations :
     *  1. Le niveau est dans les niveaux requis
     *  2. L'avis précédent dans la chaîne est signé (séquentialité)
     *  3. L'avis courant n'est pas encore signé (si existant)
     *
     * @throws ValidationException
     */
    public function poster(int $evaluationId, string $niveauStr, array $data, User $user): AvisHierarchique
    {
        $evaluation = $this->evaluationRepository->findById($evaluationId);
        $niveauxRequis = $this->niveauxRequisStrings($evaluation);

        // 1. Niveau valide ?
        if (! in_array($niveauStr, $niveauxRequis, true)) {
            throw ValidationException::withMessages([
                'niveau' => "Le niveau « {$niveauStr} » n'est pas requis pour cette fiche.",
            ]);
        }

        $niveau = NiveauAvisHierarchique::from($niveauStr);

        // 2. Séquentialité
        $this->assertPrecedentSigne($evaluationId, $niveau, $niveauxRequis);

        // 3. Avis existant signé ?
        $existant = $this->repository->trouverParNiveau($evaluationId, $niveauStr);
        if ($existant && $existant->signe) {
            throw ValidationException::withMessages([
                'signe' => 'Cet avis a déjà été signé. Il n\'est plus modifiable.',
            ]);
        }

        // Calculer l'ordre dans la chaîne
        $ordre = (int) array_search($niveauStr, $niveauxRequis, true) + 1;

        if ($existant) {
            return $this->repository->update($existant->id, [
                'avis'         => $data['avis']         ?? $existant->avis,
                'approuve'     => $data['approuve']      ?? $existant->approuve,
                'observations' => $data['observations']  ?? $existant->observations,
            ]);
        }

        return $this->repository->create([
            'evaluation_id' => $evaluationId,
            'niveau'        => $niveauStr,
            'avis'          => $data['avis']        ?? null,
            'approuve'      => $data['approuve']    ?? null,
            'observations'  => $data['observations'] ?? null,
            'signe'         => false,
            'ordre'         => $ordre,
        ]);
    }

    /**
     * Signer un avis (définitif).
     * Prérequis : l'avis précédent dans la chaîne doit être signé.
     *
     * @throws ValidationException
     */
    public function signer(int $avisId, User $user): AvisHierarchique
    {
        /** @var AvisHierarchique $avis */
        $avis = $this->repository->findById($avisId);

        if ($avis->signe) {
            throw ValidationException::withMessages([
                'signe' => 'Cet avis a déjà été signé.',
            ]);
        }

        $evaluation    = $this->evaluationRepository->findById($avis->evaluation_id);
        $niveauxRequis = $this->niveauxRequisStrings($evaluation);

        $this->assertPrecedentSigne($avis->evaluation_id, $avis->niveau, $niveauxRequis);

        return $this->repository->update($avisId, [
            'signe'          => true,
            'date_signature' => now(),
            'signe_par'      => $user->id,
        ]);
    }

    /**
     * Tous les avis requis d'une fiche sont-ils signés ?
     * (Utilisé par EvaluationStatutService avant envoi RH.)
     */
    public function tousAvisSignes(int $evaluationId): bool
    {
        $evaluation    = $this->evaluationRepository->findById($evaluationId);
        $niveauxRequis = $this->niveauxRequisStrings($evaluation);

        return $this->repository->tousNiveauxSignes($evaluationId, $niveauxRequis);
    }

    /** Liste les avis d'une fiche (ordonnés). */
    public function listeParEvaluation(int $evaluationId): Collection
    {
        return $this->repository->parEvaluation($evaluationId);
    }

    // ----------------------------------------------------------------
    // Helpers privés
    // ----------------------------------------------------------------

    /** @throws ValidationException */
    private function assertPrecedentSigne(
        int $evaluationId,
        NiveauAvisHierarchique $niveau,
        array $niveauxRequis
    ): void {
        $position = array_search($niveau->value, $niveauxRequis, true);
        if ($position === 0) {
            return; // premier niveau, toujours autorisé
        }

        $niveauPrecedentStr = $niveauxRequis[$position - 1];
        $precedent          = $this->repository->trouverParNiveau($evaluationId, $niveauPrecedentStr);

        if (! $precedent || ! $precedent->signe) {
            $precedentLabel = NiveauAvisHierarchique::from($niveauPrecedentStr)->label();
            throw ValidationException::withMessages([
                'ordre' => "L'avis du niveau « {$precedentLabel} » doit être signé en premier.",
            ]);
        }
    }

    private function estRattacheDg(string $structureType, mixed $structure): bool
    {
        return match ($structureType) {
            'Bureau'    => (bool) ($structure->service?->direction?->rattache_dg ?? false),
            'Service'   => (bool) ($structure->direction?->rattache_dg ?? false),
            'Direction' => (bool) ($structure->rattache_dg ?? false),
            default     => false,
        };
    }

    /** @return NiveauAvisHierarchique[] */
    private function chaineDepuisBureau(bool $rattacheDg): array
    {
        $chain = [
            NiveauAvisHierarchique::CHEF_BUREAU,
            NiveauAvisHierarchique::CHEF_SERVICE,
        ];
        if (! $rattacheDg) {
            $chain[] = NiveauAvisHierarchique::DIRECTEUR;
        }
        $chain[] = NiveauAvisHierarchique::DIRECTEUR_GENERAL;

        return $chain;
    }

    /** @return NiveauAvisHierarchique[] */
    private function chaineDepuisService(bool $rattacheDg): array
    {
        $chain = [NiveauAvisHierarchique::CHEF_SERVICE];
        if (! $rattacheDg) {
            $chain[] = NiveauAvisHierarchique::DIRECTEUR;
        }
        $chain[] = NiveauAvisHierarchique::DIRECTEUR_GENERAL;

        return $chain;
    }

    /** @return NiveauAvisHierarchique[] */
    private function chaineDepuisDirection(): array
    {
        return [
            NiveauAvisHierarchique::DIRECTEUR,
            NiveauAvisHierarchique::DIRECTEUR_GENERAL,
        ];
    }
}
