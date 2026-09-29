<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('frais_supplementaire_salle', function (Blueprint $table) {
            $table->id();
            $table->foreignId('frais_supplementaire_id')
                  ->constrained('frais_supplementaires')
                  ->cascadeOnDelete();
            $table->foreignId('salle_classe_id')
                  ->constrained('salles_de_classe')
                  ->cascadeOnDelete();
            $table->unique(['frais_supplementaire_id', 'salle_classe_id'], 'frais_supp_salle_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('frais_supplementaire_salle');
    }
};