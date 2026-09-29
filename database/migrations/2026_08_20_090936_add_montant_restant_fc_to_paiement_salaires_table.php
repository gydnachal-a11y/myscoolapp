<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('paiement_salaires', function (Blueprint $table) {
            $table->decimal('montant_restant_fc', 15, 2)->default(0)->after('montant_restant_usd');
        });
    }

    public function down(): void
    {
        Schema::table('paiement_salaires', function (Blueprint $table) {
            $table->dropColumn('montant_restant_fc');
        });
    }
};