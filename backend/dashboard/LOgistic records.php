<div class="tracking-list">
            <?php
            $shipments = getActiveShipments($conn, 10);
            
            if ($shipments && $shipments->num_rows > 0) {
                while ($shipment = $shipments->fetch_assoc()) {
                    $progress = getShipmentProgress($shipment);
                    $status_color = [
                        'pending' => 'warning',
                        'in_transit' => 'info',
                        'delivered' => 'success',
                        'delayed' => 'danger'
                    ][$shipment['shipment_status']] ?? 'secondary';
                    ?>
                    <div class="tracking-item">
                        <div class="tracking-header">
                            <div class="tracking-info">
                                <span class="tracking-number">#<?php echo $shipment['order_number']; ?></span>
                                <span class="badge badge-<?php echo $status_color; ?>">
                                    <?php echo ucfirst($shipment['shipment_status']); ?>
                                </span>
                            </div>
                            <span class="tracking-time">
                                <?php echo date('M d, H:i', strtotime($shipment['departure_time'] ?? $shipment['created_at'])); ?>
                            </span>
                        </div>
                        
                        <div class="tracking-details">
                            <div class="customer-info">
                                <i class="fas fa-user"></i>
                                <span><?php echo htmlspecialchars($shipment['customer_name']); ?></span>
                            </div>
                            <div class="location-info">
                                <i class="fas fa-map-marker-alt"></i>
                                <span><?php echo htmlspecialchars($shipment['delivery_address']); ?></span>
                            </div>
                            <div class="driver-info">
                                <i class="fas fa-user-tie"></i>
                                <span><?php echo htmlspecialchars($shipment['driver_name']); ?></span>
                                <small>(<?php echo htmlspecialchars($shipment['vehicle_plate']); ?>)</small>
                            </div>
                        </div>
                        
                        <div class="tracking-progress">
                            <div class="progress-steps">
                                <div class="step <?php echo $progress >= 20 ? 'completed' : ''; ?>">
                                    <i class="fas fa-clipboard-check"></i>
                                    <span>Pending</span>
                                </div>
                                <div class="step <?php echo $progress >= 60 ? 'completed' : ''; ?>">
                                    <i class="fas fa-truck"></i>
                                    <span>In Transit</span>
                                </div>
                                <div class="step <?php echo $progress >= 100 ? 'completed' : ''; ?>">
                                    <i class="fas fa-check-circle"></i>
                                    <span>Delivered</span>
                                </div>
                            </div>
                            
                            <?php if ($shipment['shipment_status'] == 'in_transit'): ?>
                            <div class="current-location">
                                <i class="fas fa-location-dot"></i>
                                <span>Current: <?php echo htmlspecialchars($shipment['current_location'] ?? 'On route'); ?></span>
                                <?php if ($shipment['estimated_arrival']): ?>
                                <small>ETA: <?php echo date('M d, H:i', strtotime($shipment['estimated_arrival'])); ?></small>
                                <?php endif; ?>
                            </div>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Admin/Employee actions -->
                        <div class="tracking-actions">
                            <?php if ($_SESSION['role'] == 'admin' || $_SESSION['role'] == 'employee'): ?>
                                <button class="btn-sm" onclick="updateStatus(<?php echo $shipment['shipment_id']; ?>)">
                                    <i class="fas fa-edit"></i> Update
                                </button>
                                <button class="btn-sm" onclick="trackShipment(<?php echo $shipment['shipment_id']; ?>)">
                                    <i class="fas fa-map"></i> Track
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php
                }
            } else {
                ?>
                <div class="empty-state">
                    <i class="fas fa-truck fa-3x"></i>
                    <p>No active shipments</p>
                </div>
                <?php
            }
            ?>
        </div>