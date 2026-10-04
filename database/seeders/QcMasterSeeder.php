<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\Instrument;
use App\Models\InstrumentType;
use App\Models\Location;
use App\Models\Material;
use App\Models\Priority;
use App\Models\Product;
use App\Models\SampleType;
use App\Models\Setting;
use App\Models\Skill;
use App\Models\Specification;
use App\Models\Status;
use App\Models\TestMethod;
use App\Models\TestParameter;
use App\Models\TestType;
use App\Models\WorkCategory;
use Illuminate\Database\Seeder;

class QcMasterSeeder extends Seeder
{
    public function run(): void
    {
        $skills = [
            ['skill_code' => 'HPLC', 'name' => 'HPLC Operation', 'category' => 'Chromatography'],
            ['skill_code' => 'GC', 'name' => 'GC Operation', 'category' => 'Chromatography'],
            ['skill_code' => 'UV', 'name' => 'UV Spectroscopy', 'category' => 'Spectroscopy'],
            ['skill_code' => 'KF', 'name' => 'Karl Fischer Titration', 'category' => 'Titration'],
            ['skill_code' => 'DISS', 'name' => 'Dissolution Testing', 'category' => 'Physical Testing'],
            ['skill_code' => 'MICRO', 'name' => 'Microbiology Testing', 'category' => 'Microbiology'],
            ['skill_code' => 'STAB', 'name' => 'Stability Testing', 'category' => 'Stability'],
            ['skill_code' => 'SAMP', 'name' => 'Sampling', 'category' => 'General'],
        ];
        foreach ($skills as $s) {
            Skill::firstOrCreate(['skill_code' => $s['skill_code']], $s);
        }

        $categories = [
            ['category_code' => 'REL', 'name' => 'Release Testing'],
            ['category_code' => 'STAB', 'name' => 'Stability Testing'],
            ['category_code' => 'RM', 'name' => 'Raw Material Testing'],
            ['category_code' => 'IP', 'name' => 'In-Process Testing'],
        ];
        foreach ($categories as $c) {
            WorkCategory::firstOrCreate(['category_code' => $c['category_code']], $c);
        }

        $sampleTypes = [
            ['sample_type_code' => 'FP', 'name' => 'Finished Product'],
            ['sample_type_code' => 'RM', 'name' => 'Raw Material'],
            ['sample_type_code' => 'WAT', 'name' => 'Water Sample'],
            ['sample_type_code' => 'STB', 'name' => 'Stability Sample'],
            ['sample_type_code' => 'PKG', 'name' => 'Packaging Material'],
        ];
        foreach ($sampleTypes as $s) {
            SampleType::firstOrCreate(['sample_type_code' => $s['sample_type_code']], $s);
        }

        $priorities = [
            ['priority_code' => 'CRIT', 'name' => 'Critical', 'level' => 4],
            ['priority_code' => 'HIGH', 'name' => 'High', 'level' => 3],
            ['priority_code' => 'NORM', 'name' => 'Normal', 'level' => 2],
            ['priority_code' => 'LOW', 'name' => 'Low', 'level' => 1],
        ];
        foreach ($priorities as $p) {
            Priority::firstOrCreate(['priority_code' => $p['priority_code']], $p + ['description' => null, 'status' => 'active']);
        }

        $statuses = [
            ['status_code' => 'NEW', 'name' => 'New Request', 'color_class' => 'info'],
            ['status_code' => 'ALLOCATED', 'name' => 'Allocated', 'color_class' => 'primary'],
            ['status_code' => 'ACCEPTED', 'name' => 'Accepted', 'color_class' => 'secondary'],
            ['status_code' => 'IN_PROGRESS', 'name' => 'In Progress', 'color_class' => 'warning'],
            ['status_code' => 'ON_HOLD', 'name' => 'On Hold', 'color_class' => 'secondary'],
            ['status_code' => 'SUBMITTED', 'name' => 'Submitted for Review', 'color_class' => 'info'],
            ['status_code' => 'UNDER_REVIEW', 'name' => 'Under Review', 'color_class' => 'primary'],
            ['status_code' => 'REWORK', 'name' => 'Rework Requested', 'color_class' => 'danger'],
            ['status_code' => 'APPROVED', 'name' => 'Approved', 'color_class' => 'success'],
            ['status_code' => 'COMPLETED', 'name' => 'Completed', 'color_class' => 'dark'],
            ['status_code' => 'REJECTED', 'name' => 'Rejected', 'color_class' => 'danger'],
            ['status_code' => 'CANCELLED', 'name' => 'Cancelled', 'color_class' => 'dark'],
        ];
        foreach ($statuses as $s) {
            Status::firstOrCreate(['status_code' => $s['status_code']], $s + ['category' => 'workflow', 'status' => 'active']);
        }

        $qc = Department::where('department_code', 'QC')->first();

        $products = [
            ['product_code' => 'PRD-001', 'product_name' => 'Paracetamol Tablets 500 mg', 'generic_name' => 'Paracetamol', 'product_type' => 'Finished Product', 'dosage_form' => 'Tablet', 'strength' => '500 mg'],
            ['product_code' => 'PRD-002', 'product_name' => 'Cefixime Capsules 200 mg', 'generic_name' => 'Cefixime', 'product_type' => 'Finished Product', 'dosage_form' => 'Capsule', 'strength' => '200 mg'],
            ['product_code' => 'PRD-003', 'product_name' => 'Amoxicillin Tablets 500 mg', 'generic_name' => 'Amoxicillin', 'product_type' => 'Finished Product', 'dosage_form' => 'Tablet', 'strength' => '500 mg'],
        ];
        foreach ($products as $p) {
            Product::firstOrCreate(['product_code' => $p['product_code']], $p + ['department_id' => $qc?->id, 'status' => 'active']);
        }

        $materials = [
            ['material_code' => 'MAT-001', 'material_name' => 'Paracetamol API', 'material_type' => 'API', 'material_category' => 'Active Ingredient', 'storage_condition' => 'Cool & dry', 'supplier' => 'ChemWorks Ltd.'],
            ['material_code' => 'MAT-002', 'material_name' => 'Purified Water', 'material_type' => 'Utility', 'material_category' => 'Solvent', 'storage_condition' => 'Ambient'],
        ];
        foreach ($materials as $m) {
            Material::firstOrCreate(['material_code' => $m['material_code']], $m + ['status' => 'active']);
        }

        $instTypes = [
            ['type_code' => 'CHROM', 'name' => 'Chromatograph'],
            ['type_code' => 'SPECTRO', 'name' => 'Spectrophotometer'],
            ['type_code' => 'TITR', 'name' => 'Titrator'],
            ['type_code' => 'DISS-A', 'name' => 'Dissolution Apparatus'],
        ];
        foreach ($instTypes as $t) {
            InstrumentType::firstOrCreate(['type_code' => $t['type_code']], $t);
        }

        $lab1 = Location::where('location_code', 'LAB-1')->first();
        $instruments = [
            ['instrument_id_code' => 'HPLC-001', 'name' => 'HPLC System 1 (Shimadzu)', 'instrument_type_id' => InstrumentType::where('type_code', 'CHROM')->value('id'), 'serial_number' => 'SHM-2201', 'manufacturer' => 'Shimadzu', 'model' => 'LC-2030', 'availability' => 'Available'],
            ['instrument_id_code' => 'HPLC-002', 'name' => 'HPLC System 2 (Agilent)', 'instrument_type_id' => InstrumentType::where('type_code', 'CHROM')->value('id'), 'serial_number' => 'AGL-1188', 'manufacturer' => 'Agilent', 'model' => '1260 Infinity', 'availability' => 'Available'],
            ['instrument_id_code' => 'UV-001', 'name' => 'UV Spectrophotometer', 'instrument_type_id' => InstrumentType::where('type_code', 'SPECTRO')->value('id'), 'serial_number' => 'UV-3310', 'manufacturer' => 'PerkinElmer', 'model' => 'Lambda 25', 'availability' => 'Available'],
            ['instrument_id_code' => 'KF-001', 'name' => 'Karl Fischer Titrator', 'instrument_type_id' => InstrumentType::where('type_code', 'TITR')->value('id'), 'serial_number' => 'KF-905', 'manufacturer' => 'Mettler Toledo', 'model' => 'V20S', 'availability' => 'Available'],
            ['instrument_id_code' => 'DISS-001', 'name' => 'Dissolution Apparatus', 'instrument_type_id' => InstrumentType::where('type_code', 'DISS-A')->value('id'), 'serial_number' => 'DS-771', 'manufacturer' => 'Hanson', 'model' => 'SR8 Plus', 'availability' => 'Available'],
        ];
        foreach ($instruments as $i) {
            Instrument::firstOrCreate(['instrument_id_code' => $i['instrument_id_code']], $i + ['location_id' => $lab1?->id, 'department_id' => $qc?->id, 'status' => 'active']);
        }

        $testTypes = [
            ['test_code' => 'TT-ASSAY', 'name' => 'Assay', 'category' => 'Chemical', 'skill' => 'HPLC', 'unit' => '%', 'hours' => 4, 'method' => 'AM-001'],
            ['test_code' => 'TT-RS', 'name' => 'Related Substances', 'category' => 'Chemical', 'skill' => 'HPLC', 'unit' => '%', 'hours' => 5, 'method' => 'RM-001'],
            ['test_code' => 'TT-WATER', 'name' => 'Water Content (KF)', 'category' => 'Chemical', 'skill' => 'KF', 'unit' => '% w/w', 'hours' => 2, 'method' => 'WM-001'],
            ['test_code' => 'TT-IDENT', 'name' => 'Identification (UV)', 'category' => 'Chemical', 'skill' => 'UV', 'unit' => '—', 'hours' => 1.5, 'method' => 'IM-001'],
            ['test_code' => 'TT-DISS', 'name' => 'Dissolution', 'category' => 'Physical', 'skill' => 'DISS', 'unit' => '%', 'hours' => 6, 'method' => 'DM-001'],
            ['test_code' => 'TT-MICRO', 'name' => 'Microbial Limits', 'category' => 'Microbiological', 'skill' => 'MICRO', 'unit' => 'CFU/g', 'hours' => 8, 'method' => 'ML-001'],
        ];
        foreach ($testTypes as $t) {
            TestType::firstOrCreate(['test_code' => $t['test_code']], [
                'name' => $t['name'],
                'category' => $t['category'],
                'department_id' => $qc?->id,
                'default_unit' => $t['unit'],
                'default_method' => $t['method'],
                'required_skill_id' => Skill::where('skill_code', $t['skill'])->value('id'),
                'estimated_duration_hours' => $t['hours'],
                'priority' => 'Normal',
                'status' => 'active',
            ]);
        }

        $methods = [
            ['method_code' => 'AM-001', 'name' => 'Assay by HPLC', 'test_code' => 'TT-ASSAY', 'version' => '2.0', 'desc' => 'Reverse phase HPLC assay against reference standard.'],
            ['method_code' => 'RM-001', 'name' => 'Related Substances by HPLC', 'test_code' => 'TT-RS', 'version' => '1.4', 'desc' => 'Gradient elution method for impurity profiling.'],
            ['method_code' => 'WM-001', 'name' => 'Water by Karl Fischer', 'test_code' => 'TT-WATER', 'version' => '1.1', 'desc' => 'Volumetric KF titration.'],
            ['method_code' => 'IM-001', 'name' => 'Identification by UV', 'test_code' => 'TT-IDENT', 'version' => '1.0', 'desc' => 'UV absorption maxima comparison.'],
            ['method_code' => 'DM-001', 'name' => 'Dissolution Apparatus II', 'test_code' => 'TT-DISS', 'version' => '3.0', 'desc' => 'USP Apparatus II, 900 mL, 50 rpm.'],
            ['method_code' => 'ML-001', 'name' => 'Microbial Limits Test', 'test_code' => 'TT-MICRO', 'version' => '1.2', 'desc' => 'TAMC/TYMC enumeration.'],
        ];
        foreach ($methods as $m) {
            TestMethod::firstOrCreate(['method_code' => $m['method_code']], [
                'name' => $m['name'],
                'test_type_id' => TestType::where('test_code', $m['test_code'])->value('id'),
                'method_description' => $m['desc'],
                'version' => $m['version'],
                'effective_date' => now()->subYear()->toDateString(),
                'status' => 'active',
            ]);
        }

        $specs = [
            ['specification_code' => 'SPEC-001', 'product' => 'PRD-001', 'test' => 'TT-ASSAY', 'value' => '98.0 – 102.0 %', 'unit' => '%'],
            ['specification_code' => 'SPEC-002', 'product' => 'PRD-001', 'test' => 'TT-RS', 'value' => 'NMT 0.5 % any individual impurity', 'unit' => '%'],
            ['specification_code' => 'SPEC-003', 'product' => 'PRD-001', 'test' => 'TT-WATER', 'value' => 'NMT 0.5 %', 'unit' => '%'],
        ];
        foreach ($specs as $s) {
            Specification::firstOrCreate(['specification_code' => $s['specification_code']], [
                'product_id' => Product::where('product_code', $s['product'])->value('id'),
                'test_type_id' => TestType::where('test_code', $s['test'])->value('id'),
                'test_method_id' => TestMethod::where('method_code', TestType::where('test_code', $s['test'])->value('default_method'))->value('id'),
                'specification_value' => $s['value'],
                'unit' => $s['unit'],
                'effective_date' => now()->subMonths(6)->toDateString(),
                'status' => 'active',
            ]);
        }

        // Parameter-driven result entry for a few test types.
        $params = [
            ['test_code' => 'TT-ASSAY', 'name' => 'Assay result', 'unit' => '%', 'required' => true],
            ['test_code' => 'TT-ASSAY', 'name' => 'System suitability (% RSD)', 'unit' => '%', 'required' => true],
            ['test_code' => 'TT-RS', 'name' => 'Any individual impurity', 'unit' => '%', 'required' => true],
            ['test_code' => 'TT-RS', 'name' => 'Total impurities', 'unit' => '%', 'required' => true],
            ['test_code' => 'TT-WATER', 'name' => 'Water content', 'unit' => '% w/w', 'required' => true],
            ['test_code' => 'TT-DISS', 'name' => 'Average dissolution', 'unit' => '%', 'required' => true],
        ];
        foreach ($params as $i => $p) {
            $ttId = TestType::where('test_code', $p['test_code'])->value('id');
            if (! $ttId) {
                continue;
            }
            TestParameter::firstOrCreate(
                ['test_type_id' => $ttId, 'parameter_name' => $p['name']],
                ['field_type' => 'decimal', 'unit' => $p['unit'], 'is_required' => $p['required'], 'sequence' => ($i % 4) + 1, 'status' => 'active']
            );
        }

        $settings = [
            ['key' => 'company_name', 'value' => 'PharmaQC Laboratories', 'group' => 'general', 'description' => 'Company name shown in the application'],
            ['key' => 'work_no_prefix', 'value' => 'WC-', 'group' => 'numbering', 'description' => 'Prefix used for work number generation'],
            ['key' => 'default_required_days', 'value' => '3', 'group' => 'work', 'description' => 'Default required-by days for new work'],
            ['key' => 'max_upload_mb', 'value' => '10', 'group' => 'files', 'description' => 'Maximum attachment size in MB'],
        ];
        foreach ($settings as $s) {
            Setting::firstOrCreate(['key' => $s['key']], $s);
        }
    }
}
