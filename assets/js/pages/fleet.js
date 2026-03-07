// assets/js/fleet.js - Combined Fleet Management JavaScript

// ============================================
// INITIALIZATION
// ============================================

document.addEventListener('DOMContentLoaded', function() {
    console.log('Fleet dashboard loaded');
    
    // Load all data
    loadFleetData();
    
    // Initialize UI
    setupEventListeners();
    initTabs();
    
    // Start real-time updates
    startRealTimeUpdates();
    
    // Load role-specific data
    loadDriverAssignment();
    loadDriverStats();
    loadDriverTrips();
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
    // Tab switching
    document.querySelectorAll('.tab').forEach(tab => {
        tab.addEventListener('click', function() {
            const tabId = this.dataset.tab;
            switchTab(tabId);
        });
    });
    
    // Reservations tab specific listener
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
            if (tabOverview) tabOverview.style.display = 'block';
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
                    statusBadge.textContent = a.shipment_status.toUpperCase();
                    statusBadge.className = 'card-badge ' + 
                        (a.shipment_status === 'in_transit' ? 'status-warning' : 'status-info');
                    
                    content.innerHTML = `
                        <div class="assignment-details">
                            <div class="detail-row">
                                <strong>Vehicle:</strong> ${a.vehicle_name} (${a.vehicle_condition}% condition)
                            </div>
                            <div class="detail-row">
                                <strong>Customer:</strong> ${a.customer_name || 'N/A'}
                            </div>
                            <div class="detail-row">
                                <strong>Delivery Address:</strong> ${a.delivery_address || 'N/A'}
                            </div>
                            <div class="detail-row">
                                <strong>Current Location:</strong> ${a.current_location || 'Not started'}
                            </div>
                            <div class="detail-row">
                                <strong>Estimated Arrival:</strong> ${a.estimated_arrival ? new Date(a.estimated_arrival).toLocaleString() : 'N/A'}
                            </div>
                        </div>
                    `;
                } else {
                    statusBadge.textContent = 'NO ASSIGNMENT';
                    statusBadge.className = 'card-badge status-warning';
                    content.innerHTML = `
                        <div style="text-align: center; padding: 30px;">
                            <div style="background-color: #fff3cd; border: 1px solid #ffeeba; color: #856404; border-radius: 8px; padding: 20px; margin-bottom: 20px;">
                                <i class="fas fa-exclamation-triangle" style="font-size: 48px; margin-bottom: 16px; color: #856404;"></i>
                                <h3 style="margin-bottom: 10px; color: #856404;">No Active Assignment</h3>
                                <p style="margin-bottom: 15px;">${data.message || 'You don\'t have any active assignments at the moment.'}</p>
                                <p style="font-size: 13px; color: #666;">Please contact your dispatcher to get assigned to a shipment.</p>
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
            
            // Log debug info to console
            if (data.debug) {
                console.log('Debug info:', data.debug);
            }
            
            if (data.success) {
                if (data.trips && data.trips.length > 0) {
                    let html = '';
                    data.trips.forEach(trip => {
                        let statusColor = '#6b7280';
                        let statusBg = '#6b728020';
                        let icon = 'truck';
                        
                        if (trip.shipment_status === 'delivered' || trip.shipment_status === 'completed') {
                            statusColor = '#10b981';
                            statusBg = '#10b98120';
                            icon = 'check-circle';
                        } else if (trip.shipment_status === 'in_transit' || trip.shipment_status === 'in-progress') {
                            statusColor = '#f59e0b';
                            statusBg = '#f59e0b20';
                            icon = 'play-circle';
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
                                        ${trip.shipment_status || 'pending'}
                                    </span>
                                </div>
                            </div>
                        `;
                    });
                    tripHistory.innerHTML = html;
                } else {
                    // Show more detailed message if debug info is available
                    let debugMessage = '';
                    if (data.debug) {
                        debugMessage = `<p style="font-size: 12px; color: #999; margin-top: 10px;">
                            Debug: Driver ID ${data.debug.driver_id}, Records: ${data.debug.total_records || 0}
                        </p>`;
                    }
                    
                    tripHistory.innerHTML = `
                        <div style="text-align: center; padding: 40px; background-color: #f8f9fa; border-radius: 8px;">
                            <i class="fas fa-info-circle" style="font-size: 48px; color: #17a2b8; margin-bottom: 16px;"></i>
                            <h3 style="color: #17a2b8; margin-bottom: 10px;">No Trip History</h3>
                            <p style="color: #6c757d;">You don't have any trip history yet.</p>
                            ${debugMessage}
                        </div>
                    `;
                }
            } else {
                tripHistory.innerHTML = '<div class="error-state">Error: ' + (data.error || 'Unknown error') + '</div>';
            }
        })
        .catch(error => {
            console.error('Error loading trips:', error);
            tripHistory.innerHTML = '<div class="error-state">Failed to load trips: ' + error.message + '</div>';
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

function updateStatus(newStatus) {
    // First, get the current assignment to get the dispatch_schedule ID
    fetch('../api/get_driver_assignment.php')
        .then(response => response.json())
        .then(data => {
            if (data.success && data.has_assignment) {
                const assignment = data.assignment;
                const assignmentId = assignment.id; // This is the dispatch_schedule ID
                
                // Ask for current location if starting trip or delivering
                let currentLocation = '';
                if (newStatus === 'in_transit' || newStatus === 'delivered') {
                    currentLocation = prompt('Enter current location:');
                    if (!currentLocation) return;
                }
                
                // Map status for dispatch_schedule
                let dispatchStatus = 'scheduled';
                if (newStatus === 'in_transit') {
                    dispatchStatus = 'in-progress';
                } else if (newStatus === 'delivered') {
                    dispatchStatus = 'completed';
                }
                
                // Show loading notification
                showNotification('Updating status...', 'info');
                
                // Update the dispatch schedule status
                fetch('../api/update_dispatch_status.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        schedule_id: assignmentId,
                        status: dispatchStatus,
                        current_location: currentLocation,
                        new_status: newStatus // Pass the original status for shipment update
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        let message = '';
                        if (newStatus === 'in_transit') {
                            message = 'Trip started successfully!';
                        } else if (newStatus === 'delivered') {
                            message = 'Delivery completed successfully!';
                        }
                        
                        showNotification(message, 'success');
                        
                        // Refresh the assignment display
                        setTimeout(() => {
                            loadDriverAssignment();
                            loadDriverTrips();
                        }, 500);
                    } else {
                        showNotification('Error: ' + (data.error || 'Unknown error'), 'error');
                    }
                })
                .catch(error => {
                    console.error('Fetch error:', error);
                    showNotification('Error: ' + error.message, 'error');
                });
            } else {
                showNotification('No active assignment found', 'error');
            }
        })
        .catch(error => {
            console.error('Error getting assignment:', error);
            showNotification('Error: ' + error.message, 'error');
        });
}

function updateLocation() {
    // First, get the current assignment
    fetch('../api/get_driver_assignment.php')
        .then(response => response.json())
        .then(data => {
            if (data.success && data.has_assignment) {
                const assignment = data.assignment;
                const assignmentId = assignment.id;
                
                const currentLocation = prompt('Enter current location:');
                if (!currentLocation) return;
                
                // Update location
                fetch('../api/update_dispatch_location.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        schedule_id: assignmentId,
                        current_location: currentLocation
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showNotification('Location updated successfully!', 'success');
                        loadDriverAssignment();
                    } else {
                        showNotification('Error: ' + (data.error || 'Unknown error'), 'error');
                    }
                })
                .catch(error => {
                    showNotification('Error: ' + error.message, 'error');
                });
            } else {
                showNotification('No active assignment found', 'error');
            }
        })
        .catch(error => {
            console.error('Error getting assignment:', error);
            showNotification('Error: ' + error.message, 'error');
        });
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
    
    fetch('../api/get_reservations.php')
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            console.log('Reservations data received:', data);
            
            if (data.success && data.reservations) {
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
    
    // Find all the containers
    const allReservationsContainer = document.querySelector('#tab-reservations .card-full .reservation-list') || 
                                     document.querySelector('#tab-reservations .reservation-list');
    
    const approvedContainer = document.querySelector('#tab-reservations .dashboard-grid .card:first-child .reservation-list');
    const rejectedContainer = document.querySelector('#tab-reservations .dashboard-grid .card:last-child .reservation-list');
    
    if (!allReservationsContainer) {
        console.error('Could not find reservations container');
        return;
    }
    
    // Filter reservations by status
    const approved = reservations.filter(r => r.status === 'approved');
    const rejected = reservations.filter(r => r.status === 'rejected');
    const pending = reservations.filter(r => r.status === 'pending');
    
    console.log(`Stats - Total: ${reservations.length}, Approved: ${approved.length}, Rejected: ${rejected.length}, Pending: ${pending.length}`);
    
    // Update badges
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
    
    // Display all reservations
    if (reservations.length === 0) {
        allReservationsContainer.innerHTML = '<div class="empty-state">No reservations found</div>';
    } else {
        let allHtml = '';
        reservations.forEach(res => {
            // Your data structure uses 'from' and 'to' which already have formatted dates
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
                                <button onclick="approveReservation(${res.id})" class="btn-small" style="background-color: #10b981; color: white; border: none; padding: 5px 10px; border-radius: 4px; margin-right: 5px; cursor: pointer;">
                                    <i class="fas fa-check"></i> Approve
                                </button>
                                <button onclick="rejectReservation(${res.id})" class="btn-small" style="background-color: #ef4444; color: white; border: none; padding: 5px 10px; border-radius: 4px; cursor: pointer;">
                                    <i class="fas fa-times"></i> Reject
                                </button>
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
                        <div style="font-weight: 500;">${res.vehicle_name}</div>
                        <div style="font-size: 12px; color: #666;">${res.requester} • ${res.from}</div>
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
                        <div style="font-weight: 500;">${res.vehicle_name}</div>
                        <div style="font-size: 12px; color: #666;">${res.requester} • ${res.from}</div>
                    </div>
                `;
            });
            rejectedContainer.innerHTML = rejectedHtml;
        }
    }
}

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
                        ${v.asset_name} (Condition: ${v.asset_condition}%) - Available Now
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

// Update the openReservationModal function
function loadVehicleAvailability() {
    const vehicleList = document.querySelector('.vehicle-list');
    if (!vehicleList) return;
    
    vehicleList.innerHTML = '<div class="loading-spinner"><i class="fas fa-spinner fa-spin"></i> Loading vehicles...</div>';
    
    fetch('../api/get_vehicles.php')
        .then(response => {
            if (!response.ok) throw new Error('Network response was not ok');
            return response.json();
        })
        .then(data => {
            if (data.success && data.vehicles.length > 0) {
                displayVehicles(data.vehicles);
            } else {
                vehicleList.innerHTML = '<div class="empty-state">No vehicles available</div>';
            }
        })
        .catch(error => {
            console.error('Error loading vehicles:', error);
            vehicleList.innerHTML = '<div class="error-state">Failed to load vehicles</div>';
        });
}

function displayVehicles(vehicles) {
    const vehicleList = document.querySelector('.vehicle-list');
    if (!vehicleList) return;
    
    let html = '';
    vehicles.forEach(vehicle => {
        // Determine status based on actual data
        let availability, statusText, statusIcon, statusColor, extraInfo = '';
        
        if (vehicle.has_pending_maintenance) {
            availability = 'maintenance';
            statusText = 'IN MAINTENANCE';
            statusIcon = 'wrench';
            statusColor = '#ef4444'; // red
            extraInfo = `<div style="font-size: 11px; color: #ef4444; margin-top: 5px;">
                <i class="fas fa-exclamation-triangle"></i> 
                Maintenance: ${vehicle.maintenance_issue || 'Scheduled'} 
                (Due: ${vehicle.due_date || 'N/A'})
            </div>`;
        } else if (vehicle.is_in_use) {
            availability = 'in-use';
            statusText = 'IN USE';
            statusIcon = 'play-circle';
            statusColor = '#f59e0b'; // orange
            extraInfo = `<div style="font-size: 11px; color: #f59e0b; margin-top: 5px;">
                <i class="fas fa-user"></i> 
                Driver: ${vehicle.current_driver || 'Unknown'}
            </div>`;
        } else {
            availability = 'available';
            statusText = 'AVAILABLE';
            statusIcon = 'check-circle';
            statusColor = '#10b981'; // green
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
                            ${vehicle.current_driver ? `<span><i class="fas fa-user"></i> ${vehicle.current_driver}</span>` : ''}
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
                                <div>
                                    <button class="btn-icon" onclick="editAssignment(${assignment.id})" style="background: none; border: none; color: #64748b; cursor: pointer; padding: 8px; border-radius: 4px;" title="Edit Assignment">
                                        <i class="fas fa-edit"></i>
                                    </button>
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

// ============================================
// DELAY ANALYSIS FUNCTIONS
// ============================================

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
    
    activityTimeline.innerHTML = '<div class="loading-spinner"><i class="fas fa-spinner fa-spin"></i> Loading activity...</div>';
    
    fetch('../api/get_driver_activity.php')
        .then(response => response.json())
        .then(data => {
            if (data.success && data.activities.length > 0) {
                displayDriverActivity(data.activities);
            } else {
                activityTimeline.innerHTML = '<div class="empty-state">No driver activity</div>';
            }
        })
        .catch(error => {
            console.error('Error loading driver activity:', error);
            activityTimeline.innerHTML = '<div class="error-state">Failed to load activity</div>';
        });
}

function displayDriverActivity(activities) {
    const activityTimeline = document.querySelector('.activity-timeline');
    if (!activityTimeline) return;
    
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

function updateDriverActivity() {
    console.log('Updating driver activity...');
}

// ============================================
// TRIP HISTORY FUNCTIONS
// ============================================

function loadTripHistory() {
    const tripList = document.querySelector('.trip-list');
    if (!tripList) return;
    
    tripList.innerHTML = '<div class="loading-spinner"><i class="fas fa-spinner fa-spin"></i> Loading trip history...</div>';
    
    fetch('../api/get_trip_history.php?limit=10')
        .then(response => response.json())
        .then(data => {
            if (data.success && data.trips.length > 0) {
                displayTripHistory(data.trips);
            } else {
                tripList.innerHTML = '<div class="empty-state">No trip history available</div>';
            }
        })
        .catch(error => {
            console.error('Error loading trip history:', error);
            tripList.innerHTML = '<div class="error-state">Failed to load trip history</div>';
        });
}

function displayTripHistory(trips) {
    const tripList = document.querySelector('.trip-list');
    if (!tripList) return;
    
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
// STYLES
// ============================================

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