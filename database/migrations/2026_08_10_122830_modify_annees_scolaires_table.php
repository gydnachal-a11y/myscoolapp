<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('annees_scolaires', function (Blueprint $table) {
            $table->dropColumn(['date_debut', 'date_fin']);
            $table->integer('annee_debut')->after('libelle');
            $table->integer('annee_fin')->after('annee_debut');
        });
    }

    public function down(): void
    {
        Schema::table('annees_scolaires', function (Blueprint $table) {
            $table->dropColumn(['annee_debut', 'annee_fin']);
            $table->date('date_debut')->after('libelle');
            $table->date('date_fin')->after('date_debut');
        });
    }
};