<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cours_salle', function (Blueprint $table) {
            // Supprimer les anciennes colonnes
            $table->dropColumn(['libelle', 'nombre_heures', 'ponderation']);

            // Ajouter les clés étrangères
            $table->foreignId('libelle_id')->nullable()->constrained('libelles')->nullOnDelete();
            $table->foreignId('ponderation_id')->nullable()->constrained('ponderations')->nullOnDelete();
            $table->foreignId('nombre_heure_id')->nullable()->constrained('nombre_heures')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('cours_salle', function (Blueprint $table) {
            $table->dropForeign(['libelle_id', 'ponderation_id', 'nombre_heure_id']);
            $table->dropColumn(['libelle_id', 'ponderation_id', 'nombre_heure_id']);

            // Recréer les anciennes colonnes
            $table->enum('libelle', ['base', 'elementaire'])->default('base');
            $table->unsignedSmallInteger('nombre_heures')->default(1);
            $table->decimal('ponderation', 5, 2)->default(1.0);
        });
    }
};