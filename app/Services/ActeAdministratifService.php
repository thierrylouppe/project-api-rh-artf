<?php

namespace App\Services;

use App\Enums\StatutDossier;
use App\Enums\TypeActeAdministratif;
use App\Interfaces\ActeAdministratifInterface;
use App\Interfaces\DossierIntegrationInterface;
use App\Interfaces\HistoriqueIntegrationInterface;
use App\Models\ActeAdministratif;
use App\Models\DossierIntegration;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/** @property ActeAdministratifInterface $repository */
class ActeAdministratifService extends BaseService
{
    public function __construct(
        ActeAdministratifInterface $repository,
        private readonly DossierIntegrationInterface $dossierRepository,
        private readonly HistoriqueIntegrationInterface $historiqueRepository,
    ) {
        parent::__construct($repository);
    }

    /**
     * Enregistre l'acte d'entrée : type du dossier si omis, numéro auto, idempotent.
     *
     * @return array{acte: ActeAdministratif, cree: bool, dossier: DossierIntegration}
     */
    public function enregistrerPourDossier(
        int $dossierId,
        ?TypeActeAdministratif $typeActe = null,
        ?string $contenu = null
    ): array {
        return DB::transaction(function () use ($dossierId, $typeActe, $contenu) {
            /** @var DossierIntegration $dossier */
            $dossier = $this->dossierRepository->findById($dossierId);
            $dossier->load('typeIntegration');

            abort_unless(
                in_array($dossier->statut, [StatutDossier::VALIDE_DG, StatutDossier::INTEGRE], true),
                422,
                "L'acte ne peut être enregistré qu'après validation DG ou en post-intégration (statut actuel : {$dossier->statut->label()})."
            );

            $typeActe ??= $dossier->typeIntegration?->acteAdministratifEnum();

            abort_if(
                $typeActe === null,
                422,
                "Aucun acte administratif configuré pour le type d'intégration « {$dossier->typeIntegration?->nom} »."
            );

            $existant = $this->repository->trouverPourDossierEtType($dossierId, $typeActe->value);
            if ($existant !== null) {
                return [
                    'acte'    => $existant,
                    'cree'    => false,
                    'dossier' => $dossier,
                ];
            }

            $userId = Auth::id();
            abort_if($userId === null, 401, 'Non authentifié.');

            $numero = $this->repository->genererNumero($typeActe);
            $acte   = $this->repository->create([
                'dossier_integration_id' => $dossierId,
                'type_acte'              => $typeActe->value,
                'numero'                 => $numero,
                'contenu'                => $contenu,
            ]);

            $this->historiqueRepository->enregistrer(
                DossierIntegration::class,
                $dossierId,
                $userId,
                'acte_genere',
                null,
                ['acte_id' => $acte->id, 'numero' => $numero, 'type_acte' => $typeActe->value],
                "Acte {$typeActe->label()} enregistré (n° {$numero})"
            );

            return [
                'acte'    => $acte,
                'cree'    => true,
                'dossier' => $dossier->fresh(['typeIntegration', 'actes', 'agent']),
            ];
        });
    }

    public function signer(int $id): ActeAdministratif
    {
        $userId = Auth::id();
        abort_if($userId === null, 401, 'Non authentifié.');

        return $this->repository->signer($id, $userId);
    }

    public function getByDossier(int $dossierId): Collection
    {
        return $this->repository->getByDossier($dossierId);
    }
}
