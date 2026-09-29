<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('creneaux_horaires', function (Blueprint $table) {
            $table->id();
            $table->string('libelle');
            $table->time('heure_debut');
            $table->time('heure_fin');
            $table->unsignedTinyInteger('ordre')->default(0);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('creneaux_horaires');
    }
};