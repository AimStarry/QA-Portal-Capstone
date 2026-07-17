<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\College;
use App\Models\Unit;
use App\Models\Program;
use App\Models\ComplianceRecord;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthAndScopingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Run seeders or create required data
        $this->collegeSoc = College::create([
            'name' => 'School of Computing',
            'code' => 'SOC',
        ]);

        $this->collegeSea = College::create([
            'name' => 'School of Engineering and Architecture',
            'code' => 'SEA',
        ]);

        $this->unitQao = Unit::create([
            'name' => 'Quality Assurance Office',
            'code' => 'QAO',
        ]);

        $this->ruQao = \App\Models\ResponsibleUnit::create([
            'name' => 'Quality Assurance Office',
            'code' => 'QAO',
            'unit_id' => $this->unitQao->unit_id,
        ]);

        $this->ruSoc = \App\Models\ResponsibleUnit::create([
            'name' => 'School of Computing',
            'code' => 'SOC',
            'college_id' => $this->collegeSoc->college_id,
        ]);

        // Seed users
        $this->admin = User::create([
            'name' => 'Admin User',
            'username' => 'admin',
            'usertype' => 'QA Admin',
            'email' => 'admin@hau.edu.ph',
            'password' => bcrypt('password'),
        ]);

        $this->deanSoc = User::create([
            'name' => 'Dean Computing',
            'username' => 'deansoc',
            'usertype' => 'Dean',
            'email' => 'deansoc@hau.edu.ph',
            'password' => bcrypt('password'),
            'college_id' => $this->collegeSoc->college_id,
            'responsible_unit_id' => $this->ruSoc->responsible_unit_id,
        ]);

        $this->headQao = User::create([
            'name' => 'Head QAO',
            'username' => 'headqao',
            'usertype' => 'Head of Unit',
            'email' => 'headqao@hau.edu.ph',
            'password' => bcrypt('password'),
            'unit_id' => $this->unitQao->unit_id,
            'responsible_unit_id' => $this->ruQao->responsible_unit_id,
        ]);

        // Create programs
        $this->bscs = Program::create([
            'program_code' => 'BSCS',
            'program_name' => 'Bachelor of Science in Computer Science',
            'college_id' => $this->collegeSoc->college_id,
            'department' => 'Computer Science Department',
            'program_level' => 'Undergraduate',
            'is_accreditable' => true,
        ]);

        $this->bsce = Program::create([
            'program_code' => 'BSCE',
            'program_name' => 'Bachelor of Science in Civil Engineering',
            'college_id' => $this->collegeSea->college_id,
            'department' => 'Civil Engineering Department',
            'program_level' => 'Undergraduate',
            'is_accreditable' => true,
        ]);
    }

    /**
     * Test guest is redirected to login.
     */
    public function test_guest_is_redirected_to_login()
    {
        $response = $this->get('/dashboard');
        $response->assertRedirect('/login');
    }

    /**
     * Test login authentication with username.
     */
    public function test_user_can_login_with_username()
    {
        $response = $this->post('/login', [
            'username' => 'deansoc',
            'password' => 'password',
        ]);

        $response->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($this->deanSoc);
    }

    /**
     * Test Dean program scoping.
     */
    public function test_dean_can_only_view_own_programs()
    {
        $response = $this->actingAs($this->deanSoc)->get('/programs');
        $response->assertStatus(200);

        // Dean SOC should see BSCS but not BSCE
        $response->assertSee('BSCS');
        $response->assertDontSee('BSCE');
    }

    public function test_head_of_unit_cannot_access_programs()
    {
        $response = $this->actingAs($this->headQao)->get('/programs');
        $response->assertStatus(200);
        $response->assertDontSee('Academic Programs Directory');
        $response->assertSee('Offices');
        $response->assertSee('Units');
    }

    /**
     * Test contact person auto assignment logic.
     */
    public function test_contact_person_is_auto_assigned_to_matching_unit_or_dean()
    {
        // 1. Create a compliance task assigned to QAO, contact empty
        $response = $this->actingAs($this->admin)->post('/compliance', [
            'program_id' => $this->bscs->program_id,
            'title' => 'Sample Task',
            'status' => 'Pending',
            'accrediting_body' => 'PAASCU',
            'school' => 'School of Computing',
            'responsible_unit' => 'Quality Assurance Office',
            'responsible_unit_id' => $this->ruQao->responsible_unit_id,
            'recommendations' => ['Rec 1'],
            'categories' => ['Category 1'],
            'areas' => ['Area 1'],
        ]);

        $response->assertRedirect();
        
        $record = ComplianceRecord::where('title', 'Sample Task')->first();
        $this->assertNotNull($record);
        
        // Since responsible_unit is 'Quality Assurance Office', it should auto-assign Head QAO!
        $this->assertEquals('Head QAO', $record->contact_person);
        $this->assertEquals('headqao@hau.edu.ph', $record->contact_email);
    }

    /**
     * Test admin can create a user with a custom email.
     */
    public function test_admin_can_create_user_with_custom_email()
    {
        $response = $this->actingAs($this->admin)->post('/users', [
            'username' => 'newuser',
            'first_name' => 'New',
            'last_name' => 'User',
            'email' => 'newuser_custom@hau.edu.ph',
            'password' => 'SecurePassword123!',
            'usertype' => 'Dean',
            'college_id' => $this->collegeSoc->college_id,
        ]);

        $response->assertRedirect('/users');
        $this->assertDatabaseHas('users', [
            'username' => 'newuser',
            'email' => 'newuser_custom@hau.edu.ph',
        ]);
        // Assert creation fails with a weak password (e.g. only letters, length < 8)
        $responseWeak = $this->actingAs($this->admin)->post('/users', [
            'username' => 'weakuser',
            'first_name' => 'Weak',
            'last_name' => 'User',
            'email' => 'weakuser@hau.edu.ph',
            'password' => 'weak',
            'usertype' => 'Dean',
            'college_id' => $this->collegeSoc->college_id,
        ]);
        $responseWeak->assertSessionHasErrors(['password']);
    }

    /**
     * Test admin can update a user with a custom email.
     */
    public function test_admin_can_update_user_email()
    {
        $response = $this->actingAs($this->admin)->put("/users/{$this->deanSoc->id}", [
            'username' => 'deansoc',
            'first_name' => 'Dean Computing',
            'last_name' => 'Updated',
            'email' => 'deansoc_new@hau.edu.ph',
            'usertype' => 'Dean',
            'college_id' => $this->collegeSoc->college_id,
        ]);

        $response->assertRedirect('/users');
        $this->assertDatabaseHas('users', [
            'id' => $this->deanSoc->id,
            'name' => 'Dean Computing Updated',
            'email' => 'deansoc_new@hau.edu.ph',
        ]);
    }

    /**
     * Test admin can assign a new Dean to a College and unassign the old one.
     */
    public function test_admin_can_assign_dean_to_college()
    {
        // Create another dean
        $otherDean = User::create([
            'username' => 'otherdean',
            'first_name' => 'Other',
            'last_name' => 'Dean',
            'usertype' => 'Dean',
            'email' => 'otherdean@hau.edu.ph',
            'password' => bcrypt('password'),
            'college_id' => null,
        ]);

        // Submit update on collegeSoc to assign otherDean as its Dean
        $response = $this->actingAs($this->admin)->put("/colleges/{$this->collegeSoc->college_id}", [
            'name' => 'School of Computing New Name',
            'code' => 'SOC',
            'dean_id' => $otherDean->id,
        ]);

        $response->assertRedirect();
        
        // Assert otherDean now has college_id set
        $otherDean->refresh();
        $this->assertEquals($this->collegeSoc->college_id, $otherDean->college_id);

        // Assert old deanSoc has college_id cleared (unassigned)
        $this->deanSoc->refresh();
        $this->assertNull($this->deanSoc->college_id);
    }

    /**
     * Test admin can assign a new Head of Unit to a Unit and unassign the old one.
     */
    public function test_admin_can_assign_head_to_unit()
    {
        // Create another unit head
        $otherHead = User::create([
            'username' => 'otherhead',
            'first_name' => 'Other',
            'last_name' => 'Head',
            'usertype' => 'Head of Unit',
            'email' => 'otherhead@hau.edu.ph',
            'password' => bcrypt('password'),
            'unit_id' => null,
        ]);

        // Submit update on unitQao to assign otherHead as its Head
        $response = $this->actingAs($this->admin)->put("/units/{$this->unitQao->id}", [
            'name' => 'Quality Assurance Office New Name',
            'code' => 'QAO',
            'head_id' => $otherHead->id,
        ]);

        $response->assertRedirect();

        // Assert otherHead now has unit_id set
        $otherHead->refresh();
        $this->assertEquals($this->unitQao->id, $otherHead->unit_id);

        // Assert old headQao has unit_id cleared (unassigned)
        $this->headQao->refresh();
        $this->assertNull($this->headQao->unit_id);
    }

    /**
     * Test Principal role data scoping.
     */
    public function test_principal_role_is_scoped_to_assigned_college()
    {
        // Create a Basic Ed college and a program
        $collegeBed = College::create([
            'name' => 'Basic Education',
            'code' => 'BED',
        ]);
        $programJhs = Program::create([
            'program_code' => 'JHS',
            'program_name' => 'Junior High School',
            'college_id' => $collegeBed->id,
            'department' => 'Basic Ed Department',
            'program_level' => 'Junior High School',
            'is_accreditable' => true,
        ]);

        // Create a principal user assigned to BED
        $principal = User::create([
            'username' => 'principalbed',
            'first_name' => 'Principal',
            'last_name' => 'BasicEd',
            'usertype' => 'Principal',
            'email' => 'principalbed@hau.edu.ph',
            'password' => bcrypt('password'),
            'college_id' => $collegeBed->id,
        ]);

        // Access programs index as Principal
        $response = $this->actingAs($principal)->get('/programs');
        $response->assertStatus(200);

        // Verify they see the JHS program
        $response->assertSee('JHS');
        // Verify they don't see the BSCS program (which belongs to SOC) or BSCE (which belongs to SEA) in the table directory
        $response->assertDontSee('data-code="BSCS"');
        $response->assertDontSee('data-code="BSCE"');
    }

    /**
     * Test that Dean can manage their own programs but not others.
     */
    public function test_dean_can_manage_own_programs_but_not_others()
    {
        // 1. Dean SOC trying to update a program in SOC (BSCS) -> Allowed
        $response = $this->actingAs($this->deanSoc)->put("/programs/{$this->bscs->program_id}", [
            'program_code' => 'BSCS',
            'program_name' => 'Bachelor of Science in Computer Science New Title',
            'college_id' => $this->collegeSoc->college_id,
            'program_level' => 'Undergraduate',
            'is_accreditable' => true,
        ]);
        $response->assertRedirect();
        $this->bscs->refresh();
        $this->assertEquals('Bachelor of Science in Computer Science New Title', $this->bscs->program_name);

        // 2. Dean SOC trying to update a program in SEA (BSCE) -> Forbidden
        $response = $this->actingAs($this->deanSoc)->put("/programs/{$this->bsce->program_id}", [
            'program_code' => 'BSCE',
            'program_name' => 'Bachelor of Science in Civil Engineering Hack',
            'college_id' => $this->collegeSea->college_id,
            'program_level' => 'Undergraduate',
            'is_accreditable' => true,
        ]);
        $response->assertStatus(403);

        // 3. Dean SOC trying to update a program to assign it to another college -> Forbidden
        $response = $this->actingAs($this->deanSoc)->put("/programs/{$this->bscs->program_id}", [
            'program_code' => 'BSCS',
            'program_name' => 'Bachelor of Science in Computer Science Hack',
            'college_id' => $this->collegeSea->college_id, // Attempting to transfer to SEA
            'program_level' => 'Undergraduate',
            'is_accreditable' => true,
        ]);
        $response->assertStatus(403);

        // 4. Dean SOC trying to delete a program in SOC (BSCS) -> Allowed
        $response = $this->actingAs($this->deanSoc)->delete("/programs/{$this->bscs->program_id}");
        $response->assertRedirect();
        $this->assertNull(Program::find($this->bscs->program_id));
    }

    /**
     * Test unit management scoping rules.
     */
    public function test_unit_management_scoping_rules()
    {
        // 1. Dean SOC trying to create a unit -> Forbidden
        $response = $this->actingAs($this->deanSoc)->post('/units', [
            'name' => 'Illegal Hack Unit',
            'code' => 'IHU',
        ]);
        $response->assertStatus(403);

        // 2. Head of Unit trying to create a unit -> Allowed
        $response = $this->actingAs($this->headQao)->post('/units', [
            'name' => 'Valid Legal Unit',
            'code' => 'VLU',
        ]);
        $response->assertRedirect();
        $this->assertTrue(\App\Models\Unit::where('code', 'VLU')->exists());

        // 3. Dean SOC trying to update a unit -> Forbidden
        $response = $this->actingAs($this->deanSoc)->put("/units/{$this->unitQao->id}", [
            'name' => 'Illegal Update Unit',
            'code' => 'IUU',
        ]);
        $response->assertStatus(403);

        // 4. Head of Unit trying to update a unit -> Allowed
        $response = $this->actingAs($this->headQao)->put("/units/{$this->unitQao->id}", [
            'name' => 'Quality Assurance Office Updated Name',
            'code' => 'QAO',
        ]);
        $response->assertRedirect();
        $this->unitQao->refresh();
        $this->assertEquals('Quality Assurance Office Updated Name', $this->unitQao->name);
    }
}
