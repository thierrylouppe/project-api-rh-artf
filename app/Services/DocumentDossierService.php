<?php

namespace App\Services;

use App\Enums\PieceCcnArt46;
use App\Interfaces\DocumentDossierInterface;
use App\Interfaces\DossierIntegrationInterface;
use App\Interfaces\TypeDocumentInterface;
use App\Models\DocumentDossier;
use App\Models\DossierIntegration;
use App\Models\TypeDocument;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

class DocumentDossierService extends BaseService
{
    /** @var Collection<int, TypeDocument>|null */
    private ?Collection $typesCcnConditionnels = null;

    /** @var array<int, list<int>> */
    private array $idsObligatoiresParDossier = [];

    public function __construct(
        DocumentDossierInterface $repository,
        private readonly DossierIntegrationInterface $dossierRepository,
        private readonly TypeDocumentInterface $typeDocumentRepository,
    ) {
        parent::__construct($repository);
    }

    public function getByDossier(int $dossierId): Collection
    {
        return $this->repository->getByDossier($dossierId);
    }

    /**
     * @return array{
     *     deposes: Collection<int, DocumentDossier>,
     *     manquants: Collection<int, array{type_document: TypeDocument, est_obligatoire: bool}>,
     *     non_valides: Collection<int, DocumentDossier>,
     *     resume: array<string, int|bool>
     * }
     */
    public function getEtatDocuments(int $dossierId): array
    {
        $dossier  = $this->chargerDossierPourPieces($dossierId);
        $deposes  = $this->repository->getByDossier($dossierId);
        $attendus = $this->resoudreDocumentsAttendus($dossier);
        $idsObligatoires = $this->idsObligatoiresPour($dossier);
        $idsDeposes = $deposes->pluck('type_document_id')->unique();

        $manquants = $attendus
            ->reject(fn (TypeDocument $type) => $idsDeposes->contains($type->id))
            ->map(fn (TypeDocument $type) => [
                'type_document'   => $type,
                'est_obligatoire' => in_array($type->id, $idsObligatoires, true),
            ])
            ->values();

        $nonValides = $deposes
            ->filter(fn (DocumentDossier $document) => in_array($document->type_document_id, $idsObligatoires, true))
            ->reject(fn (DocumentDossier $document) => $document->est_valide)
            ->values();

        $obligatoires = $attendus->filter(
            fn (TypeDocument $type) => in_array($type->id, $idsObligatoires, true)
        );
        $optionnels = $attendus->reject(
            fn (TypeDocument $type) => in_array($type->id, $idsObligatoires, true)
        );

        $obligatoiresDeposes = $obligatoires->filter(fn (TypeDocument $type) => $idsDeposes->contains($type->id));
        $optionnelsDeposes   = $optionnels->filter(fn (TypeDocument $type) => $idsDeposes->contains($type->id));

        return [
            'deposes'     => $deposes,
            'manquants'   => $manquants,
            'non_valides' => $nonValides,
            'resume'      => [
                'total_deposes'             => $deposes->count(),
                'types_deposes'             => $idsDeposes->count(),
                'obligatoires_attendus'     => $obligatoires->count(),
                'obligatoires_deposes'      => $obligatoiresDeposes->count(),
                'obligatoires_manquants'    => $obligatoires->count() - $obligatoiresDeposes->count(),
                'optionnels_attendus'       => $optionnels->count(),
                'optionnels_deposes'        => $optionnelsDeposes->count(),
                'optionnels_manquants'      => $optionnels->count() - $optionnelsDeposes->count(),
                'tous_obligatoires_deposes' => $obligatoires->count() === $obligatoiresDeposes->count(),
            ],
        ];
    }

    /**
     * Types uploadables : pivot du type + pièces CCN conditionnelles pour une embauche.
     *
     * @return list<int>|null  null si dossier introuvable
     */
    public function idsTypesAutorisesPourDossier(int $dossierId): ?array
    {
        try {
            $dossier = $this->chargerDossierPourPieces($dossierId);
        } catch (ModelNotFoundException) {
            return null;
        }

        return $this->idsTypesAutorises($dossier);
    }

    /**
     * @return list<int>
     */
    public function idsTypesAutorises(DossierIntegration $dossier): array
    {
        $dossier->loadMissing('typeIntegration.documentsObligatoires');
        $type = $dossier->typeIntegration;
        $ids  = $type?->documentsObligatoires?->pluck('id')->all() ?? [];

        if ($ids === []) {
            return [];
        }

        if ($type?->estEmbaucheCcn()) {
            $ids = array_merge($ids, $this->typesDocumentsCcnConditionnels()->pluck('id')->all());
        }

        return array_values(array_unique($ids));
    }

    public function valider(int $id, ?string $commentaire = null): DocumentDossier
    {
        return $this->repository->validerDocument($id, Auth::id(), $commentaire);
    }

    public function tousObligatoiresValides(int $dossierId): bool
    {
        $etat = $this->getEtatDocuments($dossierId);

        return $etat['resume']['tous_obligatoires_deposes'] && $etat['non_valides']->isEmpty();
    }

    public function tousObligatoiresDeposes(int $dossierId): bool
    {
        return $this->getEtatDocuments($dossierId)['resume']['tous_obligatoires_deposes'];
    }

    public function getDocumentsObligatoiresManquants(int $dossierId): Collection
    {
        return collect($this->getEtatDocuments($dossierId)['manquants'])
            ->filter(fn (array $item) => $item['est_obligatoire'])
            ->values();
    }

    public function getDocumentsObligatoiresNonValides(int $dossierId): Collection
    {
        return $this->getEtatDocuments($dossierId)['non_valides'];
    }

    private function chargerDossierPourPieces(int $dossierId): DossierIntegration
    {
        $dossier = $this->dossierRepository->findById($dossierId);
        $dossier->loadMissing(['typeIntegration.documentsObligatoires', 'agent.situationFamiliale']);

        return $dossier;
    }

    private function resoudreDocumentsAttendus(DossierIntegration $dossier): Collection
    {
        $type = $dossier->typeIntegration;
        $type?->loadMissing('documentsObligatoires');

        $base = ($type && $type->documentsObligatoires->isNotEmpty())
            ? $type->documentsObligatoires->values()
            : $this->typeDocumentRepository->getAll()
                ->filter(fn (TypeDocument $item) => $item->obligatoire)
                ->values();

        if (! $type?->estEmbaucheCcn()) {
            return $base;
        }

        return $base
            ->concat($this->typesDocumentsCcnConditionnels())
            ->unique('id')
            ->values();
    }

    /**
     * @return list<int>
     */
    private function idsObligatoiresPour(DossierIntegration $dossier): array
    {
        $cle = (int) $dossier->id;
        if (isset($this->idsObligatoiresParDossier[$cle])) {
            return $this->idsObligatoiresParDossier[$cle];
        }

        $integration = $dossier->typeIntegration;
        $integration?->loadMissing('documentsObligatoires');
        $idsPivot = $integration?->documentsObligatoires?->pluck('id')->all() ?? [];
        $idsCond  = $this->piecesConditionnellesObligatoires($dossier)->pluck('id')->all();

        if ($idsPivot !== []) {
            $ids = array_values(array_unique(array_merge($idsPivot, $idsCond)));
        } else {
            $idsGlobaux = $this->typeDocumentRepository->getObligatoires()->pluck('id')->all();
            $ids = array_values(array_unique(array_merge($idsGlobaux, $idsCond)));
        }

        return $this->idsObligatoiresParDossier[$cle] = $ids;
    }

    private function piecesConditionnellesObligatoires(DossierIntegration $dossier): Collection
    {
        if (! $dossier->typeIntegration?->estEmbaucheCcn()) {
            return collect();
        }

        $parNom = $this->typesDocumentsCcnConditionnels()->keyBy('nom');
        $requis = collect();

        if ($dossier->agent?->situationFamiliale?->statut_matrimonial === 'marie') {
            $requis->push($parNom->get(PieceCcnArt46::ACTE_MARIAGE->value));
        }

        if ($dossier->deja_salarie) {
            $requis->push($parNom->get(PieceCcnArt46::CARTE_TRAVAIL->value));
            $requis->push($parNom->get(PieceCcnArt46::CERTIFICAT_TRAVAIL->value));

            if (! filled($dossier->agent?->numero_cnss)) {
                $requis->push($parNom->get(PieceCcnArt46::NUMERO_CNSS->value));
            }
        }

        return $requis->filter()->unique('id')->values();
    }

    private function typesDocumentsCcnConditionnels(): Collection
    {
        return $this->typesCcnConditionnels ??= $this->typeDocumentRepository->findByNoms(
            array_map(fn (PieceCcnArt46 $piece) => $piece->value, PieceCcnArt46::conditionnelles())
        );
    }
}
