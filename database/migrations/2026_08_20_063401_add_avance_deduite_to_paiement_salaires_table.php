<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('paiement_salaires', function (Blueprint $table) {
            $table->decimal('avance_deduite_usd', 10, 2)->default(0);
            $table->decimal('avance_deduite_fc', 15, 2)->default(0);
        });
    }

    public function down(): void
    {
        Schema::table('paiement_salaires', function (Blueprint $table) {
            $table->dropColumn(['avance_deduite_usd', 'avance_deduite_fc']);
        });
    }
};