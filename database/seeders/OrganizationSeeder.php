<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\Designation;
use App\Models\Employee;
use App\Models\Location;
use App\Models\Skill;
use Illuminate\Database\Seeder;

class OrganizationSeeder extends Seeder
{
    public function run(): void
    {
        $qc = Department::firstOrCreate(['department_code' => 'QC'], ['name' => 'Quality Control', 'description' => 'Analytical testing laboratory']);
        $qa = Department::firstOrCreate(['department_code' => 'QA'], ['name' => 'Quality Assurance', 'description' => 'Quality systems and compliance']);
        $prod = Department::firstOrCreate(['department_code' => 'PROD'], ['name' => 'Production', 'description' => 'Manufacturing']);
        $rd = Department::firstOrCreate(['department_code' => 'RD'], ['name' => 'Research & Development', 'description' => 'Formulation development']);

        $designations = [
            ['designation_code' => 'QC-HOD', 'name' => 'QC HOD'],
            ['designation_code' => 'QC-MGR', 'name' => 'QC Manager'],
            ['designation_code' => 'QC-SUP', 'name' => 'Sr. QC Supervisor'],
            ['designation_code' => 'QC-SRA', 'name' => 'Sr. QC Analyst'],
            ['designation_code' => 'QC-ANA', 'name' => 'QC Analyst'],
            ['designation_code' => 'QC-REV', 'name' => 'QC Reviewer'],
            ['designation_code' => 'MGMT', 'name' => 'Management Executive'],
        ];

        foreach ($designations as $d) {
            Designation::firstOrCreate(['designation_code' => $d['designation_code']], $d);
        }

        Location::firstOrCreate(['location_code' => 'LAB-1'], ['name' => 'QC Laboratory — Hall 1', 'department_id' => $qc->id]);
        Location::firstOrCreate(['location_code' => 'LAB-2'], ['name' => 'QC Laboratory — Hall 2', 'department_id' => $qc->id]);

        $employees = [
            ['employee_code' => 'EMP-001', 'first_name' => 'Rajesh', 'last_name' => 'Sharma', 'department_id' => $qc->id, 'designation_id' => Designation::where('designation_code', 'QC-SUP')->value('id'), 'email' => 'rajesh.sharma@pharmaqc.com', 'mobile' => '9820011001'],
            ['employee_code' => 'EMP-002', 'first_name' => 'Raj', 'last_name' => 'Malhotra', 'department_id' => $qc->id, 'designation_id' => Designation::where('designation_code', 'QC-SRA')->value('id'), 'email' => 'raj.malhotra@pharmaqc.com', 'mobile' => '9820011002'],
            ['employee_code' => 'EMP-003', 'first_name' => 'Neha', 'last_name' => 'Verma', 'department_id' => $qc->id, 'designation_id' => Designation::where('designation_code', 'QC-ANA')->value('id'), 'email' => 'neha.verma@pharmaqc.com', 'mobile' => '9820011003'],
            ['employee_code' => 'EMP-004', 'first_name' => 'Amit', 'last_name' => 'Singh', 'department_id' => $qc->id, 'designation_id' => Designation::where('designation_code', 'QC-ANA')->value('id'), 'email' => 'amit.singh@pharmaqc.com', 'mobile' => '9820011004'],
            ['employee_code' => 'EMP-005', 'first_name' => 'Priya', 'last_name' => 'Nair', 'department_id' => $qc->id, 'designation_id' => Designation::where('designation_code', 'QC-ANA')->value('id'), 'email' => 'priya.nair@pharmaqc.com', 'mobile' => '9820011005'],
            ['employee_code' => 'EMP-006', 'first_name' => 'Vikram', 'last_name' => 'Patel', 'department_id' => $qc->id, 'designation_id' => Designation::where('designation_code', 'QC-HOD')->value('id'), 'email' => 'vikram.patel@pharmaqc.com', 'mobile' => '9820011006'],
            ['employee_code' => 'EMP-007', 'first_name' => 'Sanjay', 'last_name' => 'Mehta', 'department_id' => $qa->id, 'designation_id' => Designation::where('designation_code', 'QC-REV')->value('id'), 'email' => 'sanjay.mehta@pharmaqc.com', 'mobile' => '9820011007'],
            ['employee_code' => 'EMP-008', 'first_name' => 'Anita', 'last_name' => 'Rao', 'department_id' => $qa->id, 'designation_id' => Designation::where('designation_code', 'QC-MGR')->value('id'), 'email' => 'anita.rao@pharmaqc.com', 'mobile' => '9820011008'],
            ['employee_code' => 'EMP-009', 'first_name' => 'Ritu', 'last_name' => 'Desai', 'department_id' => $qa->id, 'designation_id' => Designation::where('designation_code', 'MGMT')->value('id'), 'email' => 'ritu.desai@pharmaqc.com', 'mobile' => '9820011009'],
        ];

        foreach ($employees as $e) {
            Employee::firstOrCreate(['employee_code' => $e['employee_code']], $e + [
                'name' => $e['first_name'].' '.$e['last_name'],
                'joining_date' => now()->subYears(rand(1, 8))->toDateString(),
                'status' => 'active',
            ]);
        }

        // Skills are created by QcMasterSeeder first; map them here if present.
        $skillMap = [
            'EMP-002' => ['HPLC' => 'Advanced', 'GC' => 'Intermediate', 'UV' => 'Advanced', 'KF' => 'Intermediate'],
            'EMP-003' => ['HPLC' => 'Intermediate', 'DISS' => 'Advanced', 'UV' => 'Intermediate'],
            'EMP-004' => ['HPLC' => 'Beginner', 'MICRO' => 'Advanced', 'STAB' => 'Intermediate'],
            'EMP-005' => ['UV' => 'Advanced', 'KF' => 'Advanced', 'DISS' => 'Intermediate'],
            'EMP-001' => ['HPLC' => 'Advanced', 'UV' => 'Advanced', 'SAMP' => 'Advanced'],
        ];

        foreach ($skillMap as $code => $skills) {
            $employee = Employee::where('employee_code', $code)->first();

            if (! $employee) {
                continue;
            }

            foreach ($skills as $skillCode => $level) {
                $skill = Skill::where('skill_code', $skillCode)->first();

                if ($skill) {
                    $employee->skills()->syncWithoutDetaching([
                        $skill->id => ['skill_level' => $level, 'authorization_status' => 'active'],
                    ]);
                }
            }
        }
    }
}
