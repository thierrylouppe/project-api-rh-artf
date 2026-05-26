<?php

namespace App\Interfaces;

use App\Models\User;

interface UserInterface extends BaseInterface
{
    public function findByEmail(string $email): ?User;
}
