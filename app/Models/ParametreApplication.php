<?php

namespace App\Models;

use App\Traits\HasFilterScope;
use Illuminate\Database\Eloquent\Model;

class ParametreApplication extends Model
{
    use HasFilterScope;

    protected $table = 'parametres_application';

    protected $fillable = ['cle', 'valeur', 'description'];

    protected array $filterable = ['cle'];
}
