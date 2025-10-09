<div>
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Task Details</h2>
        <a href="{{ route('tasks.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-2"></i>Back to Tasks
        </a>
    </div>

    <div class="row">
        <!-- Task Information -->
        <div class="col-md-8">
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">{{ $task->title }}</h5>
                    <div class="dropdown">
                        <button class="btn btn-outline-secondary dropdown-toggle" 
                                type="button" data-bs-toggle="dropdown">
                            <span class="badge {{ $task->status_badge_class }}">
                                {{ ucfirst($task->status) }}
                            </span>
                        </button>
                        <ul class="dropdown-menu">
                            @foreach($this->statuses as $status)
                                @if(!auth()->user()->isEmployee() || $status->name !== 'Complete')
                                    <li>
                                        <button class="dropdown-item" 
                                                wire:click="updateTaskStatus({{ $task->id }}, {{ $status->id }})">
                                            <span class="badge bg-{{ $status->color }} me-2">{{ $status->name }}</span>
                                        </button>
                                    </li>
                                @endif
                            @endforeach
                        </ul>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <strong>Project:</strong>
                            <span class="badge bg-info">{{ $task->project->title }}</span>
                        </div>
                        <div class="col-md-6">
                            <strong>Priority:</strong>
                            <span class="badge {{ $task->priority_badge_class }}">
                                {{ ucfirst($task->priority) }}
                            </span>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <strong>Assigned To:</strong> {{ $task->assignedTo->name }}
                        </div>
                        <div class="col-md-6">
                            <strong>Assigned By:</strong> {{ $task->assignedBy->name }}
                        </div>
                    </div>

                    @if($task->duration)
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <strong>Duration:</strong> {{ $task->duration }} hours
                        </div>
                        @if($task->due_date)
                        <div class="col-md-6">
                            <strong>Due Date:</strong> 
                            <span class="{{ $task->is_overdue ? 'text-danger' : '' }}">
                                {{ $task->due_date->format('M d, Y') }}
                                @if($task->is_overdue)
                                    <i class="bi bi-exclamation-triangle text-danger ms-1" title="Overdue"></i>
                                @endif
                            </span>
                        </div>
                        @endif
                    </div>
                    @endif

                    @if($task->description)
                    <div class="mb-3">
                        <strong>Description:</strong>
                        <p class="mt-2">{{ $task->description }}</p>
                    </div>
                    @endif

                    @if($task->notes)
                    <div class="mb-3">
                        <strong>Notes:</strong>
                        <div class="mt-2 p-3 rounded" style="background-color: var(--bg-tertiary);">
                            {!! nl2br(e($task->notes)) !!}
                        </div>
                    </div>
                    @endif
                </div>
            </div>

            <!-- Comments Section -->
            <div class="card mb-4">
                <div class="card-header">
                    <h6 class="mb-0">Comments & Discussion</h6>
                </div>
                <div class="card-body">
                    <!-- Add Comment Form -->
                    <form wire:submit="addComment" class="mb-4">
                        <div class="mb-3">
                            <textarea class="form-control @error('newComment') is-invalid @enderror" 
                                      wire:model="newComment" rows="3" 
                                      placeholder="Add a comment..."></textarea>
                            @error('newComment')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <!-- File Upload for Comments -->
                        <div class="mb-3">
                            <label class="form-label">Attach Files (Optional)</label>
                            <input type="file" class="form-control" wire:model="commentAttachments" multiple>
                            <div class="form-text">You can upload multiple files (max 10MB each)</div>
                            @error('commentAttachments.*')
                                <div class="text-danger small">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-chat-dots me-1"></i>Add Comment
                        </button>
                    </form>

                    <!-- Comments List -->
                    @if($task->noteComments->count() > 0)
                        <div class="comments-list">
                            @foreach($task->noteComments->sortByDesc('created_at') as $comment)
                                <div class="comment-item border-bottom pb-3 mb-3">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <div class="d-flex align-items-center">
                                            <div class="avatar-sm bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-2">
                                                {{ substr($comment->user->name, 0, 1) }}
                                            </div>
                                            <div>
                                                <strong>{{ $comment->user->name }}</strong>
                                                <small class="text-muted ms-2">{{ $comment->created_at->format('M d, Y H:i') }}</small>
                                            </div>
                                        </div>
                                        @if(auth()->user()->isSuperAdmin() || auth()->user()->isAdmin() || $comment->user_id === auth()->id())
                                            <button class="btn btn-sm btn-outline-danger" 
                                                    onclick="confirmDeleteComment({{ $comment->id }})">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        @endif
                                    </div>
                                    
                                    <div class="comment-content mb-2">
                                        {{ $comment->comment }}
                                    </div>
                                    
                                    <!-- Comment Attachments -->
                                    @if($comment->attachments && $comment->attachments->count() > 0)
                                        <div class="comment-attachments">
                                            <small class="text-muted">Attachments:</small>
                                            <div class="d-flex flex-wrap gap-2 mt-1">
                                                @foreach($comment->attachments as $attachment)
                                                    <div class="attachment-item d-flex align-items-center p-2 rounded" style="background-color: var(--bg-tertiary);">
                                                        <i class="bi bi-paperclip me-2"></i>
                                                        <a href="{{ route('attachments.download', $attachment->id) }}" 
                                                           class="text-decoration-none">
                                                            {{ $attachment->file_name }}
                                                        </a>
                                                        <small class="text-muted ms-2">({{ $attachment->formatted_file_size }})</small>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center text-muted py-4">
                            <i class="bi bi-chat-dots fs-1"></i>
                            <p class="mt-2">No comments yet. Be the first to comment!</p>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Add Note (Legacy) -->
            <div class="card mb-4">
                <div class="card-header">
                    <h6 class="mb-0">Add Note (Legacy)</h6>
                </div>
                <div class="card-body">
                    <form wire:submit="addNote">
                        <div class="mb-3">
                            <textarea class="form-control @error('newNote') is-invalid @enderror" 
                                      wire:model="newNote" rows="3" 
                                      placeholder="Add a note..."></textarea>
                            @error('newNote')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <button type="submit" class="btn btn-secondary btn-sm">
                            <i class="bi bi-plus-circle me-1"></i>Add Note
                        </button>
                    </form>
                </div>
            </div>

            <!-- Attachments -->
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0">Attachments</h6>
                </div>
                <div class="card-body">
                    <!-- Upload New Attachments -->
                    <form wire:submit="addAttachments" class="mb-3">
                        <div class="input-group">
                            <input type="file" class="form-control" wire:model="newAttachments" multiple>
                            <button type="submit" class="btn btn-outline-primary">
                                <i class="bi bi-upload me-1"></i>Upload
                            </button>
                        </div>
                        <div class="form-text">You can upload multiple files (max 10MB each)</div>
                    </form>

                    <!-- Existing Attachments -->
                    @if($task->attachments->count() > 0)
                        <div class="list-group">
                            @foreach($task->attachments as $attachment)
                                <div class="list-group-item d-flex justify-content-between align-items-center">
                                    <div>
                                        <i class="bi bi-paperclip me-2"></i>
                                        <strong>{{ $attachment->file_name }}</strong>
                                        <small class="text-muted ms-2">({{ $attachment->formatted_file_size }})</small>
                                        <br>
                                        <small class="text-muted">
                                            Uploaded by {{ $attachment->uploadedBy->name }} 
                                            on {{ $attachment->created_at->format('M d, Y H:i') }}
                                        </small>
                                    </div>
                                    <div class="btn-group">
                                        <a href="{{ route('attachments.download', $attachment->id) }}" 
                                           class="btn btn-sm btn-outline-primary">
                                            <i class="bi bi-download"></i>
                                        </a>
                                        @if(auth()->user()->isSuperAdmin() || $attachment->uploaded_by_user_id === auth()->id())
                                            <button type="button" class="btn btn-sm btn-outline-danger" 
                                                    onclick="confirmDeleteAttachment({{ $attachment->id }})">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="text-muted text-center">No attachments uploaded yet.</p>
                    @endif
                </div>
            </div>
        </div>

        <!-- Task Actions Sidebar -->
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0">Task Actions</h6>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        @foreach($this->statuses as $status)
                            @if($status->name === 'In Progress')
                                <button class="btn btn-outline-primary" 
                                        wire:click="updateTaskStatus({{ $task->id }}, {{ $status->id }})">
                                    <i class="bi bi-play-circle me-2"></i>{{ $status->name }}
                                </button>
                            @elseif($status->name === 'Complete' && !auth()->user()->isEmployee())
                                <button class="btn btn-outline-success" 
                                        wire:click="updateTaskStatus({{ $task->id }}, {{ $status->id }})">
                                    <i class="bi bi-check-circle me-2"></i>{{ $status->name }}
                                </button>
                            @elseif($status->name === 'Pending')
                                <button class="btn btn-outline-secondary" 
                                        wire:click="updateTaskStatus({{ $task->id }}, {{ $status->id }})">
                                    <i class="bi bi-pause-circle me-2"></i>{{ $status->name }}
                                </button>
                            @elseif($status->name === 'Submit for Approval')
                                <button class="btn btn-outline-info" 
                                        wire:click="updateTaskStatus({{ $task->id }}, {{ $status->id }})">
                                    <i class="bi bi-send me-2"></i>{{ $status->name }}
                                </button>
                            @endif
                        @endforeach
                    </div>
                </div>
            </div>

            <!-- Task Info -->
            <div class="card mt-3">
                <div class="card-header">
                    <h6 class="mb-0">Task Information</h6>
                </div>
                <div class="card-body">
                    <div class="mb-2">
                        <small class="text-muted">Created:</small><br>
                        {{ $task->created_at->format('M d, Y H:i') }}
                    </div>
                    <div class="mb-2">
                        <small class="text-muted">Last Updated:</small><br>
                        {{ $task->updated_at->format('M d, Y H:i') }}
                    </div>
                    @if($task->due_date)
                    <div class="mb-2">
                        <small class="text-muted">Due Date:</small><br>
                        <span class="{{ $task->is_overdue ? 'text-danger' : '' }}">
                            {{ $task->due_date->format('M d, Y') }}
                            @if($task->is_overdue)
                                <i class="bi bi-exclamation-triangle text-danger ms-1"></i>
                            @endif
                        </span>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Delete Attachment Confirmation Modal -->
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

    <!-- Delete Comment Confirmation Modal -->
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

    <script>
        let attachmentIdToDelete = null;
        let commentIdToDelete = null;

        function confirmDeleteAttachment(attachmentId) {
            attachmentIdToDelete = attachmentId;
            const modal = new bootstrap.Modal(document.getElementById('deleteAttachmentModal'));
            modal.show();
        }

        function confirmDeleteComment(commentId) {
            commentIdToDelete = commentId;
            const modal = new bootstrap.Modal(document.getElementById('deleteCommentModal'));
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
    </script>
</div>
