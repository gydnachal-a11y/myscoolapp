<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ✅ VRAI nom : demande_avances (avec s, pas de s à demande)
        Schema::table('demande_avances', function (Blueprint $table) {
            $table->timestamp('notifiee_vue_a')
                ->nullable()
                ->after('traite_le')
                ->comment('Date à laquelle le membre a vu la notification');
        });
    }

    public function down(): void
    {
        Schema::table('demande_avances', function (Blueprint $table) {
            $table->dropColumn('notifiee_vue_a');
        });
    }
};