@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    <!-- Header Section -->
    <div class="card mb-4">
        <div class="card-header bg-gradient-primary text-white">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2 class="mb-2 text-white fw-bold">
                        <i class="bi bi-tags me-3"></i>Project Status Management
                    </h2>
                    <p class="mb-0 text-white-50 fs-6">Create and manage statuses for your projects</p>
                </div>
                <div class="d-flex gap-3">
                    <button type="button" class="btn btn-light btn-lg px-4 py-2" data-bs-toggle="modal" data-bs-target="#statusModal" onclick="openCreateModal()">
                        <i class="bi bi-plus-circle me-2"></i>Create Status
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Search and Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-4">
                    <div class="input-group">
                        <span class="input-group-text bg-light border-end-0">
                            <i class="bi bi-search text-primary"></i>
                        </span>
                        <input type="text" class="form-control border-start-0" id="searchInput" placeholder="Search statuses...">
                    </div>
                </div>
                <div class="col-md-3">
                    <select class="form-select" id="projectFilter">
                        <option value="">All Projects</option>
                        @foreach($projects as $project)
                            <option value="{{ $project->id }}">{{ $project->title }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <select class="form-select" id="sortField">
                        <option value="order">Sort by Order</option>
                        <option value="name">Sort by Name</option>
                        <option value="created_at">Sort by Date</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <select class="form-select" id="sortDirection">
                        <option value="asc">Ascending</option>
                        <option value="desc">Descending</option>
                    </select>
                </div>
            </div>
        </div>
    </div>

    <!-- Statuses Table -->
    <div class="card">
        <div class="card-body">
            <div class="">
                <table class="table table-hover" id="statusesTable">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Project</th>
                            <th>Color</th>
                            <th>Order</th>
                            <th>Default</th>
                            <th>Leads Count</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="statusesTableBody">
                        <tr>
                            <td colspan="8" class="text-center">
                                <div class="spinner-border text-primary" role="status">
                                    <span class="visually-hidden">Loading...</span>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <!-- Pagination -->
            <div class="d-flex justify-content-between align-items-center mt-3">
                <div id="paginationInfo"></div>
                <nav>
                    <ul class="pagination mb-0" id="paginationLinks"></ul>
                </nav>
            </div>
        </div>
    </div>
</div>

<!-- Status Modal -->
<div class="modal fade" id="statusModal" tabindex="-1" aria-labelledby="statusModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="statusModalLabel">Create Status</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="statusForm">
                <div class="modal-body">
                    <input type="hidden" id="statusId" name="status_id">
                    
                    <div class="mb-3">
                        <label for="project_id" class="form-label">Project <span class="text-danger">*</span></label>
                        <select class="form-select" id="project_id" name="project_id" required>
                            <option value="">Select Project</option>
                            @foreach($projects as $project)
                                <option value="{{ $project->id }}">{{ $project->title }}</option>
                            @endforeach
                        </select>
                        <div class="invalid-feedback"></div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="name" class="form-label">Status Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="name" name="name" placeholder="Enter status name" required>
                        <div class="invalid-feedback"></div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="color" class="form-label">Color</label>
                        <select class="form-select" id="color" name="color">
                            <option value="primary">Primary (Blue)</option>
                            <option value="secondary" selected>Secondary (Gray)</option>
                            <option value="success">Success (Green)</option>
                            <option value="danger">Danger (Red)</option>
                            <option value="warning">Warning (Yellow)</option>
                            <option value="info">Info (Cyan)</option>
                            <option value="light">Light</option>
                            <option value="dark">Dark</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="order" class="form-label">Order</label>
                        <input type="number" class="form-control" id="order" name="order" placeholder="0" value="0" min="0">
                        <small class="form-text text-muted">Lower numbers appear first</small>
                    </div>
                    
                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="is_default" name="is_default" value="1">
                            <label class="form-check-label" for="is_default">
                                Set as Default Status
                            </label>
                            <small class="form-text text-muted d-block">Only one default status per project</small>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Status</button>
                </div>
            </form>
        </div>
    </div>
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
                <p>Are you sure you want to delete this status? This action cannot be undone.</p>
                <p class="text-danger"><strong>Note:</strong> If this status is used by any leads, those leads will have their status set to null.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="confirmDeleteBtn">Delete</button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
let currentPage = 1;
let statusIdToDelete = null;
let editingStatusId = null;

// CSRF Token setup
$.ajaxSetup({
    headers: {
        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
    }
});

$(document).ready(function() {
    loadStatuses();
    
    // Search functionality
    let searchTimeout;
    $('#searchInput').on('input', function() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(function() {
            currentPage = 1;
            loadStatuses();
        }, 500);
    });
    
    // Filter and sort changes
    $('#projectFilter, #sortField, #sortDirection').on('change', function() {
        currentPage = 1;
        loadStatuses();
    });
    
    // Form submission
    $('#statusForm').on('submit', function(e) {
        e.preventDefault();
        saveStatus();
    });
});

function loadStatuses() {
    const search = $('#searchInput').val();
    const projectId = $('#projectFilter').val();
    const sortField = $('#sortField').val();
    const sortDirection = $('#sortDirection').val();
    
    $.ajax({
        url: '/statuses',
        method: 'GET',
        data: {
            search: search,
            project_id: projectId,
            sort_field: sortField,
            sort_direction: sortDirection,
            per_page: 10,
            page: currentPage
        },
        success: function(response) {
            if (response.success) {
                renderStatuses(response.statuses);
                renderPagination(response.pagination);
            }
        },
        error: function() {
            Swal.fire('Error!', 'Failed to load statuses.', 'error');
        }
    });
}

function renderStatuses(statuses) {
    const tbody = $('#statusesTableBody');
    tbody.empty();
    
    if (statuses.length === 0) {
        tbody.append('<tr><td colspan="8" class="text-center">No statuses found.</td></tr>');
        return;
    }
    
    statuses.forEach(status => {
        const row = `
            <tr>
                <td>${status.id}</td>
                <td><span class="badge bg-${status.color || 'secondary'}">${status.name}</span></td>
                <td>${status.project ? status.project.title : '-'}</td>
                <td><span class="badge bg-${status.color || 'secondary'}">${status.color || 'secondary'}</span></td>
                <td>${status.order || 0}</td>
                <td>${status.is_default ? '<span class="badge bg-success">Yes</span>' : '<span class="badge bg-secondary">No</span>'}</td>
                <td>${status.leads_count ? status.leads_count : 0}</td>
                <td>
                    <button class="btn btn-sm btn-primary me-1" onclick="editStatus(${status.id})" title="Edit">
                        <i class="bi bi-pencil"></i>
                    </button>
                    <button class="btn btn-sm btn-danger" onclick="confirmDelete(${status.id})" title="Delete">
                        <i class="bi bi-trash"></i>
                    </button>
                </td>
            </tr>
        `;
        tbody.append(row);
    });
}

function renderPagination(pagination) {
    $('#paginationInfo').text(`Showing ${((pagination.current_page - 1) * pagination.per_page) + 1} to ${Math.min(pagination.current_page * pagination.per_page, pagination.total)} of ${pagination.total} entries`);
    
    const paginationLinks = $('#paginationLinks');
    paginationLinks.empty();
    
    if (pagination.last_page <= 1) return;
    
    // Previous button
    paginationLinks.append(`
        <li class="page-item ${pagination.current_page === 1 ? 'disabled' : ''}">
            <a class="page-link" href="javascript:void(0);" onclick="changePage(${pagination.current_page - 1})">Previous</a>
        </li>
    `);
    
    // Page numbers
    for (let i = 1; i <= pagination.last_page; i++) {
        if (i === 1 || i === pagination.last_page || (i >= pagination.current_page - 2 && i <= pagination.current_page + 2)) {
            paginationLinks.append(`
                <li class="page-item ${i === pagination.current_page ? 'active' : ''}">
                    <a class="page-link" href="javascript:void(0);" onclick="changePage(${i})">${i}</a>
                </li>
            `);
        } else if (i === pagination.current_page - 3 || i === pagination.current_page + 3) {
            paginationLinks.append('<li class="page-item disabled"><span class="page-link">...</span></li>');
        }
    }
    
    // Next button
    paginationLinks.append(`
        <li class="page-item ${pagination.current_page === pagination.last_page ? 'disabled' : ''}">
            <a class="page-link" href="javascript:void(0);" onclick="changePage(${pagination.current_page + 1})">Next</a>
        </li>
    `);
}

function changePage(page) {
    currentPage = page;
    loadStatuses();
}

function openCreateModal() {
    editingStatusId = null;
    $('#statusModalLabel').text('Create Status');
    $('#statusForm')[0].reset();
    $('#statusId').val('');
    $('#is_default').prop('checked', false);
    $('.invalid-feedback').text('');
    $('.form-control, .form-select').removeClass('is-invalid');
}

function editStatus(id) {
    editingStatusId = id;
    $.ajax({
        url: `/statuses/${id}/edit`,
        method: 'GET',
        success: function(response) {
            if (response.success) {
                const status = response.status;
                $('#statusModalLabel').text('Edit Status');
                $('#statusId').val(status.id);
                $('#project_id').val(status.project_id);
                $('#name').val(status.name);
                $('#color').val(status.color || 'secondary');
                $('#order').val(status.order || 0);
                $('#is_default').prop('checked', status.is_default || false);
                
                $('.invalid-feedback').text('');
                $('.form-control, .form-select').removeClass('is-invalid');
                
                new bootstrap.Modal(document.getElementById('statusModal')).show();
            }
        },
        error: function() {
            Swal.fire('Error!', 'Failed to load status data.', 'error');
        }
    });
}

function saveStatus() {
    // Validate required fields first
    if (!$('#project_id').val()) {
        Swal.fire('Error!', 'Please select a project.', 'error');
        $('#project_id').addClass('is-invalid');
        return;
    }
    
    if (!$('#name').val().trim()) {
        Swal.fire('Error!', 'Please enter a status name.', 'error');
        $('#name').addClass('is-invalid');
        return;
    }
    
    const formData = {
        project_id: $('#project_id').val(),
        name: $('#name').val().trim(),
        color: $('#color').val() || 'secondary',
        order: parseInt($('#order').val()) || 0,
        is_default: $('#is_default').is(':checked') ? 1 : 0
    };
    
    let url = '/statuses';
    let method = 'POST';
    
    if (editingStatusId) {
        url = `/statuses/${editingStatusId}`;
        formData._method = 'PUT';
    }
    
    console.log('Sending request to:', url);
    console.log('Method:', method);
    console.log('Form data:', formData);
    
    $.ajax({
        url: url,
        method: method,
        data: formData,
        success: function(response) {
            console.log('Success response:', response);
            if (response.success) {
                Swal.fire({
                    icon: 'success',
                    title: 'Success',
                    text: response.message,
                    timer: 2000,
                    showConfirmButton: false
                });
                
                bootstrap.Modal.getInstance(document.getElementById('statusModal')).hide();
                loadStatuses();
            }
        },
        error: function(xhr) {
            console.error('Error response:', xhr);
            if (xhr.status === 422) {
                const errors = xhr.responseJSON.errors;
                // Clear previous errors
                $('.form-control, .form-select').removeClass('is-invalid');
                $('.invalid-feedback').text('');
                
                $.each(errors, function(key, value) {
                    const field = $(`#${key}`);
                    field.addClass('is-invalid');
                    const feedback = field.siblings('.invalid-feedback');
                    if (feedback.length) {
                        feedback.text(value[0]);
                    } else {
                        field.after('<div class="invalid-feedback">' + value[0] + '</div>');
                    }
                });
                
                Swal.fire('Validation Error!', 'Please check the form fields.', 'error');
            } else {
                const errorMsg = xhr.responseJSON?.message || 'Failed to save status.';
                Swal.fire('Error!', errorMsg, 'error');
            }
        }
    });
}

function confirmDelete(id) {
    statusIdToDelete = id;
    new bootstrap.Modal(document.getElementById('deleteModal')).show();
}

$('#confirmDeleteBtn').on('click', function() {
    if (statusIdToDelete) {
        $.ajax({
            url: `/statuses/${statusIdToDelete}`,
            method: 'DELETE',
            success: function(response) {
                if (response.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Success',
                        text: response.message,
                        timer: 2000,
                        showConfirmButton: false
                    });
                    
                    bootstrap.Modal.getInstance(document.getElementById('deleteModal')).hide();
                    loadStatuses();
                }
            },
            error: function() {
                Swal.fire('Error!', 'Failed to delete status.', 'error');
            }
        });
    }
});
</script>
@endpush
@endsection

