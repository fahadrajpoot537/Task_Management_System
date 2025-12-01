@extends('layouts.app')

@section('content')
<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Tasks (AJAX)</h2>
        <button type="button" class="btn btn-primary" onclick="taskIndexManager.openTaskModal()">
            <i class="bi bi-plus-circle me-2"></i>Create Task
        </button>
    </div>

    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-4">
                    <input type="text" class="form-control" id="taskSearch" placeholder="Search tasks...">
                </div>
                <div class="col-md-3">
                    <select class="form-select" id="statusFilter">
                        <option value="">All Status</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <select class="form-select" id="userFilter">
                        <option value="">All Users</option>
                    </select>
                </div>
            </div>
        </div>
    </div>

    <!-- Tasks List -->
    <div id="tasksContainer">
        <div class="text-center py-4">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
        </div>
    </div>

    <!-- Pagination -->
    <div id="paginationContainer" class="mt-4">
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
                            <label for="modalTaskTitle" class="form-label">Task Title <span class="text-danger">*</span></label>
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
                                <button type="button" class="btn btn-outline-primary" onclick="taskIndexManager.openProjectModal()" title="Add New Project">
                                    <i class="bi bi-plus-lg"></i>
                                </button>
                            </div>
                            <div class="invalid-feedback"></div>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="modalTaskAssigneeId" class="form-label">Assign To <span class="text-danger">*</span></label>

                            <!-- Selected Assignees Display -->
                            <div id="selectedAssigneesContainer" class="mb-2" style="display: none;">
                                <div class="selected-employees" id="selectedAssignees">
                                    <!-- Populated by JavaScript -->
                                </div>
                            </div>

                            <div class="input-group">
                                <input type="text" class="form-control" id="modalTaskAssigneeId" placeholder="Click to select assignees" readonly>
                                <button type="button" class="btn btn-outline-primary" onclick="taskIndexManager.openEmployeeModal()">
                                    <i class="bi bi-people me-1"></i>Select Assignees
                                </button>
                            </div>
                            <div class="invalid-feedback"></div>
                        </div>

                        <!-- Priority and Category -->
                        <div class="col-md-6 mb-3">
                            <label for="modalTaskPriority" class="form-label">Priority <span class="text-danger">*</span></label>
                            <select class="form-select" id="modalTaskPriority" name="priority_id" required>
                                <option value="">Select priority</option>
                            </select>
                            <div class="invalid-feedback"></div>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="modalTaskCategory" class="form-label">Category <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <select class="form-select" id="modalTaskCategory" name="category_id" required>
                                    <option value="">Select category</option>
                                </select>
                                <button type="button" class="btn btn-outline-primary" onclick="taskIndexManager.openCategoryModal()" title="Add New Category">
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
                            <input type="number" class="form-control" id="modalTaskEstimatedHours" name="estimated_hours" min="1">
                            <div class="invalid-feedback"></div>
                        </div>

                        <!-- Nature of Task -->
                        <div class="col-md-12 mb-3">
                            <label for="modalTaskNature" class="form-label">Nature of Task <span class="text-danger">*</span></label>
                            <select class="form-select" id="modalTaskNature" name="nature" required>
                                <option value="one_time">One Time</option>
                                <option value="recurring">Recurring</option>
                            </select>
                            <div class="invalid-feedback"></div>
                        </div>

                        <!-- Recurrence Frequency (only show if Recurring is selected) -->
                        <div class="col-md-12 mb-3" id="recurrenceFrequencyContainer" style="display: none;">
                            <label for="modalTaskRecurrenceFrequency" class="form-label">Recurrence Frequency <span class="text-danger">*</span></label>
                            <select class="form-select" id="modalTaskRecurrenceFrequency" name="recurrence_frequency">
                                <option value="daily">Daily</option>
                                <option value="weekly">Weekly</option>
                                <option value="monthly">Monthly</option>
                            </select>
                            <div class="invalid-feedback"></div>
                        </div>

                        <!-- Reminder Time -->
                        <div class="col-md-12 mb-3">
                            <label for="modalTaskReminderTime" class="form-label">Reminder Date & Time</label>
                            <input type="datetime-local" class="form-control" id="modalTaskReminderTime" name="reminder_time">
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
                            <input type="file" class="form-control" id="modalTaskAttachments" name="attachments[]" multiple>
                            <div class="form-text">You can select multiple files. Maximum file size: 10MB per file.</div>
                            <div class="invalid-feedback"></div>
                        </div>
                    </div>
                </form>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="bi bi-x-circle me-2"></i>Cancel
                </button>
                <button type="button" class="btn btn-primary" onclick="taskIndexManager.createTaskFromModal()">
                    <i class="bi bi-check-circle me-2"></i>Create Task
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Employee Selection Modal -->
<div id="employeeModal" class="modal fade" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Select Assignees</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <input type="text" class="form-control" id="employeeSearch" placeholder="Search employees...">
                </div>
                <div id="employeeList" style="max-height: 400px; overflow-y: auto;">
                    <!-- Populated by JavaScript -->
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" onclick="taskIndexManager.confirmEmployeeSelection()">Confirm</button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script src="{{ asset('js/ajax-task-index.js') }}"></script>
@endpush
@endsection

