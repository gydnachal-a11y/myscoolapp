<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('annees_scolaires', function (Blueprint $table) {
            $table->unsignedTinyInteger('nombre_mois')->nullable()->after('paiement_ouvert');
            $table->unsignedTinyInteger('nombre_tranches')->nullable()->after('nombre_mois');
        });
    }

    public function down(): void
    {
        Schema::table('annees_scolaires', function (Blueprint $table) {
            $table->dropColumn(['nombre_mois', 'nombre_tranches']);
        });
    }
};