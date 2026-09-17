<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Décision de prise en charge</title>
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
        <div class="titre">Décision de prise en charge sanitaire</div>
        <div class="ref">Directeur général — CCN art. {{ $dossier->type?->articleCcn() }}</div>
    </div>
    <table class="info">
        <tr><td class="label">N° dossier</td><td>{{ $dossier->id }}</td></tr>
        <tr><td class="label">Agent</td><td>{{ $dossier->agent->prenom }} {{ $dossier->agent->nom }}{{ $dossier->agent->matricule ? ' — '.$dossier->agent->matricule : '' }}</td></tr>
        <tr><td class="label">Type</td><td>{{ $dossier->type?->label() }}</td></tr>
        <tr><td class="label">Date des soins</td><td>{{ $dossier->date_soins?->format('d/m/Y') }}</td></tr>
        <tr><td class="label">Structure</td><td>{{ $dossier->structure->nom ?? '—' }}</td></tr>
        <tr><td class="label">Bénéficiaire</td><td>{{ $dossier->ayantDroit ? ($dossier->ayantDroit->prenom.' '.$dossier->ayantDroit->nom) : 'Salarié' }}</td></tr>
        @if($dossier->type?->estEvacuation())
        <tr><td class="label">Évacuation</td><td>{{ $dossier->lieu ?: '—' }} du {{ $dossier->date_debut?->format('d/m/Y') }} au {{ $dossier->date_fin?->format('d/m/Y') }}{{ $dossier->at_mp ? ' (AT/MP)' : '' }}</td></tr>
        @endif
        <tr><td class="label">Décision</td><td>{{ $dossier->statut?->label() }}</td></tr>
        <tr><td class="label">Date de décision</td><td>{{ $dossier->date_decision?->format('d/m/Y') ?: '—' }}</td></tr>
        <tr><td class="label">Montant facture</td><td>{{ $dossier->montant_facture !== null ? number_format($dossier->montant_facture, 0, ',', ' ').' F CFA' : '—' }}</td></tr>
        <tr><td class="label">Montant accordé</td><td>{{ $dossier->montant_accorde !== null ? number_format($dossier->montant_accorde, 0, ',', ' ').' F CFA' : '—' }}</td></tr>
        @if($dossier->paie_mois)
        <tr><td class="label">Pose paie</td><td>{{ str_pad((string) $dossier->paie_mois, 2, '0', STR_PAD_LEFT) }}/{{ $dossier->paie_annee }}</td></tr>
        @endif
        <tr><td class="label">Commentaire</td><td>{{ $dossier->commentaire_decision ?: '—' }}</td></tr>
    </table>
    <div class="sig">
        <p>Le Directeur général</p>
        <p>{{ $dossier->decideur->name ?? '' }}</p>
    </div>
    <p class="footer">Cette décision est consignée au dossier social de l’agent (CCN art. {{ $dossier->type?->articleCcn() }}).</p>
</div>
</body>
</html>
