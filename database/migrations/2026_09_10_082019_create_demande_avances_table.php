<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('demande_avances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('restrict');
            $table->foreignId('session_avance_id')->constrained('session_avances')->onDelete('restrict');
            $table->decimal('montant_demande_usd', 15, 2);
            $table->decimal('montant_demande_fc', 15, 2);
            $table->decimal('taux_applique', 15, 2);
            $table->text('motif');
            $table->enum('statut', ['en_attente', 'validee', 'refusee'])->default('en_attente');
            $table->text('motif_refus')->nullable();
            $table->foreignId('traite_par')->nullable()->constrained('users')->onDelete('restrict');
            $table->timestamp('traite_le')->nullable();
            $table->foreignId('avance_id')->nullable()->constrained('avance_salaires')->onDelete('set null');
            $table->timestamps();

            $table->index(['statut', 'created_at']);
            $table->index(['user_id', 'statut']);
            $table->index('session_avance_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('demande_avances');
    }
};