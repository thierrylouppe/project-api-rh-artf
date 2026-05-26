<?php

namespace App\Models;

use App\Traits\HasFilterScope;
use Illuminate\Database\Eloquent\Model;

class Grade extends Model
{
    use HasFilterScope;

    protected $fillable = ['nom', 'sigle', 'description', 'niveau'];

    protected array $filterable = ['nom', 'niveau'];
}
