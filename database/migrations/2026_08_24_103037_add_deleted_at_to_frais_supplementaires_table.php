<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Exécute la migration.
     */
    public function up(): void
    {
        Schema::table('frais_supplementaires', function (Blueprint $table) {
            // Ajout de la colonne pour la suppression douce (SoftDeletes)
            $table->softDeletes();
        });
    }

    /**
     * Annule la migration.
     */
    public function down(): void
    {
        Schema::table('frais_supplementaires', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
    }
};