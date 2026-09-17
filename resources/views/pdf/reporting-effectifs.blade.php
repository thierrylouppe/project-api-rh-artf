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
        table { width: 100%; border-collapse: collapse; }
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
        <div>{{ $lignes->count() }} agent(s)</div>
    </div>
    <table>
        <thead>
            <tr>
                <th>Matricule</th>
                <th>Nom</th>
                <th>Prénom</th>
                <th>Statut</th>
                <th>Genre</th>
                <th>Âge</th>
                <th>Grade</th>
                <th>Fonction</th>
                <th>Direction</th>
            </tr>
        </thead>
        <tbody>
            @foreach($lignes as $ligne)
                <tr>
                    <td>{{ $ligne['matricule'] ?? '—' }}</td>
                    <td>{{ $ligne['nom'] ?? '—' }}</td>
                    <td>{{ $ligne['prenom'] ?? '—' }}</td>
                    <td>{{ $ligne['statut'] ?? '—' }}</td>
                    <td>{{ $ligne['genre'] ?? '—' }}</td>
                    <td>{{ $ligne['age'] ?? '—' }}</td>
                    <td>{{ $ligne['grade'] ?? '—' }}</td>
                    <td>{{ $ligne['fonction'] ?? '—' }}</td>
                    <td>{{ $ligne['direction'] ?? '—' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
<div class="footer">ARTF — Reporting effectifs — {{ now()->format('d/m/Y H:i') }}</div>
</body>
</html>
