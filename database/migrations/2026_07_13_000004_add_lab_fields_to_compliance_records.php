<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('compliance_records', function (Blueprint $table) {
            $table->foreignId('responsible_unit_id')->nullable()->after('responsible_unit')->constrained('responsible_units', 'responsible_unit_id')->nullOnDelete();
            $table->foreignId('laboratory_id')->nullable()->after('category')->constrained('laboratories', 'laboratory_id')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('compliance_records', function (Blueprint $table) {
            $table->dropForeign(['responsible_unit_id']);
            $table->dropForeign(['laboratory_id']);
            $table->dropColumn(['responsible_unit_id', 'laboratory_id']);
        });
    }
};
