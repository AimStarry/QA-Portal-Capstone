<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('compliance_records', function (Blueprint $table) {
            $table->foreignId('program_id')->nullable()->change();
            $table->foreignId('responsible_unit_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('compliance_records', function (Blueprint $table) {
            $table->foreignId('program_id')->nullable(false)->change();
        });
    }
};
