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
$user_role = $_SESSION['role'] ?? 'employee';
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
                    </div>
                    
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
                            Purchase Re Order
                        </button>
                       <button type="button" class="btn btn-outline" onclick="openNewProductModal()" style="border-color: #2563eb; color: #2563eb;">
    <i class="fas fa-plus-circle"></i>
    Add New Product
</button>
                    </div>
                </div>
            </div>
            <!-- Add New Product Modal -->
<div id="newProductModal" class="modal modal-hidden">
    <!-- Fixed container with flex column -->
    <div class="modal-content" style="max-width: 600px; max-height: 90vh; display: flex; flex-direction: column;">
        
        <!-- Fixed Header -->
        <div class="modal-header" style="flex-shrink: 0; padding: 20px 24px;">
            <h3 class="modal-title"><i class="fas fa-box"></i> Add New Product Request</h3>
            <button class="modal-close" onclick="closeModal('newProductModal')">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <!-- Scrollable Content -->
        <div style="overflow-y: auto; flex: 1; padding: 0 24px;">
            <form id="newProductForm">
                <div class="form-section">
                    <h4 class="form-section-title">
                        <i class="fas fa-info-circle"></i> Product Information
                    </h4>
                    
                    <div class="form-grid" style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                        <div class="form-group">
                            <label class="form-label">Product Name *</label>
                            <input type="text" id="new_product_name" class="form-input" placeholder="e.g., Nike Air Max" required>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">SKU *</label>
                            <input type="text" id="new_product_sku" class="form-input" placeholder="e.g., NK-AIR-001" required>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Category *</label>
                            <select id="new_product_category" class="form-select" required>
                                <option value="">Select Category</option>
                                <?php
                                try {
                                    $cat_stmt = $pdo->query("SELECT id, category_name FROM categories ORDER BY category_name");
                                    while($cat = $cat_stmt->fetch()) {
                                        echo '<option value="' . $cat['id'] . '">' . htmlspecialchars($cat['category_name']) . '</option>';
                                    }
                                } catch(Exception $e) {}
                                ?>
                                <option value="new">+ Add New Category</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Supplier *</label>
                            <select id="new_product_supplier" class="form-select" required>
                                <option value="">Select Supplier</option>
                                <?php
                                try {
                                    $sup_stmt = $pdo->query("SELECT id, supplier_name FROM suppliers ORDER BY supplier_name");
                                    while($sup = $sup_stmt->fetch()) {
                                        echo '<option value="' . $sup['id'] . '">' . htmlspecialchars($sup['supplier_name']) . '</option>';
                                    }
                                } catch(Exception $e) {}
                                ?>
                                <option value="new">+ Add New Supplier</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Estimated Price *</label>
                            <input type="number" id="new_product_price" class="form-input" placeholder="0.00" step="0.01" min="0" required>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Initial Order Qty</label>
                            <input type="number" id="new_product_quantity" class="form-input" value="1" min="1">
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Reorder Level</label>
                            <input type="number" id="new_product_reorder" class="form-input" value="10" min="1">
                        </div>
                    </div>
                    
                    <!-- New Category Input (Hidden) -->
                    <div id="newCategoryInput" style="display: none; margin-top: 15px; padding: 15px; background: #e8f0fe; border-radius: 8px;">
                        <label class="form-label">New Category Name</label>
                        <div style="display: flex; gap: 10px;">
                            <input type="text" id="new_category_name" class="form-input" placeholder="Enter new category name" style="flex: 1;">
                            <button type="button" class="btn btn-primary" onclick="addNewCategoryFromProduct()">Add Category</button>
                        </div>
                    </div>
                    
                    <!-- New Supplier Input (Hidden) -->
                    <div id="newSupplierInput" style="display: none; margin-top: 15px; padding: 15px; background: #e8f0fe; border-radius: 8px;">
                        <label class="form-label">New Supplier Name</label>
                        <div style="display: flex; gap: 10px;">
                            <input type="text" id="new_supplier_name" class="form-input" placeholder="Enter new supplier name" style="flex: 1;">
                        </div>
                        <button type="button" class="btn btn-primary" onclick="addNewSupplierFromProduct()" style="margin-top: 10px;">Add Supplier</button>
                    </div>
                    
                    <div class="form-group full-width" style="margin-top: 16px;">
                        <label class="form-label">Description / Justification *</label>
                        <textarea id="new_product_description" rows="4" class="form-textarea" placeholder="Why do we need this product?" required></textarea>
                    </div>
                    
                    <div class="form-group full-width">
                        <label class="form-label">
                            <input type="checkbox" id="new_product_urgent"> Mark as Urgent
                        </label>
                    </div>
                </div>
            </form>
        </div>
        
        <!-- Fixed Footer -->
        <div class="modal-footer" style="flex-shrink: 0; padding: 20px 24px;">
            <button type="button" class="btn btn-outline" onclick="closeModal('newProductModal')">Cancel</button>
            <button type="button" class="btn btn-primary" onclick="submitNewProductRequest()">
                <i class="fas fa-paper-plane"></i> Submit for Approval
            </button>
        </div>
        
    </div>
</div>
            <!-- Statistics Cards -->
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
        <?php 
        $current_search = isset($_GET['search']) ? '&search=' . urlencode($_GET['search']) : '';
        
        // Safely get pending product requests count
        $product_requests_count = 0;
        try {
            // Check if table exists first
            $table_check = $pdo->query("SHOW TABLES LIKE 'product_requests'");
            if ($table_check->rowCount() > 0) {
                $count_stmt = $pdo->query("SELECT COUNT(*) as total FROM product_requests WHERE status = 'pending'");
                $product_requests_count = $count_stmt->fetch()['total'] ?? 0;
            }
        } catch(Exception $e) {
            // Table doesn't exist yet, silently ignore
            $product_requests_count = 0;
        }
        ?>
        <button class="tab <?php echo (!isset($_GET['status']) || $_GET['status'] == 'pending') ? 'active' : ''; ?>" 
                onclick="window.location.href='?status=pending<?php echo $current_search; ?>'">
            Pending Approval
        </button>
        <button class="tab <?php echo (isset($_GET['status']) && $_GET['status'] == 'approved') ? 'active' : ''; ?>" 
                onclick="window.location.href='?status=approved<?php echo $current_search; ?>'">
            Approved
        </button>
        <button class="tab <?php echo (isset($_GET['status']) && $_GET['status'] == 'all') ? 'active' : ''; ?>" 
                onclick="window.location.href='?status=all<?php echo $current_search; ?>'">
            All Orders
        </button>
        <!-- NEW TAB for Product Requests -->
        <button class="tab <?php echo (isset($_GET['status']) && $_GET['status'] == 'requests') ? 'active' : ''; ?>" 
                onclick="window.location.href='?status=requests'">
            Product Requests 
            <?php if ($product_requests_count > 0): ?>
                <span class="badge" style="background: #ef4444; color: white; padding: 2px 8px; border-radius: 12px; font-size: 11px; margin-left: 5px;">
                    <?php echo $product_requests_count; ?>
                </span>
            <?php endif; ?>
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
                        </div>
                    </div>
                    <div class="card-body">
                        <!-- Filter Bar -->
                        <div class="filter-bar">
                            <div class="filter-group">
                                <?php if (isset($_GET['search'])): ?>
                                    <span class="active-filter" style="background: #e0f2fe; padding: 4px 12px; border-radius: 20px; font-size: 13px; display: inline-flex; align-items: center;">
                                        <i class="fas fa-search" style="color: #0369a1; margin-right: 6px;"></i> 
                                        Searching: "<strong><?php echo htmlspecialchars($_GET['search']); ?></strong>"
                                        <a href="?status=<?php echo $_GET['status'] ?? 'pending'; ?>" style="margin-left: 8px; color: #e11d48; text-decoration: none; font-weight: bold;">✕</a>
                                    </span>
                                <?php endif; ?>
                            </div>
                            <div class="search-box">
                                <i class="fas fa-search"></i>
                                <input type="text" id="searchPO" placeholder="Search by PO number..." 
                                       value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
                            </div>
                        </div>
                        
                        <!-- PO List -->
                         <?php
// Check if we're on the product requests tab
if (isset($_GET['status']) && $_GET['status'] == 'requests') {
    try {
        // Check if table exists
        $table_check = $pdo->query("SHOW TABLES LIKE 'product_requests'");
        if ($table_check->rowCount() == 0) {
            echo '<div style="text-align: center; padding: 40px; background: #f8fafc; border-radius: 8px; margin-top: 20px;">';
            echo '<i class="fas fa-box-open" style="font-size: 48px; color: #94a3b8; margin-bottom: 16px;"></i>';
            echo '<h3 style="color: #1e293b; margin-bottom: 8px;">Product Requests Coming Soon</h3>';
            echo '<p style="color: #64748b;">The product requests feature is being set up. Please check back later.</p>';
            echo '</div>';
        } else {
            // Fetch product requests
            $requests_query = "
                SELECT pr.*, 
                       c.category_name,
                       s.supplier_name,
                       u.full_name as requester_name
                FROM product_requests pr
                LEFT JOIN categories c ON pr.category_id = c.id
                LEFT JOIN suppliers s ON pr.supplier_id = s.id
                LEFT JOIN users u ON pr.requested_by = u.id
                ORDER BY 
                    CASE pr.status
                        WHEN 'pending' THEN 1
                        WHEN 'approved' THEN 2
                        WHEN 'rejected' THEN 3
                    END,
                    pr.requested_at DESC
            ";
            $requests = $pdo->query($requests_query)->fetchAll();
            ?>
            
            <!-- Product Requests Table -->
            <div class="product-requests-section" style="margin-top: 20px;">
                <h3 style="margin-bottom: 15px;">New Product Requests</h3>
                
                <?php if (empty($requests)): ?>
                    <div style="text-align: center; padding: 40px; background: #f8fafc; border-radius: 8px;">
                        <i class="fas fa-box-open" style="font-size: 48px; color: #94a3b8; margin-bottom: 16px;"></i>
                        <p style="color: #64748b;">No product requests found.</p>
                    </div>
               <?php else: ?>
    <!-- ADD THIS SCROLLABLE CONTAINER for product requests -->
    <div style="overflow-x: auto; max-height: 500px; overflow-y: auto; border: 1px solid #e2e8f0; border-radius: 8px;">
        <table class="purchase-orders-table" style="width: 100%; border-collapse: collapse;">
            <thead style="position: sticky; top: 0; background: white; z-index: 10;">
                <tr>
                    <th>Product Name</th>
                    <th>SKU</th>
                    <th>Category</th>
                    <th>Supplier</th>
                    <th>Est. Price</th>
                    <th>Requested By</th>
                    <th>Date</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($requests as $request): ?>
                <tr>
                    <td><strong><?php echo htmlspecialchars($request['product_name']); ?></strong></td>
                    <td><?php echo htmlspecialchars($request['sku']); ?></td>
                    <td><?php echo htmlspecialchars($request['category_name'] ?? 'N/A'); ?></td>
                    <td><?php echo htmlspecialchars($request['supplier_name'] ?? 'N/A'); ?></td>
                    <td>₱<?php echo number_format($request['estimated_price'], 2); ?></td>
                    <td><?php echo htmlspecialchars($request['requester_name'] ?? 'Unknown'); ?></td>
                    <td><?php echo date('M d, Y', strtotime($request['requested_at'])); ?></td>
                    <td>
                        <span class="status-badge status-<?php echo $request['status']; ?>">
                            <?php echo ucfirst($request['status']); ?>
                        </span>
                    </td>
                    <td>
    <div style="display: flex; gap: 4px;">
        <?php if ($request['status'] == 'pending'): ?>
            
            <?php if ($user_role === 'admin'): ?>
                <!-- Admin can Approve/Reject -->
                <button class="action-btn approve" title="Approve Request" 
                        onclick="approveProductRequest(<?php echo $request['id']; ?>)">
                    <i class="fas fa-check"></i>
                </button>
                <button class="action-btn reject" title="Reject Request" 
                        onclick="rejectProductRequest(<?php echo $request['id']; ?>)">
                    <i class="fas fa-times"></i>
                </button>
            <?php endif; ?>
            
            <!-- Everyone can View -->
            <button class="action-btn view" title="View Request" 
                    onclick="viewProductRequest(<?php echo $request['id']; ?>)">
                <i class="fas fa-eye"></i>
            </button>
            
        <?php else: ?>
            <!-- For approved/rejected requests - everyone can view -->
            <button class="action-btn view" title="View Request" 
                    onclick="viewProductRequest(<?php echo $request['id']; ?>)">
                <i class="fas fa-eye"></i>
            </button>
        <?php endif; ?>
    </div>
</td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>
            </div>
            <?php
        }
    } catch (Exception $e) {
        echo '<div style="text-align: center; padding: 40px; background: #f8fafc; border-radius: 8px; margin-top: 20px;">';
        echo '<i class="fas fa-exclamation-triangle" style="font-size: 48px; color: #f59e0b; margin-bottom: 16px;"></i>';
        echo '<p style="color: #64748b;">Error loading product requests. Please try again later.</p>';
        echo '</div>';
    }
}
?>
<!-- PO List - replace your existing po-list div with this -->
<div class="po-list">
    <?php
    // Get current filters
    $current_tab = isset($_GET['status']) ? $_GET['status'] : 'pending';
    $search_term = isset($_GET['search']) ? $_GET['search'] : null;
    
    $query = "
        SELECT po.*, s.supplier_name, u.full_name as requester
        FROM purchase_orders po
        JOIN suppliers s ON po.supplier_id = s.id
        LEFT JOIN users u ON po.created_by = u.id
        WHERE 1=1
    ";
    
    $params = [];
    
    // Apply tab filter
    if ($current_tab != 'all') {
        $query .= " AND po.status = :tab_status";
        $params[':tab_status'] = $current_tab;
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
        <div class="empty-state" style="text-align: center; padding: 60px 20px;">
            <?php if ($current_tab == 'pending'): ?>
                <i class="fas fa-file-alt" style="font-size: 80px; color: #9ca3af; margin-bottom: 16px;"></i>
                <h3 style="margin: 0 0 8px 0; color: #374151;">No Pending Approvals</h3>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <!-- ADD THIS SCROLLABLE CONTAINER -->
        <div style="overflow-x: auto; max-height: 500px; overflow-y: auto; border: 1px solid #e2e8f0; border-radius: 8px;">
            <table class="purchase-orders-table" style="width: 100%; border-collapse: collapse;">
                <thead style="position: sticky; top: 0; background: white; z-index: 10;">
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
                            <div style="display: flex; gap: 4px;">
                                <!-- Everyone can view -->
                                <button class="action-btn" title="View PO Details" 
                                        onclick="viewPO(<?php echo $order['id']; ?>)">
                                    <i class="fas fa-eye"></i>
                                </button>
                                
                                <?php if ($order['status'] == 'pending'): ?>
                                    <!-- Only admin can approve/reject -->
                                    <?php if ($user_role === 'admin'): ?>
                                        <button class="action-btn approve" title="Approve Purchase Order" 
                                                onclick="updatePOStatus(<?php echo $order['id']; ?>, 'approved')">
                                            <i class="fas fa-check"></i>
                                        </button>
                                        <button class="action-btn reject" title="Reject Purchase Order" 
                                                onclick="updatePOStatus(<?php echo $order['id']; ?>, 'rejected')">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    <?php endif; ?>
                                <?php endif; ?>
                                
                                <?php if ($order['status'] == 'approved'): ?>
                                    <!-- Only admin can mark as completed -->
                                    <?php if ($user_role === 'admin'): ?>
                                        <button class="action-btn complete" title="Mark as Completed" 
                                                onclick="updatePOStatus(<?php echo $order['id']; ?>, 'completed')">
                                            <i class="fas fa-check-double"></i>
                                        </button>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
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
                
                <!-- Procurement Overview -->
                <div class="card">
                    <div class="card-header">
                        <h2><i class="fas fa-chart-pie"></i> Procurement Overview</h2>
                        <span class="card-badge">Real-time</span>
                    </div>
                    <div class="card-body">
                        <?php
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
                                    <?php if ($user_role === 'admin'): ?>
                                    <div class="approval-actions">
                                        <button class="btn-approve-small" onclick="updatePOStatus(<?php echo $item['id']; ?>, 'approved')">
                                            <i class="fas fa-check"></i>
                                        </button>
                                        <button class="btn-reject-small" onclick="updatePOStatus(<?php echo $item['id']; ?>, 'rejected')">
                                            <i class="fas fa-times"></i>
                                        </button>
                                      <?php endif; ?>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    
    <!-- Purchase Order Modal -->
<div id="poModal" class="modal modal-hidden">
   <div class="modal-content po-modal" style="max-height: 90vh; overflow-y: auto;">
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
                        <input type="text" placeholder="Auto generated..." class="form-input" id="poNumber" readonly disabled style="background-color: #f5f5f5; color: #666;">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label"><i class="fas fa-calendar"></i> Order Date</label>
                        <input type="date" class="form-input" id="orderDate" value="<?php echo date('Y-m-d'); ?>">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group" style="position: relative;">
                        <label class="form-label"><i class="fas fa-building"></i> Supplier</label>
                        <select class="form-select" id="modalSupplierSelect">
                            <option value="">Select Supplier</option>
                        </select>
                        <!-- Item count display - placed directly under supplier dropdown -->
                        <div id="supplierItemCount" style="margin-top: 5px; font-size: 12px; color: #666; min-height: 20px;">
                            <span id="itemCountDisplay">0</span> items available for selected supplier
                        </div>
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
                
                <!-- Quick filter info bar -->
                <div style="background: #f0f9ff; padding: 8px 12px; border-radius: 6px; margin-bottom: 15px; display: flex; align-items: center; gap: 10px; font-size: 13px;">
                    <i class="fas fa-info-circle" style="color: #0369a1;"></i>
                    <span>Showing items for <strong id="selectedSupplierName">selected supplier</strong></span>
                </div>
                
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
                        <!-- Items will be added via JavaScript -->
                    </tbody>
                </table>
                
                <div style="display: flex; gap: 10px; margin-top: 15px;">
                    <button type="button" class="add-item-btn" onclick="addItem()">
                        <i class="fas fa-plus"></i>
                        Add Re Order Item
                    </button>
                </div>
                
                <div class="totals">
                    <div class="total-row">
                        <span class="total-label">Subtotal:</span>
                        <span class="total-value" id="subtotal">₱0.00</span>
                    </div>
                    <div class="total-row">
                        <span class="total-label">VAT (12%):</span>
                        <span class="total-value" id="tax">₱0.00</span>
                    </div>
                    <div class="total-row grand-total">
                        <span class="total-label">Total:</span>
                        <span class="total-value" id="total">₱0.00</span>
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
<script>
console.log('✅ Page JavaScript loaded');

// Global variables
let allItems = [];
let filteredItems = [];

// ===== MODAL FUNCTIONS =====
window.openPOModal = function() {
    console.log('Opening PO modal');
    const modal = document.getElementById('poModal');
    if (modal) {
        modal.classList.remove('modal-hidden');
        modal.classList.add('modal-visible');
        
        // Reset form
        document.getElementById('poForm')?.reset();
        
        // Clear items table and add one empty row
        const tbody = document.querySelector('.items-table tbody');
        if (tbody) {
            tbody.innerHTML = '';
            addItem();
        }
        
        // Load data
        setTimeout(() => {
            loadSuppliers();
            loadItems();
        }, 200);
    } else {
        alert('Modal not found!');
    }
};

window.closeModal = function(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.remove('modal-visible');
        modal.classList.add('modal-hidden');
    }
};

// ===== LOAD DATA FUNCTIONS =====
function updateSupplierNameDisplay() {
    const supplierSelect = document.getElementById('modalSupplierSelect');
    const supplierNameSpan = document.getElementById('selectedSupplierName');
    
    if (supplierSelect && supplierNameSpan) {
        const selectedOption = supplierSelect.options[supplierSelect.selectedIndex];
        if (selectedOption && selectedOption.value) {
            supplierNameSpan.textContent = selectedOption.text;
            supplierNameSpan.style.fontWeight = '600';
            supplierNameSpan.style.color = '#2563eb';
        } else {
            supplierNameSpan.textContent = 'selected supplier';
            supplierNameSpan.style.fontWeight = 'normal';
            supplierNameSpan.style.color = 'inherit';
        }
    }
}

window.filterItemsBySupplier = function() {
    const supplierId = document.getElementById('modalSupplierSelect').value;
    
    console.log('Filtering items for supplier:', supplierId);
    
    if (!supplierId || supplierId === '') {
        filteredItems = allItems;
    } else {
        filteredItems = allItems.filter(item => Number(item.supplier_id) === Number(supplierId));
    }
    
    console.log(`Showing ${filteredItems.length} items for supplier ${supplierId || 'all'}`);
    
    updateAllItemDropdowns();
    updateItemCountDisplay();
    updateSupplierNameDisplay();
    
    if (filteredItems.length === 0 && supplierId) {
        alert('No items found for this supplier. You can add new items using the "Add New Product" button.');
    }
};

async function loadSuppliers() {
    try {
        const response = await fetch('../api/get_suppliers.php');
        const suppliers = await response.json();
        
        const select = document.getElementById('modalSupplierSelect');
        if (select) {
            select.innerHTML = '<option value="">Select Supplier</option>' +
                suppliers.map(s => `<option value="${s.id}">${s.supplier_name}</option>`).join('');
        }
    } catch (error) {
        console.error('Error loading suppliers:', error);
    }
}

async function loadItems() {
    try {
        const response = await fetch('../api/get_inventory_items.php');
        const items = await response.json();
        
        if (items.error) {
            console.error('API Error:', items.error);
            alert('Error loading items: ' + items.error);
            return;
        }
        
        allItems = items;
        filteredItems = items;
        console.log('✅ Items loaded:', items.length);
        
    } catch (error) {
        console.error('Error loading items:', error);
        alert('Failed to load items. Check console for details.');
    }
}

function updateAllItemDropdowns() {
    const dropdowns = document.querySelectorAll('.item-select');
    dropdowns.forEach(select => {
        const currentValue = select.value;
        
        let options = '<option value="">Select Item</option>';
        
        if (filteredItems.length > 0) {
            options += filteredItems.map(item => 
                `<option value="${item.id}" data-price="${item.price}" ${item.id == currentValue ? 'selected' : ''}>
                    ${item.item_name} (${item.sku}) - ₱${parseFloat(item.price).toFixed(2)}
                </option>`
            ).join('');
        }
        
        select.innerHTML = options;
    });
}

function updateItemCountDisplay() {
    const countDisplay = document.getElementById('itemCountDisplay');
    if (countDisplay) {
        countDisplay.innerHTML = `<span id="itemCount">${filteredItems.length}</span> items available for selected supplier`;
    }
}

// ===== ITEM FUNCTIONS =====
window.addItem = function() {
    const tbody = document.querySelector('.items-table tbody');
    if (!tbody) return;
    
    const row = document.createElement('tr');
    
    let options = '<option value="">Select Item</option>';
    
    if (filteredItems.length > 0) {
        options += filteredItems.map(item => 
            `<option value="${item.id}" data-price="${item.price}">
                ${item.item_name} (${item.sku}) - ₱${parseFloat(item.price).toFixed(2)}
            </option>`
        ).join('');
    }
    
    row.innerHTML = `
        <td>
            <select class="item-select" onchange="updateItemPrice(this)" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                ${options}
            </select>
        </td>
        <td><input type="number" class="item-qty" value="1" min="1" onchange="calculateItemTotal(this)" style="width: 80px; padding: 8px; border: 1px solid #ddd; border-radius: 4px;"></td>
        <td><input type="number" class="item-price" value="0" step="0.01" onchange="calculateItemTotal(this)" style="width: 100px; padding: 8px; border: 1px solid #ddd; border-radius: 4px;"></td>
        <td class="item-total" style="font-weight: 600;">₱0.00</td>
        <td><button type="button" onclick="removeItem(this)" style="background:none; border:none; color:#e11d48; cursor:pointer;"><i class="fas fa-times"></i></button></td>
    `;
    
    tbody.appendChild(row);
};

window.updateItemPrice = function(select) {
    const selectedOption = select.selectedOptions[0];
    if (selectedOption && selectedOption.dataset.price) {
        const price = selectedOption.dataset.price;
        const row = select.closest('tr');
        row.querySelector('.item-price').value = price;
        calculateItemTotal(row.querySelector('.item-price'));
    }
};

window.calculateItemTotal = function(input) {
    const row = input.closest('tr');
    const qty = parseFloat(row.querySelector('.item-qty').value) || 0;
    const price = parseFloat(row.querySelector('.item-price').value) || 0;
    const total = qty * price;
    
    row.querySelector('.item-total').textContent = '₱' + total.toFixed(2);
    calculateGrandTotal();
};

window.calculateGrandTotal = function() {
    let subtotal = 0;
    document.querySelectorAll('.item-total').forEach(cell => {
        subtotal += parseFloat(cell.textContent.replace('₱', '')) || 0;
    });
    
    const tax = subtotal * 0.12;
    const total = subtotal + tax;
    
    document.getElementById('subtotal').textContent = '₱' + subtotal.toFixed(2);
    document.getElementById('tax').textContent = '₱' + tax.toFixed(2);
    document.getElementById('total').textContent = '₱' + total.toFixed(2);
    
    return {subtotal, tax, total};
};

window.removeItem = function(button) {
    const rowCount = document.querySelectorAll('.items-table tbody tr').length;
    if (rowCount > 1) {
        button.closest('tr').remove();
        calculateGrandTotal();
    } else {
        alert('You must have at least one item');
    }
};
// Add these functions right after your savePO function
// ===== PO VIEW FUNCTIONS =====
window.viewPO = async function(poId) {
    console.log('Viewing PO:', poId);
    
    try {
        const response = await fetch(`../api/get_po_details.php?po_id=${poId}`);
        const data = await response.json();
        
        if (data.success) {
            showPODetailsModal(data.po);
        } else {
            alert('Error loading PO details: ' + (data.error || 'Unknown error'));
        }
    } catch (error) {
        console.error('Error:', error);
        alert('Failed to load PO details');
    }
};

function showPODetailsModal(po) {
    // Create modal if it doesn't exist
    let modal = document.getElementById('poDetailsModal');
    if (!modal) {
        modal = document.createElement('div');
        modal.id = 'poDetailsModal';
        modal.className = 'modal modal-hidden';
        document.body.appendChild(modal);
    }
    
    // Format items table
    let itemsHtml = '';
    if (po.items && po.items.length > 0) {
        po.items.forEach(item => {
            itemsHtml += `
                <tr>
                    <td>${item.item_name} ${item.sku ? '(' + item.sku + ')' : ''}</td>
                    <td style="text-align: right;">${item.quantity}</td>
                    <td style="text-align: right;">₱${parseFloat(item.unit_price).toFixed(2)}</td>
                    <td style="text-align: right;">₱${parseFloat(item.total_price).toFixed(2)}</td>
                </tr>
            `;
        });
    }
    
    modal.innerHTML = `
        <div class="modal-content" style="max-width: 800px;">
            <div class="modal-header">
                <h3 class="modal-title">
                    <i class="fas fa-file-invoice"></i> 
                    PO Details: ${po.po_number}
                </h3>
                <button class="modal-close" onclick="closeModal('poDetailsModal')">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <div style="padding: 24px; max-height: 70vh; overflow-y: auto;">
                <div style="background: ${getStatusColor(po.status)}; color: white; padding: 12px; border-radius: 8px; margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center;">
                    <span style="font-weight: 600;">Status: ${po.status.toUpperCase()}</span>
                    <span>Priority: ${po.priority?.toUpperCase() || 'NORMAL'}</span>
                </div>
                
                <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px; margin-bottom: 20px;">
                    <div>
                        <h4 style="color: #666; margin-bottom: 10px;">Supplier Information</h4>
                        <p><strong>Name:</strong> ${po.supplier_name}</p>
                        ${po.supplier_contact ? `<p><strong>Contact:</strong> ${po.supplier_contact}</p>` : ''}
                        ${po.supplier_email ? `<p><strong>Email:</strong> ${po.supplier_email}</p>` : ''}
                    </div>
                    <div>
                        <h4 style="color: #666; margin-bottom: 10px;">Order Information</h4>
                        <p><strong>Order Date:</strong> ${formatDate(po.order_date)}</p>
                        <p><strong>Expected Delivery:</strong> ${po.expected_delivery ? formatDate(po.expected_delivery) : 'Not set'}</p>
                        <p><strong>Created By:</strong> ${po.requester || 'Unknown'}</p>
                    </div>
                </div>
                
                <h4 style="color: #666; margin: 20px 0 10px;">Items</h4>
                <table style="width: 100%; border-collapse: collapse; margin-bottom: 20px;">
                    <thead style="background: #f8fafc;">
                        <tr>
                            <th style="padding: 12px; text-align: left;">Item</th>
                            <th style="padding: 12px; text-align: right;">Qty</th>
                            <th style="padding: 12px; text-align: right;">Unit Price</th>
                            <th style="padding: 12px; text-align: right;">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${itemsHtml || '<tr><td colspan="4" style="text-align: center; padding: 20px;">No items found</td></tr>'}
                    </tbody>
                    <tfoot style="border-top: 2px solid #e2e8f0;">
                        <tr>
                            <td colspan="3" style="padding: 12px; text-align: right;"><strong>Subtotal:</strong></td>
                            <td style="padding: 12px; text-align: right;">₱${parseFloat(po.subtotal || 0).toFixed(2)}</td>
                        </tr>
                        <tr>
                            <td colspan="3" style="padding: 12px; text-align: right;"><strong>Tax (12%):</strong></td>
                            <td style="padding: 12px; text-align: right;">₱${parseFloat(po.tax_amount || 0).toFixed(2)}</td>
                        </tr>
                        <tr>
                            <td colspan="3" style="padding: 12px; text-align: right;"><strong>Total:</strong></td>
                            <td style="padding: 12px; text-align: right; font-size: 18px; font-weight: 700; color: #2563eb;">
                                ₱${parseFloat(po.total_amount || 0).toFixed(2)}
                            </td>
                        </tr>
                    </tfoot>
                </table>
                
                ${po.notes ? `
                <div style="background: #f8fafc; padding: 16px; border-radius: 8px;">
                    <h4 style="color: #666; margin-bottom: 8px;">Notes</h4>
                    <p style="margin: 0; white-space: pre-line;">${po.notes}</p>
                </div>
                ` : ''}
            </div>
            
            <div class="modal-footer" style="justify-content: space-between;">
                <div>
                    ${po.status === 'pending' ? `
                        <button class="btn btn-outline" onclick="editPO(${po.id})">
                            <i class="fas fa-edit"></i> Edit
                        </button>
                    ` : ''}
                </div>
                <div>
                    <button class="btn btn-outline" onclick="closeModal('poDetailsModal')">Close</button>
                    ${po.status === 'pending' && window.userRole === 'admin' ? `
                        <button class="btn btn-primary" onclick="updatePOStatus(${po.id}, 'approved')">
                            <i class="fas fa-check"></i> Approve
                        </button>
                        <button class="btn btn-danger" onclick="updatePOStatus(${po.id}, 'rejected')">
                            <i class="fas fa-times"></i> Reject
                        </button>
                    ` : ''}
                    ${po.status === 'approved' && window.userRole === 'admin' ? `
                        <button class="btn btn-primary" onclick="updatePOStatus(${po.id}, 'completed')">
                            <i class="fas fa-check-double"></i> Complete
                        </button>
                    ` : ''}
                </div>
            </div>
        </div>
    `;
    
    modal.classList.remove('modal-hidden');
    modal.classList.add('modal-visible');
}

// Helper functions
function getStatusColor(status) {
    const colors = {
        'draft': '#6b7280',
        'pending': '#f59e0b',
        'approved': '#10b981',
        'rejected': '#ef4444',
        'completed': '#2563eb',
        'cancelled': '#6b7280'
    };
    return colors[status] || '#6b7280';
}

function formatDate(dateStr) {
    if (!dateStr) return 'N/A';
    const date = new Date(dateStr);
    return date.toLocaleDateString('en-US', { 
        year: 'numeric', 
        month: 'short', 
        day: 'numeric' 
    });
}
window.updatePOStatus = async function(poId, newStatus) {
    console.log('Updating PO status:', {poId, newStatus});
    
    if (!confirm(`Are you sure you want to mark this PO as ${newStatus}?`)) return;
    
    try {
        const response = await fetch('../api/update_po_status.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                po_id: poId,
                status: newStatus
            })
        });
        
        console.log('Response status:', response.status);
        
        // Get the response as text first
        const responseText = await response.text();
        console.log('Raw response:', responseText);
        
        // Try to parse as JSON
        let result;
        try {
            result = JSON.parse(responseText);
        } catch (e) {
            console.warn('Response is not valid JSON:', responseText);
            
            // Even if response isn't JSON, check if status code is OK
            if (response.ok) {
                alert(`PO ${newStatus} successfully! (Status updated)`);
                window.location.reload();
                return;
            } else {
                throw new Error('Server returned invalid response');
            }
        }
        
        if (result.success) {
            alert(result.message || `PO ${newStatus} successfully!`);
            window.location.reload();
        } else {
            alert('Error: ' + (result.error || 'Unknown error'));
        }
    } catch (error) {
        console.error('Error:', error);
        
        // Last resort - ask user to refresh to check
        if (confirm('Error updating status. Would you like to refresh the page to see if it worked?')) {
            window.location.reload();
        }
    }
};
// Add editPO and updatePO functions if you want edit functionality
window.editPO = function(poId) {
    alert('Edit functionality coming soon!');
};

window.updatePOStatus = async function(poId, newStatus) {
    if (!confirm(`Are you sure you want to mark this PO as ${newStatus}?`)) return;
    
    try {
        const response = await fetch('../api/update_po_status.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({po_id: poId, status: newStatus})
        });
        
        const result = await response.json();
        if (result.success) {
            alert(`PO ${newStatus} successfully!`);
            window.location.reload();
        } else {
            alert('Error: ' + (result.error || 'Unknown error'));
        }
    } catch (error) {
        console.error('Error:', error);
        alert('Error updating PO status');
    }
};
// ===== SAVE PO FUNCTION (COMBINED VERSION) =====
window.savePO = async function() {
    console.log('Saving PO...');
    
    // Get form values
    const supplier = document.getElementById('modalSupplierSelect').value;
    const orderDate = document.getElementById('orderDate').value;
    const priority = document.getElementById('priority').value;
    const deliveryDate = document.getElementById('expectedDelivery').value;
    const description = document.getElementById('poDescription').value;
    const additionalNotes = document.getElementById('additionalNotes').value;
    
    // Validate required fields
    if (!supplier) {
        alert('Please select a supplier');
        return;
    }
    
    if (!orderDate) {
        alert('Please select an order date');
        return;
    }
    
    // Get items from the table
    const items = [];
    const itemRows = document.querySelectorAll('.items-table tbody tr');
    
    if (itemRows.length === 0) {
        alert('Please add at least one item');
        return;
    }
    
    itemRows.forEach(row => {
        // Check for new items - look for the data-is-new attribute
        if (row.getAttribute('data-is-new') === 'true') {
            const newItemData = JSON.parse(row.querySelector('.new-item-data').value);
            const qty = parseFloat(row.querySelector('.item-qty').value) || 0;
            const price = parseFloat(row.querySelector('.item-price').value) || 0;
            
            items.push({
                is_new: true,
                new_item_name: newItemData.name,
                new_sku: newItemData.sku,
                new_category: newItemData.category,
                new_category_name: newItemData.category_name,
                new_reorder_level: newItemData.reorder,
                new_description: newItemData.description || '',
                quantity: qty,
                unit_price: price
            });
        } else {
            const itemSelect = row.querySelector('.item-select');
            if (itemSelect && itemSelect.value) {
                const qty = parseFloat(row.querySelector('.item-qty').value) || 0;
                const price = parseFloat(row.querySelector('.item-price').value) || 0;
                
                items.push({
                    item_id: itemSelect.value,
                    quantity: qty,
                    unit_price: price
                });
            }
        }
    });
    
    if (items.length === 0) {
        alert('Please select at least one item');
        return;
    }
    
    // Calculate totals
    const totals = calculateGrandTotal();
    
    // Combine notes
    const combinedNotes = [description, additionalNotes].filter(n => n).join('\n');
    
    const poData = {
        supplier_id: supplier,
        order_date: orderDate,
        expected_delivery: deliveryDate || null,
        priority: priority,
        subtotal: totals.subtotal,
        tax_amount: totals.tax,
        shipping_cost: 0,
        total_amount: totals.total,
        notes: combinedNotes,
        items: items
    };
    
    console.log('Sending PO data:', JSON.stringify(poData, null, 2));
    
    try {
        const response = await fetch('../api/create_purchase_order.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(poData)
        });
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const result = await response.json();
        console.log('Server response:', result);
        
        if (result.success) {
            alert(result.message || `PO ${result.po_number} created successfully!`);
            closeModal('poModal');
            
            // Reset form
            document.getElementById('poForm').reset();
            
            // Clear items table
            const tbody = document.querySelector('.items-table tbody');
            tbody.innerHTML = '';
            
            // Add one empty row
            addItem();
            
            // Reload the page to show new PO
            window.location.reload();
        } else {
            alert('Error creating PO: ' + (result.error || 'Unknown error'));
        }
    } catch (error) {
        console.error('Error saving PO:', error);
        alert('Error saving purchase order: ' + error.message);
    }
};

// ===== SEARCH FUNCTION =====
window.filterBySearch = function(searchTerm) {
    console.log('Searching for:', searchTerm);
    
    if (!searchTerm.trim()) {
        const urlParams = new URLSearchParams(window.location.search);
        urlParams.delete('search');
        window.location.href = '?' + urlParams.toString();
        return;
    }
    
    const urlParams = new URLSearchParams(window.location.search);
    const currentTab = urlParams.get('status') || 'pending';
    window.location.href = `?status=${currentTab}&search=${encodeURIComponent(searchTerm)}`;
};

// ===== NEW PRODUCT MODAL FUNCTIONS =====
window.openNewProductModal = function() {
    document.getElementById('newProductModal').classList.remove('modal-hidden');
    document.getElementById('newProductModal').classList.add('modal-visible');
    document.getElementById('newProductForm').reset();
};

// Handle category selection
document.getElementById('new_product_category')?.addEventListener('change', function() {
    document.getElementById('newCategoryInput').style.display = this.value === 'new' ? 'block' : 'none';
});

// Handle supplier selection
document.getElementById('new_product_supplier')?.addEventListener('change', function() {
    document.getElementById('newSupplierInput').style.display = this.value === 'new' ? 'block' : 'none';
});

// Add new category
window.addNewCategoryFromProduct = function() {
    const categoryName = document.getElementById('new_category_name').value.trim();
    if (!categoryName) {
        alert('Please enter a category name');
        return;
    }
    
    const select = document.getElementById('new_product_category');
    const newOption = document.createElement('option');
    newOption.value = 'temp_' + Date.now();
    newOption.text = categoryName;
    newOption.selected = true;
    select.appendChild(newOption);
    
    document.getElementById('newCategoryInput').style.display = 'none';
    document.getElementById('new_category_name').value = '';
    alert('Category added!');
};

// Add new supplier
window.addNewSupplierFromProduct = function() {
    const supplierName = document.getElementById('new_supplier_name').value.trim();
    if (!supplierName) {
        alert('Please enter a supplier name');
        return;
    }
    
    const select = document.getElementById('new_product_supplier');
    const newOption = document.createElement('option');
    newOption.value = 'temp_' + Date.now();
    newOption.text = supplierName;
    newOption.selected = true;
    select.appendChild(newOption);
    
    document.getElementById('newSupplierInput').style.display = 'none';
    document.getElementById('new_supplier_name').value = '';
    alert('Supplier added!');
};

// Submit new product request
window.submitNewProductRequest = function() {
    const name = document.getElementById('new_product_name').value.trim();
    const sku = document.getElementById('new_product_sku').value.trim();
    const category = document.getElementById('new_product_category').value;
    const supplier = document.getElementById('new_product_supplier').value;
    const price = document.getElementById('new_product_price').value;
    const quantity = document.getElementById('new_product_quantity').value || 1;
    const reorder = document.getElementById('new_product_reorder').value || 10;
    const description = document.getElementById('new_product_description').value.trim();
    const urgent = document.getElementById('new_product_urgent').checked;
    
    if (!name || !sku || !category || !supplier || !price || !description) {
        alert('Please fill in all required fields');
        return;
    }
    
    const submitBtn = event.target;
    const originalText = submitBtn.innerHTML;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Submitting...';
    submitBtn.disabled = true;
    
    const productData = {
        product_name: name,
        sku: sku,
        category_id: category,
        supplier_id: supplier,
        estimated_price: price,
        initial_quantity: quantity,
        reorder_level: reorder,
        description: description,
        urgent: urgent
    };
    
    console.log('Submitting product request:', productData);
    
    fetch('../api/submit_product_request.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify(productData)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Product request submitted for approval!');
            closeModal('newProductModal');
        } else {
            alert('Error: ' + (data.error || 'Unknown error'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while submitting your request');
    })
    .finally(() => {
        submitBtn.innerHTML = originalText;
        submitBtn.disabled = false;
    });
};

// ===== PRODUCT REQUEST FUNCTIONS =====
window.approveProductRequest = function(requestId) {
    if (confirm('Approve this product request? This will add the product to inventory.')) {
        fetch('../api/approve_product_request.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({id: requestId, action: 'approve'})
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Product approved and added to inventory!');
                window.location.reload();
            } else {
                alert('Error: ' + data.error);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred');
        });
    }
};

window.rejectProductRequest = function(requestId) {
    const reason = prompt('Please provide a reason for rejection:');
    if (reason !== null) {
        fetch('../api/approve_product_request.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({id: requestId, action: 'reject', reason: reason})
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Product request rejected');
                window.location.reload();
            } else {
                alert('Error: ' + data.error);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred');
        });
    }
};

window.viewProductRequest = function(requestId) {
    fetch('../api/get_product_request.php?id=' + requestId)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const req = data.request;
                alert(`
📦 PRODUCT REQUEST DETAILS
─────────────────────────
Product: ${req.product_name}
SKU: ${req.sku}
Category: ${req.category_name || 'N/A'}
Supplier: ${req.supplier_name || 'N/A'}
Price: ₱${parseFloat(req.estimated_price).toFixed(2)}
Quantity: ${req.initial_quantity}
Reorder Level: ${req.reorder_level}
Status: ${req.status.toUpperCase()}
Requested by: ${req.requester_name || 'Unknown'}
Date: ${new Date(req.requested_at).toLocaleDateString()}
${req.review_notes ? `\nNotes: ${req.review_notes}` : ''}
${req.reviewed_at ? `\nReviewed: ${new Date(req.reviewed_at).toLocaleDateString()}` : ''}
                `);
            } else {
                alert('Could not load request details');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error loading request details');
        });
};

// ===== SETUP ON PAGE LOAD =====
document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM loaded, setting up event listeners...');
    
    // Setup search
    const searchInput = document.getElementById('searchPO');
    if (searchInput) {
        let timeout;
        searchInput.addEventListener('input', function() {
            clearTimeout(timeout);
            timeout = setTimeout(() => {
                filterBySearch(this.value);
            }, 500);
        });
        
        searchInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                clearTimeout(timeout);
                filterBySearch(this.value);
            }
        });
    }
    
    // Setup supplier filter
    const supplierSelect = document.getElementById('modalSupplierSelect');
    if (supplierSelect) {
        supplierSelect.addEventListener('change', filterItemsBySupplier);
    }
    
    // Add item count display after the supplier dropdown
    const supplierDropdownContainer = document.querySelector('.form-row .form-group:first-child');
    if (supplierDropdownContainer && !document.getElementById('itemCountDisplay')) {
        const countDisplay = document.createElement('div');
        countDisplay.id = 'itemCountDisplay';
        countDisplay.style.marginTop = '5px';
        countDisplay.style.fontSize = '12px';
        countDisplay.style.color = '#666';
        countDisplay.innerHTML = '<span id="itemCount">0</span> items available for selected supplier';
        supplierDropdownContainer.appendChild(countDisplay);
    }
    
    // Set order date to today if not set
    const orderDate = document.getElementById('orderDate');
    if (orderDate && !orderDate.value) {
        const today = new Date().toISOString().split('T')[0];
        orderDate.value = today;
    }
});
</script>
<script src="../assets/js/orders.js"></script>
</body>
</html>