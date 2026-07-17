<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('graduate_records', function (Blueprint $table) {
            $table->id('graduate_record_id');
            $table->foreignId('program_id')->constrained('programs', 'program_id')->cascadeOnDelete();
            $table->string('school_year');
            $table->string('term');
            $table->integer('graduates_count')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('graduate_records');
    }
};
