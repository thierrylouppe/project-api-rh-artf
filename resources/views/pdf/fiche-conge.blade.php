<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Fiche de congé</title>
    <style>
        body { font-family: 'DejaVu Sans', Arial, sans-serif; font-size: 12px; color: #1a1a1a; }
        .page { padding: 40px 50px; }
        .header { text-align: center; border-bottom: 3px double #003366; padding-bottom: 16px; margin-bottom: 24px; }
        .org { font-size: 13px; font-weight: bold; text-transform: uppercase; color: #003366; }
        .titre { font-size: 18px; font-weight: bold; text-transform: uppercase; margin-top: 10px; color: #003366; }
        table.info { width: 100%; border-collapse: collapse; }
        table.info td { padding: 6px 0; }
        table.info td.label { width: 40%; font-weight: bold; color: #444; }
    </style>
</head>
<body>
<div class="page">
    <div class="header">
        <div class="org">Autorité de Régulation des Transports Ferroviaires (ARTF)</div>
        <div class="titre">Fiche de demande de congé</div>
    </div>
    <table class="info">
        <tr><td class="label">Agent</td><td>{{ $demande->agent->prenom }} {{ $demande->agent->nom }}</td></tr>
        <tr><td class="label">Type</td><td>{{ $demande->typeConge->nom }}</td></tr>
        <tr><td class="label">Période</td><td>{{ $demande->date_debut->format('d/m/Y') }} — {{ $demande->date_fin->format('d/m/Y') }}</td></tr>
        <tr><td class="label">Jours ouvrables</td><td>{{ $demande->nb_jours }}</td></tr>
        <tr><td class="label">Statut</td><td>{{ $demande->statut->label() }}</td></tr>
        <tr><td class="label">Motif</td><td>{{ $demande->motif ?? '—' }}</td></tr>
    </table>
</div>
</body>
</html>
