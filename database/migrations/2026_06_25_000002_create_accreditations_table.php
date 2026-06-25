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
        Schema::create('accreditations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('program_id')->constrained()->cascadeOnDelete();
            $table->string('accrediting_body');
            $table->string('type')->default('Local'); // e.g. Local, International, Regulatory
            $table->string('level_or_tier')->nullable();
            $table->date('last_visit')->nullable();
            $table->date('expiry_date')->nullable();
            $table->string('status')->default('Pending'); // e.g. Active, Expiring Soon, Expired, Pending
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('accreditations');
    }
};
