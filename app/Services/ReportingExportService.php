<?php

namespace App\Services;

use App\Enums\TypeExportReporting;
use App\Models\DemandeConge;
use App\Models\Evaluation;
use App\Models\SessionEvaluation;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportingExportService
{
    public function __construct(private readonly ReportingService $reporting) {}

    public function exporter(string $type, array $filters): Response
    {
        $enum = TypeExportReporting::from($type);
        $format = $filters['format'];

        return match ($enum) {
            TypeExportReporting::EFFECTIFS => $this->effectifs($format, $filters),
            TypeExportReporting::CONGES => $this->conges($format, $filters),
            TypeExportReporting::EVALUATIONS => $this->evaluations($format, $filters),
        };
    }

    private function effectifs(string $format, array $filters): Response
    {
        $agents = $this->reporting->effectifsPourExport($filters)->map(fn ($agent) => $this->reporting->ligneEffectif($agent));
        $annee = $this->reporting->annee($filters);

        if ($format === 'csv') {
            return $this->csv('reporting-effectifs-'.$annee.'.csv', [
                'matricule', 'nom', 'prenom', 'statut', 'genre', 'age',
                'grade', 'fonction', 'type_integration', 'direction', 'service', 'bureau',
            ], $agents->map(fn (array $ligne) => [
                $ligne['matricule'], $ligne['nom'], $ligne['prenom'], $ligne['statut'],
                $ligne['genre'], $ligne['age'], $ligne['grade'], $ligne['fonction'],
                $ligne['type_integration'], $ligne['direction'], $ligne['service'], $ligne['bureau'],
            ]));
        }

        return $this->pdf('pdf.reporting-effectifs', 'reporting-effectifs-'.$annee.'.pdf', [
            'titre' => 'Effectifs',
            'annee' => $annee,
            'lignes' => $agents,
        ]);
    }

    private function conges(string $format, array $filters): Response
    {
        $annee = $this->reporting->annee($filters);
        $stats = $this->reporting->statsConges($filters);
        $demandes = $this->reporting->demandesCongePourExport($annee);

        if ($format === 'csv') {
            return $this->csv('reporting-conges-'.$annee.'.csv', [
                'id', 'matricule', 'nom', 'prenom', 'type', 'debut', 'fin', 'nb_jours', 'statut',
            ], $demandes->map(function (DemandeConge $demande) {
                return [
                    $demande->id,
                    $demande->agent?->matricule,
                    $demande->agent?->nom,
                    $demande->agent?->prenom,
                    $demande->typeConge?->nom,
                    $demande->date_debut?->format('Y-m-d'),
                    $demande->date_fin?->format('Y-m-d'),
                    $demande->nb_jours,
                    $demande->statut?->value,
                ];
            }));
        }

        return $this->pdf('pdf.reporting-conges', 'reporting-conges-'.$annee.'.pdf', [
            'titre' => 'Congés et absences',
            'annee' => $annee,
            'stats' => $stats,
            'demandes' => $demandes,
        ]);
    }

    private function evaluations(string $format, array $filters): Response
    {
        $payload = $this->reporting->evaluationsPourExport($filters);
        $fiches = $payload['fiches'];
        /** @var SessionEvaluation|null $session */
        $session = $payload['session'];
        $suffixe = $payload['portee'] === 'session'
            ? 'session-'.$session->id
            : (string) $payload['annee'];

        if ($format === 'csv') {
            return $this->csv('reporting-evaluations-'.$suffixe.'.csv', [
                'id', 'matricule', 'nom', 'prenom', 'statut', 'note_globale', 'mention',
            ], $fiches->map(function (Evaluation $fiche) {
                return [
                    $fiche->id,
                    $fiche->agent?->matricule,
                    $fiche->agent?->nom,
                    $fiche->agent?->prenom,
                    $fiche->statut?->value,
                    $fiche->note_globale,
                    $fiche->mention,
                ];
            }));
        }

        return $this->pdf('pdf.reporting-evaluations', 'reporting-evaluations-'.$suffixe.'.pdf', [
            'titre' => 'Évaluations',
            'portee' => $payload['portee'],
            'annee' => $payload['annee'] ?? null,
            'session' => $session,
            'fiches' => $fiches,
        ]);
    }

    private function csv(string $filename, array $headers, Collection $rows): StreamedResponse
    {
        return response()->streamDownload(function () use ($headers, $rows) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, $headers, ';');
            foreach ($rows as $row) {
                fputcsv($out, $row, ';');
            }
            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    private function pdf(string $view, string $filename, array $data): Response
    {
        return Pdf::loadView($view, $data)
            ->setPaper('a4', 'landscape')
            ->stream($filename);
    }
}
