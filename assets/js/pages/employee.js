// Employee Management Module
const EmployeeManagement = {
    // State
    currentPage: 1,
    itemsPerPage: 10,
    totalItems: 0,
    filters: {
        role: 'all',
        status: 'all',
        search: ''
    },
    employees: [],
    currentEmployee: null,
    deleteId: null,

    // Initialize
    init: function() {
        console.log('Employee management initialized');
        this.loadEmployees();
         this.addTrainingButtonStyle(); 
        this.loadStatistics();
        this.setupEventListeners();
    },

    // Setup Event Listeners
    setupEventListeners: function() {
        // Filter selects
        document.getElementById('filterRole')?.addEventListener('change', (e) => {
            this.filters.role = e.target.value;
            this.currentPage = 1;
            this.loadEmployees();
        });

        document.getElementById('filterStatus')?.addEventListener('change', (e) => {
            this.filters.status = e.target.value;
            this.currentPage = 1;
            this.loadEmployees();
        });

        // Search
        const searchInput = document.getElementById('searchEmployee');
        if (searchInput) {
            searchInput.addEventListener('input', debounce((e) => {
                this.filters.search = e.target.value.toLowerCase();
                this.currentPage = 1;
                this.loadEmployees();
            }, 300));
        }

        // Refresh button
        document.getElementById('refreshData')?.addEventListener('click', () => {
            this.loadEmployees();
            this.loadStatistics();
            this.showNotification('Data refreshed', 'success');
        });

        // Export button
        document.getElementById('exportData')?.addEventListener('click', () => {
            this.exportEmployees();
        });
    },

    // Load Employees from API
    loadEmployees: async function() {
        try {
            // Build query string with filters
            const params = new URLSearchParams({
                page: this.currentPage,
                limit: this.itemsPerPage,
                search: this.filters.search,
                role: this.filters.role,
                status: this.filters.status
            });

            const response = await fetch(`../api/employees.php?${params}`);
            const result = await response.json();

            if (result.success) {
                this.employees = result.data.data;
                this.totalItems = result.data.total;
                this.renderTable();
                this.updatePagination();
            } else {
                this.showNotification(result.error || 'Error loading employees', 'error');
            }
        } catch (error) {
            console.error('Error loading employees:', error);
            this.showNotification('Error connecting to server', 'error');
        }
    },

    // Load Statistics from API
    // Load Statistics from API
loadStatistics: async function() {
    try {
        const response = await fetch('../api/employees.php?stats=1');
        
        if (response.status === 403) {
            return;
        }
        
        const result = await response.json();

        if (result.success) {
            const stats = result.data;
            
            // Update stat values
            const statValues = document.querySelectorAll('.stat-value');
            if (statValues.length >= 4) {
                statValues[0].textContent = stats.total || 0;                    // Total Employees
                statValues[1].textContent = stats.active || 0;                   // Active Employees
                statValues[2].textContent = stats.active_drivers || 0;           // Active Drivers
                statValues[3].textContent = stats.active_dispatchers || 0;       // Active Dispatchers
            }

            // Update trends with real data
            const totalTrend = document.getElementById('totalTrend');
            if (totalTrend) {
                totalTrend.innerHTML = `
                    <i class="fas fa-arrow-up"></i> 
                    <span>${stats.joined_this_month || 0}</span> this month
                `;
            }

            const activeTrend = document.getElementById('activeTrend');
            if (activeTrend) {
                const activePercentage = stats.total > 0 
                    ? Math.round((stats.active / stats.total) * 100) 
                    : 0;
                activeTrend.innerHTML = `
                    <i class="fas fa-check"></i> 
                    <span>${activePercentage}</span>% of workforce
                `;
            }

            const driversTrend = document.getElementById('driversTrend');
            if (driversTrend) {
                // Show both scheduled and on road
                driversTrend.innerHTML = `
                    <i class="fas fa-truck"></i> 
                    <span>${stats.on_road || 0}</span> on road
                `;
            }

            const dispatchersTrend = document.getElementById('dispatchersTrend');
            if (dispatchersTrend) {
                dispatchersTrend.innerHTML = `
                    <i class="fas fa-clock"></i> 
                    <span>${stats.on_duty || 0}</span> on duty
                `;
            }
            
            console.log('Statistics updated:', stats);
        }
    } catch (error) {
        console.error('Error loading statistics:', error);
    }
},

    // Render Table
// Update the renderTable function to add a new action for pending training
renderTable: function() {
    const tbody = document.querySelector('#employeeTable tbody');
    if (!tbody) return;

    if (this.employees.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="5">
                    <div class="empty-state">
                        <i class="fas fa-users"></i>
                        <h3>No employees found</h3>
                        <p>Try adjusting your filters or add a new employee</p>
                        <button class="btn btn-primary" onclick="EmployeeManagement.openAddModal()">
                            <i class="fas fa-plus"></i> Add Employee
                        </button>
                    </div>
                </td>
            </tr>
        `;
        return;
    }

    let html = '';
    this.employees.forEach(emp => {
        const initials = this.getInitials(emp.full_name);
        
        // Check if employee can be assigned to training (not already a driver or in training)
        const canAssignToTraining = emp.role !== 'driver' && emp.status !== 'pending';
        
        html += `
            <tr>
                <td>
                    <div class="user-info">
                        <div class="user-avatar">${initials}</div>
                        <div class="user-details">
                            <span class="user-name">${emp.full_name}</span>
                            <span class="user-email">${emp.email}</span>
                            <span class="user-id-badge">${emp.employee_id}</span>
                        </div>
                    </div>
                </td>
                <td>
                    <span class="role-badge role-${emp.role}">
                        <i class="fas fa-${this.getRoleIcon(emp.role)}"></i>
                        ${this.formatRole(emp.role)}
                    </span>
                </td>
                <td>
                    <span class="employee-id-display">
                        <i class="fas fa-id-card"></i>
                        ${emp.employee_id}
                    </span>
                </td>
                <td>
                    <span class="status-badge status-${emp.status}">
                        <i class="fas fa-${this.getStatusIcon(emp.status)}"></i>
                        ${emp.status ? emp.status.replace('-', ' ').toUpperCase() : 'UNKNOWN'}
                    </span>
                </td>
                <td>
                    <div class="action-group">
                        <button class="action-btn view" onclick="EmployeeManagement.openViewModal(${emp.id})" title="View">
                            <i class="fas fa-eye"></i>
                        </button>
                        <button class="action-btn edit" onclick="EmployeeManagement.openEditModal(${emp.id})" title="Edit">
                            <i class="fas fa-edit"></i>
                        </button>
                        ${canAssignToTraining ? `
                        <button class="action-btn training" onclick="EmployeeManagement.openTrainingModal(${emp.id}, '${emp.full_name}')" title="Assign to Driver Training">
                            <i class="fas fa-graduation-cap"></i>
                        </button>
                        ` : ''}
                        <button class="action-btn delete" onclick="EmployeeManagement.openDeleteModal(${emp.id}, '${emp.full_name}')" title="Delete">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </td>
            </tr>
        `;
    });

    tbody.innerHTML = html;
},

// Add new function to open training assignment modal
openTrainingModal: function(userId, userName) {
    // Store in current employee
    this.currentEmployee = { id: userId, full_name: userName };
    
    // Create modal if it doesn't exist
    if (!document.getElementById('assignTrainingModal')) {
        this.createTrainingModal();
    }
    
    // Set user name in modal
    document.getElementById('trainingUserName').textContent = userName;
    document.getElementById('trainingUserId').value = userId;
    document.getElementById('trainingNotes').value = '';
    
    // Show modal
    document.getElementById('assignTrainingModal').classList.remove('modal-hidden');
},

// Create training assignment modal
createTrainingModal: function() {
    const modalHtml = `
        <div id="assignTrainingModal" class="modal modal-hidden">
            <div class="modal-content" style="max-width: 500px;">
                <div class="modal-header">
                    <h3 class="modal-title">
                        <i class="fas fa-graduation-cap"></i>
                        Assign to Driver Training
                    </h3>
                    <button class="modal-close" onclick="EmployeeManagement.closeModal('assignTrainingModal')">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div class="modal-body" style="padding: 20px;">
                    <form id="assignTrainingForm" onsubmit="event.preventDefault(); EmployeeManagement.submitTrainingAssignment();">
                        <input type="hidden" id="trainingUserId">
                        
                        <div style="margin-bottom: 20px; padding: 15px; background: #f8f9fa; border-radius: 8px;">
                            <div style="display: flex; align-items: center; gap: 15px;">
                                <div style="width: 50px; height: 50px; background: #3b82f6; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-size: 20px; font-weight: bold;">
                                    <i class="fas fa-user"></i>
                                </div>
                                <div>
                                    <div style="font-weight: 600; font-size: 16px;" id="trainingUserName"></div>
                                    <div style="color: #64748b; font-size: 13px;">Assigning to driver training program</div>
                                </div>
                            </div>
                        </div>
                        
                        <div style="margin-bottom: 20px;">
                            <label class="form-label">
                                <i class="fas fa-clipboard-list"></i>
                                Training Notes (Optional)
                            </label>
                            <textarea class="form-input" id="trainingNotes" rows="3" 
                                placeholder="Enter any notes or instructions for the dispatcher..."></textarea>
                        </div>
                        
                        <div style="margin-bottom: 20px; padding: 12px; background: #fef3c7; border-left: 4px solid #f59e0b; border-radius: 4px;">
                            <i class="fas fa-info-circle" style="color: #f59e0b; margin-right: 8px;"></i>
                            <span style="font-size: 13px; color: #92400e;">
                                This employee will be assigned to dispatcher for driver training. 
                                Their role will remain as ${this.currentEmployee?.role || 'current'} until training is completed.
                            </span>
                        </div>
                        
                        <div class="modal-footer">
                            <button type="button" class="btn btn-outline" onclick="EmployeeManagement.closeModal('assignTrainingModal')">
                                Cancel
                            </button>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-graduation-cap"></i>
                                Assign to Training
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    `;
    
    // Append to body
    document.body.insertAdjacentHTML('beforeend', modalHtml);
},

// Submit training assignment
submitTrainingAssignment: async function() {
    const userId = document.getElementById('trainingUserId').value;
    const notes = document.getElementById('trainingNotes').value;
    
    if (!userId) {
        this.showNotification('User ID is missing', 'error');
        return;
    }
    
    try {
        const response = await fetch('../api/assign_training.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                user_id: userId,
                notes: notes
            })
        });
        
        const result = await response.json();
        
        if (result.success) {
            this.showNotification('Employee assigned to training successfully', 'success');
            this.closeModal('assignTrainingModal');
            // Reload employees to show updated status
            this.loadEmployees();
        } else {
            this.showNotification(result.error || 'Error assigning to training', 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        this.showNotification('Error connecting to server', 'error');
    }
},

// Add CSS for the new action button
addTrainingButtonStyle: function() {
    const style = document.createElement('style');
    style.textContent = `
        .action-btn.training {
            background: #fef3c7;
            color: #d97706;
        }
        .action-btn.training:hover {
            background: #fde68a;
            color: #b45309;
        }
    `;
    document.head.appendChild(style);
},

// Format role name for display
formatRole: function(role) {
    const roleMap = {
        'admin': 'Admin',
        'dispatcher': 'Dispatcher',
        'driver': 'Driver',
        'fleet_manager': 'Fleet Manager',
        'mechanic': 'Mechanic',
        'employee': 'Employee'
    };
    return roleMap[role] || role;
},

// Update getRoleIcon function
getRoleIcon: function(role) {
    const icons = {
        'admin': 'crown',
        'dispatcher': 'headset',
        'driver': 'truck',
        'fleet_manager': 'flag',
        'mechanic': 'wrench',
        'employee': 'user'
    };
    return icons[role] || 'user';
},

    // Get initials from name
    getInitials: function(name) {
        if (!name) return 'U';
        return name.split(' ').map(word => word[0]).join('').toUpperCase().substring(0, 2);
    },

    // Capitalize first letter
    capitalizeFirst: function(string) {
        if (!string) return '';
        return string.charAt(0).toUpperCase() + string.slice(1);
    },

    // Get Role Icon
    getRoleIcon: function(role) {
        const icons = {
            'admin': 'crown',
            'dispatcher': 'headset',
            'driver': 'truck',
            'fleet_manager': 'flag',
            'mechanic': 'wrench',
            'employee': 'user'
        };
        return icons[role] || 'user';
    },

    // Get Status Icon
    getStatusIcon: function(status) {
        const icons = {
            'active': 'check-circle',
            'inactive': 'times-circle',
            'on-leave': 'clock'
        };
        return icons[status] || 'circle';
    },

    // Update Pagination
    updatePagination: function() {
        const totalPages = Math.ceil(this.totalItems / this.itemsPerPage);
        const start = (this.currentPage - 1) * this.itemsPerPage + 1;
        const end = Math.min(this.currentPage * this.itemsPerPage, this.totalItems);

        // Update info
        const infoElement = document.querySelector('.pagination-info');
        if (infoElement) {
            infoElement.textContent = `Showing ${start} to ${end} of ${this.totalItems} employees`;
        }

        // Update page buttons
        const controls = document.querySelector('.pagination-controls');
        if (!controls) return;

        let html = `
            <button class="page-btn" onclick="EmployeeManagement.changePage(${this.currentPage - 1})" 
                ${this.currentPage === 1 ? 'disabled' : ''}>
                <i class="fas fa-chevron-left"></i>
            </button>
        `;

        for (let i = 1; i <= totalPages; i++) {
            if (i === 1 || i === totalPages || (i >= this.currentPage - 1 && i <= this.currentPage + 1)) {
                html += `
                    <button class="page-btn ${i === this.currentPage ? 'active' : ''}" 
                        onclick="EmployeeManagement.changePage(${i})">
                        ${i}
                    </button>
                `;
            } else if (i === this.currentPage - 2 || i === this.currentPage + 2) {
                html += `<span class="page-ellipsis">...</span>`;
            }
        }

        html += `
            <button class="page-btn" onclick="EmployeeManagement.changePage(${this.currentPage + 1})"
                ${this.currentPage === totalPages ? 'disabled' : ''}>
                <i class="fas fa-chevron-right"></i>
            </button>
        `;

        controls.innerHTML = html;
    },

    // Change Page
    changePage: function(page) {
        if (page < 1) return;
        this.currentPage = page;
        this.loadEmployees();
    },

    // Open Add Modal
    openAddModal: function() {
        this.currentEmployee = null;
        document.getElementById('modalTitle').innerHTML = '<i class="fas fa-user-plus"></i> Add New Employee';
        document.getElementById('employeeForm').reset();
        document.getElementById('empPassword').required = true;
        document.getElementById('avatarPreview').textContent = 'JS';
        document.getElementById('employeeModal').classList.remove('modal-hidden');
    },

    // Open Edit Modal
    openEditModal: async function(id) {
        try {
            const response = await fetch(`../api/employees.php?id=${id}`);
            const result = await response.json();

            if (result.success) {
                this.currentEmployee = result.data;
                document.getElementById('modalTitle').innerHTML = '<i class="fas fa-user-edit"></i> Edit Employee';
                
                // Fill form
                document.getElementById('empName').value = result.data.full_name || '';
                document.getElementById('empEmail').value = result.data.email || '';
                document.getElementById('empPhone').value = result.data.phone || '';
                document.getElementById('empRole').value = result.data.role || '';
                document.getElementById('empStatus').value = result.data.status || 'active';
                document.getElementById('empDepartment').value = result.data.department || '';
                document.getElementById('empPassword').value = '';
                document.getElementById('empPassword').required = false;
                
                // Update avatar preview
                document.getElementById('avatarPreview').textContent = this.getInitials(result.data.full_name);
                
                document.getElementById('employeeModal').classList.remove('modal-hidden');
            } else {
                this.showNotification('Error loading employee data', 'error');
            }
        } catch (error) {
            console.error('Error loading employee:', error);
            this.showNotification('Error loading employee data', 'error');
        }
    },

    // Open View Modal
    // Open View Modal
openViewModal: async function(id) {
    try {
        const response = await fetch(`../api/employees.php?id=${id}`);
        const result = await response.json();

        if (result.success) {
            const emp = result.data;
            
            document.getElementById('viewAvatar').textContent = this.getInitials(emp.full_name);
            document.getElementById('viewName').textContent = emp.full_name || 'N/A';
            document.getElementById('viewEmail').textContent = emp.email || 'N/A';
            document.getElementById('viewRole').innerHTML = `
                <span class="role-badge role-${emp.role}">
                    <i class="fas fa-${this.getRoleIcon(emp.role)}"></i>
                    ${this.formatRole(emp.role)}
                </span>
            `;
            document.getElementById('viewId').innerHTML = `
                <i class="fas fa-id-card"></i> ${emp.employee_id || 'N/A'}
            `;
            document.getElementById('viewStatus').innerHTML = `
                <span class="status-badge status-${emp.status}">
                    <i class="fas fa-${this.getStatusIcon(emp.status)}"></i>
                    ${emp.status ? emp.status.replace('-', ' ').toUpperCase() : 'UNKNOWN'}
                </span>
            `;
            document.getElementById('viewPhone').innerHTML = `<i class="fas fa-phone"></i> ${emp.phone || 'Not provided'}`;
            document.getElementById('viewDepartment').innerHTML = `<i class="fas fa-building"></i> ${emp.department || 'Not assigned'}`;
            document.getElementById('viewJoinDate').innerHTML = `<i class="fas fa-calendar-plus"></i> ${emp.join_date ? new Date(emp.join_date).toLocaleDateString() : 'Not available'}`;
            document.getElementById('viewLastActive').innerHTML = `<i class="fas fa-clock"></i> ${emp.last_login ? new Date(emp.last_login).toLocaleString() : 'Never'}`;

            document.getElementById('viewModal').classList.remove('modal-hidden');
        } else {
            this.showNotification('Error loading employee data', 'error');
        }
    } catch (error) {
        console.error('Error loading employee:', error);
        this.showNotification('Error loading employee data', 'error');
    }
},
    // Open Delete Modal
    openDeleteModal: function(id, name) {
        this.deleteId = id;
        document.getElementById('deleteName').textContent = name;
        document.getElementById('deleteModal').classList.remove('modal-hidden');
    },

    // Close Modal
    closeModal: function(modalId) {
        document.getElementById(modalId).classList.add('modal-hidden');
    },

    // Save Employee (Add/Edit)
    saveEmployee: async function() {
        // Get form data
        const employeeData = {
            full_name: document.getElementById('empName').value,
            email: document.getElementById('empEmail').value,
            phone: document.getElementById('empPhone').value,
            role: document.getElementById('empRole').value,
            status: document.getElementById('empStatus').value,
            department: document.getElementById('empDepartment').value
        };

        // Add password if provided
        const password = document.getElementById('empPassword').value;
        if (password) {
            employeeData.password = password;
        } else if (!this.currentEmployee) {
            // Password required for new employees
            this.showNotification('Password is required for new employees', 'error');
            return;
        }

        // Validate
        if (!this.validateEmployee(employeeData)) {
            return;
        }

        try {
            let url = '../api/employees.php';
            let method = 'POST';

            if (this.currentEmployee) {
                // Update existing
                url += `?id=${this.currentEmployee.id}`;
                method = 'PUT';
            }

            const response = await fetch(url, {
                method: method,
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(employeeData)
            });

            const result = await response.json();

            if (result.success) {
                this.closeModal('employeeModal');
                this.loadEmployees();
                this.loadStatistics();
                this.showNotification(
                    `Employee ${this.currentEmployee ? 'updated' : 'created'} successfully`, 
                    'success'
                );
            } else {
                this.showNotification(result.error || 'Error saving employee', 'error');
            }
        } catch (error) {
            console.error('Error saving employee:', error);
            this.showNotification('Error connecting to server', 'error');
        }
    },

    // Validate Employee Form
    validateEmployee: function(data) {
        // Name validation
        if (!data.full_name || data.full_name.trim().length < 2) {
            this.showNotification('Please enter a valid name', 'error');
            return false;
        }

        // Email validation
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailRegex.test(data.email)) {
            this.showNotification('Please enter a valid email address', 'error');
            return false;
        }

        // Phone validation
        const phoneRegex = /^[\d\s\-+()]{10,}$/;
        if (!phoneRegex.test(data.phone)) {
            this.showNotification('Please enter a valid phone number', 'error');
            return false;
        }

        // Role validation
        if (!data.role) {
            this.showNotification('Please select a role', 'error');
            return false;
        }

        // Department validation
        if (!data.department) {
            this.showNotification('Please enter a department', 'error');
            return false;
        }

        return true;
    },

    // Confirm Delete
    confirmDelete: async function() {
        if (!this.deleteId) return;

        try {
            const response = await fetch(`../api/employees.php?id=${this.deleteId}`, {
                method: 'DELETE'
            });

            const result = await response.json();

            if (result.success) {
                this.closeModal('deleteModal');
                this.loadEmployees();
                this.loadStatistics();
                this.showNotification('Employee deleted successfully', 'success');
            } else {
                this.showNotification(result.error || 'Error deleting employee', 'error');
            }
        } catch (error) {
            console.error('Error deleting employee:', error);
            this.showNotification('Error connecting to server', 'error');
        }
    },

    // Export Employees
    exportEmployees: function() {
        if (this.employees.length === 0) {
            this.showNotification('No employees to export', 'warning');
            return;
        }

        // Create CSV
        const headers = ['Employee ID', 'Name', 'Email', 'Phone', 'Role', 'Status', 'Department', 'Join Date'];
        const csvData = this.employees.map(emp => [
            emp.employee_id,
            emp.full_name,
            emp.email,
            emp.phone,
            emp.role,
            emp.status,
            emp.department,
            emp.join_date
        ]);

        const csv = [headers, ...csvData].map(row => row.join(',')).join('\n');
        
        // Download
        const blob = new Blob([csv], { type: 'text/csv' });
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `employees_${new Date().toISOString().split('T')[0]}.csv`;
        a.click();
        
        this.showNotification('Employees exported successfully', 'success');
    },

    // Show Notification
    showNotification: function(message, type = 'info') {
        const toastContainer = document.getElementById('toastContainer');
        if (!toastContainer) return;

        const toast = document.createElement('div');
        toast.className = `toast ${type}`;
        
        const icon = type === 'success' ? 'check-circle' : 
                    type === 'error' ? 'exclamation-circle' : 
                    type === 'warning' ? 'exclamation-triangle' : 'info-circle';
        
        toast.innerHTML = `
            <i class="fas fa-${icon}"></i>
            <span>${message}</span>
            <i class="fas fa-times toast-close" onclick="this.parentElement.remove()"></i>
        `;

        toastContainer.appendChild(toast);

        setTimeout(() => {
            if (toast.parentElement) {
                toast.remove();
            }
        }, 5000);
    }
};

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    EmployeeManagement.init();
});

// Debounce helper
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}
function showToast(message, type = 'success') {
    const toast = document.createElement('div');
    toast.className = `toast toast-${type}`;
    toast.textContent = message;
    
    document.getElementById('toastContainer').appendChild(toast);
    
    // Auto-remove after 3 seconds
    setTimeout(() => {
        toast.remove();
    }, 3000);
}