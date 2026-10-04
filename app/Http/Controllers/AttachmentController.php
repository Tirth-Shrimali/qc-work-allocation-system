<?php

namespace App\Http\Controllers;

use App\Models\WorkAttachment;
use App\Models\WorkOrder;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AttachmentController extends Controller
{
    private const ALLOWED = ['pdf', 'png', 'jpg', 'jpeg', 'gif', 'xls', 'xlsx', 'csv', 'txt', 'doc', 'docx', 'zip'];

    private const MAX_SIZE = 10240; // KB (10 MB)

    public function store(Request $request, WorkOrder $workOrder)
    {
        $request->validate([
            'file' => ['required', 'file', 'max:'.self::MAX_SIZE],
            'work_order_test_id' => ['nullable', 'exists:work_order_tests,id'],
            'description' => ['nullable', 'string', 'max:500'],
        ], [
            'file.max' => 'File size must not exceed 10 MB.',
            'file.required' => 'Choose a file to upload.',
        ]);

        $file = $request->file('file');
        $ext = strtolower($file->extension());

        if (! in_array($ext, self::ALLOWED, true)) {
            return back()->withErrors(['file' => 'File type not allowed. Allowed: '.implode(', ', self::ALLOWED).'.']);
        }

        $storedName = Str::uuid().'.'.$ext;
        $path = $file->storeAs('work-attachments/'.$workOrder->id, $storedName, 'public');

        $attachment = $workOrder->attachments()->create([
            'work_order_test_id' => $request->input('work_order_test_id'),
            'file_name' => $file->getClientOriginalName(),
            'stored_name' => $storedName,
            'file_path' => $path,
            'file_type' => $ext,
            'file_size' => $file->getSize(),
            'uploaded_by_id' => $request->user()->id,
            'description' => $request->input('description'),
        ]);

        AuditLogger::log('Attachments', 'upload', $attachment->id, null, ['file' => $attachment->file_name]);

        return back()->with('success', 'File uploaded successfully.');
    }

    public function download(Request $request, WorkAttachment $attachment)
    {
        $this->authorizeAccess($request, $attachment);

        abort_unless(Storage::disk('public')->exists($attachment->file_path), 404, 'File not found.');

        AuditLogger::log('Attachments', 'download', $attachment->id);

        return Storage::disk('public')->download($attachment->file_path, $attachment->file_name);
    }

    public function destroy(Request $request, WorkAttachment $attachment)
    {
        $user = $request->user();

        if ($attachment->uploaded_by_id !== $user->id && ! $user->hasPermission('work_orders.edit')) {
            abort(403, 'You are not allowed to delete this file.');
        }

        Storage::disk('public')->delete($attachment->file_path);
        $attachment->delete();

        AuditLogger::log('Attachments', 'delete', $attachment->id, ['file' => $attachment->file_name]);

        return back()->with('success', 'File deleted.');
    }

    private function authorizeAccess(Request $request, WorkAttachment $attachment): void
    {
        $user = $request->user();

        if ($user->hasAnyRole(['super_admin', 'qc_admin', 'qc_hod', 'qc_supervisor', 'reviewer', 'management'])) {
            return;
        }

        $order = $attachment->workOrder;

        $isAssigned = $order->tests()
            ->where('assigned_analyst_id', $user->employee_id)
            ->exists();

        abort_unless($isAssigned || $order->requested_by_id === $user->id, 403, 'You are not authorized to download this file.');
    }
}
