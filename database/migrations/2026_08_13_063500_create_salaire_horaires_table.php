<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('salaire_horaires', function (Blueprint $table) {
            $table->id();
            $table->decimal('taux_usd', 10, 2)->default(3.00);
            $table->boolean('actif')->default(true);
            $table->timestamps();
        });

        DB::table('salaire_horaires')->insert([
            'taux_usd' => 3.00,
            'actif' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('salaire_horaires');
    }
};
