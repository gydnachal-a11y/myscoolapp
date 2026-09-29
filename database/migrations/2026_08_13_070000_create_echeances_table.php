<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('echeances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('eleve_id')->constrained('eleves')->cascadeOnDelete();
            $table->foreignId('annee_scolaire_id')->constrained('annees_scolaires')->cascadeOnDelete();
            $table->foreignId('salle_classe_id')->constrained('salles_de_classe')->cascadeOnDelete();
            $table->string('type_periode', 50);
            $table->string('periode', 50);
            $table->decimal('montant_usd', 10, 2)->default(0);
            $table->decimal('montant_fc', 12, 2)->default(0);
            $table->boolean('est_paye')->default(false);
            $table->date('date_echeance')->nullable();
            $table->timestamps();

            // Index unique avec colonnes de taille raisonnable
            $table->unique(
                ['eleve_id', 'annee_scolaire_id', 'salle_classe_id', 'type_periode', 'periode'],
                'echeances_unique_par_eleve_periode'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('echeances');
    }
};