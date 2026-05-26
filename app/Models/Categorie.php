<?php

namespace App\Models;

use App\Traits\HasFilterScope;
use Illuminate\Database\Eloquent\Model;

class Categorie extends Model
{
    use HasFilterScope;

    protected $fillable = ['nom', 'sigle', 'description'];

    protected array $filterable = ['nom'];
}
