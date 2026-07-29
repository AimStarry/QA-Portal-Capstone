<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('compliance_assignments', function (Blueprint $table) {
            $table->string('school_name')->nullable()->after('responsible_unit_id');
        });
    }

    public function down(): void
    {
        Schema::table('compliance_assignments', function (Blueprint $table) {
            $table->dropColumn('school_name');
        });
    }
};
