// assets/js/fleet.js - Combined Fleet Management JavaScript
document.addEventListener('DOMContentLoaded', function() {
    console.log('Fleet dashboard loaded');
    
    // Load all data
    loadFleetData();
    
    // Initialize UI
    setupEventListeners();
    initTabs();
    
    // Start real-time updates
    startRealTimeUpdates();
    
    // Get user role ONCE
    const userRole = document.body.dataset.userRole;
    console.log('Current user role:', userRole);
    
    // Load role-specific data
    if (userRole === 'driver') {
        console.log('👤 Driver detected');
        loadDriverAssignment();
        loadDriverStats();
        loadDriverTrips();
    }
    
    if (userRole === 'fleet_manager' || userRole === 'admin') {
        console.log('📋 Fleet manager/admin detected - loading emergency requests');
        // Load emergency requests after a short delay
        setTimeout(() => {
            if (typeof loadEmergencyBreakdowns === 'function') {
                loadEmergencyBreakdowns();
            } else {
                console.error('loadEmergencyBreakdowns function not found!');
            }
        }, 1000);
    }
    
    if (userRole === 'mechanic') {
        console.log('🔧 Mechanic detected');
        setTimeout(() => {
            if (typeof loadMechanicTasks === 'function') {
                loadMechanicTasks();
            }
            if (typeof loadMyEmergencyBreakdowns === 'function') {
            loadMyEmergencyBreakdowns();
        }   
        }, 1000);
    }
    
    // Define activeTab
    const activeTab = document.querySelector('.tab.active');
    
    // Reservations tab listener
    const reservationsTab = document.querySelector('.tab[data-tab="reservations"]');
    if (reservationsTab) {
        reservationsTab.addEventListener('click', function() {
            console.log('Reservations tab clicked - loading verifications');
            setTimeout(loadVehicleReservations, 100);
            setTimeout(loadPendingVerifications, 200);
        });
    } else {
        console.log('Reservations tab not found - user is likely driver or mechanic');
    }
    
    // Check if reservations tab is active on page load
    if (activeTab && activeTab.dataset.tab === 'reservations' && reservationsTab) {
        console.log('Reservations tab active on load');
        setTimeout(loadPendingVerifications, 500);
    }
});

// ============================================
// INITIALIZATION FUNCTIONS
// ============================================

function loadFleetData() {
    // Load data for all tabs
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

function setupEventListeners() {
    // Tab switching - UPDATED VERSION
    document.querySelectorAll('.tab').forEach(tab => {
        tab.addEventListener('click', function() {
            const tabId = this.dataset.tab;
            console.log('Tab clicked:', tabId);
            
            // Update active tab
            document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
            this.classList.add('active');
            
            // Hide all tab panes
            document.querySelectorAll('.tab-pane').forEach(pane => {
                pane.style.display = 'none';
            });
            
            // Show selected tab pane
            const selectedPane = document.getElementById(`tab-${tabId}`);
            if (selectedPane) {
                selectedPane.style.display = 'block';
                
                // Load data based on tab
                if (tabId === 'overview') {
                    console.log('Overview tab activated, loading data...');
                    // Load all overview data with delays to ensure DOM is ready
                    setTimeout(() => {
                        loadTransportEfficiency();
                        loadDelayAnalysis();
                        loadFleetCondition();
                        loadDriverPerformance();
                        loadDriverActivity();
                    }, 200);
                    
                    // Load trip history with a slightly longer delay
                    setTimeout(() => {
                        loadTripHistory();
                    }, 500);
                } else if (tabId === 'vehicles') {
                    setTimeout(() => {
                        loadVehicleAvailability();
                        loadVehicleAssignments();
                    }, 200);
                } else if (tabId === 'reservations') {
                    setTimeout(() => {
                        loadVehicleReservations();
                        loadDispatchSchedule();
                    }, 200);
                } else if (tabId === 'maintenance') {
                    setTimeout(() => {
                        loadMaintenanceReport();
                        loadFleetCondition();
                    }, 200);
                }// Add this to your existing tab click handler:
else if (tabId === 'mechanic') {
    setTimeout(() => {
        loadMechanicTasks();
    }, 200);
}              
                else if (tabId === 'drivers') {
    setTimeout(() => {
        loadDriverAssignment();
        loadDriverStats();
        loadDriverTrips();  // Only called when drivers tab is clicked
    }, 200);
}
            }
        });
    });
    
    // Reservations tab specific listener (keep this)
    document.querySelectorAll('.tab[data-tab="reservations"]').forEach(tab => {
        tab.addEventListener('click', function() {
            setTimeout(loadVehicleReservations, 100);
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
function initTabs() {
    const tabs = document.querySelectorAll('.tab');
    const tabPanes = document.querySelectorAll('.tab-pane');
    
    if (tabs.length > 0) {
        const activeTab = document.querySelector('.tab.active');
        if (!activeTab && tabs.length > 0) {
            tabs[0].classList.add('active');
            tabPanes.forEach(pane => pane.style.display = 'none');
            const tabOverview = document.getElementById('tab-overview');
            if (tabOverview) {
                tabOverview.style.display = 'block';
                // Load overview data
                setTimeout(() => {
                    loadTransportEfficiency();
                    loadDelayAnalysis();
                    loadFleetCondition();
                    loadDriverPerformance();
                    loadDriverActivity();
                    loadTripHistory();
                }, 300);
            }
        }
    }
}

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
    
    const selectedPane = document.getElementById(`tab-${tabId}`);
    if (selectedPane) {
        selectedPane.style.display = 'block';
    }
}

function startRealTimeUpdates() {
    // Refresh every 30 seconds for driver data
    setInterval(loadDriverAssignment, 30000);
    setInterval(loadDriverStats, 60000);
    
    // Refresh fleet data
    setInterval(() => {
        updateDriverActivity();
        updateVehicleAvailability();
    }, 30000);
}

// ============================================
// DRIVER FUNCTIONS
// ============================================
function loadDriverAssignment() {
    fetch('../api/get_driver_assignment.php')
        .then(response => response.json())
        .then(data => {
            const content = document.getElementById('assignment-content');
            const statusBadge = document.getElementById('assignment-status');
            
            if (!content || !statusBadge) return;
            
            if (data.success) {
                if (data.has_assignment) {
                    const a = data.assignment;
                    
                    // Parse location history with color coding
                    const locationHistory = parseLocationHistory(a.current_location);
                    
                    // Determine badge class and text based on status
                    let badgeClass = 'card-badge ';
                    let statusText = a.shipment_status.toUpperCase();
                    let statusIcon = '';
                    
                    if (a.shipment_status === 'in_transit' || a.shipment_status === 'in-progress') {
                        badgeClass += 'status-warning';
                        statusIcon = '<i class="fas fa-play-circle"></i>';
                        statusText = 'IN TRANSIT';
                    } else if (a.shipment_status === 'delivered') {
                        badgeClass += 'status-info';
                        statusIcon = '<i class="fas fa-check-circle"></i>';
                        statusText = 'DELIVERED (AWAITING RETURN)';
                    } else if (a.shipment_status === 'completed') {
                        badgeClass += 'status-success';
                        statusIcon = '<i class="fas fa-check-double"></i>';
                        statusText = 'COMPLETED';
                    } else if (a.shipment_status === 'pending') {
                        badgeClass += 'status-warning';
                        statusIcon = '<i class="fas fa-clock"></i>';
                        statusText = 'PENDING';
                    } else if (a.shipment_status === 'returned') {
                        badgeClass += 'status-success';
                        statusIcon = '<i class="fas fa-undo-alt"></i>';
                        statusText = 'RETURNED';
                    } else {
                        badgeClass += 'status-info';
                        statusIcon = '<i class="fas fa-info-circle"></i>';
                    }
                    
                    statusBadge.innerHTML = statusIcon + ' ' + statusText;
                    statusBadge.className = badgeClass;
                    
                    // Generate color-coded location dropdown HTML
                    const locationHTML = generateColorCodedLocationDropdown(locationHistory);
                    
                    // Get action buttons (update button removed from here)
                    const actionButtons = getActionButtons(a.shipment_status);
                    
                    content.innerHTML = `
                        <div class="assignment-details">
                            <div class="detail-row">
                                <strong><i class="fas fa-truck"></i> Vehicle:</strong>
                                <span>${a.vehicle_name} <span style="color: #666; font-size: 12px;">(Condition: ${a.vehicle_condition}%)</span></span>
                            </div>
                            <div class="detail-row">
                                <strong><i class="fas fa-user"></i> Customer:</strong>
                                <span>${a.customer_name || 'N/A'}</span>
                            </div>
                            <div class="detail-row">
                                <strong><i class="fas fa-map-marker-alt"></i> Delivery Address:</strong>
                                <span>${a.delivery_address || 'N/A'}</span>
                            </div>
                            
                            <!-- Color-coded Location Row -->
                            <div class="detail-row location-row">
                                <strong><i class="fas fa-location-dot"></i> Current Location:</strong>
                                ${locationHTML}
                            </div>
                            
                            <div class="detail-row">
                                <strong><i class="fas fa-clock"></i> Estimated Arrival:</strong>
                                <span>${a.estimated_arrival ? new Date(a.estimated_arrival).toLocaleString() : 'N/A'}</span>
                            </div>
                            ${a.route ? `
                            <div class="detail-row">
                                <strong><i class="fas fa-route"></i> Route:</strong>
                                <span>${a.route}</span>
                            </div>
                            ` : ''}
                            
                            <!-- Quick Actions - Update button is NOT here anymore -->
                            <div class="assignment-actions">
                                ${actionButtons}
                            </div>
                        </div>
                    `;
                    
                } else {
                    // No assignment code...
                    statusBadge.innerHTML = '<i class="fas fa-clock"></i> NO ASSIGNMENT';
                    statusBadge.className = 'card-badge status-warning';
                    content.innerHTML = `
                        <div style="text-align: center; padding: 40px;">
                            <div style="background: linear-gradient(135deg, #fff3cd 0%, #ffe69c 100%); border: 1px solid #ffe69c; color: #856404; border-radius: 16px; padding: 30px; margin-bottom: 20px;">
                                <i class="fas fa-exclamation-triangle" style="font-size: 56px; margin-bottom: 16px; color: #856404;"></i>
                                <h3 style="margin-bottom: 10px; color: #856404; font-size: 24px;">No Active Assignment</h3>
                                <p style="margin-bottom: 15px; font-size: 16px;">${data.message || 'You don\'t have any active assignments at the moment.'}</p>
                            </div>
                        </div>
                    `;
                }
            }
        })
        .catch(error => {
            console.error('Error loading assignment:', error);
        });
}

// Enhanced location history parser with clean, readable text
function parseLocationHistory(currentLocation) {
    const history = [];
    
    if (!currentLocation || currentLocation === 'Not started') {
        return [{
            displayText: 'Not started',
            timestamp: null,
            type: 'default',
            icon: 'fa-circle',
            color: '#64748b',
            time: '',
            date: '',
            formattedTime: 'Not started'
        }];
    }
    
    // Clean up any duplicate text
    currentLocation = currentLocation.replace(/Dispatch center at Dispatch center/gi, 'Dispatch center');
    
    // Split by timestamps
    const parts = currentLocation.split(/\[(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})\]/);
    
    // First pass: collect all entries
    const rawEntries = [];
    for (let i = 1; i < parts.length; i += 2) {
        const timestamp = parts[i];
        let message = parts[i + 1] ? parts[i + 1].trim() : '';
        
        if (!message || !timestamp) continue;
        
        const [datePart, timePart] = timestamp.split(' ');
        const [year, month, day] = datePart.split('-').map(Number);
        let [hour, minute, second] = timePart.split(':').map(Number);
        
        // ✅ REMOVED THE CONVERSION - Keep original hour
        
        rawEntries.push({
            timestamp: timestamp,
            message: message,
            hour: hour,
            minute: minute,
            year: year,
            month: month,
            day: day,
            sortTime: new Date(year, month-1, day, hour, minute, second).getTime()
        });
    }
    
    // Sort by time (oldest first for processing)
    rawEntries.sort((a, b) => a.sortTime - b.sortTime);
    
    // Process entries to merge location into empty entry
    const processedEntries = [];
    let lastValidLocation = null;
    
    for (let i = 0; i < rawEntries.length; i++) {
        const entry = rawEntries[i];
        
        // Check if this is a location update with empty location
        if (entry.message.includes('Location update:') && 
            (entry.message.includes('Location update:') && entry.message.replace('Location update:', '').trim() === '')) {
            
            // This is an empty location update - look ahead for the next location
            let locationToAdd = null;
            
            // Look at next entries to find a location
            for (let j = i + 1; j < rawEntries.length; j++) {
                const nextEntry = rawEntries[j];
                if (nextEntry.message.includes('Location update:')) {
                    const loc = nextEntry.message.replace('Location update:', '').trim();
                    if (loc && loc !== '') {
                        locationToAdd = loc;
                        // Mark the next entry as used/removed
                        rawEntries[j].used = true;
                        break;
                    }
                }
            }
            
            // If we found a location, update this entry
            if (locationToAdd) {
                entry.message = `Location update: ${locationToAdd}`;
                entry.displayText = `Arrived at ${locationToAdd}`;
                processedEntries.push(entry);
            } else {
                // No location found, skip this empty entry
                continue;
            }
        }
        // Skip entries that were marked as used (the ones we took location from)
        else if (!entry.used) {
            processedEntries.push(entry);
        }
    }
    
    // Now convert processed entries to the final format
    const uniqueEntries = new Map();
    
    processedEntries.forEach(entry => {
        const { timestamp, message, hour, minute, year, month, day } = entry;
        
        // Format time in 12-hour format
        const period = hour >= 12 ? 'PM' : 'AM';
        const hour12 = hour % 12 || 12;
        const time12h = `${hour12}:${minute.toString().padStart(2, '0')} ${period}`;
        
        // Format date
        const monthNames = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
        const date = `${monthNames[month-1]} ${day}, ${year}`;
        const formattedTime = `${time12h} • ${date}`;
        
        let type = 'update';
        let icon = 'fa-map-marker-alt';
        let color = '#3b82f6';
        let bgColor = '#3b82f615';
        let displayText = '';
        
        // Clean the message
        const cleanMessage = message.replace(/ at Dispatch center$/, '');
        
        // Determine type
        if (cleanMessage.includes('Status changed to in-progress')) {
            type = 'trip_start';
            icon = 'fa-play-circle';
            color = '#8b5cf6';
            bgColor = '#8b5cf615';
            
            let location = 'Dispatch center';
            const locationMatch = cleanMessage.match(/Location: ([^-\n]+)/);
            if (locationMatch) {
                location = locationMatch[1].trim();
            }
            displayText = `Started trip from ${location}`;
        }
        else if (cleanMessage.includes('Location update:')) {
            const location = cleanMessage.replace('Location update:', '').trim();
            type = 'location';
            icon = 'fa-map-pin';
            color = '#3b82f6';
            bgColor = '#3b82f615';
            displayText = `Arrived at ${location}`;
        }
        else if (cleanMessage.includes('Status changed to delivered')) {
            type = 'delivered';
            icon = 'fa-check-circle';
            color = '#10b981';
            bgColor = '#10b98115';
            
            let location = 'destination';
            const locationMatch = cleanMessage.match(/Location: ([^-\n]+)/);
            if (locationMatch) {
                location = locationMatch[1].trim();
            }
            displayText = `Delivered to ${location}`;
        }
        else if (cleanMessage.includes('Status changed to awaiting_verification')) {
            type = 'return';
            icon = 'fa-undo-alt';
            color = '#f59e0b';
            bgColor = '#f59e0b15';
            displayText = 'Return requested - waiting for verification';
        }
        else if (cleanMessage.includes('Status changed to completed')) {
            type = 'completed';
            icon = 'fa-flag-checkered';
            color = '#6b7280';
            bgColor = '#6b728015';
            displayText = 'Trip completed';
        }
        else {
            displayText = cleanMessage;
        }
        
        const uniqueKey = `${timestamp}_${displayText}`;
        
        uniqueEntries.set(uniqueKey, {
            displayText: displayText,
            raw: cleanMessage,
            timestamp: timestamp,
            type: type,
            icon: icon,
            color: color,
            bgColor: bgColor,
            time: time12h,
            date: date,
            formattedTime: formattedTime
        });
    });
    
    // Convert Map to array and sort by timestamp (newest first)
    const historyArray = Array.from(uniqueEntries.values());
    
    return historyArray.sort((a, b) => {
        if (!a.timestamp) return 1;
        if (!b.timestamp) return -1;
        
        const parseTime = (timestamp) => {
            const [datePart, timePart] = timestamp.split(' ');
            const [year, month, day] = datePart.split('-').map(Number);
            const [hour, minute, second] = timePart.split(':').map(Number);
            // ✅ REMOVED THE CONVERSION - Use original hour for sorting
            return new Date(year, month-1, day, hour, minute, second).getTime();
        };
        
        return parseTime(b.timestamp) - parseTime(a.timestamp);
    });
}
// Generate clean location dropdown
function generateColorCodedLocationDropdown(history) {
    if (!history || history.length === 0) {
        return `<div class="no-location">No location updates</div>`;
    }
    
    const current = history[0];
    
    // Get type label
    const getTypeLabel = (type) => {
        const labels = {
            'trip_start': 'TRIP START',
            'location': 'LOCATION',
            'delivered': 'DELIVERED',
            'return': 'RETURN',
            'completed': 'COMPLETED',
            'default': 'UPDATE'
        };
        return labels[type] || 'UPDATE';
    };
    
    // Generate dropdown options with 12-hour format
    let options = '';
    history.forEach((item, index) => {
        const isCurrent = index === 0;
        options += `<option value="${item.raw}" ${isCurrent ? 'selected' : ''}>
            ${item.time} - ${item.displayText}
        </option>`;
    });
    
    return `
        <div class="location-card" style="border-left: 4px solid ${current.color};">
            <!-- Current Location -->
            <div class="location-current" style="background: ${current.bgColor};">
                <i class="fas ${current.icon}" style="color: ${current.color}; font-size: 20px;"></i>
                <div class="current-info">
                    <div class="current-text">${current.displayText}</div>
                    <div class="current-time">
                        <i class="far fa-clock"></i> ${current.formattedTime || current.time + ' • ' + current.date}
                    </div>
                </div>
                <span class="type-badge" style="background: ${current.color}; color: white;">
                    ${getTypeLabel(current.type)}
                </span>
            </div>
            
            <!-- History Dropdown (if multiple updates) -->
            ${history.length > 1 ? `
            <div class="history-section">
                <div class="history-header">
                    <i class="fas fa-history"></i> Previous Updates
                </div>
                <select class="history-dropdown" id="locationHistory">
                    ${options}
                </select>
            </div>
            ` : ''}
        </div>
    `;
}

// Simplified action buttons
function getActionButtons(status) {
    switch(status) {
        case 'pending':
            return `
               
            `;
        
        case 'in_transit':
        case 'in-progress':
            return `
              
            `;
        
        case 'delivered':
            return `
               
            `;
        
        case 'returned':
            return `
              
            `;
        
        case 'completed':
            return `
               
            `;
        
        default:
            return '';
    }
}


function loadDriverTrips() {
    const tripHistory = document.getElementById('trip-history');
    if (!tripHistory) {
        console.error('Trip history element not found');
        return;
    }
    
    console.log('Loading driver trips...');
    tripHistory.innerHTML = '<div class="loading-spinner"><i class="fas fa-spinner fa-spin"></i> Loading trips...</div>';
    
    fetch('../api/get_driver_trips.php?limit=5&_=' + new Date().getTime())
        .then(response => response.json())
        .then(data => {
            console.log('Trips data received:', data);
            
            if (data.success) {
                if (data.trips && data.trips.length > 0) {
                    let html = '';
                    data.trips.forEach(trip => {
                        let statusColor = '#6b7280';
                        let statusBg = '#6b728020';
                        let icon = 'truck';
                        let displayStatus = trip.shipment_status;

                        // FIXED STATUS MAPPING
                        if (trip.shipment_status === 'completed') {
                            statusColor = '#10b981'; // Green
                            statusBg = '#10b98120';
                            icon = 'check-circle';
                            displayStatus = 'Completed';
                        }
                        else if (trip.shipment_status === 'delivered') {
                            statusColor = '#3b82f6'; // Blue
                            statusBg = '#3b82f620';
                            icon = 'check-circle';
                            displayStatus = 'Delivered (Returning)';
                        } 
                        else if (trip.shipment_status === 'awaiting_verification') {
                            statusColor = '#f59e0b'; // Orange
                            statusBg = '#f59e0b20';
                            icon = 'clock';
                            displayStatus = 'Awaiting Verification';
                        } 
                        else if (trip.shipment_status === 'in_transit' || trip.shipment_status === 'in-progress') {
                            statusColor = '#f59e0b'; // Orange
                            statusBg = '#f59e0b20';
                            icon = 'play-circle';
                            displayStatus = 'In Transit';
                        }
                        
                        html += `
                            <div class="trip-item" style="padding: 15px; border-bottom: 1px solid #eee; display: flex; align-items: center;">
                                <div style="width: 40px; height: 40px; border-radius: 8px; background-color: #f0f0f0; display: flex; align-items: center; justify-content: center; margin-right: 15px;">
                                    <i class="fas fa-${icon}" style="color: #2563eb;"></i>
                                </div>
                                <div style="flex: 1;">
                                    <div style="font-weight: 600;">${trip.customer_name || 'Trip'}</div>
                                    <div style="font-size: 13px; color: #666;">
                                        <span>${trip.vehicle_name || 'Unknown'}</span> • 
                                        <span>${trip.departure_time || 'N/A'}</span>
                                    </div>
                                </div>
                                <div>
                                    <span style="padding: 4px 12px; border-radius: 20px; font-size: 12px; background-color: ${statusBg}; color: ${statusColor};">
                                        ${displayStatus}
                                    </span>
                                </div>
                            </div>
                        `;
                    });
                    tripHistory.innerHTML = html;
                } else {
                    tripHistory.innerHTML = `
                        <div style="text-align: center; padding: 40px; background-color: #f8f9fa; border-radius: 8px;">
                            <i class="fas fa-info-circle" style="font-size: 48px; color: #17a2b8; margin-bottom: 16px;"></i>
                            <h3 style="color: #17a2b8; margin-bottom: 10px;">No Trip History</h3>
                            <p style="color: #6c757d;">You don't have any trip history yet.</p>
                        </div>
                    `;
                }
            }
        })
        .catch(error => {
            console.error('Error loading trips:', error);
            tripHistory.innerHTML = '<div class="error-state">Failed to load trips</div>';
        });
}

function loadDriverStats() {
    fetch('../api/get_driver_stats.php')
        .then(response => response.json())
        .then(data => {
            const statsDiv = document.getElementById('driver-stats');
            if (!statsDiv) return;
            
            if (data.success) {
                const stats = data.stats;
                statsDiv.innerHTML = `
                    <div class="stat-mini">
                        <div class="value">${stats.total_trips}</div>
                        <div class="label">Total Trips</div>
                    </div>
                    <div class="stat-mini">
                        <div class="value">${stats.completed_trips}</div>
                        <div class="label">Completed</div>
                    </div>
                    <div class="stat-mini">
                        <div class="value">${stats.performance_score}%</div>
                        <div class="label">Performance</div>
                    </div>
                `;
            }
        });
}

// ============================================
// VEHICLE RETURN VERIFICATION FUNCTIONS
// ============================================
function loadPendingVerifications() {
    console.log('🔄 Loading pending verifications...');
    
    const container = document.getElementById('verification-list') || 
                      document.querySelector('.verification-list');
    
    if (!container) {
        console.error('❌ Verification container not found');
        return;
    }
    
    container.innerHTML = '<div style="text-align: center; padding: 30px;"><i class="fas fa-spinner fa-spin"></i> Loading...</div>';
    
    fetch('../api/get_pending_verifications.php?_=' + new Date().getTime())
        .then(response => response.json())
        .then(data => {
            console.log('📦 API Data received:', data);
            
            if (data.success) {
                if (data.verifications && data.verifications.length > 0) {
                    let html = '';
                    data.verifications.forEach(v => {
                        html += `
                            <div class="verification-item" style="display: flex; justify-content: space-between; align-items: center; padding: 15px; border-bottom: 1px solid #f0f0f0;">
                                <div>
                                    <strong>${v.asset_name}</strong> - Driver: ${v.driver_name}
                                    <div style="font-size: 12px; color: #64748b;">Requested: ${v.requested_time}</div>
                                </div>
                                <div>
                                    <button onclick="verifyReturn(${v.id}, true)" style="background-color: #10b981; color: white; border: none; padding: 8px 15px; border-radius: 4px; margin-right: 5px; cursor: pointer;">
                                        <i class="fas fa-check"></i> Verify
                                    </button>
                                    <button onclick="verifyReturn(${v.id}, false)" style="background-color: #ef4444; color: white; border: none; padding: 8px 15px; border-radius: 4px; cursor: pointer;">
                                        <i class="fas fa-times"></i> Reject
                                    </button>
                                </div>
                            </div>
                        `;
                    });
                    container.innerHTML = html;
                } else {
                    container.innerHTML = `
                        <div style="text-align: center; padding: 30px; color: #94a3b8;">
                            <i class="fas fa-check-circle" style="font-size: 40px; margin-bottom: 10px;"></i>
                            <p>No vehicles pending verification</p>
                        </div>
                    `;
                }
            } else {
                // Show the actual error from the API
                container.innerHTML = `
                    <div style="color: #ef4444; padding: 15px; background: #fee2e2; border-radius: 8px;">
                        <strong>Error:</strong> ${data.error || 'Unknown error'}<br>
                        <small style="color: #666;">Check console for details</small>
                    </div>
                `;
            }
        })
        .catch(error => {
            console.error('❌ Error loading verifications:', error);
            container.innerHTML = '<div style="color: #ef4444; padding: 15px;">Error: ' + error.message + '</div>';
        });
}

function verifyReturn(scheduleId, approved) {
    console.log('Verifying return:', scheduleId, approved ? 'APPROVE' : 'REJECT');
    
    let message = approved ? 
        'Confirm vehicle return? This will mark the vehicle as available.' : 
        'Reject return request? Please provide a reason.';
    
    if (!approved) {
        const reason = prompt('Reason for rejection:');
        if (reason === null) return;
        
        fetch('../api/verify_vehicle_return.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                schedule_id: scheduleId,
                approved: false,
                reason: reason
            })
        })
        .then(response => response.json())
        .then(data => {
            console.log('✅ Response data:', data); // ADD THIS LINE
            if (data.success) {
                showNotification('Return request rejected', 'info');
                loadPendingVerifications();
                loadVehicleAvailability();
            } else {
                showNotification('Error: ' + (data.error || 'Unknown error'), 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showNotification('Error processing request', 'error');
        });
    } else {
        if (!confirm(message)) return;
        
        fetch('../api/verify_vehicle_return.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                schedule_id: scheduleId,
                approved: true
            })
        })
        .then(response => response.json())
        .then(data => {
            console.log('✅ Response data:', data); // ADD THIS LINE
            if (data.success) {
                showNotification('Vehicle return verified! Vehicle is now available.', 'success');
                loadPendingVerifications();
                loadVehicleAvailability();
            } else {
                showNotification('Error: ' + (data.error || 'Unknown error'), 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showNotification('Error processing request', 'error');
        });
    }
}
function updateStatus(newStatus) {
    console.log('🔵 updateStatus called with:', newStatus);
    
    // Get the button that was clicked
    const clickedButton = event?.target;
    const originalText = clickedButton ? clickedButton.innerHTML : '';
    
    // Disable the button immediately to prevent double-clicking
    if (clickedButton) {
        clickedButton.disabled = true;
        clickedButton.style.opacity = '0.5';
        clickedButton.style.cursor = 'not-allowed';
    }
    
    // First, check what assignments exist for this driver
    fetch('../api/get_driver_assignment.php?_=' + new Date().getTime())
        .then(response => {
            console.log('📡 Response status:', response.status);
            return response.json();
        })
        .then(data => {
            console.log('📦 Full assignment data:', data);
            
            if (data.success) {
                if (data.has_assignment) {
                    const assignment = data.assignment;
                    console.log('✅ Assignment found:', assignment);
                    console.log('Assignment ID:', assignment.id);
                    console.log('Current status:', assignment.shipment_status);
                    
                    // Continue with the update...
                    let currentLocation = prompt('Enter current location:');
                    if (!currentLocation) {
                        // Re-enable button if user cancels prompt
                        if (clickedButton) {
                            clickedButton.disabled = false;
                            clickedButton.style.opacity = '1';
                            clickedButton.style.cursor = 'pointer';
                        }
                        return;
                    }
                    
                    let dispatchStatus = '';
                    let message = '';
                    
                    if (newStatus === 'in_transit') {
                        dispatchStatus = 'in-progress';
                        message = 'Trip started!';
                    } 
                    else if (newStatus === 'delivered') {
                        dispatchStatus = 'delivered';
                        message = 'Goods delivered! Return to dispatch center.';
                    }
                    else if (newStatus === 'returned') {
                        dispatchStatus = 'awaiting_verification';
                        message = 'Return requested. Waiting for dispatcher verification.';
                    }
                    
                    console.log('📤 Sending to API:', {
                        schedule_id: assignment.id,
                        status: dispatchStatus,
                        current_location: currentLocation
                    });
                    
                    fetch('../api/update_dispatch_status.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({
                            schedule_id: assignment.id,
                            status: dispatchStatus,
                            current_location: currentLocation
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        console.log('📥 Update API Response:', data);
                        if (data.success) {
                            showNotification(message, 'success');
                            loadDriverAssignment();
                            loadDriverTrips();
                        } else {
                            showNotification('Error: ' + (data.error || 'Unknown error'), 'error');
                            // Re-enable button on error
                            if (clickedButton) {
                                clickedButton.disabled = false;
                                clickedButton.style.opacity = '1';
                                clickedButton.style.cursor = 'pointer';
                            }
                        }
                    })
                    .catch(error => {
                        console.error('❌ Fetch error:', error);
                        showNotification('Error: ' + error.message, 'error');
                        // Re-enable button on error
                        if (clickedButton) {
                            clickedButton.disabled = false;
                            clickedButton.style.opacity = '1';
                            clickedButton.style.cursor = 'pointer';
                        }
                    });
                } else {
                    console.log('❌ No active assignment found:', data);
                    showNotification('No active assignment found', 'error');
                    // Re-enable button
                    if (clickedButton) {
                        clickedButton.disabled = false;
                        clickedButton.style.opacity = '1';
                        clickedButton.style.cursor = 'pointer';
                    }
                }
            } else {
                console.log('❌ API returned error:', data.error);
                showNotification('Error: ' + (data.error || 'Unknown error'), 'error');
                // Re-enable button
                if (clickedButton) {
                    clickedButton.disabled = false;
                    clickedButton.style.opacity = '1';
                    clickedButton.style.cursor = 'pointer';
                }
            }
        })
        .catch(error => {
            console.error('❌ Error getting assignment:', error);
            showNotification('Error: ' + error.message, 'error');
            // Re-enable button
            if (clickedButton) {
                clickedButton.disabled = false;
                clickedButton.style.opacity = '1';
                clickedButton.style.cursor = 'pointer';
            }
        });
}

function updateLocation() {
    fetch('../api/get_driver_assignment.php')
        .then(response => response.json())
        .then(data => {
            if (data.success && data.has_assignment) {
                const assignment = data.assignment;
                const assignmentId = assignment.id;
                
                const currentLocation = prompt('Enter current location:');
                if (!currentLocation) return;
                
                // ✅ FIX: Send ONLY the location name, not the timestamp
                // Let PHP add the timestamp with the correct timezone
                
                fetch('../api/update_dispatch_location.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        schedule_id: assignmentId,
                        current_location: currentLocation // Just the location name
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showNotification('Location updated!', 'success');
                        loadDriverAssignment();
                    }
                });
            }
        });
}
// ============================================
// TRAINING MODAL FUNCTIONS
// ============================================

function openTrainingModal(trainingId) {
    console.log('🔵 Opening training modal for ID:', trainingId);
    
    const modal = document.getElementById('trainingModal');
    if (!modal) {
        console.error('❌ Training modal not found!');
        alert('Training modal not found. Please refresh the page.');
        return;
    }
    
    // Set the training ID
    document.getElementById('trainingId').value = trainingId;
    
    // Reset the form
    document.getElementById('trainingStatus').value = 'in_progress';
    document.getElementById('trainingNotes').value = '';
    
    // Show the modal
    modal.classList.remove('modal-hidden');
}

function closeTrainingModal() {
    console.log('Closing training modal');
    const modal = document.getElementById('trainingModal');
    if (modal) {
        modal.classList.add('modal-hidden');
        document.getElementById('trainingForm').reset();
    }
}

async function submitTrainingReview() {
    console.log('📤 Submitting training review...');
    
    const trainingId = document.getElementById('trainingId').value;
    const status = document.getElementById('trainingStatus').value;
    const notes = document.getElementById('trainingNotes').value;

    if (!trainingId || !status) {
        showNotification('Please select a status', 'error');
        return;
    }

    // Show loading on button
    const submitBtn = document.querySelector('#trainingForm button[type="submit"]');
    const originalText = submitBtn ? submitBtn.innerHTML : 'Submit Review';
    if (submitBtn) {
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Submitting...';
        submitBtn.disabled = true;
    }

    try {
        const response = await fetch('../api/training_review.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                training_id: trainingId,
                status: status,
                notes: notes
            })
        });

        const result = await response.json();
        console.log('📥 Review response:', result);

        if (result.success) {
            showNotification('Training review submitted successfully', 'success');
            closeTrainingModal();
            // Reload the page after a short delay to show updated data
            setTimeout(() => location.reload(), 1500);
        } else {
            showNotification(result.error || 'Error submitting review', 'error');
            if (submitBtn) {
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
            }
        }
    } catch (error) {
        console.error('❌ Error:', error);
        showNotification('Error connecting to server', 'error');
        if (submitBtn) {
            submitBtn.innerHTML = originalText;
            submitBtn.disabled = false;
        }
    }
}

// ============================================
// RESERVATION FUNCTIONS
// ============================================
function openReservationModal() {
    const modal = document.getElementById('reservationModal');
    if (!modal) return;
    
    console.log('Opening reservation modal...');
    
    // Reset form
    const form = document.getElementById('reservationForm');
    if (form) form.reset();
    
    // Set default dates (Manila time)
    const now = new Date();
    const manilaTime = new Date(now.toLocaleString('en-US', { timeZone: 'Asia/Manila' }));
    const twoHoursLater = new Date(manilaTime.getTime() + 2 * 60 * 60 * 1000);
    
    const formatDateForInput = (date) => {
        const year = date.getFullYear();
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const day = String(date.getDate()).padStart(2, '0');
        const hours = String(date.getHours()).padStart(2, '0');
        const minutes = String(date.getMinutes()).padStart(2, '0');
        return `${year}-${month}-${day}T${hours}:${minutes}`;
    };
    
    const fromDate = document.getElementById('fromDate');
    const toDate = document.getElementById('toDate');
    
    if (fromDate) fromDate.value = formatDateForInput(manilaTime);
    if (toDate) toDate.value = formatDateForInput(twoHoursLater);
    
    // Load available vehicles
    loadAvailableVehicles();
    
    modal.style.display = 'flex';
}

function closeReservationModal() {
    const modal = document.getElementById('reservationModal');
    if (modal) modal.style.display = 'none';
}

function submitReservation(event) {
    event.preventDefault();
    
    // Get form elements
    const vehicleSelect = document.getElementById('vehicleSelect');
    const customer_name = document.getElementById('customer_name');
    const department = document.getElementById('department');
    const delivery_address = document.getElementById('delivery_address');
    const purpose = document.getElementById('purpose');
    const fromDate = document.getElementById('fromDate');
    const toDate = document.getElementById('toDate');
    const submitBtn = event.target.querySelector('button[type="submit"]');
    
    // Validate
    if (!vehicleSelect.value) {
        showNotification('Please select a vehicle', 'error');
        return;
    }
    
    if (!customer_name.value.trim()) {
        showNotification('Please enter customer name', 'error');
        return;
    }
    
    if (!delivery_address.value.trim()) {
        showNotification('Please enter delivery address', 'error');
        return;
    }
    
    if (!purpose.value.trim()) {
        showNotification('Please enter a purpose for the reservation', 'error');
        return;
    }
    
    const from = new Date(fromDate.value);
    const to = new Date(toDate.value);
    
    if (from >= to) {
        showNotification('End date must be after start date', 'error');
        return;
    }
    
    // Check if reservation is at least 1 hour
    const diffHours = (to - from) / (1000 * 60 * 60);
    if (diffHours < 1) {
        showNotification('Reservation must be at least 1 hour', 'error');
        return;
    }
    
    // Prepare data
    const formData = {
        vehicle_id: vehicleSelect.value,
        customer_name: customer_name.value.trim(),
        department: department.value.trim() || 'Not specified',
        delivery_address: delivery_address.value.trim(),
        purpose: purpose.value.trim(),
        from_date: fromDate.value,
        to_date: toDate.value
    };
    
    console.log('Submitting reservation:', formData);
    
    // Show loading state
    const originalText = submitBtn.innerHTML;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Submitting...';
    submitBtn.disabled = true;
    
    // Submit
    fetch('../api/create_reservation.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(formData)
    })
    .then(response => {
        if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
        return response.json();
    })
    .then(data => {
        console.log('Reservation response:', data);
        
        if (data.success) {
            showNotification('Reservation submitted successfully!', 'success');
            closeReservationModal();
            
            // Refresh relevant data
            loadVehicleReservations();
            loadVehicleAvailability();
            
            // If user is admin/dispatcher, also refresh pending approvals
            const userRole = document.body.dataset.userRole;
            if (userRole === 'admin' || userRole === 'dispatcher') {
                loadDispatchSchedule();
            }
        } else {
            showNotification('Error: ' + (data.error || 'Unknown error'), 'error');
        }
    })
    .catch(error => {
        console.error('Submit error:', error);
        showNotification('Error: ' + error.message, 'error');
    })
    .finally(() => {
        submitBtn.innerHTML = originalText;
        submitBtn.disabled = false;
    });
}

function checkVehicleAvailability() {
    const vehicleId = document.getElementById('vehicleSelect').value;
    const fromDate = document.getElementById('fromDate').value;
    const toDate = document.getElementById('toDate').value;
    
    if (!vehicleId || !fromDate || !toDate) return;
    
    fetch('../api/check_vehicle_availability.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            vehicle_id: vehicleId,
            from_date: fromDate,
            to_date: toDate
        })
    })
    .then(response => response.json())
    .then(data => {
        const availabilityMsg = document.getElementById('availabilityMessage');
        if (!availabilityMsg) return;
        
        if (data.available) {
            availabilityMsg.innerHTML = '<i class="fas fa-check-circle"></i> Vehicle is available for this time';
            availabilityMsg.style.color = '#10b981';
            document.querySelector('#reservationForm button[type="submit"]').disabled = false;
        } else {
            availabilityMsg.innerHTML = '<i class="fas fa-exclamation-circle"></i> Vehicle is not available for this time';
            availabilityMsg.style.color = '#ef4444';
            document.querySelector('#reservationForm button[type="submit"]').disabled = true;
        }
    })
    .catch(error => console.error('Availability check error:', error));
}

// Add event listeners for real-time checking
document.addEventListener('DOMContentLoaded', function() {
    const fromDate = document.getElementById('fromDate');
    const toDate = document.getElementById('toDate');
    const vehicleSelect = document.getElementById('vehicleSelect');
    
    if (fromDate && toDate && vehicleSelect) {
        [fromDate, toDate, vehicleSelect].forEach(element => {
            element.addEventListener('change', checkVehicleAvailability);
        });
    }
});
// ============================================
// RESERVATION FILTER FUNCTIONS
// ============================================

// Store all reservations globally (if not already declared)
if (typeof allReservations === 'undefined') {
    var allReservations = [];
}

// Load vehicles into filter dropdown
function loadReservationVehicleFilter() {
    const vehicleFilter = document.getElementById('reservation-vehicle-filter');
    if (!vehicleFilter) return;
    
    fetch('../api/get_vehicles.php?_=' + new Date().getTime())
        .then(response => response.json())
        .then(data => {
            if (data.success && data.vehicles && data.vehicles.length > 0) {
                vehicleFilter.innerHTML = '<option value="all">All Vehicles</option>';
                
                data.vehicles.forEach(vehicle => {
                    const option = document.createElement('option');
                    option.value = vehicle.asset_name;
                    option.textContent = vehicle.asset_name;
                    vehicleFilter.appendChild(option);
                });
            }
        })
        .catch(error => console.error('Error loading vehicles:', error));
}

// Filter function
function filterReservations() {
    const statusFilter = document.getElementById('reservation-status-filter').value;
    const vehicleFilter = document.getElementById('reservation-vehicle-filter').value;
    
    const filtered = allReservations.filter(res => {
        if (statusFilter !== 'all' && res.status !== statusFilter) return false;
        if (vehicleFilter !== 'all' && res.vehicle_name !== vehicleFilter) return false;
        return true;
    });
    
    // Re-render using your existing display function
    if (typeof displayReservations === 'function') {
        displayReservations(filtered);
    }
}


function loadVehicleReservations() {
    console.log('Loading vehicle reservations...');
    
    // Try multiple possible selectors
    const possibleSelectors = [
        '#tab-reservations .card-full .reservation-list',
        '#tab-reservations .reservation-list',
        '#tab-reservations .card-body .reservation-list'
    ];
    
    let reservationList = null;
    for (let selector of possibleSelectors) {
        reservationList = document.querySelector(selector);
        if (reservationList) {
            console.log('Found reservation list with selector:', selector);
            break;
        }
    }
    
    if (!reservationList) {
        console.warn('Reservation list container not found. Available elements in tab-reservations:');
        const tab = document.getElementById('tab-reservations');
        if (tab) {
            console.log(tab.innerHTML.substring(0, 500) + '...');
        } else {
            console.warn('tab-reservations element not found');
        }
        return;
    }
    
    // Show loading state
    reservationList.innerHTML = '<div class="loading-spinner"><i class="fas fa-spinner fa-spin"></i> Loading reservations...</div>';
    
    fetch('../api/get_reservations.php?_=' + new Date().getTime())
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            console.log('Reservations data received:', data);
            
            if (data.success && data.reservations) {
                // ✅ MOVE THIS LINE HERE - inside the success block where 'data' exists
                allReservations = data.reservations || [];
                
                console.log(`Displaying ${data.reservations.length} reservations`);
                displayReservations(data.reservations);
                updateReservationStats(data.reservations);
            } else {
                console.warn('No reservations data:', data);
                reservationList.innerHTML = '<div class="empty-state">No reservations found</div>';
            }
        })
        .catch(error => {
            console.error('Error loading reservations:', error);
            reservationList.innerHTML = '<div class="error-state">Failed to load reservations: ' + error.message + '</div>';
        });
}
function displayReservations(reservations) {
    console.log('Displaying reservations:', reservations);
    
    // Get current filter values
    const statusFilter = document.getElementById('reservation-status-filter').value;
    const vehicleFilter = document.getElementById('reservation-vehicle-filter').value;
    
    // Get user role from body dataset
    const userRole = document.body.dataset.userRole;
    const canApproveReject = userRole === 'admin' || userRole === 'dispatcher';
    
    console.log('Current user role:', userRole, 'Can approve/reject:', canApproveReject);
    
    // Find the main reservations container
    const allReservationsContainer = document.querySelector('#tab-reservations .card-full .reservation-list');
    
    // Find approved and rejected containers - MORE SPECIFIC SELECTORS
    const approvedContainer = document.querySelector('#tab-reservations .dashboard-grid .card:first-child .reservation-list');
    const rejectedContainer = document.querySelector('#tab-reservations .dashboard-grid .card:last-child .reservation-list');
    
    // Debug output - check if containers are found
    console.log('✅ All reservations container:', allReservationsContainer);
    console.log('✅ Approved container:', approvedContainer);
    console.log('✅ Rejected container:', rejectedContainer);
    
    if (!allReservationsContainer) {
        console.error('❌ Could not find main reservations container');
        return;
    }
    
    // Filter reservations by status
    const approved = reservations.filter(r => r.status === 'approved');
    const rejected = reservations.filter(r => r.status === 'rejected');
    const pending = reservations.filter(r => r.status === 'pending');
    
    console.log(`Stats - Total: ${reservations.length}, Approved: ${approved.length}, Rejected: ${rejected.length}, Pending: ${pending.length}`);
    
    // Update the badges in the card headers
    document.querySelectorAll('#tab-reservations .card-badge').forEach(badge => {
        const cardHeader = badge.closest('.card-header');
        if (cardHeader) {
            const headerText = cardHeader.textContent || '';
            if (headerText.includes('Approved')) {
                badge.textContent = approved.length + ' this week';
            } else if (headerText.includes('Rejected')) {
                badge.textContent = rejected.length + ' this week';
            }
        }
    });
    
    // Display all reservations in the main list
    if (reservations.length === 0) {
        // Custom message based on filters
        let emptyMessage = '';
        let emptyIcon = 'fa-calendar-times';
        
        if (statusFilter === 'pending') {
            emptyMessage = 'No pending reservations';
            emptyIcon = 'fa-clock';
        } else if (statusFilter === 'rejected') {
            emptyMessage = 'No rejected reservations';
            emptyIcon = 'fa-times-circle';
        } else if (statusFilter === 'approved') {
            emptyMessage = 'No approved reservations';
            emptyIcon = 'fa-check-circle';
        } else if (statusFilter === 'all' && vehicleFilter !== 'all') {
            emptyMessage = `No reservations for ${vehicleFilter}`;
            emptyIcon = 'fa-truck';
        } else if (statusFilter !== 'all' && vehicleFilter !== 'all') {
            emptyMessage = `No ${statusFilter} reservations for ${vehicleFilter}`;
            emptyIcon = 'fa-filter';
        } else {
            emptyMessage = 'No reservations found';
            emptyIcon = 'fa-calendar-times';
        }
        
        allReservationsContainer.innerHTML = `
            <div style="text-align: center; padding: 40px; color: #94a3b8;">
                <i class="fas ${emptyIcon}" style="font-size: 48px; margin-bottom: 15px;"></i>
                <p style="font-size: 16px; margin-bottom: 20px;">${emptyMessage}</p>
                ${(statusFilter !== 'all' || vehicleFilter !== 'all') ? `
                    <button onclick="resetFilters()" style="background: #3b82f6; color: white; border: none; padding: 8px 16px; border-radius: 4px; cursor: pointer;">
                        <i class="fas fa-times"></i> Clear Filters
                    </button>
                ` : ''}
            </div>
        `;
    } else {
        let allHtml = '';
        reservations.forEach(res => {
            const fromDisplay = res.from || 'N/A';
            const toDisplay = res.to || 'N/A';
            
            // Determine status styling
            let statusColor, statusIcon, statusBg;
            if (res.status === 'approved') {
                statusColor = '#10b981';
                statusIcon = 'check-circle';
                statusBg = '#10b98120';
            } else if (res.status === 'rejected') {
                statusColor = '#ef4444';
                statusIcon = 'times-circle';
                statusBg = '#ef444420';
            } else if (res.status === 'pending') {
                statusColor = '#f59e0b';
                statusIcon = 'clock';
                statusBg = '#f59e0b20';
            } else {
                statusColor = '#6b7280';
                statusIcon = 'question-circle';
                statusBg = '#6b728020';
            }
            
            allHtml += `
                <div class="reservation-item" style="display: flex; align-items: center; justify-content: space-between; padding: 15px; border-bottom: 1px solid #f0f0f0;">
                    <div style="flex: 2;">
                        <div style="font-weight: 600; font-size: 16px; margin-bottom: 5px;">
                            ${res.vehicle_name || 'Unknown Vehicle'}
                        </div>
                        <div style="font-size: 13px; color: #64748b; margin-bottom: 3px;">
                            <i class="fas fa-user"></i> ${res.requester || 'Unknown'} • ${res.department || 'N/A'}
                        </div>
                        <div style="font-size: 13px; color: #2563eb; margin-bottom: 3px;">
                            <i class="fas fa-calendar"></i> ${fromDisplay} - ${toDisplay}
                        </div>
                        <div style="font-size: 13px; color: #666;">
                            <i class="fas fa-align-left"></i> ${res.purpose || 'No purpose specified'}
                        </div>
                        ${res.notes ? `<div style="font-size: 12px; color: #999; margin-top: 5px;"><i class="fas fa-sticky-note"></i> ${res.notes}</div>` : ''}
                    </div>
                    <div style="flex: 1; text-align: right;">
                        <span style="display: inline-block; padding: 6px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; background-color: ${statusBg}; color: ${statusColor};">
                            <i class="fas fa-${statusIcon}"></i> ${res.status.toUpperCase()}
                        </span>
                        ${res.status === 'pending' ? `
                            <div style="margin-top: 10px;">
                                ${canApproveReject ? `
                                    <button onclick="approveReservation(${res.id})" class="btn-small" style="background-color: #10b981; color: white; border: none; padding: 5px 10px; border-radius: 4px; margin-right: 5px; cursor: pointer;">
                                        <i class="fas fa-check"></i> Approve
                                    </button>
                                    <button onclick="rejectReservation(${res.id})" class="btn-small" style="background-color: #ef4444; color: white; border: none; padding: 5px 10px; border-radius: 4px; cursor: pointer;">
                                        <i class="fas fa-times"></i> Reject
                                    </button>
                                ` : `
                                    <span style="display: inline-block; padding: 5px 10px; background-color: #f3f4f6; color: #9ca3af; border-radius: 4px; font-size: 12px;">
                                        <i class="fas fa-clock"></i> Pending approval
                                    </span>
                                `}
                            </div>
                        ` : ''}
                    </div>
                </div>
            `;
        });
        allReservationsContainer.innerHTML = allHtml;
    }
    
    // Display approved reservations in the approved card
    if (approvedContainer) {
        if (approved.length === 0) {
            approvedContainer.innerHTML = '<div class="empty-state">No approved reservations</div>';
        } else {
            let approvedHtml = '';
            approved.forEach(res => {
                approvedHtml += `
                    <div style="padding: 12px; border-bottom: 1px solid #f0f0f0;">
                        <div style="font-weight: 500;">${res.vehicle_name || 'Unknown'}</div>
                        <div style="font-size: 12px; color: #666;">
                            <i class="fas fa-user"></i> ${res.requester || 'Unknown'} • 
                            <i class="fas fa-calendar"></i> ${res.from || 'N/A'}
                        </div>
                    </div>
                `;
            });
            approvedContainer.innerHTML = approvedHtml;
        }
    }
    
    // Display rejected reservations in the rejected card
    if (rejectedContainer) {
        if (rejected.length === 0) {
            rejectedContainer.innerHTML = '<div class="empty-state">No rejected reservations</div>';
        } else {
            let rejectedHtml = '';
            rejected.forEach(res => {
                rejectedHtml += `
                    <div style="padding: 12px; border-bottom: 1px solid #f0f0f0;">
                        <div style="font-weight: 500;">${res.vehicle_name || 'Unknown'}</div>
                        <div style="font-size: 12px; color: #666;">
                            <i class="fas fa-user"></i> ${res.requester || 'Unknown'} • 
                            <i class="fas fa-calendar"></i> ${res.from || 'N/A'}
                        </div>
                    </div>
                `;
            });
            rejectedContainer.innerHTML = rejectedHtml;
        }
    }
}
function resetFilters() {
    document.getElementById('reservation-status-filter').value = 'all';
    document.getElementById('reservation-vehicle-filter').value = 'all';
    
    // Reload all reservations
    if (allReservations.length > 0) {
        displayReservations(allReservations);
    } else {
        loadVehicleReservations();
    }
}
// ============================================
// EMERGENCY BREAKDOWN REPORTING (for drivers)
// ============================================
function reportEmergencyBreakdown() {
    console.log('🚨 Driver reporting emergency breakdown');
    
    // First, get current assignment to know which vehicle
    fetch('../api/get_driver_assignment.php')
        .then(response => response.json())
        .then(data => {
            console.log('Assignment data for emergency:', data);
            
            if (data.success && data.has_assignment) {
                const assignment = data.assignment;
                
                // Now get the correct vehicle_id from assets table using vehicle_name
                fetch(`../api/get_vehicle_id.php?name=${encodeURIComponent(assignment.vehicle_name)}`)
                    .then(response => response.json())
                    .then(vehicleData => {
                        if (vehicleData.success) {
                            assignment.correct_vehicle_id = vehicleData.vehicle_id;
                            showEmergencyBreakdownModal(assignment);
                        } else {
                            // If we can't get the ID, still try with the original ID
                            showEmergencyBreakdownModal(assignment);
                        }
                    })
                    .catch(error => {
                        console.error('Error getting vehicle ID:', error);
                        showEmergencyBreakdownModal(assignment);
                    });
            } else {
                showNotification('No active assignment found. You can only report emergencies while on a trip.', 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showNotification('Error checking assignment', 'error');
        });
}
function showEmergencyBreakdownModal(assignment) {
    // Create modal if it doesn't exist
    let modal = document.getElementById('emergencyBreakdownModal');
    if (!modal) {
        modal = document.createElement('div');
        modal.id = 'emergencyBreakdownModal';
        modal.className = 'modal';
        modal.innerHTML = `
            <div class="modal-content" style="max-width: 500px;">
                <div class="modal-header" style="background: #dc2626; color: white;">
                    <h3><i class="fas fa-exclamation-triangle"></i> Report Emergency Breakdown</h3>
                    <button class="modal-close" onclick="closeEmergencyBreakdownModal()" style="color: white;">&times;</button>
                </div>
                <div class="modal-body" style="padding: 20px;">
                    <form id="emergencyBreakdownForm" onsubmit="submitEmergencyBreakdown(event)">
                        <input type="hidden" id="emergencyVehicleId" value="">
                        <input type="hidden" id="emergencyVehicleName" value="">
                        <input type="hidden" id="emergencyCorrectVehicleId" value="">
                        
                        <div style="background: #fef2f2; padding: 15px; border-radius: 8px; margin-bottom: 20px; border-left: 4px solid #dc2626;">
                            <strong>Vehicle:</strong> <span id="displayVehicleName"></span><br>
                            <strong>Current Location:</strong> <span id="displayCurrentLocation"></span>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Issue Type *</label>
                            <select id="emergencyIssueType" class="form-control" required>
                                <option value="">Select issue type...</option>
                                <option value="critical">🚨 Complete Breakdown - Cannot move</option>
                                <option value="major">🔧 Engine Problem - Major issue</option>
                                <option value="major">🛞 Flat Tire / Tire Issue</option>
                                <option value="critical">⛔ Brake Failure - Cannot drive</option>
                                <option value="major">⚡ Electrical Issue</option>
                                <option value="critical">💥 Accident Damage</option>
                                <option value="minor">🔩 Minor Issue - Can continue slowly</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Priority *</label>
                            <select id="emergencyPriority" class="form-control" required>
                                <option value="high" selected>🔴 HIGH - Cannot continue trip</option>
                                <option value="medium">🟠 MEDIUM - Can continue slowly</option>
                                <option value="low">🟡 LOW - Can complete trip but needs attention</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Description of Problem *</label>
                            <textarea id="emergencyDescription" class="form-control" rows="3" required 
                                placeholder="Describe what happened, any unusual sounds, warning lights, etc..."></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Exact Location *</label>
                            <input type="text" id="emergencyLocation" class="form-control" required 
                                placeholder="e.g., KM 50 North Expressway, near gas station, landmark">
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Can you continue driving?</label>
                            <select id="emergencyCanDrive" class="form-control">
                                <option value="no">❌ No - Vehicle cannot move</option>
                                <option value="yes">⚠️ Yes - But need assistance soon</option>
                            </select>
                        </div>
                        
                        <div style="background: #ffedd5; padding: 12px; border-radius: 8px; margin: 15px 0; font-size: 13px; border-left: 4px solid #f97316;">
                            <i class="fas fa-info-circle" style="color: #c2410c;"></i> 
                            <strong>Important:</strong> Fleet manager will be notified immediately. A mechanic will be dispatched to your location. Stay with your vehicle.
                        </div>
                        
                        <div class="modal-footer">
                            <button type="button" class="btn btn-outline" onclick="closeEmergencyBreakdownModal()">Cancel</button>
                            <button type="submit" class="btn btn-danger">
                                <i class="fas fa-exclamation-triangle"></i> Report Emergency
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        `;
        document.body.appendChild(modal);
    }
    
    // Fill in vehicle info
    document.getElementById('emergencyVehicleId').value = assignment.id || assignment.vehicle_id;
    document.getElementById('emergencyVehicleName').value = assignment.vehicle_name;
    document.getElementById('emergencyCorrectVehicleId').value = assignment.correct_vehicle_id || assignment.vehicle_id || assignment.id;
    document.getElementById('displayVehicleName').textContent = assignment.vehicle_name;
    document.getElementById('displayCurrentLocation').textContent = assignment.current_location || 'Unknown';
    
    // Show modal
    modal.style.display = 'flex';
}

function closeEmergencyBreakdownModal() {
    const modal = document.getElementById('emergencyBreakdownModal');
    if (modal) {
        modal.style.display = 'none';
        document.getElementById('emergencyBreakdownForm').reset();
    }
}

function submitEmergencyBreakdown(event) {
    event.preventDefault();
    console.log('📤 Submitting emergency breakdown...');
    
    // Use the correct vehicle ID that exists in assets table
    const vehicleId = document.getElementById('emergencyCorrectVehicleId').value || 
                      document.getElementById('emergencyVehicleId').value;
    
    const formData = {
        vehicle_id: vehicleId,  // This should now be a valid ID from assets table
        vehicle_name: document.getElementById('emergencyVehicleName').value,
        issue_type: document.getElementById('emergencyIssueType').value,
        priority: document.getElementById('emergencyPriority').value,
        description: document.getElementById('emergencyDescription').value,
        location: document.getElementById('emergencyLocation').value,
        can_drive: document.getElementById('emergencyCanDrive').value
    };
    
    console.log('Form data being sent:', formData);
    
    // Validate
    if (!formData.issue_type || !formData.description || !formData.location) {
        alert('Please fill all required fields');
        return;
    }
    
    // Show loading
    const submitBtn = event.target.querySelector('button[type="submit"]');
    const originalText = submitBtn.innerHTML;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Reporting...';
    submitBtn.disabled = true;
    
    // Submit to API
    fetch('../api/report_emergency_breakdown.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(formData)
    })
    .then(response => response.json())
    .then(data => {
        console.log('Response:', data);
        if (data.success) {
            alert('✅ EMERGENCY REPORTED! Mechanic will be dispatched to your location.');
            closeEmergencyBreakdownModal();
            loadDriverAssignment();
        } else {
            alert('❌ Error: ' + (data.error || 'Unknown error'));
        }
    })
    .catch(error => {
        console.error('❌ Error:', error);
        alert('Error reporting emergency: ' + error.message);
    })
    .finally(() => {
        submitBtn.innerHTML = originalText;
        submitBtn.disabled = false;
    });
}
// ============================================
// EMERGENCY BREAKDOWN FUNCTIONS
// ============================================
function loadEmergencyBreakdowns() {
    console.log('🚨 Loading emergency breakdowns...');
    
    const breakdownList = document.getElementById('breakdown-list');
    const breakdownCount = document.getElementById('breakdown-count');
    
    if (!breakdownList) {
        console.log('Breakdown list element not found - check HTML');
        return;
    }
    
    breakdownList.innerHTML = '<div style="text-align: center; padding: 30px;"><i class="fas fa-spinner fa-spin"></i> Loading emergencies...</div>';
    
    fetch('../api/get_emergency_breakdowns.php?_=' + new Date().getTime())
        .then(response => response.json())
        .then(data => {
            console.log('📦 Breakdown data:', data);
            
            if (data.success) {
                if (breakdownCount) {
                    breakdownCount.textContent = data.active_count || 0;
                }
                
                if (data.breakdowns && data.breakdowns.length > 0) {
                    let html = '';
                    data.breakdowns.forEach(breakdown => {
                        // Determine color based on priority
                        let priorityColor, priorityBg;
                        if (breakdown.priority === 'high') {
                            priorityColor = '#dc2626';
                            priorityBg = '#fee2e2';
                        } else if (breakdown.priority === 'medium') {
                            priorityColor = '#f97316';
                            priorityBg = '#ffedd5';
                        } else {
                            priorityColor = '#3b82f6';
                            priorityBg = '#dbeafe';
                        }
                        
                        // Format time
                        const reportTime = new Date(breakdown.reported_at).toLocaleString();
                        
                        html += `
                            <div style="background: white; border: 1px solid #f0f0f0; border-left: 4px solid ${priorityColor}; border-radius: 8px; padding: 15px; margin-bottom: 15px; box-shadow: 0 2px 4px rgba(0,0,0,0.05);">
                                <div style="display: flex; justify-content: space-between; align-items: start;">
                                    <div style="flex: 1;">
                                        <div style="display: flex; gap: 10px; margin-bottom: 10px; flex-wrap: wrap;">
                                            <span style="background: ${priorityBg}; color: ${priorityColor}; padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: 600;">
                                                <i class="fas fa-exclamation-triangle"></i> ${breakdown.priority.toUpperCase()} PRIORITY
                                            </span>
                                            <span style="background: #f3f4f6; color: #4b5563; padding: 4px 12px; border-radius: 20px; font-size: 12px;">
                                                ${breakdown.issue_type}
                                            </span>
                                            <span style="background: #e0f2fe; color: #0369a1; padding: 4px 12px; border-radius: 20px; font-size: 12px;">
                                                <i class="fas fa-${breakdown.can_drive === 'yes' ? 'check' : 'times'}"></i> 
                                                Can Drive: ${breakdown.can_drive}
                                            </span>
                                        </div>
                                        
                                        <h3 style="margin: 0 0 10px 0; font-size: 18px; color: #1e293b;">${breakdown.vehicle_name}</h3>
                                        
                                        <p style="margin: 0 0 15px 0; color: #4b5563; background: #f8fafc; padding: 10px; border-radius: 6px;">
                                            <i class="fas fa-quote-left" style="color: #94a3b8; margin-right: 5px;"></i>
                                            ${breakdown.description}
                                        </p>
                                        
                                        <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 10px; margin-bottom: 10px;">
                                            <div>
                                                <div style="font-size: 11px; color: #64748b;">Driver</div>
                                                <div style="font-weight: 500;">${breakdown.driver_name || 'Unknown'} 
                                                    ${breakdown.driver_phone ? `<span style="font-size: 11px; color: #3b82f6;">(${breakdown.driver_phone})</span>` : ''}
                                                </div>
                                            </div>
                                            <div>
                                                <div style="font-size: 11px; color: #64748b;">Location</div>
                                                <div style="font-weight: 500;">${breakdown.location}</div>
                                            </div>
                                            <div>
                                                <div style="font-size: 11px; color: #64748b;">Reported</div>
                                                <div style="font-weight: 500;">${reportTime}</div>
                                            </div>
                                            <div>
                                                <div style="font-size: 11px; color: #64748b;">Status</div>
                                                <div style="font-weight: 500; text-transform: capitalize;">${breakdown.status}</div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div style="min-width: 150px; text-align: right;">
                                       <button class="btn btn-danger" onclick="assignMechanicToBreakdown(${breakdown.id})" style="width: 100%; margin-bottom: 5px; background-color: #dc2626;">
    <i class="fas fa-user-plus"></i> Assign Mechanic to Breakdown
</button>
                                        <button class="btn btn-outline" onclick="viewBreakdownDetails(${breakdown.id})" style="width: 100%;">
                                            <i class="fas fa-eye"></i> Details
                                        </button>
                                    </div>
                                </div>
                            </div>
                        `;
                    });
                    breakdownList.innerHTML = html;
                } else {
                    breakdownList.innerHTML = `
                        <div style="text-align: center; padding: 40px; color: #94a3b8;">
                            <i class="fas fa-check-circle" style="font-size: 48px; color: #10b981; margin-bottom: 15px;"></i>
                            <p style="font-size: 16px; font-weight: 500;">No active road emergencies</p>
                            <p style="font-size: 13px;">All vehicles are running smoothly</p>
                        </div>
                    `;
                }
            } else {
                breakdownList.innerHTML = `<div class="error-state">Error: ${data.error}</div>`;
            }
        })
        .catch(error => {
            console.error('Error:', error);
            breakdownList.innerHTML = '<div class="error-state">Failed to load emergencies</div>';
        });
}
// ============================================
// BREAKDOWN MECHANIC ASSIGNMENT FUNCTIONS
// ============================================

function assignMechanicToBreakdown(breakdownId) {
    console.log('🔧 Opening assign mechanic modal for breakdown ID:', breakdownId);
    
    // Fetch breakdown details first
    fetch('../api/get_breakdown_details.php?id=' + breakdownId)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showAssignMechanicBreakdownModal(data.breakdown);
            } else {
                showNotification('Error loading breakdown details', 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showNotification('Error loading breakdown details', 'error');
        });
}

function showAssignMechanicBreakdownModal(breakdown) {
    const modal = document.getElementById('assignMechanicBreakdownModal');
    if (!modal) {
        console.error('Assign mechanic breakdown modal not found');
        return;
    }
    
    // Set breakdown details
    document.getElementById('breakdownId').value = breakdown.id;
    document.getElementById('breakdownVehicle').textContent = breakdown.vehicle_name;
    document.getElementById('breakdownLocation').textContent = breakdown.location;
    document.getElementById('breakdownDriver').textContent = breakdown.driver_name + ' (' + (breakdown.driver_phone || 'No phone') + ')';
    document.getElementById('breakdownIssue').textContent = breakdown.description;
    
    // Load available mechanics
    loadAvailableMechanicsForBreakdown();
    
    modal.style.display = 'flex';
}

function closeAssignMechanicBreakdownModal() {
    const modal = document.getElementById('assignMechanicBreakdownModal');
    if (modal) {
        modal.style.display = 'none';
        document.getElementById('assignMechanicBreakdownForm').reset();
    }
}

function loadAvailableMechanicsForBreakdown() {
    const mechanicSelect = document.getElementById('breakdownMechanicSelect');
    if (!mechanicSelect) return;
    
    mechanicSelect.innerHTML = '<option value="">Loading mechanics...</option>';
    
    fetch('../api/get_available_mechanics.php')
        .then(response => response.json())
        .then(data => {
            if (data.success && data.mechanics.length > 0) {
                let options = '<option value="">Select a mechanic</option>';
                data.mechanics.forEach(mech => {
                    options += `<option value="${mech.id}">${mech.full_name} - ${mech.specialization || 'General'}</option>`;
                });
                mechanicSelect.innerHTML = options;
            } else {
                mechanicSelect.innerHTML = '<option value="">No mechanics available</option>';
            }
        })
        .catch(error => {
            console.error('Error loading mechanics:', error);
            mechanicSelect.innerHTML = '<option value="">Error loading mechanics</option>';
        });
}

function submitMechanicAssignment(event) {
    event.preventDefault();
    
    const breakdownId = document.getElementById('breakdownId').value;
    const mechanicId = document.getElementById('breakdownMechanicSelect').value;
    const estimatedArrival = document.getElementById('breakdownEstimatedArrival').value;
    const instructions = document.getElementById('breakdownInstructions').value;
    
    if (!mechanicId) {
        showNotification('Please select a mechanic', 'error');
        return;
    }
    
    // Show loading
    const submitBtn = event.target.querySelector('button[type="submit"]');
    const originalText = submitBtn.innerHTML;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Assigning...';
    submitBtn.disabled = true;
    
    fetch('../api/assign_mechanic_to_breakdown.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            breakdown_id: breakdownId,
            assigned_mechanic: mechanicId,
            estimated_arrival: estimatedArrival,
            notes: instructions
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification('✅ Mechanic assigned to breakdown successfully!', 'success');
            closeAssignMechanicBreakdownModal();
            // Refresh the breakdowns list
            loadEmergencyBreakdowns();
        } else {
            showNotification('Error: ' + data.error, 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('Error assigning mechanic', 'error');
    })
    .finally(() => {
        submitBtn.innerHTML = originalText;
        submitBtn.disabled = false;
    });
}

// ============================================
// MECHANIC FUNCTIONS - EMERGENCY BREAKDOWNS
// ============================================
// ============================================
// UNIFIED TASK DETAILS FUNCTION
// ============================================

function viewTaskDetails(taskId, taskType) {
    console.log(`📋 Viewing ${taskType} details:`, taskId);
    
    const modal = document.getElementById('taskDetailsModal');
    const container = document.getElementById('taskDetailsContainer');
    const actionBtn = document.getElementById('taskActionBtn');
    const modalTitle = document.getElementById('modalTitle');
    const userRole = document.body.dataset.userRole;
    
    if (!modal || !container) {
        console.error('Task details modal not found');
        return;
    }
    
    // Show loading
    container.innerHTML = `
        <div style="text-align: center; padding: 60px;">
            <i class="fas fa-spinner fa-spin" style="font-size: 48px; color: #2563eb;"></i>
            <p style="margin-top: 20px; color: #64748b;">Loading task details...</p>
        </div>
    `;
    
    modal.style.display = 'flex';
    
    if (taskType === 'breakdown') {
        modalTitle.innerHTML = '<i class="fas fa-exclamation-triangle"></i> Emergency Breakdown Details';
        fetch('../api/get_breakdown_details.php?id=' + taskId)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    displayTaskDetails(data.breakdown, 'breakdown', userRole);
                } else {
                    container.innerHTML = `<div class="error-state">Error: ${data.error}</div>`;
                }
            })
            .catch(error => {
                container.innerHTML = `<div class="error-state">Error loading details</div>`;
            });
    } else if (taskType === 'maintenance') {
        modalTitle.innerHTML = '<i class="fas fa-wrench"></i> Maintenance Task Details';
        fetch('../api/get_maintenance_details.php?id=' + taskId)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    displayTaskDetails(data.data, 'maintenance', userRole);
                } else {
                    container.innerHTML = `<div class="error-state">Error: ${data.error}</div>`;
                }
            })
            .catch(error => {
                container.innerHTML = `<div class="error-state">Error loading details</div>`;
            });
    }
}

function displayTaskDetails(task, type, userRole) {
    const container = document.getElementById('taskDetailsContainer');
    const actionBtn = document.getElementById('taskActionBtn');
    
    if (type === 'breakdown') {
        // Format dates
        const reportedDate = new Date(task.reported_at).toLocaleString();
        const assignedDate = task.assigned_at ? new Date(task.assigned_at).toLocaleString() : 'Not yet';
        
        // Determine priority color
        let priorityColor, priorityBg;
        if (task.priority === 'high') {
            priorityColor = '#dc2626';
            priorityBg = '#fee2e2';
        } else if (task.priority === 'medium') {
            priorityColor = '#f97316';
            priorityBg = '#ffedd5';
        } else {
            priorityColor = '#3b82f6';
            priorityBg = '#dbeafe';
        }
        
        // Set action button based on role and status
        actionBtn.style.display = 'none';
        if (userRole === 'mechanic') {
            if (task.status === 'assigned') {
                actionBtn.style.display = 'block';
                actionBtn.innerHTML = '<i class="fas fa-play"></i> Start Work';
                actionBtn.className = 'btn btn-primary';
                actionBtn.onclick = () => startBreakdownWork(task.id);
            } else if (task.status === 'in_progress') {
                actionBtn.style.display = 'block';
                actionBtn.innerHTML = '<i class="fas fa-check"></i> Mark Resolved';
                actionBtn.className = 'btn btn-success';
                actionBtn.onclick = () => completeBreakdownWork(task.id);
            }
        } else if (userRole === 'fleet_manager' || userRole === 'admin') {
            if (task.status === 'reported') {
                actionBtn.style.display = 'block';
                actionBtn.innerHTML = '<i class="fas fa-user-plus"></i> Assign Mechanic';
                actionBtn.className = 'btn btn-danger';
                actionBtn.onclick = () => assignMechanicToBreakdown(task.id);
            }
        }
        
        container.innerHTML = `
            <div style="padding: 20px;">
                <!-- Header -->
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; padding-bottom: 15px; border-bottom: 2px solid #fee2e2;">
                    <span style="background: ${priorityBg}; color: ${priorityColor}; padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: 600;">
                        <i class="fas fa-flag"></i> ${task.priority.toUpperCase()} PRIORITY
                    </span>
                    <span style="background: ${task.status === 'reported' ? '#dc2626' : task.status === 'assigned' ? '#f97316' : '#3b82f6'}; color: white; padding: 4px 12px; border-radius: 20px; font-size: 12px; text-transform: uppercase;">
                        ${task.status.replace('_', ' ')}
                    </span>
                </div>
                
                <!-- Vehicle Info -->
                <div style="background: #f8fafc; border-radius: 12px; padding: 20px; margin-bottom: 15px;">
                    <h4 style="margin: 0 0 10px 0; font-size: 16px; color: #1e293b;">
                        <i class="fas fa-truck" style="color: #2563eb;"></i> Vehicle
                    </h4>
                    <div style="font-size: 20px; font-weight: 700;">${task.vehicle_name}</div>
                </div>
                
                <!-- Two Column Grid for different users -->
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 15px;">
                    <!-- Driver Info (shown to both) -->
                    <div style="background: #f8fafc; border-radius: 12px; padding: 15px;">
                        <div style="font-size: 12px; color: #64748b; margin-bottom: 8px;">
                            <i class="fas fa-user" style="color: #10b981;"></i> Driver
                        </div>
                        <div style="font-weight: 600;">${task.driver_name || 'Unknown'}</div>
                        ${task.driver_phone ? `
                            <div style="font-size: 14px; color: #2563eb; margin-top: 5px;">
                                <i class="fas fa-phone"></i> ${task.driver_phone}
                            </div>
                        ` : ''}
                    </div>
                    
                    <!-- Location (shown to both) -->
                    <div style="background: #f8fafc; border-radius: 12px; padding: 15px;">
                        <div style="font-size: 12px; color: #64748b; margin-bottom: 8px;">
                            <i class="fas fa-map-marker-alt" style="color: #ef4444;"></i> Location
                        </div>
                        <div>${task.location}</div>
                    </div>
                </div>
                
                <!-- Issue Description -->
                <div style="background: #f8fafc; border-radius: 12px; padding: 15px; margin-bottom: 15px;">
                    <h4 style="margin: 0 0 10px 0; font-size: 16px; color: #1e293b;">
                        <i class="fas fa-exclamation-circle" style="color: #f97316;"></i> Issue
                    </h4>
                    <div style="background: white; padding: 15px; border-radius: 8px; border: 1px solid #e2e8f0;">
                        ${task.description}
                    </div>
                    <div style="display: flex; gap: 10px; margin-top: 10px;">
                        <span style="background: #f1f5f9; padding: 4px 12px; border-radius: 20px; font-size: 13px;">
                            <i class="fas fa-tag"></i> ${task.issue_type}
                        </span>
                        <span style="background: #f1f5f9; padding: 4px 12px; border-radius: 20px; font-size: 13px;">
                            <i class="fas fa-${task.can_drive === 'yes' ? 'check' : 'times'}"></i> 
                            Can Drive: ${task.can_drive === 'yes' ? 'Yes' : 'No'}
                        </span>
                    </div>
                </div>
                
                <!-- Timeline -->
                <div style="background: #f8fafc; border-radius: 12px; padding: 15px;">
                    <h4 style="margin: 0 0 10px 0; font-size: 16px; color: #1e293b;">
                        <i class="fas fa-clock" style="color: #6b7280;"></i> Timeline
                    </h4>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                        <div>
                            <div style="font-size: 12px; color: #64748b;">Reported</div>
                            <div style="font-weight: 500;">${reportedDate}</div>
                        </div>
                        <div>
                            <div style="font-size: 12px; color: #64748b;">Assigned</div>
                            <div style="font-weight: 500;">${assignedDate}</div>
                        </div>
                    </div>
                    ${userRole === 'fleet_manager' || userRole === 'admin' ? `
                        <div style="margin-top: 10px; padding-top: 10px; border-top: 1px solid #e2e8f0;">
                            <div style="font-size: 12px; color: #64748b;">Assigned Mechanic</div>
                            <div style="font-weight: 500;">${task.mechanic_name || 'Not assigned'}</div>
                        </div>
                    ` : ''}
                </div>
                
                ${task.resolution_notes ? `
                    <div style="background: #f0fdf4; border-radius: 12px; padding: 15px; margin-top: 15px;">
                        <h4 style="margin: 0 0 10px 0; font-size: 16px; color: #166534;">
                            <i class="fas fa-check-circle"></i> Resolution Notes
                        </h4>
                        <p style="margin: 0;">${task.resolution_notes}</p>
                    </div>
                ` : ''}
            </div>
        `;
        
    } else if (type === 'maintenance') {
        // Similar structure for maintenance tasks
        // ... (I'll add this in a follow-up response if needed)
    }
}

function closeTaskDetailsModal() {
    const modal = document.getElementById('taskDetailsModal');
    if (modal) {
        modal.style.display = 'none';
    }
}
function loadMyEmergencyBreakdowns() {
    console.log('🔧 Loading my emergency breakdowns...');
    
    const emergencyList = document.getElementById('my-emergency-list');
    const emergencyCount = document.getElementById('my-emergency-count');
    
    if (!emergencyList) return;
    
    emergencyList.innerHTML = '<div style="text-align: center; padding: 30px;"><i class="fas fa-spinner fa-spin"></i> Loading your emergencies...</div>';
    
    fetch('../api/get_my_breakdowns.php?_=' + new Date().getTime())
        .then(response => response.json())
        .then(data => {
            console.log('📦 My breakdowns:', data);
            
            if (data.success) {
                if (emergencyCount) {
                    emergencyCount.textContent = data.active_count;
                }
                
                if (data.breakdowns && data.breakdowns.length > 0) {
                    let html = '';
                    data.breakdowns.forEach(breakdown => {
                        // Determine priority color
                        let priorityColor, priorityBg;
                        if (breakdown.priority === 'high') {
                            priorityColor = '#dc2626';
                            priorityBg = '#fee2e2';
                        } else if (breakdown.priority === 'medium') {
                            priorityColor = '#f97316';
                            priorityBg = '#ffedd5';
                        } else {
                            priorityColor = '#3b82f6';
                            priorityBg = '#dbeafe';
                        }
                        
                        // Format times
                        const reportedTime = new Date(breakdown.reported_at).toLocaleString();
                        const assignedTime = breakdown.assigned_at ? new Date(breakdown.assigned_at).toLocaleString() : 'N/A';
                        
                        // Determine status badge
                        let statusBadge = '';
                        if (breakdown.status === 'assigned') {
                            statusBadge = '<span style="background: #f97316; color: white; padding: 4px 12px; border-radius: 20px; font-size: 12px;"><i class="fas fa-clock"></i> ASSIGNED</span>';
                        } else if (breakdown.status === 'in_progress') {
                            statusBadge = '<span style="background: #3b82f6; color: white; padding: 4px 12px; border-radius: 20px; font-size: 12px;"><i class="fas fa-play"></i> IN PROGRESS</span>';
                        }
                        
                        html += `
                            <div style="background: white; border: 1px solid #f0f0f0; border-left: 4px solid ${priorityColor}; border-radius: 8px; padding: 20px; margin-bottom: 15px;">
                                <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 15px;">
                                    <div>
                                        <span style="background: ${priorityBg}; color: ${priorityColor}; padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; margin-right: 10px;">
                                            <i class="fas fa-exclamation-triangle"></i> ${breakdown.priority.toUpperCase()} PRIORITY
                                        </span>
                                        ${statusBadge}
                                    </div>
                                    <span style="color: #64748b; font-size: 12px;">
                                        <i class="fas fa-hashtag"></i> #${breakdown.id}
                                    </span>
                                </div>
                                
                                <h3 style="margin: 0 0 15px 0; font-size: 20px; color: #1e293b;">${breakdown.vehicle_name}</h3>
                                
                                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 15px;">
                                    <div>
                                        <div style="font-size: 12px; color: #64748b; margin-bottom: 4px;">Issue</div>
                                        <div style="font-weight: 500;">${breakdown.description}</div>
                                    </div>
                                    <div>
                                        <div style="font-size: 12px; color: #64748b; margin-bottom: 4px;">Location</div>
                                        <div style="font-weight: 500;">${breakdown.location}</div>
                                    </div>
                                    <div>
                                        <div style="font-size: 12px; color: #64748b; margin-bottom: 4px;">Driver</div>
                                        <div style="font-weight: 500;">${breakdown.driver_name || 'Unknown'} 
                                            ${breakdown.driver_phone ? `<span style="font-size: 11px; color: #3b82f6;">(${breakdown.driver_phone})</span>` : ''}
                                        </div>
                                    </div>
                                    <div>
                                        <div style="font-size: 12px; color: #64748b; margin-bottom: 4px;">Reported</div>
                                        <div style="font-weight: 500;">${reportedTime}</div>
                                    </div>
                                </div>
                                
                                <div style="background: #f8fafc; padding: 15px; border-radius: 8px; margin: 15px 0;">
                                    <div style="display: flex; gap: 20px; font-size: 13px;">
                                        <div><strong>Assigned:</strong> ${assignedTime}</div>
                                        <div><strong>Can Drive:</strong> ${breakdown.can_drive === 'yes' ? 'Yes' : 'No'}</div>
                                        <div><strong>Issue Type:</strong> ${breakdown.issue_type}</div>
                                    </div>
                                </div>
                                
                                <div style="display: flex; gap: 10px; margin-top: 15px;">
                                    ${breakdown.status === 'assigned' ? `
                                        <button class="btn btn-primary" onclick="startBreakdownWork(${breakdown.id})" style="flex: 1;">
                                            <i class="fas fa-play"></i> Start Work
                                        </button>
                                    ` : ''}
                                    ${breakdown.status === 'in_progress' ? `
                                        <button class="btn btn-success" onclick="completeBreakdownWork(${breakdown.id})" style="flex: 1;">
                                            <i class="fas fa-check"></i> Mark Resolved
                                        </button>
                                    ` : ''}
                                   <button class="btn btn-secondary" onclick="viewTaskDetails(${breakdown.id}, 'breakdown')" style="flex: 1;">
        <i class="fas fa-eye"></i> Details
    </button>
                                </div>
                            </div>
                        `;
                    });
                    emergencyList.innerHTML = html;
                } else {
                    emergencyList.innerHTML = `
                        <div style="text-align: center; padding: 40px; color: #94a3b8;">
                            <i class="fas fa-check-circle" style="font-size: 48px; color: #10b981; margin-bottom: 15px;"></i>
                            <p style="font-size: 16px; font-weight: 500;">No emergency assignments</p>
                            <p style="font-size: 13px;">You're all caught up!</p>
                        </div>
                    `;
                }
            }
        })
        .catch(error => {
            console.error('Error:', error);
            emergencyList.innerHTML = '<div class="error-state">Failed to load emergencies</div>';
        });
}


function startBreakdownWork(breakdownId) {
    if (!confirm('Start working on this breakdown?')) return;
    
    fetch('../api/update_breakdown_status.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            breakdown_id: breakdownId,
            action: 'start'
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification('Started working on breakdown', 'success');
            loadMyEmergencyBreakdowns();
            loadMechanicTasks();
        } else {
            showNotification('Error: ' + data.error, 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('Error starting work', 'error');
    });
}

function completeBreakdownWork(breakdownId) {
    const notes = prompt('Enter resolution notes / work completed:');
    if (notes === null) return;
    
    fetch('../api/update_breakdown_status.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            breakdown_id: breakdownId,
            action: 'complete',
            notes: notes
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification('Breakdown resolved!', 'success');
            loadMyEmergencyBreakdowns();
            loadMechanicTasks();
            loadMechanicActivity();
        } else {
            showNotification('Error: ' + data.error, 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('Error completing work', 'error');
    });
}

function updateMechanicStatus() {
    // Toggle mechanic availability (if you have this feature)
    showNotification('Status updated', 'success');
}
// ============================================
// BREAKDOWN DETAILS FUNCTIONS
// ============================================

function viewBreakdownDetails(breakdownId) {
    console.log('🔍 Viewing breakdown details for ID:', breakdownId);
    
    // Show loading in modal first
    const modal = document.getElementById('viewBreakdownModal');
    const container = document.getElementById('breakdownDetailsContainer');
    
    if (!modal || !container) {
        console.error('View breakdown modal not found');
        return;
    }
    
    container.innerHTML = `
        <div style="text-align: center; padding: 40px;">
            <i class="fas fa-spinner fa-spin" style="font-size: 40px; color: #3b82f6;"></i>
            <p style="margin-top: 15px; color: #64748b;">Loading breakdown details...</p>
        </div>
    `;
    
    modal.style.display = 'flex';
    
    // Fetch breakdown details
    fetch('../api/get_breakdown_details.php?id=' + breakdownId)
        .then(response => response.json())
        .then(data => {
            console.log('📦 Breakdown details:', data);
            
            if (data.success) {
                displayBreakdownDetails(data.breakdown);
            } else {
                container.innerHTML = `
                    <div style="text-align: center; padding: 40px; color: #ef4444;">
                        <i class="fas fa-exclamation-triangle" style="font-size: 48px; margin-bottom: 15px;"></i>
                        <p>Error loading breakdown details</p>
                        <p style="font-size: 12px;">${data.error || 'Unknown error'}</p>
                    </div>
                `;
            }
        })
        .catch(error => {
            console.error('Error:', error);
            container.innerHTML = `
                <div style="text-align: center; padding: 40px; color: #ef4444;">
                    <i class="fas fa-exclamation-triangle" style="font-size: 48px; margin-bottom: 15px;"></i>
                    <p>Error connecting to server</p>
                </div>
            `;
        });
}

function displayBreakdownDetails(breakdown) {
    const container = document.getElementById('breakdownDetailsContainer');
    const assignBtn = document.getElementById('assignFromDetailsBtn');
    
    // Store breakdown ID for assignment button
    assignBtn.setAttribute('data-breakdown-id', breakdown.id);
    
    // Format dates
    const reportedDate = new Date(breakdown.reported_at).toLocaleString();
    
    // Determine status color
    let statusColor = '#6b7280';
    if (breakdown.status === 'reported') statusColor = '#dc2626';
    else if (breakdown.status === 'assigned') statusColor = '#f97316';
    else if (breakdown.status === 'in_progress') statusColor = '#3b82f6';
    else if (breakdown.status === 'resolved') statusColor = '#10b981';
    
    // Determine priority display
    let priorityBadge = '';
    if (breakdown.priority === 'high') {
        priorityBadge = '<span style="background: #fee2e2; color: #dc2626; padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: 600;"><i class="fas fa-exclamation-triangle"></i> HIGH PRIORITY</span>';
    } else if (breakdown.priority === 'medium') {
        priorityBadge = '<span style="background: #ffedd5; color: #f97316; padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: 600;"><i class="fas fa-exclamation-circle"></i> MEDIUM PRIORITY</span>';
    } else {
        priorityBadge = '<span style="background: #dbeafe; color: #3b82f6; padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: 600;"><i class="fas fa-info-circle"></i> LOW PRIORITY</span>';
    }
    
    container.innerHTML = `
        <div style="margin-bottom: 20px;">
            <!-- Status Badge -->
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                ${priorityBadge}
                <span style="background: ${statusColor}20; color: ${statusColor}; padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; text-transform: uppercase;">
                    ${breakdown.status.replace('_', ' ')}
                </span>
            </div>
            
            <!-- Vehicle & Driver Info Card -->
            <div style="background: #f8fafc; border-radius: 12px; padding: 20px; margin-bottom: 20px; border: 1px solid #e2e8f0;">
                <h4 style="margin: 0 0 15px 0; font-size: 16px; color: #1e293b; display: flex; align-items: center; gap: 8px;">
                    <i class="fas fa-truck" style="color: #3b82f6;"></i> Vehicle Information
                </h4>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                    <div>
                        <div style="font-size: 12px; color: #64748b; margin-bottom: 4px;">Vehicle Name</div>
                        <div style="font-weight: 600; font-size: 16px;">${breakdown.vehicle_name}</div>
                    </div>
                    <div>
                        <div style="font-size: 12px; color: #64748b; margin-bottom: 4px;">Vehicle ID</div>
                        <div style="font-weight: 500;">#${breakdown.vehicle_id}</div>
                    </div>
                </div>
            </div>
            
            <!-- Driver Info Card -->
            <div style="background: #f8fafc; border-radius: 12px; padding: 20px; margin-bottom: 20px; border: 1px solid #e2e8f0;">
                <h4 style="margin: 0 0 15px 0; font-size: 16px; color: #1e293b; display: flex; align-items: center; gap: 8px;">
                    <i class="fas fa-user" style="color: #10b981;"></i> Driver Information
                </h4>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                    <div>
                        <div style="font-size: 12px; color: #64748b; margin-bottom: 4px;">Driver Name</div>
                        <div style="font-weight: 600;">${breakdown.driver_name || 'Unknown'}</div>
                    </div>
                    <div>
                        <div style="font-size: 12px; color: #64748b; margin-bottom: 4px;">Contact Number</div>
                        <div style="font-weight: 500;">
                            ${breakdown.driver_phone ? 
                                `<a href="tel:${breakdown.driver_phone}" style="color: #3b82f6; text-decoration: none;">${breakdown.driver_phone}</a>` : 
                                'Not provided'}
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Location Card -->
            <div style="background: #f8fafc; border-radius: 12px; padding: 20px; margin-bottom: 20px; border: 1px solid #e2e8f0;">
                <h4 style="margin: 0 0 15px 0; font-size: 16px; color: #1e293b; display: flex; align-items: center; gap: 8px;">
                    <i class="fas fa-map-marker-alt" style="color: #ef4444;"></i> Location Details
                </h4>
                <div>
                    <div style="font-size: 12px; color: #64748b; margin-bottom: 4px;">Exact Location</div>
                    <div style="font-weight: 500; background: white; padding: 10px; border-radius: 6px; border: 1px solid #e2e8f0;">
                        ${breakdown.location}
                    </div>
                </div>
            </div>
            
            <!-- Issue Description Card -->
            <div style="background: #f8fafc; border-radius: 12px; padding: 20px; margin-bottom: 20px; border: 1px solid #e2e8f0;">
                <h4 style="margin: 0 0 15px 0; font-size: 16px; color: #1e293b; display: flex; align-items: center; gap: 8px;">
                    <i class="fas fa-exclamation-circle" style="color: #f97316;"></i> Issue Description
                </h4>
                <div style="background: white; padding: 15px; border-radius: 6px; border: 1px solid #e2e8f0;">
                    <p style="margin: 0 0 10px 0; line-height: 1.5;">${breakdown.description}</p>
                    <div style="display: flex; gap: 15px; margin-top: 10px; font-size: 13px;">
                        <span style="background: #f1f5f9; padding: 4px 12px; border-radius: 20px;">
                            <i class="fas fa-tag"></i> ${breakdown.issue_type}
                        </span>
                        <span style="background: #f1f5f9; padding: 4px 12px; border-radius: 20px;">
                            <i class="fas fa-${breakdown.can_drive === 'yes' ? 'check' : 'times'}"></i> 
                            Can Drive: ${breakdown.can_drive}
                        </span>
                    </div>
                </div>
            </div>
            
            <!-- Timeline Card -->
            <div style="background: #f8fafc; border-radius: 12px; padding: 20px; border: 1px solid #e2e8f0;">
                <h4 style="margin: 0 0 15px 0; font-size: 16px; color: #1e293b; display: flex; align-items: center; gap: 8px;">
                    <i class="fas fa-clock" style="color: #6b7280;"></i> Timeline
                </h4>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                    <div>
                        <div style="font-size: 12px; color: #64748b; margin-bottom: 4px;">Reported At</div>
                        <div style="font-weight: 500;">${reportedDate}</div>
                    </div>
                    ${breakdown.assigned_at ? `
                    <div>
                        <div style="font-size: 12px; color: #64748b; margin-bottom: 4px;">Assigned At</div>
                        <div style="font-weight: 500;">${new Date(breakdown.assigned_at).toLocaleString()}</div>
                    </div>
                    ` : ''}
                    ${breakdown.assigned_mechanic ? `
                    <div>
                        <div style="font-size: 12px; color: #64748b; margin-bottom: 4px;">Assigned Mechanic</div>
                        <div style="font-weight: 500;">Loading...</div>
                    </div>
                    ` : ''}
                </div>
            </div>
        </div>
    `;
   
}

function closeViewBreakdownModal() {
    const modal = document.getElementById('viewBreakdownModal');
    if (modal) {
        modal.style.display = 'none';
    }
}

function assignFromDetails() {
    const assignBtn = document.getElementById('assignFromDetailsBtn');
    const breakdownId = assignBtn.getAttribute('data-breakdown-id');
    
    if (breakdownId) {
        closeViewBreakdownModal();
        // Call the assign mechanic function with this breakdown ID
        assignMechanicToBreakdown(breakdownId);
    }
}

// ============================================
// VEHICLE FILTER FUNCTIONS - FIXED VERSION
// ============================================

// Store all vehicles globally
let allVehicles = [];

// Function to load vehicles with filters
function loadVehicleAvailability() {
    const vehicleList = document.querySelector('.vehicle-list');
    if (!vehicleList) return;
    
    vehicleList.innerHTML = '<div class="loading-spinner"><i class="fas fa-spinner fa-spin"></i> Loading vehicles...</div>';
    
    fetch('../api/get_vehicles.php?_=' + new Date().getTime())
        .then(response => {
            if (!response.ok) throw new Error('Network response was not ok');
            return response.json();
        })
        .then(data => {
            console.log('📦 Vehicle data received:', data);
            
            if (data.success && data.vehicles && data.vehicles.length > 0) {
                // Store all vehicles globally
                allVehicles = data.vehicles;
                console.log(`Loaded ${allVehicles.length} vehicles`);
                applyVehicleFilters(); // Apply filters on load
            } else {
                vehicleList.innerHTML = '<div class="empty-state">No vehicles available</div>';
            }
        })
        .catch(error => {
            console.error('Error loading vehicles:', error);
            vehicleList.innerHTML = '<div class="error-state">Failed to load vehicles</div>';
        });
}

// Function to determine vehicle status
function getVehicleStatus(vehicle) {
    // Check if has pending maintenance
    if (vehicle.has_pending_maintenance) {
        return 'maintenance';
    }
    // Check if in use
    else if (vehicle.is_in_use) {
        return 'in-use';
    }
    // Otherwise available
    else {
        return 'available';
    }
}

// Function to apply vehicle filters
function applyVehicleFilters() {
    const typeFilter = document.getElementById('vehicleTypeFilter').value;
    const statusFilter = document.getElementById('vehicleStatusFilter').value;
    const searchTerm = document.getElementById('searchFleet').value.toLowerCase().trim();
    
    console.log('Applying filters - Type:', typeFilter, 'Status:', statusFilter, 'Search:', searchTerm);
    
    // First, log all vehicles for debugging
    console.log('All vehicles:', allVehicles.map(v => ({
        name: v.asset_name,
        type: v.asset_type,
        has_maintenance: v.has_pending_maintenance,
        is_in_use: v.is_in_use,
        status: getVehicleStatus(v)
    })));
    
    const filtered = allVehicles.filter(vehicle => {
        // TYPE FILTER
        if (typeFilter !== 'all') {
            const vehicleType = (vehicle.asset_type || '').toLowerCase();
            const filterType = typeFilter.toLowerCase();
            if (vehicleType !== filterType) return false;
        }
        
        // STATUS FILTER
        if (statusFilter !== 'all') {
            const actualStatus = getVehicleStatus(vehicle);
            if (statusFilter === 'good' && actualStatus !== 'available') return false;
            if (statusFilter === 'warning' && actualStatus !== 'in-use') return false;
            if (statusFilter === 'bad' && actualStatus !== 'maintenance') return false;
        }
        
        // SEARCH FILTER
        if (searchTerm) {
            const vehicleName = (vehicle.asset_name || '').toLowerCase();
            const vehicleType = (vehicle.asset_type || '').toLowerCase();
            const vehicleId = String(vehicle.id || '');
            
            return vehicleName.includes(searchTerm) || 
                   vehicleType.includes(searchTerm) ||
                   vehicleId.includes(searchTerm);
        }
        
        return true;
    });
    
    console.log(`Filtered from ${allVehicles.length} to ${filtered.length} vehicles`);
    
    // Count by status for debugging
    const available = filtered.filter(v => getVehicleStatus(v) === 'available').length;
    const inUse = filtered.filter(v => getVehicleStatus(v) === 'in-use').length;
    const maintenance = filtered.filter(v => getVehicleStatus(v) === 'maintenance').length;
    console.log(`Filtered counts - Available: ${available}, In Use: ${inUse}, Maintenance: ${maintenance}`);
    
    displayFilteredVehicles(filtered);
}

// Function to display filtered vehicles
function displayFilteredVehicles(vehicles) {
    const vehicleList = document.querySelector('.vehicle-list');
    if (!vehicleList) return;
    
    // Get current filter values for custom message
    const typeFilter = document.getElementById('vehicleTypeFilter').value;
    const statusFilter = document.getElementById('vehicleStatusFilter').value;
    const searchTerm = document.getElementById('searchFleet').value;
    
    if (vehicles.length === 0) {
        let emptyMessage = 'No vehicles found';
        let emptyIcon = 'fa-truck';
        
        // Custom message based on filters
        if (searchTerm) {
            emptyMessage = `No vehicles matching "${searchTerm}"`;
            emptyIcon = 'fa-search';
        } else if (typeFilter !== 'all' && statusFilter !== 'all') {
            const typeText = typeFilter === 'vehicle' ? 'trucks' : 'equipment';
            const statusText = statusFilter === 'good' ? 'available' : 
                              statusFilter === 'warning' ? 'in use' : 'in maintenance';
            emptyMessage = `No ${statusText} ${typeText} found`;
            emptyIcon = 'fa-filter';
        } else if (typeFilter !== 'all') {
            emptyMessage = `No ${typeFilter === 'vehicle' ? 'trucks' : 'equipment'} found`;
            emptyIcon = typeFilter === 'vehicle' ? 'fa-truck' : 'fa-cog';
        } else if (statusFilter !== 'all') {
            const statusText = statusFilter === 'good' ? 'available' : 
                              statusFilter === 'warning' ? 'in use' : 'in maintenance';
            emptyMessage = `No ${statusText} vehicles found`;
            emptyIcon = 'fa-filter';
        }
        
        vehicleList.innerHTML = `
            <div style="text-align: center; padding: 40px; color: #94a3b8;">
                <i class="fas ${emptyIcon}" style="font-size: 48px; margin-bottom: 15px;"></i>
                <p style="font-size: 16px; margin-bottom: 20px;">${emptyMessage}</p>
                ${(typeFilter !== 'all' || statusFilter !== 'all' || searchTerm) ? `
                    <button onclick="resetVehicleFilters()" style="background: #3b82f6; color: white; border: none; padding: 8px 16px; border-radius: 4px; cursor: pointer;">
                        <i class="fas fa-times"></i> Clear Filters
                    </button>
                ` : ''}
            </div>
        `;
        return;
    }
    
    let html = '';
    vehicles.forEach(vehicle => {
        // Try to find driver name
        let driverName = vehicle.current_driver || 
                        vehicle.driver_name || 
                        vehicle.driver || 
                        vehicle.full_name || 
                        null;
        
        // Determine status based on actual data
        const actualStatus = getVehicleStatus(vehicle);
        let statusText, statusIcon, statusColor, extraInfo = '';
        
        if (actualStatus === 'maintenance') {
            statusText = 'IN MAINTENANCE';
            statusIcon = 'wrench';
            statusColor = '#ef4444';
            extraInfo = `<div style="font-size: 11px; color: #ef4444; margin-top: 5px;">
                <i class="fas fa-exclamation-triangle"></i> 
                Maintenance: ${vehicle.maintenance_issue || 'Scheduled'} 
                (Due: ${vehicle.due_date || 'N/A'})
            </div>`;
        } else if (actualStatus === 'in-use') {
            statusText = 'IN USE';
            statusIcon = 'play-circle';
            statusColor = '#f59e0b';
            extraInfo = `<div style="font-size: 11px; color: #f59e0b; margin-top: 5px;">
                <i class="fas fa-user"></i> 
                Driver: ${driverName || 'Unknown'}
            </div>`;
        } else {
            statusText = 'AVAILABLE';
            statusIcon = 'check-circle';
            statusColor = '#10b981';
        }
        
        // Get asset type display
        const assetType = vehicle.asset_type === 'vehicle' ? 'Truck' : 'Equipment';
        
        html += `
            <div class="vehicle-item" data-type="${vehicle.asset_type}" data-status="${actualStatus}">
                <div class="vehicle-info">
                    <div class="vehicle-icon">
                        <i class="fas fa-${vehicle.asset_type === 'vehicle' ? 'truck' : 'cog'}"></i>
                    </div>
                    <div class="vehicle-details">
                        <h3>${vehicle.asset_name} <span style="font-size: 12px; color: #64748b; font-weight: normal;">(${assetType})</span></h3>
                        <div class="vehicle-meta">
                            <span><i class="fas fa-plate"></i> ABC-${String(vehicle.id).padStart(4, '0')}</span>
                            <span><i class="fas fa-tachometer-alt"></i> ${vehicle.mileage || 'N/A'} km</span>
                            ${driverName ? `<span><i class="fas fa-user"></i> ${driverName}</span>` : ''}
                        </div>
                        ${extraInfo}
                    </div>
                </div>
                <div class="vehicle-status">
                    <span class="availability-badge" style="background-color: ${statusColor}20; color: ${statusColor}; padding: 6px 12px; border-radius: 20px; font-size: 12px; font-weight: 600;">
                        <i class="fas fa-${statusIcon}"></i>
                        ${statusText}
                    </span>
                </div>
                <div class="vehicle-metrics">
                    <div class="vehicle-metric">
                        <div class="value">${vehicle.fuel_level || '85'}%</div>
                        <div class="label">Fuel</div>
                    </div>
                    <div class="vehicle-metric">
                        <div class="value">${vehicle.asset_condition}%</div>
                        <div class="label">Condition</div>
                    </div>
                </div>
            </div>
        `;
    });
    
    vehicleList.innerHTML = html;
}

// Function to reset vehicle filters
function resetVehicleFilters() {
    document.getElementById('vehicleTypeFilter').value = 'all';
    document.getElementById('vehicleStatusFilter').value = 'all';
    document.getElementById('searchFleet').value = '';
    
    // Re-apply filters (which will show all vehicles)
    applyVehicleFilters();
}

// Debounced search function
const debouncedSearch = debounce(function() {
    applyVehicleFilters();
}, 300);

// Add this to your DOMContentLoaded event
document.addEventListener('DOMContentLoaded', function() {
    // ... existing code ...
    
    // Vehicle filter event listeners
    const typeFilter = document.getElementById('vehicleTypeFilter');
    const statusFilter = document.getElementById('vehicleStatusFilter');
    const searchInput = document.getElementById('searchFleet');
    
    if (typeFilter) {
        typeFilter.addEventListener('change', applyVehicleFilters);
        console.log('✅ Vehicle type filter listener added');
    }
    
    if (statusFilter) {
        statusFilter.addEventListener('change', applyVehicleFilters);
        console.log('✅ Vehicle status filter listener added');
    }
    
    if (searchInput) {
        searchInput.addEventListener('input', debouncedSearch);
        console.log('✅ Vehicle search listener added');
    }
});
function updateReservationStats(reservations) {
    const pending = reservations.filter(r => r.status === 'pending').length;
    const approved = reservations.filter(r => r.status === 'approved').length;
    const rejected = reservations.filter(r => r.status === 'rejected').length;
    console.log(`Pending: ${pending}, Approved: ${approved}, Rejected: ${rejected}`);
}

function approveReservation(resId) {
    if (!confirm('Approve this reservation? This will create a shipment.')) return;
    
    fetch('../api/update_reservation_status.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            reservation_id: resId,
            status: 'approved',
            reason: 'Approved by dispatcher'
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification('Reservation approved! Shipment created.', 'success');
            loadVehicleReservations();
            loadDispatchSchedule();
        } else {
            showNotification('Error: ' + data.error, 'error');
        }
    });
}

function rejectReservation(resId) {
    const reason = prompt('Please enter reason for rejection:');
    if (reason === null) return;
    
    fetch('../api/update_reservation_status.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            reservation_id: resId,
            status: 'rejected',
            reason: reason
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification('Reservation rejected!', 'info');
            loadVehicleReservations();
        } else {
            showNotification('Error: ' + data.error, 'error');
        }
    });
}

function viewReservation(resId) {
    console.log('Viewing reservation:', resId);
    showNotification(`Viewing reservation ${resId}`, 'info');
}

function editReservation(resId) {
    console.log('Editing reservation:', resId);
    openReservationModal(resId);
}

// ============================================
// DISPATCHER FUNCTIONS
// ============================================

function openAssignDriverModal(scheduleId, vehicle, route) {
    const modal = document.getElementById('assignDriverModal');
    if (!modal) return;
    
    document.getElementById('scheduleId').value = scheduleId;
    document.getElementById('assignVehicle').textContent = vehicle;
    document.getElementById('assignRoute').textContent = route;
    
    const driverSelect = document.getElementById('driverSelect');
    driverSelect.innerHTML = '<option value="">Loading drivers...</option>';
    
    fetch('../api/get_available_drivers.php')
        .then(response => response.json())
        .then(data => {
            if (data.success && data.drivers.length > 0) {
                let options = '<option value="">Select a driver</option>';
                data.drivers.forEach(d => {
                    options += `<option value="${d.id}">${d.full_name} (${d.employee_id})</option>`;
                });
                driverSelect.innerHTML = options;
            } else {
                driverSelect.innerHTML = '<option value="">No available drivers</option>';
            }
        })
        .catch(error => {
            console.error('Error loading drivers:', error);
            driverSelect.innerHTML = '<option value="">Error loading drivers</option>';
        });
    
    modal.style.display = 'flex';
}

function closeAssignDriverModal() {
    document.getElementById('assignDriverModal').style.display = 'none';
}

function assignDriver(event) {
    event.preventDefault();
    
    const scheduleId = document.getElementById('scheduleId').value;
    const driverId = document.getElementById('driverSelect').value;
    
    if (!driverId) {
        alert('Please select a driver');
        return;
    }
    
    fetch('../api/assign_driver_to_schedule.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            schedule_id: scheduleId,
            driver_id: driverId
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification('Driver assigned successfully!', 'success');
            closeAssignDriverModal();
            loadDispatchSchedule();
        } else {
            showNotification('Error: ' + (data.error || 'Unknown error'), 'error');
        }
    })
    .catch(error => {
        showNotification('Error: ' + error.message, 'error');
    });
}

// ============================================
// VEHICLE FUNCTIONS
// ============================================
// In your fleet.js file, update the loadAvailableVehicles function

function loadAvailableVehicles() {
    const vehicleSelect = document.getElementById('vehicleSelect');
    if (!vehicleSelect) return;
    
    vehicleSelect.innerHTML = '<option value="">Loading available vehicles...</option>';
    vehicleSelect.disabled = true;
    
    fetch('../api/get_vehicles_for_reservation.php?_=' + new Date().getTime())
        .then(response => {
            if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
            return response.json();
        })
        .then(data => {
            console.log('Available vehicles:', data);
            
            if (data.success && data.vehicles && data.vehicles.length > 0) {
                let options = '<option value="">Select a vehicle</option>';
                
                data.vehicles.forEach(v => {
                    options += `<option value="${v.id}">
                        ${v.asset_name} (Condition: ${v.asset_condition}%) 
                    </option>`;
                });
                
                vehicleSelect.innerHTML = options;
                vehicleSelect.disabled = false;
            } else {
                vehicleSelect.innerHTML = '<option value="">No vehicles available at this time</option>';
                vehicleSelect.disabled = true;
                
                // Show helpful message
                if (data.message) {
                    showNotification(data.message, 'info');
                } else {
                    showNotification('All vehicles are currently in use or under maintenance', 'info');
                }
            }
        })
        .catch(error => {
            console.error('Error loading vehicles:', error);
            vehicleSelect.innerHTML = '<option value="">Error loading vehicles</option>';
            vehicleSelect.disabled = true;
            showNotification('Failed to load vehicles. Please try again.', 'error');
        });
}

// ============================================
// MECHANIC TASK DETAILS FUNCTIONS
// ============================================

function viewMechanicTaskDetails(taskId, taskType) {
    console.log(`🔍 Viewing ${taskType} details for ID:`, taskId);
    
    const modal = document.getElementById('mechanicTaskDetailsModal');
    const container = document.getElementById('mechanicTaskDetailsContainer');
    const actionBtn = document.getElementById('mechanicTaskActionBtn');
    
    if (!modal || !container) {
        console.error('Details modal not found');
        return;
    }
    
    // Show loading
    container.innerHTML = `
        <div style="text-align: center; padding: 40px;">
            <i class="fas fa-spinner fa-spin" style="font-size: 40px; color: #3b82f6;"></i>
            <p style="margin-top: 15px; color: #64748b;">Loading task details...</p>
        </div>
    `;
    
    modal.style.display = 'flex';
    
    if (taskType === 'breakdown') {
        // Fetch breakdown details
        fetch('../api/get_breakdown_details.php?id=' + taskId)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    displayBreakdownTaskDetails(data.breakdown);
                } else {
                    container.innerHTML = `<div class="error-state">Error: ${data.error}</div>`;
                }
            })
            .catch(error => {
                container.innerHTML = `<div class="error-state">Error loading details</div>`;
            });
    } else if (taskType === 'maintenance') {
        // Fetch maintenance details
        fetch('../api/get_maintenance_details.php?id=' + taskId)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    displayMaintenanceTaskDetails(data.data);
                } else {
                    container.innerHTML = `<div class="error-state">Error: ${data.error}</div>`;
                }
            })
            .catch(error => {
                container.innerHTML = `<div class="error-state">Error loading details</div>`;
            });
    }
}

function displayBreakdownTaskDetails(breakdown) {
    const container = document.getElementById('mechanicTaskDetailsContainer');
    const actionBtn = document.getElementById('mechanicTaskActionBtn');
    
    // Format dates
    const reportedDate = new Date(breakdown.reported_at).toLocaleString();
    const assignedDate = breakdown.assigned_at ? new Date(breakdown.assigned_at).toLocaleString() : 'Not yet';
    
    // Determine status color
    let statusColor = '#6b7280';
    if (breakdown.status === 'assigned') statusColor = '#f97316';
    else if (breakdown.status === 'in_progress') statusColor = '#3b82f6';
    else if (breakdown.status === 'resolved') statusColor = '#10b981';
    
    // Set action button based on status
    if (breakdown.status === 'assigned') {
        actionBtn.style.display = 'block';
        actionBtn.innerHTML = '<i class="fas fa-play"></i> Start Work';
        actionBtn.className = 'btn btn-primary';
        actionBtn.onclick = () => startBreakdownWork(breakdown.id);
    } else if (breakdown.status === 'in_progress') {
        actionBtn.style.display = 'block';
        actionBtn.innerHTML = '<i class="fas fa-check"></i> Mark Resolved';
        actionBtn.className = 'btn btn-success';
        actionBtn.onclick = () => completeBreakdownWork(breakdown.id);
    } else {
        actionBtn.style.display = 'none';
    }
    
    container.innerHTML = `
        <div style="margin-bottom: 20px;">
            <!-- Header with type badge -->
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <span style="background: #dc2626; color: white; padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: 600;">
                    <i class="fas fa-exclamation-triangle"></i> EMERGENCY BREAKDOWN
                </span>
                <span style="background: ${statusColor}20; color: ${statusColor}; padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; text-transform: uppercase;">
                    ${breakdown.status.replace('_', ' ')}
                </span>
            </div>
            
            <!-- Vehicle Info Card -->
            <div style="background: #f8fafc; border-radius: 12px; padding: 20px; margin-bottom: 20px; border: 1px solid #e2e8f0;">
                <h4 style="margin: 0 0 15px 0; font-size: 16px; color: #1e293b;">
                    <i class="fas fa-truck" style="color: #3b82f6;"></i> Vehicle Information
                </h4>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                    <div>
                        <div style="font-size: 12px; color: #64748b;">Vehicle Name</div>
                        <div style="font-weight: 600; font-size: 18px;">${breakdown.vehicle_name}</div>
                    </div>
                    <div>
                        <div style="font-size: 12px; color: #64748b;">Vehicle ID</div>
                        <div style="font-weight: 500;">#${breakdown.vehicle_id}</div>
                    </div>
                    <div>
                        <div style="font-size: 12px; color: #64748b;">Condition</div>
                        <div style="font-weight: 500;">${breakdown.asset_condition || 'N/A'}%</div>
                    </div>
                </div>
            </div>
            
            <!-- Driver Info Card -->
            <div style="background: #f8fafc; border-radius: 12px; padding: 20px; margin-bottom: 20px; border: 1px solid #e2e8f0;">
                <h4 style="margin: 0 0 15px 0; font-size: 16px; color: #1e293b;">
                    <i class="fas fa-user" style="color: #10b981;"></i> Driver Information
                </h4>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                    <div>
                        <div style="font-size: 12px; color: #64748b;">Driver Name</div>
                        <div style="font-weight: 600;">${breakdown.driver_name || 'Unknown'}</div>
                    </div>
                    <div>
                        <div style="font-size: 12px; color: #64748b;">Contact Number</div>
                        <div style="font-weight: 500;">
                            ${breakdown.driver_phone ? 
                                `<a href="tel:${breakdown.driver_phone}" style="color: #3b82f6; text-decoration: none;">${breakdown.driver_phone}</a>` : 
                                'Not provided'}
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Location Card -->
            <div style="background: #f8fafc; border-radius: 12px; padding: 20px; margin-bottom: 20px; border: 1px solid #e2e8f0;">
                <h4 style="margin: 0 0 15px 0; font-size: 16px; color: #1e293b;">
                    <i class="fas fa-map-marker-alt" style="color: #ef4444;"></i> Location Details
                </h4>
                <div>
                    <div style="font-size: 12px; color: #64748b;">Exact Location</div>
                    <div style="font-weight: 500; background: white; padding: 10px; border-radius: 6px; border: 1px solid #e2e8f0;">
                        ${breakdown.location}
                    </div>
                </div>
            </div>
            
            <!-- Issue Description Card -->
            <div style="background: #f8fafc; border-radius: 12px; padding: 20px; margin-bottom: 20px; border: 1px solid #e2e8f0;">
                <h4 style="margin: 0 0 15px 0; font-size: 16px; color: #1e293b;">
                    <i class="fas fa-exclamation-circle" style="color: #f97316;"></i> Issue Description
                </h4>
                <div style="background: white; padding: 15px; border-radius: 6px; border: 1px solid #e2e8f0;">
                    <p style="margin: 0 0 10px 0; line-height: 1.5;">${breakdown.description}</p>
                    <div style="display: flex; gap: 15px; margin-top: 10px; font-size: 13px; flex-wrap: wrap;">
                        <span style="background: #f1f5f9; padding: 4px 12px; border-radius: 20px;">
                            <i class="fas fa-tag"></i> ${breakdown.issue_type}
                        </span>
                        <span style="background: #f1f5f9; padding: 4px 12px; border-radius: 20px;">
                            <i class="fas fa-${breakdown.can_drive === 'yes' ? 'check' : 'times'}"></i> 
                            Can Drive: ${breakdown.can_drive === 'yes' ? 'Yes' : 'No'}
                        </span>
                        <span style="background: #f1f5f9; padding: 4px 12px; border-radius: 20px;">
                            <i class="fas fa-flag"></i> ${breakdown.priority} priority
                        </span>
                    </div>
                </div>
            </div>
            
            <!-- Timeline Card -->
            <div style="background: #f8fafc; border-radius: 12px; padding: 20px; border: 1px solid #e2e8f0;">
                <h4 style="margin: 0 0 15px 0; font-size: 16px; color: #1e293b;">
                    <i class="fas fa-clock" style="color: #6b7280;"></i> Timeline
                </h4>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                    <div>
                        <div style="font-size: 12px; color: #64748b;">Reported At</div>
                        <div style="font-weight: 500;">${reportedDate}</div>
                    </div>
                    <div>
                        <div style="font-size: 12px; color: #64748b;">Assigned At</div>
                        <div style="font-weight: 500;">${assignedDate}</div>
                    </div>
                    ${breakdown.resolved_at ? `
                    <div>
                        <div style="font-size: 12px; color: #64748b;">Resolved At</div>
                        <div style="font-weight: 500;">${new Date(breakdown.resolved_at).toLocaleString()}</div>
                    </div>
                    ` : ''}
                </div>
            </div>
            
            ${breakdown.resolution_notes ? `
            <!-- Resolution Notes -->
            <div style="background: #f0fdf4; border-radius: 12px; padding: 20px; margin-top: 20px; border: 1px solid #86efac;">
                <h4 style="margin: 0 0 10px 0; font-size: 16px; color: #166534;">
                    <i class="fas fa-check-circle" style="color: #16a34a;"></i> Resolution Notes
                </h4>
                <p style="margin: 0; color: #166534;">${breakdown.resolution_notes}</p>
            </div>
            ` : ''}
        </div>
    `;
}

function displayMaintenanceTaskDetails(task) {
    const container = document.getElementById('mechanicTaskDetailsContainer');
    const actionBtn = document.getElementById('mechanicTaskActionBtn');
    
    // Format dates
    const createdDate = new Date(task.created_at).toLocaleString();
    const dueDate = new Date(task.due_date).toLocaleDateString();
    
    // Determine status color
    let statusColor = '#6b7280';
    if (task.status === 'pending') statusColor = '#f59e0b';
    else if (task.status === 'in_progress') statusColor = '#3b82f6';
    else if (task.status === 'completed') statusColor = '#10b981';
    
    // Set action button based on status
    if (task.status === 'pending') {
        actionBtn.style.display = 'block';
        actionBtn.innerHTML = '<i class="fas fa-play"></i> Start Work';
        actionBtn.className = 'btn btn-primary';
        actionBtn.onclick = () => startMaintenance(task.id);
    } else if (task.status === 'in_progress') {
        actionBtn.style.display = 'block';
        actionBtn.innerHTML = '<i class="fas fa-check"></i> Complete Task';
        actionBtn.className = 'btn btn-success';
        actionBtn.onclick = () => completeTask(task.id, task.asset_name);
    } else {
        actionBtn.style.display = 'none';
    }
    
    container.innerHTML = `
        <div style="margin-bottom: 20px;">
            <!-- Header with type badge -->
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <span style="background: #f59e0b; color: white; padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: 600;">
                    <i class="fas fa-wrench"></i> SCHEDULED MAINTENANCE
                </span>
                <span style="background: ${statusColor}20; color: ${statusColor}; padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; text-transform: uppercase;">
                    ${task.status.replace('_', ' ')}
                </span>
            </div>
            
            <!-- Vehicle Info Card -->
            <div style="background: #f8fafc; border-radius: 12px; padding: 20px; margin-bottom: 20px; border: 1px solid #e2e8f0;">
                <h4 style="margin: 0 0 15px 0; font-size: 16px; color: #1e293b;">
                    <i class="fas fa-truck" style="color: #3b82f6;"></i> Vehicle Information
                </h4>
                <div>
                    <div style="font-size: 12px; color: #64748b;">Vehicle Name</div>
                    <div style="font-weight: 600; font-size: 18px;">${task.asset_name}</div>
                </div>
            </div>
            
            <!-- Issue Details Card -->
            <div style="background: #f8fafc; border-radius: 12px; padding: 20px; margin-bottom: 20px; border: 1px solid #e2e8f0;">
                <h4 style="margin: 0 0 15px 0; font-size: 16px; color: #1e293b;">
                    <i class="fas fa-tools" style="color: #f97316;"></i> Issue Details
                </h4>
                <div style="background: white; padding: 15px; border-radius: 6px; border: 1px solid #e2e8f0;">
                    <p style="margin: 0 0 15px 0; font-size: 16px;">${task.issue}</p>
                    <div style="display: flex; gap: 15px; font-size: 13px; flex-wrap: wrap;">
                        <span style="background: #f1f5f9; padding: 4px 12px; border-radius: 20px;">
                            <i class="fas fa-tag"></i> ${task.issue_type}
                        </span>
                        <span style="background: #f1f5f9; padding: 4px 12px; border-radius: 20px;">
                            <i class="fas fa-flag"></i> ${task.priority} priority
                        </span>
                        ${task.estimated_hours ? `
                        <span style="background: #f1f5f9; padding: 4px 12px; border-radius: 20px;">
                            <i class="fas fa-clock"></i> Est: ${task.estimated_hours} hours
                        </span>
                        ` : ''}
                    </div>
                </div>
            </div>
            
            <!-- Schedule Card -->
            <div style="background: #f8fafc; border-radius: 12px; padding: 20px; margin-bottom: 20px; border: 1px solid #e2e8f0;">
                <h4 style="margin: 0 0 15px 0; font-size: 16px; color: #1e293b;">
                    <i class="fas fa-calendar" style="color: #8b5cf6;"></i> Schedule
                </h4>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                    <div>
                        <div style="font-size: 12px; color: #64748b;">Due Date</div>
                        <div style="font-weight: 500;">${dueDate}</div>
                    </div>
                    <div>
                        <div style="font-size: 12px; color: #64748b;">Created</div>
                        <div style="font-weight: 500;">${createdDate}</div>
                    </div>
                </div>
            </div>
            
            <!-- Timeline Card -->
            <div style="background: #f8fafc; border-radius: 12px; padding: 20px; border: 1px solid #e2e8f0;">
                <h4 style="margin: 0 0 15px 0; font-size: 16px; color: #1e293b;">
                    <i class="fas fa-history" style="color: #6b7280;"></i> Progress
                </h4>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                    ${task.started_at ? `
                    <div>
                        <div style="font-size: 12px; color: #64748b;">Started At</div>
                        <div style="font-weight: 500;">${new Date(task.started_at).toLocaleString()}</div>
                    </div>
                    ` : ''}
                    ${task.completed_date ? `
                    <div>
                        <div style="font-size: 12px; color: #64748b;">Completed</div>
                        <div style="font-weight: 500;">${new Date(task.completed_date).toLocaleDateString()}</div>
                    </div>
                    ` : ''}
                </div>
            </div>
            
            ${task.completed_notes ? `
            <!-- Completion Notes -->
            <div style="background: #f0fdf4; border-radius: 12px; padding: 20px; margin-top: 20px; border: 1px solid #86efac;">
                <h4 style="margin: 0 0 10px 0; font-size: 16px; color: #166534;">
                    <i class="fas fa-check-circle" style="color: #16a34a;"></i> Completion Notes
                </h4>
                <p style="margin: 0; color: #166534;">${task.completed_notes}</p>
            </div>
            ` : ''}
        </div>
    `;
}

function closeMechanicTaskDetailsModal() {
    const modal = document.getElementById('mechanicTaskDetailsModal');
    if (modal) {
        modal.style.display = 'none';
    }
}


function displayVehicles(vehicles) {
    const vehicleList = document.querySelector('.vehicle-list');
    if (!vehicleList) return;
    
    console.log('Vehicle data received:', vehicles); // Debug: see what's in the data
    
    let html = '';
    vehicles.forEach(vehicle => {
        // Log each vehicle to see its properties
        console.log('Single vehicle:', vehicle);
        
        // Try to find driver name from any possible property
        let driverName = vehicle.current_driver || 
                        vehicle.driver_name || 
                        vehicle.driver || 
                        vehicle.full_name || 
                        null;
        
        // Determine status based on actual data
        let availability, statusText, statusIcon, statusColor, extraInfo = '';
        
        if (vehicle.has_pending_maintenance) {
            availability = 'maintenance';
            statusText = 'IN MAINTENANCE';
            statusIcon = 'wrench';
            statusColor = '#ef4444';
            extraInfo = `<div style="font-size: 11px; color: #ef4444; margin-top: 5px;">
                <i class="fas fa-exclamation-triangle"></i> 
                Maintenance: ${vehicle.maintenance_issue || 'Scheduled'} 
                (Due: ${vehicle.due_date || 'N/A'})
            </div>`;
        } else if (vehicle.is_in_use) {
            availability = 'in-use';
            statusText = 'IN USE';
            statusIcon = 'play-circle';
            statusColor = '#f59e0b';
            extraInfo = `<div style="font-size: 11px; color: #f59e0b; margin-top: 5px;">
                <i class="fas fa-user"></i> 
                Driver: ${driverName || 'Unknown'}
            </div>`;
        } else {
            availability = 'available';
            statusText = 'AVAILABLE';
            statusIcon = 'check-circle';
            statusColor = '#10b981';
        }
        
        html += `
            <div class="vehicle-item" data-type="${vehicle.asset_type}" data-status="${vehicle.asset_status}">
                <div class="vehicle-info">
                    <div class="vehicle-icon">
                        <i class="fas fa-${vehicle.asset_type === 'vehicle' ? 'truck' : 'cog'}"></i>
                    </div>
                    <div class="vehicle-details">
                        <h3>${vehicle.asset_name} (VH-${String(vehicle.id).padStart(3, '0')})</h3>
                        <div class="vehicle-meta">
                            <span><i class="fas fa-plate"></i> ABC-${String(vehicle.id).padStart(4, '0')}</span>
                            <span><i class="fas fa-tachometer-alt"></i> ${vehicle.mileage || 'N/A'} km</span>
                            ${driverName ? `<span><i class="fas fa-user"></i> ${driverName}</span>` : ''}
                            <!-- Debug info - remove later -->
                            <span style="display:none;">Props: ${Object.keys(vehicle).join(', ')}</span>
                        </div>
                        ${extraInfo}
                    </div>
                </div>
                <div class="vehicle-status">
                    <span class="availability-badge availability-${availability}" style="background-color: ${statusColor}20; color: ${statusColor};">
                        <i class="fas fa-${statusIcon}"></i>
                        ${statusText}
                    </span>
                </div>
                <div class="vehicle-metrics">
                    <div class="vehicle-metric">
                        <div class="value">${vehicle.fuel_level || '85'}%</div>
                        <div class="label">Fuel</div>
                    </div>
                    <div class="vehicle-metric">
                        <div class="value">${vehicle.asset_condition}%</div>
                        <div class="label">Condition</div>
                    </div>
                </div>
            </div>
        `;
    });
    
    vehicleList.innerHTML = html;
}
function loadVehicleAssignments() {
    const assignmentList = document.querySelector('.assignment-list');
    if (!assignmentList) return;
    
    assignmentList.innerHTML = '<div class="empty-state">Loading assignments...</div>';
    
    fetch('../api/get_vehicle_assignments.php')
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            console.log('Assignment data:', data);
            
            if (data.success) {
                if (data.assignments && data.assignments.length > 0) {
                    let html = '';
                    data.assignments.forEach(assignment => {
                        // Determine status color based on your status values
                        let statusColor = '#10b981';
                        let statusIcon = 'check';
                        
                        if (assignment.status === 'pending' || assignment.status === 'scheduled') {
                            statusColor = '#f59e0b'; // orange
                            statusIcon = 'clock';
                        } else if (assignment.status === 'in_transit' || assignment.status === 'in_progress') {
                            statusColor = '#3b82f6'; // blue
                            statusIcon = 'play';
                        } else if (assignment.status === 'completed' || assignment.status === 'delivered') {
                            statusColor = '#10b981'; // green
                            statusIcon = 'check';
                        }
                        
                        html += `
                            <div class="assignment-item" style="display: flex; align-items: center; justify-content: space-between; padding: 15px; border-bottom: 1px solid #f0f0f0;">
                                <div style="flex: 2;">
                                    <div style="display: flex; align-items: center; gap: 12px;">
                                        <div style="width: 40px; height: 40px; background-color: #e6f0ff; border-radius: 8px; display: flex; align-items: center; justify-content: center; color: #2563eb;">
                                            <i class="fas fa-truck"></i>
                                        </div>
                                        <div>
                                            <div style="font-weight: 600;">${assignment.vehicle || 'Unknown Vehicle'}</div>
                                            <div style="font-size: 12px; color: #64748b;">
                                                <i class="fas fa-hashtag"></i> ${assignment.vehicle_code || 'N/A'} • 
                                                <i class="fas fa-plate"></i> ${assignment.plate || 'No plate'}
                                            </div>
                                            ${assignment.purpose ? `<div style="font-size: 11px; color: #999; margin-top: 2px;">${assignment.purpose.substring(0, 30)}${assignment.purpose.length > 30 ? '...' : ''}</div>` : ''}
                                        </div>
                                    </div>
                                </div>
                                <div style="flex: 2;">
                                    <div style="display: flex; align-items: center; gap: 12px;">
                                        <div style="width: 40px; height: 40px; background-color: #f3f4f6; border-radius: 8px; display: flex; align-items: center; justify-content: center; color: #4b5563;">
                                            <i class="fas fa-user"></i>
                                        </div>
                                        <div>
                                            <div style="font-weight: 500;">${assignment.driver || 'Unassigned'}</div>
                                            <div style="font-size: 12px; color: #64748b;">Driver</div>
                                        </div>
                                    </div>
                                </div>
                                <div style="flex: 1;">
                                    <div style="font-size: 13px; font-weight: 500;">
                                        <i class="fas fa-calendar"></i> ${assignment.date || 'TBD'}
                                    </div>
                                    <div style="font-size: 12px; color: #64748b;">
                                        <i class="fas fa-clock"></i> ${assignment.shift || 'Regular'}
                                    </div>
                                </div>
                                <div style="flex: 1; text-align: center;">
                                    <span style="display: inline-block; padding: 6px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; background-color: ${statusColor}20; color: ${statusColor};">
                                        <i class="fas fa-${statusIcon}"></i> ${assignment.status || 'pending'}
                                    </span>
                                </div>
                            </div>
                        `;
                    });
                    assignmentList.innerHTML = html;
                } else {
                    assignmentList.innerHTML = '<div class="empty-state">No vehicle assignments found</div>';
                }
            } else {
                assignmentList.innerHTML = '<div class="empty-state">Error loading assignments: ' + (data.error || 'Unknown error') + '</div>';
            }
        })
        .catch(error => {
            console.error('Error loading assignments:', error);
            assignmentList.innerHTML = '<div class="error-state">Failed to load assignments. Please try again.</div>';
        });
}

// Add to your fleet.js
function openAssignMechanicModal(id, vehicleName, issue) {
    console.log('Opening assign mechanic modal for ID:', id);
    
    const modal = document.getElementById('assignMechanicModal');
    if (!modal) {
        console.error('Assign mechanic modal not found');
        return;
    }
    
    document.getElementById('maintenanceId').value = id;
    document.getElementById('modalVehicleName').textContent = vehicleName;
    document.getElementById('modalIssue').textContent = issue;
    
    // Set default due date (3 days from now for emergencies)
    const dueDate = new Date();
    dueDate.setDate(dueDate.getDate() + 3);
    document.getElementById('dueDate').value = dueDate.toISOString().split('T')[0];
    
    // Clear any previous notes
    const notesField = document.getElementById('maintenanceNotes');
    if (notesField) {
        notesField.value = '';
    }
    
    modal.style.display = 'flex';
}

function closeAssignMechanicModal() {
    document.getElementById('assignMechanicModal').style.display = 'none';
}

function assignMechanic(event) {
    event.preventDefault();
    
    const data = {
        id: document.getElementById('maintenanceId').value,
        issue_type: document.getElementById('issueType').value,
        priority: document.getElementById('priority').value,
        assigned_mechanic: document.getElementById('assignedMechanic').value,
        estimated_hours: document.getElementById('estimatedHours').value,
        due_date: document.getElementById('dueDate').value,
        notes: document.getElementById('maintenanceNotes').value
    };
    
    fetch('../api/assign_mechanic.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(data)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification('Maintenance task assigned successfully', 'success');
            closeAssignMechanicModal();
            loadMaintenanceReport(); // Refresh the list
        } else {
            showNotification('Error: ' + data.error, 'error');
        }
    });
}

// ============================================
// MAINTENANCE MODAL FUNCTIONS
// ============================================
function openCreateMaintenanceModal(vehicleName) {
    console.log('Opening create maintenance modal for:', vehicleName);
    
    // Set the vehicle name in the modal
    const assetSelect = document.getElementById('createAssetName');
    if (assetSelect) {
        // Find and select the vehicle in the dropdown
        for (let i = 0; i < assetSelect.options.length; i++) {
            if (assetSelect.options[i].value === vehicleName) {
                assetSelect.selectedIndex = i;
                break;
            }
        }
    }
    
    // Set default due date (7 days from now)
    const dueDate = new Date();
    dueDate.setDate(dueDate.getDate() + 7);
    const dueDateInput = document.getElementById('createDueDate');
    if (dueDateInput) {
        dueDateInput.value = dueDate.toISOString().split('T')[0];
    }
    
    // Show the modal
    const modalElement = document.getElementById('createMaintenanceModal');
    if (modalElement) {
        modalElement.style.display = 'flex';
    } else {
        console.error('Create maintenance modal not found');
        alert('Maintenance modal not found. Please refresh the page.');
    }
}

function closeCreateMaintenanceModal() {
    const modalElement = document.getElementById('createMaintenanceModal');
    if (modalElement) {
        modalElement.style.display = 'none';
    }
}

// Remove the duplicate closeCreateMaintenanceModal function above this line!

function createMaintenance(event) {
    event.preventDefault();
    
    // Get form values
    const formData = {
        asset_name: document.getElementById('createAssetName').value,
        issue: document.getElementById('createIssue').value,
        issue_type: document.getElementById('createIssueType').value,
        priority: document.getElementById('createPriority').value,
        assigned_mechanic: document.getElementById('createAssignedMechanic').value || null,
        estimated_hours: document.getElementById('createEstimatedHours').value || null,
        due_date: document.getElementById('createDueDate').value
    };
    
    // Validate
    if (!formData.asset_name || !formData.issue || !formData.due_date) {
        showNotification('Please fill all required fields', 'error');
        return;
    }
    
    // Show loading state
    const submitBtn = event.target.querySelector('button[type="submit"]');
    const originalText = submitBtn.innerHTML;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Creating...';
    submitBtn.disabled = true;
    
    // Send to server
    fetch('../api/create_maintenance.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(formData)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification('Maintenance task created successfully', 'success');
            closeCreateMaintenanceModal();
            loadMaintenanceReport(); // Refresh the list
        } else {
            showNotification('Error: ' + (data.error || 'Unknown error'), 'error');
        }
    })
    .catch(error => {
        console.error('Error creating maintenance:', error);
        showNotification('Error: ' + error.message, 'error');
    })
    .finally(() => {
        submitBtn.innerHTML = originalText;
        submitBtn.disabled = false;
    });
}

function updateDriverActivity() {
    console.log('Updating driver activity...');
    // You can add logic here to refresh driver activity data
    loadDriverActivity(); // This will refresh the driver activity display
}



function openCompleteMaintenanceModal(id) {
    console.log('🔵 openCompleteMaintenanceModal called with ID:', id);
    
    // Check if modal exists
    const modal = document.getElementById('completeMaintenanceModal');
    console.log('Modal element:', modal);
    
    if (!modal) {
        console.error('❌ Modal not found! Check HTML for id="completeMaintenanceModal"');
        alert('Error: Complete maintenance modal not found in the page');
        return;
    }
    
    // Check if ID input exists
    const idInput = document.getElementById('completeMaintenanceId');
    console.log('ID input element:', idInput);
    
    if (!idInput) {
        console.error('❌ ID input not found! Check for id="completeMaintenanceId"');
        alert('Error: Maintenance ID input not found');
        return;
    }
    
    // Set the ID
    idInput.value = id;
    console.log('✅ Set ID to:', idInput.value);
    
    // Show modal
    modal.style.display = 'flex';
    console.log('✅ Modal display set to flex');
}

function closeCompleteMaintenanceModal() {
    console.log('Closing complete maintenance modal');
    const modal = document.getElementById('completeMaintenanceModal');
    if (modal) {
        modal.style.display = 'none';
        // Clear the form
        document.getElementById('completionNotes').value = '';
    }
}
// Make sure completeMaintenance is available globally
window.completeMaintenance = function(event) {
    // The function body will be replaced by the one above
};
function completeMaintenance(event) {
    event.preventDefault();
    event.stopPropagation(); // Add this to stop event bubbling
    console.log('✅ completeMaintenance function called');
    
    const id = document.getElementById('completeMaintenanceId').value;
    const notes = document.getElementById('completionNotes').value;
    
    console.log('ID:', id);
    console.log('Notes:', notes);
    
    if (!id) {
        alert('Error: No maintenance ID found');
        return;
    }
    
    // Show loading on button
    const btn = event.target.querySelector('button[type="submit"]') || 
                document.querySelector('#completeMaintenanceModal .btn-success');
    const originalText = btn ? btn.innerHTML : 'Complete';
    if (btn) {
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
        btn.disabled = true;
    }
    
    // Make sure we have valid data
    const data = { 
        id: parseInt(id), // Ensure ID is a number
        notes: notes || '' 
    };
    
    console.log('Sending data:', data);
    
    fetch('../api/complete_maintenance.php', {
        method: 'POST',
        headers: { 
            'Content-Type': 'application/json',
            'Accept': 'application/json'
        },
        body: JSON.stringify(data)
    })
    .then(response => {
        console.log('Response status:', response.status);
        return response.json();
    })
    .then(data => {
        console.log('API Response:', data);
        if (data.success) {
            alert('Maintenance completed successfully!');
            closeCompleteMaintenanceModal();
            // Reload both views
            if (typeof loadMechanicTasks === 'function') {
                loadMechanicTasks();
            }
            if (typeof loadMaintenanceReport === 'function') {
                loadMaintenanceReport();
            }
            // Also reload the page to be safe
            setTimeout(() => location.reload(), 500);
        } else {
            alert('Error: ' + (data.error || 'Unknown error'));
            if (btn) {
                btn.innerHTML = originalText;
                btn.disabled = false;
            }
        }
    })
    .catch(error => {
        console.error('Fetch Error:', error);
        alert('Error: ' + error.message);
        if (btn) {
            btn.innerHTML = originalText;
            btn.disabled = false;
        }
    });

    return false; // Prevent form submission
}
function loadFleetCondition() {
    const conditionGrid = document.querySelector('.condition-grid');
    if (!conditionGrid) return;
    
    conditionGrid.innerHTML = '<div class="loading-spinner"><i class="fas fa-spinner fa-spin"></i> Loading fleet condition...</div>';
    
    fetch('../api/get_fleet_condition.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                displayFleetCondition(data.conditions);
            } else {
                conditionGrid.innerHTML = '<div class="empty-state">No condition data</div>';
            }
        })
        .catch(error => {
            console.error('Error loading fleet condition:', error);
            conditionGrid.innerHTML = '<div class="error-state">Failed to load condition</div>';
        });
}

function displayFleetCondition(conditions) {
    const conditionGrid = document.querySelector('.condition-grid');
    if (!conditionGrid) return;
    
    let html = '';
    conditions.forEach(condition => {
        html += `
            <div class="condition-item">
                <div class="condition-icon">
                    <i class="fas fa-${condition.icon}"></i>
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

// ============================================
// TRANSPORT EFFICIENCY FUNCTIONS
// ============================================

function loadTransportEfficiency() {
    const efficiencyList = document.querySelector('.efficiency-list');
    if (!efficiencyList) return;
    
    efficiencyList.innerHTML = '<div class="loading-spinner"><i class="fas fa-spinner fa-spin"></i> Loading efficiency data...</div>';
    
    fetch('../api/get_transport_efficiency.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                displayEfficiency(data.efficiency);
            } else {
                efficiencyList.innerHTML = '<div class="empty-state">No efficiency data available</div>';
            }
        })
        .catch(error => {
            console.error('Error loading efficiency:', error);
            efficiencyList.innerHTML = '<div class="error-state">Failed to load efficiency data</div>';
        });
}

function displayEfficiency(efficiency) {
    const efficiencyList = document.querySelector('.efficiency-list');
    if (!efficiencyList) return;
    
    let html = '';
    efficiency.forEach(item => {
        const percentage = (item.current / item.target) * 100;
        const fillClass = percentage >= 90 ? 'high' : percentage >= 75 ? 'medium' : 'low';
        
        // Determine color based on performance
        let statusColor = '#6b7280';
        if (item.current >= item.target) {
            statusColor = '#10b981'; // green if meeting/exceeding target
        } else if (item.current >= item.average) {
            statusColor = '#f59e0b'; // orange if above average but below target
        } else {
            statusColor = '#ef4444'; // red if below average
        }
        
        html += `
            <div class="efficiency-item" style="margin-bottom: 20px;">
                <div class="efficiency-header" style="display: flex; justify-content: space-between; margin-bottom: 8px;">
                    <span class="efficiency-title" style="font-weight: 500; color: #1e293b;">${item.metric}</span>
                    <span class="efficiency-value" style="font-weight: 600; color: ${statusColor};">${item.current}${item.unit}</span>
                </div>
                <div class="efficiency-bar" style="height: 8px; background-color: #e9eef2; border-radius: 4px; overflow: hidden; margin-bottom: 5px;">
                    <div class="efficiency-fill ${fillClass}" style="width: ${percentage}%; height: 100%; background-color: ${statusColor};"></div>
                </div>
                <div class="efficiency-stats" style="display: flex; justify-content: space-between; font-size: 12px; color: #64748b;">
                    <span>Target: ${item.target}${item.unit}</span>
                    <span>Avg: ${item.average}${item.unit}</span>
                </div>
            </div>
        `;
    });
    
    efficiencyList.innerHTML = html;
}

// ============================================
// DELAY ANALYSIS FUNCTIONS
// ============================================
function reportDelay() {
    console.log('Opening delay report...');
    
    // First, get current assignment
    fetch('../api/get_driver_assignment.php')
        .then(response => response.json())
        .then(data => {
            if (data.success && data.has_assignment) {
                const assignment = data.assignment;
                const shipmentId = assignment.id;
                
                // Show reason prompt with suggestions
                const reason = prompt(
                    'Reason for delay (examples: traffic, weather, mechanical issue, loading problem, accident):'
                );
                if (!reason) return;
                
                // Ask for duration with unit
                let duration = prompt(
                    'How long is the delay?\n\n' +
                    'Examples:\n' +
                    '- "30 minutes"\n' +
                    '- "2 hours"\n' +
                    '- "1 day"\n' +
                    '- "until tomorrow"\n\n' +
                    'Enter duration:'
                );
                if (!duration) return;
                
                // Show loading notification
                showNotification('Reporting delay...', 'info');
                
                // Submit to API
                fetch('../api/report_delay.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        shipment_id: shipmentId,
                        reason: reason,
                        duration: duration, // Now stores as text like "2 hours"
                        route: assignment.route || assignment.delivery_address || 'Unknown route'
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showNotification('Delay reported successfully!', 'success');
                    } else {
                        showNotification('Error: ' + (data.error || 'Unknown error'), 'error');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showNotification('Failed to report delay', 'error');
                });
                
            } else {
                showNotification('No active assignment found', 'error');
            }
        })
        .catch(error => {
            console.error('Error getting assignment:', error);
            showNotification('Error getting assignment', 'error');
        });
}
function loadDelayAnalysis() {
    const delayList = document.querySelector('.delay-list');
    if (!delayList) return;
    
    delayList.innerHTML = '<div class="loading-spinner"><i class="fas fa-spinner fa-spin"></i> Loading delay data...</div>';
    
    fetch('../api/get_delay_analysis.php')
        .then(response => response.json())
        .then(data => {
            if (data.success && data.delays.length > 0) {
                displayDelays(data.delays);
            } else {
                delayList.innerHTML = '<div class="empty-state">No delays reported</div>';
            }
        })
        .catch(error => {
            console.error('Error loading delays:', error);
            delayList.innerHTML = '<div class="error-state">Failed to load delay data</div>';
        });
}

function displayDelays(delays) {
    const delayList = document.querySelector('.delay-list');
    if (!delayList) return;
    
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
                    ${delay.duration}
                </div>
                <div class="delay-type">
                    ${delay.type}
                </div>
            </div>
        `;
    });
    
    delayList.innerHTML = html;
}

// ============================================
// DRIVER PERFORMANCE FUNCTIONS
// ============================================

function loadDriverPerformance() {
    const driverList = document.querySelector('.driver-list');
    if (!driverList) return;
    
    driverList.innerHTML = '<div class="loading-spinner"><i class="fas fa-spinner fa-spin"></i> Loading driver data...</div>';
    
    fetch('../api/get_driver_performance.php')
        .then(response => response.json())
        .then(data => {
            if (data.success && data.drivers.length > 0) {
                displayDriverPerformance(data.drivers);
            } else {
                driverList.innerHTML = '<div class="empty-state">No driver data available</div>';
            }
        })
        .catch(error => {
            console.error('Error loading driver performance:', error);
            driverList.innerHTML = '<div class="error-state">Failed to load driver data</div>';
        });
}

function displayDriverPerformance(drivers) {
    const driverList = document.querySelector('.driver-list');
    if (!driverList) return;
    
    let html = '';
    drivers.forEach(driver => {
        const initials = driver.full_name.split(' ').map(n => n[0]).join('').substring(0, 2);
        
        html += `
            <div class="driver-item">
                <div class="driver-avatar">${initials}</div>
                <div class="driver-info">
                    <h3>${driver.full_name}</h3>
                    <div class="driver-meta">
                        <span>${driver.employee_id}</span>
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

function loadDriverActivity() {
    const activityTimeline = document.querySelector('.activity-timeline');
    if (!activityTimeline) return;
    
    activityTimeline.innerHTML = '<div class="loading-spinner"><i class="fas fa-spinner fa-spin"></i> Loading driver activity...</div>';
    
    fetch('../api/get_all_driver_activity.php?_=' + new Date().getTime())
        .then(response => response.json())
        .then(data => {
            console.log('Driver activity data:', data);
            
            if (data.success && data.drivers && data.drivers.length > 0) {
                displayDriverActivity(data.drivers);
            } else {
                activityTimeline.innerHTML = '<div class="empty-state">No active drivers</div>';
            }
        })
        .catch(error => {
            console.error('Error loading driver activity:', error);
            activityTimeline.innerHTML = '<div class="error-state">Failed to load driver activity</div>';
        });
}

function displayDriverActivity(drivers) {
    const activityTimeline = document.querySelector('.activity-timeline');
    if (!activityTimeline) return;
    
    // Add horizontal scroll container
    let html = `
        <div style="margin-bottom: 20px;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                <div style="display: flex; align-items: center; gap: 10px;">
                    <h3 style="font-size: 16px; font-weight: 600; color: #1e293b; margin: 0;">
                        <i class="fas fa-users" style="color: #3b82f6; margin-right: 8px;"></i>
                        Driver Monitoring
                    </h3>
                   
                </div>
                <div style="display: flex; gap: 5px;">
                    <button onclick="scrollDrivers('left')" style="background: white; border: 1px solid #e2e8f0; border-radius: 6px; padding: 4px 8px; cursor: pointer; font-size: 12px;">
                        <i class="fas fa-chevron-left"></i>
                    </button>
                    <button onclick="scrollDrivers('right')" style="background: white; border: 1px solid #e2e8f0; border-radius: 6px; padding: 4px 8px; cursor: pointer; font-size: 12px;">
                        <i class="fas fa-chevron-right"></i>
                    </button>
                </div>
            </div>
            
            <!-- Horizontal Scroll Container -->
            <div id="driverScrollContainer" style="overflow-x: auto; overflow-y: hidden; white-space: nowrap; padding: 5px 0; scroll-behavior: smooth; -webkit-overflow-scrolling: touch;">
                <div style="display: inline-flex; gap: 15px;">
    `;
    
    drivers.forEach((driver, index) => {
        // Get initials
        const initials = driver.driver_name.split(' ').map(n => n[0]).join('').substring(0, 2).toUpperCase();
        
        html += `
            <div style="width: 260px; background: white; border-radius: 12px; box-shadow: 0 2px 4px rgba(0,0,0,0.05); border: 1px solid #f0f0f0; display: inline-block; vertical-align: top;">
                
                <!-- Driver Header with Avatar -->
                <div style="padding: 16px; display: flex; align-items: center; gap: 12px;">
                    <div class="driver-avatar" style="width: 48px; height: 48px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-weight: 600; font-size: 18px;">
                        ${initials}
                    </div>
                    <div>
                        <h4 style="margin: 0 0 4px 0; font-size: 15px; font-weight: 600; color: #1e293b;">${driver.driver_name}</h4>
                        <div style="font-size: 12px; color: #64748b;">
                            <span>${driver.employee_id}</span>
                            <span style="margin-left: 8px;">${driver.total_trips} trips</span>
                        </div>
                    </div>
                </div>
                
                <!-- Stats Row -->
                <div style="padding: 0 16px 16px 16px; display: flex; justify-content: space-between;">
                    <div style="text-align: center;">
                        <div style="font-size: 18px; font-weight: 700; color: #3b82f6;">${driver.total_trips}</div>
                        <div style="font-size: 11px; color: #64748b;">Total</div>
                    </div>
                    <div style="text-align: center;">
                        <div style="font-size: 18px; font-weight: 700; color: #10b981;">${driver.completed_trips}</div>
                        <div style="font-size: 11px; color: #64748b;">Completed</div>
                    </div>
                    <div style="text-align: center;">
                        <div style="font-size: 18px; font-weight: 700; color: #8b5cf6;">${driver.performance}%</div>
                        <div style="font-size: 11px; color: #64748b;">Perf</div>
                    </div>
                </div>
                
                <!-- Current Status Badge -->
                <div style="padding: 0 16px 16px 16px;">
                    <div style="display: inline-block; padding: 4px 12px; border-radius: 20px; font-size: 11px; font-weight: 500; background-color: ${
                        driver.current_status === 'On Trip' ? '#f59e0b20' : 
                        driver.current_status === 'Assigned' ? '#3b82f620' : 
                        '#10b98120'
                    }; color: ${
                        driver.current_status === 'On Trip' ? '#f59e0b' : 
                        driver.current_status === 'Assigned' ? '#3b82f6' : 
                        '#10b981'
                    };">
                        <i class="fas fa-${
                            driver.current_status === 'On Trip' ? 'truck' : 
                            driver.current_status === 'Assigned' ? 'calendar-check' : 
                            'circle'
                        }"></i>
                        ${driver.current_status}
                    </div>
                </div>
                
                <!-- Location & Vehicle (compact) -->
                <div style="padding: 12px 16px; border-top: 1px solid #f0f0f0; background: #fafafa;">
                    <div style="font-size: 12px; color: #475569; margin-bottom: 6px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                        <i class="fas fa-map-marker-alt" style="color: #ef4444; width: 16px; margin-right: 6px;"></i>
                        ${driver.current_location || 'No location'}
                    </div>
                    <div style="font-size: 12px; color: #475569; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                        <i class="fas fa-truck" style="color: #f59e0b; width: 16px; margin-right: 6px;"></i>
                        ${driver.current_vehicle}
                    </div>
                </div>
                
                <!-- Quick Actions -->
                <div style="padding: 10px 16px; border-top: 1px solid #f0f0f0; display: flex; gap: 8px;">
                    <button onclick="viewDriverDetails(${driver.driver_id})" style="flex: 1; padding: 6px; border: none; background: #3b82f6; border-radius: 6px; font-size: 11px; color: white; cursor: pointer;">
                        <i class="fas fa-eye"></i> View
                    </button>
                    <button onclick="contactDriver(${driver.driver_id})" style="padding: 6px 10px; border: none; background: #f1f5f9; border-radius: 6px; color: #64748b; cursor: pointer;">
                        <i class="fas fa-phone"></i>
                    </button>
                </div>
            </div>
        `;
    });
    
    // Close the container
    html += `
                </div>
            </div>
        </div>
    `;
    
    activityTimeline.innerHTML = html;
    
    // Add scroll buttons functionality
    window.scrollDrivers = function(direction) {
        const container = document.getElementById('driverScrollContainer');
        const scrollAmount = 275; // Width of card + gap
        if (direction === 'left') {
            container.scrollLeft -= scrollAmount;
        } else {
            container.scrollLeft += scrollAmount;
        }
    };
}

// Keep these helper functions
function viewDriverDetails(driverId) {
    console.log('Viewing driver details for ID:', driverId);
    
    // Fetch driver details from API
    fetch(`../api/get_driver_details.php?id=${driverId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showDriverDetailsModal(data.driver);
            } else {
                showNotification('Error: ' + (data.error || 'Could not load driver details'), 'error');
            }
        })
        .catch(error => {
            console.error('Error fetching driver details:', error);
            showNotification('Error loading driver details', 'error');
        });
}

function showDriverDetailsModal(driver) {
    // Create modal if it doesn't exist
    let modal = document.getElementById('driverDetailsModal');
    if (!modal) {
        modal = document.createElement('div');
        modal.id = 'driverDetailsModal';
        modal.className = 'modal';
        modal.innerHTML = `
            <div class="modal-content" style="max-width: 500px;">
                <div class="modal-header">
                    <h3><i class="fas fa-user"></i> Driver Details</h3>
                    <button class="modal-close" onclick="closeDriverDetailsModal()">&times;</button>
                </div>
                <div class="modal-body" id="driverDetailsBody">
                    <!-- Content will be loaded here -->
                </div>
                <div class="modal-footer" style="padding: 15px; text-align: right; border-top: 1px solid #e9eef2;">
                    <button class="btn btn-secondary" onclick="closeDriverDetailsModal()">Close</button>
                </div>
            </div>
        `;
        document.body.appendChild(modal);
    }
    
    // Get initials for avatar
    const initials = driver.full_name.split(' ').map(n => n[0]).join('').substring(0, 2).toUpperCase();
    
    // Format dates
    const joinDate = driver.join_date ? new Date(driver.join_date).toLocaleDateString() : 'N/A';
    const lastLogin = driver.last_login ? new Date(driver.last_login).toLocaleString() : 'Never';
    
    // Fill modal content
    document.getElementById('driverDetailsBody').innerHTML = `
        <div style="text-align: center; margin-bottom: 20px;">
            <div style="width: 80px; height: 80px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-weight: 600; font-size: 32px; margin: 0 auto 15px;">
                ${initials}
            </div>
            <h3 style="margin: 0 0 5px 0; font-size: 20px;">${driver.full_name}</h3>
            <p style="margin: 0; color: #64748b;">${driver.employee_id}</p>
        </div>
        
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-bottom: 15px;">
            <div style="background: #f8fafc; padding: 12px; border-radius: 8px;">
                <div style="font-size: 11px; color: #64748b;">Status</div>
                <div style="font-weight: 600; color: ${driver.status === 'active' ? '#10b981' : '#ef4444'};">
                    ${driver.status ? driver.status.toUpperCase() : 'ACTIVE'}
                </div>
            </div>
            <div style="background: #f8fafc; padding: 12px; border-radius: 8px;">
                <div style="font-size: 11px; color: #64748b;">Department</div>
                <div style="font-weight: 600;">${driver.department || 'N/A'}</div>
            </div>
            <div style="background: #f8fafc; padding: 12px; border-radius: 8px;">
                <div style="font-size: 11px; color: #64748b;">Phone</div>
                <div style="font-weight: 600;">${driver.phone || 'N/A'}</div>
            </div>
            <div style="background: #f8fafc; padding: 12px; border-radius: 8px;">
                <div style="font-size: 11px; color: #64748b;">Email</div>
                <div style="font-weight: 600; word-break: break-all;">${driver.email || 'N/A'}</div>
            </div>
        </div>
        
        <div style="margin-bottom: 15px;">
            <div style="background: #f8fafc; padding: 12px; border-radius: 8px; margin-bottom: 5px;">
                <div style="font-size: 11px; color: #64748b;">Join Date</div>
                <div style="font-weight: 500;">${joinDate}</div>
            </div>
            <div style="background: #f8fafc; padding: 12px; border-radius: 8px;">
                <div style="font-size: 11px; color: #64748b;">Last Login</div>
                <div style="font-weight: 500;">${lastLogin}</div>
            </div>
        </div>
        
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
            <div style="background: #f8fafc; padding: 12px; border-radius: 8px; text-align: center;">
                <div style="font-size: 20px; font-weight: 700; color: #3b82f6;">${driver.total_trips || 0}</div>
                <div style="font-size: 11px; color: #64748b;">Total Trips</div>
            </div>
            <div style="background: #f8fafc; padding: 12px; border-radius: 8px; text-align: center;">
                <div style="font-size: 20px; font-weight: 700; color: #10b981;">${driver.completed_trips || 0}</div>
                <div style="font-size: 11px; color: #64748b;">Completed</div>
            </div>
        </div>
    `;
    
    modal.style.display = 'flex';
}

function closeDriverDetailsModal() {
    const modal = document.getElementById('driverDetailsModal');
    if (modal) modal.style.display = 'none';
}

function contactDriver(driverId) {
    console.log('Contacting driver:', driverId);
    showNotification(`Contacting driver #${driverId}...`, 'info');
}

// ============================================
// TRIP HISTORY FUNCTIONS
// ============================================

function loadTripHistory() {
    console.log('🔍 Loading trip history for admin/dispatcher...');
    console.log('Current user role:', document.body.dataset.userRole);
    
    // Check if we're in admin/dispatcher view (not driver)
    const userRole = document.body.dataset.userRole;
    if (userRole === 'driver') {
        console.log('Driver view - skipping admin trip history');
        return;
    }
    
    // Check if overview tab is active
    const activeTab = document.querySelector('.tab.active');
    console.log('Active tab:', activeTab ? activeTab.dataset.tab : 'none');
    
    if (!activeTab || activeTab.dataset.tab !== 'overview') {
        console.log('Overview tab not active, skipping trip history load');
        return;
    }
    
    // Try multiple selectors to find the trip list
    let tripList = null;
    const selectors = [
        '#tab-overview .trip-list',
        '.trip-list',
        '.card-full .trip-list',
        '.card-body .trip-list'
    ];
    
    for (let selector of selectors) {
        tripList = document.querySelector(selector);
        if (tripList) {
            console.log('✅ Found trip list with selector:', selector);
            break;
        }
    }
    
    if (!tripList) {
       
        
        // Try to find any element that might contain trip history
        const possibleCards = document.querySelectorAll('.card');
        for (let card of possibleCards) {
            const header = card.querySelector('.card-header h2');
            if (header && header.textContent.includes('Trip History')) {
                const body = card.querySelector('.card-body');
                if (body) {
                    // Create the trip list if it doesn't exist
                    tripList = document.createElement('div');
                    tripList.className = 'trip-list';
                    body.appendChild(tripList);
                    console.log('✅ Created trip list element');
                    break;
                }
            }
        }
        
        if (!tripList) {
            console.error('❌ Could not find or create trip list element');
            return;
        }
    }
    
    // Show loading state
    tripList.innerHTML = '<div class="loading-spinner"><i class="fas fa-spinner fa-spin"></i> Loading trip history...</div>';
    
    // Get filter value
    const filterSelect = document.querySelector('#tab-overview .filter-select, .filter-select');
    let days = 30;
    if (filterSelect) {
        const filterText = filterSelect.value;
        console.log('Filter selected:', filterText);
        if (filterText.includes('7')) days = 7;
        else if (filterText.includes('30')) days = 30;
    }
    
    // Fetch data
    const apiUrl = `../api/get_trip_history.php?limit=10&days=${days}&_=${new Date().getTime()}`;
    console.log('Fetching from:', apiUrl);
    
    fetch(apiUrl)
        .then(response => {
            console.log('API response status:', response.status);
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            console.log('📊 Trip history data received:', data);
            
            if (data.success && data.trips && data.trips.length > 0) {
                console.log(`Displaying ${data.trips.length} trips`);
                displayTripHistory(data.trips);
            } else {
                console.log('No trips data:', data);
                if (data.error) {
                    tripList.innerHTML = `<div class="error-state">Error: ${data.error}</div>`;
                } else {
                    tripList.innerHTML = '<div class="empty-state">No trip history available</div>';
                }
            }
        })
        .catch(error => {
            console.error('❌ Error loading trip history:', error);
            tripList.innerHTML = '<div class="error-state">Failed to load trip history: ' + error.message + '</div>';
        });
}


function displayTripHistory(trips) {
    const tripList = document.querySelector('.trip-list');
    if (!tripList) return;
    
    let html = '';
    trips.forEach(trip => {
        let statusColor = '#6b7280';
        let statusBg = '#6b728020';
        let statusIcon = 'clock';
        
        if (trip.status === 'delivered' || trip.status === 'completed') {
            statusColor = '#10b981';
            statusBg = '#10b98120';
            statusIcon = 'check-circle';
        } else if (trip.status === 'in-transit' || trip.status === 'in_transit' || trip.status === 'in-progress') {
            statusColor = '#f59e0b';
            statusBg = '#f59e0b20';
            statusIcon = 'truck';
        }
        
        html += `
            <div class="trip-item" style="display: flex; align-items: center; padding: 15px; border-bottom: 1px solid #f0f0f0;">
                <div class="trip-icon" style="width: 50px; height: 50px; background-color: #e6f0ff; border-radius: 10px; display: flex; align-items: center; justify-content: center; margin-right: 15px;">
                    <i class="fas fa-route" style="color: #2563eb; font-size: 20px;"></i>
                </div>
                
                <div class="trip-content" style="flex: 2;">
                    <div class="trip-route" style="font-weight: 600; margin-bottom: 5px;">
                        ${trip.from} → ${trip.to}
                    </div>
                    <div class="trip-meta" style="font-size: 13px; color: #64748b;">
                        <span style="margin-right: 15px;"><i class="fas fa-hashtag"></i> ${trip.id}</span>
                        <span style="margin-right: 15px;"><i class="fas fa-user"></i> ${trip.driver}</span>
                        <span><i class="fas fa-calendar"></i> ${trip.date}</span>
                    </div>
                </div>
                
                <div class="trip-stats" style="flex: 1; display: flex; gap: 20px;">
                    <div class="trip-stat" style="text-align: center;">
                        <div class="value" style="font-weight: 600;">${trip.distance}km</div>
                        <div class="label" style="font-size: 12px; color: #64748b;">Distance</div>
                    </div>
                    <div class="trip-stat" style="text-align: center;">
                        <div class="value" style="font-weight: 600;">${trip.duration}h</div>
                        <div class="label" style="font-size: 12px; color: #64748b;">Duration</div>
                    </div>
                </div>
                
                <div class="trip-status" style="min-width: 100px; text-align: right;">
                    <span style="display: inline-block; padding: 6px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; background-color: ${statusBg}; color: ${statusColor};">
                        <i class="fas fa-${statusIcon}"></i>
                        ${trip.status.toUpperCase()}
                    </span>
                </div>
            </div>
        `;
    });
    
    tripList.innerHTML = html;
}


// ============================================
// DISPATCH SCHEDULE FUNCTIONS
// ============================================
function loadDispatchSchedule() {
    const scheduleList = document.querySelector('.schedule-list');
    if (!scheduleList) return;
    
    scheduleList.innerHTML = '<div class="empty-state">Loading schedule...</div>';
    
    fetch('../api/get_dispatch_schedule.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                if (data.schedule && data.schedule.length > 0) {
                    let html = '';
                    data.schedule.forEach(item => {
                        const vehicle = item.vehicle.replace(/'/g, "\\'");
                        const route = item.route.replace(/'/g, "\\'");
                        
                        html += `
                            <div class="schedule-item" style="display: flex; align-items: center; padding: 12px; border-bottom: 1px solid #f0f0f0;">
                                <div style="width: 80px; font-weight: 600; color: #2563eb;">${item.time}</div>
                                <div style="flex: 2;">
                                    <div style="font-weight: 500;">${item.route}</div>
                                    <div style="font-size: 12px; color: #64748b;">${item.vehicle} • ${item.type}</div>
                                </div>
                                <div style="text-align: right; min-width: 150px;">
                                    <div style="font-weight: 500;">${item.driver}</div>
                                    <div style="font-size: 12px; color: #64748b; margin-bottom: 5px;">Driver</div>
                                    ${item.driver === 'Unassigned' ? 
                                        `<button class="btn-assign" onclick="openAssignDriverModal(${item.id}, '${vehicle}', '${route}')" style="background-color: #2563eb; color: white; border: none; padding: 4px 12px; border-radius: 4px; font-size: 12px; cursor: pointer;">
                                            <i class="fas fa-user-plus"></i> Assign Driver
                                        </button>` : 
                                        `<button class="btn-change" onclick="openAssignDriverModal(${item.id}, '${vehicle}', '${route}')" style="background-color: #f59e0b; color: white; border: none; padding: 4px 12px; border-radius: 4px; font-size: 12px; cursor: pointer;">
                                            <i class="fas fa-edit"></i> Change Driver
                                        </button>`
                                    }
                                </div>
                            </div>
                        `;
                    });
                    scheduleList.innerHTML = html;
                } else {
                    scheduleList.innerHTML = '<div class="empty-state">No dispatch schedule for today</div>';
                }
            } else {
                scheduleList.innerHTML = '<div class="empty-state">Error loading schedule</div>';
            }
        })
        .catch(error => {
            console.error('Error loading schedule:', error);
            scheduleList.innerHTML = '<div class="empty-state">Error loading schedule</div>';
        });
}

// ============================================
// MAINTENANCE FUNCTIONS
// ============================================

function loadMaintenanceReport() {
    const maintenanceList = document.querySelector('.maintenance-list');
    if (!maintenanceList) return;
    
    maintenanceList.innerHTML = '<div class="loading-spinner"><i class="fas fa-spinner fa-spin"></i> Loading maintenance...</div>';
    
    fetch('../api/get_maintenance_alerts.php')
        .then(response => response.json())
        .then(data => {
            if (data.success && data.alerts.length > 0) {
                displayMaintenanceAlerts(data.alerts);
            } else {
                maintenanceList.innerHTML = '<div class="empty-state">No maintenance alerts</div>';
            }
        })
        .catch(error => {
            console.error('Error loading maintenance:', error);
            maintenanceList.innerHTML = '<div class="error-state">Failed to load maintenance</div>';
        });
}

function displayMaintenanceAlerts(alerts) {
    const maintenanceList = document.querySelector('.maintenance-list');
    if (!maintenanceList) return;
    
    let html = '';
    alerts.forEach(alert => {
        const dueClass = alert.status === 'overdue' ? 'due-overdue' : alert.status === 'due-soon' ? 'due-soon' : '';
        
        html += `
            <div class="maintenance-item">
                <div class="maintenance-icon">
                    <i class="fas fa-wrench"></i>
                </div>
                <div class="maintenance-content">
                    <div class="maintenance-title">${alert.asset_name} - ${alert.issue}</div>
                    <div class="maintenance-meta">
                        <span>Due: ${alert.due_date}</span>
                        <span class="priority-${alert.priority}">${alert.priority} Priority</span>
                    </div>
                </div>
                <div class="maintenance-due ${dueClass}">
                    ${alert.status}
                </div>
            </div>
        `;
    });
    
    maintenanceList.innerHTML = html;
}

// ============================================
// UTILITY FUNCTIONS
// ============================================

function formatDateTime(dateTimeStr) {
    try {
        const date = new Date(dateTimeStr);
        if (isNaN(date.getTime())) return dateTimeStr;
        
        const options = {
            month: 'short',
            day: 'numeric',
            year: 'numeric',
            hour: 'numeric',
            minute: '2-digit',
            hour12: true
        };
        return date.toLocaleString('en-US', options);
    } catch (e) {
        return dateTimeStr;
    }
}

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
        setTimeout(() => document.body.removeChild(notification), 300);
    }, 3000);
}

function refreshCard(cardTitle) {
    console.log(`Refreshing ${cardTitle}...`);
    showNotification(`${cardTitle} refreshed`, 'success');
    
    if (cardTitle.includes('Vehicle')) {
        loadVehicleAvailability();
    } else if (cardTitle.includes('Efficiency')) {
        loadTransportEfficiency();
    } else if (cardTitle.includes('Delay')) {
        loadDelayAnalysis();
    } else if (cardTitle.includes('Driver Performance')) {
        loadDriverPerformance();
    } else if (cardTitle.includes('Reservation')) {
        loadVehicleReservations();
    } else if (cardTitle.includes('Dispatch')) {
        loadDispatchSchedule();
    } else if (cardTitle.includes('Maintenance')) {
        loadMaintenanceReport();
    }
}

function filterData(filterId, value) {
    console.log(`Filtering ${filterId}: ${value}`);
}

function searchFleet(term) {
    console.log('Searching fleet:', term);
    applyVehicleFilters();
}

function updateVehicleAvailability() {
    loadVehicleAvailability();
}

function editAssignment(assignmentId) {
    console.log('Editing assignment:', assignmentId);
    showNotification('Edit assignment modal opened', 'info');
}

function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        clearTimeout(timeout);
        timeout = setTimeout(() => func(...args), wait);
    };
}
// ============================================
// MECHANIC TAB
// ============================================
// ============================================
// MECHANIC FUNCTIONS
// ============================================

// Add this at the end of your DOMContentLoaded event
document.addEventListener('DOMContentLoaded', function() {
    // ... existing code ...
    
    // Force load mechanic tasks if role is mechanic
    if (document.body.dataset.userRole === 'mechanic') {
        console.log('Mechanic detected - forcing task load');
        setTimeout(() => {
            loadMechanicTasks();
        }, 1000);
    }
});

function displayMechanicTasks(tasks) {
    const tasksContent = document.getElementById('my-tasks-content');
    if (!tasksContent) return;
    
    let html = '<div class="task-list">';
    
    tasks.forEach(task => {
        // Determine priority color
        let priorityColor = '#6b7280';
        let priorityBg = '#f3f4f6';
        
        if (task.priority === 'high') {
            priorityColor = '#ef4444';
            priorityBg = '#fee2e2';
        } else if (task.priority === 'medium') {
            priorityColor = '#f59e0b';
            priorityBg = '#fef3c7';
        }
        
        // Determine status badge
        let statusBadge = '';
        if (task.status === 'pending') {
            statusBadge = '<span style="background-color: #f59e0b20; color: #f59e0b; padding: 4px 12px; border-radius: 20px; font-size: 12px;"><i class="fas fa-clock"></i> Pending</span>';
        } else if (task.status === 'in_progress') {
            statusBadge = '<span style="background-color: #3b82f620; color: #3b82f6; padding: 4px 12px; border-radius: 20px; font-size: 12px;"><i class="fas fa-play"></i> In Progress</span>';
        }
        
        html += `
            <div class="task-item" style="background: white; border-radius: 12px; padding: 20px; margin-bottom: 15px; box-shadow: 0 2px 4px rgba(0,0,0,0.05); border: 1px solid #f0f0f0;">
                <div style="display: flex; justify-content: space-between; align-items: start;">
                    <div>
                        <h4 style="margin: 0 0 10px 0; font-size: 18px;">${task.asset_name}</h4>
                        <p style="margin: 0 0 15px 0; color: #475569;">${task.issue}</p>
                        
                        <div style="display: flex; gap: 15px; margin-bottom: 15px; flex-wrap: wrap;">
                            <span style="background-color: ${priorityBg}; color: ${priorityColor}; padding: 4px 12px; border-radius: 20px; font-size: 12px;">
                                <i class="fas fa-flag"></i> ${task.priority} priority
                            </span>
                            <span style="background-color: #f1f5f9; color: #475569; padding: 4px 12px; border-radius: 20px; font-size: 12px;">
                                <i class="fas fa-tag"></i> ${task.issue_type}
                            </span>
                            <span style="background-color: #f1f5f9; color: #475569; padding: 4px 12px; border-radius: 20px; font-size: 12px;">
                                <i class="fas fa-calendar"></i> Due: ${task.due_date}
                            </span>
                            ${task.estimated_hours ? `
                            <span style="background-color: #f1f5f9; color: #475569; padding: 4px 12px; border-radius: 20px; font-size: 12px;">
                                <i class="fas fa-clock"></i> Est: ${task.estimated_hours}h
                            </span>` : ''}
                        </div>
                    </div>
                    ${statusBadge}
                </div>
                
                <div style="display: flex; gap: 10px; margin-top: 15px; border-top: 1px solid #f0f0f0; padding-top: 15px;">
                    ${task.status === 'pending' ? `
                        <button class="btn btn-primary" onclick="startMaintenance(${task.id})" style="flex: 1;">
                            <i class="fas fa-play"></i> Start Work
                        </button>
                    ` : ''}
                    ${task.status === 'in_progress' ? `
    <button class="btn btn-success" onclick="completeTask(${task.id}, '${task.asset_name}')" style="flex: 1;">
        <i class="fas fa-check"></i> Complete
    </button>
` : ''}
                    <button class="btn btn-secondary" onclick="viewMaintenanceDetails(${task.id})" style="flex: 1;">
                        <i class="fas fa-eye"></i> Details
                    </button>
                </div>
            </div>
        `;
    });
    
    html += '</div>';
    tasksContent.innerHTML = html;
}

function loadMechanicTasks() {
    console.log('🔧 Loading mechanic tasks...');
    console.log('Current user role:', document.body.dataset.userRole);
    
    // Check if mechanic tab exists and is visible
    const mechanicTab = document.getElementById('tab-mechanic');
    console.log('Mechanic tab exists:', mechanicTab !== null);
    if (mechanicTab) {
        console.log('Mechanic tab display:', mechanicTab.style.display);
    }
    
    const tasksList = document.getElementById('my-tasks-content');
    console.log('Tasks list element found:', tasksList !== null);
    
    if (!tasksList) {
        console.error('❌ Tasks list element not found - ID="my-tasks-content"');
        console.log('Available elements with IDs:', Array.from(document.querySelectorAll('[id]')).map(el => el.id));
        return;
    }
    
    const tasksCount = document.getElementById('tasks-count');
    console.log('Tasks count element found:', tasksCount !== null);
    
    tasksList.innerHTML = '<div style="text-align: center; padding: 40px;"><i class="fas fa-spinner fa-spin" style="font-size: 32px; color: #3b82f6;"></i><p>Loading your tasks...</p></div>';
    
    // Load stats from API (this is the ONLY stats update we need)
    loadMechanicStats();
    
    // Load activity
    loadMechanicActivity();
    
    fetch('../api/get_mechanic_tasks.php?_=' + new Date().getTime())
        .then(response => {
            console.log('API response status:', response.status);
            return response.json();
        })
        .then(data => {
            console.log('API data received:', data);
            
            if (data.success) {
                if (data.tasks && data.tasks.length > 0) {
                    console.log(`Found ${data.tasks.length} tasks`);
                    if (tasksCount) tasksCount.textContent = data.tasks.length + ' pending';
                    displayMechanicTasks(data.tasks);
                    // REMOVED: updateMechanicStats(data.tasks);  <-- DELETE THIS LINE
                } else {
                    console.log('No tasks found');
                    if (tasksCount) tasksCount.textContent = '0 tasks';
                    tasksList.innerHTML = `
                        <div style="text-align: center; padding: 60px;">
                            <i class="fas fa-check-circle" style="font-size: 64px; color: #10b981; margin-bottom: 20px;"></i>
                            <h3 style="color: #1e293b; margin-bottom: 10px;">No Tasks Assigned</h3>
                            <p style="color: #64748b;">You don't have any maintenance tasks at the moment.</p>
                        </div>
                    `;
                    // REMOVED: updateMechanicStats([]);  <-- DELETE THIS LINE
                }
            } else {
                console.error('API error:', data.error);
                tasksList.innerHTML = '<div class="error-state">Error: ' + (data.error || 'Unknown error') + '</div>';
            }
        })
        .catch(error => {
            console.error('Fetch error:', error);
            tasksList.innerHTML = '<div class="error-state">Failed to load tasks: ' + error.message + '</div>';
        });
}

// Add these new functions
function loadMechanicStats() {
    console.log('=================================');
    console.log('Loading mechanic stats...');
    console.log('=================================');
    
    fetch('../api/get_mechanic_stats.php')
        .then(response => response.json())
        .then(data => {
            console.log('=== MECHANIC STATS RAW RESPONSE ===');
            console.log('Full data:', JSON.stringify(data, null, 2));
            
            if (data.success) {
                console.log('Stats object exists');
                console.log('Stats keys:', Object.keys(data.stats));
                console.log('Completed value:', data.stats.completed);
                console.log('In progress value:', data.stats.in_progress);
                console.log('Pending value:', data.stats.pending);
                console.log('Efficiency value:', data.stats.efficiency);
                
                const statsDiv = document.getElementById('mechanic-stats');
                if (statsDiv) {
                    statsDiv.innerHTML = `
                        <div class="stat-mini">
                            <div class="value" style="color: #10b981;">${data.stats.completed || 0}</div>
                            <div class="label">Completed</div>
                        </div>
                        <div class="stat-mini">
                            <div class="value" style="color: #f59e0b;">${data.stats.in_progress || 0}</div>
                            <div class="label">In Progress</div>
                        </div>
                        <div class="stat-mini">
                            <div class="value" style="color: #8b5cf6;">${data.stats.efficiency || 0}%</div>
                            <div class="label">Efficiency</div>
                        </div>
                    `;
                }
            } else {
                console.log('Stats not successful:', data.error);
            }
        })
        .catch(error => console.error('Error loading mechanic stats:', error));
}
// After completing a task, call this
function refreshAllMechanicData() {
    loadMechanicTasks();  // Reload tasks list
    loadMechanicStats();  // Reload stats
    loadMechanicActivity(); // Reload activity
    loadMyEmergencyBreakdowns(); // Reload emergencies
}

// Call this in your complete task function
function completeTask(taskId, assetName) {
    // Your existing complete task code should look like this:
    fetch('../api/complete_task.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            task_id: taskId,
            asset_name: assetName,
            notes: document.getElementById('completionNotes')?.value || ''
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Show success message
            alert('Task completed successfully!');
            // Refresh all data
            refreshAllMechanicData();
            // Close modal if open
            closeTaskDetailsModal();
        } else {
            alert('Error: ' + data.error);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Failed to complete task');
    });
}
function loadMechanicActivity() {
    console.log('Loading mechanic activity...');
    const activityList = document.getElementById('mechanic-activity');
    
    if (!activityList) return;
    
    fetch('../api/get_mechanic_activity.php')
        .then(response => response.json())
        .then(data => {
            console.log('Mechanic activity:', data);
            
            if (data.success && data.activity.length > 0) {
                let html = '';
                data.activity.forEach(act => {
                    html += `
                        <div style="display: flex; align-items: center; padding: 12px; border-bottom: 1px solid #f0f0f0;">
                            <div style="width: 40px; height: 40px; background: #10b98120; border-radius: 8px; display: flex; align-items: center; justify-content: center; margin-right: 12px;">
                                <i class="fas fa-check-circle" style="color: #10b981;"></i>
                            </div>
                            <div style="flex: 1;">
                                <div style="font-weight: 500;">${act.asset_name}</div>
                                <div style="font-size: 12px; color: #64748b;">${act.issue}</div>
                                ${act.completed_notes ? `<div style="font-size: 11px; color: #94a3b8; margin-top: 4px;">${act.completed_notes.substring(0, 50)}${act.completed_notes.length > 50 ? '...' : ''}</div>` : ''}
                            </div>
                            <div style="text-align: right;">
                                <div style="font-size: 12px; font-weight: 500; color: #10b981;">Completed</div>
                                <div style="font-size: 11px; color: #64748b;">${act.formatted_date}</div>
                            </div>
                        </div>
                    `;
                });
                activityList.innerHTML = html;
            } else {
                activityList.innerHTML = `
                    <div style="text-align: center; padding: 30px; color: #94a3b8;">
                        <i class="fas fa-check-circle" style="font-size: 40px; margin-bottom: 10px;"></i>
                        <p>No completed tasks in the last 7 days</p>
                    </div>
                `;
            }
        })
        .catch(error => {
            console.error('Error loading mechanic activity:', error);
            activityList.innerHTML = '<div class="error-state">Failed to load activity</div>';
        });
}
// SUPER SIMPLE COMPLETE FUNCTION - Just like Details button
function completeTask(id, assetName) {
    console.log('Completing task:', id, 'for vehicle:', assetName);
    
    // Simple prompt for notes with vehicle name included
    const notes = prompt(`Enter Completion Report for ${assetName} (Task #${id}):`);
    if (notes === null) return; // User clicked Cancel
    
    // Show loading on the button
    const btn = event?.target;
    const originalText = btn ? btn.innerHTML : 'Complete';
    if (btn) {
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
        btn.disabled = true;
    }
    
    // Call API - still only need to send the ID
    fetch('../api/complete_maintenance.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ 
            id: id, 
            notes: notes 
        })
    })
    .then(response => response.json())
    .then(data => {
        console.log('Response:', data);
        if (data.success) {
            alert(`Task #${id} for ${assetName} completed!`);
            location.reload(); // Simple reload to refresh everything
        } else {
            alert('Error: ' + (data.error || 'Unknown error'));
            if (btn) {
                btn.innerHTML = originalText;
                btn.disabled = false;
            }
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error: ' + error.message);
        if (btn) {
            btn.innerHTML = originalText;
            btn.disabled = false;
        }
    });
}

function refreshMechanicTasks() {
    loadMechanicTasks();
}

function viewAllMaintenance() {
    // Switch to maintenance tab
    document.querySelector('.tab[data-tab="maintenance"]').click();
}

function viewMaintenanceDetails(id) {
    console.log('Viewing maintenance details for ID:', id);
    
    // Fetch task details
    fetch(`../api/get_maintenance_details.php?id=${id}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showMaintenanceDetailsModal(data.data);
            } else {
                showNotification('Error: ' + (data.error || 'Could not load details'), 'error');
            }
        })
        .catch(error => {
            console.error('Error fetching details:', error);
            showNotification('Error loading details', 'error');
        });
}
function startMaintenance(id) {
    if (!confirm('Start this maintenance task?')) return;
    
    fetch('../api/start_maintenance.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id: id })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification('Maintenance started', 'success');
            loadMechanicTasks(); // Refresh the tasks list
        } else {
            showNotification('Error: ' + data.error, 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('Error: ' + error.message, 'error');
    });
}
function showMaintenanceDetailsModal(task) {
    // Create modal if it doesn't exist
    let modal = document.getElementById('maintenanceDetailsModal');
    if (!modal) {
        modal = document.createElement('div');
        modal.id = 'maintenanceDetailsModal';
        modal.className = 'modal';
        modal.innerHTML = `
            <div class="modal-content" style="max-width: 500px;">
                <div class="modal-header">
                    <h3><i class="fas fa-info-circle"></i> Maintenance Task Details</h3>
                    <button class="modal-close" onclick="closeMaintenanceDetailsModal()">&times;</button>
                </div>
                <div class="modal-body" id="maintenanceDetailsBody">
                    <!-- Content will be loaded here -->
                </div>
                <div class="modal-footer" style="padding: 15px; text-align: right; border-top: 1px solid #e9eef2;">
                    <button class="btn btn-secondary" onclick="closeMaintenanceDetailsModal()">Close</button>
                </div>
            </div>
        `;
        document.body.appendChild(modal);
    }
    
    // Format dates
    const created = new Date(task.created_at).toLocaleString();
    const dueDate = new Date(task.due_date).toLocaleDateString();
    const started = task.started_at ? new Date(task.started_at).toLocaleString() : 'Not started';
    const completed = task.completed_date ? new Date(task.completed_date).toLocaleDateString() : 'Not completed';
    
    // Determine status color
    let statusColor = '#6b7280';
    if (task.status === 'completed') statusColor = '#10b981';
    else if (task.status === 'in_progress') statusColor = '#3b82f6';
    else if (task.status === 'pending') statusColor = '#f59e0b';
    
    // Fill modal content
    document.getElementById('maintenanceDetailsBody').innerHTML = `
        <div style="padding: 10px;">
            <div style="background: #f8fafc; border-radius: 8px; padding: 15px; margin-bottom: 15px;">
                <h4 style="margin: 0 0 10px 0; color: #1e293b;">${task.asset_name}</h4>
                <p style="margin: 0; color: #475569;">${task.issue}</p>
            </div>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-bottom: 15px;">
                <div style="background: #f8fafc; padding: 10px; border-radius: 6px;">
                    <div style="font-size: 11px; color: #64748b;">Priority</div>
                    <div style="font-weight: 500; color: ${task.priority === 'high' ? '#ef4444' : task.priority === 'medium' ? '#f59e0b' : '#6b7280'};">
                        ${task.priority.toUpperCase()}
                    </div>
                </div>
                <div style="background: #f8fafc; padding: 10px; border-radius: 6px;">
                    <div style="font-size: 11px; color: #64748b;">Issue Type</div>
                    <div style="font-weight: 500;">${task.issue_type}</div>
                </div>
                <div style="background: #f8fafc; padding: 10px; border-radius: 6px;">
                    <div style="font-size: 11px; color: #64748b;">Status</div>
                    <div style="font-weight: 500; color: ${statusColor};">${task.status.replace('_', ' ')}</div>
                </div>
                <div style="background: #f8fafc; padding: 10px; border-radius: 6px;">
                    <div style="font-size: 11px; color: #64748b;">Est. Hours</div>
                    <div style="font-weight: 500;">${task.estimated_hours || 'N/A'}</div>
                </div>
            </div>
            
            <div style="margin-bottom: 15px;">
                <div style="background: #f8fafc; padding: 10px; border-radius: 6px; margin-bottom: 5px;">
                    <div style="font-size: 11px; color: #64748b;">Due Date</div>
                    <div style="font-weight: 500;">${dueDate}</div>
                </div>
                <div style="background: #f8fafc; padding: 10px; border-radius: 6px; margin-bottom: 5px;">
                    <div style="font-size: 11px; color: #64748b;">Created</div>
                    <div style="font-weight: 500;">${created}</div>
                </div>
                <div style="background: #f8fafc; padding: 10px; border-radius: 6px; margin-bottom: 5px;">
                    <div style="font-size: 11px; color: #64748b;">Started</div>
                    <div style="font-weight: 500;">${started}</div>
                </div>
                <div style="background: #f8fafc; padding: 10px; border-radius: 6px;">
                    <div style="font-size: 11px; color: #64748b;">Completed</div>
                    <div style="font-weight: 500;">${completed}</div>
                </div>
            </div>
            
            ${task.completed_notes ? `
            <div style="background: #f8fafc; padding: 10px; border-radius: 6px;">
                <div style="font-size: 11px; color: #64748b;">Completion Notes</div>
                <div style="font-weight: 500;">${task.completed_notes}</div>
            </div>
            ` : ''}
        </div>
    `;
    
    modal.style.display = 'flex';
}

function closeMaintenanceDetailsModal() {
    const modal = document.getElementById('maintenanceDetailsModal');
    if (modal) modal.style.display = 'none';
}



// ============================================
// STYLES
// ============================================
// Add this INSIDE your existing DOMContentLoaded event
document.addEventListener('DOMContentLoaded', function() {
    console.log('Fleet dashboard loaded');
    
    // Load all data
    loadFleetData();
    
    // Initialize UI
    setupEventListeners();
    initTabs();
    
    // Start real-time updates
    startRealTimeUpdates();
    
    // Load role-specific data - ONLY for drivers
    const userRole = document.body.dataset.userRole;
    if (userRole === 'driver') {
        loadDriverAssignment();
        loadDriverStats();
        loadDriverTrips();
    }
    
    // ===== ADD THIS SECTION =====
    // Load vehicles into reservation filter
    loadReservationVehicleFilter();
    
    // Add filter event listeners
    const statusFilter = document.getElementById('reservation-status-filter');
    const vehicleFilter = document.getElementById('reservation-vehicle-filter');
    // Add this INSIDE your existing DOMContentLoaded event (around where you added the reservation filters)

// Vehicle filter event listeners
const typeFilter = document.getElementById('vehicleTypeFilter');
const searchInput = document.getElementById('searchFleet');

if (typeFilter) {
    typeFilter.addEventListener('change', applyVehicleFilters);
    console.log('✅ Vehicle type filter listener added');
}

if (statusFilter) {
    statusFilter.addEventListener('change', applyVehicleFilters);
    console.log('✅ Vehicle status filter listener added');
}

if (searchInput) {
    searchInput.addEventListener('input', debouncedSearch);
    console.log('✅ Vehicle search listener added');
}
    
    if (statusFilter) {
        statusFilter.addEventListener('change', filterReservations);
        console.log('✅ Status filter listener added');
    } else {
        console.warn('❌ Status filter element not found - check ID="reservation-status-filter"');
    }
    
    if (vehicleFilter) {
        vehicleFilter.addEventListener('change', filterReservations);
        console.log('✅ Vehicle filter listener added');
    } else {
        console.warn('❌ Vehicle filter element not found - check ID="reservation-vehicle-filter"');
    }
    // ===== END ADDED SECTION =====
    
    const activeTab = document.querySelector('.tab.active');
    
    // Check if overview tab is active and user is not driver
    if (userRole !== 'driver' && activeTab && activeTab.dataset.tab === 'overview') {
        console.log('Overview tab active on load, loading trip history...');
        setTimeout(loadTripHistory, 1000);
    }
    
    // Reservations tab listener
    const reservationsTab = document.querySelector('.tab[data-tab="reservations"]');
    if (reservationsTab) {
        reservationsTab.addEventListener('click', function() {
            console.log('Reservations tab clicked - loading verifications');
            setTimeout(loadVehicleReservations, 100);
            setTimeout(loadPendingVerifications, 200);
        });
    } else {
        console.log('Reservations tab not found - user is likely driver or mechanic');
    }
    
    // Check if reservations tab is active on page load
    if (activeTab && activeTab.dataset.tab === 'reservations' && reservationsTab) {
        console.log('Reservations tab active on load');
        setTimeout(loadPendingVerifications, 500);
    }
});
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
    .loading-spinner {
        text-align: center;
        padding: 30px;
        color: #64748b;
    }
    .loading-spinner i {
        margin-right: 8px;
    }
    .empty-state {
        text-align: center;
        padding: 40px 20px;
        color: #94a3b8;
    }
    .error-state {
        text-align: center;
        padding: 40px 20px;
        color: #ef4444;
        background: #fee2e2;
        border-radius: 8px;
    }
`;
document.head.appendChild(style);
// Force load mechanic tasks when role is mechanic
document.addEventListener('DOMContentLoaded', function() {
    if (document.body.dataset.userRole === 'mechanic') {
        console.log('Mechanic detected, loading tasks...');
        setTimeout(function() {
            if (typeof loadMechanicTasks === 'function') {
                loadMechanicTasks();
            } else {
                console.error('loadMechanicTasks function not found!');
            }
        }, 1000);
    }
});
// Simple toggle
function toggleSidebar() {
    const sidebar = document.querySelector('.sidebar');
    if (sidebar) {
        if (sidebar.style.display === 'none' || sidebar.style.display === '') {
            sidebar.style.display = 'block';
        } else {
            sidebar.style.display = 'none';
        }
    }
}
