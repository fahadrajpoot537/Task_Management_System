@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    <!-- Header Section -->
    <div class="card mb-4">
        <div class="card-header bg-gradient-primary text-white">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2 class="mb-2 text-white fw-bold">
                        <i class="bi bi-tags-fill me-3"></i>Lead Types Management
                    </h2>
                    <p class="mb-0 text-white-50 fs-6">Create and manage lead types for categorizing your leads</p>
                </div>
                <div class="d-flex gap-3">
                    <button type="button" class="btn btn-light btn-lg px-4 py-2" data-bs-toggle="modal" data-bs-target="#leadTypeModal" onclick="openCreateModal()">
                        <i class="bi bi-plus-circle me-2"></i>Create Lead Type
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Lead Types Table -->
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Description</th>
                            <th>Color</th>
                            <th>Order</th>
                            <th>Status</th>
                            <th>Leads Count</th>
                            <th>Created By</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($leadTypes as $leadType)
                        <tr>
                            <td>{{ $leadType->id }}</td>
                            <td>
                                <strong>{{ $leadType->name }}</strong>
                            </td>
                            <td>{{ Str::limit($leadType->description, 50) ?? '-' }}</td>
                            <td>
                                <span class="badge" style="background-color: {{ $leadType->color }}; color: white;">
                                    {{ $leadType->color }}
                                </span>
                            </td>
                            <td>{{ $leadType->order }}</td>
                            <td>
                                @if($leadType->is_active)
                                    <span class="badge bg-success">Active</span>
                                @else
                                    <span class="badge bg-secondary">Inactive</span>
                                @endif
                            </td>
                            <td>
                                <span class="badge bg-info">{{ $leadType->leads_count ?? 0 }}</span>
                            </td>
                            <td>{{ $leadType->createdBy->name ?? 'System' }}</td>
                            <td>
                                <div class="d-flex gap-2">
                                    <button class="btn btn-sm btn-primary" onclick="editLeadType({{ $leadType->id }})" title="Edit">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <button class="btn btn-sm btn-danger" onclick="deleteLeadType({{ $leadType->id }})" title="Delete">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="9" class="text-center">No lead types found. Create your first lead type!</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Lead Type Modal -->
<div class="modal fade" id="leadTypeModal" tabindex="-1" aria-labelledby="leadTypeModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="leadTypeModalLabel">Create Lead Type</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="leadTypeForm">
                <div class="modal-body">
                    <input type="hidden" id="leadTypeId" name="lead_type_id">
                    
                    <div class="mb-3">
                        <label for="name" class="form-label">Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="name" name="name" required>
                        <div class="invalid-feedback"></div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="description" class="form-label">Description</label>
                        <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label for="color" class="form-label">Color</label>
                        <div class="input-group">
                            <input type="color" class="form-control form-control-color" id="color" name="color" value="#007bff" title="Choose color">
                            <input type="text" class="form-control" id="colorHex" value="#007bff" placeholder="#007bff" pattern="^#[0-9A-Fa-f]{6}$">
                        </div>
                        <small class="text-muted">Select a color or enter hex code</small>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="order" class="form-label">Order</label>
                                <input type="number" class="form-control" id="order" name="order" value="0" min="0">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <div class="form-check form-switch mt-4">
                                    <input class="form-check-input" type="checkbox" id="is_active" name="is_active" checked>
                                    <label class="form-check-label" for="is_active">Active</label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-save me-2"></i>Save Lead Type
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
let editingLeadTypeId = null;

// CSRF Token setup
$.ajaxSetup({
    headers: {
        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
    }
});

// Color picker sync
$('#color').on('input', function() {
    $('#colorHex').val($(this).val());
});

$('#colorHex').on('input', function() {
    const hex = $(this).val();
    if (/^#[0-9A-Fa-f]{6}$/.test(hex)) {
        $('#color').val(hex);
    }
});

// Open create modal
function openCreateModal() {
    editingLeadTypeId = null;
    $('#leadTypeModalLabel').text('Create Lead Type');
    $('#leadTypeForm')[0].reset();
    $('#leadTypeId').val('');
    $('#color').val('#007bff');
    $('#colorHex').val('#007bff');
    $('#is_active').prop('checked', true);
}

// Edit lead type
function editLeadType(id) {
    editingLeadTypeId = id;
    $('#leadTypeModalLabel').text('Edit Lead Type');
    
    $.ajax({
        url: `/lead-types/${id}/edit`,
        method: 'GET',
        success: function(response) {
            const leadType = response.lead_type || response;
            $('#leadTypeId').val(leadType.id);
            $('#name').val(leadType.name);
            $('#description').val(leadType.description || '');
            $('#color').val(leadType.color || '#007bff');
            $('#colorHex').val(leadType.color || '#007bff');
            $('#order').val(leadType.order || 0);
            $('#is_active').prop('checked', leadType.is_active !== false);
            
            const modal = new bootstrap.Modal(document.getElementById('leadTypeModal'));
            modal.show();
        },
        error: function() {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Failed to load lead type data.'
            });
        }
    });
}

// Delete lead type
function deleteLeadType(id) {
    Swal.fire({
        title: 'Are you sure?',
        text: "You won't be able to revert this!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Yes, delete it!'
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: `/lead-types/${id}`,
                method: 'DELETE',
                success: function(response) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Deleted!',
                        text: response.message || 'Lead type deleted successfully.'
                    }).then(() => {
                        location.reload();
                    });
                },
                error: function(xhr) {
                    const message = xhr.responseJSON?.message || 'Failed to delete lead type.';
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: message
                    });
                }
            });
        }
    });
}

// Form submission
$('#leadTypeForm').on('submit', function(e) {
    e.preventDefault();
    
    const formData = {
        name: $('#name').val(),
        description: $('#description').val(),
        color: $('#color').val(),
        order: $('#order').val() || 0,
        is_active: $('#is_active').is(':checked')
    };
    
    const url = editingLeadTypeId 
        ? `/lead-types/${editingLeadTypeId}`
        : '/lead-types';
    const method = editingLeadTypeId ? 'PUT' : 'POST';
    
    $.ajax({
        url: url,
        method: method,
        data: formData,
        success: function(response) {
            Swal.fire({
                icon: 'success',
                title: 'Success!',
                text: response.message || 'Lead type saved successfully.'
            }).then(() => {
                bootstrap.Modal.getInstance(document.getElementById('leadTypeModal')).hide();
                location.reload();
            });
        },
        error: function(xhr) {
            let errorMessage = 'Failed to save lead type.';
            if (xhr.responseJSON?.errors) {
                const errors = xhr.responseJSON.errors;
                errorMessage = Object.values(errors).flat().join('<br>');
            } else if (xhr.responseJSON?.message) {
                errorMessage = xhr.responseJSON.message;
            }
            
            Swal.fire({
                icon: 'error',
                title: 'Error',
                html: errorMessage
            });
        }
    });
});
</script>
@endpush
@endsection

