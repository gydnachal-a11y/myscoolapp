<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fonctions', function (Blueprint $table) {
            $table->foreignId('section_id')->nullable()->after('description')->constrained('sections')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('fonctions', function (Blueprint $table) {
            $table->dropForeign(['section_id']);
            $table->dropColumn('section_id');
        });
    }
};