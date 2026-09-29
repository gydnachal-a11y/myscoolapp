<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sessions_paiement', function (Blueprint $table) {
            $table->id();
            $table->foreignId('salle_classe_id')->constrained('salles_de_classe')->cascadeOnDelete();
            $table->string('type_periode', 50); // limité à 50 caractères
            $table->string('periode', 50);      // limité à 50 caractères
            $table->timestamps();

            $table->unique(
                ['salle_classe_id', 'type_periode', 'periode'],
                'sessions_paiement_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sessions_paiement');
    }
};