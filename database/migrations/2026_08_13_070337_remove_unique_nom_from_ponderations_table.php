<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ponderations', function (Blueprint $table) {
            // Supprime l'index unique s'il existe
            $table->dropUnique('ponderations_nom_unique');
            // Change la colonne en string sans unique
            $table->string('nom')->change();
        });
    }

    public function down(): void
    {
        Schema::table('ponderations', function (Blueprint $table) {
            $table->string('nom')->unique()->change();
        });
    }
};