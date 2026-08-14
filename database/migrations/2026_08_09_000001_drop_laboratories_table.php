<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('compliance_records') && Schema::hasColumn('compliance_records', 'laboratory_id')) {
            Schema::table('compliance_records', function (Blueprint $table) {
                try {
                    $table->dropForeign(['laboratory_id']);
                } catch (\Throwable $e) {
                    // Ignore if foreign key was already dropped or doesn't exist
                }
                $table->dropColumn('laboratory_id');
            });
        }

        Schema::dropIfExists('laboratories');
    }

    public function down(): void
    {
        Schema::create('laboratories', function (Blueprint $table) {
            $table->id('laboratory_id');
            $table->string('name');
            $table->foreignId('responsible_unit_id')->constrained('responsible_units', 'responsible_unit_id')->cascadeOnDelete();
            $table->timestamps();
        });

        if (Schema::hasTable('compliance_records') && !Schema::hasColumn('compliance_records', 'laboratory_id')) {
            Schema::table('compliance_records', function (Blueprint $table) {
                $table->foreignId('laboratory_id')->nullable()->constrained('laboratories', 'laboratory_id')->nullOnDelete();
            });
        }
    }
};
