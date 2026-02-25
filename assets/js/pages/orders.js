// assets/js/orders.js

// Initialize Orders Dashboard
document.addEventListener('DOMContentLoaded', function() {
    console.log('Orders dashboard loaded');
    loadOrdersData();
    setupEventListeners();
    initTabs();
});

// Load Dashboard Data
function loadOrdersData() {
    loadPurchaseOrders();
    loadSupplierList();
    loadProcurementHistory();
    loadApprovalQueue();
}

// Setup Event Listeners
function setupEventListeners() {
    // Tab switching
    document.querySelectorAll('.tab').forEach(tab => {
        tab.addEventListener('click', function() {
            const tabId = this.dataset.tab;
            switchTab(tabId);
        });
    });
    
    // Search functionality
    const searchInput = document.getElementById('searchPO');
    if (searchInput) {
        searchInput.addEventListener('input', debounce(function(e) {
            searchPurchaseOrders(e.target.value);
        }, 300));
    }
    
    // Filter selects
    document.querySelectorAll('.filter-select').forEach(select => {
        select.addEventListener('change', function() {
            filterData(this.id, this.value);
        });
    });
    
    // Refresh button
    const refreshBtn = document.getElementById('refreshData');
    if (refreshBtn) {
        refreshBtn.addEventListener('click', function() {
            loadOrdersData();
            showNotification('Data refreshed', 'success');
        });
    }
}

// Initialize Tabs
function initTabs() {
    const tabs = document.querySelectorAll('.tab');
    const tabPanes = document.querySelectorAll('.tab-pane');
    
    if (tabs.length > 0) {
        tabs[0].classList.add('active');
        tabPanes.forEach(pane => pane.style.display = 'none');
        const pendingTab = document.getElementById('tab-pending');
        if (pendingTab) {
            pendingTab.style.display = 'block';
        }
    }
}

// Switch Tabs
function switchTab(tabId) {
    // Update tab buttons
    document.querySelectorAll('.tab').forEach(tab => {
        tab.classList.remove('active');
        if (tab.dataset.tab === tabId) {
            tab.classList.add('active');
        }
    });
    
    // Show corresponding content
    document.querySelectorAll('.tab-pane').forEach(pane => {
        pane.style.display = 'none';
    });
    document.getElementById(`tab-${tabId}`).style.display = 'block';
}

// Load Purchase Orders
function loadPurchaseOrders() {
    const poList = document.querySelector('.po-list');
    if (!poList) return;
    
    // Mock data - in real app, this would come from API
    const orders = [
        {
            id: 'PO-2024-001',
            title: 'Office Equipment Supply',
            supplier: 'Tech Supplies Inc.',
            date: '2024-02-20',
            items: 5,
            amount: 12500,
            status: 'pending'
        },
        {
            id: 'PO-2024-002',
            title: 'Raw Materials Batch A',
            supplier: 'Industrial Materials Co.',
            date: '2024-02-19',
            items: 8,
            amount: 34200,
            status: 'approved'
        },
        {
            id: 'PO-2024-003',
            title: 'IT Hardware Upgrade',
            supplier: 'Global Tech Solutions',
            date: '2024-02-18',
            items: 12,
            amount: 28750,
            status: 'draft'
        },
        {
            id: 'PO-2024-004',
            title: 'Office Furniture',
            supplier: 'Furniture Direct',
            date: '2024-02-17',
            items: 3,
            amount: 8900,
            status: 'rejected'
        },
        {
            id: 'PO-2024-005',
            title: 'Maintenance Supplies',
            supplier: 'Industrial Supply Co.',
            date: '2024-02-16',
            items: 15,
            amount: 15600,
            status: 'approved'
        }
    ];
    
    let html = '';
    orders.forEach(order => {
        html += `
            <div class="po-item">
                <div class="po-info">
                    <div class="po-icon">
                        <i class="fas fa-file-invoice"></i>
                    </div>
                    <div class="po-details">
                        <h3>${order.title}</h3>
                        <div class="po-meta">
                            <span><i class="fas fa-hashtag"></i> ${order.id}</span>
                            <span><i class="fas fa-calendar"></i> ${order.date}</span>
                            <span><i class="fas fa-boxes"></i> ${order.items} items</span>
                        </div>
                    </div>
                </div>
                <div class="po-supplier">
                    <i class="fas fa-building"></i> ${order.supplier}
                </div>
                <div class="po-amount">
                    $${order.amount.toLocaleString()}
                </div>
                <div class="po-status">
                    <span class="status-badge status-${order.status}">
                        <i class="fas fa-${order.status === 'approved' ? 'check-circle' : order.status === 'pending' ? 'clock' : order.status === 'draft' ? 'file' : 'times-circle'}"></i>
                        ${order.status.charAt(0).toUpperCase() + order.status.slice(1)}
                    </span>
                </div>
                <div class="po-actions">
                    <button class="po-action-btn" onclick="viewPO('${order.id}')" title="View">
                        <i class="fas fa-eye"></i>
                    </button>
                    <button class="po-action-btn" onclick="editPO('${order.id}')" title="Edit">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button class="po-action-btn" onclick="downloadPO('${order.id}')" title="Download">
                        <i class="fas fa-download"></i>
                    </button>
                </div>
            </div>
        `;
    });
    
    poList.innerHTML = html;
}

// Load Supplier List
function loadSupplierList() {
    const supplierList = document.querySelector('.supplier-list');
    if (!supplierList) return;
    
    // Mock data
    const suppliers = [
        {
            name: 'Tech Supplies Inc.',
            category: 'Electronics',
            rating: 4.8,
            reviews: 124,
            orders: 45,
            performance: 98,
            onTime: 96,
            avatar: 'TS'
        },
        {
            name: 'Industrial Materials Co.',
            category: 'Raw Materials',
            rating: 4.5,
            reviews: 89,
            orders: 32,
            performance: 92,
            onTime: 94,
            avatar: 'IM'
        },
        {
            name: 'Global Tech Solutions',
            category: 'IT Services',
            rating: 4.9,
            reviews: 156,
            orders: 67,
            performance: 99,
            onTime: 98,
            avatar: 'GT'
        },
        {
            name: 'Furniture Direct',
            category: 'Furniture',
            rating: 4.2,
            reviews: 56,
            orders: 23,
            performance: 85,
            onTime: 88,
            avatar: 'FD'
        },
        {
            name: 'Industrial Supply Co.',
            category: 'Maintenance',
            rating: 4.6,
            reviews: 92,
            orders: 38,
            performance: 94,
            onTime: 92,
            avatar: 'IS'
        }
    ];
    
    let html = '';
    suppliers.forEach(supplier => {
        const performanceClass = supplier.performance >= 95 ? 'high' : supplier.performance >= 85 ? 'medium' : 'low';
        
        html += `
            <div class="supplier-item">
                <div class="supplier-avatar">${supplier.avatar}</div>
                <div class="supplier-info">
                    <h3>${supplier.name}</h3>
                    <div class="supplier-meta">
                        <span>${supplier.category}</span>
                        <span>${supplier.orders} orders</span>
                    </div>
                </div>
                <div class="supplier-rating">
                    <div class="rating-stars">
                        ${getStarRating(supplier.rating)}
                    </div>
                    <div class="rating-text">${supplier.rating} (${supplier.reviews} reviews)</div>
                </div>
                <div class="supplier-stats">
                    <div class="supplier-stat">
                        <div class="value">${supplier.performance}%</div>
                        <div class="label">Performance</div>
                        <div class="performance-bar">
                            <div class="performance-fill ${performanceClass}" style="width: ${supplier.performance}%"></div>
                        </div>
                    </div>
                    <div class="supplier-stat">
                        <div class="value">${supplier.onTime}%</div>
                        <div class="label">On-time</div>
                    </div>
                </div>
            </div>
        `;
    });
    
    supplierList.innerHTML = html;
}

// Load Procurement History
function loadProcurementHistory() {
    const historyList = document.querySelector('.history-list');
    if (!historyList) return;
    
    // Mock data
    const history = [
        {
            title: 'Office Equipment Purchase',
            supplier: 'Tech Supplies Inc.',
            amount: 12500,
            date: '2024-02-20',
            status: 'approved',
            icon: 'fa-laptop'
        },
        {
            title: 'Raw Materials Order',
            supplier: 'Industrial Materials Co.',
            amount: 34200,
            date: '2024-02-19',
            status: 'approved',
            icon: 'fa-cubes'
        },
        {
            title: 'IT Hardware Upgrade',
            supplier: 'Global Tech Solutions',
            amount: 28750,
            date: '2024-02-18',
            status: 'pending',
            icon: 'fa-microchip'
        },
        {
            title: 'Office Furniture',
            supplier: 'Furniture Direct',
            amount: 8900,
            date: '2024-02-17',
            status: 'rejected',
            icon: 'fa-chair'
        },
        {
            title: 'Maintenance Supplies',
            supplier: 'Industrial Supply Co.',
            amount: 15600,
            date: '2024-02-16',
            status: 'approved',
            icon: 'fa-tools'
        },
        {
            title: 'Printing Materials',
            supplier: 'Office Supplies Co.',
            amount: 4300,
            date: '2024-02-15',
            status: 'approved',
            icon: 'fa-print'
        }
    ];
    
    let html = '';
    history.forEach(item => {
        html += `
            <div class="history-item">
                <div class="history-icon">
                    <i class="fas ${item.icon}"></i>
                </div>
                <div class="history-content">
                    <div class="history-title">${item.title}</div>
                    <div class="history-meta">
                        <span><i class="fas fa-building"></i> ${item.supplier}</span>
                        <span class="status-badge status-${item.status}">
                            <i class="fas fa-${item.status === 'approved' ? 'check-circle' : 'clock'}"></i>
                            ${item.status}
                        </span>
                    </div>
                </div>
                <div class="history-amount">
                    $${item.amount.toLocaleString()}
                </div>
                <div class="history-date">
                    <i class="fas fa-calendar"></i> ${item.date}
                </div>
            </div>
        `;
    });
    
    historyList.innerHTML = html;
}

// Load Approval Queue
function loadApprovalQueue() {
    const approvalQueue = document.querySelector('.approval-queue');
    if (!approvalQueue) return;
    
    // Mock data
    const approvals = [
        {
            id: 'PO-2024-006',
            title: 'Network Equipment',
            supplier: 'Tech Supplies Inc.',
            requester: 'John Smith',
            amount: 23400,
            priority: 'urgent',
            date: '2024-02-20'
        },
        {
            id: 'PO-2024-007',
            title: 'Safety Equipment',
            supplier: 'Industrial Safety Co.',
            requester: 'Sarah Johnson',
            amount: 8900,
            priority: 'pending',
            date: '2024-02-20'
        },
        {
            id: 'PO-2024-008',
            title: 'Software Licenses',
            supplier: 'Software Solutions Inc.',
            requester: 'Mike Wilson',
            amount: 15750,
            priority: 'pending',
            date: '2024-02-19'
        },
        {
            id: 'PO-2024-009',
            title: 'Maintenance Tools',
            supplier: 'Industrial Supply Co.',
            requester: 'Tom Brown',
            amount: 6700,
            priority: 'urgent',
            date: '2024-02-19'
        }
    ];
    
    let html = '';
    approvals.forEach(item => {
        html += `
            <div class="approval-item ${item.priority}">
                <div class="approval-info">
                    <div class="approval-icon">
                        <i class="fas fa-file-invoice"></i>
                    </div>
                    <div class="approval-details">
                        <h3>${item.title}</h3>
                        <div class="approval-meta">
                            <span>${item.id}</span>
                            <span>${item.supplier}</span>
                            <span>Requester: ${item.requester}</span>
                        </div>
                    </div>
                </div>
                <div class="approval-amount">
                    $${item.amount.toLocaleString()}
                </div>
                <div class="approval-actions">
                    <button class="approval-btn approve" onclick="approvePO('${item.id}')">
                        <i class="fas fa-check"></i> Approve
                    </button>
                    <button class="approval-btn reject" onclick="rejectPO('${item.id}')">
                        <i class="fas fa-times"></i> Reject
                    </button>
                    <button class="approval-btn review" onclick="reviewPO('${item.id}')">
                        <i class="fas fa-search"></i> Review
                    </button>
                </div>
            </div>
        `;
    });
    
    approvalQueue.innerHTML = html;
}

// Helper function for star rating
function getStarRating(rating) {
    const fullStars = Math.floor(rating);
    const halfStar = rating % 1 >= 0.5;
    let stars = '';
    
    for (let i = 0; i < fullStars; i++) {
        stars += '<i class="fas fa-star"></i>';
    }
    if (halfStar) {
        stars += '<i class="fas fa-star-half-alt"></i>';
    }
    const emptyStars = 5 - Math.ceil(rating);
    for (let i = 0; i < emptyStars; i++) {
        stars += '<i class="far fa-star"></i>';
    }
    
    return stars;
}

// Search Purchase Orders
function searchPurchaseOrders(term) {
    console.log('Searching orders:', term);
    // Implement search logic
}

// Filter Data
function filterData(filterId, value) {
    console.log('Filtering:', filterId, value);
    // Implement filter logic
}

// PO Actions
function viewPO(poId) {
    console.log('Viewing PO:', poId);
    showNotification(`Viewing PO ${poId}`, 'info');
}

function editPO(poId) {
    console.log('Editing PO:', poId);
    openPOModal(poId);
}

function downloadPO(poId) {
    console.log('Downloading PO:', poId);
    showNotification(`Downloading PO ${poId}`, 'info');
}

// Approval Actions
function approvePO(poId) {
    console.log('Approving PO:', poId);
    showNotification(`PO ${poId} approved`, 'success');
    loadApprovalQueue();
}

function rejectPO(poId) {
    console.log('Rejecting PO:', poId);
    if (confirm('Are you sure you want to reject this purchase order?')) {
        showNotification(`PO ${poId} rejected`, 'warning');
        loadApprovalQueue();
    }
}

function reviewPO(poId) {
    console.log('Reviewing PO:', poId);
    openReviewModal(poId);
}

// Open PO Modal
function openPOModal(poId = null) {
    const modal = document.getElementById('poModal');
    if (modal) {
        modal.classList.remove('modal-hidden');
        if (poId) {
            document.querySelector('.modal-title').textContent = 'Edit Purchase Order';
            loadPOData(poId);
        } else {
            document.querySelector('.modal-title').textContent = 'Create Purchase Order';
            resetPOForm();
        }
    }
}

// Close Modal
function closeModal(modalId) {
    document.getElementById(modalId).classList.add('modal-hidden');
}

// Load PO Data for Editing
function loadPOData(poId) {
    console.log('Loading PO data:', poId);
    // Implement loading logic
}

// Reset PO Form
function resetPOForm() {
    const form = document.getElementById('poForm');
    if (form) form.reset();
    
    // Reset items table
    const itemsTable = document.querySelector('.items-table tbody');
    if (itemsTable) {
        itemsTable.innerHTML = `
            <tr>
                <td><input type="text" value="Item 1" placeholder="Item name"></td>
                <td><input type="text" value="10" placeholder="Qty"></td>
                <td><input type="text" value="1250" placeholder="Price"></td>
                <td>$12,500.00</td>
                <td><button class="remove-item"><i class="fas fa-times"></i></button></td>
            </tr>
        `;
    }
    
    // Reset totals
    document.getElementById('subtotal').textContent = '$12,500.00';
    document.getElementById('tax').textContent = '$1,250.00';
    document.getElementById('total').textContent = '$13,750.00';
}

// Add Item to PO
function addItem() {
    const tbody = document.querySelector('.items-table tbody');
    const rowCount = tbody.children.length + 1;
    
    const newRow = document.createElement('tr');
    newRow.innerHTML = `
        <td><input type="text" placeholder="Item name"></td>
        <td><input type="text" value="1" placeholder="Qty"></td>
        <td><input type="text" value="0" placeholder="Price"></td>
        <td>$0.00</td>
        <td><button class="remove-item" onclick="removeItem(this)"><i class="fas fa-times"></i></button></td>
    `;
    
    tbody.appendChild(newRow);
}

// Remove Item
function removeItem(btn) {
    const row = btn.closest('tr');
    if (document.querySelectorAll('.items-table tbody tr').length > 1) {
        row.remove();
        calculateTotals();
    } else {
        showNotification('At least one item is required', 'error');
    }
}

// Calculate Totals
function calculateTotals() {
    // Implement calculation logic
}

// Save Purchase Order
function savePO() {
    showNotification('Purchase order saved successfully', 'success');
    closeModal('poModal');
    loadPurchaseOrders();
}

// Show Notification
function showNotification(message, type = 'info') {
    const notification = document.createElement('div');
    notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        padding: 12px 20px;
        background: ${type === 'success' ? '#10b981' : type === 'error' ? '#ef4444' : '#3b82f6'};
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
    
    setTimeout(() => {
        notification.style.animation = 'slideOut 0.3s ease';
        setTimeout(() => {
            document.body.removeChild(notification);
        }, 300);
    }, 3000);
}

// Debounce function
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
        from { transform: translateX(100%); opacity: 0; }
        to { transform: translateX(0); opacity: 1; }
    }
    
    @keyframes slideOut {
        from { transform: translateX(0); opacity: 1; }
        to { transform: translateX(100%); opacity: 0; }
    }
`;
document.head.appendChild(style);