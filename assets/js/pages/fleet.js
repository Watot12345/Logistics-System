// assets/js/fleet.js - Combined Fleet Management JavaScript

// ============================================
// INITIALIZATION
// ============================================
// Add this at the very beginning of your fleet.js


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
    
    // 🔴 YOU ARE MISSING THIS LINE:
    const activeTab = document.querySelector('.tab.active');
    
    // Check if overview tab is active and user is not driver
    if (userRole !== 'driver' && activeTab && activeTab.dataset.tab === 'overview') {
        console.log('Overview tab active on load, loading trip history...');
        setTimeout(loadTripHistory, 1000);
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

// Add to your fleet.js
function openAssignMechanicModal(id, vehicleName, issue) {
    document.getElementById('maintenanceId').value = id;
    document.getElementById('modalVehicleName').textContent = vehicleName;
    document.getElementById('modalIssue').textContent = issue;
    
    // Set default due date (7 days from now)
    const dueDate = new Date();
    dueDate.setDate(dueDate.getDate() + 7);
    document.getElementById('dueDate').value = dueDate.toISOString().split('T')[0];
    
    document.getElementById('assignMechanicModal').style.display = 'flex';
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
function closeCreateMaintenanceModal() {
    document.getElementById('createMaintenanceModal').style.display = 'none';
}
function openCreateMaintenanceModal() {
    console.log('Opening create maintenance modal');
    
    // Set default due date (7 days from now)
    const dueDate = new Date();
    dueDate.setDate(dueDate.getDate() + 7);
    const dueDateInput = document.getElementById('createDueDate');
    if (dueDateInput) {
        dueDateInput.value = dueDate.toISOString().split('T')[0];
    }
    
    // Show the modal
    const modal = document.getElementById('createMaintenanceModal');
    if (modal) {
        modal.style.display = 'flex';
    } else {
        console.error('Create maintenance modal not found');
        alert('Maintenance modal not found. Please refresh the page.');
    }
}

function closeCreateMaintenanceModal() {
    const modal = document.getElementById('createMaintenanceModal');
    if (modal) {
        modal.style.display = 'none';
    }
}

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
// Also make sure you have these other maintenance functions if needed
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
            loadMaintenanceReport();
        } else {
            showNotification('Error: ' + data.error, 'error');
        }
    });
}

function completeMaintenance(id) {
    const notes = prompt('Enter completion notes:');
    
    fetch('../api/complete_maintenance.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id: id, notes: notes })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification('Maintenance completed', 'success');
            loadMaintenanceReport();
        } else {
            showNotification('Error: ' + data.error, 'error');
        }
    });
}
function openCompleteMaintenanceModal(id) {
    document.getElementById('completeMaintenanceId').value = id;
    document.getElementById('completeMaintenanceModal').style.display = 'flex';
}

function closeCompleteMaintenanceModal() {
    document.getElementById('completeMaintenanceModal').style.display = 'none';
}

function completeMaintenance(event) {
    event.preventDefault();
    
    const data = {
        id: document.getElementById('completeMaintenanceId').value,
        notes: document.getElementById('completionNotes').value
    };
    
    fetch('../api/complete_maintenance.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(data)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification('Maintenance completed', 'success');
            closeCompleteMaintenanceModal();
            loadMaintenanceReport();
        } else {
            showNotification('Error: ' + data.error, 'error');
        }
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
                        <button class="btn btn-success" onclick="openCompleteMaintenanceModal(${task.id})" style="flex: 1;">
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

function updateMechanicStats(tasks) {
    const statsDiv = document.getElementById('mechanic-stats');
    if (!statsDiv) return;
    
    const completed = tasks.filter(t => t.status === 'completed').length;
    const inProgress = tasks.filter(t => t.status === 'in_progress').length;
    const total = tasks.length;
    const efficiency = total > 0 ? Math.round((completed / total) * 100) : 0;
    
    statsDiv.innerHTML = `
        <div class="stat-mini">
            <div class="value">${completed}</div>
            <div class="label">Completed</div>
        </div>
        <div class="stat-mini">
            <div class="value">${inProgress}</div>
            <div class="label">In Progress</div>
        </div>
        <div class="stat-mini">
            <div class="value">${efficiency}%</div>
            <div class="label">Efficiency</div>
        </div>
    `;
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