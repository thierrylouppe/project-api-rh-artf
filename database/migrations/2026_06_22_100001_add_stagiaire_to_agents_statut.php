<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // SQLite / fresh installs : la valeur est déjà dans create_integration_administrative_tables
        if (Schema::getConnection()->getDriverName() !== 'mysql') {
            return;
        }

        DB::statement("ALTER TABLE agents MODIFY COLUMN statut ENUM('actif','inactif','suspendu','retraite','stagiaire') NOT NULL DEFAULT 'actif'");
    }

    public function down(): void
    {
        if (Schema::getConnection()->getDriverName() !== 'mysql') {
            return;
        }

        DB::statement("UPDATE agents SET statut = 'inactif' WHERE statut = 'stagiaire'");
        DB::statement("ALTER TABLE agents MODIFY COLUMN statut ENUM('actif','inactif','suspendu','retraite') NOT NULL DEFAULT 'actif'");
    }
};
