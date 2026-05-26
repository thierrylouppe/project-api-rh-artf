<?php

namespace App\Models;

use App\Traits\HasFilterScope;
use Illuminate\Database\Eloquent\Model;

class TypeDocument extends Model
{
    use HasFilterScope;

    protected $table = 'type_documents';

    protected $fillable = ['nom', 'description', 'obligatoire'];

    protected $casts = ['obligatoire' => 'boolean'];

    protected array $filterable = ['nom', 'obligatoire'];
}
