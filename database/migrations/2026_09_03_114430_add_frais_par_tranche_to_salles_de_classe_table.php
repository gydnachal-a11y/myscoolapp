<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('salles_de_classe', function (Blueprint $table) {
            $table->decimal('frais_par_tranche', 10, 2)->nullable()->after('frais_scolarite_mensuel');
        });
    }

    public function down(): void
    {
        Schema::table('salles_de_classe', function (Blueprint $table) {
            $table->dropColumn('frais_par_tranche');
        });
    }
};