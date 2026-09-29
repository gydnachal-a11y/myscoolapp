<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->enum('type_salaire', ['manuel', 'automatique'])->default('manuel')->after('section_id');
            $table->decimal('salaire_mensuel_usd', 10, 2)->nullable()->after('type_salaire');
            $table->decimal('salaire_mensuel_fc', 12, 2)->nullable()->after('salaire_mensuel_usd');
            $table->decimal('salaire_auto_base_usd', 10, 2)->nullable()->after('salaire_mensuel_fc');
            $table->decimal('salaire_auto_base_fc', 12, 2)->nullable()->after('salaire_auto_base_usd');
            $table->decimal('salaire_ajuste_usd', 10, 2)->nullable()->after('salaire_auto_base_fc');
            $table->decimal('salaire_ajuste_fc', 12, 2)->nullable()->after('salaire_ajuste_usd');
            $table->timestamp('date_fixation_salaire')->nullable()->after('salaire_ajuste_fc');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'type_salaire',
                'salaire_mensuel_usd',
                'salaire_mensuel_fc',
                'salaire_auto_base_usd',
                'salaire_auto_base_fc',
                'salaire_ajuste_usd',
                'salaire_ajuste_fc',
                'date_fixation_salaire',
            ]);
        });
    }
};