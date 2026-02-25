<?php
// orders.php
$page_title = 'Orders & Procurement | Logistics System';
$page_css = ['../assets/css/style.css', '../assets/css/orders.css'];
include '../includes/header.php';
?>

        <!-- Page Content -->
        <div class="page-content">
                <!-- Page Header -->
                <div class="page-header">
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <div>
                            <h1>Orders & Procurement</h1>
                            <p>Manage purchase orders, suppliers, and procurement</p>
                        </div>
                        <div class="header-actions">
                            <button class="btn btn-outline" id="refreshData">
                                <i class="fas fa-sync-alt"></i>
                                Refresh
                            </button>
                            <button class="btn btn-primary" onclick="openPOModal()">
                                <i class="fas fa-plus"></i>
                                New Purchase Order
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
                        <p class="stat-value">156</p>
                        <div class="stat-trend">
                            <i class="fas fa-arrow-up"></i> 8% from last month
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-header">
                            <div class="stat-icon amber">
                                <i class="fas fa-clock"></i>
                            </div>
                            <span class="stat-badge amber">12 pending</span>
                        </div>
                        <p class="stat-label">Pending Approval</p>
                        <p class="stat-value">12</p>
                        <div class="stat-trend down">
                            <i class="fas fa-arrow-down"></i> 3 from yesterday
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-header">
                            <div class="stat-icon emerald">
                                <i class="fas fa-truck"></i>
                            </div>
                            <span class="stat-badge green">45 active</span>
                        </div>
                        <p class="stat-label">Active Suppliers</p>
                        <p class="stat-value">45</p>
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
                        <p class="stat-value">$1.2M</p>
                        <div class="stat-trend">
                            <i class="fas fa-arrow-up"></i> 15.5% from last quarter
                        </div>
                    </div>
                </div>
                
                <!-- Tabs -->
                <div class="card" style="margin-bottom: 24px;">
                    <div class="tabs">
                        <button class="tab active" data-tab="pending">Pending Approval</button>
                        <button class="tab" data-tab="approved">Approved</button>
                        <button class="tab" data-tab="all">All Orders</button>
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
                                    <select class="filter-select" id="poStatus">
                                        <option value="all">All Status</option>
                                        <option value="pending">Pending</option>
                                        <option value="approved">Approved</option>
                                        <option value="draft">Draft</option>
                                    </select>
                                    <select class="filter-select" id="poSupplier">
                                        <option value="all">All Suppliers</option>
                                        <option value="tech">Tech Supplies Inc.</option>
                                        <option value="industrial">Industrial Materials</option>
                                    </select>
                                </div>
                                <div class="search-box">
                                    <i class="fas fa-search"></i>
                                    <input type="text" id="searchPO" placeholder="Search orders...">
                                </div>
                            </div>
                            
                            <!-- PO List -->
                            <div class="po-list">
                                <!-- Dynamic content loaded via JS -->
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
                                <!-- Dynamic content loaded via JS -->
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
                                <!-- Dynamic content loaded via JS -->
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
                            <!-- Procurement Stats -->
                            <div class="procurement-stats">
                                <div class="procurement-stat-item">
                                    <div class="procurement-stat-label">Approved</div>
                                    <div class="procurement-stat-value">$234,500</div>
                                    <div class="procurement-stat-trend trend-up">
                                        <i class="fas fa-arrow-up"></i> 12% vs last month
                                    </div>
                                </div>
                                <div class="procurement-stat-item">
                                    <div class="procurement-stat-label">Pending</div>
                                    <div class="procurement-stat-value">$87,200</div>
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
                                <!-- Dynamic content loaded via JS -->
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
    
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
                            <input type="text" class="form-input" value="PO-2024-010" readonly>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label"><i class="fas fa-calendar"></i> Order Date</label>
                            <input type="date" class="form-input" value="2024-02-20">
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label"><i class="fas fa-building"></i> Supplier</label>
                            <select class="form-select">
                                <option>Tech Supplies Inc.</option>
                                <option>Industrial Materials Co.</option>
                                <option>Global Tech Solutions</option>
                                <option>Furniture Direct</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label"><i class="fas fa-tag"></i> Department</label>
                            <select class="form-select">
                                <option>IT Department</option>
                                <option>Operations</option>
                                <option>Maintenance</option>
                                <option>Administration</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-group full-width">
                        <label class="form-label"><i class="fas fa-align-left"></i> Description</label>
                        <textarea class="form-textarea" rows="2" placeholder="Enter order description..."></textarea>
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
                                <th>Item Description</th>
                                <th>Quantity</th>
                                <th>Unit Price</th>
                                <th>Total</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><input type="text" value="Item 1" placeholder="Item name"></td>
                                <td><input type="text" value="10" placeholder="Qty"></td>
                                <td><input type="text" value="1250" placeholder="Price"></td>
                                <td>$12,500.00</td>
                                <td><button class="remove-item" onclick="removeItem(this)"><i class="fas fa-times"></i></button></td>
                            </tr>
                            <tr>
                                <td><input type="text" value="Item 2" placeholder="Item name"></td>
                                <td><input type="text" value="5" placeholder="Qty"></td>
                                <td><input type="text" value="850" placeholder="Price"></td>
                                <td>$4,250.00</td>
                                <td><button class="remove-item" onclick="removeItem(this)"><i class="fas fa-times"></i></button></td>
                            </tr>
                        </tbody>
                    </table>
                    
                    <button type="button" class="add-item-btn" onclick="addItem()">
                        <i class="fas fa-plus"></i>
                        Add Item
                    </button>
                    
                    <div class="totals">
                        <div class="total-row">
                            <span class="total-label">Subtotal:</span>
                            <span class="total-value" id="subtotal">$16,750.00</span>
                        </div>
                        <div class="total-row">
                            <span class="total-label">Tax (10%):</span>
                            <span class="total-value" id="tax">$1,675.00</span>
                        </div>
                        <div class="total-row grand-total">
                            <span class="total-label">Total:</span>
                            <span class="total-value" id="total">$18,425.00</span>
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
                            <label class="form-label"><i class="fas fa-truck"></i> Delivery Date</label>
                            <input type="date" class="form-input">
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label"><i class="fas fa-flag"></i> Priority</label>
                            <select class="form-select">
                                <option>Normal</option>
                                <option>High</option>
                                <option>Urgent</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-group full-width">
                        <label class="form-label"><i class="fas fa-map-marker-alt"></i> Delivery Address</label>
                        <textarea class="form-textarea" rows="2">123 Business Ave, Suite 100, City, State 12345</textarea>
                    </div>
                    
                    <div class="form-group full-width">
                        <label class="form-label"><i class="fas fa-file-alt"></i> Notes</label>
                        <textarea class="form-textarea" rows="2" placeholder="Additional notes..."></textarea>
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
</main>
</div>
    
    <script src="../assets/js/pages/orders.js"></script>
</body>
</html>