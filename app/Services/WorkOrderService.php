<?php

namespace App\Services;

use App\Models\WorkOrder;
use App\Models\WorkOrderTest;
use App\Support\AppSettings;
use App\Support\WorkStatus;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class WorkOrderService
{
    public function __construct(private StatusService $status)
    {
    }

    public function generateWorkNo(): string
    {
        $prefix = 'WC-'.now()->format('Ym').'-';
        $last = WorkOrder::where('work_no', 'like', $prefix.'%')->orderByDesc('work_no')->value('work_no');
        $seq = $last ? ((int) substr($last, -4)) + 1 : 1;

        return $prefix.str_pad((string) $seq, 4, '0', STR_PAD_LEFT);
    }

    public function generateSampleId(): string
    {
        $prefix = 'SMP-'.now()->format('Ym').'-';
        $last = WorkOrder::where('sample_id', 'like', $prefix.'%')->orderByDesc('sample_id')->value('sample_id');
        $seq = $last ? ((int) substr($last, -4)) + 1 : 1;

        return $prefix.str_pad((string) $seq, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Create a work order with its individual tests.
     *
     * @param  array<string, mixed>  $data
     * @param  array<int, array<string, mixed>>  $tests
     */
    public function create(array $data, array $tests, \App\Models\User $actor): WorkOrder
    {
        $this->guardActiveProjectLimit();

        return DB::transaction(function () use ($data, $tests, $actor) {
            $order = WorkOrder::create([
                'work_no' => $data['work_no'] ?? $this->generateWorkNo(),
                'request_date' => $data['request_date'],
                'requested_by_id' => $actor->id,
                'department_id' => $data['department_id'],
                'work_category_id' => $data['work_category_id'] ?? null,
                'sample_id' => $data['sample_id'] ?? $this->generateSampleId(),
                'sample_type_id' => $data['sample_type_id'],
                'product_id' => $data['product_id'] ?? null,
                'material_id' => $data['material_id'] ?? null,
                'batch_no' => $data['batch_no'],
                'ar_no' => $data['ar_no'] ?? null,
                'quantity' => $data['quantity'] ?? null,
                'received_date' => $data['received_date'] ?? null,
                'sampling_date' => $data['sampling_date'] ?? null,
                'storage_condition' => $data['storage_condition'] ?? null,
                'required_date' => $data['required_date'],
                'priority_id' => $data['priority_id'] ?? null,
                'priority' => $data['priority'] ?? 'Normal',
                'status' => WorkStatus::NEW,
                'remarks' => $data['remarks'] ?? null,
            ]);

            foreach ($tests as $t) {
                WorkOrderTest::create([
                    'work_order_id' => $order->id,
                    'test_type_id' => $t['test_type_id'],
                    'test_method_id' => $t['test_method_id'] ?? null,
                    'specification' => $t['specification'] ?? null,
                    'priority' => $t['priority'] ?? $order->priority,
                    'estimated_duration' => $t['estimated_duration'] ?? 2.00,
                    'status' => WorkStatus::NEW,
                ]);
            }

            AuditLogger::log('Work Orders', 'create', $order->id, null, [
                'work_no' => $order->work_no,
                'sample_id' => $order->sample_id,
                'tests' => count($tests),
            ]);

            NotificationService::toRoles(
                ['qc_supervisor', 'qc_admin', 'qc_hod'],
                'work.created',
                'New Work Request: '.$order->work_no,
                'Sample '.$order->sample_id.' requires testing ('.count($tests).' test(s)).',
                route('work-orders.show', $order)
            );

            return $order->load('tests.testType', 'tests.testMethod');
        });
    }

    /**
     * Admin-controlled maximum of simultaneously active projects (work requests).
     * Active = any work order not COMPLETED/CANCELLED. Existing orders are never touched.
     */
    private function guardActiveProjectLimit(): void
    {
        $max = AppSettings::maxActiveProjects();

        if ($max <= 0) {
            return; // unlimited
        }

        $active = WorkOrder::whereIn('status', WorkStatus::OPEN)->count();

        if ($active >= $max) {
            throw ValidationException::withMessages([
                'batch_no' => 'The maximum number of active projects has been reached. Please complete or deactivate an existing project before creating another.',
            ]);
        }
    }
}
