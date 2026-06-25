<?php

namespace App\Models;

use App\Traits\HasFilterScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class TypeDocument extends Model
{
    use HasFilterScope;

    protected $table = 'type_documents';

    protected $fillable = ['nom', 'description', 'obligatoire'];

    protected $casts = ['obligatoire' => 'boolean'];

    protected array $filterable = ['nom', 'obligatoire'];

    public function typesIntegrations(): BelongsToMany
    {
        return $this->belongsToMany(
            TypeIntegration::class,
            'type_integration_type_document',
            'type_document_id',
            'type_integration_id'
        );
    }
}
