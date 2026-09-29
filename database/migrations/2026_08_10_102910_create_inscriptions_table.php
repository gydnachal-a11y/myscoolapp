<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('eleve_id')->constrained('eleves')->onDelete('cascade');
            $table->foreignId('annee_scolaire_id')->constrained('annees_scolaires')->onDelete('cascade');
            $table->foreignId('salle_classe_id')->constrained('salles_de_classe')->onDelete('cascade');
            $table->date('date_inscription');
            $table->decimal('reduction_frais', 10, 2)->default(0);
            $table->decimal('frais_inscription_final', 10, 2);
            $table->decimal('frais_annuel_final', 10, 2);
            $table->timestamps();

            $table->unique(['eleve_id', 'annee_scolaire_id']); // un élève ne peut être inscrit qu'une fois par année
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inscriptions');
    }
};