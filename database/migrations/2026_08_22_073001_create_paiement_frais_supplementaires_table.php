<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('paiement_frais_supplementaires', function (Blueprint $table) {
            $table->id();
            $table->foreignId('eleve_id')->constrained('eleves')->cascadeOnDelete();
            $table->foreignId('frais_supplementaire_id')->constrained('frais_supplementaires')->cascadeOnDelete();
            $table->decimal('montant_paye_usd', 10, 2);
            $table->decimal('montant_paye_fc', 10, 2);
            $table->date('date_paiement');
            $table->text('commentaire')->nullable();
            $table->timestamps();

            // Un seul paiement par élève et par frais
            $table->unique(['eleve_id', 'frais_supplementaire_id'], 'paiement_frais_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('paiement_frais_supplementaires');
    }
};