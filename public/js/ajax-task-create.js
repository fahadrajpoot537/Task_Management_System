/**
 * AJAX Task Create Management
 */

class TaskCreateManager {
    constructor() {
        this.dropdownData = null;
        this.init();
    }

    async init() {
        await this.loadDropdownData();
        this.setupEventListeners();
    }

    setupEventListeners() {
        const form = document.getElementById('taskCreateForm');
        if (form) {
            form.addEventListener('submit', (e) => {
                e.preventDefault();
                this.createTask();
            });
        }
    }

    async loadDropdownData() {
        try {
            const response = await fetch('/tasks/create/dropdown-data');
            const data = await response.json();
            if (data.success) {
                this.dropdownData = data;
                this.populateDropdowns(data);
            }
        } catch (error) {
            console.error('Error loading dropdown data:', error);
            this.showAlert('Error loading form data', 'danger');
        }
    }

    populateDropdowns(data) {
        // Populate projects
        const projectSelect = document.getElementById('project_id');
        if (projectSelect && data.projects) {
            projectSelect.innerHTML = '<option value="">Select a project</option>' +
                data.projects.map(p => `<option value="${p.id}">${p.title}</option>`).join('');
        }

        // Populate priorities
        const prioritySelect = document.getElementById('priority_id');
        if (prioritySelect && data.priorities) {
            prioritySelect.innerHTML = '<option value="">Select Priority</option>' +
                data.priorities.map(p => `<option value="${p.id}">${p.name}</option>`).join('');
        }

        // Populate categories
        const categorySelect = document.getElementById('category_id');
        if (categorySelect && data.categories) {
            categorySelect.innerHTML = '<option value="">Select Category</option>' +
                data.categories.map(c => `<option value="${c.id}">${c.name}</option>`).join('');
        }

        // Populate statuses
        const statusSelect = document.getElementById('status_id');
        if (statusSelect && data.statuses) {
            statusSelect.innerHTML = '<option value="">Select Status</option>' +
                data.statuses.map(s => `<option value="${s.id}">${s.name}</option>`).join('');
        }

        // Populate users
        const userSelect = document.getElementById('assigned_to_user_id');
        if (userSelect && data.users) {
            userSelect.innerHTML = '<option value="">Select User</option>' +
                data.users.map(u => `<option value="${u.id}">${u.name}</option>`).join('');
        }
    }

    async createTask() {
        const form = document.getElementById('taskCreateForm');
        if (!form) return;

        const formData = new FormData(form);
        
        try {
            const response = await fetch('/tasks/create/store', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: formData
            });

            const data = await response.json();
            if (data.success) {
                this.showAlert(data.message, 'success');
                setTimeout(() => {
                    window.location.href = '/tasks';
                }, 1500);
            } else {
                this.showErrors(data.errors || {});
                this.showAlert(data.message || 'Error creating task', 'danger');
            }
        } catch (error) {
            console.error('Error creating task:', error);
            this.showAlert('Error creating task', 'danger');
        }
    }

    showErrors(errors) {
        // Clear previous errors
        document.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
        document.querySelectorAll('.invalid-feedback').forEach(el => el.remove());

        // Show new errors
        Object.keys(errors).forEach(field => {
            const input = document.getElementById(field);
            if (input) {
                input.classList.add('is-invalid');
                const errorDiv = document.createElement('div');
                errorDiv.className = 'invalid-feedback';
                errorDiv.textContent = errors[field][0];
                input.parentNode.appendChild(errorDiv);
            }
        });
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
let taskCreateManager;
document.addEventListener('DOMContentLoaded', () => {
    taskCreateManager = new TaskCreateManager();
});

