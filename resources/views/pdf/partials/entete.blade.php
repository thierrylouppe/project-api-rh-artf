{{--
    En-tête institutionnel ARTF, à placer en tête du bloc .header des modèles PDF.
    Les styles sont fournis par pdf.partials.charte (inclus juste après <body>).

        @include('pdf.partials.entete', ['variante' => 'officiel'])

    - variante  : 'officiel' (tutelle, agence, direction, République + lieu et date)
                  ou 'compact' (logo horizontal + République, barre bicolore).
    - direction : direction émettrice (défaut : config('artf.direction')).
--}}
@php
    $variante = $variante ?? 'compact';
    $direction = $direction ?? config('artf.direction');
    $maj = fn (?string $texte) => mb_strtoupper((string) $texte, 'UTF-8');
@endphp
@if($variante === 'officiel')
    <table class="charte-entete">
        <tr>
            <td class="charte-logo">
                <img class="charte-symbole" src="{{ resource_path('pdf/img/logo-symbole.png') }}" alt="{{ config('artf.sigle') }}">
            </td>
            <td class="charte-institution">
                <div class="charte-tutelle">{{ $maj(config('artf.tutelle')) }}</div>
                <table class="charte-filet"><tr><td class="b" style="width: 44px;"></td><td class="r" style="width: 16px;"></td></tr></table>
                <div class="charte-agence">{{ $maj(config('artf.nom')) }}</div>
                @if($direction)
                    <div class="charte-direction">{{ $maj($direction) }}</div>
                @endif
            </td>
            <td class="charte-droite">
                <div class="charte-etat">
                    <span class="charte-pays">{{ $maj(config('artf.pays')) }}</span><br>
                    {{ config('artf.devise') }}
                </div>
                <div class="charte-lieu">
                    {{ config('artf.ville') }}, le {{ now()->locale('fr')->isoFormat('D MMMM YYYY') }}
                </div>
            </td>
        </tr>
    </table>
@else
    <table class="charte-entete compact">
        <tr>
            <td class="charte-logo">
                <img class="charte-logo-h" src="{{ resource_path('pdf/img/logo-horizontal.png') }}" alt="{{ config('artf.nom') }}">
            </td>
            <td class="charte-droite">
                <div class="charte-etat">
                    <span class="charte-pays">{{ $maj(config('artf.pays')) }}</span><br>
                    {{ config('artf.devise') }}
                </div>
            </td>
        </tr>
    </table>
    <table class="charte-barre" style="margin-bottom: 12px;"><tr><td class="b"></td><td class="r"></td></tr></table>
@endif
