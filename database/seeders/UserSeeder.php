<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::firstOrCreate(
            ['email' => 'admin@arft.cg'],
            [
                'name' => 'Administrateur ARFT',
                'password' => 'Admin@2026',
                'is_active' => true,
            ]
        );

        $admin->syncRoles(['admin']);

        $rh = User::firstOrCreate(
            ['email' => 'rh@arft.cg'],
            [
                'name' => 'Responsable RH',
                'password' => 'Rh@2026',
                'is_active' => true,
            ]
        );

        $rh->syncRoles(['rh']);
    }
}
