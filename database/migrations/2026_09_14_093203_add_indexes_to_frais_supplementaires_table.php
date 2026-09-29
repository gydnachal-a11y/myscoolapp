<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  public function up(): void
{
    Schema::table('frais_supplementaires', function (Blueprint $table) {
        $table->index('libelle');
        $table->index('est_ouvert');
    });
}

public function down(): void
{
    Schema::table('frais_supplementaires', function (Blueprint $table) {
        $table->dropIndex(['libelle']);
        $table->dropIndex(['est_ouvert']);
    });
}
};
