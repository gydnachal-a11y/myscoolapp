<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
   public function up(): void
{
    if (!Schema::hasColumn('paiement_salaires', 'motif_ecart_type')) {
        Schema::table('paiement_salaires', function (Blueprint $table) {
            $table->string('motif_ecart_type', 50)
                  ->nullable()
                  ->after('motif_ecart');
        });
    }
}

public function down(): void
{
    if (Schema::hasColumn('paiement_salaires', 'motif_ecart_type')) {
        Schema::table('paiement_salaires', function (Blueprint $table) {
            $table->dropColumn('motif_ecart_type');
        });
    }
}
};
