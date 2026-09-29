<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('cours_salle', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cours_id')->constrained('cours')->cascadeOnDelete();
            $table->foreignId('salle_classe_id')->constrained('salles_de_classe')->cascadeOnDelete();
            $table->enum('libelle', ['base', 'elementaire'])->default('base');
            $table->unsignedSmallInteger('nombre_heures')->default(1);
            $table->decimal('ponderation', 5, 2)->default(1.0);
            $table->foreignId('titulaire_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            // Un cours ne peut être assigné qu'une seule fois par salle (mais on peut changer libellé)
            $table->unique(['cours_id', 'salle_classe_id']);
        });
            }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cours_salle');
    }
};
