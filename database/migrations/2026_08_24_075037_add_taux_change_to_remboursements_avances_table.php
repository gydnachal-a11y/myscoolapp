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
        // ✅ Vérifier que la colonne n'existe pas déjà
        if (Schema::hasColumn('remboursements_avances', 'taux_change')) {
            return;
        }

        Schema::table('remboursements_avances', function (Blueprint $table) {
            $table->decimal('taux_change', 15, 2)
                  ->nullable()
                  ->after('montant_rembourse_fc')
                  ->comment('Taux USD/CDF utilisé lors du remboursement');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // ✅ Vérifier que la colonne existe avant de la supprimer
        if (! Schema::hasColumn('remboursements_avances', 'taux_change')) {
            return;
        }

        Schema::table('remboursements_avances', function (Blueprint $table) {
            $table->dropColumn('taux_change');
        });
    }
};