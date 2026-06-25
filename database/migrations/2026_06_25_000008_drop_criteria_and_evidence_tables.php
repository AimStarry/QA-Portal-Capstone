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
        Schema::dropIfExists('evidence');
        Schema::dropIfExists('criteria');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Recreate criteria table
        Schema::create('criteria', function (Blueprint $table) {
            $table->id();
            $table->foreignId('program_id')->constrained()->cascadeOnDelete();
            $table->string('area');
            $table->string('title');
            $table->text('description')->nullable();
            $table->decimal('rating', 4, 2)->nullable();
            $table->decimal('max_score', 4, 2)->default(5.00);
            $table->timestamps();
        });

        // Recreate evidence table
        Schema::create('evidence', function (Blueprint $table) {
            $table->id();
            $table->foreignId('program_id')->constrained()->cascadeOnDelete();
            $table->string('file_name');
            $table->string('file_path');
            $table->string('category');
            $table->string('uploaded_by');
            $table->timestamps();
        });
    }
};
