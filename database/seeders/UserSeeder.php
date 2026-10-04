<?php

namespace Database\Seeders;

use App\Models\Employee;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $users = [
            ['username' => 'admin', 'name' => 'System Administrator', 'email' => 'admin@pharmaqc.com', 'role' => 'super_admin', 'employee' => null],
            ['username' => 'qc_manager', 'name' => 'Anita Rao', 'email' => 'anita.rao@pharmaqc.com', 'role' => 'qc_admin', 'employee' => 'EMP-008'],
            ['username' => 'hod_qc', 'name' => 'Vikram Patel', 'email' => 'vikram.patel@pharmaqc.com', 'role' => 'qc_hod', 'employee' => 'EMP-006'],
            ['username' => 'supervisor_qc', 'name' => 'Rajesh Sharma', 'email' => 'rajesh.sharma@pharmaqc.com', 'role' => 'qc_supervisor', 'employee' => 'EMP-001'],
            ['username' => 'analyst_raj', 'name' => 'Raj Malhotra', 'email' => 'raj.malhotra@pharmaqc.com', 'role' => 'analyst', 'employee' => 'EMP-002'],
            ['username' => 'analyst_neha', 'name' => 'Neha Verma', 'email' => 'neha.verma@pharmaqc.com', 'role' => 'analyst', 'employee' => 'EMP-003'],
            ['username' => 'analyst_amit', 'name' => 'Amit Singh', 'email' => 'amit.singh@pharmaqc.com', 'role' => 'analyst', 'employee' => 'EMP-004'],
            ['username' => 'analyst_priya', 'name' => 'Priya Nair', 'email' => 'priya.nair@pharmaqc.com', 'role' => 'analyst', 'employee' => 'EMP-005'],
            ['username' => 'reviewer_qc', 'name' => 'Sanjay Mehta', 'email' => 'sanjay.mehta@pharmaqc.com', 'role' => 'reviewer', 'employee' => 'EMP-007'],
            ['username' => 'management', 'name' => 'Ritu Desai', 'email' => 'ritu.desai@pharmaqc.com', 'role' => 'management', 'employee' => 'EMP-009'],
        ];

        foreach ($users as $u) {
            $employeeId = $u['employee'] ? Employee::where('employee_code', $u['employee'])->value('id') : null;

            $user = User::firstOrCreate(
                ['username' => $u['username']],
                [
                    'name' => $u['name'],
                    'email' => $u['email'],
                    'password' => 'password',
                    'employee_id' => $employeeId,
                    'status' => 'active',
                ]
            );

            $role = Role::where('code', $u['role'])->first();
            if ($role) {
                $user->roles()->syncWithoutDetaching([$role->id]);
            }
        }
    }
}
