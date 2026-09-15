<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Rapport disciplinaire</title>
    <style>
        body { font-family: 'DejaVu Sans', Arial, sans-serif; font-size: 12px; color: #1a1a1a; }
        .page { padding: 40px 50px; }
        .header { text-align: center; border-bottom: 3px double #003366; padding-bottom: 16px; margin-bottom: 24px; }
        .org { font-size: 13px; font-weight: bold; text-transform: uppercase; color: #003366; }
        .titre { font-size: 18px; font-weight: bold; text-transform: uppercase; margin-top: 10px; color: #003366; }
        .ref { font-size: 11px; color: #555; margin-top: 6px; }
        table.info { width: 100%; border-collapse: collapse; }
        table.info td { padding: 6px 0; vertical-align: top; }
        table.info td.label { width: 38%; font-weight: bold; color: #444; }
        .footer { margin-top: 28px; font-size: 10px; color: #666; }
    </style>
</head>
<body>
<div class="page">
    <div class="header">
        <div class="org">Agence de régulation des transferts de fonds (ARTF)</div>
        <div class="titre">Rapport disciplinaire</div>
        <div class="ref">Convention collective — articles 90 et 91</div>
    </div>
    <table class="info">
        <tr><td class="label">N° dossier</td><td>{{ $sanction->id }}</td></tr>
        <tr><td class="label">Agent</td><td>{{ $sanction->agent->prenom }} {{ $sanction->agent->nom }}{{ $sanction->agent->matricule ? ' — '.$sanction->agent->matricule : '' }}</td></tr>
        <tr><td class="label">Sanction proposée</td><td>{{ $sanction->typeSanction->nom }}</td></tr>
        <tr><td class="label">Date des faits</td><td>{{ $sanction->date_faits?->format('d/m/Y') }}</td></tr>
        <tr><td class="label">Motif</td><td>{{ $sanction->motif }}</td></tr>
        @if($sanction->nb_jours)
        <tr><td class="label">Durée proposée</td><td>{{ $sanction->nb_jours }} jour(s)</td></tr>
        @endif
        @if($sanction->typeSanction->code?->value === 'licenciement')
        <tr><td class="label">Indemnité</td><td>{{ $sanction->avec_indemnite ? 'Avec indemnité' : 'Sans indemnité' }}</td></tr>
        @endif
        <tr><td class="label">Statut</td><td>{{ $sanction->statut->label() }}</td></tr>
        <tr><td class="label">Notes d'instruction</td><td>{{ $sanction->notes_instruction ?: '—' }}</td></tr>
        <tr><td class="label">Pièces jointes</td><td>{{ $sanction->pieces->pluck('nom_original')->filter()->implode(', ') ?: '—' }}</td></tr>
        <tr><td class="label">Auteur du rapport</td><td>{{ $sanction->createur->name ?? '—' }}</td></tr>
    </table>
    <p class="footer">Toute proposition de sanction comprend un rapport détaillé sur les faits ainsi que les pièces s’y rapportant. Le directeur général est la seule autorité compétente à prononcer une sanction.</p>
</div>
</body>
</html>
