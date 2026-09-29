<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
{
    Schema::table('eleves', function (Blueprint $table) {
        $table->boolean('inscrit')->default(false)->after('adresse');
    });
}

public function down()
{
    Schema::table('eleves', function (Blueprint $table) {
        $table->dropColumn('inscrit');
    });
}
};
