<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('programs', function (Blueprint $table) {
            $table->id('program_id');
            $table->string('program_code')->unique();
            $table->string('program_name');
            $table->string('former_name')->nullable();
            $table->foreignId('college_id')->constrained('colleges', 'college_id')->cascadeOnDelete();
            $table->string('department')->nullable();
            $table->string('former_department')->nullable();
            $table->string('program_level');
            $table->boolean('is_accreditable')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('programs');
    }
};
