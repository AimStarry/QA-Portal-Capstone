<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\College;
use App\Models\ResponsibleUnit;
use App\Models\Laboratory;
use App\Models\Program;

class ResponsibleUnitHierarchyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Create initial setup
        $college = College::create([
            'name' => 'School of Computing',
            'code' => 'SOC',
        ]);

        $this->admin = User::create([
            'name' => 'Admin User',
            'first_name' => 'Admin',
            'last_name' => 'User',
            'username' => 'admin',
            'email' => 'admin@hau.edu.ph',
            'password' => bcrypt('password'),
            'usertype' => 'QA Admin',
        ]);

        $this->staff = User::create([
            'name' => 'Dean SOC',
            'first_name' => 'Dean',
            'last_name' => 'SOC',
            'username' => 'deansoc',
            'email' => 'deansoc@hau.edu.ph',
            'password' => bcrypt('password'),
            'usertype' => 'Dean',
            'college_id' => $college->college_id,
        ]);

        // Seed Responsible Unit linked to college
        $this->unit = ResponsibleUnit::create([
            'name' => 'School of Computing',
            'code' => 'SOC',
            'college_id' => $college->college_id,
        ]);

        $this->staff->update(['responsible_unit_id' => $this->unit->responsible_unit_id]);

        // Seed Laboratory
        $this->lab = Laboratory::create([
            'name' => 'Ada Lovelace Computer Laboratory',
            'responsible_unit_id' => $this->unit->responsible_unit_id,
        ]);

        // Seed Academic Program
        $this->program = Program::create([
            'program_code' => 'BSCS',
            'program_name' => 'Bachelor of Science in Computer Science',
            'program_level' => 'Undergraduate',
            'college_id' => $college->college_id,
        ]);
    }

    public function test_non_admin_cannot_access_categories_settings()
    {
        $response = $this->actingAs($this->staff)->get(route('admin.categories.index'));
        $response->assertStatus(403);
    }

    public function test_admin_can_access_categories_settings()
    {
        $response = $this->actingAs($this->admin)->get(route('admin.categories.index'));
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'responsibleUnits',
            'laboratories'
        ]);
    }

    public function test_admin_can_create_responsible_unit()
    {
        $response = $this->actingAs($this->admin)->post(route('admin.categories.store-unit'), [
            'name' => 'Guidance and Counselling Office',
            'code' => 'GUIDANCE',
        ]);

        $response->assertRedirect(route('admin.categories.index'));
        $this->assertDatabaseHas('responsible_units', [
            'name' => 'Guidance and Counselling Office',
            'code' => 'GUIDANCE',
        ]);
    }

    public function test_admin_can_create_laboratory()
    {
        $response = $this->actingAs($this->admin)->post(route('admin.categories.store'), [
            'name' => 'Alan Turing Network Lab',
            'responsible_unit_id' => $this->unit->responsible_unit_id,
        ]);

        $response->assertRedirect(route('admin.categories.index'));
        $this->assertDatabaseHas('laboratories', [
            'name' => 'Alan Turing Network Lab',
            'responsible_unit_id' => $this->unit->responsible_unit_id,
        ]);
    }

    public function test_user_mapping_resolves_contact_details()
    {
        // Query the unit and resolve its first user's contact information
        $unitWithUsers = ResponsibleUnit::with('users')->find($this->unit->responsible_unit_id);
        $firstUser = $unitWithUsers->users->first();

        $this->assertNotNull($firstUser);
        $this->assertEquals('Dean SOC', $firstUser->name);
        $this->assertEquals('deansoc@hau.edu.ph', $firstUser->email);
    }

    public function test_storing_compliance_record_sets_relational_fields()
    {
        $response = $this->actingAs($this->admin)->post(route('compliance.store'), [
            'program_id' => $this->program->program_id,
            'title' => 'Submit network schema diagrams',
            'status' => 'Pending',
            'priority' => 'High',
            'responsible_unit_id' => $this->unit->responsible_unit_id,
            'categories' => ['Resurvey Feedback'],
            'accrediting_body' => 'PACUCOA',
            'school' => 'School of Computing',
            'recommendations' => ['Draw topological map'],
            'areas' => ['Area III'],
        ]);

        $response->assertRedirect(route('compliance.index'));
        $this->assertDatabaseHas('compliance_records', [
            'title' => 'Submit network schema diagrams',
            'responsible_unit_id' => $this->unit->responsible_unit_id,
            'responsible_unit' => 'School of Computing',
            'category' => 'Resurvey Feedback',
        ]);
    }
}
