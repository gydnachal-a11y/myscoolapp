<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            $table->string('site_slogan')->nullable()->after('site_name');
            $table->string('responsable_name')->nullable()->after('site_description');
            $table->date('creation_date')->nullable()->after('responsable_name');
        });
    }

    public function down(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            $table->dropColumn(['site_slogan', 'responsable_name', 'creation_date']);
        });
    }
};