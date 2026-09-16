<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Bulletin de paie</title>
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
        table.montant tr.total td { background: #eef3f8; font-weight: bold; }
        table.montant tr.net td { background: #003366; color: #fff; font-weight: bold; }
        .note { font-size: 10px; color: #666; margin-top: 16px; }
        .footer { position: fixed; bottom: 20px; left: 50px; right: 50px; text-align: center; font-size: 9px; color: #999; border-top: 1px solid #ddd; padding-top: 6px; }
    </style>
</head>
<body>
@php
    $fmt = fn ($montant) => number_format((float) $montant, 0, ',', ' ');
    $nom = trim(($snapshot['prenom'] ?? '').' '.($snapshot['nom'] ?? ''));
@endphp
<div class="page">
    <div class="header">
        <div class="org">Autorité de Régulation des Transports Ferroviaires (ARTF)</div>
        <div class="titre">Bulletin de paie</div>
        <div class="reference">
            Période {{ $periode_label }}
            @if(!empty($snapshot['matricule'])) — Matricule {{ $snapshot['matricule'] }}@endif
            &nbsp;|&nbsp; Établi le {{ now()->format('d/m/Y') }}
        </div>
    </div>

    <div class="bloc">
        <div class="bloc-titre">Identification de l'agent</div>
        <table class="info">
            <tr>
                <td class="label">Nom complet</td>
                <td>{{ $nom !== '' ? $nom : '—' }}</td>
            </tr>
            <tr>
                <td class="label">Matricule</td>
                <td>{{ $snapshot['matricule'] ?? '—' }}</td>
            </tr>
            <tr>
                <td class="label">Fonction</td>
                <td>{{ $snapshot['fonction'] ?? '—' }}@if(!empty($snapshot['fonction_sigle'])) ({{ $snapshot['fonction_sigle'] }})@endif</td>
            </tr>
            <tr>
                <td class="label">Grade</td>
                <td>{{ $snapshot['grade'] ?? '—' }}</td>
            </tr>
            <tr>
                <td class="label">Date de prise de service</td>
                <td>{{ !empty($snapshot['date_prise_service']) ? \Carbon\Carbon::parse($snapshot['date_prise_service'])->format('d/m/Y') : '—' }}</td>
            </tr>
            @if($ligne->hors_grille)
            <tr>
                <td class="label">Régime</td>
                <td>Salaire fonctionnel (art. 55) — hors grille indiciaire</td>
            </tr>
            @endif
        </table>
    </div>

    <div class="bloc">
        <div class="bloc-titre">Période</div>
        <table class="info">
            <tr>
                <td class="label">Mois de paie</td>
                <td>{{ $periode_label }}</td>
            </tr>
            <tr>
                <td class="label">Statut du lot</td>
                <td>{{ $lot->statut?->label() ?? $lot->statut }}</td>
            </tr>
        </table>
    </div>

    <div class="bloc">
        <div class="bloc-titre">Gains</div>
        <table class="montant">
            <thead>
                <tr>
                    <th>Élément</th>
                    <th>Nature</th>
                    <th>Montant (FCFA)</th>
                </tr>
            </thead>
            <tbody>
                @forelse($gains as $detail)
                <tr>
                    <td>{{ $detail->libelle }}</td>
                    <td>{{ $detail->nature?->label() ?? 'Salaire de base' }}</td>
                    <td class="right">{{ $fmt($detail->montant) }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="3">Aucun gain</td>
                </tr>
                @endforelse
                <tr class="total">
                    <td colspan="2">Total des gains</td>
                    <td class="right">{{ $fmt($ligne->total_gains) }}</td>
                </tr>
            </tbody>
        </table>
    </div>

    <div class="bloc">
        <div class="bloc-titre">Retenues</div>
        <table class="montant">
            <thead>
                <tr>
                    <th>Élément</th>
                    <th>Nature</th>
                    <th>Montant (FCFA)</th>
                </tr>
            </thead>
            <tbody>
                @forelse($retenues as $detail)
                <tr>
                    <td>{{ $detail->libelle }}</td>
                    <td>{{ $detail->nature?->label() ?? 'Retenue' }}</td>
                    <td class="right">{{ $fmt($detail->montant) }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="3">Aucune retenue</td>
                </tr>
                @endforelse
                <tr class="total">
                    <td colspan="2">Total des retenues</td>
                    <td class="right">{{ $fmt($ligne->total_retenues) }}</td>
                </tr>
            </tbody>
        </table>
    </div>

    <div class="bloc">
        <div class="bloc-titre">Net à payer</div>
        <table class="montant">
            <tbody>
                <tr class="net">
                    <td>Net à payer</td>
                    <td class="right">{{ $fmt($ligne->montant_net) }} FCFA</td>
                </tr>
            </tbody>
        </table>
        <p class="note">
            Document établi à partir du lot de paie {{ $lot->mois }}/{{ $lot->annee }}.
            Le bulletin indiciaire de carrière n'est pas modifié.
        </p>
    </div>

    <div class="footer">
        ARTF — Bulletin de paie généré automatiquement — {{ now()->format('d/m/Y H:i') }}
    </div>
</div>
</body>
</html>
