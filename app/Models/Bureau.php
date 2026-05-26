<?php

namespace App\Models;

use App\Traits\HasAutoSigle;
use App\Traits\HasFilterScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Bureau extends Model
{
    use HasAutoSigle, HasFilterScope;

    protected $table = 'bureaus';

    protected $fillable = ['nom', 'sigle', 'description', 'service_id'];

    protected array $filterable = ['nom', 'service_id'];

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }
}
