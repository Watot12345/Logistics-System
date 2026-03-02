// assets/js/fleet.js

// Initialize Fleet Dashboard
document.addEventListener('DOMContentLoaded', function() {
    console.log('Fleet dashboard loaded');
    loadFleetData();
    setupEventListeners();
    initTabs();
    startRealTimeUpdates();
});

// Load All Fleet Data
function loadFleetData() {
    loadVehicleAvailability();
    loadTransportEfficiency();
    loadDelayAnalysis();
    loadDriverPerformance();
    loadTripHistory();
    loadDriverActivity();
    loadVehicleReservations();
    loadDispatchSchedule();
    loadVehicleAssignments();
    loadMaintenanceReport();
    loadFleetCondition();
}

// Setup Event Listeners
function setupEventListeners() {
    // Tab switching
    document.querySelectorAll('.tab').forEach(tab => {
        tab.addEventListener('click', function() {
            const tabId = this.dataset.tab;
            switchTab(tabId);
        });
    });
    
    // Refresh buttons
    document.querySelectorAll('.card-action-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const card = this.closest('.card');
            const cardTitle = card.querySelector('h2').textContent;
            refreshCard(cardTitle);
        });
    });
    
    // Filter selects
    document.querySelectorAll('.filter-select').forEach(select => {
        select.addEventListener('change', function() {
            filterData(this.id, this.value);
        });
    });
    
    // Search
    const searchInput = document.getElementById('searchFleet');
    if (searchInput) {
        searchInput.addEventListener('input', debounce(function(e) {
            searchFleet(e.target.value);
        }, 300));
    }
}

// Initialize Tabs
function initTabs() {
    const tabs = document.querySelectorAll('.tab');
    const tabPanes = document.querySelectorAll('.tab-pane');
    
    if (tabs.length > 0) {
        tabs[0].classList.add('active');
        tabPanes.forEach(pane => pane.style.display = 'none');
        const tabOverview = document.getElementById('tab-overview');
        if (tabOverview) tabOverview.style.display = 'block';
    }
}

// Switch Tabs
function switchTab(tabId) {
    document.querySelectorAll('.tab').forEach(tab => {
        tab.classList.remove('active');
        if (tab.dataset.tab === tabId) {
            tab.classList.add('active');
        }
    });
    
    document.querySelectorAll('.tab-pane').forEach(pane => {
        pane.style.display = 'none';
    });
    document.getElementById(`tab-${tabId}`).style.display = 'block';
}

// Real-time updates simulation
function startRealTimeUpdates() {
    setInterval(() => {
        // Update driver activity every 30 seconds
        updateDriverActivity();
        // Update vehicle availability
        updateVehicleAvailability();
    }, 30000);
}
// Load Vehicle Availability
function loadVehicleAvailability() {
    const vehicleList = document.querySelector('.vehicle-list');
    if (!vehicleList) return;
    
    const vehicles = [
        { id: 'VH-001', name: 'Volvo FH16', type: 'Truck', plate: 'ABC-1234', availability: 'available', fuel: 78, mileage: 45230, driver: 'John Smith' },
        { id: 'VH-002', name: 'Scania R500', type: 'Truck', plate: 'XYZ-5678', availability: 'in-use', fuel: 45, mileage: 67890, driver: 'Mike Johnson' },
        { id: 'VH-003', name: 'Mercedes Sprinter', type: 'Van', plate: 'DEF-9012', availability: 'maintenance', fuel: 92, mileage: 12340, driver: null },
        { id: 'VH-004', name: 'MAN TGX', type: 'Truck', plate: 'GHI-3456', availability: 'available', fuel: 67, mileage: 34560, driver: 'Sarah Wilson' },
        { id: 'VH-005', name: 'Ford Transit', type: 'Van', plate: 'JKL-7890', availability: 'reserved', fuel: 89, mileage: 23450, driver: null },
        { id: 'VH-006', name: 'Iveco Stralis', type: 'Truck', plate: 'MNO-1234', availability: 'in-use', fuel: 34, mileage: 89120, driver: 'Tom Brown' }
    ];
    
    let html = '';
    vehicles.forEach(vehicle => {
        html += `
            <div class="vehicle-item">
                <div class="vehicle-info">
                    <div class="vehicle-icon">
                        <i class="fas fa-${vehicle.type === 'Truck' ? 'truck' : 'van-shuttle'}"></i>
                    </div>
                    <div class="vehicle-details">
                        <h3>${vehicle.name} (${vehicle.id})</h3>
                        <div class="vehicle-meta">
                            <span><i class="fas fa-plate"></i> ${vehicle.plate}</span>
                            <span><i class="fas fa-tachometer-alt"></i> ${vehicle.mileage.toLocaleString()} km</span>
                            ${vehicle.driver ? `<span><i class="fas fa-user"></i> ${vehicle.driver}</span>` : ''}
                        </div>
                    </div>
                </div>
                <div class="vehicle-status">
                    <span class="availability-badge availability-${vehicle.availability}">
                        <i class="fas fa-${vehicle.availability === 'available' ? 'check-circle' : vehicle.availability === 'in-use' ? 'play-circle' : vehicle.availability === 'maintenance' ? 'wrench' : 'calendar-check'}"></i>
                        ${vehicle.availability.replace('-', ' ').toUpperCase()}
                    </span>
                </div>
                <div class="vehicle-metrics">
                    <div class="vehicle-metric">
                        <div class="value">${vehicle.fuel}%</div>
                        <div class="label">Fuel</div>
                    </div>
                    <div class="vehicle-metric">
                        <div class="value">${Math.round(vehicle.mileage / 100)}%</div>
                        <div class="label">Usage</div>
                    </div>
                </div>
            </div>
        `;
    });
    
    vehicleList.innerHTML = html;
}

// Load Transport Efficiency
function loadTransportEfficiency() {
    const efficiencyList = document.querySelector('.efficiency-list');
    if (!efficiencyList) return;
    
    const efficiency = [
        { metric: 'Fuel Efficiency', current: 8.2, average: 9.5, target: 10, unit: 'km/l' },
        { metric: 'Load Capacity Utilization', current: 78, average: 75, target: 85, unit: '%' },
        { metric: 'Empty Running', current: 12, average: 15, target: 10, unit: '%' },
        { metric: 'On-time Delivery', current: 94, average: 92, target: 98, unit: '%' },
        { metric: 'Cost per Kilometer', current: 1.24, average: 1.35, target: 1.2, unit: '$' }
    ];
    
    let html = '';
    efficiency.forEach(item => {
        const percentage = (item.current / item.target) * 100;
        const fillClass = percentage >= 90 ? 'high' : percentage >= 75 ? 'medium' : 'low';
        
        html += `
            <div class="efficiency-item">
                <div class="efficiency-header">
                    <span class="efficiency-title">${item.metric}</span>
                    <span class="efficiency-value">${item.current} ${item.unit}</span>
                </div>
                <div class="efficiency-bar">
                    <div class="efficiency-fill ${fillClass}" style="width: ${percentage}%"></div>
                </div>
                <div class="efficiency-stats">
                    <span>Target: ${item.target} ${item.unit}</span>
                    <span>Avg: ${item.average} ${item.unit}</span>
                </div>
            </div>
        `;
    });
    
    efficiencyList.innerHTML = html;
}

// Load Delay Analysis
function loadDelayAnalysis() {
    const delayList = document.querySelector('.delay-list');
    if (!delayList) return;
    
    const delays = [
        { id: 'TR-2024-001', route: 'Warehouse A to Distribution Center', reason: 'Traffic congestion', duration: 45, type: 'traffic' },
        { id: 'TR-2024-002', route: 'Port to Warehouse B', reason: 'Loading delay', duration: 30, type: 'operational' },
        { id: 'TR-2024-003', route: 'Depot to Customer Site', reason: 'Vehicle breakdown', duration: 120, type: 'mechanical' },
        { id: 'TR-2024-004', route: 'Supplier to Factory', reason: 'Weather conditions', duration: 60, type: 'weather' },
        { id: 'TR-2024-005', route: 'Cross-dock to Store', reason: 'Documentation issues', duration: 25, type: 'administrative' }
    ];
    
    let html = '';
    delays.forEach(delay => {
        html += `
            <div class="delay-item">
                <div class="delay-icon">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="delay-content">
                    <div class="delay-title">${delay.route}</div>
                    <div class="delay-meta">
                        <span>${delay.id}</span>
                        <span>${delay.reason}</span>
                    </div>
                </div>
                <div class="delay-duration">
                    ${delay.duration} min
                </div>
                <div class="delay-type">
                    ${delay.type}
                </div>
            </div>
        `;
    });
    
    delayList.innerHTML = html;
}

// Load Driver Performance
function loadDriverPerformance() {
    const driverList = document.querySelector('.driver-list');
    if (!driverList) return;
    
    const drivers = [
        { name: 'John Smith', id: 'DR-001', trips: 45, rating: 4.8, efficiency: 96, safety: 98, avatar: 'JS' },
        { name: 'Mike Johnson', id: 'DR-002', trips: 38, rating: 4.6, efficiency: 92, safety: 94, avatar: 'MJ' },
        { name: 'Sarah Wilson', id: 'DR-003', trips: 42, rating: 4.9, efficiency: 98, safety: 99, avatar: 'SW' },
        { name: 'Tom Brown', id: 'DR-004', trips: 35, rating: 4.5, efficiency: 89, safety: 92, avatar: 'TB' },
        { name: 'Emily Davis', id: 'DR-005', trips: 28, rating: 4.7, efficiency: 94, safety: 96, avatar: 'ED' }
    ];
    
    let html = '';
    drivers.forEach(driver => {
        html += `
            <div class="driver-item">
                <div class="driver-avatar">${driver.avatar}</div>
                <div class="driver-info">
                    <h3>${driver.name}</h3>
                    <div class="driver-meta">
                        <span>${driver.id}</span>
                        <span>${driver.trips} trips</span>
                    </div>
                </div>
                <div class="driver-stats">
                    <div class="driver-stat">
                        <div class="value">${driver.efficiency}%</div>
                        <div class="label">Efficiency</div>
                    </div>
                    <div class="driver-stat">
                        <div class="value">${driver.safety}%</div>
                        <div class="label">Safety</div>
                    </div>
                </div>
                <div class="driver-rating">
                    ${getStarRating(driver.rating)}
                    <span>${driver.rating}</span>
                </div>
            </div>
        `;
    });
    
    driverList.innerHTML = html;
}

// Load Trip History
function loadTripHistory() {
    const tripList = document.querySelector('.trip-list');
    if (!tripList) return;
    
    const trips = [
        { id: 'TR-001', from: 'Warehouse A', to: 'Distribution Center', date: '2024-02-20', distance: 245, duration: 3.5, status: 'completed', driver: 'John Smith' },
        { id: 'TR-002', from: 'Port', to: 'Warehouse B', date: '2024-02-20', distance: 180, duration: 2.5, status: 'in-progress', driver: 'Mike Johnson' },
        { id: 'TR-003', from: 'Depot', to: 'Customer Site', date: '2024-02-19', distance: 95, duration: 1.5, status: 'completed', driver: 'Sarah Wilson' },
        { id: 'TR-004', from: 'Supplier', to: 'Factory', date: '2024-02-19', distance: 320, duration: 4.5, status: 'delayed', driver: 'Tom Brown' },
        { id: 'TR-005', from: 'Cross-dock', to: 'Store', date: '2024-02-18', distance: 65, duration: 1.2, status: 'completed', driver: 'Emily Davis' },
        { id: 'TR-006', from: 'Warehouse B', to: 'Airport', date: '2024-02-18', distance: 150, duration: 2.2, status: 'completed', driver: 'John Smith' }
    ];
    
    let html = '';
    trips.forEach(trip => {
        html += `
            <div class="trip-item">
                <div class="trip-icon">
                    <i class="fas fa-route"></i>
                </div>
                <div class="trip-content">
                    <div class="trip-route">${trip.from} → ${trip.to}</div>
                    <div class="trip-meta">
                        <span>${trip.id}</span>
                        <span>${trip.driver}</span>
                        <span>${trip.date}</span>
                    </div>
                </div>
                <div class="trip-stats">
                    <div class="trip-stat">
                        <div class="value">${trip.distance}km</div>
                        <div class="label">Distance</div>
                    </div>
                    <div class="trip-stat">
                        <div class="value">${trip.duration}h</div>
                        <div class="label">Duration</div>
                    </div>
                </div>
                <div class="trip-status ${trip.status}">
                    ${trip.status.replace('-', ' ')}
                </div>
            </div>
        `;
    });
    
    tripList.innerHTML = html;
}

// Load Driver Activity
function loadDriverActivity() {
    const activityTimeline = document.querySelector('.activity-timeline');
    if (!activityTimeline) return;
    
    const activities = [
        { driver: 'John Smith', action: 'Started trip', detail: 'Warehouse A → Distribution Center', time: '10 minutes ago', type: 'green' },
        { driver: 'Mike Johnson', action: 'Completed trip', detail: 'Port → Warehouse B', time: '25 minutes ago', type: 'blue' },
        { driver: 'Sarah Wilson', action: 'Vehicle check', detail: 'Pre-trip inspection', time: '1 hour ago', type: 'blue' },
        { driver: 'Tom Brown', action: 'Delay reported', detail: 'Traffic on Highway 101', time: '2 hours ago', type: 'amber' },
        { driver: 'Emily Davis', action: 'Started break', detail: '30 min rest stop', time: '3 hours ago', type: 'amber' }
    ];
    
    let html = '';
    activities.forEach(activity => {
        html += `
            <div class="activity-item">
                <div class="activity-dot ${activity.type}"></div>
                <div class="activity-content">
                    <div class="activity-header">
                        <span class="activity-title">${activity.driver}</span>
                        <span class="activity-time">${activity.time}</span>
                    </div>
                    <div class="activity-desc">${activity.action} - ${activity.detail}</div>
                    <div class="activity-meta">
                        <span><i class="fas fa-map-marker-alt"></i> GPS Tracking</span>
                    </div>
                </div>
            </div>
        `;
    });
    
    activityTimeline.innerHTML = html;
}

// Load Vehicle Reservations
function loadVehicleReservations() {
    const reservationList = document.querySelector('.reservation-list');
    if (!reservationList) return;
    
    const reservations = [
        { id: 'RES-001', vehicle: 'Volvo FH16', requester: 'John Smith', department: 'Logistics', from: '2024-02-21', to: '2024-02-23', status: 'approved' },
        { id: 'RES-002', vehicle: 'Scania R500', requester: 'Mike Johnson', department: 'Transport', from: '2024-02-22', to: '2024-02-24', status: 'pending' },
        { id: 'RES-003', vehicle: 'Mercedes Sprinter', requester: 'Sarah Wilson', department: 'Delivery', from: '2024-02-21', to: '2024-02-22', status: 'rejected' },
        { id: 'RES-004', vehicle: 'MAN TGX', requester: 'Tom Brown', department: 'Logistics', from: '2024-02-23', to: '2024-02-25', status: 'approved' },
        { id: 'RES-005', vehicle: 'Ford Transit', requester: 'Emily Davis', department: 'Maintenance', from: '2024-02-21', to: '2024-02-21', status: 'pending' }
    ];
    
    let html = '';
    reservations.forEach(res => {
        html += `
            <div class="reservation-item">
                <div class="reservation-info">
                    <div class="reservation-icon">
                        <i class="fas fa-calendar-check"></i>
                    </div>
                    <div class="reservation-details">
                        <h3>${res.vehicle}</h3>
                        <div class="reservation-meta">
                            <span>${res.id}</span>
                            <span>${res.requester}</span>
                            <span>${res.department}</span>
                        </div>
                    </div>
                </div>
                <div class="reservation-dates">
                    <i class="fas fa-calendar"></i>
                    ${res.from} - ${res.to}
                </div>
                <div class="reservation-status">
                    <span class="reservation-badge reservation-${res.status}">
                        <i class="fas fa-${res.status === 'approved' ? 'check-circle' : res.status === 'pending' ? 'clock' : 'times-circle'}"></i>
                        ${res.status}
                    </span>
                </div>
                <div class="reservation-actions">
                    <button class="reservation-btn" onclick="viewReservation('${res.id}')" title="View">
                        <i class="fas fa-eye"></i>
                    </button>
                    <button class="reservation-btn" onclick="editReservation('${res.id}')" title="Edit">
                        <i class="fas fa-edit"></i>
                    </button>
                </div>
            </div>
        `;
    });
    
    reservationList.innerHTML = html;
}

// Load Dispatch Schedule
function loadDispatchSchedule() {
    const scheduleList = document.querySelector('.schedule-list');
    if (!scheduleList) return;
    
    const schedules = [
        { time: '08:00', vehicle: 'Volvo FH16', driver: 'John Smith', route: 'Warehouse A → DC', type: 'Delivery' },
        { time: '09:30', vehicle: 'Scania R500', driver: 'Mike Johnson', route: 'Port → Warehouse B', type: 'Pickup' },
        { time: '11:00', vehicle: 'Mercedes Sprinter', driver: 'Sarah Wilson', route: 'Depot → Customer', type: 'Delivery' },
        { time: '13:30', vehicle: 'MAN TGX', driver: 'Tom Brown', route: 'Supplier → Factory', type: 'Pickup' },
        { time: '15:00', vehicle: 'Ford Transit', driver: 'Emily Davis', route: 'Cross-dock → Store', type: 'Delivery' },
        { time: '16:30', vehicle: 'Iveco Stralis', driver: 'Pending', route: 'Warehouse B → Airport', type: 'Transfer' }
    ];
    
    let html = '';
    schedules.forEach(schedule => {
        html += `
            <div class="schedule-item">
                <div class="schedule-time">${schedule.time}</div>
                <div class="schedule-content">
                    <div class="schedule-title">${schedule.route}</div>
                    <div class="schedule-meta">
                        <span>${schedule.vehicle}</span>
                        <span>${schedule.type}</span>
                    </div>
                </div>
                <div class="schedule-assignee">
                    <div class="assignee-avatar">${schedule.driver.split(' ').map(n => n[0]).join('')}</div>
                    <span class="assignee-name">${schedule.driver}</span>
                </div>
            </div>
        `;
    });
    
    scheduleList.innerHTML = html;
}

// Load Vehicle Assignments
function loadVehicleAssignments() {
    const assignmentList = document.querySelector('.assignment-list');
    if (!assignmentList) return;
    
    const assignments = [
        { vehicle: 'Volvo FH16', plate: 'ABC-1234', driver: 'John Smith', date: '2024-02-20', shift: 'Morning', status: 'active' },
        { vehicle: 'Scania R500', plate: 'XYZ-5678', driver: 'Mike Johnson', date: '2024-02-20', shift: 'Afternoon', status: 'active' },
        { vehicle: 'MAN TGX', plate: 'GHI-3456', driver: 'Sarah Wilson', date: '2024-02-20', shift: 'Morning', status: 'active' },
        { vehicle: 'Iveco Stralis', plate: 'MNO-1234', driver: 'Tom Brown', date: '2024-02-20', shift: 'Night', status: 'active' }
    ];
    
    let html = '';
    assignments.forEach(assignment => {
        html += `
            <div class="assignment-item">
                <div class="assignment-vehicle">
                    <i class="fas fa-truck"></i>
                    <span>${assignment.vehicle}</span>
                    <span style="font-size: 12px; color: #64748b;">${assignment.plate}</span>
                </div>
                <div class="assignment-driver">
                    <i class="fas fa-user"></i>
                    <span>${assignment.driver}</span>
                </div>
                <div class="assignment-date">
                    <i class="fas fa-calendar"></i>
                    ${assignment.date} (${assignment.shift})
                </div>
                <div class="assignment-status">
                    <span class="availability-badge availability-available">
                        <i class="fas fa-check-circle"></i>
                        ${assignment.status}
                    </span>
                </div>
            </div>
        `;
    });
    
    assignmentList.innerHTML = html;
}

// Load Maintenance Report
function loadMaintenanceReport() {
    const maintenanceList = document.querySelector('.maintenance-list');
    if (!maintenanceList) return;
    
    const maintenance = [
        { vehicle: 'Volvo FH16', type: 'Oil Change', due: '2024-02-25', status: 'upcoming', priority: 'normal' },
        { vehicle: 'Scania R500', type: 'Brake Inspection', due: '2024-02-22', status: 'due-soon', priority: 'high' },
        { vehicle: 'Mercedes Sprinter', type: 'Engine Service', due: '2024-02-21', status: 'overdue', priority: 'critical' },
        { vehicle: 'MAN TGX', type: 'Tire Replacement', due: '2024-02-28', status: 'upcoming', priority: 'normal' },
        { vehicle: 'Ford Transit', type: 'Annual Inspection', due: '2024-02-23', status: 'due-soon', priority: 'high' }
    ];
    
    let html = '';
    maintenance.forEach(item => {
        const dueClass = item.status === 'overdue' ? 'due-overdue' : item.status === 'due-soon' ? 'due-soon' : '';
        
        html += `
            <div class="maintenance-item">
                <div class="maintenance-icon">
                    <i class="fas fa-wrench"></i>
                </div>
                <div class="maintenance-content">
                    <div class="maintenance-title">${item.vehicle} - ${item.type}</div>
                    <div class="maintenance-meta">
                        <span>Due: ${item.due}</span>
                    </div>
                </div>
                <div class="maintenance-due ${dueClass}">
                    ${item.status.replace('-', ' ')}
                </div>
            </div>
        `;
    });
    
    maintenanceList.innerHTML = html;
}

// Load Fleet Condition
function loadFleetCondition() {
    const conditionGrid = document.querySelector('.condition-grid');
    if (!conditionGrid) return;
    
    const conditions = [
        { category: 'Excellent', count: 12, percentage: 40, color: 'good' },
        { category: 'Good', count: 10, percentage: 33, color: 'good' },
        { category: 'Fair', count: 5, percentage: 17, color: 'warning' },
        { category: 'Poor', count: 3, percentage: 10, color: 'critical' }
    ];
    
    let html = '';
    conditions.forEach(condition => {
        html += `
            <div class="condition-item">
                <div class="condition-icon">
                    <i class="fas fa-${condition.category === 'Excellent' ? 'star' : condition.category === 'Good' ? 'thumbs-up' : condition.category === 'Fair' ? 'exclamation' : 'exclamation-triangle'}"></i>
                </div>
                <h3>${condition.category}</h3>
                <div class="condition-value">${condition.count}</div>
                <div class="condition-bar">
                    <div class="condition-fill ${condition.color}" style="width: ${condition.percentage}%"></div>
                </div>
                <div class="condition-label">${condition.percentage}% of fleet</div>
            </div>
        `;
    });
    
    conditionGrid.innerHTML = html;
}

// Update Driver Activity (real-time)
function updateDriverActivity() {
    console.log('Updating driver activity...');
    // In real app, fetch new data and update
}

// Update Vehicle Availability (real-time)
function updateVehicleAvailability() {
    console.log('Updating vehicle availability...');
    // In real app, fetch new data and update
}

// Refresh Card
function refreshCard(cardTitle) {
    console.log(`Refreshing ${cardTitle}...`);
    showNotification(`${cardTitle} refreshed`, 'success');
    
    // Refresh specific data based on card title
    if (cardTitle.includes('Vehicle')) {
        loadVehicleAvailability();
    } else if (cardTitle.includes('Efficiency')) {
        loadTransportEfficiency();
    } else if (cardTitle.includes('Delay')) {
        loadDelayAnalysis();
    } else if (cardTitle.includes('Driver Performance')) {
        loadDriverPerformance();
    }
}

// Filter Data
function filterData(filterId, value) {
    console.log(`Filtering ${filterId}: ${value}`);
    // Implement filter logic
}

// Search Fleet
function searchFleet(term) {
    console.log('Searching fleet:', term);
    // Implement search logic
}

// View Reservation
function viewReservation(resId) {
    console.log('Viewing reservation:', resId);
    showNotification(`Viewing reservation ${resId}`, 'info');
}

// Edit Reservation
function editReservation(resId) {
    console.log('Editing reservation:', resId);
    openReservationModal(resId);
}

// Open Reservation Modal
function openReservationModal(resId = null) {
    if (resId) {
        console.log('Opening reservation modal for ID:', resId);
    } else {
        console.log('Opening new reservation modal');
    }
    showNotification('Reservation modal opened', 'info');
}

// Helper function for star rating
function getStarRating(rating) {
    const fullStars = Math.floor(rating);
    const halfStar = rating % 1 >= 0.5;
    let stars = '';
    
    for (let i = 0; i < fullStars; i++) {
        stars += '<i class="fas fa-star"></i>';
    }
    if (halfStar) {
        stars += '<i class="fas fa-star-half-alt"></i>';
    }
    const emptyStars = 5 - Math.ceil(rating);
    for (let i = 0; i < emptyStars; i++) {
        stars += '<i class="far fa-star"></i>';
    }
    
    return stars;
}

// Show Notification
function showNotification(message, type = 'info') {
    const notification = document.createElement('div');
    notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        padding: 12px 20px;
        background: ${type === 'success' ? '#10b981' : type === 'error' ? '#ef4444' : '#3b82f6'};
        color: white;
        border-radius: 8px;
        font-size: 14px;
        font-weight: 500;
        z-index: 1000;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        animation: slideIn 0.3s ease;
    `;
    notification.textContent = message;
    
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.style.animation = 'slideOut 0.3s ease';
        setTimeout(() => {
            document.body.removeChild(notification);
        }, 300);
    }, 3000);
}

// Debounce function
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

// Add animation styles
const style = document.createElement('style');
style.textContent = `
    @keyframes slideIn {
        from { transform: translateX(100%); opacity: 0; }
        to { transform: translateX(0); opacity: 1; }
    }
    
    @keyframes slideOut {
        from { transform: translateX(0); opacity: 1; }
        to { transform: translateX(100%); opacity: 0; }
    }
`;
document.head.appendChild(style);