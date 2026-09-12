<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\College;
use App\Models\Program;
use App\Models\ResponsibleUnit;
use App\Models\ComplianceRecord;
use App\Models\ComplianceAssignment;
use App\Models\RecommendationItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ComplianceExportTest extends TestCase
{
    use RefreshDatabase;

    protected $admin;
    protected $dean;
    protected $college1;
    protected $college2;
    protected $program1;
    protected $program2;
    protected $unit;

    protected function setUp(): void
    {
        parent::setUp();

        $this->college1 = College::create([
            'name' => 'School of Computing',
            'code' => 'SOC',
        ]);

        $this->college2 = College::create([
            'name' => 'School of Business and Accountancy',
            'code' => 'SBA',
        ]);

        $this->admin = User::create([
            'name' => 'QA Admin',
            'username' => 'qaadmin',
            'usertype' => 'QA Admin',
            'email' => 'admin@hau.edu.ph',
            'password' => bcrypt('password'),
        ]);

        $this->dean = User::create([
            'name' => 'Dean Computing',
            'username' => 'dean_soc',
            'usertype' => 'Dean',
            'email' => 'dean_soc@hau.edu.ph',
            'password' => bcrypt('password'),
            'college_id' => $this->college1->college_id,
        ]);

        $this->program1 = Program::create([
            'program_code' => 'BSCS',
            'program_name' => 'Computer Science',
            'college_id' => $this->college1->college_id,
            'department' => 'CS Dept',
            'program_level' => 'Undergraduate',
            'is_accreditable' => true,
        ]);

        $this->program2 = Program::create([
            'program_code' => 'BSA',
            'program_name' => 'Accountancy',
            'college_id' => $this->college2->college_id,
            'department' => 'Acc Dept',
            'program_level' => 'Undergraduate',
            'is_accreditable' => true,
        ]);

        $this->unit = ResponsibleUnit::create([
            'name' => 'Information Technology Services',
            'code' => 'ITS',
        ]);
    }

    public function test_compliance_export_returns_streamed_xlsx_spreadsheet()
    {
        $record = ComplianceRecord::create([
            'title' => 'Upgrade Lab Servers',
            'description' => 'Install redundant power supplies and update firmware',
            'status' => 'Pending',
            'priority' => 'High',
            'due_date' => now()->addDays(30),
            'program_id' => $this->program1->program_id,
            'accrediting_body' => 'PAASCU',
            'school' => 'School of Computing',
            'category' => 'Laboratories',
            'area' => 'Area VII',
            'responsible_unit' => 'Information Technology Services',
            'workflow_stage' => 'recommendation_created',
        ]);

        RecommendationItem::create([
            'compliance_record_id' => $record->compliance_record_id,
            'text' => 'Procure 2 redundant PSUs',
            'is_completed' => true,
        ]);

        $response = $this->actingAs($this->admin)->get(route('compliance.export'));

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

        $content = $response->streamedContent();
        $this->assertNotEmpty($content);
        // Standard ZIP/XLSX magic bytes start with PK (0x50 0x4B)
        $this->assertStringStartsWith('PK', $content);
    }

    public function test_compliance_export_csv_format_option_works()
    {
        $record = ComplianceRecord::create([
            'title' => 'Upgrade Lab Servers',
            'description' => 'Install redundant power supplies and update firmware',
            'status' => 'Pending',
            'priority' => 'High',
            'due_date' => now()->addDays(30),
            'program_id' => $this->program1->program_id,
            'accrediting_body' => 'PAASCU',
            'school' => 'School of Computing',
            'category' => 'Laboratories',
            'area' => 'Area VII',
            'responsible_unit' => 'Information Technology Services',
            'workflow_stage' => 'recommendation_created',
        ]);

        RecommendationItem::create([
            'compliance_record_id' => $record->compliance_record_id,
            'text' => 'Procure 2 redundant PSUs',
            'is_completed' => true,
        ]);

        $response = $this->actingAs($this->admin)->get(route('compliance.export', ['format' => 'csv']));

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');

        $content = $response->streamedContent();
        $this->assertStringStartsWith(chr(0xEF) . chr(0xBB) . chr(0xBF), $content);
        $this->assertStringContainsString('Compliance ID', $content);
        $this->assertStringContainsString('Upgrade Lab Servers', $content);
        $this->assertStringContainsString('PAASCU', $content);
    }

    public function test_compliance_export_orders_by_compliance_record_id_asc()
    {
        $r1 = ComplianceRecord::create([
            'title' => 'First Task',
            'status' => 'Pending',
            'program_id' => $this->program1->program_id,
        ]);

        $r2 = ComplianceRecord::create([
            'title' => 'Second Task',
            'status' => 'Compliant',
            'program_id' => $this->program1->program_id,
        ]);

        $response = $this->actingAs($this->admin)->get(route('compliance.export', ['format' => 'csv']));
        $content = $response->streamedContent();

        $pos1 = strpos($content, 'First Task');
        $pos2 = strpos($content, 'Second Task');

        $this->assertTrue($pos1 < $pos2);
    }

    public function test_compliance_export_filters_by_query_parameters()
    {
        ComplianceRecord::create([
            'title' => 'PAASCU Task 1',
            'status' => 'Compliant',
            'accrediting_body' => 'PAASCU',
            'program_id' => $this->program1->program_id,
            'workflow_stage' => 'compliant',
        ]);

        ComplianceRecord::create([
            'title' => 'PACUCOA Task 2',
            'status' => 'Non-Compliant',
            'accrediting_body' => 'PACUCOA',
            'program_id' => $this->program2->program_id,
            'workflow_stage' => 'recommendation_created',
        ]);

        // Filter by PAASCU
        $response = $this->actingAs($this->admin)->get(route('compliance.export', ['body' => 'PAASCU', 'format' => 'csv']));
        $content = $response->streamedContent();

        $this->assertStringContainsString('PAASCU Task 1', $content);
        $this->assertStringNotContainsString('PACUCOA Task 2', $content);
    }

    public function test_dean_export_is_scoped_to_their_college()
    {
        ComplianceRecord::create([
            'title' => 'Computing Faculty Task',
            'status' => 'Pending',
            'accrediting_body' => 'PAASCU',
            'program_id' => $this->program1->program_id, // SOC
        ]);

        ComplianceRecord::create([
            'title' => 'Business Curriculum Task',
            'status' => 'Pending',
            'accrediting_body' => 'PAASCU',
            'program_id' => $this->program2->program_id, // SBA
        ]);

        $response = $this->actingAs($this->dean)->get(route('compliance.export', ['format' => 'csv']));
        $content = $response->streamedContent();

        $this->assertStringContainsString('Computing Faculty Task', $content);
        $this->assertStringNotContainsString('Business Curriculum Task', $content);
    }
}
