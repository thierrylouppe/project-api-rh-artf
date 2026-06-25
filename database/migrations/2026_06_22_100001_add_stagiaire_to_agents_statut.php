<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE agents MODIFY COLUMN statut ENUM('actif','inactif','suspendu','retraite','stagiaire') NOT NULL DEFAULT 'actif'");
    }

    public function down(): void
    {
        DB::statement("UPDATE agents SET statut = 'inactif' WHERE statut = 'stagiaire'");
        DB::statement("ALTER TABLE agents MODIFY COLUMN statut ENUM('actif','inactif','suspendu','retraite') NOT NULL DEFAULT 'actif'");
    }
};
