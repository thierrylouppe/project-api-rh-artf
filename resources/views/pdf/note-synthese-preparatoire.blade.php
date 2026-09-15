<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Note de synthèse — commission préparatoire</title>
    <style>
        body { font-family: 'DejaVu Sans', Arial, sans-serif; font-size: 11px; color: #1a1a1a; }
        .page { padding: 28px 36px; }
        .header { text-align: center; border-bottom: 3px double #003366; padding-bottom: 12px; margin-bottom: 18px; }
        .org { font-size: 12px; font-weight: bold; text-transform: uppercase; color: #003366; }
        .titre { font-size: 16px; font-weight: bold; text-transform: uppercase; margin-top: 8px; color: #003366; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 14px; }
        th, td { border: 1px solid #ccc; padding: 5px 7px; text-align: left; }
        th { background: #e8eef5; color: #003366; }
        .alerte { color: #a00; font-weight: bold; }
        h2 { font-size: 12px; color: #003366; margin: 16px 0 8px; text-transform: uppercase; }
    </style>
</head>
<body>
<div class="page">
    <div class="header">
        <div class="org">Autorité de Régulation des Transports Ferroviaires (ARTF)</div>
        <div class="titre">Note de synthèse — commission préparatoire</div>
        <div>CCN art. 67 — Session {{ $commission->session?->debut_session?->format('d/m/Y') }}</div>
    </div>

    <p>Présidée par le Directeur général. Ouverture : {{ $commission->date_ouverture?->format('d/m/Y') ?? '—' }}.
        Clôture : {{ $commission->date_cloture?->format('d/m/Y') ?? '—' }}.</p>

    <h2>Observations de la commission</h2>
    <p>{{ $commission->observations ?: '—' }}</p>

    <h2>Fiches finalisées</h2>
    <table>
        <thead>
        <tr>
            <th>Agent</th>
            <th>Note N+1</th>
            <th>Note préparatoire</th>
            <th>Écart</th>
            <th>Synthèse par fiche</th>
        </tr>
        </thead>
        <tbody>
        @forelse($fiches as $fiche)
            @php
                $ecart = ($fiche->commission_note !== null && $fiche->note_globale !== null)
                    ? abs($fiche->commission_note - $fiche->note_globale)
                    : null;
            @endphp
            <tr>
                <td>{{ $fiche->agent?->prenom }} {{ $fiche->agent?->nom }}</td>
                <td>{{ $fiche->note_globale !== null ? number_format($fiche->note_globale, 2, ',', ' ') : '—' }}</td>
                <td>{{ $fiche->commission_note !== null ? number_format($fiche->commission_note, 2, ',', ' ') : '—' }}</td>
                <td class="{{ ($ecart !== null && $ecart > 5) ? 'alerte' : '' }}">
                    {{ $ecart !== null ? number_format($ecart, 2, ',', ' ') : '—' }}
                    @if($ecart !== null && $ecart > 5) ⚠ @endif
                </td>
                <td>{{ $fiche->note_synthese ?: '—' }}</td>
            </tr>
        @empty
            <tr><td colspan="5">Aucune fiche finalisée.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
</body>
</html>
