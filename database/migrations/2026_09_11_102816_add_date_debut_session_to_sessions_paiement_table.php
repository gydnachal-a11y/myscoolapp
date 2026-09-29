<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ✅ Vérifie si la colonne existe déjà (idempotent)
        if (!Schema::hasColumn('sessions_paiement', 'date_debut_session')) {
            Schema::table('sessions_paiement', function (Blueprint $table) {
                $table->date('date_debut_session')
                      ->nullable()
                      ->after('type_periode'); // ajuste la position
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('sessions_paiement', 'date_debut_session')) {
            Schema::table('sessions_paiement', function (Blueprint $table) {
                $table->dropColumn('date_debut_session');
            });
        }
    }
};