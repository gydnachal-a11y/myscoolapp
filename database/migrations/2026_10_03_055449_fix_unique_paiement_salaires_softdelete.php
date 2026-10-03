<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('paiement_salaires', function (Blueprint $table) {
            // Supprime l'ancienne contrainte
            $table->dropUnique('unique_paiement_employe_mois');

            // ✅ Nouvelle contrainte qui inclut deleted_at
            // Ainsi, un paiement supprimé (soft delete) NE BLOQUE PLUS
            // la création d'un nouveau pour le même mois.
            $table->unique(
                ['user_id', 'mois_scolaire_id', 'deleted_at'],
                'unique_paiement_employe_mois'
            );
        });
    }

    public function down(): void
    {
        Schema::table('paiement_salaires', function (Blueprint $table) {
            $table->dropUnique('unique_paiement_employe_mois');

            $table->unique(
                ['user_id', 'mois_scolaire_id'],
                'unique_paiement_employe_mois'
            );
        });
    }
};