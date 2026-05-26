<?php

namespace App\Models;

use App\Traits\HasFilterScope;
use Illuminate\Database\Eloquent\Model;

class MotifAdministratif extends Model
{
    use HasFilterScope;

    protected $table = 'motifs_administratifs';

    protected $fillable = ['nom', 'description'];

    protected array $filterable = ['nom'];
}
