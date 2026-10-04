<?php

namespace App\Http\Controllers;

use App\Models\WorkOrder;
use App\Models\WorkOrderTest;
use Illuminate\Http\Request;

class WorkCommentController extends Controller
{
    public function store(Request $request, WorkOrder $workOrder)
    {
        $data = $request->validate([
            'comment' => ['required', 'string', 'max:2000'],
            'work_order_test_id' => ['nullable', 'exists:work_order_tests,id'],
        ]);

        if ($data['work_order_test_id']) {
            abort_unless((int) $data['work_order_test_id'] === $workOrder->id
                || WorkOrderTest::find($data['work_order_test_id'])?->work_order_id === $workOrder->id, 404);
        }

        $comment = $workOrder->comments()->create([
            'work_order_test_id' => $data['work_order_test_id'] ?? null,
            'user_id' => $request->user()->id,
            'comment' => $data['comment'],
            'action_type' => 'comment',
        ]);

        if ($request->wantsJson()) {
            return response()->json([
                'ok' => true,
                'comment' => [
                    'id' => $comment->id,
                    'comment' => $comment->comment,
                    'user' => $request->user()->name,
                    'created_at' => $comment->created_at->diffForHumans(),
                ],
            ]);
        }

        return back()->with('success', 'Comment added.');
    }
}
