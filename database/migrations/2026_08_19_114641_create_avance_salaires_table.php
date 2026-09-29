<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('avance_salaires', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('mois_scolaire_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('montant_avance_usd', 10, 2);
            $table->decimal('montant_avance_fc', 12, 2)->default(0);
            $table->date('date_avance');
            $table->string('motif')->nullable();
            $table->enum('statut', ['en_attente', 'remboursee', 'partiellement_remboursee', 'annulee'])
                ->default('en_attente');
            $table->text('commentaire')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('avance_salaires');
    }
};