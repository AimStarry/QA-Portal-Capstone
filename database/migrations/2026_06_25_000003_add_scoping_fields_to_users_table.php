<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('username')->unique()->nullable()->after('email');
            $table->string('usertype')->default('QA Admin')->after('password');
            $table->foreignId('college_id')->nullable()->after('usertype')->constrained('colleges', 'college_id')->nullOnDelete();
            $table->foreignId('unit_id')->nullable()->after('college_id')->constrained('units', 'unit_id')->nullOnDelete();
            $table->string('first_name')->nullable()->after('name');
            $table->string('last_name')->nullable()->after('first_name');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['college_id']);
            $table->dropForeign(['unit_id']);
            $table->dropColumn(['username', 'usertype', 'college_id', 'unit_id', 'first_name', 'last_name']);
        });
    }
};
