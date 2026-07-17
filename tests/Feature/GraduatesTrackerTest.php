<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\College;
use App\Models\Program;
use App\Models\GraduateRecord;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GraduatesTrackerTest extends TestCase
{
    use RefreshDatabase;

    protected $admin;
    protected $dean;
    protected $college1;
    protected $college2;
    protected $program1;
    protected $program2;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'usertype' => 'QA Admin',
            'username' => 'qa_admin_test',
        ]);

        $this->college1 = College::create([
            'name' => 'School of Computing',
            'code' => 'SOC',
        ]);

        $this->college2 = College::create([
            'name' => 'School of Business and Accountancy',
            'code' => 'SBA',
        ]);

        $this->dean = User::factory()->create([
            'usertype' => 'Dean',
            'username' => 'dean_test',
            'college_id' => $this->college1->college_id,
        ]);

        $this->program1 = Program::create([
            'program_code' => 'BSCS',
            'program_name' => 'Bachelor of Science in Computer Science',
            'program_level' => 'Undergraduate',
            'college_id' => $this->college1->college_id,
        ]);

        $this->program2 = Program::create([
            'program_code' => 'BSBA',
            'program_name' => 'Bachelor of Science in Business Administration',
            'program_level' => 'Undergraduate',
            'college_id' => $this->college2->college_id,
        ]);

        // Create some graduate records
        GraduateRecord::create([
            'program_id' => $this->program1->program_id,
            'school_year' => '2025-2026',
            'term' => '1st Semester',
            'graduates_count' => 150,
        ]);

        GraduateRecord::create([
            'program_id' => $this->program2->program_id,
            'school_year' => '2025-2026',
            'term' => '1st Semester',
            'graduates_count' => 300,
        ]);
    }

    public function test_admin_can_access_graduates_tracker_and_see_all_departments()
    {
        $response = $this->actingAs($this->admin)->get(route('graduates.index'));

        $response->assertStatus(200);
        $response->assertSee('SOC');
        $response->assertSee('SBA');
        $response->assertSee('BSCS');
        $response->assertSee('BSBA');
        $response->assertSee('450'); // sum of graduates count (150 + 300)
    }

    public function test_dean_is_restricted_to_own_college_graduates()
    {
        $response = $this->actingAs($this->dean)->get(route('graduates.index'));

        $response->assertStatus(200);
        $response->assertSee('SOC');
        $response->assertDontSee('SBA');
        $response->assertSee('BSCS');
        $response->assertDontSee('BSBA');
        $response->assertSee('150'); // only college 1 graduates
        $response->assertDontSee('450');
    }

    public function test_admin_can_log_graduate_record()
    {
        $response = $this->actingAs($this->admin)->post(route('graduates.store'), [
            'program_id' => $this->program1->program_id,
            'school_year' => '2025-2026',
            'term' => '2nd Semester',
            'graduates_count' => 120,
        ]);

        $response->assertRedirect(route('graduates.index'));
        $this->assertDatabaseHas('graduate_records', [
            'program_id' => $this->program1->program_id,
            'graduates_count' => 120,
        ]);
    }

    public function test_dean_cannot_log_graduate_record()
    {
        $response = $this->actingAs($this->dean)->post(route('graduates.store'), [
            'program_id' => $this->program1->program_id,
            'school_year' => '2025-2026',
            'term' => '2nd Semester',
            'graduates_count' => 120,
        ]);

        $response->assertStatus(403);
    }
}
