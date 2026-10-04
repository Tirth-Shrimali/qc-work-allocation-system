<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Safe defaults for the four new enterprise controls.
     * Inserted only when the key does not already exist — never overwrites existing values.
     */
    private array $defaults = [
        // Feature 1 — registration
        ['registration.allow_registration', '1', 'registration', 'Allow new user self-registration (1 = enabled, 0 = disabled)'],
        ['registration.require_approval', '1', 'registration', 'New registrations require admin approval before login (1 = yes, 0 = no)'],
        ['registration.default_role', 'analyst', 'registration', 'Role code assigned to newly registered users'],

        // Feature 2 — inactivity session policy
        ['session.options', '30,60,120,240,480', 'session', 'Available inactivity timeout options in minutes'],
        ['session.default', '120', 'session', 'Default inactivity timeout in minutes'],
        ['session.max', '480', 'session', 'Maximum inactivity timeout allowed in minutes'],

        // Feature 3 — usage limits
        ['usage.max_active_users', '10', 'usage', 'Maximum simultaneous active users (0 = unlimited)'],
        ['usage.max_active_projects', '20', 'usage', 'Maximum simultaneous active work requests / projects (0 = unlimited)'],

        // Feature 4 — application validity
        ['license.activation_date', null, 'license', 'License activation date (Y-m-d); empty = not configured'],
        ['license.expiry_date', null, 'license', 'License expiry date (Y-m-d); empty = no expiry configured'],
        ['license.grace_days', '0', 'license', 'Grace period in days after expiry'],
        ['license.behavior', 'block', 'license', 'Expiry behaviour: block | readonly | restrict_login'],
        ['license.warn_thresholds', '30,15,7,3,1', 'license', 'Expiry warning thresholds in days'],
        ['license.suspended', '0', 'license', 'Manually suspend the application (1 = suspended)'],
        ['license.last_warned', '', 'license', 'Internal: last expiry-warning threshold notified'],
    ];

    public function up(): void
    {
        foreach ($this->defaults as [$key, $value, $group, $description]) {
            $exists = DB::table('settings')->where('key', $key)->exists();

            if (! $exists) {
                DB::table('settings')->insert([
                    'key' => $key,
                    'value' => $value,
                    'group' => $group,
                    'description' => $description,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        foreach ($this->defaults as [$key]) {
            DB::table('settings')->where('key', $key)->delete();
        }
    }
};
