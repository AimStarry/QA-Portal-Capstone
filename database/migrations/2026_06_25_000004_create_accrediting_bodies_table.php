<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('accrediting_bodies', function (Blueprint $table) {
            $table->id('accrediting_body_id');
            $table->string('name')->unique();
            $table->string('code')->unique();
            $table->string('type')->default('Local'); // e.g. Local, International, Regulatory
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('accrediting_bodies');
    }
};
