<?php

use App\Http\Controllers\AllocationController;
use App\Http\Controllers\AttachmentController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\ProfileController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\MasterController;
use App\Http\Controllers\MyWorkController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\WorkCommentController;
use App\Http\Controllers\WorkOrderController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/dashboard');

// Authentication
Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [LoginController::class, 'login'])->middleware('throttle:10,1');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Profile
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('/profile/password', [ProfileController::class, 'changePassword'])->name('profile.password');

    // Notifications
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notifications/{notification}/read', [NotificationController::class, 'markRead'])->name('notifications.read');
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllRead'])->name('notifications.read-all');

    // Work Requests
    Route::get('/work-orders', [WorkOrderController::class, 'index'])->name('work-orders.index');
    Route::get('/work-orders/create', [WorkOrderController::class, 'create'])
        ->middleware('permission:work_orders.create')->name('work-orders.create');
    Route::post('/work-orders', [WorkOrderController::class, 'store'])
        ->middleware('permission:work_orders.create')->name('work-orders.store');
    Route::get('/work-orders/{workOrder}', [WorkOrderController::class, 'show'])->name('work-orders.show');
    Route::put('/work-orders/{workOrder}', [WorkOrderController::class, 'update'])
        ->middleware('permission:work_orders.edit')->name('work-orders.update');
    Route::post('/work-orders/{workOrder}/cancel', [WorkOrderController::class, 'cancel'])
        ->middleware('permission:work_orders.edit')->name('work-orders.cancel');

    // Allocation engine
    Route::get('/allocation', [AllocationController::class, 'index'])
        ->middleware('permission:allocations.view')->name('allocation.index');
    Route::get('/allocation/{test}/candidates', [AllocationController::class, 'candidates'])
        ->middleware('permission:allocations.view')->name('allocation.candidates');
    Route::post('/allocation/{test}', [AllocationController::class, 'allocate'])
        ->middleware('permission:allocations.allocate')->name('allocation.store');
    Route::get('/allocation/history', [AllocationController::class, 'history'])
        ->middleware('permission:allocations.view')->name('allocation.history');

    // Analyst: my work
    Route::get('/my-work', [MyWorkController::class, 'index'])->name('my-work.index');
    Route::get('/my-work/{workOrderTest}', [MyWorkController::class, 'show'])->name('my-work.show');
    Route::post('/my-work/{workOrderTest}/action', [MyWorkController::class, 'action'])->name('my-work.action');
    Route::post('/my-work/{workOrderTest}/result', [MyWorkController::class, 'saveResult'])->name('my-work.result');
    Route::post('/my-work/{workOrderTest}/comments', [WorkCommentController::class, 'store'])->name('work-comments.store');

    // Attachments
    Route::post('/work-orders/{workOrder}/attachments', [AttachmentController::class, 'store'])->name('attachments.store');
    Route::get('/attachments/{attachment}/download', [AttachmentController::class, 'download'])->name('attachments.download');
    Route::delete('/attachments/{attachment}', [AttachmentController::class, 'destroy'])->name('attachments.destroy');

    // Review & approval
    Route::get('/review', [ReviewController::class, 'index'])
        ->middleware('permission:review.view')->name('review.index');
    Route::get('/review/{test}', [ReviewController::class, 'show'])
        ->middleware('permission:review.view')->name('review.show');
    Route::post('/review/{test}', [ReviewController::class, 'act'])
        ->middleware('permission:review.approve')->name('review.act');

    // Employees
    Route::get('/employees', [EmployeeController::class, 'index'])
        ->middleware('permission:employees.manage')->name('employees.index');
    Route::get('/employees/create', [EmployeeController::class, 'create'])
        ->middleware('permission:employees.manage')->name('employees.create');
    Route::post('/employees', [EmployeeController::class, 'store'])
        ->middleware('permission:employees.manage')->name('employees.store');
    Route::get('/employees/{employee}', [EmployeeController::class, 'show'])
        ->middleware('permission:employees.manage')->name('employees.show');
    Route::get('/employees/{employee}/edit', [EmployeeController::class, 'edit'])
        ->middleware('permission:employees.manage')->name('employees.edit');
    Route::put('/employees/{employee}', [EmployeeController::class, 'update'])
        ->middleware('permission:employees.manage')->name('employees.update');
    Route::put('/employees/{employee}/skills', [EmployeeController::class, 'syncSkills'])
        ->middleware('permission:employees.manage')->name('employees.skills');

    // Users (admin)
    Route::get('/users', [UserController::class, 'index'])
        ->middleware('permission:users.view')->name('users.index');
    Route::get('/users/create', [UserController::class, 'create'])
        ->middleware('permission:users.create')->name('users.create');
    Route::post('/users', [UserController::class, 'store'])
        ->middleware('permission:users.create')->name('users.store');
    Route::get('/users/{user}/edit', [UserController::class, 'edit'])
        ->middleware('permission:users.edit')->name('users.edit');
    Route::put('/users/{user}', [UserController::class, 'update'])
        ->middleware('permission:users.edit')->name('users.update');
    Route::post('/users/{user}/toggle-status', [UserController::class, 'toggleStatus'])
        ->middleware('permission:users.edit')->name('users.toggle');

    // QC Masters (config-driven CRUD)
    Route::get('/masters/{master}', [MasterController::class, 'index'])
        ->middleware('permission:masters.view')->name('masters.index');
    Route::get('/masters/{master}/create', [MasterController::class, 'create'])
        ->middleware('permission:masters.manage')->name('masters.create');
    Route::post('/masters/{master}', [MasterController::class, 'store'])
        ->middleware('permission:masters.manage')->name('masters.store');
    Route::get('/masters/{master}/{id}/edit', [MasterController::class, 'edit'])
        ->middleware('permission:masters.manage')->name('masters.edit');
    Route::put('/masters/{master}/{id}', [MasterController::class, 'update'])
        ->middleware('permission:masters.manage')->name('masters.update');
    Route::delete('/masters/{master}/{id}', [MasterController::class, 'destroy'])
        ->middleware('permission:masters.manage')->name('masters.destroy');

    // Reports
    Route::get('/reports', [ReportController::class, 'index'])
        ->middleware('permission:reports.view')->name('reports.index');
    Route::get('/reports/export', [ReportController::class, 'export'])
        ->middleware('permission:reports.export')->name('reports.export');

    // Audit trail
    Route::get('/audit-logs', [\App\Http\Controllers\AuditLogController::class, 'index'])
        ->middleware('permission:system.audit')->name('audit.index');
});
