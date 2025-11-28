@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    <!-- Header Section -->
    <div class="card mb-4">
        <div class="card-header bg-gradient-primary text-white">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2 class="mb-2 text-white fw-bold">
                        <i class="bi bi-calendar-check me-3"></i>Activities
                    </h2>
                    <p class="mb-0 text-white-50 fs-6">View and manage all activities across leads</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Activities Table -->
    <div class="card p-0">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-dark">
                        <tr>
                            <th>ID</th>
                            <th>Date</th>
                            <th>Type</th>
                            <th>Subject/Description</th>
                            <th>Lead</th>
                            <th>Priority</th>
                            <th>Due Date</th>
                            <th>Created By</th>
                            <th>Assigned To</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($activities as $activity)
                            <tr>
                                <td>{{ $activity->id }}</td>
                                <td>{{ $activity->date ? $activity->date->format('M d, Y') : '-' }}</td>
                                <td>
                                    <span class="badge bg-secondary">{{ $activity->type ?: '-' }}</span>
                                </td>
                                <td>
                                    @if($activity->type === 'Email')
                                        <a href="{{ route('activities.show', $activity->id) }}" class="text-decoration-none">
                                            {{ $activity->field_1 ? \Illuminate\Support\Str::limit($activity->field_1, 50) : '-' }}
                                        </a>
                                    @else
                                        {{ $activity->field_1 ? \Illuminate\Support\Str::limit($activity->field_1, 50) : '-' }}
                                    @endif
                                </td>
                                <td>
                                    @if($activity->lead)
                                        <a href="{{ route('leads.show', $activity->lead->id) }}" class="text-decoration-none">
                                            {{ $activity->lead->first_name }} {{ $activity->lead->last_name }}
                                        </a>
                                        @if($activity->lead->company)
                                            <br><small class="text-muted">{{ $activity->lead->company }}</small>
                                        @endif
                                    @else
                                        -
                                    @endif
                                </td>
                                <td>
                                    @if($activity->priority)
                                        <span class="badge bg-{{ $activity->priority == 'High' ? 'danger' : ($activity->priority == 'Medium' ? 'warning' : 'info') }}">
                                            {{ $activity->priority }}
                                        </span>
                                    @else
                                        -
                                    @endif
                                </td>
                                <td>{{ $activity->due_date ? $activity->due_date->format('M d, Y') : '-' }}</td>
                                <td>{{ $activity->createdBy ? $activity->createdBy->name : '-' }}</td>
                                <td>{{ $activity->assignedTo ? $activity->assignedTo->name : '-' }}</td>
                                <td>
                                    <div class="btn-group" role="group">
                                        @if($activity->type === 'Email' || $activity->type === 'Dropbox Reply Email' || $activity->type === 'Dropbox Email')
                                            <a href="{{ route('activities.show', $activity->id) }}" class="btn btn-sm btn-primary" title="View Email">
                                                <i class="bi bi-envelope"></i>
                                            </a>
                                        @else
                                            <button type="button" class="btn btn-sm btn-info" onclick="viewActivity({{ $activity->id }})" title="View">
                                                <i class="bi bi-eye"></i>
                                            </button>
                                        @endif
                                        <button type="button" class="btn btn-sm btn-warning" onclick="editActivity({{ $activity->id }})" title="Edit">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        <button type="button" class="btn btn-sm btn-danger" onclick="deleteActivity({{ $activity->id }})" title="Delete">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" class="text-center py-5">
                                    <i class="bi bi-inbox text-muted" style="font-size: 3rem;"></i>
                                    <p class="text-muted mt-2">No activities found</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($activities->hasPages())
            <div class="card-footer">
                <div class="d-flex justify-content-center">
                    {{ $activities->links() }}
                </div>
            </div>
        @endif
    </div>
</div>

<script>
function viewActivity(id) {
    $.ajax({
        url: '/activities/' + id,
        method: 'GET',
        data: { ajax: true },
        success: function(response) {
            if (response.success) {
                // You can implement a modal to show activity details
                alert('Activity Details:\n\n' + JSON.stringify(response.activity, null, 2));
            }
        },
        error: function(xhr) {
            alert('Error loading activity');
        }
    });
}

function editActivity(id) {
    $.ajax({
        url: '/activities/' + id + '/edit',
        method: 'GET',
        success: function(response) {
            if (response.success) {
                // You can implement a modal to edit activity
                alert('Edit Activity:\n\n' + JSON.stringify(response.activity, null, 2));
            }
        },
        error: function(xhr) {
            alert('Error loading activity for editing');
        }
    });
}

function deleteActivity(id) {
    if (confirm('Are you sure you want to delete this activity?')) {
        $.ajax({
            url: '/activities/' + id,
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                if (response.success) {
                    location.reload();
                } else {
                    alert('Error deleting activity');
                }
            },
            error: function(xhr) {
                alert('Error deleting activity');
            }
        });
    }
}
</script>
@endsection

