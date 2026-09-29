<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('eleve_id')->constrained('eleves')->cascadeOnDelete();
            $table->foreignId('cour_salle_id')->constrained('cours_salle')->cascadeOnDelete();
            $table->foreignId('periode_note_id')->constrained('periode_notes')->cascadeOnDelete();
            $table->decimal('note', 5, 2)->nullable();
            $table->text('appreciation')->nullable();
            $table->foreignId('saisie_par')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('statut', ['brouillon', 'publie'])->default('brouillon');
            $table->timestamps();

            $table->unique(['eleve_id', 'cour_salle_id', 'periode_note_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('notes');
    }
};