<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Parametregrille extends Model
{
    protected $fillable = [
        'valeur_point_indice',
        'indice_base',
        'echelon_depart',
        'echelon_fin',
        'ecart_depart',
    ];

    protected $casts = [
        'valeur_point_indice' => 'float',
    ];
}
