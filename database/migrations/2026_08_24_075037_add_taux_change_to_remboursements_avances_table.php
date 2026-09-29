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
        Schema::table('remboursements_avances', function (Blueprint $table) {
            // Ajouter la colonne taux_change (nullable pour les anciens enregistrements)
            $table->decimal('taux_change', 15, 2)
                  ->nullable()
                  ->after('montant_rembourse_fc')
                  ->comment('Taux USD/CDF utilisé lors du remboursement');

            // Optionnel : ajouter un index pour les requêtes fréquentes sur avance_id et paiement_salaire_id
            // Si non existants, vous pouvez les ajouter ici
            // $table->index('avance_id');
            // $table->index('paiement_salaire_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('remboursements_avances', function (Blueprint $table) {
            $table->dropColumn('taux_change');
        });
    }
};