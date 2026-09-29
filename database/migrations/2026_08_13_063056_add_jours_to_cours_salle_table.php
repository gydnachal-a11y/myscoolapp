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
        Schema::table('cours_salle', function (Blueprint $table) {
            // Ajoute la colonne jours (nullable, string)
            // Elle stockera les jours séparés par des virgules, ex: "Lundi,Mercredi,Vendredi"
            $table->string('jours')->nullable()->after('titulaire_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cours_salle', function (Blueprint $table) {
            $table->dropColumn('jours');
        });
    }
};