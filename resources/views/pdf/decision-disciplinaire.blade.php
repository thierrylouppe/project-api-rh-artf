<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Décision disciplinaire</title>
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
        .footer { margin-top: 36px; font-size: 11px; }
        .sig { margin-top: 48px; text-align: right; }
    </style>
</head>
<body>
<div class="page">
    <div class="header">
        <div class="org">Agence de régulation des transferts de fonds (ARTF)</div>
        <div class="titre">Décision disciplinaire</div>
        <div class="ref">Prononcée par le directeur général — CCN art. 91</div>
    </div>
    <table class="info">
        <tr><td class="label">N° dossier</td><td>{{ $sanction->id }}</td></tr>
        <tr><td class="label">Agent</td><td>{{ $sanction->agent->prenom }} {{ $sanction->agent->nom }}{{ $sanction->agent->matricule ? ' — '.$sanction->agent->matricule : '' }}</td></tr>
        <tr><td class="label">Type de sanction</td><td>{{ $sanction->typeSanction->nom }}</td></tr>
        <tr><td class="label">Date des faits</td><td>{{ $sanction->date_faits?->format('d/m/Y') }}</td></tr>
        <tr><td class="label">Motif</td><td>{{ $sanction->motif }}</td></tr>
        <tr><td class="label">Décision</td><td>{{ $sanction->statut->label() }}</td></tr>
        <tr><td class="label">Date de décision</td><td>{{ $sanction->date_decision?->format('d/m/Y') ?: '—' }}</td></tr>
        <tr><td class="label">Dispositif</td><td>{{ $sanction->decision ?: '—' }}</td></tr>
        @if($sanction->nb_jours)
        <tr><td class="label">Durée</td><td>{{ $sanction->nb_jours }} jour(s){{ $sanction->date_debut_effet ? ', du '.$sanction->date_debut_effet->format('d/m/Y').' au '.$sanction->date_fin_effet?->format('d/m/Y') : '' }}</td></tr>
        @endif
        @if($sanction->typeSanction->code?->value === 'licenciement')
        <tr><td class="label">Indemnité</td><td>{{ $sanction->avec_indemnite ? 'Avec indemnité' : 'Sans indemnité' }}</td></tr>
        @endif
        <tr><td class="label">Commentaire</td><td>{{ $sanction->commentaire_validation ?: '—' }}</td></tr>
    </table>
    <div class="sig">
        <p>Le Directeur général</p>
        <p>{{ $sanction->validateur->name ?? '' }}</p>
    </div>
    <p class="footer">Cette décision est consignée au dossier individuel de l’agent (CCN art. 90).</p>
</div>
</body>
</html>
