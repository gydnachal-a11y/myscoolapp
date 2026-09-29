<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('avance_salaires', function (Blueprint $table) {
            $table->decimal('montant_rembourse_usd', 10, 2)->default(0)->after('montant_avance_fc');
            $table->decimal('montant_rembourse_fc', 15, 2)->default(0)->after('montant_rembourse_usd');
            $table->decimal('dette_restante_usd', 10, 2)->default(0)->after('montant_rembourse_fc');
            $table->decimal('dette_restante_fc', 15, 2)->default(0)->after('dette_restante_usd');
        });
    }

    public function down(): void
    {
        Schema::table('avance_salaires', function (Blueprint $table) {
            $table->dropColumn([
                'montant_rembourse_usd',
                'montant_rembourse_fc',
                'dette_restante_usd',
                'dette_restante_fc',
            ]);
        });
    }
};