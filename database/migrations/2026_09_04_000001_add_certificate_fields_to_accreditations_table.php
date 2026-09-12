<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('accreditations', function (Blueprint $table) {
            $table->string('certificate_link', 2048)->nullable()->after('status');
            $table->string('certificate_file')->nullable()->after('certificate_link');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('accreditations', function (Blueprint $table) {
            $table->dropColumn(['certificate_link', 'certificate_file']);
        });
    }
};
