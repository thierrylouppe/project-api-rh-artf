<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Positions exemptées de notation — convention collective ARTF, art. 65. */
    public function up(): void
    {
        if (Schema::getConnection()->getDriverName() !== 'mysql') {
            return;
        }

        DB::statement("ALTER TABLE agents MODIFY COLUMN statut ENUM('actif','inactif','suspendu','retraite','stagiaire','archive','detachement','position_exceptionnelle') NOT NULL DEFAULT 'actif'");
    }

    public function down(): void
    {
        if (Schema::getConnection()->getDriverName() !== 'mysql') {
            return;
        }

        DB::statement("UPDATE agents SET statut = 'inactif' WHERE statut IN ('detachement','position_exceptionnelle')");
        DB::statement("ALTER TABLE agents MODIFY COLUMN statut ENUM('actif','inactif','suspendu','retraite','stagiaire','archive') NOT NULL DEFAULT 'actif'");
    }
};
