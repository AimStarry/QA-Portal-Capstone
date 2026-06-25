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
        Schema::create('criteria', function (Blueprint $table) {
            $table->id();
            $table->foreignId('program_id')->constrained()->cascadeOnDelete();
            $table->string('area'); // e.g. Area I: Vision, Mission & Goals, Area II: Faculty
            $table->string('title'); // e.g. Curricular Alignment, Qualifications
            $table->text('description')->nullable();
            $table->decimal('rating', 4, 2)->nullable(); // e.g. 4.50
            $table->decimal('max_score', 4, 2)->default(5.00);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('criteria');
    }
};
