<?php

use App\Http\Controllers\SalarySummaryController;
use App\Livewire\Attendance\AttendanceManager;
use App\Livewire\Attendance\UserAttendanceDetails;
use App\Livewire\Auth\Login;
use App\Livewire\Auth\Register;
use App\Livewire\Dashboard\Dashboard;
use App\Livewire\Permission\PermissionManager;
use App\Livewire\Project\ProjectCreate;
use App\Livewire\Project\ProjectIndex;
use App\Livewire\Task\TaskCreate;
use App\Livewire\Task\TaskDetails;
use App\Livewire\Team\TeamManager;
use App\Livewire\User\ProbationManager;
use App\Livewire\User\SalaryManager;
use App\Livewire\User\UserEmploymentManager;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

// Authentication Routes
Route::middleware('guest')->group(function () {
    Route::get('/login', Login::class)->name('login');
    Route::get('/register', Register::class)->name('register');
});
// zkteco route
Route::middleware('auth')->get('/zkteco', \App\Livewire\Zkteco\AttendanceManager::class)->name('zkteco');
// Logout Route
Route::post('/logout', function () {
    auth()->logout();
    request()->session()->invalidate();
    request()->session()->regenerateToken();

    return redirect('/login');
})->name('logout');

// Protected Routes
Route::middleware('auth')->group(function () {
    // Dashboard
    Route::get('/', Dashboard::class)->name('dashboard');
    Route::get('/dashboard', Dashboard::class)->name('dashboard');

    // Projects
    Route::get('/projects', ProjectIndex::class)->name('projects.index');
    Route::get('/projects/create', ProjectCreate::class)->name('projects.create');
    Route::get('/projects/{projectId}', \App\Livewire\Project\ProjectDetails::class)->name('projects.details');

    // Leads
    // Specific routes must come before resource routes to avoid conflicts
    Route::get('/leads/export', [\App\Http\Controllers\LeadController::class, 'exportLeads'])->name('leads.export');
    Route::post('/leads/import', [\App\Http\Controllers\LeadController::class, 'importLeads'])->name('leads.import');
    Route::get('/leads/project/{projectId}/statuses', [\App\Http\Controllers\LeadController::class, 'getStatusesByProject'])->name('leads.statuses');
    Route::get('/leads/lead-types', [\App\Http\Controllers\LeadController::class, 'getLeadTypes'])->name('leads.lead-types');
    Route::get('/leads/{leadId}/activities/export', [\App\Http\Controllers\LeadController::class, 'exportActivities'])->name('leads.activities.export');
    Route::resource('leads', \App\Http\Controllers\LeadController::class);
    Route::get('/leads/{id}/edit', [\App\Http\Controllers\LeadController::class, 'edit'])->name('leads.edit');
    
    // Statuses (Project Statuses)
    Route::resource('statuses', \App\Http\Controllers\StatusController::class);
    Route::get('/statuses/{id}/edit', [\App\Http\Controllers\StatusController::class, 'edit'])->name('statuses.edit');
    
    // Lead Types
    Route::resource('lead-types', \App\Http\Controllers\LeadTypeController::class);
    Route::get('/lead-types/{id}/edit', [\App\Http\Controllers\LeadTypeController::class, 'edit'])->name('lead-types.edit');
    
    // Activities
    Route::get('/activities', [\App\Http\Controllers\ActivityController::class, 'index'])->name('activities.index');
    Route::resource('activities', \App\Http\Controllers\ActivityController::class)->except(['index', 'create']);
    Route::get('/activities/{id}/edit', [\App\Http\Controllers\ActivityController::class, 'edit'])->name('activities.edit');
    Route::post('/activities/import', [\App\Http\Controllers\ActivityController::class, 'importActivities'])->name('activities.import');
    Route::post('/activities/reply', [\App\Http\Controllers\ActivityController::class, 'reply'])->name('activities.reply');

    // User Profile
    Route::get('/profile', \App\Livewire\User\ProfileEdit::class)->name('profile.edit');

    // Settings
    Route::get('/settings', \App\Livewire\Settings::class)->name('settings');

    // Tasks
    Route::prefix('/tasks')->name('tasks.')->group(function () {
        // Task Table - Main index
        Route::get('/', [\App\Http\Controllers\Ajax\Task\TaskTableController::class, 'index'])->name('index');
        Route::get('/data', [\App\Http\Controllers\Ajax\Task\TaskTableController::class, 'getTasks'])->name('data');
        Route::get('/dropdown-data', [\App\Http\Controllers\Ajax\Task\TaskTableController::class, 'getDropdownData'])->name('dropdown');
        Route::post('/store', [\App\Http\Controllers\Ajax\Task\TaskTableController::class, 'createTask'])->name('store');
        
        // Bulk routes must come before {taskId} routes to avoid route conflicts
        Route::post('/bulk/status', [\App\Http\Controllers\Ajax\Task\TaskTableController::class, 'bulkUpdateStatus'])->name('bulk.status');
        Route::post('/bulk/priority', [\App\Http\Controllers\Ajax\Task\TaskTableController::class, 'bulkUpdatePriority'])->name('bulk.priority');
        Route::post('/bulk/assignee', [\App\Http\Controllers\Ajax\Task\TaskTableController::class, 'bulkUpdateAssignee'])->name('bulk.assignee');
        Route::post('/bulk/delete', [\App\Http\Controllers\Ajax\Task\TaskTableController::class, 'bulkDeleteTasks'])->name('bulk.delete');
        
        // Task Create (must come before {taskId} routes)
        Route::get('/create', TaskCreate::class)->name('create');
        
        // Individual task routes
        Route::get('/{taskId}', [\App\Http\Controllers\Ajax\Task\TaskTableController::class, 'getTask'])->name('get');
        Route::put('/{taskId}', [\App\Http\Controllers\Ajax\Task\TaskTableController::class, 'updateTask'])->name('update');
        Route::delete('/{taskId}', [\App\Http\Controllers\Ajax\Task\TaskTableController::class, 'deleteTask'])->name('delete');
        Route::post('/{taskId}/status', [\App\Http\Controllers\Ajax\Task\TaskTableController::class, 'updateTaskStatus'])->name('status');
        Route::post('/{taskId}/priority', [\App\Http\Controllers\Ajax\Task\TaskTableController::class, 'updateTaskPriority'])->name('priority');
        Route::post('/{taskId}/category', [\App\Http\Controllers\Ajax\Task\TaskTableController::class, 'updateTaskCategory'])->name('category');
        Route::post('/{taskId}/stop-recurring', [\App\Http\Controllers\Ajax\Task\TaskTableController::class, 'stopRecurringTask'])->name('stop-recurring');
        Route::post('/{taskId}/clone', [\App\Http\Controllers\Ajax\Task\TaskTableController::class, 'cloneTask'])->name('clone');
        Route::post('/{taskId}/approve', [\App\Http\Controllers\Ajax\Task\TaskTableController::class, 'approveTask'])->name('approve');
        Route::post('/{taskId}/revisit', [\App\Http\Controllers\Ajax\Task\TaskTableController::class, 'revisitTask'])->name('revisit');

        // Task Create AJAX routes
        Route::get('/create/dropdown-data', [\App\Http\Controllers\Ajax\Task\TaskCreateController::class, 'getDropdownData'])->name('create.dropdown');
        Route::post('/create/store', [\App\Http\Controllers\Ajax\Task\TaskCreateController::class, 'createTask'])->name('create.store');
        Route::post('/create/priority', [\App\Http\Controllers\Ajax\Task\TaskCreateController::class, 'addNewPriority'])->name('create.priority');
        Route::post('/create/category', [\App\Http\Controllers\Ajax\Task\TaskCreateController::class, 'addNewCategory'])->name('create.category');
        Route::post('/create/status', [\App\Http\Controllers\Ajax\Task\TaskCreateController::class, 'addNewStatus'])->name('create.status');

        // Task Details
        Route::get('/{taskId}/details', [\App\Http\Controllers\Ajax\Task\TaskDetailsController::class, 'index'])->name('details');
        Route::get('/{taskId}/details/data', [\App\Http\Controllers\Ajax\Task\TaskDetailsController::class, 'getTask'])->name('details.data');
        Route::get('/{taskId}/details/statuses', [\App\Http\Controllers\Ajax\Task\TaskDetailsController::class, 'getStatuses'])->name('details.statuses');
        Route::post('/{taskId}/details/status', [\App\Http\Controllers\Ajax\Task\TaskDetailsController::class, 'updateTaskStatus'])->name('details.status');
        Route::post('/{taskId}/details/note', [\App\Http\Controllers\Ajax\Task\TaskDetailsController::class, 'addNote'])->name('details.note');
        Route::post('/{taskId}/details/attachments', [\App\Http\Controllers\Ajax\Task\TaskDetailsController::class, 'addAttachments'])->name('details.attachments');
        Route::delete('/{taskId}/details/attachments/{attachmentId}', [\App\Http\Controllers\Ajax\Task\TaskDetailsController::class, 'deleteAttachment'])->name('details.attachment.delete');
        Route::post('/{taskId}/details/stop-recurring', [\App\Http\Controllers\Ajax\Task\TaskDetailsController::class, 'stopRecurringTask'])->name('details.stop-recurring');
        Route::post('/{taskId}/details/comment', [\App\Http\Controllers\Ajax\Task\TaskDetailsController::class, 'addComment'])->name('details.comment');
        Route::delete('/{taskId}/details/comments/{commentId}', [\App\Http\Controllers\Ajax\Task\TaskDetailsController::class, 'deleteComment'])->name('details.comment.delete');
        Route::post('/{taskId}/details/approve', [\App\Http\Controllers\Ajax\Task\TaskDetailsController::class, 'approveTask'])->name('details.approve');
        Route::post('/{taskId}/details/revisit', [\App\Http\Controllers\Ajax\Task\TaskDetailsController::class, 'revisitTask'])->name('details.revisit');
    });

    // Attendance Routes
    Route::get('/attendance', AttendanceManager::class)->name('attendance');
    Route::get('/attendance/user/{userId}', UserAttendanceDetails::class)->name('attendance.user');
    Route::get('/attendance-viewer', \App\Livewire\Attendance\AttendanceViewer::class)->name('attendance.viewer');
    // Salary Summary Print
    Route::get('/salary-summary/print', [SalarySummaryController::class, 'print'])->name('salary-summary.print');

    Route::get('/attachments/{attachment}/download', [App\Http\Controllers\AttachmentController::class, 'download'])->name('attachments.download');
    Route::get('/attachments/{attachment}/preview', [App\Http\Controllers\AttachmentController::class, 'preview'])->name('attachments.preview');
    Route::get('/attachments/{attachment}/data', [App\Http\Controllers\AttachmentController::class, 'data'])->name('attachments.data');
    Route::get('/attachments/{attachment}/test', [App\Http\Controllers\AttachmentController::class, 'testData'])->name('attachments.test');

    // Chat
    Route::get('/chat', \App\Livewire\SlackLikeChatComponent::class)->name('chat');
    Route::get('/private-messages', \App\Livewire\PrivateChatComponent::class)->name('private-messages');
    Route::get('/slack-chat', \App\Livewire\SlackLikeChatComponent::class)->name('slack-chat');
    // Salary Management Routes
    Route::get('/salary-management', SalaryManager::class)->name('salary.management');
    // User Employment Management
    Route::get('/user-employment-management', UserEmploymentManager::class)->name('user.employment.management');

    // Probation Management Routes
    Route::get('/probation-management', ProbationManager::class)->name('probation.management');

    // Admin Routes (Super Admin only)
    Route::get('/permissions', PermissionManager::class)->name('permissions.index');
    Route::get('/roles', \App\Livewire\RoleManager::class)->name('roles.index');
    Route::get('/teams', TeamManager::class)->name('teams.index');
    Route::get('/managers', \App\Livewire\Manager\ManagerManager::class)->name('managers.index');
    Route::get('/users', \App\Livewire\User\UserManager::class)->name('users.index');
    Route::get('/users/{userId}/permissions', \App\Livewire\User\UserPermissionManager::class)->name('users.permissions');
    Route::get('/test-form', \App\Livewire\TestForm::class)->name('test.form');

    // Task Management Routes
    Route::get('/task-statuses', \App\Livewire\TaskStatusManager::class)->name('task-statuses.index');
    Route::get('/task-categories', \App\Livewire\TaskCategoryManager::class)->name('task-categories.index');
    Route::get('/task-priorities', \App\Livewire\TaskPriorityManager::class)->name('task-priorities.index');
});