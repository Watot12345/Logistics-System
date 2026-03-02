<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
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
                            <h1>Fleet Management</h1>
                            <p>Monitor vehicles, drivers, and fleet operations</p>
                        </div>
                        <div class="header-actions">
                            <button class="btn btn-outline" id="refreshData">
                                <i class="fas fa-sync-alt"></i>
                                Refresh
                            </button>
                            <button class="btn btn-primary" onclick="openReservationModal()">
                                <i class="fas fa-plus"></i>
                                New Reservation
                            </button>
                        </div>
                    </div>
                </div>
                
                <!-- Statistics Cards -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-header">
                            <div class="stat-icon blue">
                                <i class="fas fa-truck"></i>
                            </div>
                            <span class="stat-badge green">+2 this month</span>
                        </div>
                        <p class="stat-label">Total Vehicles</p>
                        <p class="stat-value">42</p>
                        <div class="stat-trend">
                            <i class="fas fa-arrow-up"></i> 5% from last month
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-header">
                            <div class="stat-icon emerald">
                                <i class="fas fa-check-circle"></i>
                            </div>
                            <span class="stat-badge green">28 available</span>
                        </div>
                        <p class="stat-label">Available</p>
                        <p class="stat-value">28</p>
                        <div class="stat-trend">
                            <i class="fas fa-arrow-up"></i> 67% of fleet
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-header">
                            <div class="stat-icon amber">
                                <i class="fas fa-tools"></i>
                            </div>
                            <span class="stat-badge amber">8 in maintenance</span>
                        </div>
                        <p class="stat-label">In Maintenance</p>
                        <p class="stat-value">8</p>
                        <div class="stat-trend down">
                            <i class="fas fa-arrow-down"></i> 2 from yesterday
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-header">
                            <div class="stat-icon purple">
                                <i class="fas fa-chart-line"></i>
                            </div>
                            <span class="stat-badge green">94% efficiency</span>
                        </div>
                        <p class="stat-label">Fleet Efficiency</p>
                        <p class="stat-value">94%</p>
                        <div class="stat-trend">
                            <i class="fas fa-arrow-up"></i> 2% vs target
                        </div>
                    </div>
                </div>
                
                <!-- Tabs -->
                <div class="card" style="margin-bottom: 24px;">
                    <div class="tabs">
                        <button class="tab active" data-tab="overview">Overview</button>
                        <button class="tab" data-tab="vehicles">Vehicles</button>
                        <button class="tab" data-tab="drivers">Drivers</button>
                        <button class="tab" data-tab="reservations">Reservations</button>
                        <button class="tab" data-tab="maintenance">Maintenance</button>
                    </div>
                </div>
                
                <!-- Tab Panes -->
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
                                    <select class="filter-select">
                                        <option value="all">All Types</option>
                                        <option value="truck">Trucks</option>
                                        <option value="van">Vans</option>
                                    </select>
                                    <select class="filter-select">
                                        <option value="all">All Status</option>
                                        <option value="available">Available</option>
                                        <option value="in-use">In Use</option>
                                        <option value="maintenance">Maintenance</option>
                                    </select>
                                </div>
                            </div>
                            <div class="vehicle-list">
                                <!-- Dynamic content loaded via JS -->
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
                
                <div id="tab-drivers" class="tab-pane" style="display: none;">
                    <!-- Driver Performance Dashboard -->
                    <div class="dashboard-grid">
                        <div class="card">
                            <div class="card-header">
                                <h2><i class="fas fa-star"></i> Driver Performance</h2>
                                <span class="card-badge">Rankings</span>
                            </div>
                            <div class="card-body">
                                <div class="driver-list">
                                    <!-- Dynamic content loaded via JS -->
                                </div>
                            </div>
                        </div>
                        
                        <div class="card">
                            <div class="card-header">
                                <h2><i class="fas fa-chart-bar"></i> Performance Metrics</h2>
                                <span class="card-badge">This month</span>
                            </div>
                            <div class="card-body">
                                <!-- Performance charts would go here -->
                                <div style="text-align: center; padding: 40px; color: #94a3b8;">
                                    <i class="fas fa-chart-bar" style="font-size: 48px; margin-bottom: 16px;"></i>
                                    <p>Performance charts loading...</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Driver Activity Dashboard -->
                    <div class="card card-full">
                        <div class="card-header">
                            <h2><i class="fas fa-activity"></i> Driver Activity Dashboard</h2>
                            <span class="card-badge">Live tracking</span>
                        </div>
                        <div class="card-body">
                            <div class="activity-timeline">
                                <!-- Dynamic content loaded via JS -->
                            </div>
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
                
                <div id="tab-maintenance" class="tab-pane" style="display: none;">
                    <!-- Maintenance Report -->
                    <div class="dashboard-grid">
                        <div class="card">
                            <div class="card-header">
                                <h2><i class="fas fa-wrench"></i> Maintenance Report</h2>
                                <span class="card-badge">Upcoming</span>
                            </div>
                            <div class="card-body">
                                <div class="maintenance-list">
                                    <!-- Dynamic content loaded via JS -->
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
                                <!-- Dynamic content loaded via JS -->
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