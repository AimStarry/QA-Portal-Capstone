<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add source tracking to risk_items so auto-generated risks record
     * which compliance record or accreditation triggered them.
     */
    public function up(): void
    {
        Schema::table('risk_items', function (Blueprint $table) {
            // 'compliance' | 'accreditation' | 'manual'
            $table->string('source_type')->nullable()->after('status');
            // The PK of the originating record (compliance_record_id or accreditation_id)
            $table->unsignedBigInteger('source_id')->nullable()->after('source_type');
            // True when the risk was created by the auto-log service
            $table->boolean('is_auto_logged')->default(false)->after('source_id');
        });
    }

    public function down(): void
    {
        Schema::table('risk_items', function (Blueprint $table) {
            $table->dropColumn(['source_type', 'source_id', 'is_auto_logged']);
        });
    }
};
