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
     * Génère un acte administratif pour un dossier et fait passer ce dernier
     * au statut ACTE_GENERE — le tout dans une transaction atomique.
     *
     * Guards :
     *  - Le dossier doit être en VALIDE_DG.
     *  - Aucun acte du même type ne doit déjà exister pour ce dossier.
     */
    public function generer(int $dossierId, TypeActeAdministratif $typeActe, ?string $contenu = null): ActeAdministratif
    {
        return DB::transaction(function () use ($dossierId, $typeActe, $contenu) {
            /** @var DossierIntegration $dossier */
            $dossier = $this->dossierRepository->findById($dossierId);

            abort_unless(
                $dossier->statut === StatutDossier::VALIDE_DG,
                422,
                "L'acte ne peut être généré qu'après la validation DG (statut actuel : {$dossier->statut->label()})."
            );

            abort_if(
                $this->repository->acteExistePourType($dossierId, $typeActe->value),
                422,
                "Un acte « {$typeActe->label()} » existe déjà pour ce dossier."
            );

            $numero = $this->repository->genererNumero($typeActe);

            $acte = $this->repository->create([
                'dossier_integration_id' => $dossierId,
                'type_acte'              => $typeActe->value,
                'numero'                 => $numero,
                'contenu'                => $contenu,
            ]);

            // Transition VALIDE_DG → ACTE_GENERE (validation de la transition + historique)
            abort_unless(
                $dossier->statut->peutTransitionnerVers(StatutDossier::ACTE_GENERE),
                422,
                "Transition invalide : {$dossier->statut->label()} → Acte généré."
            );

            $this->dossierRepository->changerStatut($dossierId, StatutDossier::ACTE_GENERE);

            $this->historiqueRepository->enregistrer(
                DossierIntegration::class,
                $dossierId,
                Auth::id() ?? 1,
                'transition_statut',
                ['statut' => $dossier->statut->value],
                ['statut' => StatutDossier::ACTE_GENERE->value],
                "Acte {$typeActe->label()} généré (n° {$numero})"
            );

            return $acte;
        });
    }

    public function signer(int $id): ActeAdministratif
    {
        return $this->repository->signer($id, Auth::id());
    }

    public function getByDossier(int $dossierId): Collection
    {
        return $this->repository->getByDossier($dossierId);
    }
}
