<?php

namespace App\Models;

use App\Traits\HasFilterScope;
use Illuminate\Database\Eloquent\Model;

class TypeConge extends Model
{
    use HasFilterScope;

    protected $table = 'type_conges';

    protected $fillable = ['nom', 'description', 'jours_max'];

    protected array $filterable = ['nom'];
}
