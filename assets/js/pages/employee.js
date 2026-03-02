// assets/js/employees.js

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

    // Initialize
    init: function() {
        console.log('Employee management initialized');
        this.loadEmployees();
        this.setupEventListeners();
        this.updateStats();
    },

    // Setup Event Listeners
    setupEventListeners: function() {
        // Filter selects
        document.getElementById('filterRole')?.addEventListener('change', (e) => {
            this.filters.role = e.target.value;
            this.currentPage = 1;
            this.filterEmployees();
        });

        document.getElementById('filterStatus')?.addEventListener('change', (e) => {
            this.filters.status = e.target.value;
            this.currentPage = 1;
            this.filterEmployees();
        });

        // Search
        const searchInput = document.getElementById('searchEmployee');
        if (searchInput) {
            searchInput.addEventListener('input', debounce((e) => {
                this.filters.search = e.target.value.toLowerCase();
                this.currentPage = 1;
                this.filterEmployees();
            }, 300));
        }

        // Refresh button
        document.getElementById('refreshData')?.addEventListener('click', () => {
            this.loadEmployees();
            this.showNotification('Data refreshed', 'success');
        });

        // Export button
        document.getElementById('exportData')?.addEventListener('click', () => {
            this.exportEmployees();
        });
    },

    // Load Employees (Mock Data)
    loadEmployees: function() {
        // Mock data - in real app, this would come from API
        this.employees = [
            {
                id: 'EMP001',
                name: 'John Smith',
                email: 'john.smith@company.com',
                phone: '+1 234-567-8901',
                role: 'admin',
                status: 'active',
                department: 'Management',
                joinDate: '2023-01-15',
                lastActive: '2024-02-20 09:30 AM',
                avatar: 'JS'
            },
            {
                id: 'EMP002',
                name: 'Sarah Johnson',
                email: 'sarah.j@company.com',
                phone: '+1 234-567-8902',
                role: 'dispatcher',
                status: 'active',
                department: 'Operations',
                joinDate: '2023-03-20',
                lastActive: '2024-02-20 08:45 AM',
                avatar: 'SJ'
            },
            {
                id: 'EMP003',
                name: 'Mike Wilson',
                email: 'mike.w@company.com',
                phone: '+1 234-567-8903',
                role: 'driver',
                status: 'active',
                department: 'Transport',
                joinDate: '2023-06-10',
                lastActive: '2024-02-20 07:15 AM',
                avatar: 'MW'
            },
            {
                id: 'EMP004',
                name: 'Emily Davis',
                email: 'emily.d@company.com',
                phone: '+1 234-567-8904',
                role: 'employee',
                status: 'active',
                department: 'Warehouse',
                joinDate: '2023-08-05',
                lastActive: '2024-02-19 04:30 PM',
                avatar: 'ED'
            },
            {
                id: 'EMP005',
                name: 'Tom Brown',
                email: 'tom.b@company.com',
                phone: '+1 234-567-8905',
                role: 'driver',
                status: 'on-leave',
                department: 'Transport',
                joinDate: '2023-02-28',
                lastActive: '2024-02-18 02:15 PM',
                avatar: 'TB'
            },
            {
                id: 'EMP006',
                name: 'Lisa Anderson',
                email: 'lisa.a@company.com',
                phone: '+1 234-567-8906',
                role: 'dispatcher',
                status: 'active',
                department: 'Operations',
                joinDate: '2023-04-12',
                lastActive: '2024-02-20 10:00 AM',
                avatar: 'LA'
            },
            {
                id: 'EMP007',
                name: 'David Lee',
                email: 'david.l@company.com',
                phone: '+1 234-567-8907',
                role: 'employee',
                status: 'inactive',
                department: 'Warehouse',
                joinDate: '2023-07-19',
                lastActive: '2024-02-15 11:20 AM',
                avatar: 'DL'
            },
            {
                id: 'EMP008',
                name: 'Anna Martinez',
                email: 'anna.m@company.com',
                phone: '+1 234-567-8908',
                role: 'admin',
                status: 'active',
                department: 'Management',
                joinDate: '2023-09-01',
                lastActive: '2024-02-20 09:15 AM',
                avatar: 'AM'
            },
            {
                id: 'EMP009',
                name: 'James Taylor',
                email: 'james.t@company.com',
                phone: '+1 234-567-8909',
                role: 'driver',
                status: 'active',
                department: 'Transport',
                joinDate: '2023-10-10',
                lastActive: '2024-02-20 08:30 AM',
                avatar: 'JT'
            },
            {
                id: 'EMP010',
                name: 'Patricia White',
                email: 'patricia.w@company.com',
                phone: '+1 234-567-8910',
                role: 'employee',
                status: 'active',
                department: 'Warehouse',
                joinDate: '2023-11-15',
                lastActive: '2024-02-19 03:45 PM',
                avatar: 'PW'
            },
            {
                id: 'EMP011',
                name: 'Robert Chen',
                email: 'robert.c@company.com',
                phone: '+1 234-567-8911',
                role: 'dispatcher',
                status: 'on-leave',
                department: 'Operations',
                joinDate: '2023-05-22',
                lastActive: '2024-02-17 01:20 PM',
                avatar: 'RC'
            },
            {
                id: 'EMP012',
                name: 'Maria Garcia',
                email: 'maria.g@company.com',
                phone: '+1 234-567-8912',
                role: 'driver',
                status: 'active',
                department: 'Transport',
                joinDate: '2023-12-01',
                lastActive: '2024-02-20 07:45 AM',
                avatar: 'MG'
            }
        ];

        this.totalItems = this.employees.length;
        this.filterEmployees();
        this.updateStats();
    },

    // Filter Employees
    filterEmployees: function() {
        let filtered = [...this.employees];

        // Apply role filter
        if (this.filters.role !== 'all') {
            filtered = filtered.filter(emp => emp.role === this.filters.role);
        }

        // Apply status filter
        if (this.filters.status !== 'all') {
            filtered = filtered.filter(emp => emp.status === this.filters.status);
        }

        // Apply search filter
        if (this.filters.search) {
            filtered = filtered.filter(emp => 
                emp.name.toLowerCase().includes(this.filters.search) ||
                emp.email.toLowerCase().includes(this.filters.search) ||
                emp.id.toLowerCase().includes(this.filters.search) ||
                emp.department.toLowerCase().includes(this.filters.search)
            );
        }

        this.renderTable(filtered);
        this.updatePagination(filtered.length);
    },

    // Render Table
    renderTable: function(filteredEmployees) {
        const tbody = document.querySelector('#employeeTable tbody');
        if (!tbody) return;

        const start = (this.currentPage - 1) * this.itemsPerPage;
        const end = start + this.itemsPerPage;
        const pageEmployees = filteredEmployees.slice(start, end);

        if (pageEmployees.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="7">
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
        pageEmployees.forEach(emp => {
            html += `
                <tr>
                    <td>
                        <div class="user-info">
                            <div class="user-avatar">${emp.avatar}</div>
                            <div class="user-details">
                                <span class="user-name">${emp.name}</span>
                                <span class="user-email">${emp.email}</span>
                            </div>
                        </div>
                    </td>
                    <td>
                        <span class="role-badge role-${emp.role}">
                            <i class="fas fa-${this.getRoleIcon(emp.role)}"></i>
                            ${emp.role.charAt(0).toUpperCase() + emp.role.slice(1)}
                        </span>
                    </td>
                    <td>
                        <div class="contact-info">
                            <div class="contact-item">
                                <i class="fas fa-phone"></i>
                                ${emp.phone}
                            </div>
                            <div class="contact-item">
                                <i class="fas fa-building"></i>
                                ${emp.department}
                            </div>
                        </div>
                    </td>
                    <td>
                        <span class="status-badge status-${emp.status}">
                            <i class="fas fa-${this.getStatusIcon(emp.status)}"></i>
                            ${emp.status.replace('-', ' ').toUpperCase()}
                        </span>
                    </td>
                    <td>
                        <div class="action-group">
                            <button class="action-btn view" onclick="EmployeeManagement.openViewModal('${emp.id}')" title="View">
                                <i class="fas fa-eye"></i>
                            </button>
                            <button class="action-btn edit" onclick="EmployeeManagement.openEditModal('${emp.id}')" title="Edit">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button class="action-btn delete" onclick="EmployeeManagement.openDeleteModal('${emp.id}')" title="Delete">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </td>
                </tr>
            `;
        });

        tbody.innerHTML = html;
    },

    // Get Role Icon
    getRoleIcon: function(role) {
        const icons = {
            'admin': 'crown',
            'dispatcher': 'headset',
            'driver': 'truck',
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
    updatePagination: function(totalFiltered) {
        const totalPages = Math.ceil(totalFiltered / this.itemsPerPage);
        const start = (this.currentPage - 1) * this.itemsPerPage + 1;
        const end = Math.min(this.currentPage * this.itemsPerPage, totalFiltered);

        // Update info
        document.querySelector('.pagination-info').textContent = 
            `Showing ${start} to ${end} of ${totalFiltered} employees`;

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
                html += `<button class="page-btn" disabled>...</button>`;
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
        if (page < 1 || page > Math.ceil(this.totalItems / this.itemsPerPage)) return;
        this.currentPage = page;
        this.filterEmployees();
    },

    // Update Statistics
    updateStats: function() {
        const total = this.employees.length;
        const active = this.employees.filter(e => e.status === 'active').length;
        const drivers = this.employees.filter(e => e.role === 'driver').length;
        const dispatchers = this.employees.filter(e => e.role === 'dispatcher').length;

        document.querySelectorAll('.stat-value').forEach((el, index) => {
            if (index === 0) el.textContent = total;
            if (index === 1) el.textContent = active;
            if (index === 2) el.textContent = drivers;
            if (index === 3) el.textContent = dispatchers;
        });
    },

    // Open Add Modal
    openAddModal: function() {
        this.currentEmployee = null;
        document.getElementById('modalTitle').innerHTML = '<i class="fas fa-user-plus"></i> Add New Employee';
        document.getElementById('employeeForm').reset();
        document.getElementById('employeeModal').classList.remove('modal-hidden');
        
        // Set default password hint
        document.querySelector('.form-hint').textContent = 'Leave blank to auto-generate';
    },

    // Open Edit Modal
    openEditModal: function(empId) {
        const employee = this.employees.find(e => e.id === empId);
        if (!employee) return;

        this.currentEmployee = employee;
        document.getElementById('modalTitle').innerHTML = '<i class="fas fa-user-edit"></i> Edit Employee';
        
        // Fill form
        document.getElementById('empName').value = employee.name;
        document.getElementById('empEmail').value = employee.email;
        document.getElementById('empPhone').value = employee.phone;
        document.getElementById('empRole').value = employee.role;
        document.getElementById('empStatus').value = employee.status;
        document.getElementById('empDepartment').value = employee.department;
        
        document.getElementById('employeeModal').classList.remove('modal-hidden');
        
        // Update avatar preview
        document.getElementById('avatarPreview').textContent = employee.avatar;
    },

    // Open View Modal
    openViewModal: function(empId) {
        const employee = this.employees.find(e => e.id === empId);
        if (!employee) return;

        document.getElementById('viewAvatar').textContent = employee.avatar;
        document.getElementById('viewName').textContent = employee.name;
        document.getElementById('viewEmail').textContent = employee.email;
        document.getElementById('viewRole').innerHTML = `
            <span class="role-badge role-${employee.role}">
                <i class="fas fa-${this.getRoleIcon(employee.role)}"></i>
                ${employee.role.charAt(0).toUpperCase() + employee.role.slice(1)}
            </span>
        `;
        document.getElementById('viewStatus').innerHTML = `
            <span class="status-badge status-${employee.status}">
                <i class="fas fa-${this.getStatusIcon(employee.status)}"></i>
                ${employee.status.replace('-', ' ').toUpperCase()}
            </span>
        `;
        document.getElementById('viewPhone').innerHTML = `<i class="fas fa-phone"></i> ${employee.phone}`;
        document.getElementById('viewDepartment').innerHTML = `<i class="fas fa-building"></i> ${employee.department}`;
        document.getElementById('viewId').innerHTML = `<i class="fas fa-id-card"></i> ${employee.id}`;
        document.getElementById('viewJoinDate').innerHTML = `<i class="fas fa-calendar-plus"></i> ${employee.joinDate}`;
        document.getElementById('viewLastActive').innerHTML = `<i class="fas fa-clock"></i> ${employee.lastActive}`;

        document.getElementById('viewModal').classList.remove('modal-hidden');
    },

    // Open Delete Modal
    openDeleteModal: function(empId) {
        const employee = this.employees.find(e => e.id === empId);
        if (!employee) return;

        this.currentEmployee = employee;
        document.getElementById('deleteName').textContent = employee.name;
        document.getElementById('deleteRole').textContent = employee.role;
        document.getElementById('deleteModal').classList.remove('modal-hidden');
    },

    // Close Modal
    closeModal: function(modalId) {
        document.getElementById(modalId).classList.add('modal-hidden');
    },

    // Save Employee (Add/Edit)
    saveEmployee: function() {
        const form = document.getElementById('employeeForm');
        
        // Get form data
        const employeeData = {
            name: document.getElementById('empName').value,
            email: document.getElementById('empEmail').value,
            phone: document.getElementById('empPhone').value,
            role: document.getElementById('empRole').value,
            status: document.getElementById('empStatus').value,
            department: document.getElementById('empDepartment').value
        };

        // Validate
        if (!this.validateEmployee(employeeData)) {
            return;
        }

        if (this.currentEmployee) {
            // Update existing
            Object.assign(this.currentEmployee, employeeData);
            this.showNotification(`Employee ${employeeData.name} updated successfully`, 'success');
        } else {
            // Add new
            const newEmployee = {
                ...employeeData,
                id: 'EMP' + String(this.employees.length + 1).padStart(3, '0'),
                joinDate: new Date().toISOString().split('T')[0],
                lastActive: 'Just now',
                avatar: employeeData.name.split(' ').map(n => n[0]).join('')
            };
            this.employees.push(newEmployee);
            this.showNotification(`Employee ${employeeData.name} added successfully`, 'success');
        }

        this.closeModal('employeeModal');
        this.filterEmployees();
        this.updateStats();
    },

    // Validate Employee Form
    validateEmployee: function(data) {
        // Name validation
        if (!data.name || data.name.trim().length < 2) {
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

        return true;
    },

    // Confirm Delete
    confirmDelete: function() {
        if (this.currentEmployee) {
            const index = this.employees.findIndex(e => e.id === this.currentEmployee.id);
            if (index !== -1) {
                const name = this.employees[index].name;
                this.employees.splice(index, 1);
                this.showNotification(`Employee ${name} deleted successfully`, 'success');
            }
        }
        
        this.closeModal('deleteModal');
        this.filterEmployees();
        this.updateStats();
    },

    // Export Employees
    exportEmployees: function() {
        // Create CSV
        const headers = ['ID', 'Name', 'Email', 'Phone', 'Role', 'Status', 'Department', 'Join Date'];
        const csvData = this.employees.map(emp => [
            emp.id,
            emp.name,
            emp.email,
            emp.phone,
            emp.role,
            emp.status,
            emp.department,
            emp.joinDate
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
        const toast = document.createElement('div');
        toast.className = `toast ${type}`;
        toast.innerHTML = `
            <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-circle' : 'info-circle'}"></i>
            <span>${message}</span>
            <i class="fas fa-times toast-close" onclick="this.parentElement.remove()"></i>
        `;

        document.getElementById('toastContainer').appendChild(toast);

        setTimeout(() => {
            toast.remove();
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