<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Attestation de congé</title>
    <style>
        body { font-family: 'DejaVu Sans', Arial, sans-serif; font-size: 12px; color: #1a1a1a; }
        .page { padding: 40px 50px; }
        .header { text-align: center; border-bottom: 3px double #003366; padding-bottom: 16px; margin-bottom: 24px; }
        .org { font-size: 13px; font-weight: bold; text-transform: uppercase; color: #003366; }
        .titre { font-size: 18px; font-weight: bold; text-transform: uppercase; margin-top: 10px; color: #003366; }
        p { line-height: 1.6; margin-bottom: 14px; }
    </style>
</head>
<body>
@include('pdf.partials.charte', ['variante' => 'officiel'])
<div class="page">
    <div class="header">
        @include('pdf.partials.entete', ['variante' => 'officiel'])
        <div class="titre">Attestation de congé</div>
    </div>
    <p class="corps">
        Le <span class="signataire">Directeur Général de l'{{ config('artf.nom') }}</span>
        atteste que l'agent désigné ci-dessous a obtenu un {{ mb_strtolower($demande->typeConge->nom) }} dans les conditions suivantes :
    </p>
    <table class="info">
        <tr><td class="label">Agent</td><td><strong>{{ mb_strtoupper($demande->agent->nom) }}</strong> {{ $demande->agent->prenom }}</td></tr>
        <tr><td class="label">Matricule</td><td>{{ $demande->agent->matricule ?? '—' }}</td></tr>
        <tr><td class="label">Nature du congé</td><td>{{ $demande->typeConge->nom }}</td></tr>
        <tr><td class="label">Période</td><td>du <strong>{{ $demande->date_debut->format('d/m/Y') }}</strong> au <strong>{{ $demande->date_fin->format('d/m/Y') }}</strong></td></tr>
        <tr><td class="label">Durée</td><td>{{ $demande->nb_jours }} jour(s) ouvrable(s)</td></tr>
        <tr><td class="label">Décision RH</td><td>{{ $demande->date_validation_rh?->format('d/m/Y') ?? now()->format('d/m/Y') }}</td></tr>
    </table>
    <p class="corps">En foi de quoi, la présente attestation lui est délivrée pour servir et valoir ce que de droit.</p>
    <div class="sig">
        <p>Le Directeur Général</p>
        <p>Signature &amp; cachet</p>
    </div>
</div>
</body>
</html>
