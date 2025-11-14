@extends('layouts.app')

@section('content')
    <div class="container-fluid py-4" style="overflow: visible; position: relative;">
        <!-- Header with Back Button -->
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div class="d-flex align-items-center">
                <i class="bi bi-person-lines-fill text-primary me-2 fs-4"></i>
                <h4 class="mb-0 text-primary fw-bold">Lead Details</h4>
            </div>
            <a href="{{ route('leads.index') }}" class="btn btn-outline-primary btn-sm">
                <i class="bi bi-arrow-left me-1"></i>Back to Leads
            </a>
        </div>

        <!-- Tabs -->
        <ul class="nav nav-tabs mb-3" id="leadTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="summary-tab" data-bs-toggle="tab" data-bs-target="#summary" type="button"
                    role="tab">
                    Summary
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="details-tab" data-bs-toggle="tab" data-bs-target="#details"
                    type="button" role="tab" aria-selected="true">
                    Details
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="transactions-tab" data-bs-toggle="tab" data-bs-target="#transactions"
                    type="button" role="tab">
                    Transactions
                </button>
            </li>
            <li class="nav-item ms-auto">
                <button class="btn btn-sm btn-outline-secondary">
                    <i class="bi bi-link-45deg me-1"></i>No Linked Leads
                </button>
            </li>
        </ul>

        <!-- Status Bar -->
        <div class="card mb-3">
            <div class="card-body py-2">
                <div class="d-flex flex-wrap align-items-center gap-2">
                    <span class="badge bg-secondary">Open {{ $lead->received_date->diffInHours(now()) }} Hours</span>
                    @if ($lead->status)
                        <span class="badge bg-{{ $lead->status->color ?? 'primary' }}">{{ $lead->status->name }}</span>
                    @else
                        <span class="badge bg-primary">New</span>
                    @endif

                    @if ($lead->project && $lead->project->statuses)
                        @foreach ($lead->project->statuses->sortBy('order') as $projectStatus)
                            <span
                                class="badge bg-{{ $projectStatus->color ?? 'secondary' }} 
                                {{ $lead->status && $lead->status->id === $projectStatus->id ? 'border border-dark border-2' : '' }}">
                                {{ $projectStatus->name }}
                            </span>
                        @endforeach
                    @endif
                </div>
            </div>
        </div>

        <!-- Tab Content -->
        <div class="tab-content" id="leadTabsContent">
            <!-- Details Tab -->
            <div class="tab-pane fade show active" id="details" role="tabpanel">
                <!-- Lead Information (Two Columns) -->
                <div class="card mb-3">
                    <div class="card-body">
                        <div class="row">
                            <!-- Left Column -->
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="text-muted small">Contact & Reference:</label>
                                    <div class="fw-bold">
                                        {{ $lead->title ? $lead->title . ' ' : '' }}{{ $lead->first_name }}
                                        {{ $lead->last_name }}
                                        @if ($lead->flg_reference || $lead->sub_reference)
                                            ({{ $lead->flg_reference }}{{ $lead->sub_reference ? '-' . $lead->sub_reference : '' }})
                                        @endif
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label class="text-muted small">Date & Time Received:</label>
                                    <div>
                                        {{ $lead->received_date ? $lead->received_date->format('jS M Y H:i') : $lead->created_at->format('jS M Y H:i') }}
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label class="text-muted small">Phone Number:</label>
                                    <div>{{ $lead->phone ?: 'None' }}</div>
                                </div>
                                <div class="mb-3">
                                    <label class="text-muted small">Email Address:</label>
                                    <div>{{ $lead->email ?: 'None' }}</div>
                                </div>
                            </div>
                            <!-- Right Column -->
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="text-muted small">Lead Group & Type:</label>
                                    <div>
                                        {{ $lead->project ? $lead->project->title . ' > ' . $lead->leadType->name : 'General > General' }}
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label class="text-muted small">Status & Assigned User:</label>
                                    <div>
                                        {{ $lead->status ? $lead->status->name : 'New' }}
                                        ({{ $lead->user_name }})
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label class="text-muted small">Alternative Phone Number:</label>
                                    <div>{{ $lead->alternative_phone ?: 'None' }}</div>
                                </div>
                                <div class="mb-3">
                                    <label class="text-muted small">Notes:</label>
                                    <div>{{ $lead->note ?: 'None' }}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Action Bar -->
                <div class="card mb-3" style="overflow: visible;">
                    <div class="card-body py-2">
                        <div class="d-flex flex-wrap gap-2 align-items-center">
                            <button class="btn btn-sm btn-primary" onclick="editLead({{ $lead->id }})">
                                <i class="bi bi-pencil me-1"></i>Edit
                            </button>

                            <div class="btn-group ">
                                <button type="button" class="btn btn-sm btn-success dropdown-toggle"
                                    data-bs-toggle="dropdown" aria-expanded="false" id="newActivityDropdownBtn">
                                    <i class="bi bi-plus-circle me-1"></i>New
                                </button>
                                <ul class="dropdown-menu">
                                    <li><a class="dropdown-item" href="javascript:void(0);"
                                            onclick="createActivity('Task'); return false;"><i
                                                class="bi bi-check-square me-2"></i>Task</a></li>
                                    <li><a class="dropdown-item" href="javascript:void(0);"
                                            onclick="createActivity('Event'); return false;"><i
                                                class="bi bi-calendar-event me-2"></i>Event</a></li>
                                    <li><a class="dropdown-item" href="javascript:void(0);"
                                            onclick="createActivity('Note'); return false;"><i
                                                class="bi bi-sticky me-2"></i>Note</a></li>
                                    <li><a class="dropdown-item" href="javascript:void(0);"
                                            onclick="createActivity('Telephone Call'); return false;"><i
                                                class="bi bi-telephone me-2"></i>Telephone Call</a></li>
                                    <li><a class="dropdown-item" href="javascript:void(0);"
                                            onclick="createActivity('Text Message'); return false;"><i
                                                class="bi bi-chat-dots me-2"></i>Text Message</a></li>
                                    <li><a class="dropdown-item" href="javascript:void(0);"
                                            onclick="createActivity('Email'); return false;"><i
                                                class="bi bi-envelope me-2"></i>Email</a></li>
                                    <li><a class="dropdown-item" href="javascript:void(0);"
                                            onclick="createActivity('Letter'); return false;"><i
                                                class="bi bi-file-text me-2"></i>Letter</a></li>
                                    <li><a class="dropdown-item" href="javascript:void(0);"
                                            onclick="createActivity('Document'); return false;"><i
                                                class="bi bi-file-earmark me-2"></i>Document</a></li>
                                    <li><a class="dropdown-item" href="javascript:void(0);"
                                            onclick="createActivity('Appointment'); return false;"><i
                                                class="bi bi-calendar-check me-2"></i>Appointment</a></li>
                                </ul>
                            </div>

                            <div class="btn-group">
                                <button type="button" class="btn btn-sm btn-outline-secondary dropdown-toggle"
                                    data-bs-toggle="dropdown">
                                    Workflows
                                </button>
                                <ul class="dropdown-menu">
                                    <li><a class="dropdown-item" href="#">Workflow 1</a></li>
                                    <li><a class="dropdown-item" href="#">Workflow 2</a></li>
                                </ul>
                            </div>

                            <div class="btn-group position-relative">
                                <button type="button" class="btn btn-sm btn-outline-secondary" id="leadTypeDropdownBtn">
                                    Type
                                    @if ($lead->leadType)
                                        <span class="badge bg-primary ms-1">{{ $lead->leadType->name }}</span>
                                    @endif
                                    <i class="bi bi-chevron-down ms-1"></i>
                                </button>
                                <!-- Custom Lead Type Popup Menu -->
                                <div class="user-popup" id="leadTypePopup" style="display: none;">
                                    <div class="user-popup-content">
                                        <div class="user-popup-header">
                                            <span>Select Lead Type</span>
                                            <button type="button" class="btn-close-popup"
                                                onclick="closeLeadTypePopup()">
                                                <i class="bi bi-x"></i>
                                            </button>
                                        </div>
                                        <div class="user-popup-search">
                                            <input type="text" id="leadTypeSearchInput"
                                                class="form-control form-control-sm" placeholder="Search lead types...">
                                        </div>
                                        <div class="user-popup-body" id="leadTypePopupBody">
                                            <div class="text-center py-3">
                                                <div class="spinner-border spinner-border-sm" role="status">
                                                    <span class="visually-hidden">Loading...</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="btn-group position-relative">
                                <button type="button" class="btn btn-sm btn-outline-secondary" id="statusDropdownBtn">
                                    Status
                                    @if ($lead->status)
                                        <span class="badge bg-primary ms-1">{{ $lead->status->name }}</span>
                                    @endif
                                    <i class="bi bi-chevron-down ms-1"></i>
                                </button>
                                <!-- Custom Status Popup Menu -->
                                <div class="user-popup" id="statusPopup" style="display: none;">
                                    <div class="user-popup-content">
                                        <div class="user-popup-header">
                                            <span>Select Status</span>
                                            <button type="button" class="btn-close-popup" onclick="closeStatusPopup()">
                                                <i class="bi bi-x"></i>
                                            </button>
                                        </div>
                                        <div class="user-popup-search">
                                            <input type="text" id="statusSearchInput"
                                                class="form-control form-control-sm" placeholder="Search statuses...">
                                        </div>
                                        <div class="user-popup-body" id="statusPopupBody">
                                            <div class="text-center py-3">
                                                <div class="spinner-border spinner-border-sm" role="status">
                                                    <span class="visually-hidden">Loading...</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="btn-group position-relative">
                                <button type="button" class="btn btn-sm btn-outline-secondary" id="userDropdownBtn">
                                    User

                                    <span class="badge bg-primary ms-1">{{ $lead->user_name }}</span>
                                    <i class="bi bi-chevron-down ms-1"></i>
                                </button>
                                <!-- Custom User Popup Menu -->
                                <div class="user-popup" id="userPopup" style="display: none;">
                                    <div class="user-popup-content">
                                        <div class="user-popup-header">
                                            <span>Select User</span>
                                            <button type="button" class="btn-close-popup" onclick="closeUserPopup()">
                                                <i class="bi bi-x"></i>
                                            </button>
                                        </div>
                                        <div class="user-popup-search">
                                            <input type="text" id="userSearchInput"
                                                class="form-control form-control-sm" placeholder="Search users...">
                                        </div>
                                        <div class="user-popup-body" id="userPopupBody">
                                            <div class="text-center py-3">
                                                <div class="spinner-border spinner-border-sm" role="status">
                                                    <span class="visually-hidden">Loading...</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <button class="btn btn-sm btn-danger" onclick="deleteLead({{ $lead->id }})">
                                <i class="bi bi-trash me-1"></i>Delete
                            </button>
                            <button class="btn btn-sm btn-outline-secondary" onclick="copyLead({{ $lead->id }})">
                                <i class="bi bi-files me-1"></i>Copy
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Activities Section -->
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h6 class="mb-0">Activities</h6>
                        <div class="d-flex gap-2">
                            <a href="{{ route('leads.activities.export', $lead->id) }}" class="btn btn-sm btn-success">
                                <i class="bi bi-download me-1"></i>Export
                            </a>
                            <button type="button" class="btn btn-sm btn-info" data-bs-toggle="modal"
                                data-bs-target="#importActivitiesModal">
                                <i class="bi bi-upload me-1"></i>Import
                            </button>
                            <button class="btn btn-sm btn-outline-secondary" onclick="toggleFilters()">
                                <i class="bi bi-funnel me-1"></i>Filters
                            </button>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <div id="activitiesList">
                            @if ($lead->activities && $lead->activities->count() > 0)
                                <div>
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>Date</th>
                                                <th>Type</th>
                                                <th>Description</th>
                                                <th>Priority</th>
                                                <th>Due Date</th>
                                                <th>Created By</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($lead->activities as $activity)
                                                <tr>
                                                    <td>{{ $activity->date ? $activity->date->format('M d, Y') : '-' }}
                                                    </td>
                                                    <td><span
                                                            class="badge bg-secondary">{{ $activity->type ?: '-' }}</span>
                                                    </td>
                                                    <td>{{ $activity->field_1 ? \Illuminate\Support\Str::limit($activity->field_1, 50) : '-' }}
                                                    </td>
                                                    <td>
                                                        @if ($activity->priority)
                                                            <span
                                                                class="badge bg-{{ $activity->priority == 'High' ? 'danger' : ($activity->priority == 'Medium' ? 'warning' : 'info') }}">
                                                                {{ $activity->priority }}
                                                            </span>
                                                        @else
                                                            -
                                                        @endif
                                                    </td>
                                                    <td>{{ $activity->due_date ? $activity->due_date->format('M d, Y') : '-' }}
                                                    </td>
                                                    <td>{{ $activity->createdBy ? $activity->createdBy->name : '-' }}</td>
                                                    <td>
                                                        @if ($activity->type === 'Email' || $activity->type === 'Dropbox Reply Email' || $activity->type === 'Dropbox Email')
                                                            <a href="{{ route('activities.show', $activity->id) }}"
                                                                class="btn btn-sm btn-info" title="View Email">
                                                                <i class="bi bi-eye"></i>
                                                            </a>
                                                        @elseif($activity->type === 'Document')
                                                            <div class="d-flex gap-2">
                                                                <button class="btn btn-sm document-preview-btn"
                                                                    onclick="previewDocumentFromServer({{ $activity->id }})"
                                                                    title="Preview">
                                                                    <i class="bi bi-eye"></i>
                                                                </button>
                                                                <button class="btn btn-sm document-delete-btn"
                                                                    onclick="deleteActivity({{ $activity->id }})"
                                                                    title="Delete">
                                                                    <i class="bi bi-trash"></i>
                                                                </button>
                                                            </div>
                                                        @else
                                                            <button class="btn btn-sm btn-primary"
                                                                onclick="editActivity({{ $activity->id }})"
                                                                title="Edit">
                                                                <i class="bi bi-pencil"></i>
                                                            </button>
                                                            <button class="btn btn-sm btn-danger"
                                                                onclick="deleteActivity({{ $activity->id }})"
                                                                title="Delete">
                                                                <i class="bi bi-trash"></i>
                                                            </button>
                                                        @endif
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @else
                                <div class="text-center py-5">
                                    <i class="bi bi-inbox text-muted" style="font-size: 3rem;"></i>
                                    <p class="text-muted mt-2">No activities found</p>
                                    <p class="text-muted small">Click "New" to create an activity</p>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <!-- Summary Tab -->
            <div class="tab-pane fade" id="summary" role="tabpanel">
                <div class="card">
                    <div class="card-body">
                        <h5>Summary</h5>
                        <p>Summary content will be displayed here.</p>
                    </div>
                </div>
            </div>

            <!-- Transactions Tab -->
            <div class="tab-pane fade" id="transactions" role="tabpanel">
                <div class="card">
                    <div class="card-body">
                        <h5>Transactions</h5>
                        <p>Transactions content will be displayed here.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Activity Modal -->
    <div class="modal fade" id="activityModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content" style="background-color: #fff;">
                <div class="modal-header" id="activityModalHeader"
                    style="background-color: #fff; border-bottom: 1px solid #dee2e6;">
                    <div class="d-flex justify-content-between align-items-center w-100">
                        <h4 class="modal-title mb-0 fw-bold text-dark" id="activityModalLabel"
                            style="font-size: 1.5rem;">Create Activity</h4>
                        <div class="d-flex align-items-center gap-2">
                            <a href="#" class="text-decoration-none text-primary small" id="viewCalendarLink"
                                style="display: none;">
                                <i class="bi bi-calendar3 me-1"></i>View Calendar
                            </a>
                            <a href="{{ route('leads.show', $lead->id) }}" target="_blank"
                                class="text-decoration-none text-primary small" id="openLeadLink" style="display: none;">
                                <i class="bi bi-box-arrow-up-right me-1"></i>Open Lead in New Window
                            </a>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                    </div>
                </div>
                <form id="activityForm">
                    <div class="modal-body" id="activityModalBody" style="background-color: #fff;">
                        <input type="hidden" id="activityId" name="activity_id">
                        <input type="hidden" id="activityLeadId" name="lead_id" value="{{ $lead->id }}">

                        <!-- Information Sections (for Note and Task types) -->
                        <div id="noteInfoSections" style="display: none;">
                            <div class="row mb-4">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <strong class="text-dark d-block mb-1" style="font-weight: 600;">Contact &
                                            Reference</strong>
                                        <span class="text-muted" id="contactReference"
                                            style="color: #6c757d;">{{ $lead->first_name }} {{ $lead->last_name }}
                                            ({{ $lead->flg_reference ?: 'N/A' }})</span>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <strong class="text-dark d-block mb-1" style="font-weight: 600;">Status & Assigned
                                            User</strong>
                                        <span class="text-muted" id="statusAssignedUser"
                                            style="color: #6c757d;">{{ $lead->status ? $lead->status->name : 'New' }}
                                            @if ($lead->addedBy)
                                                ({{ $lead->addedBy->name }})
                                            @endif
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Task Specific Section -->
                        <div id="taskSpecificSection" style="display: none;">
                            <!-- Task Due Section -->
                            <div class="mb-4">
                                <label class="form-label fw-bold text-dark mb-2">Task Due</label>
                                <div class="row g-2 mb-2">
                                    <div class="col-md-4">
                                        <select class="form-select" id="taskDueType" name="task_due_type">
                                            <option value="specific_date_time" selected>Specific Date & Time</option>
                                            <option value="relative">Relative</option>
                                            <option value="no_date">No Date</option>
                                        </select>
                                    </div>
                                    <div class="col-md-8 d-flex align-items-center">
                                        <div class="form-check me-3">
                                            <input class="form-check-input" type="checkbox" id="taskPrioritise"
                                                name="priority_check">
                                            <label class="form-check-label" for="taskPrioritise">Prioritise</label>
                                        </div>
                                        <a href="javascript:void(0);" class="text-decoration-none text-primary small"
                                            id="addEndDateTimeLink" onclick="toggleEndDateTime()">
                                            - Add an End Date/Time
                                        </a>
                                    </div>
                                </div>

                                <!-- Date and Time Pickers -->
                                <div id="taskDateTimeSection">
                                    <div class="row g-2 mb-2">
                                        <div class="col-auto">
                                            <select class="form-select form-select-sm" id="taskDueMonth"
                                                name="task_due_month" style="width: 80px;">
                                                @for ($i = 1; $i <= 12; $i++)
                                                    <option value="{{ str_pad($i, 2, '0', STR_PAD_LEFT) }}"
                                                        {{ date('m') == $i ? 'selected' : '' }}>
                                                        {{ str_pad($i, 2, '0', STR_PAD_LEFT) }}</option>
                                                @endfor
                                            </select>
                                        </div>
                                        <div class="col-auto">
                                            <select class="form-select form-select-sm" id="taskDueDay"
                                                name="task_due_day" style="width: 70px;">
                                                @for ($i = 1; $i <= 31; $i++)
                                                    <option value="{{ str_pad($i, 2, '0', STR_PAD_LEFT) }}"
                                                        {{ date('d') == $i ? 'selected' : '' }}>
                                                        {{ str_pad($i, 2, '0', STR_PAD_LEFT) }}</option>
                                                @endfor
                                            </select>
                                        </div>
                                        <div class="col-auto">
                                            <select class="form-select form-select-sm" id="taskDueYear"
                                                name="task_due_year" style="width: 90px;">
                                                @for ($i = date('Y'); $i <= date('Y') + 5; $i++)
                                                    <option value="{{ $i }}"
                                                        {{ date('Y') == $i ? 'selected' : '' }}>{{ $i }}
                                                    </option>
                                                @endfor
                                            </select>
                                        </div>
                                        <div class="col-auto d-flex align-items-center">
                                            <span class="me-2">at</span>
                                        </div>
                                        <div class="col-auto">
                                            <select class="form-select form-select-sm" id="taskDueHour"
                                                name="task_due_hour" style="width: 70px;">
                                                @for ($i = 0; $i <= 23; $i++)
                                                    <option value="{{ str_pad($i, 2, '0', STR_PAD_LEFT) }}"
                                                        {{ date('H') == $i ? 'selected' : '' }}>
                                                        {{ str_pad($i, 2, '0', STR_PAD_LEFT) }}</option>
                                                @endfor
                                            </select>
                                        </div>
                                        <div class="col-auto">
                                            <select class="form-select form-select-sm" id="taskDueMinute"
                                                name="task_due_minute" style="width: 70px;">
                                                @for ($i = 0; $i <= 59; $i += 1)
                                                    <option value="{{ str_pad($i, 2, '0', STR_PAD_LEFT) }}"
                                                        {{ date('i') == $i ? 'selected' : '' }}>
                                                        {{ str_pad($i, 2, '0', STR_PAD_LEFT) }}</option>
                                                @endfor
                                            </select>
                                        </div>
                                    </div>
                                </div>

                                <!-- End Date/Time Section (hidden by default) -->
                                <div id="endDateTimeSection" style="display: none;" class="mt-3">
                                    <label class="form-label fw-bold text-dark mb-2">End Date/Time</label>
                                    <div class="row g-2">
                                        <div class="col-auto">
                                            <select class="form-select form-select-sm" id="taskEndMonth"
                                                name="task_end_month" style="width: 80px;">
                                                @for ($i = 1; $i <= 12; $i++)
                                                    <option value="{{ str_pad($i, 2, '0', STR_PAD_LEFT) }}">
                                                        {{ str_pad($i, 2, '0', STR_PAD_LEFT) }}</option>
                                                @endfor
                                            </select>
                                        </div>
                                        <div class="col-auto">
                                            <select class="form-select form-select-sm" id="taskEndDay"
                                                name="task_end_day" style="width: 70px;">
                                                @for ($i = 1; $i <= 31; $i++)
                                                    <option value="{{ str_pad($i, 2, '0', STR_PAD_LEFT) }}">
                                                        {{ str_pad($i, 2, '0', STR_PAD_LEFT) }}</option>
                                                @endfor
                                            </select>
                                        </div>
                                        <div class="col-auto">
                                            <select class="form-select form-select-sm" id="taskEndYear"
                                                name="task_end_year" style="width: 90px;">
                                                @for ($i = date('Y'); $i <= date('Y') + 5; $i++)
                                                    <option value="{{ $i }}">{{ $i }}</option>
                                                @endfor
                                            </select>
                                        </div>
                                        <div class="col-auto d-flex align-items-center">
                                            <span class="me-2">at</span>
                                        </div>
                                        <div class="col-auto">
                                            <select class="form-select form-select-sm" id="taskEndHour"
                                                name="task_end_hour" style="width: 70px;">
                                                @for ($i = 0; $i <= 23; $i++)
                                                    <option value="{{ str_pad($i, 2, '0', STR_PAD_LEFT) }}">
                                                        {{ str_pad($i, 2, '0', STR_PAD_LEFT) }}</option>
                                                @endfor
                                            </select>
                                        </div>
                                        <div class="col-auto">
                                            <select class="form-select form-select-sm" id="taskEndMinute"
                                                name="task_end_minute" style="width: 70px;">
                                                @for ($i = 0; $i <= 59; $i += 1)
                                                    <option value="{{ str_pad($i, 2, '0', STR_PAD_LEFT) }}">
                                                        {{ str_pad($i, 2, '0', STR_PAD_LEFT) }}</option>
                                                @endfor
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Assign To Section -->
                            <div class="mb-4">
                                <label class="form-label fw-bold text-dark mb-2">Assign To:</label>
                                <select class="form-select" id="taskAssignTo" name="assigned_to">
                                    <option value="">Select User</option>
                                    @foreach ($users as $user)
                                        <option value="{{ $user->id }}">User: {{ $user->name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- Task Details Section -->
                            <div class="mb-4">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <label class="form-label fw-bold text-dark mb-0">Task Details:</label>
                                    <div class="dropdown">
                                        <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button"
                                            id="insertTextDropdown" data-bs-toggle="dropdown">
                                            Insert Text
                                        </button>
                                        <ul class="dropdown-menu" aria-labelledby="insertTextDropdown">
                                            <li><a class="dropdown-item" href="javascript:void(0);"
                                                    onclick="insertTaskText('Call Requested')">Call Requested</a></li>
                                            <li><a class="dropdown-item" href="javascript:void(0);"
                                                    onclick="insertTaskText('Make Initial Call')">Make Initial Call</a>
                                            </li>
                                            <li><a class="dropdown-item" href="javascript:void(0);"
                                                    onclick="insertTaskText('Send Initial Email')">Send Initial Email</a>
                                            </li>
                                            <li><a class="dropdown-item" href="javascript:void(0);"
                                                    onclick="insertTaskText('Send Initial Letter')">Send Initial Letter</a>
                                            </li>
                                            <li><a class="dropdown-item" href="javascript:void(0);"
                                                    onclick="insertTaskText('Make Follow-Up Call')">Make Follow-Up Call</a>
                                            </li>
                                            <li><a class="dropdown-item" href="javascript:void(0);"
                                                    onclick="insertTaskText('Send Follow-Up Email')">Send Follow-Up
                                                    Email</a></li>
                                            <li><a class="dropdown-item" href="javascript:void(0);"
                                                    onclick="insertTaskText('Send Follow-Up Letter')">Send Follow-Up
                                                    Letter</a></li>
                                            <li><a class="dropdown-item" href="javascript:void(0);"
                                                    onclick="insertTaskText('Review Lead')">Review Lead</a></li>
                                            <li><a class="dropdown-item" href="javascript:void(0);"
                                                    onclick="insertTaskText('Process Lead')">Process Lead</a></li>
                                            <li><a class="dropdown-item" href="javascript:void(0);"
                                                    onclick="insertTaskText('Close Lead')">Close Lead</a></li>
                                        </ul>
                                    </div>
                                </div>
                                <textarea class="form-control" id="taskDetails" name="field_1" rows="8" placeholder="Enter task details..."
                                    style="resize: vertical; border: 1px solid #dee2e6;"></textarea>
                            </div>

                            <!-- Bottom Action Section -->
                            <div class="mb-3">
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="checkbox" id="assignLeadToUser"
                                        name="assign_lead_to_user">
                                    <label class="form-check-label" for="assignLeadToUser">Assign Lead to User</label>
                                </div>
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="checkbox" id="createTaskCheck"
                                        name="create_task_check" checked>
                                    <label class="form-check-label" for="createTaskCheck">Create a</label>
                                    <select class="form-select form-select-sm d-inline-block ms-2" id="createTaskType"
                                        name="create_task_type" style="width: 120px; display: inline-block;">
                                        <option value="Task" selected>Task</option>
                                        <option value="Event">Event</option>
                                        <option value="Appointment">Appointment</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- Event Specific Section -->
                        <div id="eventSpecificSection" style="display: none;">
                            <div class="row mb-4">
                                <!-- Left Column -->
                                <div class="col-md-6">
                                    <!-- Contact & Reference -->
                                    <div class="mb-4">
                                        <strong class="text-dark d-block mb-1" style="font-weight: 600;">Contact &
                                            Reference</strong>
                                        <span class="text-muted" id="eventContactReference"
                                            style="color: #6c757d;">{{ $lead->first_name }} {{ $lead->last_name }}
                                            ({{ $lead->flg_reference ?: 'N/A' }})</span>
                                    </div>

                                    <!-- Event Timing - Start -->
                                    <div class="mb-4">
                                        <label class="form-label fw-bold text-dark mb-2">Start:</label>
                                        <div class="row g-2 mb-2">
                                            <div class="col-md-4">
                                                <select class="form-select form-select-sm" id="eventStartType"
                                                    name="event_start_type">
                                                    <option value="specific_date_time" selected>Specific Date & Time
                                                    </option>
                                                    <option value="relative">Relative</option>
                                                    <option value="no_date">No Date</option>
                                                </select>
                                            </div>
                                            <div class="col-md-8 d-flex align-items-center">
                                                <div class="form-check me-3">
                                                    <input class="form-check-input" type="checkbox" id="eventPrioritise"
                                                        name="event_priority_check">
                                                    <label class="form-check-label"
                                                        for="eventPrioritise">Prioritise</label>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Start Date and Time Pickers -->
                                        <div class="row g-2 mb-2">
                                            <div class="col-auto">
                                                <select class="form-select form-select-sm" id="eventStartMonth"
                                                    name="event_start_month" style="width: 80px;">
                                                    @for ($i = 1; $i <= 12; $i++)
                                                        <option value="{{ str_pad($i, 2, '0', STR_PAD_LEFT) }}"
                                                            {{ date('m') == $i ? 'selected' : '' }}>
                                                            {{ str_pad($i, 2, '0', STR_PAD_LEFT) }}</option>
                                                    @endfor
                                                </select>
                                            </div>
                                            <div class="col-auto">
                                                <select class="form-select form-select-sm" id="eventStartDay"
                                                    name="event_start_day" style="width: 70px;">
                                                    @for ($i = 1; $i <= 31; $i++)
                                                        <option value="{{ str_pad($i, 2, '0', STR_PAD_LEFT) }}"
                                                            {{ date('d') == $i ? 'selected' : '' }}>
                                                            {{ str_pad($i, 2, '0', STR_PAD_LEFT) }}</option>
                                                    @endfor
                                                </select>
                                            </div>
                                            <div class="col-auto">
                                                <select class="form-select form-select-sm" id="eventStartYear"
                                                    name="event_start_year" style="width: 90px;">
                                                    @for ($i = date('Y'); $i <= date('Y') + 5; $i++)
                                                        <option value="{{ $i }}"
                                                            {{ date('Y') == $i ? 'selected' : '' }}>{{ $i }}
                                                        </option>
                                                    @endfor
                                                </select>
                                            </div>
                                            <div class="col-auto d-flex align-items-center">
                                                <span class="me-2">at</span>
                                            </div>
                                            <div class="col-auto">
                                                <select class="form-select form-select-sm" id="eventStartHour"
                                                    name="event_start_hour" style="width: 70px;">
                                                    @for ($i = 0; $i <= 23; $i++)
                                                        <option value="{{ str_pad($i, 2, '0', STR_PAD_LEFT) }}"
                                                            {{ date('H') == $i ? 'selected' : '' }}>
                                                            {{ str_pad($i, 2, '0', STR_PAD_LEFT) }}</option>
                                                    @endfor
                                                </select>
                                            </div>
                                            <div class="col-auto">
                                                <select class="form-select form-select-sm" id="eventStartMinute"
                                                    name="event_start_minute" style="width: 70px;">
                                                    @for ($i = 0; $i <= 59; $i += 1)
                                                        <option value="{{ str_pad($i, 2, '0', STR_PAD_LEFT) }}"
                                                            {{ date('i') == $i ? 'selected' : '' }}>
                                                            {{ str_pad($i, 2, '0', STR_PAD_LEFT) }}</option>
                                                    @endfor
                                                </select>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Event Timing - End -->
                                    <div class="mb-4">
                                        <label class="form-label fw-bold text-dark mb-2">End:</label>
                                        <!-- End Date and Time Pickers -->
                                        <div class="row g-2 mb-2">
                                            <div class="col-auto">
                                                <select class="form-select form-select-sm" id="eventEndMonth"
                                                    name="event_end_month" style="width: 80px;">
                                                    @for ($i = 1; $i <= 12; $i++)
                                                        <option value="{{ str_pad($i, 2, '0', STR_PAD_LEFT) }}"
                                                            {{ date('m') == $i ? 'selected' : '' }}>
                                                            {{ str_pad($i, 2, '0', STR_PAD_LEFT) }}</option>
                                                    @endfor
                                                </select>
                                            </div>
                                            <div class="col-auto">
                                                <select class="form-select form-select-sm" id="eventEndDay"
                                                    name="event_end_day" style="width: 70px;">
                                                    @for ($i = 1; $i <= 31; $i++)
                                                        <option value="{{ str_pad($i, 2, '0', STR_PAD_LEFT) }}"
                                                            {{ date('d') == $i ? 'selected' : '' }}>
                                                            {{ str_pad($i, 2, '0', STR_PAD_LEFT) }}</option>
                                                    @endfor
                                                </select>
                                            </div>
                                            <div class="col-auto">
                                                <select class="form-select form-select-sm" id="eventEndYear"
                                                    name="event_end_year" style="width: 90px;">
                                                    @for ($i = date('Y'); $i <= date('Y') + 5; $i++)
                                                        <option value="{{ $i }}"
                                                            {{ date('Y') == $i ? 'selected' : '' }}>{{ $i }}
                                                        </option>
                                                    @endfor
                                                </select>
                                            </div>
                                            <div class="col-auto d-flex align-items-center">
                                                <span class="me-2">at</span>
                                            </div>
                                            <div class="col-auto">
                                                <select class="form-select form-select-sm" id="eventEndHour"
                                                    name="event_end_hour" style="width: 70px;">
                                                    @for ($i = 0; $i <= 23; $i++)
                                                        <option value="{{ str_pad($i, 2, '0', STR_PAD_LEFT) }}"
                                                            {{ date('H') == $i ? 'selected' : '' }}>
                                                            {{ str_pad($i, 2, '0', STR_PAD_LEFT) }}</option>
                                                    @endfor
                                                </select>
                                            </div>
                                            <div class="col-auto">
                                                <select class="form-select form-select-sm" id="eventEndMinute"
                                                    name="event_end_minute" style="width: 70px;">
                                                    @for ($i = 0; $i <= 59; $i += 1)
                                                        <option value="{{ str_pad($i, 2, '0', STR_PAD_LEFT) }}"
                                                            {{ date('i') == $i ? 'selected' : '' }}>
                                                            {{ str_pad($i, 2, '0', STR_PAD_LEFT) }}</option>
                                                    @endfor
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Right Column -->
                                <div class="col-md-6">
                                    <!-- Status & Assigned User -->
                                    <div class="mb-4">
                                        <strong class="text-dark d-block mb-1" style="font-weight: 600;">Status & Assigned
                                            User</strong>
                                        <span class="text-muted" id="eventStatusAssignedUser"
                                            style="color: #6c757d;">{{ $lead->status ? $lead->status->name : 'New' }}
                                            @if ($lead->addedBy)
                                                ({{ $lead->addedBy->name }})
                                            @endif
                                        </span>
                                    </div>

                                    <!-- Assign To Section -->
                                    <div class="mb-4">
                                        <label class="form-label fw-bold text-dark mb-2">Assign To:</label>
                                        <select class="form-select" id="eventAssignTo" name="assigned_to">
                                            <option value="">Select User</option>
                                            @foreach ($users as $user)
                                                <option value="{{ $user->id }}">User: {{ $user->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <!-- Event Details Section -->
                            <div class="mb-4">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <label class="form-label fw-bold text-dark mb-0">Event Details:</label>
                                    <div class="dropdown">
                                        <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button"
                                            id="insertEventTextDropdown" data-bs-toggle="dropdown">
                                            Insert Text
                                        </button>
                                        <ul class="dropdown-menu" aria-labelledby="insertEventTextDropdown">
                                            <li><a class="dropdown-item" href="javascript:void(0);"
                                                    onclick="insertEventText('Call Requested')">Call Requested</a></li>
                                            <li><a class="dropdown-item" href="javascript:void(0);"
                                                    onclick="insertEventText('Make Initial Call')">Make Initial Call</a>
                                            </li>
                                            <li><a class="dropdown-item" href="javascript:void(0);"
                                                    onclick="insertEventText('Send Initial Email')">Send Initial Email</a>
                                            </li>
                                            <li><a class="dropdown-item" href="javascript:void(0);"
                                                    onclick="insertEventText('Send Initial Letter')">Send Initial
                                                    Letter</a></li>
                                            <li><a class="dropdown-item" href="javascript:void(0);"
                                                    onclick="insertEventText('Make Follow-Up Call')">Make Follow-Up
                                                    Call</a></li>
                                            <li><a class="dropdown-item" href="javascript:void(0);"
                                                    onclick="insertEventText('Send Follow-Up Email')">Send Follow-Up
                                                    Email</a></li>
                                            <li><a class="dropdown-item" href="javascript:void(0);"
                                                    onclick="insertEventText('Send Follow-Up Letter')">Send Follow-Up
                                                    Letter</a></li>
                                            <li><a class="dropdown-item" href="javascript:void(0);"
                                                    onclick="insertEventText('Review Lead')">Review Lead</a></li>
                                            <li><a class="dropdown-item" href="javascript:void(0);"
                                                    onclick="insertEventText('Process Lead')">Process Lead</a></li>
                                            <li><a class="dropdown-item" href="javascript:void(0);"
                                                    onclick="insertEventText('Close Lead')">Close Lead</a></li>
                                        </ul>
                                    </div>
                                </div>
                                <textarea class="form-control" id="eventDetails" name="field_1" rows="8" placeholder="Enter event details..."
                                    style="resize: vertical; border: 1px solid #dee2e6;"></textarea>
                            </div>

                            <!-- Bottom Action Section -->
                            <div class="mb-3">
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="checkbox" id="eventAssignLeadToUser"
                                        name="assign_lead_to_user">
                                    <label class="form-check-label" for="eventAssignLeadToUser">Assign Lead to
                                        User</label>
                                </div>
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="checkbox" id="createEventCheck"
                                        name="create_event_check" checked>
                                    <label class="form-check-label" for="createEventCheck">Create a</label>
                                    <select class="form-select form-select-sm d-inline-block ms-2" id="createEventType"
                                        name="create_event_type" style="width: 120px; display: inline-block;">
                                        <option value="Task">Task</option>
                                        <option value="Event" selected>Event</option>
                                        <option value="Appointment">Appointment</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="row g-3" id="standardFields">
                            <div class="col-md-6">
                                <label for="activityType" class="form-label">Type <span
                                        class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="activityType" name="type" required
                                    readonly>
                            </div>
                            <div class="col-md-6">
                                <label for="activityDate" class="form-label">Date <span
                                        class="text-danger">*</span></label>
                                <input type="date" class="form-control" id="activityDate" name="date" required>
                            </div>

                            <!-- Common Fields -->
                            <div class="col-12" id="descriptionField">
                                <label for="activityDescription" class="form-label">Description/Notes</label>
                                <div class="d-flex justify-content-between align-items-center mb-1" id="emailBodyActions"
                                    style="display: none;">
                                    <small class="text-muted">Type or paste plain text - it will be automatically converted
                                        to HTML format when saved</small>
                                </div>
                                <textarea class="form-control" id="activityDescription" name="description" rows="4"
                                    placeholder="Enter description or notes..."></textarea>
                                <small class="text-muted" id="emailBodyHint" style="display: none;">Tip: Type or paste
                                    plain text here. It will be automatically converted to HTML format (with line breaks and
                                    links) when saved.</small>
                            </div>
                        </div>

                        <!-- Note Details Section (for Note type) -->
                        <div id="noteDetailsSection" style="display: none;">
                            <label for="activityDescriptionNote" class="form-label text-dark mb-2"
                                style="font-weight: 500;">Note Details:</label>
                            <textarea class="form-control" id="activityDescriptionNote" name="field_1" rows="8"
                                placeholder="Enter note details..." style="resize: vertical; border: 1px solid #dee2e6;"></textarea>
                        </div>

                        <!-- Email Specific Fields -->
                        <div class="col-12" id="emailFields" style="display: none;">
                            <div class="row g-2">
                                <div class="col-12">
                                    <label for="activityEmail" class="form-label">Email Address <span
                                            class="text-danger">*</span></label>
                                    <input type="email" class="form-control" id="activityEmail" name="email"
                                        placeholder="recipient@example.com" required>
                                </div>
                                <div class="col-12">
                                    <label for="activityEmailSubject" class="form-label">Subject <span
                                            class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="activityEmailSubject" name="field_1"
                                        placeholder="Email subject" required>
                                </div>
                                <div class="col-md-6">
                                    <label for="activityCC" class="form-label">CC</label>
                                    <input type="text" class="form-control" id="activityCC" name="cc"
                                        placeholder="cc@example.com">
                                </div>
                                <div class="col-md-6">
                                    <label for="activityBCC" class="form-label">BCC</label>
                                    <input type="text" class="form-control" id="activityBCC" name="bcc"
                                        placeholder="bcc@example.com">
                                </div>
                                <div class="col-12">
                                    <label for="activityEmailAttachment" class="form-label">Attachments</label>
                                    <input type="file" class="form-control" id="activityEmailAttachment"
                                        name="file" multiple>
                                    <small class="text-muted">You can select multiple files</small>
                                    <div id="emailAttachmentPreview" class="mt-2"></div>
                                </div>
                            </div>
                        </div>

                        <!-- Phone Call / Text Message Fields -->
                        <div class="col-12" id="phoneFields" style="display: none;">
                            <label for="activityPhone" class="form-label">Phone Number</label>
                            <input type="text" class="form-control" id="activityPhone" name="phone"
                                placeholder="Phone number">
                        </div>

                        <!-- Task/Event/Appointment Fields -->
                        <div class="col-md-6" id="priorityField" style="display: none;">
                            <label for="activityPriority" class="form-label">Priority</label>
                            <select class="form-select" id="activityPriority" name="priority">
                                <option value="">Select Priority</option>
                                <option value="Low">Low</option>
                                <option value="Medium">Medium</option>
                                <option value="High">High</option>
                            </select>
                        </div>
                        <div class="col-md-6" id="dueDateField" style="display: none;">
                            <label for="activityDueDate" class="form-label">Due Date</label>
                            <input type="date" class="form-control" id="activityDueDate" name="due_date">
                        </div>
                        <div class="col-md-6" id="endDateField" style="display: none;">
                            <label for="activityEndDate" class="form-label">End Date</label>
                            <input type="date" class="form-control" id="activityEndDate" name="end_date">
                        </div>

                        <!-- Document Upload Section -->
                        <div id="documentUploadSection" style="display: none;">
                            <div class="mb-4">
                                <label class="form-label fw-bold text-dark mb-3">Upload Document</label>
                                <div class="document-upload-area" id="documentUploadArea">
                                    <div class="document-upload-icon">
                                        <i class="bi bi-cloud-arrow-up"></i>
                                    </div>
                                    <div class="document-upload-text">Drag&Drop files here</div>
                                    <div class="document-upload-separator">or</div>
                                    <label for="documentFileInput" class="document-browse-btn" id="documentBrowseBtn"
                                        style="cursor: pointer; margin: 0; display: inline-block;">
                                        Browse Files
                                    </label>
                                    <input type="file" id="documentFileInput" name="document_files[]" multiple
                                        style="position: absolute; width: 1px; height: 1px; opacity: 0; overflow: hidden; clip: rect(0, 0, 0, 0);">
                                </div>
                                <div class="document-file-list" id="documentFileList"></div>
                            </div>
                        </div>

                        <!-- Custom Fields -->
                        <div class="col-md-6" id="field1Container" style="display: none;">
                            <label for="activityField1" class="form-label">Field 1</label>
                            <input type="text" class="form-control" id="activityField1" name="field_1">
                        </div>
                        <div class="col-md-6" id="field2Container" style="display: none;">
                            <label for="activityField2" class="form-label">Field 2</label>
                            <input type="text" class="form-control" id="activityField2" name="field_2">
                        </div>
                    </div>
            </div>
            <div class="modal-footer" style="background-color: #fff; border-top: 1px solid #dee2e6;">
                <div class="d-flex justify-content-between w-100 align-items-center">
                    <button type="button" class="btn btn-outline-primary" id="taskNextBtn"
                        style="display: none;">Next</button>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="button" class="btn btn-primary" id="activitySubmitBtn"
                            style="pointer-events: auto !important; cursor: pointer !important;">Save Activity</button>
                    </div>
                </div>
            </div>
            </form>
        </div>
    </div>
    </div>

    <!-- Document Preview Modal -->
    <div class="modal fade" id="documentPreviewModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="documentPreviewModalLabel">File Preview</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="documentPreviewBody" style="max-height: 70vh; overflow-y: auto;">
                    <!-- Preview content will be inserted here -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <style>
        /* Dropdown menu when moved to body to escape stacking context */
        body>.dropdown-menu-temp,
        body>.dropdown-menu-temp.show {
            z-index: 999999 !important;
            position: fixed !important;
            display: block !important;
            visibility: visible !important;
            opacity: 1 !important;
            background-color: #fff !important;
            border: 1px solid #dee2e6 !important;
            border-radius: 0.375rem !important;
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15) !important;
            padding: 0.5rem 0 !important;
            min-width: 200px !important;
        }

        /* Ensure dropdown menu is visible when shown */
        .btn-group .dropdown-menu.show {
            display: block !important;
            visibility: visible !important;
            opacity: 1 !important;
        }

        /* Ensure cards don't overlap dropdown */
        .card {
            position: relative;
            z-index: 1 !important;
        }

        .card.mb-3 {
            z-index: 1000 !important;
        }

        /* Document Upload Drag and Drop Styles */
        .document-upload-area {
            border: 1px dashed #d1d5db;
            border-radius: 8px;
            padding: 3rem 2rem;
            text-align: center;
            background-color: #fff;
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .document-upload-area:hover {
            border-color: #93c5fd;
            background-color: #f8fafc;
        }

        .document-upload-area.dragover {
            border-color: #60a5fa;
            background-color: #eff6ff;
        }

        .document-upload-icon {
            font-size: 3rem;
            color: #9ca3af;
            margin-bottom: 1rem;
        }

        .document-upload-icon i {
            color: #9ca3af;
        }

        .document-upload-text {
            color: #6b7280;
            font-size: 1rem;
            margin-bottom: 0.5rem;
        }

        .document-upload-separator {
            color: #9ca3af;
            font-size: 0.875rem;
            margin: 0.75rem 0;
        }

        .document-browse-btn {
            border: 1px solid #60a5fa;
            color: #3b82f6;
            background-color: #fff;
            padding: 0.5rem 1.5rem;
            border-radius: 6px;
            font-size: 0.875rem;
            cursor: pointer;
            transition: all 0.2s ease;
            font-weight: 500;
            display: inline-block;
            text-align: center;
            user-select: none;
        }

        .document-browse-btn:hover {
            background-color: #3b82f6;
            color: #fff;
            border-color: #3b82f6;
        }

        .document-file-list {
            margin-top: 1.5rem;
            text-align: left;
        }

        .document-file-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.75rem;
            background-color: #f9fafb;
            border-radius: 6px;
            margin-bottom: 0.5rem;
        }

        .document-file-name {
            color: #374151;
            font-size: 0.875rem;
        }

        .document-file-size {
            color: #6b7280;
            font-size: 0.75rem;
        }

        .document-file-actions {
            display: flex;
            gap: 0.5rem;
            align-items: center;
        }

        .document-preview-btn,
        .document-delete-btn {
            padding: 0.25rem 0.5rem;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.875rem;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            gap: 0.25rem;
        }

        .document-preview-btn {
            background-color: #3b82f6;
            color: #fff;
        }

        .document-preview-btn:hover {
            background-color: #2563eb;
        }

        .document-delete-btn {
            background-color: #ef4444;
            color: #fff;
        }

        .document-delete-btn:hover {
            background-color: #dc2626;
        }

        /* Enhanced Select2 Lead Type Styling */
        .lead-type-select+.select2-container {
            min-width: 200px;
        }

        .lead-type-select+.select2-container .select2-selection {
            border: 1px solid #ced4da;
            border-radius: 0.375rem;
            height: 31px;
            padding: 0;
            background-color: #fff;
            transition: all 0.2s ease;
        }

        .lead-type-select+.select2-container .select2-selection:hover {
            border-color: #86b7fe;
            box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.1);
        }

        .lead-type-select+.select2-container .select2-selection:focus,
        .lead-type-select+.select2-container.select2-container--focus .select2-selection {
            border-color: #86b7fe;
            outline: 0;
            box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
        }

        .lead-type-select+.select2-container .select2-selection__rendered {
            padding-left: 8px;
            padding-right: 20px;
            line-height: 29px;
            color: #212529;
            font-size: 0.875rem;
        }

        .lead-type-select+.select2-container .select2-selection__arrow {
            height: 29px;
            right: 8px;
        }

        .lead-type-select+.select2-container .select2-selection__arrow b {
            border-color: #6c757d transparent transparent transparent;
            border-width: 5px 4px 0 4px;
            margin-top: -2px;
        }

        /* Select2 Dropdown Styling */
        .select2-dropdown {
            border: 1px solid #ced4da;
            border-radius: 0.375rem;
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
            margin-top: 2px;
        }

        .select2-search--dropdown {
            padding: 8px;
        }

        .select2-search--dropdown .select2-search__field {
            border: 1px solid #ced4da;
            border-radius: 0.375rem;
            padding: 6px 12px;
            font-size: 0.875rem;
            outline: none;
        }

        .select2-search--dropdown .select2-search__field:focus {
            border-color: #86b7fe;
            box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
        }

        .select2-results__option {
            padding: 8px 12px;
            font-size: 0.875rem;
            transition: background-color 0.15s ease;
        }

        .select2-results__option--highlighted {
            background-color: #0d6efd;
            color: white;
        }

        .select2-results__option[aria-selected="true"] {
            background-color: #e7f1ff;
            color: #0d6efd;
            font-weight: 500;
        }

        .select2-results__option[aria-selected="true"].select2-results__option--highlighted {
            background-color: #0d6efd;
            color: white;
        }

        /* Badge styling for selected lead type */
        .lead-type-select+.select2-container .select2-selection__rendered .badge {
            margin-left: 4px;
            font-size: 0.7rem;
            padding: 2px 6px;
        }

        /* Custom User Popup */
        .user-popup {
            position: absolute;
            top: 100%;
            left: 0;
            margin-top: 5px;
            z-index: 999999;
            min-width: 250px;
            display: none;
        }

        .user-popup-content {
            background: white;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            overflow: hidden;
        }

        .user-popup-header {
            padding: 10px 15px;
            background: #f8f9fa;
            border-bottom: 1px solid #dee2e6;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-weight: 600;
            font-size: 14px;
        }

        .btn-close-popup {
            background: none;
            border: none;
            padding: 0;
            cursor: pointer;
            font-size: 18px;
            color: #6c757d;
            line-height: 1;
            width: 24px;
            height: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 4px;
            transition: all 0.2s;
        }

        .btn-close-popup:hover {
            background-color: #e9ecef;
            color: #212529;
        }

        .user-popup-search {
            padding: 10px 15px;
            border-bottom: 1px solid #dee2e6;
            background: #fff;
        }

        .user-popup-search input {
            width: 100%;
            border: 1px solid #ced4da;
            border-radius: 4px;
            padding: 6px 10px;
            font-size: 0.875rem;
        }

        .user-popup-search input:focus {
            border-color: #86b7fe;
            outline: 0;
            box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
        }

        .user-popup-body {
            max-height: 300px;
            overflow-y: auto;
            overflow-x: hidden;
        }

        .user-popup-item {
            padding: 10px 15px;
            cursor: pointer;
            border-bottom: 1px solid #f1f1f1;
            transition: background-color 0.2s;
            display: block;
            text-decoration: none;
            color: #212529;
        }

        .user-popup-item:hover {
            background-color: #f8f9fa;
        }

        .user-popup-item.active {
            background-color: #0d6efd;
            color: white;
        }

        .user-popup-item.active:hover {
            background-color: #0b5ed7;
        }

        .user-popup-item.none {
            font-style: italic;
            color: #6c757d;
        }

        .user-popup-item.hidden {
            display: none;
        }

        /* Scrollbar styling */
        .user-popup-body::-webkit-scrollbar {
            width: 8px;
        }

        .user-popup-body::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 4px;
        }

        .user-popup-body::-webkit-scrollbar-thumb {
            background: #888;
            border-radius: 4px;
        }

        .user-popup-body::-webkit-scrollbar-thumb:hover {
            background: #555;
        }
    </style>

@section('styles')
    <!-- Select2 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css"
        rel="stylesheet" />
@endsection

@push('scripts')
    <!-- Select2 JS -->
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        // Ensure jQuery is loaded
        if (typeof jQuery === 'undefined') {
            console.error('jQuery is not loaded!');
        }

        let editingActivityId = null;

        // Make createActivity globally accessible - define it immediately
        window.createActivity = function(type) {
            console.log('createActivity called with type:', type);

            if (typeof jQuery === 'undefined') {
                alert('jQuery is not loaded. Please refresh the page.');
                return;
            }

            // Close the dropdown first
            const dropdownButton = document.getElementById('newActivityDropdownBtn');
            if (dropdownButton) {
                const dropdownInstance = bootstrap.Dropdown.getInstance(dropdownButton);
                if (dropdownInstance) {
                    dropdownInstance.hide();
                } else {
                    // If no instance exists, try to hide it manually
                    $(dropdownButton).next('.dropdown-menu').removeClass('show');
                    dropdownButton.setAttribute('aria-expanded', 'false');
                }
            }

            editingActivityId = null;
            $('#activityModalLabel').text('Create ' + type);
            $('#activitySubmitBtn').text('Save ' + type);
            $('#activityForm')[0].reset();
            $('#activityId').val('');
            $('#activityType').val(type);
            $('#activityDate').val(new Date().toISOString().split('T')[0]);

            // Reset all fields
            $('#activityDescription').val('');
            $('#activityDescriptionNote').val('');

            // Show/hide fields based on activity type
            if (typeof window.showActivityFields === 'function') {
                window.showActivityFields(type);
            } else {
                console.error('showActivityFields function not found');
            }

            const modalElement = document.getElementById('activityModal');
            if (modalElement) {
                try {
                    const modal = new bootstrap.Modal(modalElement);
                    modal.show();
                    console.log('Modal opened successfully');
                } catch (e) {
                    console.error('Error opening modal:', e);
                    alert('Error opening modal. Please refresh the page.');
                }
            } else {
                console.error('Activity modal element not found');
                alert('Error: Activity modal not found. Please refresh the page.');
            }
        };

        $(document).ready(function() {
            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });

            console.log('Lead details page scripts loaded');
            console.log('createActivity function available:', typeof window.createActivity);

            // Initialize Bootstrap dropdowns (but exclude our custom dropdown in .btn-group)
            try {
                var dropdownElementList = [].slice.call(document.querySelectorAll('.dropdown-toggle'));
                // Filter out dropdowns in .btn-group (our custom dropdown)
                dropdownElementList = dropdownElementList.filter(function(el) {
                    return !el.closest('.btn-group');
                });
                var dropdownList = dropdownElementList.map(function(dropdownToggleEl) {
                    try {
                        return new bootstrap.Dropdown(dropdownToggleEl);
                    } catch (e) {
                        console.error('Error initializing dropdown:', e);
                        return null;
                    }
                });
                console.log('Initialized', dropdownList.length, 'dropdowns (excluded .btn-group dropdowns)');
            } catch (e) {
                console.error('Bootstrap dropdown initialization failed:', e);
            }

            // Manual dropdown toggle - works regardless of Bootstrap
            // Use capture phase to check state before Bootstrap processes it
            $(document).on('click', '.btn-group .dropdown-toggle', function(e) {
                e.preventDefault();
                e.stopPropagation();
                e.stopImmediatePropagation();

                const $this = $(this);
                const $btnGroup = $this.closest('.btn-group');

                console.log('Dropdown button clicked');

                // First, restore any dropdown that was moved to body for this button group
                $('body > .dropdown-menu-temp').each(function() {
                    const $tempMenu = $(this);
                    const originalParent = $tempMenu.data('original-parent');
                    if (originalParent && originalParent.length && originalParent.is($btnGroup)) {
                        $tempMenu.removeClass('dropdown-menu-temp').removeData('original-parent')
                            .appendTo($btnGroup);
                    }
                });

                // Now find the menu in the button group
                let $menu = $btnGroup.find('.dropdown-menu');

                console.log('Menu found:', $menu.length);
                console.log('Menu HTML:', $menu.length > 0 ? $menu[0].outerHTML.substring(0, 100) :
                    'Not found');

                if ($menu.length) {
                    // Check if dropdown is actually visible/open by checking computed styles
                    // Since we removed data-bs-toggle, Bootstrap won't interfere
                    const computedDisplay = window.getComputedStyle($menu[0]).display;
                    const computedVisibility = window.getComputedStyle($menu[0]).visibility;
                    const computedOpacity = window.getComputedStyle($menu[0]).opacity;
                    const computedPosition = window.getComputedStyle($menu[0]).position;
                    const rect = $menu[0].getBoundingClientRect();
                    const isCurrentlyOpen = computedDisplay === 'block' &&
                        computedVisibility === 'visible' &&
                        parseFloat(computedOpacity) > 0 &&
                        rect.width > 0 &&
                        rect.height > 0;

                    console.log('Is currently open:', isCurrentlyOpen);
                    console.log('Computed styles - Display:', computedDisplay, 'Visibility:',
                        computedVisibility, 'Opacity:', computedOpacity, 'Position:', computedPosition);
                    console.log('Bounding rect:', rect);

                    // Close all other dropdowns first
                    $('.btn-group .dropdown-menu').not($menu).removeClass('show').attr('style',
                        'display: none !important; visibility: hidden !important; opacity: 0 !important;'
                        );

                    // Restore and close any other dropdowns that were moved to body
                    $('body > .dropdown-menu-temp').not($menu).each(function() {
                        const $otherMenu = $(this);
                        const $otherParent = $otherMenu.data('original-parent');
                        if ($otherParent && $otherParent.length) {
                            $otherMenu.removeClass('dropdown-menu-temp show').removeData(
                                'original-parent').attr('style',
                                'display: none !important; visibility: hidden !important; opacity: 0 !important;'
                                ).appendTo($otherParent);
                        }
                    });

                    // Toggle this dropdown
                    if (isCurrentlyOpen) {
                        // Close it
                        $menu.removeClass('show').attr('style',
                            'display: none !important; visibility: hidden !important; opacity: 0 !important;'
                            );
                        // If it was moved to body, move it back
                        if ($menu.parent().is('body')) {
                            $menu.removeClass('dropdown-menu-temp').removeData('original-parent').appendTo(
                                $btnGroup);
                        }
                        console.log('Dropdown closed');
                    } else {
                        // Open it
                        // Ensure menu is in btn-group first
                        if (!$menu.parent().is($btnGroup)) {
                            if ($menu.parent().is('body')) {
                                $menu.removeClass('dropdown-menu-temp').removeData('original-parent');
                            }
                            $menu.appendTo($btnGroup);
                        }

                        // Calculate position using fixed positioning to appear above card
                        const buttonOffset = $this.offset();
                        const buttonHeight = $this.outerHeight();
                        const scrollTop = $(window).scrollTop();
                        const scrollLeft = $(window).scrollLeft();

                        const topPos = buttonOffset.top + buttonHeight + 5 - scrollTop;
                        const leftPos = buttonOffset.left - scrollLeft;

                        console.log('Opening dropdown at:', topPos, leftPos);
                        console.log('Button offset:', buttonOffset, 'Button height:', buttonHeight,
                            'Scroll:', scrollTop, scrollLeft);

                        // First, clear any existing inline styles
                        $menu.removeAttr('style');
                        $menu.removeClass('show');

                        // Move dropdown to body to escape any stacking context issues
                        if (!$menu.parent().is('body')) {
                            // Store reference to original parent
                            $menu.data('original-parent', $btnGroup);
                            $menu.addClass('dropdown-menu-temp').appendTo('body');
                        }

                        // Wait for DOM update, then apply styles
                        setTimeout(function() {
                            // Apply styles with !important using attr - must be done after moving to body
                            const styleString =
                                'display: block !important; ' +
                                'visibility: visible !important; ' +
                                'opacity: 1 !important; ' +
                                'position: fixed !important; ' +
                                'top: ' + topPos + 'px !important; ' +
                                'left: ' + leftPos + 'px !important; ' +
                                'z-index: 999999 !important; ' +
                                'min-width: 200px !important; ' +
                                'transform: none !important; ' +
                                'margin-top: 0 !important; ' +
                                'background-color: #fff !important; ' +
                                'border: 1px solid #dee2e6 !important; ' +
                                'border-radius: 0.375rem !important; ' +
                                'box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15) !important; ' +
                                'padding: 0.5rem 0 !important;';

                            $menu.addClass('show').attr('style', styleString);

                            // Also set directly on the element
                            const menuEl = $menu[0];
                            menuEl.style.setProperty('display', 'block', 'important');
                            menuEl.style.setProperty('visibility', 'visible', 'important');
                            menuEl.style.setProperty('opacity', '1', 'important');
                            menuEl.style.setProperty('position', 'fixed', 'important');
                            menuEl.style.setProperty('top', topPos + 'px', 'important');
                            menuEl.style.setProperty('left', leftPos + 'px', 'important');
                            menuEl.style.setProperty('z-index', '999999', 'important');
                            menuEl.style.setProperty('min-width', '200px', 'important');

                            // Force a reflow
                            menuEl.offsetHeight;

                            // Verify it's visible
                            setTimeout(function() {
                                const finalRect = menuEl.getBoundingClientRect();
                                const finalDisplay = window.getComputedStyle(menuEl)
                                .display;
                                const finalVisibility = window.getComputedStyle(menuEl)
                                    .visibility;
                                const finalOpacity = window.getComputedStyle(menuEl)
                                .opacity;
                                console.log('After opening - Display:', finalDisplay,
                                    'Visibility:', finalVisibility, 'Opacity:',
                                    finalOpacity);
                                console.log('Rect:', finalRect);
                                console.log('Menu is visible:', finalDisplay === 'block' &&
                                    finalVisibility === 'visible' && finalRect.width >
                                    0 && finalRect.height > 0);

                                if (finalDisplay !== 'block' || finalRect.width === 0) {
                                    console.error(
                                        'Dropdown still not visible! Trying alternative method...'
                                        );
                                    // Try alternative: clone and replace
                                    const $clone = $menu.clone(true);
                                    $menu.replaceWith($clone);
                                    $clone.attr('style', styleString);
                                    $clone.addClass('show dropdown-menu-temp');
                                    $clone.data('original-parent', $btnGroup);
                                }
                            }, 50);
                        }, 10);
                    }
                } else {
                    console.error('Dropdown menu not found for button group');
                    console.log('Button group HTML:', $btnGroup[0].outerHTML);
                }
            });

            // Close dropdown when clicking outside
            $(document).on('click', function(e) {
                if (!$(e.target).closest('.btn-group').length && !$(e.target).closest('.dropdown-menu')
                    .length) {
                    // Close all dropdowns in btn-groups
                    $('.btn-group .dropdown-menu').removeClass('show').css({
                        'display': 'none',
                        'visibility': 'hidden'
                    });

                    // Move dropdowns back to their original position if they were moved to body
                    $('body > .dropdown-menu-temp').each(function() {
                        const $menu = $(this);
                        const $btnGroup = $menu.data('original-parent');
                        if ($btnGroup && $btnGroup.length) {
                            $menu.removeClass('dropdown-menu-temp show').removeData(
                                'original-parent').css({
                                'display': 'none',
                                'visibility': 'hidden'
                            }).appendTo($btnGroup);
                        }
                    });
                }
            });

            // Also add event listeners as fallback for activity dropdown items
            $(document).on('click', '.dropdown-item[onclick*="createActivity"]', function(e) {
                e.preventDefault();
                e.stopPropagation();

                // Close the dropdown
                const dropdown = bootstrap.Dropdown.getInstance($(this).closest('.btn-group').find(
                    '.dropdown-toggle')[0]);
                if (dropdown) {
                    dropdown.hide();
                }

                const onclickAttr = $(this).attr('onclick');
                if (onclickAttr) {
                    const match = onclickAttr.match(/createActivity\('([^']+)'\)/);
                    if (match && match[1]) {
                        const activityType = match[1];
                        console.log('Event listener triggered for:', activityType);
                        if (typeof window.createActivity === 'function') {
                            window.createActivity(activityType);
                        } else {
                            console.error('createActivity function not available');
                            alert('Error: Activity creation function not loaded. Please refresh the page.');
                        }
                    }
                } else {
                    // Try to get activity type from text content
                    const activityType = $(this).text().trim();
                    console.log('No onclick found, trying text content:', activityType);
                    if (typeof window.createActivity === 'function') {
                        window.createActivity(activityType);
                    }
                }
            });
        });

        function editLead(id) {
            // Redirect to index page with edit parameter to open edit modal
            window.location.href = '/leads?edit=' + id;
        }

        // Make showActivityFields globally accessible
        window.showActivityFields = function(type) {
            // Hide all conditional fields first
            $('#emailFields, #phoneFields, #priorityField, #dueDateField, #endDateField, #field1Container, #field2Container, #noteInfoSections, #noteDetailsSection, #taskSpecificSection, #eventSpecificSection, #viewCalendarLink, #taskNextBtn, #emailBodyActions, #documentUploadSection')
                .hide();
            $('#standardFields').show();
            $('#openLeadLink').hide();
            // Reset modal header styling
            $('#activityModalHeader').removeClass('pb-2').addClass('border-0 pb-0');
            $('#activityModalBody').addClass('pt-3');

            // Show common fields
            $('#descriptionField').show();

            // Show type-specific fields
            switch (type) {
                case 'Email':
                    $('#emailFields').show();
                    $('#descriptionField label').text('Email Body (HTML)');
                    $('#descriptionField').show();
                    $('#activityDescription').attr('rows', '10');
                    $('#emailBodyHint').show();
                    $('#emailBodyActions').show();
                    break;
                case 'Telephone Call':
                case 'Text Message':
                    $('#phoneFields').show();
                    $('#descriptionField label').text('Call/Message Notes');
                    break;
                case 'Task':
                    // Hide standard fields and show Task-specific layout
                    $('#standardFields').hide();
                    $('#noteInfoSections').show();
                    $('#taskSpecificSection').show();
                    $('#viewCalendarLink').show();
                    $('#openLeadLink').show();
                    $('#taskNextBtn').show();
                    // Update modal header styling for Task
                    $('#activityModalHeader').removeClass('border-0 pb-0').addClass('pb-2');
                    $('#activityModalBody').removeClass('pt-3');
                    // Set default date/time to current
                    const now = new Date();
                    $('#taskDueMonth').val(String(now.getMonth() + 1).padStart(2, '0'));
                    $('#taskDueDay').val(String(now.getDate()).padStart(2, '0'));
                    $('#taskDueYear').val(now.getFullYear());
                    $('#taskDueHour').val(String(now.getHours()).padStart(2, '0'));
                    $('#taskDueMinute').val(String(now.getMinutes()).padStart(2, '0'));
                    break;
                case 'Event':
                    // Hide standard fields and show Event-specific layout
                    $('#standardFields').hide();
                    $('#noteInfoSections').hide();
                    $('#eventSpecificSection').show();
                    $('#viewCalendarLink').show();
                    $('#openLeadLink').show();
                    $('#taskNextBtn').show();
                    // Update modal header styling for Event
                    $('#activityModalHeader').removeClass('border-0 pb-0').addClass('pb-2');
                    $('#activityModalBody').removeClass('pt-3');
                    // Set default date/time to current for start
                    const eventNow = new Date();
                    $('#eventStartMonth').val(String(eventNow.getMonth() + 1).padStart(2, '0'));
                    $('#eventStartDay').val(String(eventNow.getDate()).padStart(2, '0'));
                    $('#eventStartYear').val(eventNow.getFullYear());
                    $('#eventStartHour').val(String(eventNow.getHours()).padStart(2, '0'));
                    $('#eventStartMinute').val(String(eventNow.getMinutes()).padStart(2, '0'));
                    // Set default end time to 1 hour after start
                    const eventEnd = new Date(eventNow);
                    eventEnd.setHours(eventEnd.getHours() + 1);
                    $('#eventEndMonth').val(String(eventEnd.getMonth() + 1).padStart(2, '0'));
                    $('#eventEndDay').val(String(eventEnd.getDate()).padStart(2, '0'));
                    $('#eventEndYear').val(eventEnd.getFullYear());
                    $('#eventEndHour').val(String(eventEnd.getHours()).padStart(2, '0'));
                    $('#eventEndMinute').val(String(eventEnd.getMinutes()).padStart(2, '0'));
                    break;
                case 'Appointment':
                    $('#priorityField, #dueDateField').show();
                    $('#endDateField').show();
                    $('#descriptionField label').text('Description');
                    break;
                case 'Note':
                    // Hide standard fields and show Note-specific layout
                    $('#standardFields').hide();
                    $('#noteInfoSections').show();
                    $('#noteDetailsSection').show();
                    $('#openLeadLink').show();
                    // Update modal header styling for Note
                    $('#activityModalHeader').removeClass('border-0 pb-0').addClass('pb-2');
                    $('#activityModalBody').removeClass('pt-3');
                    // Note type uses field_1 directly, no sync needed
                    break;
                case 'Letter':
                    $('#field1Container, #field2Container').show();
                    $('#descriptionField label').text('Description');
                    break;
                case 'Document':
                    // Hide standard fields (Type and Date)
                    $('#standardFields').hide();
                    // Hide description field
                    $('#descriptionField').hide();
                    // Set default date to today if not already set
                    if (!$('#activityDate').val()) {
                        const today = new Date();
                        const dateStr = today.toISOString().split('T')[0];
                        $('#activityDate').val(dateStr);
                    }
                    // Show only document upload section
                    $('#documentUploadSection').show();
                    // Initialize drag and drop when Document type is selected
                    setTimeout(function() {
                        initializeDocumentDragDrop();
                    }, 100);
                    break;
                default:
                    $('#descriptionField label').text('Description');
            }
        };

        // Function to toggle End Date/Time section
        function toggleEndDateTime() {
            const endSection = $('#endDateTimeSection');
            if (endSection.is(':visible')) {
                endSection.hide();
                $('#addEndDateTimeLink').text('- Add an End Date/Time');
            } else {
                endSection.show();
                $('#addEndDateTimeLink').text('- Remove End Date/Time');
            }
        }

        // Function to insert predefined text into Task Details
        function insertTaskText(text) {
            const taskDetails = $('#taskDetails');
            const currentText = taskDetails.val();
            const newText = currentText ? currentText + '\n' + text : text;
            taskDetails.val(newText);
            taskDetails.focus();
        }

        // Function to remove email attachment
        function removeEmailAttachment() {
            $('#emailAttachmentPreview').empty();
            $('#activityEmailAttachment').val('');
        }

        // Function to paste HTML source code
        window.pasteHtmlSource = function() {
            // Prompt user to paste HTML
            const htmlContent = prompt('Paste HTML source code here:');
            if (htmlContent && htmlContent.trim()) {
                const textarea = document.getElementById('activityDescription');
                const start = textarea.selectionStart || 0;
                const end = textarea.selectionEnd || 0;
                const currentValue = textarea.value;
                const newValue = currentValue.substring(0, start) + htmlContent + currentValue.substring(end);
                textarea.value = newValue;
                // Set cursor position after inserted content
                const newCursorPos = start + htmlContent.length;
                textarea.setSelectionRange(newCursorPos, newCursorPos);
                textarea.focus();
            }
        };

        // Function to convert plain text to HTML
        function convertTextToHtml(text) {
            if (!text || !text.trim()) {
                return '';
            }

            // Escape HTML special characters first
            let html = text
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;');

            // Convert URLs to links
            const urlRegex = /(https?:\/\/[^\s]+)/g;
            html = html.replace(urlRegex, '<a href="$1" target="_blank">$1</a>');

            // Convert line breaks to <br> tags
            html = html.replace(/\n/g, '<br>');

            // Wrap in a simple HTML structure
            return `<div style="font-family: Arial, sans-serif; font-size: 14px; line-height: 1.6; color: #333;">${html}</div>`;
        }

        // Function to convert HTML to plain text (for editing)
        function convertHtmlToText(html) {
            if (!html || !html.trim()) {
                return '';
            }

            // Create a temporary div element
            const tempDiv = document.createElement('div');
            tempDiv.innerHTML = html;

            // Get text content and preserve line breaks
            let text = tempDiv.textContent || tempDiv.innerText || '';

            // Convert <br> tags back to newlines
            text = text.replace(/<br\s*\/?>/gi, '\n');

            // Clean up multiple newlines
            text = text.replace(/\n{3,}/g, '\n\n');

            return text.trim();
        }

        // Handle file input change for email attachments
        $(document).on('change', '#activityEmailAttachment', function() {
            const files = this.files;
            const preview = $('#emailAttachmentPreview');
            preview.empty();

            if (files.length > 0) {
                let fileList =
                    '<div class="alert alert-info"><i class="bi bi-paperclip me-2"></i><strong>Selected Files:</strong><ul class="mb-0 mt-2">';
                for (let i = 0; i < files.length; i++) {
                    fileList += `<li>${files[i].name} (${(files[i].size / 1024).toFixed(2)} KB)</li>`;
                }
                fileList += '</ul></div>';
                preview.html(fileList);
            }
        });

        // Document Upload Functionality
        let documentFiles = [];

        // Make upload area clickable (but not when clicking the browse label)
        $(document).on('click', '#documentUploadArea', function(e) {
            // Don't trigger if clicking on the browse label or its children
            if (e.target.id === 'documentBrowseBtn' ||
                $(e.target).closest('#documentBrowseBtn').length > 0 ||
                $(e.target).closest('label[for="documentFileInput"]').length > 0) {
                return;
            }

            // Trigger file input click
            const fileInput = document.getElementById('documentFileInput');
            if (fileInput) {
                fileInput.click();
            }
        });

        // File input change handler
        $(document).on('change', '#documentFileInput', function() {
            handleDocumentFiles(this.files);
        });

        // Initialize drag and drop handlers
        let dragDropInitialized = false;

        function initializeDocumentDragDrop() {
            const documentUploadArea = document.getElementById('documentUploadArea');
            if (documentUploadArea && !dragDropInitialized) {
                // Prevent default drag behaviors
                ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
                    documentUploadArea.addEventListener(eventName, preventDefaults, false);
                });

                // Highlight drop area when item is dragged over it
                ['dragenter', 'dragover'].forEach(eventName => {
                    documentUploadArea.addEventListener(eventName, highlight, false);
                });

                ['dragleave', 'drop'].forEach(eventName => {
                    documentUploadArea.addEventListener(eventName, unhighlight, false);
                });

                // Handle dropped files
                documentUploadArea.addEventListener('drop', handleDrop, false);

                dragDropInitialized = true;
            }
        }

        // Reset flag when modal is hidden
        $('#activityModal').on('hidden.bs.modal', function() {
            dragDropInitialized = false;
        });

        function preventDefaults(e) {
            e.preventDefault();
            e.stopPropagation();
        }

        function highlight(e) {
            $('#documentUploadArea').addClass('dragover');
        }

        function unhighlight(e) {
            $('#documentUploadArea').removeClass('dragover');
        }

        function handleDrop(e) {
            const dt = e.dataTransfer;
            const files = dt.files;
            handleDocumentFiles(files);
        }

        function handleDocumentFiles(files) {
            const fileInput = document.getElementById('documentFileInput');
            const dataTransfer = new DataTransfer();

            // Add existing files
            for (let i = 0; i < documentFiles.length; i++) {
                dataTransfer.items.add(documentFiles[i]);
            }

            // Add new files
            for (let i = 0; i < files.length; i++) {
                // Check if file already exists
                const fileExists = documentFiles.some(f => f.name === files[i].name && f.size === files[i].size);
                if (!fileExists) {
                    documentFiles.push(files[i]);
                    dataTransfer.items.add(files[i]);
                }
            }

            // Update file input
            fileInput.files = dataTransfer.files;

            // Display file list
            displayDocumentFiles();
        }

        function displayDocumentFiles() {
            const fileList = $('#documentFileList');
            fileList.empty();

            if (documentFiles.length > 0) {
                documentFiles.forEach((file, index) => {
                    const fileSize = (file.size / 1024).toFixed(2);
                    const fileItem = `
                            <div class="document-file-item">
                                <div>
                                    <div class="document-file-name">${file.name}</div>
                                    <div class="document-file-size">${fileSize} KB</div>
                                </div>
                                <div class="document-file-actions">
                                    <button type="button" class="document-preview-btn" onclick="previewDocumentFile(${index})">
                                        <i class="bi bi-eye"></i> 
                                    </button>
                                    <button type="button" class="document-delete-btn" onclick="removeDocumentFile(${index})">
                                        <i class="bi bi-trash"></i> 
                                    </button>
                                </div>
                            </div>
                        `;
                    fileList.append(fileItem);
                });
            }
        }

        // Preview file function
        window.previewDocumentFile = function(index) {
            if (index < 0 || index >= documentFiles.length) {
                return;
            }

            const file = documentFiles[index];
            const previewBody = $('#documentPreviewBody');
            const previewLabel = $('#documentPreviewModalLabel');

            // Set modal title
            previewLabel.text(`Preview: ${file.name}`);

            // Clear previous content
            previewBody.empty();

            // Check file type and show appropriate preview
            const fileType = file.type;
            const fileName = file.name.toLowerCase();

            // Image files
            if (fileType.startsWith('image/')) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    previewBody.html(`
                            <div class="text-center">
                                <img src="${e.target.result}" alt="${file.name}" class="img-fluid" style="max-width: 100%; height: auto;">
                            </div>
                        `);
                };
                reader.readAsDataURL(file);
            }
            // PDF files
            else if (fileType === 'application/pdf' || fileName.endsWith('.pdf')) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    previewBody.html(`
                            <div class="text-center">
                                <iframe src="${e.target.result}" style="width: 100%; height: 600px; border: 1px solid #dee2e6;" frameborder="0"></iframe>
                            </div>
                        `);
                };
                reader.readAsDataURL(file);
            }
            // Text files
            else if (fileType.startsWith('text/') || fileName.endsWith('.txt') || fileName.endsWith('.csv') || fileName
                .endsWith('.json') || fileName.endsWith('.xml') || fileName.endsWith('.html') || fileName.endsWith(
                    '.css') || fileName.endsWith('.js')) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    previewBody.html(`
                            <pre style="background-color: #f8f9fa; padding: 1rem; border-radius: 4px; overflow-x: auto; white-space: pre-wrap; word-wrap: break-word;">${e.target.result}</pre>
                        `);
                };
                reader.readAsText(file);
            }
            // Video files
            else if (fileType.startsWith('video/')) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    previewBody.html(`
                            <div class="text-center">
                                <video controls style="max-width: 100%; height: auto;">
                                    <source src="${e.target.result}" type="${fileType}">
                                    Your browser does not support the video tag.
                                </video>
                            </div>
                        `);
                };
                reader.readAsDataURL(file);
            }
            // Audio files
            else if (fileType.startsWith('audio/')) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    previewBody.html(`
                            <div class="text-center">
                                <audio controls style="width: 100%;">
                                    <source src="${e.target.result}" type="${fileType}">
                                    Your browser does not support the audio tag.
                                </audio>
                            </div>
                        `);
                };
                reader.readAsDataURL(file);
            }
            // Unsupported file types
            else {
                const fileSize = (file.size / 1024).toFixed(2);
                previewBody.html(`
                        <div class="text-center p-4">
                            <i class="bi bi-file-earmark" style="font-size: 4rem; color: #6c757d;"></i>
                            <h5 class="mt-3">${file.name}</h5>
                            <p class="text-muted">File Type: ${fileType || 'Unknown'}</p>
                            <p class="text-muted">File Size: ${fileSize} KB</p>
                            <p class="text-muted mt-3">Preview not available for this file type.</p>
                            <p class="text-muted">Please download the file to view its contents.</p>
                        </div>
                    `);
            }

            // Show modal
            const previewModal = new bootstrap.Modal(document.getElementById('documentPreviewModal'));
            previewModal.show();
        };

        // Remove file function
        window.removeDocumentFile = function(index) {
            documentFiles.splice(index, 1);

            // Update file input
            const fileInput = document.getElementById('documentFileInput');
            const dataTransfer = new DataTransfer();
            documentFiles.forEach(file => {
                dataTransfer.items.add(file);
            });
            fileInput.files = dataTransfer.files;

            // Update display
            displayDocumentFiles();
        };

        // Reset document files when modal is hidden
        $('#activityModal').on('hidden.bs.modal', function() {
            documentFiles = [];
            $('#documentFileList').empty();
            $('#documentFileInput').val('');
        });

        // Preview document from server
        window.previewDocumentFromServer = function(activityId) {
            const previewBody = $('#documentPreviewBody');
            const previewLabel = $('#documentPreviewModalLabel');

            // Show loading state
            previewBody.html(
                '<div class="text-center p-4"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div></div>'
                );

            // Fetch activity data
            $.ajax({
                url: `/activities/${activityId}/edit`,
                method: 'GET',
                success: function(response) {
                    if (response.success && response.activity) {
                        const activity = response.activity;
                        console.log('Activity data:', activity);
                        console.log('File field:', activity.file);

                        // Check if file field exists and has content
                        let files = [];
                        if (activity.file) {
                            if (typeof activity.file === 'string') {
                                files = activity.file.split(',').map(f => f.trim()).filter(f => f.length >
                                    0);
                            } else if (Array.isArray(activity.file)) {
                                files = activity.file;
                            }
                        }

                        console.log('Files array:', files);

                        if (files.length === 0) {
                            previewLabel.text('Preview: No Files');
                            previewBody.html(`
                                    <div class="text-center p-4">
                                        <i class="bi bi-file-earmark" style="font-size: 4rem; color: #6c757d;"></i>
                                        <p class="text-muted mt-3">No files found for this document.</p>
                                        <p class="text-muted small">Activity ID: ${activity.id}</p>
                                        <p class="text-muted small">File field value: ${activity.file || 'null'}</p>
                                    </div>
                                `);
                        } else {
                            previewLabel.text(`Preview: ${activity.type || 'Document'}`);

                            // Display all files
                            let previewContent = '<div class="mb-3"><strong>Files:</strong></div>';

                            files.forEach(function(filePath, index) {
                                filePath = filePath.trim();
                                const fileName = filePath.split('/').pop() || filePath;
                                const fileUrl = filePath.startsWith('http') ? filePath :
                                    `/${filePath}`;
                                const fileExtension = fileName.split('.').pop().toLowerCase();

                                previewContent += `<div class="mb-4 p-3 border rounded">`;
                                previewContent += `<h6 class="mb-2">${fileName}</h6>`;

                                // Determine file type and show appropriate preview
                                if (['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'].includes(
                                        fileExtension)) {
                                    // Image files
                                    previewContent += `
                                            <div class="text-center">
                                                <img src="${fileUrl}" alt="${fileName}" class="img-fluid" style="max-width: 100%; height: auto; border: 1px solid #dee2e6; border-radius: 4px;">
                                            </div>
                                        `;
                                } else if (fileExtension === 'pdf') {
                                    // PDF files
                                    previewContent += `
                                            <div class="text-center">
                                                <iframe src="${fileUrl}" style="width: 100%; height: 600px; border: 1px solid #dee2e6;" frameborder="0"></iframe>
                                            </div>
                                        `;
                                } else if (['mp4', 'webm', 'ogg', 'avi', 'mov', 'wmv', 'flv', 'mkv']
                                    .includes(fileExtension)) {
                                    // Video files
                                    let videoType = 'video/mp4';
                                    if (fileExtension === 'mov') {
                                        videoType = 'video/quicktime';
                                    } else if (fileExtension === 'avi') {
                                        videoType = 'video/x-msvideo';
                                    } else if (fileExtension === 'webm') {
                                        videoType = 'video/webm';
                                    } else if (fileExtension === 'ogg') {
                                        videoType = 'video/ogg';
                                    } else if (fileExtension === 'wmv') {
                                        videoType = 'video/x-ms-wmv';
                                    } else if (fileExtension === 'flv') {
                                        videoType = 'video/x-flv';
                                    } else {
                                        videoType = `video/${fileExtension}`;
                                    }

                                    previewContent += `
                                            <div class="text-center">
                                                <video controls style="max-width: 100%; height: auto; border: 1px solid #dee2e6; border-radius: 4px;">
                                                    <source src="${fileUrl}" type="${videoType}">
                                                    Your browser does not support the video tag.
                                                </video>
                                            </div>
                                        `;
                                } else if (['mp3', 'wav', 'ogg', 'aac', 'm4a', 'flac'].includes(
                                        fileExtension)) {
                                    // Audio files
                                    previewContent += `
                                            <div class="text-center">
                                                <audio controls style="width: 100%;">
                                                    <source src="${fileUrl}" type="audio/${fileExtension}">
                                                    Your browser does not support the audio tag.
                                                </audio>
                                            </div>
                                        `;
                                } else if (['txt', 'csv', 'json', 'xml', 'html', 'css', 'js', 'md']
                                    .includes(fileExtension)) {
                                    // Text files - show placeholder, will load via AJAX
                                    previewContent += `<div class="text-center p-3" id="text-preview-${index}">
                                            <div class="spinner-border spinner-border-sm" role="status"></div> Loading...
                                        </div>`;
                                } else {
                                    // Unsupported file types
                                    previewContent += `
                                            <div class="text-center p-3">
                                                <i class="bi bi-file-earmark" style="font-size: 3rem; color: #6c757d;"></i>
                                                <p class="text-muted mt-2">Preview not available for this file type.</p>
                                                <a href="${fileUrl}" target="_blank" class="btn btn-sm btn-primary mt-2">
                                                    <i class="bi bi-download"></i> Download File
                                                </a>
                                            </div>
                                        `;
                                }

                                previewContent += `</div>`;
                            });

                            previewBody.html(previewContent);

                            // Load text files after HTML is set
                            files.forEach(function(filePath, index) {
                                filePath = filePath.trim();
                                const fileName = filePath.split('/').pop() || filePath;
                                const fileUrl = filePath.startsWith('http') ? filePath :
                                    `/${filePath}`;
                                const fileExtension = fileName.split('.').pop().toLowerCase();

                                if (['txt', 'csv', 'json', 'xml', 'html', 'css', 'js', 'md']
                                    .includes(fileExtension)) {
                                    // Fetch text file content
                                    fetch(fileUrl)
                                        .then(response => response.text())
                                        .then(text => {
                                            const textPreview =
                                                `<pre style="background-color: #f8f9fa; padding: 1rem; border-radius: 4px; overflow-x: auto; white-space: pre-wrap; word-wrap: break-word;">${text}</pre>`;
                                            $(`#text-preview-${index}`).html(textPreview);
                                        })
                                        .catch(err => {
                                            $(`#text-preview-${index}`).html(
                                                `<p class="text-muted">Unable to load file content.</p>`
                                                );
                                        });
                                }
                            });
                        }

                        // Show modal
                        const previewModal = new bootstrap.Modal(document.getElementById(
                            'documentPreviewModal'));
                        previewModal.show();
                    } else {
                        Swal.fire('Error!', 'Unable to load document.', 'error');
                    }
                },
                error: function() {
                    previewBody.html(`
                            <div class="text-center p-4">
                                <i class="bi bi-exclamation-triangle" style="font-size: 4rem; color: #dc3545;"></i>
                                <p class="text-danger mt-3">Error loading document.</p>
                            </div>
                        `);
                }
            });
        };

        // Handle paste event for Email Body textarea to preserve HTML content
        $(document).on('paste', '#activityDescription', function(e) {
            // Only handle if Email fields are visible
            if ($('#emailFields').is(':visible')) {
                e.preventDefault();
                const clipboardData = e.originalEvent.clipboardData || window.clipboardData;

                if (clipboardData) {
                    // Try to get HTML content first
                    let htmlContent = clipboardData.getData('text/html');
                    let textContent = clipboardData.getData('text/plain');

                    // If HTML content exists, use it; otherwise use plain text
                    if (htmlContent && htmlContent.trim()) {
                        // Get current cursor position
                        const textarea = this;
                        const start = textarea.selectionStart;
                        const end = textarea.selectionEnd;
                        const currentValue = $(textarea).val();

                        // Insert HTML content at cursor position
                        const newValue = currentValue.substring(0, start) + htmlContent + currentValue.substring(
                            end);
                        $(textarea).val(newValue);

                        // Restore cursor position
                        const newCursorPos = start + htmlContent.length;
                        textarea.setSelectionRange(newCursorPos, newCursorPos);
                    } else if (textContent) {
                        // Fallback to plain text if no HTML
                        const textarea = this;
                        const start = textarea.selectionStart;
                        const end = textarea.selectionEnd;
                        const currentValue = $(textarea).val();
                        const newValue = currentValue.substring(0, start) + textContent + currentValue.substring(
                            end);
                        $(textarea).val(newValue);
                        const newCursorPos = start + textContent.length;
                        textarea.setSelectionRange(newCursorPos, newCursorPos);
                    }
                }
            }
        });

        function editActivity(id) {
            editingActivityId = id;
            $.ajax({
                url: `/activities/${id}/edit`,
                method: 'GET',
                success: function(response) {
                    if (response.success) {
                        const activity = response.activity;
                        $('#activityModalLabel').text('Edit ' + activity.type);
                        $('#activitySubmitBtn').text('Update ' + activity.type);
                        $('#activityId').val(activity.id);
                        $('#activityType').val(activity.type);
                        $('#activityDate').val(activity.date);
                        // For Email type: field_1 = Subject, field_2 = Email Content
                        if (activity.type === 'Email') {
                            $('#activityEmailSubject').val(activity.field_1 || '');
                            // Convert HTML back to plain text for editing
                            const htmlContent = activity.field_2 || '';
                            const plainText = convertHtmlToText(htmlContent);
                            $('#activityDescription').val(plainText);
                        } else {
                            $('#activityDescription').val(activity.field_1 || '');
                        }
                        $('#activityDescriptionNote').val(activity.field_1 || '');
                        $('#taskDetails').val(activity.field_1 || '');
                        $('#eventDetails').val(activity.field_1 || '');
                        $('#activityPriority').val(activity.priority || '');
                        $('#activityDueDate').val(activity.due_date || '');
                        $('#activityEndDate').val(activity.end_date || '');
                        $('#activityEmail').val(activity.email || '');
                        $('#activityCC').val(activity.cc || '');
                        $('#activityBCC').val(activity.bcc || '');
                        $('#activityPhone').val(activity.phone || '');
                        $('#activityField1').val(activity.field_1 || '');
                        $('#activityField2').val(activity.field_2 || '');

                        // Handle file attachment display for Email type
                        if (activity.type === 'Email' && activity.file) {
                            const filePreview = $('#emailAttachmentPreview');
                            const files = activity.file.split(',');
                            let fileList =
                                '<div class="alert alert-info"><i class="bi bi-paperclip me-2"></i><strong>Current Attachments:</strong><ul class="mb-0 mt-2">';
                            files.forEach(function(file) {
                                fileList += `<li>${file}</li>`;
                            });
                            fileList +=
                                '</ul><button type="button" class="btn btn-sm btn-outline-danger mt-2" onclick="removeEmailAttachment()">Remove</button></div>';
                            filePreview.html(fileList);
                        }

                        // Handle Task-specific fields
                        if (activity.type === 'Task' && activity.due_date) {
                            const dueDate = new Date(activity.due_date);
                            $('#taskDueMonth').val(String(dueDate.getMonth() + 1).padStart(2, '0'));
                            $('#taskDueDay').val(String(dueDate.getDate()).padStart(2, '0'));
                            $('#taskDueYear').val(dueDate.getFullYear());
                            $('#taskDueHour').val(String(dueDate.getHours()).padStart(2, '0'));
                            $('#taskDueMinute').val(String(dueDate.getMinutes()).padStart(2, '0'));

                            if (activity.priority === 'High') {
                                $('#taskPrioritise').prop('checked', true);
                            }
                        }

                        if (activity.type === 'Task' && activity.end_date) {
                            const endDate = new Date(activity.end_date);
                            $('#taskEndMonth').val(String(endDate.getMonth() + 1).padStart(2, '0'));
                            $('#taskEndDay').val(String(endDate.getDate()).padStart(2, '0'));
                            $('#taskEndYear').val(endDate.getFullYear());
                            $('#taskEndHour').val(String(endDate.getHours()).padStart(2, '0'));
                            $('#taskEndMinute').val(String(endDate.getMinutes()).padStart(2, '0'));
                            $('#endDateTimeSection').show();
                            $('#addEndDateTimeLink').text('- Remove End Date/Time');
                        }

                        // Handle Event-specific fields
                        if (activity.type === 'Event' && activity.due_date) {
                            const startDate = new Date(activity.due_date);
                            $('#eventStartMonth').val(String(startDate.getMonth() + 1).padStart(2, '0'));
                            $('#eventStartDay').val(String(startDate.getDate()).padStart(2, '0'));
                            $('#eventStartYear').val(startDate.getFullYear());
                            $('#eventStartHour').val(String(startDate.getHours()).padStart(2, '0'));
                            $('#eventStartMinute').val(String(startDate.getMinutes()).padStart(2, '0'));

                            if (activity.priority === 'High') {
                                $('#eventPrioritise').prop('checked', true);
                            }
                        }

                        if (activity.type === 'Event' && activity.end_date) {
                            const endDate = new Date(activity.end_date);
                            $('#eventEndMonth').val(String(endDate.getMonth() + 1).padStart(2, '0'));
                            $('#eventEndDay').val(String(endDate.getDate()).padStart(2, '0'));
                            $('#eventEndYear').val(endDate.getFullYear());
                            $('#eventEndHour').val(String(endDate.getHours()).padStart(2, '0'));
                            $('#eventEndMinute').val(String(endDate.getMinutes()).padStart(2, '0'));
                        }

                        if (activity.assigned_to) {
                            $('#taskAssignTo').val(activity.assigned_to);
                            $('#eventAssignTo').val(activity.assigned_to);
                        }

                        // Show appropriate fields based on activity type
                        showActivityFields(activity.type);

                        new bootstrap.Modal(document.getElementById('activityModal')).show();
                    }
                },
                error: function() {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Failed to load activity data.'
                    });
                }
            });
        }

        function deleteActivity(id) {
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
                        url: `/activities/${id}`,
                        method: 'DELETE',
                        success: function(response) {
                            if (response.success) {
                                Swal.fire('Deleted!', 'Activity has been deleted.', 'success');
                                loadActivities();
                            }
                        },
                        error: function() {
                            Swal.fire('Error!', 'Failed to delete activity.', 'error');
                        }
                    });
                }
            });
        }

        function loadActivities() {
            $.ajax({
                url: '/leads/{{ $lead->id }}',
                method: 'GET',
                data: {
                    ajax: true
                },
                success: function(response) {
                    if (response.success && response.activities) {
                        renderActivities(response.activities);
                    } else {
                        // Reload page if AJAX response not available
                        location.reload();
                    }
                },
                error: function() {
                    // Fallback to page reload
                    location.reload();
                }
            });
        }

        function renderActivities(activities) {
            const tbody = $('#activitiesList tbody');
            if (!tbody.length) {
                // If table doesn't exist, reload page
                location.reload();
                return;
            }

            tbody.empty();

            if (activities.length === 0) {
                $('#activitiesList').html(`
            <div class="text-center py-5">
                <i class="bi bi-inbox text-muted" style="font-size: 3rem;"></i>
                <p class="text-muted mt-2">No activities found</p>
                <p class="text-muted small">Click "New" to create an activity</p>
            </div>
        `);
                return;
            }

            // Ensure table structure exists
            if (!$('#activitiesList table').length) {
                $('#activitiesList').html(`
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Type</th>
                            <th>Description</th>
                            <th>Priority</th>
                            <th>Due Date</th>
                            <th>Created By</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        `);
            }

            activities.forEach(function(activity) {
                const priorityBadge = activity.priority ?
                    `<span class="badge bg-${activity.priority == 'High' ? 'danger' : (activity.priority == 'Medium' ? 'warning' : 'info')}">${activity.priority}</span>` :
                    '-';

                // Determine action buttons based on activity type
                let actionButtons = '';
                if (activity.type === 'Email' || activity.type === 'Dropbox Reply Email' || activity.type ===
                    'Dropbox Email') {
                    actionButtons = `
                            <a href="/activities/${activity.id}" class="btn btn-sm btn-info" title="View Email">
                                <i class="bi bi-eye"></i>
                            </a>
                        `;
                } else if (activity.type === 'Document') {
                    actionButtons = `
                            <button class="btn btn-sm document-preview-btn" onclick="previewDocumentFromServer(${activity.id})" title="Preview">
                                <i class="bi bi-eye"></i> Preview
                            </button>
                            <button class="btn btn-sm document-delete-btn" onclick="deleteActivity(${activity.id})" title="Delete">
                                <i class="bi bi-trash"></i> Delete
                            </button>
                        `;
                } else {
                    actionButtons = `
                            <button class="btn btn-sm btn-primary" onclick="editActivity(${activity.id})" title="Edit">
                                <i class="bi bi-pencil"></i>
                            </button>
                            <button class="btn btn-sm btn-danger" onclick="deleteActivity(${activity.id})" title="Delete">
                                <i class="bi bi-trash"></i>
                            </button>
                        `;
                }

                const row = `
            <tr>
                <td>${activity.date ? new Date(activity.date).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' }) : '-'}</td>
                <td><span class="badge bg-secondary">${activity.type || '-'}</span></td>
                <td>${activity.field_1 ? (activity.field_1.length > 50 ? activity.field_1.substring(0, 50) + '...' : activity.field_1) : '-'}</td>
                <td>${priorityBadge}</td>
                <td>${activity.due_date ? new Date(activity.due_date).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' }) : '-'}</td>
                <td>${activity.created_by ? (activity.created_by.name || '-') : '-'}</td>
                <td>
                    ${actionButtons}
                </td>
            </tr>
        `;
                $('#activitiesList tbody').append(row);
            });
        }

        function deleteLead(id) {
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
                        url: `/leads/${id}`,
                        method: 'DELETE',
                        success: function(response) {
                            if (response.success) {
                                Swal.fire('Deleted!', 'Lead has been deleted.', 'success').then(() => {
                                    window.location.href = '/leads';
                                });
                            }
                        },
                        error: function() {
                            Swal.fire('Error!', 'Failed to delete lead.', 'error');
                        }
                    });
                }
            });
        }

        function loadStatusesForProject() {
            const projectId = {{ $lead->project_id }};
            if (!projectId) {
                $('#statusPopupBody').html('<div class="text-center py-3 text-muted">No Project Selected</div>');
                return;
            }

            $.ajax({
                url: `/leads/project/${projectId}/statuses`,
                method: 'GET',
                success: function(response) {
                    if (response.success && response.statuses.length > 0) {
                        let menuItems = '';
                        response.statuses.forEach(function(status) {
                            const isSelected = {{ $lead->status_id ?? 'null' }} == status.id;
                            menuItems +=
                                `<a href="javascript:void(0);" class="user-popup-item ${isSelected ? 'active' : ''}" data-status-id="${status.id}" onclick="updateStatus(${status.id}); return false;">${status.name}</a>`;
                        });
                        $('#statusPopupBody').html(menuItems);
                    } else {
                        $('#statusPopupBody').html(
                            '<div class="text-center py-3 text-muted">No Statuses Available</div>');
                    }
                },
                error: function() {
                    $('#statusPopupBody').html(
                        '<div class="text-center py-3 text-muted">Error Loading Statuses</div>');
                }
            });
        }

        function openStatusPopup() {
            const $popup = $('#statusPopup');

            // Load statuses if not loaded
            if ($('#statusPopupBody').html().trim() === '' || $('#statusPopupBody').find('.spinner-border').length > 0) {
                loadStatusesForProject();
            }

            // Show popup (it will stick to button since it's absolutely positioned relative to btn-group)
            $popup.css('display', 'block');

            // Focus on search input
            setTimeout(function() {
                $('#statusSearchInput').focus();
            }, 100);
        }

        function closeStatusPopup() {
            $('#statusPopup').css('display', 'none');
            $('#statusSearchInput').val('');
            filterStatuses('');
        }

        function filterStatuses(searchTerm) {
            const searchLower = searchTerm.toLowerCase().trim();
            $('#statusPopupBody .user-popup-item').each(function() {
                const $item = $(this);
                const text = $item.text().toLowerCase();
                if (text.includes(searchLower)) {
                    $item.removeClass('hidden');
                } else {
                    $item.addClass('hidden');
                }
            });
        }

        function updateStatus(statusId) {
            // Close the popup first
            closeStatusPopup();

            $.ajax({
                url: `/leads/{{ $lead->id }}`,
                method: 'PUT',
                data: {
                    _method: 'PUT',
                    status_id: statusId,
                    project_id: {{ $lead->project_id }},
                    first_name: '{{ $lead->first_name }}'
                },
                success: function(response) {
                    if (response.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Success',
                            text: 'Status updated successfully.',
                            timer: 2000,
                            showConfirmButton: false
                        });
                        location.reload();
                    }
                },
                error: function() {
                    Swal.fire('Error!', 'Failed to update status.', 'error');
                }
            });
        }

        function loadLeadTypes() {
            $.ajax({
                url: `/leads/lead-types`,
                method: 'GET',
                success: function(response) {
                    if (response.success && response.lead_types.length > 0) {
                        let menuItems = '';
                        // Add option to remove lead type
                        menuItems +=
                            `<a href="javascript:void(0);" class="user-popup-item none" onclick="updateLeadType(null); return false;"><em>None</em></a>`;

                        // Add all lead types
                        response.lead_types.forEach(function(leadType) {
                            const isSelected = {{ $lead->lead_type_id ?? 'null' }} == leadType.id;
                            menuItems +=
                                `<a href="javascript:void(0);" class="user-popup-item ${isSelected ? 'active' : ''}" data-lead-type-id="${leadType.id}" onclick="updateLeadType(${leadType.id}); return false;">${leadType.name}</a>`;
                        });

                        $('#leadTypePopupBody').html(menuItems);
                    } else {
                        $('#leadTypePopupBody').html(
                            '<div class="text-center py-3 text-muted">No Lead Types Available</div>');
                    }
                },
                error: function() {
                    $('#leadTypePopupBody').html(
                        '<div class="text-center py-3 text-muted">Error Loading Lead Types</div>');
                }
            });
        }

        function openLeadTypePopup() {
            const $popup = $('#leadTypePopup');

            // Load lead types if not loaded
            if ($('#leadTypePopupBody').html().trim() === '' || $('#leadTypePopupBody').find('.spinner-border').length >
                0) {
                loadLeadTypes();
            }

            // Show popup (it will stick to button since it's absolutely positioned relative to btn-group)
            $popup.css('display', 'block');

            // Focus on search input
            setTimeout(function() {
                $('#leadTypeSearchInput').focus();
            }, 100);
        }

        function closeLeadTypePopup() {
            $('#leadTypePopup').css('display', 'none');
            $('#leadTypeSearchInput').val('');
            filterLeadTypes('');
        }

        function filterLeadTypes(searchTerm) {
            const searchLower = searchTerm.toLowerCase().trim();
            $('#leadTypePopupBody .user-popup-item').each(function() {
                const $item = $(this);
                const text = $item.text().toLowerCase();
                if (text.includes(searchLower)) {
                    $item.removeClass('hidden');
                } else {
                    $item.addClass('hidden');
                }
            });
        }

        function updateLeadType(leadTypeId) {
            // Close the popup first
            closeLeadTypePopup();

            $.ajax({
                url: `/leads/{{ $lead->id }}`,
                method: 'PUT',
                data: {
                    _method: 'PUT',
                    lead_type_id: leadTypeId || null,
                    project_id: {{ $lead->project_id }},
                    first_name: '{{ $lead->first_name }}'
                },
                success: function(response) {
                    if (response.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Success',
                            text: 'Lead type updated successfully.',
                            timer: 2000,
                            showConfirmButton: false
                        });
                        location.reload();
                    }
                },
                error: function() {
                    Swal.fire('Error!', 'Failed to update lead type.', 'error');
                }
            });
        }

        function loadUsers() {
            // Use users from page (already loaded)
            @if (isset($users) && $users->count() > 0)
                let menuItems = '';
                // Add option to remove user
                menuItems +=
                    `<a href="javascript:void(0);" class="user-popup-item none" onclick="updateUser(null); return false;"><em>None</em></a>`;
                @foreach ($users as $user)
                    const isSelected{{ $user->id }} = {{ $lead->added_by ?? 'null' }} == {{ $user->id }};
                    menuItems +=
                        `<a href="javascript:void(0);" class="user-popup-item ${isSelected{{ $user->id }} ? 'active' : ''}" data-user-id="{{ $user->id }}" onclick="updateUser({{ $user->id }}); return false;">{{ $user->name }}</a>`;
                @endforeach

                $('#userPopupBody').html(menuItems);
            @else
                $('#userPopupBody').html('<div class="text-center py-3 text-muted">No Users Available</div>');
            @endif
        }

        function openUserPopup() {
            const $popup = $('#userPopup');

            // Load users if not loaded
            if ($('#userPopupBody').html().trim() === '' || $('#userPopupBody').find('.spinner-border').length > 0) {
                loadUsers();
            }

            // Show popup (it will stick to button since it's absolutely positioned relative to btn-group)
            $popup.css('display', 'block');

            // Focus on search input
            setTimeout(function() {
                $('#userSearchInput').focus();
            }, 100);
        }

        function closeUserPopup() {
            $('#userPopup').css('display', 'none');
            $('#userSearchInput').val('');
            filterUsers('');
        }

        function positionUserPopup() {
            // Popup is absolutely positioned relative to .btn-group
            // No need for complex positioning - it will stick with the button automatically
            // Just ensure it's visible
        }

        function filterUsers(searchTerm) {
            const searchLower = searchTerm.toLowerCase().trim();
            $('.user-popup-item').each(function() {
                const $item = $(this);
                const text = $item.text().toLowerCase();
                if (text.includes(searchLower)) {
                    $item.removeClass('hidden');
                } else {
                    $item.addClass('hidden');
                }
            });
        }

        function updateUser(userId) {
            // Close the popup first
            closeUserPopup();

            // Get user name if userId is provided
            let userName = null;
            if (userId) {
                const userItem = $(`#userPopupBody .user-popup-item[data-user-id="${userId}"]`);
                if (userItem.length) {
                    userName = userItem.text().trim();
                }
            }

            $.ajax({
                url: `/leads/{{ $lead->id }}`,
                method: 'PUT',
                data: {
                    _method: 'PUT',
                    added_by: userId || null,
                    user_name: userName || null,
                    project_id: {{ $lead->project_id }},
                    first_name: '{{ $lead->first_name }}'
                },
                success: function(response) {
                    if (response.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Success',
                            text: 'User assigned successfully.',
                            timer: 2000,
                            showConfirmButton: false
                        });
                        location.reload();
                    }
                },
                error: function() {
                    Swal.fire('Error!', 'Failed to assign user.', 'error');
                }
            });
        }

        // Load statuses and lead types when page loads
        $(document).ready(function() {
            // Open status popup on button click
            $('#statusDropdownBtn').on('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                if ($('#statusPopup').css('display') === 'none') {
                    openStatusPopup();
                } else {
                    closeStatusPopup();
                }
            });

            // Open lead type popup on button click
            $('#leadTypeDropdownBtn').on('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                if ($('#leadTypePopup').css('display') === 'none') {
                    openLeadTypePopup();
                } else {
                    closeLeadTypePopup();
                }
            });

            // Open user popup on button click
            $('#userDropdownBtn').on('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                if ($('#userPopup').css('display') === 'none') {
                    openUserPopup();
                } else {
                    closeUserPopup();
                }
            });

            // Handle search input for statuses (use event delegation since input is inside popup)
            $(document).on('input', '#statusSearchInput', function() {
                const searchTerm = $(this).val();
                filterStatuses(searchTerm);
            });

            // Handle search input for lead types (use event delegation since input is inside popup)
            $(document).on('input', '#leadTypeSearchInput', function() {
                const searchTerm = $(this).val();
                filterLeadTypes(searchTerm);
            });

            // Handle search input for users (use event delegation since input is inside popup)
            $(document).on('input', '#userSearchInput', function() {
                const searchTerm = $(this).val();
                filterUsers(searchTerm);
            });

            // Close popups when clicking outside
            $(document).on('click', function(e) {
                if (!$(e.target).closest('#statusDropdownBtn').length &&
                    !$(e.target).closest('#statusPopup').length) {
                    closeStatusPopup();
                }
                if (!$(e.target).closest('#leadTypeDropdownBtn').length &&
                    !$(e.target).closest('#leadTypePopup').length) {
                    closeLeadTypePopup();
                }
                if (!$(e.target).closest('#userDropdownBtn').length &&
                    !$(e.target).closest('#userPopup').length) {
                    closeUserPopup();
                }
            });
        });

        function copyLead(id) {
            // Implementation for copying lead
            Swal.fire('Info', 'Copy functionality will be implemented.', 'info');
        }

        function toggleFilters() {
            // Implementation for filters
            Swal.fire('Info', 'Filters functionality will be implemented.', 'info');
        }

        // Handle form submission
        function submitActivityForm() {
            // Collect all form data
            // For Email type: field_1 = Subject, field_2 = Email Content
            // For Note type: field_1 = Note Details
            // For Task type: field_1 = Task Details
            // For Event type: field_1 = Event Details
            // For other types: field_1 = Description
            let field1Value = '';
            let field2Value = '';

            if ($('#emailFields').is(':visible')) {
                // Email type: field_1 = Subject, field_2 = Email Content
                field1Value = $('#activityEmailSubject').val() || '';
                // Convert plain text to HTML in background
                const plainText = $('#activityDescription').val() || '';
                field2Value = convertTextToHtml(plainText);
            } else if ($('#noteDetailsSection').is(':visible') && $('#activityDescriptionNote').length) {
                // Note type: use the note textarea
                field1Value = $('#activityDescriptionNote').val() || '';
                field2Value = $('#activityField2').val() || '';
            } else if ($('#taskSpecificSection').is(':visible') && $('#taskDetails').length) {
                // Task type: use the task details textarea
                field1Value = $('#taskDetails').val() || '';
                field2Value = $('#activityField2').val() || '';
            } else if ($('#eventSpecificSection').is(':visible') && $('#eventDetails').length) {
                // Event type: use the event details textarea
                field1Value = $('#eventDetails').val() || '';
                field2Value = $('#activityField2').val() || '';
            } else if ($('#activityDescription').length && $('#activityDescription').val()) {
                // Other types: use description field if it has a value
                field1Value = $('#activityDescription').val() || '';
                field2Value = $('#activityField2').val() || '';
            } else {
                // Fallback: use field_1 and field_2 inputs if available
                field1Value = $('#activityField1').val() || '';
                field2Value = $('#activityField2').val() || '';
            }

            // Get all form values
            const leadId = $('#activityLeadId').val();
            const type = $('#activityType').val();
            let date = $('#activityDate').val();
            let dueDate = null;
            let endDate = null;

            // Handle Task-specific date/time fields
            if ($('#taskSpecificSection').is(':visible')) {
                // Build date from Task Due dropdowns
                const taskDueMonth = $('#taskDueMonth').val();
                const taskDueDay = $('#taskDueDay').val();
                const taskDueYear = $('#taskDueYear').val();
                const taskDueHour = $('#taskDueHour').val();
                const taskDueMinute = $('#taskDueMinute').val();

                if (taskDueYear && taskDueMonth && taskDueDay) {
                    // Build date string: YYYY-MM-DD HH:MM:SS
                    date = `${taskDueYear}-${taskDueMonth}-${taskDueDay}`;
                    dueDate = `${taskDueYear}-${taskDueMonth}-${taskDueDay} ${taskDueHour}:${taskDueMinute}:00`;
                }

                // Build end date if visible
                if ($('#endDateTimeSection').is(':visible')) {
                    const taskEndMonth = $('#taskEndMonth').val();
                    const taskEndDay = $('#taskEndDay').val();
                    const taskEndYear = $('#taskEndYear').val();
                    const taskEndHour = $('#taskEndHour').val();
                    const taskEndMinute = $('#taskEndMinute').val();

                    if (taskEndYear && taskEndMonth && taskEndDay) {
                        endDate = `${taskEndYear}-${taskEndMonth}-${taskEndDay} ${taskEndHour}:${taskEndMinute}:00`;
                    }
                }
            }

            // Handle Event-specific date/time fields
            if ($('#eventSpecificSection').is(':visible')) {
                // Build date from Event Start dropdowns
                const eventStartMonth = $('#eventStartMonth').val();
                const eventStartDay = $('#eventStartDay').val();
                const eventStartYear = $('#eventStartYear').val();
                const eventStartHour = $('#eventStartHour').val();
                const eventStartMinute = $('#eventStartMinute').val();

                if (eventStartYear && eventStartMonth && eventStartDay) {
                    // Build date string: YYYY-MM-DD HH:MM:SS
                    date = `${eventStartYear}-${eventStartMonth}-${eventStartDay}`;
                    dueDate =
                        `${eventStartYear}-${eventStartMonth}-${eventStartDay} ${eventStartHour}:${eventStartMinute}:00`;
                }

                // Build end date from Event End dropdowns
                const eventEndMonth = $('#eventEndMonth').val();
                const eventEndDay = $('#eventEndDay').val();
                const eventEndYear = $('#eventEndYear').val();
                const eventEndHour = $('#eventEndHour').val();
                const eventEndMinute = $('#eventEndMinute').val();

                if (eventEndYear && eventEndMonth && eventEndDay) {
                    endDate = `${eventEndYear}-${eventEndMonth}-${eventEndDay} ${eventEndHour}:${eventEndMinute}:00`;
                }
            }

            console.log('Form Data:', {
                lead_id: leadId,
                type: type,
                date: date,
                due_date: dueDate,
                end_date: endDate,
                field_1: field1Value,
                field1Length: field1Value.length
            });

            if (!leadId || !type || !date) {
                Swal.fire('Error!', 'Please fill in all required fields (Type and Date).', 'error');
                return;
            }

            // Determine priority - for Task or Event, check if Prioritise checkbox is checked
            let priority = null;
            if ($('#taskSpecificSection').is(':visible')) {
                priority = $('#taskPrioritise').is(':checked') ? 'High' : null;
            } else if ($('#eventSpecificSection').is(':visible')) {
                priority = $('#eventPrioritise').is(':checked') ? 'High' : null;
            } else {
                priority = $('#activityPriority').val() || null;
            }

            // Create FormData for file uploads
            const formData = new FormData();
            formData.append('lead_id', leadId);
            formData.append('type', type);
            formData.append('date', date);
            formData.append('field_1', field1Value || '');
            formData.append('field_2', field2Value || '');
            formData.append('priority', priority || '');
            formData.append('due_date', dueDate || $('#activityDueDate').val() || '');
            formData.append('end_date', endDate || $('#activityEndDate').val() || '');
            formData.append('email', $('#activityEmail').val() || '');
            formData.append('cc', $('#activityCC').val() || '');
            formData.append('bcc', $('#activityBCC').val() || '');
            formData.append('phone', $('#activityPhone').val() || '');
            formData.append('assigned_to', ($('#taskAssignTo').val() || $('#eventAssignTo').val()) || '');
            formData.append('created_by', {{ auth()->id() }});

            // Handle file upload for Email type
            if ($('#emailFields').is(':visible') && $('#activityEmailAttachment').length && $('#activityEmailAttachment')[0]
                .files.length > 0) {
                const files = $('#activityEmailAttachment')[0].files;
                for (let i = 0; i < files.length; i++) {
                    formData.append('file[]', files[i]);
                }
            }

            // Handle file upload for Document type
            if ($('#documentUploadSection').is(':visible') && $('#documentFileInput').length && $('#documentFileInput')[0]
                .files.length > 0) {
                const files = $('#documentFileInput')[0].files;
                for (let i = 0; i < files.length; i++) {
                    formData.append('document_files[]', files[i]);
                }
            }

            let url = '/activities';
            let method = 'POST';

            if (editingActivityId) {
                url = `/activities/${editingActivityId}`;
                formData.append('_method', 'PUT');
            }

            console.log('Sending AJAX request to:', url);
            console.log('Method:', method);

            $.ajax({
                url: url,
                method: method,
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    console.log('Success response:', response);
                    if (response.success) {
                        // Close modal first and ensure backdrop is removed
                        const modalElement = document.getElementById('activityModal');
                        const modalInstance = bootstrap.Modal.getInstance(modalElement);

                        if (modalInstance) {
                            modalInstance.hide();
                        } else {
                            // Fallback: hide using jQuery if Bootstrap instance doesn't exist
                            $('#activityModal').modal('hide');
                        }

                        // Force remove backdrop and modal-open class if they persist
                        setTimeout(function() {
                            $('.modal-backdrop').remove();
                            $('body').removeClass('modal-open');
                            $('body').css('overflow', '');
                            $('body').css('padding-right', '');
                        }, 100);

                        // Reset form and clear editing state
                        $('#activityForm')[0].reset();
                        editingActivityId = null;
                        $('#activityModalLabel').text('Add Activity');
                        $('#activitySubmitBtn').text('Save');

                        // Hide all conditional fields
                        $('#standardFields, #noteInfoSections, #noteDetailsSection, #emailFields, #phoneFields, #priorityField, #dueDateField, #endDateField, #field1Container, #field2Container, #descriptionField, #openLeadLink, #taskSpecificSection, #viewCalendarLink, #taskNextBtn, #endDateTimeSection, #emailBodyActions')
                            .hide();
                        $('#standardFields').show(); // Show standard fields by default
                        $('#addEndDateTimeLink').text('- Add an End Date/Time');

                        // Reset modal header styling
                        $('#activityModalHeader').addClass('border-0 pb-0').removeClass('pb-2');
                        $('#activityModalBody').addClass('pt-3');

                        // Show success message
                        Swal.fire({
                            icon: 'success',
                            title: 'Success',
                            text: response.message || 'Activity saved successfully.',
                            timer: 2000,
                            showConfirmButton: false
                        }).then(function() {
                            // Reload activities list via AJAX after modal is closed
                            loadActivities();
                        });

                        // Also reload activities immediately (in case user doesn't wait for timer)
                        loadActivities();
                    } else {
                        Swal.fire('Error!', response.message || 'Failed to save activity.', 'error');
                    }
                },
                error: function(xhr) {
                    console.error('Error response:', xhr);
                    console.error('Status:', xhr.status);
                    console.error('Response:', xhr.responseJSON);

                    if (xhr.status === 422) {
                        const errors = xhr.responseJSON.errors;
                        let errorMsg = 'Validation errors:\n';
                        $.each(errors, function(key, value) {
                            errorMsg += key + ': ' + value[0] + '\n';
                        });
                        Swal.fire('Error!', errorMsg, 'error');
                    } else if (xhr.responseJSON && xhr.responseJSON.message) {
                        Swal.fire('Error!', xhr.responseJSON.message, 'error');
                    } else {
                        Swal.fire('Error!', 'Failed to save activity. Please check console for details.',
                            'error');
                    }
                }
            });
        }

        // Form submit handler - use event delegation
        $(document).off('submit', '#activityForm').on('submit', '#activityForm', function(e) {
            e.preventDefault();
            e.stopPropagation();
            console.log('Form submit event triggered');
            submitActivityForm();
            return false;
        });

        // Direct button click handler as fallback - use event delegation
        $(document).off('click', '#activitySubmitBtn').on('click', '#activitySubmitBtn', function(e) {
            e.preventDefault();
            e.stopPropagation();
            console.log('Save button clicked directly');
            submitActivityForm();
            return false;
        });

        // Clean up modal when it's hidden
        $('#activityModal').on('hidden.bs.modal', function() {
            console.log('Activity modal hidden - cleaning up');

            // Force remove backdrop and modal-open class if they persist
            $('.modal-backdrop').remove();
            $('body').removeClass('modal-open');
            $('body').css('overflow', '');
            $('body').css('padding-right', '');

            // Reset form
            $('#activityForm')[0].reset();
            editingActivityId = null;
            $('#activityModalLabel').text('Add Activity');
            $('#activitySubmitBtn').text('Save');

            // Hide all conditional fields
            $('#standardFields, #noteInfoSections, #noteDetailsSection, #emailFields, #phoneFields, #priorityField, #dueDateField, #endDateField, #field1Container, #field2Container, #descriptionField, #openLeadLink, #taskSpecificSection, #eventSpecificSection, #viewCalendarLink, #taskNextBtn, #endDateTimeSection, #emailBodyActions')
                .hide();
            $('#standardFields').show(); // Show standard fields by default
            $('#addEndDateTimeLink').text('- Add an End Date/Time');

            // Reset modal header styling
            $('#activityModalHeader').addClass('border-0 pb-0').removeClass('pb-2');
            $('#activityModalBody').addClass('pt-3');
        });

        // Also ensure button is clickable when modal is shown
        $('#activityModal').on('shown.bs.modal', function() {
            console.log('Modal shown, ensuring button is clickable');
            $('#activitySubmitBtn').css({
                'pointer-events': 'auto',
                'cursor': 'pointer',
                'z-index': 'auto'
            });
        });
    </script>

    <!-- Import Activities Modal -->
    <div class="modal fade" id="importActivitiesModal" tabindex="-1" aria-labelledby="importActivitiesModalLabel"
        aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="importActivitiesModalLabel">Import Activities from CSV</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="importActivitiesForm">
                    <div class="modal-body">
                        <div class="alert alert-info">
                            <i class="bi bi-info-circle me-2"></i>
                            <strong>Instructions:</strong>
                            <ul class="mb-0 mt-2">
                                <li>CSV file must have a <strong>Reference</strong> column matching the lead's Reference
                                    (flg_reference)</li>
                                <li>Required fields: <strong>Reference</strong>, <strong>ActivityType</strong>,
                                    <strong>ActivityDateTime</strong></li>
                                <li>Activities will be linked to leads based on the Reference column</li>
                                <li>Date format: <strong>ActivityDateTime</strong> should be in d/m/Y H:i format (e.g.,
                                    07/11/2025 07:55)</li>
                                <li>If <strong>ActivityID</strong> is provided and already exists, that row will be skipped
                                </li>
                            </ul>
                        </div>
                        <div class="mb-3">
                            <label for="importActivitiesFile" class="form-label">Select CSV File <span
                                    class="text-danger">*</span></label>
                            <input type="file" class="form-control" id="importActivitiesFile" name="file"
                                accept=".csv,.txt" required>
                            <small class="text-muted">Maximum file size: 10MB</small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-upload me-2"></i>Import Activities
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        // Handle import activities form submission
        $('#importActivitiesForm').on('submit', function(e) {
            e.preventDefault();

            const formData = new FormData(this);
            const fileInput = $('#importActivitiesFile')[0];

            if (!fileInput.files.length) {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Please select a CSV file to import.'
                });
                return;
            }

            formData.append('file', fileInput.files[0]);

            $.ajax({
                url: '/activities/import',
                method: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                success: function(response) {
                    if (response.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Import Successful',
                            html: response.message.replace(/\n/g, '<br>'),
                            confirmButtonText: 'OK'
                        });

                        bootstrap.Modal.getInstance(document.getElementById('importActivitiesModal'))
                            .hide();
                        $('#importActivitiesForm')[0].reset();
                        loadActivities(); // Reload activities list
                    } else {
                        // Handle error response (success: false)
                        let message = response.message || 'Import failed.';
                        if (response.errors && response.errors.length > 0) {
                            message += '<br><br><strong>Errors:</strong><br>' + response.errors.join(
                                '<br>');
                        }

                        Swal.fire({
                            icon: 'error',
                            title: 'Import Failed',
                            html: message,
                            confirmButtonText: 'OK'
                        });
                    }
                },
                error: function(xhr) {
                    let errorMessage = 'Failed to import activities. Please try again.';
                    if (xhr.responseJSON) {
                        if (xhr.responseJSON.message) {
                            errorMessage = xhr.responseJSON.message;
                            if (xhr.responseJSON.errors && xhr.responseJSON.errors.length > 0) {
                                errorMessage += '\n\nErrors:\n' + xhr.responseJSON.errors.join('\n');
                            }
                        } else if (xhr.responseJSON.errors) {
                            errorMessage = Object.values(xhr.responseJSON.errors).flat().join('\n');
                        }
                    }

                    Swal.fire({
                        icon: 'error',
                        title: 'Import Failed',
                        html: errorMessage.replace(/\n/g, '<br>'),
                        confirmButtonText: 'OK'
                    });
                }
            });
        });
    </script>
@endpush
@endsection
