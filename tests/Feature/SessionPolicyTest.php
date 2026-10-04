<?php

namespace Tests\Feature;

use App\Models\UserSession;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithQc;
use Tests\TestCase;

class SessionPolicyTest extends TestCase
{
    use RefreshDatabase;
    use InteractsWithQc;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRbac();
    }

    public function test_login_page_offers_stay_active_choices(): void
    {
        $this->setSetting('session.options', '30,60,120,240,480');
        $this->setSetting('session.default', '120');

        $this->get('/login')
            ->assertStatus(200)
            ->assertSee('Stay active for')
            ->assertSee('30 Minutes')
            ->assertSee('8 Hours');
    }

    public function test_user_selected_timeout_is_stored_with_the_session(): void
    {
        $user = $this->makeUser('analyst');

        $this->loginViaForm($user, ['session_timeout' => 30])->assertRedirect('/dashboard');

        $record = UserSession::where('user_id', $user->id)->where('is_active', true)->first();

        $this->assertNotNull($record);
        $this->assertSame(30, (int) $record->timeout_minutes);
        $this->assertNotNull($record->login_at);
    }

    public function test_timeout_beyond_admin_maximum_falls_back_to_default(): void
    {
        $this->setSetting('session.options', '30,60,120');
        $this->setSetting('session.default', '60');
        $this->setSetting('session.max', '120');

        $user = $this->makeUser('analyst');

        // 480 is not an allowed option (and above the max) → configured default applies.
        $this->loginViaForm($user, ['session_timeout' => 480])->assertRedirect('/dashboard');

        $record = UserSession::where('user_id', $user->id)->where('is_active', true)->first();

        $this->assertSame(60, (int) $record->timeout_minutes);
    }

    public function test_inactivity_expires_the_session_server_side(): void
    {
        $user = $this->makeUser('analyst');

        $this->loginViaForm($user, ['session_timeout' => 30])->assertRedirect('/dashboard');

        // User is working normally...
        $this->get('/dashboard')->assertStatus(200);

        // ...then goes idle beyond the chosen duration.
        UserSession::where('user_id', $user->id)->update([
            'last_activity_at' => now()->subMinutes(31),
        ]);

        $response = $this->get('/dashboard');

        $response->assertRedirect('/login');

        $record = UserSession::where('user_id', $user->id)->first();
        $this->assertFalse((bool) $record->is_active);
        $this->assertSame('inactivity', $record->logout_reason);

        // The login screen explains what happened without leaking internals.
        $this->get('/login')
            ->assertStatus(200)
            ->assertSee('expired because of inactivity');
    }

    public function test_activity_within_the_window_keeps_the_session_alive(): void
    {
        $user = $this->makeUser('analyst');

        $this->loginViaForm($user, ['session_timeout' => 60])->assertRedirect('/dashboard');

        UserSession::where('user_id', $user->id)->update([
            'last_activity_at' => now()->subMinutes(30),
        ]);

        $this->get('/dashboard')->assertStatus(200);

        $record = UserSession::where('user_id', $user->id)->first();
        $this->assertTrue((bool) $record->is_active);
        $this->assertTrue($record->last_activity_at->greaterThanOrEqualTo(now()->subSeconds(10)));
    }

    public function test_stored_timeout_cannot_exceed_the_admin_maximum_at_runtime(): void
    {
        $user = $this->makeUser('analyst');

        $this->loginViaForm($user, ['session_timeout' => 480])->assertRedirect('/dashboard');

        // Admin tightens the maximum after login (stored row keeps the old value).
        $this->setSetting('session.max', '60');
        $this->setSetting('session.options', '30,60');

        UserSession::where('user_id', $user->id)->update([
            'last_activity_at' => now()->subMinutes(61),
            'timeout_minutes' => 480,
        ]);

        // Middleware clamps to the admin maximum → session expires.
        $this->get('/dashboard')->assertRedirect('/login');
    }

    public function test_logout_ends_the_tracked_session(): void
    {
        $user = $this->makeUser('analyst');

        $this->loginViaForm($user)->assertRedirect('/dashboard');

        $this->post('/logout')->assertRedirect('/login');

        $record = UserSession::where('user_id', $user->id)->first();
        $this->assertFalse((bool) $record->is_active);
        $this->assertSame('logout', $record->logout_reason);
        $this->assertNotNull($record->logout_at);
    }

    public function test_remember_me_does_not_bypass_the_inactivity_policy(): void
    {
        $user = $this->makeUser('analyst');

        $this->loginViaForm($user, ['session_timeout' => 30, 'remember' => 1])
            ->assertRedirect('/dashboard');

        UserSession::where('user_id', $user->id)->update([
            'last_activity_at' => now()->subMinutes(45),
        ]);

        // Even with a remember-me cookie, the same idle session is killed server-side.
        $this->get('/dashboard')->assertRedirect('/login');

        $record = UserSession::where('user_id', $user->id)->first();
        $this->assertFalse((bool) $record->is_active);
    }
}
