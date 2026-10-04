<?php

namespace Tests\Feature;

use App\Models\UserSession;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithQc;
use Tests\TestCase;

class ConcurrentUsersTest extends TestCase
{
    use RefreshDatabase;
    use InteractsWithQc;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRbac();
    }

    /** Simulate a live session slot held by a user. */
    private function holdSlot($user, string $sessionId, int $idleMinutes = 0): UserSession
    {
        return UserSession::create([
            'user_id' => $user->id,
            'session_id' => $sessionId,
            'login_at' => now()->subMinutes($idleMinutes + 5),
            'last_activity_at' => now()->subMinutes($idleMinutes),
            'is_active' => true,
            'timeout_minutes' => 120,
        ]);
    }

    public function test_login_is_blocked_when_maximum_active_users_reached(): void
    {
        $this->setSetting('usage.max_active_users', '2');

        $userA = $this->makeUser('analyst', ['username' => 'slot.a']);
        $userB = $this->makeUser('analyst', ['username' => 'slot.b']);
        $userC = $this->makeUser('analyst', ['username' => 'slot.c']);

        $this->holdSlot($userA, 'sess-a');
        $this->holdSlot($userB, 'sess-b');

        $response = $this->loginViaForm($userC);

        $response->assertSessionHasErrors('username');
        $this->assertStringContainsString(
            'maximum number of active users',
            session('errors')->first()
        );

        // Blocked user is NOT authenticated and holds no slot.
        $this->assertGuest();
        $this->assertSame(0, UserSession::where('user_id', $userC->id)->count());

        // Existing users were not disturbed.
        $this->assertTrue((bool) UserSession::where('user_id', $userA->id)->first()->is_active);
    }

    public function test_slot_is_released_so_a_new_user_can_log_in(): void
    {
        $this->setSetting('usage.max_active_users', '2');

        $userA = $this->makeUser('analyst', ['username' => 'slot.d']);
        $userB = $this->makeUser('analyst', ['username' => 'slot.e']);
        $userC = $this->makeUser('analyst', ['username' => 'slot.f']);

        $this->holdSlot($userA, 'sess-d');
        $this->holdSlot($userB, 'sess-e');

        $this->loginViaForm($userC)->assertSessionHasErrors('username');

        // User A logs out → slot freed.
        UserSession::where('user_id', $userA->id)->update([
            'is_active' => false,
            'logout_at' => now(),
            'logout_reason' => 'logout',
        ]);

        $this->loginViaForm($userC)->assertRedirect('/dashboard');
    }

    public function test_user_with_the_only_slot_may_relogin_without_extra_capacity(): void
    {
        $this->setSetting('usage.max_active_users', '1');

        $userA = $this->makeUser('analyst', ['username' => 'slot.g']);
        $this->holdSlot($userA, 'sess-g');

        // Re-login by the same (already counted) user is allowed.
        $this->loginViaForm($userA)->assertRedirect('/dashboard');
    }

    public function test_stale_sessions_do_not_permanently_consume_slots(): void
    {
        $this->setSetting('usage.max_active_users', '1');
        $this->setSetting('session.max', '480');

        $idleUser = $this->makeUser('analyst', ['username' => 'slot.idle']);
        $newUser = $this->makeUser('analyst', ['username' => 'slot.new']);

        // Abandoned session: no logout, but idle far beyond any allowed timeout.
        $this->holdSlot($idleUser, 'sess-idle', 481);

        $this->loginViaForm($newUser)->assertRedirect('/dashboard');

        // The stale row was released.
        $this->assertFalse((bool) UserSession::where('user_id', $idleUser->id)->first()->is_active);
    }

    public function test_unlimited_setting_allows_login_despite_many_active_sessions(): void
    {
        $this->setSetting('usage.max_active_users', '0');

        // Five other users hold live sessions — far beyond any typical limit.
        foreach (range(1, 5) as $i) {
            $holder = $this->makeUser('analyst', ['username' => "slot.holder{$i}"]);
            $this->holdSlot($holder, "sess-holder-{$i}");
        }

        $newcomer = $this->makeUser('analyst', ['username' => 'slot.unlimited.new']);

        $this->loginViaForm($newcomer)->assertRedirect('/dashboard');

        $this->assertAuthenticatedAs($newcomer);
        $this->assertSame(1, \App\Models\LoginLog::where('status', 'SUCCESS')->count());
        // 5 holders + the newcomer are all counted as active users.
        $this->assertSame(6, (new \App\Services\SessionTracker)->activeCount());
    }

    public function test_active_user_count_uses_sessions_not_the_users_table(): void
    {
        $this->setSetting('usage.max_active_users', '10');

        // Five users exist but none has a live session → count must be 0.
        foreach (range(1, 5) as $i) {
            $this->makeUser('analyst', ['username' => "ghost.{$i}"]);
        }

        $this->assertSame(0, (new \App\Services\SessionTracker)->activeCount());
    }
}
