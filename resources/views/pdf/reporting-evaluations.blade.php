<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>{{ $titre }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'DejaVu Sans', Arial, sans-serif; font-size: 10px; color: #1a1a1a; }
        .page { padding: 24px 28px; }
        .header { text-align: center; border-bottom: 3px double #003366; padding-bottom: 10px; margin-bottom: 14px; }
        .header .org { font-size: 11px; font-weight: bold; text-transform: uppercase; color: #003366; }
        .header .titre { font-size: 15px; font-weight: bold; text-transform: uppercase; margin-top: 6px; color: #003366; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #ccc; padding: 4px 6px; text-align: left; }
        th { background: #003366; color: #fff; font-size: 9px; text-transform: uppercase; }
        .footer { position: fixed; bottom: 14px; left: 28px; right: 28px; text-align: center; font-size: 8px; color: #999; border-top: 1px solid #ddd; padding-top: 4px; }
    </style>
</head>
<body>
<div class="page">
    <div class="header">
        <div class="org">Autorité de Régulation des Transports Ferroviaires (ARTF)</div>
        <div class="titre">{{ $titre }}</div>
        <div>
            @if($portee === 'session' && $session)
                Session #{{ $session->id }}
                ({{ $session->debut_session?->format('d/m/Y') }} – {{ $session->fin_session?->format('d/m/Y') }})
            @else
                Année {{ $annee }}
            @endif
            — {{ $fiches->count() }} fiche(s)
        </div>
    </div>
    <table>
        <thead>
            <tr>
                <th>Matricule</th>
                <th>Nom</th>
                <th>Statut</th>
                <th>Note /20</th>
                <th>Mention</th>
            </tr>
        </thead>
        <tbody>
            @foreach($fiches as $fiche)
                <tr>
                    <td>{{ $fiche->agent?->matricule ?? '—' }}</td>
                    <td>{{ $fiche->agent?->nom }} {{ $fiche->agent?->prenom }}</td>
                    <td>{{ $fiche->statut?->label() ?? $fiche->statut?->value }}</td>
                    <td>{{ $fiche->note_globale !== null ? number_format((float) $fiche->note_globale, 2, ',', ' ') : '—' }}</td>
                    <td>{{ $fiche->mention ?? '—' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
<div class="footer">ARTF — Reporting évaluations — {{ now()->format('d/m/Y H:i') }}</div>
</body>
</html>
