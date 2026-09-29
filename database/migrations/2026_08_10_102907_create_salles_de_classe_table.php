<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('salles_de_classe', function (Blueprint $table) {
            $table->id();
            $table->string('nom');
            $table->foreignId('section_id')->constrained('sections')->onDelete('cascade');
            $table->foreignId('option_id')->nullable()->constrained('options')->onDelete('set null');
            $table->integer('capacite_max');
            $table->decimal('frais_inscription', 10, 2);
            $table->decimal('frais_annuel', 10, 2);
            $table->integer('age_min');
            $table->integer('age_max');
            $table->text('description')->nullable();
            $table->foreignId('salle_superieure_id')->nullable()->constrained('salles_de_classe')->onDelete('set null');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('salles_de_classe');
    }
};