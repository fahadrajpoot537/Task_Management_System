# Task Management System - Deep Technical Analysis

## Table of Contents
1. [System Architecture Overview](#system-architecture-overview)
2. [Database Structure & Relationships](#database-structure--relationships)
3. [Core Modules Deep Dive](#core-modules-deep-dive)
4. [Business Logic & Algorithms](#business-logic--algorithms)
5. [Service Layer Architecture](#service-layer-architecture)
6. [Real-time Communication](#real-time-communication)
7. [Attendance Management System](#attendance-management-system)
8. [Salary Calculation System](#salary-calculation-system)
9. [Permission & Authorization Flow](#permission--authorization-flow)
10. [Task Management Features](#task-management-features)
11. [Email Notification System](#email-notification-system)
12. [File Management](#file-management)
13. [Console Commands & Automation](#console-commands--automation)
14. [Event System & Broadcasting](#event-system--broadcasting)

---

## System Architecture Overview

### Technology Stack
- **Framework**: Laravel 10
- **Frontend Framework**: Livewire 3
- **UI Framework**: Bootstrap 5
- **Database**: MySQL/PostgreSQL
- **Real-time**: Laravel Broadcasting + Pusher/Redis
- **Biometric Integration**: Zkteco SDK
- **Email**: Laravel Mail (SMTP)
- **File Storage**: Laravel Storage (Local/Cloud)

### Application Structure
- **MVC Pattern**: Traditional Laravel MVC
- **Component-Based UI**: Livewire components
- **Service Layer**: Business logic separation
- **Event-Driven**: Broadcasting for real-time features
- **Command-Based**: Scheduled tasks via Artisan commands

---

## Database Structure & Relationships

### Core Models and Relationships

#### User Model (`User`)
**Fillable Attributes:**
- Personal: `name`, `email`, `password`, `phone`, `bio`, `avatar`
- Employment: `role_id`, `manager_id`, `joining_date`, `hired_at`, `probation_end_at`, `employment_status`, `designation_id`
- Attendance: `is_online`, `last_seen`, `check_in_time`, `check_out_time`, `shift_start`, `shift_end`
- Biometric: `zkteco_uid`, `device_user_id`, `k50_device_uid`
- Salary: `monthly_salary`, `bonus`, `incentive`

**Key Relationships:**
```php
// Hierarchical Relationships
role(): BelongsTo(Role)
manager(): BelongsTo(User, 'manager_id') // Self-referential
teamMembers(): HasMany(User, 'manager_id')

// Task Relationships
assignedTasks(): HasMany(Task, 'assigned_to_user_id')
assignedByTasks(): HasMany(Task, 'assigned_by_user_id')

// Permission Relationships
permissions(): BelongsToMany(Permission) // Custom user permissions
hasPermission(string $permission): bool // Checks role + custom permissions

// Helper Methods
manageableUsers(): Collection // Returns users based on role hierarchy
isOnline(): bool // Checks last_seen within 5 minutes
```

**Permission Check Logic:**
```php
hasPermission(string $permission): bool {
    // 1. Check role permissions first
    $hasRolePermission = $this->role->permissions()->where('name', $permission)->exists();
    
    // 2. Check custom user permissions
    $hasCustomPermission = $this->permissions()->where('name', $permission)->exists();
    
    // 3. Return true if either exists
    return $hasRolePermission || $hasCustomPermission;
}
```

#### Task Model (`Task`)
**Fillable Attributes:**
- Basic: `title`, `description`, `notes`, `duration`
- Relationships: `project_id`, `assigned_to_user_id`, `assigned_by_user_id`, `priority_id`, `category_id`, `status_id`
- Time Tracking: `estimated_hours`, `actual_hours`, `started_at`, `completed_at`
- Dates: `due_date`, `reminder_time`, `next_recurrence_date`
- Recurring: `is_recurring`, `is_recurring_active`, `nature_of_task`, `parent_task_id`

**Key Relationships:**
```php
// Project Relationship
project(): BelongsTo(Project)

// User Relationships (Legacy + Multiple Assignees)
assignedTo(): BelongsTo(User, 'assigned_to_user_id') // Legacy single assignee
assignees(): BelongsToMany(User, 'task_assignments') // Multiple assignees
assignedBy(): BelongsTo(User, 'assigned_by_user_id')

// Status, Priority, Category
status(): BelongsTo(TaskStatus)
priority(): BelongsTo(TaskPriority)
category(): BelongsTo(TaskCategory)

// Attachments & Comments
attachments(): HasMany(Attachment)
noteComments(): HasMany(TaskNoteComment)

// Recurring Task Relationships
parentTask(): BelongsTo(Task, 'parent_task_id')
childTasks(): HasMany(Task, 'parent_task_id')
```

**Complex Accessor Methods:**

1. **Overdue Check:**
```php
getIsOverdueAttribute(): bool {
    return $this->due_date && 
           $this->due_date->isPast() && 
           (!$this->status || $this->status->name !== 'Complete');
}
```

2. **Time Tracking Status:**
```php
getTimeTrackingStatusAttribute(): string {
    // Returns: 'completed', 'in_progress', 'ready_to_start', 'not_started'
    if ($this->status && $this->status->name === 'Complete' && $this->completed_at) {
        return 'completed';
    } elseif ($this->status && $this->status->name === 'In Progress' && $this->started_at) {
        return 'in_progress';
    } elseif ($this->status && $this->status->name === 'In Progress' && !$this->started_at) {
        return 'ready_to_start';
    }
    return 'not_started';
}
```

3. **Hours Comparison:**
```php
getHoursComparisonAttribute(): array {
    $estimated = $this->estimated_hours ?? 0;
    $actual = $this->actual_hours ?? 0;
    return [
        'estimated' => $estimated,
        'actual' => $actual,
        'difference' => $actual - $estimated,
        'percentage' => $estimated > 0 ? round(($actual / $estimated) * 100, 1) : 0,
    ];
}
```

4. **Delay Information:**
```php
getDelayInfoAttribute(): array {
    // Calculates if task was delayed, early, or on time
    // Returns: ['is_delayed', 'is_early', 'delay_days', 'early_days', 'status']
    // Status can be: 'delayed', 'early', 'on_time', 'no_completion_date'
}
```

5. **Multiple Assignees Management:**
```php
syncAssignees(array $userIds, int $assignedByUserId): void {
    $assignments = [];
    foreach ($userIds as $userId) {
        $assignments[$userId] = [
            'assigned_by_user_id' => $assignedByUserId,
            'assigned_at' => now(),
        ];
    }
    $this->assignees()->sync($assignments);
}
```

#### AttendanceRecord Model
**Fillable Attributes:**
- `user_id`, `attendance_date`, `check_in_time`, `check_out_time`
- `late_minutes`, `early_minutes`, `hours_worked`
- `status` (present, late, absent, wfh, paid_leave, holiday, pending)
- `device_uid`, `notes`, `bonus`, `incentive`

**Status Values:**
- `present`: On time attendance
- `late`: Arrived after expected time
- `absent`: No attendance
- `wfh`: Work from home (custom hours)
- `paid_leave`: Paid leave (full expected hours)
- `holiday`: Company holiday (0 hours)
- `pending`: No record yet (shows missing days)

---

## Core Modules Deep Dive

### 1. Authentication & Authorization

#### Role Hierarchy
```
Super Admin (hierarchy_level: 1)
  └── Admin (hierarchy_level: 2)
       └── Manager (hierarchy_level: 3)
            └── Employee (hierarchy_level: 4)
```

**Role Features:**
- **Hierarchy-based Access**: Higher roles can manage lower roles
- **System Roles**: Cannot be deleted or modified (except by Super Admin)
- **Permission Inheritance**: Roles have many-to-many permissions
- **User-level Overrides**: Users can have custom permissions beyond role

#### Permission System Flow
```
1. User makes request
2. Check if user has permission via:
   - Role permissions (role->permissions)
   - Custom user permissions (user->permissions)
3. If either exists, grant access
4. Otherwise, deny access
```

**Permission Checking Example:**
```php
// In Livewire Components
if (!$user->hasPermission('manage_users')) {
    abort(403, 'You do not have permission to manage users.');
}

// Role-based helper methods
if ($user->isSuperAdmin()) { ... }
if ($user->isAdmin()) { ... }
if ($user->isManager()) { ... }
if ($user->isEmployee()) { ... }
```

---

### 2. Task Management Deep Dive

#### Multiple Assignees Feature

**Database Schema:**
```sql
task_assignments (
    id,
    task_id,
    user_id,
    assigned_by_user_id,
    assigned_at,
    created_at,
    updated_at
)
```

**Implementation:**
- Tasks can have multiple users assigned via `task_assignments` pivot table
- Legacy `assigned_to_user_id` maintained for backward compatibility
- All assignees receive email notifications
- Permission checks consider all assignees

**Access Control Logic:**
```php
// Check if user can access task
$isAssignee = $task->assigned_to_user_id == $user->id || 
              $task->assignees->contains('id', $user->id);
$isCreator = $task->assigned_by_user_id == $user->id;

// Managers can see team member tasks
if ($user->isManager()) {
    $teamMemberIds = $user->teamMembers->pluck('id')->push($user->id);
    $isTeamTask = in_array($task->assigned_to_user_id, $teamMemberIds->toArray());
}
```

#### Recurring Tasks System

**Recurrence Types:**
- `daily`: Creates task every day
- `weekly`: Creates task every week
- `monthly`: Creates task every month
- `until_stop`: Creates task until manually stopped

**Recurrence Logic:**
```php
canGenerateNextOccurrence(): bool {
    return in_array($this->nature_of_task, ['weekly', 'monthly', 'until_stop']) && 
           $this->is_recurring_active && 
           $this->status && 
           $this->status->name === 'Complete';
}

// Service creates next occurrence when task is completed
createNextOccurrence(Task $originalTask): Task {
    $newTask = $originalTask->replicate();
    $newTask->status_id = $pendingStatus->id;
    $newTask->due_date = Carbon::tomorrow(); // Next day
    $newTask->parent_task_id = $originalTask->parent_task_id ?: $originalTask->id;
    $newTask->started_at = null;
    $newTask->completed_at = null;
    $newTask->actual_hours = null;
    $newTask->save();
    
    // Send notifications
    $this->emailService->sendTaskCreatedNotification($newTask);
    return $newTask;
}
```

**Console Commands:**
- `tasks:process-recurring`: Process all completed recurring tasks
- `tasks:generate-daily`: Generate daily recurring tasks
- Scheduled via Laravel Task Scheduler

#### Task Status Workflow
```
Pending → In Progress → Complete
   ↓
Revisit (if not approved)
   ↓
Pending (after review)
```

**Status Change Triggers:**
- Task created: `Pending`
- User starts work: `In Progress` (sets `started_at`)
- User completes: `Complete` (sets `completed_at`)
- Admin reviews: Can mark for `Revisit`
- Status changes trigger email notifications

#### Time Tracking Algorithm

**Estimated vs Actual:**
```php
getHoursComparisonAttribute(): array {
    $estimated = $this->estimated_hours ?? 0;
    $actual = $this->actual_hours ?? 0;
    return [
        'estimated' => $estimated,
        'actual' => $actual,
        'difference' => $actual - $estimated,
        'percentage' => $estimated > 0 ? round(($actual / $estimated) * 100, 1) : 0,
    ];
}
```

**Delay Calculation:**
```php
getDelayInfoAttribute(): array {
    if (!$this->due_date || !$this->completed_at) {
        return ['status' => 'no_completion_date', ...];
    }
    
    $dueDate = $this->due_date;
    $completedDate = $this->completed_at->toDateString();
    
    if ($completedDate > $dueDate->toDateString()) {
        // Delayed
        $delayDays = $dueDate->diffInDays($this->completed_at, false);
        return ['is_delayed' => true, 'delay_days' => $delayDays, 'status' => 'delayed'];
    } elseif ($completedDate < $dueDate->toDateString()) {
        // Early
        $earlyDays = $this->completed_at->diffInDays($dueDate, false);
        return ['is_early' => true, 'early_days' => $earlyDays, 'status' => 'early'];
    }
    
    return ['status' => 'on_time'];
}
```

---

## Attendance Management System

### Attendance Record Processing

#### Zkteco Device Integration

**Fetch Process:**
```php
// Command: attendance:fetch-daily
1. Connect to Zkteco device (IP:PORT from .env)
2. Get device info (version, serial, platform)
3. Fetch all attendance records from device
4. Filter records for target date
5. Match records to users via device_user_id or zkteco_uid
6. Process check-in/check-out detection
7. Calculate late minutes with special rules
8. Create/Update attendance records in database
```

**Late Minute Calculation Rules:**
```php
// Rule 1: Ignore seconds - compare only at minute level
$companySetTime = Carbon::parse($recordDate . ' ' . $expectedCheckInTime)->startOfMinute();
$actualTime = Carbon::parse($recordDate . ' ' . $actualCheckInTime)->startOfMinute();

// Rule 2: Late minute calculation
if ($actualTime->greaterThan($companySetTime)) {
    $calculatedLateMinutes = $actualTime->diffInMinutes($companySetTime);
    
    // Rule 3: Special late calculation rules
    if ($calculatedLateMinutes < 30) {
        $lateMinutes = (int)$calculatedLateMinutes; // Store exact time
    } elseif ($calculatedLateMinutes >= 30 && $calculatedLateMinutes < 60) {
        $lateMinutes = 60; // Mark as 1 hour for 30-59 minutes
    } else {
        $lateMinutes = (int)$calculatedLateMinutes; // Store exact for 1 hour+
    }
}
```

**Check-in/Check-out Detection:**
```php
// Determine if timestamp is check-in or check-out
$expectedCheckOut = $user->check_out_time ? 
    Carbon::parse($recordDate . ' ' . $user->check_out_time) : null;

if ($expectedCheckOut && $actualTime->greaterThan($expectedCheckOut->copy()->subHours(2))) {
    // This is likely a check-out (within 2 hours of expected check-out)
    $isCheckOut = true;
    // Calculate early departure minutes
} else {
    // This is a check-in
    $isCheckOut = false;
    // Calculate late minutes
}
```

### Attendance Calculation Logic

#### Hours Worked Calculation (Grace Period)

**Algorithm:**
```php
calculateHoursWorkedWithGrace($attendanceRecord) {
    // 1. Exclude absent/holiday (0 hours)
    if ($attendanceRecord->status === 'absent' || 
        $attendanceRecord->status === 'holiday') {
        return 0;
    }
    
    // 2. WFH/Paid Leave: Use stored hours_worked
    if (in_array($attendanceRecord->status, ['wfh', 'paid_leave'])) {
        return $attendanceRecord->hours_worked ?? 0;
    }
    
    // 3. Get expected hours for user
    $expectedHours = $this->calculateExpectedHours($user);
    
    // 4. Get late minutes from database
    $lateMinutes = $attendanceRecord->late_minutes ?? 0;
    
    // 5. Grace period: Short lates (≤30 minutes) get full hours
    if ($lateMinutes > 0 && $lateMinutes <= 30) {
        return $expectedHours; // Full hours for short lates
    }
    
    // 6. For lates > 30 minutes: Deduct late hours
    $lateHours = $lateMinutes / 60;
    return max(0, $expectedHours - $lateHours);
}
```

**Expected Hours Calculation:**
```php
calculateExpectedHours($user) {
    if (!$user->check_in_time || !$user->check_out_time) {
        return 9; // Default 9 hours
    }
    
    $shiftStart = Carbon::parse($user->check_in_time);
    $shiftEnd = Carbon::parse($user->check_out_time);
    $totalMinutes = $shiftEnd->diffInMinutes($shiftStart);
    $totalHours = $totalMinutes / 60;
    
    // Subtract 0.5 hours (30 minutes) for lunch break
    return max(0, $totalHours - 0.5);
}
```

#### Short Late Penalty System

**Counting Short Lates:**
```php
countShortLates($attendanceRecords) {
    $shortLateCount = 0;
    foreach ($attendanceRecords as $record) {
        if (!$record->check_in_time || $record->status === 'absent') {
            continue;
        }
        
        $lateMinutes = $this->calculateLateMinutesWithGrace($record);
        
        // Count only short lates (≤30 minutes)
        if ($lateMinutes > 0 && $lateMinutes <= 30) {
            $shortLateCount++;
        }
    }
    return $shortLateCount;
}
```

**Penalty Calculation:**
```php
// Every 3 short lates = 1 day wage deduction
$fullDayPenaltyCount = intdiv($shortLateCount, 3);
$remainingShortLates = $shortLateCount % 3;

// Penalty = (full days × daily wage) + (remaining × 200)
$shortLatePenalty = ($fullDayPenaltyCount * $dailyWage) + ($remainingShortLates * 200);
```

### Attendance View Features

#### View Types
1. **Daily View**: Shows individual records for each date
2. **Weekly View**: Aggregated data by week
3. **Monthly View**: Aggregated data by month

#### Missing Days Handling
- **Pending Records**: System creates placeholder records for missing weekdays
- **Working Days Only**: Only Monday-Friday are considered working days
- **Visual Indicators**: Pending records shown with special styling

#### Summary Statistics
```php
getSummaryStatsProperty() {
    return [
        'total_records' => $attendanceRecords->count(),
        'present_days' => $attendanceRecords->where('status', 'present')->count(),
        'late_days' => $attendanceRecords->where('status', 'late')->count(),
        'absent_days' => $recordedAbsentCount + $missingDays,
        'wfh_days' => $attendanceRecords->where('status', 'wfh')->count(),
        'paid_leave_days' => $attendanceRecords->where('status', 'paid_leave')->count(),
        'total_hours' => $this->calculateTotalHoursWithGrace($attendanceRecords),
        'total_late_hours' => $totalLateMinutes / 60,
        'avg_hours_per_day' => $attendanceRecords->avg('hours_worked'),
        // View-specific stats
        'expected_hours' => $this->getExpectedWorkingHours(),
        'short_late_count' => $this->countShortLates($attendanceRecords),
    ];
}
```

---

## Salary Calculation System

### Monthly Salary Breakdown

#### Wage Calculation
```php
// Daily Wage = Monthly Salary / Expected Working Days
$dailyWage = $expectedWorkingDays > 0 ? $monthlySalary / $expectedWorkingDays : 0;

// Hourly Wage = Daily Wage / Expected Hours Per Day
$hourlyWage = $expectedHoursPerDay > 0 ? $dailyWage / $expectedHoursPerDay : 0;
```

#### Breakdown Calculation
```php
calculateBreakdown() {
    foreach ($attendanceRecords as $record) {
        // 1. Calculate actual hours worked (with grace period)
        $actualHoursWorked = $this->calculateHoursWorkedWithGrace($record);
        
        // 2. If no deduction option: Pay full expected hours even if worked less
        if ($this->noDeduction && $record->status !== 'absent' && 
            $record->status !== 'holiday' && $record->status !== 'pending') {
            $hoursWorked = $expectedHoursPerDay;
        } else {
            $hoursWorked = $actualHoursWorked;
        }
        
        // 3. Calculate wages earned for this day
        $wagesEarned = $hoursWorked * $hourlyWage;
        
        $breakdown[] = [
            'date' => $record->attendance_date,
            'status' => $record->status,
            'hours_worked' => $actualHoursWorked, // Show actual
            'wages_earned' => $wagesEarned, // But pay based on no deduction logic
        ];
    }
    
    // 4. Calculate deductions
    $shortLatePenalty = $this->calculateShortLatePenalty();
    $absentDeduction = $recordedAbsentDays * $dailyWage;
    
    // 5. Calculate bonuses
    $punctualBonus = $this->calculatePunctualBonus();
    $manualBonus = $this->manualBonus ?? 0;
    
    // 6. Final calculation
    $totalWagesEarned = $grossWages - $actualShortLatePenalty - $actualAbsentDeduction;
    $finalWages = $totalWagesEarned + $punctualBonus + $manualBonus;
}
```

#### Punctual Bonus Calculation
```php
// Punctual Bonus: 2500 if:
// 1. No late arrivals (status != 'late')
// 2. No absent days
// 3. No missing working days
// 4. User is NOT on probation
$hasAnyLate = $attendanceRecords->contains(function ($r) {
    return isset($r->status) && $r->status === 'late';
});
$hasAnyAbsent = $attendanceRecords->contains(function ($r) {
    return isset($r->status) && $r->status === 'absent';
});
$missingWorkingDays = $this->countMissingWorkingDays();
$isOnProbation = ($user->employment_status === 'probation') || 
                 ($user->probation_end_at && Carbon::parse($user->probation_end_at)->isFuture());

$punctualBonus = (!$isOnProbation && !$hasAnyLate && 
                  !$hasAnyAbsent && $missingWorkingDays === 0) ? 2500 : 0;
```

---

## Service Layer Architecture

### Email Notification Service

#### Notification Types

1. **Task Created Notification**
```php
sendTaskCreatedNotification(Task $task) {
    $recipients = collect();
    
    // Always notify SuperAdmin and all Admins
    $superAdmins = User::whereHas('role', fn($q) => $q->where('name', 'super_admin'))->get();
    $admins = User::whereHas('role', fn($q) => $q->where('name', 'admin'))->get();
    
    $recipients->merge($superAdmins)->merge($admins);
    
    // Notify assigned user and their manager
    if ($task->assignedTo) {
        $recipients->push($task->assignedTo);
        if ($task->assignedTo->manager) {
            $recipients->push($task->assignedTo->manager);
        }
    }
    
    // Send emails to all unique recipients
    foreach ($recipients->unique('id') as $recipient) {
        Mail::to($recipient->email)->send(new TaskCreated($task, 'New Task Created'));
    }
}
```

2. **Task Assigned Notification** (Role-based templates)
```php
sendTaskAssignedNotification(Task $task) {
    $recipients = $this->getTaskAssignedRecipients($task);
    
    foreach ($recipients as $recipient) {
        // Different email templates based on recipient role
        if ($recipient->role->name === 'super_admin') {
            Mail::to($recipient->email)->send(
                new TaskAssignedToManager($task, 'Employee Assigned to Task')
            );
        } elseif ($recipient->role->name === 'manager') {
            Mail::to($recipient->email)->send(
                new TaskAssignedToManager($task, 'Your Employee Has Been Assigned a Task')
            );
        } elseif ($recipient->id === $task->assigned_to_user_id) {
            Mail::to($recipient->email)->send(
                new TaskAssignedToEmployee($task, 'New Task Assigned to You')
            );
        }
    }
}
```

#### Notification Recipients Logic

**Task Events Recipients:**
- Super Admin: Always notified
- Admin: Always notified
- Assigned User: Always notified
- Assigned User's Manager: Always notified
- Comment Author: Excluded from comment notifications

### Recurring Task Service

```php
class RecurringTaskService {
    /**
     * Process recurring task when completed
     */
    processRecurringTask(Task $task) {
        if (!$task->canGenerateNextOccurrence()) {
            return;
        }
        $this->createNextOccurrence($task);
    }
    
    /**
     * Create next occurrence
     */
    private createNextOccurrence(Task $originalTask): Task {
        // Replicate task
        $newTask = $originalTask->replicate();
        
        // Reset completion data
        $newTask->status_id = $pendingStatus->id;
        $newTask->due_date = Carbon::tomorrow();
        $newTask->parent_task_id = $originalTask->parent_task_id ?: $originalTask->id;
        $newTask->started_at = null;
        $newTask->completed_at = null;
        $newTask->actual_hours = null;
        $newTask->is_recurring_active = true;
        
        $newTask->save();
        
        // Send notifications
        $this->emailService->sendTaskCreatedNotification($newTask);
        
        return $newTask;
    }
}
```

---

## Real-time Communication

### Chat System Architecture

#### Channel-based Chat

**Models:**
- `Channel`: Chat channels/groups
- `Message`: Messages in channels
- `ChannelMember`: Channel membership (pivot table)

**Broadcasting:**
```php
// Event: MessageSent
broadcastOn(): [
    new PrivateChannel('channel.' . $this->message->channel_id)
]

// Event: DirectMessageSent
broadcastOn(): [
    new PrivateChannel('user.' . $this->directMessage->receiver_id)
]
```

**Channel Management:**
```php
// Add member
addMember(User $user): void {
    if (!$this->hasMember($user)) {
        $this->members()->attach($user->id, ['joined_at' => now()]);
    }
}

// Remove member
removeMember(User $user): void {
    $this->members()->detach($user->id);
}
```

#### Private Messaging

**Model: `DirectMessage`**
```php
// Conversation scope
scopeConversation($query, $user1Id, $user2Id) {
    return $query->where(function ($q) use ($user1Id, $user2Id) {
        $q->where('sender_id', $user1Id)->where('receiver_id', $user2Id)
          ->orWhere('sender_id', $user2Id)->where('receiver_id', $user1Id);
    })->orderBy('created_at', 'asc');
}

// Unread messages
scopeUnreadFor($query, $userId) {
    return $query->where('receiver_id', $userId)->where('is_read', false);
}
```

### Online Status System

```php
// User Model
isOnline(): bool {
    if (!$this->last_seen) {
        return false;
    }
    return $this->last_seen->diffInMinutes(now()) < 5;
}
```

---

## Console Commands & Automation

### 1. Fetch Daily Attendance
```php
// Command: attendance:fetch-daily {--date=}
// Description: Fetch daily attendance from ZKTeco device

Process:
1. Connect to ZKTeco device (IP:PORT)
2. Get device info
3. Fetch attendance records
4. Filter by target date
5. Match to users (device_user_id/zkteco_uid)
6. Calculate late minutes with rules
7. Create/Update records
```

### 2. Process Recurring Tasks
```php
// Command: tasks:process-recurring
// Description: Process recurring tasks and create next occurrences

Process:
1. Find all completed recurring tasks
2. Check if can generate next occurrence
3. Create next occurrence
4. Send email notifications
```

### 3. Convert Probation to Permanent
```php
// Command: users:convert-probation {--dry-run}
// Description: Convert users from probation to permanent after 3 months

Process:
1. Find users with employment_status = 'probation'
2. Check if joining_date <= 3 months ago
3. Update employment_status to 'active'
4. Set probation_end_at = now()
5. Log conversion
```

### 4. Generate Daily Recurring Tasks
```php
// Command: tasks:generate-daily
// Description: Generate daily recurring tasks

Process:
1. Find tasks with nature_of_task = 'daily'
2. Check if task should be generated today
3. Create new task for today
4. Send notifications
```

---

## Event System & Broadcasting

### Broadcast Events

#### MessageSent Event
```php
class MessageSent implements ShouldBroadcast {
    public $message;
    
    broadcastOn(): [
        new PrivateChannel('channel.' . $this->message->channel_id)
    ]
    
    broadcastWith(): [
        'id' => $this->message->id,
        'user_id' => $this->message->user_id,
        'channel_id' => $this->message->channel_id,
        'body' => $this->message->body,
        'user' => [
            'id' => $this->message->user->id,
            'name' => $this->message->user->name,
            'avatar_url' => $this->message->user->avatar_url,
        ],
    ]
}
```

#### DirectMessageSent Event
```php
class DirectMessageSent implements ShouldBroadcast {
    public $directMessage;
    
    broadcastOn(): [
        new PrivateChannel('user.' . $this->directMessage->receiver_id)
    ]
    
    broadcastWith(): [
        'id' => $this->directMessage->id,
        'sender_id' => $this->directMessage->sender_id,
        'receiver_id' => $this->directMessage->receiver_id,
        'message' => $this->directMessage->message,
        'sender' => [...]
    ]
}
```

### Event Listeners

Events are typically fired when:
- Task created/updated/completed
- Message sent (channel/private)
- Status changed
- Comments added

---

## File Management

### Attachment System

**Model: `Attachment`**
```php
// Relationships
task(): BelongsTo(Task)
comment(): BelongsTo(TaskNoteComment)
uploadedBy(): BelongsTo(User)

// Accessor
getFormattedFileSizeAttribute(): string {
    // Formats bytes to KB, MB, GB
}
```

**Controller Methods:**
- `download(Attachment $attachment)`: File download
- `preview(Attachment $attachment)`: File preview (PDF, images, videos)
- `data(Attachment $attachment)`: JSON data for JS consumption

**File Storage:**
- Primary: `storage/app/public/attachments`
- Fallback: `storage/app/attachments`
- Fallback: `storage/app/private`

**Supported Preview Types:**
- PDF: `application/pdf`
- Images: `image/jpeg`, `image/png`, `image/gif`
- Videos: `video/mp4`, `video/webm`, etc.
- Text: `text/plain`

---

## Dashboard System

### Role-based Dashboard Statistics

**Super Admin Dashboard:**
```php
[
    'total_projects' => Project::count(),
    'total_tasks' => Task::count(),
    'completed_tasks' => Task::whereHas('status', fn($q) => $q->where('name', 'Complete'))->count(),
    'pending_tasks' => Task::whereHas('status', fn($q) => $q->where('name', 'Pending'))->count(),
    'in_progress_tasks' => Task::whereHas('status', fn($q) => $q->where('name', 'In Progress'))->count(),
    'overdue_tasks' => Task::where('due_date', '<', now())
        ->whereDoesntHave('status', fn($q) => $q->where('name', 'Complete'))->count(),
    'total_users' => User::count(),
    'categories' => [...], // Count by category
    'total_estimated_hours' => Task::sum('estimated_hours'),
    'total_actual_hours' => Task::sum('actual_hours'),
    'delayed_tasks' => ..., // Tasks completed after due date
    'early_tasks' => ..., // Tasks completed before due date
    'on_time_tasks' => ..., // Tasks completed on due date
]
```

**Manager Dashboard:**
- Shows only team member tasks/projects
- Includes team member count
- Filtered by `teamMemberIds`

**Employee Dashboard:**
- Shows only assigned tasks
- No project statistics
- Personal task metrics only

---

## Key Business Rules

### 1. Late Calculation Rules
- **< 30 minutes**: Store exact late minutes
- **30-59 minutes**: Store as 60 minutes (1 hour)
- **≥ 60 minutes**: Store exact late minutes

### 2. Hours Worked Grace Period
- **Short lates (≤30 min)**: Full expected hours
- **Long lates (>30 min)**: Deduct late hours from expected hours

### 3. Short Late Penalty
- **3 short lates = 1 day wage deduction**
- **Remaining short lates = 200 each**

### 4. Punctual Bonus
- **2500 bonus** if:
  - No late arrivals
  - No absent days
  - No missing working days
  - NOT on probation

### 5. Working Days
- **Monday-Friday** only
- **Excludes weekends and holidays**
- **Holidays marked separately** (0 hours, no deduction)

### 6. Probation Rules
- **Auto-convert after 3 months** from joining date
- **No punctual bonus** during probation
- **Tracked via `probation_end_at` field**

---

## Security Features

### 1. CSRF Protection
- Laravel's built-in CSRF token validation
- Applied to all POST/PUT/DELETE requests

### 2. SQL Injection Prevention
- Eloquent ORM with parameter binding
- Query builder with prepared statements

### 3. XSS Protection
- Blade templating auto-escapes output
- `{!! !!}` used only for trusted HTML

### 4. Password Security
- Bcrypt hashing (via Laravel Hash facade)
- Minimum complexity (can be configured)

### 5. Access Control
- Route middleware for authentication
- Component-level permission checks
- Database-level role checks

### 6. File Upload Security
- File type validation
- File size limits (10MB)
- Storage in secure directories
- Filename sanitization

---

## Performance Optimizations

### 1. Database Indexing
- Foreign key indexes on relationships
- Indexes on frequently queried fields
- Composite indexes for complex queries

### 2. Eager Loading
```php
Task::with(['project', 'assignedTo', 'status', 'priority', 'assignees'])
    ->get();
```

### 3. Pagination
- Laravel pagination for large datasets
- Configurable per-page limits

### 4. Query Optimization
- Selective field loading
- Relationship pre-loading
- Query result caching (where applicable)

### 5. Livewire Optimization
- Component isolation
- Property hydration
- Minimal re-renders

---

## Integration Points

### 1. Zkteco Biometric Device
- **SDK**: `Jmrashed\Zkteco\Lib\ZKTeco`
- **Connection**: TCP/IP (IP:PORT)
- **Data Sync**: Daily via console command
- **User Matching**: `device_user_id` or `zkteco_uid`

### 2. Email System
- **SMTP Configuration**: Via `.env`
- **Queue Support**: Can use Laravel queues
- **Template System**: Blade email templates

### 3. Storage System
- **Local Storage**: Default
- **Cloud Ready**: Can switch to S3/other drivers
- **Symbolic Links**: `php artisan storage:link`

---

## Development Notes

### Key Design Decisions

1. **Multiple Assignees**: Implemented via pivot table to maintain scalability
2. **Legacy Support**: `assigned_to_user_id` maintained for backward compatibility
3. **Permission System**: Two-tier (role + user) for flexibility
4. **Grace Period**: Short lates get full hours to account for minor delays
5. **Recurring Tasks**: Parent-child relationship for tracking history
6. **Real-time Chat**: Broadcasting for instant updates
7. **Attendance**: Complex calculation logic with multiple statuses
8. **Salary**: Breakdown system with bonuses and deductions

### Areas for Enhancement

1. **Caching**: Redis/Memcached for frequently accessed data
2. **Queue System**: Background job processing for emails/tasks
3. **API**: RESTful API for mobile applications
4. **Notifications**: In-app notification system
5. **Reporting**: Advanced reporting and analytics
6. **Export**: PDF/Excel export functionality
7. **Calendar**: Calendar view for tasks/attendance
8. **Mobile App**: Native mobile applications

---

## Conclusion

This Task Management System is a comprehensive enterprise solution with:

- **22+ Major Modules**
- **Complex Business Logic** (attendance, salary calculations)
- **Real-time Features** (chat, notifications)
- **Automation** (recurring tasks, attendance fetching)
- **Integration** (Zkteco biometric devices)
- **Role-based Access Control** (hierarchical permissions)
- **Email Notifications** (comprehensive notification system)
- **File Management** (attachments, previews)
- **Time Tracking** (estimated vs actual, delays)
- **HR Management** (attendance, salary, probation)

The system is built with scalability, security, and maintainability in mind, using Laravel's best practices and modern web development patterns.

