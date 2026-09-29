<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ponderations', function (Blueprint $table) {
            $table->id();
            $table->string('nom')->unique();
            $table->decimal('valeur', 5, 2)->default(1.0);
            $table->timestamps();
        });

        DB::table('ponderations')->insert([
            ['nom' => '1', 'valeur' => 1.0, 'created_at' => now(), 'updated_at' => now()],
            ['nom' => '2', 'valeur' => 2.0, 'created_at' => now(), 'updated_at' => now()],
            ['nom' => '3', 'valeur' => 3.0, 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('ponderations');
    }
};