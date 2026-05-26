<?php

namespace App\Models;

use App\Traits\HasFilterScope;
use Illuminate\Database\Eloquent\Model;

class TypeRecrutement extends Model
{
    use HasFilterScope;

    protected $table = 'type_recrutements';

    protected $fillable = ['nom', 'description'];

    protected array $filterable = ['nom'];
}
