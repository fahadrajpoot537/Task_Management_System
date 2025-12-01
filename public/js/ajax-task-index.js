/**
 * AJAX Task Index Management
 * Simplified version similar to TaskTableManager
 */

class TaskIndexManager {
    constructor() {
        this.currentPage = 1;
        this.filters = {
            search: '',
            statusFilter: '',
            priorityFilter: '',
            userFilter: ''
        };
        this.sortField = 'created_at';
        this.sortDirection = 'desc';
        this.dropdownData = null;
        this.modalDropdownData = null;
        this.selectedAssignees = [];
        this.tempSelectedAssignees = [];
        this.init();
    }

    init() {
        this.loadDropdownData();
        this.loadTasks();
        this.setupEventListeners();
    }

    setupEventListeners() {
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

        ['statusFilter', 'priorityFilter', 'userFilter'].forEach(filterId => {
            const filter = document.getElementById(filterId);
            if (filter) {
                filter.addEventListener('change', (e) => {
                    this.filters[filterId] = e.target.value;
                    this.currentPage = 1;
                    this.loadTasks();
                });
            }
        });

        // Nature of task change handler - set up after modal is opened
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
    }

    async loadDropdownData() {
        try {
            const response = await fetch('/tasks/dropdown-data');
            const data = await response.json();
            if (data.success) {
                this.dropdownData = data;
                this.populateDropdowns(data);
            }
        } catch (error) {
            console.error('Error loading dropdown data:', error);
        }
    }

    async loadModalDropdownData() {
        try {
            const response = await fetch('/tasks/dropdown-data');
            const data = await response.json();
            if (data.success) {
                this.modalDropdownData = data;
                this.populateModalDropdowns(data);
            }
        } catch (error) {
            console.error('Error loading modal dropdown data:', error);
        }
    }

    populateDropdowns(data) {
        const statusFilter = document.getElementById('statusFilter');
        if (statusFilter && data.statuses) {
            statusFilter.innerHTML = '<option value="">All Status</option>' +
                data.statuses.map(s => `<option value="${s.id}">${s.name}</option>`).join('');
        }

        const userFilter = document.getElementById('userFilter');
        if (userFilter && data.users) {
            userFilter.innerHTML = '<option value="">All Users</option>' +
                data.users.map(u => `<option value="${u.id}">${u.name}</option>`).join('');
        }
    }

    async loadTasks(page = 1) {
        try {
            const params = new URLSearchParams({
                page: page,
                per_page: 10,
                sortField: this.sortField,
                sortDirection: this.sortDirection,
                ...this.filters
            });

            const response = await fetch(`/tasks/data?${params}`);
            const data = await response.json();

            if (data.success) {
                this.currentPage = data.pagination.current_page;
                this.renderTasks(data.tasks);
                this.renderPagination(data.pagination);
            }
        } catch (error) {
            console.error('Error loading tasks:', error);
        }
    }

    renderTasks(tasks) {
        const container = document.getElementById('tasksContainer');
        if (!container) return;

        if (tasks.length === 0) {
            container.innerHTML = '<p class="text-center text-muted">No tasks found</p>';
            return;
        }

        container.innerHTML = tasks.map(task => `
            <div class="card mb-3">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <h5 class="card-title">
                                <a href="/tasks/${task.id}/details">${task.title}</a>
                            </h5>
                            <p class="card-text text-muted">${task.description || ''}</p>
                            <div class="d-flex gap-2 flex-wrap">
                                ${task.status ? `<span class="badge bg-${task.status.color}">${task.status.name}</span>` : ''}
                                ${task.priority ? `<span class="badge bg-${task.priority.color}">${task.priority.name}</span>` : ''}
                            </div>
                        </div>
                        <div class="dropdown">
                            <button class="btn btn-sm btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown">
                                Actions
                            </button>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="/tasks/${task.id}/details">View</a></li>
                                <li><a class="dropdown-item" href="#" onclick="taskIndexManager.updateStatus(${task.id}, event)">Change Status</a></li>
                                <li><a class="dropdown-item text-danger" href="#" onclick="taskIndexManager.deleteTask(${task.id})">Delete</a></li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        `).join('');
    }

    async updateStatus(taskId, event) {
        event.preventDefault();
        // Implementation similar to TaskTableManager
        console.log('Update status for task:', taskId);
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
            }
        } catch (error) {
            console.error('Error deleting task:', error);
        }
    }

    renderPagination(pagination) {
        const container = document.getElementById('paginationContainer');
        if (!container) return;

        let html = '<nav><ul class="pagination">';
        
        html += `<li class="page-item ${pagination.current_page === 1 ? 'disabled' : ''}">
            <a class="page-link" href="#" onclick="taskIndexManager.loadTasks(${pagination.current_page - 1}); return false;">Previous</a>
        </li>`;

        for (let i = 1; i <= pagination.last_page; i++) {
            html += `<li class="page-item ${i === pagination.current_page ? 'active' : ''}">
                <a class="page-link" href="#" onclick="taskIndexManager.loadTasks(${i}); return false;">${i}</a>
            </li>`;
        }

        html += `<li class="page-item ${pagination.current_page === pagination.last_page ? 'disabled' : ''}">
            <a class="page-link" href="#" onclick="taskIndexManager.loadTasks(${pagination.current_page + 1}); return false;">Next</a>
        </li>`;

        html += '</ul></nav>';
        container.innerHTML = html;
    }

    populateModalDropdowns(data) {
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

    async openTaskModal() {
        const modal = new bootstrap.Modal(document.getElementById('taskModal'));
        this.selectedAssignees = [];
        this.tempSelectedAssignees = [];
        
        // Load dropdown data first
        await this.loadModalDropdownData();
        
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

    resetModalForm() {
        const form = document.getElementById('taskCreateModalForm');
        if (form) {
            form.reset();
            document.getElementById('recurrenceFrequencyContainer').style.display = 'none';
            this.updateSelectedAssigneesDisplay();
            this.clearModalAlerts();
            // Clear validation errors
            form.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
            form.querySelectorAll('.invalid-feedback').forEach(el => el.textContent = '');
        }
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
            // Remove recurrence_frequency if not recurring
            formData.delete('recurrence_frequency');
        }

        // Validate required fields
        if (!formData.get('title') || !formData.get('priority_id') || !formData.get('category_id')) {
            this.showModalAlert('Please fill in all required fields', 'danger');
            return;
        }

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
                }, 1500);
            } else {
                this.showModalAlert(data.message || 'Error creating task', 'danger');
                if (data.errors) {
                    this.showFormErrors(data.errors);
                }
            }
        } catch (error) {
            console.error('Error creating task:', error);
            this.showModalAlert('Error creating task', 'danger');
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
        if (!this.modalDropdownData || !this.modalDropdownData.users) {
            this.loadModalDropdownData().then(() => {
                this.renderEmployeeList();
                const modal = new bootstrap.Modal(document.getElementById('employeeModal'));
                this.tempSelectedAssignees = [...this.selectedAssignees];
                modal.show();
            });
        } else {
            this.renderEmployeeList();
            const modal = new bootstrap.Modal(document.getElementById('employeeModal'));
            this.tempSelectedAssignees = [...this.selectedAssignees];
            modal.show();
        }
    }

    renderEmployeeList(searchTerm = '') {
        const employeeList = document.getElementById('employeeList');
        if (!employeeList || !this.modalDropdownData || !this.modalDropdownData.users) return;

        let users = this.modalDropdownData.users;
        if (searchTerm) {
            users = users.filter(u => u.name.toLowerCase().includes(searchTerm.toLowerCase()));
        }

        employeeList.innerHTML = users.map(user => {
            const isSelected = this.tempSelectedAssignees.includes(user.id);
            return `
                <div class="form-check mb-2 p-2 border rounded">
                    <input class="form-check-input" type="checkbox" value="${user.id}" 
                           id="employee_${user.id}" ${isSelected ? 'checked' : ''} 
                           onchange="taskIndexManager.toggleEmployeeSelection(${user.id})">
                    <label class="form-check-label" for="employee_${user.id}">
                        ${user.name}
                    </label>
                </div>
            `;
        }).join('');
    }

    toggleEmployeeSelection(userId) {
        const index = this.tempSelectedAssignees.indexOf(userId);
        if (index > -1) {
            this.tempSelectedAssignees.splice(index, 1);
        } else {
            this.tempSelectedAssignees.push(userId);
        }
    }

    confirmEmployeeSelection() {
        this.selectedAssignees = [...this.tempSelectedAssignees];
        this.updateSelectedAssigneesDisplay();
        bootstrap.Modal.getInstance(document.getElementById('employeeModal')).hide();
    }

    updateSelectedAssigneesDisplay() {
        const container = document.getElementById('selectedAssigneesContainer');
        const display = document.getElementById('selectedAssignees');
        const input = document.getElementById('modalTaskAssigneeId');

        if (!container || !display || !input) return;

        if (this.selectedAssignees.length === 0) {
            container.style.display = 'none';
            input.value = '';
            return;
        }

        container.style.display = 'block';
        
        // Get user names from dropdown data
        const userNames = this.selectedAssignees.map(id => {
            const user = this.modalDropdownData?.users?.find(u => u.id === id);
            return user ? user.name : `User ${id}`;
        });

        display.innerHTML = this.selectedAssignees.map((userId, index) => {
            const userName = userNames[index];
            return `
                <span class="badge bg-primary me-1 mb-1">
                    ${userName}
                    <button type="button" class="btn-close btn-close-white btn-sm ms-1" 
                            style="font-size: 0.7em;" 
                            onclick="taskIndexManager.removeAssignee(${userId})"></button>
                </span>
            `;
        }).join('');

        input.value = userNames.join(', ');
    }

    removeAssignee(userId) {
        this.selectedAssignees = this.selectedAssignees.filter(id => id !== userId);
        this.updateSelectedAssigneesDisplay();
    }

    filterEmployees(searchTerm) {
        this.renderEmployeeList(searchTerm);
    }

    openProjectModal() {
        // Simplified - you can implement a full project creation modal later
        const title = prompt('Enter project title:');
        if (title) {
            this.showModalAlert('Project creation feature coming soon. Please create project from Projects page.', 'info');
        }
    }

    openCategoryModal() {
        // Simplified - you can implement a full category creation modal later
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

    showAlert(message, type = 'info') {
        const alertDiv = document.createElement('div');
        alertDiv.className = `alert alert-${type} alert-dismissible fade show`;
        alertDiv.style.position = 'fixed';
        alertDiv.style.top = '20px';
        alertDiv.style.right = '20px';
        alertDiv.style.zIndex = '9999';
        alertDiv.innerHTML = `${message}<button type="button" class="btn-close" data-bs-dismiss="alert"></button>`;
        document.body.appendChild(alertDiv);
        setTimeout(() => alertDiv.remove(), 5000);
    }
}

let taskIndexManager;
document.addEventListener('DOMContentLoaded', () => {
    taskIndexManager = new TaskIndexManager();
});

