<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use App\Mail\PasswordResetMail;
use Tests\TestCase;

class PasswordResetAndAdminHierarchyTest extends TestCase
{
    use RefreshDatabase;

    protected $admin;
    protected $qaoadmin;
    protected $dean;

    protected function setUp(): void
    {
        parent::setUp();

        // Seed basic users
        $this->admin = User::create([
            'name' => 'Primary Admin',
            'username' => 'admin',
            'usertype' => 'QA Admin',
            'email' => 'admin@hau.edu.ph',
            'password' => bcrypt('password'),
        ]);

        $this->qaoadmin = User::create([
            'name' => 'Secondary Admin',
            'username' => 'qaoadmin',
            'usertype' => 'QA Admin',
            'email' => 'qaoadmin@hau.edu.ph',
            'password' => bcrypt('password'),
        ]);

        $this->dean = User::create([
            'name' => 'Dean User',
            'username' => 'dean',
            'usertype' => 'Dean',
            'email' => 'dean@hau.edu.ph',
            'password' => bcrypt('password'),
        ]);
    }

    /**
     * Test request reset OTP sends email and saves OTP.
     */
    public function test_request_reset_sends_otp_and_saves_in_db()
    {
        Mail::fake();

        $response = $this->post(route('password.email'), [
            'email' => 'dean@hau.edu.ph',
        ]);

        $response->assertStatus(302);
        $response->assertRedirect(route('password.verify-otp'));
        $response->assertSessionHas('otp_email', 'dean@hau.edu.ph');

        // Verify OTP saved in DB
        $otpRecord = DB::table('password_reset_otps')->where('email', 'dean@hau.edu.ph')->first();
        $this->assertNotNull($otpRecord);
        $this->assertEquals(0, $otpRecord->attempts);

        // Verify Mail dispatched
        Mail::assertSent(PasswordResetMail::class, function ($mail) {
            return $mail->hasTo('dean@hau.edu.ph') &&
                   $mail->user->email === 'dean@hau.edu.ph';
        });
    }

    /**
     * Test OTP verification page rendering and validation.
     */
    public function test_verify_otp_page_renders_with_session()
    {
        $response = $this->withSession(['otp_email' => 'dean@hau.edu.ph'])->get(route('password.verify-otp'));
        $response->assertStatus(200);
        $response->assertViewIs('auth.verify-otp');
    }

    /**
     * Test successful OTP verification.
     */
    public function test_successful_otp_verification()
    {
        // Insert dummy OTP
        $rawOtp = '123456';
        DB::table('password_reset_otps')->insert([
            'email' => 'dean@hau.edu.ph',
            'otp' => Hash::make($rawOtp),
            'expires_at' => now()->addMinutes(10),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->withSession(['otp_email' => 'dean@hau.edu.ph'])
            ->post(route('password.verify-otp.post'), [
                'otp' => $rawOtp,
            ]);

        $response->assertRedirect(route('password.reset-form'));
        $response->assertSessionHas('otp_verified_email', 'dean@hau.edu.ph');
        $response->assertSessionMissing('otp_email');

        // Check OTP deleted from database
        $this->assertNull(DB::table('password_reset_otps')->where('email', 'dean@hau.edu.ph')->first());
    }

    /**
     * Test password can be reset after OTP verified.
     */
    public function test_password_can_be_reset_after_otp_verification()
    {
        $response = $this->withSession(['otp_verified_email' => 'dean@hau.edu.ph'])
            ->post(route('password.update'), [
                'password' => 'NewP@ssw0rd!',
                'password_confirmation' => 'NewP@ssw0rd!',
            ]);

        $response->assertRedirect(route('login'));
        $response->assertSessionHas('success');
        $response->assertSessionMissing('otp_verified_email');

        // Check password updated
        $this->dean->refresh();
        $this->assertTrue(Hash::check('NewP@ssw0rd!', $this->dean->password));
    }

    /**
     * Test primary admin (admin) can edit secondary admin (qaoadmin).
     */
    public function test_primary_admin_can_edit_secondary_admin()
    {
        $response = $this->actingAs($this->admin)
            ->withSession(['active_role' => 'QA Admin'])
            ->put(route('users.update', $this->qaoadmin->id), [
                'username' => 'qaoadmin_edited',
                'first_name' => 'QAO',
                'last_name' => 'Admin Edited',
                'email' => 'qaoadmin_edited@hau.edu.ph',
                'usertype' => 'QA Admin',
                'password' => 'NewP@ssw0rd2!',
            ]);

        $response->assertRedirect(route('users.index'));
        $response->assertSessionHas('success');

        $this->qaoadmin->refresh();
        $this->assertEquals('qaoadmin_edited', $this->qaoadmin->username);
        $this->assertTrue(Hash::check('NewP@ssw0rd2!', $this->qaoadmin->password));
    }

    /**
     * Test secondary admin (qaoadmin) cannot edit other admin.
     */
    public function test_secondary_admin_cannot_edit_other_admin()
    {
        $response = $this->actingAs($this->qaoadmin)
            ->withSession(['active_role' => 'QA Admin'])
            ->put(route('users.update', $this->admin->id), [
                'username' => 'admin_hacked',
                'first_name' => 'Hacked',
                'last_name' => 'Admin',
                'email' => 'admin_hacked@hau.edu.ph',
                'usertype' => 'QA Admin',
            ]);

        $response->assertRedirect(route('users.index'));
        $response->assertSessionHas('error');

        // Verify admin username remains untouched
        $this->admin->refresh();
        $this->assertEquals('admin', $this->admin->username);
    }

    /**
     * Test secondary admin (qaoadmin) cannot delete other admin.
     */
    public function test_secondary_admin_cannot_delete_other_admin()
    {
        $response = $this->actingAs($this->qaoadmin)
            ->withSession(['active_role' => 'QA Admin'])
            ->delete(route('users.destroy', $this->admin->id));

        $response->assertRedirect(route('users.index'));
        $response->assertSessionHas('error');

        $this->assertNotNull(User::find($this->admin->id));
    }

    /**
     * Test primary admin (admin) can delete secondary admin.
     */
    public function test_primary_admin_can_delete_secondary_admin()
    {
        $response = $this->actingAs($this->admin)
            ->withSession(['active_role' => 'QA Admin'])
            ->delete(route('users.destroy', $this->qaoadmin->id));

        $response->assertRedirect(route('users.index'));
        $response->assertSessionHas('success');

        $this->assertNull(User::find($this->qaoadmin->id));
    }
}
