<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * CCN ARTF — art. 79 (mise en disponibilité) et art. 80 (sous le drapeau).
 *
 * 1. Étend l'ENUM statut des agents avec 'disponibilite' et 'sous_le_drapeau'.
 * 2. Retire "Mise en disponibilité" de type_absences (c'est une position
 *    administrative, pas un type d'absence).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::getConnection()->getDriverName() !== 'mysql') {
            return;
        }

        // 1. Étendre l'ENUM agents.statut
        DB::statement("
            ALTER TABLE agents
            MODIFY COLUMN statut ENUM(
                'actif',
                'inactif',
                'suspendu',
                'retraite',
                'stagiaire',
                'archive',
                'detachement',
                'position_exceptionnelle',
                'disponibilite',
                'sous_le_drapeau'
            ) NOT NULL DEFAULT 'actif'
        ");

        // 2. Supprimer "Mise en disponibilité" de type_absences si aucune
        //    absence ne la référence (sécurité avant suppression).
        $id = DB::table('type_absences')->where('nom', 'Mise en disponibilité')->value('id');

        if ($id !== null) {
            $utilise = DB::table('absences')->where('type_absence_id', $id)->exists();

            if (! $utilise) {
                DB::table('type_absences')->where('id', $id)->delete();
            } else {
                // Des absences historiques la référencent : on renomme pour
                // indiquer le statut correct sans casser les FK existantes.
                DB::table('type_absences')
                    ->where('id', $id)
                    ->update(['nom' => '[Obsolète] Mise en disponibilité — voir StatutAgent']);
            }
        }
    }

    public function down(): void
    {
        if (Schema::getConnection()->getDriverName() !== 'mysql') {
            return;
        }

        // Rétablir l'ancien ENUM (sans les nouvelles valeurs)
        DB::statement("UPDATE agents SET statut = 'inactif' WHERE statut IN ('disponibilite', 'sous_le_drapeau')");

        DB::statement("
            ALTER TABLE agents
            MODIFY COLUMN statut ENUM(
                'actif',
                'inactif',
                'suspendu',
                'retraite',
                'stagiaire',
                'archive',
                'detachement',
                'position_exceptionnelle'
            ) NOT NULL DEFAULT 'actif'
        ");
    }
};
