<?php

namespace App\Repositories;

use App\Interfaces\TypeDocumentInterface;
use App\Models\TypeDocument;

class TypeDocumentRepository extends BaseRepository implements TypeDocumentInterface
{
    protected function model(): string
    {
        return TypeDocument::class;
    }
}
