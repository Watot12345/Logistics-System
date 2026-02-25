<?php
// dashboard.php
$page_title = 'Dashboard | Logistics System';
$page_css = 'assets/css/style.css';
include 'includes/header.php';
?>

        <!-- Page Content -->
        <div class="main-content">
                <!-- Page Header -->
                <div class="page-header">
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <div>
                            <h1>Logistics Dashboard</h1>
                            <p>Monitor assets, maintenance, and documentation</p>
                        </div>
                        <div>
                            <button class="btn btn-outline" id="refreshDashboard">
                                <i class="fas fa-sync-alt"></i>
                                Refresh
                            </button>
                        </div>
                    </div>
                </div>
                
                <!-- Dashboard Grid -->
                <div class="dashboard-grid">
                    <!-- Asset List and Current Condition -->
                    <div class="card">
                        <div class="card-header">
                            <h2><i class="fas fa-boxes"></i> Asset List & Condition</h2>
                            <span class="card-badge">45 assets</span>
                        </div>
                        <div class="card-body">
                            <div class="asset-list">
                                <!-- Dynamic content loaded via JavaScript -->
                            </div>
                        </div>
                    </div>
                    
                    <!-- Maintenance Schedule Alerts -->
                    <div class="card">
                        <div class="card-header">
                            <h2><i class="fas fa-exclamation-triangle"></i> Maintenance Alerts</h2>
                            <span class="card-badge">5 due soon</span>
                        </div>
                        <div class="card-body">
                            <div class="alert-list">
                                <!-- Dynamic content loaded via JavaScript -->
                            </div>
                        </div>
                    </div>
                    
                    <!-- Maintenance History Report -->
                    <div class="card">
                        <div class="card-header">
                            <h2><i class="fas fa-history"></i> Maintenance History</h2>
                            <span class="card-badge">Last 30 days</span>
                        </div>
                        <div class="card-body">
                            <div class="timeline">
                                <!-- Dynamic content loaded via JavaScript -->
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Asset Lifecycle Summary -->
                <div class="card card-full">
                    <div class="card-header">
                        <h2><i class="fas fa-chart-pie"></i> Asset Lifecycle Summary</h2>
                        <span class="card-badge">Current status</span>
                    </div>
                    <div class="card-body">
                        <div id="assetLifecycle">
                            <!-- Dynamic content loaded via JavaScript -->
                        </div>
                    </div>
                </div>
                
                <!-- Digital Document Repository -->
                <div class="card card-full">
                    <div class="card-header">
                        <h2><i class="fas fa-folder"></i> Digital Document Repository</h2>
                        <span class="card-badge">69 documents</span>
                    </div>
                    <div class="card-body">
                        <div class="document-grid">
                            <!-- Dynamic content loaded via JavaScript -->
                        </div>
                    </div>
                </div>
                
                <!-- Logistics Document Tracking Dashboard -->
                <div class="card card-full">
                    <div class="card-header">
                        <h2><i class="fas fa-truck"></i> Logistics Document Tracking</h2>
                        <span class="card-badge">Active shipments</span>
                    </div>
                    <div class="card-body">
                        <div class="tracking-list">
                            <!-- Dynamic content loaded via JavaScript -->
                        </div>
                    </div>
                </div>
                
                <!-- Document Access Logs -->
                <div class="card card-full">
                    <div class="card-header">
                        <h2><i class="fas fa-clipboard-list"></i> Document Access Logs</h2>
                        <span class="card-badge">Real-time</span>
                    </div>
                    <div class="card-body">
                        <table class="logs-table">
                            <thead>
                                <tr>
                                    <th>User</th>
                                    <th>Document</th>
                                    <th>Action</th>
                                    <th>Time</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- Dynamic content loaded via JavaScript -->
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <!-- Downloadable Logistics Records -->
                <div class="card card-full">
                    <div class="card-header">
                        <h2><i class="fas fa-download"></i> Downloadable Records</h2>
                        <span class="card-badge">5 files</span>
                    </div>
                    <div class="card-body">
                        <div class="download-section">
                            <!-- Dynamic content loaded via JavaScript -->
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
</div>
    
    <script src="assets/js/modals/dashboard.js"></script>
</body>
</html>