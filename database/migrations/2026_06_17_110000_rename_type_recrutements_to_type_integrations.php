<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::rename('type_recrutements', 'type_integrations');
    }

    public function down(): void
    {
        Schema::rename('type_integrations', 'type_recrutements');
    }
};
