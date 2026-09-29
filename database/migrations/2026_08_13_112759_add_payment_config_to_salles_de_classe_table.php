<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('salles_de_classe', function (Blueprint $table) {
            $table->enum('mode_paiement', ['mensuel', 'tranche'])->default('mensuel')->after('frais_annuel');
            $table->decimal('frais_scolarite_mensuel', 10, 2)->nullable()->after('mode_paiement');
            $table->unsignedTinyInteger('nombre_tranches')->nullable()->after('frais_scolarite_mensuel');
        });
    }

    public function down(): void
    {
        Schema::table('salles_de_classe', function (Blueprint $table) {
            $table->dropColumn(['mode_paiement', 'frais_scolarite_mensuel', 'nombre_tranches']);
        });
    }
};