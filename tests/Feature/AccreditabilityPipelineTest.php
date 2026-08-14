<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\College;
use App\Models\Program;
use App\Models\ComplianceRecord;
use App\Models\Accreditation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccreditabilityPipelineTest extends TestCase
{
    use RefreshDatabase;

    protected $ru;

    protected function setUp(): void
    {
        parent::setUp();

        $this->college = College::create([
            'name' => 'School of Computing',
            'code' => 'SOC',
        ]);

        $this->ru = \App\Models\ResponsibleUnit::create([
            'name' => 'Quality Assurance Office',
            'code' => 'QAO',
        ]);

        $this->admin = User::create([
            'name' => 'Admin User',
            'username' => 'admin',
            'usertype' => 'QA Admin',
            'email' => 'admin@hau.edu.ph',
            'password' => bcrypt('password'),
        ]);

        $this->program = Program::create([
            'program_code' => 'BSCS',
            'program_name' => 'Bachelor of Science in Computer Science',
            'college_id' => $this->college->college_id,
            'department' => 'CS Department',
            'program_level' => 'Undergraduate',
            'is_accreditable' => true,
        ]);
    }

    /**
     * Test program accreditable status is independent of compliance records.
     */
    public function test_program_remains_accreditable_on_deficient_compliance_record()
    {
        $this->assertTrue($this->program->is_accreditable);

        // Create a deficient compliance record (status: Pending)
        $this->actingAs($this->admin)->post('/compliance', [
            'program_id' => $this->program->program_id,
            'title' => 'Sample Deficient Record',
            'status' => 'Pending',
            'accrediting_body' => 'PAASCU',
            'school' => 'School of Computing',
            'responsible_unit' => 'Quality Assurance Office',
            'responsible_unit_id' => $this->ru->responsible_unit_id,
            'recommendations' => ['Rec 1'],
            'categories' => ['Category 1'],
            'areas' => ['Area 1'],
        ]);

        $this->program->refresh();
        $this->assertTrue($this->program->is_accreditable);
    }

    /**
     * Test AccreditationController blocks creating an accreditation for a non-accreditable program.
     */
    public function test_blocks_creating_acreditation_for_non_accreditable_program()
    {
        // Mark program as non-accreditable
        $this->program->update(['is_accreditable' => false]);

        $response = $this->actingAs($this->admin)->post('/accreditations', [
            'program_id' => $this->program->program_id,
            'accrediting_body' => 'PAASCU',
            'type' => 'Local',
            'level_or_tier' => 'Level 1',
            'status' => 'Active',
            'last_visit' => '2026-01-01',
            'expiry_date' => '2030-01-01',
        ]);

        $response->assertSessionHasErrors(['program_id']);
        $this->assertDatabaseMissing('accreditations', [
            'program_id' => $this->program->program_id,
            'level_or_tier' => 'Level 1',
        ]);
    }
}
