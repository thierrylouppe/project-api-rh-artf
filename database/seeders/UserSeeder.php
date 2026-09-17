<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $users = [
            ['email' => 'admin@artf.cg',        'name' => 'Administrateur ARFT', 'password' => 'Admin@2026',       'role' => 'admin'],
            ['email' => 'rh@artf.cg',           'name' => 'Responsable RH',      'password' => 'Rh@2026',          'role' => 'rh'],
            ['email' => 'dg@artf.cg',           'name' => 'Directeur Général',   'password' => 'Dg@2026',          'role' => 'directeur-general'],
            ['email' => 'directeur@artf.cg',    'name' => 'Directeur',           'password' => 'Directeur@2026',   'role' => 'directeur'],
            ['email' => 'chef-service@artf.cg', 'name' => 'Chef de service',     'password' => 'ChefService@2026', 'role' => 'chef-service'],
            ['email' => 'chef-bureau@artf.cg',  'name' => 'Chef de bureau',      'password' => 'ChefBureau@2026',  'role' => 'chef-bureau'],
            ['email' => 'agent@artf.cg',        'name' => 'Agent',               'password' => 'Agent@2026',       'role' => 'agent'],
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
