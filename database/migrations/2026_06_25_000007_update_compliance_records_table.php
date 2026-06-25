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
        Schema::table('compliance_records', function (Blueprint $table) {
            $table->string('document_link')->nullable()->after('responsible_person');
            $table->string('pending_status')->nullable()->after('document_link');
            $table->string('pending_document_link')->nullable()->after('pending_status');
            $table->string('approval_state')->default('None')->after('pending_document_link'); // 'None', 'Pending Approval', 'Rejected'
            $table->string('rejection_reason')->nullable()->after('approval_state');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('compliance_records', function (Blueprint $table) {
            $table->dropColumn([
                'document_link',
                'pending_status',
                'pending_document_link',
                'approval_state',
                'rejection_reason'
            ]);
        });
    }
};
