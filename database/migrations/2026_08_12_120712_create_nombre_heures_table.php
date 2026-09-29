<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('nombre_heures', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('valeur')->unique();
            $table->string('libelle')->nullable();
            $table->timestamps();
        });

        foreach ([1, 2, 3, 4, 5, 6, 8, 10] as $h) {
            DB::table('nombre_heures')->insert([
                'valeur' => $h,
                'libelle' => $h . 'h',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('nombre_heures');
    }
};