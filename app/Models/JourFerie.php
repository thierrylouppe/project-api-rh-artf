<?php

namespace App\Models;

use App\Traits\HasFilterScope;
use Illuminate\Database\Eloquent\Model;

class JourFerie extends Model
{
    use HasFilterScope;

    protected $table = 'jour_feries';

    protected $fillable = ['nom', 'date', 'recurrent'];

    protected $casts = [
        'date'      => 'date',
        'recurrent' => 'boolean',
    ];

    protected array $filterable = ['recurrent'];
}
