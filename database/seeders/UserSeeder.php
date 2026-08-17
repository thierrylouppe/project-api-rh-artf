<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $users = [
            ['email' => 'admin@arft.cg',        'name' => 'Administrateur ARFT', 'password' => 'Admin@2026',       'role' => 'admin'],
            ['email' => 'rh@arft.cg',           'name' => 'Responsable RH',      'password' => 'Rh@2026',          'role' => 'rh'],
            ['email' => 'dg@arft.cg',           'name' => 'Directeur Général',   'password' => 'Dg@2026',          'role' => 'directeur-general'],
            ['email' => 'directeur@arft.cg',    'name' => 'Directeur',           'password' => 'Directeur@2026',   'role' => 'directeur'],
            ['email' => 'chef-service@arft.cg', 'name' => 'Chef de service',     'password' => 'ChefService@2026', 'role' => 'chef-service'],
            ['email' => 'chef-bureau@arft.cg',  'name' => 'Chef de bureau',      'password' => 'ChefBureau@2026',  'role' => 'chef-bureau'],
            ['email' => 'agent@arft.cg',        'name' => 'Agent',               'password' => 'Agent@2026',       'role' => 'agent'],
        ];

        foreach ($users as $data) {
            $user = User::firstOrCreate(
                ['email' => $data['email']],
                [
                    'name' => $data['name'],
                    'password' => $data['password'],
                    'is_active' => true,
                ]
            );

            $user->syncRoles([$data['role']]);
        }
    }
}
