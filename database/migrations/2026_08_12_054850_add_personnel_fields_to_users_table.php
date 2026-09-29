<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->enum('sexe', ['M', 'F'])->nullable()->after('role');
            $table->string('adresse')->nullable()->after('sexe');
            $table->string('telephone', 20)->nullable()->after('adresse');
            $table->date('date_naissance')->nullable()->after('telephone');
            $table->string('matricule')->unique()->nullable()->after('date_naissance');
            $table->string('photo')->nullable()->after('matricule');
            $table->foreignId('fonction_id')->nullable()->after('photo')->constrained('fonctions')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['fonction_id']);
            $table->dropColumn(['sexe', 'adresse', 'telephone', 'date_naissance', 'matricule', 'photo', 'fonction_id']);
        });
    }
};