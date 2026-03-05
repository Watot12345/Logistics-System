// assets/js/reports.js

// Reports Module
const ReportsModule = {
    // State
    currentDateRange: 'month',
    customDateRange: {
        start: null,
        end: null
    },
    reports: {},

    // Initialize
    init: function() {
        console.log('Reports module initialized');
        this.loadAllReports();
        this.setupEventListeners();
        this.initCharts();
        this.updateDateRange();
    },

    // Setup Event Listeners
    setupEventListeners: function() {
        // Date range buttons
        document.querySelectorAll('.date-range-item').forEach(item => {
            item.addEventListener('click', (e) => {
                document.querySelectorAll('.date-range-item').forEach(btn => {
                    btn.classList.remove('active');
                });
                e.target.classList.add('active');
                this.currentDateRange = e.target.dataset.range;
                this.updateDateRange();
                this.loadAllReports();
            });
        });

        // Export buttons
        document.getElementById('exportReport')?.addEventListener('click', () => {
            this.openExportModal();
        });

        document.getElementById('scheduleReport')?.addEventListener('click', () => {
            this.openScheduleModal();
        });

        document.getElementById('printReport')?.addEventListener('click', () => {
            window.print();
        });

        // Refresh button
        document.getElementById('refreshData')?.addEventListener('click', () => {
            this.loadAllReports();
            this.showNotification('Reports refreshed', 'success');
        });
    },

    // Load All Reports
    loadAllReports: function() {
        this.loadInventoryReport();
        this.loadOrdersReport();
        this.loadFleetReport();
        this.loadEmployeeReport();
        this.loadFinancialReport();
        this.loadPerformanceReport();
        this.loadComplianceReport();
        this.loadAuditReport();
    },

    // Load Inventory Report
    loadInventoryReport: function() {
        const reportBody = document.getElementById('inventoryReport');
        if (!reportBody) return;A

        // Mock data
        const data = {
            totalItems: 1234,
            categories: 45,
            lowStock: 23,
            outOfStock: 8,
            totalValue: 45200,
            topItems: [
                { name: 'HP Laptop ProBook', sku: 'LPT-001', stock: 45, value: 40499.55 },
                { name: 'Office Desk Chair', sku: 'FUR-001', stock: 5, value: 749.95 },
                { name: 'Cotton T-Shirt', sku: 'CLTH-001', stock: 0, value: 0 },
                { name: 'Wireless Mouse', sku: 'ACC-001', stock: 120, value: 2998.80 },
                { name: 'Monitor 24"', sku: 'MON-001', stock: 15, value: 4499.85 }
            ]
        };

        let html = `
            <div class="summary-stats">
                <div class="summary-item">
                    <div class="summary-label">Total Items</div>
                    <div class="summary-value">${data.totalItems}</div>
                    <div class="summary-change change-positive">
                        <i class="fas fa-arrow-up"></i> 12%
                    </div>
                </div>
                <div class="summary-item">
                    <div class="summary-label">Categories</div>
                    <div class="summary-value">${data.categories}</div>
                    <div class="summary-change change-positive">
                        <i class="fas fa-arrow-up"></i> 3
                    </div>
                </div>
                <div class="summary-item">
                    <div class="summary-label">Low Stock</div>
                    <div class="summary-value">${data.lowStock}</div>
                    <div class="summary-change change-negative">
                        <i class="fas fa-exclamation"></i> Alert
                    </div>
                </div>
                <div class="summary-item">
                    <div class="summary-label">Total Value</div>
                    <div class="summary-value">$${(data.totalValue/1000).toFixed(1)}K</div>
                    <div class="summary-change change-positive">
                        <i class="fas fa-arrow-up"></i> 5.2%
                    </div>
                </div>
            </div>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Item</th>
                        <th>SKU</th>
                        <th>Stock</th>
                        <th>Value</th>
                    </tr>
                </thead>
                <tbody>
        `;

        data.topItems.forEach(item => {
            html += `
                <tr>
                    <td>${item.name}</td>
                    <td>${item.sku}</td>
                    <td>
                        <span class="badge ${item.stock === 0 ? 'badge-danger' : item.stock < 10 ? 'badge-warning' : 'badge-success'}">
                            ${item.stock} units
                        </span>
                    </td>
                    <td class="amount">$${item.value.toFixed(2)}</td>
                </tr>
            `;
        });

        html += `
                </tbody>
            </table>
        `;

        reportBody.innerHTML = html;
    },

    // Load Orders Report
    loadOrdersReport: function() {
        const reportBody = document.getElementById('ordersReport');
        if (!reportBody) return;

        // Mock data
        const data = {
            total: 156,
            approved: 98,
            pending: 32,
            rejected: 26,
            totalValue: 1245000,
            recentOrders: [
                { id: 'PO-001', supplier: 'Tech Supplies Inc.', amount: 12500, status: 'approved', date: '2024-02-20' },
                { id: 'PO-002', supplier: 'Industrial Materials', amount: 34200, status: 'pending', date: '2024-02-19' },
                { id: 'PO-003', supplier: 'Global Tech', amount: 28750, status: 'approved', date: '2024-02-18' },
                { id: 'PO-004', supplier: 'Furniture Direct', amount: 8900, status: 'rejected', date: '2024-02-17' },
                { id: 'PO-005', supplier: 'Office Supplies Co.', amount: 4300, status: 'approved', date: '2024-02-16' }
            ]
        };

        let html = `
            <div class="kpi-grid">
                <div class="kpi-item">
                    <div class="kpi-title">
                        <i class="fas fa-shopping-cart"></i> Total Orders
                    </div>
                    <div class="kpi-value">${data.total}</div>
                    <div class="kpi-trend positive">
                        <i class="fas fa-arrow-up"></i> 12% vs last month
                    </div>
                </div>
                <div class="kpi-item">
                    <div class="kpi-title">
                        <i class="fas fa-check-circle"></i> Approved
                    </div>
                    <div class="kpi-value">${data.approved}</div>
                    <div class="kpi-trend positive">
                        ${Math.round(data.approved/data.total*100)}% of total
                    </div>
                </div>
                <div class="kpi-item">
                    <div class="kpi-title">
                        <i class="fas fa-clock"></i> Pending
                    </div>
                    <div class="kpi-value">${data.pending}</div>
                    <div class="kpi-trend negative">
                        Needs attention
                    </div>
                </div>
                <div class="kpi-item">
                    <div class="kpi-title">
                        <i class="fas fa-dollar-sign"></i> Total Value
                    </div>
                    <div class="kpi-value">$${(data.totalValue/1000000).toFixed(1)}M</div>
                    <div class="kpi-trend positive">
                        +15.5% vs last quarter
                    </div>
                </div>
            </div>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>PO Number</th>
                        <th>Supplier</th>
                        <th>Amount</th>
                        <th>Status</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
        `;

        data.recentOrders.forEach(order => {
            html += `
                <tr>
                    <td>${order.id}</td>
                    <td>${order.supplier}</td>
                    <td class="amount">$${order.amount.toLocaleString()}</td>
                    <td>
                        <span class="badge badge-${order.status === 'approved' ? 'success' : order.status === 'pending' ? 'warning' : 'danger'}">
                            ${order.status}
                        </span>
                    </td>
                    <td>${order.date}</td>
                </tr>
            `;
        });

        html += `
                </tbody>
            </table>
        `;

        reportBody.innerHTML = html;
    },

    // Load Fleet Report
    loadFleetReport: function() {
        const reportBody = document.getElementById('fleetReport');
        if (!reportBody) return;

        // Mock data
        const data = {
            totalVehicles: 42,
            available: 28,
            inUse: 10,
            maintenance: 4,
            efficiency: 94,
            fuelEfficiency: 8.2,
            utilization: 78,
            onTime: 96,
            vehicles: [
                { name: 'Volvo FH16', plate: 'ABC-123', status: 'available', efficiency: 96, trips: 45 },
                { name: 'Scania R500', plate: 'XYZ-789', status: 'in-use', efficiency: 92, trips: 38 },
                { name: 'Mercedes Sprinter', plate: 'DEF-456', status: 'maintenance', efficiency: 85, trips: 42 },
                { name: 'MAN TGX', plate: 'GHI-012', status: 'available', efficiency: 94, trips: 35 },
                { name: 'Ford Transit', plate: 'JKL-345', status: 'in-use', efficiency: 89, trips: 28 }
            ]
        };

        let html = `
            <div class="summary-stats">
                <div class="summary-item">
                    <div class="summary-label">Total Vehicles</div>
                    <div class="summary-value">${data.totalVehicles}</div>
                    <div class="summary-change change-positive">+2 this month</div>
                </div>
                <div class="summary-item">
                    <div class="summary-label">Available</div>
                    <div class="summary-value">${data.available}</div>
                    <div class="summary-change change-positive">${Math.round(data.available/data.totalVehicles*100)}%</div>
                </div>
                <div class="summary-item">
                    <div class="summary-label">In Maintenance</div>
                    <div class="summary-value">${data.maintenance}</div>
                    <div class="summary-change change-negative">-2 from yesterday</div>
                </div>
                <div class="summary-item">
                    <div class="summary-label">Efficiency</div>
                    <div class="summary-value">${data.efficiency}%</div>
                    <div class="summary-change change-positive">+2% vs target</div>
                </div>
            </div>
            
            <div class="kpi-grid" style="margin-top: 16px;">
                <div class="kpi-item">
                    <div class="kpi-title">Fuel Efficiency</div>
                    <div class="kpi-value">${data.fuelEfficiency} km/l</div>
                    <div class="progress-bar">
                        <div class="progress-fill emerald" style="width: 82%"></div>
                    </div>
                </div>
                <div class="kpi-item">
                    <div class="kpi-title">Utilization</div>
                    <div class="kpi-value">${data.utilization}%</div>
                    <div class="progress-bar">
                        <div class="progress-fill blue" style="width: 78%"></div>
                    </div>
                </div>
                <div class="kpi-item">
                    <div class="kpi-title">On-time Delivery</div>
                    <div class="kpi-value">${data.onTime}%</div>
                    <div class="progress-bar">
                        <div class="progress-fill emerald" style="width: 96%"></div>
                    </div>
                </div>
            </div>
            
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Vehicle</th>
                        <th>Plate</th>
                        <th>Status</th>
                        <th>Efficiency</th>
                        <th>Trips</th>
                    </tr>
                </thead>
                <tbody>
        `;

        data.vehicles.forEach(vehicle => {
            html += `
                <tr>
                    <td>${vehicle.name}</td>
                    <td>${vehicle.plate}</td>
                    <td>
                        <span class="badge badge-${vehicle.status === 'available' ? 'success' : vehicle.status === 'in-use' ? 'warning' : 'danger'}">
                            ${vehicle.status}
                        </span>
                    </td>
                    <td>
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <span>${vehicle.efficiency}%</span>
                            <div class="progress-bar" style="width: 60px;">
                                <div class="progress-fill ${vehicle.efficiency >= 95 ? 'emerald' : vehicle.efficiency >= 85 ? 'blue' : 'amber'}" 
                                     style="width: ${vehicle.efficiency}%"></div>
                            </div>
                        </div>
                    </td>
                    <td>${vehicle.trips}</td>
                </tr>
            `;
        });

        html += `
                </tbody>
            </table>
        `;

        reportBody.innerHTML = html;
    },

    // Load Employee Report
    loadEmployeeReport: function() {
        const reportBody = document.getElementById('employeeReport');
        if (!reportBody) return;

        // Mock data
        const data = {
            total: 45,
            active: 38,
            onLeave: 4,
            inactive: 3,
            byRole: {
                admin: 3,
                dispatcher: 8,
                driver: 22,
                employee: 12
            },
            recentActivity: [
                { name: 'John Smith', action: 'Completed trip', time: '2 hours ago' },
                { name: 'Sarah Johnson', action: 'Started shift', time: '4 hours ago' },
                { name: 'Mike Wilson', action: 'Vehicle check', time: '5 hours ago' },
                { name: 'Emily Davis', action: 'Ended shift', time: '6 hours ago' }
            ]
        };

        let html = `
            <div class="summary-stats">
                <div class="summary-item">
                    <div class="summary-label">Total Employees</div>
                    <div class="summary-value">${data.total}</div>
                    <div class="summary-change change-positive">+3 this month</div>
                </div>
                <div class="summary-item">
                    <div class="summary-label">Active</div>
                    <div class="summary-value">${data.active}</div>
                    <div class="summary-change change-positive">${Math.round(data.active/data.total*100)}%</div>
                </div>
                <div class="summary-item">
                    <div class="summary-label">On Leave</div>
                    <div class="summary-value">${data.onLeave}</div>
                    <div class="summary-change change-neutral">-</div>
                </div>
                <div class="summary-item">
                    <div class="summary-label">Drivers</div>
                    <div class="summary-value">${data.byRole.driver}</div>
                    <div class="summary-change change-positive">49% of workforce</div>
                </div>
            </div>
            
            <div style="display: flex; gap: 20px; margin: 20px 0;">
                <div style="flex: 1;">
                    <div style="font-size: 13px; color: #64748b; margin-bottom: 8px;">Role Distribution</div>
                    <div style="display: flex; flex-direction: column; gap: 8px;">
                        <div>
                            <div style="display: flex; justify-content: space-between; font-size: 13px;">
                                <span>Admins</span>
                                <span>${data.byRole.admin}</span>
                            </div>
                            <div class="progress-bar">
                                <div class="progress-fill purple" style="width: ${data.byRole.admin/data.total*100}%"></div>
                            </div>
                        </div>
                        <div>
                            <div style="display: flex; justify-content: space-between; font-size: 13px;">
                                <span>Dispatchers</span>
                                <span>${data.byRole.dispatcher}</span>
                            </div>
                            <div class="progress-bar">
                                <div class="progress-fill amber" style="width: ${data.byRole.dispatcher/data.total*100}%"></div>
                            </div>
                        </div>
                        <div>
                            <div style="display: flex; justify-content: space-between; font-size: 13px;">
                                <span>Drivers</span>
                                <span>${data.byRole.driver}</span>
                            </div>
                            <div class="progress-bar">
                                <div class="progress-fill emerald" style="width: ${data.byRole.driver/data.total*100}%"></div>
                            </div>
                        </div>
                        <div>
                            <div style="display: flex; justify-content: space-between; font-size: 13px;">
                                <span>Employees</span>
                                <span>${data.byRole.employee}</span>
                            </div>
                            <div class="progress-bar">
                                <div class="progress-fill blue" style="width: ${data.byRole.employee/data.total*100}%"></div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div style="flex: 1;">
                    <div style="font-size: 13px; color: #64748b; margin-bottom: 8px;">Recent Activity</div>
                    <div style="display: flex; flex-direction: column; gap: 8px;">
        `;

        data.recentActivity.forEach(activity => {
            html += `
                <div style="display: flex; align-items: center; gap: 8px; padding: 8px; background: #f8fafc; border-radius: 8px;">
                    <i class="fas fa-circle" style="font-size: 8px; color: #10b981;"></i>
                    <div style="flex: 1;">
                        <span style="font-weight: 500;">${activity.name}</span>
                        <span style="color: #64748b; margin-left: 4px;">${activity.action}</span>
                    </div>
                    <span style="font-size: 11px; color: #94a3b8;">${activity.time}</span>
                </div>
            `;
        });

        html += `
                    </div>
                </div>
            </div>
        `;

        reportBody.innerHTML = html;
    },

    // Load Financial Report
    loadFinancialReport: function() {
        const reportBody = document.getElementById('financialReport');
        if (!reportBody) return;

        // Mock data
        const data = {
            revenue: 2450000,
            expenses: 1875000,
            profit: 575000,
            margin: 23.5,
            categories: [
                { name: 'Transport', amount: 890000, percentage: 36 },
                { name: 'Inventory', amount: 650000, percentage: 27 },
                { name: 'Labor', amount: 420000, percentage: 17 },
                { name: 'Maintenance', amount: 315000, percentage: 13 },
                { name: 'Other', amount: 180000, percentage: 7 }
            ]
        };

        let html = `
            <div class="kpi-grid">
                <div class="kpi-item">
                    <div class="kpi-title">Revenue</div>
                    <div class="kpi-value">$${(data.revenue/1000000).toFixed(2)}M</div>
                    <div class="kpi-trend positive">
                        <i class="fas fa-arrow-up"></i> 12.5%
                    </div>
                </div>
                <div class="kpi-item">
                    <div class="kpi-title">Expenses</div>
                    <div class="kpi-value">$${(data.expenses/1000000).toFixed(2)}M</div>
                    <div class="kpi-trend negative">
                        <i class="fas fa-arrow-up"></i> 8.3%
                    </div>
                </div>
                <div class="kpi-item">
                    <div class="kpi-title">Profit</div>
                    <div class="kpi-value">$${(data.profit/1000000).toFixed(2)}M</div>
                    <div class="kpi-trend positive">
                        +15.2%
                    </div>
                </div>
                <div class="kpi-item">
                    <div class="kpi-title">Margin</div>
                    <div class="kpi-value">${data.margin}%</div>
                    <div class="kpi-trend positive">
                        +3.2%
                    </div>
                </div>
            </div>
            
            <div style="margin-top: 20px;">
                <div style="font-size: 14px; font-weight: 600; margin-bottom: 12px;">Expense Breakdown</div>
                <div style="display: flex; flex-direction: column; gap: 12px;">
        `;

        data.categories.forEach(cat => {
            html += `
                <div>
                    <div style="display: flex; justify-content: space-between; font-size: 13px; margin-bottom: 4px;">
                        <span>${cat.name}</span>
                        <span>$${(cat.amount/1000).toFixed(0)}K (${cat.percentage}%)</span>
                    </div>
                    <div class="progress-bar">
                        <div class="progress-fill blue" style="width: ${cat.percentage}%"></div>
                    </div>
                </div>
            `;
        });

        html += `
                </div>
            </div>
            
            <div style="margin-top: 20px; padding: 16px; background: #f8fafc; border-radius: 12px;">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <div>
                        <div style="font-size: 13px; color: #64748b;">Profit Margin Trend</div>
                        <div style="font-size: 18px; font-weight: 600; color: #1e293b;">${data.margin}%</div>
                    </div>
                    <div>
                        <span class="trend-indicator trend-up">
                            <i class="fas fa-arrow-up"></i> +3.2% vs last month
                        </span>
                    </div>
                </div>
                <div class="mini-chart" style="margin-top: 12px;">
                    <div class="chart-bar" style="height: 40px;"></div>
                    <div class="chart-bar" style="height: 55px;"></div>
                    <div class="chart-bar" style="height: 35px;"></div>
                    <div class="chart-bar" style="height: 65px;"></div>
                    <div class="chart-bar" style="height: 45px;"></div>
                    <div class="chart-bar" style="height: 70px;"></div>
                    <div class="chart-bar" style="height: 50px;"></div>
                </div>
            </div>
        `;

        reportBody.innerHTML = html;
    },

    // Load Performance Report
    loadPerformanceReport: function() {
        const reportBody = document.getElementById('performanceReport');
        if (!reportBody) return;

        // Mock data
        const data = {
            overall: 94,
            onTime: 96,
            quality: 98,
            efficiency: 92,
            kpis: [
                { name: 'On-time Delivery', value: 96, target: 98, status: 'good' },
                { name: 'Order Accuracy', value: 99, target: 99.5, status: 'good' },
                { name: 'Inventory Accuracy', value: 97, target: 98, status: 'warning' },
                { name: 'Vehicle Utilization', value: 78, target: 85, status: 'warning' },
                { name: 'Driver Efficiency', value: 94, target: 95, status: 'good' },
                { name: 'Customer Satisfaction', value: 4.8, target: 4.9, status: 'good' }
            ]
        };

        let html = `
            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 20px;">
                <div>
                    <div style="font-size: 13px; color: #64748b;">Overall Performance</div>
                    <div style="font-size: 36px; font-weight: 700; color: #1e293b;">${data.overall}%</div>
                </div>
                <div class="trend-indicator trend-up">
                    <i class="fas fa-arrow-up"></i> +2% vs target
                </div>
            </div>
            
            <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; margin-bottom: 20px;">
                <div style="text-align: center;">
                    <div style="font-size: 24px; font-weight: 700; color: #10b981;">${data.onTime}%</div>
                    <div style="font-size: 12px; color: #64748b;">On-time</div>
                </div>
                <div style="text-align: center;">
                    <div style="font-size: 24px; font-weight: 700; color: #2563eb;">${data.quality}%</div>
                    <div style="font-size: 12px; color: #64748b;">Quality</div>
                </div>
                <div style="text-align: center;">
                    <div style="font-size: 24px; font-weight: 700; color: #f59e0b;">${data.efficiency}%</div>
                    <div style="font-size: 12px; color: #64748b;">Efficiency</div>
                </div>
            </div>
            
            <table class="data-table">
                <thead>
                    <tr>
                        <th>KPI</th>
                        <th>Current</th>
                        <th>Target</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
        `;

        data.kpis.forEach(kpi => {
            const percentOfTarget = (kpi.value / kpi.target) * 100;
            html += `
                <tr>
                    <td>${kpi.name}</td>
                    <td>${kpi.value}${kpi.name.includes('Satisfaction') ? '/5' : '%'}</td>
                    <td>${kpi.target}${kpi.name.includes('Satisfaction') ? '/5' : '%'}</td>
                    <td>
                        <span class="badge ${percentOfTarget >= 98 ? 'badge-success' : percentOfTarget >= 90 ? 'badge-warning' : 'badge-danger'}">
                            ${percentOfTarget >= 98 ? 'On Track' : percentOfTarget >= 90 ? 'Needs Improvement' : 'Critical'}
                        </span>
                    </td>
                </tr>
            `;
        });

        html += `
                </tbody>
            </table>
        `;

        reportBody.innerHTML = html;
    },

    // Load Compliance Report
    loadComplianceReport: function() {
        const reportBody = document.getElementById('complianceReport');
        if (!reportBody) return;

        // Mock data
        const data = {
            overall: 98.5,
            certifications: [
                { name: 'ISO 9001', status: 'valid', expiry: '2024-12-31' },
                { name: 'Safety Standard', status: 'valid', expiry: '2024-06-30' },
                { name: 'Environmental', status: 'pending', expiry: '2024-03-15' },
                { name: 'Quality Assurance', status: 'valid', expiry: '2024-09-30' }
            ],
            audits: [
                { date: '2024-02-15', type: 'Safety', result: 'Pass', score: 98 },
                { date: '2024-02-01', type: 'Quality', result: 'Pass', score: 97 },
                { date: '2024-01-20', type: 'Environmental', result: 'Pass', score: 95 }
            ]
        };

        let html = `
            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 20px;">
                <div>
                    <div style="font-size: 13px; color: #64748b;">Compliance Score</div>
                    <div style="font-size: 36px; font-weight: 700; color: #1e293b;">${data.overall}%</div>
                </div>
                <div class="trend-indicator trend-up">
                    <i class="fas fa-check-circle"></i> Compliant
                </div>
            </div>
            
            <div style="margin-bottom: 20px;">
                <div style="font-size: 14px; font-weight: 600; margin-bottom: 12px;">Certifications</div>
                <div style="display: flex; flex-direction: column; gap: 8px;">
        `;

        data.certifications.forEach(cert => {
            const daysToExpiry = Math.ceil((new Date(cert.expiry) - new Date()) / (1000 * 60 * 60 * 24));
            html += `
                <div style="display: flex; align-items: center; justify-content: space-between; padding: 8px; background: #f8fafc; border-radius: 8px;">
                    <div>
                        <span style="font-weight: 500;">${cert.name}</span>
                        <span style="font-size: 12px; color: #64748b; margin-left: 8px;">Expires: ${cert.expiry}</span>
                    </div>
                    <span class="badge ${daysToExpiry > 90 ? 'badge-success' : daysToExpiry > 30 ? 'badge-warning' : 'badge-danger'}">
                        ${daysToExpiry} days left
                    </span>
                </div>
            `;
        });

        html += `
                </div>
            </div>
            
            <div>
                <div style="font-size: 14px; font-weight: 600; margin-bottom: 12px;">Recent Audits</div>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Type</th>
                            <th>Result</th>
                            <th>Score</th>
                        </tr>
                    </thead>
                    <tbody>
        `;

        data.audits.forEach(audit => {
            html += `
                <tr>
                    <td>${audit.date}</td>
                    <td>${audit.type}</td>
                    <td>
                        <span class="badge badge-success">${audit.result}</span>
                    </td>
                    <td>${audit.score}%</td>
                </tr>
            `;
        });

        html += `
                    </tbody>
                </table>
            </div>
        `;

        reportBody.innerHTML = html;
    },

    // Load Audit Report
    loadAuditReport: function() {
        const reportBody = document.getElementById('auditReport');
        if (!reportBody) return;

        // Mock data
        const data = {
            totalLogs: 1234,
            userActions: 892,
            systemEvents: 342,
            recentLogs: [
                { user: 'John Smith', action: 'Updated inventory', timestamp: '2024-02-20 09:30', ip: '192.168.1.100' },
                { user: 'Sarah Johnson', action: 'Created purchase order', timestamp: '2024-02-20 08:45', ip: '192.168.1.101' },
                { user: 'System', action: 'Database backup', timestamp: '2024-02-20 02:00', ip: 'System' },
                { user: 'Mike Wilson', action: 'Assigned vehicle', timestamp: '2024-02-19 16:20', ip: '192.168.1.102' },
                { user: 'Emily Davis', action: 'Generated report', timestamp: '2024-02-19 14:15', ip: '192.168.1.103' }
            ]
        };

        let html = `
            <div class="summary-stats">
                <div class="summary-item">
                    <div class="summary-label">Total Logs</div>
                    <div class="summary-value">${data.totalLogs}</div>
                    <div class="summary-change">Last 30 days</div>
                </div>
                <div class="summary-item">
                    <div class="summary-label">User Actions</div>
                    <div class="summary-value">${data.userActions}</div>
                    <div class="summary-change">72% of total</div>
                </div>
                <div class="summary-item">
                    <div class="summary-label">System Events</div>
                    <div class="summary-value">${data.systemEvents}</div>
                    <div class="summary-change">28% of total</div>
                </div>
                <div class="summary-item">
                    <div class="summary-label">Unique Users</div>
                    <div class="summary-value">24</div>
                    <div class="summary-change">Active today</div>
                </div>
            </div>
            
            <table class="data-table">
                <thead>
                    <tr>
                        <th>User</th>
                        <th>Action</th>
                        <th>Timestamp</th>
                        <th>IP Address</th>
                    </tr>
                </thead>
                <tbody>
        `;

        data.recentLogs.forEach(log => {
            html += `
                <tr>
                    <td>${log.user}</td>
                    <td>${log.action}</td>
                    <td>${log.timestamp}</td>
                    <td style="font-family: monospace;">${log.ip}</td>
                </tr>
            `;
        });

        html += `
                </tbody>
            </table>
        `;

        reportBody.innerHTML = html;
    },

    // Initialize Charts
    initCharts: function() {
        // In a real app, you would initialize chart libraries like Chart.js or D3
        console.log('Charts initialized');
    },

    // Update Date Range
    updateDateRange: function() {
        const rangeDisplay = document.getElementById('dateRangeDisplay');
        if (!rangeDisplay) return;

        const today = new Date();
        let start, end;

        switch(this.currentDateRange) {
            case 'today':
                start = today;
                end = today;
                rangeDisplay.textContent = 'Today';
                break;
            case 'week':
                start = new Date(today.setDate(today.getDate() - 7));
                end = new Date();
                rangeDisplay.textContent = 'Last 7 days';
                break;
            case 'month':
                start = new Date(today.setMonth(today.getMonth() - 1));
                end = new Date();
                rangeDisplay.textContent = 'Last 30 days';
                break;
            case 'quarter':
                start = new Date(today.setMonth(today.getMonth() - 3));
                end = new Date();
                rangeDisplay.textContent = 'Last 90 days';
                break;
            case 'year':
                start = new Date(today.setFullYear(today.getFullYear() - 1));
                end = new Date();
                rangeDisplay.textContent = 'Last 12 months';
                break;
        }
    },

    // Open Export Modal
    openExportModal: function() {
        document.getElementById('exportModal').classList.remove('modal-hidden');
    },

    // Open Schedule Modal
    openScheduleModal: function() {
        document.getElementById('scheduleModal').classList.remove('modal-hidden');
    },

    // Close Modal
    closeModal: function(modalId) {
        document.getElementById(modalId).classList.add('modal-hidden');
    },

    // Export Report
    exportReport: function(format) {
        console.log(`Exporting report as ${format}`);
        this.closeModal('exportModal');
        this.showNotification(`Report exported as ${format.toUpperCase()}`, 'success');
    },

    // Schedule Report
    scheduleReport: function() {
        const frequency = document.querySelector('.frequency-btn.active')?.textContent || 'Daily';
        console.log(`Scheduling report: ${frequency}`);
        this.closeModal('scheduleModal');
        this.showNotification(`Report scheduled (${frequency})`, 'success');
    },

    // Show Notification
    showNotification: function(message, type = 'info') {
        const notification = document.createElement('div');
        notification.className = `toast ${type}`;
        notification.innerHTML = `
            <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-circle' : 'info-circle'}"></i>
            <span>${message}</span>
        `;

        document.body.appendChild(notification);

        setTimeout(() => {
            notification.remove();
        }, 3000);
    }
};

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    ReportsModule.init();
});