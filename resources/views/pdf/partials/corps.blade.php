{{--
    Mise en forme du corps des documents PDF, commune à tous les modèles.
    Incluse par pdf.partials.charte : elle surcharge (même spécificité, déclarée après)
    les classes partagées par les modèles — .header, .objet, .bloc, .ligne, table.info,
    tableaux de données, signatures, mentions — sans toucher à leur HTML.
--}}
@php
    $bleu = config('artf.couleurs.bleu');
    $rouge = config('artf.couleurs.rouge');
    $encre = '#1f2a37';
    $gris = '#6b7684';
    $trait = '#e3e8ef';
    $fond = '#f5f8fb';
@endphp
<style>
    body { color: {{ $encre }}; }

    /* ---- Titre du document ---- */
    .header { border-bottom: 1px solid {{ $trait }}; padding-bottom: 14px; margin-bottom: 22px; }
    .header .titre { font-size: 17px; letter-spacing: 1.5px; margin-top: 6px; }
    .header .sous-titre { font-size: 10px; font-weight: bold; color: {{ $rouge }}; text-transform: uppercase; letter-spacing: 2px; margin-top: 4px; }
    .header .reference, .header .ref { display: inline-block; margin-top: 8px; padding: 3px 12px; background: {{ $fond }}; border: 1px solid {{ $trait }}; border-radius: 10px; font-size: 9px; color: {{ $gris }}; }

    /* ---- Objet et texte courant ---- */
    .objet { background: {{ $fond }}; border-left: 3px solid {{ $bleu }}; padding: 10px 14px; margin-bottom: 18px; line-height: 1.5; }
    .objet strong { color: {{ $bleu }}; }
    .corps, .intro { text-align: justify; line-height: 1.75; }
    .signataire, .corps .signataire, .intro .signataire { text-decoration: none; color: {{ $bleu }}; }

    /* ---- Sections ---- */
    h2 { font-size: 10.5px; font-weight: bold; color: {{ $bleu }}; text-transform: uppercase; letter-spacing: 1px; border-left: 3px solid {{ $rouge }}; padding: 1px 0 1px 8px; margin: 20px 0 10px 0; }
    .bloc { background: #fff; border: 1px solid {{ $trait }}; border-radius: 4px; padding: 12px 16px 8px 16px; margin-bottom: 14px; }
    .bloc-titre { color: {{ $bleu }}; font-size: 9.5px; letter-spacing: 1px; border-bottom: none; border-left: 3px solid {{ $rouge }}; padding: 1px 0 1px 8px; margin-bottom: 8px; }

    /* ---- Couples libellé / valeur ---- */
    .ligne { border-bottom: 1px solid {{ $trait }}; padding: 5px 0; margin-bottom: 0; }
    .ligne .label { font-weight: normal; color: {{ $gris }}; }
    .ligne .valeur { color: {{ $encre }}; font-weight: bold; }

    /* Valeurs par défaut de faible spécificité : les largeurs définies par un modèle restent prioritaires. */
    .info { width: 100%; border-collapse: collapse; }
    .info .label { width: 32%; }
    table.info { border: 1px solid {{ $trait }}; margin-bottom: 18px; }
    table.info td { padding: 7px 12px; border-bottom: 1px solid {{ $trait }}; vertical-align: top; }
    table.info td.label { background: {{ $fond }}; font-weight: normal; color: {{ $gris }}; border-right: 1px solid {{ $trait }}; }
    .bloc table.info { border: none; margin-bottom: 0; }
    .bloc table.info td { padding: 6px 0; }
    .bloc table.info td.label { background: none; border-right: none; }

    /* Tableaux sans classe dont la 1re colonne est un libellé (fiche d'évaluation) */
    td.label { color: {{ $gris }}; font-weight: normal; background: {{ $fond }}; }
    td.mention { font-weight: bold; color: {{ $bleu }}; }

    /* ---- Tableaux de données ---- */
    th, td { border-color: {{ $trait }}; }
    th, table.montant th { background: {{ $bleu }}; color: #fff; font-size: 9px; font-weight: bold; text-transform: uppercase; letter-spacing: 0.4px; border-color: {{ $bleu }}; }
    tbody tr:nth-child(even) td { background: {{ $fond }}; }
    /* Pas de zébrage sur les couples libellé / valeur : seule la colonne libellé est teintée. */
    table.info tr:nth-child(even) td { background: none; }
    table.info tr td.label { background: {{ $fond }}; }
    .bloc table.info tr td.label { background: none; }
    table.montant tr.total td { background: #eaf0f7; color: {{ $bleu }}; }
    table.montant tr.net td { background: {{ $bleu }}; color: #fff; font-size: 11px; }

    /* ---- Badges ---- */
    .statut-badge { background: #eaf0f7; color: {{ $bleu }}; border: 1px solid #c9d6e6; border-radius: 10px; padding: 2px 10px; }

    /* ---- Signatures ---- */
    .signatures { margin-top: 34px; }
    .sign-bloc .sign-titre { color: {{ $bleu }}; letter-spacing: 0.5px; }
    .sign-bloc .sign-espace { height: 64px; border-bottom: 1px solid #b8c2cf; }
    .sign-bloc .sign-nom { color: {{ $gris }}; font-size: 9.5px; }
    .sig { margin-top: 30px; text-align: right; page-break-inside: avoid; }
    .sig p { margin: 0; }
    .sig p:first-child { font-weight: bold; color: {{ $bleu }}; text-transform: uppercase; font-size: 10px; letter-spacing: 0.5px; padding-bottom: 60px; }

    /* ---- Mentions de bas de document (p.footer : texte réglementaire, pas le pied fixe) ---- */
    p.footer, .note { font-size: 9px; color: {{ $gris }}; font-style: italic; }
    p.footer { margin-top: 28px; padding-top: 8px; border-top: 1px solid {{ $trait }}; }
</style>
