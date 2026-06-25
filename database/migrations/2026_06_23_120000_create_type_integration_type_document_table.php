<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('type_integration_type_document', function (Blueprint $table) {
            $table->foreignId('type_integration_id')
                  ->constrained('type_integrations')
                  ->cascadeOnDelete();
            $table->foreignId('type_document_id')
                  ->constrained('type_documents')
                  ->cascadeOnDelete();
            $table->primary(['type_integration_id', 'type_document_id']);
        });

        Schema::table('type_integrations', function (Blueprint $table) {
            $table->dropColumn('documents_obligatoires');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('type_integration_type_document');

        Schema::table('type_integrations', function (Blueprint $table) {
            $table->json('documents_obligatoires')->nullable();
        });
    }
};
