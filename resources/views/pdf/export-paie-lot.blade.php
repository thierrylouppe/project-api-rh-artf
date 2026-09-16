<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Export paie {{ $periode_label }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'DejaVu Sans', Arial, sans-serif; font-size: 11px; color: #1a1a1a; }
        .page { padding: 28px 32px; }
        .header { text-align: center; border-bottom: 3px double #003366; padding-bottom: 12px; margin-bottom: 18px; }
        .header .org { font-size: 12px; font-weight: bold; text-transform: uppercase; letter-spacing: 1px; color: #003366; }
        .header .titre { font-size: 16px; font-weight: bold; text-transform: uppercase; letter-spacing: 1px; margin-top: 8px; color: #003366; }
        .header .reference { font-size: 10px; color: #666; margin-top: 6px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #ccc; padding: 6px 8px; text-align: left; }
        th { background: #003366; color: #fff; font-size: 10px; text-transform: uppercase; }
        td.right { text-align: right; }
        tr.total td { background: #eef3f8; font-weight: bold; }
        .footer { position: fixed; bottom: 16px; left: 32px; right: 32px; text-align: center; font-size: 9px; color: #999; border-top: 1px solid #ddd; padding-top: 6px; }
    </style>
</head>
<body>
@php
    $fmt = fn ($montant) => number_format((float) $montant, 0, ',', ' ');
@endphp
<div class="page">
    <div class="header">
        <div class="org">Autorité de Régulation des Transports Ferroviaires (ARTF)</div>
        <div class="titre">Masse salariale</div>
        <div class="reference">
            {{ $periode_label }} — {{ $lot->nb_lignes }} bulletin(s) — Statut {{ $lot->statut?->label() }}
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th>Matricule</th>
                <th>Nom</th>
                <th>Prénom</th>
                <th>Hors grille</th>
                <th>Base</th>
                <th>Gains</th>
                <th>Retenues</th>
                <th>Net</th>
            </tr>
        </thead>
        <tbody>
            @foreach($lignes as $ligne)
                @php $snapshot = $ligne->snapshot_agent ?? []; @endphp
                <tr>
                    <td>{{ $snapshot['matricule'] ?? $ligne->agent?->matricule ?? '—' }}</td>
                    <td>{{ $snapshot['nom'] ?? $ligne->agent?->nom ?? '—' }}</td>
                    <td>{{ $snapshot['prenom'] ?? $ligne->agent?->prenom ?? '—' }}</td>
                    <td>{{ $ligne->hors_grille ? 'Oui' : 'Non' }}</td>
                    <td class="right">{{ $fmt($ligne->montant_base) }}</td>
                    <td class="right">{{ $fmt($ligne->total_gains) }}</td>
                    <td class="right">{{ $fmt($ligne->total_retenues) }}</td>
                    <td class="right">{{ $fmt($ligne->montant_net) }}</td>
                </tr>
            @endforeach
            <tr class="total">
                <td colspan="4">Total</td>
                <td class="right">{{ $fmt($lignes->sum('montant_base')) }}</td>
                <td class="right">{{ $fmt($lot->total_gains) }}</td>
                <td class="right">{{ $fmt($lot->total_retenues) }}</td>
                <td class="right">{{ $fmt($lot->total_net) }}</td>
            </tr>
        </tbody>
    </table>
</div>
<div class="footer">
    ARTF — Export de paie généré automatiquement — {{ now()->format('d/m/Y H:i') }}
</div>
</body>
</html>
