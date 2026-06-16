<?php

namespace App\Interfaces;

use Illuminate\Database\Eloquent\Model;

interface ParametregrileInterface extends BaseInterface
{
    /** Retourne l'unique ligne de paramètres, en la créant avec les valeurs par défaut si absente. */
    public function getCurrent(): Model;
}
