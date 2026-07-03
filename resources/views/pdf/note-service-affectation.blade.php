<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Note de Service — Affectation</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'DejaVu Sans', Arial, sans-serif; font-size: 11px; color: #1a1a1a; line-height: 1.6; }
        .page { padding: 40px 50px; }

        /* En-tête */
        .header { text-align: center; border-bottom: 3px double #003366; padding-bottom: 16px; margin-bottom: 28px; }
        .header .org { font-size: 12px; font-weight: bold; text-transform: uppercase; letter-spacing: 1px; color: #003366; }
        .header .titre { font-size: 20px; font-weight: bold; text-transform: uppercase; letter-spacing: 2px; margin-top: 10px; color: #003366; }
        .header .sous-titre { font-size: 13px; font-weight: bold; color: #555; margin-top: 4px; letter-spacing: 1px; }
        .header .reference { font-size: 10px; color: #666; margin-top: 6px; }

        /* Méta-info */
        .meta { display: flex; justify-content: space-between; margin-bottom: 24px; }
        .meta .meta-item { font-size: 11px; }
        .meta .meta-item strong { color: #003366; }

        /* Objet */
        .objet { margin-bottom: 20px; padding: 10px 14px; background: #eef4ff; border-left: 4px solid #003366; font-size: 12px; }
        .objet strong { color: #003366; }

        /* Blocs info */
        .bloc { border: 1px solid #ccc; border-radius: 4px; padding: 14px 18px; margin-bottom: 16px; }
        .bloc-titre { font-size: 10px; font-weight: bold; text-transform: uppercase; color: #003366; letter-spacing: 0.5px; margin-bottom: 10px; border-bottom: 1px solid #ddd; padding-bottom: 6px; }
        .ligne { display: flex; margin-bottom: 5px; }
        .ligne .label { width: 200px; font-weight: bold; color: #444; flex-shrink: 0; }
        .ligne .valeur { flex: 1; }

        /* Corps du texte */
        .corps { text-align: justify; margin-bottom: 20px; font-size: 11.5px; line-height: 1.8; }
        .corps .signataire { font-weight: bold; text-decoration: underline; }

        /* Motif */
        .motif-bloc { border: 1px solid #e0e0e0; border-radius: 4px; padding: 12px 16px; margin-bottom: 20px; background: #fafafa; font-style: italic; }

        /* Signatures */
        .signatures { display: flex; justify-content: space-between; margin-top: 40px; }
        .sign-bloc { text-align: center; width: 45%; }
        .sign-bloc .sign-titre { font-weight: bold; font-size: 10px; text-transform: uppercase; color: #003366; margin-bottom: 8px; }
        .sign-bloc .sign-espace { height: 60px; border-bottom: 1px solid #999; margin-bottom: 8px; }
        .sign-bloc .sign-nom { font-size: 10px; color: #555; }

        /* Pied de page */
        .footer { position: fixed; bottom: 20px; left: 50px; right: 50px; text-align: center; font-size: 9px; color: #aaa; border-top: 1px solid #ddd; padding-top: 6px; }

        /* Badge statut */
        .statut-badge { display: inline-block; padding: 2px 8px; border-radius: 3px; font-size: 9px; font-weight: bold; background: #e6f0ff; color: #003366; border: 1px solid #b3ccff; margin-left: 8px; vertical-align: middle; }
    </style>
</head>
<body>
<div class="page">

    {{-- En-tête --}}
    <div class="header">
        <div class="org">Autorité de Régulation des Transports Ferroviaires (ARTF)</div>
        <div class="titre">Note de Service</div>
        <div class="sous-titre">Affectation de Personnel</div>
        <div class="reference">
            Réf. : NS-AFF-{{ date('Y') }}-{{ str_pad($affectation->id, 4, '0', STR_PAD_LEFT) }}
            &nbsp;|&nbsp;
            Établie le {{ now()->format('d/m/Y') }}
        </div>
    </div>

    {{-- Méta-informations --}}
    <div class="meta">
        <div class="meta-item">
            <strong>N° :</strong> NS-AFF-{{ date('Y') }}-{{ str_pad($affectation->id, 4, '0', STR_PAD_LEFT) }}
        </div>
        <div class="meta-item">
            <strong>Date d'effet :</strong> {{ $affectation->date_affectation?->format('d/m/Y') ?? '—' }}
        </div>
        <div class="meta-item">
            <strong>Statut :</strong>
            <span class="statut-badge">{{ $affectation->statut?->label() }}</span>
        </div>
    </div>

    {{-- Objet --}}
    <div class="objet">
        <strong>OBJET :</strong>
        Affectation de
        <strong>{{ strtoupper($affectation->agent?->nom ?? '—') }} {{ $affectation->agent?->prenom ?? '' }}</strong>
        @if($structure)
            au(à la) <strong>{{ $structure->nom }}</strong>
        @endif
    </div>

    {{-- Corps principal --}}
    <p class="corps">
        Le <span class="signataire">Directeur Général de l'Autorité de Régulation des Transports Ferroviaires</span>,<br>
        <br>
        décide de l'affectation de l'agent dont les informations figurent ci-dessous,
        avec prise d'effet au <strong>{{ $affectation->date_affectation?->format('d/m/Y') ?? '—' }}</strong>.
    </p>

    {{-- Informations de l'agent --}}
    <div class="bloc">
        <div class="bloc-titre">Informations de l'agent</div>
        <div class="ligne">
            <span class="label">Nom &amp; Prénom</span>
            <span class="valeur">{{ strtoupper($affectation->agent?->nom ?? '—') }} {{ $affectation->agent?->prenom ?? '' }}</span>
        </div>
        <div class="ligne">
            <span class="label">Matricule</span>
            <span class="valeur">{{ $affectation->agent?->matricule ?? '—' }}</span>
        </div>
        @if($affectation->agent?->grade)
        <div class="ligne">
            <span class="label">Grade</span>
            <span class="valeur">{{ $affectation->agent->grade->libelle }}</span>
        </div>
        @endif
        @if($affectation->agent?->categorie)
        <div class="ligne">
            <span class="label">Catégorie</span>
            <span class="valeur">{{ $affectation->agent->categorie->libelle }}</span>
        </div>
        @endif
        @if($affectation->agent?->echelon)
        <div class="ligne">
            <span class="label">Échelon</span>
            <span class="valeur">{{ $affectation->agent->echelon->libelle }}</span>
        </div>
        @endif
    </div>

    {{-- Détails de l'affectation --}}
    <div class="bloc">
        <div class="bloc-titre">Lieu d'affectation</div>
        @if($structure)
        <div class="ligne">
            <span class="label">Structure</span>
            <span class="valeur">{{ $structure->nom }}</span>
        </div>
        @if(isset($structure->service))
        <div class="ligne">
            <span class="label">Service de rattachement</span>
            <span class="valeur">{{ $structure->service->nom }}</span>
        </div>
        @endif
        @if(isset($structure->service->direction) || isset($structure->direction))
        <div class="ligne">
            <span class="label">Direction</span>
            <span class="valeur">{{ $structure->service->direction->nom ?? $structure->direction->nom ?? '—' }}</span>
        </div>
        @endif
        @endif
        <div class="ligne">
            <span class="label">Date d'effet</span>
            <span class="valeur">{{ $affectation->date_affectation?->format('d/m/Y') ?? '—' }}</span>
        </div>
        @if($affectation->date_fin)
        <div class="ligne">
            <span class="label">Date de fin</span>
            <span class="valeur">{{ $affectation->date_fin->format('d/m/Y') }}</span>
        </div>
        @endif
        @if($affectation->superieurHierarchique)
        <div class="ligne">
            <span class="label">Supérieur hiérarchique</span>
            <span class="valeur">
                {{ strtoupper($affectation->superieurHierarchique->nom) }}
                {{ $affectation->superieurHierarchique->prenom }}
                @if($affectation->superieurHierarchique->matricule)
                    ({{ $affectation->superieurHierarchique->matricule }})
                @endif
            </span>
        </div>
        @else
        <div class="ligne">
            <span class="label">Supérieur hiérarchique</span>
            <span class="valeur" style="color:#888;font-style:italic;">Non défini</span>
        </div>
        @endif
    </div>

    {{-- Motif --}}
    @if($affectation->motif)
    <div class="bloc">
        <div class="bloc-titre">Motif</div>
        <div class="motif-bloc">{{ $affectation->motif }}</div>
    </div>
    @endif

    {{-- Signatures --}}
    <div class="signatures">
        <div class="sign-bloc">
            <div class="sign-titre">Le Supérieur Hiérarchique</div>
            <div class="sign-espace"></div>
            <div class="sign-nom">
                @if($affectation->superieurHierarchique)
                    {{ strtoupper($affectation->superieurHierarchique->nom) }} {{ $affectation->superieurHierarchique->prenom }}
                @else
                    ........................
                @endif
            </div>
        </div>
        <div class="sign-bloc">
            <div class="sign-titre">Le Directeur Général</div>
            <div class="sign-espace"></div>
            <div class="sign-nom">Signature &amp; Cachet</div>
        </div>
    </div>

</div>

<div class="footer">
    ARTF — Note de service générée automatiquement le {{ now()->format('d/m/Y à H:i') }}
    — Réf. NS-AFF-{{ date('Y') }}-{{ str_pad($affectation->id, 4, '0', STR_PAD_LEFT) }}
    — Ne pas reproduire sans autorisation
</div>
</body>
</html>
