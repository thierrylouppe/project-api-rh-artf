<?php

namespace App\Models;

use App\Traits\HasFilterScope;
use Illuminate\Database\Eloquent\Model;

class TypeIntegration extends Model
{
    use HasFilterScope;

    protected $table = 'type_integrations';

    protected $fillable = ['nom', 'description'];

    protected array $filterable = ['nom'];
}
