<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Disable caching for this page
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}
// Add this helper function
function safe_js_string($str) {
    if ($str === null) return '';
    // Remove line breaks and escape quotes
    $str = str_replace(["\r", "\n"], ' ', $str);
    $str = addslashes($str);
    return $str;
}

require_once '../config/db.php';

// Check if database connection exists
if (!isset($pdo)) {
    error_log("Fleet: Database connection not available");
    die("Database connection error. Please check configuration.");
}

$available_count = 0;
$maintenance_count = 0;
$on_route_count = 0;
$total_count = 0;
$real_available_count = 0;
$real_maintenance_count = 0;
$real_in_use_count = 0;
$user_role = $_SESSION['role'];
$can_manage_maintenance = in_array($user_role, ['admin', 'fleet_manager']);
$can_view_maintenance = in_array($user_role, ['admin', 'fleet_manager', 'dispatcher', 'employee']);
$condition_stats = ['excellent' => 0, 'good' => 0, 'fair' => 0, 'poor' => 0];
function isVehicleInUse($vehicle_id, $pdo) {
    // Check shipments - any non-completed shipment
    $shipment_check = $pdo->prepare("
        SELECT COUNT(*) FROM shipments 
        WHERE vehicle_id = ? 
        AND shipment_status NOT IN ('completed', 'cancelled')
    ");
    $shipment_check->execute([$vehicle_id]);
    if ($shipment_check->fetchColumn() > 0) {
        return true;
    }
    
    // Check dispatch_schedule - any non-completed dispatch
    $dispatch_check = $pdo->prepare("
        SELECT COUNT(*) FROM dispatch_schedule 
        WHERE vehicle_id = ? 
        AND status NOT IN ('completed', 'cancelled')
    ");
    $dispatch_check->execute([$vehicle_id]);
    return $dispatch_check->fetchColumn() > 0;
}


// Get real statistics
try {
    // CRITICAL FIX: Ensure PDO is in exception mode
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // DIAGNOSTIC: Check SQL mode and raw asset count on Railway
    $sql_mode_row = $pdo->query("SELECT @@sql_mode as sql_mode")->fetch(PDO::FETCH_ASSOC);
    $current_sql_mode = $sql_mode_row['sql_mode'] ?? 'unknown';
    error_log("FLEET DIAG - SQL mode: " . $current_sql_mode);
    
    $raw_asset_count = $pdo->query("SELECT COUNT(*) as cnt FROM assets")->fetch(PDO::FETCH_ASSOC);
    error_log("FLEET DIAG - Total assets in DB: " . ($raw_asset_count['cnt'] ?? 0));
    
    $raw_vehicle_count = $pdo->query("SELECT COUNT(*) as cnt FROM assets WHERE asset_type = 'vehicle'")->fetch(PDO::FETCH_ASSOC);
    error_log("FLEET DIAG - Assets with asset_type='vehicle': " . ($raw_vehicle_count['cnt'] ?? 0));
    
    $asset_types_check = $pdo->query("SELECT DISTINCT asset_type FROM assets")->fetchAll(PDO::FETCH_COLUMN);
    error_log("FLEET DIAG - Distinct asset_type values: " . implode(', ', $asset_types_check));
    
    // Get active shipments for stats
    $stmt = $pdo->query("
        SELECT COUNT(*) as total,
               SUM(CASE WHEN shipment_status = 'delivered' THEN 1 ELSE 0 END) as delivered
        FROM shipments
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
    ");
    $shipment_stats = $stmt->fetch();
    
    // Log for debugging on deployment
    error_log("Fleet Stats - Shipments found: " . ($shipment_stats['total'] ?? 0));
    
    // FIX: Initialize total_vehicles as 0 first (will calculate later)
    $total_vehicles = 0;

    // Get vehicles added this month
    $stmt = $pdo->query("
        SELECT COUNT(*) as total
        FROM assets
        WHERE asset_type = 'vehicle'
        AND MONTH(created_at) = MONTH(CURDATE())
        AND YEAR(created_at) = YEAR(CURDATE())
    ");
    $vehicles_this_month = $stmt->fetch()['total'] ?? 0;

    // Get vehicles added last month
    $stmt = $pdo->query("
        SELECT COUNT(*) as total
        FROM assets
        WHERE asset_type = 'vehicle'
        AND MONTH(created_at) = MONTH(DATE_SUB(CURDATE(), INTERVAL 1 MONTH))
        AND YEAR(created_at) = YEAR(DATE_SUB(CURDATE(), INTERVAL 1 MONTH))
    ");
    $vehicles_last_month = $stmt->fetch()['total'] ?? 0;
    
    // FIXED: Rewritten to avoid ONLY_FULL_GROUP_BY violation on Railway MySQL strict mode.
    // Uses subqueries to get one active shipment/dispatch per vehicle instead of GROUP BY a.*
    $stmt = $pdo->query("
    SELECT
        a.id,
        a.asset_name,
        a.asset_type,
        a.status,
        a.asset_condition,
        a.created_at,
        a.updated_at,
        s.shipment_status,
        s.driver_id as shipment_driver_id,
        ds.status as dispatch_status,
        ds.driver_id as dispatch_driver_id,
        u.full_name as current_driver,
        CASE
            WHEN s.shipment_id IS NOT NULL AND s.shipment_status IN ('in_transit', 'pending') THEN 1
            WHEN ds.id IS NOT NULL AND ds.status IN ('in-progress', 'scheduled', 'delivered', 'awaiting_verification') THEN 1
            ELSE 0
        END as is_in_use
    FROM assets a
    LEFT JOIN (
        SELECT vehicle_id, shipment_id, shipment_status, driver_id
        FROM shipments
        WHERE shipment_status IN ('in_transit', 'pending')
        ORDER BY created_at DESC
    ) s ON a.id = s.vehicle_id
    LEFT JOIN (
        SELECT vehicle_id, id, status, driver_id
        FROM dispatch_schedule
        WHERE status IN ('in-progress', 'scheduled', 'delivered', 'awaiting_verification')
        ORDER BY created_at DESC
    ) ds ON a.id = ds.vehicle_id
    LEFT JOIN users u ON COALESCE(s.driver_id, ds.driver_id) = u.id
    WHERE a.asset_type = 'vehicle'
    ORDER BY a.created_at DESC
    ");
    $vehicles = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Log vehicle count for debugging
    error_log("Fleet Stats - Total vehicles found: " . count($vehicles));
    
    // Get maintenance alerts - INCLUDES BOTH PENDING AND IN_PROGRESS
    $stmt = $pdo->query("
        SELECT * FROM maintenance_alerts 
        WHERE status IN ('pending', 'in_progress')
        ORDER BY 
            CASE priority 
                WHEN 'high' THEN 1
                WHEN 'medium' THEN 2
                WHEN 'low' THEN 3
            END,
            due_date ASC
    ");
    $maintenance_alerts = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // DEBUG: Check what maintenance alerts were found
    echo "<!-- MAINTENANCE ALERTS FOUND: " . count($maintenance_alerts) . " -->\n";
    foreach ($maintenance_alerts as $alert) {
        echo "<!-- Alert: Vehicle='{$alert['asset_name']}', Status='{$alert['status']}', Priority='{$alert['priority']}' -->\n";
    }

    // Reset counters
    $real_available_count = 0;
    $real_maintenance_count = 0;
    $real_in_use_count = 0;

    foreach ($vehicles as $vehicle) {
        $is_maintenance = false;
        
        // Check for ANY active maintenance alerts (pending OR in_progress)
        foreach ($maintenance_alerts as $alert) {
            if ($alert['asset_name'] == $vehicle['asset_name']) {
                $is_maintenance = true;
                break;
            }
        }
        
        // If in maintenance, count as maintenance and skip further checks
        if ($is_maintenance) {
            $real_maintenance_count++;
            echo "<!-- Vehicle {$vehicle['asset_name']}: MAINTENANCE -->\n";
            continue;
        }
        
        // Check if vehicle is in use - using more specific status checks
        $check_dispatch = $pdo->prepare("
            SELECT COUNT(*) FROM dispatch_schedule 
            WHERE vehicle_id = ? 
            AND status IN ('in-progress', 'scheduled', 'delivered', 'awaiting_verification')
        ");
        $check_dispatch->execute([$vehicle['id']]);
        $dispatch_active = $check_dispatch->fetchColumn() > 0;
        
        $check_shipment = $pdo->prepare("
            SELECT COUNT(*) FROM shipments 
            WHERE vehicle_id = ? 
            AND shipment_status IN ('in_transit', 'pending')
        ");
        $check_shipment->execute([$vehicle['id']]);
        $shipment_active = $check_shipment->fetchColumn() > 0;
        
        if ($dispatch_active || $shipment_active) {
            $real_in_use_count++;
            echo "<!-- Vehicle {$vehicle['asset_name']}: IN USE (dispatch=" . ($dispatch_active ? 'Y' : 'N') . ", shipment=" . ($shipment_active ? 'Y' : 'N') . ") -->\n";
        } else {
            $real_available_count++;
            echo "<!-- Vehicle {$vehicle['asset_name']}: AVAILABLE -->\n";
        }
    }

    $available_count = $real_available_count;
    $maintenance_count = $real_maintenance_count;
    $in_use_count = $real_in_use_count;

    // FIX: Calculate total vehicles from the actual vehicles array
    $total_vehicles = count($vehicles);
    
    // CRITICAL FIX: If all counts are 0 but we have vehicles, recalculate
    if ($total_vehicles > 0 && $available_count === 0 && $maintenance_count === 0 && $in_use_count === 0) {
        error_log("WARNING: Stats are 0 but vehicles exist. Recalculating...");
        
        // Recalculate from scratch
        $available_count = 0;
        $maintenance_count = 0;
        $in_use_count = 0;
        
        foreach ($vehicles as $v) {
            $has_maint = false;
            foreach ($maintenance_alerts as $alert) {
                if ($alert['asset_name'] == $v['asset_name']) {
                    $has_maint = true;
                    break;
                }
            }
            
            if ($has_maint) {
                $maintenance_count++;
            } else if ($v['is_in_use']) {
                $in_use_count++;
            } else {
                $available_count++;
            }
        }
        
        error_log("Recalculated - Available: $available_count, Maintenance: $maintenance_count, In Use: $in_use_count");
    }
    
    // Log final counts
    error_log("Fleet Stats - Available: $available_count, Maintenance: $maintenance_count, In Use: $in_use_count, Total: $total_vehicles");

    // FINAL DEBUG
    echo "<!-- FINAL COUNTS - Available: $available_count, Maintenance: $maintenance_count, In Use: $in_use_count -->\n";
    echo "<!-- TOTAL VEHICLES (FIXED): $total_vehicles -->\n";
    
    // Get drivers
    $stmt = $pdo->query("
        SELECT id, full_name, employee_id, status 
        FROM users 
        WHERE role = 'driver' AND status = 'active'
        ORDER BY full_name
    ");
    $drivers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Detailed debug for vehicles
    echo "<!-- ===== DETAILED DEBUG ===== -->\n";
    foreach ($vehicles as $v) {
        $has_maintenance = false;
        foreach ($maintenance_alerts as $alert) {
            if ($alert['asset_name'] == $v['asset_name']) {
                $has_maintenance = true;
                break;
            }
        }
        echo "<!-- Vehicle: {$v['asset_name']} -->\n";
        echo "<!--   - Status: {$v['status']} -->\n";
        echo "<!--   - Shipment Status: " . ($v['shipment_status'] ?? 'NULL') . " -->\n";
        echo "<!--   - Dispatch Status: " . ($v['dispatch_status'] ?? 'NULL') . " -->\n";
        echo "<!--   - is_in_use: " . ($v['is_in_use'] ?? 0) . " -->\n";
        echo "<!--   - Has Maintenance: " . ($has_maintenance ? 'Yes' : 'No') . " -->\n";
    }
    echo "<!-- Available: $available_count, Maintenance: $maintenance_count, In Use: $in_use_count -->\n";
    echo "<!-- TOTAL VEHICLES: $total_vehicles -->\n";
    echo "<!-- ===== END DEBUG ===== -->\n";
    
} catch (PDOException $e) {
    error_log("Fleet page error: " . $e->getMessage());
    error_log("Fleet page error trace: " . $e->getTraceAsString());
    
    // CRITICAL: Show error to user if in development
    if (getenv('RAILWAY_ENVIRONMENT')) {
        error_log("DEPLOYMENT ERROR: Stats calculation failed - " . $e->getMessage());
    }
    
    // Initialize all variables with safe defaults
    $total_vehicles = 0;
    $available_count = 0;
    $maintenance_count = 0;
    $in_use_count = 0;
    $real_available_count = 0;
    $real_maintenance_count = 0;
    $real_in_use_count = 0;
    $vehicles = [];
    $maintenance_alerts = [];
    $drivers = [];
    $condition_stats = ['excellent' => 0, 'good' => 0, 'fair' => 0, 'poor' => 0];
    
    // Show error to admin users
    if ($_SESSION['role'] === 'admin') {
        echo "<!-- Database Error: " . htmlspecialchars($e->getMessage()) . " -->";
    }
}

$page_title = 'Fleet Management | Logistics System';
$page_css = ['../assets/css/style.css', '../assets/css/fleet.css'];
include '../includes/header.php';
?>
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        /* ===== MOBILE RESPONSIVE STYLES ===== */

/* Mobile Header with Menu Button */
.mobile-header {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    height: 60px;
    background: white;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    z-index: 1001;
    padding: 0 20px;
    align-items: center;
    justify-content: space-between;
}

.mobile-menu-btn {
    background: none;
    border: none;
    font-size: 24px;
    color: #3b82f6;
    cursor: pointer;
    padding: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.mobile-title {
    font-size: 18px;
    font-weight: 600;
    color: #1e293b;
}

.mobile-user {
    display: flex;
    align-items: center;
    gap: 10px;
}

.mobile-user .avatar-small {
    width: 36px;
    height: 36px;
}

/* Main content padding for mobile */
.page-content {
    transition: margin-left 0.3s ease;
}

/* ===== RESPONSIVE BREAKPOINTS ===== */

/* Tablet Styles (768px - 1024px) */
@media screen and (max-width: 1024px) {
    .stats-grid {
        grid-template-columns: repeat(2, 1fr) !important;
        gap: 15px !important;
    }
    
    .dashboard-grid-3 {
        grid-template-columns: repeat(2, 1fr) !important;
    }
    
    .card-full .card-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 10px;
    }
    
    .card-actions {
        width: 100%;
    }
    
    .search-box {
        width: 100%;
    }
    
    .search-box input {
        width: 100%;
    }
}

/* Mobile Styles (up to 768px) */
@media screen and (max-width: 768px) {
    /* Show mobile header */
    .mobile-header {
        display: flex;
    }
    
    /* Adjust main content for fixed header */
    body {
        padding-top: 60px;
    }
    
    .page-content {
        margin-left: 0 !important;
        width: 100% !important;
        padding: 15px !important;
    }
    
    /* Sidebar mobile styles */
    .sidebar {
        position: fixed !important;
        left: -280px !important;
        top: 0 !important;
        bottom: 0 !important;
        width: 280px !important;
        transition: left 0.3s ease !important;
        z-index: 1002 !important;
        background: white !important;
        box-shadow: 2px 0 10px rgba(0,0,0,0.1) !important;
        overflow-y: auto !important;
    }
    
    .sidebar.mobile-show {
        left: 0 !important;
    }
    
    /* Sidebar overlay */
    .sidebar-overlay {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0,0,0,0.5);
        z-index: 1001;
    }
    
    .sidebar-overlay.show {
        display: block !important;
    }
    
    /* Header adjustments */
    .header {
        display: none !important;
    }
    
    /* Grid adjustments */
    .stats-grid {
        grid-template-columns: 1fr !important;
        gap: 12px !important;
        margin-top: 10px !important;
    }
    
    .dashboard-grid-3,
    .dashboard-grid {
        grid-template-columns: 1fr !important;
    }
    
    /* Card adjustments */
    .card {
        padding: 15px !important;
    }
    
    .card-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 10px;
    }
    
    /* Tab adjustments */
    .tabs {
        overflow-x: auto;
        white-space: nowrap;
        -webkit-overflow-scrolling: touch;
        padding: 5px 0;
        margin: 0 -5px;
    }
    
    .tabs .tab {
        display: inline-block;
        white-space: nowrap;
        margin: 0 5px;
        padding: 10px 15px;
        font-size: 14px;
    }
    
    /* Vehicle list adjustments */
    .vehicle-item {
        flex-direction: column;
        align-items: flex-start !important;
        gap: 15px;
    }
    
    .vehicle-info {
        width: 100%;
    }
    
    .vehicle-status {
        width: 100%;
        text-align: left !important;
    }
    
    .vehicle-metrics {
        width: 100%;
        justify-content: flex-start !important;
    }
    
    /* Filter bar adjustments */
    .filter-bar {
        flex-direction: column;
        gap: 10px;
    }
    
    .filter-group {
        width: 100%;
    }
    
    .filter-select {
        width: 100%;
    }
    
    /* Table adjustments */
    .table {
        display: block;
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
    }
    
    /* Modal adjustments */
    .modal-content {
        width: 90% !important;
        margin: 20px auto !important;
        max-height: 80vh !important;
        overflow-y: auto !important;
    }
    
    /* Driver activity cards */
    #driverScrollContainer {
        padding: 10px 0 !important;
    }
    
    #driverScrollContainer > div {
        gap: 10px !important;
    }
    
    #driverScrollContainer > div > div {
        width: 240px !important;
    }
    
    /* Maintenance items */
    .maintenance-item {
        flex-direction: column;
        align-items: flex-start !important;
        gap: 10px;
    }
    
    .maintenance-actions {
        width: 100%;
        display: flex;
        justify-content: flex-end;
    }
    
    /* Reservation items */
    .reservation-item {
        flex-direction: column;
        align-items: flex-start !important;
        gap: 15px;
    }
    
    .reservation-item > div:last-child {
        width: 100%;
        text-align: left !important;
    }
    
    /* Form adjustments */
    .form-row {
        grid-template-columns: 1fr !important;
        gap: 10px !important;
    }
    
    /* Button adjustments */
    .btn {
        width: 100%;
        margin: 5px 0 !important;
    }
    
    .action-buttons {
        display: flex;
        flex-direction: column;
        gap: 10px;
    }
    
    /* Driver assignment card */
    .assignment-details {
        padding: 10px !important;
    }
    
    .detail-row {
        flex-direction: column;
        align-items: flex-start !important;
        gap: 5px;
    }
    
    /* Location dropdown */
    .location-card {
        width: 100%;
    }
    
    .history-dropdown {
        max-width: 100%;
    }
    
    /* Stats mini grid */
    .stats-mini-grid {
        grid-template-columns: repeat(2, 1fr) !important;
    }
    
    /* Quick action buttons */
    .assignment-actions {
        flex-direction: column;
        gap: 8px;
    }
    
    /* Training stats grid */
    [style*="grid-template-columns: repeat(3, 1fr)"] {
        grid-template-columns: 1fr !important;
    }
}

/* Small mobile styles (up to 480px) */
@media screen and (max-width: 480px) {
    .page-content {
        padding: 10px !important;
    }
    
    .stat-card {
        padding: 15px !important;
    }
    
    .stat-value {
        font-size: 24px !important;
    }
    
    .mobile-title {
        font-size: 16px;
    }
    
    .mobile-user .avatar-small {
        width: 32px;
        height: 32px;
    }
    
    .verification-input {
        font-size: 1.5rem !important;
        letter-spacing: 4px !important;
    }
    
    .modal-content {
        width: 95% !important;
        padding: 15px !important;
    }
    
    .vehicle-meta {
        flex-wrap: wrap;
    }
    
    .vehicle-meta span {
        width: 100%;
        margin: 2px 0;
    }
    
    .availability-badge {
        width: 100%;
        text-align: center;
    }
}

/* Landscape mode adjustments */
@media screen and (max-width: 900px) and (orientation: landscape) {
    .modal-content {
        max-height: 90vh !important;
        overflow-y: auto !important;
    }
    
    .sidebar {
        overflow-y: auto !important;
    }
    
    .stats-grid {
        grid-template-columns: repeat(2, 1fr) !important;
    }
}

/* Print styles */
@media print {
    .sidebar,
    .mobile-header,
    .header,
    .btn,
    button,
    .modal,
    .sidebar-overlay {
        display: none !important;
    }
    
    .page-content {
        margin: 0 !important;
        padding: 0 !important;
    }
    
    .card {
        break-inside: avoid;
        page-break-inside: avoid;
    }
}
    </style>
</head>
<body>
            <!-- Page Content -->
            <div class="page-content">
                      <header class="header">
    <div class="header-container">
        
        <div class="header-left">
        </div>
        
        <div class="header-right">
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
                
   <body data-user-role="<?php echo $_SESSION['role']; ?>">            
           <!-- Statistics Cards - HIDDEN from drivers -->
<?php if ($_SESSION['role'] !== 'driver' && $_SESSION['role'] !== 'mechanic'): ?>
<!-- Debug Info (remove after fixing) -->
<?php if ($_SESSION['role'] === 'admin'): ?>
<?php endif; ?>

<div class="stats-grid">
    <?php
    // Use the already calculated values from above
    // $available_count, $maintenance_count, $in_use_count are already calculated!
    
    // Calculate vehicle growth
    $vehicle_growth = $vehicles_this_month - ($vehicles_last_month ?? 0);
    $growth_sign = $vehicle_growth >= 0 ? '+' : '';
    
    // Calculate real fleet efficiency (completed shipments vs total shipments)
    $total_shipments_30d = $shipment_stats['total'] ?? 0;
    $completed_shipments_30d = $shipment_stats['delivered'] ?? 0;
    $real_efficiency = $total_shipments_30d > 0 ? round(($completed_shipments_30d / $total_shipments_30d) * 100) : 0;
    
    // Calculate previous month for trend
    $prev_month_shipments = $pdo->query("
        SELECT COUNT(*) as total, 
               SUM(CASE WHEN shipment_status = 'delivered' THEN 1 ELSE 0 END) as delivered
        FROM shipments 
        WHERE created_at BETWEEN DATE_SUB(NOW(), INTERVAL 60 DAY) AND DATE_SUB(NOW(), INTERVAL 30 DAY)
    ")->fetch();
    $prev_efficiency = $prev_month_shipments['total'] > 0 ? round(($prev_month_shipments['delivered'] / $prev_month_shipments['total']) * 100) : 0;
    $trend_class = $real_efficiency >= $prev_efficiency ? 'up' : 'down';
    $trend_icon = $real_efficiency >= $prev_efficiency ? 'arrow-up' : 'arrow-down';
    $trend_value = abs($real_efficiency - $prev_efficiency);
    ?>
    
    <!-- Total Vehicles Card -->
    <div class="stat-card">
        <div class="stat-header">
            <div class="stat-icon blue">
                <i class="fas fa-truck"></i>
            </div>
            <span class="stat-badge <?php echo $vehicle_growth >= 0 ? 'green' : 'red'; ?>">
                <?php echo $growth_sign . $vehicle_growth; ?> this month
            </span>
        </div>
        <p class="stat-label">Total Vehicles</p>
        <p class="stat-value" id="total-vehicles-stat"><?php echo $total_vehicles; ?></p>
        <div class="stat-trend">
            <i class="fas fa-arrow-up"></i> Fleet total
        </div>
    </div>
    
    <!-- Available Vehicles Card -->
    <?php
// Determine colors based on available count
// Use emerald/green if >0, amber if 0
$available_icon_color = $available_count > 0 ? 'emerald' : 'amber';
$badge_color = $available_count > 0 ? 'green' : 'amber';
?>
<div class="stat-card">
    <div class="stat-header">
        <div class="stat-icon <?php echo $available_icon_color; ?>">
            <i class="fas fa-check-circle"></i>
        </div>
        <span class="stat-badge <?php echo $badge_color; ?>">
            <?php echo $available_count; ?> available
            <?php if ($available_count == 0): ?>
                <i class="fas fa-exclamation-triangle" style="margin-left: 5px;"></i>
            <?php endif; ?>
        </span>
    </div>
    <p class="stat-label">Available Now</p>
    <p class="stat-value" id="available-vehicles-stat"><?php echo $available_count; ?></p>
    <div class="stat-trend">
        <i class="fas fa-arrow-up"></i> <?php echo $total_vehicles > 0 ? round(($available_count/$total_vehicles)*100) : 0; ?>% of fleet
    </div>
</div>
    
    <!-- In Maintenance Card -->
    <div class="stat-card">
        <div class="stat-header">
            <div class="stat-icon amber">
                <i class="fas fa-tools"></i>
            </div>
            <span class="stat-badge amber"><?php echo $maintenance_count; ?> in maintenance</span>
        </div>
        <p class="stat-label">In Maintenance</p>
        <p class="stat-value" id="maintenance-vehicles-stat"><?php echo $maintenance_count; ?></p>
        <div class="stat-trend down">
           <?php
// Count by status
$pending_count = 0;
$in_progress_count = 0;
foreach ($maintenance_alerts as $alert) {
    if ($alert['status'] == 'pending') $pending_count++;
    if ($alert['status'] == 'in_progress') $in_progress_count++;
}

// Determine what to show
if ($in_progress_count > 0 && $pending_count > 0) {
    $status_text = $pending_count . ' pending, ' . $in_progress_count . ' in progress';
    $icon = 'fa-tasks';
} elseif ($in_progress_count > 0) {
    $status_text = $in_progress_count . ' in progress';
    $icon = 'fa-spinner fa-spin';
} else {
    $status_text = $pending_count . ' pending';
    $icon = 'fa-clock';
}
?>
<i class="fas <?php echo $icon; ?>"></i> <?php echo $status_text; ?>
        </div>
    </div>
    
    <!-- Fleet Efficiency Card -->
    <div class="stat-card">
        <div class="stat-header">
            <div class="stat-icon purple">
                <i class="fas fa-chart-line"></i>
            </div>
            <span class="stat-badge <?php echo $trend_class == 'up' ? 'green' : 'amber'; ?>"><?php echo $real_efficiency; ?>% efficiency</span>
        </div>
        <p class="stat-label">Fleet Efficiency</p>
        <p class="stat-value"><?php echo $real_efficiency; ?>%</p>
        <div class="stat-trend <?php echo $trend_class; ?>">
            <i class="fas fa-<?php echo $trend_icon; ?>"></i> <?php echo $trend_value; ?>% vs last month
        </div>
    </div>
</div>
<?php endif; ?>

                
                <!-- Tabs -->
                <div class="card" style="margin-bottom: 24px;">
                       <div class="tabs">
        <!-- These tabs are visible to ALL EXCEPT drivers -->
        <?php if ($_SESSION['role'] !== 'driver' && $_SESSION['role'] !== 'mechanic'): ?>
        <button class="tab active" data-tab="overview">Transport Cost Analysis & Optimization</button>
        <button class="tab" data-tab="vehicles">Vehicles Availability & Assign Report</button>
        <button class="tab" data-tab="reservations">Vehicles Reservations & Dispatch System</button>
        <button class="tab" data-tab="maintenance">Fleet & Vehicles management</button>
        <?php endif; ?>
        
        <!-- Drivers tab - ONLY visible to drivers -->
        <?php if ($_SESSION['role'] === 'driver'): ?>
        <button class="tab active" data-tab="drivers">Drivers and Trip Performance Monitor</button>
        <?php endif; ?>
        <!-- Mechanics tab - ONLY visible to mechanics -->
        <?php if ($_SESSION['role'] === 'mechanic'): ?>
        <button class="tab active" data-tab="mechanic">Maintenance Tasks</button>
        <?php endif; ?>
    </div>
                </div>
                
                <!-- Tab Panes -->
                 <?php if ($_SESSION['role'] !== 'driver' && $_SESSION['role'] !== 'mechanic'): ?>  
                <div id="tab-overview" class="tab-pane">
                    <!-- Dashboard Grid 3 Columns -->
                    <div class="dashboard-grid-3">
                        <!-- Transport Efficiency Report -->
                        <div class="card">
                            <div class="card-header">
                                <h2><i class="fas fa-chart-line"></i> Transport Efficiency</h2>
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
                            <div class="trip-history-list">
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
    // Determine correct availability based on real data
    $is_in_use = ($vehicle['shipment_status'] == 'in_transit' || $vehicle['shipment_status'] == 'pending');
    
    // Check if has ACTIVE maintenance (pending OR in_progress)
    $has_maintenance = false;
foreach ($maintenance_alerts as $alert) {
    if ($alert['asset_name'] == $vehicle['asset_name'] && in_array($alert['status'], ['pending', 'in_progress'])) {
        $has_maintenance = true;
        break;
    }
}
    
    // Set availability status - MAINTENANCE TAKES PRIORITY
    if ($has_maintenance) {
        $availability = 'maintenance';
        $status_text = 'MAINTENANCE';
        $icon = 'wrench';
    } else if ($is_in_use) { 
        $availability = 'in-use';
        $status_text = 'IN USE';
        $icon = 'play-circle';
    } else {
        $availability = 'available';
        $status_text = 'AVAILABLE';
        $icon = 'check-circle';
    }
    
    $fuel = rand(30, 100);
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
                <i class="fas fa-<?php echo $icon; ?>"></i>
                <?php echo $status_text; ?>
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
                        <i class="fas fa-check"></i> Delivered
                    </button>
                      <button class="btn btn-warning" onclick="reportDelay()" style="width: 100%; margin-bottom: 10px;">
                <i class="fas fa-exclamation-triangle"></i> Report Delay
            </button>
            <button class="btn btn-info" onclick="updateStatus('returned')" style="width: 100%; margin-bottom: 10px;">
    <i class="fas fa-warehouse"></i> Returned to Dispatch Center
</button>
                    <button class="btn btn-warning" onclick="updateLocation()" style="width: 100%;">
                        <i class="fas fa-map-marker-alt"></i> Update Location
                    </button>
                   <button class="btn btn-danger" onclick="reportEmergencyBreakdown()" style="width: 100%; margin-top: 10px; font-weight: bold;">
                        <i class="fas fa-exclamation-circle"></i> 🚨 EMERGENCY BREAKDOWN
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

<?php if ($_SESSION['role'] === 'mechanic'): ?>
<div id="tab-mechanic" class="tab-pane" style="display: block;">
    
    <!-- Emergency Breakdowns Assigned to Me -->
    <div class="card card-full" style="margin-bottom: 20px; border-left: 4px solid #dc2626;">
        <div class="card-header" style="background: #fee2e2;">
            <h2><i class="fas fa-exclamation-triangle" style="color: #dc2626;"></i> My Emergency Assignments</h2>
            <span class="card-badge" style="background: #dc2626; color: white;" id="my-emergency-count">0</span>
        </div>
        <div class="card-body" id="my-emergency-list">
            <!-- Loaded via AJAX -->
        </div>
    </div>
    
    <!-- My Scheduled Maintenance Tasks -->
    <div class="card card-full" id="my-tasks-card">
        <div class="card-header">
            <h2><i class="fas fa-tasks"></i> My Scheduled Maintenance Tasks</h2>
            <span class="card-badge" id="tasks-count">Loading...</span>
        </div>
        <div class="card-body" id="my-tasks-content">
            <!-- Existing mechanic tasks loaded here -->
        </div>
    </div>
    
    <!-- My Performance Stats -->
    <div class="dashboard-grid">
        <div class="card">
            <div class="card-header">
                <h2><i class="fas fa-chart-line"></i> My Performance</h2>
            </div>
            <div class="card-body">
                <div class="stats-mini-grid" id="mechanic-stats">
                    <!-- Filled by JS -->
                </div>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h2><i class="fas fa-clock"></i> Quick Actions</h2>
            </div>
            <div class="card-body">
                <div class="action-buttons">
                    <button class="btn btn-primary" onclick="refreshMechanicTasks()" style="width: 100%; margin-bottom: 10px;">
                        <i class="fas fa-sync-alt"></i> Refresh Tasks
                    </button>
                    <button class="btn btn-info" onclick="updateMechanicStatus()" style="width: 100%;">
                        <i class="fas fa-toggle-on"></i> Toggle Availability
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Recent Activity -->
    <div class="card card-full">
        <div class="card-header">
            <h2><i class="fas fa-history"></i> My Recent Activity</h2>
            <span class="card-badge">Last 7 days</span>
        </div>
        <div class="card-body">
            <div class="activity-list" id="mechanic-activity">
                <!-- Dynamic content loaded via JS -->
            </div>
        </div>
    </div>
</div>
<?php endif; ?>
<!-- Unified Task Details Modal -->
<div id="taskDetailsModal" class="modal" style="display: none;">
    <div class="modal-content" style="max-width: 650px;">
        <div class="modal-header" style="background: #2563eb; color: white;">
            <h3><i class="fas fa-info-circle"></i> <span id="modalTitle">Task Details</span></h3>
            <button class="modal-close" onclick="closeTaskDetailsModal()" style="color: white;">&times;</button>
        </div>
        <div class="modal-body" style="padding: 0;" id="taskDetailsContainer">
            <!-- Content will be loaded dynamically -->
        </div>
        <div class="modal-footer" style="padding: 15px; border-top: 1px solid #e5e7eb; display: flex; gap: 10px; justify-content: flex-end;">
            <button class="btn btn-outline" onclick="closeTaskDetailsModal()">Close</button>
            <button class="btn btn-primary" id="taskActionBtn" style="display: none;">Action</button>
        </div>
    </div>
</div>

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
        <select id="reservation-status-filter" class="filter-select">
            <option value="all">All Status</option>
            <option value="approved">Approved</option>
            <option value="pending">Pending</option>
            <option value="rejected">Rejected</option>
        </select>
        <select id="reservation-vehicle-filter" class="filter-select">
            <option value="all">All Vehicles</option>
            <!-- Vehicles will be loaded here by JavaScript -->
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

<!-- Pending Verification Section -->
<?php
function isDispatcher() {
    if (isset($_SESSION['user_data']) && is_array($_SESSION['user_data'])) {
        if (isset($_SESSION['user_data']['role']) && $_SESSION['user_data']['role'] === 'dispatcher') {
            return true;
        }
        if (isset($_SESSION['user_data']['usertype']) && $_SESSION['user_data']['usertype'] === 'dispatcher') {
            return true;
        }
        if (isset($_SESSION['user_data']['user_type']) && $_SESSION['user_data']['user_type'] === 'dispatcher') {
            return true;
        }
    }
    if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'dispatcher') {
        return true;
    }
    if (isset($_SESSION['role']) && $_SESSION['role'] === 'dispatcher') {
        return true;
    }
    if (isset($_SESSION['usertype']) && $_SESSION['usertype'] === 'dispatcher') {
        return true;
    }
    return false;
}

if (function_exists('isDispatcher') && isDispatcher()):
    // Define $trainees HERE inside the dispatcher condition
    try {
        $stmt = $pdo->query("
            SELECT dt.*, u.full_name, u.email, u.employee_id, a.full_name as assigned_by_name
            FROM driver_training dt
            JOIN users u ON dt.user_id = u.id
            JOIN users a ON dt.assigned_by = a.id
            WHERE dt.training_status IN ('pending', 'in_progress')
            ORDER BY dt.assigned_date DESC
        ");
        $trainees = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $trainees = [];
        error_log("Error fetching trainees: " . $e->getMessage());
    }
?>
    <div class="card card-full">
        <div class="card-header">
            <h2><i class="fas fa-clipboard-check"></i> Pending Verification</h2>
            <span class="card-badge">Vehicles awaiting return confirmation</span>
        </div>
        <div class="card-body">
            <div id="verification-list" class="verification-list">
                <div style="text-align: center; padding: 30px; color: #94a3b8;">
                    <i class="fas fa-spinner fa-spin" style="font-size: 40px; margin-bottom: 10px;"></i>
                    <p>Loading verification data...</p>
                </div>
            </div>
        </div>
    </div>

    <!-- New Driver Training Section -->
    <div class="card card-full" style="margin-top: 20px;">
        <div class="card-header">
            <h2><i class="fas fa-users"></i> Driver Training</h2>
            <span class="card-badge">Pending Trainees</span>
        </div>
        <div class="card-body">
            <!-- Training Stats -->
            <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 15px; margin-bottom: 20px;">
                <div style="background: linear-gradient(135deg, #f59e0b, #d97706); padding: 15px; border-radius: 10px; color: white;">
                    <h3 style="margin: 0; font-size: 0.9rem; opacity: 0.9;">Pending Training</h3>
                    <p style="margin: 5px 0 0; font-size: 2rem; font-weight: bold;">
                        <?php echo count(array_filter($trainees, function($t) { return $t['training_status'] === 'pending'; })); ?>
                    </p>
                </div>
                <div style="background: linear-gradient(135deg, #3b82f6, #2563eb); padding: 15px; border-radius: 10px; color: white;">
                    <h3 style="margin: 0; font-size: 0.9rem; opacity: 0.9;">In Progress</h3>
                    <p style="margin: 5px 0 0; font-size: 2rem; font-weight: bold;">
                        <?php echo count(array_filter($trainees, function($t) { return $t['training_status'] === 'in_progress'; })); ?>
                    </p>
                </div>
                <div style="background: linear-gradient(135deg, #10b981, #059669); padding: 15px; border-radius: 10px; color: white;">
                    <h3 style="margin: 0; font-size: 0.9rem; opacity: 0.9;">Completed This Month</h3>
                    <p style="margin: 5px 0 0; font-size: 2rem; font-weight: bold;">
                        <?php
                        try {
                            $stmt = $pdo->query("
                                SELECT COUNT(*) as count FROM driver_training 
                                WHERE training_status = 'passed' 
                                AND MONTH(completed_date) = MONTH(CURRENT_DATE())
                            ");
                            $completed = $stmt->fetch(PDO::FETCH_ASSOC);
                            echo $completed['count'];
                        } catch (PDOException $e) {
                            echo "0";
                        }
                        ?>
                    </p>
                </div>
            </div>

            <!-- Trainees List -->
            <table class="table" style="width: 100%;">
                <thead>
                    <tr>
                        <th>Employee</th>
                        <th>Employee ID</th>
                        <th>Assigned By</th>
                        <th>Assigned Date</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($trainees)): ?>
                    <tr>
                        <td colspan="6" style="text-align: center; padding: 30px;">
                            <i class="fas fa-user-graduate" style="font-size: 40px; color: #94a3b8; margin-bottom: 10px;"></i>
                            <p>No pending trainees</p>
                        </td>
                    </tr>
                    <?php else: ?>
                        <?php foreach ($trainees as $trainee): ?>
                        <tr>
                            <td>
                                <div style="display: flex; align-items: center; gap: 10px;">
                                    <div class="avatar-small">
                                        <?php 
                                        $name = $trainee['full_name'];
                                        $words = explode(" ", $name);
                                        $initials = "";
                                        foreach ($words as $word) {
                                            $initials .= strtoupper(substr($word, 0, 1));
                                        }
                                        echo $initials;
                                        ?>
                                    </div>
                                    <div>
                                        <strong><?php echo htmlspecialchars($trainee['full_name']); ?></strong>
                                        <br>
                                        <small style="color: #64748b;"><?php echo htmlspecialchars($trainee['email']); ?></small>
                                    </div>
                                </div>
                            </td>
                            <td><?php echo htmlspecialchars($trainee['employee_id']); ?></td>
                            <td><?php echo htmlspecialchars($trainee['assigned_by_name']); ?></td>
                            <td><?php echo date('M d, Y', strtotime($trainee['assigned_date'])); ?></td>
                            <td>
                                <?php 
                                $statusColors = [
                                    'pending' => 'badge-warning',
                                    'in_progress' => 'badge-info',
                                    'passed' => 'badge-success',
                                    'failed' => 'badge-danger'
                                ];
                                $statusText = [
                                    'pending' => 'Pending',
                                    'in_progress' => 'In Progress',
                                    'passed' => 'Passed',
                                    'failed' => 'Failed'
                                ];
                                ?>
                                <span class="badge <?php echo $statusColors[$trainee['training_status']]; ?>">
                                    <?php echo $statusText[$trainee['training_status']]; ?>
                                </span>
                            </td>
                            <td>
                                <button class="action-btn" onclick="openTrainingModal(<?php echo $trainee['id']; ?>)" title="Review">
                                    <i class="fas fa-clipboard-check"></i>
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Training Review Modal -->
    <div id="trainingModal" class="modal modal-hidden">
        <div class="modal-content" style="max-width: 500px;">
            <div class="modal-header">
                <h3 class="modal-title">Review Driver Training</h3>
                <button class="modal-close" onclick="closeTrainingModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body" style="padding: 20px;">
                <form id="trainingForm" onsubmit="event.preventDefault(); submitTrainingReview();">
                    <input type="hidden" id="trainingId">
                    
                    <div style="margin-bottom: 20px;">
                        <label class="form-label">Training Status</label>
                        <select class="form-select" id="trainingStatus" required>
                            <option value="in_progress">In Progress</option>
                            <option value="passed">Passed - Promote to Driver</option>
                            <option value="failed">Failed - Reject</option>
                        </select>
                    </div>
                    
                    <div style="margin-bottom: 20px;">
                        <label class="form-label">Notes / Evaluation</label>
                        <textarea class="form-input" id="trainingNotes" rows="4" 
                            placeholder="Enter evaluation notes, feedback, or reason for decision..."></textarea>
                    </div>
                    
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline" onclick="closeTrainingModal()">Cancel</button>
                        <button type="submit" class="btn btn-primary">Submit Review</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
<?php 
endif; // End of dispatcher condition
?>
                    
                    
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
                    <label for="vehicleSelect">Select available Vehicle *</label>
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
                    <label>Purpose</label>
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
<!-- Maintenance Report -->
<div class="card">
    <div class="card-header">
        <h2><i class="fas fa-wrench"></i> Maintenance Report</h2>
        <div style="display: flex; gap: 10px;">
            <?php
// Count by status (you can reuse the same counts from above)
if (!isset($pending_count)) {
    $pending_count = 0;
    $in_progress_count = 0;
    foreach ($maintenance_alerts as $alert) {
        if ($alert['status'] == 'pending') $pending_count++;
        if ($alert['status'] == 'in_progress') $in_progress_count++;
    }
}

// Show appropriate badge
if ($in_progress_count > 0 && $pending_count > 0): ?>
    <span class="card-badge" >
        <i class="fas fa-clock"></i> <?php echo $pending_count; ?> pending
    </span>
    <span class="card-badge">
        <i class="fas fa-spinner fa-spin"></i> <?php echo $in_progress_count; ?> in progress
    </span>
<?php elseif ($in_progress_count > 0): ?>
    <span class="card-badge" >
        <i class="fas fa-spinner fa-spin"></i> <?php echo $in_progress_count; ?> in progress
    </span>
<?php else: ?>
    <span class="card-badge" >
        <i class="fas fa-clock"></i> <?php echo $pending_count; ?> pending
    </span>
<?php endif; ?>
            <?php if ($can_manage_maintenance): ?>
               <button class="btn btn-sm btn-primary" onclick="openCreateMaintenanceModal()">
    <i class="fas fa-wrench"></i> New Task
</button>
            <?php endif; ?>
        </div>
    </div>
    <div class="card-body">
        <div class="maintenance-list">
            <?php if (!empty($maintenance_alerts)): ?>
                <?php foreach ($maintenance_alerts as $alert): 
                    $due_date = new DateTime($alert['due_date']);
                    $now = new DateTime();
                    $days_diff = $now->diff($due_date)->days;
                    $status = $due_date < $now ? 'overdue' : ($days_diff <= 3 ? 'due-soon' : 'upcoming');
                    
                    // Get mechanic name if assigned
                    $mechanic_name = 'Unassigned';
                    if (!empty($alert['assigned_mechanic'])) {
                        $mech_stmt = $pdo->prepare("SELECT full_name FROM users WHERE id = ?");
                        $mech_stmt->execute([$alert['assigned_mechanic']]);
                        $mechanic_name = $mech_stmt->fetchColumn() ?: 'Unknown';
                    }
                ?>
                <div class="maintenance-item">
                    <div class="maintenance-icon">
                        <i class="fas fa-wrench"></i>
                    </div>
                    <div class="maintenance-content">
                        <div class="maintenance-title">
                            <?php echo htmlspecialchars($alert['asset_name']); ?> - <?php echo htmlspecialchars($alert['issue']); ?>
                            <?php if ($alert['issue_type'] == 'critical'): ?>
                                <span class="badge critical">Critical</span>
                            <?php elseif ($alert['issue_type'] == 'major'): ?>
                                <span class="badge major">Major</span>
                            <?php else: ?>
                                <span class="badge minor">Minor</span>
                            <?php endif; ?>
                        </div>
                        <div class="maintenance-meta">
                            <span><i class="fas fa-calendar"></i> Due: <?php echo date('M d, Y', strtotime($alert['due_date'])); ?></span>
                            <span class="priority-<?php echo $alert['priority']; ?>"><?php echo ucfirst($alert['priority']); ?> Priority</span>
                            <span><i class="fas fa-user-cog"></i> Mechanic: <?php echo $mechanic_name; ?></span>
                            <?php if ($alert['estimated_hours']): ?>
                                <span><i class="fas fa-clock"></i> Est: <?php echo $alert['estimated_hours']; ?> hrs</span>
                            <?php endif; ?>
                        </div>
                        <?php if ($alert['status'] == 'in_progress'): ?>
                            <div class="progress-indicator">
                                <span class="badge in-progress">In Progress</span>
                                <?php if ($alert['started_at']): ?>
                                    <small>Started: <?php echo date('M d, H:i', strtotime($alert['started_at'])); ?></small>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="maintenance-due <?php echo $status == 'overdue' ? 'due-overdue' : ($status == 'due-soon' ? 'due-soon' : ''); ?>">
                        <?php echo ucfirst($status); ?>
                    </div>
                    <div class="maintenance-actions">
                        <?php if ($can_manage_maintenance): ?>
                            <?php if ($alert['status'] == 'pending'): ?>
                                <button class="btn-icon" onclick="openAssignMechanicModal(<?php echo $alert['id']; ?>, '<?php echo htmlspecialchars($alert['asset_name']); ?>', '<?php echo htmlspecialchars($alert['issue']); ?>')" title="Assign Mechanic">
                                    <i class="fas fa-user-plus"></i>
                                </button>
                                <button class="btn-icon" onclick="startMaintenance(<?php echo $alert['id']; ?>)" title="Start Work">
                                    <i class="fas fa-play"></i>
                                </button>
                            <?php elseif ($alert['status'] == 'in_progress'): ?>
                                <button class="btn-icon" onclick="openCompleteMaintenanceModal(<?php echo $alert['id']; ?>)" title="Complete">
                                    <i class="fas fa-check"></i>
                                </button>
                            <?php endif; ?>
                            <button class="btn-icon" onclick="editMaintenance(<?php echo $alert['id']; ?>)" title="Edit">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button class="btn-icon" onclick="deleteMaintenance(<?php echo $alert['id']; ?>)" title="Delete">
                                <i class="fas fa-trash"></i>
                            </button>
                        <?php else: ?>
                            <button class="btn-icon" onclick="viewMaintenance(<?php echo $alert['id']; ?>)" title="View Details">
                                <i class="fas fa-eye"></i>
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p style="text-align: center; padding: 20px; color: #999;">No pending maintenance alerts</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Create Maintenance Modal -->
<div id="createMaintenanceModal" class="modal" style="display: none;">
    <div class="modal-content" style="max-width: 500px;">
        <div class="modal-header">
            <h3><i class="fas fa-plus-circle"></i> Create New Maintenance Task</h3>
            <button class="modal-close" onclick="closeCreateMaintenanceModal()">&times;</button>
        </div>
        <div class="modal-body">
            <form id="createMaintenanceForm" onsubmit="createMaintenance(event)">
                <div class="form-group">
                    <label for="createAssetName">Vehicle *</label>
                    <select id="createAssetName" class="form-control" required>
                        <option value="">Select vehicle...</option>
                        <?php
                        // Get all vehicles
                        $vehicle_stmt = $pdo->query("SELECT id, asset_name FROM assets WHERE asset_type = 'vehicle' ORDER BY asset_name");
                        while ($v = $vehicle_stmt->fetch()) {
                            echo "<option value=\"" . htmlspecialchars($v['asset_name']) . "\">" . htmlspecialchars($v['asset_name']) . "</option>";
                        }
                        ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="createIssue">Issue Description *</label>
                    <input type="text" id="createIssue" class="form-control" placeholder="e.g., Oil change, Brake repair" required>
                </div>
                
                <div class="form-row" style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                    <div class="form-group">
                        <label for="createIssueType">Issue Type *</label>
                        <select id="createIssueType" class="form-control" required>
                            <option value="minor">Minor</option>
                            <option value="major">Major</option>
                            <option value="critical">Critical</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="createPriority">Priority *</label>
                        <select id="createPriority" class="form-control" required>
                            <option value="low">Low</option>
                            <option value="medium">Medium</option>
                            <option value="high">High</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-row" style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                    <div class="form-group">
                        <label for="createAssignedMechanic">Assign Mechanic</label>
                        <select id="createAssignedMechanic" class="form-control">
                            <option value="">Select mechanic...</option>
                            <?php
                            // Get mechanics from users table
                            $mech_stmt = $pdo->query("SELECT id, full_name FROM users WHERE role = 'mechanic' AND status = 'active' ORDER BY full_name");
                            while ($mech = $mech_stmt->fetch()) {
                                echo "<option value=\"{$mech['id']}\">{$mech['full_name']}</option>";
                            }
                            ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="createEstimatedHours">Est. Hours</label>
                        <input type="number" id="createEstimatedHours" class="form-control" step="0.5" min="0.5" placeholder="e.g., 2.5">
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="createDueDate">Due Date *</label>
                    <input type="date" id="createDueDate" class="form-control" required>
                </div>
                
                <div class="modal-actions" style="display: flex; gap: 10px; margin-top: 20px;">
                    <button type="button" class="btn btn-secondary" onclick="closeCreateMaintenanceModal()" style="flex: 1;">Cancel</button>
                    <button type="submit" class="btn btn-primary" style="flex: 1;">
                        <i class="fas fa-save"></i> Create Task
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Assign Mechanic Modal -->
<div id="assignMechanicModal" class="modal" style="display: none;">
    <div class="modal-content" style="max-width: 500px;">
        <div class="modal-header">
            <h3><i class="fas fa-wrench"></i> Assign Maintenance Task</h3>
            <button class="modal-close" onclick="closeAssignMechanicModal()">&times;</button>
        </div>
        <div class="modal-body">
            <form id="assignMechanicForm" onsubmit="assignMechanic(event)">
                <input type="hidden" id="maintenanceId" value="">
                
                <div class="form-group">
                    <label>Vehicle</label>
                    <div id="modalVehicleName" style="padding: 10px; background-color: #f8f9fa; border-radius: 4px; font-weight: 500;"></div>
                </div>
                
                <div class="form-group">
                    <label>Issue</label>
                    <div id="modalIssue" style="padding: 10px; background-color: #f8f9fa; border-radius: 4px;"></div>
                </div>
                
                <div class="form-row" style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                    <div class="form-group">
                        <label for="issueType">Issue Type *</label>
                        <select id="issueType" class="form-control" required>
                            <option value="minor">Minor</option>
                            <option value="major">Major</option>
                            <option value="critical">Critical</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="priority">Priority *</label>
                        <select id="priority" class="form-control" required>
                            <option value="low">Low</option>
                            <option value="medium">Medium</option>
                            <option value="high">High</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-row" style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                    <div class="form-group">
                        <label for="assignedMechanic">Assign Mechanic *</label>
                        <select id="assignedMechanic" class="form-control" required>
                            <option value="">Select mechanic...</option>
                            <?php
                            // Get mechanics from users table
                            $mech_stmt = $pdo->query("SELECT id, full_name FROM users WHERE role = 'mechanic' AND status = 'active' ORDER BY full_name");
                            while ($mech = $mech_stmt->fetch()) {
                                echo "<option value=\"{$mech['id']}\">{$mech['full_name']}</option>";
                            }
                            ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="estimatedHours">Est. Hours</label>
                        <input type="number" id="estimatedHours" class="form-control" step="0.5" min="0.5" placeholder="e.g., 2.5">
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="dueDate">Due Date *</label>
                    <input type="date" id="dueDate" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label for="maintenanceNotes">Notes</label>
                    <textarea id="maintenanceNotes" class="form-control" rows="3" placeholder="Additional instructions..."></textarea>
                </div>
                
                <div class="modal-actions" style="display: flex; gap: 10px; margin-top: 20px;">
                    <button type="button" class="btn btn-secondary" onclick="closeAssignMechanicModal()" style="flex: 1;">Cancel</button>
                    <button type="submit" class="btn btn-primary" style="flex: 1;">
                        <i class="fas fa-save"></i> Assign Task
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Complete Maintenance Modal -->
<div id="completeMaintenanceModal" class="modal" style="display: none;">
    <div class="modal-content" style="max-width: 400px;">
        <div class="modal-header">
            <h3><i class="fas fa-check-circle"></i> Complete Maintenance</h3>
            <button class="modal-close" onclick="closeCompleteMaintenanceModal()">&times;</button>
        </div>
        <div class="modal-body">
            <form id="completeMaintenanceForm" onsubmit="completeMaintenance(event)">
                <input type="hidden" id="completeMaintenanceId" value="">
                
                <div class="form-group">
                    <label>Completion Notes</label>
                    <textarea id="completionNotes" class="form-control" rows="4" placeholder="Describe what was done..."></textarea>
                </div>
                
                <div class="modal-actions" style="display: flex; gap: 10px; margin-top: 20px;">
                    <button type="button" class="btn btn-secondary" onclick="closeCompleteMaintenanceModal()" style="flex: 1;">Cancel</button>
                    <button type="submit" class="btn btn-success" style="flex: 1;">
                        <i class="fas fa-check"></i> Complete
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
                        
                        
                    <!-- Maintenance Summary with Repair History -->
<div class="card">
    <div class="card-header">
        <h2><i class="fas fa-clipboard-check"></i> Maintenance Summary</h2>
        <span class="card-badge">This month</span>
    </div>
    <div class="card-body">
        <?php
        // Get maintenance statistics
        $stats = $pdo->query("
            SELECT 
                COUNT(*) as total_tasks,
                SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_tasks,
                SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) as in_progress_tasks,
                SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_tasks,
                SUM(CASE WHEN due_date < CURDATE() AND status != 'completed' THEN 1 ELSE 0 END) as overdue_tasks,
                COUNT(DISTINCT assigned_mechanic) as active_mechanics
            FROM maintenance_alerts 
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        ")->fetch();
        
        // Get recent completed repairs
        $recent_completed = $pdo->query("
            SELECT m.*, u.full_name as mechanic_name
            FROM maintenance_alerts m
            LEFT JOIN users u ON m.assigned_mechanic = u.id
            WHERE m.status = 'completed' 
            AND m.completed_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
            ORDER BY m.completed_date DESC
            LIMIT 5
        ")->fetchAll();
        ?>
        
        <!-- Stats Grid -->
        <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 15px; margin-bottom: 20px;">
            <div style="text-align: center; padding: 15px; background: #f8fafc; border-radius: 8px;">
                <div style="font-size: 24px; font-weight: 700; color: #3b82f6;"><?php echo $stats['total_tasks'] ?? 0; ?></div>
                <div style="font-size: 12px; color: #64748b;">Total Tasks</div>
            </div>
            <div style="text-align: center; padding: 15px; background: #f8fafc; border-radius: 8px;">
                <div style="font-size: 24px; font-weight: 700; color: #f59e0b;"><?php echo $stats['in_progress_tasks'] ?? 0; ?></div>
                <div style="font-size: 12px; color: #64748b;">In Progress</div>
            </div>
            <div style="text-align: center; padding: 15px; background: #f8fafc; border-radius: 8px;">
                <div style="font-size: 24px; font-weight: 700; color: #10b981;"><?php echo $stats['completed_tasks'] ?? 0; ?></div>
                <div style="font-size: 12px; color: #64748b;">Completed</div>
            </div>
        </div>
        
        <!-- Overdue Alert -->
        <?php if (($stats['overdue_tasks'] ?? 0) > 0): ?>
        <div style="background-color: #fee2e2; border: 1px solid #fecaca; color: #dc2626; padding: 10px 15px; border-radius: 8px; margin-bottom: 20px;">
            <i class="fas fa-exclamation-triangle"></i>
            <strong><?php echo $stats['overdue_tasks']; ?></strong> overdue task(s) require immediate attention!
        </div>
        <?php endif; ?>
        
        <!-- Recent Repairs -->
        <div>
            <h4 style="margin: 0 0 15px 0; font-size: 14px; color: #1e293b;">
                <i class="fas fa-history"></i> Recent Repairs (Last 7 Days)
            </h4>
            
            <?php if (!empty($recent_completed)): ?>
                <div class="repair-history-list">
                    <?php foreach ($recent_completed as $repair): ?>
                        <div style="display: flex; justify-content: space-between; align-items: center; padding: 10px; border-bottom: 1px solid #f0f0f0;">
                            <div>
                                <div style="font-weight: 500;"><?php echo htmlspecialchars($repair['asset_name']); ?></div>
                                <div style="font-size: 12px; color: #64748b;">
                                    <i class="fas fa-tools"></i> <?php echo htmlspecialchars($repair['issue']); ?>
                                </div>
                            </div>
                            <div style="text-align: right;">
                                <div style="font-size: 12px; font-weight: 500; color: #10b981;">
                                    <i class="fas fa-check-circle"></i> Completed
                                </div>
                                <div style="font-size: 11px; color: #64748b;">
                                    <?php echo date('M d', strtotime($repair['completed_date'])); ?> 
                                    by <?php echo htmlspecialchars($repair['mechanic_name'] ?? 'Unknown'); ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p style="text-align: center; padding: 20px; color: #94a3b8; background: #f8fafc; border-radius: 8px;">
                    <i class="fas fa-info-circle"></i> No repairs completed in the last 7 days
                </p>
            <?php endif; ?>
        </div>
        <!-- Mechanic Performance -->
        <?php if ($can_manage_maintenance): ?>
        <div style="margin-top: 20px;">
            <h4 style="margin: 0 0 15px 0; font-size: 14px; color: #1e293b;">
                <i class="fas fa-users"></i> Mechanic Performance
            </h4>
            <?php
            $mechanic_perf = $pdo->query("
                SELECT 
                    u.full_name,
                    COUNT(m.id) as total_tasks,
                    SUM(CASE WHEN m.status = 'completed' THEN 1 ELSE 0 END) as completed,
                    ROUND(AVG(CASE WHEN m.status = 'completed' THEN DATEDIFF(m.completed_date, m.due_date) ELSE NULL END), 1) as avg_completion_time
                FROM users u
                LEFT JOIN maintenance_alerts m ON u.id = m.assigned_mechanic
                WHERE u.role = 'mechanic' AND u.status = 'active'
                GROUP BY u.id
                ORDER BY completed DESC
            ")->fetchAll();
            ?>
            
            <?php foreach ($mechanic_perf as $mech): ?>
                <div style="display: flex; justify-content: space-between; align-items: center; padding: 8px; border-bottom: 1px dashed #f0f0f0;">
                    <div>
                        <span style="font-weight: 500;"><?php echo htmlspecialchars($mech['full_name']); ?></span>
                        <span style="font-size: 11px; color: #64748b; margin-left: 10px;">
                            <?php echo $mech['completed']; ?>/<?php echo $mech['total_tasks']; ?> completed
                        </span>
                    </div>
                    <span style="font-size: 12px; color: <?php echo ($mech['avg_completion_time'] <= 0 ? '#10b981' : '#f59e0b'); ?>;">
                        <?php echo $mech['avg_completion_time'] ? $mech['avg_completion_time'] . ' days avg' : 'N/A'; ?>
                    </span>
                </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</div>
<!-- Assign Mechanic to Breakdown Modal -->
<div id="assignMechanicBreakdownModal" class="modal" style="display: none;">
    <div class="modal-content" style="max-width: 500px;">
        <div class="modal-header" style="background: #dc2626; color: white;">
            <h3><i class="fas fa-wrench"></i> Assign Mechanic to Breakdown</h3>
            <button class="modal-close" onclick="closeAssignMechanicBreakdownModal()" style="color: white;">&times;</button>
        </div>
        <div class="modal-body" style="padding: 20px;">
          <form id="assignMechanicBreakdownForm" onsubmit="submitMechanicAssignment(event)">
                <input type="hidden" id="breakdownId" value="">
                
                <div style="background: #fef2f2; padding: 15px; border-radius: 8px; margin-bottom: 20px; border-left: 4px solid #dc2626;">
                    <div id="breakdownSummary">
                        <strong>Vehicle:</strong> <span id="breakdownVehicle"></span><br>
                        <strong>Location:</strong> <span id="breakdownLocation"></span><br>
                        <strong>Driver:</strong> <span id="breakdownDriver"></span><br>
                        <strong>Issue:</strong> <span id="breakdownIssue"></span>
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Select Mechanic *</label>
                    <select id="breakdownMechanicSelect" class="form-control" required>
                        <option value="">Loading mechanics...</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Estimated Arrival Time</label>
                    <input type="text" id="breakdownEstimatedArrival" class="form-control" 
                        placeholder="e.g., 30 minutes, 1 hour, ASAP">
                </div>
                
                <div class="form-group">
                    <label class="form-label">Instructions for Mechanic</label>
                    <textarea id="breakdownInstructions" class="form-control" rows="3" 
                        placeholder="Any special instructions, tools needed, or additional info..."></textarea>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline" onclick="closeAssignMechanicBreakdownModal()">Cancel</button>
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-check-circle"></i> Assign Mechanic
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<!-- View Breakdown Details Modal -->
<div id="viewBreakdownModal" class="modal" style="display: none;">
    <div class="modal-content" style="max-width: 600px;">
        <div class="modal-header" style="background: #dc2626; color: white;">
            <h3><i class="fas fa-exclamation-triangle"></i> Emergency Breakdown Details</h3>
            <button class="modal-close" onclick="closeViewBreakdownModal()" style="color: white;">&times;</button>
        </div>
        <div class="modal-body" style="padding: 20px;">
            <div id="breakdownDetailsContainer">
                <!-- Content will be loaded dynamically -->
            </div>
        </div>
        <div class="modal-footer" style="padding: 15px; border-top: 1px solid #e5e7eb; display: flex; gap: 10px; justify-content: flex-end;">
            <button class="btn btn-outline" onclick="closeViewBreakdownModal()">Close</button>
            <button class="btn btn-danger" id="assignFromDetailsBtn" onclick="assignFromDetails()">
                <i class="fas fa-user-plus"></i> Assign Mechanic
            </button>
        </div>
    </div>
</div>
  <?php if ($_SESSION['role'] === 'fleet_manager' || $_SESSION['role'] === 'admin'): ?>
<!-- Emergency Breakdowns Section (Always visible) -->
<div class="card card-full" style="border-left: 4px solid #ef4444; margin-bottom: 20px;">
    <div class="card-header" style="background: #fee2e2;">
        <h2><i class="fas fa-exclamation-triangle" style="color: #ef4444;"></i> Active Breakdowns</h2>
        <span class="card-badge" style="background: #ef4444; color: white;" id="breakdown-count">0</span>
    </div>
    <div class="card-body" id="breakdown-list">
        <!-- Loaded via AJAX -->
    </div>
</div>
<?php endif; ?>
                </div>
            </div>
        </main>
    </div>
<script>
document.body.dataset.userRole = '<?php echo $_SESSION['role']; ?>';
</script>
<script src="../assets/js/pages/fleet.js"></script>

<!-- Move this AFTER fleet.js and make it more aggressive -->
<script>
// OVERRIDE: Force stats to show PHP-calculated values
(function() {
    console.log('🔧 Force-updating stats with PHP values');
    
    // Get the PHP-calculated values
    const phpStats = {
        total: <?php echo $total_vehicles; ?>,
        available: <?php echo $available_count; ?>,
        maintenance: <?php echo $maintenance_count; ?>,
        inUse: <?php echo $in_use_count ?? 0; ?>
    };
    
    console.log('PHP Stats:', phpStats);
    
    // Function to update stats
    function updateStats() {
        const totalStat = document.getElementById('total-vehicles-stat');
        const availableStat = document.getElementById('available-vehicles-stat');
        const maintenanceStat = document.getElementById('maintenance-vehicles-stat');
        
        if (totalStat) {
            totalStat.textContent = phpStats.total;
            console.log('✅ Updated total to:', phpStats.total);
        }
        
        if (availableStat) {
            availableStat.textContent = phpStats.available;
            console.log('✅ Updated available to:', phpStats.available);
            
            // Update the percentage
            const percentSpan = document.querySelector('.stat-card:nth-child(2) .stat-trend');
            if (percentSpan && phpStats.total > 0) {
                const percent = Math.round((phpStats.available / phpStats.total) * 100);
                percentSpan.innerHTML = `<i class="fas fa-arrow-up"></i> ${percent}% of fleet`;
            }
        }
        
        if (maintenanceStat) {
            maintenanceStat.textContent = phpStats.maintenance;
            console.log('✅ Updated maintenance to:', phpStats.maintenance);
            
            // Update maintenance status text
            const maintTrend = document.querySelector('.stat-card:nth-child(3) .stat-trend');
            if (maintTrend) {
                const pendingCount = <?php 
                    $pending = 0;
                    foreach ($maintenance_alerts as $alert) {
                        if ($alert['status'] == 'pending') $pending++;
                    }
                    echo $pending;
                ?>;
                const inProgressCount = <?php 
                    $in_progress = 0;
                    foreach ($maintenance_alerts as $alert) {
                        if ($alert['status'] == 'in_progress') $in_progress++;
                    }
                    echo $in_progress;
                ?>;
                
                if (inProgressCount > 0 && pendingCount > 0) {
                    maintTrend.innerHTML = `<i class="fas fa-tasks"></i> ${pendingCount} pending, ${inProgressCount} in progress`;
                } else if (inProgressCount > 0) {
                    maintTrend.innerHTML = `<i class="fas fa-spinner fa-spin"></i> ${inProgressCount} in progress`;
                } else {
                    maintTrend.innerHTML = `<i class="fas fa-clock"></i> ${pendingCount} pending`;
                }
            }
        }
        
        // Update fleet efficiency
        const efficiencyStat = document.querySelector('.stat-card:nth-child(4) .stat-value');
        if (efficiencyStat) {
            const totalShipments = <?php echo $shipment_stats['total'] ?? 0; ?>;
            const delivered = <?php echo $shipment_stats['delivered'] ?? 0; ?>;
            const efficiency = totalShipments > 0 ? Math.round((delivered / totalShipments) * 100) : 0;
            efficiencyStat.textContent = efficiency + '%';
        }
    }
    
    // Run immediately
    updateStats();
    
    // Run after DOM is loaded
    document.addEventListener('DOMContentLoaded', updateStats);
    
    // Run repeatedly to catch any late updates
    let attempts = 0;
    const interval = setInterval(function() {
        const totalStat = document.getElementById('total-vehicles-stat');
        if (totalStat && totalStat.textContent != phpStats.total) {
            console.log('⏰ Stats changed, updating again...');
            updateStats();
        }
        
        attempts++;
        if (attempts > 10) clearInterval(interval); // Stop after 10 attempts
    }, 500);
    
    // Also run after a longer delay
    setTimeout(updateStats, 2000);
    setTimeout(updateStats, 5000);
})();

// ===== MOBILE RESPONSIVE FUNCTIONS =====

// Create mobile header if it doesn't exist
function initMobileHeader() {
    if (document.querySelector('.mobile-header')) return;
    
    const mobileHeader = document.createElement('div');
    mobileHeader.className = 'mobile-header';
    
    // Get user info from existing header
    const userName = document.querySelector('.user-name-header')?.textContent || 'User';
    const avatar = document.querySelector('.avatar-small')?.innerHTML || 'U';
    
    mobileHeader.innerHTML = `
        <button class="mobile-menu-btn" onclick="toggleMobileSidebar()">
            <i class="fas fa-bars"></i>
        </button>
        <div class="mobile-title">Fleet Management</div>
        <div class="mobile-user">
            <div class="avatar-small">${avatar}</div>
        </div>
    `;
    
    document.body.insertBefore(mobileHeader, document.body.firstChild);
    
    // Create overlay if it doesn't exist
    if (!document.querySelector('.sidebar-overlay')) {
        const overlay = document.createElement('div');
        overlay.className = 'sidebar-overlay';
        overlay.onclick = toggleMobileSidebar;
        document.body.appendChild(overlay);
    }
}

// Toggle mobile sidebar
function toggleMobileSidebar() {
    const sidebar = document.querySelector('.sidebar');
    const overlay = document.querySelector('.sidebar-overlay');
    
    if (sidebar) {
        sidebar.classList.toggle('mobile-show');
        if (overlay) overlay.classList.toggle('show');
    }
}

// Close sidebar when clicking a link (on mobile)
function setupMobileNavLinks() {
    const navLinks = document.querySelectorAll('.sidebar .nav-item a, .sidebar a');
    navLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            if (window.innerWidth <= 768) {
                // Don't close if it's a dropdown toggle
                if (this.closest('.dropdown-toggle')) return;
                
                setTimeout(() => {
                    toggleMobileSidebar();
                }, 100);
            }
        });
    });
}

// Handle window resize
function handleResize() {
    if (window.innerWidth > 768) {
        const sidebar = document.querySelector('.sidebar');
        const overlay = document.querySelector('.sidebar-overlay');
        
        if (sidebar) sidebar.classList.remove('mobile-show');
        if (overlay) overlay.classList.remove('show');
    }
}

// Make tables scrollable on mobile
function makeTablesResponsive() {
    const tables = document.querySelectorAll('.table');
    tables.forEach(table => {
        if (!table.parentElement.classList.contains('table-responsive')) {
            const wrapper = document.createElement('div');
            wrapper.className = 'table-responsive';
            wrapper.style.overflowX = 'auto';
            wrapper.style.WebkitOverflowScrolling = 'touch';
            table.parentNode.insertBefore(wrapper, table);
            wrapper.appendChild(table);
        }
    });
}

// Adjust modals for mobile
function adjustModalsForMobile() {
    const modals = document.querySelectorAll('.modal');
    modals.forEach(modal => {
        const content = modal.querySelector('.modal-content');
        if (content) {
            // Ensure modals are scrollable on mobile
            if (window.innerWidth <= 768) {
                content.style.maxHeight = '80vh';
                content.style.overflowY = 'auto';
            }
        }
    });
}

// Initialize all mobile features
function initMobileResponsive() {
    initMobileHeader();
    setupMobileNavLinks();
    makeTablesResponsive();
    adjustModalsForMobile();
    
    window.addEventListener('resize', function() {
        handleResize();
        adjustModalsForMobile();
    });
}

// Call this when document is ready
document.addEventListener('DOMContentLoaded', function() {
    initMobileResponsive();
    
    // Handle orientation change
    window.addEventListener('orientationchange', function() {
        setTimeout(adjustModalsForMobile, 100);
    });
});
// Fix for deployment: Refresh stats if they show 0 but vehicles exist
document.addEventListener('DOMContentLoaded', function() {
    setTimeout(function() {
        const totalVehicles = parseInt(document.getElementById('total-vehicles-stat')?.textContent || '0');
        const availableVehicles = parseInt(document.getElementById('available-vehicles-stat')?.textContent || '0');
        const maintenanceVehicles = parseInt(document.getElementById('maintenance-vehicles-stat')?.textContent || '0');
        
        console.log('Stats check - Total:', totalVehicles, 'Available:', availableVehicles, 'Maintenance:', maintenanceVehicles);
        
        // If all stats are 0 but we have vehicles in the table, reload the page once
        if (totalVehicles === 0 && availableVehicles === 0 && maintenanceVehicles === 0) {
            const vehicleItems = document.querySelectorAll('.vehicle-item');
            if (vehicleItems.length > 0 && !sessionStorage.getItem('stats_reloaded')) {
                console.log('Stats are 0 but vehicles exist. Reloading page...');
                sessionStorage.setItem('stats_reloaded', 'true');
                location.reload();
            }
        } else {
            // Clear the reload flag if stats are showing correctly
            sessionStorage.removeItem('stats_reloaded');
        }
    }, 1000);
});
</script>
</body>