<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('responsible_units', function (Blueprint $table) {
            $table->id('responsible_unit_id');
            $table->string('name')->unique();
            $table->string('code')->nullable();
            $table->foreignId('college_id')->nullable()->constrained('colleges', 'college_id')->nullOnDelete();
            $table->foreignId('unit_id')->nullable()->constrained('units', 'unit_id')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('responsible_units');
    }
};
