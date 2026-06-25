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
        Schema::create('risk_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('program_id')->constrained()->cascadeOnDelete();
            $table->text('description');
            $table->string('likelihood'); // Low, Medium, High
            $table->string('impact'); // Low, Medium, High
            $table->text('mitigation_plan')->nullable();
            $table->string('status')->default('Identified'); // Identified, Mitigated, Monitoring
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('risk_items');
    }
};
