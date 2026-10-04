<?php

namespace Database\Seeders;

use App\Models\AllocationHistory;
use App\Models\AppNotification;
use App\Models\AuditLog;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Material;
use App\Models\Priority;
use App\Models\Product;
use App\Models\ReviewHistory;
use App\Models\SampleType;
use App\Models\TestResult;
use App\Models\User;
use App\Models\WorkAllocation;
use App\Models\WorkCategory;
use App\Models\WorkComment;
use App\Models\WorkOrder;
use App\Models\WorkOrderTest;
use App\Support\WorkStatus;
use Illuminate\Database\Seeder;

class WorkOrderSeeder extends Seeder
{
    public function run(): void
    {
        if (WorkOrder::count() > 0) {
            return; // demo work data already present
        }

        $qc = Department::where('department_code', 'QC')->first();
        $supervisor = User::where('username', 'supervisor_qc')->first();
        $analysts = [
            'raj' => Employee::where('employee_code', 'EMP-002')->first(),
            'neha' => Employee::where('employee_code', 'EMP-003')->first(),
            'amit' => Employee::where('employee_code', 'EMP-004')->first(),
            'priya' => Employee::where('employee_code', 'EMP-005')->first(),
        ];
        $product = Product::where('product_code', 'PRD-001')->first();
        $material = Material::where('material_code', 'MAT-001')->first();
        $fp = SampleType::where('sample_type_code', 'FP')->first();
        $rm = SampleType::where('sample_type_code', 'RM')->first();
        $category = WorkCategory::where('category_code', 'REL')->first();
        $priorityId = Priority::where('priority_code', 'HIGH')->value('id');

        $assay = \App\Models\TestType::where('test_code', 'TT-ASSAY')->first();
        $rs = \App\Models\TestType::where('test_code', 'TT-RS')->first();
        $water = \App\Models\TestType::where('test_code', 'TT-WATER')->first();
        $ident = \App\Models\TestType::where('test_code', 'TT-IDENT')->first();
        $diss = \App\Models\TestType::where('test_code', 'TT-DISS')->first();

        $spec = fn ($tt) => match ($tt?->test_code) {
            'TT-ASSAY' => '98.0 – 102.0 %',
            'TT-RS' => 'NMT 0.5 % any individual impurity',
            'TT-WATER' => 'NMT 0.5 %',
            'TT-DISS' => 'NLT 80% in 45 min',
            default => 'As per specification',
        };

        $makeOrder = function (array $d) use ($qc, $supervisor, $product, $material, $fp, $rm, $category) {
            return WorkOrder::create([
                'work_no' => $d['work_no'],
                'request_date' => $d['request_date'],
                'requested_by_id' => $supervisor->id,
                'department_id' => $qc->id,
                'work_category_id' => $category?->id,
                'sample_id' => $d['sample_id'],
                'sample_type_id' => $d['sample'] === 'rm' ? $rm->id : $fp->id,
                'product_id' => $d['sample'] === 'rm' ? null : $product?->id,
                'material_id' => $d['sample'] === 'rm' ? $material?->id : null,
                'batch_no' => $d['batch'],
                'ar_no' => 'AR-'.$d['work_no'],
                'quantity' => '100 tablets',
                'received_date' => $d['request_date'],
                'sampling_date' => $d['request_date'],
                'storage_condition' => '2–8 °C, protected from light',
                'required_date' => $d['required_date'],
                'priority_id' => Priority::where('name', $d['priority'])->value('id'),
                'priority' => $d['priority'],
                'status' => $d['status'],
                'remarks' => $d['remarks'] ?? null,
            ]);
        };

        $makeTest = function (WorkOrder $o, $tt, string $status, $analyst = null, array $extra = []) use ($spec, $supervisor) {
            $test = WorkOrderTest::create([
                'work_order_id' => $o->id,
                'test_type_id' => $tt->id,
                'test_method_id' => $tt->methods()->value('id'),
                'specification' => $spec($tt),
                'priority' => $o->priority,
                'estimated_duration' => $tt->estimated_duration_hours,
                'status' => $status,
                'assigned_analyst_id' => $analyst?->id,
                'review_status' => $extra['review_status'] ?? null,
            ]);

            if ($analyst) {
                WorkAllocation::create([
                    'work_order_test_id' => $test->id,
                    'analyst_id' => $analyst->id,
                    'allocated_by_id' => $supervisor->id,
                    'instrument_id' => $extra['instrument_id'] ?? null,
                    'status' => 'ACTIVE',
                ]);
                AllocationHistory::create([
                    'work_order_test_id' => $test->id,
                    'previous_analyst_id' => null,
                    'new_analyst_id' => $analyst->id,
                    'allocated_by_id' => $supervisor->id,
                    'status' => WorkStatus::NEW,
                    'reason' => 'Initial allocation',
                ]);
            }

            return $test;
        };

        $makeResult = function (WorkOrderTest $test, $analyst, array $d) {
            return TestResult::create($d + [
                'work_order_test_id' => $test->id,
                'analyst_id' => $analyst->id,
                'entered_at' => $d['entered_at'] ?? now(),
            ]);
        };

        // 1. NEW work order — pending allocation (2 tests)
        $o1 = $makeOrder(['work_no' => 'WC-202610-0001', 'sample_id' => 'SMP-202610-0001', 'sample' => 'fp', 'batch' => 'B-2026-0451', 'request_date' => now()->subDays(2)->toDateString(), 'required_date' => now()->addDays(1)->toDateString(), 'priority' => 'High', 'status' => WorkStatus::NEW]);
        $makeTest($o1, $assay, WorkStatus::NEW);
        $makeTest($o1, $rs, WorkStatus::NEW);

        // 2. ALLOCATED — accepted by no one yet
        $o2 = $makeOrder(['work_no' => 'WC-202610-0002', 'sample_id' => 'SMP-202610-0002', 'sample' => 'rm', 'batch' => 'API-7712', 'request_date' => now()->subDays(3)->toDateString(), 'required_date' => now()->addDays(2)->toDateString(), 'priority' => 'Normal', 'status' => WorkStatus::ALLOCATED]);
        $makeTest($o2, $water, WorkStatus::ALLOCATED, $analysts['priya']);
        $makeTest($o2, $ident, WorkStatus::ALLOCATED, $analysts['raj']);

        // 3. IN PROGRESS with result saved (not submitted)
        $o3 = $makeOrder(['work_no' => 'WC-202610-0003', 'sample_id' => 'SMP-202610-0003', 'sample' => 'fp', 'batch' => 'B-2026-0448', 'request_date' => now()->subDays(5)->toDateString(), 'required_date' => now()->addDays(1)->toDateString(), 'priority' => 'Critical', 'status' => WorkStatus::IN_PROGRESS]);
        $t3 = $makeTest($o3, $assay, WorkStatus::IN_PROGRESS, $analysts['raj'], ['instrument_id' => \App\Models\Instrument::where('instrument_id_code', 'HPLC-001')->value('id')]);
        $makeResult($t3, $analysts['raj'], [
            'result_value' => '99.62', 'unit' => '%', 'specification_reference' => '98.0 – 102.0 %',
            'result_status' => 'PASS', 'start_time' => now()->subDay(), 'instrument_id' => \App\Models\Instrument::where('instrument_id_code', 'HPLC-001')->value('id'),
        ]);

        // 4. SUBMITTED — awaiting review
        $o4 = $makeOrder(['work_no' => 'WC-202610-0004', 'sample_id' => 'SMP-202610-0004', 'sample' => 'fp', 'batch' => 'B-2026-0440', 'request_date' => now()->subDays(7)->toDateString(), 'required_date' => now()->toDateString(), 'priority' => 'High', 'status' => WorkStatus::SUBMITTED]);
        $t4 = $makeTest($o4, $diss, WorkStatus::SUBMITTED, $analysts['neha'], ['review_status' => 'PENDING']);
        $makeResult($t4, $analysts['neha'], [
            'result_value' => '92.4', 'unit' => '%', 'specification_reference' => 'NLT 80% in 45 min',
            'result_status' => 'PASS', 'start_time' => now()->subDays(2), 'end_time' => now()->subDay(),
        ]);

        // 5. UNDER REVIEW
        $o5 = $makeOrder(['work_no' => 'WC-202610-0005', 'sample_id' => 'SMP-202610-0005', 'sample' => 'fp', 'batch' => 'B-2026-0437', 'request_date' => now()->subDays(8)->toDateString(), 'required_date' => now()->addDays(2)->toDateString(), 'priority' => 'Normal', 'status' => WorkStatus::UNDER_REVIEW]);
        $t5 = $makeTest($o5, $rs, WorkStatus::UNDER_REVIEW, $analysts['raj'], ['review_status' => 'IN_REVIEW']);
        $makeResult($t5, $analysts['raj'], [
            'result_value' => '0.12', 'unit' => '%', 'specification_reference' => 'NMT 0.5 %',
            'result_status' => 'PASS', 'start_time' => now()->subDays(3), 'end_time' => now()->subDays(2),
        ]);

        // 6. REWORK — reviewer asked for repeat
        $o6 = $makeOrder(['work_no' => 'WC-202610-0006', 'sample_id' => 'SMP-202610-0006', 'sample' => 'fp', 'batch' => 'B-2026-0430', 'request_date' => now()->subDays(10)->toDateString(), 'required_date' => now()->addDays(3)->toDateString(), 'priority' => 'Normal', 'status' => WorkStatus::REWORK]);
        $t6 = $makeTest($o6, $water, WorkStatus::REWORK, $analysts['priya'], ['review_status' => 'REWORK']);
        $reviewer = User::where('username', 'reviewer_qc')->first();
        $r6 = $makeResult($t6, $analysts['priya'], [
            'result_value' => '0.72', 'unit' => '% w/w', 'specification_reference' => 'NMT 0.5 %',
            'result_status' => 'OOS', 'start_time' => now()->subDays(4), 'end_time' => now()->subDays(3),
            'reviewed_by_id' => $reviewer?->id, 'reviewed_at' => now()->subDays(2),
            'rework_reason' => 'System suitability failed before injection. Repeat the determination with a freshly prepared sample.',
        ]);
        ReviewHistory::create([
            'work_order_test_id' => $t6->id,
            'reviewer_id' => $reviewer->id,
            'action' => 'REWORK',
            'previous_status' => WorkStatus::UNDER_REVIEW,
            'new_status' => WorkStatus::REWORK,
            'reason' => 'System suitability failed before injection. Repeat the determination with a freshly prepared sample.',
        ]);

        // 7. COMPLETED — approved and closed
        $o7 = $makeOrder(['work_no' => 'WC-202609-0007', 'sample_id' => 'SMP-202609-0007', 'sample' => 'fp', 'batch' => 'B-2026-0420', 'request_date' => now()->subDays(18)->toDateString(), 'required_date' => now()->subDays(12)->toDateString(), 'priority' => 'Normal', 'status' => WorkStatus::COMPLETED]);
        $t7 = $makeTest($o7, $assay, WorkStatus::COMPLETED, $analysts['neha'], ['review_status' => 'APPROVED']);
        $makeResult($t7, $analysts['neha'], [
            'result_value' => '100.1', 'unit' => '%', 'specification_reference' => '98.0 – 102.0 %',
            'result_status' => 'PASS', 'start_time' => now()->subDays(17), 'end_time' => now()->subDays(16),
            'reviewed_by_id' => $reviewer?->id, 'reviewed_at' => now()->subDays(15),
        ]);
        ReviewHistory::create([
            'work_order_test_id' => $t7->id,
            'reviewer_id' => $reviewer->id,
            'action' => 'APPROVE',
            'previous_status' => WorkStatus::UNDER_REVIEW,
            'new_status' => WorkStatus::APPROVED,
            'comments' => 'Results verified against raw data. Approved.',
        ]);

        // 8. Overdue in-progress work
        $o8 = $makeOrder(['work_no' => 'WC-202609-0008', 'sample_id' => 'SMP-202609-0008', 'sample' => 'fp', 'batch' => 'B-2026-0415', 'request_date' => now()->subDays(14)->toDateString(), 'required_date' => now()->subDays(2)->toDateString(), 'priority' => 'Critical', 'status' => WorkStatus::IN_PROGRESS]);
        $makeTest($o8, $diss, WorkStatus::IN_PROGRESS, $analysts['amit']);
        $makeTest($o8, $ident, WorkStatus::ALLOCATED, $analysts['neha']);

        // Comments
        WorkComment::create(['work_order_id' => $o3->id, 'work_order_test_id' => $t3->id, 'user_id' => $supervisor->id, 'comment' => 'Priority sample — please finish before the batch release meeting.', 'action_type' => 'comment']);
        WorkComment::create(['work_order_id' => $o6->id, 'work_order_test_id' => $t6->id, 'user_id' => $reviewer->id, 'comment' => 'Repeat KF with a fresh titration vessel and attach the raw titration curve.', 'action_type' => 'rework']);
        WorkComment::create(['work_order_id' => $o8->id, 'user_id' => $supervisor->id, 'comment' => 'This order is overdue — escalate today.', 'action_type' => 'comment']);

        // Notifications
        $notify = function (User $user, string $type, string $title, string $message, string $link = null) {
            if ($user) {
                AppNotification::create(['user_id' => $user->id, 'type' => $type, 'title' => $title, 'message' => $message, 'link' => $link]);
            }
        };

        foreach (['analyst_raj', 'analyst_neha', 'analyst_amit', 'analyst_priya'] as $uname) {
            $u = User::where('username', $uname)->first();
            $notify($u, 'work.allocated', 'New Test Allocated', 'You have new tests assigned in the QC work allocation system.', '/my-work');
        }
        $notify($reviewer, 'work.submitted', 'Submitted for Review', 'Tests are waiting for your review.', '/review');
        $notify(User::where('username', 'hod_qc')->first(), 'digest.daily', 'Daily QC Summary', '2 work orders are overdue and 2 tests await review.', '/dashboard');

        // Audit trail
        foreach (WorkOrder::all() as $wo) {
            AuditLog::create([
                'user_id' => $supervisor->id,
                'module' => 'Work Orders',
                'record_id' => (string) $wo->id,
                'action' => 'create',
                'new_values' => ['work_no' => $wo->work_no, 'status' => $wo->status],
                'ip_address' => '127.0.0.1',
            ]);
        }
        AuditLog::create([
            'user_id' => $supervisor->id,
            'module' => 'Allocations',
            'record_id' => null,
            'action' => 'allocate',
            'new_values' => ['note' => 'Demo allocations for seed data'],
            'ip_address' => '127.0.0.1',
        ]);
    }
}
