<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('paiement_salaires', function (Blueprint $table) {
            // Ajout des colonnes pour le remboursement de dette et le report
            $table->decimal('dette_remboursee_usd', 15, 2)->default(0)->after('avance_deduite_fc');
            $table->decimal('dette_remboursee_fc', 15, 2)->default(0)->after('dette_remboursee_usd');
            $table->decimal('report_dette_usd', 15, 2)->default(0)->after('dette_remboursee_fc');
            $table->decimal('report_dette_fc', 15, 2)->default(0)->after('report_dette_usd');
        });
    }

    public function down()
    {
        Schema::table('paiement_salaires', function (Blueprint $table) {
            $table->dropColumn([
                'dette_remboursee_usd',
                'dette_remboursee_fc',
                'report_dette_usd',
                'report_dette_fc',
            ]);
        });
    }
};