<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Bulletin de salaire</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'DejaVu Sans', Arial, sans-serif; font-size: 12px; color: #1a1a1a; line-height: 1.55; }
        .page { padding: 40px 50px; }
        .header { text-align: center; border-bottom: 3px double #003366; padding-bottom: 16px; margin-bottom: 28px; }
        .header .org { font-size: 13px; font-weight: bold; text-transform: uppercase; letter-spacing: 1px; color: #003366; }
        .header .titre { font-size: 18px; font-weight: bold; text-transform: uppercase; letter-spacing: 2px; margin-top: 10px; color: #003366; }
        .header .reference { font-size: 10px; color: #666; margin-top: 6px; }
        .bloc { border: 1px solid #ccc; border-radius: 4px; padding: 14px 18px; margin-bottom: 18px; background: #fafafa; }
        .bloc-titre { font-size: 11px; font-weight: bold; text-transform: uppercase; color: #003366; letter-spacing: 0.5px; margin-bottom: 10px; border-bottom: 1px solid #ddd; padding-bottom: 6px; }
        table.info { width: 100%; border-collapse: collapse; }
        table.info td { padding: 5px 0; vertical-align: top; }
        table.info td.label { width: 42%; font-weight: bold; color: #444; }
        table.montant { width: 100%; border-collapse: collapse; margin-top: 6px; }
        table.montant th, table.montant td { border: 1px solid #ccc; padding: 8px 10px; text-align: left; }
        table.montant th { background: #003366; color: #fff; font-size: 11px; text-transform: uppercase; }
        table.montant td.right { text-align: right; font-weight: bold; }
        .note { font-size: 10px; color: #666; margin-top: 16px; }
        .footer { position: fixed; bottom: 20px; left: 50px; right: 50px; text-align: center; font-size: 9px; color: #999; border-top: 1px solid #ddd; padding-top: 6px; }
    </style>
</head>
<body>
<div class="page">
    <div class="header">
        <div class="org">Autorité de Régulation des Transports Ferroviaires (ARTF)</div>
        <div class="titre">Bulletin de salaire</div>
        <div class="reference">
            Agent #{{ $agent->id }}
            @if($agent->matricule) — Matricule {{ $agent->matricule }}@endif
            &nbsp;|&nbsp; Établi le {{ now()->format('d/m/Y') }}
        </div>
    </div>

    <div class="bloc">
        <div class="bloc-titre">Identification de l'agent</div>
        <table class="info">
            <tr>
                <td class="label">Nom complet</td>
                <td>{{ $agent->prenom }} {{ $agent->nom }}</td>
            </tr>
            <tr>
                <td class="label">Catégorie / Grade</td>
                <td>
                    {{ $agent->categorie?->nom ?? $salaireAgent->classe?->categorie?->nom ?? '—' }}
                    /
                    {{ $agent->grade?->nom ?? $salaireAgent->classe?->grade?->nom ?? '—' }}
                </td>
            </tr>
            <tr>
                <td class="label">Fonction</td>
                <td>{{ $agent->fonction?->nom ?? '—' }}</td>
            </tr>
            <tr>
                <td class="label">Échelon</td>
                <td>{{ $salaireAgent->echelon }}</td>
            </tr>
        </table>
    </div>

    <div class="bloc">
        <div class="bloc-titre">Période salariale</div>
        <table class="info">
            <tr>
                <td class="label">Date de début</td>
                <td>{{ $salaireAgent->date_debut?->format('d/m/Y') ?? '—' }}</td>
            </tr>
            <tr>
                <td class="label">Date de fin</td>
                <td>{{ $salaireAgent->date_fin?->format('d/m/Y') ?? 'En cours' }}</td>
            </tr>
            <tr>
                <td class="label">Statut</td>
                <td>{{ $salaireAgent->statut?->label() ?? $salaireAgent->statut }}</td>
            </tr>
            <tr>
                <td class="label">Type de changement</td>
                <td>{{ $salaireAgent->type_changement?->label() ?? '—' }}</td>
            </tr>
            @if($salaireAgent->motif)
            <tr>
                <td class="label">Motif</td>
                <td>{{ $salaireAgent->motif }}</td>
            </tr>
            @endif
        </table>
    </div>

    <div class="bloc">
        <div class="bloc-titre">Rémunération</div>
        <table class="montant">
            <thead>
                <tr>
                    <th>Élément</th>
                    <th>Montant (FCFA)</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Salaire de base (grille)</td>
                    <td class="right">{{ number_format((float) $salaireAgent->montant_base, 0, ',', ' ') }}</td>
                </tr>
                <tr>
                    <td>Montant net</td>
                    <td class="right">{{ number_format((float) ($salaireAgent->montant_net ?? $salaireAgent->montant_base), 0, ',', ' ') }}</td>
                </tr>
                @if($salaireAgent->salaire)
                <tr>
                    <td>Indice (réf. grille)</td>
                    <td class="right">{{ $salaireAgent->salaire->indice }}</td>
                </tr>
                @endif
            </tbody>
        </table>
        <p class="note">
            Document simplifié à usage RH. Coefficient classe :
            {{ $salaireAgent->classe?->coefficient ?? '—' }}.
        </p>
    </div>

    <div class="footer">
        ARTF — Bulletin généré automatiquement — {{ now()->format('d/m/Y H:i') }}
    </div>
</div>
</body>
</html>
