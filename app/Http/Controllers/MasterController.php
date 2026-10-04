<?php

namespace App\Http\Controllers;

use App\Services\AuditLogger;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * Generic CRUD driven by the MASTER_CONFIG map, shared by all QC masters.
 */
class MasterController extends Controller
{
    /**
     * master key => configuration (built per request; option lists are lazy).
     * columns: [db_column => [label, type, options-callback|null, required]]
     */
    public static function configMap(): array
    {
        return [
        'departments' => [
            'label' => 'Departments',
            'singular' => 'Department',
            'model' => \App\Models\Department::class,
            'search' => ['department_code', 'name'],
            'columns' => [
                'department_code' => ['Department Code', 'text', null, true],
                'name' => ['Name', 'text', null, true],
                'description' => ['Description', 'textarea', null, false],
                'status' => ['Status', 'status', null, true],
            ],
            'table' => [
                'department_code' => 'Code',
                'name' => 'Name',
                'description' => 'Description',
                'status' => 'Status',
            ],
        ],
        'designations' => [
            'label' => 'Designations',
            'singular' => 'Designation',
            'model' => \App\Models\Designation::class,
            'search' => ['designation_code', 'name'],
            'columns' => [
                'designation_code' => ['Designation Code', 'text', null, true],
                'name' => ['Name', 'text', null, true],
                'description' => ['Description', 'textarea', null, false],
                'status' => ['Status', 'status', null, true],
            ],
            'table' => [
                'designation_code' => 'Code',
                'name' => 'Name',
                'status' => 'Status',
            ],
        ],
        'products' => [
            'label' => 'Products',
            'singular' => 'Product',
            'model' => \App\Models\Product::class,
            'search' => ['product_code', 'product_name', 'generic_name'],
            'columns' => [
                'product_code' => ['Product Code', 'text', null, true],
                'product_name' => ['Product Name', 'text', null, true],
                'generic_name' => ['Generic Name', 'text', null, false],
                'product_type' => ['Product Type', 'select', ['Finished Product', 'Raw Material', 'Packaging'], true],
                'dosage_form' => ['Dosage Form', 'text', null, false],
                'strength' => ['Strength', 'text', null, false],
                'manufacturer' => ['Manufacturer', 'text', null, false],
                'department_id' => ['Department', 'fk', fn () => \App\Models\Department::active()->orderBy('name')->get(), false],
                'status' => ['Status', 'status', null, true],
            ],
            'table' => [
                'product_code' => 'Code',
                'product_name' => 'Product',
                'product_type' => 'Type',
                'strength' => 'Strength',
                'status' => 'Status',
            ],
        ],
        'materials' => [
            'label' => 'Materials',
            'singular' => 'Material',
            'model' => \App\Models\Material::class,
            'search' => ['material_code', 'material_name'],
            'columns' => [
                'material_code' => ['Material Code', 'text', null, true],
                'material_name' => ['Material Name', 'text', null, true],
                'material_type' => ['Material Type', 'text', null, false],
                'material_category' => ['Category', 'text', null, false],
                'specification' => ['Specification', 'textarea', null, false],
                'storage_condition' => ['Storage Condition', 'text', null, false],
                'supplier' => ['Supplier', 'text', null, false],
                'status' => ['Status', 'status', null, true],
            ],
            'table' => [
                'material_code' => 'Code',
                'material_name' => 'Material',
                'material_type' => 'Type',
                'supplier' => 'Supplier',
                'status' => 'Status',
            ],
        ],
        'sample-types' => [
            'label' => 'Sample Types',
            'singular' => 'Sample Type',
            'model' => \App\Models\SampleType::class,
            'search' => ['sample_type_code', 'name'],
            'columns' => [
                'sample_type_code' => ['Code', 'text', null, true],
                'name' => ['Name', 'text', null, true],
                'description' => ['Description', 'textarea', null, false],
                'status' => ['Status', 'status', null, true],
            ],
            'table' => [
                'sample_type_code' => 'Code',
                'name' => 'Name',
                'status' => 'Status',
            ],
        ],
        'work-categories' => [
            'label' => 'Work Categories',
            'singular' => 'Work Category',
            'model' => \App\Models\WorkCategory::class,
            'search' => ['category_code', 'name'],
            'columns' => [
                'category_code' => ['Code', 'text', null, true],
                'name' => ['Name', 'text', null, true],
                'description' => ['Description', 'textarea', null, false],
                'status' => ['Status', 'status', null, true],
            ],
            'table' => [
                'category_code' => 'Code',
                'name' => 'Name',
                'status' => 'Status',
            ],
        ],
        'priorities' => [
            'label' => 'Priorities',
            'singular' => 'Priority',
            'model' => \App\Models\Priority::class,
            'search' => ['priority_code', 'name'],
            'columns' => [
                'priority_code' => ['Code', 'text', null, true],
                'name' => ['Name', 'text', null, true],
                'level' => ['Level (higher = more urgent)', 'number', null, true],
                'description' => ['Description', 'textarea', null, false],
                'status' => ['Status', 'status', null, true],
            ],
            'table' => [
                'priority_code' => 'Code',
                'name' => 'Name',
                'level' => 'Level',
                'status' => 'Status',
            ],
        ],
        'skills' => [
            'label' => 'Skills',
            'singular' => 'Skill',
            'model' => \App\Models\Skill::class,
            'search' => ['skill_code', 'name', 'category'],
            'columns' => [
                'skill_code' => ['Code', 'text', null, true],
                'name' => ['Name', 'text', null, true],
                'category' => ['Category', 'text', null, false],
                'description' => ['Description', 'textarea', null, false],
                'status' => ['Status', 'status', null, true],
            ],
            'table' => [
                'skill_code' => 'Code',
                'name' => 'Skill',
                'category' => 'Category',
                'status' => 'Status',
            ],
        ],
        'instrument-types' => [
            'label' => 'Instrument Types',
            'singular' => 'Instrument Type',
            'model' => \App\Models\InstrumentType::class,
            'search' => ['type_code', 'name'],
            'columns' => [
                'type_code' => ['Code', 'text', null, true],
                'name' => ['Name', 'text', null, true],
                'description' => ['Description', 'textarea', null, false],
                'status' => ['Status', 'status', null, true],
            ],
            'table' => [
                'type_code' => 'Code',
                'name' => 'Name',
                'status' => 'Status',
            ],
        ],
        'instruments' => [
            'label' => 'Instruments',
            'singular' => 'Instrument',
            'model' => \App\Models\Instrument::class,
            'search' => ['instrument_id_code', 'name', 'serial_number'],
            'columns' => [
                'instrument_id_code' => ['Instrument ID', 'text', null, true],
                'name' => ['Name', 'text', null, true],
                'instrument_type_id' => ['Instrument Type', 'fk', fn () => \App\Models\InstrumentType::active()->orderBy('name')->get(), true],
                'instrument_code' => ['Asset Code', 'text', null, false],
                'serial_number' => ['Serial Number', 'text', null, false],
                'manufacturer' => ['Manufacturer', 'text', null, false],
                'model' => ['Model', 'text', null, false],
                'location_id' => ['Location', 'fk', fn () => \App\Models\Location::active()->orderBy('name')->get(), false],
                'availability' => ['Availability', 'select', ['Available', 'Busy', 'Maintenance'], true],
                'status' => ['Status', 'status', null, true],
            ],
            'table' => [
                'instrument_id_code' => 'Instrument ID',
                'name' => 'Name',
                'serial_number' => 'Serial No.',
                'availability' => 'Availability',
                'status' => 'Status',
            ],
        ],
        'test-types' => [
            'label' => 'Test Types',
            'singular' => 'Test Type',
            'model' => \App\Models\TestType::class,
            'search' => ['test_code', 'name'],
            'columns' => [
                'test_code' => ['Code', 'text', null, true],
                'name' => ['Name', 'text', null, true],
                'category' => ['Category', 'text', null, false],
                'department_id' => ['Department', 'fk', fn () => \App\Models\Department::active()->orderBy('name')->get(), false],
                'default_unit' => ['Default Unit', 'text', null, false],
                'default_method' => ['Default Method', 'text', null, false],
                'required_skill_id' => ['Required Skill', 'fk', fn () => \App\Models\Skill::active()->orderBy('name')->get(), false],
                'estimated_duration_hours' => ['Estimated Duration (hours)', 'number', null, true],
                'status' => ['Status', 'status', null, true],
            ],
            'table' => [
                'test_code' => 'Code',
                'name' => 'Test',
                'category' => 'Category',
                'estimated_duration_hours' => 'Est. Hours',
                'status' => 'Status',
            ],
        ],
        'test-methods' => [
            'label' => 'Test Methods',
            'singular' => 'Test Method',
            'model' => \App\Models\TestMethod::class,
            'search' => ['method_code', 'name'],
            'columns' => [
                'method_code' => ['Method Code', 'text', null, true],
                'name' => ['Name', 'text', null, true],
                'test_type_id' => ['Test Type', 'fk', fn () => \App\Models\TestType::active()->orderBy('name')->get(), true],
                'method_description' => ['Description', 'textarea', null, false],
                'version' => ['Version', 'text', null, true],
                'effective_date' => ['Effective Date', 'date', null, false],
                'status' => ['Status', 'status', null, true],
            ],
            'table' => [
                'method_code' => 'Code',
                'name' => 'Method',
                'version' => 'Version',
                'status' => 'Status',
            ],
        ],
        'specifications' => [
            'label' => 'Specifications',
            'singular' => 'Specification',
            'model' => \App\Models\Specification::class,
            'search' => ['specification_code', 'specification_value'],
            'columns' => [
                'specification_code' => ['Code', 'text', null, true],
                'product_id' => ['Product', 'fk', fn () => \App\Models\Product::active()->orderBy('product_name')->get(), false],
                'test_type_id' => ['Test Type', 'fk', fn () => \App\Models\TestType::active()->orderBy('name')->get(), true],
                'test_method_id' => ['Test Method', 'fk', fn () => \App\Models\TestMethod::active()->orderBy('name')->get(), false],
                'specification_value' => ['Specification', 'text', null, true],
                'unit' => ['Unit', 'text', null, false],
                'effective_date' => ['Effective Date', 'date', null, false],
                'status' => ['Status', 'status', null, true],
            ],
            'table' => [
                'specification_code' => 'Code',
                'specification_value' => 'Specification',
                'unit' => 'Unit',
                'status' => 'Status',
            ],
        ],
        'locations' => [
            'label' => 'Locations',
            'singular' => 'Location',
            'model' => \App\Models\Location::class,
            'search' => ['location_code', 'name'],
            'columns' => [
                'location_code' => ['Code', 'text', null, true],
                'name' => ['Name', 'text', null, true],
                'department_id' => ['Department', 'fk', fn () => \App\Models\Department::active()->orderBy('name')->get(), false],
                'description' => ['Description', 'textarea', null, false],
                'status' => ['Status', 'status', null, true],
            ],
            'table' => [
                'location_code' => 'Code',
                'name' => 'Name',
                'status' => 'Status',
            ],
        ],
        'settings' => [
            'label' => 'Settings',
            'singular' => 'Setting',
            'model' => \App\Models\Setting::class,
            'search' => ['key', 'value'],
            'columns' => [
                'key' => ['Setting Key', 'text', null, true],
                'value' => ['Value', 'textarea', null, false],
                'group' => ['Group', 'text', null, true],
                'description' => ['Description', 'text', null, false],
            ],
            'table' => [
                'key' => 'Key',
                'value' => 'Value',
                'group' => 'Group',
                'description' => 'Description',
            ],
        ],
        ];
    }

    public function index(Request $request, string $master)
    {
        $config = $this->config($master);

        /** @var class-string<Model> $model */
        $model = $config['model'];
        $query = $model::query();

        if ($search = $request->input('q')) {
            $query->where(function ($q) use ($config, $search) {
                foreach ($config['search'] as $i => $col) {
                    $i === 0 ? $q->where($col, 'like', "%{$search}%") : $q->orWhere($col, 'like', "%{$search}%");
                }
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        $items = $query->paginate(15)->withQueryString();

        return view('masters.index', [
            'config' => $config,
            'master' => $master,
            'items' => $items,
        ]);
    }

    public function create(string $master)
    {
        $config = $this->config($master);

        return view('masters.form', [
            'config' => $config,
            'master' => $master,
            'item' => null,
        ]);
    }

    public function store(Request $request, string $master)
    {
        $config = $this->config($master);
        $data = $request->validate($this->rules($config));

        /** @var class-string<Model> $model */
        $model = $config['model'];
        $item = $model::create($data);

        AuditLogger::log($config['label'], 'create', $item->getKey(), null, $data);

        return redirect()->route('masters.index', $master)
            ->with('success', "{$config['singular']} created successfully.");
    }

    public function edit(string $master, int $id)
    {
        $config = $this->config($master);
        $item = $config['model']::findOrFail($id);

        return view('masters.form', [
            'config' => $config,
            'master' => $master,
            'item' => $item,
        ]);
    }

    public function update(Request $request, string $master, int $id)
    {
        $config = $this->config($master);
        $item = $config['model']::findOrFail($id);
        $data = $request->validate($this->rules($config, $id));

        $old = $item->only(array_keys($data));
        $item->update($data);

        AuditLogger::log($config['label'], 'update', $item->getKey(), $old, $data);

        return redirect()->route('masters.index', $master)
            ->with('success', "{$config['singular']} updated successfully.");
    }

    public function destroy(string $master, int $id)
    {
        $config = $this->config($master);
        $item = $config['model']::findOrFail($id);

        try {
            $item->delete();
            AuditLogger::log($config['label'], 'delete', $id);
        } catch (\Exception $e) {
            return back()->withErrors(['item' => 'This record is referenced by other data and cannot be deleted. Mark it inactive instead.']);
        }

        return back()->with('success', "{$config['singular']} deleted.");
    }

    private function config(string $master): array
    {
        $map = self::configMap();

        abort_unless(isset($map[$master]), 404);

        return $map[$master];
    }

    private function rules(array $config, ?int $ignoreId = null): array
    {
        $rules = [];

        foreach ($config['columns'] as $column => [$label, $type, $options, $required]) {
            $field = $column;
            $base = [];

            if ($required) {
                $base[] = 'required';
            } else {
                $base[] = 'nullable';
            }

            if ($type === 'fk') {
                $table = match ($column) {
                    'department_id' => 'departments',
                    'product_id' => 'products',
                    'material_id' => 'materials',
                    'instrument_type_id' => 'instrument_types',
                    'location_id' => 'locations',
                    'test_type_id' => 'test_types',
                    'test_method_id' => 'test_methods',
                    'required_skill_id' => 'skills',
                    default => str_replace('_id', 's', $column),
                };
                $base[] = "exists:{$table},id";
            } elseif ($type === 'number') {
                $base[] = 'numeric';
                $base[] = 'min:0';
            } elseif ($type === 'date') {
                $base[] = 'date';
            } elseif ($type === 'status') {
                $base[] = Rule::in(['active', 'inactive']);
            } elseif ($type === 'select') {
                $base[] = Rule::in($options);
            } else {
                $base[] = 'string';
                $base[] = 'max:1000';
            }

            // Unique check on the first column (codes).
            $firstColumn = array_key_first($config['columns']);
            if ($column === $firstColumn) {
                $table = (new ($config['model']))->getTable();
                $base[] = Rule::unique($table, $column)->ignore($ignoreId);
            }

            $rules[$field] = $base;
        }

        return $rules;
    }
}
