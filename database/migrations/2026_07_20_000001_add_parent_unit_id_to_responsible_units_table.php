<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('responsible_units', function (Blueprint $table) {
            $table->unsignedBigInteger('parent_unit_id')->nullable()->after('unit_id');
            $table->foreign('parent_unit_id')->references('responsible_unit_id')->on('responsible_units')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('responsible_units', function (Blueprint $table) {
            $table->dropForeign(['parent_unit_id']);
            $table->dropColumn('parent_unit_id');
        });
    }
};
