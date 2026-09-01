<?php

namespace App\Models;

use App\Traits\HasFilterScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class DocumentAgent extends Model
{
    use HasFilterScope;
    use SoftDeletes;

    protected $table = 'documents_agents';

    protected $fillable = [
        'agent_id',
        'type_document_id',
        'titre',
        'sous_dossier',
        'chemin_fichier',
        'nom_original',
        'taille',
        'mime_type',
    ];

    protected $casts = [
        'taille' => 'integer',
    ];

    protected array $filterable = ['agent_id', 'type_document_id', 'sous_dossier'];

    public function agent(): BelongsTo
    {
        return $this->belongsTo(Agent::class);
    }

    public function typeDocument(): BelongsTo
    {
        return $this->belongsTo(TypeDocument::class);
    }
}
