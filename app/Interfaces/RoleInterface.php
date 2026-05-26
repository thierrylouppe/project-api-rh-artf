<?php

namespace App\Interfaces;

use Illuminate\Support\Collection;
use Spatie\Permission\Models\Role;

interface RoleInterface extends BaseInterface
{
    public function getAllWithPermissions(): Collection;

    public function dupliquer(int $id): Role;
}
