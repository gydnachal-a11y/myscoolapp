<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('paiement_salaires', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete();
            $table->foreignId('mois_scolaire_id')
                ->constrained('mois_scolaires')
                ->cascadeOnDelete();
            $table->decimal('montant_attendu_usd', 10, 2);
            $table->decimal('montant_attendu_fc', 15, 2);
            $table->decimal('montant_paye_usd', 10, 2);
            $table->decimal('montant_paye_fc', 15, 2);
            $table->decimal('montant_restant_usd', 10, 2)->default(0);
            $table->boolean('est_paye')->default(false);
            $table->text('motif_ecart')->nullable();
            $table->date('date_paiement');
            $table->timestamps();

            // Unicité : un employé ne peut être payé qu'une seule fois par mois
            $table->unique(['user_id', 'mois_scolaire_id'], 'unique_paiement_employe_mois');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('paiement_salaires');
    }
};