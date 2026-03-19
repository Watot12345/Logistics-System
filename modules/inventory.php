<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}

// Check user role
$isAdmin = isset($_SESSION['role']) && in_array($_SESSION['role'], ['admin', 'employee', 'fleet_manager', 'dispatcher']);
$isEmployee = isset($_SESSION['role']) && in_array($_SESSION['role'], ['admin', 'employee', 'fleet_manager', 'dispatcher']);

// Include database connection first
require_once '../backend/inventory-function.php';

$inventory = new InventoryManager();


// Handle POST requests (Add, Edit, Delete, Add Category, Add Supplier)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    
    $action = $_POST['action'] ?? '';
    
    // ADD ITEM HANDLER
    if ($action === 'add' && $isAdmin) {
        $data = [
            'item_name' => $_POST['item_name'] ?? '',
            'sku' => $_POST['sku'] ?? '',
            'category' => $_POST['category'] ?? '',
            'quantity' => $_POST['quantity'] ?? 0,
            'price' => $_POST['price'] ?? 0,
            'reorder_level' => $_POST['reorder_level'] ?? 10,
            'description' => $_POST['description'] ?? '',
            'supplier' => $_POST['supplier'] ?? ''
        ];
        $result = $inventory->addItem($data);
        echo json_encode($result);
        exit();
    }
    
    // ADD CATEGORY HANDLER
    if ($action === 'add_category' && $isAdmin) {
        $result = $inventory->addCategory(
            $_POST['category_name'] ?? '', 
            $_POST['color'] ?? 'blue'
        );
        echo json_encode($result);
        exit();
    }
    
    // ADD SUPPLIER HANDLER - Using the OOP method
    if ($action === 'add_supplier' && $isAdmin) {
        $result = $inventory->addSupplier($_POST);
        echo json_encode($result);
        exit();
    }
    
    // Add these handlers after your existing POST handlers in the main file

// DELETE CATEGORY HANDLER
if ($action === 'delete_category' && $isAdmin) {
    $id = $_POST['id'] ?? 0;
    $result = $inventory->deleteCategory($id);
    echo json_encode($result);
    exit();
}

// DELETE SUPPLIER HANDLER
if ($action === 'delete_supplier' && $isAdmin) {
    $id = $_POST['id'] ?? 0;
    $result = $inventory->deleteSupplier($id);
    echo json_encode($result);
    exit();
}

// GET CATEGORY HANDLER
if ($action === 'get_category') {
    $id = $_POST['id'] ?? 0;
    $category = $inventory->getCategoryById($id);
    echo json_encode($category);
    exit();
}

// GET SUPPLIER HANDLER
if ($action === 'get_supplier') {
    $id = $_POST['id'] ?? 0;
    $supplier = $inventory->getSupplierById($id);
    echo json_encode($supplier);
    exit();
}
    // EDIT ITEM HANDLER
    if ($action === 'edit' && $isAdmin) {
        $id = $_POST['id'] ?? 0;
        $data = [
            'item_name' => $_POST['item_name'] ?? '',
            'sku' => $_POST['sku'] ?? '',
            'category' => $_POST['category'] ?? '',
            'quantity' => $_POST['quantity'] ?? 0,
            'price' => $_POST['price'] ?? 0,
            'reorder_level' => $_POST['reorder_level'] ?? 10,
            'description' => $_POST['description'] ?? '',
            'supplier' => $_POST['supplier'] ?? ''
        ];
        $result = $inventory->updateItem($id, $data);
        echo json_encode($result);
        exit();
    }
    
    // DELETE ITEM HANDLER
    if ($action === 'delete' && $isAdmin) {
        $id = $_POST['id'] ?? 0;
        $result = $inventory->deleteItem($id);
        echo json_encode($result);
        exit();
    }
    
    // GET ITEM HANDLER
    if ($action === 'get_item') {
        $id = $_POST['id'] ?? 0;
        $item = $inventory->getItemById($id);
        echo json_encode($item);
        exit();
    }
}

$categories = $inventory->getCategories();
$suppliers = $inventory->getSuppliers();
// Get total count for pagination
$total_items = $inventory->getTotalCount($_GET['filter'] ?? 'all', $_GET['category'] ?? null, $_GET['supplier'] ?? null);

// Calculate pagination
$items_per_page = 10;
$current_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$total_pages = ceil($total_items / $items_per_page);
$start_from = ($current_page - 1) * $items_per_page + 1;
$end_to = min($current_page * $items_per_page, $total_items);

// Get items for current page with filters
$items = $inventory->getInventoryItems(
    $current_page, 
    $items_per_page, 
    $_GET['filter'] ?? 'all', 
    $_GET['category'] ?? null,
    $_GET['supplier'] ?? null
);

// Get data from database for display
$stats = $inventory->getDashboardStats();
$recent_additions = $inventory->getRecentAdditions(7);
$low_stock_count = $inventory->getLowStockCount();

$items_growth = $stats['items_growth'] ?? 0;
$items_growth_color = $items_growth >= 0 ? 'green' : 'red';
$items_growth_sign = $items_growth >= 0 ? '+' : '';
$items_growth_text = $items_growth_sign . $items_growth . '%';

$categories_growth_abs = $stats['categories_growth_abs'] ?? 0;
$categories_growth_color = $categories_growth_abs > 0 ? 'green' : 'gray';
$categories_text = $categories_growth_abs > 0 ? '+' . $categories_growth_abs . ' new' : 'No change';

$low_stock_color = $low_stock_count > 0 ? 'amber' : 'green';
$low_stock_text = $low_stock_count > 0 ? 'Need reorder' : 'Stock OK';

$value_growth = $stats['value_growth'] ?? 0;
$value_growth_color = $value_growth >= 0 ? 'green' : 'red';
$value_growth_sign = $value_growth >= 0 ? '+' : '';
$value_growth_text = $value_growth_sign . $value_growth . '%';

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .btn-icon {
    background: none;
    border: none;
    padding: 4px;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: 4px;
    transition: background-color 0.2s;
}

.btn-icon:hover {
    background-color: rgba(0, 0, 0, 0.05);
}
        .loading {
            opacity: 0.5;
            pointer-events: none;
        }
        .stat-badge.red {
    background: linear-gradient(135deg, #ef4444, #dc2626);
    color: white;
}
    </style>
    <title>Inventory Management | Logistics System</title>
</head>
<body>
    <?php include '../includes/header.php'; ?>
    
    <!-- Main Content -->
    <main class="main-content">
        <header class="header">
            <div class="header-container">
                <div class="header-left">
                    <button class="menu-toggle" onclick="toggleSidebar()">
                        <i class="fas fa-bars"></i>
                    </button>
                    <div class="search-container">
                        <i class="fas fa-search search-icon"></i>
                        <input type="text" class="search-input" placeholder="Search..." id="searchInput" onkeyup="searchItems()">
                    </div>
                </div>
                
                <div class="header-right">
                    <div class="divider"></div>
                    <div class="user-info-header">
                        <span class="user-name-header">
                            <?php echo htmlspecialchars($_SESSION['full_name'] ?? 'User'); ?>
                            <?php if ($isAdmin): ?>
                            <?php elseif ($isEmployee): ?>
                            <?php endif; ?>
                        </span>
                        <div class="avatar-small">
                            <?php
                                $name = $_SESSION['full_name'] ?? 'User';
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
        
        <!-- Page Content -->
        <div class="page-content">
            <!-- Page Header -->
            <div class="page-header">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <div>
                        <h1 class="header-title">Smart Warehousing</h1>
                        <p class="header-subtitle">Manage your products, stock levels, and inventory items</p>
                    </div>
                    <div class="header-right-content">
                        <span class="last-updated">Last updated: <?php echo date('M d, Y H:i'); ?></span>
                        <?php if ($isEmployee): ?>
                            
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Statistics Cards -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-header">
            <div class="stat-icon blue">
                <i class="fas fa-boxes"></i>
            </div>
            <span class="stat-badge <?php echo $items_growth_color; ?>">
                <?php echo $items_growth_text; ?>
            </span>
        </div>
        <p class="stat-label">Total Items</p>
        <p class="stat-value"><?php echo number_format($stats['total_items'] ?? 0); ?></p>
        <div class="stat-progress">
            <div class="progress-bar blue" style="width: 75%"></div>
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-header">
            <div class="stat-icon emerald">
                <i class="fas fa-tags"></i>
            </div>
            <span class="stat-badge <?php echo $categories_growth_color; ?>">
                <?php echo $categories_text; ?>
            </span>
        </div>
        <p class="stat-label">Categories</p>
        <p class="stat-value"><?php echo number_format($stats['total_categories'] ?? 0); ?></p>
        <div class="stat-progress">
            <div class="progress-bar emerald" style="width: 100%"></div>
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-header">
            <div class="stat-icon amber">
                <i class="fas fa-exclamation-triangle"></i>
            </div>
            <span class="stat-badge <?php echo $low_stock_color; ?>">
                <?php echo $low_stock_text; ?>
            </span>
        </div>
        <p class="stat-label">Low Stock Items</p>
        <p class="stat-value" style="color: #d97706;"><?php echo number_format($low_stock_count); ?></p>
        <div class="stat-progress">
            <div class="progress-bar amber" style="width: <?php echo $low_stock_count > 0 ? 25 : 0; ?>%"></div>
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-header">
            <div class="stat-icon purple">
                <i class="fas fa-dollar-sign"></i>
            </div>
            <span class="stat-badge <?php echo $value_growth_color; ?>">
                <?php echo $value_growth_text; ?>
            </span>
        </div>
        <p class="stat-label">Total Value</p>
        <p class="stat-value">₱<?php echo number_format($stats['total_inventory_value'] ?? 0, 2); ?></p>
        <div class="stat-progress">
            <div class="progress-bar purple" style="width: 66%"></div>
        </div>
    </div>
</div>
            
            
<!-- Filter Section -->
<div class="filters-section">
    <div class="filter-group">
        <div class="filter-buttons">
            <a href="?filter=all" class="filter-btn <?php echo (!isset($_GET['filter']) || $_GET['filter'] == 'all') ? 'active' : ''; ?>">All</a>
            <a href="?filter=in_stock" class="filter-btn <?php echo ($_GET['filter'] ?? '') == 'in_stock' ? 'active' : ''; ?>">In Stock</a>
            <a href="?filter=low_stock" class="filter-btn <?php echo ($_GET['filter'] ?? '') == 'low_stock' ? 'active' : ''; ?>">Low Stock</a>
            <a href="?filter=out_of_stock" class="filter-btn <?php echo ($_GET['filter'] ?? '') == 'out_of_stock' ? 'active' : ''; ?>">Out of Stock</a>
            <a href="?view=suppliers" class="filter-btn <?php echo ($_GET['view'] ?? '') == 'suppliers' ? 'active' : ''; ?>">Suppliers Page</a>
        </div>
        
        <div style="display: flex; gap: 8px; align-items: center; flex-wrap: wrap;">
            <!-- Category Filter with Delete -->
            <div style="display: flex; gap: 8px; align-items: center;">
                <select class="category-select" onchange="window.location.href='?category='+this.value<?php echo isset($_GET['supplier']) ? '+\'&supplier='.$_GET['supplier'].'\'' : ''; ?>">
                    <option value="All Categories">All Categories</option>
                    <?php foreach ($categories as $category): ?>
                        <option value="<?php echo htmlspecialchars($category['category_name']); ?>" 
                            <?php echo ($_GET['category'] ?? '') == $category['category_name'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($category['category_name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                
               
                    <div style="display: flex; gap: 4px;">
                        <button class="btn btn-icon" onclick="openAddCategoryModal()" title="Add New Category">
                            <i class="fas fa-plus-circle" style="color: #2563eb; font-size: 24px;"></i>
                        </button>
                        <?php if (isset($_GET['category']) && $_GET['category'] !== 'All Categories'): ?>
                            <?php 
                            $selectedCategory = null;
                            foreach ($categories as $cat) {
                                if ($cat['category_name'] === $_GET['category']) {
                                    $selectedCategory = $cat;
                                    break;
                                }
                            }
                            ?>
                            <?php if ($selectedCategory): ?>
                                <button class="btn btn-icon" onclick="openDeleteCategoryModal(<?php echo $selectedCategory['id']; ?>, '<?php echo htmlspecialchars($selectedCategory['category_name']); ?>')" title="Delete Category">
                                    <i class="fas fa-trash" style="color: #e11d48; font-size: 20px;"></i>
                                </button>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
               
            </div>
            
            <!-- Supplier Filter with Delete -->
            <div style="display: flex; gap: 8px; align-items: center;">
                <select class="category-select" onchange="window.location.href='?supplier='+this.value<?php echo isset($_GET['category']) ? '+\'&category='.$_GET['category'].'\'' : ''; ?>">
                    <option value="All Suppliers">Suppliers Products</option>
                    <?php foreach ($suppliers as $supplier): ?>
                        <option value="<?php echo htmlspecialchars($supplier['supplier_name']); ?>" 
                            <?php echo ($_GET['supplier'] ?? '') == $supplier['supplier_name'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($supplier['supplier_name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                
               
                    <div style="display: flex; gap: 4px;">
                        <button class="btn btn-icon" onclick="openAddSupplierModal()" title="Add New Supplier">
                            <i class="fas fa-plus-circle" style="color: #10b981; font-size: 24px;"></i>
                        </button>
                        <?php if (isset($_GET['supplier']) && $_GET['supplier'] !== 'All Suppliers'): ?>
                            <?php 
                            $selectedSupplier = null;
                            foreach ($suppliers as $sup) {
                                if ($sup['supplier_name'] === $_GET['supplier']) {
                                    $selectedSupplier = $sup;
                                    break;
                                }
                            }
                            ?>
                            <?php if ($selectedSupplier): ?>
                                <button class="btn btn-icon" onclick="openDeleteSupplierModal(<?php echo $selectedSupplier['id']; ?>, '<?php echo htmlspecialchars($selectedSupplier['supplier_name']); ?>')" title="Delete Supplier">
                                    <i class="fas fa-trash" style="color: #e11d48; font-size: 20px;"></i>
                                </button>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
            
            </div>
        </div>
    </div>
    
        <div class="action-buttons">
            <button class="btn btn-outline" onclick="exportData()">
                <i class="fas fa-download"></i>
                Export
            </button>
            
            <?php if ($isAdmin): ?>
                <button class="btn btn-primary" onclick="openAddModal()">
                    <i class="fas fa-plus"></i>
                    Add Item
                </button>
            <?php endif; ?>
        </div>
</div>
<!-- Delete Category Confirmation Modal -->
<div id="deleteCategoryModal" class="modal modal-hidden">
    <div class="modal-content delete-modal">
        <div class="delete-icon">
            <i class="fas fa-exclamation-triangle" style="color: #e11d48;"></i>
        </div>
        <h3 class="delete-title">Delete Category</h3>
        <p class="delete-text" id="deleteCategoryText">Are you sure you want to delete this category? This action cannot be undone.</p>
        <input type="hidden" id="delete_category_id">
        <div class="delete-actions">
            <button class="btn btn-outline" onclick="closeModal('deleteCategoryModal')">Cancel</button>
            <button class="btn btn-primary" style="background: linear-gradient(135deg, #e11d48, #be123c);" onclick="confirmDeleteCategory()">Delete Category</button>
        </div>
    </div>
</div>

<!-- Delete Supplier Confirmation Modal -->
<div id="deleteSupplierModal" class="modal modal-hidden">
    <div class="modal-content delete-modal">
        <div class="delete-icon">
            <i class="fas fa-exclamation-triangle" style="color: #e11d48;"></i>
        </div>
        <h3 class="delete-title">Delete Supplier</h3>
        <p class="delete-text" id="deleteSupplierText">Are you sure you want to delete this supplier? This action cannot be undone.</p>
        <input type="hidden" id="delete_supplier_id">
        <div class="delete-actions">
            <button class="btn btn-outline" onclick="closeModal('deleteSupplierModal')">Cancel</button>
            <button class="btn btn-primary" style="background: linear-gradient(135deg, #e11d48, #be123c);" onclick="confirmDeleteSupplier()">Delete Supplier</button>
        </div>
    </div>
</div>




           <!-- Add Supplier Modal -->
<div id="addSupplierModal" class="modal modal-hidden">
    <div class="modal-content" style="max-width: 400px;">
        <div class="modal-header">
            <h3 class="modal-title">Add New Supplier</h3>
            <button class="modal-close" onclick="closeModal('addSupplierModal')">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <form id="addSupplierForm" onsubmit="return submitAddSupplier(event)">
            <div class="form-group">
                <label class="form-label">Supplier Name</label>
                <input type="text" id="supplier_name" class="form-input" placeholder="Enter supplier name" required>
            </div>
            
            <div class="form-group">
                <label class="form-label">Contact Person (Optional)</label>
                <input type="text" id="contact_person" class="form-input" placeholder="Enter contact person">
            </div>
            
            <div class="form-group">
                <label class="form-label">Email (Optional)</label>
                <input type="email" id="supplier_email" class="form-input" placeholder="Enter email">
            </div>
            
            <div class="form-group">
                <label class="form-label">Phone (Optional)</label>
                <input type="text" id="supplier_phone" class="form-input" placeholder="Enter phone number">
            </div>
            
            <div class="form-group full-width">
                <label class="form-label">Address (Optional)</label>
                <textarea id="supplier_address" rows="2" class="form-textarea" placeholder="Enter address"></textarea>
            </div>
            
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="closeModal('addSupplierModal')">Cancel</button>
                <button type="submit" class="btn btn-primary">Add Supplier</button>
            </div>
        </form>
    </div>
</div>
               <!-- Add Category Modal -->
<div id="addCategoryModal" class="modal modal-hidden">
    <div class="modal-content" style="max-width: 400px;">
        <div class="modal-header">
            <h3 class="modal-title">Add New Category</h3>
            <button class="modal-close" onclick="closeModal('addCategoryModal')">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <form id="addCategoryForm" onsubmit="return submitAddCategory(event)">
            <div class="form-group">
                <label class="form-label">Category Name</label>
                <input type="text" id="category_name" class="form-input" placeholder="Enter category name" required>
            </div>
            
            <div class="form-group">
                <label class="form-label">Color</label>
                <select id="category_color" class="form-select" required>
                    <option value="blue">Blue</option>
                    <option value="emerald">Green</option>
                    <option value="purple">Purple</option>
                    <option value="amber">Orange</option>
                    <option value="rose">Red</option>
                </select>
            </div>
            
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="closeModal('addCategoryModal')">Cancel</button>
                <button type="submit" class="btn btn-primary">Add Category</button>
            </div>
        </form>
    </div>
</div>
                
                    
            
            
            
           <!-- Main Content Area - Shows either Inventory or Suppliers -->
<?php if (isset($_GET['view']) && $_GET['view'] === 'suppliers'): ?>
    
    <!-- SUPPLIERS TABLE - Shows when "All Suppliers" is clicked -->
    <?php if ($isAdmin): ?>
    <div class="suppliers-section">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <div>
                <h2 style="font-size: 1.5rem; font-weight: 600; color: #333;">Supplier Management</h2>
                <p style="color: #666; margin-top: 4px;">View and manage all your suppliers</p>
            </div>
        </div>
        
        <div class="table-container">
            <div style="overflow-x: auto;">
                <table class="table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Supplier Name</th>
                            <th>Contact Person</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Address</th>
                            <th>Products</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        // Get suppliers with product count
                          $suppliers_with_count = $inventory->getSuppliersWithProductCount();
                        ?>
                        
                        <?php if (empty($suppliers_with_count)): ?>
                            <tr>
                                <td colspan="9" style="text-align: center; padding: 40px;">
                                    <i class="fas fa-truck" style="font-size: 48px; color: #ccc; margin-bottom: 16px;"></i>
                                    <p style="color: #666;">No suppliers found</p>
                                    <button class="btn btn-primary" onclick="openAddSupplierModal()" style="margin-top: 16px;">
                                        <i class="fas fa-plus"></i> Add Your First Supplier
                                    </button>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($suppliers_with_count as $supplier): ?>
                            <tr>
                                <td><span style="font-weight: 600;">#<?php echo $supplier['id']; ?></span></td>
                                <td>
                                    <div style="display: flex; align-items: center; gap: 8px;">
                                        <div style="width: 32px; height: 32px; background: linear-gradient(135deg, #10b981, #059669); border-radius: 8px; display: flex; align-items: center; justify-content: center;">
                                            <i class="fas fa-building" style="color: white; font-size: 16px;"></i>
                                        </div>
                                        <span style="font-weight: 600;"><?php echo htmlspecialchars($supplier['supplier_name']); ?></span>
                                    </div>
                                </td>
                                <td><?php echo htmlspecialchars($supplier['contact_person']); ?></td>
                                <td>
                                    <?php if ($supplier['email'] !== '—'): ?>
                                        <a href="mailto:<?php echo htmlspecialchars($supplier['email']); ?>" style="color: #2563eb; text-decoration: none;">
                                            <?php echo htmlspecialchars($supplier['email']); ?>
                                        </a>
                                    <?php else: ?>
                                        <span style="color: #999;">—</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($supplier['phone'] !== '—'): ?>
                                        <span><?php echo htmlspecialchars($supplier['phone']); ?></span>
                                    <?php else: ?>
                                        <span style="color: #999;">—</span>
                                    <?php endif; ?>
                                </td>
                                <td style="max-width: 200px;">
                                    <span style="font-size: 13px; color: #666;">
                                        <?php echo htmlspecialchars($supplier['address']); ?>
                                    </span>
                                </td>
                                <td>
                                    <span style="background: #e0f2fe; color: #0369a1; padding: 4px 8px; border-radius: 12px; font-size: 12px; font-weight: 600;">
                                        <?php echo $supplier['product_count']; ?> items
                                    </span>
                                </td>
                                <td>
                                    <?php 
                                    if (!empty($supplier['created_at'])) {
                                        echo date('M d, Y', strtotime($supplier['created_at']));
                                    } else {
                                        echo '—';
                                    }
                                    ?>
                                </td>
                                <td>
                                    <div class="action-group">
                                        <button class="action-btn view" onclick="viewSupplierDetails(<?php echo $supplier['id']; ?>)" title="View Supplier">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <button class="action-btn edit" onclick="editSupplier(<?php echo $supplier['id']; ?>)" title="Edit Supplier">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button class="action-btn delete" onclick="openDeleteSupplierModal(<?php echo $supplier['id']; ?>, '<?php echo htmlspecialchars($supplier['supplier_name']); ?>')" 
                                            <?php echo $supplier['product_count'] > 0 ? 'disabled title="Cannot delete supplier with existing items"' : ''; ?>>
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php endif; ?>

<?php else: ?>
    
    <!-- INVENTORY TABLE - Shows by default or when filters are used -->
    <div class="table-container">
        <div style="overflow-x: auto;">
            <table class="table">
                <thead>
                    <tr>
                        <th>
                            <input type="checkbox" class="checkbox" id="selectAll" <?php echo $isEmployee ? 'disabled' : ''; ?>>
                        </th>
                        <th>Item Details</th>
                        <th>Category</th>
                        <th>Stock Level</th>
                        <th>Price</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="inventoryTableBody">
                    <?php if (empty($items)): ?>
                        <tr>
                            <td colspan="7" style="text-align: center; padding: 40px;">
                                <i class="fas fa-box-open" style="font-size: 48px; color: #ccc; margin-bottom: 16px;"></i>
                                <p style="color: #666;">No inventory items found</p>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($items as $item): ?>
                        <tr id="row-<?php echo $item['id']; ?>">
                            <td>
                                <input type="checkbox" class="checkbox" value="<?php echo $item['id']; ?>" <?php echo $isEmployee ? 'disabled' : ''; ?>>
                            </td>
                            <td>
                                <div class="item-details">
                                    <div class="item-icon">
                                        <?php
                                        $icon = match($item['category_name'] ?? '') {
                                            'Electronics' => 'laptop',
                                            'Furniture' => 'chair',
                                            'Clothing' => 'tshirt',
                                            default => 'box'
                                        };
                                        ?>
                                        <i class="fas fa-<?php echo $icon; ?>"></i>
                                    </div>
                                    <div class="item-info">
                                        <span class="item-name"><?php echo htmlspecialchars($item['item_name']); ?></span>
                                        <span class="item-sku">SKU: <?php echo htmlspecialchars($item['sku']); ?></span>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <span class="category-badge <?php echo $item['category_color'] ?? 'blue'; ?>">
                                    <?php echo htmlspecialchars($item['category_name'] ?? 'Uncategorized'); ?>
                                </span>
                            </td>
                            <td>
                                <div class="stock-level">
                                    <div class="progress-small">
                                        <?php 
                                        $max_stock = max($item['reorder_level'] * 2, 1);
                                        $percentage = min(100, ($item['quantity'] / $max_stock) * 100);
                                        $colorClass = match($item['status'] ?? '') {
                                            'low_stock' => 'amber',
                                            'out_of_stock' => 'rose',
                                            default => 'emerald'
                                        };
                                        ?>
                                        <div class="progress-fill <?php echo $colorClass; ?>" style="width: <?php echo $percentage; ?>%"></div>
                                    </div>
                                    <span class="stock-text"><?php echo $item['quantity']; ?> units</span>
                                </div>
                            </td>
                            <td>
                                <span class="price">₱<?php echo number_format($item['price'], 2); ?></span>
                            </td>
                            <td>
                        
<?php
$status_class = match($item['status'] ?? 'in_stock') {
    'low_stock' => 'low-stock',
    'out_of_stock' => 'out-of-stock',
    default => 'in-stock'
};
$status_icon = match($item['status'] ?? 'in_stock') {
    'low_stock' => 'exclamation-circle',
    'out_of_stock' => 'times-circle',
    default => 'check-circle'
};
?>
<span class="status-badge <?php echo $status_class; ?>">
    <i class="fas fa-<?php echo $status_icon; ?>"></i>
    <?php echo ucwords(str_replace('_', ' ', $item['status'] ?? 'In Stock')); ?>
</span>
                            </td>
                            <td>
                                <div class="action-group">
                                    <button class="action-btn view" onclick="openViewModal(<?php echo $item['id']; ?>)">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    
                                    <?php if ($isAdmin): ?>
                                        <button class="action-btn edit" onclick="openEditModal(<?php echo $item['id']; ?>)">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button class="action-btn delete" onclick="deleteItem(<?php echo $item['id']; ?>)">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination (only show for inventory, not for suppliers) -->
        <?php if ($total_pages > 1): ?>
        <div class="pagination">
            <p class="pagination-info">
                Showing <?php echo $start_from; ?> to <?php echo $end_to; ?> of <?php echo $total_items; ?> results
            </p>
            <div class="pagination-controls">
                <a href="?page=<?php echo max(1, $current_page - 1); ?>&filter=<?php echo $_GET['filter'] ?? 'all'; ?>&category=<?php echo urlencode($_GET['category'] ?? ''); ?>" 
                   class="page-btn <?php echo $current_page <= 1 ? 'disabled' : ''; ?>">
                    <i class="fas fa-chevron-left"></i>
                </a>
                
                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <a href="?page=<?php echo $i; ?>&filter=<?php echo $_GET['filter'] ?? 'all'; ?>&category=<?php echo urlencode($_GET['category'] ?? ''); ?>" 
                       class="page-btn <?php echo $current_page == $i ? 'active' : ''; ?>">
                        <?php echo $i; ?>
                    </a>
                <?php endfor; ?>
                
                <a href="?page=<?php echo min($total_pages, $current_page + 1); ?>&filter=<?php echo $_GET['filter'] ?? 'all'; ?>&category=<?php echo urlencode($_GET['category'] ?? ''); ?>" 
                   class="page-btn <?php echo $current_page >= $total_pages ? 'disabled' : ''; ?>">
                    <i class="fas fa-chevron-right"></i>
                </a>
            </div>
        </div>
        <?php endif; ?>
    </div>
    
<?php endif; ?>

<!-- View Supplier Modal -->
<div id="viewSupplierModal" class="modal modal-hidden">
    <div class="modal-content" style="max-width: 500px;">
        <div class="modal-header">
            <h3 class="modal-title">Supplier Details</h3>
            <button class="modal-close" onclick="closeModal('viewSupplierModal')">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <div style="padding: 10px;">
            <div style="display: flex; align-items: center; gap: 16px; margin-bottom: 24px;">
                <div style="width: 60px; height: 60px; background: linear-gradient(135deg, #10b981, #059669); border-radius: 12px; display: flex; align-items: center; justify-content: center;">
                    <i class="fas fa-building" style="color: white; font-size: 30px;"></i>
                </div>
                <div>
                    <h2 style="font-size: 20px; font-weight: 600; margin: 0;" id="view_supplier_name"></h2>
                    <p style="color: #666; margin: 4px 0 0;" id="view_supplier_id"></p>
                </div>
            </div>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 16px;">
                <div style="background: #f8f9fa; padding: 12px; border-radius: 8px;">
                    <p style="color: #666; font-size: 12px; margin-bottom: 4px;">Contact Person</p>
                    <p style="font-weight: 500; margin: 0;" id="view_supplier_contact"></p>
                </div>
                <div style="background: #f8f9fa; padding: 12px; border-radius: 8px;">
                    <p style="color: #666; font-size: 12px; margin-bottom: 4px;">Phone</p>
                    <p style="font-weight: 500; margin: 0;" id="view_supplier_phone"></p>
                </div>
            </div>
            
            <div style="background: #f8f9fa; padding: 12px; border-radius: 8px; margin-bottom: 16px;">
                <p style="color: #666; font-size: 12px; margin-bottom: 4px;">Email</p>
                <p style="font-weight: 500; margin: 0;" id="view_supplier_email"></p>
            </div>
            
            <div style="background: #f8f9fa; padding: 12px; border-radius: 8px; margin-bottom: 16px;">
                <p style="color: #666; font-size: 12px; margin-bottom: 4px;">Address</p>
                <p style="font-weight: 500; margin: 0;" id="view_supplier_address"></p>
            </div>
            
            <div style="background: #f8f9fa; padding: 12px; border-radius: 8px; margin-bottom: 16px;">
                <p style="color: #666; font-size: 12px; margin-bottom: 4px;">Products Supplied</p>
                <p style="font-weight: 500; margin: 0;" id="view_supplier_products"></p>
            </div>
            
            <div style="background: #f8f9fa; padding: 12px; border-radius: 8px;">
                <p style="color: #666; font-size: 12px; margin-bottom: 4px;">Created On</p>
                <p style="font-weight: 500; margin: 0;" id="view_supplier_created"></p>
            </div>
            
            <div class="modal-footer" style="margin-top: 20px;">
                <button class="btn btn-outline" onclick="closeModal('viewSupplierModal')">Close</button>
                <button class="btn btn-primary" onclick="editSupplierFromView()">Edit Supplier</button>
            </div>
        </div>
    </div>
</div>
                
                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                <div class="pagination">
                    <p class="pagination-info">
                        Showing <?php echo $start_from; ?> to <?php echo $end_to; ?> of <?php echo $total_items; ?> results
                    </p>
                    <div class="pagination-controls">
                        <a href="?page=<?php echo max(1, $current_page - 1); ?>&filter=<?php echo $_GET['filter'] ?? 'all'; ?>&category=<?php echo urlencode($_GET['category'] ?? ''); ?>" 
                           class="page-btn <?php echo $current_page <= 1 ? 'disabled' : ''; ?>">
                            <i class="fas fa-chevron-left"></i>
                        </a>
                        
                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                            <a href="?page=<?php echo $i; ?>&filter=<?php echo $_GET['filter'] ?? 'all'; ?>&category=<?php echo urlencode($_GET['category'] ?? ''); ?>" 
                               class="page-btn <?php echo $current_page == $i ? 'active' : ''; ?>">
                                <?php echo $i; ?>
                            </a>
                        <?php endfor; ?>
                        
                        <a href="?page=<?php echo min($total_pages, $current_page + 1); ?>&filter=<?php echo $_GET['filter'] ?? 'all'; ?>&category=<?php echo urlencode($_GET['category'] ?? ''); ?>" 
                           class="page-btn <?php echo $current_page >= $total_pages ? 'disabled' : ''; ?>">
                            <i class="fas fa-chevron-right"></i>
                        </a>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <!-- Add Item Modal -->
    <div id="addModal" class="modal modal-hidden">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">Add New Inventory Item</h3>
                <button class="modal-close" onclick="closeModal('addModal')">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <form id="addItemForm" onsubmit="return submitAddItem(event)">
                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label">Item Name</label>
                        <input type="text" id="add_item_name" class="form-input" placeholder="Enter item name" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">SKU</label>
                        <input type="text" id="add_sku" class="form-input" placeholder="Enter SKU" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Category</label>
                        <select id="add_category" class="form-select" required>
                            <option value="">Select Category</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?php echo htmlspecialchars($category['category_name']); ?>">
                                    <?php echo htmlspecialchars($category['category_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Quantity</label>
                        <input type="number" id="add_quantity" class="form-input" placeholder="Enter quantity" required min="0">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Price</label>
                        <input type="number" id="add_price" step="0.01" class="form-input" placeholder="Enter price" required min="0">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Reorder Level</label>
                        <input type="number" id="add_reorder_level" class="form-input" placeholder="Enter reorder level" required min="0">
                    </div>
                    <div class="form-group full-width">
                        <label class="form-label">Description</label>
                        <textarea id="add_description" rows="3" class="form-textarea" placeholder="Enter item description"></textarea>
                    </div>
                    <div class="form-group full-width">
                        <label class="form-label">Supplier</label>
                        <select id="add_supplier" class="form-select" required>
                            <option value="">Select Supplier</option>
                            <?php foreach ($suppliers as $supplier): ?>
                                <option value="<?php echo htmlspecialchars($supplier['supplier_name']); ?>">
                                    <?php echo htmlspecialchars($supplier['supplier_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline" onclick="closeModal('addModal')">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Item</button>
                </div>
            </form>
        </div>
    </div>
    
  <!-- Edit Item Modal -->
<div id="editModal" class="modal modal-hidden">
    <div class="modal-content">
        <div class="modal-header">
            <h3 class="modal-title">Edit Inventory Item</h3>
            <button class="modal-close" onclick="closeModal('editModal')">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <form id="editItemForm" onsubmit="return submitEditItem(event)">
            <input type="hidden" id="edit_item_id">
            <div class="form-grid">
                <div class="form-group">
                    <label class="form-label">Item Name</label>
                    <input type="text" id="edit_item_name" class="form-input" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">SKU</label>
                    <input type="text" id="edit_sku" class="form-input" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Category</label>
                    <select id="edit_category" class="form-select" required>
                        <option value="">Select Category</option>
                        <?php if (!empty($categories)): ?>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?php echo htmlspecialchars($category['category_name']); ?>">
                                    <?php echo htmlspecialchars($category['category_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <option value="">No categories available</option>
                        <?php endif; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Quantity</label>
                    <input type="number" id="edit_quantity" class="form-input" required min="0">
                </div>
                
                <div class="form-group">
                    <label class="form-label">Price</label>
                    <input type="number" id="edit_price" step="0.01" class="form-input" required min="0">
                </div>
                
                <div class="form-group">
                    <label class="form-label">Reorder Level</label>
                    <input type="number" id="edit_reorder_level" class="form-input" required min="0">
                </div>
                
                <div class="form-group full-width">
                    <label class="form-label">Description</label>
                    <textarea id="edit_description" rows="3" class="form-textarea"></textarea>
                </div>
                
                <div class="form-group full-width">
                    <label class="form-label">Supplier</label>
                    <select id="edit_supplier" class="form-select" required>
                        <option value="">Select Supplier</option>
                        <?php if (!empty($suppliers)): ?>
                            <?php foreach ($suppliers as $supplier): ?>
                                <option value="<?php echo htmlspecialchars($supplier['supplier_name']); ?>">
                                    <?php echo htmlspecialchars($supplier['supplier_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <option value="">No suppliers available</option>
                        <?php endif; ?>
                    </select>
                </div>
            </div>
            
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="closeModal('editModal')">Cancel</button>
                <button type="submit" class="btn btn-primary">Save Changes</button>
            </div>
        </form>
    </div>
</div>
    
    <!-- View Item Modal -->
    <div id="viewModal" class="modal modal-hidden">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">Item Details</h3>
                <button class="modal-close" onclick="closeModal('viewModal')">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <div>
                <div class="detail-row">
                    <div>
                        <p class="detail-label">Item Name</p>
                        <p class="detail-value" id="view_item_name"></p>
                    </div>
                    <div>
                        <p class="detail-label">SKU</p>
                        <p class="detail-value" id="view_sku"></p>
                    </div>
                </div>
                
                <div class="detail-row">
                    <div>
                        <p class="detail-label">Category</p>
                        <p class="detail-value" id="view_category"></p>
                    </div>
                    <div>
                        <p class="detail-label">Quantity</p>
                        <p class="detail-value" id="view_quantity"></p>
                    </div>
                </div>
                
                <div class="detail-row">
                    <div>
                        <p class="detail-label">Price</p>
                        <p class="detail-value highlight" id="view_price"></p>
                    </div>
                    <div>
                        <p class="detail-label">Reorder Level</p>
                        <p class="detail-value" id="view_reorder_level"></p>
                    </div>
                </div>
                
                <div class="detail-row">
                    <div>
                        <p class="detail-label">Status</p>
                        <p id="view_status"></p>
                    </div>
                    <div>
                        <p class="detail-label">Supplier</p>
                        <p class="detail-value" id="view_supplier"></p>
                    </div>
                </div>
                
                <div style="margin-bottom: 16px;">
                    <p class="detail-label">Description</p>
                    <p class="description-box" id="view_description"></p>
                </div>
                
                <div>
                    <p class="detail-label">Last Updated</p>
                    <p class="detail-value" id="view_last_updated"></p>
                </div>
<div class="stock-history" style="margin-top: 20px;">
    <h4>Recent Stock Movements</h4>
    <div id="stock_history"></div>
</div>
                <div class="modal-footer">
                    <button class="btn btn-outline" onclick="closeModal('viewModal')">Close</button>
                    <?php if ($isAdmin): ?>
                        <button class="btn btn-primary" onclick="openEditModalFromView()">Edit Item</button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Delete Confirmation Modal -->
    <div id="deleteModal" class="modal modal-hidden">
        <div class="modal-content delete-modal">
            <div class="delete-icon">
                <i class="fas fa-exclamation-triangle"></i>
            </div>
            <h3 class="delete-title">Delete Item</h3>
            <p class="delete-text">Are you sure you want to delete this item? This action cannot be undone.</p>
            <div class="delete-actions">
                <button class="btn btn-outline" onclick="closeModal('deleteModal')">Cancel</button>
                <button class="btn btn-primary" style="background: linear-gradient(135deg, #e11d48, #be123c);" onclick="confirmDelete()">Delete</button>
            </div>
        </div>
    </div>
    
    <script>
        let currentViewItemId = null;
        let itemToDelete = null;
        // View Supplier Details
let currentViewSupplierId = null;

function viewSupplierDetails(id) {
    currentViewSupplierId = id;
    
    const formData = new FormData();
    formData.append('action', 'get_supplier');
    formData.append('id', id);
    
    fetch(window.location.href, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data) {
            document.getElementById('view_supplier_name').textContent = data.supplier_name;
            document.getElementById('view_supplier_id').textContent = `Supplier ID: #${data.id}`;
            document.getElementById('view_supplier_contact').textContent = data.contact_person || '—';
            document.getElementById('view_supplier_phone').textContent = data.phone || '—';
            document.getElementById('view_supplier_email').textContent = data.email || '—';
            document.getElementById('view_supplier_address').textContent = data.address || '—';
            document.getElementById('view_supplier_products').textContent = (data.product_count || 0) + ' items';
            
            if (data.created_at) {
                const date = new Date(data.created_at);
                document.getElementById('view_supplier_created').textContent = date.toLocaleDateString('en-US', { 
                    year: 'numeric', 
                    month: 'long', 
                    day: 'numeric' 
                });
            } else {
                document.getElementById('view_supplier_created').textContent = '—';
            }
            
            document.getElementById('viewSupplierModal').classList.remove('modal-hidden');
        }
    });
}

function editSupplierFromView() {
    closeModal('viewSupplierModal');
    if (currentViewSupplierId) {
        editSupplier(currentViewSupplierId);
    }
}

// Edit Supplier function (update your existing one)
function editSupplier(id) {
    const formData = new FormData();
    formData.append('action', 'get_supplier');
    formData.append('id', id);
    
    fetch(window.location.href, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data) {
            // You'll need to create an edit supplier modal first
            alert('Edit functionality coming soon!');
        }
    });
}
        
        function toggleSidebar() {
            document.getElementById('sidebar').classList.toggle('active');
        }
        
        function openAddModal() {
            <?php if ($isAdmin): ?>
                document.getElementById('addModal').classList.remove('modal-hidden');
                document.getElementById('addItemForm').reset();
            <?php else: ?>
                alert('Only administrators can add items.');
            <?php endif; ?>
        }
        
        function submitAddItem(event) {
            event.preventDefault();
            
            const formData = new FormData();
            formData.append('action', 'add');
            formData.append('item_name', document.getElementById('add_item_name').value);
            formData.append('sku', document.getElementById('add_sku').value);
            formData.append('category', document.getElementById('add_category').value);
            formData.append('quantity', document.getElementById('add_quantity').value);
            formData.append('price', document.getElementById('add_price').value);
            formData.append('reorder_level', document.getElementById('add_reorder_level').value);
            formData.append('description', document.getElementById('add_description').value);
            formData.append('supplier', document.getElementById('add_supplier').value);
            
            const submitBtn = event.target.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Adding...';
            submitBtn.disabled = true;
            
            fetch(window.location.href, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Item added successfully!');
                    closeModal('addModal');
                    location.reload();
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while adding the item.');
            })
            .finally(() => {
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
            });
            
            return false;
        }
        
        function openEditModal(id) {
            <?php if ($isAdmin): ?>
                const formData = new FormData();
                formData.append('action', 'get_item');
                formData.append('id', id);
                
                fetch(window.location.href, {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data) {
                        document.getElementById('edit_item_id').value = data.id;
                        document.getElementById('edit_item_name').value = data.item_name;
                        document.getElementById('edit_sku').value = data.sku;
                        document.getElementById('edit_category').value = data.category_name;
                        document.getElementById('edit_quantity').value = data.quantity;
                        document.getElementById('edit_price').value = data.price;
                        document.getElementById('edit_reorder_level').value = data.reorder_level;
                        document.getElementById('edit_description').value = data.description;
                        document.getElementById('edit_supplier').value = data.supplier_name;
                        
                        document.getElementById('editModal').classList.remove('modal-hidden');
                    }
                });
            <?php else: ?>
                alert('Only administrators can edit items.');
            <?php endif; ?>
        }
        
        function submitEditItem(event) {
            event.preventDefault();
            
            const formData = new FormData();
            formData.append('action', 'edit');
            formData.append('id', document.getElementById('edit_item_id').value);
            formData.append('item_name', document.getElementById('edit_item_name').value);
            formData.append('sku', document.getElementById('edit_sku').value);
            formData.append('category', document.getElementById('edit_category').value);
            formData.append('quantity', document.getElementById('edit_quantity').value);
            formData.append('price', document.getElementById('edit_price').value);
            formData.append('reorder_level', document.getElementById('edit_reorder_level').value);
            formData.append('description', document.getElementById('edit_description').value);
            formData.append('supplier', document.getElementById('edit_supplier').value);
            
            const submitBtn = event.target.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';
            submitBtn.disabled = true;
            
            fetch(window.location.href, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Item updated successfully!');
                    closeModal('editModal');
                    location.reload();
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while updating the item.');
            })
            .finally(() => {
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
            });
            
            return false;
        }
        
        function openViewModal(id) {
            currentViewItemId = id;
            
            const formData = new FormData();
            formData.append('action', 'get_item');
            formData.append('id', id);
            
            fetch(window.location.href, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data) {
                    document.getElementById('view_item_name').textContent = data.item_name;
                    document.getElementById('view_sku').textContent = data.sku;
                    document.getElementById('view_category').textContent = data.category_name;
                    document.getElementById('view_quantity').textContent = data.quantity + ' units';
                    document.getElementById('view_price').textContent = '$' + parseFloat(data.price).toFixed(2);
                    document.getElementById('view_reorder_level').textContent = data.reorder_level + ' units';
                    document.getElementById('view_supplier').textContent = data.supplier_name;
                    document.getElementById('view_description').textContent = data.description || 'No description';
                    
                    const statusBadge = getStatusBadge(data.status);
                    document.getElementById('view_status').innerHTML = statusBadge;
                    
                    const date = new Date(data.last_updated);
                    document.getElementById('view_last_updated').textContent = date.toLocaleDateString('en-US', { 
                        year: 'numeric', 
                        month: 'long', 
                        day: 'numeric' 
                    });
                    
                    document.getElementById('viewModal').classList.remove('modal-hidden');
                }
            });
        }
        
        function openEditModalFromView() {
            closeModal('viewModal');
            openEditModal(currentViewItemId);
        }
        
        function getStatusBadge(status) {
    const badges = {
        'in_stock': '<span class="status-badge in-stock"><i class="fas fa-check-circle"></i> In Stock</span>',
        'low_stock': '<span class="status-badge low-stock"><i class="fas fa-exclamation-circle"></i> Low Stock</span>',
        'out_of_stock': '<span class="status-badge out-of-stock"><i class="fas fa-times-circle"></i> Out of Stock</span>'
    };
    return badges[status] || badges['in_stock'];
}
        
        function deleteItem(id) {
            <?php if ($isAdmin): ?>
                itemToDelete = id;
                document.getElementById('deleteModal').classList.remove('modal-hidden');
            <?php else: ?>
                alert('Only administrators can delete items.');
            <?php endif; ?>
        }
        
        function confirmDelete() {
            <?php if ($isAdmin): ?>
                const formData = new FormData();
                formData.append('action', 'delete');
                formData.append('id', itemToDelete);
                
                fetch(window.location.href, {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Item deleted successfully!');
                        closeModal('deleteModal');
                        location.reload();
                    } else {
                        alert('Error: ' + data.message);
                    }
                });
            <?php endif; ?>
        }
        
        function closeModal(modalId) {
            document.getElementById(modalId).classList.add('modal-hidden');
        }
        
        function exportData() {
            window.location.href = '../backend/export.php?format=csv';
        }
        
        function searchItems() {
            const searchTerm = document.getElementById('searchInput').value.toLowerCase();
            const rows = document.querySelectorAll('#inventoryTableBody tr');
            
            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(searchTerm) ? '' : 'none';
            });
        }
        function openAddCategoryModal() {
    document.getElementById('addCategoryModal').classList.remove('modal-hidden');
    document.getElementById('addCategoryForm').reset();
}

function openAddSupplierModal() {
    document.getElementById('addSupplierModal').classList.remove('modal-hidden');
    document.getElementById('addSupplierForm').reset();
}

function submitAddSupplier(event) {
    event.preventDefault();
    
    const formData = new FormData();
    formData.append('action', 'add_supplier');
    formData.append('supplier_name', document.getElementById('supplier_name').value);
    formData.append('contact_person', document.getElementById('contact_person').value);
    formData.append('email', document.getElementById('supplier_email').value);
    formData.append('phone', document.getElementById('supplier_phone').value);
    formData.append('address', document.getElementById('supplier_address').value);
    
    const submitBtn = event.target.querySelector('button[type="submit"]');
    const originalText = submitBtn.innerHTML;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Adding...';
    submitBtn.disabled = true;
    
    fetch(window.location.href, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Supplier added successfully!');
            closeModal('addSupplierModal');
            location.reload();
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while adding the supplier.');
    })
    .finally(() => {
        submitBtn.innerHTML = originalText;
        submitBtn.disabled = false;
    });
    
    return false;
}

// Add these functions to your JavaScript section

// Category Management Functions
function openDeleteCategoryModal(id, categoryName) {
    <?php if ($isAdmin): ?>
        document.getElementById('delete_category_id').value = id;
        document.getElementById('deleteCategoryText').textContent = 
            `Are you sure you want to delete the category "${categoryName}"? This action cannot be undone.`;
        document.getElementById('deleteCategoryModal').classList.remove('modal-hidden');
    <?php else: ?>
        alert('Only administrators can delete categories.');
    <?php endif; ?>
}

function confirmDeleteCategory() {
    const id = document.getElementById('delete_category_id').value;
    
    const formData = new FormData();
    formData.append('action', 'delete_category');
    formData.append('id', id);
    
    const deleteBtn = document.querySelector('#deleteCategoryModal .btn-primary');
    const originalText = deleteBtn.innerHTML;
    deleteBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Deleting...';
    deleteBtn.disabled = true;
    
    fetch(window.location.href, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Category deleted successfully!');
            closeModal('deleteCategoryModal');
            location.reload();
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while deleting the category.');
    })
    .finally(() => {
        deleteBtn.innerHTML = originalText;
        deleteBtn.disabled = false;
    });
}

// Supplier Management Functions
function openDeleteSupplierModal(id, supplierName) {
    <?php if ($isAdmin): ?>
        // First check if supplier has items
        fetch(`check-supplier-items.php?supplier_id=${id}`)
            .then(response => response.json())
            .then(data => {
                if (data.has_items) {
                    // Show alert that supplier has items
                    alert(`Cannot delete supplier "${supplierName}" because they have ${data.item_count} item(s). Please remove or reassign the items first.`);
                } else {
                    // No items - show delete modal
                    document.getElementById('delete_supplier_id').value = id;
                    document.getElementById('deleteSupplierText').textContent = 
                        `Are you sure you want to delete the supplier "${supplierName}"? This action cannot be undone.`;
                    document.getElementById('deleteSupplierModal').classList.remove('modal-hidden');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error checking supplier status. Please try again.');
            });
    <?php else: ?>
        alert('Only administrators can delete suppliers.');
    <?php endif; ?>
}

function confirmDeleteSupplier() {
    const id = document.getElementById('delete_supplier_id').value;
    
    const formData = new FormData();
    formData.append('action', 'delete_supplier');
    formData.append('id', id);
    
    const deleteBtn = document.querySelector('#deleteSupplierModal .btn-primary');
    const originalText = deleteBtn.innerHTML;
    deleteBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Deleting...';
    deleteBtn.disabled = true;
    
    fetch(window.location.href, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Supplier deleted successfully!');
            closeModal('deleteSupplierModal');
            location.reload();
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while deleting the supplier.');
    })
    .finally(() => {
        deleteBtn.innerHTML = originalText;
        deleteBtn.disabled = false;
    });
}

function submitAddCategory(event) {
    event.preventDefault();
    
    const formData = new FormData();
    formData.append('action', 'add_category');
    formData.append('category_name', document.getElementById('category_name').value);
    formData.append('color', document.getElementById('category_color').value);
    
    const submitBtn = event.target.querySelector('button[type="submit"]');
    const originalText = submitBtn.innerHTML;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Adding...';
    submitBtn.disabled = true;
    
    fetch(window.location.href, {
        method: 'POST',
        body: formData
    })
    .then(response => response.text()) // Get as text first, not JSON
    .then(text => {
        console.log('Raw response:', text); // Log the raw response
        console.log('Response length:', text.length);
        console.log('First 100 chars:', text.substring(0, 100));
        
        try {
            const data = JSON.parse(text);
            if (data.success) {
                alert('Category added successfully!');
                closeModal('addCategoryModal');
                location.reload();
            } else {
                alert('Error: ' + (data.message || 'Unknown error'));
            }
        } catch (e) {
            console.error('JSON parse error:', e);
            // Show what was returned
            alert('Server returned non-JSON data. Check console for details.');
        }
    })
    .catch(error => {
        console.error('Fetch error:', error);
        alert('Network error: ' + error.message);
    })
    .finally(() => {
        submitBtn.innerHTML = originalText;
        submitBtn.disabled = false;
    });
    
    return false;
}
        
        // Select all checkbox
        document.getElementById('selectAll')?.addEventListener('change', function(e) {
            <?php if ($isAdmin): ?>
                const checkboxes = document.querySelectorAll('.checkbox:not(#selectAll)');
                checkboxes.forEach(cb => cb.checked = e.target.checked);
            <?php endif; ?>
        });
        
        // Close modal when clicking outside
        window.onclick = function(event) {
            if (event.target.classList.contains('modal')) {
                event.target.classList.add('modal-hidden');
            }
        }
        
        // Escape key to close modal
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                const modals = document.querySelectorAll('.modal:not(.modal-hidden)');
                modals.forEach(modal => {
                    modal.classList.add('modal-hidden');
                });
            }
        });
    </script>
</body>
</html>