<?php

namespace App\Models;

use App\Traits\HasFilterScope;
use Illuminate\Database\Eloquent\Model;

class Echelon extends Model
{
    use HasFilterScope;

    protected $fillable = ['nom', 'numero', 'description'];

    protected array $filterable = ['nom', 'numero'];
}
