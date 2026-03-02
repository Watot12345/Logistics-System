-- =====================================================
-- Logistics Management System Database Schema
-- =====================================================

-- Users table
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    employee_id VARCHAR(20) UNIQUE NOT NULL,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(50) NOT NULL,
    phone VARCHAR(20),
    avatar_url VARCHAR(255),    
    role ENUM('admin', 'dispatcher', 'driver', 'employee') NOT NULL DEFAULT 'employee',
    status ENUM('active', 'inactive', 'on-leave') NOT NULL DEFAULT 'active',
    department VARCHAR(100),
    join_date DATE,
    last_login DATETIME,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_role (role),
    INDEX idx_status (status),
    INDEX idx_email (email),
    INDEX idx_employee_id (employee_id)
) ENGINE=InnoDB;

-- User sessions table
CREATE TABLE user_sessions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    session_token VARCHAR(255) UNIQUE NOT NULL,
    ip_address VARCHAR(45),
    user_agent TEXT,
    login_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    logout_time TIMESTAMP NULL,
    is_active BOOLEAN DEFAULT TRUE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_token (session_token),
    INDEX idx_user_active (user_id, is_active)
) ENGINE=InnoDB;

-- User activity logs
CREATE TABLE user_activity_logs (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    action_type VARCHAR(50) NOT NULL,
    action_description TEXT,
    ip_address VARCHAR(45),
    user_agent TEXT,
    module VARCHAR(50),
    reference_id VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_user (user_id),
    INDEX idx_module (module),
    INDEX idx_created (created_at),
    INDEX idx_action_type (action_type)
) ENGINE=InnoDB;

-- =====================================================
-- Inventory Management Tables
-- =====================================================

-- Categories table
CREATE TABLE categories (
    id INT PRIMARY KEY AUTO_INCREMENT,
    category_name VARCHAR(100) NOT NULL,
    category_code VARCHAR(20) UNIQUE NOT NULL,
    description TEXT,
    parent_id INT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (parent_id) REFERENCES categories(id) ON DELETE SET NULL,
    INDEX idx_category_name (category_name),
    INDEX idx_category_code (category_code)
) ENGINE=InnoDB;

-- Suppliers table
CREATE TABLE suppliers (
    id INT PRIMARY KEY AUTO_INCREMENT,
    supplier_code VARCHAR(20) UNIQUE NOT NULL,
    company_name VARCHAR(200) NOT NULL,
    contact_person VARCHAR(100),
    email VARCHAR(100),
    phone VARCHAR(20),
    alternate_phone VARCHAR(20),
    address TEXT,
    city VARCHAR(100),
    state VARCHAR(50),
    postal_code VARCHAR(20),
    country VARCHAR(50) DEFAULT 'USA',
    tax_id VARCHAR(50),
    payment_terms VARCHAR(50),
    rating DECIMAL(3,2) DEFAULT 0,
    total_orders INT DEFAULT 0,
    on_time_delivery_rate DECIMAL(5,2) DEFAULT 0,
    performance_score DECIMAL(5,2) DEFAULT 0,
    status ENUM('active', 'inactive', 'blacklisted') DEFAULT 'active',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_supplier_code (supplier_code),
    INDEX idx_company_name (company_name),
    INDEX idx_rating (rating),
    INDEX_idx_status (status)
) ENGINE=InnoDB;

-- Products/Inventory table
CREATE TABLE products (
    id INT PRIMARY KEY AUTO_INCREMENT,
    sku VARCHAR(50) UNIQUE NOT NULL,
    barcode VARCHAR(100) UNIQUE,
    product_name VARCHAR(255) NOT NULL,
    description TEXT,
    category_id INT,
    supplier_id INT,
    unit_price DECIMAL(15,2) NOT NULL,
    cost_price DECIMAL(15,2),
    quantity INT NOT NULL DEFAULT 0,
    reorder_level INT DEFAULT 10,
    reorder_quantity INT DEFAULT 0,
    max_stock_level INT,
    min_stock_level INT,
    location_warehouse VARCHAR(50),
    location_aisle VARCHAR(20),
    location_bin VARCHAR(20),
    weight DECIMAL(10,2),
    dimensions VARCHAR(50),
    tax_rate DECIMAL(5,2) DEFAULT 0,
    status ENUM('in-stock', 'low-stock', 'out-of-stock', 'discontinued') DEFAULT 'in-stock',
    image_url VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL,
    FOREIGN KEY (supplier_id) REFERENCES suppliers(id) ON DELETE SET NULL,
    INDEX idx_sku (sku),
    INDEX idx_product_name (product_name),
    INDEX idx_category (category_id),
    INDEX idx_supplier (supplier_id),
    INDEX idx_status (status),
    INDEX idx_stock_level (quantity, reorder_level)
) ENGINE=InnoDB;

-- Inventory transactions
CREATE TABLE inventory_transactions (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    transaction_type ENUM('purchase', 'sale', 'adjustment', 'return', 'transfer') NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL,
    previous_quantity INT NOT NULL,
    new_quantity INT NOT NULL,
    unit_price DECIMAL(15,2),
    total_amount DECIMAL(15,2),
    reference_type VARCHAR(50),
    reference_id VARCHAR(50),
    notes TEXT,
    performed_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    FOREIGN KEY (performed_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_product (product_id),
    INDEX idx_type (transaction_type),
    INDEX idx_reference (reference_type, reference_id),
    INDEX idx_created (created_at)
) ENGINE=InnoDB;

-- =====================================================
-- Orders and Procurement Tables
-- =====================================================

-- Purchase Orders table
CREATE TABLE purchase_orders (
    id INT PRIMARY KEY AUTO_INCREMENT,
    po_number VARCHAR(50) UNIQUE NOT NULL,
    supplier_id INT NOT NULL,
    order_date DATE NOT NULL,
    expected_delivery DATE,
    actual_delivery DATE,
    status ENUM('draft', 'pending', 'approved', 'rejected', 'completed', 'cancelled') DEFAULT 'draft',
    priority ENUM('low', 'normal', 'high', 'urgent') DEFAULT 'normal',
    subtotal DECIMAL(15,2) NOT NULL,
    tax_amount DECIMAL(15,2) DEFAULT 0,
    shipping_cost DECIMAL(15,2) DEFAULT 0,
    total_amount DECIMAL(15,2) NOT NULL,
    currency VARCHAR(3) DEFAULT 'USD',
    payment_terms VARCHAR(50),
    shipping_address TEXT,
    notes TEXT,
    approved_by INT,
    approved_at DATETIME,
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (supplier_id) REFERENCES suppliers(id),
    FOREIGN KEY (approved_by) REFERENCES users(id),
    FOREIGN KEY (created_by) REFERENCES users(id),
    INDEX idx_po_number (po_number),
    INDEX idx_supplier (supplier_id),
    INDEX_idx_status (status),
    INDEX_idx_order_date (order_date),
    INDEX_idx_priority (priority)
) ENGINE=InnoDB;

-- Purchase Order Items
CREATE TABLE purchase_order_items (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    po_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL,
    unit_price DECIMAL(15,2) NOT NULL,
    total_price DECIMAL(15,2) NOT NULL,
    received_quantity INT DEFAULT 0,
    status ENUM('pending', 'partial', 'received', 'cancelled') DEFAULT 'pending',
    notes TEXT,
    FOREIGN KEY (po_id) REFERENCES purchase_orders(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id),
    INDEX idx_po (po_id),
    INDEX_idx_product (product_id)
) ENGINE=InnoDB;

-- Suppliers Performance History
CREATE TABLE supplier_performance (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    supplier_id INT NOT NULL,
    po_id INT NOT NULL,
    delivery_date DATE,
    expected_date DATE,
    days_delayed INT,
    quality_rating DECIMAL(3,2),
    communication_rating DECIMAL(3,2),
    overall_rating DECIMAL(3,2),
    notes TEXT,
    evaluated_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (supplier_id) REFERENCES suppliers(id),
    FOREIGN KEY (po_id) REFERENCES purchase_orders(id),
    FOREIGN KEY (evaluated_by) REFERENCES users(id),
    INDEX idx_supplier (supplier_id),
    INDEX_idx_rating (overall_rating)
) ENGINE=InnoDB;

-- =====================================================
-- Fleet Management Tables
-- =====================================================

-- Vehicles table
CREATE TABLE vehicles (
    id INT PRIMARY KEY AUTO_INCREMENT,
    vehicle_code VARCHAR(20) UNIQUE NOT NULL,
    registration_number VARCHAR(20) UNIQUE NOT NULL,
    make VARCHAR(50) NOT NULL,
    model VARCHAR(50) NOT NULL,
    year INT,
    vehicle_type ENUM('truck', 'van', 'trailer', 'special') NOT NULL,
    fuel_type ENUM('diesel', 'petrol', 'electric', 'hybrid') DEFAULT 'diesel',
    capacity_weight DECIMAL(10,2),
    capacity_volume DECIMAL(10,2),
    mileage DECIMAL(10,2) DEFAULT 0,
    fuel_efficiency DECIMAL(5,2),
    last_maintenance DATE,
    next_maintenance DATE,
    status ENUM('available', 'in-use', 'maintenance', 'reserved', 'retired') DEFAULT 'available',
    location VARCHAR(100),
    assigned_driver_id INT,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (assigned_driver_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_registration (registration_number),
    INDEX_idx_status (status),
    INDEX_idx_type (vehicle_type)
) ENGINE=InnoDB;

-- Drivers table (extends users)
CREATE TABLE drivers (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT UNIQUE NOT NULL,
    license_number VARCHAR(50) UNIQUE NOT NULL,
    license_expiry DATE NOT NULL,
    license_class VARCHAR(20),
    hire_date DATE,
    emergency_contact_name VARCHAR(100),
    emergency_contact_phone VARCHAR(20),
    medical_certificate_expiry DATE,
    total_trips INT DEFAULT 0,
    total_distance DECIMAL(10,2) DEFAULT 0,
    driving_hours INT DEFAULT 0,
    safety_score DECIMAL(5,2) DEFAULT 100,
    efficiency_score DECIMAL(5,2) DEFAULT 0,
    rating DECIMAL(3,2) DEFAULT 0,
    status ENUM('active', 'off-duty', 'on-leave', 'suspended') DEFAULT 'active',
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_license (license_number),
    INDEX_idx_status (status)
) ENGINE=InnoDB;

-- Trips table
CREATE TABLE trips (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    trip_number VARCHAR(50) UNIQUE NOT NULL,
    vehicle_id INT NOT NULL,
    driver_id INT NOT NULL,
    start_location VARCHAR(255) NOT NULL,
    end_location VARCHAR(255) NOT NULL,
    start_time DATETIME,
    end_time DATETIME,
    planned_distance DECIMAL(10,2),
    actual_distance DECIMAL(10,2),
    planned_duration INT,
    actual_duration INT,
    fuel_used DECIMAL(10,2),
    fuel_efficiency DECIMAL(5,2),
    cargo_weight DECIMAL(10,2),
    cargo_value DECIMAL(15,2),
    status ENUM('scheduled', 'in-progress', 'completed', 'delayed', 'cancelled') DEFAULT 'scheduled',
    delay_minutes INT DEFAULT 0,
    delay_reason TEXT,
    notes TEXT,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (vehicle_id) REFERENCES vehicles(id),
    FOREIGN KEY (driver_id) REFERENCES drivers(id),
    FOREIGN KEY (created_by) REFERENCES users(id),
    INDEX idx_trip_number (trip_number),
    INDEX_idx_vehicle (vehicle_id),
    INDEX_idx_driver (driver_id),
    INDEX_idx_status (status),
    INDEX_idx_dates (start_time, end_time)
) ENGINE=InnoDB;

-- Vehicle reservations
CREATE TABLE vehicle_reservations (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    reservation_number VARCHAR(50) UNIQUE NOT NULL,
    vehicle_id INT NOT NULL,
    requested_by INT NOT NULL,
    driver_id INT,
    purpose VARCHAR(255),
    start_date DATETIME NOT NULL,
    end_date DATETIME NOT NULL,
    status ENUM('pending', 'approved', 'rejected', 'cancelled', 'completed') DEFAULT 'pending',
    approved_by INT,
    approved_at DATETIME,
    rejection_reason TEXT,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (vehicle_id) REFERENCES vehicles(id),
    FOREIGN KEY (requested_by) REFERENCES users(id),
    FOREIGN KEY (driver_id) REFERENCES drivers(id),
    FOREIGN KEY (approved_by) REFERENCES users(id),
    INDEX idx_reservation (reservation_number),
    INDEX_idx_vehicle (vehicle_id),
    INDEX_idx_status (status),
    INDEX_idx_dates (start_date, end_date)
) ENGINE=InnoDB;

-- Maintenance records
CREATE TABLE maintenance_records (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    vehicle_id INT NOT NULL,
    maintenance_type ENUM('routine', 'repair', 'inspection', 'emergency') NOT NULL,
    description TEXT,
    scheduled_date DATE,
    completed_date DATE,
    odometer_reading DECIMAL(10,2),
    cost DECIMAL(15,2),
    service_provider VARCHAR(200),
    technician_name VARCHAR(100),
    parts_used TEXT,
    status ENUM('scheduled', 'in-progress', 'completed', 'overdue') DEFAULT 'scheduled',
    priority ENUM('low', 'normal', 'high', 'critical') DEFAULT 'normal',
    notes TEXT,
    performed_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (vehicle_id) REFERENCES vehicles(id),
    FOREIGN KEY (performed_by) REFERENCES users(id),
    INDEX_idx_vehicle (vehicle_id),
    INDEX_idx_status (status),
    INDEX_idx_dates (scheduled_date, completed_date)
) ENGINE=InnoDB;

-- Fuel logs
CREATE TABLE fuel_logs (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    vehicle_id INT NOT NULL,
    trip_id BIGINT,
    driver_id INT,
    fuel_date DATETIME NOT NULL,
    quantity DECIMAL(10,2) NOT NULL,
    unit_price DECIMAL(10,2) NOT NULL,
    total_cost DECIMAL(15,2) NOT NULL,
    odometer DECIMAL(10,2),
    fuel_type VARCHAR(20),
    station_name VARCHAR(200),
    location VARCHAR(255),
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (vehicle_id) REFERENCES vehicles(id),
    FOREIGN KEY (trip_id) REFERENCES trips(id),
    FOREIGN KEY (driver_id) REFERENCES drivers(id),
    INDEX_idx_vehicle (vehicle_id),
    INDEX_idx_date (fuel_date)
) ENGINE=InnoDB;

-- =====================================================
-- Documents and Reports Tables
-- =====================================================

-- Documents table
CREATE TABLE documents (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    document_number VARCHAR(50) UNIQUE NOT NULL,
    document_type ENUM('po', 'invoice', 'contract', 'certificate', 'report', 'manual', 'other') NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    file_name VARCHAR(255),
    file_path VARCHAR(500),
    file_size INT,
    mime_type VARCHAR(100),
    reference_type VARCHAR(50),
    reference_id INT,
    version VARCHAR(20),
    is_public BOOLEAN DEFAULT FALSE,
    uploaded_by INT,
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_downloaded DATETIME,
    download_count INT DEFAULT 0,
    status ENUM('active', 'archived', 'deleted') DEFAULT 'active',
    expires_at DATE,
    notes TEXT,
    FOREIGN KEY (uploaded_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_document_number (document_number),
    INDEX_idx_type (document_type),
    INDEX_idx_reference (reference_type, reference_id),
    INDEX_idx_uploaded (uploaded_by)
) ENGINE=InnoDB;

-- Document access logs
CREATE TABLE document_access_logs (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    document_id BIGINT NOT NULL,
    user_id INT,
    access_type ENUM('view', 'download', 'print', 'edit', 'delete') NOT NULL,
    ip_address VARCHAR(45),
    user_agent TEXT,
    accessed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (document_id) REFERENCES documents(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX_idx_document (document_id),
    INDEX_idx_user (user_id),
    INDEX_idx_accessed (accessed_at)
) ENGINE=InnoDB;

-- Reports table
CREATE TABLE reports (
    id INT PRIMARY KEY AUTO_INCREMENT,
    report_name VARCHAR(200) NOT NULL,
    report_type ENUM('inventory', 'orders', 'fleet', 'employee', 'financial', 'performance', 'compliance', 'audit') NOT NULL,
    description TEXT,
    parameters JSON,
    format ENUM('pdf', 'excel', 'csv', 'html') DEFAULT 'pdf',
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    file_path VARCHAR(500),
    is_scheduled BOOLEAN DEFAULT FALSE,
    schedule_frequency ENUM('daily', 'weekly', 'monthly', 'quarterly') NULL,
    schedule_recipients JSON,
    last_generated DATETIME,
    next_generation DATETIME,
    FOREIGN KEY (created_by) REFERENCES users(id),
    INDEX_idx_type (report_type),
    INDEX_idx_created (created_by)
) ENGINE=InnoDB;

-- =====================================================
-- System Settings and Configuration
-- =====================================================

-- System settings
CREATE TABLE system_settings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    setting_key VARCHAR(100) UNIQUE NOT NULL,
    setting_value TEXT,
    setting_type ENUM('text', 'number', 'boolean', 'json', 'email') DEFAULT 'text',
    description TEXT,
    is_public BOOLEAN DEFAULT FALSE,
    updated_by INT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (updated_by) REFERENCES users(id),
    INDEX_idx_key (setting_key)
) ENGINE=InnoDB;

-- Notifications
CREATE TABLE notifications (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    type ENUM('info', 'success', 'warning', 'error') NOT NULL,
    title VARCHAR(255) NOT NULL,
    message TEXT,
    link VARCHAR(500),
    is_read BOOLEAN DEFAULT FALSE,
    read_at DATETIME,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX_idx_user (user_id),
    INDEX_idx_read (user_id, is_read),
    INDEX_idx_created (created_at)
) ENGINE=InnoDB;

CREATE TABLE shipments (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    shipment_number VARCHAR(50) UNIQUE NOT NULL,
    origin VARCHAR(255) NOT NULL,
    destination VARCHAR(255) NOT NULL,
    current_location VARCHAR(255),
    status ENUM(
        'processing',
        'in-transit',
        'delivered',
        'on-hold',
        'cancelled'
    ) DEFAULT 'processing',
    departure_date DATETIME,
    expected_delivery DATETIME,
    actual_delivery DATETIME,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (created_by) REFERENCES users(id),

    INDEX idx_shipment_number (shipment_number),
    INDEX idx_status (status)
);