<?php

namespace App\Http\Controllers;

use App\Models\LicenseHistory;
use App\Models\Role;
use App\Models\WorkOrder;
use App\Services\AuditLogger;
use App\Services\SessionTracker;
use App\Support\AppSettings;
use App\Support\WorkStatus;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Admin-controlled settings centre. All values live in the existing
 * `settings` key/value table — this is a structured front-end for it.
 */
class SettingsController extends Controller
{
    public const SECTIONS = ['overview', 'users', 'session', 'usage', 'license'];

    public const SESSION_PRESETS = [15, 30, 60, 120, 240, 480, 720, 1440];

    public const USER_LIMIT_PRESETS = [1, 5, 10, 20, 50, 100];

    public const PROJECT_LIMIT_PRESETS = [1, 5, 10, 20, 50, 100];

    public const WARN_PRESETS = [30, 15, 7, 3, 1];

    /* ------------------------------------------------------------ views */

    public function index(Request $request, ?string $section = null)
    {
        $section = $section ?? 'overview';

        abort_unless(in_array($section, self::SECTIONS, true), 404);

        $tracker = new SessionTracker;

        return view('settings.index', [
            'section' => $section,
            'roles' => Role::active()->orderBy('name')->get(),
            'history' => LicenseHistory::with('user')->latest()->limit(15)->get(),
            // real, computed dashboard values
            'activeUsers' => $tracker->activeCount(),
            'maxUsers' => AppSettings::maxActiveUsers(),
            'activeProjects' => WorkOrder::whereIn('status', WorkStatus::OPEN)->count(),
            'maxProjects' => AppSettings::maxActiveProjects(),
            'licenseState' => AppSettings::licenseState(),
            'licenseStateLabel' => AppSettings::licenseStateLabel(),
            'remainingDays' => AppSettings::remainingDays(),
            'activation' => AppSettings::licenseActivation(),
            'expiry' => AppSettings::licenseExpiry(),
        ]);
    }

    public function activeUsers(Request $request)
    {
        $rows = (new SessionTracker)->activeRows()->get();

        return view('settings.active-users', [
            'sessions' => $rows,
            'activeCount' => $rows->pluck('user_id')->unique()->count(),
            'maxUsers' => AppSettings::maxActiveUsers(),
        ]);
    }

    /* ------------------------------------------------------------ writes */

    public function update(Request $request, string $section)
    {
        abort_unless(in_array($section, ['users', 'session', 'usage', 'license'], true), 404);

        [$data, $keys] = match ($section) {
            'users' => [$this->validateUsers($request), ['registration.allow_registration', 'registration.require_approval', 'registration.default_role']],
            'session' => [$this->validateSession($request), ['session.options', 'session.default', 'session.max']],
            'usage' => [$this->validateUsage($request), ['usage.max_active_users', 'usage.max_active_projects']],
            'license' => [$this->validateLicense($request), ['license.activation_date', 'license.expiry_date', 'license.grace_days', 'license.behavior', 'license.warn_thresholds']],
        };

        $old = [];
        foreach ($keys as $key) {
            $old[$key] = AppSettings::get($key);
        }

        match ($section) {
            'users' => $this->persistUsers($data),
            'session' => $this->persistSession($data),
            'usage' => $this->persistUsage($data),
            'license' => $this->persistLicense($data, $request),
        };

        $new = [];
        foreach ($keys as $key) {
            $new[$key] = AppSettings::get($key);
        }

        AuditLogger::log('Settings', "update ({$section})", null, $old, $new);

        return redirect()->route('settings.section', $section)
            ->with('success', ucfirst($section).' settings updated successfully.');
    }

    public function extendLicense(Request $request)
    {
        $data = $request->validate([
            'mode' => ['required', Rule::in(['days', 'date'])],
            'days' => ['nullable', 'integer', 'min:1', 'max:3650'],
            'new_date' => ['nullable', 'date', 'after:today'],
            'reason' => ['required', 'string', 'max:500'],
        ], [
            'days.required' => 'Choose a number of days to extend by.',
            'new_date.required' => 'Choose the new expiry date.',
            'reason.required' => 'A reason for the change is required.',
        ]);

        $current = AppSettings::licenseExpiry();

        if ($data['mode'] === 'days') {
            abort_if(empty($data['days']), 422, 'Days required');
            $base = ($current && $current->isFuture()) ? $current->copy() : now()->startOfDay();
            $newExpiry = $base->addDays((int) $data['days']);
        } else {
            abort_if(empty($data['new_date']), 422, 'Date required');
            $newExpiry = \Carbon\Carbon::parse($data['new_date'])->startOfDay();
        }

        if ($current && $newExpiry->lte($current)) {
            return back()->withErrors(['new_date' => 'The new expiry date must be after the current expiry ('.$current->format('d M Y').').']);
        }

        $previousExpiry = $current;
        $previousActivation = AppSettings::licenseActivation();

        AppSettings::set('license.expiry_date', $newExpiry->toDateString());

        // No expiry configured yet → this extension also activates the licence.
        if (! $previousActivation) {
            AppSettings::set('license.activation_date', now()->toDateString());
        }

        // Reset the warning-notification marker so new thresholds fire again.
        AppSettings::set('license.last_warned', '');

        LicenseHistory::create([
            'action' => $previousExpiry ? 'extended' : 'activated',
            'previous_activation' => $previousActivation,
            'new_activation' => AppSettings::licenseActivation(),
            'previous_expiry' => $previousExpiry,
            'new_expiry' => $newExpiry,
            'user_id' => $request->user()->id,
            'reason' => $data['reason'],
        ]);

        AuditLogger::log('License', $previousExpiry ? 'extend' : 'activate', null, [
            'previous_expiry' => $previousExpiry?->toDateString(),
        ], [
            'new_expiry' => $newExpiry->toDateString(),
            'reason' => $data['reason'],
        ]);

        return redirect()->route('settings.section', 'license')
            ->with('success', 'Application validity extended to '.$newExpiry->format('d M Y').'.');
    }

    public function toggleSuspend(Request $request)
    {
        $suspended = AppSettings::suspended();

        $data = $request->validate([
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        AppSettings::set('license.suspended', $suspended ? '0' : '1');

        LicenseHistory::create([
            'action' => $suspended ? 'reactivated' : 'suspended',
            'previous_activation' => AppSettings::licenseActivation(),
            'new_activation' => AppSettings::licenseActivation(),
            'previous_expiry' => AppSettings::licenseExpiry(),
            'new_expiry' => AppSettings::licenseExpiry(),
            'user_id' => $request->user()->id,
            'reason' => $data['reason'] ?? ($suspended ? 'Reactivated by administrator' : 'Suspended by administrator'),
        ]);

        AuditLogger::log('License', $suspended ? 'reactivate' : 'suspend', null, ['suspended' => $suspended], ['suspended' => ! $suspended]);

        return redirect()->route('settings.section', 'license')
            ->with('success', $suspended ? 'Application reactivated.' : 'Application suspended.');
    }

    /* ------------------------------------------------------- validation */

    private function validateUsers(Request $request): array
    {
        return $request->validate([
            'allow_registration' => ['required', Rule::in(['0', '1'])],
            'require_approval' => ['required', Rule::in(['0', '1'])],
            'default_role' => ['required', Rule::in(Role::pluck('code')->all())],
        ]);
    }

    private function validateSession(Request $request): array
    {
        return $request->validate([
            'options' => ['required', 'array', 'min:1'],
            'options.*' => ['integer', Rule::in(self::SESSION_PRESETS)],
            'default' => ['required', 'integer', Rule::in(self::SESSION_PRESETS)],
            'max' => ['required', 'integer', 'min:5', 'max:10080', 'gte:default'],
        ], [
            'options.required' => 'Select at least one inactivity duration.',
            'max.gte' => 'The maximum must be greater than or equal to the default duration.',
        ]);
    }

    private function validateUsage(Request $request): array
    {
        return $request->validate([
            'max_active_users' => ['required', 'integer', 'min:0', 'max:100000'],
            'max_active_projects' => ['required', 'integer', 'min:0', 'max:100000'],
        ]);
    }

    private function validateLicense(Request $request): array
    {
        return $request->validate([
            'activation_date' => ['nullable', 'date'],
            'expiry_date' => [
                'nullable', 'date',
                function ($attribute, $value, $fail) use ($request) {
                    $activation = $request->input('activation_date');

                    if ($activation && $value && strtotime($value) < strtotime($activation)) {
                        $fail('The expiry date must be on or after the activation date.');
                    }
                },
            ],
            'grace_days' => ['required', 'integer', 'min:0', 'max:365'],
            'behavior' => ['required', Rule::in(['block', 'readonly', 'restrict_login'])],
            'warn_thresholds' => ['required', 'array', 'min:1'],
            'warn_thresholds.*' => ['integer', Rule::in(self::WARN_PRESETS)],
        ]);
    }

    /* ---------------------------------------------------------- persist */

    private function persistUsers(array $data): void
    {
        AppSettings::set('registration.allow_registration', $data['allow_registration']);
        AppSettings::set('registration.require_approval', $data['require_approval']);
        AppSettings::set('registration.default_role', $data['default_role']);
    }

    private function persistSession(array $data): void
    {
        $max = (int) $data['max'];
        $options = collect($data['options'])->map(fn ($v) => (int) $v)->sort()->values();

        // Consistency: every option (and the default) must fit under the maximum.
        $options = $options->filter(fn ($v) => $v <= $max)->values();

        if ($options->isEmpty()) {
            $options = collect([$max]);
        }

        $default = min((int) $data['default'], $max);

        if (! $options->contains($default)) {
            $default = $options->first();
        }

        AppSettings::set('session.options', $options->implode(','));
        AppSettings::set('session.default', (string) $default);
        AppSettings::set('session.max', (string) $max);
    }

    private function persistUsage(array $data): void
    {
        AppSettings::set('usage.max_active_users', (string) (int) $data['max_active_users']);
        AppSettings::set('usage.max_active_projects', (string) (int) $data['max_active_projects']);
    }

    private function persistLicense(array $data, Request $request): void
    {
        $oldExpiry = AppSettings::licenseExpiry();
        $oldActivation = AppSettings::licenseActivation();
        $oldBehavior = AppSettings::licenseBehavior();

        AppSettings::set('license.activation_date', $data['activation_date'] ?: '');
        AppSettings::set('license.expiry_date', $data['expiry_date'] ?: '');
        AppSettings::set('license.grace_days', (string) (int) $data['grace_days']);
        AppSettings::set('license.behavior', $data['behavior']);
        AppSettings::set('license.warn_thresholds', collect($data['warn_thresholds'])->map(fn ($v) => (int) $v)->sortDesc()->implode(','));

        $newExpiry = AppSettings::licenseExpiry();
        $newActivation = AppSettings::licenseActivation();
        $newBehavior = AppSettings::licenseBehavior();

        $dateChanged = ($oldExpiry?->toDateString() !== $newExpiry?->toDateString())
            || ($oldActivation?->toDateString() !== $newActivation?->toDateString());

        if ($dateChanged) {
            LicenseHistory::create([
                'action' => $newExpiry && ! $oldExpiry ? 'activated' : 'expiry_changed',
                'previous_activation' => $oldActivation,
                'new_activation' => $newActivation,
                'previous_expiry' => $oldExpiry,
                'new_expiry' => $newExpiry,
                'user_id' => $request->user()->id,
                'reason' => 'Validity dates updated from settings',
            ]);

            AppSettings::set('license.last_warned', '');
        }

        if ($oldBehavior !== $newBehavior) {
            LicenseHistory::create([
                'action' => 'policy_changed',
                'previous_activation' => $newActivation,
                'new_activation' => $newActivation,
                'previous_expiry' => $newExpiry,
                'new_expiry' => $newExpiry,
                'user_id' => $request->user()->id,
                'reason' => "Expiry behaviour changed from '{$oldBehavior}' to '{$newBehavior}'",
            ]);
        }
    }
}
