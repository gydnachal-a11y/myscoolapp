<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('remboursements_avances', function (Blueprint $table) {
            $table->id();

            // ============================================================
            // CLÉS ÉTRANGÈRES
            // ============================================================
            $table->foreignId('avance_id')
                  ->constrained('avance_salaires')
                  ->cascadeOnDelete();

            $table->foreignId('paiement_salaire_id')
                  ->nullable()
                  ->constrained('paiement_salaires')
                  ->nullOnDelete();

            // ============================================================
            // MONTANTS
            // ============================================================
            $table->decimal('montant_rembourse_usd', 10, 2);
            $table->decimal('montant_rembourse_fc', 15, 2)->default(0);

            // ✅ CORRECTION : ajout du champ taux_change (requis par le modèle)
            $table->decimal('taux_change', 15, 2)->default(0);

            // ============================================================
            // MÉTADONNÉES
            // ============================================================
            $table->date('date_remboursement');
            $table->text('commentaire')->nullable();
            $table->timestamps();

            // ============================================================
            // INDEX (recommandés pour les performances)
            // ============================================================
            $table->index('avance_id');
            $table->index('paiement_salaire_id');
            $table->index('date_remboursement');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('remboursements_avances');
    }
};