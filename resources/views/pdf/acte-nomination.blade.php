<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>{{ $typeActe->label() }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'DejaVu Sans', Arial, sans-serif; font-size: 11px; color: #1a1a1a; line-height: 1.6; }
        .page { padding: 40px 50px; }
        .header { text-align: center; border-bottom: 3px double #003366; padding-bottom: 16px; margin-bottom: 28px; }
        .header .org { font-size: 12px; font-weight: bold; text-transform: uppercase; letter-spacing: 1px; color: #003366; }
        .header .titre { font-size: 20px; font-weight: bold; text-transform: uppercase; letter-spacing: 2px; margin-top: 10px; color: #003366; }
        .header .sous-titre { font-size: 13px; font-weight: bold; color: #555; margin-top: 4px; letter-spacing: 1px; }
        .header .reference { font-size: 10px; color: #666; margin-top: 6px; }
        .objet { margin-bottom: 20px; padding: 10px 14px; background: #eef4ff; border-left: 4px solid #003366; font-size: 12px; }
        .objet strong { color: #003366; }
        .bloc { border: 1px solid #ccc; border-radius: 4px; padding: 14px 18px; margin-bottom: 16px; }
        .bloc-titre { font-size: 10px; font-weight: bold; text-transform: uppercase; color: #003366; letter-spacing: 0.5px; margin-bottom: 10px; border-bottom: 1px solid #ddd; padding-bottom: 6px; }
        .ligne { display: flex; margin-bottom: 5px; }
        .ligne .label { width: 200px; font-weight: bold; color: #444; flex-shrink: 0; }
        .ligne .valeur { flex: 1; }
        .corps { text-align: justify; margin-bottom: 20px; font-size: 11.5px; line-height: 1.8; }
        .signataire { font-weight: bold; text-decoration: underline; }
        .signatures { display: flex; justify-content: space-between; margin-top: 40px; }
        .sign-bloc { text-align: center; width: 45%; }
        .sign-bloc .sign-titre { font-weight: bold; font-size: 10px; text-transform: uppercase; color: #003366; margin-bottom: 8px; }
        .sign-bloc .sign-espace { height: 60px; border-bottom: 1px solid #999; margin-bottom: 8px; }
        .footer { position: fixed; bottom: 20px; left: 50px; right: 50px; text-align: center; font-size: 9px; color: #aaa; border-top: 1px solid #ddd; padding-top: 6px; }
        .statut-badge { display: inline-block; padding: 2px 8px; border-radius: 3px; font-size: 9px; font-weight: bold; background: #e6f0ff; color: #003366; border: 1px solid #b3ccff; }
    </style>
</head>
<body>
<div class="page">
    <div class="header">
        <div class="org">Autorité de Régulation des Transports Ferroviaires (ARTF)</div>
        <div class="titre">{{ $typeActe->titreDocument() }}</div>
        <div class="sous-titre">Nomination</div>
        <div class="reference">
            Réf. : {{ $reference }}
            &nbsp;|&nbsp;
            Établie le {{ now()->format('d/m/Y') }}
        </div>
    </div>

    <div class="objet">
        <strong>OBJET :</strong>
        Nomination de
        <strong>{{ strtoupper($nomination->agent?->nom ?? '—') }} {{ $nomination->agent?->prenom ?? '' }}</strong>
        au poste de <strong>{{ $nomination->poste }}</strong>
        @if($structure)
            — <strong>{{ $structure->nom }}</strong>
        @endif
    </div>

    <p class="corps">
        Le <span class="signataire">Directeur Général de l'Autorité de Régulation des Transports Ferroviaires</span>,<br><br>
        décide de nommer l'agent dont les informations figurent ci-dessous,
        avec prise d'effet au <strong>{{ $nomination->date_debut?->format('d/m/Y') ?? '—' }}</strong>.
    </p>

    <div class="bloc">
        <div class="bloc-titre">Informations de l'agent</div>
        <div class="ligne">
            <span class="label">Nom &amp; Prénom</span>
            <span class="valeur">{{ strtoupper($nomination->agent?->nom ?? '—') }} {{ $nomination->agent?->prenom ?? '' }}</span>
        </div>
        <div class="ligne">
            <span class="label">Matricule</span>
            <span class="valeur">{{ $nomination->agent?->matricule ?? '—' }}</span>
        </div>
        @if($nomination->agent?->grade)
        <div class="ligne">
            <span class="label">Grade</span>
            <span class="valeur">{{ $nomination->agent->grade->libelle }}</span>
        </div>
        @endif
    </div>

    <div class="bloc">
        <div class="bloc-titre">Nomination</div>
        <div class="ligne">
            <span class="label">Poste</span>
            <span class="valeur">{{ $nomination->poste }}</span>
        </div>
        @if($structure)
        <div class="ligne">
            <span class="label">Structure</span>
            <span class="valeur">{{ $structure->nom }}</span>
        </div>
        @endif
        <div class="ligne">
            <span class="label">Type d'acte</span>
            <span class="valeur">{{ $typeActe->label() }}</span>
        </div>
        <div class="ligne">
            <span class="label">Date de début</span>
            <span class="valeur">{{ $nomination->date_debut?->format('d/m/Y') ?? '—' }}</span>
        </div>
        <div class="ligne">
            <span class="label">Statut</span>
            <span class="valeur"><span class="statut-badge">{{ $nomination->statut?->label() }}</span></span>
        </div>
    </div>

    <div class="signatures">
        <div class="sign-bloc">
            <div class="sign-titre">L'intéressé</div>
            <div class="sign-espace"></div>
        </div>
        <div class="sign-bloc">
            <div class="sign-titre">Le Directeur Général</div>
            <div class="sign-espace"></div>
        </div>
    </div>
</div>

<div class="footer">
    ARTF — {{ $typeActe->label() }} généré automatiquement le {{ now()->format('d/m/Y à H:i') }}
    — Réf. {{ $reference }}
</div>
</body>
</html>
