<?php

namespace App\Services;

use App\Enums\SensPaieElement;
use App\Interfaces\PaieLotInterface;
use App\Models\PaieLot;
use App\Models\PaieLotLigneDetail;
use Barryvdh\DomPDF\Facade\Pdf;
use Symfony\Component\HttpFoundation\Response;

class PaieBulletinService
{
    public function __construct(private readonly PaieLotInterface $lotRepository) {}

    public function genererPdf(int $lotId, int $ligneId): Response
    {
        $this->lotRepository->findById($lotId);
        $ligne = $this->lotRepository->getLigne($lotId, $ligneId);
        $ligne->loadMissing(['details', 'lot']);

        $gains = $ligne->details
            ->filter(fn (PaieLotLigneDetail $detail) => $detail->sens === SensPaieElement::GAIN)
            ->sortBy(fn (PaieLotLigneDetail $detail) => $this->rangGain($detail))
            ->values();

        $retenues = $ligne->details
            ->filter(fn (PaieLotLigneDetail $detail) => $detail->sens === SensPaieElement::RETENUE)
            ->values();

        $lot = $ligne->lot;
        abort_unless($lot instanceof PaieLot, 404, 'Lot de paie introuvable.');

        $snapshot = $ligne->snapshot_agent ?? [];
        $periode = sprintf('%04d-%02d', $lot->annee, $lot->mois);
        $matricule = $snapshot['matricule'] ?? $ligne->agent_id;

        $pdf = Pdf::loadView('pdf.bulletin-paie', [
            'ligne' => $ligne,
            'lot' => $lot,
            'snapshot' => $snapshot,
            'gains' => $gains,
            'retenues' => $retenues,
            'periode_label' => $this->libellePeriode($lot),
        ]);

        return $pdf->stream("bulletin-paie-{$matricule}-{$periode}.pdf");
    }

    private function rangGain(PaieLotLigneDetail $detail): int
    {
        return match ($detail->code) {
            'salaire_base' => 0,
            'salaire_fonctionnel' => 1,
            default => match ($detail->nature?->value) {
                'prime' => 2,
                'indemnite' => 3,
                'allocation' => 4,
                default => 5,
            },
        };
    }

    private function libellePeriode(PaieLot $lot): string
    {
        $mois = [
            1 => 'janvier',
            2 => 'février',
            3 => 'mars',
            4 => 'avril',
            5 => 'mai',
            6 => 'juin',
            7 => 'juillet',
            8 => 'août',
            9 => 'septembre',
            10 => 'octobre',
            11 => 'novembre',
            12 => 'décembre',
        ];

        return ($mois[(int) $lot->mois] ?? (string) $lot->mois).' '.$lot->annee;
    }
}
