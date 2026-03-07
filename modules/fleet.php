<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}
require_once '../config/db.php';

// Get real statistics
try {
    // Total vehicles from assets table
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM assets WHERE asset_type = 'vehicle'");
    $total_vehicles = $stmt->fetch()['total'] ?? 0;
    
    // Available vehicles (status = 'good')
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM assets WHERE asset_type = 'vehicle' AND status = 'good'");
    $available_vehicles = $stmt->fetch()['total'] ?? 0;
    
    // Vehicles in maintenance (status = 'warning' or 'bad')
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM assets WHERE asset_type = 'vehicle' AND status IN ('warning', 'bad')");
    $maintenance_vehicles = $stmt->fetch()['total'] ?? 0;
    
    // Get active shipments
    $stmt = $pdo->query("
        SELECT COUNT(*) as total, 
               SUM(CASE WHEN shipment_status = 'in_transit' THEN 1 ELSE 0 END) as in_transit,
               SUM(CASE WHEN shipment_status = 'delivered' THEN 1 ELSE 0 END) as delivered
        FROM shipments 
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
    ");
    $shipment_stats = $stmt->fetch();
    
    // Calculate efficiency (delivered / total shipments)
    $total_shipments = $shipment_stats['total'] ?? 0;
    $delivered = $shipment_stats['delivered'] ?? 0;
    $fleet_efficiency = $total_shipments > 0 ? round(($delivered / $total_shipments) * 100) : 94;
    
    // Get vehicles for availability
    $stmt = $pdo->query("
        SELECT a.*, 
               u.full_name as current_driver,
               s.shipment_status
        FROM assets a
        LEFT JOIN shipments s ON a.id = s.vehicle_id AND s.shipment_status IN ('in_transit', 'pending')
        LEFT JOIN users u ON s.driver_id = u.id
        WHERE a.asset_type = 'vehicle'
        ORDER BY a.created_at DESC
    ");
    $vehicles = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get maintenance alerts
    $stmt = $pdo->query("
        SELECT * FROM maintenance_alerts 
        WHERE status = 'pending' 
        ORDER BY 
            CASE priority 
                WHEN 'high' THEN 1
                WHEN 'medium' THEN 2
                WHEN 'low' THEN 3
            END,
            due_date ASC
    ");
    $maintenance_alerts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get drivers (users with role 'driver')
    $stmt = $pdo->query("
        SELECT id, full_name, employee_id, status 
        FROM users 
        WHERE role = 'driver' AND status = 'active'
        ORDER BY full_name
    ");
    $drivers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get recent trips/shipments
    $stmt = $pdo->query("
        SELECT s.*, 
               a.asset_name as vehicle_name,
               u.full_name as driver_name
        FROM shipments s
        LEFT JOIN assets a ON s.vehicle_id = a.id
        LEFT JOIN users u ON s.driver_id = u.id
        ORDER BY s.created_at DESC
        LIMIT 10
    ");
    $recent_trips = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get fleet condition summary
    $stmt = $pdo->query("
        SELECT 
            SUM(CASE WHEN asset_condition >= 70 THEN 1 ELSE 0 END) as excellent,
            SUM(CASE WHEN asset_condition >= 50 AND asset_condition < 70 THEN 1 ELSE 0 END) as good,
            SUM(CASE WHEN asset_condition >= 30 AND asset_condition < 50 THEN 1 ELSE 0 END) as fair,
            SUM(CASE WHEN asset_condition < 30 THEN 1 ELSE 0 END) as poor
        FROM assets 
        WHERE asset_type = 'vehicle'
    ");
    $condition_stats = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Get vehicle reservations (if you have a reservations table)
    // For now, we'll use sample data
    
} catch (PDOException $e) {
    error_log("Fleet page error: " . $e->getMessage());
    $total_vehicles = 0;
    $available_vehicles = 0;
    $maintenance_vehicles = 0;
    $fleet_efficiency = 0;
    $vehicles = [];
    $maintenance_alerts = [];
    $drivers = [];
    $recent_trips = [];
    $condition_stats = ['excellent' => 0, 'good' => 0, 'fair' => 0, 'poor' => 0];
}
$page_title = 'Fleet Management | Logistics System';
$page_css = ['../assets/css/style.css', '../assets/css/fleet.css'];
include '../includes/header.php';
?>
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
                <!-- Page Header -->
                <div class="page-header">
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <div>
                            <h1>Fleet & Transportation Operations</h1>
                            <p>Monitor vehicles, drivers, and fleet operations</p>
                        </div>
                        <div class="header-actions">
                        </div>
                    </div>
                </div>
                
               
           <!-- Statistics Cards - HIDDEN from drivers -->
<?php if ($_SESSION['role'] !== 'driver'): ?>
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-header">
            <div class="stat-icon blue">
                <i class="fas fa-truck"></i>
            </div>
            <span class="stat-badge green">+<?php echo $total_vehicles - 40; ?> this month</span>
        </div>
        <p class="stat-label">Total Vehicles</p>
        <p class="stat-value"><?php echo $total_vehicles; ?></p>
        <div class="stat-trend">
            <i class="fas fa-arrow-up"></i> <?php echo round(($total_vehicles/40)*100 - 100); ?>% from last month
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-header">
            <div class="stat-icon emerald">
                <i class="fas fa-check-circle"></i>
            </div>
            <span class="stat-badge green"><?php echo $available_vehicles; ?> available</span>
        </div>
        <p class="stat-label">Available</p>
        <p class="stat-value"><?php echo $available_vehicles; ?></p>
        <div class="stat-trend">
            <i class="fas fa-arrow-up"></i> <?php echo round(($available_vehicles/$total_vehicles)*100); ?>% of fleet
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-header">
            <div class="stat-icon amber">
                <i class="fas fa-tools"></i>
            </div>
            <span class="stat-badge amber"><?php echo $maintenance_vehicles; ?> in maintenance</span>
        </div>
        <p class="stat-label">In Maintenance</p>
        <p class="stat-value"><?php echo $maintenance_vehicles; ?></p>
        <div class="stat-trend down">
            <i class="fas fa-arrow-down"></i> <?php echo count($maintenance_alerts); ?> pending alerts
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-header">
            <div class="stat-icon purple">
                <i class="fas fa-chart-line"></i>
            </div>
            <span class="stat-badge green"><?php echo $fleet_efficiency; ?>% efficiency</span>
        </div>
        <p class="stat-label">Fleet Efficiency</p>
        <p class="stat-value"><?php echo $fleet_efficiency; ?>%</p>
        <div class="stat-trend">
            <i class="fas fa-arrow-up"></i> <?php echo $fleet_efficiency - 92; ?>% vs target
        </div>
    </div>
</div>
<?php endif; ?>
                
                <!-- Tabs -->
                <div class="card" style="margin-bottom: 24px;">
                       <div class="tabs">
        <!-- These tabs are visible to ALL EXCEPT drivers -->
        <?php if ($_SESSION['role'] !== 'driver'): ?>
        <button class="tab active" data-tab="overview">Transport Cost Analysis & Optimization</button>
        <button class="tab" data-tab="vehicles">Vehicles Availability & Assign Report</button>
        <button class="tab" data-tab="reservations">Vehicles Reservations & Dispatch System</button>
        <button class="tab" data-tab="maintenance">Fleet & Vehicles management</button>
        <?php endif; ?>
        
        <!-- Drivers tab - ONLY visible to drivers -->
        <?php if ($_SESSION['role'] === 'driver'): ?>
        <button class="tab active" data-tab="drivers">Drivers and Trip Performance Monitor</button>
        <?php endif; ?>
    </div>
                </div>
                
                <!-- Tab Panes -->
                 <?php if ($_SESSION['role'] !== 'driver'): ?>  
                <div id="tab-overview" class="tab-pane">
                    <!-- Dashboard Grid 3 Columns -->
                    <div class="dashboard-grid-3">
                        <!-- Transport Efficiency Report -->
                        <div class="card">
                            <div class="card-header">
                                <h2><i class="fas fa-chart-line"></i> Transport Efficiency</h2>
                                <div class="card-actions">
                                    <button class="card-action-btn">
                                        <i class="fas fa-download"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="efficiency-list">
                                    <!-- Dynamic content loaded via JS -->
                                </div>
                            </div>
                        </div>
                        
                        <!-- Delay Analysis Report -->
                        <div class="card">
                            <div class="card-header">
                                <h2><i class="fas fa-clock"></i> Delay Analysis</h2>
                                <span class="card-badge">Last 24h</span>
                            </div>
                            <div class="card-body">
                                <div class="delay-list">
                                    <!-- Dynamic content loaded via JS -->
                                </div>
                            </div>
                            <div class="card-footer">
                                <a href="#" style="color: #2563eb; text-decoration: none; font-size: 13px;">View all delays →</a>
                            </div>
                        </div>
                        
                        <!-- Fleet Condition Summary -->
                        <div class="card">
                            <div class="card-header">
                                <h2><i class="fas fa-heartbeat"></i> Fleet Condition</h2>
                                <span class="card-badge">Current</span>
                            </div>
                            <div class="card-body">
                                <div class="condition-grid">
                                    <!-- Dynamic content loaded via JS -->
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Dashboard Grid 2 Columns -->
                    <div class="dashboard-grid">
                        <!-- Driver Performance Report -->
                        <div class="card">
                            <div class="card-header">
                                <h2><i class="fas fa-users"></i> Driver Performance</h2>
                                <span class="card-badge">Top performers</span>
                            </div>
                            <div class="card-body">
                                <div class="driver-list">
                                    <!-- Dynamic content loaded via JS -->
                                </div>
                            </div>
                        </div>
                        
                        <!-- Driver Activity Dashboard -->
                        <div class="card">
                            <div class="card-header">
                                <h2><i class="fas fa-activity"></i> Driver Activity</h2>
                                <span class="card-badge">Real-time</span>
                            </div>
                            <div class="card-body">
                                <div class="activity-timeline">
                                    <!-- Dynamic content loaded via JS -->
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Full Width Card -->
                    <div class="card card-full">
                        <div class="card-header">
                            <h2><i class="fas fa-history"></i> Trip History Report</h2>
                            <div class="card-actions">
                                <select class="filter-select" style="width: auto;">
                                    <option>Last 7 days</option>
                                    <option>Last 30 days</option>
                                    <option>This month</option>
                                </select>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="trip-list">
                                <!-- Dynamic content loaded via JS -->
                            </div>
                        </div>
                    </div>
                </div>
                
                <div id="tab-vehicles" class="tab-pane" style="display: none;">
                   <!-- Vehicle Availability Report -->
<div class="card card-full">
    <div class="card-header">
        <h2><i class="fas fa-truck"></i> Vehicle Availability Report</h2>
        <div class="card-actions">
            <div class="search-box">
                <i class="fas fa-search"></i>
                <input type="text" id="searchFleet" placeholder="Search vehicles...">
            </div>
        </div>
    </div>
    <div class="card-body">
        <div class="filter-bar">
            <div class="filter-group">
                <select class="filter-select" id="vehicleTypeFilter">
                    <option value="all">All Types</option>
                    <option value="vehicle">Trucks</option>
                    <option value="equipment">Equipment</option>
                </select>
                <select class="filter-select" id="vehicleStatusFilter">
                    <option value="all">All Status</option>
                    <option value="good">Available</option>
                    <option value="warning">In Use</option>
                    <option value="bad">Maintenance</option>
                </select>
            </div>
        </div>
        <div class="vehicle-list" id="vehicleList">
            <?php foreach ($vehicles as $vehicle): 
                $availability = $vehicle['status'] == 'good' ? 'available' : ($vehicle['status'] == 'warning' ? 'in-use' : 'maintenance');
                $fuel = rand(30, 100); // You'd need a fuel table for real data
            ?>
            <div class="vehicle-item" data-type="<?php echo $vehicle['asset_type']; ?>" data-status="<?php echo $vehicle['status']; ?>">
                <div class="vehicle-info">
                    <div class="vehicle-icon">
                        <i class="fas fa-<?php echo $vehicle['asset_type'] == 'vehicle' ? 'truck' : 'cog'; ?>"></i>
                    </div>
                    <div class="vehicle-details">
                        <h3><?php echo htmlspecialchars($vehicle['asset_name']); ?> (VH-<?php echo str_pad($vehicle['id'], 3, '0', STR_PAD_LEFT); ?>)</h3>
                        <div class="vehicle-meta">
                            <span><i class="fas fa-plate"></i> <?php echo 'ABC-' . str_pad($vehicle['id'], 4, '0', STR_PAD_LEFT); ?></span>
                            <span><i class="fas fa-tachometer-alt"></i> <?php echo rand(10000, 90000); ?> km</span>
                            <?php if ($vehicle['current_driver']): ?>
                                <span><i class="fas fa-user"></i> <?php echo htmlspecialchars($vehicle['current_driver']); ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <div class="vehicle-status">
                    <span class="availability-badge availability-<?php echo $availability; ?>">
                        <i class="fas fa-<?php echo $availability == 'available' ? 'check-circle' : ($availability == 'in-use' ? 'play-circle' : 'wrench'); ?>"></i>
                        <?php echo strtoupper($availability); ?>
                    </span>
                </div>
                <div class="vehicle-metrics">
                    <div class="vehicle-metric">
                        <div class="value"><?php echo $fuel; ?>%</div>
                        <div class="label">Fuel</div>
                    </div>
                    <div class="vehicle-metric">
                        <div class="value"><?php echo $vehicle['asset_condition']; ?>%</div>
                        <div class="label">Condition</div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>
                    
                    <!-- Vehicle Assignment Report -->
                    <div class="card card-full">
                        <div class="card-header">
                            <h2><i class="fas fa-clipboard-list"></i> Vehicle Assignment Report</h2>
                            <span class="card-badge">Today's assignments</span>
                        </div>
                        <div class="card-body">
                            <div class="assignment-list">
                                <!-- Dynamic content loaded via JS -->
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>


               <?php if ($_SESSION['role'] === 'driver'): ?>
<div id="tab-drivers" class="tab-pane" style="display: block;">
    <div class="card card-full" id="current-assignment-card">
        <div class="card-header">
            <h2><i class="fas fa-truck"></i> My Current Assignment</h2>
            <span class="card-badge" id="assignment-status">Loading...</span>
        </div>
        <div class="card-body" id="assignment-content">
            <div style="text-align: center; padding: 40px;">
                <i class="fas fa-spinner fa-spin" style="font-size: 32px; color: #3b82f6;"></i>
                <p>Loading your assignment...</p>
            </div>
        </div>
    </div>
    <!-- Driver Performance Dashboard -->
       <div class="dashboard-grid">
        <div class="card">
            <div class="card-header">
                <h2><i class="fas fa-chart-line"></i> My Performance</h2>
            </div>
            <div class="card-body">
                <div class="stats-mini-grid" id="driver-stats">
                    <!-- Filled by JS -->
                </div>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h2><i class="fas fa-map-marked-alt"></i> Quick Actions</h2>
            </div>
            <div class="card-body">
                <div class="action-buttons">
                    <button class="btn btn-primary" onclick="updateStatus('in_transit')" style="width: 100%; margin-bottom: 10px;">
                        <i class="fas fa-play"></i> Start Trip
                    </button>
                    <button class="btn btn-success" onclick="updateStatus('delivered')" style="width: 100%; margin-bottom: 10px;">
                        <i class="fas fa-check"></i> Mark Delivered
                    </button>
                    <button class="btn btn-warning" onclick="updateLocation()" style="width: 100%;">
                        <i class="fas fa-map-marker-alt"></i> Update Location
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- My Activity Dashboard -->
    <div class="card card-full">
        <div class="card-header">
            <h2><i class="fas fa-activity"></i> My Recent Trips</h2>
            <span class="card-badge">Live tracking</span>
        </div>
        <div class="card-body">
            <div class="trip-list" id="trip-history">
                <!-- Show ONLY this driver's trips -->
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

                <div id="tab-reservations" class="tab-pane" style="display: none;">
                    <!-- Vehicle Reservation List -->
                    <div class="card card-full">
                        <div class="card-header">
                            <h2><i class="fas fa-calendar-alt"></i> Vehicle Reservation List</h2>
                            <div class="card-actions">
                                <button class="btn btn-primary" onclick="openReservationModal()">
                                    <i class="fas fa-plus"></i> New Reservation
                                </button>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="filter-bar">
                                <div class="filter-group">
                                    <select class="filter-select">
                                        <option value="all">All Status</option>
                                        <option value="approved">Approved</option>
                                        <option value="pending">Pending</option>
                                        <option value="rejected">Rejected</option>
                                    </select>
                                    <select class="filter-select">
                                        <option value="all">All Vehicles</option>
                                        <option value="volvo">Volvo FH16</option>
                                        <option value="scania">Scania R500</option>
                                    </select>
                                </div>
                            </div>
                            <div class="reservation-list">
                                <!-- Dynamic content loaded via JS -->
                            </div>
                        </div>
                    </div>
                    
                    <!-- Dispatch Schedule -->
                    <div class="card card-full">
                        <div class="card-header">
                            <h2><i class="fas fa-clock"></i> Dispatch Schedule</h2>
                            <span class="card-badge">Today</span>
                        </div>
                        <div class="card-body">
                            <div class="schedule-list">
                                <!-- Dynamic content loaded via JS -->
                            </div>
                        </div>
                    </div>
                    
                    <!-- Approved and Rejected Reservations -->
                    <div class="dashboard-grid">
                        <div class="card">
                            <div class="card-header">
                                <h2><i class="fas fa-check-circle" style="color: #10b981;"></i> Approved Reservations</h2>
                                <span class="card-badge">12 this week</span>
                            </div>
                            <div class="card-body">
                                <div class="reservation-list">
                                    <!-- Filtered approved reservations -->
                                </div>
                            </div>
                        </div>
                        
                        <div class="card">
                            <div class="card-header">
                                <h2><i class="fas fa-times-circle" style="color: #ef4444;"></i> Rejected Reservations</h2>
                                <span class="card-badge">3 this week</span>
                            </div>
                            <div class="card-body">
                                <div class="reservation-list">
                                    <!-- Filtered rejected reservations -->
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
               
                <!-- Reservation Modal -->
<div id="reservationModal" class="modal" style="display: none;">
    <div class="modal-content" style="max-width: 600px;">
        <div class="modal-header">
            <h3>New Vehicle Reservation</h3>
            <button class="modal-close" onclick="closeReservationModal()">&times;</button>
        </div>
        <div class="modal-body">
            <form id="reservationForm" onsubmit="submitReservation(event)">
                <div class="form-group">
                    <label for="vehicleSelect">Select Vehicle *</label>
                    <select id="vehicleSelect" class="form-control" required>
                        <option value="">Loading vehicles...</option>
                    </select>
                    <div id="availabilityMessage" style="margin-top: 5px; font-size: 13px;"></div>
                </div>
                
                <div class="form-row" style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                    <div class="form-group">
                        <label for="customer_name">Customer Name *</label>
                        <input type="text" id="customer_name" class="form-control" placeholder="e.g., ABC Company" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="department">Department</label>
                        <input type="text" id="department" class="form-control" placeholder="e.g., Logistics, Sales">
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="delivery_address">Delivery Address *</label>
                    <textarea id="delivery_address" class="form-control" rows="2" required placeholder="Full delivery address"></textarea>
                </div>
                
                <div class="form-group">
                    <label for="purpose">Purpose of Reservation *</label>
                    <textarea id="purpose" class="form-control" rows="2" required placeholder="Explain why you need the vehicle"></textarea>
                </div>
                
                <div class="form-row" style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                    <div class="form-group">
                        <label for="fromDate">From Date & Time *</label>
                        <input type="datetime-local" id="fromDate" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="toDate">To Date & Time *</label>
                        <input type="datetime-local" id="toDate" class="form-control" required>
                    </div>
                </div>
                
                <div class="form-group" style="margin-top: 20px;">
                    <button type="submit" class="btn btn-primary" style="width: 100%;">
                        <i class="fas fa-calendar-check"></i> Submit Reservation
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Assign Driver Modal -->
<div id="assignDriverModal" class="modal" style="display: none;">
    <div class="modal-content" style="max-width: 400px;">
        <div class="modal-header">
            <h3>Assign Driver to Schedule</h3>
            <button class="modal-close" onclick="closeAssignDriverModal()">&times;</button>
        </div>
        <div class="modal-body">
            <form id="assignDriverForm" onsubmit="assignDriver(event)">
                <input type="hidden" id="scheduleId" value="">
                
                <div class="form-group">
                    <label>Vehicle</label>
                    <div id="assignVehicle" style="padding: 10px; background-color: #f8f9fa; border-radius: 4px; font-weight: 500;"></div>
                </div>
                
                <div class="form-group">
                    <label>Route</label>
                    <div id="assignRoute" style="padding: 10px; background-color: #f8f9fa; border-radius: 4px;"></div>
                </div>
                
                <div class="form-group">
                    <label for="driverSelect">Select Driver *</label>
                    <select id="driverSelect" class="form-control" required>
                        <option value="">Loading drivers...</option>
                    </select>
                </div>
                
                <div class="form-group" style="margin-top: 20px;">
                    <button type="submit" class="btn btn-primary" style="width: 100%;">
                        <i class="fas fa-user-check"></i> Assign Driver
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

                <div id="tab-maintenance" class="tab-pane" style="display: none;">
                    <!-- Maintenance Report -->
                    <div class="dashboard-grid">
                        <!-- Maintenance Report -->
<div class="card">
    <div class="card-header">
        <h2><i class="fas fa-wrench"></i> Maintenance Report</h2>
        <span class="card-badge"><?php echo count($maintenance_alerts); ?> pending</span>
    </div>
    <div class="card-body">
        <div class="maintenance-list">
            <?php if (!empty($maintenance_alerts)): ?>
                <?php foreach ($maintenance_alerts as $alert): 
                    $due_date = new DateTime($alert['due_date']);
                    $now = new DateTime();
                    $days_diff = $now->diff($due_date)->days;
                    $status = $due_date < $now ? 'overdue' : ($days_diff <= 3 ? 'due-soon' : 'upcoming');
                    $priority_class = $alert['priority'] == 'high' ? 'critical' : ($alert['priority'] == 'medium' ? 'high' : 'normal');
                ?>
                <div class="maintenance-item">
                    <div class="maintenance-icon">
                        <i class="fas fa-wrench"></i>
                    </div>
                    <div class="maintenance-content">
                        <div class="maintenance-title"><?php echo htmlspecialchars($alert['asset_name']); ?> - <?php echo htmlspecialchars($alert['issue']); ?></div>
                        <div class="maintenance-meta">
                            <span>Due: <?php echo date('M d, Y', strtotime($alert['due_date'])); ?></span>
                            <span class="priority-<?php echo $alert['priority']; ?>"><?php echo ucfirst($alert['priority']); ?> Priority</span>
                        </div>
                    </div>
                    <div class="maintenance-due <?php echo $status == 'overdue' ? 'due-overdue' : ($status == 'due-soon' ? 'due-soon' : ''); ?>">
                        <?php echo ucfirst($status); ?>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p style="text-align: center; padding: 20px; color: #999;">No pending maintenance alerts</p>
            <?php endif; ?>
        </div>
    </div>
</div>
                        
                        <div class="card">
                            <div class="card-header">
                                <h2><i class="fas fa-clipboard-check"></i> Maintenance Summary</h2>
                                <span class="card-badge">This month</span>
                            </div>
                            <div class="card-body">
                                <div class="condition-grid">
                                    <!-- Dynamic content loaded via JS -->
                                </div>
                            </div>
                        </div>
                    </div>
                    
                   
                   <!-- Fleet Condition Summary -->
<div class="card card-full">
    <div class="card-header">
        <h2><i class="fas fa-heartbeat"></i> Fleet Condition Summary</h2>
        <span class="card-badge">Detailed view</span>
    </div>
    <div class="card-body">
        <div class="condition-grid" style="grid-template-columns: repeat(4, 1fr);">
            <div class="condition-item">
                <div class="condition-icon">
                    <i class="fas fa-star"></i>
                </div>
                <h3>Excellent</h3>
                <div class="condition-value"><?php echo $condition_stats['excellent'] ?? 0; ?></div>
                <div class="condition-bar">
                    <div class="condition-fill good" style="width: <?php echo $total_vehicles > 0 ? round(($condition_stats['excellent']/$total_vehicles)*100) : 0; ?>%"></div>
                </div>
                <div class="condition-label"><?php echo $total_vehicles > 0 ? round(($condition_stats['excellent']/$total_vehicles)*100) : 0; ?>% of fleet</div>
            </div>
            <div class="condition-item">
                <div class="condition-icon">
                    <i class="fas fa-thumbs-up"></i>
                </div>
                <h3>Good</h3>
                <div class="condition-value"><?php echo $condition_stats['good'] ?? 0; ?></div>
                <div class="condition-bar">
                    <div class="condition-fill good" style="width: <?php echo $total_vehicles > 0 ? round(($condition_stats['good']/$total_vehicles)*100) : 0; ?>%"></div>
                </div>
                <div class="condition-label"><?php echo $total_vehicles > 0 ? round(($condition_stats['good']/$total_vehicles)*100) : 0; ?>% of fleet</div>
            </div>
            <div class="condition-item">
                <div class="condition-icon">
                    <i class="fas fa-exclamation"></i>
                </div>
                <h3>Fair</h3>
                <div class="condition-value"><?php echo $condition_stats['fair'] ?? 0; ?></div>
                <div class="condition-bar">
                    <div class="condition-fill warning" style="width: <?php echo $total_vehicles > 0 ? round(($condition_stats['fair']/$total_vehicles)*100) : 0; ?>%"></div>
                </div>
                <div class="condition-label"><?php echo $total_vehicles > 0 ? round(($condition_stats['fair']/$total_vehicles)*100) : 0; ?>% of fleet</div>
            </div>
            <div class="condition-item">
                <div class="condition-icon">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
                <h3>Poor</h3>
                <div class="condition-value"><?php echo $condition_stats['poor'] ?? 0; ?></div>
                <div class="condition-bar">
                    <div class="condition-fill critical" style="width: <?php echo $total_vehicles > 0 ? round(($condition_stats['poor']/$total_vehicles)*100) : 0; ?>%"></div>
                </div>
                <div class="condition-label"><?php echo $total_vehicles > 0 ? round(($condition_stats['poor']/$total_vehicles)*100) : 0; ?>% of fleet</div>
            </div>
        </div>
    </div>
</div>
                </div>
            </div>
        </main>
    </div>
    <script src="../assets/js/pages/fleet.js"></script>
</body>
</html>