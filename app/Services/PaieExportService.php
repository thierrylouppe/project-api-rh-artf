<?php

namespace App\Services;

use App\Interfaces\PaieLotInterface;
use App\Models\PaieLot;
use App\Models\PaieLotLigne;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PaieExportService
{
    public function __construct(private readonly PaieLotInterface $lotRepository) {}

    public function exporter(int $lotId, string $format): Response
    {
        $lot = $this->lotRepository->findById($lotId);
        abort_unless($lot instanceof PaieLot, 404, 'Lot de paie introuvable.');
        abort_unless(
            $lot->statut->peutExporter(),
            422,
            'L\'export n\'est disponible qu\'après validation du lot.'
        );

        $lignes = $this->lotRepository->getLignes($lotId);

        return $format === 'pdf'
            ? $this->pdf($lot, $lignes)
            : $this->csv($lot, $lignes);
    }

    /**
     * @param  Collection<int, PaieLotLigne>  $lignes
     */
    private function csv(PaieLot $lot, Collection $lignes): StreamedResponse
    {
        $filename = 'export-paie-'.$lot->periodeCode().'.csv';

        return response()->streamDownload(function () use ($lot, $lignes) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, [
                'matricule',
                'nom',
                'prenom',
                'hors_grille',
                'montant_base',
                'total_gains',
                'total_retenues',
                'montant_net',
            ], ';');

            foreach ($lignes as $ligne) {
                $snapshot = $ligne->snapshot_agent ?? [];
                fputcsv($out, [
                    $snapshot['matricule'] ?? $ligne->agent?->matricule,
                    $snapshot['nom'] ?? $ligne->agent?->nom,
                    $snapshot['prenom'] ?? $ligne->agent?->prenom,
                    $ligne->hors_grille ? '1' : '0',
                    (int) round((float) $ligne->montant_base),
                    (int) round((float) $ligne->total_gains),
                    (int) round((float) $ligne->total_retenues),
                    (int) round((float) $ligne->montant_net),
                ], ';');
            }

            fputcsv($out, [
                '',
                '',
                'TOTAL',
                '',
                (int) round((float) $lignes->sum(fn (PaieLotLigne $ligne) => (float) $ligne->montant_base)),
                (int) round((float) $lot->total_gains),
                (int) round((float) $lot->total_retenues),
                (int) round((float) $lot->total_net),
            ], ';');

            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    /**
     * @param  Collection<int, PaieLotLigne>  $lignes
     */
    private function pdf(PaieLot $lot, Collection $lignes): Response
    {
        $pdf = Pdf::loadView('pdf.export-paie-lot', [
            'lot' => $lot,
            'lignes' => $lignes,
            'periode_label' => $lot->periodeLabel(),
        ])->setPaper('a4', 'landscape');

        return $pdf->stream('export-paie-'.$lot->periodeCode().'.pdf');
    }
}
