<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Attestation de Stage</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'DejaVu Sans', Arial, sans-serif; font-size: 12px; color: #1a1a1a; line-height: 1.6; }

        .page { padding: 40px 50px; }

        /* En-tête */
        .header { text-align: center; border-bottom: 3px double #003366; padding-bottom: 16px; margin-bottom: 28px; }
        .header .org { font-size: 13px; font-weight: bold; text-transform: uppercase; letter-spacing: 1px; color: #003366; }
        .header .titre { font-size: 20px; font-weight: bold; text-transform: uppercase; letter-spacing: 2px; margin-top: 10px; color: #003366; }
        .header .reference { font-size: 10px; color: #666; margin-top: 6px; }

        /* Corps */
        .intro { text-align: justify; margin-bottom: 20px; font-size: 12.5px; }
        .intro .signataire { font-weight: bold; text-decoration: underline; }

        /* Bloc informations */
        .bloc { border: 1px solid #ccc; border-radius: 4px; padding: 16px 20px; margin-bottom: 20px; background: #fafafa; }
        .bloc-titre { font-size: 11px; font-weight: bold; text-transform: uppercase; color: #003366; letter-spacing: 0.5px; margin-bottom: 10px; border-bottom: 1px solid #ddd; padding-bottom: 6px; }
        .ligne { display: flex; margin-bottom: 6px; }
        .ligne .label { width: 200px; font-weight: bold; color: #444; flex-shrink: 0; }
        .ligne .valeur { flex: 1; }

        /* Évaluation */
        .evaluation { margin-bottom: 20px; }
        .note-cercle { display: inline-block; width: 50px; height: 50px; border: 3px solid #003366; border-radius: 50%; text-align: center; line-height: 44px; font-size: 18px; font-weight: bold; color: #003366; margin-right: 12px; vertical-align: middle; }

        /* Certifie */
        .certifie { background: #eef4ff; border-left: 4px solid #003366; padding: 12px 16px; margin-bottom: 28px; font-size: 12.5px; }

        /* Signatures */
        .signatures { display: flex; justify-content: space-between; margin-top: 40px; }
        .sign-bloc { text-align: center; width: 45%; }
        .sign-bloc .sign-titre { font-weight: bold; font-size: 11px; text-transform: uppercase; color: #003366; margin-bottom: 8px; }
        .sign-bloc .sign-espace { height: 60px; border-bottom: 1px solid #999; margin-bottom: 8px; }
        .sign-bloc .sign-nom { font-size: 11px; color: #555; }

        /* Pied de page */
        .footer { position: fixed; bottom: 20px; left: 50px; right: 50px; text-align: center; font-size: 9px; color: #999; border-top: 1px solid #ddd; padding-top: 6px; }
    </style>
</head>
<body>
<div class="page">

    {{-- En-tête --}}
    <div class="header">
        <div class="org">Autorité de Régulation des Transports Ferroviaires (ARTF)</div>
        <div class="titre">Attestation de Stage</div>
        <div class="reference">
            Réf. : {{ $convention->dossier?->reference ?? '—' }} &nbsp;|&nbsp;
            Établi le {{ now()->format('d/m/Y') }}
        </div>
    </div>

    {{-- Introduction --}}
    <p class="intro">
        Le <span class="signataire">Directeur Général de l'Autorité de Régulation des Transports Ferroviaires</span>,
        <br>certifie que :
    </p>

    {{-- Informations stagiaire --}}
    <div class="bloc">
        <div class="bloc-titre">Informations du stagiaire</div>
        <div class="ligne">
            <span class="label">Nom &amp; Prénom</span>
            <span class="valeur">{{ strtoupper($convention->agent?->nom ?? '—') }} {{ $convention->agent?->prenom ?? '' }}</span>
        </div>
        <div class="ligne">
            <span class="label">Matricule</span>
            <span class="valeur">{{ $convention->agent?->matricule ?? '—' }}</span>
        </div>
        <div class="ligne">
            <span class="label">Établissement d'origine</span>
            <span class="valeur">{{ $convention->etablissement }}</span>
        </div>
        <div class="ligne">
            <span class="label">Type de stage</span>
            <span class="valeur">{{ $convention->type_stage?->label() ?? '—' }}</span>
        </div>
    </div>

    {{-- Période du stage --}}
    <div class="bloc">
        <div class="bloc-titre">Période du stage</div>
        <div class="ligne">
            <span class="label">Date de début</span>
            <span class="valeur">{{ $convention->date_debut?->format('d/m/Y') ?? '—' }}</span>
        </div>
        <div class="ligne">
            <span class="label">Date de fin</span>
            <span class="valeur">{{ $convention->date_fin?->format('d/m/Y') ?? '—' }}</span>
        </div>
        @if($convention->tuteurInterne)
        <div class="ligne">
            <span class="label">Tuteur ARTF</span>
            <span class="valeur">{{ $convention->tuteurInterne->nom_complet }}</span>
        </div>
        @endif
    </div>

    {{-- Évaluation finale --}}
    @if($convention->note_finale !== null)
    <div class="bloc evaluation">
        <div class="bloc-titre">Évaluation finale</div>
        <p>
            <span class="note-cercle">{{ number_format($convention->note_finale, 1) }}</span>
            <strong>Note finale :</strong> {{ number_format($convention->note_finale, 2) }} / 20
        </p>
        @if($convention->appreciation)
        <p style="margin-top:10px;">
            <strong>Appréciation :</strong><br>
            <em>{{ $convention->appreciation }}</em>
        </p>
        @endif
    </div>
    @endif

    {{-- Certifié --}}
    <div class="certifie">
        La présente attestation est délivrée à
        <strong>{{ strtoupper($convention->agent?->nom ?? '—') }} {{ $convention->agent?->prenom ?? '' }}</strong>
        pour servir et valoir ce que de droit.
    </div>

    {{-- Signatures --}}
    <div class="signatures">
        <div class="sign-bloc">
            <div class="sign-titre">Le Tuteur ARTF</div>
            <div class="sign-espace"></div>
            <div class="sign-nom">{{ $convention->tuteurInterne?->nom_complet ?? '........................' }}</div>
        </div>
        <div class="sign-bloc">
            <div class="sign-titre">Le Directeur Général</div>
            <div class="sign-espace"></div>
            <div class="sign-nom">Signature &amp; Cachet</div>
        </div>
    </div>

</div>

<div class="footer">
    ARTF — Document généré automatiquement le {{ now()->format('d/m/Y à H:i') }} — Ne pas reproduire sans autorisation
</div>
</body>
</html>
