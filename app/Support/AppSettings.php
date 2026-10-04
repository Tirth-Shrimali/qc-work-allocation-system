<?php

namespace App\Support;

use App\Models\Setting;
use Carbon\Carbon;

/**
 * Central, database-backed accessors for the enterprise control settings.
 * Every value is read from the `settings` table at call time (no stale cache),
 * so admin changes take effect on the very next request.
 */
class AppSettings
{
    public const GROUPS = ['general', 'registration', 'session', 'usage', 'license'];

    /* ----------------------------------------------------- raw access */

    public static function get(string $key, ?string $default = null): ?string
    {
        $value = Setting::getValue($key);

        return $value === null ? $default : $value;
    }

    public static function set(string $key, string $value): void
    {
        Setting::updateOrCreate(['key' => $key], ['value' => $value]);
    }

    public static function int(string $key, int $default): int
    {
        $raw = static::get($key);

        return $raw !== null && $raw !== '' && is_numeric($raw) ? (int) $raw : $default;
    }

    public static function bool(string $key, bool $default): bool
    {
        $raw = static::get($key);

        if ($raw === null || $raw === '') {
            return $default;
        }

        return in_array(strtolower($raw), ['1', 'true', 'yes', 'on'], true);
    }

    /* ------------------------------------------- Feature 1: registration */

    public static function allowRegistration(): bool
    {
        return static::bool('registration.allow_registration', true);
    }

    public static function requireApproval(): bool
    {
        return static::bool('registration.require_approval', true);
    }

    public static function defaultRoleCode(): string
    {
        return static::get('registration.default_role', 'analyst') ?: 'analyst';
    }

    /* ------------------------------------ Feature 2: inactivity session */

    /** Allowed inactivity options in minutes (already capped at the admin maximum). */
    public static function sessionOptions(): array
    {
        $raw = static::get('session.options', '30,60,120,240,480') ?: '30,60,120,240,480';
        $max = static::sessionMax();

        $options = collect(explode(',', $raw))
            ->map(fn ($v) => (int) trim($v))
            ->filter(fn ($v) => $v > 0 && $v <= $max)
            ->unique()
            ->sort()
            ->values();

        if ($options->isEmpty()) {
            $options = collect([$max]);
        }

        return $options->all();
    }

    public static function sessionDefault(): int
    {
        $default = static::int('session.default', 120);

        $options = static::sessionOptions();

        // Default must be one of the offered options (and never above the maximum).
        if (! in_array($default, $options, true)) {
            $default = (int) collect($options)->first();
        }

        return min($default, static::sessionMax());
    }

    public static function sessionMax(): int
    {
        $max = static::int('session.max', 480);

        return $max > 0 ? $max : 480;
    }

    /**
     * Resolve a user's chosen timeout safely:
     * must be an allowed option and never above the admin maximum — anything else
     * falls back to the configured default.
     */
    public static function resolveTimeout($choice): int
    {
        $choice = is_numeric($choice) ? (int) $choice : null;

        if ($choice !== null && in_array($choice, static::sessionOptions(), true) && $choice <= static::sessionMax()) {
            return $choice;
        }

        return static::sessionDefault();
    }

    /** [[value, label], …] for the login screen select. */
    public static function timeoutChoices(): array
    {
        $default = static::sessionDefault();

        return collect(static::sessionOptions())
            ->map(fn ($minutes) => [$minutes, static::minutesLabel($minutes), $minutes === $default])
            ->all();
    }

    public static function minutesLabel(int $minutes): string
    {
        if ($minutes < 60) {
            return "{$minutes} Minutes";
        }

        $hours = intdiv($minutes, 60);
        $rest = $minutes % 60;
        $label = $hours.($hours === 1 ? ' Hour' : ' Hours');

        return $rest > 0 ? "{$label} {$rest} Minutes" : $label;
    }

    /* ---------------------------------- Feature 3: usage limits (users) */

    /** 0 = unlimited. */
    public static function maxActiveUsers(): int
    {
        return max(0, static::int('usage.max_active_users', 10));
    }

    /* ------------------------------- Feature 3B: active projects limit */

    /** 0 = unlimited. */
    public static function maxActiveProjects(): int
    {
        return max(0, static::int('usage.max_active_projects', 20));
    }

    /* --------------------------------- Feature 4: application validity */

    public static function licenseActivation(): ?Carbon
    {
        return static::date('license.activation_date');
    }

    public static function licenseExpiry(): ?Carbon
    {
        return static::date('license.expiry_date');
    }

    public static function graceDays(): int
    {
        return max(0, static::int('license.grace_days', 0));
    }

    public static function licenseBehavior(): string
    {
        $behavior = static::get('license.behavior', 'block') ?: 'block';

        return in_array($behavior, ['block', 'readonly', 'restrict_login'], true) ? $behavior : 'block';
    }

    public static function warnThresholds(): array
    {
        $raw = static::get('license.warn_thresholds', '30,15,7,3,1') ?: '30,15,7,3,1';

        return collect(explode(',', $raw))
            ->map(fn ($v) => (int) trim($v))
            ->filter(fn ($v) => $v > 0)
            ->unique()
            ->sortDesc()
            ->values()
            ->all();
    }

    public static function suspended(): bool
    {
        return static::bool('license.suspended', false);
    }

    /**
     * Server-side validity state. Uses the application's own clock — never
     * anything supplied by the browser.
     *
     * suspended | expired | grace | expiring | active | unconfigured
     */
    public static function licenseState(): string
    {
        if (static::suspended()) {
            return 'suspended';
        }

        $expiry = static::licenseExpiry();

        if (! $expiry) {
            return 'unconfigured';
        }

        $today = now()->startOfDay();
        $expiryDay = $expiry->copy()->startOfDay();

        if ($today->gt($expiryDay)) {
            $graceEnd = $expiryDay->copy()->addDays(static::graceDays());

            return $today->lte($graceEnd) ? 'grace' : 'expired';
        }

        $remaining = static::remainingDays();

        if ($remaining !== null && static::warningThresholdHit($remaining) !== null) {
            return 'expiring';
        }

        return 'active';
    }

    /** Whole days remaining until expiry (server clock); null when no expiry configured. */
    public static function remainingDays(): ?int
    {
        $expiry = static::licenseExpiry();

        if (! $expiry) {
            return null;
        }

        return (int) round(($expiry->copy()->startOfDay()->getTimestamp() - now()->startOfDay()->getTimestamp()) / 86400);
    }

    /** The warning threshold (days) that the remaining time has crossed, if any. */
    public static function warningThresholdHit(?int $remaining = null): ?int
    {
        $remaining ??= static::remainingDays();

        if ($remaining === null || $remaining < 0) {
            return null;
        }

        foreach (static::warnThresholds() as $threshold) {
            if ($remaining <= $threshold) {
                return $threshold;
            }
        }

        return null;
    }

    /** Human label for badge display. */
    public static function licenseStateLabel(): string
    {
        return match (static::licenseState()) {
            'suspended' => 'Suspended',
            'expired' => 'Expired',
            'grace' => 'Grace Period',
            'expiring' => 'Expiring Soon',
            'active' => 'Active',
            default => 'No Expiry Set',
        };
    }

    /* ------------------------------------------------------- helpers */

    private static function date(string $key): ?Carbon
    {
        $raw = static::get($key);

        if (! $raw) {
            return null;
        }

        try {
            return Carbon::parse($raw)->startOfDay();
        } catch (\Throwable) {
            return null;
        }
    }
}
