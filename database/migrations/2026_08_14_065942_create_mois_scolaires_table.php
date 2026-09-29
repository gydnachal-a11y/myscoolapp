<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mois_scolaires', function (Blueprint $table) {
            $table->id();
            $table->foreignId('annee_scolaire_id')->constrained('annees_scolaires')->cascadeOnDelete();
            $table->string('mois'); // Format 'Y-m' ex: '2026-09'
            $table->date('date_debut');
            $table->date('date_fin');
            $table->timestamps();

            $table->unique(['annee_scolaire_id', 'mois']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mois_scolaires');
    }
};