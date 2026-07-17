<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('compliance_records', function (Blueprint $table) {
            $table->id('compliance_record_id');
            $table->foreignId('program_id')->constrained('programs', 'program_id')->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('status')->default('Pending'); // e.g. Compliant, Non-Compliant, Pending
            $table->string('priority')->default('Medium'); // e.g. Low, Medium, High
            $table->date('due_date')->nullable();
            $table->string('responsible_unit')->nullable();
            $table->string('contact_person')->nullable();
            $table->string('contact_email')->nullable();
            $table->string('document_link')->nullable(); // holds approved evidence doc link
            $table->string('pending_status')->nullable();
            $table->string('pending_document_link')->nullable(); // holds proposed evidence doc link
            $table->string('approval_state')->default('Not Submitted'); // e.g. Not Submitted, Pending Approval, Approved, Rejected
            $table->text('rejection_reason')->nullable();
            $table->string('accrediting_body')->nullable();
            $table->string('school')->nullable();
            $table->text('recommendation')->nullable();
            $table->string('category')->nullable();
            $table->string('area')->nullable();
            $table->text('action_plan')->nullable();
            $table->date('visit_date')->nullable();
            $table->string('workflow_stage')->default('Preparation');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('compliance_records');
    }
};
