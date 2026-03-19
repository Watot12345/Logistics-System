<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/security_headers.php';

// DEBUG - Add this temporary code
$debug_role = $_SESSION['role'] ?? 'NOT SET';
$debug_check = in_array($debug_role, ['admin', 'dispatcher', 'employee', 'fleet_manager']) ? 'YES' : 'NO';
?>
<!-- ========== SESSION DEBUG ========== -->
<!-- Session Role: <?php echo $debug_role; ?> -->
<!-- Allowed Check: <?php echo $debug_check; ?> -->
<!-- Session Data: <?php echo htmlspecialchars(print_r($_SESSION, true)); ?> -->
<!-- ========== END DEBUG ========== -->
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>📦</text></svg>">
    <?php
    if (isset($page_css)) {
        if (is_array($page_css)) {
            foreach ($page_css as $css) {
                echo '<link rel="stylesheet" href="' . $css . '">' . "\n    ";
            }
        } else {
            echo '<link rel="stylesheet" href="' . $page_css . '">';
        }
    } else {
        // Make sure this path is correct - should point to your optimized CSS
        echo '<link rel="stylesheet" href="assets/css/dashboard.css">';
    }
    ?>
    <title><?php echo $page_title ?? 'Logistics System'; ?></title>
</head>
<body>
    <div class="app-container">
        <!-- Sidebar -->
        <aside class="sidebar" id="sidebar">
            <div class="logo-area">
                <div class="logo-container">
                    <div class="logo-icon">
                        <i class="fas fa-cubes"></i>
                    </div>
                    <div class="logo-text">
                        <h1>Logistics</h1>
                        <p>hotel & restaurant</p>
                    </div>
                </div>
            </div>
            
<nav class="nav-menu">
    <div class="nav-section">
        <p class="nav-section-title">Main Menu</p>
        <ul class="nav-list">
            
            <!-- Dashboard - visible to admin, dispatcher, employee, fleet_manager -->
<?php 
$show_dashboard = in_array($_SESSION['role'], ['admin', 'dispatcher', 'employee', 'fleet_manager']);
echo "<!-- DEBUG: Show Dashboard = " . ($show_dashboard ? 'YES' : 'NO') . " -->";

if ($show_dashboard): 
?>
<li class="nav-item">
    <a href="<?php echo (strpos($_SERVER['PHP_SELF'], 'modules') !== false) ? '../dashboard.php' : 'dashboard.php'; ?>" 
       class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'dashboard.php') ? 'active' : ''; ?>">
        <i class="fas fa-th-large"></i>
        <span>Dashboard</span>
    </a>
</li>
<?php endif; ?>
            
            <!-- Smart warehousing - visible to admin, dispatcher, employee, fleet_manager -->
            <?php if (in_array($_SESSION['role'], ['admin', 'dispatcher', 'employee', 'fleet_manager'])): ?>
            <li class="nav-item">
                <a href="<?php echo (strpos($_SERVER['PHP_SELF'], 'modules') !== false) ? 'inventory.php' : 'modules/inventory.php'; ?>" 
                   class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'inventory.php') ? 'active' : ''; ?>">
                    <i class="fas fa-boxes"></i>
                    <span>Smart warehousing</span>
                </a>
            </li>
            <?php endif; ?>
            
            <!-- Purchase Orders - visible to admin, employee -->
            <?php if (in_array($_SESSION['role'], ['admin', 'employee','dispatcher', 'fleet_manager'])): ?>
            <li class="nav-item">
                <a href="<?php echo (strpos($_SERVER['PHP_SELF'], 'modules') !== false) ? 'orders.php' : 'modules/orders.php'; ?>" 
                   class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'orders.php') ? 'active' : ''; ?>">
                    <i class="fas fa-shopping-cart"></i>
                    <span>Purchase Orders</span>
                </a>
            </li>
            <?php endif; ?>
            
            <!-- Fleet Management - visible to admin, fleet_manager, driver, dispatcher -->
            <?php if (in_array($_SESSION['role'], ['admin', 'fleet_manager', 'driver', 'dispatcher', 'employee'])): ?>
            <li class="nav-item">
                <a href="<?php echo (strpos($_SERVER['PHP_SELF'], 'modules') !== false) ? 'fleet.php' : 'modules/fleet.php'; ?>" 
                   class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'fleet.php') ? 'active' : ''; ?>">
                    <i class="fas fa-truck"></i>
                    <span>Fleet Operations</span>
                </a>
            </li>
            <?php endif; ?>

            <?php if (in_array($_SESSION['role'], ['admin'])): ?>
            <li class="nav-item">
                <a href="<?php echo (strpos($_SERVER['PHP_SELF'], 'modules') !== false) ? 'employee.php' : 'modules/employee.php'; ?>" 
                   class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'employee.php') ? 'active' : ''; ?>">
                    <i class="fas fa-users"></i>
                    <span>Employee Management</span>
                </a>
            </li>
            <?php endif; ?>
        </ul>
    </div>
</nav>
            
            <div class="user-profile">
    <div class="profile-container">
        <div class="avatar">
            <?php echo isset($_SESSION['full_name']) ? strtoupper(substr($_SESSION['full_name'], 0, 2)) : 'GU'; ?>
        </div>

        <div class="user-info">
            <p class="user-name">
                <?php echo isset($_SESSION['full_name']) ? htmlspecialchars($_SESSION['full_name']) : 'Guest User'; ?>
            </p>
            <p class="user-role">
                <?php 
                $role = isset($_SESSION['role']) ? $_SESSION['role'] : 'Guest';
                
                // Format the role name for display
                switch($role) {
                    case 'fleet_manager':
                        $display_role = 'Fleet Manager';
                        break;
                    case 'admin':
                        $display_role = 'Admin';
                        break;
                    case 'dispatcher':
                        $display_role = 'Dispatcher';
                        break;
                    case 'driver':
                        $display_role = 'Driver';
                        break;
                    case 'employee':
                        $display_role = 'Employee';
                        break;
                    default:
                        $display_role = ucfirst($role);
                }
                
                echo htmlspecialchars($display_role); 
                ?>
            </p>
        </div>
          <a href="<?php echo (strpos($_SERVER['PHP_SELF'], 'modules') !== false) ? '../includes/logout.php' : 'includes/logout.php'; ?>" class="logout-btn">
            <i class="fas fa-sign-out-alt"></i>
        </a>
    </div>
        </aside>
        <!-- Rest of your content will be added here -->