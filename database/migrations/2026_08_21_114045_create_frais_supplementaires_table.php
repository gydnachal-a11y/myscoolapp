<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('frais_supplementaires', function (Blueprint $table) {
            $table->id();
            $table->string('libelle');
            $table->decimal('montant', 10, 2);
            $table->date('date_debut');
            $table->date('date_fin');
            $table->text('description')->nullable();
            $table->boolean('est_pour_toutes_salles')->default(false);
            $table->boolean('est_ouvert')->default(false); // ouverture du paiement
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('frais_supplementaires');
    }
};