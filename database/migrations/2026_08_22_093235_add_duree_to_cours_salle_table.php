<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cours_salle', function (Blueprint $table) {
            $table->string('duree', 50)->nullable()->after('jours');
        });
    }

    public function down(): void
    {
        Schema::table('cours_salle', function (Blueprint $table) {
            $table->dropColumn('duree');
        });
    }
};