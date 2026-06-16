<?php

namespace App\Repositories;

use App\Interfaces\ParametregrileInterface;
use App\Models\Parametregrille;
use Illuminate\Database\Eloquent\Model;

class ParametregrileRepository extends BaseRepository implements ParametregrileInterface
{
    protected function model(): string
    {
        return Parametregrille::class;
    }

    public function getCurrent(): Model
    {
        return Parametregrille::firstOrCreate([], [
            'valeur_point_indice' => 300,
            'indice_base'         => 445,
            'echelon_depart'      => 1,
            'echelon_fin'         => 12,
            'ecart_depart'        => 45,
        ]);
    }
}
