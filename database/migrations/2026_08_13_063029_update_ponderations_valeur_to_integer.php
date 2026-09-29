<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ponderations', function (Blueprint $table) {
            $table->integer('valeur')->change();
        });
    }

    public function down(): void
    {
        Schema::table('ponderations', function (Blueprint $table) {
            $table->decimal('valeur', 5, 2)->change();
        });
    }
};