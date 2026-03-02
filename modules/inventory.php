<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
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
            <!-- Page Content -->
            <div class="page-content">
                <!-- Page Header -->
                <div class="page-header">
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <div>
                            <h1 class="header-title">Inventory Management</h1>
                            <p class="header-subtitle">Manage your products, stock levels, and inventory items</p>
                        </div>
                        <div class="header-right-content">
                            <span class="last-updated">Last updated: 5 mins ago</span>
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
                            <span class="stat-badge green">+12.5%</span>
                        </div>
                        <p class="stat-label">Total Items</p>
                        <p class="stat-value">1,234</p>
                        <div class="stat-progress">
                            <div class="progress-bar blue" style="width: 75%"></div>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-header">
                            <div class="stat-icon emerald">
                                <i class="fas fa-tags"></i>
                            </div>
                            <span class="stat-badge green">+3 new</span>
                        </div>
                        <p class="stat-label">Categories</p>
                        <p class="stat-value">45</p>
                        <div class="stat-progress">
                            <div class="progress-bar emerald" style="width: 100%"></div>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-header">
                            <div class="stat-icon amber">
                                <i class="fas fa-exclamation-triangle"></i>
                            </div>
                            <span class="stat-badge amber">Need reorder</span>
                        </div>
                        <p class="stat-label">Low Stock Items</p>
                        <p class="stat-value" style="color: #d97706;">23</p>
                        <div class="stat-progress">
                            <div class="progress-bar amber" style="width: 25%"></div>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-header">
                            <div class="stat-icon purple">
                                <i class="fas fa-dollar-sign"></i>
                            </div>
                            <span class="stat-badge green">+5.2%</span>
                        </div>
                        <p class="stat-label">Total Value</p>
                        <p class="stat-value">$45.2K</p>
                        <div class="stat-progress">
                            <div class="progress-bar purple" style="width: 66%"></div>
                        </div>
                    </div>
                </div>
                
                <!-- Filters and Actions -->
                <div class="filters-section">
                    <div class="filter-group">
                        <div class="filter-buttons">
                            <button class="filter-btn active">All</button>
                            <button class="filter-btn">In Stock</button>
                            <button class="filter-btn">Low Stock</button>
                            <button class="filter-btn">Out of Stock</button>
                        </div>
                        
                        <select class="category-select">
                            <option>All Categories</option>
                            <option>Electronics</option>
                            <option>Furniture</option>
                            <option>Clothing</option>
                        </select>
                    </div>
                    
                    <div class="action-buttons">
                        <button class="btn btn-outline">
                            <i class="fas fa-download"></i>
                            Export
                        </button>
                        <button class="btn btn-primary" onclick="openAddModal()">
                            <i class="fas fa-plus"></i>
                            Add Item
                        </button>
                    </div>
                </div>
                
                <!-- Inventory Table -->
                <div class="table-container">
                    <div style="overflow-x: auto;">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>
                                        <input type="checkbox" class="checkbox">
                                    </th>
                                    <th>Item Details</th>
                                    <th>Category</th>
                                    <th>Stock Level</th>
                                    <th>Price</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- Row 1 -->
                                <tr>
                                    <td>
                                        <input type="checkbox" class="checkbox">
                                    </td>
                                    <td>
                                        <div class="item-details">
                                            <div class="item-icon">
                                                <i class="fas fa-laptop"></i>
                                            </div>
                                            <div class="item-info">
                                                <span class="item-name">HP Laptop ProBook</span>
                                                <span class="item-sku">SKU: LPT-HP-001</span>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="category-badge blue">Electronics</span>
                                    </td>
                                    <td>
                                        <div class="stock-level">
                                            <div class="progress-small">
                                                <div class="progress-fill emerald" style="width: 75%"></div>
                                            </div>
                                            <span class="stock-text">45 units</span>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="price">$899.99</span>
                                    </td>
                                    <td>
                                        <span class="status-badge in-stock">
                                            <i class="fas fa-check-circle"></i>
                                            In Stock
                                        </span>
                                    </td>
                                    <td>
                                        <div class="action-group">
                                            <button class="action-btn view" onclick="openViewModal(1)">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <button class="action-btn edit" onclick="openEditModal(1)">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button class="action-btn delete" onclick="deleteItem(1)">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                
                                <!-- Row 2 - Low Stock -->
                                <tr>
                                    <td>
                                        <input type="checkbox" class="checkbox">
                                    </td>
                                    <td>
                                        <div class="item-details">
                                            <div class="item-icon">
                                                <i class="fas fa-chair"></i>
                                            </div>
                                            <div class="item-info">
                                                <span class="item-name">Office Desk Chair</span>
                                                <span class="item-sku">SKU: FUR-001</span>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="category-badge emerald">Furniture</span>
                                    </td>
                                    <td>
                                        <div class="stock-level">
                                            <div class="progress-small">
                                                <div class="progress-fill amber" style="width: 15%"></div>
                                            </div>
                                            <span class="stock-text">5 units</span>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="price">$149.99</span>
                                    </td>
                                    <td>
                                        <span class="status-badge low-stock">
                                            <i class="fas fa-exclamation-circle"></i>
                                            Low Stock
                                        </span>
                                    </td>
                                    <td>
                                        <div class="action-group">
                                            <button class="action-btn view" onclick="openViewModal(2)">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <button class="action-btn edit" onclick="openEditModal(2)">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button class="action-btn delete" onclick="deleteItem(2)">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                
                                <!-- Row 3 - Out of Stock -->
                                <tr>
                                    <td>
                                        <input type="checkbox" class="checkbox">
                                    </td>
                                    <td>
                                        <div class="item-details">
                                            <div class="item-icon">
                                                <i class="fas fa-tshirt"></i>
                                            </div>
                                            <div class="item-info">
                                                <span class="item-name">Cotton T-Shirt (L)</span>
                                                <span class="item-sku">SKU: CLTH-001</span>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="category-badge purple">Clothing</span>
                                    </td>
                                    <td>
                                        <div class="stock-level">
                                            <div class="progress-small">
                                                <div class="progress-fill rose" style="width: 0%"></div>
                                            </div>
                                            <span class="stock-text">0 units</span>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="price">$24.99</span>
                                    </td>
                                    <td>
                                        <span class="status-badge out-of-stock">
                                            <i class="fas fa-times-circle"></i>
                                            Out of Stock
                                        </span>
                                    </td>
                                    <td>
                                        <div class="action-group">
                                            <button class="action-btn view" onclick="openViewModal(3)">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <button class="action-btn edit" onclick="openEditModal(3)">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button class="action-btn delete" onclick="deleteItem(3)">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Pagination -->
                    <div class="pagination">
                        <p class="pagination-info">Showing 1 to 3 of 97 results</p>
                        <div class="pagination-controls">
                            <button class="page-btn">
                                <i class="fas fa-chevron-left"></i>
                            </button>
                            <button class="page-btn active">1</button>
                            <button class="page-btn">2</button>
                            <button class="page-btn">3</button>
                            <button class="page-btn">
                                <i class="fas fa-chevron-right"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
    
    <!-- Add Item Modal -->
    <div id="addModal" class="modal modal-hidden">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">Add New Inventory Item</h3>
                <button class="modal-close" onclick="closeModal('addModal')">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <form>
                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label">Item Name</label>
                        <input type="text" class="form-input" placeholder="Enter item name">
                    </div>
                    <div class="form-group">
                        <label class="form-label">SKU</label>
                        <input type="text" class="form-input" placeholder="Enter SKU">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Category</label>
                        <select class="form-select">
                            <option>Electronics</option>
                            <option>Furniture</option>
                            <option>Clothing</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Quantity</label>
                        <input type="number" class="form-input" placeholder="Enter quantity">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Price</label>
                        <input type="number" step="0.01" class="form-input" placeholder="Enter price">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Reorder Level</label>
                        <input type="number" class="form-input" placeholder="Enter reorder level">
                    </div>
                    <div class="form-group full-width">
                        <label class="form-label">Description</label>
                        <textarea rows="3" class="form-textarea" placeholder="Enter item description"></textarea>
                    </div>
                    <div class="form-group full-width">
                        <label class="form-label">Supplier</label>
                        <select class="form-select">
                            <option>ABC Supplies Inc.</option>
                            <option>Global Traders Ltd.</option>
                            <option>Direct Source Co.</option>
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
            
            <form>
                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label">Item Name</label>
                        <input type="text" class="form-input" value="HP Laptop ProBook">
                    </div>
                    <div class="form-group">
                        <label class="form-label">SKU</label>
                        <input type="text" class="form-input" value="LPT-HP-001">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Category</label>
                        <select class="form-select">
                            <option selected>Electronics</option>
                            <option>Furniture</option>
                            <option>Clothing</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Quantity</label>
                        <input type="number" class="form-input" value="45">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Price</label>
                        <input type="number" step="0.01" class="form-input" value="899.99">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Reorder Level</label>
                        <input type="number" class="form-input" value="10">
                    </div>
                    <div class="form-group full-width">
                        <label class="form-label">Description</label>
                        <textarea rows="3" class="form-textarea">High-performance laptop for business use</textarea>
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
                        <p class="detail-value">HP Laptop ProBook</p>
                    </div>
                    <div>
                        <p class="detail-label">SKU</p>
                        <p class="detail-value">LPT-HP-001</p>
                    </div>
                </div>
                
                <div class="detail-row">
                    <div>
                        <p class="detail-label">Category</p>
                        <p class="detail-value">Electronics</p>
                    </div>
                    <div>
                        <p class="detail-label">Quantity</p>
                        <p class="detail-value">45 units</p>
                    </div>
                </div>
                
                <div class="detail-row">
                    <div>
                        <p class="detail-label">Price</p>
                        <p class="detail-value highlight">$899.99</p>
                    </div>
                    <div>
                        <p class="detail-label">Reorder Level</p>
                        <p class="detail-value">10 units</p>
                    </div>
                </div>
                
                <div class="detail-row">
                    <div>
                        <p class="detail-label">Status</p>
                        <p><span class="status-badge in-stock"><i class="fas fa-check-circle"></i> In Stock</span></p>
                    </div>
                    <div>
                        <p class="detail-label">Supplier</p>
                        <p class="detail-value">ABC Supplies Inc.</p>
                    </div>
                </div>
                
                <div style="margin-bottom: 16px;">
                    <p class="detail-label">Description</p>
                    <p class="description-box">High-performance laptop for business use with Intel Core i7, 16GB RAM, 512GB SSD</p>
                </div>
                
                <div>
                    <p class="detail-label">Last Updated</p>
                    <p class="detail-value">February 20, 2024</p>
                </div>
                
                <div class="modal-footer">
                    <button class="btn btn-outline" onclick="closeModal('viewModal')">Close</button>
                    <button class="btn btn-primary" onclick="openEditModal(1)">Edit Item</button>
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
    
    <!-- JavaScript -->
    <script>
        function toggleSidebar() {
            document.getElementById('sidebar').classList.toggle('active');
        }
        
        function openAddModal() {
            document.getElementById('addModal').classList.remove('modal-hidden');
        }
        
        function openEditModal(id) {
            document.getElementById('editModal').classList.remove('modal-hidden');
        }
        
        function openViewModal(id) {
            document.getElementById('viewModal').classList.remove('modal-hidden');
        }
        
        function deleteItem(id) {
            document.getElementById('deleteModal').classList.remove('modal-hidden');
        }
        
        function closeModal(modalId) {
            document.getElementById(modalId).classList.add('modal-hidden');
        }
        
        function confirmDelete() {
            closeModal('deleteModal');
            alert('Item deleted successfully!');
        }
        
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