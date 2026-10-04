<?php

namespace Tests\Feature;

use App\Models\AppNotification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\Concerns\InteractsWithQc;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;
    use InteractsWithQc;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRbac();
    }

    public function test_registration_page_loads_when_enabled(): void
    {
        $this->setSetting('registration.allow_registration', '1');

        $this->get('/register')
            ->assertStatus(200)
            ->assertSee('Create your account');
    }

    public function test_registration_page_is_blocked_when_disabled(): void
    {
        $this->setSetting('registration.allow_registration', '0');

        $response = $this->get('/register');

        $response->assertRedirect('/login');
        $response->assertSessionHasErrors('username');
    }

    public function test_registration_post_is_blocked_when_disabled(): void
    {
        $this->setSetting('registration.allow_registration', '0');

        $before = User::count();

        $response = $this->post('/register', [
            'name' => 'Blocked User',
            'username' => 'blocked.user',
            'email' => 'blocked@test.local',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertRedirect('/login');
        $response->assertSessionHasErrors('username');
        $this->assertSame($before, User::count());
    }

    public function test_valid_registration_creates_pending_user_requiring_approval(): void
    {
        $this->setSetting('registration.allow_registration', '1');
        $this->setSetting('registration.require_approval', '1');
        $this->setSetting('registration.default_role', 'analyst');

        // There is someone to notify (admins receive the registration event).
        $this->makeUser('qc_admin');

        $response = $this->post('/register', [
            'name' => 'Rahul Verma',
            'username' => 'rahul_verma',
            'email' => 'rahul@test.local',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertRedirect('/login');
        $response->assertSessionHas('success');

        $user = User::where('username', 'rahul_verma')->firstOrFail();

        $this->assertSame('pending', $user->status);
        $this->assertNull($user->approved_at);
        $this->assertTrue($user->hasRole('analyst'));
        $this->assertTrue(Hash::check('password123', $user->password));
        $this->assertNotSame('password123', $user->password);

        // Pending users cannot sign in.
        $login = $this->loginViaForm($user);
        $login->assertSessionHasErrors('username');
        $this->assertGuest();

        // Admins were notified of the registration.
        $this->assertTrue(
            AppNotification::where('type', 'user.registered')->exists()
        );
    }

    public function test_registration_is_active_immediately_when_approval_disabled(): void
    {
        $this->setSetting('registration.allow_registration', '1');
        $this->setSetting('registration.require_approval', '0');

        $this->post('/register', [
            'name' => 'Instant User',
            'username' => 'instant_user',
            'email' => 'instant@test.local',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])->assertRedirect('/login');

        $user = User::where('username', 'instant_user')->firstOrFail();

        $this->assertSame('active', $user->status);
        $this->assertNotNull($user->approved_at);

        $this->post('/login', [
            'username' => 'instant_user',
            'password' => 'password123',
        ])->assertRedirect('/dashboard');
    }

    public function test_duplicate_username_and_email_are_rejected(): void
    {
        $this->setSetting('registration.allow_registration', '1');

        $this->makeUser('analyst', ['username' => 'taken_name', 'email' => 'taken@test.local']);

        $this->post('/register', [
            'name' => 'Copy Cat',
            'username' => 'taken_name',
            'email' => 'other@test.local',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])->assertSessionHasErrors('username');

        $this->post('/register', [
            'name' => 'Copy Cat',
            'username' => 'other_name',
            'email' => 'taken@test.local',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])->assertSessionHasErrors('email');
    }

    public function test_password_validation_requires_confirmation_and_minimum_length(): void
    {
        $this->setSetting('registration.allow_registration', '1');

        $this->post('/register', [
            'name' => 'Weak Password',
            'username' => 'weak_pass',
            'email' => 'weak@test.local',
            'password' => 'short',
            'password_confirmation' => 'short',
        ])->assertSessionHasErrors('password');

        $this->post('/register', [
            'name' => 'Mismatch',
            'username' => 'mismatch_user',
            'email' => 'mismatch@test.local',
            'password' => 'password123',
            'password_confirmation' => 'different123',
        ])->assertSessionHasErrors('password');
    }

    public function test_registrant_cannot_choose_own_role_or_status(): void
    {
        $this->setSetting('registration.allow_registration', '1');
        $this->setSetting('registration.require_approval', '1');

        $before = User::count();

        $this->post('/register', [
            'name' => 'Sneaky User',
            'username' => 'sneaky_user',
            'email' => 'sneaky@test.local',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'status' => 'active',
            'role_id' => 1,
            'roles' => ['super_admin'],
        ]);

        $user = User::where('username', 'sneaky_user')->firstOrFail();

        $this->assertSame('pending', $user->status);
        $this->assertFalse($user->hasRole('super_admin'));
        $this->assertSame($before + 1, User::count());
    }

    public function test_unauthorized_users_cannot_approve_accounts(): void
    {
        $analyst = $this->makeUser('analyst');
        $pending = $this->makeUser('analyst', ['username' => 'pending.one', 'status' => 'pending']);

        $this->actingAs($analyst)
            ->post("/users/{$pending->id}/approve")
            ->assertForbidden();

        $this->assertSame('pending', $pending->fresh()->status);
    }

    public function test_admin_can_approve_and_reject_pending_users(): void
    {
        $admin = $this->makeUser('qc_admin');
        $pending = $this->makeUser('analyst', ['username' => 'pending.two', 'status' => 'pending']);
        $other = $this->makeUser('analyst', ['username' => 'pending.three', 'status' => 'pending']);

        $this->actingAs($admin)
            ->post("/users/{$pending->id}/approve")
            ->assertRedirect();

        $pending->refresh();
        $this->assertSame('active', $pending->status);
        $this->assertNotNull($pending->approved_at);
        $this->assertTrue(AppNotification::where('user_id', $pending->id)->where('type', 'user.approved')->exists());

        $this->actingAs($admin)
            ->post("/users/{$other->id}/reject")
            ->assertRedirect();

        $other->refresh();
        $this->assertSame('inactive', $other->status);
        $this->assertNull($other->approved_at);
    }
}
