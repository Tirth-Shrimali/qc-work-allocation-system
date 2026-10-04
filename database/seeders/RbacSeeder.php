<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class RbacSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            'User Management' => ['users.view', 'users.create', 'users.edit', 'users.delete'],
            'Role Management' => ['roles.view', 'roles.manage'],
            'Organization' => ['departments.manage', 'employees.manage'],
            'QC Masters' => ['masters.view', 'masters.manage'],
            'Work Management' => ['work_orders.view', 'work_orders.create', 'work_orders.edit', 'work_orders.delete'],
            'Allocation Engine' => ['allocations.view', 'allocations.allocate', 'allocations.reallocate'],
            'Execution' => ['execution.view', 'execution.enter_result', 'execution.submit'],
            'Review & Approval' => ['review.view', 'review.approve', 'review.rework'],
            'Analytics' => ['dashboards.view', 'reports.view', 'reports.export'],
            'System Administration' => ['system.audit', 'system.settings', 'system.backup'],
        ];

        foreach ($permissions as $module => $codes) {
            foreach ($codes as $code) {
                Permission::firstOrCreate(
                    ['code' => $code],
                    ['name' => ucwords(str_replace(['.', '_'], ' ', $code)), 'module' => $module]
                );
            }
        }

        $all = Permission::pluck('id', 'id');

        $roles = [
            'super_admin' => ['name' => 'Super Administrator', 'desc' => 'Full system access', 'permissions' => $all->keys()],
            'qc_admin' => ['name' => 'QC Admin / Manager', 'desc' => 'Manages QC operations, employees, work, allocation, review and reports', 'permissions' => $all->keys()],
            'qc_hod' => ['name' => 'QC HOD', 'desc' => 'Department performance, workload, reports and approvals', 'permissions' => $all->filter(fn ($id) => in_array(Permission::find($id)->code, [
                'dashboards.view', 'reports.view', 'reports.export', 'work_orders.view', 'work_orders.create',
                'allocations.view', 'execution.view', 'masters.view', 'review.view', 'review.approve', 'review.rework',
            ]))],
            'qc_supervisor' => ['name' => 'QC Supervisor', 'desc' => 'Allocates work, monitors analysts, reallocation and review', 'permissions' => $all->filter(fn ($id) => in_array(Permission::find($id)->code, [
                'dashboards.view', 'work_orders.view', 'work_orders.create', 'work_orders.edit',
                'allocations.view', 'allocations.allocate', 'allocations.reallocate',
                'execution.view', 'review.view', 'review.rework', 'masters.view', 'employees.manage', 'reports.view',
            ]))],
            'analyst' => ['name' => 'QC Analyst', 'desc' => 'Executes assigned tests, enters results and submits for review', 'permissions' => $all->filter(fn ($id) => in_array(Permission::find($id)->code, [
                'dashboards.view', 'work_orders.view', 'execution.view', 'execution.enter_result', 'execution.submit',
            ]))],
            'reviewer' => ['name' => 'Reviewer', 'desc' => 'Reviews results, approves, rejects or requests rework', 'permissions' => $all->filter(fn ($id) => in_array(Permission::find($id)->code, [
                'dashboards.view', 'work_orders.view', 'execution.view', 'review.view', 'review.approve', 'review.rework', 'reports.view', 'masters.view',
            ]))],
            'management' => ['name' => 'Management / Viewer', 'desc' => 'Read-only dashboards and reports', 'permissions' => $all->filter(fn ($id) => in_array(Permission::find($id)->code, [
                'dashboards.view', 'reports.view', 'reports.export', 'work_orders.view',
            ]))],
        ];

        foreach ($roles as $code => $def) {
            $role = Role::firstOrCreate(
                ['code' => $code],
                ['name' => $def['name'], 'description' => $def['desc'], 'status' => 'active']
            );

            $role->permissions()->sync($def['permissions']->all());
        }
    }
}
