<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Décision d'arrêt santé</title>
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
        <div class="titre">Décision d’arrêt maladie / accident</div>
        <div class="ref">Directeur général — CCN art. {{ $arret->nature?->articleCcn() }}</div>
    </div>
    <table class="info">
        <tr><td class="label">N° dossier</td><td>{{ $arret->id }}</td></tr>
        <tr><td class="label">Agent</td><td>{{ $arret->agent->prenom }} {{ $arret->agent->nom }}{{ $arret->agent->matricule ? ' — '.$arret->agent->matricule : '' }}</td></tr>
        <tr><td class="label">Nature</td><td>{{ $arret->nature?->label() }}</td></tr>
        <tr><td class="label">Date du fait</td><td>{{ $arret->date_fait?->format('d/m/Y') }}</td></tr>
        <tr><td class="label">Période d’arrêt</td><td>{{ $arret->date_debut?->format('d/m/Y') }}{{ $arret->date_fin ? ' au '.$arret->date_fin->format('d/m/Y') : '' }}</td></tr>
        <tr><td class="label">Médecin / formation</td><td>{{ $arret->structure->nom ?? '—' }}</td></tr>
        <tr><td class="label">Décision</td><td>{{ $arret->statut?->label() }}</td></tr>
        <tr><td class="label">Date de décision</td><td>{{ $arret->date_decision?->format('d/m/Y') ?: '—' }}</td></tr>
        <tr><td class="label">Durée d’allocation</td><td>{{ $arret->nb_mois }} mois{{ $arret->nb_mois_majoration ? ' dont '.$arret->nb_mois_majoration.' de majoration (art. 133)' : '' }}</td></tr>
        <tr><td class="label">Montant mensuel</td><td>{{ $arret->montant_mensuel !== null ? number_format($arret->montant_mensuel, 0, ',', ' ').' F CFA' : '—' }}</td></tr>
        @if($arret->montant_mensuel_demi)
        <tr><td class="label">Montant mensuel (demi)</td><td>{{ number_format($arret->montant_mensuel_demi, 0, ',', ' ').' F CFA' }}</td></tr>
        @endif
        @if($arret->alerte_72h)
        <tr><td class="label">Alerte 72 h</td><td>Notification tardive (CCN art. 131) — n’empêche pas l’accord</td></tr>
        @endif
        <tr><td class="label">Commentaire</td><td>{{ $arret->commentaire_decision ?: '—' }}</td></tr>
    </table>
    <div class="sig">
        <p>Le Directeur général</p>
        <p>{{ $arret->decideur->name ?? '' }}</p>
    </div>
    <p class="footer">Cette décision est consignée au dossier social de l’agent (CCN art. {{ $arret->nature?->articleCcn() }}).</p>
</div>
</body>
</html>
