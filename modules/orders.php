<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);
if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}

// Include database connection
require_once '../config/db.php';



// Get real statistics from database
try {
    // Total orders
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM purchase_orders");
    $total_orders = $stmt->fetch()['total'] ?? 0;
    
    // Pending approval
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM purchase_orders WHERE status = 'pending'");
    $pending_count = $stmt->fetch()['total'] ?? 0;
    
    // Active suppliers (suppliers with at least one PO)
    $stmt = $pdo->query("SELECT COUNT(DISTINCT supplier_id) as total FROM purchase_orders");
    $active_suppliers = $stmt->fetch()['total'] ?? 0;
    
    // Total spend
    $stmt = $pdo->query("SELECT SUM(total_amount) as total FROM purchase_orders WHERE status IN ('approved', 'completed')");
    $total_spend = $stmt->fetch()['total'] ?? 0;
    
    // Get recent purchase orders
    $stmt = $pdo->query("
        SELECT po.*, s.supplier_name 
        FROM purchase_orders po
        JOIN suppliers s ON po.supplier_id = s.id
        ORDER BY po.created_at DESC
        LIMIT 10
    ");
    $recent_orders = $stmt->fetchAll();
    
} catch(PDOException $e) {
    // Handle error gracefully
    $total_orders = 0;
    $pending_count = 0;
    $active_suppliers = 0;
    $total_spend = 0;
    $recent_orders = [];
}
?>
<?php
// orders.php
$page_title = 'Orders & Procurement | Logistics System';
$page_css = ['../assets/css/style.css', '../assets/css/orders.css'];
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
            
            <!-- Page Header -->
            <div class="page-header">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <div>
                        <h1>Procurement & Sourcing Management</h1>
                        <p>Manage purchase orders, suppliers, and procurement</p>
                    </div>
                    <div class="header-actions">
                        <button class="btn btn-primary" onclick="openPOModal()">
                            <i class="fas fa-plus"></i>
                            New Purchase Order
                        </button>
                    </div>
                </div>
            </div>
            
            <!-- Statistics Cards - Now with REAL data -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-header">
                        <div class="stat-icon blue">
                            <i class="fas fa-shopping-cart"></i>
                        </div>
                        <span class="stat-badge green">+12%</span>
                    </div>
                    <p class="stat-label">Total Orders</p>
                    <p class="stat-value"><?php echo number_format($total_orders); ?></p>
                    <div class="stat-trend">
                        <i class="fas fa-arrow-up"></i> 8% from last month
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-header">
                        <div class="stat-icon amber">
                            <i class="fas fa-clock"></i>
                        </div>
                        <span class="stat-badge amber"><?php echo $pending_count; ?> pending</span>
                    </div>
                    <p class="stat-label">Pending Approval</p>
                    <p class="stat-value"><?php echo number_format($pending_count); ?></p>
                    <div class="stat-trend down">
                        <i class="fas fa-arrow-down"></i> 3 from yesterday
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-header">
                        <div class="stat-icon emerald">
                            <i class="fas fa-truck"></i>
                        </div>
                        <span class="stat-badge green"><?php echo $active_suppliers; ?> active</span>
                    </div>
                    <p class="stat-label">Active Suppliers</p>
                    <p class="stat-value"><?php echo number_format($active_suppliers); ?></p>
                    <div class="stat-trend">
                        <i class="fas fa-arrow-up"></i> 5 new this month
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-header">
                        <div class="stat-icon purple">
                            <i class="fas fa-dollar-sign"></i>
                        </div>
                        <span class="stat-badge green">+15.5%</span>
                    </div>
                    <p class="stat-label">Total Spend</p>
                   <p class="stat-value">
    ₱<?php 
    if ($total_spend >= 1000000) {
        echo number_format($total_spend / 1000000, 2) . 'M';
    } elseif ($total_spend >= 1000) {
        echo number_format($total_spend / 1000, 2) . 'K';
    } else {
        echo number_format($total_spend, 2);
    }
    ?>
</p>
                    <div class="stat-trend">
                        <i class="fas fa-arrow-up"></i> 15.5% from last quarter
                    </div>
                </div>
            </div>
            
            <!-- Tabs -->
           <div class="card" style="margin-bottom: 24px;">
    <div class="tabs">
        <button class="tab <?php echo (!isset($_GET['status']) || $_GET['status'] == 'pending') ? 'active' : ''; ?>" data-tab="pending" onclick="window.location.href='?status=pending'">
            Pending Approval
        </button>
        <button class="tab <?php echo (isset($_GET['status']) && $_GET['status'] == 'approved') ? 'active' : ''; ?>" data-tab="approved" onclick="window.location.href='?status=approved'">
            Approved
        </button>
        <button class="tab <?php echo (isset($_GET['status']) && $_GET['status'] == 'all') ? 'active' : ''; ?>" data-tab="all" onclick="window.location.href='?status=all'">
            All Orders
        </button>
    </div>
</div>
            
            <!-- Dashboard Grid -->
            <div class="dashboard-grid">
    <!-- Purchase Order Documents -->
    <div class="card">
        <div class="card-header">
            <h2><i class="fas fa-file-invoice"></i> Purchase Orders</h2>
            <div class="card-actions">
                <button class="card-action-btn" onclick="loadPurchaseOrders()">
                    <i class="fas fa-sync-alt"></i>
                </button>
            </div>
        </div>
        <div class="card-body">
            <!-- Filter Bar -->
            <div class="filter-bar">
                <div class="filter-group">
                    <select class="filter-select" id="poSupplier" onchange="filterBySupplier(this.value)">
                        <option value="all">Suppliers Products</option>
                        <?php
                        // Get suppliers for filter
                        $suppliers = $pdo->query("SELECT id, supplier_name FROM suppliers ORDER BY supplier_name")->fetchAll();
                        foreach ($suppliers as $supplier) {
                            $selected = (isset($_GET['supplier']) && $_GET['supplier'] == $supplier['id']) ? 'selected' : '';
                            echo "<option value=\"{$supplier['id']}\" $selected>{$supplier['supplier_name']}</option>";
                        }
                        ?>
                    </select>
                </div>
                <div class="search-box">
                    <i class="fas fa-search"></i>
                    <input type="text" id="searchPO" placeholder="Search orders..." value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
                </div>
            </div>
            
            <!-- PO List - Filtered by tab -->
<div class="po-list">
    <?php
    // Get current filters
    $current_tab = isset($_GET['status']) ? $_GET['status'] : 'pending';
    $status_filter = isset($_GET['filter_status']) ? $_GET['filter_status'] : 'all';
    $supplier_filter = isset($_GET['supplier']) ? $_GET['supplier'] : null;
    $search_term = isset($_GET['search']) ? $_GET['search'] : null;
    
    $query = "
        SELECT po.*, s.supplier_name, u.full_name as requester
        FROM purchase_orders po
        JOIN suppliers s ON po.supplier_id = s.id
        LEFT JOIN users u ON po.created_by = u.id
        WHERE 1=1
    ";
    
    $params = [];
    
    // Apply tab filter (this is the main filter)
    if ($current_tab != 'all') {
        // If tab is not 'all', filter by that status
        $query .= " AND po.status = :tab_status";
        $params[':tab_status'] = $current_tab;
    } else {
        // If tab is 'all', apply the status dropdown filter if not 'all'
        if ($status_filter != 'all') {
            $query .= " AND po.status = :filter_status";
            $params[':filter_status'] = $status_filter;
        }
        // If status_filter is 'all', show all statuses (no status filter)
    }
    
    // Apply supplier filter
    if ($supplier_filter && $supplier_filter != 'all') {
        $query .= " AND po.supplier_id = :supplier";
        $params[':supplier'] = $supplier_filter;
    }
    
    // Apply search
    if ($search_term) {
        $query .= " AND (po.po_number LIKE :search OR s.supplier_name LIKE :search)";
        $params[':search'] = "%$search_term%";
    }
    
    $query .= " ORDER BY po.created_at DESC LIMIT 50";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $filtered_orders = $stmt->fetchAll();
    
    if (empty($filtered_orders)): ?>
        <div class="empty-state">
            <i class="fas fa-file-invoice" style="font-size: 48px; color: #cbd5e1; margin-bottom: 16px;"></i>
            <h3 style="color: #64748b; margin-bottom: 8px;"></h3>
            <p style="color: #94a3b8;">
                <?php 
                if ($current_tab != 'all') {
                    echo "No " . $current_tab . " orders match the current filters";
                } else {
                    if ($status_filter != 'all') {
                        echo "No " . $status_filter . " orders match the current filters";
                    } else {
                        echo "";
                    }
                }
                ?>
            </p>
        </div>
    <?php else: ?>
        <table class="purchase-orders-table">
            <thead>
                <tr>
                    <th>PO Number</th>
                    <th>Supplier</th>
                    <th>Order Date</th>
                    <th>Total</th>
                    <th>Status</th>
                    <th>Priority</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($filtered_orders as $order): ?>
                <tr>
                    <td><strong><?php echo htmlspecialchars($order['po_number']); ?></strong></td>
                    <td><?php echo htmlspecialchars($order['supplier_name']); ?></td>
                    <td><?php echo date('Y-m-d', strtotime($order['order_date'])); ?></td>
                    <td>₱<?php echo number_format($order['total_amount'], 2); ?></td>
                    <td>
                        <span class="status-badge status-<?php echo $order['status']; ?>">
                            <?php echo ucfirst($order['status']); ?>
                        </span>
                    </td>
                    <td>
                        <span class="priority-badge priority-<?php echo $order['priority']; ?>">
                            <?php echo ucfirst($order['priority']); ?>
                        </span>
                    </td>
                    <td>
                        <button class="action-btn" onclick="viewPO(<?php echo $order['id']; ?>)">
                            <i class="fas fa-eye"></i>
                        </button>
                        <?php if ($order['status'] == 'pending'): ?>
                        <button class="action-btn approve" onclick="updatePOStatus(<?php echo $order['id']; ?>, 'approved')">
                            <i class="fas fa-check"></i>
                        </button>
                        <button class="action-btn reject" onclick="updatePOStatus(<?php echo $order['id']; ?>, 'rejected')">
                            <i class="fas fa-times"></i>
                        </button>
                        <?php endif; ?>
                        <?php if ($order['status'] == 'approved'): ?>
                        <button class="action-btn complete" onclick="updatePOStatus(<?php echo $order['id']; ?>, 'completed')">
                            <i class="fas fa-check-double"></i>
                        </button>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>
        </div>
    </div>
                
                <!-- Supplier List and Performance -->
                <div class="card">
                    <div class="card-header">
                        <h2><i class="fas fa-building"></i> Supplier Performance</h2>
                        <span class="card-badge">Top 5</span>
                    </div>
                    <div class="card-body">
                        <div class="supplier-list">
                            <?php
                            // Get supplier performance
                            $stmt = $pdo->query("
                                SELECT 
                                    s.id,
                                    s.supplier_name,
                                    s.contact_person,
                                    COUNT(DISTINCT po.id) as total_orders,
                                    SUM(po.total_amount) as total_spent,
                                    AVG(CASE 
                                        WHEN po.actual_delivery <= po.expected_delivery THEN 100 
                                        WHEN po.actual_delivery IS NOT NULL THEN 80 
                                        ELSE 90 
                                    END) as performance_score
                                FROM suppliers s
                                LEFT JOIN purchase_orders po ON s.id = po.supplier_id
                                GROUP BY s.id
                                HAVING total_orders > 0
                                ORDER BY performance_score DESC
                                LIMIT 5
                            ");
                            $top_suppliers = $stmt->fetchAll();
                            
                            if (empty($top_suppliers)): ?>
                                <div class="empty-state">No supplier data available</div>
                            <?php else: ?>
                                <?php foreach ($top_suppliers as $supplier): 
                                    $score = round($supplier['performance_score'] ?? 0);
                                    $scoreClass = $score >= 90 ? 'excellent' : ($score >= 80 ? 'good' : 'average');
                                ?>
                                <div class="supplier-item">
                                    <div class="supplier-info">
                                        <h4><?php echo htmlspecialchars($supplier['supplier_name']); ?></h4>
                                        <p><?php echo htmlspecialchars($supplier['contact_person']); ?> • <?php echo $supplier['total_orders']; ?> orders</p>
                                    </div>
                                    <div class="supplier-score <?php echo $scoreClass; ?>">
                                        <?php echo $score; ?>%
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Procurement History and Approval Dashboard -->
            <div class="dashboard-grid">
                <!-- Procurement History -->
                <div class="card">
                    <div class="card-header">
                        <h2><i class="fas fa-history"></i> Procurement History</h2>
                        <span class="card-badge">Last 30 days</span>
                    </div>
                    <div class="card-body">
                        <div class="history-list">
                            <?php
                            // Get procurement history
                            $stmt = $pdo->query("
                                SELECT 
                                    po.id,
                                    po.po_number,
                                    po.total_amount,
                                    po.status,
                                    po.created_at,
                                    s.supplier_name
                                FROM purchase_orders po
                                JOIN suppliers s ON po.supplier_id = s.id
                                WHERE po.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                                ORDER BY po.created_at DESC
                                LIMIT 10
                            ");
                            $history = $stmt->fetchAll();
                            
                            if (empty($history)): ?>
                                <div class="empty-state">No history available</div>
                            <?php else: ?>
                                <?php foreach ($history as $item): 
                                    $statusClass = $item['status'];
                                    $icons = [
                                        'draft' => 'fa-file',
                                        'pending' => 'fa-clock',
                                        'approved' => 'fa-check-circle',
                                        'rejected' => 'fa-times-circle',
                                        'completed' => 'fa-check-double',
                                        'cancelled' => 'fa-ban'
                                    ];
                                    $icon = $icons[$item['status']] ?? 'fa-file';
                                ?>
                                <div class="history-item">
                                    <div class="history-icon status-<?php echo $item['status']; ?>">
                                        <i class="fas <?php echo $icon; ?>"></i>
                                    </div>
                                    <div class="history-details">
                                        <h4><?php echo htmlspecialchars($item['po_number']); ?></h4>
                                        <p><?php echo htmlspecialchars($item['supplier_name']); ?></p>
                                    </div>
                                    <div class="history-amount">
                                        ₱<?php echo number_format($item['total_amount'], 2); ?>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <!-- Approved & Pending Procurement Dashboard -->
                <div class="card">
                    <div class="card-header">
                        <h2><i class="fas fa-chart-pie"></i> Procurement Overview</h2>
                        <span class="card-badge">Real-time</span>
                    </div>
                    <div class="card-body">
                        <?php
                        // Get monthly totals
                        $stmt = $pdo->query("
                            SELECT 
                                SUM(CASE WHEN status = 'approved' THEN total_amount ELSE 0 END) as approved_month,
                                SUM(CASE WHEN status = 'pending' THEN total_amount ELSE 0 END) as pending_month
                            FROM purchase_orders 
                            WHERE MONTH(created_at) = MONTH(CURDATE())
                        ");
                        $monthly = $stmt->fetch();
                        $approved_month = $monthly['approved_month'] ?? 0;
                        $pending_month = $monthly['pending_month'] ?? 0;
                        
                        // Get approval queue
                        $stmt = $pdo->query("
                            SELECT po.id, po.po_number, po.total_amount, po.created_at,
                                   s.supplier_name, u.full_name as requester
                            FROM purchase_orders po
                            JOIN suppliers s ON po.supplier_id = s.id
                            JOIN users u ON po.created_by = u.id
                            WHERE po.status = 'pending'
                            ORDER BY 
                                CASE po.priority 
                                    WHEN 'urgent' THEN 1
                                    WHEN 'high' THEN 2
                                    WHEN 'normal' THEN 3
                                    ELSE 4
                                END,
                                po.created_at ASC
                            LIMIT 5
                        ");
                        $approval_queue = $stmt->fetchAll();
                        ?>
                        
                        <!-- Procurement Stats -->
                        <div class="procurement-stats">
                            <div class="procurement-stat-item">
                                <div class="procurement-stat-label">Approved</div>
                                <div class="procurement-stat-value">₱<?php echo number_format($approved_month); ?></div>
                                <div class="procurement-stat-trend trend-up">
                                    <i class="fas fa-arrow-up"></i> 12% vs last month
                                </div>
                            </div>
                            <div class="procurement-stat-item">
                                <div class="procurement-stat-label">Pending</div>
                                <div class="procurement-stat-value">₱<?php echo number_format($pending_month); ?></div>
                                <div class="procurement-stat-trend trend-down">
                                    <i class="fas fa-arrow-down"></i> 5% vs last month
                                </div>
                            </div>
                        </div>
                        
                        <!-- Approval Queue -->
                        <h3 style="font-size: 14px; font-weight: 600; color: #1e293b; margin: 16px 0 12px;">
                            <i class="fas fa-clock" style="color: #f59e0b; margin-right: 8px;"></i>
                            Approval Queue
                        </h3>
                        <div class="approval-queue">
                            <?php if (empty($approval_queue)): ?>
                                <div class="empty-state">No items pending approval</div>
                            <?php else: ?>
                                <?php foreach ($approval_queue as $item): ?>
                                <div class="approval-item">
                                    <div class="approval-info">
                                        <h4><?php echo htmlspecialchars($item['po_number']); ?></h4>
                                        <p><?php echo htmlspecialchars($item['supplier_name']); ?> • Requested by <?php echo htmlspecialchars($item['requester']); ?></p>
                                    </div>
                                    <div class="approval-amount">
                                        ₱<?php echo number_format($item['total_amount'], 2); ?>
                                    </div>
                                    <div class="approval-actions">
                                        <button class="btn-approve-small" onclick="updatePOStatus(<?php echo $item['id']; ?>, 'approved')">
                                            <i class="fas fa-check"></i>
                                        </button>
                                        <button class="btn-reject-small" onclick="updatePOStatus(<?php echo $item['id']; ?>, 'rejected')">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    
    <!-- Purchase Order Modal (Keep as is) -->
    <!-- Purchase Order Modal -->
<div id="poModal" class="modal modal-hidden">
    <div class="modal-content po-modal">
        <div class="modal-header">
            <h3 class="modal-title">Create Purchase Order</h3>
            <button class="modal-close" onclick="closeModal('poModal')">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <form id="poForm">
            <!-- Basic Information -->
            <div class="form-section">
                <h4 class="form-section-title">
                    <i class="fas fa-info-circle"></i>
                    Basic Information
                </h4>
                
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label"><i class="fas fa-hashtag"></i> PO Number</label>
                        <input type="text" class="form-input" id="poNumber" readonly>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label"><i class="fas fa-calendar"></i> Order Date</label>
                        <input type="date" class="form-input" id="orderDate" value="<?php echo date('Y-m-d'); ?>">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label"><i class="fas fa-building"></i> Supplier</label>
                        <select class="form-select" id="modalSupplierSelect">
                            <option value="">Select Supplier</option>
                            <!-- Options will be loaded via JavaScript -->
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label"><i class="fas fa-tag"></i> Department</label>
                        <select class="form-select" id="modalDepartmentSelect">
                            <option value="IT Department">IT Department</option>
                            <option value="Operations">Operations</option>
                            <option value="Maintenance">Maintenance</option>
                            <option value="Administration">Administration</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-group full-width">
                    <label class="form-label"><i class="fas fa-align-left"></i> Description</label>
                    <textarea class="form-textarea" id="poDescription" rows="2" placeholder="Enter order description..."></textarea>
                </div>
            </div>
            
            <!-- Items -->
            <div class="form-section">
                <h4 class="form-section-title">
                    <i class="fas fa-boxes"></i>
                    Items
                </h4>
                
                <table class="items-table">
                    <thead>
                        <tr>
                            <th>Item</th>
                            <th>Quantity</th>
                            <th>Unit Price</th>
                            <th>Total</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Items will be added dynamically via JavaScript -->
                    </tbody>
                </table>
                
                <button type="button" class="add-item-btn" onclick="addItem()">
                    <i class="fas fa-plus"></i>
                    Add Item
                </button>
                
                <div class="totals">
                    <div class="total-row">
                        <span class="total-label">Subtotal:</span>
                        <span class="total-value" id="subtotal">₱0.00</span>
                    </div>
                    <div class="total-row">
                        <span class="total-label">Tax (10%):</span>
                        <span class="total-value" id="tax">₱0.00</span>
                    </div>
                    <div class="total-row grand-total">
                        <span class="total-label">Total:</span>
                        <span class="total-value" id="total">$0.00</span>
                    </div>
                </div>
            </div>
            
            <!-- Additional Information -->
            <div class="form-section">
                <h4 class="form-section-title">
                    <i class="fas fa-paperclip"></i>
                    Additional Information
                </h4>
                
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label"><i class="fas fa-truck"></i> Expected Delivery</label>
                        <input type="date" class="form-input" id="expectedDelivery">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label"><i class="fas fa-flag"></i> Priority</label>
                        <select class="form-select" id="priority">
                            <option value="low">Low</option>
                            <option value="normal" selected>Normal</option>
                            <option value="high">High</option>
                            <option value="urgent">Urgent</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-group full-width">
                    <label class="form-label"><i class="fas fa-map-marker-alt"></i> Shipping Address</label>
                    <textarea class="form-textarea" id="shippingAddress" rows="2">123 Business Ave, Suite 100, City, State 12345</textarea>
                </div>
                
                <div class="form-group full-width">
                    <label class="form-label"><i class="fas fa-file-alt"></i> Notes</label>
                    <textarea class="form-textarea" id="additionalNotes" rows="2" placeholder="Additional notes..."></textarea>
                </div>
            </div>
            
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="closeModal('poModal')">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="savePO()">
                    <i class="fas fa-save"></i>
                    Save Purchase Order
                </button>
            </div>
        </form>
    </div>
</div>
    
    <script src="../assets/js/pages/orders.js"></script>
</body>
</html>