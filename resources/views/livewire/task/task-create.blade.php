<div>
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Create New Task</h2>
        <a href="{{ route('tasks.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-2"></i>Back to Tasks
        </a>
    </div>

    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-body">
                    <form wire:submit="createTask">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="project_id" class="form-label">Project <span class="text-danger">*</span></label>
                                <select class="form-select @error('project_id') is-invalid @enderror" 
                                        id="project_id" wire:model="project_id" required>
                                    <option value="">Select a project</option>
                                    @foreach($this->projects as $project)
                                        <option value="{{ $project->id }}">{{ $project->title }}</option>
                                    @endforeach
                                </select>
                                @error('project_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="assigned_to_user_id" class="form-label">Assign To <span class="text-danger">*</span></label>
                                <select class="form-select @error('assigned_to_user_id') is-invalid @enderror" 
                                        id="assigned_to_user_id" wire:model="assigned_to_user_id" required>
                                    <option value="">Select a user</option>
                                    @foreach($this->availableUsers as $user)
                                        <option value="{{ $user->id }}">{{ $user->name }}</option>
                                    @endforeach
                                </select>
                                @error('assigned_to_user_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="title" class="form-label">Task Title <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('title') is-invalid @enderror" 
                                   id="title" wire:model="title" required autofocus>
                            @error('title')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control @error('description') is-invalid @enderror" 
                                      id="description" wire:model="description" rows="4" 
                                      placeholder="Describe the task..."></textarea>
                            @error('description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label for="priority_id" class="form-label">Priority <span class="text-danger">*</span></label>
                                <div class="position-relative">
                                    <select class="form-select @error('priority_id') is-invalid @enderror" 
                                            id="priority_id" wire:model="priority_id" required>
                                        <option value="">Select Priority</option>
                                        @foreach($this->priorities as $priority)
                                            <option value="{{ $priority->id }}">{{ $priority->name }}</option>
                                        @endforeach
                                    </select>
                                    
                                    <!-- Add Priority Button -->
                                    <button type="button" class="btn btn-sm btn-outline-primary position-absolute top-0 end-0" 
                                            style="transform: translateY(-50%); z-index: 10;"
                                            wire:click="showAddPriorityForm">
                                        <i class="bi bi-plus"></i>
                                    </button>
                                    
                                    <!-- Add Priority Form -->
                                    @if($showAddPriorityForm)
                                        <div class="position-absolute top-100 start-0 w-100 bg-white border rounded shadow-lg p-2" 
                                             style="z-index: 1000;">
                                            <div class="mb-2">
                                                <input type="text" class="form-control form-control-sm" 
                                                       wire:model="newPriorityName" 
                                                       placeholder="Priority name">
                                                @error('newPriorityName')
                                                    <small class="text-danger">{{ $message }}</small>
                                                @enderror
                                            </div>
                                            <div class="mb-2">
                                                <select class="form-select form-select-sm" wire:model="newPriorityColor">
                                                    <option value="primary">Primary (Blue)</option>
                                                    <option value="secondary">Secondary (Gray)</option>
                                                    <option value="success">Success (Green)</option>
                                                    <option value="danger">Danger (Red)</option>
                                                    <option value="warning">Warning (Yellow)</option>
                                                    <option value="info">Info (Light Blue)</option>
                                                    <option value="dark">Dark (Black)</option>
                                </select>
                                            </div>
                                            <div class="d-flex gap-1">
                                                <button type="button" class="btn btn-sm btn-success" 
                                                        wire:click="addNewPriority">
                                                    <i class="bi bi-check"></i>
                                                </button>
                                                <button type="button" class="btn btn-sm btn-secondary" 
                                                        wire:click="hideAddPriorityForm">
                                                    <i class="bi bi-x"></i>
                                                </button>
                                            </div>
                                        </div>
                                    @endif
                                </div>
                                @error('priority_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-4 mb-3">
                                <label for="category_id" class="form-label">Category <span class="text-danger">*</span></label>
                                <div class="position-relative">
                                    <select class="form-select @error('category_id') is-invalid @enderror" 
                                            id="category_id" wire:model="category_id" required>
                                        <option value="">Select Category</option>
                                        @foreach($this->categories as $category)
                                            <option value="{{ $category->id }}">{{ $category->name }}</option>
                                        @endforeach
                                    </select>
                                    
                                    <!-- Add Category Button -->
                                    <button type="button" class="btn btn-sm btn-outline-primary position-absolute top-0 end-0" 
                                            style="transform: translateY(-50%); z-index: 10;"
                                            wire:click="showAddCategoryForm">
                                        <i class="bi bi-plus"></i>
                                    </button>
                                    
                                    <!-- Add Category Form -->
                                    @if($showAddCategoryForm)
                                        <div class="position-absolute top-100 start-0 w-100 bg-white border rounded shadow-lg p-2" 
                                             style="z-index: 1000;">
                                            <div class="mb-2">
                                                <input type="text" class="form-control form-control-sm" 
                                                       wire:model="newCategoryName" 
                                                       placeholder="Category name">
                                                @error('newCategoryName')
                                                    <small class="text-danger">{{ $message }}</small>
                                                @enderror
                                            </div>
                                            <div class="mb-2">
                                                <select class="form-select form-select-sm" wire:model="newCategoryIcon">
                                                    <option value="bi-code-slash">Code</option>
                                                    <option value="bi-palette">Design</option>
                                                    <option value="bi-bug">Testing</option>
                                                    <option value="bi-file-text">Documentation</option>
                                                    <option value="bi-people">Meeting</option>
                                                    <option value="bi-list-task">General</option>
                                                    <option value="bi-gear">Settings</option>
                                                    <option value="bi-graph-up">Analytics</option>
                                                </select>
                                            </div>
                                            <div class="mb-2">
                                                <select class="form-select form-select-sm" wire:model="newCategoryColor">
                                                    <option value="primary">Primary (Blue)</option>
                                                    <option value="secondary">Secondary (Gray)</option>
                                                    <option value="success">Success (Green)</option>
                                                    <option value="danger">Danger (Red)</option>
                                                    <option value="warning">Warning (Yellow)</option>
                                                    <option value="info">Info (Light Blue)</option>
                                                    <option value="dark">Dark (Black)</option>
                                                </select>
                                            </div>
                                            <div class="d-flex gap-1">
                                                <button type="button" class="btn btn-sm btn-success" 
                                                        wire:click="addNewCategory">
                                                    <i class="bi bi-check"></i>
                                                </button>
                                                <button type="button" class="btn btn-sm btn-secondary" 
                                                        wire:click="hideAddCategoryForm">
                                                    <i class="bi bi-x"></i>
                                                </button>
                                            </div>
                                        </div>
                                    @endif
                                </div>
                                @error('category_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-4 mb-3">
                                <label for="status_id" class="form-label">Status</label>
                                <div class="position-relative">
                                    <select class="form-select @error('status_id') is-invalid @enderror" 
                                            id="status_id" wire:model="status_id">
                                        <option value="">Select Status (Optional)</option>
                                        @foreach($this->statuses as $status)
                                            <option value="{{ $status->id }}">{{ $status->name }}</option>
                                        @endforeach
                                    </select>
                                    
                                    <!-- Add Status Button -->
                                    <button type="button" class="btn btn-sm btn-outline-primary position-absolute top-0 end-0" 
                                            style="transform: translateY(-50%); z-index: 10;"
                                            wire:click="showAddStatusForm">
                                        <i class="bi bi-plus"></i>
                                    </button>
                                    
                                    <!-- Add Status Form -->
                                    @if($showAddStatusForm)
                                        <div class="position-absolute top-100 start-0 w-100 bg-white border rounded shadow-lg p-2" 
                                             style="z-index: 1000;">
                                            <div class="mb-2">
                                                <input type="text" class="form-control form-control-sm" 
                                                       wire:model="newStatusName" 
                                                       placeholder="Status name">
                                                @error('newStatusName')
                                                    <small class="text-danger">{{ $message }}</small>
                                                @enderror
                                            </div>
                                            <div class="mb-2">
                                                <select class="form-select form-select-sm" wire:model="newStatusColor">
                                                    <option value="primary">Primary (Blue)</option>
                                                    <option value="secondary">Secondary (Gray)</option>
                                                    <option value="success">Success (Green)</option>
                                                    <option value="danger">Danger (Red)</option>
                                                    <option value="warning">Warning (Yellow)</option>
                                                    <option value="info">Info (Light Blue)</option>
                                                    <option value="dark">Dark (Black)</option>
                                                </select>
                                            </div>
                                            <div class="mb-2">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" wire:model="newStatusIsDefault" id="newStatusIsDefault">
                                                    <label class="form-check-label" for="newStatusIsDefault">
                                                        Set as default status
                                                    </label>
                                                </div>
                                            </div>
                                            <div class="d-flex gap-1">
                                                <button type="button" class="btn btn-sm btn-success" 
                                                        wire:click="addNewStatus">
                                                    <i class="bi bi-check"></i>
                                                </button>
                                                <button type="button" class="btn btn-sm btn-secondary" 
                                                        wire:click="hideAddStatusForm">
                                                    <i class="bi bi-x"></i>
                                                </button>
                                            </div>
                                        </div>
                                    @endif
                                </div>
                                @error('status_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="duration" class="form-label">Duration (hours)</label>
                                <input type="number" class="form-control @error('duration') is-invalid @enderror" 
                                       id="duration" wire:model="duration" min="1">
                                @error('duration')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="due_date" class="form-label">Due Date</label>
                                <input type="date" class="form-control @error('due_date') is-invalid @enderror" 
                                       id="due_date" wire:model="due_date" min="{{ date('Y-m-d') }}">
                                @error('due_date')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="nature_of_task" class="form-label">Nature of Task <span class="text-danger">*</span></label>
                                <select class="form-select @error('nature_of_task') is-invalid @enderror" 
                                        id="nature_of_task" wire:model="nature_of_task" required>
                                    <option value="daily">Daily</option>
                                    <option value="weekly">Weekly</option>
                                    <option value="monthly">Monthly</option>
                                    <option value="until_stop">Until Stopped</option>
                                </select>
                                <div class="form-text">
                                    <strong>Daily:</strong> Task will be created once and completed normally.<br>
                                    <strong>Weekly/Monthly/Until Stopped:</strong> Task will automatically recreate itself when completed until manually stopped.
                                </div>
                                @error('nature_of_task')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="notes" class="form-label">Notes</label>
                            <textarea class="form-control @error('notes') is-invalid @enderror" 
                                      id="notes" wire:model="notes" rows="3" 
                                      placeholder="Additional notes..."></textarea>
                            @error('notes')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="attachments" class="form-label">Attachments</label>
                            <input type="file" class="form-control @error('attachments.*') is-invalid @enderror" 
                                   id="attachments" wire:model="attachments" multiple>
                            <div class="form-text">You can upload multiple files (max 10MB each)</div>
                            @error('attachments.*')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="d-flex justify-content-end gap-2">
                            <a href="{{ route('tasks.index') }}" class="btn btn-outline-secondary">
                                Cancel
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-check-circle me-2"></i>Create Task
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
