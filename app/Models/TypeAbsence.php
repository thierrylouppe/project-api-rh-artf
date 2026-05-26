<?php

namespace App\Models;

use App\Traits\HasFilterScope;
use Illuminate\Database\Eloquent\Model;

class TypeAbsence extends Model
{
    use HasFilterScope;

    protected $table = 'type_absences';

    protected $fillable = ['nom', 'description', 'justification_requise'];

    protected $casts = ['justification_requise' => 'boolean'];

    protected array $filterable = ['nom', 'justification_requise'];
}
