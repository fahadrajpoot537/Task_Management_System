@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    <!-- Header Section -->
    <div class="card mb-4">
        <div class="card-header bg-gradient-primary text-white">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2 class="mb-2 text-white fw-bold">
                        <i class="bi bi-person-lines-fill me-3"></i>Lead Management
                    </h2>
                    <p class="mb-0 text-white-50 fs-6">Create, manage, and track your leads efficiently</p>
                </div>
                <div class="d-flex gap-3">
                    <button type="button" class="btn btn-light btn-lg px-4 py-2" data-bs-toggle="modal" data-bs-target="#leadModal" onclick="openCreateModal()">
                        <i class="bi bi-plus-circle me-2"></i>Create Lead
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
                        <input type="text" class="form-control border-start-0" id="searchInput" placeholder="Search leads...">
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
                        <option value="created_at">Sort by Date</option>
                        <option value="first_name">Sort by Name</option>
                        <option value="company">Sort by Company</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <select class="form-select" id="sortDirection">
                        <option value="desc">Newest First</option>
                        <option value="asc">Oldest First</option>
                    </select>
                </div>
            </div>
        </div>
    </div>

    <!-- Leads Table -->
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover" id="leadsTable">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Company</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Project</th>
                            <th>Status</th>
                            <th>Received Date</th>
                            <th>Added By</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="leadsTableBody">
                        <tr>
                            <td colspan="10" class="text-center">
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

<!-- Lead Modal -->
<div class="modal fade" id="leadModal" tabindex="-1" aria-labelledby="leadModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="leadModalLabel">Create Lead</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="leadForm">
                <div class="modal-body">
                    <input type="hidden" id="leadId" name="lead_id">
                    
                    <div class="row g-3 mb-3">
                        <div class="col-12">
                            <label for="project_id" class="form-label">Project <span class="text-danger">*</span></label>
                            <select class="form-select" id="project_id" name="project_id" required>
                                <option value="">Select Project</option>
                                @foreach($projects as $project)
                                    <option value="{{ $project->id }}" {{ request('project_id') == $project->id ? 'selected' : '' }}>{{ $project->title }}</option>
                                @endforeach
                            </select>
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="col-12">
                            <label for="status_id" class="form-label">Status</label>
                            <select class="form-select" id="status_id" name="status_id">
                                <option value="">Select Status</option>
                            </select>
                            <div class="invalid-feedback"></div>
                        </div>
                    </div>
                    
                    <h5 class="mb-3">Contact Details</h5>
                    
                    <div class="row g-3">
                        <div class="col-12">
                            <label for="title_first_name" class="form-label">Title & First Name:</label>
                            <div class="row g-2">
                                <div class="col-md-3">
                                    <select class="form-select" id="title" name="title">
                                        <option value="">Select...</option>
                                        <option value="Mr">Mr</option>
                                        <option value="Mrs">Mrs</option>
                                        <option value="Miss">Miss</option>
                                        <option value="Ms">Ms</option>
                                        <option value="Dr">Dr</option>
                                        <option value="Prof">Prof</option>
                                    </select>
                                </div>
                                <div class="col-md-9">
                                    <input type="text" class="form-control" id="first_name" name="first_name" placeholder="First Name" required>
                                    <div class="invalid-feedback"></div>
                                </div>
                            </div>
                        </div>
                        <div class="col-12">
                            <label for="last_name" class="form-label">Last Name:</label>
                            <input type="text" class="form-control" id="last_name" name="last_name">
                        </div>
                        <div class="col-12">
                            <label for="company" class="form-label">Company:</label>
                            <input type="text" class="form-control" id="company" name="company">
                        </div>
                        <div class="col-12">
                            <label for="phone" class="form-label">Phone Number:</label>
                            <input type="text" class="form-control" id="phone" name="phone">
                        </div>
                        <div class="col-12">
                            <label for="alternative_phone" class="form-label">Alternative Phone Number:</label>
                            <input type="text" class="form-control" id="alternative_phone" name="alternative_phone">
                        </div>
                        <div class="col-12">
                            <label for="email" class="form-label">Email Address:</label>
                            <input type="email" class="form-control" id="email" name="email">
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="col-12">
                            <label for="address" class="form-label">Address:</label>
                            <input type="text" class="form-control mb-2" id="address_line1" name="address_line1" placeholder="Address Line 1">
                            <input type="text" class="form-control mb-2" id="address_line2" name="address_line2" placeholder="Address Line 2">
                            <input type="text" class="form-control" id="address_line3" name="address_line3" placeholder="Address Line 3">
                            <input type="hidden" id="address" name="address">
                        </div>
                        <div class="col-12">
                            <label for="city" class="form-label">Town/City:</label>
                            <input type="text" class="form-control" id="city" name="city">
                        </div>
                        <div class="col-12">
                            <label for="postcode" class="form-label">Postcode:</label>
                            <input type="text" class="form-control" id="postcode" name="postcode">
                        </div>
                        <div class="col-12">
                            <label for="date_of_birth" class="form-label">Date of Birth:</label>
                            <div class="row g-2">
                                <div class="col-md-4">
                                    <select class="form-select" id="dob_day" name="dob_day">
                                        <option value="">dd</option>
                                        @for($i = 1; $i <= 31; $i++)
                                            <option value="{{ str_pad($i, 2, '0', STR_PAD_LEFT) }}">{{ str_pad($i, 2, '0', STR_PAD_LEFT) }}</option>
                                        @endfor
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <select class="form-select" id="dob_month" name="dob_month">
                                        <option value="">mm</option>
                                        @for($i = 1; $i <= 12; $i++)
                                            <option value="{{ str_pad($i, 2, '0', STR_PAD_LEFT) }}">{{ str_pad($i, 2, '0', STR_PAD_LEFT) }}</option>
                                        @endfor
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <input type="text" class="form-control" id="dob_year" name="dob_year" placeholder="Year" maxlength="4">
                                </div>
                            </div>
                            <input type="hidden" id="date_of_birth" name="date_of_birth">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Lead</button>
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
                <p>Are you sure you want to delete this lead? This action cannot be undone.</p>
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
let leadIdToDelete = null;
let editingLeadId = null;

// CSRF Token setup
$.ajaxSetup({
    headers: {
        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
    }
});

// Load leads on page load
$(document).ready(function() {
    loadLeads();
    
    // Search input with debounce
    let searchTimeout;
    $('#searchInput').on('input', function() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
            currentPage = 1;
            loadLeads();
        }, 500);
    });
    
    // Filter and sort changes
    $('#projectFilter, #sortField, #sortDirection').on('change', function() {
        currentPage = 1;
        loadLeads();
    });
    
    // Load statuses when project changes
    $('#project_id').on('change', function() {
        const projectId = $(this).val();
        loadStatusesByProject(projectId);
    });
    
    // Form submission
    $('#leadForm').on('submit', function(e) {
        e.preventDefault();
        saveLead();
    });
});

function loadLeads() {
    const search = $('#searchInput').val();
    const projectId = $('#projectFilter').val();
    const sortField = $('#sortField').val();
    const sortDirection = $('#sortDirection').val();
    
    $.ajax({
        url: '{{ route("leads.index") }}',
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
                renderLeads(response.leads);
                renderPagination(response.pagination);
            }
        },
        error: function(xhr) {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Failed to load leads. Please try again.'
            });
        }
    });
}

function renderLeads(leads) {
    const tbody = $('#leadsTableBody');
    tbody.empty();
    
    if (leads.length === 0) {
        tbody.append(`
            <tr>
                <td colspan="10" class="text-center py-4">
                    <i class="bi bi-inbox text-muted" style="font-size: 3rem;"></i>
                    <p class="text-muted mt-2">No leads found</p>
                </td>
            </tr>
        `);
        return;
    }
    
    leads.forEach(lead => {
        const row = `
            <tr>
                <td>${lead.id}</td>
                <td>${lead.first_name} ${lead.last_name || ''}</td>
                <td>${lead.company || '-'}</td>
                <td>${lead.email || '-'}</td>
                <td>${lead.phone || '-'}</td>
                <td>${lead.project ? lead.project.title : '-'}</td>
                <td><span class="badge bg-${lead.status ? (lead.status.color || 'secondary') : 'secondary'}">${lead.status ? lead.status.name : '-'}</span></td>
                <td>${lead.received_date ? new Date(lead.received_date).toLocaleDateString() : '-'}</td>
                <td>${(lead.added_by && lead.added_by.name) ? lead.added_by.name : '-'}</td>
                <td>
                    <a href="/leads/${lead.id}" class="btn btn-sm btn-info me-1" title="View Details">
                        <i class="bi bi-eye"></i>
                    </a>
                    <button class="btn btn-sm btn-primary me-1" onclick="editLead(${lead.id})" title="Edit">
                        <i class="bi bi-pencil"></i>
                    </button>
                    <button class="btn btn-sm btn-danger" onclick="confirmDelete(${lead.id})" title="Delete">
                        <i class="bi bi-trash"></i>
                    </button>
                </td>
            </tr>
        `;
        tbody.append(row);
    });
}

function renderPagination(pagination) {
    const info = `Showing ${((pagination.current_page - 1) * pagination.per_page) + 1} to ${Math.min(pagination.current_page * pagination.per_page, pagination.total)} of ${pagination.total} leads`;
    $('#paginationInfo').text(info);
    
    const paginationLinks = $('#paginationLinks');
    paginationLinks.empty();
    
    // Previous button
    const prevDisabled = pagination.current_page === 1 ? 'disabled' : '';
    paginationLinks.append(`
        <li class="page-item ${prevDisabled}">
            <a class="page-link" href="#" onclick="changePage(${pagination.current_page - 1}); return false;">Previous</a>
        </li>
    `);
    
    // Page numbers
    for (let i = 1; i <= pagination.last_page; i++) {
        const active = i === pagination.current_page ? 'active' : '';
        paginationLinks.append(`
            <li class="page-item ${active}">
                <a class="page-link" href="#" onclick="changePage(${i}); return false;">${i}</a>
            </li>
        `);
    }
    
    // Next button
    const nextDisabled = pagination.current_page === pagination.last_page ? 'disabled' : '';
    paginationLinks.append(`
        <li class="page-item ${nextDisabled}">
            <a class="page-link" href="#" onclick="changePage(${pagination.current_page + 1}); return false;">Next</a>
        </li>
    `);
}

function changePage(page) {
    currentPage = page;
    loadLeads();
}

function loadStatusesByProject(projectId, selectedStatusId = null) {
    if (!projectId) {
        $('#status_id').html('<option value="">Select Status</option>');
        return;
    }
    
    $.ajax({
        url: `/leads/project/${projectId}/statuses`,
        method: 'GET',
        success: function(response) {
            if (response.success) {
                let options = '<option value="">Select Status</option>';
                response.statuses.forEach(function(status) {
                    const selected = (selectedStatusId && status.id == selectedStatusId) ? 'selected' : '';
                    options += `<option value="${status.id}" ${selected}>${status.name}</option>`;
                });
                $('#status_id').html(options);
            }
        },
        error: function() {
            $('#status_id').html('<option value="">Select Status</option>');
        }
    });
}

function openCreateModal() {
    editingLeadId = null;
    $('#leadModalLabel').text('Create Lead');
    $('#leadForm')[0].reset();
    $('#leadId').val('');
    $('#address').val('');
    $('#date_of_birth').val('');
    $('#status_id').html('<option value="">Select Status</option>');
    // Clear address lines and DOB fields
    $('#address_line1').val('');
    $('#address_line2').val('');
    $('#address_line3').val('');
    $('#dob_day').val('');
    $('#dob_month').val('');
    $('#dob_year').val('');
    $('.invalid-feedback').text('');
    $('.form-control, .form-select').removeClass('is-invalid');
}

function editLead(id) {
    editingLeadId = id;
    $.ajax({
        url: `/leads/${id}/edit`,
        method: 'GET',
        success: function(response) {
            if (response.success) {
                const lead = response.lead;
                $('#leadModalLabel').text('Edit Lead');
                $('#leadId').val(lead.id);
                $('#project_id').val(lead.project_id);
                
                // Load statuses for the selected project and set the status
                loadStatusesByProject(lead.project_id, lead.status_id);
                
                $('#first_name').val(lead.first_name);
                $('#last_name').val(lead.last_name || '');
                $('#title').val(lead.title || '');
                $('#email').val(lead.email || '');
                $('#phone').val(lead.phone || '');
                $('#alternative_phone').val(lead.alternative_phone || '');
                $('#company').val(lead.company || '');
                $('#city').val(lead.city || '');
                $('#postcode').val(lead.postcode || '');
                
                // Split address into multiple lines
                splitAddress(lead.address || '');
                
                // Split date of birth into day, month, year
                splitDOB(lead.date_of_birth || '');
                
                $('.invalid-feedback').text('');
                $('.form-control, .form-select').removeClass('is-invalid');
                
                new bootstrap.Modal(document.getElementById('leadModal')).show();
            }
        },
        error: function(xhr) {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Failed to load lead data. Please try again.'
            });
        }
    });
}

function combineAddressFields() {
    const line1 = $('#address_line1').val() || '';
    const line2 = $('#address_line2').val() || '';
    const line3 = $('#address_line3').val() || '';
    const address = [line1, line2, line3].filter(line => line.trim() !== '').join('\n');
    $('#address').val(address);
}

function combineDOBFields() {
    const day = $('#dob_day').val() || '';
    const month = $('#dob_month').val() || '';
    const year = $('#dob_year').val() || '';
    
    if (day && month && year) {
        const dob = `${year}-${month}-${day}`;
        $('#date_of_birth').val(dob);
    } else {
        $('#date_of_birth').val('');
    }
}

function splitDOB(dateString) {
    if (!dateString) {
        $('#dob_day').val('');
        $('#dob_month').val('');
        $('#dob_year').val('');
        return;
    }
    
    // Parse date string in YYYY-MM-DD format to avoid timezone issues
    const dateMatch = dateString.match(/^(\d{4})-(\d{2})-(\d{2})/);
    if (dateMatch) {
        $('#dob_year').val(dateMatch[1]);
        $('#dob_month').val(dateMatch[2]);
        $('#dob_day').val(dateMatch[3]);
    } else {
        // Fallback to Date object parsing
        const date = new Date(dateString);
        if (!isNaN(date.getTime())) {
            $('#dob_day').val(String(date.getDate()).padStart(2, '0'));
            $('#dob_month').val(String(date.getMonth() + 1).padStart(2, '0'));
            $('#dob_year').val(date.getFullYear());
        }
    }
}

function splitAddress(addressString) {
    if (!addressString) {
        $('#address_line1').val('');
        $('#address_line2').val('');
        $('#address_line3').val('');
        return;
    }
    
    const lines = addressString.split('\n');
    $('#address_line1').val(lines[0] || '');
    $('#address_line2').val(lines[1] || '');
    $('#address_line3').val(lines[2] || '');
}

function saveLead() {
    // Combine address lines and DOB before submission
    combineAddressFields();
    combineDOBFields();
    
    // Get form data and remove address_line and dob_ fields (we only want the combined fields)
    const formDataArray = $('#leadForm').serializeArray();
    const formData = {};
    
    formDataArray.forEach(function(item) {
        // Skip address_line and dob_ fields as we're using the combined 'address' and 'date_of_birth' fields
        if (!item.name.startsWith('address_line') && !item.name.startsWith('dob_')) {
            formData[item.name] = item.value;
        }
    });
    
    // Add the combined address and date_of_birth
    formData.address = $('#address').val();
    formData.date_of_birth = $('#date_of_birth').val() || null;
    
    let url = '/leads';
    let method = 'POST';
    
    if (editingLeadId) {
        url = `/leads/${editingLeadId}`;
        formData._method = 'PUT';
    }
    
    $.ajax({
        url: url,
        method: method,
        data: formData,
        success: function(response) {
            if (response.success) {
                Swal.fire({
                    icon: 'success',
                    title: 'Success',
                    text: response.message,
                    timer: 2000,
                    showConfirmButton: false
                });
                
                bootstrap.Modal.getInstance(document.getElementById('leadModal')).hide();
                loadLeads();
            }
        },
        error: function(xhr) {
            if (xhr.status === 422) {
                const errors = xhr.responseJSON.errors;
                $('.invalid-feedback').text('');
                $('.form-control, .form-select').removeClass('is-invalid');
                
                $.each(errors, function(key, value) {
                    const field = $(`#${key}`);
                    field.addClass('is-invalid');
                    field.siblings('.invalid-feedback').text(value[0]);
                });
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Failed to save lead. Please try again.'
                });
            }
        }
    });
}

function confirmDelete(id) {
    leadIdToDelete = id;
    new bootstrap.Modal(document.getElementById('deleteModal')).show();
}

$('#confirmDeleteBtn').on('click', function() {
    if (leadIdToDelete) {
        $.ajax({
            url: `/leads/${leadIdToDelete}`,
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
                    loadLeads();
                }
            },
            error: function(xhr) {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Failed to delete lead. Please try again.'
                });
            }
        });
    }
});
</script>
@endpush
@endsection

