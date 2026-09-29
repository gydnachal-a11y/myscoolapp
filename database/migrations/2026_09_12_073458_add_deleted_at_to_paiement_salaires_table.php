<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ajoute la colonne `deleted_at` (soft deletes) à `paiement_salaires`.
     */
    public function up(): void
    {
        Schema::table('paiement_salaires', function (Blueprint $table) {
            // Colonne `deleted_at` nullable + index automatique
            $table->softDeletes();

            // ✅ Index composite pour accélérer les requêtes fréquentes
            //    (paiements actifs d'un employé pour un mois donné)
            $table->index(
                ['user_id', 'mois_scolaire_id', 'deleted_at'],
                'paiement_salaires_user_mois_active_idx'
            );
        });
    }

    /**
     * Supprime la colonne et l'index.
     */
    public function down(): void
    {
        Schema::table('paiement_salaires', function (Blueprint $table) {
            $table->dropIndex('paiement_salaires_user_mois_active_idx');
            $table->dropSoftDeletes();
        });
    }
};