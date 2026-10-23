// assets/js/dashboard.js

// Sidebar Toggle
function toggleSidebar() {
    document.getElementById('sidebar').classList.toggle('active');
}

// Close sidebar when clicking outside on mobile
document.addEventListener('click', function(event) {
    const sidebar = document.getElementById('sidebar');
    const menuToggle = document.querySelector('.menu-toggle');
    
    if (window.innerWidth <= 768) {
        if (!sidebar.contains(event.target) && !menuToggle.contains(event.target)) {
            sidebar.classList.remove('active');
        }
    }
});

// Initialize Dashboard
document.addEventListener('DOMContentLoaded', function() {
    console.log('Dashboard loaded');
    loadDashboardData();
    setupEventListeners();
    startRealTimeUpdates();
});

// Load Dashboard Data
function loadDashboardData() {
    // In a real application, this would fetch data from an API
    // For now, we'll use mock data
    
    updateAssetList();
    updateMaintenanceAlerts();
    updateMaintenanceHistory();
    updateAssetLifecycle();
    updateDocumentRepository();
    updateTrackingDashboard();
    updateAccessLogs();
    updateDownloadableRecords();
}

// Setup Event Listeners
function setupEventListeners() {
    // Search functionality
    const searchInput = document.querySelector('.search-input');
    if (searchInput) {
        searchInput.addEventListener('input', debounce(function(e) {
            const searchTerm = e.target.value.toLowerCase();
            console.log('Searching:', searchTerm);
            filterDashboard(searchTerm);
        }, 300));
    }
    
    // Refresh button
    const refreshBtn = document.getElementById('refreshDashboard');
    if (refreshBtn) {
        refreshBtn.addEventListener('click', function() {
            loadDashboardData();
            showNotification('Dashboard refreshed', 'success');
        });
    }
    
    // Document download buttons
    document.querySelectorAll('.download-btn').forEach(btn => {
        btn.addEventListener('click', function(e) {
            const record = this.closest('.download-item');
            const recordName = record.querySelector('h3').textContent;
            downloadRecord(recordName);
        });
    });
}

// Real-time updates simulation
function startRealTimeUpdates() {
    setInterval(() => {
        // Simulate real-time updates every 30 seconds
        updateMaintenanceAlerts();
        updateAccessLogs();
    }, 30000);
}

// Update Asset List
function updateAssetList() {
    const assetList = document.querySelector('.asset-list');
    if (!assetList) return;
    
    // Mock data - in real app, this would come from API
    const assets = [
        { name: 'Forklift F-101', type: 'Heavy Equipment', condition: 92, status: 'good' },
        { name: 'Conveyor Belt C-205', type: 'Machinery', condition: 78, status: 'warning' },
        { name: 'Pallet Jack P-303', type: 'Equipment', condition: 95, status: 'good' },
        { name: 'Generator G-407', type: 'Power', condition: 65, status: 'warning' },
        { name: 'HVAC Unit H-512', type: 'Climate', condition: 88, status: 'good' }
    ];
    
    let html = '';
    assets.forEach(asset => {
        html += `
            <div class="asset-item">
                <div class="asset-info">
                    <div class="asset-icon">
                        <i class="fas fa-cog"></i>
                    </div>
                    <div class="asset-details">
                        <h3>${asset.name}</h3>
                        <p>${asset.type}</p>
                    </div>
                </div>
                <div class="asset-condition">
                    <div class="percentage">${asset.condition}%</div>
                    <div class="label">Condition</div>
                </div>
            </div>
        `;
    });
    
    assetList.innerHTML = html;
}

// Update Maintenance Alerts
function updateMaintenanceAlerts() {
    const alertList = document.querySelector('.alert-list');
    if (!alertList) return;
    
    // Mock data
    const alerts = [
        { title: 'Forklift F-101 Maintenance Due', asset: 'Forklift F-101', days: 2, priority: 'urgent' },
        { title: 'Conveyor Belt Inspection', asset: 'Conveyor C-205', days: 5, priority: 'warning' },
        { title: 'Generator Service', asset: 'Generator G-407', days: 7, priority: 'warning' },
        { title: 'HVAC Filter Replacement', asset: 'HVAC H-512', days: 12, priority: 'normal' },
        { title: 'Pallet Jack Calibration', asset: 'Pallet P-303', days: 15, priority: 'normal' }
    ];
    
    let html = '';
    alerts.forEach(alert => {
        html += `
            <div class="alert-item ${alert.priority}">
                <div class="alert-icon ${alert.priority}">
                    <i class="fas fa-exclamation"></i>
                </div>
                <div class="alert-content">
                    <div class="alert-title">${alert.title}</div>
                    <div class="alert-meta">${alert.asset}</div>
                    <div class="alert-time">Due in ${alert.days} days</div>
                </div>
            </div>
        `;
    });
    
    alertList.innerHTML = html;
}

// Update Maintenance History
function updateMaintenanceHistory() {
    const timeline = document.querySelector('.timeline');
    if (!timeline) return;
    
    // Mock data
    const history = [
        { action: 'Forklift F-101 - Oil Change', date: '2024-02-20', time: '2 days ago', type: 'blue' },
        { action: 'Conveyor Belt - Belt Replacement', date: '2024-02-18', time: '4 days ago', type: 'emerald' },
        { action: 'Generator - Load Test', date: '2024-02-15', time: '1 week ago', type: 'amber' },
        { action: 'HVAC - Filter Change', date: '2024-02-12', time: '10 days ago', type: 'blue' },
        { action: 'Pallet Jack - Inspection', date: '2024-02-10', time: '2 weeks ago', type: 'emerald' }
    ];
    
    let html = '';
    history.forEach(item => {
        html += `
            <div class="timeline-item">
                <div class="timeline-dot ${item.type}"></div>
                <div class="timeline-content">
                    <p>${item.action}</p>
                    <div class="time">${item.time}</div>
                </div>
            </div>
        `;
    });
    
    timeline.innerHTML = html;
}

// Update Asset Lifecycle
function updateAssetLifecycle() {
    // This would typically be a chart
    // For now, we'll create a simple representation
    const lifecycleContainer = document.getElementById('assetLifecycle');
    if (!lifecycleContainer) return;
    
    const lifecycleData = [
        { stage: 'Operational', count: 45, percentage: 75 },
        { stage: 'Maintenance', count: 8, percentage: 13 },
        { stage: 'End-of-Life', count: 4, percentage: 7 },
        { stage: 'Retired', count: 3, percentage: 5 }
    ];
    
    let html = '<div style="display: flex; flex-direction: column; gap: 12px;">';
    lifecycleData.forEach(item => {
        html += `
            <div>
                <div style="display: flex; justify-content: space-between; margin-bottom: 4px;">
                    <span style="font-size: 13px; color: #475569;">${item.stage}</span>
                    <span style="font-size: 13px; font-weight: 600; color: #1e293b;">${item.count} assets</span>
                </div>
                <div style="height: 6px; background-color: #e2e8f0; border-radius: 999px; overflow: hidden;">
                    <div style="height: 100%; width: ${item.percentage}%; background: linear-gradient(90deg, #2563eb, #3b82f6); border-radius: 999px;"></div>
                </div>
            </div>
        `;
    });
    html += '</div>';
    
    lifecycleContainer.innerHTML = html;
}

// Update Document Repository
function updateDocumentRepository() {
    const docGrid = document.querySelector('.document-grid');
    if (!docGrid) return;
    
    // Mock data
    const documents = [
        { name: 'Maintenance Manuals', files: 24, icon: 'fa-book' },
        { name: 'Equipment Certificates', files: 18, icon: 'fa-certificate' },
        { name: 'Safety Guidelines', files: 12, icon: 'fa-shield-alt' },
        { name: 'Warranty Documents', files: 15, icon: 'fa-file-contract' }
    ];
    
    let html = '';
    documents.forEach(doc => {
        html += `
            <div class="document-item">
                <div class="document-icon">
                    <i class="fas ${doc.icon}"></i>
                </div>
                <h3>${doc.name}</h3>
                <p>${doc.files} files</p>
                <button class="btn-small">View All</button>
            </div>
        `;
    });
    
    docGrid.innerHTML = html;
}

// Update Tracking Dashboard
function updateTrackingDashboard() {
    const trackingList = document.querySelector('.tracking-list');
    if (!trackingList) return;
    
    // Mock data
    const shipments = [
        { id: 'SH-2024-001', status: 'In Transit', location: 'Distribution Center', time: '2 hours' },
        { id: 'SH-2024-002', status: 'Delivered', location: 'Customer Site', time: 'Completed' },
        { id: 'SH-2024-003', status: 'Processing', location: 'Warehouse', time: 'Today' },
        { id: 'SH-2024-004', status: 'In Transit', location: 'On Route', time: '3 hours' }
    ];
    
    let html = '<div style="display: flex; flex-direction: column; gap: 12px;">';
    shipments.forEach(ship => {
        html += `
            <div style="display: flex; align-items: center; justify-content: space-between; padding: 8px; background-color: #f8fafc; border-radius: 8px;">
                <div>
                    <div style="font-weight: 600; font-size: 13px;">${ship.id}</div>
                    <div style="font-size: 12px; color: #64748b;">${ship.location}</div>
                </div>
                <div>
                    <span class="status-badge ${ship.status === 'Delivered' ? 'status-good' : ship.status === 'In Transit' ? 'status-warning' : 'status-critical'}">
                        ${ship.status}
                    </span>
                </div>
            </div>
        `;
    });
    html += '</div>';
    
    trackingList.innerHTML = html;
}

// Update Access Logs
async function updateAccessLogs() {
    const logsTable = document.querySelector('.logs-table tbody');
    if (!logsTable) return;

    try {
        const res = await fetch('backend/dashboard.php'); // adjust path if needed
        const logs = await res.json();

        if (!Array.isArray(logs)) {
            logsTable.innerHTML = '<tr><td colspan="4">No logs found</td></tr>';
            return;
        }

        let html = '';
        logs.forEach(log => {
            html += `
                <tr>
                    <td>${log.user}</td>
                    <td>${log.document}</td>
                    <td>${log.action_type}</td>
                    <td>${log.time}</td>
                </tr>
            `;
        });

        logsTable.innerHTML = html;
    } catch (err) {
        console.error('Error fetching logs:', err);
        logsTable.innerHTML = '<tr><td colspan="4">Error loading logs</td></tr>';
    }
}

// Load on page load
updateAccessLogs();

// Optional: refresh every 30 seconds
setInterval(updateAccessLogs, 30000);


// Call once on page load
updateAccessLogs();

// Optional: refresh every 30 seconds
setInterval(updateAccessLogs, 30000);

// Update Downloadable Records
function updateDownloadableRecords() {
    const downloadSection = document.querySelector('.download-section');
    if (!downloadSection) return;
    
    // Mock data
    const records = [
        { name: 'Maintenance History 2024', type: 'PDF', size: '2.4 MB', date: '2024-02-20' },
        { name: 'Asset Inventory Report', type: 'Excel', size: '1.8 MB', date: '2024-02-19' },
        { name: 'Compliance Certificates', type: 'PDF', size: '3.2 MB', date: '2024-02-18' },
        { name: 'Maintenance Schedule', type: 'PDF', size: '1.2 MB', date: '2024-02-17' },
        { name: 'Audit Trail Q1', type: 'CSV', size: '4.5 MB', date: '2024-02-15' }
    ];
    
    let html = '';
    records.forEach(record => {
        html += `
            <div class="download-item">
                <div class="download-info">
                    <div class="download-icon">
                        <i class="fas fa-file-${record.type === 'PDF' ? 'pdf' : record.type === 'Excel' ? 'excel' : 'alt'}"></i>
                    </div>
                    <div class="download-details">
                        <h3>${record.name}</h3>
                        <p>${record.type} • ${record.size} • ${record.date}</p>
                    </div>
                </div>
                <button class="download-btn" onclick="downloadRecord('${record.name}')">
                    <i class="fas fa-download"></i>
                </button>
            </div>
        `;
    });
    
    downloadSection.innerHTML = html;
}

// Download Record
function downloadRecord(recordName) {
    console.log('Downloading:', recordName);
    showNotification(`Downloading ${recordName}`, 'info');
    
    // Simulate download
    setTimeout(() => {
        showNotification(`${recordName} downloaded successfully`, 'success');
    }, 1500);
}

// Filter Dashboard
function filterDashboard(searchTerm) {
    console.log('Filtering dashboard for:', searchTerm);
    // Implement filtering logic here
}

// Show Notification
function showNotification(message, type = 'info') {
    // Create notification element
    const notification = document.createElement('div');
    notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        padding: 12px 20px;
        background-color: ${type === 'success' ? '#10b981' : type === 'error' ? '#ef4444' : '#3b82f6'};
        color: white;
        border-radius: 8px;
        font-size: 14px;
        font-weight: 500;
        z-index: 1000;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        animation: slideIn 0.3s ease;
    `;
    notification.textContent = message;
    
    document.body.appendChild(notification);
    
    // Remove after 3 seconds
    setTimeout(() => {
        notification.style.animation = 'slideOut 0.3s ease';
        setTimeout(() => {
            document.body.removeChild(notification);
        }, 300);
    }, 3000);
}

// Debounce function for search
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

// Add animation styles
const style = document.createElement('style');
style.textContent = `
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
    
    @keyframes slideOut {
        from {
            transform: translateX(0);
            opacity: 1;
        }
        to {
            transform: translateX(100%);
            opacity: 0;
        }
    }
`;
document.head.appendChild(style);