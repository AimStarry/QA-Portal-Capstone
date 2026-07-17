<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('responsible_unit_id')->nullable()->after('unit_id')->constrained('responsible_units', 'responsible_unit_id')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['responsible_unit_id']);
            $table->dropColumn('responsible_unit_id');
        });
    }
};
