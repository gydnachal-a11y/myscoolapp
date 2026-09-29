<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // Remplace l'ENUM par un simple VARCHAR pour permettre n'importe quel rôle
        Schema::table('users', function (Blueprint $table) {
            $table->string('role', 30)->default('secretaire')->change();
        });
    }

    public function down()
    {
        DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('super_admin','admin','directeur','secretaire','comptable','enseignant') NOT NULL DEFAULT 'secretaire'");
    }
};