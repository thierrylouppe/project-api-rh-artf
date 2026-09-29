{{--
    Charte graphique ARTF commune à tous les PDF (dompdf) : polices, marges de page,
    filigrane et pied de page répétés sur chaque page.

    À inclure juste après <body> (dompdf ne répète sur chaque page que les éléments
    position: fixed enfants directs de <body>) :
        @include('pdf.partials.charte', ['variante' => 'officiel'])
    puis l'en-tête institutionnel dans le bloc .header : voir pdf.partials.entete.

    - variante : 'officiel' (actes, décisions, attestations : filigrane) ou 'compact'
                 (bulletins, fiches, reportings).
    - marge    : retrait horizontal du contenu en px (aligne le pied de page sur .page).

    Mise en page en tableaux uniquement : dompdf ne gère pas flexbox.
--}}
@php
    $variante = $variante ?? 'compact';
    $marge = $marge ?? 50;
    $bleu = config('artf.couleurs.bleu');
    $rouge = config('artf.couleurs.rouge');
    $police = fn (string $fichier) => resource_path('pdf/fonts/'.$fichier);

    // dompdf met en cache les métriques des polices @font-face dans ce dossier (config dompdf.font_dir)
    // et échoue s'il n'existe pas (absent d'un clone neuf ou d'un storage partagé en production).
    \Illuminate\Support\Facades\File::ensureDirectoryExists(config('dompdf.options.font_dir', storage_path('fonts')));
@endphp
<style>
    @font-face { font-family: 'Ubuntu'; font-style: normal; font-weight: normal; src: url('{{ $police('Ubuntu-Regular.ttf') }}') format('truetype'); }
    @font-face { font-family: 'Ubuntu'; font-style: normal; font-weight: bold; src: url('{{ $police('Ubuntu-Bold.ttf') }}') format('truetype'); }
    @font-face { font-family: 'Ubuntu'; font-style: italic; font-weight: normal; src: url('{{ $police('Ubuntu-Italic.ttf') }}') format('truetype'); }

    /* !important : la règle "* { margin: 0 }" des modèles écrase @page dans dompdf.
       Marges latérales à 0 : elles restent portées par le padding de .page. */
    @page { margin: 22px 0 100px 0 !important; }
    body { font-family: 'Ubuntu', 'DejaVu Sans', sans-serif; }
    .page { padding-top: 10px; }
    .header { border-bottom-color: {{ $bleu }}; }
    .header .titre { color: {{ $bleu }}; }
    .signatures { page-break-inside: avoid; }

    /* Mention "généré le …" propre à chaque modèle : placée dans la marge basse, au-dessus du pied de charte. */
    div.footer { bottom: -30px; border-top: none; padding-top: 0; }

    .charte-entete { width: 100%; border-collapse: collapse; margin: 0 0 14px 0; }
    /* Neutralise les règles génériques table/th/td des modèles (bordures, marges, largeur). */
    .charte-entete td, .charte-pied-texte td, .charte-barre td, .charte-filet td { border: none; background: none; }
    .charte-entete td { vertical-align: middle; padding: 0; }
    .charte-entete .charte-logo { width: 16%; text-align: left; }
    .charte-entete .charte-institution { width: 50%; text-align: center; }
    .charte-entete .charte-droite { width: 34%; text-align: center; }
    .charte-tutelle { font-size: 9.5px; font-weight: bold; color: #1a1a1a; letter-spacing: 0.3px; line-height: 1.35; }
    .charte-agence { font-size: 10.5px; font-weight: bold; color: {{ $bleu }}; letter-spacing: 0.3px; line-height: 1.35; }
    .charte-direction { font-size: 8.5px; font-weight: bold; color: #333; margin-top: 5px; line-height: 1.35; }
    .charte-etat { font-size: 9.5px; color: #1a1a1a; line-height: 1.45; }
    .charte-etat .charte-pays { font-weight: bold; letter-spacing: 0.3px; }
    .charte-lieu { font-size: 9.5px; color: #1a1a1a; margin-top: 14px; }
    .charte-symbole { height: 74px; }
    .charte-logo-h { height: 46px; }
    .charte-entete.compact .charte-logo { width: 60%; }
    .charte-entete.compact .charte-droite { width: 40%; text-align: right; }

    .charte-filet { width: 60px; border-collapse: collapse; margin: 5px auto; }
    .charte-filet td { height: 3px; padding: 0; font-size: 0; line-height: 0; }
    .charte-filet td.b { background: {{ $bleu }}; }
    .charte-filet td.r { background: {{ $rouge }}; }
    .charte-barre { width: 100%; border-collapse: collapse; margin: 0; }
    .charte-barre td { height: 4px; padding: 0; font-size: 0; line-height: 0; }
    .charte-barre td.b { width: 75%; background: {{ $bleu }}; }
    .charte-barre td.r { width: 25%; background: {{ $rouge }}; }

    .charte-filigrane { position: fixed; top: 320px; left: 0; right: 0; text-align: center; }
    .charte-filigrane img { width: 430px; }

    .charte-pied { position: fixed; bottom: -88px; left: {{ $marge }}px; right: {{ $marge }}px; }
    .charte-pied-texte { width: 100%; border-collapse: collapse; margin: 6px 0 0 0; }
    .charte-pied-texte td { font-size: 8.5px; color: {{ $bleu }}; line-height: 1.5; padding: 0; vertical-align: top; }
    .charte-pied-texte .centre { width: 80%; text-align: center; }
    .charte-pied-texte .cote { width: 10%; text-align: right; color: #888; }
    .charte-pagenum:before { content: counter(page); }
</style>

@if($variante === 'officiel')
    <div class="charte-filigrane"><img src="{{ resource_path('pdf/img/filigrane.png') }}" alt=""></div>
@endif

<div class="charte-pied">
    <table class="charte-barre"><tr><td class="b"></td><td class="r"></td></tr></table>
    <table class="charte-pied-texte">
        <tr>
            <td class="cote"></td>
            <td class="centre">
                {{ config('artf.nom') }}, {{ config('artf.adresse') }}<br>
                Tél : {{ config('artf.telephone') }} | {{ config('artf.email') }} | {{ config('artf.site') }}
            </td>
            <td class="cote">Page <span class="charte-pagenum"></span></td>
        </tr>
    </table>
</div>
