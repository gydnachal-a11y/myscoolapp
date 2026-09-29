<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('sessions_paiement', 'date_fin_session')) {
            Schema::table('sessions_paiement', function (Blueprint $table) {
                $table->date('date_fin_session')
                      ->nullable()
                      ->after('date_debut_session');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('sessions_paiement', 'date_fin_session')) {
            Schema::table('sessions_paiement', function (Blueprint $table) {
                $table->dropColumn('date_fin_session');
            });
        }
    }
};