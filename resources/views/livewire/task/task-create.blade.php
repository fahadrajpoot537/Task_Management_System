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
                                <div class="input-group">
                                    <input type="text" class="form-control @error('assigned_to_user_id') is-invalid @enderror" 
                                           id="assigned_to_user_id" 
                                           name="assigned_to_user_id"
                                           placeholder="Click to select employee" 
                                           readonly>
                                    <input type="hidden" id="assigned_to_user_id_hidden" name="assigned_to_user_id_hidden">
                                    <button type="button" class="btn btn-outline-primary" 
                                            onclick="openEmployeeModal()">
                                        <i class="bi bi-people me-1"></i>Select Employee
                                    </button>
                                </div>
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

    <!-- Employee Selection Modal -->
    <div class="modal fade" id="employeeModal" tabindex="-1" aria-labelledby="employeeModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title fw-bold" id="employeeModalLabel">
                        <i class="bi bi-people me-2"></i>Select Employee
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                
                <div class="modal-body">
                    <!-- Search Box -->
                    <div class="mb-3">
                        <div class="input-group">
                            <span class="input-group-text">
                                <i class="bi bi-search"></i>
                            </span>
                            <input type="text" class="form-control" id="employeeSearch" placeholder="Search employees by name...">
                        </div>
                    </div>
                    
                    <!-- Employee List -->
                    <div class="employee-list" style="max-height: 400px; overflow-y: auto;">
                        <div class="row g-2" id="employeeGrid">
                            <!-- Employees will be loaded here -->
                        </div>
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="bi bi-x-circle me-2"></i>Cancel
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    let employees = [];
    let selectedEmployee = null;

    // Load employees when page loads
    document.addEventListener('DOMContentLoaded', function() {
        loadEmployees();
    });

    // Load employees from server
    async function loadEmployees() {
        try {
            const response = await fetch('/api/users');
            if (response.ok) {
                employees = await response.json();
                console.log('Loaded employees:', employees); // Debug log
                displayEmployees(employees);
            } else {
                console.error('Failed to load employees');
                displayEmployees([]);
            }
        } catch (error) {
            console.error('Error loading employees:', error);
            displayEmployees([]);
        }
    }

    // Display employees in the grid
    function displayEmployees(employeeList) {
        const grid = document.getElementById('employeeGrid');
        grid.innerHTML = '';

        if (employeeList.length === 0) {
            grid.innerHTML = `
                <div class="col-12 text-center py-4">
                    <i class="bi bi-person-x text-muted" style="font-size: 3rem;"></i>
                    <p class="text-muted mt-2 mb-0">No employees available</p>
                </div>
            `;
            return;
        }

        employeeList.forEach(employee => {
            const employeeCard = document.createElement('div');
            employeeCard.className = 'col-md-6 col-lg-4';
            employeeCard.innerHTML = `
                <div class="card employee-card h-100" style="cursor: pointer; transition: all 0.2s;" onclick="selectEmployee(${employee.id}, '${employee.name}')">
                    <div class="card-body text-center p-3">
                        <div class="mb-2">
                            <i class="bi bi-person-circle text-primary" style="font-size: 2rem;"></i>
                        </div>
                        <h6 class="card-title mb-1">${employee.name}</h6>
                    </div>
                </div>
            `;
            grid.appendChild(employeeCard);
        });
    }

    // Open employee modal
    function openEmployeeModal() {
        const modal = new bootstrap.Modal(document.getElementById('employeeModal'));
        modal.show();
    }

    // Select employee
    function selectEmployee(employeeId, employeeName) {
        selectedEmployee = { id: employeeId, name: employeeName };
        
        // Update the form fields
        document.getElementById('assigned_to_user_id').value = employeeName;
        document.getElementById('assigned_to_user_id_hidden').value = employeeId;
        
        // Close modal
        const modal = bootstrap.Modal.getInstance(document.getElementById('employeeModal'));
        modal.hide();
    }

    // Search functionality
    document.getElementById('employeeSearch').addEventListener('input', function() {
        const searchTerm = this.value.toLowerCase();
        const filteredEmployees = employees.filter(employee => 
            employee.name.toLowerCase().includes(searchTerm)
        );
        displayEmployees(filteredEmployees);
    });

    // Add hover effects
    document.addEventListener('mouseover', function(e) {
        if (e.target.closest('.employee-card')) {
            e.target.closest('.employee-card').style.transform = 'translateY(-2px)';
            e.target.closest('.employee-card').style.boxShadow = '0 4px 8px rgba(0,0,0,0.1)';
        }
    });

    document.addEventListener('mouseout', function(e) {
        if (e.target.closest('.employee-card')) {
            e.target.closest('.employee-card').style.transform = 'translateY(0)';
            e.target.closest('.employee-card').style.boxShadow = 'none';
        }
    });
</script>

<style>
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
</style>
