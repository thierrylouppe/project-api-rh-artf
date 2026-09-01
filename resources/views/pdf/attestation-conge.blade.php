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
<div class="page">
    <div class="header">
        <div class="org">Autorité de Régulation des Transports Ferroviaires (ARTF)</div>
        <div class="titre">Attestation de congé</div>
    </div>
    <p>
        Il est attesté que {{ $demande->agent->prenom }} {{ $demande->agent->nom }}
        a obtenu un {{ strtolower($demande->typeConge->nom) }}
        du {{ $demande->date_debut->format('d/m/Y') }} au {{ $demande->date_fin->format('d/m/Y') }}
        ({{ $demande->nb_jours }} jour(s) ouvrable(s)).
    </p>
    <p>Décision RH du {{ $demande->date_validation_rh?->format('d/m/Y') ?? now()->format('d/m/Y') }}.</p>
</div>
</body>
</html>
