<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Supprime l'ancienne table
        Schema::dropIfExists('taux_changes');

        // Recrée la table avec les nouvelles colonnes
        Schema::create('taux_changes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('devise_source_id')->constrained('devises')->onDelete('cascade');
            $table->foreignId('devise_cible_id')->constrained('devises')->onDelete('cascade');
            $table->decimal('taux', 15, 6);
            $table->unique(['devise_source_id', 'devise_cible_id']);
            $table->timestamps();
        });

        // Insère le taux par défaut USD → CDF
        DB::table('taux_changes')->insert([
            'devise_source_id' => 1, // USD
            'devise_cible_id'  => 2, // CDF
            'taux'             => 2800,
            'created_at'       => now(),
            'updated_at'       => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('taux_changes');
    }
};