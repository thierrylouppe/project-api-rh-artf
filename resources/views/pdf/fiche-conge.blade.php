<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Fiche de congé</title>
    <style>
        body { font-family: 'DejaVu Sans', Arial, sans-serif; font-size: 11px; color: #1a1a1a; }
        .page { padding: 40px 50px; }
        .header { text-align: center; padding-bottom: 16px; margin-bottom: 20px; }
        .titre { font-size: 18px; font-weight: bold; text-transform: uppercase; margin-top: 10px; }

        /* Mise en page en tableaux : dompdf ne gère pas flexbox. */
        .dc-grille { width: 100%; border-collapse: separate; border-spacing: 0; }
        .dc-grille td { vertical-align: top; padding: 0; border: none; }
        .dc-surtitre { font-size: 8.5px; font-weight: bold; color: #6b7684; text-transform: uppercase; letter-spacing: 1px; }

        .dc-demandeur { border: 1px solid #e3e8ef; border-left: 4px solid #035191; padding: 12px 16px; margin-bottom: 18px; }
        .dc-nom { font-size: 15px; font-weight: bold; color: #035191; margin: 3px 0 2px 0; }
        .dc-infos { font-size: 10px; color: #6b7684; }
        .dc-statut { display: inline-block; padding: 4px 12px; border-radius: 10px; font-size: 9.5px; font-weight: bold; text-transform: uppercase; letter-spacing: 0.5px; }
        .dc-statut.ok { background: #e7f5ee; color: #1e7d4f; border: 1px solid #b9e2cc; }
        .dc-statut.ko { background: #fdecee; color: #c2182f; border: 1px solid #f6c3ca; }
        .dc-statut.encours { background: #fff5e0; color: #9a6400; border: 1px solid #f1d79c; }

        .dc-section { font-size: 10.5px; font-weight: bold; color: #035191; text-transform: uppercase; letter-spacing: 1px; border-left: 3px solid #ed2642; padding: 1px 0 1px 8px; margin: 0 0 10px 0; }

        .dc-tuiles { width: 100%; border-collapse: collapse; margin-bottom: 12px; }
        .dc-tuiles td.dc-espace, .dc-etapes td.dc-espace { width: 2%; background: none; border: none; padding: 0; }
        .dc-tuiles td { width: 32%; background: #f5f8fb; border: 1px solid #e3e8ef; border-top: 3px solid #035191; padding: 10px 12px; vertical-align: top; }
        .dc-tuiles td.duree { border-top-color: #ed2642; }
        .dc-grand { font-size: 17px; font-weight: bold; color: #1f2a37; margin-top: 4px; }
        .dc-petit { font-size: 9.5px; color: #6b7684; }

        .dc-type { margin-bottom: 8px; font-size: 11px; }
        .dc-type strong { color: #035191; }
        .dc-motif { background: #f5f8fb; border-left: 3px solid #c9d6e6; padding: 8px 12px; font-style: italic; color: #374151; margin-bottom: 20px; }

        .dc-etapes { width: 100%; border-collapse: collapse; }
        .dc-etapes td { border: 1px solid #e3e8ef; padding: 10px 12px; vertical-align: top; }
        .dc-num { display: inline-block; width: 16px; padding: 3px 0; line-height: 1; border-radius: 8px; background: #035191; color: #fff; font-size: 9px; font-weight: bold; text-align: center; margin-right: 4px; }
        .dc-etape-titre { font-size: 9.5px; font-weight: bold; color: #1f2a37; text-transform: uppercase; letter-spacing: 0.4px; }
        .dc-etat { font-size: 10px; font-weight: bold; margin: 8px 0 4px 0; }
        .dc-etat.ok { color: #1e7d4f; }
        .dc-etat.ko { color: #c2182f; }
        .dc-etat.attente { color: #9a6400; }
        .dc-etat.na { color: #9aa3ad; }
        .dc-valideur { font-size: 10px; color: #1f2a37; }
        .dc-date { font-size: 9px; color: #6b7684; }
        .dc-commentaire { font-size: 9.5px; font-style: italic; color: #4b5563; margin-top: 6px; padding-top: 6px; border-top: 1px dashed #e3e8ef; }
    </style>
</head>
<body>
@include('pdf.partials.charte', ['variante' => 'compact'])
@use('App\Enums\StatutDemandeConge', 'S')
@php
    $agent = $demande->agent;
    $statut = $demande->statut;
    $classeStatut = match ($statut) {
        S::VALIDEE_DG => 'ok',
        S::REJETEE_N1, S::REJETEE_RH, S::REJETEE_DG, S::ANNULEE => 'ko',
        default => 'encours',
    };
    $dateLongue = fn ($date) => $date?->locale('fr')->isoFormat('dddd D MMMM YYYY');

    // État de chaque visa du circuit N+1 → RH → DG (l'étape N+1 peut être court-circuitée).
    $etape = function (string $niveau, S $rejet, array $statutsAval, $valideur, $date, ?string $commentaire) use ($statut) {
        [$classe, $libelle] = match (true) {
            $statut === $rejet => ['ko', 'Rejetée'],
            $date !== null => ['ok', 'Validée'],
            $statut === S::ANNULEE => ['na', 'Demande annulée'],
            in_array($statut, $statutsAval, true) => ['na', 'Non requise'],
            default => ['attente', 'En attente'],
        };

        return compact('niveau', 'classe', 'libelle', 'valideur', 'date', 'commentaire');
    };
    $etapes = [
        $etape('Supérieur hiérarchique (N+1)', S::REJETEE_N1, [S::VALIDEE_RH, S::REJETEE_RH, S::VALIDEE_DG, S::REJETEE_DG],
            $demande->valideurN1?->name, $demande->date_validation_n1, $demande->commentaire_n1),
        $etape('Ressources humaines', S::REJETEE_RH, [S::VALIDEE_DG, S::REJETEE_DG],
            $demande->valideurRh?->name, $demande->date_validation_rh, $demande->commentaire_rh),
        $etape('Direction générale', S::REJETEE_DG, [],
            $demande->valideurDg?->name, $demande->date_validation_dg, $demande->commentaire_dg),
    ];
@endphp
<div class="page">
    <div class="header">
        @include('pdf.partials.entete', ['variante' => 'compact'])
        <div class="titre">Fiche de demande de congé</div>
        <div class="reference">Demande n° {{ str_pad($demande->id, 4, '0', STR_PAD_LEFT) }} · déposée le {{ $demande->created_at?->format('d/m/Y') ?? '—' }}</div>
    </div>

    {{-- Demandeur --}}
    <div class="dc-demandeur">
        <table class="dc-grille">
            <tr>
                <td>
                    <div class="dc-surtitre">Demandeur</div>
                    <div class="dc-nom">{{ mb_strtoupper($agent->nom) }} {{ $agent->prenom }}</div>
                    <div class="dc-infos">
                        Matricule {{ $agent->matricule ?? '—' }}
                        @if($agent->fonction) · {{ $agent->fonction->nom }}@endif
                        @if($agent->grade) · {{ $agent->grade->nom }}@endif
                    </div>
                </td>
                <td style="text-align: right; width: 32%;">
                    <div class="dc-surtitre" style="margin-bottom: 5px;">Statut</div>
                    <span class="dc-statut {{ $classeStatut }}">{{ $statut->label() }}</span>
                </td>
            </tr>
        </table>
    </div>

    {{-- Congé demandé --}}
    <div class="dc-section">Congé demandé</div>
    <table class="dc-tuiles">
        <tr>
            <td>
                <div class="dc-surtitre">Du</div>
                <div class="dc-grand">{{ $demande->date_debut->format('d/m/Y') }}</div>
                <div class="dc-petit">{{ $dateLongue($demande->date_debut) }}</div>
            </td>
            <td class="dc-espace"></td>
            <td>
                <div class="dc-surtitre">Au</div>
                <div class="dc-grand">{{ $demande->date_fin->format('d/m/Y') }}</div>
                <div class="dc-petit">{{ $dateLongue($demande->date_fin) }}</div>
            </td>
            <td class="dc-espace"></td>
            <td class="duree">
                <div class="dc-surtitre">Durée</div>
                <div class="dc-grand">{{ $demande->nb_jours }} jour{{ $demande->nb_jours > 1 ? 's' : '' }}</div>
                <div class="dc-petit">ouvrable{{ $demande->nb_jours > 1 ? 's' : '' }}</div>
            </td>
        </tr>
    </table>
    <div class="dc-type">Nature du congé : <strong>{{ $demande->typeConge->nom }}</strong></div>
    <div class="dc-motif">{{ $demande->motif ?: 'Aucun motif précisé.' }}</div>

    {{-- Circuit de validation --}}
    <div class="dc-section">Circuit de validation</div>
    <table class="dc-etapes">
        <tr>
            @foreach($etapes as $i => $e)
                @if($i > 0)<td class="dc-espace"></td>@endif
                <td style="width: 32%;">
                    <span class="dc-num">{{ $i + 1 }}</span>
                    <span class="dc-etape-titre">{{ $e['niveau'] }}</span>
                    <div class="dc-etat {{ $e['classe'] }}">{{ $e['libelle'] }}</div>
                    @if($e['valideur'])
                        <div class="dc-valideur">{{ $e['valideur'] }}</div>
                    @endif
                    @if($e['date'])
                        <div class="dc-date">le {{ $e['date']->format('d/m/Y à H:i') }}</div>
                    @endif
                    @if($e['commentaire'])
                        <div class="dc-commentaire">« {{ $e['commentaire'] }} »</div>
                    @endif
                </td>
            @endforeach
        </tr>
    </table>
</div>
</body>
</html>
