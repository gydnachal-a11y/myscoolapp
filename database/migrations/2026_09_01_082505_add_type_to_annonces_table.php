<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('annonces', function (Blueprint $table) {
            $table->enum('type', ['public', 'prive'])->default('public')->after('contenu');
        });

        // Table pivot pour les lectures (notification cloche)
        Schema::create('annonce_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('annonce_id')->constrained()->onDelete('cascade');
            $table->timestamp('lu_a')->nullable();
            $table->timestamps();
            $table->unique(['user_id', 'annonce_id']);
        });
    }

    public function down(): void
    {
        Schema::table('annonces', function (Blueprint $table) {
            $table->dropColumn('type');
        });
        Schema::dropIfExists('annonce_user');
    }
};