<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Note de service — Lot d'affectations</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'DejaVu Sans', Arial, sans-serif; font-size: 11px; color: #1a1a1a; line-height: 1.6; }
        .page { padding: 40px 50px; }
        .header { text-align: center; border-bottom: 3px double #003366; padding-bottom: 16px; margin-bottom: 24px; }
        .header .org { font-size: 12px; font-weight: bold; text-transform: uppercase; letter-spacing: 1px; color: #003366; }
        .header .titre { font-size: 18px; font-weight: bold; text-transform: uppercase; margin-top: 10px; color: #003366; }
        .header .reference { font-size: 10px; color: #666; margin-top: 6px; }
        .objet { margin-bottom: 18px; padding: 10px 14px; background: #eef4ff; border-left: 4px solid #003366; }
        .corps { text-align: justify; margin-bottom: 16px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th, td { border: 1px solid #ccc; padding: 6px 8px; text-align: left; font-size: 10px; }
        th { background: #eef4ff; color: #003366; text-transform: uppercase; }
        .footer { position: fixed; bottom: 20px; left: 50px; right: 50px; text-align: center; font-size: 9px; color: #aaa; border-top: 1px solid #ddd; padding-top: 6px; }
    </style>
</head>
<body>
<div class="page">
    <div class="header">
        <div class="org">Autorité de Régulation des Transports Ferroviaires (ARTF)</div>
        <div class="titre">Note de service — Affectations</div>
        <div class="reference">Réf. : {{ $reference }} · {{ now()->format('d/m/Y') }} · Lot n° {{ $lot->id }}</div>
    </div>

    <div class="objet">
        <strong>OBJET :</strong> Affectation collective — un seul acte, un seul circuit.
        Prise d'effet au <strong>{{ $lot->date_affectation?->format('d/m/Y') }}</strong>.
        @if($lot->motif)
            <br><strong>Motif :</strong> {{ $lot->motif }}
        @endif
    </div>

    <p class="corps">
        Le Directeur Général de l'ARTF décide d'affecter les agents ci-dessous
        aux structures indiquées.
    </p>

    <table>
        <thead>
            <tr>
                <th>N°</th>
                <th>Agent</th>
                <th>Matricule</th>
                <th>Structure</th>
                <th>Supérieur</th>
            </tr>
        </thead>
        <tbody>
        @foreach($lot->affectations as $i => $ligne)
            <tr>
                <td>{{ $i + 1 }}</td>
                <td>{{ strtoupper($ligne->agent?->nom ?? '—') }} {{ $ligne->agent?->prenom }}</td>
                <td>{{ $ligne->agent?->matricule ?? '—' }}</td>
                <td>{{ $ligne->structure?->nom ?? class_basename($ligne->structurable_type).' #'.$ligne->structurable_id }}</td>
                <td>
                    @if($ligne->superieurHierarchique)
                        {{ strtoupper($ligne->superieurHierarchique->nom) }} {{ $ligne->superieurHierarchique->prenom }}
                    @else
                        —
                    @endif
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>
<div class="footer">ARTF — {{ $reference }} — généré le {{ now()->format('d/m/Y à H:i') }}</div>
</body>
</html>
