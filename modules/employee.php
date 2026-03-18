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
                        <div class="header-left"></div>
                        <div class="header-right">
                            <div class="divider"></div>
                            <div class="user-info-header">
                                <span class="user-name-header">
                                    <?php echo htmlspecialchars($_SESSION['full_name']); ?>
                                </span>
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

                <!-- Page Header with Tabs -->
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
                            <button class="btn btn-primary" onclick="EmployeeManagement.openAddModal()">
                                <i class="fas fa-user-plus"></i>
                                Add Employee
                            </button>
                        </div>
                    </div>
                    
                    <!-- Tabs Navigation -->
                    <div class="admin-tabs" style="margin-top: 20px; border-bottom: 2px solid #e5e7eb;">
                        <button class="tab-btn active" onclick="switchTab('employees')" id="tabEmployees">
                            <i class="fas fa-users"></i>
                            Employees
                        </button>
                        <button class="tab-btn" onclick="switchTab('logistics')" id="tabLogistics">
                            <i class="fas fa-truck"></i>
                            Logistics Portal
                        </button>
                    </div>
                </div>
                <!-- EMPLOYEES TAB (Your existing admin panel) -->
                <div id="employeesTab" class="tab-content">
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
                            <div class="stat-trend" id="totalTrend">
                                <i class="fas fa-arrow-up"></i> <span>0</span> this month
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
                            <div class="stat-trend" id="activeTrend">
                                <i class="fas fa-check"></i> <span>0</span>% of workforce
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
                            <div class="stat-trend" id="driversTrend">
                                <i class="fas fa-truck"></i> <span class="onroad-count">0</span> on road
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
                            <div class="stat-trend" id="dispatchersTrend">
                                <i class="fas fa-clock"></i> <span class="onduty-count">0</span> on duty
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
                                <option value="fleet_manager">Fleet Manager</option>
                                <option value="mechanic">Mechanic</option>
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
                            <input type="text" id="searchEmployee" placeholder="Search by name, email, or ID...">
                        </div>
                    </div>
                    
                    <!-- Employees Table -->
                    <div class="table-container">
                        <table class="table" id="employeeTable">
                            <thead>
                                <tr>
                                    <th>Employee</th>
                                    <th>Role</th>
                                    <th>Employee ID</th>
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

                <!-- LOGISTICS PORTAL TAB (Fixed) -->
                <div id="logisticsTab" class="tab-content" style="display: none;">
                    <div class="logistics-portal">
                        <!-- Header -->
                        <div class="logistics-header" style="background: linear-gradient(135deg, #fbbf24 0%, #d97706 100%); padding: 20px; border-radius: 10px; margin-bottom: 20px; color: white;">
                            <h2 style="margin: 0;"><i class="fas fa-truck"></i> Logistics Department Portal</h2>
                            <p style="margin: 5px 0 0; opacity: 0.9;">Submit and track employee requisitions to HR</p>
                        </div>

                        <!-- Two Column Layout -->
                        <div class="grid" style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                            <!-- Left Column: Submit Requisition -->
                            <div class="card" style="background: white; border-radius: 10px; box-shadow: 0 2px 5px rgba(0,0,0,0.1);">
                                <div class="card-header" style="padding: 15px; border-bottom: 1px solid #e5e7eb;">
                                    <h3 style="margin: 0;"><i class="fas fa-paper-plane" style="color: #fbbf24;"></i> Submit Requisition to HR</h3>
                                </div>
                                <div class="card-body" style="padding: 15px;">
                                    <form id="logisticsRequisitionForm">
                                        <div class="form-group" style="margin-bottom: 15px;">
                                            <label class="form-label">Job Title</label>
                                            <input type="text" id="reqJobTitle" class="form-input" value="Logistics Coordinator" required>
                                        </div>
                                        
                                        <div class="form-group" style="margin-bottom: 15px;">
                                            <label class="form-label">Department</label>
                                            <select id="reqDepartment" class="form-select" required>
                                                <option value="Logistics">Logistics</option>
                                                <option value="Warehouse">Warehouse</option>
                                                <option value="Transportation">Transportation</option>
                                                <option value="Supply Chain">Supply Chain</option>
                                                <option value="Inventory">Inventory</option>
                                            </select>
                                        </div>
                                        
                                        <div class="form-group" style="margin-bottom: 15px;">
                                            <label class="form-label">Requested By</label>
                                            <input type="text" id="reqRequestedBy" class="form-input" value="<?php echo htmlspecialchars($_SESSION['full_name']); ?>" required>
                                        </div>
                                        
                                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-bottom: 15px;">
                                            <div class="form-group">
                                                <label class="form-label">Positions</label>
                                                <input type="number" id="reqPositions" class="form-input" value="1" min="1" required>
                                            </div>
                                            <div class="form-group">
                                                <label class="form-label">Needed By</label>
                                                <input type="date" id="reqNeededBy" class="form-input" required>
                                            </div>
                                        </div>
                                        
                                        <div class="form-group" style="margin-bottom: 15px;">
                                            <label class="form-label">Priority</label>
                                            <select id="reqPriority" class="form-select">
                                                <option value="low">Low</option>
                                                <option value="medium" selected>Medium</option>
                                                <option value="high">High</option>
                                                <option value="urgent">Urgent</option>
                                            </select>
                                        </div>
                                        
                                        <div class="form-group" style="margin-bottom: 15px;">
                                            <label class="form-label">Justification</label>
                                            <textarea id="reqJustification" class="form-input" rows="2" placeholder="Why is this position needed?"></textarea>
                                        </div>
                                        
                                        <button type="submit" class="btn btn-primary" style="width: 100%;">
                                            <i class="fas fa-paper-plane"></i> Submit to HR
                                        </button>
                                    </form>
                                </div>
                            </div>

                            <!-- Right Column: Recent Requisitions -->
                            <div class="card" style="background: white; border-radius: 10px; box-shadow: 0 2px 5px rgba(0,0,0,0.1);">
                                <div class="card-header" style="padding: 15px; border-bottom: 1px solid #e5e7eb;">
                                    <h3 style="margin: 0;"><i class="fas fa-clipboard-list" style="color: #fbbf24;"></i> Recent Requisitions</h3>
                                </div>
                                <div class="card-body" style="padding: 15px;">
                                    <div id="requisitionsList" style="max-height: 500px; overflow-y: auto;">
                                        <!-- Requisitions will appear here -->
                                    </div>
                                </div>
                            </div>
                        </div>

                       
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- Add JavaScript for tabs and API calls -->
    <script>
    const API_BASE = 'https://humanresource.up.railway.app/api';
    const API_KEY = 'logistic_system_2026_key_98765';

    // Tab switching function - FIXED: Removed fetchLogisticsEmployees
    function switchTab(tab) {
        // Update tab buttons
        document.getElementById('tabEmployees').classList.remove('active');
        document.getElementById('tabLogistics').classList.remove('active');
        
        // Hide all tabs
        document.getElementById('employeesTab').style.display = 'none';
        document.getElementById('logisticsTab').style.display = 'none';
        
        // Show selected tab
        if (tab === 'employees') {
            document.getElementById('tabEmployees').classList.add('active');
            document.getElementById('employeesTab').style.display = 'block';
        } else if (tab === 'logistics') {
            document.getElementById('tabLogistics').classList.add('active');
            document.getElementById('logisticsTab').style.display = 'block';
            // Load requisitions when switching to logistics tab
            fetchRequisitions();
        }
    }

    // Submit requisition to HR
    document.getElementById('logisticsRequisitionForm').addEventListener('submit', async function(e) {
        e.preventDefault();
        
        const formData = {
            job_title: document.getElementById('reqJobTitle').value,
            department: document.getElementById('reqDepartment').value,
            requested_by: document.getElementById('reqRequestedBy').value,
            positions: parseInt(document.getElementById('reqPositions').value),
            needed_by: document.getElementById('reqNeededBy').value,
            priority: document.getElementById('reqPriority').value,
            justification: document.getElementById('reqJustification').value
        };
        
        const submitBtn = e.target.querySelector('button[type="submit"]');
        const originalText = submitBtn.innerHTML;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Submitting...';
        submitBtn.disabled = true;
        
        updateApiResponse('⏳ Submitting requisition to HR...');
        
        try {
            const response = await fetch(`${API_BASE}/job-requisition.php?api_key=${API_KEY}`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(formData)
            });
            
            const data = await response.json();
            updateApiResponse(JSON.stringify(data, null, 2));
            
            if (data.success) {
                showToast('✅ Requisition submitted successfully! ID: ' + data.data.id);
                // Reset form
                e.target.reset();
                document.getElementById('reqJobTitle').value = 'Logistics Coordinator';
                document.getElementById('reqPositions').value = '1';
                document.getElementById('reqRequestedBy').value = '<?php echo htmlspecialchars($_SESSION['full_name']); ?>';
                document.getElementById('reqNeededBy').valueAsDate = new Date(new Date().setMonth(new Date().getMonth() + 1));
                // Refresh requisitions list
                fetchRequisitions();
            } else {
                showToast('❌ Error: ' + data.error, 'error');
            }
        } catch (error) {
            updateApiResponse(`Error: ${error.message}`);
            showToast('❌ Error: ' + error.message, 'error');
        } finally {
            submitBtn.innerHTML = originalText;
            submitBtn.disabled = false;
        }
    });

    // Fetch recent requisitions
    async function fetchRequisitions() {
        const listDiv = document.getElementById('requisitionsList');
        
        listDiv.innerHTML = '<p style="text-align: center; padding: 20px;"><i class="fas fa-spinner fa-spin"></i> Loading requisitions...</p>';
        
        try {
            const response = await fetch(`${API_BASE}/job-requisition.php?api_key=${API_KEY}`);
            const data = await response.json();
            
            updateApiResponse(JSON.stringify(data, null, 2));

            if (data.success && data.data?.length > 0) {
                // Filter for logistics department
                const logisticsReqs = data.data.filter(req => 
                    ['Logistics', 'Warehouse', 'Transportation', 'Supply Chain', 'Inventory'].includes(req.department)
                ).slice(0, 10); // Show last 10
                
                if (logisticsReqs.length > 0) {
                    let html = '';
                    logisticsReqs.forEach(req => {
                        const statusColor = req.status === 'approved' ? '#10b981' : 
                                           req.status === 'pending' ? '#f59e0b' : 
                                           req.status === 'rejected' ? '#ef4444' : '#3b82f6';
                        const priorityColor = req.priority === 'high' ? '#ef4444' : 
                                            req.priority === 'medium' ? '#f59e0b' : '#10b981';
                        
                        html += `
                            <div style="background: #f9fafb; padding: 15px; margin-bottom: 10px; border-radius: 8px; border-left: 4px solid #fbbf24; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
                                <div style="display: flex; justify-content: space-between; align-items: center;">
                                    <div style="flex: 1;">
                                        <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 5px;">
                                            <strong style="font-size: 16px;">${req.job_title}</strong>
                                            <span style="background: ${statusColor}; color: white; padding: 2px 10px; border-radius: 20px; font-size: 11px; font-weight: 500;">
                                                ${req.status}
                                            </span>
                                        </div>
                                        <div style="color: #4b5563; font-size: 13px; margin-bottom: 5px;">
                                            <i class="fas fa-building"></i> ${req.department} • 
                                            <i class="fas fa-user"></i> ${req.requested_by}
                                        </div>
                                        <div style="display: flex; gap: 15px; font-size: 12px; color: #6b7280; flex-wrap: wrap;">
                                            <span><i class="fas fa-users"></i> ${req.positions} position${req.positions > 1 ? 's' : ''}</span>
                                            <span><i class="fas fa-calendar"></i> Needed: ${req.needed_by}</span>
                                            <span style="color: ${priorityColor};"><i class="fas fa-flag"></i> ${req.priority}</span>
                                        </div>
                                        ${req.justification ? `<div style="margin-top: 8px; font-size: 12px; color: #6b7280; background: #f3f4f6; padding: 8px; border-radius: 4px;"><i class="fas fa-comment"></i> ${req.justification}</div>` : ''}
                                    </div>
                                </div>
                            </div>
                        `;
                    });
                    listDiv.innerHTML = html;
                } else {
                    listDiv.innerHTML = '<p style="text-align: center; padding: 30px; color: #666;"><i class="fas fa-inbox fa-2x" style="display: block; margin-bottom: 10px;"></i>No requisitions from logistics departments</p>';
                }
            } else {
                listDiv.innerHTML = '<p style="text-align: center; padding: 30px; color: #666;"><i class="fas fa-inbox fa-2x" style="display: block; margin-bottom: 10px;"></i>No requisitions found</p>';
            }
        } catch (error) {
            listDiv.innerHTML = `<p style="text-align: center; padding: 20px; color: #ef4444;"><i class="fas fa-exclamation-circle"></i> Error loading requisitions: ${error.message}</p>`;
            updateApiResponse(`Error: ${error.message}`);
        }
    }

    // Update API response display
    function updateApiResponse(content) {
        const apiResponse = document.getElementById('apiResponse');
        if (apiResponse) {
            apiResponse.innerHTML = content;
        }
    }

    // Toast notification
    function showToast(message, type = 'success') {
        const toast = document.createElement('div');
        toast.className = `toast-notification ${type}`;
        toast.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            background: ${type === 'success' ? '#10b981' : '#ef4444'};
            color: white;
            padding: 12px 20px;
            border-radius: 5px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            z-index: 9999;
            font-weight: 500;
        `;
        toast.innerHTML = `<i class="fas ${type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'} mr-2"></i>${message}`;
        document.body.appendChild(toast);

        setTimeout(() => {
            toast.remove();
        }, 3000);
    }

    // Set default date for needed by
    window.addEventListener('load', function() {
        const dateInput = document.getElementById('reqNeededBy');
        if (dateInput) {
            const today = new Date();
            const nextMonth = new Date(today.setMonth(today.getMonth() + 1));
            dateInput.valueAsDate = nextMonth;
        }
        
        // Load requisitions if logistics tab is active (though it starts hidden)
        // This will load when tab is switched
    });
    </script>

    <style>
    /* Tab styles */
    .admin-tabs {
        display: flex;
        gap: 10px;
        margin-top: 20px;
    }

    .tab-btn {
        padding: 10px 20px;
        border: none;
        background: none;
        cursor: pointer;
        font-size: 14px;
        color: #6b7280;
        border-bottom: 2px solid transparent;
        transition: all 0.3s;
    }

    .tab-btn:hover {
        color: #fbbf24;
    }

    .tab-btn.active {
        color: #fbbf24;
        border-bottom-color: #fbbf24;
    }

    .tab-btn i {
        margin-right: 8px;
    }

    /* Logistics portal styles */
    .logistics-portal .card {
        transition: transform 0.2s;
    }

    .logistics-portal .card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 10px rgba(0,0,0,0.15);
    }

    .logistics-header {
        transition: all 0.3s;
    }

    .logistics-header:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 15px rgba(0,0,0,0.2);
    }

    .toast-notification {
        animation: slideIn 0.3s ease-out;
    }

    @keyframes slideIn {
        from {
            transform: translateX(100%);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }
    </style>

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
                            <option value="fleet_manager">Fleet Manager</option>
                            <option value="mechanic">Mechanic</option>
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
                            <option value="pending">Pending Training</option>
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
                    <div class="profile-employee-id" id="viewId"></div>
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
    <script>
const API_BASE = 'https://humanresource.up.railway.app/api';
const API_KEY = 'logistic_system_2026_key_98765';

// Tab switching function
function switchTab(tab) {
    document.getElementById('tabEmployees').classList.remove('active');
    document.getElementById('tabLogistics').classList.remove('active');
    
    document.getElementById('employeesTab').style.display = 'none';
    document.getElementById('logisticsTab').style.display = 'none';
    
    if (tab === 'employees') {
        document.getElementById('tabEmployees').classList.add('active');
        document.getElementById('employeesTab').style.display = 'block';
    } else if (tab === 'logistics') {
        document.getElementById('tabLogistics').classList.add('active');
        document.getElementById('logisticsTab').style.display = 'block';
        fetchRequisitions();
    }
}

// Submit requisition to HR
document.getElementById('logisticsRequisitionForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const formData = {
        job_title: document.getElementById('reqJobTitle').value,
        department: document.getElementById('reqDepartment').value,
        requested_by: document.getElementById('reqRequestedBy').value,
        positions: parseInt(document.getElementById('reqPositions').value),
        needed_by: document.getElementById('reqNeededBy').value,
        priority: document.getElementById('reqPriority').value,
        justification: document.getElementById('reqJustification').value
    };
    
    const submitBtn = e.target.querySelector('button[type="submit"]');
    const originalText = submitBtn.innerHTML;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Submitting...';
    submitBtn.disabled = true;
    
    updateApiResponse('⏳ Submitting requisition to HR...');
    
    try {
        const response = await fetch(`${API_BASE}/job-requisition.php?api_key=${API_KEY}`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(formData)
        });
        
        const data = await response.json();
        updateApiResponse(JSON.stringify(data, null, 2));
        
        if (data.success) {
            showToast('✅ Requisition submitted successfully! ID: ' + data.data.id);
            e.target.reset();
            document.getElementById('reqJobTitle').value = 'Logistics Coordinator';
            document.getElementById('reqPositions').value = '1';
            document.getElementById('reqRequestedBy').value = '<?php echo htmlspecialchars($_SESSION['full_name']); ?>';
            document.getElementById('reqNeededBy').valueAsDate = new Date(new Date().setMonth(new Date().getMonth() + 1));
            fetchRequisitions();
        } else {
            showToast('❌ Error: ' + data.error, 'error');
        }
    } catch (error) {
        updateApiResponse(`Error: ${error.message}`);
        showToast('❌ Error: ' + error.message, 'error');
    } finally {
        submitBtn.innerHTML = originalText;
        submitBtn.disabled = false;
    }
});

// Fetch recent requisitions
async function fetchRequisitions() {
    const listDiv = document.getElementById('requisitionsList');
    
    listDiv.innerHTML = '<p style="text-align: center; padding: 20px;"><i class="fas fa-spinner fa-spin"></i> Loading requisitions...</p>';
    
    try {
        const response = await fetch(`${API_BASE}/job-requisition.php?api_key=${API_KEY}`);
        const data = await response.json();
        
        updateApiResponse(JSON.stringify(data, null, 2));

        if (data.success && data.data?.length > 0) {
            const logisticsReqs = data.data.filter(req => 
                ['Logistics', 'Warehouse', 'Transportation', 'Supply Chain', 'Inventory'].includes(req.department)
            ).slice(0, 10);
            
            if (logisticsReqs.length > 0) {
                let html = '';
                logisticsReqs.forEach(req => {
                    const statusColor = req.status === 'approved' ? '#10b981' : 
                                       req.status === 'pending' ? '#f59e0b' : 
                                       req.status === 'rejected' ? '#ef4444' : '#3b82f6';
                    
                    html += `
                        <div class="requisition-item" data-req-id="${req.id}" style="background: #f9fafb; padding: 15px; margin-bottom: 10px; border-radius: 8px; border-left: 4px solid #fbbf24; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
                            <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                                <div style="flex: 1;">
                                    <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 5px; flex-wrap: wrap;">
                                        <strong style="font-size: 16px;">${req.job_title}</strong>
                                        <span class="requisition-status" style="background: ${statusColor}; color: white; padding: 2px 10px; border-radius: 20px; font-size: 11px; font-weight: 500;">
                                            ${req.status}
                                        </span>
                                    </div>
                                    <div style="color: #4b5563; font-size: 13px; margin-bottom: 5px;">
                                        <i class="fas fa-building"></i> ${req.department} • 
                                        <i class="fas fa-user"></i> ${req.requested_by}
                                    </div>
                                    <div style="display: flex; gap: 15px; font-size: 12px; color: #6b7280; flex-wrap: wrap;">
                                        <span><i class="fas fa-users"></i> ${req.positions} position${req.positions > 1 ? 's' : ''}</span>
                                        <span><i class="fas fa-calendar"></i> Needed: ${req.needed_by}</span>
                                        <span style="color: ${req.priority === 'high' ? '#ef4444' : req.priority === 'medium' ? '#f59e0b' : '#10b981'};">
                                            <i class="fas fa-flag"></i> ${req.priority}
                                        </span>
                                    </div>
                                </div>
                                
                                <!-- Action buttons -->
                                <div class="requisition-actions" style="display: flex; gap: 5px;">
                                    ${req.status === 'pending' ? `
                                        <button onclick="approveRequisition('${req.id}', ${JSON.stringify(req).replace(/"/g, '&quot;')})" 
                                                class="btn btn-sm btn-success" style="padding: 5px 10px; font-size: 12px;">
                                            <i class="fas fa-check"></i> Approve & Create Employee
                                        </button>
                                    ` : ''}
                                    
                                    ${req.status === 'approved' ? `
                                        <span style="color: #10b981; font-size: 12px; padding: 5px;">
                                            <i class="fas fa-check-circle"></i> Ready to hire
                                        </span>
                                    ` : ''}
                                </div>
                            </div>
                            
                            ${req.justification ? `
                                <div style="margin-top: 10px; font-size: 12px; color: #6b7280; background: #f3f4f6; padding: 8px; border-radius: 4px;">
                                    <i class="fas fa-comment"></i> ${req.justification}
                                </div>
                            ` : ''}
                        </div>
                    `;
                });
                listDiv.innerHTML = html;
            } else {
                listDiv.innerHTML = '<p style="text-align: center; padding: 30px; color: #666;"><i class="fas fa-inbox fa-2x" style="display: block; margin-bottom: 10px;"></i>No requisitions from logistics departments</p>';
            }
        } else {
            listDiv.innerHTML = '<p style="text-align: center; padding: 30px; color: #666;"><i class="fas fa-inbox fa-2x" style="display: block; margin-bottom: 10px;"></i>No requisitions found</p>';
        }
    } catch (error) {
        listDiv.innerHTML = `<p style="text-align: center; padding: 20px; color: #ef4444;"><i class="fas fa-exclamation-circle"></i> Error loading requisitions: ${error.message}</p>`;
        updateApiResponse(`Error: ${error.message}`);
    }
}

// Approve requisition and create employee using your existing EmployeeManagement system
async function approveRequisition(requisitionId, requisitionData) {
    // Show loading
    showToast('⏳ Processing approval and creating employee...', 'info');
    
    try {
        // 1. Update requisition status in HR API
        const updateResponse = await fetch(`${API_BASE}/job-requisition.php?api_key=${API_KEY}`, {
            method: 'PUT',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                id: requisitionId,
                status: 'approved'
            })
        });
        
        const updateResult = await updateResponse.json();
        console.log('Requisition approved:', updateResult);
        
        // 2. Generate employee data based on requisition
        const employeeData = generateEmployeeFromRequisition(requisitionData);
        
        // 3. Use your existing EmployeeManagement to add the employee
        // We'll simulate the form submission since we can't directly call the private method
        await addEmployeeViaForm(employeeData);
        
        // 4. Refresh the requisitions list to show updated status
        fetchRequisitions();
        
        // 5. Show success message with option to view in employee tab
        showToastWithAction(
            '✅ Employee created successfully!', 
            'View in Employees tab', 
            () => switchTab('employees')
        );
        
    } catch (error) {
        console.error('Error in approval process:', error);
        showToast('❌ Error: ' + error.message, 'error');
    }
}

// Generate employee data from requisition
function generateEmployeeFromRequisition(req) {
    const firstNames = ['John', 'Jane', 'Michael', 'Sarah', 'David', 'Maria', 'Robert', 'Lisa', 'James', 'Patricia'];
    const lastNames = ['Smith', 'Johnson', 'Williams', 'Brown', 'Jones', 'Garcia', 'Miller', 'Davis', 'Rodriguez', 'Martinez'];
    
    // For multiple positions, we'll create multiple employees
    // For demo, we'll create one but you could loop
    const firstName = firstNames[Math.floor(Math.random() * firstNames.length)];
    const lastName = lastNames[Math.floor(Math.random() * lastNames.length)];
    const fullName = `${firstName} ${lastName}`;
    
    // Map job title to role
    const role = mapJobTitleToRole(req.job_title);
    
    return {
        full_name: fullName,
        email: `${firstName.toLowerCase()}.${lastName.toLowerCase()}@logistics.com`,
        phone: generatePhoneNumber(),
        role: role,
        status: 'active',
        department: req.department,
        password: 'Welcome123!' // Default password, user would change on first login
    };
}

// Map job title to system role
function mapJobTitleToRole(jobTitle) {
    const title = jobTitle.toLowerCase();
    if (title.includes('driver')) return 'driver';
    if (title.includes('dispatcher') || title.includes('coordinator')) return 'dispatcher';
    if (title.includes('manager')) return 'fleet_manager';
    if (title.includes('mechanic')) return 'mechanic';
    if (title.includes('admin')) return 'admin';
    return 'employee'; // Default
}

// Generate a realistic phone number
function generatePhoneNumber() {
    const areaCode = Math.floor(Math.random() * 900) + 100;
    const prefix = Math.floor(Math.random() * 900) + 100;
    const lineNumber = Math.floor(Math.random() * 9000) + 1000;
    return `(${areaCode}) ${prefix}-${lineNumber}`;
}

// Add employee using your existing system via API
async function addEmployeeViaForm(employeeData) {
    try {
        const response = await fetch('../api/employees.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(employeeData)
        });
        
        const result = await response.json();
        
        if (!result.success) {
            throw new Error(result.error || 'Failed to create employee');
        }
        
        console.log('Employee created:', result);
        
        // Refresh the employee data if on employees tab
        if (document.getElementById('employeesTab').style.display !== 'none') {
            EmployeeManagement.loadEmployees();
            EmployeeManagement.loadStatistics();
        }
        
        return result;
    } catch (error) {
        console.error('Error creating employee:', error);
        throw error;
    }
}

// Show toast with action button
function showToastWithAction(message, actionText, actionCallback) {
    const toastContainer = document.getElementById('toastContainer');
    if (!toastContainer) return;

    const toast = document.createElement('div');
    toast.className = 'toast success';
    toast.style.cssText = `
        display: flex;
        align-items: center;
        justify-content: space-between;
        background: #10b981;
        color: white;
        padding: 12px 20px;
        border-radius: 5px;
        margin-bottom: 10px;
        box-shadow: 0 4px 6px rgba(0,0,0,0.1);
    `;
    
    toast.innerHTML = `
        <div style="display: flex; align-items: center; gap: 10px;">
            <i class="fas fa-check-circle"></i>
            <span>${message}</span>
        </div>
        <button onclick="${actionCallback.toString()}; this.parentElement.remove();" 
                style="background: white; color: #10b981; border: none; padding: 5px 10px; border-radius: 3px; font-size: 12px; cursor: pointer; margin-left: 15px;">
            ${actionText} →
        </button>
        <i class="fas fa-times" style="margin-left: 10px; cursor: pointer; opacity: 0.8;" onclick="this.parentElement.remove()"></i>
    `;

    toastContainer.appendChild(toast);

    setTimeout(() => {
        if (toast.parentElement) {
            toast.remove();
        }
    }, 8000);
}

// Update API response display
function updateApiResponse(content) {
    const apiResponse = document.getElementById('apiResponse');
    if (apiResponse) {
        if (typeof content === 'string') {
            apiResponse.innerHTML = content;
        } else {
            apiResponse.innerHTML = JSON.stringify(content, null, 2);
        }
    }
}

// Toast notification (your existing function but enhanced)
function showToast(message, type = 'success') {
    const toastContainer = document.getElementById('toastContainer');
    if (!toastContainer) return;

    const toast = document.createElement('div');
    toast.className = `toast toast-${type}`;
    
    const icon = type === 'success' ? 'check-circle' : 
                type === 'error' ? 'exclamation-circle' : 
                type === 'info' ? 'info-circle' : 'exclamation-triangle';
    
    toast.innerHTML = `
        <div style="display: flex; align-items: center; gap: 10px;">
            <i class="fas fa-${icon}"></i>
            <span>${message}</span>
        </div>
    `;

    toastContainer.appendChild(toast);

    setTimeout(() => {
        if (toast.parentElement) {
            toast.remove();
        }
    }, 5000);
}

// Set default date for needed by
window.addEventListener('load', function() {
    const dateInput = document.getElementById('reqNeededBy');
    if (dateInput) {
        const today = new Date();
        const nextMonth = new Date(today.setMonth(today.getMonth() + 1));
        dateInput.valueAsDate = nextMonth;
    }
});
</script>   
    <script src="../assets/js/pages/employee.js"></script>
</body>
</html>