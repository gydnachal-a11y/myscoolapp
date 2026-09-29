<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('paiements', 'statut')) {
            Schema::table('paiements', function (Blueprint $table) {
                $table->enum('statut', ['paye', 'partiel', 'impaye', 'surpaye'])
                      ->default('paye')
                      ->after('montant_paye_fc');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('paiements', 'statut')) {
            Schema::table('paiements', function (Blueprint $table) {
                $table->dropColumn('statut');
            });
        }
    }
};