<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'sexe')) {
                $table->enum('sexe', ['M', 'F'])->nullable()->after('role');
            }
            if (! Schema::hasColumn('users', 'adresse')) {
                $table->string('adresse')->nullable()->after('sexe');
            }
            if (! Schema::hasColumn('users', 'telephone')) {
                $table->string('telephone', 20)->nullable()->after('adresse');
            }
            if (! Schema::hasColumn('users', 'date_naissance')) {
                $table->date('date_naissance')->nullable()->after('telephone');
            }
            if (! Schema::hasColumn('users', 'matricule')) {
                $table->string('matricule')->unique()->nullable()->after('date_naissance');
            }
            if (! Schema::hasColumn('users', 'photo')) {
                $table->string('photo')->nullable()->after('matricule');
            }
            if (! Schema::hasColumn('users', 'fonction_id')) {
                $table->foreignId('fonction_id')->nullable()->after('photo')->constrained('fonctions')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'fonction_id')) {
                $table->dropForeign(['fonction_id']);
            }
            $columnsToDrop = array_filter(
                ['sexe', 'adresse', 'telephone', 'date_naissance', 'matricule', 'photo', 'fonction_id'],
                fn ($col) => Schema::hasColumn('users', $col)
            );
            if (! empty($columnsToDrop)) {
                $table->dropColumn($columnsToDrop);
            }
        });
    }
};