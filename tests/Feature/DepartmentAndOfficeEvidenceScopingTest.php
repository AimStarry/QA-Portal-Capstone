<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\College;
use App\Models\Unit;
use App\Models\Program;
use App\Models\ResponsibleUnit;
use App\Models\ComplianceRecord;
use App\Models\ComplianceAssignment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DepartmentAndOfficeEvidenceScopingTest extends TestCase
{
    use RefreshDatabase;

    protected $admin;
    protected $collegeSba;
    protected $collegeSas;
    protected $deanSba;
    protected $deanSas;
    protected $officeOia;
    protected $unitOia;
    protected $headOia;
    protected $ruSba;
    protected $ruSas;

    protected function setUp(): void
    {
        parent::setUp();

        $this->collegeSba = College::create([
            'name' => 'School of Business and Accountancy',
            'code' => 'SBA',
        ]);

        $this->collegeSas = College::create([
            'name' => 'School of Arts and Sciences',
            'code' => 'SAS',
        ]);

        $this->ruSba = ResponsibleUnit::create([
            'name' => 'School of Business and Accountancy',
            'code' => 'SBA',
            'college_id' => $this->collegeSba->college_id,
        ]);

        $this->ruSas = ResponsibleUnit::create([
            'name' => 'School of Arts and Sciences',
            'code' => 'SAS',
            'college_id' => $this->collegeSas->college_id,
        ]);

        // Administrative Office (Unit)
        $this->unitOia = Unit::create([
            'name' => 'Office of International Affairs',
            'code' => 'OIA',
        ]);

        $this->officeOia = ResponsibleUnit::create([
            'name' => 'Office of International Affairs',
            'code' => 'OIA',
            'unit_id' => $this->unitOia->unit_id,
        ]);

        // Users
        $this->admin = User::create([
            'name' => 'QA Admin User',
            'username' => 'qa_admin',
            'email' => 'qa@hau.edu.ph',
            'usertype' => 'QA Admin',
            'password' => bcrypt('password'),
        ]);

        $this->deanSba = User::create([
            'name' => 'Dean SBA',
            'username' => 'dean_sba',
            'email' => 'deansba@hau.edu.ph',
            'usertype' => 'Dean',
            'college_id' => $this->collegeSba->college_id,
            'password' => bcrypt('password'),
        ]);

        $this->deanSas = User::create([
            'name' => 'Dean SAS',
            'username' => 'dean_sas',
            'email' => 'deansas@hau.edu.ph',
            'usertype' => 'Dean',
            'college_id' => $this->collegeSas->college_id,
            'password' => bcrypt('password'),
        ]);

        $this->headOia = User::create([
            'name' => 'Head of OIA',
            'username' => 'head_oia',
            'email' => 'oia@hau.edu.ph',
            'usertype' => 'Head of Unit',
            'responsible_unit_id' => $this->officeOia->responsible_unit_id,
            'unit_id' => $this->unitOia->unit_id,
            'password' => bcrypt('password'),
        ]);
    }

    public function test_department_matrix_consolidates_under_department_evidence_column()
    {
        $response = $this->actingAs($this->admin)->post('/compliance', [
            'title' => 'Group A Department OBE Syllabi',
            'status' => 'Pending',
            'accrediting_body' => 'PAASCU',
            'school' => 'School of Business and Accountancy; School of Arts and Sciences',
            'categories' => ['General'],
            'areas' => ['Area IV: Teaching-Learning'],
            'recommendations' => ['Submit department syllabi'],
            'responsible_unit_ids' => [$this->ruSba->responsible_unit_id, $this->ruSas->responsible_unit_id],
        ]);

        $response->assertRedirect('/compliance');

        $record = ComplianceRecord::where('title', 'Group A Department OBE Syllabi')->first();
        $this->assertNotNull($record);

        // Should NOT create 2x2 = 4 assignments, but only 2 (1 per school)
        $this->assertEquals(2, $record->assignments()->count());

        $gridData = $record->getMatrixGridData();
        $this->assertCount(2, $gridData['schools']);
        // Only 1 column: Department Evidence
        $this->assertCount(1, $gridData['units']);
        $this->assertEquals('Department Evidence', $gridData['units'][0]['name']);
        $this->assertEquals(2, $gridData['total_cells']);

        // SBA row has SBA assignment
        $sbaCell = $gridData['matrix']['School of Business and Accountancy']['cells']['unit_dept'];
        $this->assertNotNull($sbaCell);
        $this->assertEquals('School of Business and Accountancy', $sbaCell->school_name);

        // SAS row has SAS assignment
        $sasCell = $gridData['matrix']['School of Arts and Sciences']['cells']['unit_dept'];
        $this->assertNotNull($sasCell);
        $this->assertEquals('School of Arts and Sciences', $sasCell->school_name);
    }

    public function test_dean_can_only_submit_for_their_own_school()
    {
        $record = ComplianceRecord::create([
            'title' => 'Department Evidence Test',
            'status' => 'Pending',
            'accrediting_body' => 'PAASCU',
            'school' => 'School of Business and Accountancy; School of Arts and Sciences',
            'category' => 'General',
            'area' => 'Area IV',
        ]);

        $sbaAssignment = $record->assignments()->create([
            'school_name' => 'School of Business and Accountancy',
            'responsible_unit_id' => $this->ruSba->responsible_unit_id,
            'status' => 'Pending',
            'approval_state' => 'None',
        ]);

        $sasAssignment = $record->assignments()->create([
            'school_name' => 'School of Arts and Sciences',
            'responsible_unit_id' => $this->ruSas->responsible_unit_id,
            'status' => 'Pending',
            'approval_state' => 'None',
        ]);

        // Dean SBA can submit for SBA
        $response = $this->actingAs($this->deanSba)->postJson("/compliance/{$record->compliance_record_id}/submit-update", [
            'assignment_id' => $sbaAssignment->id,
            'pending_document_link' => 'https://sharepoint.com/sba-link',
            'action_plan' => 'SBA action plan',
        ]);
        $response->assertOk();
        $sbaAssignment->refresh();
        $this->assertEquals('Pending Approval', $sbaAssignment->approval_state);

        // Dean SBA CANNOT submit for SAS (Forbidden 403)
        $forbiddenResponse = $this->actingAs($this->deanSba)->postJson("/compliance/{$record->compliance_record_id}/submit-update", [
            'assignment_id' => $sasAssignment->id,
            'pending_document_link' => 'https://sharepoint.com/hacked-sas-link',
            'action_plan' => 'Illegitimate SAS submission',
        ]);
        $forbiddenResponse->assertStatus(403);
    }

    public function test_office_can_submit_to_all_departments_assigned_to_them()
    {
        $record = ComplianceRecord::create([
            'title' => 'OIA International Exchange Assessment',
            'status' => 'Pending',
            'accrediting_body' => 'PAASCU',
            'school' => 'School of Business and Accountancy; School of Arts and Sciences',
            'category' => 'Resurvey Feedback',
            'area' => 'Area 6: External Relations',
        ]);

        $oiaSba = $record->assignments()->create([
            'school_name' => 'School of Business and Accountancy',
            'responsible_unit_id' => $this->officeOia->responsible_unit_id,
            'status' => 'Pending',
            'approval_state' => 'None',
        ]);

        $oiaSas = $record->assignments()->create([
            'school_name' => 'School of Arts and Sciences',
            'responsible_unit_id' => $this->officeOia->responsible_unit_id,
            'status' => 'Pending',
            'approval_state' => 'None',
        ]);

        // Head of OIA can submit for SBA row
        $res1 = $this->actingAs($this->headOia)->postJson("/compliance/{$record->compliance_record_id}/submit-update", [
            'assignment_id' => $oiaSba->id,
            'pending_document_link' => 'https://sharepoint.com/oia-sba',
            'action_plan' => 'OIA submission for SBA',
        ]);
        $res1->assertOk();

        // Head of OIA can also submit for SAS row
        $res2 = $this->actingAs($this->headOia)->postJson("/compliance/{$record->compliance_record_id}/submit-update", [
            'assignment_id' => $oiaSas->id,
            'pending_document_link' => 'https://sharepoint.com/oia-sas',
            'action_plan' => 'OIA submission for SAS',
        ]);
        $res2->assertOk();

        // But Dean SBA cannot submit to OIA's assignment
        $res3 = $this->actingAs($this->deanSba)->postJson("/compliance/{$record->compliance_record_id}/submit-update", [
            'assignment_id' => $oiaSba->id,
            'pending_document_link' => 'https://sharepoint.com/dean-overwriting-oia',
            'action_plan' => 'Dean attempting to submit OIA evidence',
        ]);
        $res3->assertStatus(403);
    }
}
