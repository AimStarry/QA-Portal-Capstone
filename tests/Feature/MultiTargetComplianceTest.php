<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\College;
use App\Models\Program;
use App\Models\ResponsibleUnit;
use App\Models\ComplianceRecord;
use App\Models\ComplianceAssignment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MultiTargetComplianceTest extends TestCase
{
    use RefreshDatabase;

    protected $admin;
    protected $college;
    protected $program1;
    protected $program2;
    protected $unit1;
    protected $unit2;

    protected function setUp(): void
    {
        parent::setUp();

        $this->college = College::create([
            'name' => 'School of Computing',
            'code' => 'SOC',
        ]);

        $this->admin = User::create([
            'name' => 'QA Admin',
            'username' => 'qaadmin',
            'usertype' => 'QA Admin',
            'email' => 'admin@hau.edu.ph',
            'password' => bcrypt('password'),
        ]);

        $this->program1 = Program::create([
            'program_code' => 'BSCS',
            'program_name' => 'Computer Science',
            'college_id' => $this->college->college_id,
            'department' => 'CS Dept',
            'program_level' => 'Undergraduate',
            'is_accreditable' => true,
        ]);

        $this->program2 = Program::create([
            'program_code' => 'BSIT',
            'program_name' => 'Information Tech',
            'college_id' => $this->college->college_id,
            'department' => 'IT Dept',
            'program_level' => 'Undergraduate',
            'is_accreditable' => true,
        ]);

        $this->unit1 = ResponsibleUnit::create([
            'name' => 'IT Department',
            'code' => 'ITD',
        ]);

        $this->unit2 = ResponsibleUnit::create([
            'name' => 'CS Department',
            'code' => 'CSD',
        ]);
    }

    public function test_can_create_compliance_record_with_multiple_programs_and_units()
    {
        $response = $this->actingAs($this->admin)->post('/compliance', [
            'title' => 'Multi Target Syllabus Requirement',
            'description' => 'Submit syllabi for AY 2026-2027',
            'status' => 'Pending',
            'priority' => 'High',
            'due_date' => '2026-08-30',
            'accrediting_body' => 'PAASCU',
            'school' => 'School of Computing',
            'categories' => ['General'],
            'areas' => ['Area I: Instruction'],
            'recommendations' => ['Submit updated syllabus link'],
            'program_ids' => [$this->program1->program_id, $this->program2->program_id],
            'responsible_unit_ids' => [$this->unit1->responsible_unit_id],
        ]);

        $response->assertRedirect('/compliance');

        $record = ComplianceRecord::first();
        $this->assertNotNull($record);
        $this->assertEquals('Multi Target Syllabus Requirement', $record->title);

        // Verify compliance assignments
        $this->assertEquals(3, $record->assignments()->count());
        $this->assertTrue($record->assignments()->where('program_id', $this->program1->program_id)->exists());
        $this->assertTrue($record->assignments()->where('program_id', $this->program2->program_id)->exists());
        $this->assertTrue($record->assignments()->where('responsible_unit_id', $this->unit1->responsible_unit_id)->exists());
    }

    public function test_independent_sharepoint_link_submission_and_approval()
    {
        $record = ComplianceRecord::create([
            'title' => 'Lab Inspection Task',
            'status' => 'Pending',
            'accrediting_body' => 'PACUCOA',
            'school' => 'School of Computing',
            'category' => 'General',
            'area' => 'Area IV',
            'program_id' => $this->program1->program_id,
        ]);

        $assignment1 = $record->assignments()->create([
            'program_id' => $this->program1->program_id,
            'status' => 'Pending',
            'approval_state' => 'None',
        ]);

        $assignment2 = $record->assignments()->create([
            'program_id' => $this->program2->program_id,
            'status' => 'Pending',
            'approval_state' => 'None',
        ]);

        // Submit link for assignment 1
        $this->actingAs($this->admin)->post("/compliance/{$record->compliance_record_id}/submit-update", [
            'assignment_id' => $assignment1->id,
            'pending_document_link' => 'https://sharepoint.com/bscs-evidence',
            'action_plan' => 'BSCS Evidence uploaded',
        ]);

        $assignment1->refresh();
        $this->assertEquals('Pending Approval', $assignment1->approval_state);
        $this->assertEquals('https://sharepoint.com/bscs-evidence', $assignment1->pending_document_link);

        // Admin approves assignment 1
        $this->actingAs($this->admin)->post("/compliance/{$record->compliance_record_id}/approve", [
            'assignment_id' => $assignment1->id,
        ]);

        $assignment1->refresh();
        $assignment2->refresh();
        $this->assertEquals('Compliant', $assignment1->status);
        $this->assertEquals('https://sharepoint.com/bscs-evidence', $assignment1->document_link);
        $this->assertEquals('Pending', $assignment2->status);
    }
}
