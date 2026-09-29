<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('cours_salle', function (Blueprint $table) {
            $table->foreignId('creneau_horaire_id')
                  ->nullable()
                  ->constrained('creneaux_horaires')
                  ->nullOnDelete();
            // Optionnel : supprimer l'ancien champ duree
            // $table->dropColumn('duree');
        });
    }

    public function down()
    {
        Schema::table('cours_salle', function (Blueprint $table) {
            $table->dropConstrainedForeignId('creneau_horaire_id');
            // $table->string('duree')->nullable();
        });
    }
};