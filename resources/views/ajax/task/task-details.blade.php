@extends('layouts.app')

@section('content')
<div class="task-details-container" id="taskDetailsContainer" data-task-id="{{ $taskId }}">
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
                <div class="card-header text-white border-0 py-2" id="taskHeader">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="d-flex align-items-center">
                            <i class="bi bi-clipboard-data me-2"></i>
                            <h6 class="mb-0 fw-bold" id="taskTitle">Loading...</h6>
                            <span id="taskBadges"></span>
                        </div>
                        <div id="taskStatusBadge">
                            <div class="spinner-border spinner-border-sm text-white" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-body p-3">
                    <!-- Compact Description -->
                    <div id="taskDescription" class="mb-3" style="display: none;">
                        <h6 class="fw-bold text-dark mb-2 small">
                            <i class="bi bi-file-text me-1"></i>Description
                        </h6>
                        <div class="description-box-compact" id="taskDescriptionContent"></div>
                    </div>
                </div>
                
                <!-- Attachments -->
                <div class="card mb-3">
                    <div class="card-header py-2">
                        <h6 class="mb-0">
                            <i class="bi bi-paperclip me-2"></i>Attachments (<span id="attachmentsCount">0</span>)
                        </h6>
                    </div>
                    <div class="card-body p-3">
                        <!-- Upload New Attachments -->
                        <form id="addAttachmentForm" class="mb-3">
                            <div class="input-group input-group-sm">
                                <input type="file" class="form-control" id="attachmentsInput" multiple>
                                <button type="submit" class="btn btn-outline-primary">
                                    <i class="bi bi-upload me-1"></i>Upload
                                </button>
                            </div>
                            <div class="form-text small">You can upload multiple files (max 10MB each)</div>
                        </form>

                        <!-- Existing Attachments -->
                        <div id="attachmentsSection">
                            <div class="text-center py-3">
                                <div class="spinner-border spinner-border-sm text-primary" role="status">
                                    <span class="visually-hidden">Loading...</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
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
                    <form id="addCommentForm" class="mb-3">
                        <div class="mb-2">
                            <textarea id="commentInput" rows="2" class="form-control form-control-sm" placeholder="Add a comment..."></textarea>
                            <div class="invalid-feedback" id="commentError"></div>
                            <input type="file" class="form-control form-control-sm mt-2" id="commentAttachments" multiple>
                            <div class="invalid-feedback" id="commentAttachmentsError"></div>
                        </div>
                        <button type="submit" class="btn btn-primary btn-sm">
                            <i class="bi bi-chat-dots me-1"></i>Add Comment
                        </button>
                    </form>

                    <!-- Comments List -->
                    <div id="commentsSection">
                        <div class="text-center py-3">
                            <div class="spinner-border spinner-border-sm text-primary" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sidebar -->
        <div class="col-lg-4">
            <!-- Actions Section -->
            <div class="card mb-3" id="actionsSection" style="display: none;">
                <div class="card-header py-2">
                    <h6 class="mb-0">
                        <i class="bi bi-gear me-2"></i>Actions
                    </h6>
                </div>
                <div class="card-body p-3">
                    <div class="d-grid gap-2" id="actionsContent">
                        <!-- Populated by JavaScript -->
                    </div>
                </div>
            </div>

            <!-- Task Information -->
            <div class="card">
                <div class="card-header py-2">
                    <h6 class="mb-0">
                        <i class="bi bi-info-circle me-2"></i>Task Information
                    </h6>
                </div>
                <div class="card-body p-3">
                    <div class="d-grid gap-2" id="taskInfo">
                        <div class="text-center py-4">
                            <div class="spinner-border spinner-border-sm text-primary" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                        </div>
                    </div>
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
    <div class="modal fade" id="adminReviewModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title fw-bold" id="adminReviewModalTitle">
                        <i class="bi bi-check-circle me-2"></i>Review Completed Task
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-info">
                        <strong><i class="bi bi-info-circle me-2"></i>Task Information:</strong>
                        <p class="mb-0 mt-2" id="adminReviewTaskInfo"></p>
                    </div>
                    <div class="mb-4">
                        <label class="fw-bold mb-2">
                            <i class="bi bi-chat-text me-2"></i>
                            <span id="adminReviewLabel">Review Comments (Optional)</span>
                        </label>
                        <textarea id="adminReviewComments" rows="4" class="form-control" placeholder="Add any comments or feedback for the assignee..."></textarea>
                        <small class="text-muted" id="adminReviewHelpText">Comments will be logged with the review.</small>
                    </div>
                    <div class="alert alert-info" id="adminReviewActionInfo">
                        <strong><i class="bi bi-info-circle me-2"></i>Review Options:</strong>
                        <ul class="mb-0 mt-2 ps-3">
                            <li><strong>Approve:</strong> Mark the task as finally completed (no further action required).</li>
                            <li><strong>Revisit:</strong> Send the task back to the assignee with "Needs Revisit" status.</li>
                        </ul>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="bi bi-x-circle me-2"></i>Cancel
                    </button>
                    <button type="button" class="btn btn-success" id="approveTaskBtn">
                        <i class="bi bi-check-circle me-2"></i>Approve Task
                    </button>
                    <button type="button" class="btn btn-warning text-dark" id="revisitTaskBtn">
                        <i class="bi bi-arrow-clockwise me-2"></i>Mark for Revisit
                    </button>
                </div>
            </div>
        </div>
    </div>

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
</div>

@push('scripts')
<script src="{{ asset('js/ajax-task-details.js') }}"></script>
<script>
    let attachmentIdToDelete = null;
    let commentIdToDelete = null;

    function previewAttachment(attachmentId, fileName, fileExtension) {
        document.getElementById('fileNameDisplay').textContent = fileName;
        
        const contentDiv = document.getElementById('filePreviewContent');
        contentDiv.innerHTML = `
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
            <p class="mt-2">Loading preview...</p>
        `;
        
        const previewUrl = '{{ route("attachments.preview", ":id") }}'.replace(':id', attachmentId);
        const downloadUrl = '{{ route("attachments.download", ":id") }}'.replace(':id', attachmentId);
        
        document.getElementById('downloadFileLink').href = downloadUrl;
        
        const modal = new bootstrap.Modal(document.getElementById('filePreviewModal'));
        modal.show();
        
        if (['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp', 'svg'].includes(fileExtension)) {
            contentDiv.innerHTML = `<img src="${previewUrl}" class="img-fluid" alt="${fileName}" style="max-width: 100%; height: auto;" />`;
        } else if (fileExtension === 'pdf') {
            contentDiv.innerHTML = `<iframe src="${previewUrl}" style="width: 100%; height: 70vh; border: none;" frameborder="0"></iframe>`;
        } else if (['mp4', 'webm', 'ogg', 'avi', 'mov', 'wmv', 'flv', 'mkv'].includes(fileExtension)) {
            contentDiv.innerHTML = `<video controls style="width: 100%; max-height: 70vh;"><source src="${previewUrl}" type="video/${fileExtension}">Your browser does not support the video tag.</video>`;
        } else if (fileExtension === 'txt') {
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
        } else {
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

    function confirmDeleteComment(commentId) {
        commentIdToDelete = commentId;
        const modal = new bootstrap.Modal(document.getElementById('deleteCommentModal'));
        modal.show();
    }

    document.getElementById('confirmDeleteAttachmentBtn').addEventListener('click', function() {
        if (attachmentIdToDelete && typeof taskDetailsManager !== 'undefined') {
            taskDetailsManager.deleteAttachment(attachmentIdToDelete);
            const modal = bootstrap.Modal.getInstance(document.getElementById('deleteAttachmentModal'));
            modal.hide();
            attachmentIdToDelete = null;
        }
    });

    document.getElementById('confirmDeleteCommentBtn').addEventListener('click', function() {
        if (commentIdToDelete && typeof taskDetailsManager !== 'undefined') {
            taskDetailsManager.deleteComment(commentIdToDelete);
            const modal = bootstrap.Modal.getInstance(document.getElementById('deleteCommentModal'));
            modal.hide();
            commentIdToDelete = null;
        }
    });

    window.toggleAttachments = function() {
        const attachmentItems = document.querySelectorAll('[data-attachment-item]');
        const btn = document.getElementById('toggleAttachmentsBtn');
        
        let allVisible = true;
        attachmentItems.forEach(item => {
            if (item.classList.contains('attachment-hidden')) {
                allVisible = false;
            }
        });

        attachmentItems.forEach(item => {
            const index = parseInt(item.getAttribute('data-attachment-item'));
            if (allVisible) {
                if (index >= 3) {
                    item.classList.add('attachment-hidden');
                }
            } else {
                item.classList.remove('attachment-hidden');
            }
        });

        if (btn) {
            if (allVisible) {
                btn.innerHTML = '<i class="bi bi-chevron-down me-1"></i>View All (' + attachmentItems.length + ')';
            } else {
                btn.innerHTML = '<i class="bi bi-chevron-up me-1"></i>Show Less';
            }
        }
    };

    window.toggleCommentAttachments = function(commentId) {
        const attachmentItems = document.querySelectorAll(`[data-comment-attachment-item^="${commentId}-"]`);
        const btn = event.target.closest('.text-center').querySelector('button');
        
        let allVisible = true;
        attachmentItems.forEach(item => {
            if (item.classList.contains('attachment-hidden')) {
                allVisible = false;
            }
        });

        attachmentItems.forEach(item => {
            const index = parseInt(item.getAttribute('data-comment-attachment-item').split('-')[1]);
            if (allVisible) {
                if (index >= 3) {
                    item.classList.add('attachment-hidden');
                }
            } else {
                item.classList.remove('attachment-hidden');
            }
        });

        if (btn) {
            if (allVisible) {
                btn.innerHTML = '<i class="bi bi-chevron-down me-1"></i>View All (' + attachmentItems.length + ')';
            } else {
                btn.innerHTML = '<i class="bi bi-chevron-up me-1"></i>Show Less';
            }
        }
    };

    window.toggleComments = function() {
        const commentItems = document.querySelectorAll('[data-comment-item]');
        const btn = document.getElementById('toggleCommentsBtn');
        
        let allVisible = true;
        commentItems.forEach(item => {
            if (item.classList.contains('attachment-hidden')) {
                allVisible = false;
            }
        });

        commentItems.forEach(item => {
            const index = parseInt(item.getAttribute('data-comment-item'));
            if (allVisible) {
                if (index >= 3) {
                    item.classList.add('attachment-hidden');
                }
            } else {
                item.classList.remove('attachment-hidden');
            }
        });

        if (btn) {
            if (allVisible) {
                btn.innerHTML = '<i class="bi bi-chevron-down me-1"></i>View All (' + commentItems.length + ')';
            } else {
                btn.innerHTML = '<i class="bi bi-chevron-up me-1"></i>Show Less';
            }
        }
    };
</script>
@endpush

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

    @media (max-width: 768px) {
        .info-card-compact {
            flex-direction: column;
            text-align: center;
        }
    }

    * {
        transition: background-color 0.3s ease, color 0.3s ease, border-color 0.3s ease;
    }

    .attachment-hidden {
        display: none !important;
    }
</style>
@endsection
