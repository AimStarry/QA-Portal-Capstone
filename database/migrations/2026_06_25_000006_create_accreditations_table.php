<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('accreditations', function (Blueprint $table) {
            $table->id('accreditation_id');
            $table->foreignId('program_id')->constrained('programs', 'program_id')->cascadeOnDelete();
            $table->string('accrediting_body');
            $table->string('type')->default('Local'); // e.g. Local, International, Regulatory
            $table->string('level_or_tier')->nullable();
            $table->date('last_visit')->nullable();
            $table->date('expiry_date')->nullable();
            $table->string('status')->default('Pending'); // e.g. Active, Expiring Soon, Expired, Pending
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('accreditations');
    }
};
