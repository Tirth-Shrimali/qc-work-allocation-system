<?php

namespace Tests\Concerns;

use App\Models\Role;
use App\Models\Setting;
use App\Models\User;
use Database\Seeders\RbacSeeder;

trait InteractsWithQc
{
    protected function seedRbac(): void
    {
        $this->seed(RbacSeeder::class);
    }

    protected function makeUser(string $roleCode, array $attributes = []): User
    {
        $user = User::create(array_merge([
            'name' => 'Test '.ucfirst($roleCode),
            'username' => substr('t'.uniqid('').$roleCode, 0, 100),
            'email' => uniqid('user_', false).'@test.local',
            'password' => 'password',
            'status' => 'active',
        ], $attributes));

        $role = Role::where('code', $roleCode)->first();

        if ($role) {
            $user->roles()->attach($role->id);
        }

        return $user;
    }

    protected function setSetting(string $key, string $value): void
    {
        Setting::updateOrCreate(['key' => $key], ['value' => $value]);
    }

    /** Perform a real login through the login form (creates a tracked session). */
    protected function loginViaForm(User $user, array $extra = [])
    {
        return $this->post('/login', array_merge([
            'username' => $user->username,
            'password' => 'password',
        ], $extra));
    }

    protected function validOrderPayload(int $departmentId, int $sampleTypeId, array $overrides = []): array
    {
        return array_merge([
            'request_date' => now()->toDateString(),
            'department_id' => $departmentId,
            'sample_type_id' => $sampleTypeId,
            'batch_no' => 'BATCH-'.uniqid(),
            'required_date' => now()->addDays(5)->toDateString(),
            'priority' => 'high',
            'tests' => [
                ['test_type_id' => 1],
            ],
        ], $overrides);
    }
}
