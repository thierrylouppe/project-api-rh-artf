<?php

/*
|--------------------------------------------------------------------------
| Identité institutionnelle de l'ARTF
|--------------------------------------------------------------------------
|
| Source unique des mentions officielles reprises dans les documents PDF
| (en-tête, pied de page). Voir resources/views/pdf/partials/charte.blade.php.
|
*/

return [

    'nom' => 'Agence de Régulation des Transferts de Fonds',
    'sigle' => 'ARTF',
    'tutelle' => "Ministère de l'Économie et des Finances",

    'pays' => 'République du Congo',
    'devise' => 'Unité * Travail * Progrès',
    'ville' => 'Brazzaville',

    // Direction émettrice par défaut des documents du SIRH.
    'direction' => 'Direction des Ressources Humaines et de la Logistique',

    'adresse' => 'Sis N° 70, avenue Nelson MANDELA, BP : 93 Brazzaville',
    'telephone' => '(+242) 06 950 69 69',
    'email' => 'contact@artf.cg',
    'site' => 'www.artf.cg',

    'couleurs' => [
        'bleu' => '#035191',
        'rouge' => '#ed2642',
    ],

];
