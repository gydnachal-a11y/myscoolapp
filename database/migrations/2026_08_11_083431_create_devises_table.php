<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('devises', function (Blueprint $table) {
            $table->id();
            $table->string('code', 3)->unique();
            $table->string('nom');
            $table->string('symbole', 10)->nullable();
            $table->boolean('est_defaut')->default(false);
            $table->timestamps();
        });

        DB::table('devises')->insert([
            ['code' => 'USD', 'nom' => 'Dollar américain', 'symbole' => '$', 'est_defaut' => true],
            ['code' => 'CDF', 'nom' => 'Franc congolais',   'symbole' => 'FC', 'est_defaut' => false],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('devises');
    }
};