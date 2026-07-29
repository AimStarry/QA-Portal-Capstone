<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('compliance_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('compliance_record_id')->constrained('compliance_records', 'compliance_record_id')->cascadeOnDelete();
            $table->foreignId('program_id')->nullable()->constrained('programs', 'program_id')->cascadeOnDelete();
            $table->foreignId('responsible_unit_id')->nullable()->constrained('responsible_units', 'responsible_unit_id')->cascadeOnDelete();
            $table->string('status')->default('Pending'); // e.g. Compliant, Non-Compliant, Pending
            $table->string('approval_state')->default('None'); // e.g. None, Pending Approval, Approved, Rejected
            $table->text('document_link')->nullable();
            $table->text('pending_document_link')->nullable();
            $table->text('action_plan')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->string('workflow_stage')->default('recommendation_created');
            $table->timestamps();
        });

        // Migrate existing compliance records into compliance_assignments
        $records = DB::table('compliance_records')->get();
        foreach ($records as $record) {
            DB::table('compliance_assignments')->insert([
                'compliance_record_id' => $record->compliance_record_id,
                'program_id'           => $record->program_id,
                'responsible_unit_id'  => $record->responsible_unit_id,
                'status'               => $record->status ?? 'Pending',
                'approval_state'       => $record->approval_state ?? 'None',
                'document_link'        => $record->document_link,
                'pending_document_link'=> $record->pending_document_link,
                'action_plan'          => $record->action_plan,
                'rejection_reason'     => $record->rejection_reason,
                'workflow_stage'       => $record->workflow_stage ?? 'recommendation_created',
                'created_at'           => $record->created_at ?? now(),
                'updated_at'           => $record->updated_at ?? now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('compliance_assignments');
    }
};
