<?php

namespace App\Http\Controllers;

use App\Models\WorkOrderTest;
use App\Services\ReviewService;
use App\Services\StatusService;
use App\Support\WorkStatus;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    public function __construct(private ReviewService $review, private StatusService $status)
    {
    }

    public function index(Request $request)
    {
        $view = $request->input('view', 'pending');

        $query = WorkOrderTest::query()
            ->with(['workOrder:id,work_no,sample_id,required_date,priority', 'testType:id,name', 'assignedAnalyst'])
            ->latest('updated_at');

        if ($view === 'pending') {
            $query->where('status', WorkStatus::SUBMITTED);
        } elseif ($view === 'in_review') {
            $query->where('status', WorkStatus::UNDER_REVIEW);
        } elseif ($view === 'rework') {
            $query->where('status', WorkStatus::REWORK);
        } elseif ($view === 'history') {
            $query->whereIn('status', [WorkStatus::APPROVED, WorkStatus::REWORK, WorkStatus::REJECTED, WorkStatus::COMPLETED]);
        }

        if ($search = $request->input('q')) {
            $query->whereHas('workOrder', fn ($q) => $q->where('work_no', 'like', "%{$search}%")
                ->orWhere('sample_id', 'like', "%{$search}%"));
        }

        $tests = $query->paginate(15)->withQueryString();

        return view('review.index', [
            'tests' => $tests,
            'view' => $view,
            'counts' => [
                'pending' => WorkOrderTest::where('status', WorkStatus::SUBMITTED)->count(),
                'in_review' => WorkOrderTest::where('status', WorkStatus::UNDER_REVIEW)->count(),
                'rework' => WorkOrderTest::where('status', WorkStatus::REWORK)->count(),
                'history' => WorkOrderTest::whereIn('status', [WorkStatus::APPROVED, WorkStatus::REWORK, WorkStatus::REJECTED, WorkStatus::COMPLETED])->count(),
            ],
        ]);
    }

    public function show(Request $request, WorkOrderTest $test)
    {
        // Opening a submitted test moves it UNDER_REVIEW (validated transition).
        if ($test->status === WorkStatus::SUBMITTED && $request->user()->hasPermission('review.approve')) {
            try {
                $this->status->transitionTest($test, WorkStatus::UNDER_REVIEW);
            } catch (\RuntimeException) {
                // keep current status if racing
            }
            $test->refresh();
        }

        $test->load([
            'workOrder.department', 'workOrder.product', 'workOrder.sampleType', 'workOrder.requestedBy',
            'testType', 'testMethod', 'assignedAnalyst', 'assignedInstrument',
            'latestResult.parameters', 'latestResult.reviewedBy', 'latestResult.method',
            'reviewHistory.reviewer', 'workOrder.comments.user', 'results',
        ]);

        return view('review.show', [
            'test' => $test,
            'result' => $test->latestResult,
        ]);
    }

    public function act(Request $request, WorkOrderTest $test)
    {
        $data = $request->validate([
            'action' => ['required', 'in:approve,rework,reject'],
            'reason' => ['nullable', 'string', 'max:2000'],
        ], [
            'reason.required' => 'A reason/comment is required.',
        ]);

        if ($test->status !== WorkStatus::UNDER_REVIEW) {
            return back()->withErrors(['action' => 'This test is not under review.']);
        }

        try {
            if ($data['action'] === 'approve') {
                $this->review->approve($test, $request->user(), $data['reason'] ?? null);
            } elseif ($data['action'] === 'rework') {
                $request->validate(['reason' => ['required', 'string', 'max:2000']]);
                $this->review->requestRework($test, $request->user(), $data['reason']);
            } else {
                $request->validate(['reason' => ['required', 'string', 'max:2000']]);
                $this->review->reject($test, $request->user(), $data['reason']);
            }
        } catch (\RuntimeException $e) {
            return back()->withErrors(['action' => $e->getMessage()]);
        }

        $label = ['approve' => 'approved', 'rework' => 'sent back for rework', 'reject' => 'rejected'][$data['action']];

        return redirect()->route('review.index')
            ->with('success', "Test {$label} successfully.");
    }
}
