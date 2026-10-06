<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>ARTF - Fiche d'évaluation</title>
</head>
<body>
@include('pdf.partials.charte', ['variante' => 'officiel', 'marge' => 36])
<style>
        body {
            font-family: 'Ubuntu', 'DejaVu Sans', sans-serif;
            font-size: 13px;
            color: #111;
        }
        .page { padding: 4px 36px 0; }
        table { width: 100%; border-collapse: collapse; }
        td, th { vertical-align: top; }
        .nu, .nu td, .nu th { border: none; padding: 0; }
        .grille { margin-bottom: 8px; }
        .grille td, .grille th {
            border: 1px solid #222;
            padding: 7px 8px;
            font-size: 13px;
        }
        .grille th { background: #f4f4f4; font-weight: bold; text-align: center; }
        .entete { font-size: 9px; font-weight: bold; text-align: center; line-height: 1.35; }
        .filet { letter-spacing: 1px; }
        .centre { text-align: center; }
        .droite { text-align: right; }
        .gras { font-weight: bold; }
        .souligne { text-decoration: underline; }
        .titre-fiche {
            border: 1px solid #222;
            text-align: center;
            padding: 20px 12px;
            margin: 16px 0 14px;
        }
        .titre-fiche h1 {
            font-size: 14px;
            margin: 0;
            letter-spacing: 0.4px;
        }
        .titre-fiche p { margin: 10px 0 0; font-size: 13px; }
        h3 {
            font-size: 13px;
            margin: 14px 0 12px;
        }
        table.identite, table.identite tr, table.identite td {
            border: none;
            border-left: 0;
            border-right: 0;
            border-top: 0;
            border-bottom: 0;
        }
        table.identite { margin: 4px 0 10px; border-collapse: collapse; }
        table.identite td { padding: 6px 10px 6px 0; font-size: 13px; }
        .nouvelle-page { page-break-before: always; }
        .encadre {
            border: 1px solid #222;
            padding: 8px 10px;
            margin: 6px 0 10px;
        }
        .encadre p { margin: 0 0 6px; }
        .signature { height: 62px; }
        .mention { font-size: 14px; font-weight: bold; margin: 10px 0; }
        .photo { width: 72px; height: 90px; }
        h2 {
            font-size: 14px;
            margin: 18px 0 14px;
            text-align: center;
            text-transform: none;
            letter-spacing: 0;
            border: none;
            padding: 0;
            color: #111;
        }
        table.grille th {
            background: #f4f4f4;
            color: #111;
            font-size: 13px;
            font-weight: bold;
            text-align: center;
            text-transform: none;
            letter-spacing: 0;
            border: 1px solid #222;
        }
        table.grille td { background: #fff; color: #111; border: 1px solid #222; }
        table.grille tr:nth-child(even) td { background: #fff; }
        table.identite tr td, table.identite tr:nth-child(even) td, table.nu td {
            background: none;
            border: none;
            color: #111;
        }
        .bandeau td { border: none; background: none; vertical-align: top; padding: 0; }
        table.entete-lettre, table.entete-lettre tr td, table.entete-lettre tr:nth-child(even) td {
            border: none;
            background: none;
            color: #1a1a1a;
            vertical-align: top;
            padding: 0;
        }
        .entete-lettre .pile { width: 1px; }
        .entete-lettre .pile-axe {
            width: auto;
            margin: 0;
            text-align: center;
            font-size: 11px;
            font-weight: bold;
            line-height: 1.25;
            text-transform: uppercase;
            white-space: nowrap;
        }
        .entete-lettre .pile-axe .agence { color: {{ config('artf.couleurs.bleu') }}; margin-top: 8px; }
        .entete-lettre .pile-axe .suite { margin-top: 8px; }
        .entete-lettre .cote { width: 100%; text-align: right; }
        .entete-lettre .republique {
            font-size: 11px;
            font-weight: bold;
            text-transform: uppercase;
            line-height: 1.35;
        }
        .entete-lettre .devise { font-weight: normal; text-transform: none; }
        table.entete-lettre table.charte-filet {
            width: 60px;
            margin: 5px auto 0;
            border-collapse: collapse;
        }
        table.entete-lettre table.charte-filet td,
        table.entete-lettre table.charte-filet tr:nth-child(even) td {
            height: 3px;
            padding: 0;
            border: none;
            font-size: 0;
            line-height: 0;
        }
        table.entete-lettre table.charte-filet td.b { width: 44px; background: {{ config('artf.couleurs.bleu') }}; }
        table.entete-lettre table.charte-filet td.r { width: 16px; background: {{ config('artf.couleurs.rouge') }}; }
        .logo-entete { height: 78px; margin: 4px 0; }
    </style>
@php
    $maj = fn (?string $texte) => mb_strtoupper((string) $texte, 'UTF-8');
@endphp
<div class="page">
<table class="entete-lettre">
    <tr>
        <td class="pile">
            <table class="pile-axe" align="left">
                <tr><td>
                    {{ $maj("Ministère de l'Économie") }}<br>ET DES FINANCES
                    <table class="charte-filet" align="center"><tr><td class="b"></td><td class="r"></td></tr></table>
                    <div class="agence">{{ $maj('Agence de Régulation') }}<br>{{ $maj('des Transferts de Fonds') }}</div>
                    <img class="logo-entete" src="{{ resource_path('pdf/img/logo-symbole.png') }}" alt="{{ config('artf.sigle') }}">
                    <div class="suite">{{ $maj('Direction des Ressources') }}<br>{{ $maj('Humaines et de la Logistique') }}</div>
                    <div class="suite">{{ $maj('Service des ressources humaines') }}</div>
                    <div class="suite">{{ $maj('Bureau personnel') }}</div>
                </td></tr>
            </table>
        </td>
        <td class="cote republique">
            {{ $maj(config('artf.pays')) }}<br>
            <span class="devise">{{ config('artf.devise') }}</span>
            @if($doc['photo'])
                <br><img src="{{ $doc['photo'] }}" alt="" class="photo">
            @endif
        </td>
    </tr>
</table>

<div class="titre-fiche">
    <h1>{{ $doc['titre'] }}</h1>
    @if($doc['session'])
        <p>{{ $doc['session'] }}</p>
    @endif
</div>

<p class="gras droite">Date de l'évaluation : {{ $doc['date_evaluation'] }}</p>

<h2>I. <span class="souligne">RENSEIGNEMENTS GENERAUX (partie réservée à la DRHL)</span></h2>

<h3>1. <span class="souligne">Identification de l'agent à noter</span></h3>
<table class="identite">
    <tr>
        <td>Nom (s) : {{ $doc['agent']['nom'] }}</td>
        <td>Prénom (s) : {{ $doc['agent']['prenom'] }}</td>
        <td>Matricule : {{ $doc['agent']['matricule'] }}</td>
    </tr>
    <tr>
        <td>Grade : {{ $doc['agent']['grade'] }}</td>
        <td>Fonction : {{ $doc['agent']['fonction'] }}</td>
        <td>Ancienneté : {{ $doc['agent']['anciennete'] }}</td>
    </tr>
    <tr>
        <td>Direction : {{ $doc['agent']['direction'] }}</td>
        <td>Service : {{ $doc['agent']['service'] }}</td>
        <td>Bureau : {{ $doc['agent']['bureau'] }}</td>
    </tr>
</table>

<h3>2. <span class="souligne">Identification du supérieur hiérarchique</span></h3>
<table class="identite">
    <tr>
        <td>Nom (s) et Prénom (s) : {{ $doc['superieur']['nom_complet'] }}</td>
    </tr>
    <tr>
        <td>Fonction / Grade : {{ $doc['superieur']['fonction_grade'] }}</td>
    </tr>
</table>

<h3 class="nouvelle-page">3. <span class="souligne">Suivi administratif (partie réservée à la DRHL)</span></h3>

<table class="nu">
    <tr>
        <td style="width: 48%; padding-right: 8px;">
            <table class="grille">
                <tr>
                    <th>Nombre de jours d'absences non justifiées</th>
                </tr>
                <tr>
                    <td class="centre" style="height: 36px;">{{ $doc['jours_absence'] }}</td>
                </tr>
            </table>
        </td>
        <td style="width: 52%; padding-left: 8px;">
            <table class="grille">
                <tr>
                    <th colspan="2">Sanctions</th>
                </tr>
                @forelse($doc['sanctions'] as $sanction)
                    @if($loop->first)
                        <tr>
                            <th>Nature</th>
                            <th>Nombre de fois</th>
                        </tr>
                    @endif
                    <tr>
                        <td>{{ $sanction['nature'] }}</td>
                        <td class="centre">{{ $sanction['nombre'] }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="2" class="centre">Aucune sanction</td>
                    </tr>
                @endforelse
            </table>
        </td>
    </tr>
</table>

<h2>II. <span class="souligne">CRITERES GENERAUX D'EVALUATION</span></h2>

@foreach($doc['blocs'] as $bloc)
    <h3 class="{{ $bloc['saut'] ? 'nouvelle-page' : '' }}">
        {{ $bloc['numero'] }}. <span class="souligne">{{ $bloc['titre'] }}</span>
    </h3>
    <table class="grille">
        <tr>
            <th style="width: 70%;"></th>
            <th style="width: 15%;">Note</th>
            <th style="width: 15%;">Barème</th>
        </tr>
        @forelse($bloc['lignes'] as $ligne)
            <tr>
                <td>{{ $ligne['libelle'] }}</td>
                <td class="centre">{{ $ligne['note'] }}</td>
                <td class="centre">{{ $ligne['bareme'] }}</td>
            </tr>
        @empty
            <tr>
                <td colspan="3" class="centre">Aucune note saisie.</td>
            </tr>
        @endforelse
        <tr>
            <td></td>
            <td class="centre gras">Total</td>
            <td class="centre gras">{{ $bloc['total'] }} / {{ $bloc['bareme'] }}</td>
        </tr>
    </table>
@endforeach

<p class="mention">NOTE GLOBALE : {{ $doc['note_globale'] }} / 20 @if($doc['mention']) — {{ $doc['mention'] }} @endif</p>

@if(count($doc['connaissances']) > 0)
    <h3 class="centre souligne">Connaissances complémentaires à acquérir (à énumérer)</h3>
    <div class="encadre">
        @foreach($doc['connaissances'] as $connaissance)
            <p>— {{ $connaissance }}</p>
        @endforeach
    </div>
@endif

<h3 class="centre souligne">Avis</h3>

@foreach($doc['avis'] as $avis)
    <div class="encadre">
        <p class="gras souligne centre">{{ $avis['titre'] }}</p>
        <p>{!! nl2br(e($avis['texte'])) !!}</p>
        <p class="droite">({{ $avis['signataire'] }}, {{ $avis['date'] ?? '—' }} et signature)</p>
    </div>
@endforeach

@if($doc['reclamation'])
    <h3 class="centre souligne">Réclamations éventuelles (s'il y a lieu)</h3>
    <div class="encadre">
        <p>{{ $doc['reclamation'] }}</p>
    </div>
@endif

<table class="grille" style="margin-top: 12px;">
    <tr>
        <td class="gras" style="width: 48%; border-bottom: 1px solid #222;">Signature de l'évaluateur :</td>
        <td style="width: 4%; border: none;"></td>
        <td class="gras droite" style="width: 48%;">Signature de l'évalué(e) :</td>
    </tr>
    <tr>
        <td class="signature">
            @if($doc['signature_evaluateur'])
                Signé le {{ $doc['signature_evaluateur'] }}
            @endif
        </td>
        <td style="border: none;"></td>
        <td class="signature droite">
            @if($doc['signature_evalue'])
                Signé le {{ $doc['signature_evalue'] }}
            @endif
        </td>
    </tr>
</table>

@if($doc['commission'])
    <h2>III. <span class="souligne">EVALUATION GENERALE PAR LA COMMISSION</span></h2>

    <table class="grille">
        <tr>
            <th style="width: 70%;">Question</th>
            <th style="width: 15%;">Note</th>
            <th style="width: 15%;">Barème</th>
        </tr>
        @foreach($doc['blocs'] as $bloc)
            <tr>
                <td colspan="3" class="gras">{{ $bloc['numero'] }}. <span class="souligne">{{ $bloc['titre'] }}</span></td>
            </tr>
            @forelse($bloc['lignes'] as $ligne)
                <tr>
                    <td>{{ $ligne['libelle'] }}</td>
                    <td class="centre">{{ $ligne['note'] }}</td>
                    <td class="centre">{{ $ligne['bareme'] }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="3" class="centre">Aucune note saisie.</td>
                </tr>
            @endforelse
        @endforeach
        @php
            $sommeNotes = 0.0;
            $sommeBaremes = 0.0;
            foreach ($doc['blocs'] as $blocCommission) {
                $sommeNotes += (float) str_replace(',', '.', str_replace(' ', '', (string) $blocCommission['total']));
                $sommeBaremes += (float) str_replace(',', '.', str_replace(' ', '', (string) $blocCommission['bareme']));
            }
            $totalGeneral = rtrim(rtrim(number_format($sommeNotes, 2, ',', ' '), '0'), ',');
            $baremeGeneral = rtrim(rtrim(number_format($sommeBaremes, 2, ',', ' '), '0'), ',');
        @endphp
        <tr>
            <td class="gras">Total général</td>
            <td class="centre gras">{{ $totalGeneral }}</td>
            <td class="centre gras">/ {{ $baremeGeneral }}</td>
        </tr>
    </table>

    @if($doc['commission']['synthese'])
        <h3 class="nouvelle-page centre souligne">Evaluation du Président de la commission</h3>
        <div class="encadre">
            <p>{{ $doc['commission']['synthese'] }}</p>
        </div>
    @endif

    @if($doc['commission']['decision'])
        <h2>IV. <span class="souligne">DECISION DE LA COMMISSION D'AVANCEMENT</span></h2>
        <div class="encadre">
            <p class="gras">Décision de la commission d'avancement :</p>
            <p>{{ $doc['commission']['decision'] }}</p>
        </div>
        <p class="droite">Fait à Brazzaville, le</p>
        <p class="droite gras">LE PRESIDENT.</p>
    @endif
@endif

</div>
</body>
</html>
