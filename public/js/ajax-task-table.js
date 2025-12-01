/**
 * AJAX Task Table Management
 * This file handles all AJAX interactions for the task table
 */

class TaskTableManager {
    constructor() {
        this.currentPage = 1;
        this.filters = {
            search: '',
            projectFilter: '',
            statusFilter: '',
            categoryFilter: '',
            assigneeFilter: ''
        };
        this.selectedTasks = [];
        this.dropdownData = null;
        this.userPermissions = null;
        this.selectedAssignees = [];
        this.tempSelectedAssignees = [];
        this.editSelectedAssignees = [];
        this.editTempSelectedAssignees = [];
        this.isEditMode = false;
        this.currentEditTaskId = null;
        this.isUpdating = false; // Flag to prevent multiple simultaneous updates
        this.init();
    }

    init() {
        this.loadDropdownData();
        this.loadTasks();
        this.setupEventListeners();
    }

    setupEventListeners() {
        // Search input
        const searchInput = document.getElementById('taskSearch');
        if (searchInput) {
            let searchTimeout;
            searchInput.addEventListener('input', (e) => {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(() => {
                    this.filters.search = e.target.value;
                    this.currentPage = 1;
                    this.loadTasks();
                }, 500);
            });
        }

        // Filter dropdowns
        ['projectFilter', 'statusFilter', 'categoryFilter', 'assigneeFilter'].forEach(filterId => {
            const filter = document.getElementById(filterId);
            if (filter) {
                filter.addEventListener('change', (e) => {
                    this.filters[filterId] = e.target.value;
                    this.currentPage = 1;
                    this.loadTasks();
                });
            }
        });

        // Nature of task change handler
        document.addEventListener('change', (e) => {
            if (e.target.id === 'modalTaskNature') {
                const recurrenceContainer = document.getElementById('recurrenceFrequencyContainer');
                const recurrenceSelect = document.getElementById('modalTaskRecurrenceFrequency');
                if (recurrenceContainer) {
                    recurrenceContainer.style.display = e.target.value === 'recurring' ? 'block' : 'none';
                    if (recurrenceSelect && e.target.value === 'recurring') {
                        recurrenceSelect.required = true;
                    } else if (recurrenceSelect) {
                        recurrenceSelect.required = false;
                    }
                }
            }
        });

        // Employee search
        const employeeSearch = document.getElementById('employeeSearch');
        if (employeeSearch) {
            employeeSearch.addEventListener('input', (e) => {
                this.filterEmployees(e.target.value);
            });
        }

        // Select all checkbox
        const selectAll = document.getElementById('selectAllTasks');
        if (selectAll) {
            selectAll.addEventListener('change', (e) => {
                this.toggleAllTasks(e.target.checked);
            });
        }
    }

    async loadDropdownData() {
        try {
            const response = await fetch('/tasks/dropdown-data');
            const data = await response.json();
            if (data.success) {
                this.dropdownData = data;
                this.userPermissions = data.user || null;
                this.populateDropdowns(data);
            }
        } catch (error) {
            console.error('Error loading dropdown data:', error);
            this.showAlert('Error loading dropdown data', 'danger');
        }
    }

    populateDropdowns(data) {
        // Populate project filter
        const projectFilter = document.getElementById('projectFilter');
        if (projectFilter && data.projects) {
            projectFilter.innerHTML = '<option value="">All Projects</option>' +
                data.projects.map(p => `<option value="${p.id}">${p.title}</option>`).join('');
        }

        // Populate status filter
        const statusFilter = document.getElementById('statusFilter');
        if (statusFilter && data.statuses) {
            statusFilter.innerHTML = '<option value="">All Status</option>' +
                data.statuses.map(s => `<option value="${s.id}">${s.name}</option>`).join('');
        }

        // Populate category filter
        const categoryFilter = document.getElementById('categoryFilter');
        if (categoryFilter && data.categories) {
            categoryFilter.innerHTML = '<option value="">All Categories</option>' +
                data.categories.map(c => `<option value="${c.id}">${c.name}</option>`).join('');
        }

        // Populate assignee filter
        const assigneeFilter = document.getElementById('assigneeFilter');
        if (assigneeFilter && data.users) {
            assigneeFilter.innerHTML = '<option value="">All Assignees</option>' +
                data.users.map(u => `<option value="${u.id}">${u.name}</option>`).join('');
        }

        // Populate bulk action dropdowns
        this.populateBulkActionDropdowns(data);
    }

    populateBulkActionDropdowns(data) {
        // Populate bulk status dropdown
        const statusDropdown = document.getElementById('bulkStatusDropdown');
        if (statusDropdown && data.statuses) {
            statusDropdown.innerHTML = data.statuses
                .filter(s => ['Complete', 'In Progress'].includes(s.name))
                .map(s =>
                    `<li><a class="dropdown-item" href="#" onclick="bulkUpdateStatus(${s.id}, '${s.name}')">${s.name}</a></li>`
                )
                .join('');
        }

        // Populate bulk priority dropdown
        const priorityDropdown = document.getElementById('bulkPriorityDropdown');
        if (priorityDropdown && data.priorities) {
            priorityDropdown.innerHTML = data.priorities
                .map(p =>
                    `<li><a class="dropdown-item" href="#" onclick="bulkUpdatePriority(${p.id}, '${p.name}')">${p.name}</a></li>`
                )
                .join('');
        }

        // Populate bulk assignee select (Select2)
        const assigneeSelect = document.getElementById('bulkAssigneeSelect');
        if (assigneeSelect && data.users) {
            // Store current value if Select2 is initialized
            let currentValue = [];
            if (typeof $ !== 'undefined' && $(assigneeSelect).hasClass('select2-hidden-accessible')) {
                currentValue = $(assigneeSelect).val() || [];
                $(assigneeSelect).select2('destroy');
            } else {
                const options = assigneeSelect.selectedOptions;
                currentValue = Array.from(options).map(opt => opt.value);
            }
            
            // Populate options
            assigneeSelect.innerHTML = data.users
                .map(u => `<option value="${u.id}">${u.name}</option>`)
                .join('');
            
            // Initialize Select2 with multiple selection
            this.initializeBulkAssigneeSelect2(assigneeSelect, currentValue);
        }
    }

    initializeBulkAssigneeSelect2(selectElement, currentValue = []) {
        // Wait for jQuery and Select2 to be available
        if (typeof $ === 'undefined' || typeof $.fn.select2 === 'undefined') {
            setTimeout(() => {
                this.initializeBulkAssigneeSelect2(selectElement, currentValue);
            }, 100);
            return;
        }
        
        // Initialize Select2 with multiple selection
        $(selectElement).select2({
            theme: 'bootstrap-5',
            placeholder: 'Select assignees...',
            allowClear: true,
            multiple: true,
            width: '100%',
            dropdownParent: $(selectElement).closest('.bulk-assignee-container').length > 0 
                ? $(selectElement).closest('.bulk-assignee-container') 
                : $('body')
        });
        
        // Restore value if it exists
        if (currentValue && currentValue.length > 0) {
            $(selectElement).val(currentValue).trigger('change');
        }
    }

    async loadTasks(page = 1) {
        try {
            const params = new URLSearchParams({
                page: page,
                per_page: 15,
                ...this.filters
            });

            const response = await fetch(`/tasks/data?${params}`);
            const data = await response.json();

            if (data.success) {
                this.currentPage = data.pagination.current_page;
                this.renderTasks(data.tasks);
                this.renderPagination(data.pagination);
            } else {
                this.showAlert('Error loading tasks', 'danger');
            }
        } catch (error) {
            console.error('Error loading tasks:', error);
            this.showAlert('Error loading tasks', 'danger');
        }
    }

    renderTasks(tasks) {
        const tbody = document.querySelector('#tasksTable tbody');
        if (!tbody) return;

        if (tasks.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="14" class="text-center py-4">
                        <div class="text-muted">
                            <i class="bi bi-inbox fs-1 d-block mb-3"></i>
                            <h5>No tasks found</h5>
                            <p class="mb-0">Start by creating your first task!</p>
                        </div>
                    </td>
                </tr>
            `;
            return;
        }

        tbody.innerHTML = tasks.map(task => this.renderTaskRow(task)).join('');
        this.attachTaskEventListeners();
        this.initializeTooltips();
    }

    initializeTooltips() {
        // Initialize Bootstrap tooltips for assignee badges
        const tooltipElements = document.querySelectorAll('[data-bs-toggle="tooltip"]');
        tooltipElements.forEach(element => {
            new bootstrap.Tooltip(element);
        });
    }

    renderAssignees(task) {
        // Check if task has assignees relationship (multiple assignees)
        if (task.assignees && Array.isArray(task.assignees) && task.assignees.length > 0) {
            const assignees = task.assignees;
            const firstAssignee = assignees[0];
            const additionalCount = assignees.length - 1;
            const firstInitial = firstAssignee.name ? firstAssignee.name.charAt(0).toUpperCase() : '?';
            const firstRole = firstAssignee.role && firstAssignee.role.name ? 
                firstAssignee.role.name.charAt(0).toUpperCase() + firstAssignee.role.name.slice(1) : 'Employee';
            
            let html = `
                <div class="assignees-compact d-flex align-items-center flex-wrap">
                    <div class="assignee-item d-flex align-items-center me-2">
                        <div class="avatar-sm bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-2"
                            style="width:32px;height:32px;font-size:12px;min-width:32px;">
                            ${firstInitial}
                        </div>
                        <div class="assignee-info">
                            <div class="fw-semibold" style="font-size:0.875rem;line-height:1.2;">
                                ${firstAssignee.name}
                            </div>
                            <small class="text-muted" style="font-size:0.75rem;">${firstRole}</small>
                        </div>
                    </div>
            `;
            
            if (additionalCount > 0) {
                const additionalNames = assignees.slice(1).map(a => a.name).join(', ');
                html += `
                    <div class="additional-assignees ms-1">
                        <span class="badge bg-primary assignee-more-badge d-inline-flex align-items-center"
                            data-bs-toggle="tooltip" data-bs-placement="top"
                            title="Additional Assignees: ${additionalNames}"
                            style="cursor:pointer;padding:0.35rem 0.5rem;">
                            <i class="bi bi-plus-circle me-1"></i>
                            <span>${additionalCount}</span>
                        </span>
                    </div>
                `;
            }
            
            html += '</div>';
            return html;
        } 
        // Fallback to assigned_to if no assignees relationship
        else if (task.assigned_to) {
            const initial = task.assigned_to.name ? task.assigned_to.name.charAt(0).toUpperCase() : '?';
            const role = task.assigned_to.role && task.assigned_to.role.name ? 
                task.assigned_to.role.name.charAt(0).toUpperCase() + task.assigned_to.role.name.slice(1) : 'Employee';
            return `
                <div class="d-flex align-items-center">
                    <div class="avatar-sm bg-gradient-primary text-white rounded-circle d-flex align-items-center justify-content-center me-2"
                        style="width:32px;height:32px;font-size:12px;min-width:32px;">
                        ${initial}
                    </div>
                    <div>
                        <div class="fw-semibold" style="font-size:0.875rem;">${task.assigned_to.name}</div>
                        <small class="text-muted" style="font-size:0.75rem;">${role}</small>
                    </div>
                </div>
            `;
        } 
        // Unassigned
        else {
            return `
                <div class="d-flex align-items-center">
                    <div class="avatar-sm bg-light border d-flex align-items-center justify-content-center me-2"
                        style="width:32px;height:32px;min-width:32px;">
                        <i class="bi bi-person text-muted"></i>
                    </div>
                    <span class="text-muted">Unassigned</span>
                </div>
            `;
        }
    }

    renderTaskRow(task) {
        const dueDate = task.due_date ? new Date(task.due_date).toLocaleDateString() : '-';
        const priorityHtml = this.renderPriorityDropdown(task);
        const categoryHtml = this.renderCategoryDropdown(task);
        const assigneesHtml = this.renderAssignees(task);
        const projectName = task.project ? task.project.title : '-';
        const isRecurring = ['daily', 'weekly', 'monthly', 'until_stop'].includes(task.nature_of_task);
        const recurringBadge = isRecurring ? 
            `<span class="badge bg-info ms-2"><i class="bi bi-arrow-repeat me-1"></i>${this.formatNatureOfTask(task.nature_of_task)}</span>` : '';
        
        // Check if user can edit/delete this task
        const canEditTask = this.canEditTask(task);
        const statusHtml = this.renderStatusColumn(task);
        const actionsHtml = this.renderActionsColumn(task, canEditTask);

        return `
            <tr class="task-row" data-task-id="${task.id}">
                <td>
                    <input type="checkbox" class="form-check-input task-checkbox" 
                           value="${task.id}" onchange="taskTableManager.toggleTaskSelection(this)">
                </td>
                <td class="d-none d-md-table-cell">${task.id}</td>
                <td>
                    <a href="/tasks/${task.id}/details" class="text-decoration-none fw-bold">
                        ${this.escapeHtml(task.title)}
                    </a>
                    ${recurringBadge}
                </td>
                <td class="d-none d-lg-table-cell">${task.description ? this.escapeHtml(task.description.substring(0, 50)) + '...' : '-'}</td>
                <td class="d-none d-lg-table-cell">${this.escapeHtml(projectName)}</td>
                <td class="d-none d-xl-table-cell">${assigneesHtml}</td>
                <td class="d-none d-lg-table-cell">${priorityHtml}</td>
                <td class="d-none d-lg-table-cell">${categoryHtml}</td>
                <td>${statusHtml}</td>
                <td class="d-none d-md-table-cell">${dueDate}</td>
                <td class="d-none d-xl-table-cell">
                    <div class="d-flex flex-column">
                        ${task.estimated_hours ? `<small class="text-muted">Est: ${task.estimated_hours}h</small>` : ''}
                        ${task.actual_hours ? `<small class="text-primary">Act: ${task.actual_hours}h</small>` : ''}
                        ${!task.estimated_hours && !task.actual_hours ? '<span class="text-muted">-</span>' : ''}
                    </div>
                </td>
                <td class="d-none d-lg-table-cell">${task.nature_of_task ? this.formatNatureOfTask(task.nature_of_task) : '-'}</td>
                <td>${actionsHtml}</td>
            </tr>
        `;
    }

    canEditTask(task) {
        if (!this.userPermissions) return false;
        return this.userPermissions.is_super_admin || 
               this.userPermissions.is_admin || 
               task.assigned_by_user_id === this.userPermissions.id;
    }

    renderStatusColumn(task) {
        // Check if user can see static status (SuperAdmin/Admin OR task creator OR task is approved)
        const canSeeStaticStatus = this.userPermissions && (
            this.userPermissions.is_super_admin ||
            this.userPermissions.is_admin ||
            task.assigned_by_user_id === this.userPermissions.id ||
            task.is_approved === true
        );

        if (canSeeStaticStatus) {
            // Show static badge with approved indicator
            const statusBadge = task.status ? 
                `<span class="badge bg-${task.status.color} me-2">${task.status.name}</span>` : 
                '<span class="badge bg-secondary me-2">No Status</span>';
            const approvedBadge = task.is_approved ? 
                '<span class="badge bg-success" title="Task Approved"><i class="bi bi-check-circle-fill"></i></span>' : '';
            return `
                <div class="d-flex align-items-center gap-1">
                    ${statusBadge}
                    ${approvedBadge}
                </div>
            `;
        } else {
            // Show dropdown with only "Complete" and "In Progress" options
            const statusBadge = task.status ? 
                `<span class="badge bg-${task.status.color}">${task.status.name}</span>` : 
                '<span class="badge bg-secondary">No Status</span>';
            return `
                <div class="dropdown">
                    <button class="btn btn-sm badge bg-${task.status?.color || 'secondary'} dropdown-toggle" 
                            type="button" data-bs-toggle="dropdown" aria-expanded="false" data-task-id="${task.id}">
                        ${statusBadge}
                    </button>
                    <ul class="dropdown-menu" id="statusDropdown-${task.id}">
                        ${this.renderStatusOptions(task.status?.id, task.id)}
                    </ul>
                </div>
            `;
        }
    }

    renderActionsColumn(task, canEditTask) {
        if (!canEditTask) {
            return '<span class="text-muted">No actions</span>';
        }

        const isRecurring = ['daily', 'weekly', 'monthly'].includes(task.nature_of_task);
        const showStopRecurring = isRecurring && task.is_recurring_active;
        const showAdminReview = this.userPermissions && 
                                task.status && 
                                task.status.name === 'Complete' && 
                                !task.is_approved && 
                                (this.userPermissions.is_super_admin || this.userPermissions.is_admin);

        let buttons = `
            <div class="btn-group" role="group">
                <button class="btn btn-sm btn-outline-primary" onclick="taskTableManager.editTask(${task.id})" title="Edit">
                    <i class="bi bi-pencil"></i>
                </button>
                <button class="btn btn-sm btn-outline-info" onclick="taskTableManager.openCloneTaskModal(${task.id})" title="Clone Task">
                    <i class="bi bi-files"></i>
                </button>
        `;

        if (showAdminReview) {
            buttons += `
                <button class="btn btn-sm btn-outline-success" onclick="window.location.href='/tasks/${task.id}/details'" title="Review Completed Task">
                    <i class="bi bi-check-circle"></i>
                </button>
            `;
        }

        if (showStopRecurring) {
            buttons += `
                <button class="btn btn-sm btn-outline-warning" onclick="taskTableManager.stopRecurring(${task.id})" title="Stop Recurrence">
                    <i class="bi bi-stop-circle"></i>
                </button>
            `;
        }

        buttons += `
                <button class="btn btn-sm btn-outline-danger" onclick="taskTableManager.deleteTask(${task.id})" title="Delete">
                    <i class="bi bi-trash"></i>
                </button>
            </div>
        `;

        return buttons;
    }

    formatNatureOfTask(nature) {
        if (!nature) return '';
        return nature.split('_').map(word => 
            word.charAt(0).toUpperCase() + word.slice(1)
        ).join(' ');
    }

    escapeHtml(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    renderStatusOptions(currentStatusId, taskId) {
        if (!this.dropdownData || !this.dropdownData.statuses) return '';
        return this.dropdownData.statuses
            .filter(s => ['Complete', 'In Progress'].includes(s.name))
            .map(s => `
                <li>
                    <a class="dropdown-item ${s.id === currentStatusId ? 'active' : ''}" 
                       href="#" onclick="taskTableManager.updateStatus(${s.id}, event, ${taskId})">
                        <span class="badge bg-${s.color} me-2">${s.name}</span>
                    </a>
                </li>
            `).join('');
    }

    renderPriorityDropdown(task) {
        const currentPriority = task.priority;
        const priorityButton = currentPriority ? 
            `<button class="btn btn-sm badge bg-${currentPriority.color ?? 'secondary'} dropdown-toggle" 
                     type="button" data-bs-toggle="dropdown" aria-expanded="false" data-task-id="${task.id}">
                ${currentPriority.name}
             </button>` :
            `<button class="btn btn-sm badge bg-secondary dropdown-toggle" 
                     type="button" data-bs-toggle="dropdown" aria-expanded="false" data-task-id="${task.id}">
                No Priority
             </button>`;
        
        return `
            <div class="dropdown">
                ${priorityButton}
                <ul class="dropdown-menu" id="priorityDropdown-${task.id}">
                    ${this.renderPriorityOptions(task.priority?.id)}
                </ul>
            </div>
        `;
    }

    renderPriorityOptions(currentPriorityId) {
        if (!this.dropdownData || !this.dropdownData.priorities) return '';
        return this.dropdownData.priorities
            .map(p => `
                <li>
                    <a class="dropdown-item ${p.id === currentPriorityId ? 'active' : ''}" 
                       href="#" onclick="taskTableManager.updatePriority(${p.id}, event)">
                        <span class="badge bg-${p.color} me-2">${p.name}</span>
                    </a>
                </li>
            `).join('');
    }

    renderCategoryDropdown(task) {
        const currentCategory = task.category;
        const categoryButton = currentCategory ? 
            `<button class="btn btn-sm badge bg-${currentCategory.color ?? 'secondary'} dropdown-toggle" 
                     type="button" data-bs-toggle="dropdown" aria-expanded="false" data-task-id="${task.id}">
                <i class="bi ${currentCategory.icon ?? 'bi-tag'} me-1"></i>
                ${currentCategory.name}
             </button>` :
            `<button class="btn btn-sm badge bg-secondary dropdown-toggle" 
                     type="button" data-bs-toggle="dropdown" aria-expanded="false" data-task-id="${task.id}">
                <i class="bi bi-tag me-1"></i>
                No Category
             </button>`;
        
        return `
            <div class="dropdown">
                ${categoryButton}
                <ul class="dropdown-menu" id="categoryDropdown-${task.id}">
                    ${this.renderCategoryOptions(task.category?.id)}
                </ul>
            </div>
        `;
    }

    renderCategoryOptions(currentCategoryId) {
        if (!this.dropdownData || !this.dropdownData.categories) return '';
        return this.dropdownData.categories
            .map(c => `
                <li>
                    <a class="dropdown-item ${c.id === currentCategoryId ? 'active' : ''}" 
                       href="#" onclick="taskTableManager.updateCategory(${c.id}, event)">
                        <span class="badge bg-${c.color} me-2">
                            <i class="bi ${c.icon} me-1"></i>
                            ${c.name}
                        </span>
                    </a>
                </li>
            `).join('');
    }

    attachTaskEventListeners() {
        // Status dropdown clicks
        document.querySelectorAll('[data-task-id]').forEach(btn => {
            const taskId = btn.getAttribute('data-task-id');
            const statusDropdown = document.getElementById(`statusDropdown-${taskId}`);
            if (statusDropdown) {
                statusDropdown.addEventListener('click', (e) => {
                    e.preventDefault();
                    const link = e.target.closest('a');
                    if (link) {
                        const statusId = link.getAttribute('onclick').match(/\d+/)[0];
                        this.updateStatus(statusId, e, taskId);
                    }
                });
            }
            
            // Priority dropdown clicks
            const priorityDropdown = document.getElementById(`priorityDropdown-${taskId}`);
            if (priorityDropdown) {
                priorityDropdown.addEventListener('click', (e) => {
                    e.preventDefault();
                    const link = e.target.closest('a');
                    if (link) {
                        const priorityId = link.getAttribute('onclick').match(/\d+/)[0];
                        this.updatePriority(priorityId, e, taskId);
                    }
                });
            }
            
            // Category dropdown clicks
            const categoryDropdown = document.getElementById(`categoryDropdown-${taskId}`);
            if (categoryDropdown) {
                categoryDropdown.addEventListener('click', (e) => {
                    e.preventDefault();
                    const link = e.target.closest('a');
                    if (link) {
                        const categoryId = link.getAttribute('onclick').match(/\d+/)[0];
                        this.updateCategory(categoryId, e, taskId);
                    }
                });
            }
        });
    }

    async updateStatus(statusId, event, taskId = null) {
        event.preventDefault();
        
        // Prevent multiple simultaneous updates
        if (this.isUpdating) {
            return;
        }
        
        if (!taskId) {
            taskId = event.target.closest('tr').getAttribute('data-task-id');
        }

        this.isUpdating = true;
        
        try {
            const response = await fetch(`/tasks/${taskId}/status`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({ status_id: statusId })
            });

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const data = await response.json();
            if (data.success) {
                // Use Swal if available, otherwise fallback to alert
                const SwalInstance = (typeof window !== 'undefined' && window.Swal) || (typeof Swal !== 'undefined' ? Swal : null);
                
                if (SwalInstance) {
                    SwalInstance.fire({
                        icon: 'success',
                        title: 'Success',
                        text: data.message || 'Status updated successfully.',
                        timer: 2000,
                        showConfirmButton: false,
                        allowOutsideClick: false,
                        allowEscapeKey: false
                    }).then(() => {
                        try {
                            location.reload();
                        } catch (reloadError) {
                            console.error('Error during reload:', reloadError);
                            // Silently handle reload error
                        }
                    }).catch((error) => {
                        console.error('Error in Swal promise:', error);
                        // If Swal fails, just reload anyway
                        try {
                            location.reload();
                        } catch (reloadError) {
                            console.error('Error during reload:', reloadError);
                        }
                    });
                } else {
                    // Fallback if Swal is not available
                    alert(data.message || 'Status updated successfully.');
                    setTimeout(() => {
                        location.reload();
                    }, 100);
                }
            } else {
                const SwalInstance = (typeof window !== 'undefined' && window.Swal) || (typeof Swal !== 'undefined' ? Swal : null);
                if (SwalInstance) {
                    SwalInstance.fire({
                        icon: 'error',
                        title: 'Error',
                        text: data.message || 'Failed to update status.'
                    });
                } else {
                    alert(data.message || 'Failed to update status.');
                }
            }
        } catch (error) {
            console.error('Error updating status:', error);
            const SwalInstance = (typeof window !== 'undefined' && window.Swal) || (typeof Swal !== 'undefined' ? Swal : null);
            if (SwalInstance) {
                SwalInstance.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Failed to update status. Please try again.'
                });
            } else {
                alert('Failed to update status. Please try again.');
            }
        } finally {
            this.isUpdating = false;
        }
    }

    async updatePriority(priorityId, event, taskId = null) {
        event.preventDefault();
        
        // Prevent multiple simultaneous updates
        if (this.isUpdating) {
            return;
        }
        
        if (!taskId) {
            taskId = event.target.closest('tr').getAttribute('data-task-id');
        }

        this.isUpdating = true;
        
        try {
            const response = await fetch(`/tasks/${taskId}/priority`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({ priority_id: priorityId })
            });

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const data = await response.json();
            if (data.success) {
                // Use Swal if available, otherwise fallback to alert
                const SwalInstance = (typeof window !== 'undefined' && window.Swal) || (typeof Swal !== 'undefined' ? Swal : null);
                
                if (SwalInstance) {
                    SwalInstance.fire({
                        icon: 'success',
                        title: 'Success',
                        text: data.message || 'Priority updated successfully.',
                        timer: 2000,
                        showConfirmButton: false,
                        allowOutsideClick: false,
                        allowEscapeKey: false
                    }).then(() => {
                        try {
                            location.reload();
                        } catch (reloadError) {
                            console.error('Error during reload:', reloadError);
                            // Silently handle reload error
                        }
                    }).catch((error) => {
                        console.error('Error in Swal promise:', error);
                        // If Swal fails, just reload anyway
                        try {
                            location.reload();
                        } catch (reloadError) {
                            console.error('Error during reload:', reloadError);
                        }
                    });
                } else {
                    // Fallback if Swal is not available
                    alert(data.message || 'Priority updated successfully.');
                    setTimeout(() => {
                        location.reload();
                    }, 100);
                }
            } else {
                const SwalInstance = (typeof window !== 'undefined' && window.Swal) || (typeof Swal !== 'undefined' ? Swal : null);
                if (SwalInstance) {
                    SwalInstance.fire({
                        icon: 'error',
                        title: 'Error',
                        text: data.message || 'Failed to update priority.'
                    });
                } else {
                    alert(data.message || 'Failed to update priority.');
                }
            }
        } catch (error) {
            console.error('Error updating priority:', error);
            const SwalInstance = (typeof window !== 'undefined' && window.Swal) || (typeof Swal !== 'undefined' ? Swal : null);
            if (SwalInstance) {
                SwalInstance.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Failed to update priority. Please try again.'
                });
            } else {
                alert('Failed to update priority. Please try again.');
            }
        } finally {
            this.isUpdating = false;
        }
    }

    async updateCategory(categoryId, event, taskId = null) {
        event.preventDefault();
        
        // Prevent multiple simultaneous updates
        if (this.isUpdating) {
            return;
        }
        
        if (!taskId) {
            taskId = event.target.closest('tr').getAttribute('data-task-id');
        }

        this.isUpdating = true;
        
        try {
            const response = await fetch(`/tasks/${taskId}/category`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({ category_id: categoryId })
            });

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const data = await response.json();
            if (data.success) {
                // Use Swal if available, otherwise fallback to alert
                const SwalInstance = (typeof window !== 'undefined' && window.Swal) || (typeof Swal !== 'undefined' ? Swal : null);
                
                if (SwalInstance) {
                    SwalInstance.fire({
                        icon: 'success',
                        title: 'Success',
                        text: data.message || 'Category updated successfully.',
                        timer: 2000,
                        showConfirmButton: false,
                        allowOutsideClick: false,
                        allowEscapeKey: false
                    }).then(() => {
                        try {
                            location.reload();
                        } catch (reloadError) {
                            console.error('Error during reload:', reloadError);
                            // Silently handle reload error
                        }
                    }).catch((error) => {
                        console.error('Error in Swal promise:', error);
                        // If Swal fails, just reload anyway
                        try {
                            location.reload();
                        } catch (reloadError) {
                            console.error('Error during reload:', reloadError);
                        }
                    });
                } else {
                    // Fallback if Swal is not available
                    alert(data.message || 'Category updated successfully.');
                    setTimeout(() => {
                        location.reload();
                    }, 100);
                }
            } else {
                const SwalInstance = (typeof window !== 'undefined' && window.Swal) || (typeof Swal !== 'undefined' ? Swal : null);
                if (SwalInstance) {
                    SwalInstance.fire({
                        icon: 'error',
                        title: 'Error',
                        text: data.message || 'Failed to update category.'
                    });
                } else {
                    alert(data.message || 'Failed to update category.');
                }
            }
        } catch (error) {
            console.error('Error updating category:', error);
            const SwalInstance = (typeof window !== 'undefined' && window.Swal) || (typeof Swal !== 'undefined' ? Swal : null);
            if (SwalInstance) {
                SwalInstance.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Failed to update category. Please try again.'
                });
            } else {
                alert('Failed to update category. Please try again.');
            }
        } finally {
            this.isUpdating = false;
        }
    }

    async editTask(taskId) {
        try {
            // Ensure dropdown data is loaded first
            if (!this.dropdownData) {
                await this.loadDropdownData();
            }
            
            const response = await fetch(`/tasks/${taskId}`);
            const data = await response.json();
            if (data.success) {
                this.showEditModal(data.task);
            } else {
                this.showAlert(data.message || 'Error loading task', 'danger');
            }
        } catch (error) {
            console.error('Error loading task:', error);
            this.showAlert('Error loading task', 'danger');
        }
    }

    async showEditModal(task) {
        this.isEditMode = true;
        this.currentEditTaskId = task.id;
        
        // Clear validation errors first
        this.clearEditModalValidationErrors();
        
        // Ensure dropdown data is loaded
        if (!this.dropdownData) {
            await this.loadDropdownData();
        }
        
        // Store values before populating dropdowns - handle both direct IDs and relationship objects
        const priorityId = task.priority_id !== null && task.priority_id !== undefined 
            ? task.priority_id 
            : (task.priority && task.priority.id ? task.priority.id : null);
        const categoryId = task.category_id !== null && task.category_id !== undefined 
            ? task.category_id 
            : (task.category && task.category.id ? task.category.id : null);
        const projectId = task.project_id !== null && task.project_id !== undefined 
            ? task.project_id 
            : (task.project && task.project.id ? task.project.id : null);
        
        // Set basic form field values first
        document.getElementById('editModalTaskId').value = task.id;
        document.getElementById('editModalTaskTitle').value = task.title || '';
        document.getElementById('editModalTaskDescription').value = task.description || '';
        document.getElementById('editModalTaskDueDate').value = task.due_date ? task.due_date.split(' ')[0] : '';
        document.getElementById('editModalTaskEstimatedHours').value = task.estimated_hours || '';
        
            // Set nature of task
            const natureField = document.getElementById('editModalTaskNature');
            const recurrenceField = document.getElementById('editModalTaskRecurrenceFrequency');
            const recurrenceContainer = document.getElementById('editRecurrenceFrequencyContainer');
            
            if (task.is_recurring) {
                natureField.value = 'recurring';
                if (recurrenceField) {
                    recurrenceField.value = task.nature_of_task || 'daily';
                    recurrenceField.setAttribute('required', 'required');
                }
                if (recurrenceContainer) {
                    recurrenceContainer.style.display = 'block';
                }
            } else {
                natureField.value = 'one_time';
                if (recurrenceField) {
                    recurrenceField.removeAttribute('required');
                }
                if (recurrenceContainer) {
                    recurrenceContainer.style.display = 'none';
                }
            }
        
        // Set reminder time
        if (task.reminder_time) {
            const reminderDate = new Date(task.reminder_time);
            const formattedDate = reminderDate.toISOString().slice(0, 16);
            document.getElementById('editModalTaskReminderTime').value = formattedDate;
        } else {
            document.getElementById('editModalTaskReminderTime').value = '';
        }
        
        // Load assignees
        this.editSelectedAssignees = task.assignees ? task.assignees.map(a => ({
            id: a.id,
            name: a.name,
            email: a.email || '',
            role: a.role ? a.role.name : 'Employee'
        })) : [];
        this.editTempSelectedAssignees = [...this.editSelectedAssignees];
        this.updateEditSelectedAssigneesDisplay();
        
        // Show modal first
        const editModalElement = document.getElementById('editTaskModal');
        const editModal = new bootstrap.Modal(editModalElement);
        editModal.show();
        
        // Wait for modal to be fully shown, then populate dropdowns and set values
        editModalElement.addEventListener('shown.bs.modal', function populateAndSetValues() {
            // Remove listener to avoid multiple calls
            editModalElement.removeEventListener('shown.bs.modal', populateAndSetValues);
            
            // Populate dropdowns
            taskTableManager.populateEditModalDropdowns();
            
            // Set dropdown values after they're populated
            setTimeout(() => {
                // Convert IDs to strings for comparison (HTML select values are strings)
                const projectIdStr = projectId ? String(projectId) : '';
                const priorityIdStr = priorityId ? String(priorityId) : '';
                const categoryIdStr = categoryId ? String(categoryId) : '';
                
                const projectSelect = document.getElementById('editModalTaskProjectId');
                if (projectSelect && projectIdStr) {
                    projectSelect.value = projectIdStr;
                }
                
                const prioritySelect = document.getElementById('editModalTaskPriority');
                if (prioritySelect && priorityIdStr) {
                    prioritySelect.value = priorityIdStr;
                    // Double-check the value was set
                    if (prioritySelect.value !== priorityIdStr) {
                        setTimeout(() => {
                            prioritySelect.value = priorityIdStr;
                        }, 100);
                    }
                }
                
                const categorySelect = document.getElementById('editModalTaskCategory');
                if (categorySelect && categoryIdStr) {
                    categorySelect.value = categoryIdStr;
                    // Double-check the value was set
                    if (categorySelect.value !== categoryIdStr) {
                        setTimeout(() => {
                            categorySelect.value = categoryIdStr;
                        }, 100);
                    }
                }
            }, 200);
        }, { once: true });
    }

    populateEditModalDropdowns() {
        if (!this.dropdownData) {
            // If dropdown data not loaded, load it first
            return this.loadDropdownData().then(() => {
                this.populateEditModalDropdowns();
            });
        }
        
        // Populate projects
        const projectSelect = document.getElementById('editModalTaskProjectId');
        if (projectSelect && this.dropdownData.projects) {
            const currentValue = projectSelect.value;
            projectSelect.innerHTML = '<option value="">Select a project (Optional)</option>' +
                this.dropdownData.projects.map(p => 
                    `<option value="${p.id}">${p.title}</option>`
                ).join('');
            // Restore value if it exists
            if (currentValue) {
                projectSelect.value = currentValue;
            }
        }
        
        // Populate priorities
        const prioritySelect = document.getElementById('editModalTaskPriority');
        if (prioritySelect && this.dropdownData.priorities) {
            const currentValue = prioritySelect.value;
            prioritySelect.innerHTML = '<option value="">Select priority</option>' +
                this.dropdownData.priorities.map(p => 
                    `<option value="${p.id}">${p.name}</option>`
                ).join('');
            // Restore value if it exists
            if (currentValue) {
                prioritySelect.value = currentValue;
            }
        }
        
        // Populate categories
        const categorySelect = document.getElementById('editModalTaskCategory');
        if (categorySelect && this.dropdownData.categories) {
            const currentValue = categorySelect.value;
            categorySelect.innerHTML = '<option value="">Select category</option>' +
                this.dropdownData.categories.map(c => 
                    `<option value="${c.id}">${c.name}</option>`
                ).join('');
            // Restore value if it exists
            if (currentValue) {
                categorySelect.value = currentValue;
            }
        }
    }

    clearEditModalValidationErrors() {
        // Remove was-validated class
        const form = document.getElementById('taskEditModalForm');
        if (form) {
            form.classList.remove('was-validated');
        }
        
        // Clear all invalid feedback
        document.querySelectorAll('#taskEditModalForm .is-invalid').forEach(el => {
            el.classList.remove('is-invalid');
        });
        
        // Clear invalid feedback text
        document.querySelectorAll('#taskEditModalForm .invalid-feedback').forEach(el => {
            el.textContent = '';
        });
        
        // Clear alert container
        const alertContainer = document.getElementById('editModalAlertContainer');
        if (alertContainer) {
            alertContainer.innerHTML = '';
        }
    }

    openEmployeeModalForEdit() {
        this.isEditMode = true;
        this.tempSelectedAssignees = [...this.editTempSelectedAssignees];
        this.openEmployeeModal();
    }

    updateEditSelectedAssigneesDisplay() {
        const container = document.getElementById('editSelectedAssigneesContainer');
        const badgesContainer = document.getElementById('editSelectedAssignees');
        const button = document.getElementById('editSelectAssigneesBtn');
        
        if (this.editSelectedAssignees.length > 0) {
            container.style.display = 'block';
            badgesContainer.innerHTML = this.editSelectedAssignees.map(emp => `
                <span class="badge bg-primary me-1 mb-1">
                    ${emp.name}
                    <button type="button" class="btn-close btn-close-white btn-sm ms-1" 
                            onclick="taskTableManager.removeEditAssignee(${emp.id})"
                            style="font-size: 0.7em;"></button>
                </span>
            `).join('');
            button.innerHTML = `<i class="bi bi-people me-1"></i>Change Assignees`;
        } else {
            container.style.display = 'none';
            button.innerHTML = `<i class="bi bi-people me-1"></i>Select Assignees`;
        }
    }

    removeEditAssignee(employeeId) {
        this.editSelectedAssignees = this.editSelectedAssignees.filter(e => e.id !== employeeId);
        this.editTempSelectedAssignees = this.editSelectedAssignees.filter(e => e.id !== employeeId);
        this.updateEditSelectedAssigneesDisplay();
    }

    async updateTaskFromModal() {
        const form = document.getElementById('taskEditModalForm');
        
        // Clear previous validation errors
        this.clearEditModalValidationErrors();
        
        // Get all field references
        const titleField = document.getElementById('editModalTaskTitle');
        const priorityField = document.getElementById('editModalTaskPriority');
        const categoryField = document.getElementById('editModalTaskCategory');
        const natureField = document.getElementById('editModalTaskNature');
        const recurrenceFrequencyField = document.getElementById('editModalTaskRecurrenceFrequency');
        
        // Validate assignees manually (not a form field)
        if (!this.editSelectedAssignees || this.editSelectedAssignees.length === 0) {
            this.showEditModalAlert('Please select at least one assignee', 'danger');
            const assigneeLabel = document.querySelector('label[for="editModalTaskAssigneeId"]');
            if (assigneeLabel) {
                const assigneeContainer = assigneeLabel.parentElement;
                if (assigneeContainer) {
                    const feedback = assigneeContainer.querySelector('.invalid-feedback');
                    if (feedback) {
                        feedback.textContent = 'Please select at least one assignee';
                        feedback.style.display = 'block';
                    }
                    const selectBtn = document.getElementById('editSelectAssigneesBtn');
                    if (selectBtn) {
                        selectBtn.classList.add('border-danger');
                    }
                }
            }
            return;
        }
        
        // Handle recurrence frequency requirement dynamically
        const nature = natureField ? natureField.value : '';
        
        if (nature === 'recurring') {
            // Make recurrence frequency required when recurring is selected
            if (recurrenceFrequencyField) {
                if (!recurrenceFrequencyField.value || recurrenceFrequencyField.value === '') {
                    recurrenceFrequencyField.classList.add('is-invalid');
                    const feedback = recurrenceFrequencyField.parentElement.querySelector('.invalid-feedback');
                    if (feedback) {
                        feedback.textContent = 'Recurrence frequency is required for recurring tasks';
                    }
                    form.classList.add('was-validated');
                    recurrenceFrequencyField.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    recurrenceFrequencyField.focus();
                    return;
                }
            }
        } else {
            // Remove required attribute if not recurring and clear any validation errors
            if (recurrenceFrequencyField) {
                recurrenceFrequencyField.removeAttribute('required');
                recurrenceFrequencyField.classList.remove('is-invalid');
                const feedback = recurrenceFrequencyField.parentElement.querySelector('.invalid-feedback');
                if (feedback) {
                    feedback.textContent = '';
                }
            }
        }
        
        // Validate visible required fields manually
        let hasErrors = false;
        let firstInvalidField = null;
        
        // Validate title
        if (!titleField || !titleField.value || titleField.value.trim() === '') {
            if (titleField) {
                titleField.classList.add('is-invalid');
                const feedback = titleField.parentElement.querySelector('.invalid-feedback');
                if (feedback) feedback.textContent = 'Task title is required';
            }
            hasErrors = true;
            if (!firstInvalidField) firstInvalidField = titleField;
        } else if (titleField) {
            titleField.classList.remove('is-invalid');
        }
        
        // Validate priority - check if it's a valid selected option (not the placeholder)
        if (!priorityField || !priorityField.value || priorityField.value === '' || priorityField.selectedIndex <= 0) {
            if (priorityField) {
                priorityField.classList.add('is-invalid');
                const feedback = priorityField.parentElement.querySelector('.invalid-feedback');
                if (feedback) feedback.textContent = 'Priority is required';
            }
            hasErrors = true;
            if (!firstInvalidField) firstInvalidField = priorityField;
        } else if (priorityField) {
            priorityField.classList.remove('is-invalid');
        }
        
        // Validate category - check if it's a valid selected option (not the placeholder)
        if (!categoryField || !categoryField.value || categoryField.value === '' || categoryField.selectedIndex <= 0) {
            if (categoryField) {
                categoryField.classList.add('is-invalid');
                const feedback = categoryField.parentElement.querySelector('.invalid-feedback');
                if (feedback) feedback.textContent = 'Category is required';
            }
            hasErrors = true;
            if (!firstInvalidField) firstInvalidField = categoryField;
        } else if (categoryField) {
            categoryField.classList.remove('is-invalid');
        }
        
        // Validate nature
        if (!natureField || !natureField.value || natureField.value === '') {
            if (natureField) {
                natureField.classList.add('is-invalid');
                const feedback = natureField.parentElement.querySelector('.invalid-feedback');
                if (feedback) feedback.textContent = 'Nature of task is required';
            }
            hasErrors = true;
            if (!firstInvalidField) firstInvalidField = natureField;
        } else if (natureField) {
            natureField.classList.remove('is-invalid');
        }
        
        if (hasErrors) {
            form.classList.add('was-validated');
            if (firstInvalidField) {
                firstInvalidField.scrollIntoView({ behavior: 'smooth', block: 'center' });
                firstInvalidField.focus();
            }
            this.showEditModalAlert('Please fill in all required fields', 'danger');
            return;
        }

        // Get values directly from form fields to ensure we have them
        const taskId = document.getElementById('editModalTaskId')?.value;
        const title = document.getElementById('editModalTaskTitle')?.value;
        const priorityId = document.getElementById('editModalTaskPriority')?.value;
        const categoryId = document.getElementById('editModalTaskCategory')?.value;
        const projectId = document.getElementById('editModalTaskProjectId')?.value;
        const description = document.getElementById('editModalTaskDescription')?.value || '';
        const dueDate = document.getElementById('editModalTaskDueDate')?.value || '';
        const estimatedHours = document.getElementById('editModalTaskEstimatedHours')?.value || '';
        const reminderTime = document.getElementById('editModalTaskReminderTime')?.value || '';
        // Note: 'nature' variable is already defined above from natureField.value
        
        // Verify all required fields have values
        if (!taskId || !title || !priorityId || !categoryId || !nature) {
            console.error('Missing required fields:', { taskId, title, priorityId, categoryId, nature });
            this.showEditModalAlert('Please fill in all required fields', 'danger');
            return;
        }
        
        // Create FormData and populate with values
        const formData = new FormData();
        formData.append('task_id', taskId);
        formData.append('title', title);
        formData.append('priority_id', priorityId);
        formData.append('category_id', categoryId);
        formData.append('nature', nature);
        
        // Optional fields
        if (description) formData.append('description', description);
        if (projectId && projectId !== '') formData.append('project_id', projectId);
        if (dueDate) formData.append('due_date', dueDate);
        if (estimatedHours) formData.append('estimated_hours', estimatedHours);
        if (reminderTime) formData.append('reminder_time', reminderTime);
        
        // Add assignee IDs as array (already validated above)
        const assigneeIds = this.editSelectedAssignees.map(e => e.id);
        if (!assigneeIds || assigneeIds.length === 0) {
            console.error('No assignees selected');
            this.showEditModalAlert('Please select at least one assignee', 'danger');
            return;
        }
        
        // Clear any existing assignee_ids first
        formData.delete('assignee_ids');
        formData.delete('assignee_ids[]');
        // Add each assignee ID
        assigneeIds.forEach(userId => {
            formData.append('assignee_ids[]', userId);
        });
        
        // Handle recurrence frequency - only add if nature is recurring (using nature variable from above)
        if (nature === 'recurring') {
            const frequency = document.getElementById('editModalTaskRecurrenceFrequency')?.value;
            if (!frequency) {
                this.showEditModalAlert('Please select recurrence frequency', 'danger');
                if (recurrenceFrequencyField) {
                    recurrenceFrequencyField.classList.add('is-invalid');
                    recurrenceFrequencyField.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    recurrenceFrequencyField.focus();
                }
                return;
            }
            formData.append('recurrence_frequency', frequency);
        }
        
        // Handle file attachments if any
        const attachmentsInput = document.getElementById('editModalTaskAttachments');
        if (attachmentsInput && attachmentsInput.files && attachmentsInput.files.length > 0) {
            for (let i = 0; i < attachmentsInput.files.length; i++) {
                formData.append('attachments[]', attachmentsInput.files[i]);
            }
        }
        
        // Add _method for Laravel form method spoofing (required for PUT with FormData)
        formData.append('_method', 'PUT');
        
        // Debug: Log form data before sending
        console.log('Form data being sent:');
        for (let pair of formData.entries()) {
            console.log(pair[0] + ': ' + pair[1]);
        }
        console.log('Assignee IDs:', assigneeIds);

        // Show loading state
        this.showUpdateTaskLoader(true);

        try {
            // Use POST method with _method=PUT for Laravel form spoofing
            // This ensures FormData is properly parsed by Laravel
            const response = await fetch(`/tasks/${taskId}`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    // Don't set Content-Type header - browser will set it automatically with boundary for FormData
                },
                body: formData
            });

            const data = await response.json();
            
            if (data.success) {
                this.showEditModalAlert(data.message, 'success');
                setTimeout(() => {
                    const editModal = bootstrap.Modal.getInstance(document.getElementById('editTaskModal'));
                    if (editModal) editModal.hide();
                    this.resetEditModal();
                    this.loadTasks(this.currentPage);
                    this.showUpdateTaskLoader(false);
                }, 1500);
            } else {
                // Hide loading state on error
                this.showUpdateTaskLoader(false);
                
                // Log errors for debugging
                console.error('Update task errors:', data.errors);
                console.error('Response data:', data);
                
                let errorMessage = data.message || 'Error updating task';
                if (data.errors) {
                    // Show first error message
                    const firstError = Object.values(data.errors)[0];
                    if (firstError && firstError[0]) {
                        errorMessage = firstError[0];
                    }
                    this.displayEditFormErrors(data.errors);
                }
                this.showEditModalAlert(errorMessage, 'danger');
            }
        } catch (error) {
            console.error('Error updating task:', error);
            this.showUpdateTaskLoader(false);
            this.showEditModalAlert('Error updating task', 'danger');
        }
    }

    showEditModalAlert(message, type) {
        const container = document.getElementById('editModalAlertContainer');
        container.innerHTML = `
            <div class="alert alert-${type} alert-dismissible fade show m-3" role="alert">
                <i class="bi bi-${type === 'success' ? 'check-circle' : 'exclamation-triangle'} me-2"></i>${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        `;
    }

    displayEditFormErrors(errors) {
        Object.keys(errors).forEach(field => {
            const input = document.querySelector(`#taskEditModalForm [name="${field}"]`);
            if (input) {
                input.classList.add('is-invalid');
                const feedback = input.parentElement.querySelector('.invalid-feedback');
                if (feedback) {
                    feedback.textContent = errors[field][0];
                }
            }
        });
    }

    resetEditModal() {
        const form = document.getElementById('taskEditModalForm');
        if (form) {
            form.reset();
            form.classList.remove('was-validated');
        }
        
        this.editSelectedAssignees = [];
        this.editTempSelectedAssignees = [];
        this.isEditMode = false;
        this.currentEditTaskId = null;
        this.updateEditSelectedAssigneesDisplay();
        
        // Clear validation errors
        this.clearEditModalValidationErrors();
        
        // Reset loader state
        this.showUpdateTaskLoader(false);
    }

    openCloneTaskModal(taskId) {
        const modalElement = document.getElementById('cloneTaskModal');
        if (!modalElement) {
            console.error('Clone task modal element not found');
            return;
        }
        
        // Set task ID
        const taskIdInput = document.getElementById('cloneModalTaskId');
        if (taskIdInput) {
            taskIdInput.value = taskId;
        }
        
        // Reset form
        const form = document.getElementById('cloneTaskModalForm');
        if (form) {
            form.reset();
            // Set min date to tomorrow
            const dueDateInput = document.getElementById('cloneModalDueDate');
            if (dueDateInput) {
                const tomorrow = new Date();
                tomorrow.setDate(tomorrow.getDate() + 1);
                dueDateInput.min = tomorrow.toISOString().split('T')[0];
            }
        }
        
        // Clear alerts
        this.clearCloneModalAlerts();
        
        // Reset loader
        this.showCloneTaskLoader(false);
        
        // Show modal
        const modal = new bootstrap.Modal(modalElement);
        modal.show();
    }

    async submitCloneTask() {
        const form = document.getElementById('cloneTaskModalForm');
        if (!form) return;
        
        const taskId = document.getElementById('cloneModalTaskId')?.value;
        const dueDate = document.getElementById('cloneModalDueDate')?.value;
        
        if (!taskId || !dueDate) {
            this.showCloneModalAlert('Please select a due date', 'danger');
            const dueDateInput = document.getElementById('cloneModalDueDate');
            if (dueDateInput) {
                dueDateInput.classList.add('is-invalid');
                const feedback = dueDateInput.parentElement.querySelector('.invalid-feedback');
                if (feedback) {
                    feedback.textContent = 'Due date is required';
                }
            }
            return;
        }
        
        // Clear validation errors
        const dueDateInput = document.getElementById('cloneModalDueDate');
        if (dueDateInput) {
            dueDateInput.classList.remove('is-invalid');
        }
        
        // Show loading state
        this.showCloneTaskLoader(true);
        
        try {
            const response = await fetch(`/tasks/${taskId}/clone`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({ due_date: dueDate })
            });

            const data = await response.json();
            if (data.success) {
                this.showCloneModalAlert(data.message, 'success');
                setTimeout(() => {
                    const cloneModal = bootstrap.Modal.getInstance(document.getElementById('cloneTaskModal'));
                    if (cloneModal) cloneModal.hide();
                    this.showCloneTaskLoader(false);
                    this.loadTasks(this.currentPage);
                }, 1500);
            } else {
                this.showCloneTaskLoader(false);
                this.showCloneModalAlert(data.message || 'Error cloning task', 'danger');
                if (data.errors && data.errors.due_date) {
                    if (dueDateInput) {
                        dueDateInput.classList.add('is-invalid');
                        const feedback = dueDateInput.parentElement.querySelector('.invalid-feedback');
                        if (feedback) {
                            feedback.textContent = data.errors.due_date[0];
                        }
                    }
                }
            }
        } catch (error) {
            console.error('Error cloning task:', error);
            this.showCloneTaskLoader(false);
            this.showCloneModalAlert('Error cloning task', 'danger');
        }
    }

    showCloneTaskLoader(show) {
        const submitBtn = document.getElementById('cloneTaskSubmitBtn');
        const cancelBtn = document.getElementById('cloneTaskCancelBtn');
        const spinner = document.getElementById('cloneTaskSpinner');
        const icon = document.getElementById('cloneTaskIcon');
        const text = document.getElementById('cloneTaskText');

        if (show) {
            if (submitBtn) submitBtn.disabled = true;
            if (cancelBtn) cancelBtn.disabled = true;
            if (spinner) spinner.classList.remove('d-none');
            if (icon) icon.classList.add('d-none');
            if (text) text.textContent = 'Cloning...';
        } else {
            if (submitBtn) submitBtn.disabled = false;
            if (cancelBtn) cancelBtn.disabled = false;
            if (spinner) spinner.classList.add('d-none');
            if (icon) icon.classList.remove('d-none');
            if (text) text.textContent = 'Clone Task';
        }
    }

    showCloneModalAlert(message, type) {
        const container = document.getElementById('cloneModalAlertContainer');
        if (container) {
            container.innerHTML = `
                <div class="alert alert-${type} alert-dismissible fade show m-3" role="alert">
                    <i class="bi bi-${type === 'success' ? 'check-circle' : 'exclamation-triangle'} me-2"></i>${message}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            `;
        }
    }

    clearCloneModalAlerts() {
        const container = document.getElementById('cloneModalAlertContainer');
        if (container) {
            container.innerHTML = '';
        }
    }

    async stopRecurring(taskId) {
        if (!confirm('Are you sure you want to stop recurring task generation?')) return;

        try {
            const response = await fetch(`/tasks/${taskId}/stop-recurring`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                }
            });

            const data = await response.json();
            if (data.success) {
                this.showAlert(data.message, 'success');
                this.loadTasks(this.currentPage);
            } else {
                this.showAlert(data.message || 'Error stopping recurring task', 'danger');
            }
        } catch (error) {
            console.error('Error stopping recurring task:', error);
            this.showAlert('Error stopping recurring task', 'danger');
        }
    }

    async deleteTask(taskId) {
        if (!confirm('Are you sure you want to delete this task?')) return;

        try {
            const response = await fetch(`/tasks/${taskId}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                }
            });

            const data = await response.json();
            if (data.success) {
                this.showAlert(data.message, 'success');
                this.loadTasks(this.currentPage);
            } else {
                this.showAlert(data.message || 'Error deleting task', 'danger');
            }
        } catch (error) {
            console.error('Error deleting task:', error);
            this.showAlert('Error deleting task', 'danger');
        }
    }

    toggleTaskSelection(checkbox) {
        const taskId = parseInt(checkbox.value);
        if (checkbox.checked) {
            if (!this.selectedTasks.includes(taskId)) {
                this.selectedTasks.push(taskId);
            }
        } else {
            this.selectedTasks = this.selectedTasks.filter(id => id !== taskId);
        }
        this.updateBulkActions();
    }

    toggleAllTasks(checked) {
        const checkboxes = document.querySelectorAll('.task-checkbox');
        checkboxes.forEach(cb => {
            cb.checked = checked;
            this.toggleTaskSelection(cb);
        });
    }

    updateBulkActions() {
        const bulkActions = document.getElementById('bulkActions');
        const selectedCount = document.getElementById('selectedCount');
        
        if (this.selectedTasks.length > 0) {
            if (bulkActions) bulkActions.style.display = 'block';
            if (selectedCount) selectedCount.textContent = `${this.selectedTasks.length} tasks selected`;
            
            // Ensure bulk dropdowns are populated when bulk actions are shown
            if (this.dropdownData) {
                this.populateBulkActionDropdowns(this.dropdownData);
            } else {
                // If data not loaded yet, load it and populate dropdowns when done
                this.loadDropdownData().then(() => {
                    if (this.dropdownData) {
                        this.populateBulkActionDropdowns(this.dropdownData);
                    }
                });
            }
        } else {
            if (bulkActions) bulkActions.style.display = 'none';
            // Clear assignee select when no tasks selected
            const assigneeSelect = document.getElementById('bulkAssigneeSelect');
            if (assigneeSelect && typeof $ !== 'undefined' && $(assigneeSelect).hasClass('select2-hidden-accessible')) {
                $(assigneeSelect).val(null).trigger('change');
            }
        }
    }

    async bulkUpdateStatus(statusId) {
        if (this.selectedTasks.length === 0) return;

        try {
            const response = await fetch('/tasks/bulk/status', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({
                    task_ids: this.selectedTasks,
                    status_id: statusId
                })
            });

            const data = await response.json();
            if (data.success) {
                this.showAlert(data.message, 'success');
                this.selectedTasks = [];
                this.updateBulkActions();
                this.loadTasks(this.currentPage);
            } else {
                this.showAlert(data.message || 'Error updating status', 'danger');
            }
        } catch (error) {
            console.error('Error bulk updating status:', error);
            this.showAlert('Error updating status', 'danger');
        }
    }

    async bulkUpdatePriority(priorityId) {
        if (this.selectedTasks.length === 0) return;

        try {
            const response = await fetch('/tasks/bulk/priority', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({
                    task_ids: this.selectedTasks,
                    priority_id: priorityId
                })
            });

            const data = await response.json();
            if (data.success) {
                this.showAlert(data.message, 'success');
                this.selectedTasks = [];
                this.updateBulkActions();
                this.loadTasks(this.currentPage);
            } else {
                this.showAlert(data.message || 'Error updating priority', 'danger');
            }
        } catch (error) {
            console.error('Error bulk updating priority:', error);
            this.showAlert('Error updating priority', 'danger');
        }
    }

    async bulkUpdateAssignee(userId) {
        if (this.selectedTasks.length === 0) return;

        try {
            const response = await fetch('/tasks/bulk/assignee', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({
                    task_ids: this.selectedTasks,
                    user_id: userId
                })
            });

            const data = await response.json();
            if (data.success) {
                this.showAlert(data.message, 'success');
                this.selectedTasks = [];
                this.updateBulkActions();
                this.loadTasks(this.currentPage);
            } else {
                this.showAlert(data.message || 'Error updating assignee', 'danger');
            }
        } catch (error) {
            console.error('Error bulk updating assignee:', error);
            this.showAlert('Error updating assignee', 'danger');
        }
    }

    async bulkUpdateAssigneeFromSelect() {
        if (this.selectedTasks.length === 0) {
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'warning',
                    title: 'No Tasks Selected',
                    text: 'Please select tasks first before assigning them.',
                    confirmButtonColor: '#3085d6'
                });
            } else {
                alert('Please select tasks first before assigning them.');
            }
            return;
        }

        const assigneeSelect = document.getElementById('bulkAssigneeSelect');
        if (!assigneeSelect) return;

        // Get selected user IDs from Select2
        let selectedUserIds = [];
        if (typeof $ !== 'undefined' && $(assigneeSelect).hasClass('select2-hidden-accessible')) {
            selectedUserIds = $(assigneeSelect).val() || [];
        } else {
            // Fallback if Select2 not initialized
            const options = assigneeSelect.selectedOptions;
            selectedUserIds = Array.from(options).map(opt => opt.value);
        }

        if (selectedUserIds.length === 0) {
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'warning',
                    title: 'No Assignees Selected',
                    text: 'Please select at least one assignee.',
                    confirmButtonColor: '#3085d6'
                });
            } else {
                alert('Please select at least one assignee.');
            }
            return;
        }

        const taskCount = this.selectedTasks.length;
        const userCount = selectedUserIds.length;

        // Show confirmation
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: 'Assign Tasks',
                html: `Are you sure you want to assign <strong>${taskCount}</strong> task${taskCount > 1 ? 's' : ''} to <strong>${userCount}</strong> assignee${userCount > 1 ? 's' : ''}?`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, assign them!',
                cancelButtonText: 'Cancel',
                allowOutsideClick: false,
                allowEscapeKey: false
            }).then(async (result) => {
                if (result.isConfirmed) {
                    await this.executeBulkAssigneeUpdate(selectedUserIds);
                }
            });
        } else {
            if (confirm(`Assign ${taskCount} task(s) to ${userCount} assignee(s)?`)) {
                await this.executeBulkAssigneeUpdate(selectedUserIds);
            }
        }
    }

    async executeBulkAssigneeUpdate(assigneeIds) {
        try {
            const response = await fetch('/tasks/bulk/assignee', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({
                    task_ids: this.selectedTasks,
                    assignee_ids: assigneeIds
                })
            });

            const data = await response.json();
            if (data.success) {
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'success',
                        title: 'Success',
                        text: data.message || 'Tasks assigned successfully.',
                        timer: 2000,
                        showConfirmButton: false
                    }).then(() => {
                        // Clear selection and reset
                        this.selectedTasks = [];
                        const assigneeSelect = document.getElementById('bulkAssigneeSelect');
                        if (assigneeSelect && typeof $ !== 'undefined' && $(assigneeSelect).hasClass('select2-hidden-accessible')) {
                            $(assigneeSelect).val(null).trigger('change');
                        }
                        this.updateBulkActions();
                        location.reload();
                    });
                } else {
                    alert(data.message || 'Tasks assigned successfully.');
                    this.selectedTasks = [];
                    this.updateBulkActions();
                    location.reload();
                }
            } else {
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: data.message || 'Failed to assign tasks.'
                    });
                } else {
                    alert(data.message || 'Failed to assign tasks.');
                }
            }
        } catch (error) {
            console.error('Error bulk updating assignee:', error);
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Failed to assign tasks. Please try again.'
                });
            } else {
                alert('Failed to assign tasks. Please try again.');
            }
        }
    }

    async bulkDeleteTasks() {
        if (this.selectedTasks.length === 0) return;
        if (!confirm(`Are you sure you want to delete ${this.selectedTasks.length} task(s)?`)) return;

        try {
            const response = await fetch('/tasks/bulk/delete', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({
                    task_ids: this.selectedTasks
                })
            });

            const data = await response.json();
            if (data.success) {
                this.showAlert(data.message, 'success');
                this.selectedTasks = [];
                this.updateBulkActions();
                this.loadTasks(this.currentPage);
            } else {
                this.showAlert(data.message || 'Error deleting tasks', 'danger');
            }
        } catch (error) {
            console.error('Error bulk deleting tasks:', error);
            this.showAlert('Error deleting tasks', 'danger');
        }
    }

    clearSelection() {
        this.selectedTasks = [];
        document.querySelectorAll('.task-checkbox').forEach(cb => cb.checked = false);
        document.getElementById('selectAllTasks').checked = false;
        this.updateBulkActions();
    }

    renderPagination(pagination) {
        const paginationContainer = document.getElementById('taskPagination');
        if (!paginationContainer) return;

        let html = '<nav><ul class="pagination">';
        
        // Previous button
        html += `<li class="page-item ${pagination.current_page === 1 ? 'disabled' : ''}">
            <a class="page-link" href="#" onclick="taskTableManager.loadTasks(${pagination.current_page - 1}); return false;">Previous</a>
        </li>`;

        // Page numbers
        for (let i = 1; i <= pagination.last_page; i++) {
            if (i === 1 || i === pagination.last_page || (i >= pagination.current_page - 2 && i <= pagination.current_page + 2)) {
                html += `<li class="page-item ${i === pagination.current_page ? 'active' : ''}">
                    <a class="page-link" href="#" onclick="taskTableManager.loadTasks(${i}); return false;">${i}</a>
                </li>`;
            } else if (i === pagination.current_page - 3 || i === pagination.current_page + 3) {
                html += '<li class="page-item disabled"><span class="page-link">...</span></li>';
            }
        }

        // Next button
        html += `<li class="page-item ${pagination.current_page === pagination.last_page ? 'disabled' : ''}">
            <a class="page-link" href="#" onclick="taskTableManager.loadTasks(${pagination.current_page + 1}); return false;">Next</a>
        </li>`;

        html += '</ul></nav>';
        paginationContainer.innerHTML = html;
    }

    showAlert(message, type = 'info') {
        // Create or update alert element
        let alertDiv = document.getElementById('ajaxAlert');
        if (!alertDiv) {
            alertDiv = document.createElement('div');
            alertDiv.id = 'ajaxAlert';
            alertDiv.className = 'alert alert-dismissible fade show';
            alertDiv.style.position = 'fixed';
            alertDiv.style.top = '20px';
            alertDiv.style.right = '20px';
            alertDiv.style.zIndex = '9999';
            document.body.appendChild(alertDiv);
        }
        
        alertDiv.className = `alert alert-${type} alert-dismissible fade show`;
        alertDiv.innerHTML = `
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
        
        setTimeout(() => {
            if (alertDiv) alertDiv.remove();
        }, 5000);
    }

    // Modal Functions
    async openTaskModal() {
        const modalElement = document.getElementById('taskModal');
        if (!modalElement) {
            console.error('Task modal element not found');
            return;
        }
        
        const modal = new bootstrap.Modal(modalElement);
        this.selectedAssignees = [];
        this.tempSelectedAssignees = [];
        
        // Load dropdown data first
        await this.loadDropdownData();
        this.populateModalDropdowns(this.dropdownData);
        
        // Reset form
        this.resetModalForm();
        
        // Show modal
        modal.show();
        
        // Set up nature change handler
        const natureSelect = document.getElementById('modalTaskNature');
        if (natureSelect) {
            const recurrenceContainer = document.getElementById('recurrenceFrequencyContainer');
            if (recurrenceContainer) {
                recurrenceContainer.style.display = natureSelect.value === 'recurring' ? 'block' : 'none';
            }
        }
    }

    populateModalDropdowns(data) {
        if (!data) return;

        // Populate project dropdown
        const projectSelect = document.getElementById('modalTaskProjectId');
        if (projectSelect && data.projects) {
            projectSelect.innerHTML = '<option value="">Select a project (Optional)</option>' +
                data.projects.map(p => `<option value="${p.id}">${p.title}</option>`).join('');
        }

        // Populate priority dropdown
        const prioritySelect = document.getElementById('modalTaskPriority');
        if (prioritySelect && data.priorities) {
            prioritySelect.innerHTML = '<option value="">Select priority</option>' +
                data.priorities.map(p => `<option value="${p.id}">${p.name}</option>`).join('');
        }

        // Populate category dropdown
        const categorySelect = document.getElementById('modalTaskCategory');
        if (categorySelect && data.categories) {
            categorySelect.innerHTML = '<option value="">Select category</option>' +
                data.categories.map(c => `<option value="${c.id}">${c.name}</option>`).join('');
        }
    }

    resetModalForm() {
        const form = document.getElementById('taskCreateModalForm');
        if (form) {
            form.reset();
            const recurrenceContainer = document.getElementById('recurrenceFrequencyContainer');
            if (recurrenceContainer) recurrenceContainer.style.display = 'none';
            this.updateSelectedAssigneesDisplay();
            this.clearModalAlerts();
            // Clear validation errors
            form.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
            form.querySelectorAll('.invalid-feedback').forEach(el => el.textContent = '');
        }
        
        // Reset loader state
        this.showCreateTaskLoader(false);
    }

    async createTaskFromModal() {
        const form = document.getElementById('taskCreateModalForm');
        if (!form) return;

        // Validate assignees
        if (this.selectedAssignees.length === 0) {
            this.showModalAlert('Please select at least one assignee', 'danger');
            return;
        }

        const formData = new FormData(form);
        
        // Add assignee IDs
        this.selectedAssignees.forEach(userId => {
            formData.append('assignee_ids[]', userId);
        });

        // Handle recurrence frequency - only add if nature is recurring
        const nature = formData.get('nature');
        if (nature === 'recurring') {
            const frequency = formData.get('recurrence_frequency');
            if (!frequency) {
                this.showModalAlert('Please select recurrence frequency', 'danger');
                return;
            }
        } else {
            formData.delete('recurrence_frequency');
        }

        // Validate required fields
        if (!formData.get('title') || !formData.get('priority_id') || !formData.get('category_id')) {
            this.showModalAlert('Please fill in all required fields', 'danger');
            return;
        }

        // Show loading state
        this.showCreateTaskLoader(true);

        try {
            const response = await fetch('/tasks/store', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: formData
            });

            const data = await response.json();
            if (data.success) {
                this.showModalAlert(data.message, 'success');
                setTimeout(() => {
                    bootstrap.Modal.getInstance(document.getElementById('taskModal')).hide();
                    this.loadTasks(this.currentPage);
                    this.showCreateTaskLoader(false);
                }, 1500);
            } else {
                this.showCreateTaskLoader(false);
                this.showModalAlert(data.message || 'Error creating task', 'danger');
                if (data.errors) {
                    this.showFormErrors(data.errors);
                }
            }
        } catch (error) {
            console.error('Error creating task:', error);
            this.showCreateTaskLoader(false);
            this.showModalAlert('Error creating task', 'danger');
        }
    }

    showCreateTaskLoader(show) {
        const submitBtn = document.getElementById('createTaskSubmitBtn');
        const cancelBtn = document.getElementById('createTaskCancelBtn');
        const spinner = document.getElementById('createTaskSpinner');
        const icon = document.getElementById('createTaskIcon');
        const text = document.getElementById('createTaskText');

        if (show) {
            if (submitBtn) submitBtn.disabled = true;
            if (cancelBtn) cancelBtn.disabled = true;
            if (spinner) spinner.classList.remove('d-none');
            if (icon) icon.classList.add('d-none');
            if (text) text.textContent = 'Creating...';
        } else {
            if (submitBtn) submitBtn.disabled = false;
            if (cancelBtn) cancelBtn.disabled = false;
            if (spinner) spinner.classList.add('d-none');
            if (icon) icon.classList.remove('d-none');
            if (text) text.textContent = 'Create Task';
        }
    }

    showUpdateTaskLoader(show) {
        const submitBtn = document.getElementById('updateTaskSubmitBtn');
        const cancelBtn = document.getElementById('updateTaskCancelBtn');
        const spinner = document.getElementById('updateTaskSpinner');
        const icon = document.getElementById('updateTaskIcon');
        const text = document.getElementById('updateTaskText');

        if (show) {
            if (submitBtn) submitBtn.disabled = true;
            if (cancelBtn) cancelBtn.disabled = true;
            if (spinner) spinner.classList.remove('d-none');
            if (icon) icon.classList.add('d-none');
            if (text) text.textContent = 'Updating...';
        } else {
            if (submitBtn) submitBtn.disabled = false;
            if (cancelBtn) cancelBtn.disabled = false;
            if (spinner) spinner.classList.add('d-none');
            if (icon) icon.classList.remove('d-none');
            if (text) text.textContent = 'Update Task';
        }
    }

    showFormErrors(errors) {
        Object.keys(errors).forEach(field => {
            const input = document.querySelector(`[name="${field}"]`);
            if (input) {
                input.classList.add('is-invalid');
                const feedback = input.parentNode.querySelector('.invalid-feedback') || 
                    input.nextElementSibling?.classList.contains('invalid-feedback') ? 
                    input.nextElementSibling : null;
                if (feedback) {
                    feedback.textContent = errors[field][0];
                } else {
                    const errorDiv = document.createElement('div');
                    errorDiv.className = 'invalid-feedback';
                    errorDiv.textContent = errors[field][0];
                    input.parentNode.appendChild(errorDiv);
                }
            }
        });
    }

    openEmployeeModal() {
        const employeeModal = document.getElementById('employeeModal');
        if (!employeeModal) return;

        // Remove any existing event listeners to avoid duplicates
        const existingHandler = employeeModal._saveSelectionsHandler;
        if (existingHandler) {
            employeeModal.removeEventListener('hidden.bs.modal', existingHandler);
        }

        // Set up event listener to save selections when modal is hidden
        const saveSelectionsOnClose = () => {
            if (this.isEditMode) {
                this.editSelectedAssignees = [...this.editTempSelectedAssignees];
                this.updateEditSelectedAssigneesDisplay();
            } else {
                this.selectedAssignees = [...this.tempSelectedAssignees];
                this.updateSelectedAssigneesDisplay();
            }
            employeeModal.removeEventListener('hidden.bs.modal', saveSelectionsOnClose);
            delete employeeModal._saveSelectionsHandler;
        };
        employeeModal.addEventListener('hidden.bs.modal', saveSelectionsOnClose);
        employeeModal._saveSelectionsHandler = saveSelectionsOnClose;

        if (!this.dropdownData || !this.dropdownData.users) {
            this.loadDropdownData().then(() => {
                if (this.isEditMode) {
                    this.tempSelectedAssignees = this.editTempSelectedAssignees.map(e => e.id);
                } else {
                    this.tempSelectedAssignees = [...this.selectedAssignees];
                }
                this.updateSelectedAssigneesDisplay();
                this.updateModalSelectedDisplay();
                // Clear search
                const searchInput = document.getElementById('employeeSearch');
                if (searchInput) searchInput.value = '';
                this.renderEmployeeList();
                const modal = new bootstrap.Modal(employeeModal);
                modal.show();
            });
        } else {
            if (this.isEditMode) {
                this.tempSelectedAssignees = this.editTempSelectedAssignees.map(e => e.id);
            } else {
                this.tempSelectedAssignees = [...this.selectedAssignees];
            }
            this.updateSelectedAssigneesDisplay();
            this.updateModalSelectedDisplay();
            // Clear search
            const searchInput = document.getElementById('employeeSearch');
            if (searchInput) searchInput.value = '';
            this.renderEmployeeList();
            const modal = new bootstrap.Modal(employeeModal);
            modal.show();
        }
    }

    renderEmployeeList(searchTerm = '') {
        const employeeList = document.getElementById('employeeList');
        if (!employeeList || !this.dropdownData || !this.dropdownData.users) return;

        let users = this.dropdownData.users;
        if (searchTerm) {
            users = users.filter(u => u.name.toLowerCase().includes(searchTerm.toLowerCase()));
        }

        if (users.length === 0) {
            employeeList.innerHTML = `
                <div class="text-center py-4">
                    <i class="bi bi-person-x text-muted" style="font-size: 3rem;"></i>
                    <p class="text-muted mt-2 mb-0">
                        ${searchTerm ? `No employees found matching "${searchTerm}"` : 'No employees available'}
                    </p>
                </div>
            `;
            return;
        }

        employeeList.innerHTML = `
            <div class="row g-2">
                ${users.map(user => {
                    const isSelected = Array.isArray(this.tempSelectedAssignees) && 
                        (this.tempSelectedAssignees.includes(user.id) || 
                         (typeof this.tempSelectedAssignees[0] === 'object' && 
                          this.tempSelectedAssignees.some(e => e.id === user.id)));
                    const roleName = user.role ? (user.role.name ? user.role.name.charAt(0).toUpperCase() + user.role.name.slice(1) : 'Employee') : 'Employee';
                    return `
                        <div class="col-md-6 col-lg-4">
                            <div class="card employee-card h-100" 
                                 style="cursor: pointer; transition: all 0.2s; ${isSelected ? 'border: 2px solid #28a745;' : ''}"
                                 onclick="taskTableManager.toggleEmployeeSelection(${user.id})"
                                 onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 4px 8px rgba(0,0,0,0.1)'"
                                 onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='none'">
                                <div class="card-body text-center p-3">
                                    <div class="mb-2">
                                        <i class="bi bi-person-circle text-primary" style="font-size: 2rem;"></i>
                                    </div>
                                    <h6 class="card-title mb-1">${user.name}</h6>
                                    <small class="text-muted">${roleName}</small>
                                    ${user.email ? `
                                        <div class="mt-1">
                                            <small class="text-muted">
                                                <i class="bi bi-envelope me-1"></i>${user.email}
                                            </small>
                                        </div>
                                    ` : ''}
                                    ${isSelected ? `
                                        <div class="mt-2">
                                            <span class="badge bg-success">
                                                <i class="bi bi-check-circle me-1"></i>Selected
                                            </span>
                                        </div>
                                    ` : ''}
                                </div>
                            </div>
                        </div>
                    `;
                }).join('')}
            </div>
        `;
    }

    confirmEmployeeSelection() {
        // Save selections based on mode
        if (this.isEditMode) {
            // Convert tempSelectedAssignees (array of IDs) to array of objects
            this.editTempSelectedAssignees = this.tempSelectedAssignees.map(id => {
                const user = this.dropdownData?.users?.find(u => u.id === id);
                return user ? {
                    id: user.id,
                    name: user.name,
                    email: user.email || '',
                    role: user.role ? user.role.name : 'Employee'
                } : null;
            }).filter(e => e !== null);
            this.editSelectedAssignees = [...this.editTempSelectedAssignees];
            this.updateEditSelectedAssigneesDisplay();
        } else {
            this.selectedAssignees = [...this.tempSelectedAssignees];
            this.updateSelectedAssigneesDisplay();
        }
        // Close modal (this will trigger the hidden.bs.modal event which will also save)
        const modal = bootstrap.Modal.getInstance(document.getElementById('employeeModal'));
        if (modal) {
            modal.hide();
        }
    }

    updateSelectedAssigneesDisplay() {
        const container = document.getElementById('selectedAssigneesContainer');
        const display = document.getElementById('selectedAssignees');
        const selectBtn = document.getElementById('selectAssigneesBtn');
        const modalDisplay = document.getElementById('selectedEmployeesDisplay');
        const modalBadges = document.getElementById('selectedEmployeesBadges');

        if (!container || !display || !selectBtn) return;

        if (this.selectedAssignees.length === 0) {
            container.style.display = 'none';
            if (modalDisplay) modalDisplay.style.display = 'none';
            selectBtn.innerHTML = '<i class="bi bi-people me-1"></i>Select Assignees';
            return;
        }

        container.style.display = 'block';
        if (modalDisplay) modalDisplay.style.display = 'block';
        
        // Get user names from dropdown data
        const userNames = this.selectedAssignees.map(id => {
            const user = this.dropdownData?.users?.find(u => u.id === id);
            return user ? user.name : `User ${id}`;
        });

        display.innerHTML = this.selectedAssignees.map((userId, index) => {
            const userName = userNames[index];
            return `
                <span class="badge bg-primary me-1 mb-1">
                    ${userName}
                    <button type="button" class="btn-close btn-close-white btn-sm ms-1" 
                            style="font-size: 0.7em;" 
                            onclick="taskTableManager.removeAssignee(${userId})"></button>
                </span>
            `;
        }).join('');

        // Update button text
        selectBtn.innerHTML = '<i class="bi bi-people me-1"></i>Change Assignees';

        // Update modal badges
        if (modalBadges) {
            modalBadges.innerHTML = this.selectedAssignees.map((userId, index) => {
                const userName = userNames[index];
                return `
                    <span class="badge bg-primary me-1 mb-1">
                        ${userName}
                        <button type="button" class="btn-close btn-close-white btn-sm ms-1" 
                                style="font-size: 0.7em;" 
                                onclick="taskTableManager.removeAssignee(${userId}); taskTableManager.renderEmployeeList(document.getElementById('employeeSearch').value);"></button>
                    </span>
                `;
            }).join('');
        }
    }

    removeAssignee(userId) {
        this.selectedAssignees = this.selectedAssignees.filter(id => id !== userId);
        this.tempSelectedAssignees = this.tempSelectedAssignees.filter(id => id !== userId);
        this.updateSelectedAssigneesDisplay();
        this.updateModalSelectedDisplay();
        // If modal is open, re-render the list
        const employeeModal = document.getElementById('employeeModal');
        if (employeeModal && employeeModal.classList.contains('show')) {
            const searchTerm = document.getElementById('employeeSearch')?.value || '';
            this.renderEmployeeList(searchTerm);
        }
    }

    filterEmployees(searchTerm) {
        this.renderEmployeeList(searchTerm);
    }

    toggleEmployeeSelection(userId) {
        const index = this.tempSelectedAssignees.indexOf(userId);
        if (index > -1) {
            this.tempSelectedAssignees.splice(index, 1);
        } else {
            this.tempSelectedAssignees.push(userId);
        }
        // Update the selected employees display in modal
        this.updateModalSelectedDisplay();
        // Re-render to update selected badges
        const searchTerm = document.getElementById('employeeSearch')?.value || '';
        this.renderEmployeeList(searchTerm);
    }

    updateModalSelectedDisplay() {
        const modalDisplay = document.getElementById('selectedEmployeesDisplay');
        const modalBadges = document.getElementById('selectedEmployeesBadges');
        
        if (!modalDisplay || !modalBadges) return;

        // Handle both array of IDs and array of objects
        const selectedIds = Array.isArray(this.tempSelectedAssignees) && this.tempSelectedAssignees.length > 0
            ? (typeof this.tempSelectedAssignees[0] === 'object' 
                ? this.tempSelectedAssignees.map(e => e.id)
                : this.tempSelectedAssignees)
            : [];

        if (selectedIds.length === 0) {
            modalDisplay.style.display = 'none';
            modalBadges.innerHTML = '';
            return;
        }

        modalDisplay.style.display = 'block';
        
        // Get user names from dropdown data
        const userNames = selectedIds.map(id => {
            const user = this.dropdownData?.users?.find(u => u.id === id);
            return user ? user.name : `User ${id}`;
        });

        modalBadges.innerHTML = selectedIds.map((userId, index) => {
            const userName = userNames[index];
            return `
                <span class="badge bg-primary me-1 mb-1">
                    ${userName}
                    <button type="button" class="btn-close btn-close-white btn-sm ms-1" 
                            style="font-size: 0.7em;" 
                            onclick="taskTableManager.removeAssignee(${userId}); taskTableManager.renderEmployeeList(document.getElementById('employeeSearch').value);"></button>
                </span>
            `;
        }).join('');
    }

    openProjectModal() {
        const title = prompt('Enter project title:');
        if (title) {
            this.showModalAlert('Project creation feature coming soon. Please create project from Projects page.', 'info');
        }
    }

    openCategoryModal() {
        const name = prompt('Enter category name:');
        if (name) {
            this.showModalAlert('Category creation feature coming soon. Please create category from Categories page.', 'info');
        }
    }

    showModalAlert(message, type = 'info') {
        const container = document.getElementById('modalAlertContainer');
        if (!container) return;

        container.innerHTML = `
            <div class="alert alert-${type} alert-dismissible fade show m-3" role="alert">
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        `;

        setTimeout(() => {
            container.innerHTML = '';
        }, 5000);
    }

    clearModalAlerts() {
        const container = document.getElementById('modalAlertContainer');
        if (container) {
            container.innerHTML = '';
        }
    }
}

// Initialize when DOM is ready
let taskTableManager;
document.addEventListener('DOMContentLoaded', () => {
    taskTableManager = new TaskTableManager();
});

// Global functions for inline event handlers
function toggleAllTasks(checkbox) {
    if (taskTableManager) {
        taskTableManager.toggleAllTasks(checkbox.checked);
    }
}

function toggleTaskSelection(checkbox) {
    if (taskTableManager) {
        taskTableManager.toggleTaskSelection(checkbox);
    }
}

function bulkUpdateStatus(statusId, statusName) {
    if (taskTableManager) {
        taskTableManager.bulkUpdateStatus(statusId);
    }
}

function bulkUpdatePriority(priorityId, priorityName) {
    if (taskTableManager) {
        taskTableManager.bulkUpdatePriority(priorityId);
    }
}

function bulkUpdateAssignee(userId, userName) {
    if (taskTableManager) {
        taskTableManager.bulkUpdateAssignee(userId);
    }
}

function bulkDeleteTasks() {
    if (taskTableManager) {
        taskTableManager.bulkDeleteTasks();
    }
}

function clearSelection() {
    if (taskTableManager) {
        taskTableManager.clearSelection();
    }
}

