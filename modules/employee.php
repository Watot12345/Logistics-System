<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}
$page_title = 'Employee Management | Logistics System';
$page_css = ['../assets/css/style.css', '../assets/css/employee.css'];
include '../includes/header.php';
?>

            <!-- Page Content -->
            <div class="page-content">
                <header class="header">
    <div class="header-container">
        
        <div class="header-left">
            <button class="menu-toggle" onclick="toggleSidebar()">
                <i class="fas fa-bars"></i>
            </button>

            <div class="search-container">
                <i class="fas fa-search search-icon"></i>
                <input type="text" class="search-input" placeholder="Search...">
            </div>
        </div>
        
        <div class="header-right">

            <button class="header-btn">
                <i class="fas fa-bell"></i>
                <span class="notification-badge"></span>
            </button>

            <button class="header-btn">
                <i class="fas fa-envelope"></i>
            </button>

            <div class="divider"></div>

            <div class="user-info-header">

                <!-- FULL NAME -->
                <span class="user-name-header">
                    <?php echo htmlspecialchars($_SESSION['full_name']); ?>
                </span>

                <!-- AVATAR INITIALS -->
                <div class="avatar-small">
                    <?php
                        $name = $_SESSION['full_name'];
                        $words = explode(" ", $name);
                        $initials = "";

                        foreach ($words as $word) {
                            $initials .= strtoupper(substr($word, 0, 1));
                        }

                        echo $initials;
                    ?>
                </div>

            </div>

        </div>

    </div>
</header>       
                <!-- Page Header -->
                <div class="page-header">
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <div>
                            <h1>Employee Management</h1>
                            <p>Manage users, roles, and permissions</p>
                        </div>
                        <div class="header-actions">
                            <button class="btn btn-outline" id="exportData">
                                <i class="fas fa-download"></i>
                                Export
                            </button>
                            <button class="btn btn-outline" id="refreshData">
                                <i class="fas fa-sync-alt"></i>
                                Refresh
                            </button>
                            <button class="btn btn-primary" onclick="EmployeeManagement.openAddModal()">
                                <i class="fas fa-user-plus"></i>
                                Add Employee
                            </button>
                        </div>
                    </div>
                </div>
                
                <!-- Statistics Cards -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-header">
                            <div class="stat-icon blue">
                                <i class="fas fa-users"></i>
                            </div>
                            <span class="stat-badge green">Total</span>
                        </div>
                        <p class="stat-label">Total Employees</p>
                        <p class="stat-value">0</p>
                        <div class="stat-trend">
                            <i class="fas fa-arrow-up"></i> +2 this month
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-header">
                            <div class="stat-icon emerald">
                                <i class="fas fa-check-circle"></i>
                            </div>
                            <span class="stat-badge green">Active</span>
                        </div>
                        <p class="stat-label">Active Employees</p>
                        <p class="stat-value">0</p>
                        <div class="stat-trend">
                            <i class="fas fa-check"></i> 85% of workforce
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-header">
                            <div class="stat-icon amber">
                                <i class="fas fa-truck"></i>
                            </div>
                            <span class="stat-badge blue">Drivers</span>
                        </div>
                        <p class="stat-label">Active Drivers</p>
                        <p class="stat-value">0</p>
                        <div class="stat-trend">
                            <i class="fas fa-arrow-up"></i> 3 on road
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-header">
                            <div class="stat-icon purple">
                                <i class="fas fa-headset"></i>
                            </div>
                            <span class="stat-badge amber">Dispatchers</span>
                        </div>
                        <p class="stat-label">Dispatchers</p>
                        <p class="stat-value">0</p>
                        <div class="stat-trend">
                            <i class="fas fa-clock"></i> 2 on duty
                        </div>
                    </div>
                </div>
                
                <!-- Filter Bar -->
                <div class="filter-bar">
                    <div class="filter-group">
                        <select class="filter-select" id="filterRole">
                            <option value="all">All Roles</option>
                            <option value="admin">Admin</option>
                            <option value="dispatcher">Dispatcher</option>
                            <option value="driver">Driver</option>
                            <option value="employee">Employee</option>
                        </select>
                        <select class="filter-select" id="filterStatus">
                            <option value="all">All Status</option>
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                            <option value="on-leave">On Leave</option>
                        </select>
                    </div>
                    <div class="search-box">
                        <i class="fas fa-search"></i>
                        <input type="text" id="searchEmployee" placeholder="Search by name, email, or department...">
                    </div>
                </div>
                
                <!-- Employees Table -->
                <div class="table-container">
                    <table class="table" id="employeeTable">
                        <thead>
                            <tr>
                                <th>Employee</th>
                                <th>Role</th>
                                <th>Contact</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Dynamic content loaded via JavaScript -->
                        </tbody>
                    </table>
                    
                    <!-- Pagination -->
                    <div class="pagination">
                        <p class="pagination-info">Showing 0 to 0 of 0 employees</p>
                        <div class="pagination-controls">
                            <!-- Dynamic pagination -->
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
    
    <!-- Add/Edit Employee Modal -->
    <div id="employeeModal" class="modal modal-hidden">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title" id="modalTitle">
                    <i class="fas fa-user-plus"></i>
                    Add New Employee
                </h3>
                <button class="modal-close" onclick="EmployeeManagement.closeModal('employeeModal')">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <form id="employeeForm" onsubmit="event.preventDefault(); EmployeeManagement.saveEmployee();">
                <!-- Avatar Preview -->
                <div class="avatar-upload">
                    <div class="avatar-preview" id="avatarPreview">JS</div>
                    <div>
                        <button type="button" class="avatar-upload-btn">
                            <i class="fas fa-camera"></i>
                            Change Avatar
                        </button>
                        <p class="form-hint">Recommended: 400x400px JPG or PNG</p>
                    </div>
                </div>
                
                <div class="form-grid">
                    <div class="form-group full-width">
                        <label class="form-label">
                            <i class="fas fa-user"></i>
                            Full Name
                        </label>
                        <input type="text" class="form-input" id="empName" placeholder="Enter full name" required>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">
                            <i class="fas fa-envelope"></i>
                            Email Address
                        </label>
                        <input type="email" class="form-input" id="empEmail" placeholder="email@company.com" required>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">
                            <i class="fas fa-phone"></i>
                            Phone Number
                        </label>
                        <input type="tel" class="form-input" id="empPhone" placeholder="+1 234-567-8900" required>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">
                            <i class="fas fa-briefcase"></i>
                            Role
                        </label>
                        <select class="form-select" id="empRole" required>
                            <option value="">Select Role</option>
                            <option value="admin">Admin</option>
                            <option value="dispatcher">Dispatcher</option>
                            <option value="driver">Driver</option>
                            <option value="employee">Employee</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">
                            <i class="fas fa-circle"></i>
                            Status
                        </label>
                        <select class="form-select" id="empStatus" required>
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                            <option value="on-leave">On Leave</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">
                            <i class="fas fa-building"></i>
                            Department
                        </label>
                        <input type="text" class="form-input" id="empDepartment" placeholder="Department" required>
                    </div>
                    
                    <div class="form-group full-width">
                        <label class="form-label">
                            <i class="fas fa-lock"></i>
                            Password
                        </label>
                        <input type="password" class="form-input" id="empPassword" placeholder="Enter password">
                        <p class="form-hint">Leave blank to keep current password (when editing)</p>
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline" onclick="EmployeeManagement.closeModal('employeeModal')">
                        Cancel
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i>
                        Save Employee
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- View Employee Modal -->
    <div id="viewModal" class="modal modal-hidden">
        <div class="modal-content view-modal">
            <div class="modal-header">
                <h3 class="modal-title">
                    <i class="fas fa-user-circle"></i>
                    Employee Profile
                </h3>
                <button class="modal-close" onclick="EmployeeManagement.closeModal('viewModal')">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <div class="profile-header">
                <div class="profile-avatar" id="viewAvatar">JD</div>
                <div class="profile-info">
                    <h2 id="viewName">John Doe</h2>
                    <p id="viewEmail">john.doe@company.com</p>
                    <div class="profile-badges" id="viewRole"></div>
                </div>
            </div>
            
            <div class="profile-detail-row">
                <div class="profile-detail-label">Status</div>
                <div class="profile-detail-value" id="viewStatus"></div>
            </div>
            
            <div class="profile-detail-row">
                <div class="profile-detail-label">Contact</div>
                <div class="profile-detail-value" id="viewPhone"></div>
            </div>
            
            <div class="profile-detail-row">
                <div class="profile-detail-label">Department</div>
                <div class="profile-detail-value" id="viewDepartment"></div>
            </div>
            
            <div class="profile-detail-row">
                <div class="profile-detail-label">Employee ID</div>
                <div class="profile-detail-value" id="viewId"></div>
            </div>
            
            <div class="profile-detail-row">
                <div class="profile-detail-label">Join Date</div>
                <div class="profile-detail-value" id="viewJoinDate"></div>
            </div>
            
            <div class="profile-detail-row">
                <div class="profile-detail-label">Last Active</div>
                <div class="profile-detail-value" id="viewLastActive"></div>
            </div>
            
            <div class="activity-card">
                <div class="activity-title">
                    <i class="fas fa-clock"></i>
                    Recent Activity
                </div>
                <div class="activity-list">
                    <div class="activity-item">
                        <i class="fas fa-check-circle" style="color: #10b981;"></i>
                        <span>Completed trip #TR-2024-001</span>
                        <span class="time">2 hours ago</span>
                    </div>
                    <div class="activity-item">
                        <i class="fas fa-truck" style="color: #2563eb;"></i>
                        <span>Started shift - Morning</span>
                        <span class="time">8 hours ago</span>
                    </div>
                    <div class="activity-item">
                        <i class="fas fa-clock" style="color: #f59e0b;"></i>
                        <span>Vehicle inspection completed</span>
                        <span class="time">Yesterday</span>
                    </div>
                </div>
            </div>
            
            <div class="modal-footer">
                <button class="btn btn-outline" onclick="EmployeeManagement.closeModal('viewModal')">
                    Close
                </button>
                <button class="btn btn-primary" onclick="EmployeeManagement.closeModal('viewModal'); EmployeeManagement.openEditModal(document.getElementById('viewId').textContent.split(' ')[1])">
                    <i class="fas fa-edit"></i>
                    Edit Profile
                </button>
            </div>
        </div>
    </div>
    
    <!-- Delete Confirmation Modal -->
    <div id="deleteModal" class="modal modal-hidden">
        <div class="modal-content delete-modal">
            <div class="delete-icon">
                <i class="fas fa-exclamation-triangle"></i>
            </div>
            <h3 class="delete-title">Delete Employee</h3>
            <p class="delete-text">
                Are you sure you want to delete <strong id="deleteName">John Smith</strong>?
                <br>
                Role: <span id="deleteRole">Admin</span>
            </p>
            <div class="delete-warning">
                <i class="fas fa-exclamation-circle"></i>
                This action cannot be undone. All associated data will be permanently removed.
            </div>
            <div class="delete-actions">
                <button class="btn btn-outline" onclick="EmployeeManagement.closeModal('deleteModal')">
                    Cancel
                </button>
                <button class="btn btn-danger" onclick="EmployeeManagement.confirmDelete()">
                    <i class="fas fa-trash"></i>
                    Delete Employee
                </button>
            </div>
        </div>
    </div>
    
    <!-- Toast Notifications Container -->
    <div id="toastContainer" class="toast-container"></div>
    
    <script src="../assets/js/pages/employee.js"></script>
</body>
</html>