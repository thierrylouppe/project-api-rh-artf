<?php

namespace App\Services;

use App\Interfaces\TypeDocumentInterface;

class TypeDocumentService extends BaseService
{
    public function __construct(TypeDocumentInterface $repository)
    {
        parent::__construct($repository);
    }
}
