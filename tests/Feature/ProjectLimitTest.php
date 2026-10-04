<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\SampleType;
use App\Models\TestType;
use App\Models\WorkOrder;
use App\Support\WorkStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithQc;
use Tests\TestCase;

class ProjectLimitTest extends TestCase
{
    use RefreshDatabase;
    use InteractsWithQc;

    private Department $department;

    private SampleType $sampleType;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRbac();

        $this->department = Department::create([
            'department_code' => 'QC-'.uniqid(),
            'name' => 'Quality Control',
            'status' => 'active',
        ]);

        $this->sampleType = SampleType::create([
            'sample_type_code' => 'ST-'.uniqid(),
            'name' => 'Raw Material',
            'status' => 'active',
        ]);

        TestType::create([
            'test_code' => 'TT-'.uniqid(),
            'name' => 'Assay',
            'estimated_duration_hours' => 2.00,
            'status' => 'active',
        ]);
    }

    public function test_creating_a_work_order_within_the_limit_succeeds(): void
    {
        $this->setSetting('usage.max_active_projects', '2');

        $manager = $this->makeUser('qc_admin');

        $this->actingAs($manager)
            ->post('/work-orders', $this->validOrderPayload($this->department->id, $this->sampleType->id))
            ->assertRedirectContains('/work-orders/');

        $this->assertSame(1, WorkOrder::count());
    }

    public function test_creating_beyond_the_active_project_limit_is_blocked(): void
    {
        $this->setSetting('usage.max_active_projects', '1');

        $manager = $this->makeUser('qc_admin');

        // First order fills the only slot.
        $this->actingAs($manager)
            ->post('/work-orders', $this->validOrderPayload($this->department->id, $this->sampleType->id))
            ->assertRedirectContains('/work-orders/');

        $this->assertSame(1, WorkOrder::count());

        // Second creation is refused with the professional message.
        $response = $this->actingAs($manager)
            ->post('/work-orders', $this->validOrderPayload($this->department->id, $this->sampleType->id));

        $response->assertSessionHasErrors('batch_no');
        $this->assertStringContainsString(
            'maximum number of active projects',
            session('errors')->first()
        );

        // Nothing was created; the existing order keeps working.
        $this->assertSame(1, WorkOrder::count());
        $this->assertSame(WorkStatus::NEW, WorkOrder::first()->status);
    }

    public function test_completing_an_order_frees_a_slot(): void
    {
        $this->setSetting('usage.max_active_projects', '1');

        $manager = $this->makeUser('qc_admin');

        $this->actingAs($manager)
            ->post('/work-orders', $this->validOrderPayload($this->department->id, $this->sampleType->id));

        $order = WorkOrder::first();
        $order->update(['status' => WorkStatus::COMPLETED]);

        $this->actingAs($manager)
            ->post('/work-orders', $this->validOrderPayload($this->department->id, $this->sampleType->id))
            ->assertRedirectContains('/work-orders/');

        $this->assertSame(2, WorkOrder::count());
    }

    public function test_unlimited_project_setting_allows_creation(): void
    {
        $this->setSetting('usage.max_active_projects', '0');

        $manager = $this->makeUser('qc_admin');

        foreach (range(1, 3) as $i) {
            $this->actingAs($manager)
                ->post('/work-orders', $this->validOrderPayload($this->department->id, $this->sampleType->id))
                ->assertRedirectContains('/work-orders/');
        }

        $this->assertSame(3, WorkOrder::count());
    }

    public function test_existing_orders_are_untouched_when_limit_is_at_capacity(): void
    {
        $this->setSetting('usage.max_active_projects', '1');

        $manager = $this->makeUser('qc_admin');

        $this->actingAs($manager)
            ->post('/work-orders', $this->validOrderPayload($this->department->id, $this->sampleType->id));

        $before = WorkOrder::first()->only(['id', 'work_no', 'status', 'batch_no']);

        $this->actingAs($manager)
            ->post('/work-orders', $this->validOrderPayload($this->department->id, $this->sampleType->id))
            ->assertSessionHasErrors();

        $this->assertSame($before, WorkOrder::first()->only(['id', 'work_no', 'status', 'batch_no']));
    }
}
