<div>
    <div class="task-table">
   
        <!-- Table Header -->
        <div class="table-header">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h3 class="mb-2 text-white fw-bold">
                        <i class="bi bi-kanban me-3"></i>Task Management
                    </h3>
                    <p class="mb-0 text-white-50 fs-6">Create, manage, and track your tasks efficiently</p>
                </div>
                <div class="d-flex gap-3">
                    <button class="btn btn-light btn-lg px-4 py-2" wire:click="showTaskCreationModal"
                        wire:loading.attr="disabled">
                        
                        <i class="bi bi-plus-circle me-2"></i>Add Task
                        <span wire:loading wire:target="showTaskCreationModal">
                            <span class="spinner-border spinner-border-sm ms-2"></span>
                        </span>
                    </button>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="p-3 border-bottom">
            <div class="row g-2">
                <div class="col-12 col-md-3">
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-search"></i></span>
                        <input type="text" class="form-control" placeholder="Search tasks..."
                            wire:model.live="search">
                    </div>
                </div>
                <div class="col-6 col-md-2">
                    <select class="form-select" wire:model.live="projectFilter">
                        <option value="">All Projects</option>
                        @foreach($this->projects as $project)
                            <option value="{{ $project->id }}">{{ $project->title }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-6 col-md-2">
                    <select class="form-select" wire:model.live="statusFilter">
                        <option value="">All Status</option>
                        @foreach($this->statuses as $status)
                            <option value="{{ $status->id }}">{{ $status->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-6 col-md-2">
                    <select class="form-select" wire:model.live="categoryFilter">
                        <option value="">All Categories</option>
                        @foreach($this->categories as $category)
                            <option value="{{ $category->id }}">{{ $category->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-md-2">
                    <select class="form-select" wire:model.live="assigneeFilter">
                        <option value="">All Assignees</option>
                        @foreach($this->users as $user)
                            <option value="{{ $user->id }}">{{ $user->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <!-- Mobile Filter Toggle -->
            <div class="d-md-none mt-2">
                <button class="btn btn-outline-secondary btn-sm w-100" type="button" data-bs-toggle="collapse"
                    data-bs-target="#mobileFilters" aria-expanded="false">
                    <i class="bi bi-funnel me-2"></i>More Filters
                </button>
                <div class="collapse mt-2" id="mobileFilters">
                    <div class="row g-2">
                        <div class="col-6">
                            <select class="form-select form-select-sm" wire:model.live="projectFilter">
                                <option value="">All Projects</option>
                                @foreach ($this->projects as $project)
                                    <option value="{{ $project->id }}">{{ $project->title }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-6">
                            <select class="form-select form-select-sm" wire:model.live="statusFilter">
                                <option value="">All Status</option>
                                @foreach ($this->statuses as $status)
                                    <option value="{{ $status->id }}">{{ $status->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-6">
                            <select class="form-select form-select-sm" wire:model.live="categoryFilter">
                                <option value="">All Categories</option>
                                @foreach ($this->categories as $category)
                                    <option value="{{ $category->id }}">{{ $category->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-6">
                            <select class="form-select form-select-sm" wire:model.live="assigneeFilter">
                                <option value="">All Assignees</option>
                                @foreach ($this->users as $user)
                                    <option value="{{ $user->id }}">{{ $user->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Mobile Task Cards (Hidden on Desktop) -->
        <div class="mobile-task-cards">
            @foreach($this->tasks as $task)
                <div class="mobile-task-item" wire:key="mobile-task-{{ $task->id }}">
                    <div class="mobile-task-header">
                        <div>
                            <div class="mobile-task-title">
                                <a href="{{ route('tasks.details', $task->id) }}"
                                    class="text-decoration-none fw-bold" style="color: var(--text-primary);">
                                    {{ $task->title }}
                                </a>
                                @if (in_array($task->nature_of_task, ['daily', 'weekly', 'monthly', 'until_stop']))
                                    <span class="badge bg-info ms-2">
                                        <i
                                            class="bi bi-arrow-repeat me-1"></i>{{ ucfirst(str_replace('_', ' ', $task->nature_of_task)) }}
                                    </span>
                                @endif
                            </div>
                            @if ($task->project)
                                <small class="text-muted">{{ $task->project->title }}</small>
                            @endif
                        </div>
                        <div class="dropdown">
                            <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                <i class="bi bi-three-dots-vertical"></i>
                            </button>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="#"
                                        wire:click="startEditing({{ $task->id }})"><i
                                            class="bi bi-pencil me-2"></i>Edit</a></li>
                                <li><a class="dropdown-item text-info" href="#"
                                        wire:click="openCloneModal({{ $task->id }})"><i
                                            class="bi bi-files me-2"></i>Clone</a></li>
                                @if (in_array($task->nature_of_task, ['daily', 'weekly', 'monthly', 'until_stop']) && $task->is_recurring_active)
                                    <li><a class="dropdown-item text-warning" href="#"
                                            wire:click="stopRecurringTask({{ $task->id }})"><i
                                                class="bi bi-stop-circle me-2"></i>Stop Recurrence</a></li>
                                @endif
                                <li><a class="dropdown-item text-danger" href="#"
                                        wire:click="deleteTask({{ $task->id }})"><i
                                            class="bi bi-trash me-2"></i>Delete</a></li>
                            </ul>
                        </div>
                    </div>

                    <div class="mobile-task-meta">
                        @if($task->assignedTo)
                            <div class="mobile-task-meta-item">
                                <i class="bi bi-person"></i>
                                <span>{{ $task->assignedTo->name }}</span>
                            </div>
                        @endif

                        @if ($task->priority)
                            <div class="mobile-task-meta-item">
                                <span class="badge bg-{{ $task->priority->color }}">{{ $task->priority->name }}</span>
                            </div>
                        @endif

                        @if ($task->category)
                            <div class="mobile-task-meta-item">
                                <i class="bi {{ $task->category->icon }}"></i>
                                <span>{{ $task->category->name }}</span>
                            </div>
                        @endif

                        @if ($task->status)
                            <div class="mobile-task-meta-item">
                                <div class="d-flex align-items-center gap-1">
                                    <span class="badge bg-{{ $task->status->color }}">{{ $task->status->name }}</span>
                                    @if ($task->is_approved)
                                        <span class="badge bg-success" title="Task Approved">
                                            <i class="bi bi-check-circle-fill"></i>
                                        </span>
                                    @endif
                                </div>
                            </div>
                        @endif

                        @if ($task->due_date)
                            <div class="mobile-task-meta-item">
                                <i class="bi bi-calendar"></i>
                                <span>{{ $task->due_date->format('M d, Y') }}</span>
                            </div>
                        @endif

                        @if ($task->estimated_hours)
                            <div class="mobile-task-meta-item">
                                <i class="bi bi-clock"></i>
                                <span>{{ $task->estimated_hours }}h</span>
                            </div>
                        @endif
                    </div>

                    @if ($task->notes)
                        <div class="mobile-task-notes">
                            {{ Str::limit($task->notes, 100) }}
                        </div>
                    @endif

                    <div class="mobile-task-actions">
                        @if($task->status)
                            <div class="dropdown">
                                <button class="btn btn-sm btn-outline-primary dropdown-toggle" type="button"
                                    data-bs-toggle="dropdown">
                                    <div class="d-flex align-items-center gap-1">
                                        <span class="badge bg-{{ $task->status->color }}">{{ $task->status->name }}</span>
                                        @if ($task->is_approved)
                                            <span class="badge bg-success" title="Task Approved">
                                                <i class="bi bi-check-circle-fill"></i>
                                            </span>
                                        @endif
                                    </div>
                                </button>
                                <ul class="dropdown-menu">
                                    @foreach ($this->statuses as $status)
                                        @if (in_array($status->name, ['Complete', 'In Progress']))
                                            <li>
                                                <a class="dropdown-item {{ $task->status && $task->status->id === $status->id ? 'active' : '' }}"
                                                    href="#"
                                                    wire:click="updateTaskStatus({{ $task->id }}, {{ $status->id }})">
                                                    <span
                                                        class="badge bg-{{ $status->color }} me-2">{{ $status->name }}</span>
                                                </a>
                                            </li>
                                        @endif
                                    @endforeach
                                </ul>
                            </div>
                        @endif
                    </div>
                </div>
            @endforeach

            @if ($this->tasks->count() == 0)
                <div class="text-center py-4">
                    <div class="text-muted">
                        <i class="bi bi-inbox fs-1 d-block mb-3"></i>
                        <h5>No tasks found</h5>
                        <p class="mb-0">Start by creating your first task!</p>
                    </div>
                </div>
            @endif
        </div>

        <!-- Bulk Actions -->
        <div id="bulkActions" class="p-3 border-bottom bg-light" style="display: none;">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <span id="selectedCount" class="badge bg-primary me-2">0 tasks selected</span>
                    <span class="text-muted">Bulk actions available</span>
                </div>
                <div class="d-flex gap-2">
                    <div class="dropdown">
                        <button class="btn btn-sm btn-outline-primary dropdown-toggle" type="button"
                            data-bs-toggle="dropdown">
                            <i class="bi bi-check-circle me-1"></i>Update Status
                        </button>
                        <ul class="dropdown-menu">
                            @foreach ($this->statuses as $status)
                                @if (in_array($status->name, ['Complete', 'In Progress']))
                                    <li><a class="dropdown-item" href="#"
                                            onclick="bulkUpdateStatus({{ $status->id }}, '{{ $status->name }}')">{{ $status->name }}</a>
                                    </li>
                                @endif
                            @endforeach
                        </ul>
                    </div>
                    @if (auth()->user()->isSuperAdmin() || auth()->user()->isAdmin() || auth()->user()->isManager())
                    <div class="dropdown">
                        <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button"
                            data-bs-toggle="dropdown">
                            <i class="bi bi-arrow-up-circle me-1"></i>Update Priority
                        </button>
                        <ul class="dropdown-menu">
                            @foreach ($this->priorities as $priority)
                                <li><a class="dropdown-item" href="#"
                                        onclick="bulkUpdatePriority({{ $priority->id }}, '{{ $priority->name }}')">{{ $priority->name }}</a>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                    <div class="dropdown">
                        <button class="btn btn-sm btn-outline-warning dropdown-toggle" type="button"
                            data-bs-toggle="dropdown">
                            <i class="bi bi-person-plus me-1"></i>Assign To
                        </button>
                        <ul class="dropdown-menu">
                            @foreach ($this->users as $user)
                                <li><a class="dropdown-item" href="#"
                                        onclick="bulkUpdateAssignee({{ $user->id }}, '{{ $user->name }}')">{{ $user->name }}</a>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                    {{-- <div class="dropdown">
                        <button class="btn btn-sm btn-outline-info dropdown-toggle" type="button"
                            data-bs-toggle="dropdown">
                            <i class="bi bi-gear me-1"></i>Update Nature
                        </button>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="#"
                                    onclick="bulkUpdateNature('one_time', 'One Time')">One Time</a></li>
                            <li><a class="dropdown-item" href="#"
                                    onclick="bulkUpdateNature('recurring', 'Recurring')">Recurring</a></li>
                        </ul>
                    </div> --}}
                    <button class="btn btn-sm btn-outline-danger" onclick="bulkDeleteTasks()">
                        <i class="bi bi-trash me-1"></i>Delete Selected
                    </button>
                    @endif
                    <button class="btn btn-sm btn-outline-secondary" onclick="clearSelection()">
                        <i class="bi bi-x-circle me-1"></i>Clear Selection
                    </button>
                </div>
            </div>
        </div>

        <!-- Task Table -->
        <div class="table-responsive">
            <table id="tasksTable" class="table table-hover mb-0">
                <thead class="table-dark">
                    <tr>
                        <th style="min-width: 50px;">
                            <input type="checkbox" id="selectAllTasks" class="form-check-input"
                                onchange="toggleAllTasks(this)">
                        </th>
                        <th class="d-none d-md-table-cell" style="min-width: 50px;">#</th>
                        <th style="min-width: 250px;">Title</th>
                        <th class="d-none d-lg-table-cell" style="min-width: 150px;">Description</th>
                        <th class="d-none d-lg-table-cell" style="min-width: 120px;">Project</th>
                        <th class="d-none d-xl-table-cell" style="min-width: 150px;">Assignee</th>
                        <th class="d-none d-lg-table-cell" style="min-width: 100px;">Priority</th>
                        <th class="d-none d-lg-table-cell" style="min-width: 100px;">Category</th>
                        <th style="min-width: 120px;">Status</th>
                        <th class="d-none d-md-table-cell" style="min-width: 120px;">Due Date</th>
                        <th class="d-none d-xl-table-cell" style="min-width: 80px;">Hours</th>
                        <th class="d-none d-lg-table-cell" style="min-width: 100px;">Nature</th>
                        <th style="min-width: 100px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @if($this->tasks->count() == 0)
                        <tr>
                            <td colspan="11" class="text-center py-4">
                                <div class="text-muted">
                                    <i class="bi bi-inbox fs-1 d-block mb-3"></i>
                                    <h5>No tasks found</h5>
                                    <p class="mb-0">Start by creating your first task!</p>
                                </div>
                            </td>
                        </tr>
                    @endif

                    <!-- New Task Row -->
                    @if($editingTaskId === 0)
                        <tr class="task-row editing">
                            <td class="d-none d-md-table-cell">
                                <i class="bi bi-plus-circle text-success"></i>
                            </td>
                            <td>
                                <input type="text" class="form-control form-control-sm" wire:model="newTaskTitle"
                                    placeholder="Task title..." wire:keydown.enter="saveTask">
                            </td>
                            <td class="d-none d-lg-table-cell">
                                <select class="form-select form-select-sm" wire:model="newTaskProjectId">
                                    <option value="">Select Project</option>
                                    @foreach($this->projects as $project)
                                        <option value="{{ $project->id }}">{{ $project->title }}</option>
                                    @endforeach
                                </select>
                            </td>
                            <td class="d-none d-xl-table-cell">
                                <div class="d-flex flex-column gap-1">
                                    <!-- Selected Employees Display -->
                                    @if(count($selectedEmployeeNames) > 0)
                                        <div class="selected-employees">
                                            @foreach($selectedEmployeeNames as $index => $name)
                                                <span class="badge bg-primary me-1 mb-1">
                                                    {{ $name }}
                                                    <button type="button" class="btn-close btn-close-white btn-sm ms-1" 
                                                            wire:click="removeEmployee({{ $newTaskAssigneeIds[$index] }})"
                                                            style="font-size: 0.7em;"></button>
                                                </span>
                                            @endforeach
                                        </div>
                                    @endif
                                    
                                    <!-- Select Employee Button -->
                                    <button type="button" class="btn btn-outline-primary btn-sm" 
                                            wire:click="showEmployeeSelectionModal">
                                        <i class="bi bi-people me-1"></i>
                                        {{ count($selectedEmployeeNames) > 0 ? 'Change Assignees' : 'Select Assignees' }}
                                    </button>
                                </div>
                            </td>
                            <td class="d-none d-lg-table-cell">
                                <select class="form-select form-select-sm" wire:model="newTaskPriority">
                                    <option value="">Select Priority</option>
                                    @foreach($this->priorities as $priority)
                                        <option value="{{ $priority->id }}">{{ $priority->name }}</option>
                                    @endforeach
                                </select>
                                @if ($this->canManagePriorities())
                                    <small class="text-muted">
                                        <a href="#" wire:click="toggleCustomPriorityForm"
                                            class="text-decoration-none">
                                            <i class="bi bi-plus-circle"></i> Add Custom Priority
                                        </a>
                                    </small>
                                @endif
                            </td>
                            <td class="d-none d-lg-table-cell">
                                <select class="form-select form-select-sm" wire:model="newTaskCategory">
                                    <option value="">Select Category</option>
                                    @foreach($this->categories as $category)
                                        <option value="{{ $category->id }}">{{ $category->name }}</option>
                                    @endforeach
                                </select>
                                @if ($this->canManageCategories())
                                    <small class="text-muted">
                                        <a href="#" wire:click="toggleCustomCategoryForm"
                                            class="text-decoration-none">
                                            <i class="bi bi-plus-circle"></i> Add Custom Category
                                        </a>
                                    </small>
                                @endif
                            </td>
                            <td>
                                <span class="badge bg-warning">Pending</span>
                            </td>
                            <td class="d-none d-md-table-cell">
                                <input type="date" class="form-control form-control-sm" wire:model="newTaskDueDate">
                            </td>
                            <td class="d-none d-xl-table-cell">
                                <input type="number" class="form-control form-control-sm" wire:model="newTaskEstimatedHours" 
                                       placeholder="Hours" min="0" step="0.5">
                            </td>
                            <td class="d-none d-lg-table-cell">
                                <button class="btn btn-sm btn-outline-secondary"
                                    wire:click="openNotesModal(0, 'commit')" title="Add Notes">
                                    <i class="bi bi-plus-circle me-1"></i>Add Notes
                                </button>
                            </td>
                            <td>
                                <div class="btn-group" role="group">
                                    <button class="btn btn-sm btn-success" wire:click="saveTask" title="Save">
                                        <i class="bi bi-check"></i>
                                    </button>
                                    <button class="btn btn-sm btn-secondary" wire:click="cancelEditing" title="Cancel">
                                        <i class="bi bi-x"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @endif

                    <!-- Existing Tasks -->
                    @foreach ($this->tasks as $task)
                        <!-- Normal Row -->
                            <tr class="task-row">
                                <td>
                                    <input type="checkbox" class="form-check-input task-checkbox"
                                        value="{{ $task->id }}" onchange="toggleTaskSelection(this)">
                                </td>
                                <td class="d-none d-md-table-cell">{{ $task->id }}</td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        
                                        <div class="flex-grow-1">
                                            <strong class="fs-6 d-block">
                                                <a href="{{ route('tasks.details', $task->id) }}"
                                                    class="text-decoration-none fw-bold"
                                                    style="color: var(--text-primary);">
                                                    {{ $task->title }}
                                                </a>
                                           
                                            </strong>
                                          
                                            <div class="d-flex flex-wrap gap-1 mt-1 d-sm-none">
                                                @if($task->project)
                                                    <span class="badge bg-info badge-sm">{{ Str::limit($task->project->title, 15) }}</span>
                                                @endif
                                                @if($task->priority)
                                                    @if(is_object($task->priority))
                                                        <span class="badge bg-{{ $task->priority->color ?? 'secondary' }} badge-sm">
                                                            {{ $task->priority->name }}
                                                        </span>
                                                    @else
                                                        <span class="badge bg-secondary badge-sm">
                                                            {{ ucfirst($task->priority) }}
                                                        </span>
                                                    @endif
                                                @endif
                                                @if($task->category)
                                                    @if(is_object($task->category))
                                                        <span class="badge bg-{{ $task->category->color ?? 'secondary' }} badge-sm">
                                                            <i class="bi {{ $task->category->icon ?? 'bi-tag' }} me-1"></i>
                                                            {{ $task->category->name }}
                                                        </span>
                                                    @endif
                                                @endif
                                                
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td class="d-none d-lg-table-cell">
                                    <div class="description-container">
                                        {{ Str::limit($task->description, 20) }}
                                    </div>
                                </td>
                                <td class="d-none d-lg-table-cell">
                                    @if ($task->project)
                                        <span class="badge bg-info">{{ $task->project->title }}</span>
                                    @else
                                        <span class="badge bg-secondary">No Project</span>
                                    @endif
                                </td>
                                <td class="d-none d-xl-table-cell">
                                    @if ($task->assignees->count() > 0)
                                        <div class="assignees-compact d-flex align-items-center">
                                            @php
                                                $assignees = $task->assignees;
                                                $firstAssignee = $assignees->first();
                                                $additionalCount = $assignees->count() - 1;
                                            @endphp
                                            
                                            <!-- First Assignee -->
                                            <div class="assignee-item d-flex align-items-center">
                                                <div class="avatar-sm bg-gradient-primary text-white rounded-circle d-flex align-items-center justify-content-center me-2">
                                                    {{ substr($firstAssignee->name, 0, 1) }}
                                                </div>
                                                <div class="assignee-info">
                                                    <div class="fw-semibold">{{ $firstAssignee->name }}</div>
                                                    @if ($firstAssignee->role)
                                                        <small class="text-muted">{{ $firstAssignee->role->name }}</small>
                                                    @endif
                                                </div>
                                            </div>
                                            
                                            <!-- Additional Assignees Indicator -->
                                            @if($additionalCount > 0)
                                                <div class="additional-assignees ms-2">
                                                    <button type="button" 
                                                            class="btn btn-sm p-1"
                                                            data-bs-toggle="popover" 
                                                            data-bs-trigger="hover"
                                                            data-bs-placement="top"
                                                            data-bs-html="true"
                                                            data-bs-content="
                                                                @foreach($assignees->skip(1) as $assignee)
                                                                    <div class='d-flex align-items-center mb-1'>
                                                                        <div class='avatar-xs bg-gradient-primary text-white rounded-circle d-flex align-items-center justify-content-center me-2'>
                                                                            {{ substr($assignee->name, 0, 1) }}
                                                                        </div>
                                                                        <div>
                                                                            <div class='fw-semibold'>{{ $assignee->name }}</div>
                                                                            @if($assignee->role)
                                                                                <small class='text-muted'>{{ $assignee->role->name }}</small>
                                                                            @endif
                                                                        </div>
                                                                    </div>
                                                                @endforeach
                                                            "
                                                            title="Additional Assignees">
                                                        
                                                        <span class="badge bg-primary ms-1"><i class="bi bi-plus"></i> {{ $additionalCount }}</span>
                                                    </button>
                                                </div>
                                            @endif
                                        </div>
                                    @elseif($task->assignedTo)
                                        <div class="d-flex align-items-center">
                                            <div class="avatar-sm bg-gradient-primary text-white rounded-circle d-flex align-items-center justify-content-center me-2">
                                            <div class="avatar-sm bg-gradient-primary text-white rounded-circle d-flex align-items-center justify-content-center me-2">
                                                {{ substr($task->assignedTo->name, 0, 1) }}
                                            </div>
                                            <div>
                                                <div class="fw-semibold">{{ $task->assignedTo->name }}</div>
                                                @if ($task->assignedTo->role)
                                                    <small class="text-muted">{{ $task->assignedTo->role->name }}</small>
                                                @endif
                                            </div>
                                        </div>
                                    @else
                                        <div class="d-flex align-items-center">
                                            <div class="avatar-sm bg-light border d-flex align-items-center justify-content-center me-2">
                                                <i class="bi bi-person text-muted"></i>
                                            </div>
                                            <span class="text-muted">Unassigned</span>
                                        </div>
                                    @endif
                                </td>
                                <td class="d-none d-lg-table-cell">
                                    <div class="dropdown">
                                        @if ($task->priority)
                                            @if (is_object($task->priority))
                                                <button
                                                    class="btn btn-sm badge bg-{{ $task->priority->color ?? 'secondary' }} dropdown-toggle"
                                                    type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                                    {{ $task->priority->name }}
                                                </button>
                                            @else
                                                <button class="btn btn-sm badge bg-secondary dropdown-toggle"
                                                    type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                                    {{ ucfirst($task->priority) }}
                                                </button>
                                            @endif
                                        @else
                                            <button class="btn btn-sm badge bg-secondary dropdown-toggle"
                                                type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                                No Priority
                                            </button>
                                        @endif
                                        <ul class="dropdown-menu">
                                            @foreach($this->priorities as $priority)
                                                <li>
                                                    <a class="dropdown-item {{ $task->priority && is_object($task->priority) && $task->priority->id === $priority->id ? 'active' : '' }}"
                                                        href="#"
                                                        wire:click="updateTaskPriority({{ $task->id }}, {{ $priority->id }})">
                                                        <span
                                                            class="badge bg-{{ $priority->color }} me-2">{{ $priority->name }}</span>
                                                    </a>
                                                </li>
                                            @endforeach
                                            @if($this->canManagePriorities())
                                                <li><hr class="dropdown-divider"></li>
                                                <li>
                                                    <a class="dropdown-item" href="#"
                                                        wire:click="toggleCustomPriorityForm">
                                                        <i class="bi bi-plus-circle me-2"></i>Add Custom Priority
                                                    </a>
                                                </li>
                                            @endif
                                        </ul>
                                    </div>
                                </td>
                                <td class="d-none d-lg-table-cell">
                                    <div class="dropdown">
                                        @if ($task->category)
                                            @if (is_object($task->category))
                                                <button
                                                    class="btn btn-sm badge bg-{{ $task->category->color ?? 'secondary' }} dropdown-toggle"
                                                    type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                                    <i class="bi {{ $task->category->icon ?? 'bi-tag' }} me-1"></i>
                                                    {{ $task->category->name }}
                                                </button>
                                            @else
                                                <button class="btn btn-sm badge bg-secondary dropdown-toggle"
                                                    type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                                    <i class="bi bi-tag me-1"></i>
                                                    {{ ucfirst($task->category) }}
                                                </button>
                                            @endif
                                        @else
                                            <button class="btn btn-sm badge bg-secondary dropdown-toggle"
                                                type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                                <i class="bi bi-tag me-1"></i>
                                                No Category
                                            </button>
                                        @endif
                                        <ul class="dropdown-menu">
                                            @foreach($this->categories as $category)
                                                <li>
                                                    <a class="dropdown-item {{ $task->category && is_object($task->category) && $task->category->id === $category->id ? 'active' : '' }}"
                                                        href="#"
                                                        wire:click="updateTaskCategory({{ $task->id }}, {{ $category->id }})">
                                                        <span class="badge bg-{{ $category->color }} me-2">
                                                            <i class="bi {{ $category->icon }} me-1"></i>
                                                            {{ $category->name }}
                                                        </span>
                                                    </a>
                                                </li>
                                            @endforeach
                                            @if($this->canManageCategories())
                                                <li><hr class="dropdown-divider"></li>
                                                <li>
                                                    <a class="dropdown-item" href="#"
                                                        wire:click="toggleCustomCategoryForm">
                                                        <i class="bi bi-plus-circle me-2"></i>Add Custom Category
                                                    </a>
                                                </li>
                                            @endif
                                        </ul>
                                    </div>
                                </td>
                                <td>
                                    @if ((auth()->user()->isSuperAdmin() || auth()->user()->isAdmin() || $task->assigned_by_user_id === auth()->id() || $task->is_approved === true))
                                    <div class="d-flex align-items-center gap-1">
                                        <span class="badge bg-{{ $task->status->color }} me-2">{{ $task->status->name }}</span>
                                        @if ($task->is_approved)
                                            <span class="badge bg-success" title="Task Approved">
                                                <i class="bi bi-check-circle-fill"></i>
                                            </span>
                                        @endif
                                    </div>
                                    @else
                                        <!-- Regular dropdown for other users and statuses -->
                                        <div class="dropdown">
                                            @if ($task->status)
                                                <button
                                                    class="btn btn-sm badge bg-{{ $task->status->color }} dropdown-toggle"
                                                    type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                                    <div class="d-flex align-items-center gap-1">
                                                        {{ $task->status->name }}
                                                      
                                                    </div>
                                                </button>
                                              
                                            @else
                                                <button class="btn btn-sm badge bg-secondary dropdown-toggle"
                                                    type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                                    No Status
                                                </button>
                                            @endif
                                            <ul class="dropdown-menu">
                                                @foreach ($this->statuses as $status)
                                                    @if (in_array($status->name, ['Complete', 'In Progress']))
                                                        <li>
                                                            <a class="dropdown-item {{ $task->status && $task->status->id === $status->id ? 'active' : '' }}"
                                                                href="#"
                                                                wire:click="updateTaskStatus({{ $task->id }}, {{ $status->id }})">
                                                                <span
                                                                    class="badge bg-{{ $status->color }} me-2">{{ $status->name }}</span>
                                                            </a>
                                                        </li>
                                                    @endif
                                                @endforeach
                                                @if ($this->canManageStatuses())
                                                    {{-- <li>
                                                        <hr class="dropdown-divider">
                                                    </li>
                                                    <li>
                                                        <a class="dropdown-item" href="#"
                                                            wire:click="toggleCustomStatusForm">
                                                            <i class="bi bi-plus-circle me-2"></i>Add Custom Status
                                                        </a>
                                                    </li> --}}
                                                @endif
                                            </ul>
                                        </div>
                                    @endif
                                </td>
                                <td class="d-none d-md-table-cell">
                                    @if($task->due_date)
                                        <div class="d-flex flex-column">
                                            <span class="badge bg-{{ $task->due_date < now() && (!$task->status || !is_object($task->status) || $task->status->name !== 'Complete') ? 'danger' : 'secondary' }} mb-1">
                                                {{ $task->due_date->format('M d') }}
                                            </span>
                                            @if($task->status && is_object($task->status) && $task->status->name === 'Complete' && $task->completed_at)
                                                <small class="badge {{ $task->delay_badge_class }}">
                                                    {{ $task->delay_badge_text }}
                                                </small>
                                            @endif
                                        </div>
                                    @else
                                        <span class="text-muted">No due date</span>
                                    @endif
                                </td>
                                <td class="d-none d-xl-table-cell">
                                    <div class="d-flex flex-column">
                                        @if($task->estimated_hours)
                                            <small class="text-muted">Est: {{ $task->estimated_hours }}h</small>
                                        @endif
                                        @if($task->actual_hours)
                                            <small class="text-primary">Act: {{ $task->actual_hours }}h</small>
                                        @endif
                                        @if(!$task->estimated_hours && !$task->actual_hours)
                                            <span class="text-muted">-</span>
                                        @endif
                                    </div>
                                </td>
                                <td class="d-none d-lg-table-cell">
                                    <span class="badge bg-info">{{ ucfirst(str_replace('_', ' ', $task->nature_of_task)) }}</span>
                                </td>
                            
                                {{-- <td class="d-none d-lg-table-cell">
                                    <div class="notes-container">
                                        @if ($task->notes && trim($task->notes) !== '')
                                            <div class="notes-preview"
                                                style="max-height: 40px; overflow: hidden; font-size: 0.8rem; color: #6c757d; cursor: pointer; padding: 4px; border-radius: 4px; background-color: #f8f9fa;"
                                                wire:click="openNotesModal({{ $task->id }}, 'view')"
                                                title="Click to view notes">
                                                {{ Str::limit($task->notes, 80) }}
                                            </div>
                                            @if (auth()->user()->isSuperAdmin() || auth()->user()->isAdmin() || $task->assigned_by_user_id === auth()->id())
                                                <button class="btn btn-sm btn-outline-success mt-1"
                                                    wire:click="openNotesModal({{ $task->id }}, 'commit')"
                                                    title="Edit Notes">
                                                    <i class="bi bi-pencil"></i>
                                                </button>
                                            @endif
                                            @if($task->noteComments->count() > 0)
                                                <div class="mt-1">
                                                    <small class="text-muted">
                                                        <i class="bi bi-chat-dots me-1"></i>{{ $task->noteComments->count() }} comment(s)
                                                    </small>
                                                </div>
                                            @endif
                                        @else
                                            @if (auth()->user()->isSuperAdmin() || auth()->user()->isAdmin() || $task->assigned_by_user_id === auth()->id())
                                                <button class="btn btn-sm btn-outline-secondary"
                                                    wire:click="openNotesModal({{ $task->id }}, 'commit')"
                                                    title="Add Notes">
                                                    <i class="bi bi-plus-circle me-1"></i>Add Notes
                                                </button>
                                            @else
                                                <span class="text-muted">No notes</span>
                                            @endif
                                        @endif
                                    </div>
                                </td> --}}
                                <td>
                                    @if (auth()->user()->isSuperAdmin() || auth()->user()->isAdmin() || $task->assigned_by_user_id === auth()->id())
                                    <div class="btn-group" role="group">
                                        <button class="btn btn-sm btn-outline-primary" wire:click="startEditing({{ $task->id }})" title="Edit">
                                            <i class="bi bi-pencil"></i>
                                        </button>

                                        <button class="btn btn-sm btn-outline-info"
                                            wire:click="openCloneModal({{ $task->id }})" title="Clone Task">
                                            <i class="bi bi-files"></i>
                                        </button>

                                        <!-- Admin Review Actions for Completed Tasks -->
                                        @if (
                                            $task->status &&
                                                $task->status->name === 'Complete' &&
                                                !$task->is_approved &&
                                                (auth()->user()->isSuperAdmin() || auth()->user()->isAdmin()))
                                            <button class="btn btn-sm btn-outline-success"
                                                wire:click="showAdminReview({{ $task->id }})"
                                                title="Review Completed Task">
                                                <i class="bi bi-check-circle"></i>
                                            </button>
                                            
                                        @endif

                                        @if (in_array($task->nature_of_task, ['daily', 'weekly', 'monthly']) && $task->is_recurring_active)
                                            <button class="btn btn-sm btn-outline-warning"
                                                wire:click="stopRecurringTask({{ $task->id }})"
                                                title="Stop Recurrence">
                                                <i class="bi bi-stop-circle"></i>
                                            </button>
                                        @endif
                                        <button class="btn btn-sm btn-outline-danger"
                                            onclick="confirmDelete({{ $task->id }})" title="Delete">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </div>
                                    @else
                                    <span class="text-muted">No actions</span>
                                    @endif
                                </td>
                            </tr>
                    @endforeach
                </tbody>
  

            </table>
        </div>

        <!-- Mobile Card Layout -->


        <!-- DataTables will handle pagination -->
    </div>
                <!-- Admin Review Modal - Simple and Working -->
                @if ($showAdminReviewModal)
                <div wire:ignore.self class="modal fade show d-block" tabindex="-1"
                    role="dialog" style="@if ($showAdminReviewModal) background: rgba(0,0,0,0.5); z-index: 1055; @endif">
                    <div class="modal-dialog modal-lg modal-dialog-centered" role="document" style="z-index: 1056;">
                        <div class="modal-content admin-review-modal-content">
    
                        <!-- Header -->
                        <div class="modal-header admin-review-modal-header">
                            <h5 class="modal-title fw-bold">
                                @if($adminReviewAction === 'approve')
                                    <i class="bi bi-check-circle me-2"></i>Approve Task
                                @elseif($adminReviewAction === 'revisit')
                                    <i class="bi bi-arrow-clockwise me-2"></i>Mark Task for Revisit
                                @else
                                    <i class="bi bi-check-circle me-2"></i>Review Completed Task
                                @endif
                            </h5>
                            <button type="button" class="btn-close btn-close-white"
                                wire:click="closeAdminReviewModal"></button>
                        </div>
    
                            <!-- Body -->
                            <div class="modal-body" style="background-color: var(--bg-secondary); color: var(--text-primary);">
                                @php
                                    $task = \App\Models\Task::find($adminReviewTaskId);
                                @endphp
    
                                @if ($task)
                                    <!-- Task Details -->
                                    <div class="p-3 rounded mb-4 task-details-box">
                                        <h6 class="fw-bold mb-3" style="color: var(--text-primary);">
                                            <i class="bi bi-list-task text-primary me-2"></i>Task Details
                                        </h6>
    
                                        <div class="row">
                                            <div class="col-md-6 mb-2">
                                                <p style="color: var(--text-primary);"><strong>Title:</strong> {{ $task->title }}</p>
                                                <p style="color: var(--text-primary);"><strong>Project:</strong> {{ $task->project->title ?? 'No Project' }}</p>
                                                <p style="color: var(--text-primary);">
                                                    <strong>Priority:</strong>
                                                    <span class="badge"
                                                        style="background: {{ $task->priority->color ?? '#6c757d' }};">
                                                        {{ $task->priority->name ?? 'Medium' }}
                                                    </span>
                                                </p>
                                            </div>
                                            <div class="col-md-6 mb-2">
                                                <p style="color: var(--text-primary);"><strong>Assigned To:</strong> {{ $task->assigneeNames ?? 'N/A' }}</p>
                                                @if ($task->due_date)
                                                    <p style="color: var(--text-primary);"><strong>Due Date:</strong>
                                                        {{ \Carbon\Carbon::parse($task->due_date)->format('M d, Y') }}</p>
                                                @endif
                                                <p style="color: var(--text-primary);"><strong>Completed At:</strong>
                                                    {{ $task->completed_at ? \Carbon\Carbon::parse($task->completed_at)->format('M d, Y H:i') : 'N/A' }}
                                                </p>
                                            </div>
                                        </div>
    
                                        @if ($task->description)
                                            <div class="mt-3">
                                                <strong style="color: var(--text-primary);">Description:</strong>
                                                <div class="border rounded p-2 mt-1 description-box">
                                                    <span style="color: var(--text-primary);">{{ $task->description }}</span>
                                                </div>
                                            </div>
                                        @endif
                                    </div>
    
                                <!-- Comments Section -->
                                <div class="mb-4">
                                    <label class="fw-bold mb-2" style="color: var(--text-primary);">
                                        <i class="bi bi-chat-text me-2"></i>
                                        @if($adminReviewAction === 'approve')
                                            Approval Comments (Optional)
                                        @elseif($adminReviewAction === 'revisit')
                                            Revisit Comments (Required)
                                        @else
                                            Admin Comments (Optional)
                                        @endif
                                    </label>
                                    <textarea wire:model="adminReviewComments" rows="4" class="form-control admin-review-textarea"
                                        placeholder="@if($adminReviewAction === 'approve')Add any comments about the task completion...@elseif($adminReviewAction === 'revisit')Explain why the task needs to be revisited...@elseAdd any comments or feedback for the assignee...@endif"></textarea>
                                    <small class="text-muted">
                                        @if($adminReviewAction === 'approve')
                                            Comments will be logged with the approval.
                                        @elseif($adminReviewAction === 'revisit')
                                            Comments will be included in the email notification sent to assignees.
                                        @else
                                            Comments will be included in the email notification if task is marked for revisit.
                                        @endif
                                    </small>
                                </div>

                                <!-- Action Info -->
                                @if($adminReviewAction === 'approve')
                                    <div class="alert alert-success">
                                        <strong><i class="bi bi-check-circle me-2"></i>Approval Action:</strong>
                                        <p class="mb-0 mt-2">This will mark the task as finally completed. No further action will be required from the assignee.</p>
                                    </div>
                                @elseif($adminReviewAction === 'revisit')
                                    <div class="alert alert-warning">
                                        <strong><i class="bi bi-arrow-clockwise me-2"></i>Revisit Action:</strong>
                                        <p class="mb-0 mt-2">This will change the task status to "Needs Revisit" and send an email notification to all assignees with your comments.</p>
                                    </div>
                                @else
                                    <div class="alert alert-info">
                                        <strong><i class="bi bi-info-circle me-2"></i>Review Options:</strong>
                                        <ul class="mb-0 mt-2 ps-3">
                                            <li><strong>Approve:</strong> Mark the task as finally completed (no further action required).</li>
                                            <li><strong>Revisit:</strong> Send the task back to the assignee with "Needs Revisit" status.</li>
                                        </ul>
                                    </div>
                                @endif
                                @else
                                    <p class="text-danger">Task not found or deleted.</p>
                                @endif
                            </div>
    
                        <!-- Footer -->
                        <div class="modal-footer" style="background-color: var(--bg-secondary); border-top: 1px solid var(--border-color);">
                            <button type="button" class="btn btn-secondary" wire:click="closeAdminReviewModal">
                                <i class="bi bi-x-circle me-2"></i>Cancel
                            </button>
                            @if($adminReviewAction === 'approve')
                                <button type="button" class="btn btn-success" wire:click="approveTask">
                                    <i class="bi bi-check-circle me-2"></i>Approve Task
                                </button>
                            @elseif($adminReviewAction === 'revisit')
                                <button type="button" class="btn btn-warning admin-review-warning-btn" wire:click="revisitTask">
                                    <i class="bi bi-arrow-clockwise me-2"></i>Mark for Revisit
                                </button>
                            @else
                                <button type="button" class="btn btn-success" wire:click="approveTask">
                                    <i class="bi bi-check-circle me-2"></i>Approve Task
                                </button>
                                <button type="button" class="btn btn-warning admin-review-warning-btn" wire:click="revisitTask">
                                    <i class="bi bi-arrow-clockwise me-2"></i>Mark for Revisit
                                </button>
                            @endif
                        </div>
                        </div>
                    </div>
                </div>
            @endif
    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Confirm Delete</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete this task? This action cannot be undone.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-danger" id="confirmDeleteBtn">Delete</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        let taskIdToDelete = null;

        function confirmDelete(taskId) {
            taskIdToDelete = taskId;
            const modal = new bootstrap.Modal(document.getElementById('deleteModal'));
            modal.show();
        }

        document.getElementById('confirmDeleteBtn').addEventListener('click', function() {
            if (taskIdToDelete) {
                @this.deleteTask(taskIdToDelete);
                const modal = bootstrap.Modal.getInstance(document.getElementById('deleteModal'));
                modal.hide();
            }
        });

        // Fix dropdown positioning to appear above all rows
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize all dropdowns with proper positioning
            function initializeDropdowns() {
                document.querySelectorAll('.dropdown-toggle').forEach(toggle => {
                    // Remove any existing event listeners
                    toggle.removeEventListener('click', handleDropdownClick);
                    // Add new event listener
                    toggle.addEventListener('click', handleDropdownClick);
                });
            }

            function handleDropdownClick(e) {
                const dropdownMenu = this.querySelector('.dropdown-menu');
                if (!dropdownMenu) return;

                // Wait for Bootstrap to show the dropdown
                setTimeout(() => {
                    positionDropdown(this, dropdownMenu);
                }, 50);
            }

            function positionDropdown(toggle, menu) {
                const toggleRect = toggle.getBoundingClientRect();
                const menuRect = menu.getBoundingClientRect();
                const viewportHeight = window.innerHeight;
                const viewportWidth = window.innerWidth;

                // Reset positioning
                menu.style.top = '';
                menu.style.bottom = '';
                menu.style.left = '';
                menu.style.right = '';
                menu.style.transform = '';
                menu.style.position = 'absolute';
                menu.style.zIndex = '1050';

                // Force dropdown to be visible first
                menu.style.display = 'block';
                menu.style.visibility = 'visible';
                menu.style.opacity = '1';

                // Get updated menu dimensions after showing
                const updatedMenuRect = menu.getBoundingClientRect();

                // Check if dropdown goes beyond viewport bottom
                if (toggleRect.bottom + updatedMenuRect.height > viewportHeight - 10) {
                    // Position above the button
                    menu.style.top = 'auto';
                    menu.style.bottom = '100%';
                    menu.style.marginTop = '0';
                    menu.style.marginBottom = '2px';
                    menu.classList.add('dropup');
                } else {
                    menu.classList.remove('dropup');
                }

                // Check if dropdown goes beyond viewport right edge
                if (toggleRect.left + updatedMenuRect.width > viewportWidth - 10) {
                    menu.style.left = 'auto';
                    menu.style.right = '0';
                }

                // Check if dropdown goes beyond viewport left edge
                if (toggleRect.left < 10) {
                    menu.style.left = '0';
                    menu.style.right = 'auto';
                }

                // Additional check for table-specific positioning
                const tableContainer = toggle.closest('.table-responsive');
                if (tableContainer) {
                    const tableRect = tableContainer.getBoundingClientRect();

                    // If dropdown would be clipped by table container
                    if (toggleRect.bottom + updatedMenuRect.height > tableRect.bottom) {
                        menu.style.top = 'auto';
                        menu.style.bottom = '100%';
                        menu.style.marginTop = '0';
                        menu.style.marginBottom = '2px';
                        menu.classList.add('dropup');
                    }
                }

                // Ensure dropdown is always visible
                menu.style.display = 'block';
                menu.style.visibility = 'visible';
                menu.style.opacity = '1';
                menu.style.position = 'absolute';
                menu.style.zIndex = '1050';
            }

            // Handle Bootstrap dropdown events
            document.addEventListener('show.bs.dropdown', function(e) {
                const dropdownMenu = e.target.querySelector('.dropdown-menu');
                if (dropdownMenu && e.target.closest('.task-table-container')) {
                    // Set maximum z-index
                    dropdownMenu.style.zIndex = '99999';
                    dropdownMenu.style.position = 'absolute';
                    
                    // Ensure it appears above all table rows
                    setTimeout(() => {
                        dropdownMenu.style.zIndex = '99999';
                        dropdownMenu.style.position = 'absolute';
                    }, 10);
                }
            });
        });

    </script>

    <style>
        .avatar-sm {
            width: 24px;
            height: 24px;
            font-size: 0.75rem;
        }

        .badge-sm {
            font-size: 0.7rem;
            padding: 0.25em 0.5em;
        }

        /* Responsive table styles */
        .table-responsive {
            position: relative;
            border: none;
            border-radius: 0;
            overflow-x: auto !important;
            overflow-y: auto;
        }

        .table-responsive .sticky-top {
            position: sticky;
            top: 0;
            z-index: 10;
            background-color: #212529 !important;
        }

        .table-responsive table {
            min-width: 1200px !important;
            /* Ensure table has minimum width for horizontal scroll */
            width: 100%;
        }

        .table-responsive::-webkit-scrollbar {
            height: 8px;
        }

        .table-responsive::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 4px;
        }

        .table-responsive::-webkit-scrollbar-thumb {
            background: #888;
            border-radius: 4px;
        }

        .table-responsive::-webkit-scrollbar-thumb:hover {
            background: #555;
        }

        @media (max-width: 768px) {
            .table-responsive {
                border: none;
            }

            .table {
                font-size: 0.875rem;
            }

            .btn-group .btn {
                padding: 0.25rem 0.5rem;
            }

            .dropdown-menu {
                font-size: 0.8rem;
            }

            .table-responsive table {
                min-width: 800px !important;
                /* Smaller minimum width on mobile */
            }
        }

        @media (max-width: 576px) {
            .table {
                font-size: 0.8rem;
            }

            .btn-sm {
                padding: 0.2rem 0.4rem;
                font-size: 0.75rem;
            }

            .badge {
                font-size: 0.65rem;
            }
        }

        /* Status Dropdown Styling */
        .dropdown-toggle::after {
            margin-left: 0.5em;
            font-size: 0.7em;
        }

        .dropdown-menu {
            border: none;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            border-radius: 0.5rem;
            padding: 0.5rem 0;
            z-index: 9999 !important;
            position: absolute !important;
        }

        .dropdown-item {
            padding: 0.5rem 1rem;
            font-size: 0.875rem;
            transition: all 0.2s ease;
        }

        .dropdown-item:hover {
            background-color: #f8f9fa;
            color: #495057;
        }

        .dropdown-item.active {
            background-color: #007bff;
            color: white;
        }

        .dropdown-item i {
            width: 16px;
            text-align: center;
        }

        /* Fix dropdown z-index issues */
        .dropdown {
            position: relative;
            z-index: 1000;
        }

        .table-responsive {
            overflow: visible !important;
        }

        .task-table {
            overflow: visible !important;
        }

        .task-table .table {
            overflow: visible !important;
        }

        .table-responsive td {
            overflow: visible !important;
            position: relative !important;
        }

        .table-responsive tr {
            overflow: visible !important;
        }

        /* Force all containers to allow dropdown overflow */
        .task-table,
        .task-table *,
        .table-responsive,
        .table-responsive * {
            overflow: visible !important;
        }

        /* Exception for horizontal scrolling */
        .table-responsive {
            overflow-x: auto !important;
            overflow-y: visible !important;
        }

        /* Dropdown positioning for table edges - Specific fixes */
        .dropdown-menu {
            position: absolute !important;
            z-index: 1050 !important;
            display: none;
            min-width: 160px;
            padding: 0.5rem 0;
            margin: 0;
            background-color: #fff;
            border: 1px solid rgba(0, 0, 0, .15);
            border-radius: 0.375rem;
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.175);
        }

        .dropdown-menu.show {
            display: block !important;
            position: absolute !important;
            z-index: 1050 !important;
        }

        /* Force dropdown positioning */
        .dropdown-menu[data-bs-popper] {
            top: 100% !important;
            left: 0 !important;
            margin-top: 0.125rem !important;
        }

        /* Dropup positioning */
        .dropdown-menu.dropup {
            top: auto !important;
            bottom: 100% !important;
            margin-top: 0 !important;
            margin-bottom: 0.125rem !important;
        }

        /* Ensure dropdowns are not clipped */
        .table td .dropdown-menu {
            position: absolute !important;
            z-index: 1050 !important;
            top: 100% !important;
            left: 0 !important;
            right: auto !important;
            transform: none !important;
        }


        .task-table {
            position: relative;
            overflow: visible;
            /* Allow dropdowns to extend beyond table */
            border-radius: 0.5rem;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            background: white;
        }

        .task-table .table-header {
           
            padding: 1.5rem;
            border-radius: 0.5rem 0.5rem 0 0;
        }

        .task-table .table-responsive {
            overflow-x: auto !important;
            overflow-y: auto;
            max-height: 70vh;
            border: none;
            border-radius: 0 0 0.5rem 0.5rem;
            width: 100%;
            display: block;
        }

        .task-table .table {
            margin-bottom: 0;
            min-width: 1200px;
            /* Force horizontal scroll */
            width: 100%;
        }

        .task-table .table td {
            position: relative;
        }

        /* Mobile-specific styles */
        @media (max-width: 991px) {
            .notes-container {
                max-width: 120px;
            }

            .notes-preview {
                font-size: 0.7rem !important;
            }

            /* Hide desktop table on mobile, show mobile cards */
            .table-responsive {
                display: none;
            }

            .mobile-task-cards {
                display: block;
            }
        }

        @media (min-width: 992px) {

            /* Hide mobile cards on desktop, show table */
            .mobile-task-cards {
                display: none;
            }

            .table-responsive {
                display: block;
            }
        }

        /* Mobile task cards styling */
        .task-table .mobile-task-cards {
            padding: 1rem;
            background: white;
            border-radius: 0 0 0.5rem 0.5rem;
        }

        .mobile-task-item {
            background: white;
            border: 1px solid #dee2e6;
            border-radius: 0.5rem;
            padding: 1rem;
            margin-bottom: 1rem;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .mobile-task-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 0.75rem;
        }

        .mobile-task-title {
            font-weight: 600;
            font-size: 1rem;
            color: #212529;
            margin-bottom: 0.25rem;
        }

        .mobile-task-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
            margin-bottom: 0.75rem;
        }

        .mobile-task-meta-item {
            display: flex;
            align-items: center;
            gap: 0.25rem;
            font-size: 0.875rem;
            color: #6c757d;
        }

        .mobile-task-notes {
            font-size: 0.875rem;
            color: #495057;
            line-height: 1.4;
            margin-bottom: 0.75rem;
            padding: 0.5rem;
            background-color: #f8f9fa;
            border-radius: 0.25rem;
        }

        .mobile-task-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }


        /* Ensure proper spacing on mobile */
        @media (max-width: 768px) {
            .task-row td {
                padding: 0.5rem 0.25rem;
            }

            .btn-group .btn {
                margin: 0 1px;
            }

            .task-table .mobile-task-cards {
                padding: 0.5rem;
            }

            .mobile-task-item {
                padding: 0.75rem;
            }
        }
    </style>

    <script>
        // Initialize DataTable when Livewire updates
        document.addEventListener('livewire:updated', function () {
            if ($.fn.DataTable.isDataTable('#tasksTable')) {
                $('#tasksTable').DataTable().destroy();
            }

            $('#tasksTable').DataTable({
                responsive: true,
                pageLength: 10,
                lengthMenu: [[5, 10, 25, 50, -1], [5, 10, 25, 50, "All"]],
                order: [[0, 'desc']],
                columnDefs: [
                    { orderable: false, targets: [10] }, // Actions column
                    { className: "text-center", targets: [0, 4, 5, 6, 7, 8, 9, 10] }, // Center align certain columns
                    { responsivePriority: 1, targets: [1] }, // Title column priority
                    { responsivePriority: 2, targets: [6] }, // Status column priority
                    { responsivePriority: 3, targets: [10] }, // Actions column priority
                ],
                language: {
                    search: "Search tasks:",
                    lengthMenu: "Show _MENU_ tasks per page",
                    info: "Showing _START_ to _END_ of _TOTAL_ tasks",
                    paginate: {
                        first: "First",
                        last: "Last",
                        next: "Next",
                        previous: "Previous"
                    }
                },
                dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>' +
                    '<"row"<"col-sm-12"tr>>' +
                    '<"row"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>',
                responsive: {
                    details: {
                        type: 'column',
                        target: 'tr'
                    }
                },
                // Fix dropdown positioning with DataTables
                drawCallback: function() {
                    // Re-initialize dropdown positioning after table redraw
                    setTimeout(() => {
                        const dropdowns = document.querySelectorAll('.dropdown-menu');
                        dropdowns.forEach(dropdown => {
                            dropdown.style.zIndex = '1050';
                            dropdown.style.position = 'absolute';
                        });

                        // Re-attach dropdown event listeners
                        document.querySelectorAll('.dropdown-toggle').forEach(toggle => {
                            toggle.addEventListener('click', function(e) {
                                const dropdownMenu = this.querySelector(
                                    '.dropdown-menu');
                                if (dropdownMenu) {
                                    // Smart positioning logic
                                    setTimeout(() => {
                                        const tableContainer = this
                                            .closest(
                                                '.table-responsive') ||
                                            this.closest('.task-table');
                                        const tableRect =
                                            tableContainer ?
                                            tableContainer
                                            .getBoundingClientRect() :
                                            null;
                                        const dropdownRect =
                                            dropdownMenu
                                            .getBoundingClientRect();
                                        const toggleRect = this
                                            .getBoundingClientRect();

                                        // Reset positioning
                                        dropdownMenu.style.top = '100%';
                                        dropdownMenu.style.left = '0';
                                        dropdownMenu.style.right =
                                            'auto';
                                        dropdownMenu.style.transform =
                                            'none';

                                        // Check if dropdown goes beyond table bottom
                                        if (tableRect && (toggleRect
                                                .bottom + dropdownRect
                                                .height) > tableRect
                                            .bottom) {
                                            dropdownMenu.style.top =
                                                'auto';
                                            dropdownMenu.style.bottom =
                                                '100%';
                                            dropdownMenu.style
                                                .marginTop = '0';
                                            dropdownMenu.style
                                                .marginBottom = '2px';
                                        }

                                        // Check if dropdown goes beyond table right edge
                                        if (tableRect && (toggleRect
                                                .left + dropdownRect
                                                .width) > tableRect
                                            .right) {
                                            dropdownMenu.style.left =
                                                'auto';
                                            dropdownMenu.style.right =
                                                '0';
                                        }

                                        dropdownMenu.style.zIndex =
                                            '1050';
                                        dropdownMenu.style.position =
                                            'absolute';
                                    }, 10);
                                }
                            });
                        });
                    }, 100);
                }
            });
        });

        // Initialize DataTable on page load
        document.addEventListener('DOMContentLoaded', function () {
            setTimeout(function() {
                if ($.fn.DataTable.isDataTable('#tasksTable')) {
                    $('#tasksTable').DataTable().destroy();
                }
                
                $('#tasksTable').DataTable({
                    responsive: true,
                    pageLength: 10,
                    lengthMenu: [[5, 10, 25, 50, -1], [5, 10, 25, 50, "All"]],
                    order: [[0, 'desc']],
                    columnDefs: [
                        { orderable: false, targets: [10] }, // Actions column
                        { className: "text-center", targets: [0, 4, 5, 6, 7, 8, 9, 10] }, // Center align certain columns
                        { responsivePriority: 1, targets: [1] }, // Title column priority
                        { responsivePriority: 2, targets: [6] }, // Status column priority
                        { responsivePriority: 3, targets: [10] }, // Actions column priority
                    ],
                    language: {
                        search: "Search tasks:",
                        lengthMenu: "Show _MENU_ tasks per page",
                        info: "Showing _START_ to _END_ of _TOTAL_ tasks",
                        paginate: {
                            first: "First",
                            last: "Last",
                            next: "Next",
                            previous: "Previous"
                        }
                    },
                    dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>' +
                        '<"row"<"col-sm-12"tr>>' +
                        '<"row"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>',
                    responsive: {
                        details: {
                            type: 'column',
                            target: 'tr'
                        }
                    },
                    drawCallback: function() {
                        setTimeout(() => {
                            const dropdowns = document.querySelectorAll('.dropdown-menu');
                            dropdowns.forEach(dropdown => {
                                dropdown.style.zIndex = '1050';
                                dropdown.style.position = 'absolute';
                            });

                            // Re-attach dropdown event listeners for edge positioning
                            document.querySelectorAll('.dropdown-toggle').forEach(toggle => {
                                toggle.addEventListener('click', function(e) {
                                    const dropdownMenu = this.querySelector(
                                        '.dropdown-menu');
                                    if (dropdownMenu) {
                                        setTimeout(() => {
                                            const tableContainer = this
                                                .closest(
                                                    '.table-responsive'
                                                ) || this.closest(
                                                    '.task-table');
                                            const tableRect =
                                                tableContainer ?
                                                tableContainer
                                                .getBoundingClientRect() :
                                                null;
                                            const dropdownRect =
                                                dropdownMenu
                                                .getBoundingClientRect();
                                            const toggleRect = this
                                                .getBoundingClientRect();

                                            // Reset positioning
                                            dropdownMenu.style.top =
                                                '100%';
                                            dropdownMenu.style.left =
                                                '0';
                                            dropdownMenu.style.right =
                                                'auto';
                                            dropdownMenu.style
                                                .transform = 'none';

                                            // Check if dropdown goes beyond table bottom
                                            if (tableRect && (toggleRect
                                                    .bottom +
                                                    dropdownRect.height
                                                ) > tableRect.bottom) {
                                                dropdownMenu.style.top =
                                                    'auto';
                                                dropdownMenu.style
                                                    .bottom = '100%';
                                                dropdownMenu.style
                                                    .marginTop = '0';
                                                dropdownMenu.style
                                                    .marginBottom =
                                                    '2px';
                                            }

                                            // Check if dropdown goes beyond table right edge
                                            if (tableRect && (toggleRect
                                                    .left + dropdownRect
                                                    .width) > tableRect
                                                .right) {
                                                dropdownMenu.style
                                                    .left = 'auto';
                                                dropdownMenu.style
                                                    .right = '0';
                                            }

                                            dropdownMenu.style.zIndex =
                                                '1050';
                                            dropdownMenu.style
                                                .position = 'absolute';
                                        }, 10);
                                    }
                                });
                            });
                        }, 100);
                    }
                });
            }
        });
    </script>

    <!-- Notes Modal -->
    @if ($showNotesModal)
        <div class="modal fade show" style="display: block;" tabindex="-1" role="dialog">
            <div class="modal-dialog modal-xl" role="document">
                <div class="modal-content shadow-lg border-0">
                    <div class="modal-header bg-gradient-primary text-white border-0">
                        <h5 class="modal-title fw-bold">
                            <i class="bi bi-sticky me-2"></i>{{ $notesModalTitle }}
                        </h5>
                        <button type="button" class="btn-close btn-close-white"
                            wire:click="closeNotesModal"></button>
                    </div>
                    <div class="modal-body p-4">
                        @if ($notesModalMode === 'commit')
                            <div class="row">
                                <div class="col-lg-8">
                                    <div class="card border-0 shadow-sm">
                                        <div class="card-header bg-light border-0">
                                            <h6 class="mb-0 fw-semibold">
                                                <i class="bi bi-pencil-square me-2 text-primary"></i>Notes Content
                                            </h6>
                                        </div>
                                        <div class="card-body">
                                            <div class="mb-3">
                                                <label for="notesContent" class="form-label fw-semibold">Write your
                                                    notes</label>
                                                <textarea class="form-control border-0 shadow-sm" id="notesContent" wire:model="notesModalContent" rows="8"
                                                    placeholder="Enter your notes here..." style="resize: vertical; min-height: 200px;"></textarea>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-lg-4">
                                    <div class="card border-0 shadow-sm">
                                        <div class="card-header bg-light border-0">
                                            <h6 class="mb-0 fw-semibold">
                                                <i class="bi bi-paperclip me-2 text-primary"></i>Attach Files
                                            </h6>
                                        </div>
                                        <div class="card-body">
                                            <div class="mb-3">
                                                <label for="notesAttachments" class="form-label fw-semibold">Select
                                                    Files</label>
                                                <input type="file" class="form-control border-0 shadow-sm"
                                                    id="notesAttachments" wire:model="notesAttachments" multiple
                                                    accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.txt,.jpg,.jpeg,.png,.gif,.zip,.rar,.mp4,.webm,.ogg,.avi,.mov,.wmv,.flv,.mkv">
                                                <div class="form-text mt-2">
                                                    <i class="bi bi-info-circle me-1 text-info"></i>
                                                    <small>Supported: PDF, DOC, XLS, PPT, TXT, Images, Videos, Archives
                                                        (Max: 10MB each)</small>
                                                </div>

                                                <!-- Uploaded Files Preview -->
                                                @if ($notesAttachments)
                                                    <div class="mt-3">
                                                        <h6 class="text-muted mb-2 fw-semibold">
                                                            <i class="bi bi-upload me-1"></i>Files to Upload
                                                            ({{ count($notesAttachments) }})
                                                        </h6>
                                                        <div class="list-group list-group-flush">
                                                            @foreach ($notesAttachments as $index => $file)
                                                                <div
                                                                    class="list-group-item d-flex align-items-center justify-content-between border-0 px-0">
                                                                    <div class="d-flex align-items-center">
                                                                        <div class="file-icon-sm me-3">
                                                                            <i
                                                                                class="bi bi-file-earmark text-primary"></i>
                                                                        </div>
                                                                        <div>
                                                                            <div class="fw-semibold text-truncate"
                                                                                style="max-width: 200px;">
                                                                                {{ $file->getClientOriginalName() }}
                                                                            </div>
                                                                            <small
                                                                                class="text-muted">{{ number_format($file->getSize() / 1024, 1) }}
                                                                                KB</small>
                                                                        </div>
                                                                    </div>
                                                                    <button type="button"
                                                                        class="btn btn-sm btn-outline-danger rounded-circle"
                                                                        wire:click="removeNotesAttachment({{ $index }})"
                                                                        style="width: 32px; height: 32px;">
                                                                        <i class="bi bi-x"></i>
                                                                    </button>
                                                                </div>
                                                            @endforeach
                                                        </div>
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- <div class="mb-3">
                            <label for="commitMessage" class="form-label">Commit Message</label>
                            <input type="text" class="form-control" 
                                   id="commitMessage"
                                   wire:model="commitMessage" 
                                   placeholder="Enter commit message (e.g., 'Updated task requirements')">
                            <div class="form-text">Describe what changes you're making to the notes.</div>
                        </div> --}}
                        @else
                            <div class="notes-view">
                                <!-- Notes Content -->
                                @if ($notesModalContent)
                                    <div class="card border-0 shadow-sm mb-4">
                                        <div class="card-header bg-light border-0">
                                            <h6 class="mb-0 fw-semibold">
                                                <i class="bi bi-sticky me-2 text-primary"></i>Notes Content
                                            </h6>
                                        </div>
                                        <div class="card-body">
                                            <div class="notes-content"
                                                style="line-height: 1.8; font-size: 1rem; color: #495057;">
                                                {{ $notesModalContent }}
                                            </div>
                                        </div>
                                        <!-- Files Display Section -->
                                        <div class="card border-0 shadow-sm mb-4">
                                            <div
                                                class="card-header bg-light-blue border-0 d-flex justify-content-between align-items-center">
                                                <h6 class="mb-0 fw-semibold">
                                                    <i class="bi bi-paperclip me-2 text-primary"></i>Attached Files
                                                    <span
                                                        class="badge bg-primary ms-2">{{ $this->getTaskNotesAttachments()->count() }}</span>
                                                </h6>
                                                <i class="bi bi-plus-circle me-2 text-primary"
                                                    data-bs-toggle="tooltip" data-bs-placement="top"
                                                    title="Click to Attach File" onclick="openAttachFileModal()"
                                                    style="cursor: pointer;"></i>
                                            </div>
                                            <div class="card-body bg-light-blue">
                                                @if ($this->getTaskNotesAttachments()->count() > 0)
                                                    <div class="attachments-minimal"
                                                        wire:init="initializeModalTooltips">
                                                        @foreach ($this->getTaskNotesAttachments() as $attachment)
                                                            @php
                                                                $extension = strtolower(
                                                                    pathinfo(
                                                                        $attachment->file_name,
                                                                        PATHINFO_EXTENSION,
                                                                    ),
                                                                );
                                                                $iconClass = match ($extension) {
                                                                    'pdf' => 'bi-file-earmark-pdf',
                                                                    'doc', 'docx' => 'bi-file-earmark-word',
                                                                    'xls', 'xlsx' => 'bi-file-earmark-excel',
                                                                    'ppt', 'pptx' => 'bi-file-earmark-ppt',
                                                                    'jpg',
                                                                    'jpeg',
                                                                    'png',
                                                                    'gif'
                                                                        => 'bi-file-earmark-image',
                                                                    'mp4',
                                                                    'webm',
                                                                    'ogg',
                                                                    'avi',
                                                                    'mov',
                                                                    'wmv',
                                                                    'flv',
                                                                    'mkv'
                                                                        => 'bi-file-earmark-play',
                                                                    'zip', 'rar' => 'bi-file-earmark-zip',
                                                                    'txt' => 'bi-file-earmark-text',
                                                                    default => 'bi-file-earmark',
                                                                };
                                                                $isPreviewable = $this->isPreviewableFile($extension);
                                                            @endphp

                                                            <div class="attachment-card d-flex justify-content-between align-items-center p-2"
                                                                wire:key="attachment-{{ $attachment->id }}">
                                                                <div class="attachment-icon-container">

                                                                    <div
                                                                        class="attachment-name d-flex align-items-center text-truncate gap-1">
                                                                        <i class="bi {{ $iconClass }}"
                                                                            style="font-size: 1.25rem;"
                                                                            data-bs-toggle="tooltip"
                                                                            data-bs-placement="top"
                                                                            title="{{ $attachment->file_name }}"></i>
                                                                        {{ $attachment->file_name }}
                                                                    </div>
                                                                    <div class="attachment-info-container">

                                                                        <div
                                                                            class="attachment-timestamp d-flex align-items-center text-muted gap-1">
                                                                            <i class="bi bi-calendar"></i><small
                                                                                class="text-muted">
                                                                                {{ $attachment->created_at->format('g:i A, j, F') }}</small>
                                                                            <i class="bi bi-person me-1 cursor-pointer"
                                                                                data-bs-toggle="tooltip"
                                                                                data-bs-placement="top"
                                                                                title="Uploaded by {{ $attachment->uploadedBy->name }}"></i>
                                                                            {{ $attachment->uploadedBy->name }}
                                                                        </div>
                                                                    </div>
                                                                </div>

                                                                <div class="attachment-actions-container">
                                                                    @if ($isPreviewable)
                                                                        <i class="bi bi-eye me-1"
                                                                            style="cursor: pointer;"
                                                                            data-bs-toggle="tooltip"
                                                                            data-bs-placement="top" title="View File"
                                                                            onclick="openFilePreviewModal({{ $attachment->id }}, '{{ $attachment->file_name }}')"></i>
                                                                    @endif
                                                                    <a href="{{ route('attachments.download', $attachment->id) }}"
                                                                        download="{{ $attachment->file_name }}">
                                                                        <i class="bi bi-download me-1"
                                                                            data-bs-toggle="tooltip"
                                                                            data-bs-placement="top"
                                                                            title="Download File"
                                                                            style="cursor: pointer;"></i>
                                                                    </a>
                                                                    @if ($this->canEditNotes())
                                                                        <i class="bi bi-trash me-1 text-danger"
                                                                            data-bs-toggle="tooltip"
                                                                            data-bs-placement="top"
                                                                            title="Delete File"
                                                                            wire:click="deleteNotesAttachment({{ $attachment->id }})"
                                                                            onclick="return confirm('Are you sure you want to delete this file?')"
                                                                            style="cursor: pointer;"></i>
                                                                    @endif
                                                                </div>
                                                            </div>
                                                        @endforeach
                                                    </div>
                                                @else
                                                    <div class="text-center py-4">
                                                        <i class="bi bi-file-earmark fs-1 text-muted mb-3 d-block"></i>
                                                        <h6 class="text-muted mb-2">No Files Attached</h6>
                                                        <p class="text-muted mb-0">This task doesn't have any file
                                                            attachments
                                                            yet.</p>
                                                    </div>
                                                @endif

                                                <!-- Initialize tooltips for attachments -->
                                                <div wire:init="initializeModalTooltips"></div>
                                            </div>
                                        </div>
                                    </div>
                                @else
                                    <div class="card border-0 shadow-sm mb-4">
                                        <div class="card-body text-center py-5">
                                            <i class="bi bi-sticky fs-1 text-muted mb-3 d-block"></i>
                                            <h5 class="text-muted mb-2">No Notes Available</h5>
                                            <p class="text-muted mb-0">This task doesn't have any notes yet.</p>
                                        </div>
                                    </div>
                                @endif



                                <!-- Comments Section -->
                                <div class="card border-0 shadow-sm">
                                    <div class="card-header bg-light border-0">
                                        <h6 class="mb-0 fw-semibold">
                                            <i class="bi bi-chat-dots me-2 text-primary"></i>Comments
                                            <span
                                                class="badge bg-primary ms-2">{{ $this->getTaskCommentsCount() }}</span>
                                        </h6>
                                    </div>
                                    <div class="card-body">
                                        <!-- Comments List -->
                                        <div class="comments-list mb-4" style="max-height: 400px; overflow-y: auto;">
                                            @foreach ($this->getTaskComments() as $comment)
                                                <div class="comment-item mb-4 p-3 border rounded-3 bg-light">
                                                    <div class="d-flex justify-content-between align-items-start mb-3">
                                                        <div class="d-flex align-items-center">
                                                            <div
                                                                class="avatar-sm bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-3">
                                                                {{ substr($comment->user->name, 0, 1) }}
                                                            </div>
                                                            <div>
                                                                <strong
                                                                    class="fs-6 fw-semibold">{{ $comment->user->name }}</strong>
                                                                <br>
                                                                <small class="text-muted">
                                                                    <i
                                                                        class="bi bi-clock me-1"></i>{{ $comment->created_at->format('M d, Y g:i A') }}
                                                                </small>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="comment-content"
                                                        style="line-height: 1.6; color: #495057;">
                                                        {{ $comment->comment }}
                                                    </div>

                                                    <!-- Comment Attachments -->
                                                    @if ($comment->attachments && $comment->attachments->count() > 0)
                                                        <div class="comment-attachments mt-3">
                                                            <small class="text-muted fw-semibold">
                                                                <i class="bi bi-paperclip me-1"></i>Attachments
                                                                ({{ $comment->attachments->count() }})
                                                            </small>
                                                            <div class="attachments-minimal mt-2">
                                                                @foreach ($comment->attachments as $attachment)
                                                                    @php
                                                                        $extension = strtolower(
                                                                            pathinfo(
                                                                                $attachment->file_name,
                                                                                PATHINFO_EXTENSION,
                                                                            ),
                                                                        );
                                                                        $iconClass = match ($extension) {
                                                                            'pdf' => 'bi-file-earmark-pdf',
                                                                            'doc', 'docx' => 'bi-file-earmark-word',
                                                                            'xls', 'xlsx' => 'bi-file-earmark-excel',
                                                                            'ppt', 'pptx' => 'bi-file-earmark-ppt',
                                                                            'jpg',
                                                                            'jpeg',
                                                                            'png',
                                                                            'gif'
                                                                                => 'bi-file-earmark-image',
                                                                            'mp4',
                                                                            'webm',
                                                                            'ogg',
                                                                            'avi',
                                                                            'mov',
                                                                            'wmv',
                                                                            'flv',
                                                                            'mkv'
                                                                                => 'bi-file-earmark-play',
                                                                            'zip', 'rar' => 'bi-file-earmark-zip',
                                                                            'txt' => 'bi-file-earmark-text',
                                                                            default => 'bi-file-earmark',
                                                                        };
                                                                        $isPreviewable = $this->isPreviewableFile(
                                                                            $extension,
                                                                        );
                                                                    @endphp

                                                                    <div class="attachment-row attachment-row-small d-flex justify-content-between align-items-center p-2"
                                                                        wire:key="comment-attachment-{{ $attachment->id }}">

                                                                        <div class="attachment-details">
                                                                            <div
                                                                                class="attachment-filename attachment-filename-small">
                                                                                <i class="bi {{ $iconClass }}"
                                                                                    data-bs-toggle="tooltip"
                                                                                    data-bs-placement="top"
                                                                                    title="{{ $attachment->file_name }}"></i>
                                                                                {{ $attachment->file_name }}
                                                                            </div>

                                                                        </div>
                                                                        <div class="attachment-buttons">
                                                                            @if ($isPreviewable)
                                                                                <i class="bi bi-eye me-1"
                                                                                    data-bs-toggle="tooltip"
                                                                                    data-bs-placement="top"
                                                                                    title="Click to View File"
                                                                                    wire:click="openFilePreview({{ $attachment->id }})"></i>
                                                                            @endif
                                                                            <a href="{{ route('attachments.download', $attachment->id) }}"
                                                                                class="btn-minimal btn-minimal-small"
                                                                                title="Download File"
                                                                                download="{{ $attachment->file_name }}">
                                                                                <i class="bi bi-download me-1"
                                                                                    data-bs-toggle="tooltip"
                                                                                    data-bs-placement="top"
                                                                                    title="Click to Download File"></i>
                                                                            </a>
                                                                        </div>
                                                                    </div>
                                                                @endforeach
                                                            </div>
                                                        </div>
                                                    @endif
                                                </div>
                                            @endforeach

                                            @if ($this->getTaskCommentsCount() == 0)
                                                <div class="text-center py-5">
                                                    <i class="bi bi-chat fs-1 text-muted mb-3 d-block"></i>
                                                    <h6 class="text-muted mb-2">No Comments Yet</h6>
                                                    <p class="text-muted mb-0">Be the first to add a comment!</p>
                                                </div>
                                            @endif
                                        </div>

                                        <!-- Add Comment Form -->
                                        <div class="add-comment-form">
                                            <div class="card border-0 bg-light">
                                                <div class="card-body">
                                                    <h6 class="fw-semibold mb-3">
                                                        <i class="bi bi-plus-circle me-2 text-primary"></i>Add Comment
                                                    </h6>
                                                    <div class="mb-3">
                                                        <label for="newComment" class="form-label fw-semibold">Your
                                                            Comment</label>
                                                        <textarea class="form-control border-0 shadow-sm" id="newComment" wire:model="newComment" rows="4"
                                                            placeholder="Write your comment here..." style="resize: vertical;"></textarea>
                                                    </div>

                                                    <!-- File Upload for Comments -->
                                                    <div class="mb-3">
                                                        <label class="form-label fw-semibold">
                                                            <i class="bi bi-paperclip me-1"></i>Attach Files (Optional)
                                                        </label>
                                                        <input type="file" class="form-control border-0 shadow-sm"
                                                            wire:model="commentAttachments" multiple
                                                            accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.txt,.jpg,.jpeg,.png,.gif,.zip,.rar,.mp4,.webm,.ogg,.avi,.mov,.wmv,.flv,.mkv">
                                                        <div class="form-text mt-2">
                                                            <i class="bi bi-info-circle me-1 text-info"></i>
                                                            <small>You can upload multiple files (max 10MB each)</small>
                                                        </div>
                                                        @error('commentAttachments.*')
                                                            <div class="text-danger small">{{ $message }}</div>
                                                        @enderror

                                                        <!-- Selected files preview -->
                                                        @if ($commentAttachments)
                                                            <div class="mt-3">
                                                                <h6 class="text-success mb-2 fw-semibold">
                                                                    <i class="bi bi-check-circle me-1"></i>Selected
                                                                    Files ({{ count($commentAttachments) }})
                                                                </h6>
                                                                <div class="list-group list-group-flush">
                                                                    @foreach ($commentAttachments as $file)
                                                                        <div
                                                                            class="list-group-item d-flex align-items-center border-0 px-0">
                                                                            <i
                                                                                class="bi bi-file-earmark text-success me-2"></i>
                                                                            <div class="flex-grow-1">
                                                                                <div class="fw-semibold">
                                                                                    {{ $file->getClientOriginalName() }}
                                                                                </div>
                                                                                <small
                                                                                    class="text-muted">{{ number_format($file->getSize() / 1024, 2) }}
                                                                                    KB</small>
                                                                            </div>
                                                                        </div>
                                                                    @endforeach
                                                                </div>
                                                            </div>
                                                        @endif
                                                    </div>

                                                    <button type="button" class="btn btn-primary btn-lg"
                                                        wire:click="addComment" wire:loading.attr="disabled">
                                                        <span wire:loading wire:target="addComment">
                                                            <span class="spinner-border spinner-border-sm me-2"></span>
                                                        </span>
                                                        <i class="bi bi-send me-2"></i>Add Comment
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endif
                    </div>
                    <div class="modal-footer bg-light border-0">
                        @if ($notesModalMode === 'commit')
                            <button type="button" class="btn btn-outline-secondary btn-lg"
                                wire:click="closeNotesModal">
                                <i class="bi bi-x-circle me-2"></i>Cancel
                            </button>
                            <button type="button" class="btn btn-success btn-lg" wire:click="commitNotes">
                                <i class="bi bi-check-circle me-2"></i>Save Notes
                            </button>
                        @else
                            <button type="button" class="btn btn-outline-secondary btn-lg"
                                wire:click="closeNotesModal">
                                <i class="bi bi-x-circle me-2"></i>Close
                            </button>
                            @if ($this->canEditNotes())
                                <button type="button" class="btn btn-primary btn-lg"
                                    wire:click="openNotesModal({{ $notesModalTaskId }}, 'commit')">
                                    <i class="bi bi-pencil-square me-2"></i>Edit Notes
                                </button>
                            @endif
                        @endif
                    </div>
                </div>
            </div>
        </div>
        <div class="modal-backdrop fade show"></div>

        <!-- Attach Files Modal (Inside Notes Modal) -->
        <div id="attachFileModal" class="modal fade" tabindex="-1" role="dialog" style="z-index: 1060;">
            <div class="modal-dialog modal-lg" role="document">
                <div class="modal-content shadow-lg border-0">
                    <div class="modal-header bg-gradient-primary text-white border-0">
                        <h5 class="modal-title fw-bold">
                            <i class="bi bi-paperclip me-2"></i>Attach Files to Task
                        </h5>
                        <button type="button" class="btn-close btn-close-white"
                            onclick="closeAttachFileModal()"></button>
                    </div>
                    <div class="modal-body">
                        @if (session()->has('message'))
                            <div class="alert alert-success alert-dismissible fade show" role="alert">
                                <i class="bi bi-check-circle me-2"></i>{{ session('message') }}
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        @endif

                        @if (session()->has('error'))
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <i class="bi bi-exclamation-triangle me-2"></i>{{ session('error') }}
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        @endif

                        <form wire:submit.prevent="attachFiles">
                            <div class="mb-4">
                                <label for="attachFiles" class="form-label fw-semibold">
                                    <i class="bi bi-cloud-upload me-1"></i>Select Files to Attach
                                </label>
                                <input type="file" class="form-control border-0 shadow-sm" id="attachFiles"
                                    wire:model="attachFiles" multiple
                                    accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.txt,.jpg,.jpeg,.png,.gif,.zip,.rar,.mp4,.webm,.ogg,.avi,.mov,.wmv,.flv,.mkv">
                                <div class="form-text mt-2">
                                    <i class="bi bi-info-circle me-1 text-info"></i>
                                    <small>You can select multiple files (max 10MB each)</small>
                                </div>
                                @error('attachFiles.*')
                                    <div class="text-danger small">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Selected files preview -->
                            @if ($attachFiles)
                                <div class="mb-4">
                                    <h6 class="text-success mb-3 fw-semibold">
                                        <i class="bi bi-check-circle me-1"></i>Selected Files
                                        ({{ count($attachFiles) }})
                                    </h6>
                                    <div class="list-group list-group-flush">
                                        @foreach ($attachFiles as $index => $file)
                                            <div class="list-group-item d-flex align-items-center border-0 px-0">
                                                <div class="file-icon-sm me-3">
                                                    <i class="bi bi-file-earmark text-primary"></i>
                                                </div>
                                                <div class="flex-grow-1">
                                                    <div class="fw-semibold">{{ $file->getClientOriginalName() }}
                                                    </div>
                                                    <small
                                                        class="text-muted">{{ number_format($file->getSize() / 1024, 2) }}
                                                        KB</small>
                                                </div>
                                                <button type="button" class="btn btn-sm btn-outline-danger"
                                                    wire:click="removeAttachFile({{ $index }})">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endif

                            <div class="modal-footer bg-light border-0">
                                <button type="button" class="btn btn-secondary btn-lg"
                                    onclick="closeAttachFileModal()">
                                    <i class="bi bi-x-circle me-1"></i>Close
                                </button>
                                <div class="text-muted">
                                    <small><i class="bi bi-info-circle me-1"></i>Files will be attached automatically
                                        when selected</small>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- Custom Status Creation Form -->
    @if ($showCustomStatusForm)
        <div class="modal fade show" style="display: block;" tabindex="-1" role="dialog">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">
                            <i class="bi bi-plus-circle me-2"></i>Add Custom Status
                        </h5>
                        <button type="button" class="btn-close" wire:click="resetCustomStatusForm"></button>
                    </div>
                    <div class="modal-body">
                        <form wire:submit.prevent="createCustomStatus">
                            <div class="mb-3">
                                <label for="customStatusName" class="form-label">Status Name</label>
                                <input type="text"
                                    class="form-control @error('customStatusName') is-invalid @enderror"
                                    id="customStatusName" wire:model="customStatusName"
                                    placeholder="Enter status name">
                                @error('customStatusName')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="mb-3">
                                <label for="customStatusColor" class="form-label">Color</label>
                                <select class="form-select @error('customStatusColor') is-invalid @enderror"
                                    id="customStatusColor" wire:model="customStatusColor">
                                    <option value="primary">Primary (Blue)</option>
                                    <option value="secondary">Secondary (Gray)</option>
                                    <option value="success">Success (Green)</option>
                                    <option value="danger">Danger (Red)</option>
                                    <option value="warning">Warning (Yellow)</option>
                                    <option value="info">Info (Cyan)</option>
                                    <option value="light">Light</option>
                                    <option value="dark">Dark</option>
                                </select>
                                @error('customStatusColor')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-success">
                                    <i class="bi bi-check-circle"></i> Create Status
                                </button>
                                <button type="button" wire:click="resetCustomStatusForm" class="btn btn-secondary">
                                    <i class="bi bi-x-circle"></i> Cancel
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        <div class="modal-backdrop fade show"></div>
    @endif

    <!-- Custom Priority Creation Form -->
    @if ($showCustomPriorityForm)
        <div class="modal fade show" style="display: block;" tabindex="-1" role="dialog">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">
                            <i class="bi bi-plus-circle me-2"></i>Add Custom Priority
                        </h5>
                        <button type="button" class="btn-close" wire:click="resetCustomPriorityForm"></button>
                    </div>
                    <div class="modal-body">
                        <form wire:submit.prevent="createCustomPriority">
                            <div class="mb-3">
                                <label for="customPriorityName" class="form-label">Priority Name</label>
                                <input type="text"
                                    class="form-control @error('customPriorityName') is-invalid @enderror"
                                    id="customPriorityName" wire:model="customPriorityName"
                                    placeholder="Enter priority name">
                                @error('customPriorityName')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="mb-3">
                                <label for="customPriorityColor" class="form-label">Color</label>
                                <select class="form-select @error('customPriorityColor') is-invalid @enderror"
                                    id="customPriorityColor" wire:model="customPriorityColor">
                                    <option value="primary">Primary (Blue)</option>
                                    <option value="secondary">Secondary (Gray)</option>
                                    <option value="success">Success (Green)</option>
                                    <option value="danger">Danger (Red)</option>
                                    <option value="warning">Warning (Yellow)</option>
                                    <option value="info">Info (Cyan)</option>
                                    <option value="light">Light</option>
                                    <option value="dark">Dark</option>
                                </select>
                                @error('customPriorityColor')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-success">
                                    <i class="bi bi-check-circle"></i> Create Priority
                                </button>
                                <button type="button" wire:click="resetCustomPriorityForm"
                                    class="btn btn-secondary">
                                    <i class="bi bi-x-circle"></i> Cancel
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        <div class="modal-backdrop fade show"></div>
    @endif

    <!-- Custom Category Creation Form -->
    @if ($showCustomCategoryForm)
        <div class="modal fade show" style="display: block;" tabindex="-1" role="dialog">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">
                            <i class="bi bi-plus-circle me-2"></i>Add Custom Category
                        </h5>
                        <button type="button" class="btn-close" wire:click="resetCustomCategoryForm"></button>
                    </div>
                    <div class="modal-body">
                        <form wire:submit.prevent="createCustomCategory">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="customCategoryName" class="form-label">Category Name</label>
                                        <input type="text"
                                            class="form-control @error('customCategoryName') is-invalid @enderror"
                                            id="customCategoryName" wire:model="customCategoryName"
                                            placeholder="Enter category name">
                                        @error('customCategoryName')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="customCategoryIcon" class="form-label">Icon</label>
                                        <select class="form-select @error('customCategoryIcon') is-invalid @enderror"
                                            id="customCategoryIcon" wire:model="customCategoryIcon">
                                            <option value="bi-list-task">List Task</option>
                                            <option value="bi-code-slash">Code</option>
                                            <option value="bi-palette">Design</option>
                                            <option value="bi-bug">Testing</option>
                                            <option value="bi-file-text">Documentation</option>
                                            <option value="bi-people">Meeting</option>
                                            <option value="bi-calendar">Calendar</option>
                                            <option value="bi-chat">Chat</option>
                                            <option value="bi-graph-up">Analytics</option>
                                            <option value="bi-gear">Settings</option>
                                        </select>
                                        @error('customCategoryIcon')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="customCategoryColor" class="form-label">Color</label>
                                <select class="form-select @error('customCategoryColor') is-invalid @enderror"
                                    id="customCategoryColor" wire:model="customCategoryColor">
                                    <option value="primary">Primary (Blue)</option>
                                    <option value="secondary">Secondary (Gray)</option>
                                    <option value="success">Success (Green)</option>
                                    <option value="danger">Danger (Red)</option>
                                    <option value="warning">Warning (Yellow)</option>
                                    <option value="info">Info (Cyan)</option>
                                    <option value="light">Light</option>
                                    <option value="dark">Dark</option>
                                </select>
                                @error('customCategoryColor')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-success">
                                    <i class="bi bi-check-circle"></i> Create Category
                                </button>
                                <button type="button" wire:click="resetCustomCategoryForm"
                                    class="btn btn-secondary">
                                    <i class="bi bi-x-circle"></i> Cancel
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        <div class="modal-backdrop fade show"></div>
    @endif

    <!-- File Preview Modal -->
    <div id="filePreviewModal" class="modal fade" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-xl" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="bi bi-eye me-2"></i>File Preview: <span id="previewFileName"></span>
                    </h5>
                    <button type="button" class="btn-close" onclick="closeFilePreviewModal()"></button>
                </div>
                <div class="modal-body">
                    <div class="file-preview-container" id="filePreviewContent">
                        <!-- Loading state -->
                        <div id="previewLoadingState" class="text-center py-5">
                            <div class="spinner-border text-primary mb-3" role="status"
                                style="width: 3rem; height: 3rem;">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                            <h5 class="text-muted">Loading file preview...</h5>
                            <p class="text-muted">Please wait while we prepare your file</p>
                        </div>
                        <!-- Content will be loaded here -->
                        <div id="previewContent"
                            style="display: none; filter: blur(2px); transition: filter 0.3s ease;">
                            <!-- Actual content will be loaded here -->
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeFilePreviewModal()">
                        <i class="bi bi-x-circle me-1"></i>Close
                    </button>
                    <a href="#" id="previewDownloadLink" class="btn btn-primary">
                        <i class="bi bi-download me-1"></i>Download
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Employee Selection Modal -->
    @if($showEmployeeModal)
        <div wire:ignore.self class="modal fade show d-block" tabindex="-1" style="background-color: rgba(0,0,0,0.5); z-index: 1060;">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title fw-bold">
                            <i class="bi bi-people me-2"></i>Select Assignees
                        </h5>
                        <button type="button" class="btn-close btn-close-white" wire:click="closeEmployeeModal"></button>
                    </div>
                    
                    <div class="modal-body">
                        <!-- Selected Employees Display -->
                        @if(count($selectedEmployeeNames) > 0)
                            <div class="mb-3">
                                <h6 class="fw-bold">Selected Assignees:</h6>
                                <div class="selected-employees">
                                    @foreach($selectedEmployeeNames as $index => $name)
                                        <span class="badge bg-primary me-1 mb-1">
                                            {{ $name }}
                                            @php
                                                $assigneeIds = $showTaskModal ? $modalTaskAssigneeIds : $newTaskAssigneeIds;
                                            @endphp
                                            @if(isset($assigneeIds[$index]))
                                                <button type="button" class="btn-close btn-close-white btn-sm ms-1" 
                                                        wire:click="removeEmployee({{ $assigneeIds[$index] }})"
                                                        style="font-size: 0.7em;"></button>
                                            @endif
                                        </span>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                        
                        <!-- Search Box -->
                        <div class="mb-3">
                            <div class="input-group">
                                <span class="input-group-text">
                                    <i class="bi bi-search"></i>
                                </span>
                                <input type="text" class="form-control" 
                                       wire:model="employeeSearch" 
                                       placeholder="Search employees by name...">
                            </div>
                        </div>
                        
                        <!-- Employee List -->
                        <div class="employee-list" style="max-height: 400px; overflow-y: auto;">
                            @if($this->filteredEmployees && $this->filteredEmployees->count() > 0)
                                <div class="row g-2">
                                    @foreach($this->filteredEmployees as $employee)
                                        <div class="col-md-6 col-lg-4">
                                            <div class="card employee-card h-100" 
                                                 style="cursor: pointer; transition: all 0.2s;"
                                                 wire:click="selectEmployee({{ $employee->id }})"
                                                 onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 4px 8px rgba(0,0,0,0.1)'"
                                                 onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='none'">
                                                <div class="card-body text-center p-3">
                                                    <div class="mb-2">
                                                        <i class="bi bi-person-circle text-primary" style="font-size: 2rem;"></i>
                                                    </div>
                                                    <h6 class="card-title mb-1">{{ $employee->name }}</h6>
                                                    <small class="text-muted">
                                                        @if($employee->role)
                                                            {{ ucfirst($employee->role->name) }}
                                                        @else
                                                            Employee
                                                        @endif
                                                    </small>
                                                    @if($employee->email)
                                                        <div class="mt-1">
                                                            <small class="text-muted">
                                                                <i class="bi bi-envelope me-1"></i>{{ $employee->email }}
                                                            </small>
                                                        </div>
                                                    @endif
                                                    @if(in_array($employee->id, $showTaskModal ? $modalTaskAssigneeIds : $newTaskAssigneeIds))
                                                        <div class="mt-2">
                                                            <span class="badge bg-success">
                                                                <i class="bi bi-check-circle me-1"></i>Selected
                                                            </span>
                                                        </div>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <div class="text-center py-4">
                                    <i class="bi bi-person-x text-muted" style="font-size: 3rem;"></i>
                                    <p class="text-muted mt-2 mb-0">
                                        @if($employeeSearch)
                                            No employees found matching "{{ $employeeSearch }}"
                                        @else
                                            No employees available
                                        @endif
                                    </p>
                                </div>
                            @endif
                        </div>
                    </div>
                    
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" wire:click="closeEmployeeModal">
                            <i class="bi bi-x-circle me-2"></i>Done
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- Task Creation Modal -->
    @if($showTaskModal)
        <div wire:ignore.self class="modal fade show d-block" tabindex="-1" style="background-color: rgba(0,0,0,0.5); z-index: 1055;">
            <div class="modal-dialog modal-xl">
                <div class="modal-content">
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title fw-bold">
                            <i class="bi bi-plus-circle me-2"></i>Create New Task
                        </h5>
                        <button type="button" class="btn-close btn-close-white" wire:click="closeTaskModal"></button>
                    </div>
                    
                    <!-- Flash Messages -->
                    @if (session()->has('success'))
                        <div class="alert alert-success alert-dismissible fade show m-3" role="alert">
                            <i class="bi bi-check-circle me-2"></i>{{ session('success') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    @if (session()->has('error'))
                        <div class="alert alert-danger alert-dismissible fade show m-3" role="alert">
                            <i class="bi bi-exclamation-triangle me-2"></i>{{ session('error') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif
                    
                    <div class="modal-body">
                        <form wire:submit="createTaskFromModal">
                            <div class="row">
                                <!-- Task Title -->
                                <div class="col-md-12 mb-3">
                                    <label for="modalTaskTitle" class="form-label">Task Title <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control @error('modalTaskTitle') is-invalid @enderror" 
                                           id="modalTaskTitle" wire:model="modalTaskTitle" required>
                                    @error('modalTaskTitle')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <!-- Project and Assignee -->
                                <div class="col-md-6 mb-3">
                                    <label for="modalTaskProjectId" class="form-label">Project</label>
                                    <div class="input-group">
                                        <select class="form-select @error('modalTaskProjectId') is-invalid @enderror" 
                                                id="modalTaskProjectId" wire:model="modalTaskProjectId">
                                            <option value="">Select a project (Optional)</option>
                                            @foreach($this->projects as $project)
                                                <option value="{{ $project->id }}">{{ $project->title }}</option>
                                            @endforeach
                                        </select>
                                        <button type="button" class="btn btn-outline-primary" 
                                                wire:click="openProjectCreateModal" 
                                                title="Add New Project">
                                            <i class="bi bi-plus-lg"></i>
                                        </button>
                                    </div>
                                    @error('modalTaskProjectId')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label for="modalTaskAssigneeId" class="form-label">Assign To <span class="text-danger">*</span></label>
                                    
                                    <!-- Selected Assignees Display -->
                                    @if(count($selectedEmployeeNames) > 0 && $showTaskModal)
                                        <div class="mb-2">
                                            <div class="selected-employees">
                                                @foreach($selectedEmployeeNames as $index => $name)
                                                    <span class="badge bg-primary me-1 mb-1">
                                                        {{ $name }}
                                                        @if(isset($modalTaskAssigneeIds[$index]))
                                                            <button type="button" class="btn-close btn-close-white btn-sm ms-1" 
                                                                    wire:click="removeEmployee({{ $modalTaskAssigneeIds[$index] }})"
                                                                    style="font-size: 0.7em;"></button>
                                                        @endif
                                                    </span>
                                                @endforeach
                                            </div>
                                        </div>
                                    @endif
                                    
                                    <div class="input-group">
                                        <input type="text" class="form-control @error('modalTaskAssigneeIds') is-invalid @enderror" 
                                               id="modalTaskAssigneeId" 
                                               placeholder="Click to select assignees" 
                                               readonly>
                                        <button type="button" class="btn btn-outline-primary" 
                                                wire:click="showEmployeeSelectionModal">
                                            <i class="bi bi-people me-1"></i>Select Assignees
                                        </button>
                                    </div>
                                    @error('modalTaskAssigneeIds')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <!-- Priority and Category -->
                                <div class="col-md-6 mb-3">
                                    <label for="modalTaskPriority" class="form-label">Priority <span class="text-danger">*</span></label>
                                    <select class="form-select @error('modalTaskPriority') is-invalid @enderror" 
                                            id="modalTaskPriority" wire:model="modalTaskPriority" required>
                                        <option value="">Select priority</option>
                                        @foreach($this->priorities as $priority)
                                            <option value="{{ $priority->id }}">{{ $priority->name }}</option>
                                        @endforeach
                                    </select>
                                    @error('modalTaskPriority')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label for="modalTaskCategory" class="form-label">Category <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <select class="form-select @error('modalTaskCategory') is-invalid @enderror" 
                                                id="modalTaskCategory" wire:model="modalTaskCategory" required>
                                            <option value="">Select category</option>
                                            @foreach($this->categories as $category)
                                                <option value="{{ $category->id }}">{{ $category->name }}</option>
                                            @endforeach
                                        </select>
                                        <button type="button" class="btn btn-outline-primary" 
                                                wire:click="openCategoryCreateModal" 
                                                title="Add New Category">
                                            <i class="bi bi-plus-lg"></i>
                                        </button>
                                    </div>
                                    @error('modalTaskCategory')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <!-- Due Date and Estimated Hours -->
                                <div class="col-md-6 mb-3">
                                    <label for="modalTaskDueDate" class="form-label">Due Date</label>
                                    <input type="date" class="form-control @error('modalTaskDueDate') is-invalid @enderror" 
                                           id="modalTaskDueDate" wire:model="modalTaskDueDate">
                                    @error('modalTaskDueDate')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label for="modalTaskEstimatedHours" class="form-label">Estimated Hours</label>
                                    <input type="number" class="form-control @error('modalTaskEstimatedHours') is-invalid @enderror" 
                                           id="modalTaskEstimatedHours" wire:model="modalTaskEstimatedHours" min="1">
                                    @error('modalTaskEstimatedHours')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <!-- Nature of Task -->
                                <div class="col-md-12 mb-3">
                                    <label for="modalTaskNature" class="form-label">Nature of Task <span class="text-danger">*</span></label>
                                    <select class="form-select @error('modalTaskNature') is-invalid @enderror" 
                                            id="modalTaskNature" wire:model="modalTaskNature" required>
                                        <option value="one_time">One Time</option>
                                        <option value="recurring">Recurring</option>
                                    </select>
                                    @error('modalTaskNature')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <!-- Recurrence Frequency (only show if Recurring is selected) -->
                                <div class="col-md-12 mb-3" 
                                     x-show="$wire.modalTaskNature === 'recurring'"
                                     x-transition>
                                    <label for="modalTaskRecurrenceFrequency" class="form-label">Recurrence Frequency <span class="text-danger">*</span></label>
                                    <select class="form-select @error('modalTaskRecurrenceFrequency') is-invalid @enderror" 
                                            id="modalTaskRecurrenceFrequency" wire:model="modalTaskRecurrenceFrequency" required>
                                        <option value="daily">Daily</option>
                                        <option value="weekly">Weekly</option>
                                        <option value="monthly">Monthly</option>
                                    </select>
                                    @error('modalTaskRecurrenceFrequency')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <!-- Reminder Time -->
                                <div class="col-md-12 mb-3">
                                    <label for="modalTaskReminderTime" class="form-label">Reminder Date & Time</label>
                                    <input type="time" class="form-control @error('modalTaskReminderTime') is-invalid @enderror" 
                                            id="modalTaskReminderTime" wire:model="modalTaskReminderTime">
                                    @error('modalTaskReminderTime')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <div class="form-text">Set a date and time to receive a reminder for this task</div>
                                </div>

                                <!-- Description -->
                                <div class="col-md-12 mb-3">
                                    <label for="modalTaskDescription" class="form-label">Description</label>
                                    <textarea class="form-control @error('modalTaskDescription') is-invalid @enderror" 
                                              id="modalTaskDescription" wire:model="modalTaskDescription" rows="3"></textarea>
                                    @error('modalTaskDescription')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <!-- Notes -->
                                {{-- <div class="col-md-12 mb-3">
                                    <label for="modalTaskNotes" class="form-label">Notes</label>
                                    <textarea class="form-control @error('modalTaskNotes') is-invalid @enderror" 
                                              id="modalTaskNotes" wire:model="modalTaskNotes" rows="2"></textarea>
                                    @error('modalTaskNotes')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div> --}}

                                <!-- Attachments -->
                                <div class="col-md-12 mb-3">
                                    <label for="modalTaskAttachments" class="form-label">Attachments</label>
                                    <input type="file" class="form-control @error('modalTaskAttachments.*') is-invalid @enderror" 
                                           id="modalTaskAttachments" wire:model="modalTaskAttachments" multiple>
                                    @error('modalTaskAttachments.*')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <div class="form-text">You can select multiple files. Maximum file size: 10MB per file.</div>
                                </div>
                            </div>
                        </form>
                    </div>
                    
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" wire:click="closeTaskModal">
                            <i class="bi bi-x-circle me-2"></i>Cancel
                        </button>
                        <button type="button" class="btn btn-primary" wire:click="createTaskFromModal">
                            <i class="bi bi-check-circle me-2"></i>Create Task
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- Project Creation Modal -->
    @if($showProjectCreateModal)
        <div wire:ignore.self class="modal fade show d-block" tabindex="-1" role="dialog" style="background-color: rgba(0,0,0,0.7); z-index: 1060; position: fixed; top: 0; left: 0; width: 100%; height: 100%; overflow: auto;">
            <div class="modal-dialog modal-dialog-centered modal-lg" style="z-index: 1061; position: relative;">
                <div class="modal-content" style="z-index: 1062;">
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title fw-bold">
                            <i class="bi bi-folder-plus me-2"></i>Create New Project
                        </h5>
                        <button type="button" class="btn-close btn-close-white" wire:click="closeProjectCreateModal"></button>
                    </div>
                    
                    <!-- Flash Messages -->
                    @if (session()->has('success'))
                        <div class="alert alert-success alert-dismissible fade show m-3" role="alert">
                            <i class="bi bi-check-circle me-2"></i>{{ session('success') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    @if (session()->has('error'))
                        <div class="alert alert-danger alert-dismissible fade show m-3" role="alert">
                            <i class="bi bi-exclamation-triangle me-2"></i>{{ session('error') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif
                    
                    <div class="modal-body">
                        <form wire:submit="createProjectFromModal">
                            <!-- Project Title -->
                            <div class="mb-3">
                                <label for="newProjectTitle" class="form-label">Project Title <span class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('newProjectTitle') is-invalid @enderror" 
                                       id="newProjectTitle" wire:model="newProjectTitle" required
                                       placeholder="Enter a descriptive project title...">
                                <div class="form-text">
                                    <i class="bi bi-lightbulb me-1"></i>
                                    Choose a clear, descriptive name that reflects your project's purpose
                                </div>
                                @error('newProjectTitle')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Project Description -->
                            <div class="mb-3">
                                <label for="newProjectDescription" class="form-label">Project Description <span class="text-danger">*</span></label>
                                <textarea class="form-control @error('newProjectDescription') is-invalid @enderror" 
                                          id="newProjectDescription" wire:model="newProjectDescription" 
                                          rows="5" required
                                          placeholder="Describe the project's purpose, goals, scope, deliverables, and key information (minimum 10 characters)..."></textarea>
                                <div class="form-text">
                                    <i class="bi bi-info-circle me-1"></i>
                                    Describe the project's purpose, goals, scope, deliverables, and key information (minimum 10 characters)
                                </div>
                                @error('newProjectDescription')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </form>
                    </div>
                    
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" wire:click="closeProjectCreateModal">
                            <i class="bi bi-x-circle me-2"></i>Cancel
                        </button>
                        <button type="button" class="btn btn-primary" wire:click="createProjectFromModal">
                            <i class="bi bi-check-circle me-2"></i>Create Project
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- Category Creation Modal -->
    @if($showCategoryCreateModal)
        <div wire:ignore.self class="modal fade show d-block" tabindex="-1" role="dialog" style="background-color: rgba(0,0,0,0.7); z-index: 1060; position: fixed; top: 0; left: 0; width: 100%; height: 100%; overflow: auto;">
            <div class="modal-dialog modal-dialog-centered modal-lg" style="z-index: 1061; position: relative;">
                <div class="modal-content" style="z-index: 1062;">
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title fw-bold">
                            <i class="bi bi-tag-plus me-2"></i>Create New Category
                        </h5>
                        <button type="button" class="btn-close btn-close-white" wire:click="closeCategoryCreateModal"></button>
                    </div>
                    
                    <!-- Flash Messages -->
                    @if (session()->has('success'))
                        <div class="alert alert-success alert-dismissible fade show m-3" role="alert">
                            <i class="bi bi-check-circle me-2"></i>{{ session('success') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    @if (session()->has('error'))
                        <div class="alert alert-danger alert-dismissible fade show m-3" role="alert">
                            <i class="bi bi-exclamation-triangle me-2"></i>{{ session('error') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif
                    
                    <div class="modal-body">
                        <form wire:submit="createCategoryFromModal">
                            <!-- Category Name -->
                            <div class="mb-3">
                                <label for="newCategoryTitle" class="form-label">Category Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('newCategoryTitle') is-invalid @enderror" 
                                       id="newCategoryTitle" wire:model.live="newCategoryTitle" required
                                       placeholder="Enter category name...">
                                <div class="form-text">
                                    <i class="bi bi-lightbulb me-1"></i>
                                    Choose a clear, descriptive name for the category
                                </div>
                                @error('newCategoryTitle')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Category Icon -->
                            <div class="mb-3">
                                <label for="newCategoryIcon" class="form-label">Icon <span class="text-danger">*</span></label>
                                <select class="form-select @error('newCategoryIcon') is-invalid @enderror" 
                                        id="newCategoryIcon" wire:model.live="newCategoryIcon" required>
                                    <option value="bi-list-task">List Task</option>
                                    <option value="bi-code-slash">Code</option>
                                    <option value="bi-palette">Palette</option>
                                    <option value="bi-bug">Bug</option>
                                    <option value="bi-file-text">Document</option>
                                    <option value="bi-people">People</option>
                                    <option value="bi-gear">Settings</option>
                                    <option value="bi-calendar">Calendar</option>
                                    <option value="bi-check-circle">Check</option>
                                    <option value="bi-clock">Clock</option>
                                    <option value="bi-folder">Folder</option>
                                    <option value="bi-chat-dots">Chat</option>
                                    <option value="bi-graph-up">Graph</option>
                                    <option value="bi-lightning">Lightning</option>
                                    <option value="bi-star">Star</option>
                                </select>
                                <div class="form-text">
                                    <i class="bi {{ $newCategoryIcon }} me-1"></i>
                                    Preview: <span class="badge bg-{{ $newCategoryColor }}"><i class="bi {{ $newCategoryIcon }} me-1"></i>{{ $newCategoryTitle ?: 'Category Name' }}</span>
                                </div>
                                @error('newCategoryIcon')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Category Color -->
                            <div class="mb-3">
                                <label for="newCategoryColor" class="form-label">Color <span class="text-danger">*</span></label>
                                <select class="form-select @error('newCategoryColor') is-invalid @enderror" 
                                        id="newCategoryColor" wire:model.live="newCategoryColor" required>
                                    <option value="primary">Primary (Blue)</option>
                                    <option value="secondary">Secondary (Gray)</option>
                                    <option value="success">Success (Green)</option>
                                    <option value="danger">Danger (Red)</option>
                                    <option value="warning">Warning (Yellow)</option>
                                    <option value="info">Info (Cyan)</option>
                                    <option value="dark">Dark (Black)</option>
                                </select>
                                <div class="form-text">
                                    <i class="bi bi-palette me-1"></i>
                                    Choose a color for the category badge
                                </div>
                                @error('newCategoryColor')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </form>
                    </div>
                    
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" wire:click="closeCategoryCreateModal">
                            <i class="bi bi-x-circle me-2"></i>Cancel
                        </button>
                        <button type="button" class="btn btn-primary" wire:click="createCategoryFromModal">
                            <i class="bi bi-check-circle me-2"></i>Create Category
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- Task Edit Modal -->
    @if($showEditModal)
        <div wire:ignore.self class="modal fade show d-block" tabindex="-1" style="background-color: rgba(0,0,0,0.5); z-index: 1055;">
            <div class="modal-dialog modal-xl">
                <div class="modal-content">
                    <div class="modal-header bg-warning text-dark">
                        <h5 class="modal-title fw-bold">
                            <i class="bi bi-pencil-circle me-2"></i>Edit Task
                        </h5>
                        <button type="button" class="btn-close" wire:click="closeEditModal"></button>
                    </div>
                    
                    <!-- Flash Messages -->
                    @if (session()->has('success'))
                        <div class="alert alert-success alert-dismissible fade show m-3" role="alert">
                            <i class="bi bi-check-circle me-2"></i>{{ session('success') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    @if (session()->has('error'))
                        <div class="alert alert-danger alert-dismissible fade show m-3" role="alert">
                            <i class="bi bi-exclamation-triangle me-2"></i>{{ session('error') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif
                    
                    <div class="modal-body">
                        <form wire:submit="updateTaskFromModal">
                            <div class="row">
                                <!-- Task Title -->
                                <div class="col-md-12 mb-3">
                                    <label for="editModalTaskTitle" class="form-label">Task Title <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control @error('editModalTaskTitle') is-invalid @enderror" 
                                           id="editModalTaskTitle" wire:model="editModalTaskTitle" required>
                                    @error('editModalTaskTitle')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <!-- Project and Assignee -->
                                <div class="col-md-6 mb-3">
                                    <label for="editModalTaskProjectId" class="form-label">Project</label>
                                    <select class="form-select @error('editModalTaskProjectId') is-invalid @enderror" 
                                            id="editModalTaskProjectId" wire:model="editModalTaskProjectId">
                                        <option value="">Select a project (Optional)</option>
                                        @foreach($this->projects as $project)
                                            <option value="{{ $project->id }}">{{ $project->title }}</option>
                                        @endforeach
                                    </select>
                                    @error('editModalTaskProjectId')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label for="editModalTaskAssigneeIds" class="form-label">Assign To <span class="text-danger">*</span></label>
                                    
                                    <!-- Selected Assignees Display -->
                                    @if(count($selectedEmployeeNames) > 0 && $showEditModal)
                                        <div class="mb-2">
                                            <div class="selected-employees">
                                                @foreach($selectedEmployeeNames as $index => $name)
                                                    <span class="badge bg-primary me-1 mb-1">
                                                        {{ $name }}
                                                        @if(isset($editModalTaskAssigneeIds[$index]))
                                                            <button type="button" class="btn-close btn-close-white btn-sm ms-1" 
                                                                    wire:click="removeEmployee({{ $editModalTaskAssigneeIds[$index] }})"
                                                                    style="font-size: 0.7em;"></button>
                                                        @endif
                                                    </span>
                                                @endforeach
                                            </div>
                                        </div>
                                    @endif
                                    
                                    <div class="input-group">
                                        <input type="text" class="form-control @error('editModalTaskAssigneeIds') is-invalid @enderror" 
                                               id="editModalTaskAssigneeId" 
                                               placeholder="Click to select assignees" 
                                               readonly>
                                        <button type="button" class="btn btn-outline-primary" 
                                                wire:click="showEmployeeSelectionModalForEdit">
                                            <i class="bi bi-people me-1"></i>Select Assignees
                                        </button>
                                    </div>
                                    @error('editModalTaskAssigneeIds')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <!-- Priority and Category -->
                                <div class="col-md-6 mb-3">
                                    <label for="editModalTaskPriority" class="form-label">Priority <span class="text-danger">*</span></label>
                                    <select class="form-select @error('editModalTaskPriority') is-invalid @enderror" 
                                            id="editModalTaskPriority" wire:model="editModalTaskPriority" required>
                                        <option value="">Select priority</option>
                                        @foreach($this->priorities as $priority)
                                            <option value="{{ $priority->id }}">{{ $priority->name }}</option>
                                        @endforeach
                                    </select>
                                    @error('editModalTaskPriority')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label for="editModalTaskCategory" class="form-label">Category <span class="text-danger">*</span></label>
                                    <select class="form-select @error('editModalTaskCategory') is-invalid @enderror" 
                                            id="editModalTaskCategory" wire:model="editModalTaskCategory" required>
                                        <option value="">Select category</option>
                                        @foreach($this->categories as $category)
                                            <option value="{{ $category->id }}">{{ $category->name }}</option>
                                        @endforeach
                                    </select>
                                    @error('editModalTaskCategory')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <!-- Due Date and Estimated Hours -->
                                <div class="col-md-6 mb-3">
                                    <label for="editModalTaskDueDate" class="form-label">Due Date</label>
                                    <input type="date" class="form-control @error('editModalTaskDueDate') is-invalid @enderror" 
                                           id="editModalTaskDueDate" wire:model="editModalTaskDueDate">
                                    @error('editModalTaskDueDate')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label for="editModalTaskEstimatedHours" class="form-label">Estimated Hours</label>
                                    <input type="number" class="form-control @error('editModalTaskEstimatedHours') is-invalid @enderror" 
                                           id="editModalTaskEstimatedHours" wire:model="editModalTaskEstimatedHours" min="0">
                                    @error('editModalTaskEstimatedHours')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <!-- Nature of Task -->
                                <div class="col-md-12 mb-3">
                                    <label for="editModalTaskNature" class="form-label">Nature of Task <span class="text-danger">*</span></label>
                                    <select class="form-select @error('editModalTaskNature') is-invalid @enderror" 
                                            id="editModalTaskNature" wire:model="editModalTaskNature" required>
                                        <option value="one_time">One Time</option>
                                        <option value="recurring">Recurring</option>
                                    </select>
                                    @error('editModalTaskNature')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <!-- Recurrence Frequency (only show if Recurring is selected) -->
                                <div class="col-md-12 mb-3" 
                                     x-show="$wire.editModalTaskNature === 'recurring'"
                                     x-transition>
                                    <label for="editModalTaskRecurrenceFrequency" class="form-label">Recurrence Frequency <span class="text-danger">*</span></label>
                                    <select class="form-select @error('editModalTaskRecurrenceFrequency') is-invalid @enderror" 
                                            id="editModalTaskRecurrenceFrequency" wire:model="editModalTaskRecurrenceFrequency" required>
                                        <option value="daily">Daily</option>
                                        <option value="weekly">Weekly</option>
                                        <option value="monthly">Monthly</option>
                                    </select>
                                    @error('editModalTaskRecurrenceFrequency')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <!-- Reminder Time -->
                                <div class="col-md-12 mb-3">
                                    <label for="editModalTaskReminderTime" class="form-label">Reminder Date & Time</label>
                                    <input type="datetime-local" class="form-control @error('editModalTaskReminderTime') is-invalid @enderror" 
                                            id="editModalTaskReminderTime" wire:model="editModalTaskReminderTime">
                                    @error('editModalTaskReminderTime')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <div class="form-text">Set a date and time to receive a reminder for this task</div>
                                </div>

                                <!-- Description -->
                                <div class="col-md-12 mb-3">
                                    <label for="editModalTaskDescription" class="form-label">Description</label>
                                    <textarea class="form-control @error('editModalTaskDescription') is-invalid @enderror" 
                                              id="editModalTaskDescription" wire:model="editModalTaskDescription" rows="3"></textarea>
                                    @error('editModalTaskDescription')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <!-- Attachments -->
                                <div class="col-md-12 mb-3">
                                    <label for="editModalTaskAttachments" class="form-label">Add New Attachments</label>
                                    <input type="file" class="form-control @error('editModalTaskAttachments.*') is-invalid @enderror" 
                                           id="editModalTaskAttachments" wire:model="editModalTaskAttachments" multiple>
                                    @error('editModalTaskAttachments.*')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <div class="form-text">You can select multiple files. Maximum file size: 10MB per file.</div>
                                </div>
                            </div>
                        </form>
                    </div>
                    
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" wire:click="closeEditModal">
                            <i class="bi bi-x-circle me-2"></i>Cancel
                        </button>
                        <button type="button" class="btn btn-warning" wire:click="updateTaskFromModal">
                            <i class="bi bi-save me-2"></i>Update Task
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <style>
        .btn-light{
            color:#007bff !important;
        }
        .employee-card {
            border: 1px solid #e9ecef;
            transition: all 0.2s ease;
        }
        
        .employee-card:hover {
            border-color: #0d6efd;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        
        .employee-card:active {
            transform: translateY(0);
        }
        
        .employee-list::-webkit-scrollbar {
            width: 6px;
        }
        
        .employee-list::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 3px;
        }
        
        .employee-list::-webkit-scrollbar-thumb {
            background: #c1c1c1;
            border-radius: 3px;
        }
        
        .employee-list::-webkit-scrollbar-thumb:hover {
            background: #a8a8a8;
        }
        
        /* Modal z-index fixes */
        .modal.show {
            z-index: 1055 !important;
        }
        
        .modal.show[style*="z-index: 1060"] {
            z-index: 1060 !important;
        }
        
        /* Compact Assignee Display */
        .assignees-compact {
            display: flex;
            align-items: center;
        }
        
        .assignee-item {
            flex-shrink: 0;
        }
        
        .additional-assignees {
            flex-shrink: 0;
        }
        
        .avatar-xs {
            width: 20px;
            height: 20px;
            font-size: 0.7rem;
        }
        
        .additional-assignees .btn {
            width: 32px;
            height: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 0;
        }
        
        .additional-assignees .badge {
            font-size: 0.6rem;
            padding: 0.2em 0.4em;
        }
        
        /* Popover styling */
        .popover {
            max-width: 300px;
        }
        
        .popover-body {
            padding: 0.75rem;
        }
        
        /* Dark theme support */
        [data-bs-theme="dark"] .employee-card {
            border-color: #495057;
            background-color: #2d2d2d;
        }
        
        [data-bs-theme="dark"] .employee-card:hover {
            border-color: #0d6efd;
        }
        
        [data-bs-theme="dark"] .employee-list::-webkit-scrollbar-track {
            background: #2d2d2d;
        }
        
        [data-bs-theme="dark"] .employee-list::-webkit-scrollbar-thumb {
            background: #495057;
        }
        
        [data-bs-theme="dark"] .employee-list::-webkit-scrollbar-thumb:hover {
            background: #6c757d;
        }
        
        /* Admin Review Modal Dark Theme Support */
        .admin-review-modal-header {
            background: linear-gradient(135deg, #0d6efd 0%, #6610f2 100%);
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            color: #ffffff;
        }
        
        [data-bs-theme="dark"] .admin-review-modal-content {
            background-color: var(--bg-secondary);
            border-color: var(--border-color);
            color: var(--text-primary);
        }
        
        [data-bs-theme="dark"] .admin-review-modal-content .modal-header {
            background: linear-gradient(135deg, #1e293b 0%, #334155 100%);
            border-bottom-color: var(--border-color);
            color: #ffffff;
        }
        
        .task-details-box {
            background-color: var(--bg-tertiary);
            border: 1px solid var(--border-color);
        }
        
        [data-bs-theme="dark"] .task-details-box {
            background-color: var(--bg-tertiary);
            border-color: var(--border-color);
        }
        
        .description-box {
            background-color: var(--bg-secondary);
            border-color: var(--border-color);
        }
        
        [data-bs-theme="dark"] .description-box {
            background-color: var(--bg-secondary);
            border-color: var(--border-color);
        }
        
        [data-bs-theme="dark"] .admin-review-textarea {
            background-color: var(--bg-tertiary);
            border-color: var(--border-color);
            color: var(--text-primary);
        }
        
        [data-bs-theme="dark"] .admin-review-textarea:focus {
            background-color: var(--bg-tertiary);
            border-color: #0d6efd;
            color: var(--text-primary);
            box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.25);
        }
        
        [data-bs-theme="dark"] .admin-review-textarea::placeholder {
            color: var(--text-muted);
        }
        
        [data-bs-theme="dark"] .admin-review-warning-btn {
            color: #000 !important;
        }
        
        [data-bs-theme="dark"] .text-muted {
            color: var(--text-muted) !important;
        }
        
        [data-bs-theme="dark"] .admin-review-modal-content .text-danger {
            color: #f56565 !important;
        }
    </style>

    <!-- Task Clone Modal -->
    @if($showCloneModal)
        <div wire:ignore.self class="modal fade show d-block" tabindex="-1" style="background-color: rgba(0,0,0,0.5); z-index: 1055;">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header bg-info text-white">
                        <h5 class="modal-title fw-bold">
                            <i class="bi bi-files me-2"></i>Clone Task
                        </h5>
                        <button type="button" class="btn-close btn-close-white" wire:click="closeCloneModal"></button>
                    </div>
                    
                    <div class="modal-body">
                        <form wire:submit="cloneTask">
                            @if (session()->has('success'))
                                <div class="alert alert-success alert-dismissible fade show" role="alert">
                                    <i class="bi bi-check-circle me-2"></i>{{ session('success') }}
                                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                                </div>
                            @endif

                            @if (session()->has('error'))
                                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                    <i class="bi bi-exclamation-triangle me-2"></i>{{ session('error') }}
                                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                                </div>
                            @endif

                            <div class="alert alert-info">
                                <i class="bi bi-info-circle me-2"></i>
                                Please select a due date for the cloned task.
                            </div>

                            <div class="mb-3">
                                <label for="cloneModalDueDate" class="form-label">
                                    <strong>New Due Date <span class="text-danger">*</span></strong>
                                </label>
                                <input type="date" 
                                       class="form-control @error('cloneModalDueDate') is-invalid @enderror" 
                                       id="cloneModalDueDate" 
                                       wire:model="cloneModalDueDate"
                                       min="{{ date('Y-m-d', strtotime('+1 day')) }}"
                                       required>
                                @error('cloneModalDueDate')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <div class="form-text text-muted">
                                    <i class="bi bi-calendar me-1"></i>Select a date after today
                                </div>
                            </div>

                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-info text-white">
                                    <i class="bi bi-files me-2"></i>Clone Task
                                </button>
                                <button type="button" class="btn btn-secondary" wire:click="closeCloneModal">
                                    <i class="bi bi-x-circle me-2"></i>Cancel
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>

<script>
    // Global Select2 initialization function
    window.initializeSelect2 = function() {
       

        // Find all assignee select elements
        const assigneeSelects = document.querySelectorAll('.assignee-select');
        // console.log('Found assignee selects:', assigneeSelects.length);

        assigneeSelects.forEach(function(select) {
            // Check if Select2 is already initialized
            if (select.hasAttribute('data-select2-id')) {
                console.log('Select2 already initialized, destroying...');
                $(select).select2('destroy');
            }

            // Initialize Select2
            $(select).select2({
                theme: 'bootstrap-5',
                placeholder: 'Select assignees...',
                allowClear: true,
                width: '100%',
                dropdownParent: $('body')
            });

            console.log('Select2 initialized for:', select);

            // Handle change events
            $(select).off('change.select2').on('change.select2', function(e) {
                const data = $(this).val();
                console.log('Select2 changed:', data);
                @this.set('newTaskAssigneeIds', data);
            });
        });
    };

    // Initialize Bootstrap tooltips
    function initializeTooltips() {
        // Destroy existing tooltips
        const existingTooltips = document.querySelectorAll('[data-bs-toggle="tooltip"]');
        existingTooltips.forEach(tooltip => {
            const bsTooltip = bootstrap.Tooltip.getInstance(tooltip);
            if (bsTooltip) {
                bsTooltip.dispose();
            }
        });

        // Initialize new tooltips
        const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        tooltipTriggerList.map(function(tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });

        // console.log('Initialized tooltips for', tooltipTriggerList.length, 'elements');
    }

    // Initialize tooltips specifically for modal content
    function initializeModalTooltips() {
        // Wait for modal to be fully rendered
        setTimeout(() => {
            const modalTooltips = document.querySelectorAll('.modal [data-bs-toggle="tooltip"]');
            modalTooltips.forEach(tooltip => {
                const bsTooltip = bootstrap.Tooltip.getInstance(tooltip);
                if (bsTooltip) {
                    bsTooltip.dispose();
                }
            });

            modalTooltips.forEach(tooltip => {
                new bootstrap.Tooltip(tooltip);
            });

            // console.log('Initialized modal tooltips for', modalTooltips.length, 'elements');
        }, 100);
    }

    // Initialize tooltips for a specific attachment card
    function initializeAttachmentTooltips(cardElement) {
        const tooltips = cardElement.querySelectorAll('[data-bs-toggle="tooltip"]');
        tooltips.forEach(tooltip => {
            const bsTooltip = bootstrap.Tooltip.getInstance(tooltip);
            if (!bsTooltip) {
                new bootstrap.Tooltip(tooltip);
            }
        });
    }

    // Initialize when Livewire is ready
    document.addEventListener('livewire:init', () => {
        // console.log('Livewire initialized');

        // Initialize popovers
        function initializePopovers() {
            var popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
            var popoverList = popoverTriggerList.map(function (popoverTriggerEl) {
                return new bootstrap.Popover(popoverTriggerEl);
            });
        }
        
        // Initial setup
        setTimeout(window.initializeSelect2, 200);
        initializeTooltips();
        initializePopovers();

        // Re-initialize after updates
        Livewire.on('task-updated', () => {
            // console.log('Task updated, re-initializing Select2');
            setTimeout(window.initializeSelect2, 300);
            setTimeout(initializeTooltips, 300);
            setTimeout(initializePopovers, 300);
        });

        Livewire.on('task-created', () => {
            // console.log('Task created, re-initializing Select2');
            setTimeout(window.initializeSelect2, 300);
            setTimeout(initializeTooltips, 300);
            setTimeout(initializePopovers, 300);
        });

        Livewire.on('refresh-select2', () => {
            // console.log('Refresh Select2 event received');
            setTimeout(window.initializeSelect2, 100);
            setTimeout(initializeTooltips, 100);
            setTimeout(initializePopovers, 100);
        });

        // Listen for project creation to refresh projects list
        Livewire.on('project-created', () => {
            // Force Livewire to re-render and update the projects dropdown
            // The projects property will automatically refresh on next render
            setTimeout(() => {
                // Refresh the project select dropdown after Livewire updates
                const projectSelect = document.getElementById('modalTaskProjectId');
                if (projectSelect) {
                    // Trigger change event to ensure Livewire binding is updated
                    projectSelect.dispatchEvent(new Event('change', { bubbles: true }));
                }
            }, 100);
        });

        // Listen for category creation to refresh categories list
        Livewire.on('category-created', () => {
            // Force Livewire to re-render and update the categories dropdown
            // The categories property will automatically refresh on next render
            setTimeout(() => {
                // Refresh the category select dropdown after Livewire updates
                const categorySelect = document.getElementById('modalTaskCategory');
                if (categorySelect) {
                    // Trigger change event to ensure Livewire binding is updated
                    categorySelect.dispatchEvent(new Event('change', { bubbles: true }));
                }
            }, 100);
        });

        // Handle morph updates
        Livewire.hook('morph.updated', ({
            component,
            el
        }) => {
            // console.log('Morph updated, checking for Select2');
            setTimeout(window.initializeSelect2, 200);
            setTimeout(initializeTooltips, 200);
            
            // Fix admin review modal positioning
            setTimeout(() => {
                const modal = document.querySelector('.modal.show');
                if (modal && modal.querySelector('.modal-title') && modal.querySelector('.modal-title').textContent.includes('Review Completed Task')) {
                    modal.style.position = 'fixed';
                    modal.style.top = '0';
                    modal.style.left = '0';
                    modal.style.zIndex = '1055';
                    modal.style.width = '100%';
                    modal.style.height = '100%';
                    modal.style.display = 'flex';
                    modal.style.alignItems = 'center';
                    modal.style.justifyContent = 'center';
                    modal.style.backgroundColor = 'rgba(0,0,0,0.5)';
                    
                    const modalDialog = modal.querySelector('.modal-dialog');
                    if (modalDialog) {
                        modalDialog.style.zIndex = '1056';
                        modalDialog.style.position = 'relative';
                    }
                }
            }, 50);
        });

        // Handle notes modal opening
        Livewire.on('notes-modal-opened', () => {
            // console.log('Notes modal opened, initializing tooltips');
            initializeModalTooltips();
        });

        // Handle modal tooltip initialization
        Livewire.on('initialize-modal-tooltips', () => {
            // console.log('Initializing modal tooltips');
            initializeModalTooltips();
        });

        // Hook into Livewire updates to initialize tooltips when modal content changes
        Livewire.hook('morph.updated', ({
            component,
            el
        }) => {
            // Check if this is the notes modal
            if (el.querySelector('.attachments-grid')) {
                // console.log('Notes modal content updated, initializing tooltips');
                setTimeout(initializeModalTooltips, 100);
            }
        });
    });

    // Global task selection variable
    window.selectedTasks = new Set();

    // Fallback initialization
    $(document).ready(function() {
        console.log('Document ready, initializing Select2 fallback');
        setTimeout(window.initializeSelect2, 1000);
    });

    // Task Selection Functions

    // Initialize task selection functionality
    function initializeTaskSelection() {
        console.log('Initializing task selection functionality...');

        // Ensure the select all checkbox exists
        const selectAllCheckbox = document.getElementById('selectAllTasks');
        if (!selectAllCheckbox) {
            console.error('Select all checkbox not found!');
            return;
        }

        // Ensure task checkboxes exist
        const taskCheckboxes = document.querySelectorAll('.task-checkbox');
        console.log('Found', taskCheckboxes.length, 'task checkboxes');

        // Initialize bulk actions
        updateBulkActions();

        console.log('Task selection functionality initialized successfully');
    }

    // Initialize when DOM is ready
    document.addEventListener('DOMContentLoaded', function() {
        initializeTaskSelection();
    });

    // Also initialize after Livewire updates
    document.addEventListener('livewire:navigated', function() {
        initializeTaskSelection();
    });

    function toggleAllTasks(selectAllCheckbox) {
        console.log('Toggle all tasks called, checked:', selectAllCheckbox.checked);

        if (!selectAllCheckbox) {
            console.error('Select all checkbox is null or undefined');
            return;
        }

        const taskCheckboxes = document.querySelectorAll('.task-checkbox:not([disabled])');
        console.log('Found task checkboxes:', taskCheckboxes.length);

        if (taskCheckboxes.length === 0) {
            console.warn('No task checkboxes found');
            return;
        }

        const isChecked = selectAllCheckbox.checked;

        // Clear current selection
        window.selectedTasks.clear();

        taskCheckboxes.forEach(checkbox => {
            checkbox.checked = isChecked;
            if (isChecked) {
                window.selectedTasks.add(checkbox.value);
            }
        });

        console.log('Selected tasks after toggle all:', Array.from(window.selectedTasks));
        updateBulkActions();
    }

    function toggleTaskSelection(checkbox) {
        console.log('Toggle task selection:', checkbox.value, 'checked:', checkbox.checked);

        if (checkbox.checked) {
            window.selectedTasks.add(checkbox.value);
        } else {
            window.selectedTasks.delete(checkbox.value);
        }

        console.log('Selected tasks after individual toggle:', Array.from(window.selectedTasks));
        updateSelectAllCheckbox();
        updateBulkActions();
    }

    function updateSelectAllCheckbox() {
        const selectAllCheckbox = document.getElementById('selectAllTasks');
        const taskCheckboxes = document.querySelectorAll('.task-checkbox:not([disabled])');
        const checkedCount = document.querySelectorAll('.task-checkbox:not([disabled]):checked').length;

        console.log('Update select all - total:', taskCheckboxes.length, 'checked:', checkedCount);

        if (checkedCount === 0) {
            selectAllCheckbox.checked = false;
            selectAllCheckbox.indeterminate = false;
        } else if (checkedCount === taskCheckboxes.length) {
            selectAllCheckbox.checked = true;
            selectAllCheckbox.indeterminate = false;
        } else {
            selectAllCheckbox.checked = false;
            selectAllCheckbox.indeterminate = true;
        }
    }

    function updateBulkActions() {
        const bulkActions = document.getElementById('bulkActions');
        const selectedCount = document.getElementById('selectedCount');

        console.log('Update bulk actions - selected count:', window.selectedTasks.size);

        if (!bulkActions) {
            console.error('Bulk actions element not found!');
            return;
        }

        if (!selectedCount) {
            console.error('Selected count element not found!');
            return;
        }

        if (window.selectedTasks.size > 0) {
            bulkActions.style.display = 'block';
            selectedCount.textContent =
                `${window.selectedTasks.size} task${window.selectedTasks.size > 1 ? 's' : ''} selected`;
        } else {
            bulkActions.style.display = 'none';
        }
    }

    function clearSelection() {
        console.log('Clearing selection');
        window.selectedTasks.clear();
        document.querySelectorAll('.task-checkbox').forEach(checkbox => {
            checkbox.checked = false;
        });
        const selectAllCheckbox = document.getElementById('selectAllTasks');
        if (selectAllCheckbox) {
            selectAllCheckbox.checked = false;
            selectAllCheckbox.indeterminate = false;
        }
        updateBulkActions();
    }

    function bulkUpdateStatus(statusId, statusName) {
        if (window.selectedTasks.size === 0) {
            Swal.fire({
                icon: 'warning',
                title: 'No Tasks Selected',
                text: 'Please select tasks first before updating their status.',
                confirmButtonColor: '#3085d6'
            });
            return;
        }

        const taskCount = window.selectedTasks.size;
        const taskIds = Array.from(window.selectedTasks);

        console.log('Bulk updating status for tasks:', taskIds, 'to status:', statusId, statusName);

        // Show SweetAlert confirmation dialog
        Swal.fire({
            title: 'Update Status',
            html: `Are you sure you want to update <strong>${taskCount}</strong> task${taskCount > 1 ? 's' : ''} to status <strong>${statusName}</strong>?`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Yes, update them!',
            cancelButtonText: 'Cancel',
            allowOutsideClick: false,
            allowEscapeKey: false
        }).then((result) => {
            if (result.isConfirmed) {
                // Show loader immediately
                Swal.fire({
                    title: 'Processing...',
                    html: `Updating ${taskCount} task${taskCount > 1 ? 's' : ''} status to <strong>${statusName}</strong>`,
                    icon: 'info',
                    allowOutsideClick: false,
                    allowEscapeKey: false,
                    showConfirmButton: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });

                // Call Livewire method and wait for completion
                @this.call('bulkUpdateStatus', taskIds, statusId)
                    .then(() => {
                        // Wait a bit for Livewire to finish processing
                        setTimeout(() => {
                            clearSelection();
                            
                            // Show success message
                            Swal.fire({
                                icon: 'success',
                                title: 'Status Updated!',
                                text: `Successfully updated ${taskCount} task${taskCount > 1 ? 's' : ''} to status: ${statusName}`,
                                confirmButtonColor: '#10b981',
                                timer: 3000,
                                timerProgressBar: true,
                                allowOutsideClick: false
                            });
                        }, 300);
                    })
                    .catch((error) => {
                        Swal.fire({
                            icon: 'error',
                            title: 'Update Failed',
                            text: 'Error: ' + (error.message || 'An error occurred while updating tasks'),
                            confirmButtonColor: '#d33'
                        });
                    });
            }
        });
    }

    function bulkUpdatePriority(priorityId, priorityName) {
        if (window.selectedTasks.size === 0) {
            Swal.fire({
                icon: 'warning',
                title: 'No Tasks Selected',
                text: 'Please select tasks first before updating their priority.',
                confirmButtonColor: '#3085d6'
            });
            return;
        }

        const taskCount = window.selectedTasks.size;
        const taskIds = Array.from(window.selectedTasks);

        console.log('Bulk updating priority for tasks:', taskIds, 'to priority:', priorityId, priorityName);

        // Show SweetAlert confirmation dialog
        Swal.fire({
            title: 'Update Priority',
            html: `Are you sure you want to update <strong>${taskCount}</strong> task${taskCount > 1 ? 's' : ''} to priority <strong>${priorityName}</strong>?`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Yes, update them!',
            cancelButtonText: 'Cancel',
            allowOutsideClick: false,
            allowEscapeKey: false
        }).then((result) => {
            if (result.isConfirmed) {
                // Show loader immediately
                Swal.fire({
                    title: 'Processing...',
                    html: `Updating ${taskCount} task${taskCount > 1 ? 's' : ''} priority to <strong>${priorityName}</strong>`,
                    icon: 'info',
                    allowOutsideClick: false,
                    allowEscapeKey: false,
                    showConfirmButton: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });

                // Call Livewire method and wait for completion
                @this.call('bulkUpdatePriority', taskIds, priorityId)
                    .then(() => {
                        // Wait a bit for Livewire to finish processing
                        setTimeout(() => {
                            clearSelection();
                            
                            // Show success message
                            Swal.fire({
                                icon: 'success',
                                title: 'Priority Updated!',
                                text: `Successfully updated ${taskCount} task${taskCount > 1 ? 's' : ''} to priority: ${priorityName}`,
                                confirmButtonColor: '#10b981',
                                timer: 3000,
                                timerProgressBar: true,
                                allowOutsideClick: false
                            });
                        }, 300);
                    })
                    .catch((error) => {
                        Swal.fire({
                            icon: 'error',
                            title: 'Update Failed',
                            text: 'Error: ' + (error.message || 'An error occurred while updating tasks'),
                            confirmButtonColor: '#d33'
                        });
                    });
            }
        });
    }

    function bulkUpdateAssignee(userId, userName) {
        if (window.selectedTasks.size === 0) {
            Swal.fire({
                icon: 'warning',
                title: 'No Tasks Selected',
                text: 'Please select tasks first before assigning them.',
                confirmButtonColor: '#3085d6'
            });
            return;
        }

        const taskCount = window.selectedTasks.size;
        const taskIds = Array.from(window.selectedTasks);

        console.log('Bulk updating assignee for tasks:', taskIds, 'to user:', userId, userName);

        // Show SweetAlert confirmation dialog
        Swal.fire({
            title: 'Assign Tasks',
            html: `Are you sure you want to assign <strong>${taskCount}</strong> task${taskCount > 1 ? 's' : ''} to <strong>${userName}</strong>?`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Yes, assign them!',
            cancelButtonText: 'Cancel',
            allowOutsideClick: false,
            allowEscapeKey: false
        }).then((result) => {
            if (result.isConfirmed) {
                // Show loader immediately
                Swal.fire({
                    title: 'Processing...',
                    html: `Assigning ${taskCount} task${taskCount > 1 ? 's' : ''} to <strong>${userName}</strong>`,
                    icon: 'info',
                    allowOutsideClick: false,
                    allowEscapeKey: false,
                    showConfirmButton: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });

                // Call Livewire method and wait for completion
                @this.call('bulkUpdateAssignee', taskIds, userId)
                    .then(() => {
                        // Wait a bit for Livewire to finish processing
                        setTimeout(() => {
                            clearSelection();
                            
                            // Show success message
                            Swal.fire({
                                icon: 'success',
                                title: 'Tasks Assigned!',
                                text: `Successfully assigned ${taskCount} task${taskCount > 1 ? 's' : ''} to ${userName}`,
                                confirmButtonColor: '#10b981',
                                timer: 3000,
                                timerProgressBar: true,
                                allowOutsideClick: false
                            });
                        }, 300);
                    })
                    .catch((error) => {
                        Swal.fire({
                            icon: 'error',
                            title: 'Assignment Failed',
                            text: 'Error: ' + (error.message || 'An error occurred while assigning tasks'),
                            confirmButtonColor: '#d33'
                        });
                    });
            }
        });
    }

    function bulkUpdateNature(nature, natureName) {
        if (window.selectedTasks.size === 0) {
            Swal.fire({
                icon: 'warning',
                title: 'No Tasks Selected',
                text: 'Please select tasks first before updating their nature.',
                confirmButtonColor: '#3085d6'
            });
            return;
        }

        const taskCount = window.selectedTasks.size;
        const taskIds = Array.from(window.selectedTasks);

        console.log('Bulk updating nature for tasks:', taskIds, 'to nature:', nature, natureName);

        // Show SweetAlert confirmation dialog
        Swal.fire({
            title: 'Update Nature',
            html: `Are you sure you want to update <strong>${taskCount}</strong> task${taskCount > 1 ? 's' : ''} to nature <strong>${natureName}</strong>?`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Yes, update them!',
            cancelButtonText: 'Cancel',
            allowOutsideClick: false,
            allowEscapeKey: false
        }).then((result) => {
            if (result.isConfirmed) {
                // Show loader immediately
                Swal.fire({
                    title: 'Processing...',
                    html: `Updating ${taskCount} task${taskCount > 1 ? 's' : ''} nature to <strong>${natureName}</strong>`,
                    icon: 'info',
                    allowOutsideClick: false,
                    allowEscapeKey: false,
                    showConfirmButton: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });

                // Call Livewire method and wait for completion
                @this.call('bulkUpdateNature', taskIds, nature)
                    .then(() => {
                        // Wait a bit for Livewire to finish processing
                        setTimeout(() => {
                            clearSelection();
                            
                            // Show success message
                            Swal.fire({
                                icon: 'success',
                                title: 'Nature Updated!',
                                text: `Successfully updated ${taskCount} task${taskCount > 1 ? 's' : ''} to nature: ${natureName}`,
                                confirmButtonColor: '#10b981',
                                timer: 3000,
                                timerProgressBar: true,
                                allowOutsideClick: false
                            });
                        }, 300);
                    })
                    .catch((error) => {
                        Swal.fire({
                            icon: 'error',
                            title: 'Update Failed',
                            text: 'Error: ' + (error.message || 'An error occurred while updating tasks'),
                            confirmButtonColor: '#d33'
                        });
                    });
            }
        });
    }

    function bulkDeleteTasks() {
        if (window.selectedTasks.size === 0) {
            Swal.fire({
                icon: 'warning',
                title: 'No Tasks Selected',
                text: 'Please select tasks first before deleting them.',
                confirmButtonColor: '#3085d6'
            });
            return;
        }

        const taskCount = window.selectedTasks.size;
        const taskIds = Array.from(window.selectedTasks);

        // Show SweetAlert confirmation dialog
        Swal.fire({
            title: 'Delete Tasks',
            html: `Are you sure you want to delete <strong>${taskCount}</strong> selected task${taskCount > 1 ? 's' : ''}?<br><br><span class="text-danger"><strong>This action cannot be undone!</strong></span>`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Yes, delete them!',
            cancelButtonText: 'Cancel',
            allowOutsideClick: false,
            allowEscapeKey: false
        }).then((result) => {
            if (result.isConfirmed) {
                // Show loader immediately
                Swal.fire({
                    title: 'Processing...',
                    html: `Deleting ${taskCount} task${taskCount > 1 ? 's' : ''}...`,
                    icon: 'info',
                    allowOutsideClick: false,
                    allowEscapeKey: false,
                    showConfirmButton: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });

                // Call Livewire method and wait for completion
                @this.call('bulkDeleteTasks', taskIds)
                    .then(() => {
                        // Wait a bit for Livewire to finish processing
                        setTimeout(() => {
                            clearSelection();
                            
                            // Show success message
                            Swal.fire({
                                icon: 'success',
                                title: 'Tasks Deleted!',
                                text: `Successfully deleted ${taskCount} task${taskCount > 1 ? 's' : ''}`,
                                confirmButtonColor: '#10b981',
                                timer: 3000,
                                timerProgressBar: true,
                                allowOutsideClick: false
                            });
                        }, 300);
                    })
                    .catch((error) => {
                        Swal.fire({
                            icon: 'error',
                            title: 'Deletion Failed',
                            text: 'Error: ' + (error.message || 'An error occurred while deleting tasks'),
                            confirmButtonColor: '#d33'
                        });
                    });
            }
        });
    }

    // Modal control functions
    function openAttachFileModal() {
        const modal = document.getElementById('attachFileModal');
        if (modal) {
            // Show modal using Bootstrap
            const bsModal = new bootstrap.Modal(modal);
            bsModal.show();
        }
    }

    function closeAttachFileModal() {
        const modal = document.getElementById('attachFileModal');
        if (modal) {
            // Hide modal using Bootstrap
            const bsModal = bootstrap.Modal.getInstance(modal);
            if (bsModal) {
                bsModal.hide();
            }
        }
    }

    function openFilePreviewModal(attachmentId, fileName) {
        console.log('Opening preview for:', attachmentId, fileName);

        // Set the file name in the modal title
        document.getElementById('previewFileName').textContent = fileName;

        // Set the download link
        document.getElementById('previewDownloadLink').href = `/attachments/${attachmentId}/download`;

        // Show loading state
        showPreviewLoadingState();

        // Show the modal
        const modal = document.getElementById('filePreviewModal');
        if (modal) {
            const bsModal = new bootstrap.Modal(modal);
            bsModal.show();
        }

        // Load content directly without Livewire
        setTimeout(() => {
            populateFilePreviewContent(attachmentId);
        }, 100);
    }

    function closeFilePreviewModal() {
        console.log('Closing preview modal');
        const modal = document.getElementById('filePreviewModal');
        if (modal) {
            const bsModal = bootstrap.Modal.getInstance(modal);
            if (bsModal) {
                bsModal.hide();
            }
        }
        // Reset loading state for next time
        showPreviewLoadingState();
    }

    function showPreviewLoadingState() {
        const loadingState = document.getElementById('previewLoadingState');
        const contentDiv = document.getElementById('previewContent');

        if (loadingState) {
            loadingState.style.display = 'block';
        }
        if (contentDiv) {
            contentDiv.style.display = 'none';
        }
    }

    function hidePreviewLoadingState() {
        const loadingState = document.getElementById('previewLoadingState');
        const contentDiv = document.getElementById('previewContent');

        if (loadingState) {
            loadingState.style.display = 'none';
        }
        if (contentDiv) {
            contentDiv.style.display = 'block';
            // Remove blur effect with smooth transition
            setTimeout(() => {
                contentDiv.style.filter = 'blur(0px)';
            }, 100);
        }
    }

    function showPreviewErrorState() {
        const contentDiv = document.getElementById('filePreviewContent');
        contentDiv.innerHTML = `
            <div class="text-center py-5">
                <i class="bi bi-exclamation-triangle text-warning mb-3" style="font-size: 3rem;"></i>
                <h5 class="text-muted">Error Loading Preview</h5>
                <p class="text-muted">Unable to load file preview. Please try again.</p>
                <button class="btn btn-primary" onclick="location.reload()">
                    <i class="bi bi-arrow-clockwise me-1"></i>Retry
                </button>
            </div>
        `;
    }

    function populateFilePreviewContent(attachmentId) {
        console.log('Fetching data for attachment ID:', attachmentId);

        // First, let's test if the API endpoint exists
        console.log('Testing API endpoint...');

        // Simple direct fetch without test endpoint
        fetch(`/attachments/${attachmentId}/data`)
            .then(response => {
                console.log('Response status:', response.status);
                console.log('Response headers:', response.headers);
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.text(); // Get as text first to see raw response
            })
            .then(text => {
                console.log('Raw response:', text);
                try {
                    const data = JSON.parse(text);
                    console.log('Parsed data:', data);
                    return data;
                } catch (e) {
                    console.error('JSON parse error:', e);
                    console.log('Raw text that failed to parse:', text);
                    throw e;
                }
            })
            .then(data => {
                console.log('Received data:', data);
                if (data.success && data.attachment) {
                    const previewFile = data.attachment;
                    const contentDiv = document.getElementById('previewContent');

                    if (!contentDiv) {
                        console.error('Content div not found');
                        return;
                    }

                    const extension = previewFile.file_name.split('.').pop().toLowerCase();
                    console.log('File extension:', extension);
                    console.log('Preview file data:', previewFile);

                    let content = '';

                    if (extension === 'pdf') {
                        content =
                            `<iframe src="/attachments/${previewFile.id}/preview" width="100%" height="600px" style="border: none; border-radius: 0.375rem;"></iframe>`;
                    } else if (['jpg', 'jpeg', 'png', 'gif'].includes(extension)) {
                        content =
                            `<div class="text-center"><img src="/attachments/${previewFile.id}/preview" alt="${previewFile.file_name}" class="img-fluid rounded" style="max-height: 600px;"></div>`;
                    } else if (extension === 'txt') {
                        content =
                            `<div class="text-preview"><pre class="bg-light p-3 rounded" style="max-height: 600px; overflow-y: auto;">${previewFile.content || 'Content not available'}</pre></div>`;
                    } else if (['mp4', 'webm', 'ogg', 'avi', 'mov', 'wmv', 'flv', 'mkv'].includes(extension)) {
                        content =
                            `<div class="video-preview text-center"><video controls style="max-width: 100%; max-height: 600px; border-radius: 0.375rem;" preload="metadata"><source src="/attachments/${previewFile.id}/preview" type="video/${extension}">Your browser does not support the video tag.</video></div>`;
                    } else {
                        content =
                            `<div class="text-center py-5"><i class="bi bi-file-earmark fs-1 text-muted mb-3"></i><h5 class="text-muted">Preview not available</h5><p class="text-muted">This file type cannot be previewed.</p><a href="/attachments/${previewFile.id}/download" class="btn btn-primary"><i class="bi bi-download me-1"></i>Download File</a></div>`;
                    }

                    // Add file info panel
                    content +=
                        `<div class="file-info-panel mt-3 p-3 bg-light rounded"><div class="row"><div class="col-md-6"><strong>File Name:</strong> ${previewFile.file_name}<br><strong>File Size:</strong> ${previewFile.formatted_file_size}<br><strong>File Type:</strong> ${extension.toUpperCase()}</div><div class="col-md-6"><strong>Uploaded by:</strong> ${previewFile.uploaded_by?.name || 'Unknown'}<br><strong>Upload Date:</strong> ${new Date(previewFile.created_at).toLocaleDateString()}<br><strong>Task:</strong> ${previewFile.task?.title || 'N/A'}</div></div></div>`;

                    console.log('Setting content...');
                    contentDiv.innerHTML = content;
                    console.log('Content set successfully');

                    // Hide loading state after content is set
                    setTimeout(() => {
                        hidePreviewLoadingState();
                    }, 100);
                } else {
                    console.error('Invalid data received:', data);
                    showPreviewErrorState();
                }
            })
            .catch(error => {
                console.error('Error fetching file data:', error);
                showPreviewErrorState();
            });
    }

    // Debug function to check modal
    function checkModal() {
        console.log('Checking for modal...');
        const debugBox = document.querySelector('div[style*="position: fixed"][style*="background: red"]');
        const modal = document.querySelector('.modal.show');
        const backdrop = document.querySelector('.modal-backdrop');

        console.log('Debug box found:', debugBox ? 'YES' : 'NO');
        console.log('Modal found:', modal ? 'YES' : 'NO');
        console.log('Backdrop found:', backdrop ? 'YES' : 'NO');

        if (debugBox) {
            console.log('Debug box text:', debugBox.textContent);
        }

        if (modal) {
            console.log('Modal display style:', window.getComputedStyle(modal).display);
            console.log('Modal visibility:', window.getComputedStyle(modal).visibility);
        }
    }
</script>
</div>
