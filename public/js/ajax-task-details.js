/**
 * AJAX Task Details Management
 */

class TaskDetailsManager {
    constructor(taskId) {
        this.taskId = taskId;
        this.task = null;
        this.statuses = [];
        this.init();
    }

    async init() {
        await Promise.all([
            this.loadTask(),
            this.loadStatuses()
        ]);
        this.setupEventListeners();
    }

    setupEventListeners() {
        // Add note form
        const noteForm = document.getElementById('addNoteForm');
        if (noteForm) {
            noteForm.addEventListener('submit', (e) => {
                e.preventDefault();
                this.addNote();
            });
        }

        // Add comment form
        const commentForm = document.getElementById('addCommentForm');
        if (commentForm) {
            commentForm.addEventListener('submit', (e) => {
                e.preventDefault();
                this.addComment();
            });
        }

        // Add attachments form
        const attachmentForm = document.getElementById('addAttachmentForm');
        if (attachmentForm) {
            attachmentForm.addEventListener('submit', (e) => {
                e.preventDefault();
                this.addAttachments();
            });
        }
    }

    async loadTask() {
        try {
            const response = await fetch(`/tasks/${this.taskId}/details/data`);
            const data = await response.json();
            if (data.success) {
                this.task = data.task;
                this.renderTask();
            } else {
                console.error('Error loading task:', data.message);
                this.showError('Error loading task: ' + (data.message || 'Unknown error'));
            }
        } catch (error) {
            console.error('Error loading task:', error);
            this.showError('Error loading task: ' + error.message);
        }
    }

    async loadStatuses() {
        try {
            const response = await fetch(`/tasks/${this.taskId}/details/statuses`);
            const data = await response.json();
            if (data.success) {
                this.statuses = data.statuses;
                this.populateStatusDropdown();
            }
        } catch (error) {
            console.error('Error loading statuses:', error);
        }
    }

    showError(message) {
        const taskInfo = document.getElementById('taskInfo');
        const taskHeader = document.getElementById('taskHeader');
        const attachmentsSection = document.getElementById('attachmentsSection');
        const commentsSection = document.getElementById('commentsSection');
        
        if (taskInfo) {
            taskInfo.innerHTML = `
                <div class="alert alert-danger">
                    <i class="bi bi-exclamation-triangle me-2"></i>${message}
                </div>
            `;
        }
        if (taskHeader) {
            taskHeader.innerHTML = `
                <div class="alert alert-danger mb-0">
                    <i class="bi bi-exclamation-triangle me-2"></i>Error loading task
                </div>
            `;
        }
        if (attachmentsSection) {
            attachmentsSection.innerHTML = '<p class="text-muted small">Error loading attachments</p>';
        }
        if (commentsSection) {
            commentsSection.innerHTML = '<p class="text-muted small">Error loading comments</p>';
        }
    }

    populateStatusDropdown() {
        // Status dropdown is now handled in renderTaskHeader
        // This function is kept for compatibility
    }

    renderTask() {
        if (!this.task) return;

        // Update page title
        document.title = `${this.task.title} - Task Details`;

        // Render task header
        this.renderTaskHeader();

        // Render task description
        this.renderTaskDescription();

        // Render task info sidebar
        this.renderTaskInfo();

        // Render actions section
        this.renderActions();

        // Render attachments
        this.renderAttachments();

        // Render comments
        this.renderComments();
    }

    renderTaskHeader() {
        const taskHeader = document.getElementById('taskHeader');
        const taskTitle = document.getElementById('taskTitle');
        const taskBadges = document.getElementById('taskBadges');
        const taskStatusBadge = document.getElementById('taskStatusBadge');

        if (!taskHeader) return;

        // Set header background color based on status
        const statusColor = this.task.status?.color || 'primary';
        taskHeader.className = `card-header text-white border-0 py-2 bg-${statusColor}`;

        if (taskTitle) {
            taskTitle.textContent = this.task.title;
        }

        // Render badges
        if (taskBadges) {
            let badgesHtml = '';
            if (this.task.is_recurring || ['daily', 'weekly', 'monthly'].includes(this.task.nature_of_task)) {
                badgesHtml += '<span class="badge bg-light text-primary ms-2 small">Recurring</span>';
            }
            if (this.task.is_approved) {
                badgesHtml += '<span class="badge bg-success ms-2 small">Approved</span>';
            }
            taskBadges.innerHTML = badgesHtml;
        }

        // Render status badge
        if (taskStatusBadge && this.task.status) {
            const statusColor = this.task.status.color || 'secondary';
            const canEditStatus = !this.task.is_approved && 
                (this.task.status.name === 'Complete' || this.task.status.name === 'In Progress');
            
            if (canEditStatus && this.statuses.length > 0) {
                // Show dropdown for status update
                const availableStatuses = this.statuses.filter(s => 
                    ['Complete', 'In Progress'].includes(s.name)
                );
                
                if (availableStatuses.length > 0) {
                    taskStatusBadge.innerHTML = `
                        <div class="dropdown">
                            <button class="btn btn-light btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                <span class="badge bg-${statusColor} small">${this.task.status.name}</span>
                            </button>
                            <ul class="dropdown-menu">
                                ${availableStatuses.map(s => `
                                    <li>
                                        <button class="dropdown-item" onclick="taskDetailsManager.updateStatusTo(${s.id})">
                                            <span class="badge bg-${s.color} me-2">${s.name}</span>
                                        </button>
                                    </li>
                                `).join('')}
                            </ul>
                        </div>
                    `;
                } else {
                    taskStatusBadge.innerHTML = `<span class="badge bg-${statusColor} small">${this.task.status.name}</span>`;
                }
            } else {
                taskStatusBadge.innerHTML = `<span class="badge bg-${statusColor} small">${this.task.status.name}</span>`;
            }
        }
    }

    renderTaskDescription() {
        const taskDescription = document.getElementById('taskDescription');
        const taskDescriptionContent = document.getElementById('taskDescriptionContent');
        
        if (this.task.description) {
            if (taskDescription) taskDescription.style.display = 'block';
            if (taskDescriptionContent) {
                taskDescriptionContent.textContent = this.task.description;
            }
        } else {
            if (taskDescription) taskDescription.style.display = 'none';
        }
    }

    renderTaskInfo() {
        const taskInfo = document.getElementById('taskInfo');
        if (!taskInfo) return;

        const isOverdue = this.task.due_date && new Date(this.task.due_date) < new Date();
        const dueDateFormatted = this.task.due_date ? 
            new Date(this.task.due_date).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' }) : null;

        taskInfo.innerHTML = `
            <div class="info-card-compact">
                <i class="bi bi-folder text-info me-2"></i>
                <span class="text-muted small">Project:</span>
                ${this.task.project ? 
                    `<span class="badge bg-info small">${this.task.project.title}</span>` : 
                    `<span class="badge bg-secondary small">No Project</span>`
                }
            </div>
            <div class="info-card-compact">
                <i class="bi bi-flag text-warning me-2"></i>
                <span class="text-muted small">Priority:</span>
                <span class="badge bg-${this.task.priority?.color || 'secondary'} small">
                    ${this.task.priority?.name || 'No Priority'}
                </span>
            </div>
            <div class="info-card-compact">
                <i class="bi bi-person text-primary me-2"></i>
                <span class="text-muted small">Assigned To:</span>
                <span class="badge bg-primary small">${this.task.assigned_to?.name || 'Unassigned'}</span>
            </div>
            <div class="info-card-compact">
                <i class="bi bi-person-check text-success me-2"></i>
                <span class="text-muted small">Assigned By:</span>
                <span class="badge bg-success small">${this.task.assigned_by?.name || 'Unknown'}</span>
            </div>
            ${this.task.estimated_hours ? `
                <div class="info-card-compact">
                    <i class="bi bi-clock text-info me-2"></i>
                    <span class="text-muted small">Duration:</span>
                    <span class="badge bg-info small">${this.task.estimated_hours}h</span>
                </div>
            ` : ''}
            ${dueDateFormatted ? `
                <div class="info-card-compact">
                    <i class="bi bi-calendar-event ${isOverdue ? 'text-danger' : 'text-success'} me-2"></i>
                    <span class="text-muted small">Due Date:</span>
                    <span class="badge ${isOverdue ? 'bg-danger' : 'bg-success'} small">
                        ${dueDateFormatted}
                        ${isOverdue ? '<i class="bi bi-exclamation-triangle ms-1"></i>' : ''}
                    </span>
                </div>
            ` : ''}
        `;
    }

    renderActions() {
        const actionsSection = document.getElementById('actionsSection');
        const actionsContent = document.getElementById('actionsContent');
        
        if (!actionsSection || !actionsContent) return;

        const showActions = (!this.task.is_approved && this.task.status?.name === 'Complete') || 
                           this.task.is_recurring_active === true;

        if (!showActions) {
            actionsSection.style.display = 'none';
            return;
        }

        actionsSection.style.display = 'block';
        let actionsHtml = '';

        // Stop Recurring button
        if (this.task.is_recurring_active && 
            (this.task.nature_of_task !== 'one_time' || this.task.assigned_by_user_id === this.getCurrentUserId())) {
            actionsHtml += `
                <button class="btn btn-outline-warning btn-sm" onclick="taskDetailsManager.stopRecurring()">
                    <i class="bi bi-stop-circle me-2"></i>Stop Recurring
                </button>
            `;
        }

        // Review Task button
        if (this.task.status?.name === 'Complete' && !this.task.is_approved) {
            actionsHtml += `
                <button class="btn btn-success btn-sm" onclick="taskDetailsManager.showAdminReview()">
                    <i class="bi bi-clipboard-check me-1"></i>Review Task
                </button>
            `;
        }

        actionsContent.innerHTML = actionsHtml || '<p class="text-muted small mb-0">No actions available</p>';
    }

    getCurrentUserId() {
        // This should be set from the backend or a meta tag
        const meta = document.querySelector('meta[name="user-id"]');
        return meta ? parseInt(meta.content) : null;
    }

    renderAttachments() {
        const attachmentsSection = document.getElementById('attachmentsSection');
        const attachmentsCount = document.getElementById('attachmentsCount');
        
        if (!attachmentsSection) return;

        if (!this.task.attachments || this.task.attachments.length === 0) {
            attachmentsSection.innerHTML = '<p class="text-muted small mb-0">No attachments yet.</p>';
            if (attachmentsCount) attachmentsCount.textContent = '0';
            return;
        }

        if (attachmentsCount) attachmentsCount.textContent = this.task.attachments.length;

        attachmentsSection.innerHTML = `
            <div class="attachments-list">
                ${this.task.attachments.map((attachment, index) => {
                    const extension = attachment.file_name ? 
                        attachment.file_name.split('.').pop().toLowerCase() : '';
                    const iconClass = this.getFileIconClass(extension);
                    const fileSize = attachment.file_size ? 
                        (attachment.file_size / 1024).toFixed(1) + ' KB' : 'Unknown size';
                    const createdAt = new Date(attachment.created_at).toLocaleString();
                    const isHidden = index >= 3 ? 'attachment-hidden' : '';
                    
                    return `
                        <div class="attachment-item d-flex justify-content-between align-items-center mb-2 p-2 border rounded ${isHidden}" 
                             data-attachment-item="${index}">
                            <div class="d-flex align-items-center">
                                <i class="bi ${iconClass} text-muted me-2"></i>
                                <div>
                                    <div class="small fw-semibold">${attachment.file_name}</div>
                                    <div class="text-muted small">${fileSize} • ${createdAt}</div>
                                </div>
                            </div>
                            <div class="btn-group btn-group-sm">
                                <button type="button" class="btn btn-outline-primary" 
                                    onclick="previewAttachment(${attachment.id}, '${attachment.file_name}', '${extension}')">
                                    <i class="bi bi-eye"></i>
                                </button>
                                <a href="/attachments/${attachment.id}/download"
                                    class="btn btn-outline-primary">
                                    <i class="bi bi-download"></i>
                                </a>
                                ${this.canDeleteAttachment(attachment) ? `
                                    <button type="button" class="btn btn-outline-danger" 
                                        onclick="confirmDeleteAttachment(${attachment.id})">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                ` : ''}
                            </div>
                        </div>
                    `;
                }).join('')}
                ${this.task.attachments.length > 3 ? `
                    <div class="text-center mt-2">
                        <button type="button" class="btn btn-sm btn-outline-primary" id="toggleAttachmentsBtn" 
                            onclick="toggleAttachments()">
                            <i class="bi bi-chevron-down me-1"></i>View All (${this.task.attachments.length})
                        </button>
                    </div>
                ` : ''}
            </div>
        `;
    }

    getFileIconClass(extension) {
        const iconMap = {
            'pdf': 'bi-file-earmark-pdf',
            'doc': 'bi-file-earmark-word',
            'docx': 'bi-file-earmark-word',
            'xls': 'bi-file-earmark-excel',
            'xlsx': 'bi-file-earmark-excel',
            'ppt': 'bi-file-earmark-ppt',
            'pptx': 'bi-file-earmark-ppt',
            'jpg': 'bi-file-earmark-image',
            'jpeg': 'bi-file-earmark-image',
            'png': 'bi-file-earmark-image',
            'gif': 'bi-file-earmark-image',
            'mp4': 'bi-file-earmark-play',
            'webm': 'bi-file-earmark-play',
            'zip': 'bi-file-earmark-zip',
            'rar': 'bi-file-earmark-zip',
            'txt': 'bi-file-earmark-text'
        };
        return iconMap[extension] || 'bi-file-earmark';
    }

    canDeleteAttachment(attachment) {
        // Check if user can delete (super admin or owner)
        // This should be checked from backend, but for now we'll allow it
        return true;
    }

    renderComments() {
        const commentsSection = document.getElementById('commentsSection');
        if (!commentsSection) return;

        if (!this.task.note_comments || this.task.note_comments.length === 0) {
            commentsSection.innerHTML = `
                <div class="text-center py-3">
                    <i class="bi bi-chat-dots text-muted" style="font-size: 2rem;"></i>
                    <p class="text-muted small mb-0 mt-2">No comments yet</p>
                </div>
            `;
            return;
        }

        commentsSection.innerHTML = `
            <div class="comments-list">
                ${this.task.note_comments.map((comment, commentIndex) => {
                    const isHidden = commentIndex >= 3 ? 'attachment-hidden' : '';
                    const createdAt = new Date(comment.created_at).toLocaleString();
                    const timeAgo = this.getTimeAgo(new Date(comment.created_at));
                    
                    return `
                        <div class="comment-item mb-2 p-2 border rounded ${isHidden}" data-comment-item="${commentIndex}">
                            <div class="d-flex justify-content-between align-items-start">
                                <div class="flex-grow-1">
                                    <div class="d-flex align-items-center mb-1">
                                        <i class="bi bi-person text-primary me-2"></i>
                                        <strong class="small">${comment.user?.name || 'Unknown'}</strong>
                                        <span class="text-muted small ms-2">${timeAgo}</span>
                                    </div>
                                    <div class="comment-text small">
                                        ${this.escapeHtml(comment.comment)}
                                    </div>
                                    ${comment.attachments && comment.attachments.length > 0 ? this.renderCommentAttachments(comment) : ''}
                                </div>
                                ${this.canDeleteComment(comment) ? `
                                    <button class="btn btn-sm btn-outline-danger" onclick="confirmDeleteComment(${comment.id})">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                ` : ''}
                            </div>
                        </div>
                    `;
                }).join('')}
                ${this.task.note_comments.length > 3 ? `
                    <div class="text-center mt-3">
                        <button type="button" class="btn btn-sm btn-outline-primary" id="toggleCommentsBtn"
                            onclick="toggleComments()">
                            <i class="bi bi-chevron-down me-1"></i>View All (${this.task.note_comments.length})
                        </button>
                    </div>
                ` : ''}
            </div>
        `;
    }

    renderCommentAttachments(comment) {
        return `
            <div class="mt-2">
                ${comment.attachments.map((attachment, attIndex) => {
                    const extension = attachment.file_name ? 
                        attachment.file_name.split('.').pop().toLowerCase() : '';
                    const iconClass = this.getFileIconClass(extension);
                    const fileSize = attachment.file_size ? 
                        (attachment.file_size / 1024).toFixed(1) + ' KB' : 'Unknown size';
                    const createdAt = new Date(attachment.created_at).toLocaleString();
                    const isHidden = attIndex >= 3 ? 'attachment-hidden' : '';
                    
                    return `
                        <div class="attachment-item d-flex justify-content-between align-items-center mb-1 p-2 border rounded ${isHidden}"
                             data-comment-attachment-item="${comment.id}-${attIndex}">
                            <div class="d-flex align-items-center">
                                <i class="bi ${iconClass} text-info me-2"></i>
                                <div>
                                    <div class="small fw-semibold">${attachment.file_name}</div>
                                    <div class="text-muted small">${fileSize} • ${createdAt}</div>
                                </div>
                            </div>
                            <div class="btn-group btn-group-sm">
                                <button type="button" class="btn btn-outline-primary" 
                                    onclick="previewAttachment(${attachment.id}, '${attachment.file_name}', '${extension}')">
                                    <i class="bi bi-eye"></i>
                                </button>
                                <a href="/attachments/${attachment.id}/download"
                                    class="btn btn-outline-primary">
                                    <i class="bi bi-download"></i>
                                </a>
                                ${this.canDeleteAttachment(attachment) ? `
                                    <button type="button" class="btn btn-outline-danger" 
                                        onclick="confirmDeleteAttachment(${attachment.id})">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                ` : ''}
                            </div>
                        </div>
                    `;
                }).join('')}
                ${comment.attachments.length > 3 ? `
                    <div class="text-center mt-2">
                        <button type="button" class="btn btn-sm btn-outline-primary" 
                            onclick="toggleCommentAttachments(${comment.id})">
                            <i class="bi bi-chevron-down me-1"></i>View All (${comment.attachments.length})
                        </button>
                    </div>
                ` : ''}
            </div>
        `;
    }

    canDeleteComment(comment) {
        // Check if user can delete comment
        return true; // Should be checked from backend
    }

    getTimeAgo(date) {
        const seconds = Math.floor((new Date() - date) / 1000);
        if (seconds < 60) return 'just now';
        const minutes = Math.floor(seconds / 60);
        if (minutes < 60) return `${minutes} minute${minutes > 1 ? 's' : ''} ago`;
        const hours = Math.floor(minutes / 60);
        if (hours < 24) return `${hours} hour${hours > 1 ? 's' : ''} ago`;
        const days = Math.floor(hours / 24);
        if (days < 7) return `${days} day${days > 1 ? 's' : ''} ago`;
        return date.toLocaleDateString();
    }

    escapeHtml(text) {
        const map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return text.replace(/[&<>"']/g, m => map[m]);
    }

    async updateStatus() {
        const statusSelect = document.getElementById('statusSelect');
        if (!statusSelect) return;

        const statusId = statusSelect.value;
        if (!statusId) return;

        await this.updateStatusTo(statusId);
    }

    async updateStatusTo(statusId) {
        try {
            const response = await fetch(`/tasks/${this.taskId}/details/status`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({ status_id: statusId })
            });

            const data = await response.json();
            if (data.success) {
                this.showAlert(data.message, 'success');
                await this.loadTask();
            } else {
                this.showAlert(data.message || 'Error updating status', 'danger');
            }
        } catch (error) {
            console.error('Error updating status:', error);
            this.showAlert('Error updating status', 'danger');
        }
    }

    showAdminReview() {
        const modalElement = document.getElementById('adminReviewModal');
        if (!modalElement) {
            console.error('Admin review modal not found');
            return;
        }
        
        const modal = new bootstrap.Modal(modalElement);
        const taskInfo = document.getElementById('adminReviewTaskInfo');
        const commentsTextarea = document.getElementById('adminReviewComments');
        
        if (taskInfo && this.task) {
            taskInfo.innerHTML = `
                <strong>Title:</strong> ${this.escapeHtml(this.task.title)}<br>
                <strong>Project:</strong> ${this.task.project ? this.escapeHtml(this.task.project.title) : 'No Project'}<br>
                <strong>Assigned To:</strong> ${this.task.assigned_to ? this.escapeHtml(this.task.assigned_to.name) : 'Unassigned'}
            `;
        }
        
        // Clear comments textarea
        if (commentsTextarea) {
            commentsTextarea.value = '';
            commentsTextarea.classList.remove('is-invalid');
        }
        
        // Setup event listeners if not already set
        this.setupAdminReviewListeners();
        
        // Reset modal when hidden
        modalElement.addEventListener('hidden.bs.modal', function cleanup() {
            if (commentsTextarea) {
                commentsTextarea.value = '';
                commentsTextarea.classList.remove('is-invalid');
            }
            modalElement.removeEventListener('hidden.bs.modal', cleanup);
        }, { once: true });
        
        modal.show();
    }

    setupAdminReviewListeners() {
        // Remove existing listeners to avoid duplicates
        const approveBtn = document.getElementById('approveTaskBtn');
        const revisitBtn = document.getElementById('revisitTaskBtn');
        
        if (approveBtn && !approveBtn.hasAttribute('data-listener-attached')) {
            approveBtn.setAttribute('data-listener-attached', 'true');
            approveBtn.addEventListener('click', () => this.approveTask());
        }
        
        if (revisitBtn && !revisitBtn.hasAttribute('data-listener-attached')) {
            revisitBtn.setAttribute('data-listener-attached', 'true');
            revisitBtn.addEventListener('click', () => this.revisitTask());
        }
    }

    async approveTask() {
        const commentsTextarea = document.getElementById('adminReviewComments');
        const comments = commentsTextarea ? commentsTextarea.value.trim() : '';
        
        // Show loading state
        const approveBtn = document.getElementById('approveTaskBtn');
        const revisitBtn = document.getElementById('revisitTaskBtn');
        const cancelBtn = document.querySelector('#adminReviewModal .btn-secondary');
        
        const approveOriginalText = approveBtn ? approveBtn.innerHTML : '';
        
        if (approveBtn) {
            approveBtn.disabled = true;
            approveBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Approving...';
        }
        if (revisitBtn) revisitBtn.disabled = true;
        if (cancelBtn) cancelBtn.disabled = true;

        try {
            const response = await fetch(`/tasks/${this.taskId}/details/approve`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({ comments: comments })
            });

            const data = await response.json();
            
            if (data.success) {
                this.showAlert(data.message, 'success');
                const modal = bootstrap.Modal.getInstance(document.getElementById('adminReviewModal'));
                if (modal) modal.hide();
                await this.loadTask();
            } else {
                this.showAlert(data.message || 'Error approving task', 'danger');
            }
        } catch (error) {
            console.error('Error approving task:', error);
            this.showAlert('Error approving task', 'danger');
        } finally {
            if (approveBtn) {
                approveBtn.disabled = false;
                approveBtn.innerHTML = approveOriginalText;
            }
            if (revisitBtn) revisitBtn.disabled = false;
            if (cancelBtn) cancelBtn.disabled = false;
        }
    }

    async revisitTask() {
        const commentsTextarea = document.getElementById('adminReviewComments');
        const comments = commentsTextarea ? commentsTextarea.value.trim() : '';
        
        // Show loading state
        const approveBtn = document.getElementById('approveTaskBtn');
        const revisitBtn = document.getElementById('revisitTaskBtn');
        const cancelBtn = document.querySelector('#adminReviewModal .btn-secondary');
        
        const revisitOriginalText = revisitBtn ? revisitBtn.innerHTML : '';
        
        if (revisitBtn) {
            revisitBtn.disabled = true;
            revisitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Marking for Revisit...';
        }
        if (approveBtn) approveBtn.disabled = true;
        if (cancelBtn) cancelBtn.disabled = true;

        try {
            const response = await fetch(`/tasks/${this.taskId}/details/revisit`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({ comments: comments })
            });

            const data = await response.json();
            
            if (data.success) {
                this.showAlert(data.message, 'success');
                const modal = bootstrap.Modal.getInstance(document.getElementById('adminReviewModal'));
                if (modal) modal.hide();
                await this.loadTask();
            } else {
                this.showAlert(data.message || 'Error marking task for revisit', 'danger');
            }
        } catch (error) {
            console.error('Error revisiting task:', error);
            this.showAlert('Error marking task for revisit', 'danger');
        } finally {
            if (revisitBtn) {
                revisitBtn.disabled = false;
                revisitBtn.innerHTML = revisitOriginalText;
            }
            if (approveBtn) approveBtn.disabled = false;
            if (cancelBtn) cancelBtn.disabled = false;
        }
    }

    async addNote() {
        const noteInput = document.getElementById('noteInput');
        if (!noteInput || !noteInput.value.trim()) return;

        try {
            const response = await fetch(`/tasks/${this.taskId}/details/note`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({ note: noteInput.value })
            });

            const data = await response.json();
            if (data.success) {
                this.showAlert(data.message, 'success');
                noteInput.value = '';
                this.loadTask();
            } else {
                this.showAlert(data.message || 'Error adding note', 'danger');
            }
        } catch (error) {
            console.error('Error adding note:', error);
            this.showAlert('Error adding note', 'danger');
        }
    }

    async addComment() {
        const commentInput = document.getElementById('commentInput');
        if (!commentInput || !commentInput.value.trim()) return;

        const formData = new FormData();
        formData.append('comment', commentInput.value);

        const attachmentInput = document.getElementById('commentAttachments');
        if (attachmentInput && attachmentInput.files.length > 0) {
            Array.from(attachmentInput.files).forEach(file => {
                formData.append('attachments[]', file);
            });
        }

        try {
            const response = await fetch(`/tasks/${this.taskId}/details/comment`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: formData
            });

            const data = await response.json();
            if (data.success) {
                this.showAlert(data.message, 'success');
                commentInput.value = '';
                if (attachmentInput) attachmentInput.value = '';
                this.loadTask();
            } else {
                this.showAlert(data.message || 'Error adding comment', 'danger');
            }
        } catch (error) {
            console.error('Error adding comment:', error);
            this.showAlert('Error adding comment', 'danger');
        }
    }

    async addAttachments() {
        const attachmentInput = document.getElementById('attachmentsInput');
        if (!attachmentInput || attachmentInput.files.length === 0) return;

        const formData = new FormData();
        Array.from(attachmentInput.files).forEach(file => {
            formData.append('attachments[]', file);
        });

        try {
            const response = await fetch(`/tasks/${this.taskId}/details/attachments`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: formData
            });

            const data = await response.json();
            if (data.success) {
                this.showAlert(data.message, 'success');
                attachmentInput.value = '';
                this.loadTask();
            } else {
                this.showAlert(data.message || 'Error uploading attachments', 'danger');
            }
        } catch (error) {
            console.error('Error uploading attachments:', error);
            this.showAlert('Error uploading attachments', 'danger');
        }
    }

    async deleteAttachment(attachmentId) {
        try {
            const response = await fetch(`/tasks/${this.taskId}/details/attachments/${attachmentId}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                }
            });

            const data = await response.json();
            if (data.success) {
                this.showAlert(data.message, 'success');
                await this.loadTask();
            } else {
                this.showAlert(data.message || 'Error deleting attachment', 'danger');
            }
        } catch (error) {
            console.error('Error deleting attachment:', error);
            this.showAlert('Error deleting attachment', 'danger');
        }
    }

    async deleteComment(commentId) {
        try {
            const response = await fetch(`/tasks/${this.taskId}/details/comments/${commentId}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                }
            });

            const data = await response.json();
            if (data.success) {
                this.showAlert(data.message, 'success');
                await this.loadTask();
            } else {
                this.showAlert(data.message || 'Error deleting comment', 'danger');
            }
        } catch (error) {
            console.error('Error deleting comment:', error);
            this.showAlert('Error deleting comment', 'danger');
        }
    }

    async stopRecurring() {
        if (!confirm('Are you sure you want to stop recurring task generation?')) return;

        try {
            const response = await fetch(`/tasks/${this.taskId}/details/stop-recurring`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                }
            });

            const data = await response.json();
            if (data.success) {
                this.showAlert(data.message, 'success');
                this.loadTask();
            } else {
                this.showAlert(data.message || 'Error stopping recurring task', 'danger');
            }
        } catch (error) {
            console.error('Error stopping recurring task:', error);
            this.showAlert('Error stopping recurring task', 'danger');
        }
    }

    showAlert(message, type = 'info') {
        const alertDiv = document.createElement('div');
        alertDiv.className = `alert alert-${type} alert-dismissible fade show`;
        alertDiv.style.position = 'fixed';
        alertDiv.style.top = '20px';
        alertDiv.style.right = '20px';
        alertDiv.style.zIndex = '9999';
        alertDiv.innerHTML = `
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
        document.body.appendChild(alertDiv);
        
        setTimeout(() => {
            alertDiv.remove();
        }, 5000);
    }
}

// Initialize
let taskDetailsManager;
document.addEventListener('DOMContentLoaded', () => {
    const container = document.getElementById('taskDetailsContainer');
    if (!container) {
        console.error('Task details container not found');
        return;
    }
    
    const taskId = container.getAttribute('data-task-id');
    if (!taskId) {
        console.error('Task ID not found in container');
        return;
    }
    
    console.log('Initializing TaskDetailsManager with taskId:', taskId);
    taskDetailsManager = new TaskDetailsManager(taskId);
});

