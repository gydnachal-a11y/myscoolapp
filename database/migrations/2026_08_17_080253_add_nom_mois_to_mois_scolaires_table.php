<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mois_scolaires', function (Blueprint $table) {
            $table->string('nom_mois')->nullable()->after('mois');
        });
    }

    public function down(): void
    {
        Schema::table('mois_scolaires', function (Blueprint $table) {
            $table->dropColumn('nom_mois');
        });
    }
};