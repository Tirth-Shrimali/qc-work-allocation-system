<?php

namespace Tests\Feature;

use App\Models\LicenseHistory;
use App\Models\UserSession;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithQc;
use Tests\TestCase;

class LicenseTest extends TestCase
{
    use RefreshDatabase;
    use InteractsWithQc;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRbac();
    }

    private function configureLicense(string $expiry, int $grace = 0, string $behavior = 'block'): void
    {
        $this->setSetting('license.activation_date', now()->subDays(30)->toDateString());
        $this->setSetting('license.expiry_date', $expiry);
        $this->setSetting('license.grace_days', (string) $grace);
        $this->setSetting('license.behavior', $behavior);
        $this->setSetting('license.suspended', '0');
    }

    /* --------------------------------------------------------- states */

    public function test_application_works_normally_while_active(): void
    {
        $this->configureLicense(now()->addDays(60)->toDateString());

        $analyst = $this->makeUser('analyst');

        $this->actingAs($analyst)->get('/dashboard')->assertStatus(200);
    }

    public function test_expired_license_blocks_the_application_with_a_professional_page(): void
    {
        $this->configureLicense(now()->subDays(10)->toDateString(), 0, 'block');

        $analyst = $this->makeUser('analyst');

        $response = $this->actingAs($analyst)->get('/dashboard');

        $response->assertStatus(503);
        $response->assertSee('Application Validity Expired');
        $response->assertSee('Please contact your system administrator');
        // No internals leak to the user.
        $response->assertDontSee('Exception');
        $response->assertDontSee('SELECT');
        $response->assertDontSee('.php');
    }

    public function test_privileged_administrator_can_still_access_to_renew(): void
    {
        $this->configureLicense(now()->subDays(10)->toDateString(), 0, 'block');

        $admin = $this->makeUser('qc_admin');

        $this->actingAs($admin)->get('/dashboard')->assertStatus(200);
        $this->actingAs($admin)->get('/settings/license')->assertStatus(200);
    }

    public function test_grace_period_allows_access_with_a_clear_banner(): void
    {
        $this->configureLicense(now()->subDays(2)->toDateString(), 7, 'block');

        $analyst = $this->makeUser('analyst');

        $this->actingAs($analyst)
            ->get('/dashboard')
            ->assertStatus(200)
            ->assertSee('License Expired — Grace Period Active');
    }

    public function test_after_grace_the_configured_behaviour_applies(): void
    {
        $this->configureLicense(now()->subDays(10)->toDateString(), 7, 'block');

        $analyst = $this->makeUser('analyst');

        $this->actingAs($analyst)->get('/dashboard')->assertStatus(503);
    }

    public function test_suspended_application_is_blocked_even_inside_grace(): void
    {
        $this->configureLicense(now()->subDays(1)->toDateString(), 30, 'block');
        $this->setSetting('license.suspended', '1');

        $analyst = $this->makeUser('analyst');

        $this->actingAs($analyst)->get('/dashboard')->assertStatus(503)->assertSee('Suspended');
    }

    /* ------------------------------------------------------ behaviours */

    public function test_readonly_behaviour_allows_gets_but_blocks_changes(): void
    {
        $this->configureLicense(now()->subDays(10)->toDateString(), 0, 'readonly');

        $analyst = $this->makeUser('analyst');

        $this->actingAs($analyst)->get('/dashboard')->assertStatus(200);

        $this->actingAs($analyst)->post('/notifications/read-all')->assertStatus(503);
    }

    public function test_restrict_login_behaviour_blocks_the_login_screen_for_guests(): void
    {
        $this->configureLicense(now()->subDays(10)->toDateString(), 0, 'restrict_login');

        $this->get('/login')->assertStatus(503)->assertSee('Application Validity Expired');

        // An authorized administrator is not restricted at all.
        $admin = $this->makeUser('qc_admin');
        $this->actingAs($admin)->get('/dashboard')->assertStatus(200);
    }

    /* ------------------------------------------------------ extension */

    public function test_admin_can_extend_validity_by_days_with_history(): void
    {
        $this->configureLicense(now()->subDays(10)->toDateString(), 0, 'block');

        $admin = $this->makeUser('qc_admin');
        $newExpiry = now()->startOfDay()->addDays(90)->toDateString();

        $this->actingAs($admin)->post('/settings/license/extend', [
            'mode' => 'days',
            'days' => 90,
            'reason' => 'Company requested project extension',
        ])->assertRedirect(route('settings.section', 'license'));

        $history = LicenseHistory::latest('id')->first();

        $this->assertNotNull($history);
        $this->assertSame('extended', $history->action);
        $this->assertSame(now()->subDays(10)->toDateString(), $history->previous_expiry?->toDateString());
        $this->assertSame($newExpiry, $history->new_expiry?->toDateString());
        $this->assertSame($admin->id, $history->user_id);
        $this->assertSame('Company requested project extension', $history->reason);

        // The application works again for everyone.
        $analyst = $this->makeUser('analyst');
        $this->actingAs($analyst)->get('/dashboard')->assertStatus(200);
    }

    public function test_admin_can_set_an_absolute_new_expiry_date(): void
    {
        $admin = $this->makeUser('qc_admin');
        $future = now()->addDays(120)->toDateString();

        $this->actingAs($admin)->post('/settings/license/extend', [
            'mode' => 'date',
            'new_date' => $future,
            'reason' => 'Initial contract activation',
        ])->assertRedirect(route('settings.section', 'license'));

        $this->assertSame($future, \App\Support\AppSettings::licenseExpiry()?->toDateString());
        $this->assertNotNull(\App\Support\AppSettings::licenseActivation());
        $this->assertSame(1, LicenseHistory::count());
    }

    public function test_extension_must_be_in_the_future_of_current_expiry(): void
    {
        $this->configureLicense(now()->addDays(60)->toDateString());

        $admin = $this->makeUser('qc_admin');

        $this->actingAs($admin)->post('/settings/license/extend', [
            'mode' => 'date',
            'new_date' => now()->addDays(10)->toDateString(),
            'reason' => 'Trying to shorten',
        ])->assertSessionHasErrors('new_date');
    }

    /* --------------------------------------------------- authorization */

    public function test_normal_users_cannot_modify_the_license(): void
    {
        $analyst = $this->makeUser('analyst');

        $this->actingAs($analyst)->get('/settings')->assertForbidden();
        $this->actingAs($analyst)->get('/settings/license')->assertForbidden();

        $this->actingAs($analyst)->post('/settings/license/extend', [
            'mode' => 'days',
            'days' => 365,
            'reason' => 'please',
        ])->assertForbidden();

        $this->actingAs($analyst)->post('/settings/license/toggle')->assertForbidden();
        $this->assertSame(0, LicenseHistory::count());
    }

    public function test_license_settings_survive_a_settings_save_without_fake_dates(): void
    {
        // Fresh install: no expiry configured → application runs (unconfigured state).
        $analyst = $this->makeUser('analyst');

        $this->actingAs($analyst)->get('/dashboard')->assertStatus(200);
        $this->assertNull(\App\Support\AppSettings::licenseExpiry());
        $this->assertSame('unconfigured', \App\Support\AppSettings::licenseState());
    }

    /* ----------------------------------------------- server-side time */

    public function test_expiry_uses_the_server_clock_not_client_input(): void
    {
        $this->configureLicense(now()->subDay()->toDateString(), 0, 'block');

        // No request input, cookie or header can shift the computed state.
        $analyst = $this->makeUser('analyst');

        $response = $this->actingAs($analyst)
            ->withHeaders(['X-Client-Date' => now()->subYears(5)->format('r')])
            ->withCookie('client_time', now()->subYears(5)->toDateString())
            ->get('/dashboard');

        $response->assertStatus(503);
    }
}
