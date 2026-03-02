<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}
$page_title = 'Employee Management | Logistics System';
$page_css = ['../assets/css/style.css', '../assets/css/reports.css'];
include '../includes/header.php';
?>

            <!-- Page Content -->
            <div class="page-content">
                <!-- Report Header -->
                <div class="report-header">
                    <div class="report-header-left">
                        <h1>System Reports</h1>
                        <p>Comprehensive analytics and insights across all modules</p>
                    </div>
                    <div class="report-header-right">
                        <div class="date-range-picker">
                            <span class="date-range-item active" data-range="today">Today</span>
                            <span class="date-range-item" data-range="week">Week</span>
                            <span class="date-range-item" data-range="month">Month</span>
                            <span class="date-range-item" data-range="quarter">Quarter</span>
                            <span class="date-range-item" data-range="year">Year</span>
                            <span class="date-picker-custom">
                                <i class="fas fa-calendar"></i>
                                <span id="dateRangeDisplay">Last 30 days</span>
                                <i class="fas fa-chevron-down"></i>
                            </span>
                        </div>
                        <button class="btn btn-outline" id="exportReport">
                            <i class="fas fa-download"></i>
                            Export
                        </button>
                        <button class="btn btn-outline" id="scheduleReport">
                            <i class="fas fa-clock"></i>
                            Schedule
                        </button>
                        <button class="btn btn-outline" id="printReport">
                            <i class="fas fa-print"></i>
                            Print
                        </button>
                        <button class="btn btn-outline" id="refreshData">
                            <i class="fas fa-sync-alt"></i>
                            Refresh
                        </button>
                    </div>
                </div>
                
                <!-- Summary Cards -->
                <div class="summary-stats" style="margin-bottom: 24px;">
                    <div class="summary-item">
                        <div class="summary-label">System Health</div>
                        <div class="summary-value">98.5%</div>
                        <div class="summary-change change-positive">
                            <i class="fas fa-arrow-up"></i> 1.2%
                        </div>
                    </div>
                    <div class="summary-item">
                        <div class="summary-label">Active Users</div>
                        <div class="summary-value">124</div>
                        <div class="summary-change change-positive">
                            <i class="fas fa-arrow-up"></i> 12
                        </div>
                    </div>
                    <div class="summary-item">
                        <div class="summary-label">Total Transactions</div>
                        <div class="summary-value">1,856</div>
                        <div class="summary-change change-positive">
                            <i class="fas fa-arrow-up"></i> 8.3%
                        </div>
                    </div>
                    <div class="summary-item">
                        <div class="summary-label">Avg Response Time</div>
                        <div class="summary-value">234ms</div>
                        <div class="summary-change change-negative">
                            <i class="fas fa-arrow-down"></i> 12ms
                        </div>
                    </div>
                </div>
                
                <!-- Reports Grid -->
                <div class="report-grid">
                    <!-- Inventory Report -->
                    <div class="report-card">
                        <div class="report-card-header">
                            <h2>
                                <i class="fas fa-boxes"></i>
                                Inventory Report
                            </h2>
                            <div class="report-card-actions">
                                <button class="report-card-btn" onclick="ReportsModule.exportReport('pdf')" title="Download PDF">
                                    <i class="fas fa-file-pdf"></i>
                                </button>
                                <button class="report-card-btn" onclick="ReportsModule.exportReport('excel')" title="Download Excel">
                                    <i class="fas fa-file-excel"></i>
                                </button>
                            </div>
                        </div>
                        <div class="report-card-body" id="inventoryReport">
                            <!-- Dynamic content loaded via JS -->
                        </div>
                        <div class="report-card-footer">
                            <span>Updated: 5 minutes ago</span>
                            <a href="#" class="view-details">
                                View Details <i class="fas fa-arrow-right"></i>
                            </a>
                        </div>
                    </div>
                    
                    <!-- Orders Report -->
                    <div class="report-card">
                        <div class="report-card-header">
                            <h2>
                                <i class="fas fa-shopping-cart"></i>
                                Orders Report
                            </h2>
                            <div class="report-card-actions">
                                <button class="report-card-btn" onclick="ReportsModule.exportReport('pdf')">
                                    <i class="fas fa-file-pdf"></i>
                                </button>
                                <button class="report-card-btn" onclick="ReportsModule.exportReport('excel')">
                                    <i class="fas fa-file-excel"></i>
                                </button>
                            </div>
                        </div>
                        <div class="report-card-body" id="ordersReport">
                            <!-- Dynamic content loaded via JS -->
                        </div>
                        <div class="report-card-footer">
                            <span>Updated: 10 minutes ago</span>
                            <a href="#" class="view-details">
                                View Details <i class="fas fa-arrow-right"></i>
                            </a>
                        </div>
                    </div>
                    
                    <!-- Fleet Report -->
                    <div class="report-card">
                        <div class="report-card-header">
                            <h2>
                                <i class="fas fa-truck"></i>
                                Fleet Report
                            </h2>
                            <div class="report-card-actions">
                                <button class="report-card-btn" onclick="ReportsModule.exportReport('pdf')">
                                    <i class="fas fa-file-pdf"></i>
                                </button>
                                <button class="report-card-btn" onclick="ReportsModule.exportReport('excel')">
                                    <i class="fas fa-file-excel"></i>
                                </button>
                            </div>
                        </div>
                        <div class="report-card-body" id="fleetReport">
                            <!-- Dynamic content loaded via JS -->
                        </div>
                        <div class="report-card-footer">
                            <span>Updated: 15 minutes ago</span>
                            <a href="#" class="view-details">
                                View Details <i class="fas fa-arrow-right"></i>
                            </a>
                        </div>
                    </div>
                </div>
                
                <div class="report-grid-2">
                    <!-- Employee Report -->
                    <div class="report-card">
                        <div class="report-card-header">
                            <h2>
                                <i class="fas fa-users"></i>
                                Employee Report
                            </h2>
                            <div class="report-card-actions">
                                <button class="report-card-btn" onclick="ReportsModule.exportReport('pdf')">
                                    <i class="fas fa-file-pdf"></i>
                                </button>
                                <button class="report-card-btn" onclick="ReportsModule.exportReport('excel')">
                                    <i class="fas fa-file-excel"></i>
                                </button>
                            </div>
                        </div>
                        <div class="report-card-body" id="employeeReport">
                            <!-- Dynamic content loaded via JS -->
                        </div>
                        <div class="report-card-footer">
                            <span>Updated: 20 minutes ago</span>
                            <a href="#" class="view-details">
                                View Details <i class="fas fa-arrow-right"></i>
                            </a>
                        </div>
                    </div>
                    
                    <!-- Financial Report -->
                    <div class="report-card">
                        <div class="report-card-header">
                            <h2>
                                <i class="fas fa-chart-line"></i>
                                Financial Report
                            </h2>
                            <div class="report-card-actions">
                                <button class="report-card-btn" onclick="ReportsModule.exportReport('pdf')">
                                    <i class="fas fa-file-pdf"></i>
                                </button>
                                <button class="report-card-btn" onclick="ReportsModule.exportReport('excel')">
                                    <i class="fas fa-file-excel"></i>
                                </button>
                            </div>
                        </div>
                        <div class="report-card-body" id="financialReport">
                            <!-- Dynamic content loaded via JS -->
                        </div>
                        <div class="report-card-footer">
                            <span>Updated: 30 minutes ago</span>
                            <a href="#" class="view-details">
                                View Details <i class="fas fa-arrow-right"></i>
                            </a>
                        </div>
                    </div>
                </div>
                
                <div class="report-grid">
                    <!-- Performance Report -->
                    <div class="report-card">
                        <div class="report-card-header">
                            <h2>
                                <i class="fas fa-chart-bar"></i>
                                Performance Report
                            </h2>
                            <div class="report-card-actions">
                                <button class="report-card-btn" onclick="ReportsModule.exportReport('pdf')">
                                    <i class="fas fa-file-pdf"></i>
                                </button>
                                <button class="report-card-btn" onclick="ReportsModule.exportReport('excel')">
                                    <i class="fas fa-file-excel"></i>
                                </button>
                            </div>
                        </div>
                        <div class="report-card-body" id="performanceReport">
                            <!-- Dynamic content loaded via JS -->
                        </div>
                        <div class="report-card-footer">
                            <span>Updated: 45 minutes ago</span>
                            <a href="#" class="view-details">
                                View Details <i class="fas fa-arrow-right"></i>
                            </a>
                        </div>
                    </div>
                    
                    <!-- Compliance Report -->
                    <div class="report-card">
                        <div class="report-card-header">
                            <h2>
                                <i class="fas fa-shield-alt"></i>
                                Compliance Report
                            </h2>
                            <div class="report-card-actions">
                                <button class="report-card-btn" onclick="ReportsModule.exportReport('pdf')">
                                    <i class="fas fa-file-pdf"></i>
                                </button>
                                <button class="report-card-btn" onclick="ReportsModule.exportReport('excel')">
                                    <i class="fas fa-file-excel"></i>
                                </button>
                            </div>
                        </div>
                        <div class="report-card-body" id="complianceReport">
                            <!-- Dynamic content loaded via JS -->
                        </div>
                        <div class="report-card-footer">
                            <span>Updated: 1 hour ago</span>
                            <a href="#" class="view-details">
                                View Details <i class="fas fa-arrow-right"></i>
                            </a>
                        </div>
                    </div>
                    
                    <!-- Audit Report -->
                    <div class="report-card">
                        <div class="report-card-header">
                            <h2>
                                <i class="fas fa-history"></i>
                                Audit Report
                            </h2>
                            <div class="report-card-actions">
                                <button class="report-card-btn" onclick="ReportsModule.exportReport('pdf')">
                                    <i class="fas fa-file-pdf"></i>
                                </button>
                                <button class="report-card-btn" onclick="ReportsModule.exportReport('excel')">
                                    <i class="fas fa-file-excel"></i>
                                </button>
                            </div>
                        </div>
                        <div class="report-card-body" id="auditReport">
                            <!-- Dynamic content loaded via JS -->
                        </div>
                        <div class="report-card-footer">
                            <span>Updated: 2 hours ago</span>
                            <a href="#" class="view-details">
                                View Details <i class="fas fa-arrow-right"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
    
    <!-- Export Modal -->
    <div id="exportModal" class="modal modal-hidden">
        <div class="modal-content export-modal">
            <div class="modal-header">
                <h3 class="modal-title">
                    <i class="fas fa-download"></i>
                    Export Reports
                </h3>
                <button class="modal-close" onclick="ReportsModule.closeModal('exportModal')">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <div class="export-options">
                <div class="export-option" onclick="ReportsModule.exportReport('pdf')">
                    <div class="export-option-icon pdf">
                        <i class="fas fa-file-pdf"></i>
                    </div>
                    <div class="export-option-info">
                        <h3>PDF Document</h3>
                        <p>Export as PDF with charts and tables</p>
                    </div>
                    <span class="export-format-badge">PDF</span>
                </div>
                
                <div class="export-option" onclick="ReportsModule.exportReport('excel')">
                    <div class="export-option-icon excel">
                        <i class="fas fa-file-excel"></i>
                    </div>
                    <div class="export-option-info">
                        <h3>Excel Spreadsheet</h3>
                        <p>Export as Excel with raw data</p>
                    </div>
                    <span class="export-format-badge">XLSX</span>
                </div>
                
                <div class="export-option" onclick="ReportsModule.exportReport('csv')">
                    <div class="export-option-icon csv">
                        <i class="fas fa-file-csv"></i>
                    </div>
                    <div class="export-option-info">
                        <h3>CSV File</h3>
                        <p>Export as CSV for data processing</p>
                    </div>
                    <span class="export-format-badge">CSV</span>
                </div>
            </div>
            
            <div class="modal-footer">
                <button class="btn btn-outline" onclick="ReportsModule.closeModal('exportModal')">
                    Cancel
                </button>
            </div>
        </div>
    </div>
    
    <!-- Schedule Report Modal -->
    <div id="scheduleModal" class="modal modal-hidden">
        <div class="modal-content schedule-modal">
            <div class="modal-header">
                <h3 class="modal-title">
                    <i class="fas fa-clock"></i>
                    Schedule Report
                </h3>
                <button class="modal-close" onclick="ReportsModule.closeModal('scheduleModal')">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <div class="schedule-options">
                <div>
                    <label class="form-label">Report Type</label>
                    <select class="form-select">
                        <option>All Reports</option>
                        <option>Inventory Report</option>
                        <option>Orders Report</option>
                        <option>Fleet Report</option>
                        <option>Employee Report</option>
                        <option>Financial Report</option>
                    </select>
                </div>
                
                <div>
                    <label class="form-label">Frequency</label>
                    <div class="schedule-frequency">
                        <button class="frequency-btn active">Daily</button>
                        <button class="frequency-btn">Weekly</button>
                        <button class="frequency-btn">Monthly</button>
                        <button class="frequency-btn">Quarterly</button>
                    </div>
                </div>
                
                <div>
                    <label class="form-label">Format</label>
                    <select class="form-select">
                        <option>PDF</option>
                        <option>Excel</option>
                        <option>CSV</option>
                    </select>
                </div>
                
                <div>
                    <label class="form-label">Email Recipients</label>
                    <div class="schedule-recipients">
                        <div class="recipient-tag">
                            admin@company.com
                            <i class="fas fa-times"></i>
                        </div>
                        <div class="recipient-tag">
                            reports@company.com
                            <i class="fas fa-times"></i>
                        </div>
                        <input type="email" class="form-input" placeholder="Add email...">
                    </div>
                </div>
            </div>
            
            <div class="modal-footer">
                <button class="btn btn-outline" onclick="ReportsModule.closeModal('scheduleModal')">
                    Cancel
                </button>
                <button class="btn btn-primary" onclick="ReportsModule.scheduleReport()">
                    <i class="fas fa-clock"></i>
                    Schedule Report
                </button>
            </div>
        </div>
    </div>
    
    <script src="../assets/js/pages/reports.js"></script>
</body>
</html>