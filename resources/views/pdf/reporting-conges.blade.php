<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>{{ $titre }} {{ $annee }}</title>
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
        <div class="titre">{{ $titre }} — {{ $annee }}</div>
        <div>
            {{ $stats['demandes']['total'] }} demande(s) —
            {{ $stats['demandes']['jours_accordes'] }} j. accordés —
            {{ $stats['demandes']['en_conge_aujourd_hui'] }} agent(s) en congé aujourd'hui
        </div>
    </div>
    <table>
        <thead>
            <tr>
                <th>Matricule</th>
                <th>Nom</th>
                <th>Type</th>
                <th>Début</th>
                <th>Fin</th>
                <th>Jours</th>
                <th>Statut</th>
            </tr>
        </thead>
        <tbody>
            @foreach($demandes as $demande)
                <tr>
                    <td>{{ $demande->agent?->matricule ?? '—' }}</td>
                    <td>{{ $demande->agent?->nom }} {{ $demande->agent?->prenom }}</td>
                    <td>{{ $demande->typeConge?->nom ?? '—' }}</td>
                    <td>{{ $demande->date_debut?->format('d/m/Y') }}</td>
                    <td>{{ $demande->date_fin?->format('d/m/Y') }}</td>
                    <td>{{ $demande->nb_jours }}</td>
                    <td>{{ $demande->statut?->label() ?? $demande->statut?->value }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
<div class="footer">ARTF — Reporting congés — {{ now()->format('d/m/Y H:i') }}</div>
</body>
</html>
