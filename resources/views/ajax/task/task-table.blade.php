@extends('layouts.app')

@section('content')
    <style>
        .table-responsive {
            overflow: visible !important;
            overflow-x: visible;
            overflow-x: auto !important;
            overflow-y: auto;
            max-height: 70vh;
            border: none;
            border-radius: 0 0 0.5rem 0.5rem;
            width: 100%;
            display: block;
            position: relative !important;
        }
        
        .bulk-assignee-container {
            min-width: 200px;
        }
        
        .bulk-assignee-container .form-label {
            font-size: 0.875rem;
            font-weight: 500;
            color: #495057;
        }
        
        .bulk-assignee-container .select2-container {
            width: 100% !important;
        }
    </style>
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
                    <button type="button" class="btn btn-light btn-lg px-4 py-2" onclick="taskTableManager.openTaskModal()">
                        <i class="bi bi-plus-circle me-2"></i>Add Task
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
                        <input type="text" class="form-control" id="taskSearch" placeholder="Search tasks...">
                    </div>
                </div>
                <div class="col-6 col-md-2">
                    <select class="form-select" id="projectFilter">
                        <option value="">All Projects</option>
                    </select>
                </div>
                <div class="col-6 col-md-2">
                    <select class="form-select" id="statusFilter">
                        <option value="">All Status</option>
                    </select>
                </div>
                <div class="col-6 col-md-2">
                    <select class="form-select" id="categoryFilter">
                        <option value="">All Categories</option>
                    </select>
                </div>
                <div class="col-12 col-md-2">
                    <select class="form-select" id="assigneeFilter">
                        <option value="">All Assignees</option>
                    </select>
                </div>
            </div>
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
                        <ul class="dropdown-menu" id="bulkStatusDropdown">
                            <!-- Populated by JavaScript -->
                        </ul>
                    </div>
                    @if (auth()->user()->isSuperAdmin() || auth()->user()->isAdmin() || auth()->user()->isManager())
                        <div class="dropdown">
                            <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button"
                                data-bs-toggle="dropdown">
                                <i class="bi bi-arrow-up-circle me-1"></i>Update Priority
                            </button>
                            <ul class="dropdown-menu" id="bulkPriorityDropdown">
                                <!-- Populated by JavaScript -->
                            </ul>
                        </div>
                        <div class="bulk-assignee-container" style="min-width: 200px;">
                            <label for="bulkAssigneeSelect" class="form-label small mb-1 d-block">
                                <i class="bi bi-person-plus me-1"></i>Assign To
                            </label>
                            <select class="form-select form-select-sm" id="bulkAssigneeSelect" multiple>
                                <!-- Populated by JavaScript -->
                            </select>
                            <button class="btn btn-sm btn-outline-warning mt-1" onclick="taskTableManager.bulkUpdateAssigneeFromSelect()" style="width: 100%;">
                                <i class="bi bi-check-circle me-1"></i>Apply Assignment
                            </button>
                        </div>
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
        <div class="table-responsive ">
            <table id="tasksTable" class="table table-hover mb-0">
                <thead class="table-dark">
                    <tr>
                        <th style="min-width: 50px;">
                            <input type="checkbox" id="selectAllTasks" class="form-check-input">
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
                    <tr>
                        <td colspan="14" class="text-center py-4">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div class="p-3" id="taskPagination">
            <!-- Populated by JavaScript -->
        </div>
    </div>

    <!-- Task Creation Modal -->
    <div id="taskModal" class="modal fade" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title fw-bold">
                        <i class="bi bi-plus-circle me-2"></i>Create New Task
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>

                <div id="modalAlertContainer"></div>

                <div class="modal-body">
                    <form id="taskCreateModalForm">
                        <div class="row">
                            <!-- Task Title -->
                            <div class="col-md-12 mb-3">
                                <label for="modalTaskTitle" class="form-label">Task Title <span
                                        class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="modalTaskTitle" name="title" required>
                                <div class="invalid-feedback"></div>
                            </div>

                            <!-- Project and Assignee -->
                            <div class="col-md-6 mb-3">
                                <label for="modalTaskProjectId" class="form-label">Project</label>
                                <div class="input-group">
                                    <select class="form-select" id="modalTaskProjectId" name="project_id">
                                        <option value="">Select a project (Optional)</option>
                                    </select>
                                    <button type="button" class="btn btn-outline-primary"
                                        onclick="taskTableManager.openProjectModal()" title="Add New Project">
                                        <i class="bi bi-plus-lg"></i>
                                    </button>
                                </div>
                                <div class="invalid-feedback"></div>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="modalTaskAssigneeId" class="form-label">Assign To <span
                                        class="text-danger">*</span></label>

                                <div class="d-flex flex-column gap-1">
                                    <!-- Selected Employees Display -->
                                    <div id="selectedAssigneesContainer" style="display: none;">
                                        <div class="selected-employees" id="selectedAssignees">
                                            <!-- Populated by JavaScript -->
                                        </div>
                                    </div>

                                    <!-- Select Employee Button -->
                                    <button type="button" class="btn btn-outline-primary btn-sm"
                                        onclick="taskTableManager.openEmployeeModal()" id="selectAssigneesBtn">
                                        <i class="bi bi-people me-1"></i>Select Assignees
                                    </button>
                                </div>
                                <div class="invalid-feedback"></div>
                            </div>

                            <!-- Priority and Category -->
                            <div class="col-md-6 mb-3">
                                <label for="modalTaskPriority" class="form-label">Priority <span
                                        class="text-danger">*</span></label>
                                <select class="form-select" id="modalTaskPriority" name="priority_id" required>
                                    <option value="">Select priority</option>
                                </select>
                                <div class="invalid-feedback"></div>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="modalTaskCategory" class="form-label">Category <span
                                        class="text-danger">*</span></label>
                                <div class="input-group">
                                    <select class="form-select" id="modalTaskCategory" name="category_id" required>
                                        <option value="">Select category</option>
                                    </select>
                                    <button type="button" class="btn btn-outline-primary"
                                        onclick="taskTableManager.openCategoryModal()" title="Add New Category">
                                        <i class="bi bi-plus-lg"></i>
                                    </button>
                                </div>
                                <div class="invalid-feedback"></div>
                            </div>

                            <!-- Due Date and Estimated Hours -->
                            <div class="col-md-6 mb-3">
                                <label for="modalTaskDueDate" class="form-label">Due Date</label>
                                <input type="date" class="form-control" id="modalTaskDueDate" name="due_date">
                                <div class="invalid-feedback"></div>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="modalTaskEstimatedHours" class="form-label">Estimated Hours</label>
                                <input type="number" class="form-control" id="modalTaskEstimatedHours"
                                    name="estimated_hours" min="1">
                                <div class="invalid-feedback"></div>
                            </div>

                            <!-- Nature of Task -->
                            <div class="col-md-12 mb-3">
                                <label for="modalTaskNature" class="form-label">Nature of Task <span
                                        class="text-danger">*</span></label>
                                <select class="form-select" id="modalTaskNature" name="nature" required>
                                    <option value="one_time">One Time</option>
                                    <option value="recurring">Recurring</option>
                                </select>
                                <div class="invalid-feedback"></div>
                            </div>

                            <!-- Recurrence Frequency (only show if Recurring is selected) -->
                            <div class="col-md-12 mb-3" id="recurrenceFrequencyContainer" style="display: none;">
                                <label for="modalTaskRecurrenceFrequency" class="form-label">Recurrence Frequency <span
                                        class="text-danger">*</span></label>
                                <select class="form-select" id="modalTaskRecurrenceFrequency"
                                    name="recurrence_frequency">
                                    <option value="daily">Daily</option>
                                    <option value="weekly">Weekly</option>
                                    <option value="monthly">Monthly</option>
                                </select>
                                <div class="invalid-feedback"></div>
                            </div>

                            <!-- Reminder Time -->
                            <div class="col-md-12 mb-3">
                                <label for="modalTaskReminderTime" class="form-label">Reminder Date & Time</label>
                                <input type="datetime-local" class="form-control" id="modalTaskReminderTime"
                                    name="reminder_time">
                                <div class="form-text">Set a date and time to receive a reminder for this task</div>
                                <div class="invalid-feedback"></div>
                            </div>

                            <!-- Description -->
                            <div class="col-md-12 mb-3">
                                <label for="modalTaskDescription" class="form-label">Description</label>
                                <textarea class="form-control" id="modalTaskDescription" name="description" rows="3"></textarea>
                                <div class="invalid-feedback"></div>
                            </div>

                            <!-- Attachments -->
                            <div class="col-md-12 mb-3">
                                <label for="modalTaskAttachments" class="form-label">Attachments</label>
                                <input type="file" class="form-control" id="modalTaskAttachments"
                                    name="attachments[]" multiple>
                                <div class="form-text">You can select multiple files. Maximum file size: 10MB per file.
                                </div>
                                <div class="invalid-feedback"></div>
                            </div>
                        </div>
                    </form>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" id="createTaskCancelBtn">
                        <i class="bi bi-x-circle me-2"></i>Cancel
                    </button>
                    <button type="button" class="btn btn-primary" onclick="taskTableManager.createTaskFromModal()"
                        id="createTaskSubmitBtn">
                        <span class="spinner-border spinner-border-sm me-2 d-none" id="createTaskSpinner" role="status"
                            aria-hidden="true"></span>
                        <i class="bi bi-check-circle me-2" id="createTaskIcon"></i>
                        <span id="createTaskText">Create Task</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Task Edit Modal -->
    <div id="editTaskModal" class="modal fade" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header bg-warning text-dark">
                    <h5 class="modal-title fw-bold">
                        <i class="bi bi-pencil-circle me-2"></i>Edit Task
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div id="editModalAlertContainer"></div>

                <div class="modal-body">
                    <form id="taskEditModalForm">
                        <input type="hidden" id="editModalTaskId" name="task_id">
                        <div class="row">
                            <!-- Task Title -->
                            <div class="col-md-12 mb-3">
                                <label for="editModalTaskTitle" class="form-label">Task Title <span
                                        class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="editModalTaskTitle" name="title"
                                    required>
                                <div class="invalid-feedback"></div>
                            </div>

                            <!-- Project and Assignee -->
                            <div class="col-md-6 mb-3">
                                <label for="editModalTaskProjectId" class="form-label">Project</label>
                                <div class="input-group">
                                    <select class="form-select" id="editModalTaskProjectId" name="project_id">
                                        <option value="">Select a project (Optional)</option>
                                    </select>
                                    <button type="button" class="btn btn-outline-primary"
                                        onclick="taskTableManager.openProjectModal()" title="Add New Project">
                                        <i class="bi bi-plus-lg"></i>
                                    </button>
                                </div>
                                <div class="invalid-feedback"></div>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="editModalTaskAssigneeId" class="form-label">Assign To <span
                                        class="text-danger">*</span></label>

                                <div class="d-flex flex-column gap-1">
                                    <!-- Selected Employees Display -->
                                    <div id="editSelectedAssigneesContainer" style="display: none;">
                                        <div class="selected-employees" id="editSelectedAssignees">
                                            <!-- Populated by JavaScript -->
                                        </div>
                                    </div>

                                    <!-- Select Employee Button -->
                                    <button type="button" class="btn btn-outline-primary btn-sm"
                                        onclick="taskTableManager.openEmployeeModalForEdit()" id="editSelectAssigneesBtn">
                                        <i class="bi bi-people me-1"></i>Select Assignees
                                    </button>
                                </div>
                                <div class="invalid-feedback"></div>
                            </div>

                            <!-- Priority and Category -->
                            <div class="col-md-6 mb-3">
                                <label for="editModalTaskPriority" class="form-label">Priority <span
                                        class="text-danger">*</span></label>
                                <select class="form-select" id="editModalTaskPriority" name="priority_id" required>
                                    <option value="">Select priority</option>
                                </select>
                                <div class="invalid-feedback"></div>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="editModalTaskCategory" class="form-label">Category <span
                                        class="text-danger">*</span></label>
                                <div class="input-group">
                                    <select class="form-select" id="editModalTaskCategory" name="category_id" required>
                                        <option value="">Select category</option>
                                    </select>
                                    <button type="button" class="btn btn-outline-primary"
                                        onclick="taskTableManager.openCategoryModal()" title="Add New Category">
                                        <i class="bi bi-plus-lg"></i>
                                    </button>
                                </div>
                                <div class="invalid-feedback"></div>
                            </div>

                            <!-- Due Date and Estimated Hours -->
                            <div class="col-md-6 mb-3">
                                <label for="editModalTaskDueDate" class="form-label">Due Date</label>
                                <input type="date" class="form-control" id="editModalTaskDueDate" name="due_date">
                                <div class="invalid-feedback"></div>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="editModalTaskEstimatedHours" class="form-label">Estimated Hours</label>
                                <input type="number" class="form-control" id="editModalTaskEstimatedHours"
                                    name="estimated_hours" min="0">
                                <div class="invalid-feedback"></div>
                            </div>

                            <!-- Nature of Task -->
                            <div class="col-md-12 mb-3">
                                <label for="editModalTaskNature" class="form-label">Nature of Task <span
                                        class="text-danger">*</span></label>
                                <select class="form-select" id="editModalTaskNature" name="nature" required>
                                    <option value="one_time">One Time</option>
                                    <option value="recurring">Recurring</option>
                                </select>
                                <div class="invalid-feedback"></div>
                            </div>

                            <!-- Recurrence Frequency (only show if Recurring is selected) -->
                            <div class="col-md-12 mb-3" id="editRecurrenceFrequencyContainer" style="display: none;">
                                <label for="editModalTaskRecurrenceFrequency" class="form-label">Recurrence Frequency
                                    <span class="text-danger">*</span></label>
                                <select class="form-select" id="editModalTaskRecurrenceFrequency"
                                    name="recurrence_frequency">
                                    <option value="">Select frequency</option>
                                    <option value="daily">Daily</option>
                                    <option value="weekly">Weekly</option>
                                    <option value="monthly">Monthly</option>
                                </select>
                                <div class="invalid-feedback"></div>
                            </div>

                            <!-- Reminder Time -->
                            <div class="col-md-12 mb-3">
                                <label for="editModalTaskReminderTime" class="form-label">Reminder Date & Time</label>
                                <input type="datetime-local" class="form-control" id="editModalTaskReminderTime"
                                    name="reminder_time">
                                <div class="form-text">Set a date and time to receive a reminder for this task</div>
                                <div class="invalid-feedback"></div>
                            </div>

                            <!-- Description -->
                            <div class="col-md-12 mb-3">
                                <label for="editModalTaskDescription" class="form-label">Description</label>
                                <textarea class="form-control" id="editModalTaskDescription" name="description" rows="3"></textarea>
                                <div class="invalid-feedback"></div>
                            </div>

                            <!-- Attachments -->
                            <div class="col-md-12 mb-3">
                                <label for="editModalTaskAttachments" class="form-label">Add New Attachments</label>
                                <input type="file" class="form-control" id="editModalTaskAttachments"
                                    name="attachments[]" multiple>
                                <div class="form-text">You can select multiple files. Maximum file size: 10MB per file.
                                </div>
                                <div class="invalid-feedback"></div>
                            </div>
                        </div>
                    </form>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" id="updateTaskCancelBtn">
                        <i class="bi bi-x-circle me-2"></i>Cancel
                    </button>
                    <button type="button" class="btn btn-warning" onclick="taskTableManager.updateTaskFromModal()"
                        id="updateTaskSubmitBtn">
                        <span class="spinner-border spinner-border-sm me-2 d-none" id="updateTaskSpinner" role="status"
                            aria-hidden="true"></span>
                        <i class="bi bi-save me-2" id="updateTaskIcon"></i>
                        <span id="updateTaskText">Update Task</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Task Clone Modal -->
    <div id="cloneTaskModal" class="modal fade" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-info text-white">
                    <h5 class="modal-title fw-bold">
                        <i class="bi bi-files me-2"></i>Clone Task
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>

                <div id="cloneModalAlertContainer"></div>

                <div class="modal-body">
                    <form id="cloneTaskModalForm">
                        <input type="hidden" id="cloneModalTaskId" name="task_id">

                        <div class="alert alert-info">
                            <i class="bi bi-info-circle me-2"></i>
                            Please select a due date for the cloned task.
                        </div>

                        <div class="mb-3">
                            <label for="cloneModalDueDate" class="form-label">
                                <strong>New Due Date <span class="text-danger">*</span></strong>
                            </label>
                            <input type="date" class="form-control" id="cloneModalDueDate" name="due_date"
                                min="{{ date('Y-m-d', strtotime('+1 day')) }}" required>
                            <div class="invalid-feedback"></div>
                            <div class="form-text text-muted">
                                <i class="bi bi-calendar me-1"></i>Select a date after today
                            </div>
                        </div>
                    </form>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" id="cloneTaskCancelBtn">
                        <i class="bi bi-x-circle me-2"></i>Cancel
                    </button>
                    <button type="button" class="btn btn-info text-white" onclick="taskTableManager.submitCloneTask()"
                        id="cloneTaskSubmitBtn">
                        <span class="spinner-border spinner-border-sm me-2 d-none" id="cloneTaskSpinner" role="status"
                            aria-hidden="true"></span>
                        <i class="bi bi-files me-2" id="cloneTaskIcon"></i>
                        <span id="cloneTaskText">Clone Task</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Employee Selection Modal -->
    <div id="employeeModal" class="modal fade" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title fw-bold">
                        <i class="bi bi-people me-2"></i>Select Assignees
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <!-- Selected Employees Display -->
                    <div id="selectedEmployeesDisplay" class="mb-3" style="display: none;">
                        <h6 class="fw-bold">Selected Assignees:</h6>
                        <div class="selected-employees" id="selectedEmployeesBadges">
                            <!-- Populated by JavaScript -->
                        </div>
                    </div>

                    <!-- Search Box -->
                    <div class="mb-3">
                        <div class="input-group">
                            <span class="input-group-text">
                                <i class="bi bi-search"></i>
                            </span>
                            <input type="text" class="form-control" id="employeeSearch"
                                placeholder="Search employees by name...">
                        </div>
                    </div>

                    <!-- Employee List -->
                    <div class="employee-list" id="employeeList" style="max-height: 400px; overflow-y: auto;">
                        <!-- Populated by JavaScript -->
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary"
                        onclick="taskTableManager.confirmEmployeeSelection()">
                        <i class="bi bi-x-circle me-2"></i>Done
                    </button>
                </div>
            </div>
        </div>
    </div>

    @push('styles')
        <!-- Select2 CSS -->
        <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
        <link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />
    @endpush

    @push('scripts')
        <!-- Select2 JS -->
        <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
        <script src="{{ asset('js/ajax-task-table.js') }}"></script>
        <script>
            // Bulk dropdowns are now populated automatically when dropdown data loads
            // and when bulk actions section is shown (handled in updateBulkActions method)

                // Ensure modal loads data when shown
                const taskModal = document.getElementById('taskModal');
                if (taskModal) {
                    taskModal.addEventListener('show.bs.modal', function() {
                        if (typeof taskTableManager !== 'undefined') {
                            if (typeof taskTableManager.loadDropdownData === 'function') {
                                taskTableManager.loadDropdownData();
                            }
                        }
                    });
                }

                // Edit modal event listeners
                const editTaskModal = document.getElementById('editTaskModal');
                if (editTaskModal) {
                    editTaskModal.addEventListener('show.bs.modal', function() {
                        if (typeof taskTableManager !== 'undefined') {
                            if (typeof taskTableManager.loadDropdownData === 'function') {
                                taskTableManager.loadDropdownData();
                            }
                        }
                    });

                    // Handle nature of task change in edit modal
                    const editNatureSelect = document.getElementById('editModalTaskNature');
                    if (editNatureSelect) {
                        editNatureSelect.addEventListener('change', function() {
                            const recurrenceContainer = document.getElementById(
                                'editRecurrenceFrequencyContainer');
                            const recurrenceField = document.getElementById('editModalTaskRecurrenceFrequency');
                            if (this.value === 'recurring') {
                                recurrenceContainer.style.display = 'block';
                                if (recurrenceField) {
                                    recurrenceField.setAttribute('required', 'required');
                                }
                            } else {
                                recurrenceContainer.style.display = 'none';
                                if (recurrenceField) {
                                    recurrenceField.removeAttribute('required');
                                    recurrenceField.classList.remove('is-invalid');
                                }
                            }
                        });
                    }

                    // Reset edit modal when closed
                    editTaskModal.addEventListener('hidden.bs.modal', function() {
                        if (typeof taskTableManager !== 'undefined') {
                            taskTableManager.resetEditModal();
                        }
                    });
                }

                // Clone modal event listeners
                const cloneTaskModal = document.getElementById('cloneTaskModal');
                if (cloneTaskModal) {
                    // Reset clone modal when closed
                    cloneTaskModal.addEventListener('hidden.bs.modal', function() {
                        if (typeof taskTableManager !== 'undefined') {
                            const form = document.getElementById('cloneTaskModalForm');
                            if (form) {
                                form.reset();
                                form.querySelectorAll('.is-invalid').forEach(el => el.classList.remove(
                                    'is-invalid'));
                                form.querySelectorAll('.invalid-feedback').forEach(el => el.textContent = '');
                            }
                            taskTableManager.clearCloneModalAlerts();
                            taskTableManager.showCloneTaskLoader(false);
                        }
                    });
                }
            });
        </script>
    @endpush
@endsection
