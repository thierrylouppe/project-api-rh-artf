<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Décision de prestation</title>
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
        <div class="titre">Décision de prestation sociale</div>
        <div class="ref">Directeur général — CCN art. {{ $prestation->type?->articleCcn() }}</div>
    </div>
    <table class="info">
        <tr><td class="label">N° dossier</td><td>{{ $prestation->id }}</td></tr>
        <tr><td class="label">Agent</td><td>{{ $prestation->agent->prenom }} {{ $prestation->agent->nom }}{{ $prestation->agent->matricule ? ' — '.$prestation->agent->matricule : '' }}</td></tr>
        <tr><td class="label">Type</td><td>{{ $prestation->type?->label() }}</td></tr>
        <tr><td class="label">Date des faits</td><td>{{ $prestation->date_fait?->format('d/m/Y') }}</td></tr>
        <tr><td class="label">Bénéficiaire</td><td>{{ $prestation->ayantDroit?->prenom }} {{ $prestation->ayantDroit?->nom }}{{ $prestation->beneficiaire_libelle ? ($prestation->ayantDroit ? ' — ' : '').$prestation->beneficiaire_libelle : '' }}</td></tr>
        <tr><td class="label">Décision</td><td>{{ $prestation->statut?->label() }}</td></tr>
        <tr><td class="label">Date de décision</td><td>{{ $prestation->date_decision?->format('d/m/Y') ?: '—' }}</td></tr>
        <tr><td class="label">Montant accordé</td><td>{{ $prestation->montant_accorde !== null ? number_format($prestation->montant_accorde, 0, ',', ' ').' F CFA' : '—' }}</td></tr>
        @if($prestation->paie_mois)
        <tr><td class="label">Pose paie</td><td>{{ str_pad((string) $prestation->paie_mois, 2, '0', STR_PAD_LEFT) }}/{{ $prestation->paie_annee }}</td></tr>
        @endif
        <tr><td class="label">Commentaire</td><td>{{ $prestation->commentaire_decision ?: '—' }}</td></tr>
    </table>
    <div class="sig">
        <p>Le Directeur général</p>
        <p>{{ $prestation->decideur->name ?? '' }}</p>
    </div>
    <p class="footer">Cette décision est consignée au dossier social de l’agent (CCN art. {{ $prestation->type?->articleCcn() }}).</p>
</div>
</body>
</html>
