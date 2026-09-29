<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  public function up(): void
{
    Schema::create('settings', function (Blueprint $table) {
        $table->id();
        $table->string('key')->unique();
        $table->text('value')->nullable();
        $table->timestamps();
    });

    // Valeurs par défaut
    DB::table('settings')->insert([
        ['key' => 'ecole_nom', 'value' => 'Mon École'],
        ['key' => 'ecole_adresse', 'value' => '123, Avenue de la Révolution, Kinshasa'],
        ['key' => 'ecole_telephone', 'value' => '+243 800 000 000'],
        ['key' => 'ecole_email', 'value' => 'contact@ecole.cd'],
        ['key' => 'ecole_logo', 'value' => null], // chemin vers le logo
    ]);
}
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
