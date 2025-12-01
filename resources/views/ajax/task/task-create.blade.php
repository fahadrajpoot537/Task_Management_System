@extends('layouts.app')

@section('content')
<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Create New Task (AJAX)</h2>
        <a href="{{ route('tasks.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-2"></i>Back to Tasks
        </a>
    </div>

    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-body">
                    <form id="taskCreateForm">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="project_id" class="form-label">Project <span class="text-danger">*</span></label>
                                <select class="form-select" id="project_id" name="project_id" required>
                                    <option value="">Select a project</option>
                                </select>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="assigned_to_user_id" class="form-label">Assign To <span class="text-danger">*</span></label>
                                <select class="form-select" id="assigned_to_user_id" name="assigned_to_user_id" required>
                                    <option value="">Select User</option>
                                </select>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="title" class="form-label">Task Title <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="title" name="title" required autofocus>
                        </div>

                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control" id="description" name="description" rows="4" placeholder="Describe the task..."></textarea>
                        </div>

                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label for="priority_id" class="form-label">Priority <span class="text-danger">*</span></label>
                                <select class="form-select" id="priority_id" name="priority_id" required>
                                    <option value="">Select Priority</option>
                                </select>
                            </div>

                            <div class="col-md-4 mb-3">
                                <label for="category_id" class="form-label">Category <span class="text-danger">*</span></label>
                                <select class="form-select" id="category_id" name="category_id" required>
                                    <option value="">Select Category</option>
                                </select>
                            </div>

                            <div class="col-md-4 mb-3">
                                <label for="status_id" class="form-label">Status</label>
                                <select class="form-select" id="status_id" name="status_id">
                                    <option value="">Select Status</option>
                                </select>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="due_date" class="form-label">Due Date</label>
                                <input type="date" class="form-control" id="due_date" name="due_date">
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="duration" class="form-label">Duration (hours)</label>
                                <input type="number" class="form-control" id="duration" name="duration" min="1">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="nature_of_task" class="form-label">Nature of Task <span class="text-danger">*</span></label>
                            <select class="form-select" id="nature_of_task" name="nature_of_task" required>
                                <option value="daily">Daily</option>
                                <option value="weekly">Weekly</option>
                                <option value="monthly">Monthly</option>
                                <option value="until_stop">Until Stop</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="notes" class="form-label">Notes</label>
                            <textarea class="form-control" id="notes" name="notes" rows="3" placeholder="Additional notes..."></textarea>
                        </div>

                        <div class="mb-3">
                            <label for="attachments" class="form-label">Attachments</label>
                            <input type="file" class="form-control" id="attachments" name="attachments[]" multiple>
                            <small class="text-muted">Max 10MB per file</small>
                        </div>

                        <div class="d-flex justify-content-end gap-2">
                            <a href="{{ route('tasks.index') }}" class="btn btn-secondary">Cancel</a>
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

@push('scripts')
<script src="{{ asset('js/ajax-task-create.js') }}"></script>
@endpush
@endsection

