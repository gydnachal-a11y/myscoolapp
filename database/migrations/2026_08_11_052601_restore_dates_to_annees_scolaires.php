<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('annees_scolaires', function (Blueprint $table) {
            // Supprimer les colonnes entières si elles existent
            if (Schema::hasColumn('annees_scolaires', 'annee_debut')) {
                $table->dropColumn(['annee_debut', 'annee_fin']);
            }
            // Ajouter les colonnes de date
            $table->date('date_debut')->nullable();
            $table->date('date_fin')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('annees_scolaires', function (Blueprint $table) {
            $table->dropColumn(['date_debut', 'date_fin']);
            $table->integer('annee_debut')->nullable();
            $table->integer('annee_fin')->nullable();
        });
    }
};