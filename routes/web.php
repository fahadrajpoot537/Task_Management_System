<?php

use App\Livewire\Auth\Login;
use App\Livewire\Auth\Register;
use App\Livewire\Dashboard\Dashboard;
use App\Livewire\Permission\PermissionManager;
use App\Livewire\Project\ProjectCreate;
use App\Livewire\Project\ProjectIndex;
use App\Livewire\Task\TaskCreate;
use App\Livewire\Task\TaskDetails;
use App\Livewire\Task\TaskIndex;
use App\Livewire\Team\TeamManager;
use App\Livewire\Attendance\AttendanceManager;
use App\Livewire\Attendance\UserAttendanceDetails;
use App\Livewire\User\SalaryManager;
use App\Livewire\User\ProbationManager;
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

    // User Profile
    Route::get('/profile', \App\Livewire\User\ProfileEdit::class)->name('profile.edit');
    
    // Settings
    Route::get('/settings', \App\Livewire\Settings::class)->name('settings');

    // Tasks
    Route::get('/tasks', \App\Livewire\Task\TaskTable::class)->name('tasks.index');
    Route::get('/tasks/create', TaskCreate::class)->name('tasks.create');
    Route::get('/tasks/{taskId}', TaskDetails::class)->name('tasks.details');

    // Attendance Routes
    Route::get('/attendance', AttendanceManager::class)->name('attendance');
    Route::get('/attendance/user/{userId}', UserAttendanceDetails::class)->name('attendance.user');
    Route::get('/attendance-viewer', \App\Livewire\Attendance\AttendanceViewer::class)->name('attendance.viewer');
    
    // Salary Management Routes
    Route::get('/salary-management', SalaryManager::class)->name('salary.management');
    
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