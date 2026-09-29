<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fonctions', function (Blueprint $table) {
            $table->id();
            $table->string('nom');
            $table->text('description')->nullable();
            $table->timestamps();
        });

        // Insérer quelques fonctions de base
        DB::table('fonctions')->insert([
            ['nom' => 'Directeur', 'description' => 'Chef d\'établissement', 'created_at' => now(), 'updated_at' => now()],
            ['nom' => 'Secrétaire', 'description' => 'Gestion administrative', 'created_at' => now(), 'updated_at' => now()],
            ['nom' => 'Comptable', 'description' => 'Gestion financière', 'created_at' => now(), 'updated_at' => now()],
            ['nom' => 'Enseignant', 'description' => 'Corps enseignant', 'created_at' => now(), 'updated_at' => now()],
            ['nom' => 'Surveillant', 'description' => 'Surveillance des élèves', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('fonctions');
    }
};