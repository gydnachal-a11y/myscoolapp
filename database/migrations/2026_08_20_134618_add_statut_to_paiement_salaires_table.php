<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('paiement_salaires', function (Blueprint $table) {
            $table->string('statut', 20)->default('paye')->after('est_paye');
        });
    }

    public function down(): void
    {
        Schema::table('paiement_salaires', function (Blueprint $table) {
            $table->dropColumn('statut');
        });
    }
};