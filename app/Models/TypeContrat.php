<?php

namespace App\Models;

use App\Traits\HasFilterScope;
use Illuminate\Database\Eloquent\Model;

class TypeContrat extends Model
{
    use HasFilterScope;

    protected $table = 'type_contrats';

    protected $fillable = ['nom', 'sigle', 'description'];

    protected array $filterable = ['nom'];
}
