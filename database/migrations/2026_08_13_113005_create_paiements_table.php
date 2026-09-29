<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('paiements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('eleve_id')->constrained('eleves')->cascadeOnDelete();
            $table->foreignId('annee_scolaire_id')->constrained('annees_scolaires')->cascadeOnDelete();
            $table->foreignId('salle_classe_id')->constrained('salles_de_classe')->cascadeOnDelete();
            $table->string('type_periode'); // 'mensuel' ou 'tranche'
            $table->string('periode');      // ex: '2026-09' ou 'Tranche 1'
            $table->decimal('montant_attendu_usd', 10, 2)->default(0);
            $table->decimal('montant_attendu_fc', 12, 2)->default(0);
            $table->decimal('montant_paye_usd', 10, 2)->default(0);
            $table->decimal('montant_paye_fc', 12, 2)->default(0);
            $table->decimal('montant_restant_usd', 10, 2)->default(0);
            $table->decimal('montant_restant_fc', 12, 2)->default(0);
            $table->date('date_paiement');
            $table->text('commentaire')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('paiements');
    }
};