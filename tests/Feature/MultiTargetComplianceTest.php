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

    public function test_cartesian_product_n_schools_by_m_units_and_matrix_rollup()
    {
        $response = $this->actingAs($this->admin)->post('/compliance', [
            'title' => 'Multi-School Multi-Unit Audit',
            'status' => 'Pending',
            'accrediting_body' => 'PAASCU',
            'school' => 'School of Computing; School of Business; School of Education',
            'categories' => ['General'],
            'areas' => ['Area I'],
            'recommendations' => ['Action 1'],
            'responsible_unit_ids' => [$this->unit1->responsible_unit_id, $this->unit2->responsible_unit_id],
        ]);

        $response->assertRedirect('/compliance');

        $record = ComplianceRecord::where('title', 'Multi-School Multi-Unit Audit')->first();
        $this->assertNotNull($record);

        // 3 Schools x 2 Units = 6 assignments
        $this->assertEquals(6, $record->assignments()->count());

        $gridData = $record->getMatrixGridData();
        $this->assertTrue($gridData['has_matrix']);
        $this->assertCount(3, $gridData['schools']);
        $this->assertCount(2, $gridData['units']);
        $this->assertEquals(6, $gridData['total_cells']);
        $this->assertEquals(0, $gridData['completed_cells']);
        $this->assertEquals(0, $gridData['matrix_rate']);

        // Approve 1 cell (e.g. School of Computing x IT Department)
        $cell = $record->assignments()
            ->where('school_name', 'School of Computing')
            ->where('responsible_unit_id', $this->unit1->responsible_unit_id)
            ->first();
        $this->assertNotNull($cell);

        $this->actingAs($this->admin)->post("/compliance/{$record->compliance_record_id}/submit-update", [
            'assignment_id' => $cell->id,
            'pending_document_link' => 'https://sharepoint.com/soc-it-doc',
            'action_plan' => 'SOC IT evidence ready',
        ]);

        $this->actingAs($this->admin)->post("/compliance/{$record->compliance_record_id}/approve", [
            'assignment_id' => $cell->id,
        ]);

        $cell->refresh();
        $this->assertEquals('Compliant', $cell->status);

        $freshGrid = $record->fresh()->getMatrixGridData();
        $this->assertEquals(1, $freshGrid['completed_cells']);
        $this->assertEquals(17, $freshGrid['matrix_rate']); // 1/6 = 17%
        $this->assertEquals(1, $freshGrid['matrix']['School of Computing']['completed_count']);
        $this->assertEquals(2, $freshGrid['matrix']['School of Computing']['total_count']);
        $this->assertFalse($freshGrid['matrix']['School of Computing']['is_complete']);
    }
}
