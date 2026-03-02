<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
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
                        <li class="nav-item">
                            <a href="<?php echo (strpos($_SERVER['PHP_SELF'], 'modules') !== false) ? '../dashboard.php' : 'dashboard.php'; ?>" class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'dashboard.php') ? 'active' : ''; ?>">
                                <i class="fas fa-th-large"></i>
                                <span>Dashboard</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="<?php echo (strpos($_SERVER['PHP_SELF'], 'modules') !== false) ? 'inventory.php' : 'modules/inventory.php'; ?>" class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'inventory.php') ? 'active' : ''; ?>">
                                <i class="fas fa-boxes"></i>
                                <span>Inventory</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="<?php echo (strpos($_SERVER['PHP_SELF'], 'modules') !== false) ? 'orders.php' : 'modules/orders.php'; ?>" class="nav-link <?php echo (strpos($_SERVER['PHP_SELF'], 'orders.php') !== false) ? 'active' : ''; ?>">
                                <i class="fas fa-shopping-cart"></i>
                                <span>Orders</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="<?php echo (strpos($_SERVER['PHP_SELF'], 'modules') !== false) ? 'fleet.php' : 'modules/fleet.php'; ?>" class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'fleet.php') ? 'active' : ''; ?>">
                                <i class="fas fa-truck"></i>
                                <span>Fleet management</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="<?php echo (strpos($_SERVER['PHP_SELF'], 'modules') !== false) ? 'employee.php' : 'modules/employee.php'; ?>" class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'employee.php') ? 'active' : ''; ?>">
                                <i class="fas fa-users"></i>
                                <span>Employee management</span>
                            </a>
                        </li>
                    </ul>
                </div>
                
                <div class="nav-section">
                    <p class="nav-section-title">Reports</p>
                    <ul class="nav-list">
                        <li class="nav-item">
                            <a href="<?php echo (strpos($_SERVER['PHP_SELF'], 'modules') !== false) ? 'reports.php' : 'modules/reports.php'; ?>" class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'reports.php') ? 'active' : ''; ?>">
                                <i class="fas fa-file-alt"></i>
                                <span>Reports</span>
                            </a>
                        </li>
                    </ul>
                </div>
                
                <div class="nav-section">
                    <p class="nav-section-title">Settings</p>
                    <ul class="nav-list">
                        <li class="nav-item">
                            <a href="#" class="nav-link">
                                <i class="fas fa-cog"></i>
                                <span>Settings</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="#" class="nav-link">
                                <i class="fas fa-shield-alt"></i>
                                <span>Security</span>
                            </a>
                        </li>
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
                            <?php echo isset($_SESSION['role']) ? htmlspecialchars($_SESSION['role']) : 'Guest'; ?>
                        </p>
                    </div>

                    <a href="<?php echo (strpos($_SERVER['PHP_SELF'], 'modules') !== false) ? '../includes/logout.php' : 'includes/logout.php'; ?>" class="logout-btn">
                        <i class="fas fa-sign-out-alt"></i>
                    </a>
                </div>
            </div>
        </aside>
        <!-- Rest of your content will be added here -->