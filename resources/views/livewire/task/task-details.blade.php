<div class="task-details-container">
    <!-- Compact Header -->
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div class="d-flex align-items-center">
            <i class="bi bi-clipboard-check text-primary me-2"></i>
            <h4 class="mb-0 text-primary fw-bold">Task Details</h4>
        </div>
        <a href="{{ route('tasks.index') }}" class="btn btn-outline-primary btn-sm">
            <i class="bi bi-arrow-left me-1"></i>Back
        </a>
    </div>

    <div class="row g-3">
        <!-- Task Information -->
        <div class="col-lg-8">
            <div class="card shadow-sm border-0 mb-3">
                <div class="card-header text-white border-0 py-2">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="d-flex align-items-center">
                            <i class="bi bi-clipboard-data me-2"></i>
                            <h6 class="mb-0 fw-bold">{{ $task->title }}</h6>
                            @if ($task->nature_of_task === 'recurring')
                                <span class="badge bg-light text-primary ms-2 small">Recurring</span>
                            @endif
                            @if ($task->is_approved)
                                <span class="badge bg-success ms-2 small">Approved</span>
                            @endif
                        </div>
                        @if (auth()->user()->isSuperAdmin() ||
                                auth()->user()->isAdmin() ||
                                auth()->user()->isManager() ||
                                $task->is_approved === true)
                            <span class="badge {{ $task->status_badge_class }} small">
                                {{ $task->status->name }}
                            </span>
                        @else
                            <div class="dropdown">
                                <button class="btn btn-light btn-sm dropdown-toggle" type="button"
                                    data-bs-toggle="dropdown">
                                    <span class="badge {{ $task->status_badge_class }} small">
                                        {{ $task->status->name }}
                                    </span>
                                </button>
                                <ul class="dropdown-menu">
                                    @foreach ($this->statuses as $status)
                                        @if (in_array($status->name, ['Complete', 'In Progress']))
                                            <li>
                                                <button class="dropdown-item"
                                                    wire:click="updateTaskStatus({{ $task->id }}, {{ $status->id }})">
                                                    <span
                                                        class="badge bg-{{ $status->color }} me-2">{{ $status->name }}</span>
                                                </button>
                                            </li>
                                        @endif
                                    @endforeach
                                </ul>
                            </div>
                        @endif
                    </div>
                </div>
                <div class="card-body p-3">
                    <!-- Compact Description -->
                    @if ($task->description)
                        <div class="mb-3">
                            <h6 class="fw-bold text-dark mb-2 small">
                                <i class="bi bi-file-text me-1"></i>Description
                            </h6>
                            <div class="description-box-compact">
                                {{ $task->description }}
                            </div>
                        </div>
                    @endif

                    <!-- Compact Notes -->
                    {{-- @if ($task->notes)
                        <div class="mb-3">
                            <h6 class="fw-bold text-dark mb-2 small">
                                <i class="bi bi-sticky me-1"></i>Notes
                            </h6>
                            <div class="notes-box-compact">
                                {!! nl2br(e($task->notes)) !!}
                            </div>
                        </div>
                    @endif --}}
                </div>
                @if ($task->attachments && $task->attachments->count() > 0)
                <!-- Attachments -->
                <div class="card mb-3">
                    <div class="card-header py-2">
                      
                        <h6 class="mb-0"> <i class="bi bi-paperclip me-2"></i>Attachments ({{ $task->attachments->count() }})</h6>
                    </div>
                    <div class="card-body p-3">
                        <!-- Upload New Attachments -->
                        <form wire:submit="addAttachments" class="mb-3">
                            <div class="input-group input-group-sm">
                                <input type="file" class="form-control" wire:model="newAttachments" multiple>
                                <button type="submit" class="btn btn-outline-primary">
                                    <i class="bi bi-upload me-1"></i>Upload
                                </button>
                            </div>
                            <div class="form-text small">You can upload multiple files (max 10MB each)</div>
                        </form>

                        <!-- Existing Attachments -->
                        @if ($task->attachments && $task->attachments->count() > 0)
                            <div class="attachments-list">
                                @foreach ($task->attachments as $index => $attachment)
                                    <div
                                        class="attachment-item d-flex justify-content-between align-items-center mb-2 p-2 border rounded {{ $index >= 3 ? 'attachment-hidden' : '' }}"
                                        data-attachment-item="{{ $index }}">
                                        <div class="d-flex align-items-center">
                                            @php
                                            $extension = strtolower(
                                                pathinfo(
                                                    $attachment->file_name,
                                                    PATHINFO_EXTENSION,
                                                ),
                                            );
                                            $iconClass = match ($extension) {
                                                'pdf' => 'bi-file-earmark-pdf',
                                                'doc', 'docx' => 'bi-file-earmark-word',
                                                'xls', 'xlsx' => 'bi-file-earmark-excel',
                                                'ppt', 'pptx' => 'bi-file-earmark-ppt',
                                                'jpg',
                                                'jpeg',
                                                'png',
                                                'gif'
                                                    => 'bi-file-earmark-image',
                                                'mp4',
                                                'webm',
                                                'ogg',
                                                'avi',
                                                'mov',
                                                'wmv',
                                                'flv',
                                                'mkv'
                                                    => 'bi-file-earmark-play',
                                                'zip', 'rar' => 'bi-file-earmark-zip',
                                                'txt' => 'bi-file-earmark-text',
                                                default => 'bi-file-earmark',
                                            };
                                          
                                        @endphp
                                            @if ($attachment->file_name)
                                                <i class="bi {{ $iconClass }} text-muted me-2"></i>
                                            @else
                                                <i class="bi bi-file-earmark text-muted me-2"></i>
                                            @endif
                                           
                                            <div>
                                                <div class="small fw-semibold">{{ $attachment->file_name }}</div>
                                                <div class="text-muted small">{{ $attachment->file_size ? number_format($attachment->file_size / 1024, 1) . ' KB' : 'Unknown size' }} •
                                                    {{ $attachment->created_at->diffForHumans() }}</div>
                                            </div>
                                        </div>
                                        <div class="btn-group btn-group-sm">
                                            {{-- preview the file --}}
                                            <button type="button" class="btn btn-outline-primary" 
                                                onclick="previewAttachment({{ $attachment->id }}, '{{ $attachment->file_name }}', '{{ $extension }}')">
                                                <i class="bi bi-eye"></i>
                                            </button>
                                            
                                            <a href="{{ route('attachments.download', $attachment->id) }}"
                                                class="btn btn-outline-primary">
                                                <i class="bi bi-download"></i>
                                            </a>
                                            @if (auth()->user()->isSuperAdmin() || $attachment->uploaded_by_user_id === auth()->id())
                                                <button type="button" class="btn btn-outline-danger"
                                                    onclick="confirmDeleteAttachment({{ $attachment->id }})">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                                
                                @if ($task->attachments->count() > 3)
                                    <div class="text-center mt-2">
                                        <button type="button" class="btn btn-sm btn-outline-primary" id="toggleAttachmentsBtn" 
                                            onclick="toggleAttachments()">
                                            <i class="bi bi-chevron-down me-1"></i>View All ({{ $task->attachments->count() }})
                                        </button>
                                    </div>
                                @endif
                            </div>
                        @else
                            <p class="text-muted small mb-0">No attachments yet.</p>
                        @endif
                    </div>
                </div>
                @endif
            </div>

            <!-- Comments Section -->
            <div class="card mb-3">
                <div class="card-header py-2">
                    <h6 class="mb-0">
                        <i class="bi bi-chat-dots me-2"></i>Comments
                    </h6>
                </div>
                <div class="card-body p-3">
                    <!-- Add Comment Form -->
                    <form wire:submit="addComment" class="mb-3">
                        <div class="mb-2">
                            <textarea wire:model="newComment" rows="2" class="form-control form-control-sm" placeholder="Add a comment..."></textarea>
                            @error('newComment')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <input type="file" class="form-control form-control-sm mt-2"
                                wire:model="commentAttachments" multiple>
                            @error('commentAttachments.*')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <button type="submit" class="btn btn-primary btn-sm">
                            <i class="bi bi-chat-dots me-1"></i>Add Comment
                        </button>
                    </form>

                    <!-- Comments List -->
                    @if ($task->noteComments && $task->noteComments->count() > 0)
                        <div class="comments-list">
                            @foreach ($task->noteComments as $commentIndex => $comment)
                                <div class="comment-item mb-2 p-2 border rounded {{ $commentIndex >= 3 ? 'attachment-hidden' : '' }}"
                                    data-comment-item="{{ $commentIndex }}">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div class="flex-grow-1">
                                            <div class="d-flex align-items-center mb-1">
                                                <i class="bi bi-person text-primary me-2"></i>
                                                <strong class="small">{{ $comment->user->name }}</strong>
                                                <span class="text-muted small ms-2">{{ $comment->created_at->diffForHumans() }}</span>
                                            </div>
                                            <div class="comment-text small">
                                                {{ $comment->comment }}
                                            </div>
                                            @if ($comment->attachments && $comment->attachments->count() > 0)
                                                <div class="mt-2">
                                                    @foreach ($comment->attachments as $attIndex => $attachment)
                                                        @php
                                                        $extension = strtolower(pathinfo($attachment->file_name, PATHINFO_EXTENSION));
                                                        $iconClass = match ($extension) {
                                                            'pdf' => 'bi-file-earmark-pdf',
                                                            'doc', 'docx' => 'bi-file-earmark-word',
                                                            'xls', 'xlsx' => 'bi-file-earmark-excel',
                                                            'ppt', 'pptx' => 'bi-file-earmark-ppt',
                                                            'jpg', 'jpeg', 'png', 'gif' => 'bi-file-earmark-image',
                                                            'mp4', 'webm', 'ogg', 'avi', 'mov', 'wmv', 'flv', 'mkv' => 'bi-file-earmark-play',
                                                            'zip', 'rar' => 'bi-file-earmark-zip',
                                                            'txt' => 'bi-file-earmark-text',
                                                            default => 'bi-file-earmark',
                                                        };
                                                        @endphp
                                                        <div class="attachment-item d-flex justify-content-between align-items-center mb-1 p-2 border rounded {{ $attIndex >= 3 ? 'attachment-hidden' : '' }}"
                                                            data-comment-attachment-item="{{ $comment->id }}-{{ $attIndex }}">
                                                            <div class="d-flex align-items-center">
                                                                <i class="bi {{ $iconClass }} text-info me-2"></i>
                                                                <div>
                                                                    <div class="small fw-semibold">{{ $attachment->file_name }}</div>
                                                                    <div class="text-muted small">{{ $attachment->file_size ? number_format($attachment->file_size / 1024, 1) . ' KB' : 'Unknown size' }} • {{ $attachment->created_at->diffForHumans() }}</div>
                                                                </div>
                                                            </div>
                                                            <div class="btn-group btn-group-sm">
                                                                <button type="button" class="btn btn-outline-primary" 
                                                                    onclick="previewAttachment({{ $attachment->id }}, '{{ $attachment->file_name }}', '{{ $extension }}')">
                                                                    <i class="bi bi-eye"></i>
                                                                </button>
                                                                <a href="{{ route('attachments.download', $attachment->id) }}" class="btn btn-outline-primary">
                                                                    <i class="bi bi-download"></i>
                                                                </a>
                                                                @if (auth()->user()->isSuperAdmin() || $attachment->uploaded_by_user_id === auth()->id())
                                                                    <button type="button" class="btn btn-outline-danger" onclick="confirmDeleteAttachment({{ $attachment->id }})">
                                                                        <i class="bi bi-trash"></i>
                                                                    </button>
                                                                @endif
                                                            </div>
                                                        </div>
                                                    @endforeach
                                                    
                                                    @if ($comment->attachments->count() > 3)
                                                        <div class="text-center mt-2">
                                                            <button type="button" class="btn btn-sm btn-outline-primary" 
                                                                onclick="toggleCommentAttachments({{ $comment->id }})">
                                                                <i class="bi bi-chevron-down me-1"></i>View All ({{ $comment->attachments->count() }})
                                                            </button>
                                                        </div>
                                                    @endif
                                                </div>
                                            @endif
                                        </div>
                                        @if (auth()->user()->isSuperAdmin() || auth()->user()->isAdmin() || $comment->user_id === auth()->id())
                                            <button class="btn btn-sm btn-outline-danger" onclick="confirmDeleteComment({{ $comment->id }})">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                            
                            @if ($task->noteComments->count() > 3)
                                <div class="text-center mt-3">
                                    <button type="button" class="btn btn-sm btn-outline-primary" id="toggleCommentsBtn"
                                        onclick="toggleComments()">
                                        <i class="bi bi-chevron-down me-1"></i>View All ({{ $task->noteComments->count() }})
                                    </button>
                                </div>
                            @endif
                        </div>
                    @else
                        <div class="text-center py-3">
                            <i class="bi bi-chat-dots text-muted" style="font-size: 2rem;"></i>
                            <p class="text-muted small mb-0 mt-2">No comments yet</p>
                        </div>
                    @endif
                </div>

                <!-- Notes Section -->
                {{-- <div class="card mb-3">
                    <div class="card-header py-2">
                        <h6 class="mb-0">
                            <i class="bi bi-sticky me-2"></i>Notes
                        </h6>
                    </div>
                    <div class="card-body p-3">
                        @if ($task->notes)
                            <div class="notes-box-compact">
                                {!! nl2br(e($task->notes)) !!}
                            </div>
                        @else
                            <p class="text-muted small mb-0">No notes added yet.</p>
                        @endif
                    </div>
                </div> --}}

            </div>
        </div>

        <!-- Sidebar -->
        <div class="col-lg-4">
            @if (!$task->is_approved && $task->status->name === 'Complete')
            <div class="card mb-3">
               
                <div class="card-header py-2">
                    <h6 class="mb-0">
                        <i class="bi bi-gear me-2"></i>Actions
                    </h6>
                </div>
                <div class="card-body p-3">
                    <div class="d-grid gap-2">
                        @if (
                            ($task->nature_of_task !== 'one_time' && $task->is_recurring_active && auth()->user()->isSuperAdmin()) ||
                                auth()->user()->isAdmin() ||
                                auth()->user()->isManager() ||
                                $task->assigned_by_user_id === auth()->id())
                            <button class="btn btn-outline-warning btn-sm" wire:click="stopRecurringTask">
                                <i class="bi bi-stop-circle me-2"></i>Stop Recurring
                            </button>
                        @endif

                        <!-- Compact Admin Review Actions for Completed Tasks -->
                        @if (
                            $task->status &&
                                $task->status->name === 'Complete' &&
                                !$task->is_approved &&
                                (auth()->user()->isSuperAdmin() ||
                                    auth()->user()->isAdmin() ||
                                    auth()->user()->isManager() ||
                                    $task->assigned_by_user_id === auth()->id()))
                             <button class="btn btn-success btn-sm" wire:click="showAdminReview"
                             title="Review Completed Task">
                             <i class="bi bi-clipboard-check me-1"></i>Review Task
                         </button>
                        @endif
                    </div>
                </div>
            </div>
@endif
            <!-- Task Information -->
            <div class="card">
                <div class="card-header py-2">
                    <h6 class="mb-0">
                        <i class="bi bi-info-circle me-2"></i>Task Information
                    </h6>
                </div>
                <div class="card-body p-3">
                    <div class="d-grid gap-2">
                        <div class="info-card-compact">
                            <i class="bi bi-folder text-info me-2"></i>
                            <span class="text-muted small">Project:</span>
                            <span class="badge bg-info small">{{ $task->project->title }}</span>
                        </div>
                        <div class="info-card-compact">
                            <i class="bi bi-flag text-warning me-2"></i>
                            <span class="text-muted small">Priority:</span>
                            <span class="badge {{ $task->priority_badge_class }} small">
                                {{ $task->priority->name }}
                            </span>
                        </div>
                        <div class="info-card-compact">
                            <i class="bi bi-person text-primary me-2"></i>
                            <span class="text-muted small">Assigned To:</span>
                            <span class="badge bg-primary small">{{ $task->assignedTo->name }}</span>
                        </div>
                        <div class="info-card-compact">
                            <i class="bi bi-person-check text-success me-2"></i>
                            <span class="text-muted small">Assigned By:</span>
                            <span class="badge bg-success small">{{ $task->assignedBy->name }}</span>
                        </div>
                        @if ($task->duration)
                            <div class="info-card-compact">
                                <i class="bi bi-clock text-info me-2"></i>
                                <span class="text-muted small">Duration:</span>
                                <span class="badge bg-info small">{{ $task->duration }}h</span>
                            </div>
                        @endif
                        @if ($task->due_date)
                            <div class="info-card-compact">
                                <i
                                    class="bi bi-calendar-event {{ $task->is_overdue ? 'text-danger' : 'text-success' }} me-2"></i>
                                <span class="text-muted small">Due Date:</span>
                                <span class="badge {{ $task->is_overdue ? 'bg-danger' : 'bg-success' }} small">
                                    {{ $task->due_date->format('M d, Y') }}
                                    @if ($task->is_overdue)
                                        <i class="bi bi-exclamation-triangle ms-1"></i>
                                    @endif
                                </span>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

    <!-- Delete Attachment Modal -->
    <div class="modal fade" id="deleteAttachmentModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Confirm Delete</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete this attachment? This action cannot be undone.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-danger" id="confirmDeleteAttachmentBtn">Delete</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Delete Comment Modal -->
    <div class="modal fade" id="deleteCommentModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Confirm Delete</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete this comment? This action cannot be undone.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-danger" id="confirmDeleteCommentBtn">Delete</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Admin Review Modal -->
    @if ($showAdminReviewModal)
        <div wire:ignore.self class="modal fade show d-block" tabindex="-1"
            style="background-color: rgba(0,0,0,0.5);">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <!-- Header -->
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title fw-bold">
                            @if ($adminReviewAction === 'approve')
                                <i class="bi bi-check-circle me-2"></i>Approve Task
                            @elseif($adminReviewAction === 'revisit')
                                <i class="bi bi-arrow-clockwise me-2"></i>Mark Task for Revisit
                            @else
                                <i class="bi bi-check-circle me-2"></i>Review Completed Task
                            @endif
                        </h5>
                        <button type="button" class="btn-close btn-close-white"
                            wire:click="closeAdminReviewModal"></button>
                    </div>

                    <!-- Body -->
                    <div class="modal-body">
                        <div class="alert alert-info">
                            <strong><i class="bi bi-info-circle me-2"></i>Task Information:</strong>
                            <p class="mb-0 mt-2">
                                <strong>Title:</strong> {{ $task->title }}<br>
                                <strong>Project:</strong> {{ $task->project->title }}<br>
                                <strong>Assigned To:</strong>
                                {{ $task->assignedTo ? $task->assignedTo->name : 'Unassigned' }}
                            </p>
                        </div>

                        <div class="mb-4">
                            <label class="fw-bold mb-2">
                                <i class="bi bi-chat-text me-2"></i>
                                @if ($adminReviewAction === 'approve')
                                    Approval Comments (Optional)
                                @elseif($adminReviewAction === 'revisit')
                                    Revisit Comments (Required)
                                @else
                                    Review Comments (Optional)
                                @endif
                            </label>
                            <textarea wire:model="adminReviewComments" rows="4" class="form-control"
                                placeholder="@if ($adminReviewAction === 'approve') Add any comments about the task completion...@elseif($adminReviewAction === 'revisit')Explain why the task needs to be revisited...@elseAdd any comments or feedback for the assignee... @endif"></textarea>
                            <small class="text-muted">
                                @if ($adminReviewAction === 'approve')
                                    Comments will be logged with the approval.
                                @elseif($adminReviewAction === 'revisit')
                                    Comments will be included in the email notification sent to assignees.
                                @else
                                    Comments will be logged with the review.
                                @endif
                            </small>
                        </div>

                        <!-- Action Info -->
                        @if ($adminReviewAction === 'approve')
                            <div class="alert alert-success">
                                <strong><i class="bi bi-check-circle me-2"></i>Approval Action:</strong>
                                <p class="mb-0 mt-2">This will mark the task as finally completed. No further
                                    action will be required from the assignee.</p>
                            </div>
                        @elseif($adminReviewAction === 'revisit')
                            <div class="alert alert-warning">
                                <strong><i class="bi bi-arrow-clockwise me-2"></i>Revisit Action:</strong>
                                <p class="mb-0 mt-2">This will change the task status to "Needs Revisit" and send
                                    an email notification to the assignee.</p>
                            </div>
                        @else
                            <div class="alert alert-info">
                                <strong><i class="bi bi-info-circle me-2"></i>Review Options:</strong>
                                <ul class="mb-0 mt-2 ps-3">
                                    <li><strong>Approve:</strong> Mark the task as finally completed (no further
                                        action required).</li>
                                    <li><strong>Revisit:</strong> Send the task back to the assignee with "Needs
                                        Revisit" status.</li>
                                </ul>
                            </div>
                        @endif
                    </div>

                    <!-- Footer -->
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" wire:click="closeAdminReviewModal">
                            <i class="bi bi-x-circle me-2"></i>Cancel
                        </button>
                        @if ($adminReviewAction === 'approve')
                            <button type="button" class="btn btn-success" wire:click="approveTask">
                                <i class="bi bi-check-circle me-2"></i>Approve Task
                            </button>
                        @elseif($adminReviewAction === 'revisit')
                            <button type="button" class="btn btn-warning text-dark" wire:click="revisitTask">
                                <i class="bi bi-arrow-clockwise me-2"></i>Mark for Revisit
                            </button>
                        @else
                            <button type="button" class="btn btn-success" wire:click="approveTask">
                                <i class="bi bi-check-circle me-2"></i>Approve Task
                            </button>
                            <button type="button" class="btn btn-warning text-dark" wire:click="revisitTask">
                                <i class="bi bi-arrow-clockwise me-2"></i>Mark for Revisit
                            </button>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- File Preview Modal -->
    <div class="modal fade" id="filePreviewModal" tabindex="-1" aria-labelledby="filePreviewModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="filePreviewModalLabel">
                        <i class="bi bi-file-earmark me-2"></i><span id="fileNameDisplay"></span>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-0" style="max-height: 70vh; overflow: auto;">
                    <div id="filePreviewContent" class="text-center p-4">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <p class="mt-2">Loading preview...</p>
                    </div>
                </div>
                <div class="modal-footer">
                    <a id="downloadFileLink" href="#" class="btn btn-primary" download>
                        <i class="bi bi-download me-2"></i>Download
                    </a>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        let attachmentIdToDelete = null;
        let commentIdToDelete = null;

        function previewAttachment(attachmentId, fileName, fileExtension) {
            // Set file name in modal title
            document.getElementById('fileNameDisplay').textContent = fileName;
            
            // Reset content to loading state
            const contentDiv = document.getElementById('filePreviewContent');
            contentDiv.innerHTML = `
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <p class="mt-2">Loading preview...</p>
            `;
            
            // Get preview URL
            const previewUrl = '{{ route("attachments.preview", ":id") }}'.replace(':id', attachmentId);
            const downloadUrl = '{{ route("attachments.download", ":id") }}'.replace(':id', attachmentId);
            
            // Set download link
            document.getElementById('downloadFileLink').href = downloadUrl;
            
            // Show modal
            const modal = new bootstrap.Modal(document.getElementById('filePreviewModal'));
            
            // Listen for modal hidden event to clean up
            const modalElement = document.getElementById('filePreviewModal');
            modalElement.addEventListener('hidden.bs.modal', function cleanup() {
                modalElement.removeEventListener('hidden.bs.modal', cleanup);
            });
            
            modal.show();
            
            // Image preview
            if (['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp', 'svg'].includes(fileExtension)) {
                contentDiv.innerHTML = `<img src="${previewUrl}" class="img-fluid" alt="${fileName}" style="max-width: 100%; height: auto;" />`;
            }
            // PDF preview
            else if (fileExtension === 'pdf') {
                contentDiv.innerHTML = `<iframe src="${previewUrl}" style="width: 100%; height: 70vh; border: none;" frameborder="0"></iframe>`;
            }
            // Video preview
            else if (['mp4', 'webm', 'ogg', 'avi', 'mov', 'wmv', 'flv', 'mkv'].includes(fileExtension)) {
                contentDiv.innerHTML = `<video controls style="width: 100%; max-height: 70vh;"><source src="${previewUrl}" type="video/${fileExtension}">Your browser does not support the video tag.</video>`;
            }
            // Text preview
            else if (fileExtension === 'txt') {
                fetch('{{ route("attachments.data", ":id") }}'.replace(':id', attachmentId))
                    .then(response => response.json())
                    .then(data => {
                        if (data.success && data.attachment.content) {
                            contentDiv.innerHTML = `<pre class="text-start p-3" style="background: #f8f9fa; border-radius: 4px; white-space: pre-wrap;">${escapeHtml(data.attachment.content)}</pre>`;
                        } else {
                            contentDiv.innerHTML = `<p class="text-muted">Content not available for preview.</p>`;
                        }
                    })
                    .catch(error => {
                        contentDiv.innerHTML = `<p class="text-danger">Error loading file content: ${error.message}</p>`;
                    });
            }
            // Unsupported file type
            else {
                contentDiv.innerHTML = `
                    <div class="p-4">
                        <i class="bi bi-file-earmark display-1 text-muted"></i>
                        <p class="mt-3">Preview not available for this file type (.${fileExtension})</p>
                        <p class="text-muted small">Please download the file to view it.</p>
                    </div>
                `;
            }
        }

        function escapeHtml(text) {
            const map = {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            };
            return text.replace(/[&<>"']/g, m => map[m]);
        }

        function confirmDeleteAttachment(attachmentId) {
            attachmentIdToDelete = attachmentId;
            const modal = new bootstrap.Modal(document.getElementById('deleteAttachmentModal'));
            modal.show();
        }

        document.getElementById('confirmDeleteAttachmentBtn').addEventListener('click', function() {
            if (attachmentIdToDelete) {
                @this.deleteAttachment(attachmentIdToDelete);
                const modal = bootstrap.Modal.getInstance(document.getElementById('deleteAttachmentModal'));
                modal.hide();
            }
        });

        document.getElementById('confirmDeleteCommentBtn').addEventListener('click', function() {
            if (commentIdToDelete) {
                @this.deleteComment(commentIdToDelete);
                const modal = bootstrap.Modal.getInstance(document.getElementById('deleteCommentModal'));
                modal.hide();
            }
        });

        // Toggle attachments visibility
        window.toggleAttachments = function() {
            const attachmentItems = document.querySelectorAll('[data-attachment-item]');
            const btn = document.getElementById('toggleAttachmentsBtn');
            
            // Check if any items are hidden
            let allVisible = true;
            attachmentItems.forEach(item => {
                if (item.classList.contains('attachment-hidden')) {
                    allVisible = false;
                }
            });

            attachmentItems.forEach(item => {
                const index = parseInt(item.getAttribute('data-attachment-item'));
                if (allVisible) {
                    // Hide all items after index 2 (keep first 3 visible)
                    if (index >= 3) {
                        item.classList.add('attachment-hidden');
                    }
                } else {
                    // Show all items
                    item.classList.remove('attachment-hidden');
                }
            });

            if (allVisible) {
                btn.innerHTML = '<i class="bi bi-chevron-down me-1"></i>View All (' + attachmentItems.length + ')';
            } else {
                btn.innerHTML = '<i class="bi bi-chevron-up me-1"></i>Show Less';
            }
        };

        // Toggle comment attachments visibility
        window.toggleCommentAttachments = function(commentId) {
            const attachmentItems = document.querySelectorAll(`[data-comment-attachment-item^="${commentId}-"]`);
            const btn = event.target.closest('.text-center').querySelector('button');
            
            // Check if any items are hidden
            let allVisible = true;
            attachmentItems.forEach(item => {
                if (item.classList.contains('attachment-hidden')) {
                    allVisible = false;
                }
            });

            attachmentItems.forEach(item => {
                const index = parseInt(item.getAttribute('data-comment-attachment-item').split('-')[1]);
                if (allVisible) {
                    // Hide all items after index 2 (keep first 3 visible)
                    if (index >= 3) {
                        item.classList.add('attachment-hidden');
                    }
                } else {
                    // Show all items
                    item.classList.remove('attachment-hidden');
                }
            });

            if (allVisible) {
                btn.innerHTML = '<i class="bi bi-chevron-down me-1"></i>View All (' + attachmentItems.length + ')';
            } else {
                btn.innerHTML = '<i class="bi bi-chevron-up me-1"></i>Show Less';
            }
        };

        // Toggle comments visibility
        window.toggleComments = function() {
            const commentItems = document.querySelectorAll('[data-comment-item]');
            const btn = document.getElementById('toggleCommentsBtn');
            
            // Check if any items are hidden
            let allVisible = true;
            commentItems.forEach(item => {
                if (item.classList.contains('attachment-hidden')) {
                    allVisible = false;
                }
            });

            commentItems.forEach(item => {
                const index = parseInt(item.getAttribute('data-comment-item'));
                if (allVisible) {
                    // Hide all items after index 2 (keep first 3 visible)
                    if (index >= 3) {
                        item.classList.add('attachment-hidden');
                    }
                } else {
                    // Show all items
                    item.classList.remove('attachment-hidden');
                }
            });

            if (allVisible && btn) {
                btn.innerHTML = '<i class="bi bi-chevron-down me-1"></i>View All (' + commentItems.length + ')';
            } else if (btn) {
                btn.innerHTML = '<i class="bi bi-chevron-up me-1"></i>Show Less';
            }
        };
    </script>

    <style>
        /* Minimized Task Details Styles with Dark Theme Support */
        :root {
            --bg-primary: #f8f9fa;
            --bg-secondary: #e9ecef;
            --bg-card: #ffffff;
            --text-primary: #212529;
            --text-secondary: #6c757d;
            --border-color: #e9ecef;
            --shadow-light: rgba(0, 0, 0, 0.1);
        }

        [data-bs-theme="dark"] {
            --bg-primary: #1a1a1a;
            --bg-secondary: #2d2d2d;
            --bg-card: #2d2d2d;
            --text-primary: #ffffff;
            --text-secondary: #adb5bd;
            --border-color: #495057;
            --shadow-light: rgba(0, 0, 0, 0.3);
        }

        .task-details-container {
            background: linear-gradient(135deg, var(--bg-primary) 0%, var(--bg-secondary) 100%);
            min-height: 100vh;
            padding: 1rem 2rem;
            color: var(--text-primary);
        }

        .bg-gradient-primary {
            background: linear-gradient(135deg, #0d6efd 0%, #6610f2 100%) !important;
        }

        .card {
            border-radius: 8px;
            background-color: var(--bg-card);
            border: 1px solid var(--border-color);
        }

        .card-header {
            border-radius: 8px 8px 0 0 !important;
            border-bottom: 1px solid var(--border-color);
        }

        .card-body {
            background-color: var(--bg-card);
            color: var(--text-primary);
        }

        .info-card-compact {
            display: flex;
            align-items: center;
            padding: 0.5rem;
            background: var(--bg-card);
            border-radius: 6px;
            border: 1px solid var(--border-color);
            font-size: 0.875rem;
        }

        .description-box-compact,
        .notes-box-compact {
            background: var(--bg-secondary);
            border: 1px solid var(--border-color);
            border-radius: 6px;
            padding: 0.75rem;
            font-size: 0.875rem;
            line-height: 1.4;
            color: var(--text-primary);
        }

        .admin-actions-compact {
            background: var(--bg-secondary);
            border: 1px solid var(--border-color);
            border-radius: 8px;
            padding: 1rem;
            margin: 1rem 0;
        }

        .comment-item,
        .note-item,
        .attachment-item,
        .file-item {
            background: var(--bg-secondary);
            border-color: var(--border-color);
        }

        .file-comment {
            font-style: italic;
            color: var(--text-secondary);
        }

        .badge {
            border-radius: 4px;
            font-weight: 600;
        }

        /* Dark theme adjustments */
        [data-bs-theme="dark"] .text-primary {
            color: #ffffff !important;
        }

        [data-bs-theme="dark"] .text-muted {
            color: #adb5bd !important;
        }

        [data-bs-theme="dark"] .text-dark {
            color: #ffffff !important;
        }

        [data-bs-theme="dark"] .form-control {
            background-color: var(--bg-card);
            border-color: var(--border-color);
            color: var(--text-primary);
        }

        [data-bs-theme="dark"] .form-control:focus {
            background-color: var(--bg-card);
            border-color: #0d6efd;
            color: var(--text-primary);
            box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.25);
        }

        /* Responsive */
        @media (max-width: 768px) {
            .info-card-compact {
                flex-direction: column;
                text-align: center;
                
            }
        }

        /* Smooth transitions */
        * {
            transition: background-color 0.3s ease, color 0.3s ease, border-color 0.3s ease;
        }

        /* Attachment visibility */
        .attachment-hidden {
            display: none !important;
        }
    </style>
    </div>
