<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\Designation;
use App\Models\Employee;
use App\Models\Skill;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class EmployeeController extends Controller
{
    public function index(Request $request)
    {
        $query = Employee::query()
            ->with(['department', 'designation'])
            ->orderBy('employee_code');

        if ($search = $request->input('q')) {
            $query->where(function ($q) use ($search) {
                $q->where('employee_code', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($request->filled('department_id')) {
            $query->where('department_id', $request->input('department_id'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        $employees = $query->paginate(15)->withQueryString();

        return view('employees.index', [
            'employees' => $employees,
            'departments' => Department::active()->orderBy('name')->get(),
        ]);
    }

    public function create()
    {
        return view('employees.form', array_merge($this->formData(), ['employee' => null]));
    }

    public function store(Request $request)
    {
        $data = $this->validate($request);

        $employee = Employee::create($data);

        AuditLogger::log('Employees', 'create', $employee->id, null, ['employee_code' => $employee->employee_code]);

        return redirect()->route('employees.index')
            ->with('success', "Employee {$employee->employee_code} created successfully.");
    }

    public function show(Employee $employee)
    {
        $employee->load(['department', 'designation', 'skills', 'user', 'assignedTests.workOrder', 'testResults.test.workOrder']);

        return view('employees.show', [
            'employee' => $employee,
            'openWork' => $employee->assignedTests()->whereIn('status', [
                \App\Support\WorkStatus::ALLOCATED, \App\Support\WorkStatus::ACCEPTED,
                \App\Support\WorkStatus::IN_PROGRESS, \App\Support\WorkStatus::ON_HOLD,
                \App\Support\WorkStatus::REWORK,
            ])->count(),
        ]);
    }

    public function edit(Employee $employee)
    {
        return view('employees.form', array_merge($this->formData(), ['employee' => $employee]));
    }

    public function update(Request $request, Employee $employee)
    {
        $data = $this->validate($request, $employee);

        $old = $employee->only(array_keys($data));
        $employee->update($data);

        AuditLogger::log('Employees', 'update', $employee->id, $old, $data);

        return redirect()->route('employees.index')
            ->with('success', 'Employee updated successfully.');
    }

    public function syncSkills(Request $request, Employee $employee)
    {
        $data = $request->validate([
            'skills' => ['nullable', 'array'],
            'skills.*.skill_id' => ['nullable', 'exists:skills,id'],
            'skills.*.skill_level' => ['required_with:skills', 'in:Beginner,Intermediate,Advanced'],
        ]);

        $sync = [];
        foreach ($data['skills'] ?? [] as $row) {
            if (empty($row['skill_id'])) {
                continue; // unchecked skill
            }
            $sync[$row['skill_id']] = ['skill_level' => $row['skill_level'] ?? 'Intermediate', 'authorization_status' => 'active'];
        }

        $employee->skills()->sync($sync);

        AuditLogger::log('Employees', 'sync_skills', $employee->id, null, ['skills' => array_keys($sync)]);

        return back()->with('success', 'Skills updated successfully.');
    }

    private function validate(Request $request, ?Employee $employee = null): array
    {
        return $request->validate([
            'employee_code' => ['required', 'string', 'max:50', Rule::unique('employees', 'employee_code')->ignore($employee?->id)],
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'department_id' => ['required', 'exists:departments,id'],
            'designation_id' => ['required', 'exists:designations,id'],
            'email' => ['nullable', 'email', 'max:255', Rule::unique('employees', 'email')->ignore($employee?->id)],
            'mobile' => ['nullable', 'string', 'max:20'],
            'joining_date' => ['nullable', 'date'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
            'remarks' => ['nullable', 'string', 'max:1000'],
        ]);
    }

    private function formData(): array
    {
        return [
            'departments' => Department::active()->orderBy('name')->get(),
            'designations' => Designation::active()->orderBy('name')->get(),
            'allSkills' => Skill::active()->orderBy('name')->get(),
        ];
    }
}
