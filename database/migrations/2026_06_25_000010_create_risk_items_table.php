<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('risk_items', function (Blueprint $table) {
            $table->id('risk_item_id');
            $table->foreignId('program_id')->constrained('programs', 'program_id')->cascadeOnDelete();
            $table->text('description');
            $table->string('likelihood');
            $table->string('impact');
            $table->text('mitigation_plan')->nullable();
            $table->string('status')->default('Open');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('risk_items');
    }
};
