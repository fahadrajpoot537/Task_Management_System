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
                    <button class="btn btn-light btn-lg px-4 py-2" wire:click="startEditing(0)" wire:loading.attr="disabled">
                        <i class="bi bi-plus-circle me-2"></i>Add Task
                        <span wire:loading wire:target="startEditing">
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
        </div>

        <!-- Mobile Task Cards (Hidden on Desktop) -->
        <div class="mobile-task-cards">
            @foreach($this->tasks as $task)
                <div class="mobile-task-item" wire:key="mobile-task-{{ $task->id }}">
                    <div class="mobile-task-header">
                        <div>
                            <div class="mobile-task-title">{{ $task->title }}</div>
                            @if($task->project)
                                <small class="text-muted">{{ $task->project->title }}</small>
                            @endif
                        </div>
                        <div class="dropdown">
                            <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                <i class="bi bi-three-dots-vertical"></i>
                            </button>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="#" wire:click="startEditing({{ $task->id }})"><i class="bi bi-pencil me-2"></i>Edit</a></li>
                                <li><a class="dropdown-item text-danger" href="#" wire:click="deleteTask({{ $task->id }})"><i class="bi bi-trash me-2"></i>Delete</a></li>
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
                        
                        @if($task->priority)
                            <div class="mobile-task-meta-item">
                                <span class="badge bg-{{ $task->priority->color }}">{{ $task->priority->name }}</span>
                            </div>
                        @endif
                        
                        @if($task->category)
                            <div class="mobile-task-meta-item">
                                <i class="bi {{ $task->category->icon }}"></i>
                                <span>{{ $task->category->name }}</span>
                            </div>
                        @endif
                        
                        @if($task->status)
                            <div class="mobile-task-meta-item">
                                <span class="badge bg-{{ $task->status->color }}">{{ $task->status->name }}</span>
                            </div>
                        @endif
                        
                        @if($task->due_date)
                            <div class="mobile-task-meta-item">
                                <i class="bi bi-calendar"></i>
                                <span>{{ $task->due_date->format('M d, Y') }}</span>
                            </div>
                        @endif
                        
                        @if($task->estimated_hours)
                            <div class="mobile-task-meta-item">
                                <i class="bi bi-clock"></i>
                                <span>{{ $task->estimated_hours }}h</span>
                            </div>
                        @endif
                    </div>
                    
                    @if($task->notes)
                        <div class="mobile-task-notes">
                            {{ Str::limit($task->notes, 100) }}
                        </div>
                    @endif
                    
                    <div class="mobile-task-actions">
                        @if($task->status)
                            <div class="dropdown">
                                <button class="btn btn-sm btn-outline-primary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                    <span class="badge bg-{{ $task->status->color }}">{{ $task->status->name }}</span>
                                </button>
                                <ul class="dropdown-menu">
                                    @foreach($this->statuses as $status)
                                        @if(!auth()->user()->isEmployee() || $status->name !== 'Complete')
                                            <li>
                                                <a class="dropdown-item {{ $task->status && $task->status->id === $status->id ? 'active' : '' }}" 
                                                   href="#" wire:click="updateTaskStatus({{ $task->id }}, {{ $status->id }})">
                                                    <span class="badge bg-{{ $status->color }} me-2">{{ $status->name }}</span>
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
            
            @if($this->tasks->count() == 0)
                <div class="text-center py-4">
                    <div class="text-muted">
                        <i class="bi bi-inbox fs-1 d-block mb-3"></i>
                        <h5>No tasks found</h5>
                        <p class="mb-0">Start by creating your first task!</p>
                    </div>
                </div>
            @endif
        </div>

        <!-- Task Table -->
        <div class="table-responsive">
            <table id="tasksTable" class="table table-hover mb-0">
                <thead class="table-dark">
                    <tr>
                        <th class="d-none d-md-table-cell" width="4%">#</th>
                        <th width="25%">Title</th>
                        <th class="d-none d-lg-table-cell" width="12%">Project</th>
                        <th class="d-none d-xl-table-cell" width="12%">Assignee</th>
                        <th class="d-none d-lg-table-cell" width="8%">Priority</th>
                        <th class="d-none d-lg-table-cell" width="8%">Category</th>
                        <th width="10%">Status</th>
                        <th class="d-none d-md-table-cell" width="8%">Due Date</th>
                        <th class="d-none d-xl-table-cell" width="6%">Hours</th>
                        <th class="d-none d-lg-table-cell" width="12%">Notes</th>
                        <th width="8%">Actions</th>
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
                                <select class="form-select form-select-sm" wire:model="newTaskAssigneeId">
                                    <option value="">Unassigned</option>
                                    @foreach($this->users as $user)
                                        <option value="{{ $user->id }}">{{ $user->name }}</option>
                                    @endforeach
                                </select>
                            </td>
                            <td class="d-none d-lg-table-cell">
                                <select class="form-select form-select-sm" wire:model="newTaskPriority">
                                    <option value="">Select Priority</option>
                                    @foreach($this->priorities as $priority)
                                        <option value="{{ $priority->id }}">{{ $priority->name }}</option>
                                    @endforeach
                                </select>
                                @if($this->canManagePriorities())
                                    <small class="text-muted">
                                        <a href="#" wire:click="toggleCustomPriorityForm" class="text-decoration-none">
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
                                @if($this->canManageCategories())
                                    <small class="text-muted">
                                        <a href="#" wire:click="toggleCustomCategoryForm" class="text-decoration-none">
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
                                        wire:click="openNotesModal(0, 'commit')" 
                                        title="Add Notes">
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
                    @foreach($this->tasks as $task)
                        @if($editingTaskId === $task->id)
                            <!-- Editing Row -->
                            <tr class="task-row editing">
                                <td class="d-none d-md-table-cell">{{ $task->id }}</td>
                                <td>
                                    <input type="text" class="form-control form-control-sm" wire:model="newTaskTitle" 
                                           wire:keydown.enter="saveTask">
                                </td>
                                <td class="d-none d-lg-table-cell">
                                    <select class="form-select form-select-sm" wire:model="newTaskProjectId">
                                        @foreach($this->projects as $project)
                                            <option value="{{ $project->id }}">{{ $project->title }}</option>
                                        @endforeach
                                    </select>
                                </td>
                                <td class="d-none d-xl-table-cell">
                                    <select class="form-select form-select-sm" wire:model="newTaskAssigneeId">
                                        <option value="">Unassigned</option>
                                        @foreach($this->users as $user)
                                            <option value="{{ $user->id }}">{{ $user->name }}</option>
                                        @endforeach
                                    </select>
                                </td>
                                <td class="d-none d-lg-table-cell">
                                    <select class="form-select form-select-sm" wire:model="newTaskPriority">
                                        @foreach($this->priorities as $priority)
                                            <option value="{{ $priority->id }}">{{ $priority->name }}</option>
                                        @endforeach
                                    </select>
                                </td>
                                <td class="d-none d-lg-table-cell">
                                    <select class="form-select form-select-sm" wire:model="newTaskCategory">
                                        @foreach($this->categories as $category)
                                            <option value="{{ $category->id }}">{{ $category->name }}</option>
                                        @endforeach
                                    </select>
                                </td>
                                <td>
                                    @if($task->status)
                                        <span class="badge bg-{{ $task->status->color }}">
                                            {{ $task->status->name }}
                                        </span>
                                    @else
                                        <span class="badge bg-secondary">No Status</span>
                                    @endif
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
                                            wire:click="openNotesModal({{ $task->id }}, 'commit')" 
                                            title="Commit Notes">
                                        <i class="bi bi-git me-1"></i>Commit Notes
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
                        @else
                            <!-- Normal Row -->
                            <tr class="task-row">
                                <td class="d-none d-md-table-cell">{{ $task->id }}</td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="me-2 d-none d-sm-block">
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
                                        </div>
                                        <div class="flex-grow-1">
                                            <strong class="fs-6 d-block">{{ $task->title }}</strong>
                                            @if($task->description)
                                                <small class="text-muted d-none d-sm-block">{{ Str::limit($task->description, 50) }}</small>
                                            @endif
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
                                    @if($task->project)
                                        <span class="badge bg-info">{{ $task->project->title }}</span>
                                    @else
                                        <span class="badge bg-secondary">No Project</span>
                                    @endif
                                </td>
                                <td class="d-none d-xl-table-cell">
                                    @if($task->assignedTo)
                                        <div class="d-flex align-items-center">
                                            <div class="avatar-sm bg-gradient-primary text-white rounded-circle d-flex align-items-center justify-content-center me-2">
                                                {{ substr($task->assignedTo->name, 0, 1) }}
                                            </div>
                                            <div>
                                                <div class="fw-semibold">{{ $task->assignedTo->name }}</div>
                                                @if($task->assignedTo->role)
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
                                        @if($task->priority)
                                            @if(is_object($task->priority))
                                                <button class="btn btn-sm badge bg-{{ $task->priority->color ?? 'secondary' }} dropdown-toggle" 
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
                                                       href="#" wire:click="updateTaskPriority({{ $task->id }}, {{ $priority->id }})">
                                                        <span class="badge bg-{{ $priority->color }} me-2">{{ $priority->name }}</span>
                                                    </a>
                                                </li>
                                            @endforeach
                                            @if($this->canManagePriorities())
                                                <li><hr class="dropdown-divider"></li>
                                                <li>
                                                    <a class="dropdown-item" href="#" wire:click="toggleCustomPriorityForm">
                                                        <i class="bi bi-plus-circle me-2"></i>Add Custom Priority
                                                    </a>
                                                </li>
                                            @endif
                                        </ul>
                                    </div>
                                </td>
                                <td class="d-none d-lg-table-cell">
                                    <div class="dropdown">
                                        @if($task->category)
                                            @if(is_object($task->category))
                                                <button class="btn btn-sm badge bg-{{ $task->category->color ?? 'secondary' }} dropdown-toggle" 
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
                                                       href="#" wire:click="updateTaskCategory({{ $task->id }}, {{ $category->id }})">
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
                                                    <a class="dropdown-item" href="#" wire:click="toggleCustomCategoryForm">
                                                        <i class="bi bi-plus-circle me-2"></i>Add Custom Category
                                                    </a>
                                                </li>
                                            @endif
                                        </ul>
                                    </div>
                                </td>
                                <td>
                                    <div class="dropdown">
                                        @if($task->status)
                                            <button class="btn btn-sm badge bg-{{ $task->status->color }} dropdown-toggle" 
                                                    type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                                {{ $task->status->name }}
                                            </button>
                                        @else
                                            <button class="btn btn-sm badge bg-secondary dropdown-toggle" 
                                                    type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                                No Status
                                            </button>
                                        @endif
                                        <ul class="dropdown-menu">
                                            @foreach($this->statuses as $status)
                                                @if(!auth()->user()->isEmployee() || $status->name !== 'Complete')
                                                    <li>
                                                        <a class="dropdown-item {{ $task->status && $task->status->id === $status->id ? 'active' : '' }}" 
                                                           href="#" wire:click="updateTaskStatus({{ $task->id }}, {{ $status->id }})">
                                                            <span class="badge bg-{{ $status->color }} me-2">{{ $status->name }}</span>
                                                        </a>
                                                    </li>
                                                @endif
                                            @endforeach
                                            @if($this->canManageStatuses())
                                                <li><hr class="dropdown-divider"></li>
                                                <li>
                                                    <a class="dropdown-item" href="#" wire:click="toggleCustomStatusForm">
                                                        <i class="bi bi-plus-circle me-2"></i>Add Custom Status
                                                    </a>
                                                </li>
                                            @endif
                                        </ul>
                                    </div>
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
                                    <div class="notes-container">
                                        @if($task->notes)
                                            <div class="notes-preview" style="max-height: 40px; overflow: hidden; font-size: 0.8rem; color: #6c757d; cursor: pointer;" 
                                                 wire:click="openNotesModal({{ $task->id }}, 'view')" 
                                                 title="Click to view notes">
                                                {{ Str::limit($task->notes, 80) }}
                                            </div>
                                            @if(auth()->user()->isSuperAdmin() || auth()->user()->isAdmin() || $task->assigned_by_user_id === auth()->id())
                                                <button class="btn btn-sm btn-outline-success mt-1" 
                                                        wire:click="openNotesModal({{ $task->id }}, 'commit')" 
                                                        title="Commit Notes">
                                                    <i class="bi bi-git"></i>
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
                                            @if(auth()->user()->isSuperAdmin() || auth()->user()->isAdmin() || $task->assigned_by_user_id === auth()->id())
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
                                </td>
                                <td>
                                    <div class="btn-group" role="group">
                                        <button class="btn btn-sm btn-outline-primary" wire:click="startEditing({{ $task->id }})" title="Edit">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        <button class="btn btn-sm btn-outline-danger" 
                                                onclick="confirmDelete({{ $task->id }})" title="Delete">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @endif
                    @endforeach
                </tbody>
            </table>
        </div>

        <!-- Mobile Card Layout -->
       

        <!-- DataTables will handle pagination -->
    </div>

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
            // Handle dropdown show event
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
        }

        /* Ensure proper spacing on mobile */
        @media (max-width: 768px) {
            .task-row td {
                padding: 0.5rem 0.25rem;
            }
            
            .btn-group .btn {
                margin: 0 1px;
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
                    }
                });
            }, 100);
        });
    </script>

    <!-- Notes Modal -->
    @if($showNotesModal)
    <div class="modal fade show" style="display: block;" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="bi bi-sticky me-2"></i>{{ $notesModalTitle }}
                    </h5>
                    <button type="button" class="btn-close" wire:click="closeNotesModal"></button>
                </div>
                <div class="modal-body">
                    @if($notesModalMode === 'commit')
                        <div class="mb-3">
                            <label for="notesContent" class="form-label">Notes</label>
                            <textarea class="form-control" 
                                      id="notesContent"
                                      wire:model="notesModalContent" 
                                      rows="8" 
                                      placeholder="Enter your notes here..."></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="commitMessage" class="form-label">Commit Message</label>
                            <input type="text" class="form-control" 
                                   id="commitMessage"
                                   wire:model="commitMessage" 
                                   placeholder="Enter commit message (e.g., 'Updated task requirements')">
                            <div class="form-text">Describe what changes you're making to the notes.</div>
                        </div>
                    @else
                        <div class="notes-view">
                            @if($notesModalContent)
                                <div class="notes-content mb-4" style="white-space: pre-wrap; line-height: 1.6; font-size: 0.95rem; padding: 1rem; background-color: #f8f9fa; border-radius: 0.5rem;">
                                    {{ $notesModalContent }}
                                </div>
                            @else
                                <div class="text-muted text-center py-4">
                                    <i class="bi bi-sticky fs-1 d-block mb-3"></i>
                                    <p>No notes available for this task.</p>
                                </div>
                            @endif
                            
                            <!-- Comments Section -->
                            <div class="comments-section">
                                <h6 class="mb-3">
                                    <i class="bi bi-chat-dots me-2"></i>Comments
                                    <span class="badge bg-secondary ms-2">{{ $this->getTaskCommentsCount() }}</span>
                                </h6>
                                
                                <!-- Comments List -->
                                <div class="comments-list mb-3" style="max-height: 300px; overflow-y: auto;">
                                    @foreach($this->getTaskComments() as $comment)
                                        <div class="comment-item mb-3 p-3 border rounded">
                                            <div class="d-flex justify-content-between align-items-start mb-2">
                                                <div class="d-flex align-items-center">
                                                    <div class="avatar-sm bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-2">
                                                        {{ substr($comment->user->name, 0, 1) }}
                                                    </div>
                                                    <div>
                                                        <strong class="fs-6">{{ $comment->user->name }}</strong>
                                                        <br>
                                                        <small class="text-muted">{{ $comment->created_at->format('M d, Y g:i A') }}</small>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="comment-content" style="white-space: pre-wrap; line-height: 1.5;">
                                                {{ $comment->comment }}
                                            </div>
                                        </div>
                                    @endforeach
                                    
                                    @if($this->getTaskCommentsCount() == 0)
                                        <div class="text-muted text-center py-3">
                                            <i class="bi bi-chat fs-3 d-block mb-2"></i>
                                            <p class="mb-0">No comments yet. Be the first to comment!</p>
                                        </div>
                                    @endif
                                </div>
                                
                                <!-- Add Comment Form -->
                                <div class="add-comment-form">
                                    <div class="mb-3">
                                        <label for="newComment" class="form-label">Add Comment</label>
                                        <textarea class="form-control" 
                                                  id="newComment"
                                                  wire:model="newComment" 
                                                  rows="3" 
                                                  placeholder="Write your comment here..."></textarea>
                                    </div>
                                    <button type="button" class="btn btn-primary" wire:click="addComment" wire:loading.attr="disabled">
                                        <span wire:loading wire:target="addComment">
                                            <span class="spinner-border spinner-border-sm me-2"></span>
                                        </span>
                                        <i class="bi bi-send me-1"></i>Add Comment
                                    </button>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
                <div class="modal-footer">
                    @if($notesModalMode === 'commit')
                        <button type="button" class="btn btn-secondary" wire:click="closeNotesModal">
                            <i class="bi bi-x-circle me-1"></i>Cancel
                        </button>
                        <button type="button" class="btn btn-success" wire:click="commitNotes">
                            <i class="bi bi-git me-1"></i>Commit Notes
                        </button>
                    @else
                        <button type="button" class="btn btn-secondary" wire:click="closeNotesModal">
                            <i class="bi bi-x-circle me-1"></i>Close
                        </button>
                        @if($this->canEditNotes())
                            <button type="button" class="btn btn-success" wire:click="openNotesModal({{ $notesModalTaskId }}, 'commit')">
                                <i class="bi bi-git me-1"></i>Commit Notes
                            </button>
                        @endif
                    @endif
                </div>
            </div>
        </div>
    </div>
    <div class="modal-backdrop fade show"></div>
    @endif

    <!-- Custom Status Creation Form -->
    @if($showCustomStatusForm)
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
                            <input type="text" class="form-control @error('customStatusName') is-invalid @enderror" 
                                   id="customStatusName" wire:model="customStatusName" placeholder="Enter status name">
                            @error('customStatusName') <div class="invalid-feedback">{{ $message }}</div> @enderror
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
                            @error('customStatusColor') <div class="invalid-feedback">{{ $message }}</div> @enderror
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
    @if($showCustomPriorityForm)
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
                            <input type="text" class="form-control @error('customPriorityName') is-invalid @enderror" 
                                   id="customPriorityName" wire:model="customPriorityName" placeholder="Enter priority name">
                            @error('customPriorityName') <div class="invalid-feedback">{{ $message }}</div> @enderror
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
                            @error('customPriorityColor') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-success">
                                <i class="bi bi-check-circle"></i> Create Priority
                            </button>
                            <button type="button" wire:click="resetCustomPriorityForm" class="btn btn-secondary">
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
    @if($showCustomCategoryForm)
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
                                    <input type="text" class="form-control @error('customCategoryName') is-invalid @enderror" 
                                           id="customCategoryName" wire:model="customCategoryName" placeholder="Enter category name">
                                    @error('customCategoryName') <div class="invalid-feedback">{{ $message }}</div> @enderror
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
                                    @error('customCategoryIcon') <div class="invalid-feedback">{{ $message }}</div> @enderror
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
                            @error('customCategoryColor') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-success">
                                <i class="bi bi-check-circle"></i> Create Category
                            </button>
                            <button type="button" wire:click="resetCustomCategoryForm" class="btn btn-secondary">
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
</div>
