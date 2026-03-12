-- =====================================================
-- USERS & AUTHENTICATION MODULE
-- =====================================================

-- Users table: Stores all system users with their roles and permissions
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    employee_id VARCHAR(20) UNIQUE NOT NULL,        -- Unique employee identifier
    username VARCHAR(50) UNIQUE NOT NULL,           -- Login username
    email VARCHAR(100) UNIQUE NOT NULL,             -- User email (also used for login)
    password VARCHAR(255) NOT NULL,                  -- Hashed password
    full_name VARCHAR(50) NOT NULL,                  -- User's full name
    phone VARCHAR(20),                               -- Contact number
    avatar_url VARCHAR(255),                          -- Profile picture path
    role ENUM('admin','dispatcher','driver','fleet_manager','employee','mechanic') NOT NULL DEFAULT 'employee',
    status ENUM('active','inactive','on-leave') NOT NULL DEFAULT 'active',
    department VARCHAR(100),                          -- Department/team
    join_date DATE,                                   -- Date user joined
    last_login DATETIME,                              -- Last login timestamp
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    -- Performance indexes
    INDEX idx_role (role),
    INDEX idx_status (status),
    INDEX idx_email (email),
    INDEX idx_employee_id (employee_id)
) COMMENT='System users and employees';

-- User sessions table: Tracks active user sessions for security
CREATE TABLE user_sessions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    session_token VARCHAR(255) UNIQUE NOT NULL,      -- Unique session identifier
    ip_address VARCHAR(45),                            -- User's IP address
    user_agent TEXT,                                   -- Browser/device info
    login_time TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    logout_time TIMESTAMP NULL,                        -- When user logged out
    is_active BOOLEAN DEFAULT 1,                       -- Session status
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_token (session_token),
    INDEX idx_user_active (user_id, is_active)
) COMMENT='Active user sessions for authentication';

-- =====================================================
-- ASSETS & FLEET MANAGEMENT MODULE
-- =====================================================

-- Assets table: Manages vehicles, equipment, and warehouse assets
CREATE TABLE assets (
    id INT PRIMARY KEY AUTO_INCREMENT,
    asset_name VARCHAR(255) NOT NULL,                  -- Name/plate number
    asset_type ENUM('vehicle','equipment','warehouse','other') NOT NULL,
    status ENUM('good','warning','bad') NOT NULL DEFAULT 'good',  -- Current condition
    asset_condition INT NOT NULL DEFAULT 100,          -- Percentage (0-100)
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_status (status),
    INDEX idx_asset_type (asset_type)
) COMMENT='Fleet vehicles and company assets';

-- Documents table: Stores files related to assets (registrations, insurance, etc.)
CREATE TABLE documents (
    id INT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(255) NOT NULL,                       -- Document title
    document_type VARCHAR(100) NOT NULL,                -- Type (registration, insurance, etc.)
    file_name VARCHAR(255) NOT NULL,                    -- Original filename
    file_path VARCHAR(500) NOT NULL,                    -- Storage path
    file_size INT,                                       -- Size in bytes
    description TEXT,                                    -- Optional description
    asset_id INT,                                        -- Related asset (if any)
    uploaded_by INT,                                     -- User who uploaded
    expiry_date DATE,                                    -- Document expiration
    uploaded_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (asset_id) REFERENCES assets(id) ON DELETE SET NULL,
    FOREIGN KEY (uploaded_by) REFERENCES users(id) ON DELETE SET NULL
) COMMENT='Documents and files attached to assets';

-- User activity logs: Tracks document access for audit trails
CREATE TABLE user_activity_logs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    document_id INT NOT NULL,
    action_type VARCHAR(50) NOT NULL,                   -- 'upload', 'download', 'view', etc.
    timestamp DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (document_id) REFERENCES documents(id)
) COMMENT='Audit log for document access';

-- Maintenance alerts: Tracks vehicle/equipment maintenance tasks
CREATE TABLE maintenance_alerts (
    id INT PRIMARY KEY AUTO_INCREMENT,
    asset_name VARCHAR(255) NOT NULL,                    -- Asset being maintained
    issue VARCHAR(255) NOT NULL,                          -- Description of issue
    issue_type ENUM('minor','major','critical') NOT NULL DEFAULT 'minor',
    priority ENUM('low','medium','high') NOT NULL,
    assigned_mechanic INT,                                -- Mechanic assigned to task
    estimated_hours DECIMAL(5,2),                         -- Estimated repair time
    due_date DATE NOT NULL,                                -- Target completion date
    started_at DATETIME,                                   -- When work started
    completed_date DATE,                                   -- When work completed
    completed_notes TEXT,                                  -- Work summary
    status ENUM('pending','in_progress','completed','cancelled') NOT NULL DEFAULT 'pending',
    created_by INT NOT NULL,                               -- Who created the alert
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (assigned_mechanic) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_assigned_mechanic (assigned_mechanic),
    INDEX idx_status (status),
    INDEX idx_issue_type (issue_type)
) COMMENT='Maintenance tasks and alerts for assets';

-- =====================================================
-- INVENTORY MANAGEMENT MODULE
-- =====================================================

-- Categories: Product categories for inventory items
CREATE TABLE categories (
    id INT PRIMARY KEY AUTO_INCREMENT,
    category_name VARCHAR(100) UNIQUE NOT NULL,
    description TEXT,
    color_class VARCHAR(50),                             -- For UI display
    item_count INT DEFAULT 0,                             -- Denormalized count
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_category_name (category_name)
) COMMENT='Inventory item categories';

-- Suppliers: Vendor/supplier information
CREATE TABLE suppliers (
    id INT PRIMARY KEY AUTO_INCREMENT,
    supplier_name VARCHAR(255) NOT NULL,
    contact_person VARCHAR(255),
    email VARCHAR(255),
    phone VARCHAR(50),
    address TEXT,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_supplier_name (supplier_name)
) COMMENT='Product suppliers and vendors';

-- Inventory items: Main inventory tracking table
CREATE TABLE inventory_items (
    id INT PRIMARY KEY AUTO_INCREMENT,
    item_name VARCHAR(255) NOT NULL,
    sku VARCHAR(50) UNIQUE NOT NULL,                     -- Stock Keeping Unit
    category_id INT,                                       -- Category foreign key
    quantity INT NOT NULL DEFAULT 0,                       -- Current stock
    price DECIMAL(10,2) NOT NULL,                          -- Unit price
    reorder_level INT DEFAULT 10,                          -- Threshold for reorder
    description TEXT,
    supplier_id INT,                                       -- Preferred supplier
    -- Generated column: Automatically calculates stock status
    status ENUM('in_stock','low_stock','out_of_stock') GENERATED ALWAYS AS (
        CASE 
            WHEN quantity <= 0 THEN 'out_of_stock'
            WHEN quantity <= reorder_level THEN 'low_stock'
            ELSE 'in_stock'
        END
    ) STORED,
    last_updated TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id),
    FOREIGN KEY (supplier_id) REFERENCES suppliers(id),
    INDEX idx_sku (sku),
    INDEX idx_category (category_id),
    INDEX idx_status (status),
    INDEX idx_supplier (supplier_id)
) COMMENT='Main inventory items tracking';

-- Stock movements: Audit trail for all inventory changes
CREATE TABLE stock_movements (
    id INT PRIMARY KEY AUTO_INCREMENT,
    item_id INT NOT NULL,
    movement_type ENUM('in','out','adjustment') NOT NULL,
    quantity_change INT NOT NULL,
    previous_quantity INT NOT NULL,
    new_quantity INT NOT NULL,
    notes TEXT,                                            -- Reason for movement
    user_id INT,                                           -- User who made the change
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (item_id) REFERENCES inventory_items(id) ON DELETE CASCADE,
    INDEX idx_item_id (item_id),
    INDEX idx_created_at (created_at)
) COMMENT='Inventory transaction history';

-- Price history: Tracks price changes over time
CREATE TABLE price_history (
    id INT PRIMARY KEY AUTO_INCREMENT,
    item_id INT NOT NULL,
    old_price DECIMAL(10,2) NOT NULL,
    new_price DECIMAL(10,2) NOT NULL,
    changed_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (item_id) REFERENCES inventory_items(id) ON DELETE CASCADE
) COMMENT='Historical record of price changes';

-- =====================================================
-- PURCHASE ORDER MODULE
-- =====================================================

-- Purchase orders: Main purchase order tracking
CREATE TABLE purchase_orders (
    id INT PRIMARY KEY AUTO_INCREMENT,
    po_number VARCHAR(50) UNIQUE NOT NULL,                 -- Unique PO number
    supplier_id INT NOT NULL,
    order_date DATE NOT NULL,
    expected_delivery DATE,
    actual_delivery DATE,
    status ENUM('draft','pending','approved','rejected','completed','cancelled') DEFAULT 'draft',
    priority ENUM('low','normal','high','urgent') DEFAULT 'normal',
    subtotal DECIMAL(15,2) NOT NULL,
    tax_amount DECIMAL(15,2) DEFAULT 0.00,
    shipping_cost DECIMAL(15,2) DEFAULT 0.00,
    total_amount DECIMAL(15,2) NOT NULL,
    notes TEXT,
    approved_by INT,                                        -- User who approved
    approved_at DATETIME,
    created_by INT NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (supplier_id) REFERENCES suppliers(id),
    FOREIGN KEY (approved_by) REFERENCES users(id),
    FOREIGN KEY (created_by) REFERENCES users(id),
    INDEX idx_po_number (po_number),
    INDEX idx_supplier (supplier_id),
    INDEX idx_status (status)
) COMMENT='Purchase orders to suppliers';

-- Purchase order items: Line items within each PO
CREATE TABLE purchase_order_items (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    po_id INT NOT NULL,
    item_id INT NOT NULL,
    quantity INT NOT NULL,
    unit_price DECIMAL(15,2) NOT NULL,
    total_price DECIMAL(15,2) NOT NULL,
    received_quantity INT DEFAULT 0,                        -- Partially received
    status ENUM('pending','partial','received','cancelled') DEFAULT 'pending',
    notes TEXT,
    FOREIGN KEY (po_id) REFERENCES purchase_orders(id) ON DELETE CASCADE,
    FOREIGN KEY (item_id) REFERENCES inventory_items(id),
    INDEX idx_po (po_id),
    INDEX idx_item (item_id)
) COMMENT='Line items within purchase orders';

-- Receiving history: Records of goods received
CREATE TABLE receiving_history (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    po_id INT NOT NULL,
    item_id INT NOT NULL,
    quantity_received INT NOT NULL,
    received_by INT NOT NULL,
    received_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    stock_movement_id INT,                                   -- Links to stock movement
    notes TEXT,
    FOREIGN KEY (po_id) REFERENCES purchase_orders(id),
    FOREIGN KEY (item_id) REFERENCES inventory_items(id),
    FOREIGN KEY (received_by) REFERENCES users(id),
    FOREIGN KEY (stock_movement_id) REFERENCES stock_movements(id)
) COMMENT='History of received items from POs';

-- =====================================================
-- VEHICLE RESERVATIONS & DISPATCH MODULE
-- =====================================================

-- Vehicle reservations: Requests for vehicle usage
CREATE TABLE vehicle_reservations (
    id INT PRIMARY KEY AUTO_INCREMENT,
    vehicle_id INT NOT NULL,
    requester_id INT NOT NULL,                               -- User requesting
    customer_name VARCHAR(255),                               -- Client name (if external)
    delivery_address TEXT,
    department VARCHAR(100),                                  -- Requesting department
    purpose TEXT,                                             -- Reason for reservation
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    start_time TIME,
    end_time TIME,
    status ENUM('pending','approved','rejected','completed') DEFAULT 'pending',
    notes TEXT,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (vehicle_id) REFERENCES assets(id) ON DELETE CASCADE,
    FOREIGN KEY (requester_id) REFERENCES users(id) ON DELETE CASCADE
) COMMENT='Vehicle reservation requests';

-- Dispatch schedule: Scheduled vehicle dispatches
CREATE TABLE dispatch_schedule (
    id INT PRIMARY KEY AUTO_INCREMENT,
    reservation_id INT,                                       -- Linked reservation
    vehicle_id INT NOT NULL,
    driver_id INT,
    scheduled_date DATE NOT NULL,
    shift ENUM('morning','afternoon','night') DEFAULT 'morning',
    status ENUM('scheduled','in-progress','delivered','awaiting_verification','completed','cancelled') DEFAULT 'scheduled',
    notes TEXT,                                               -- Dispatch notes and history
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (reservation_id) REFERENCES vehicle_reservations(id) ON DELETE SET NULL,
    FOREIGN KEY (vehicle_id) REFERENCES assets(id) ON DELETE CASCADE,
    FOREIGN KEY (driver_id) REFERENCES users(id) ON DELETE SET NULL
) COMMENT='Scheduled vehicle dispatches';

-- =====================================================
-- SHIPMENTS & TRACKING MODULE
-- =====================================================

-- Shipments: Main shipment tracking
CREATE TABLE shipments (
    shipment_id INT PRIMARY KEY AUTO_INCREMENT,
    customer_name VARCHAR(255),
    delivery_address TEXT,
    order_id INT,                                            -- Reference to external order
    vehicle_id INT,
    driver_id INT,
    shipment_status ENUM('pending','in_transit','delivered','delayed') DEFAULT 'pending',
    departure_time DATETIME,
    estimated_arrival DATETIME,
    actual_arrival DATETIME,
    current_location VARCHAR(255),
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) COMMENT='Shipment tracking information';

-- Shipment tracking: Real-time location updates
CREATE TABLE shipment_tracking (
    tracking_id INT PRIMARY KEY AUTO_INCREMENT,
    shipment_id INT,
    location VARCHAR(255),                                    -- Current location
    status_update VARCHAR(255),                               -- Status message
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (shipment_id) REFERENCES shipments(shipment_id)
) COMMENT='Real-time shipment location tracking';

-- Shipment delays: Records of delivery delays and reasons
CREATE TABLE shipment_delays (
    id INT PRIMARY KEY AUTO_INCREMENT,
    shipment_id VARCHAR(50),
    driver_id INT,
    route_name VARCHAR(255),
    delay_reason VARCHAR(255),
    delay_minutes VARCHAR(50),
    delay_duration VARCHAR(50),
    delay_type ENUM('traffic','weather','mechanical','loading','accident','other') DEFAULT 'other',
    reported_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    delay_unit ENUM('minutes','hours','days') DEFAULT 'minutes',
    INDEX idx_shipment (shipment_id),
    INDEX idx_driver (driver_id)
) COMMENT='Records of shipment delays and causes';

-- =====================================================
-- DASHBOARD STATISTICS VIEW
-- =====================================================

-- Dashboard stats: Pre-calculated metrics for dashboard display
CREATE VIEW dashboard_stats AS
SELECT 
    (SELECT COUNT(*) FROM inventory_items) AS total_items,
    (SELECT COUNT(*) FROM categories) AS total_categories,
    (SELECT COUNT(*) FROM inventory_items WHERE quantity <= reorder_level AND quantity > 0) AS low_stock_items,
    (SELECT COUNT(*) FROM inventory_items WHERE quantity <= 0) AS out_of_stock_items,
    (SELECT SUM(quantity * price) FROM inventory_items) AS total_inventory_value,
    (SELECT COUNT(DISTINCT supplier_id) FROM inventory_items) AS active_suppliers
COMMENT='Dashboard metrics view for quick statistics';