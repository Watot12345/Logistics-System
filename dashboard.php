<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}
require 'config/db.php';
$page_title = 'Dashboard | Logistics System';
$page_css = 'assets/css/style.css';
include 'includes/header.php';
include '../dashboard/maintenance.php';

$user_role = $_SESSION['role'] ?? 'employee';

// Handle CRUD operations for admin only
$message = '';
$message_type = '';

try {
    $stmt = $pdo->query("SELECT * FROM assets ORDER BY created_at DESC");
    $assets = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $assets = [];
}

try {
    $stmt = $pdo->query("SELECT * FROM maintenance_alerts ORDER BY due_date ASC");
    $alerts = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $alerts = [];
}

// Count assets + alerts for total
$totalItems = count($assets) + count($alerts);

// Initialize counts
$lifecycleCounts = [
    'Operational' => 0,
    'Maintenance - Pending' => 0,
    'Maintenance - Overdue' => 0,
    'Maintenance - Done' => 0,
    'End-of-Life' => 0,
    'Retired' => 0
];

// Count assets per stage
foreach ($assets as $asset) {
    $stage = $asset['lifecycle_stage'] ?? 'Operational';
    if (isset($lifecycleCounts[$stage])) {
        $lifecycleCounts[$stage]++;
    }
}

// Count maintenance alerts by stage
$now = new DateTime();
foreach ($alerts as $alert) {
    if ($alert['status'] === 'done') {
        $lifecycleCounts['Maintenance - Done']++;
    } elseif ($alert['status'] === 'pending') {
        $dueDate = new DateTime($alert['due_date']);
        if ($dueDate < $now) {
            $lifecycleCounts['Maintenance - Overdue']++;
        } else {
            $lifecycleCounts['Maintenance - Pending']++;
        }
    }
}

// Prepare data for UI
$lifecycleData = [];
foreach ($lifecycleCounts as $stage => $count) {
    $percentage = ($totalItems > 0) ? round(($count / $totalItems) * 100) : 0;
    $lifecycleData[] = [
        'stage' => $stage,
        'count' => $count,
        'percentage' => $percentage
    ];
}

if ($user_role === 'admin') {
    // CREATE: Add new asset
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create_asset') {
        $asset_name = trim($_POST['asset_name']);
        $asset_type = trim($_POST['asset_type']);
        $status = trim($_POST['status']);
        $asset_condition = trim($_POST['asset_condition']);
        
        if (!empty($asset_name) && !empty($asset_type)) {
            try {
                $stmt = $pdo->prepare("INSERT INTO assets (asset_name, asset_type, status, asset_condition, created_at) VALUES (?, ?, ?, ?, NOW())");
                $stmt->execute([$asset_name, $asset_type, $status, $asset_condition]);
                $message = 'Asset created successfully!';
                $message_type = 'success';
            } catch (PDOException $e) {
                $message = 'Error creating asset: ' . $e->getMessage();
                $message_type = 'error';
            }
        }
    }
    
    // UPDATE: Edit asset
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_asset') {
        $asset_id = $_POST['asset_id'];
        $asset_name = trim($_POST['asset_name']);
        $asset_type = trim($_POST['asset_type']);
        $status = trim($_POST['status']);
        $asset_condition = trim($_POST['asset_condition']);
        
        if (!empty($asset_id) && !empty($asset_name)) {
            try {
                $stmt = $pdo->prepare("UPDATE assets SET asset_name = ?, asset_type = ?, status = ?, asset_condition = ? WHERE id = ?");
                $stmt->execute([$asset_name, $asset_type, $status, $asset_condition, $asset_id]);
                $message = 'Asset updated successfully!';
                $message_type = 'success';
            } catch (PDOException $e) {
                $message = 'Error updating asset: ' . $e->getMessage();
                $message_type = 'error';
            }
        }
    }
    
    // DELETE: Remove asset
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_asset') {
        $asset_id = $_POST['asset_id'];
        
        if (!empty($asset_id)) {
            try {
                $stmt = $pdo->prepare("DELETE FROM assets WHERE id = ?");
                $stmt->execute([$asset_id]);
                $message = 'Asset deleted successfully!';
                $message_type = 'success';
            } catch (PDOException $e) {
                $message = 'Error deleting asset: ' . $e->getMessage();
                $message_type = 'error';
            }
        }
    }
    
    if ($user_role === 'admin') {
    // CREATE: Add new maintenance alert
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create_alert') {
        $asset_name = trim($_POST['asset_name']);
        $issue = trim($_POST['issue']);
        $priority = trim($_POST['priority']);
        $due_date = trim($_POST['due_date']);
        
        if (!empty($asset_name) && !empty($issue) && !empty($priority) && !empty($due_date)) {
            try {
                $stmt = $pdo->prepare("INSERT INTO maintenance_alerts (asset_name, issue, priority, due_date, created_at) VALUES (?, ?, ?, ?, NOW())");
                $stmt->execute([$asset_name, $issue, $priority, $due_date]);
                $message = 'Maintenance alert created successfully!';
                $message_type = 'success';
            } catch (PDOException $e) {
                $message = 'Error creating maintenance alert: ' . $e->getMessage();
                $message_type = 'error';
            }
        }
    }
    
    // UPDATE: Edit maintenance alert
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_alert') {
        $alert_id = $_POST['alert_id'];
        $asset_name = trim($_POST['asset_name']);
        $issue = trim($_POST['issue']);
        $priority = trim($_POST['priority']);
        $due_date = trim($_POST['due_date']);
        
        if (!empty($alert_id) && !empty($asset_name)) {
            try {
                $stmt = $pdo->prepare("UPDATE maintenance_alerts SET asset_name = ?, issue = ?, priority = ?, due_date = ? WHERE id = ?");
                $stmt->execute([$asset_name, $issue, $priority, $due_date, $alert_id]);
                $message = 'Maintenance alert updated successfully!';
                $message_type = 'success';
            } catch (PDOException $e) {
                $message = 'Error updating maintenance alert: ' . $e->getMessage();
                $message_type = 'error';
            }
        }
    }
    
     if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'complete_alert') {
    $alert_id = $_POST['alert_id'];
    if (!empty($alert_id)) {
        try {
            $stmt = $pdo->prepare("UPDATE maintenance_alerts SET status = 'done', completed_date = NOW() WHERE id = ?");
            $stmt->execute([$alert_id]);
            $message = 'Alert marked as completed!';
            $message_type = 'success';
        } catch (PDOException $e) {
            $message = 'Error updating alert: ' . $e->getMessage();
            $message_type = 'error';
        }
    }
}

    // DELETE: Remove maintenance alert
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_alert') {
        $alert_id = $_POST['alert_id'];
        
        if (!empty($alert_id)) {
            try {
                $stmt = $pdo->prepare("DELETE FROM maintenance_alerts WHERE id = ?");
                $stmt->execute([$alert_id]);
                $message = 'Maintenance alert deleted successfully!';
                $message_type = 'success';
            } catch (PDOException $e) {
                $message = 'Error deleting maintenance alert: ' . $e->getMessage();
                $message_type = 'error';
            }
        }
    }
}

    
    // Fetch activity logs for admin
    try {
        $logs_result = $pdo->query("
            SELECT 
                u.username as user,
                'Asset' as document,
                'Viewed' as action_type,
                u.last_login as time
            FROM users u
            WHERE u.last_login IS NOT NULL
            ORDER BY u.last_login DESC
            LIMIT 10
        ");
        $activity_logs = $logs_result->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $activity_logs = [];
    }
}
?>


<main>
    <!-- Page Content -->
    <div class="main-content">
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
                    <span class="user-name-header">
                        <?php echo htmlspecialchars($_SESSION['full_name']); ?>
                    </span>

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
                    <h1>Logistics Dashboard</h1>
                    <p>Monitor assets, maintenance, and documentation</p>
                </div>
            </div>
        </div>

        <!-- Message Display -->
        <?php if (!empty($message)): ?>
        <div class="message-box" style="margin-bottom: 20px; padding: 15px; border-radius: 8px; <?php echo $message_type === 'success' ? 'background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb;' : 'background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb;'; ?>">
            <i class="fas <?php echo $message_type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'; ?>"></i>
            <?php echo htmlspecialchars($message); ?>
        </div>
        <?php endif; ?>
        
        <!-- Dashboard Grid -->
        <div class="dashboard-grid">
            <!-- Asset Management (Admin) or Asset List & Condition (Employee) -->
            <?php if ($user_role === 'admin'): ?>
            <!-- Asset Management (ADMIN CRUD) -->
            <div class="card card-full">
                <div class="card-header">
                    <h2><i class="fas fa-boxes"></i> Asset Management (Admin)</h2>
                    <button class="btn btn-primary" onclick="toggleAssetForm()" style="padding: 8px 16px; font-size: 14px;">
                        <i class="fas fa-plus"></i> Add Asset
                    </button>
                </div>

                <!-- CREATE Form (hidden by default) -->
                <div id="assetFormContainer" class="form-container" style="display: none;">
                    <h3>Add New Asset</h3>
                    <form method="POST" action="" class="asset-form">
                        <input type="hidden" name="action" value="create_asset">
                        <div class="form-grid">
                            <div>
                                <label>Asset Name *</label>
                                <input type="text" name="asset_name" required placeholder="e.g., Vehicle #001" class="form-input">
                            </div>
                            <div>
                                <label>Asset Type *</label>
                                <select name="asset_type" required class="form-input">
                                    <option value="">Select Type</option>
                                    <option value="vehicle">Vehicle</option>
                                    <option value="equipment">Equipment</option>
                                    <option value="warehouse">Warehouse</option>
                                    <option value="other">Other</option>
                                </select>
                            </div>
                            <div>
                                <label>Status *</label>
                                <select name="status" required style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                                    <option value="good">good</option>
                                    <option value="warning">warning</option>
                                    <option value="bad">bad</option>
                                </select>
                            </div>
                            <div>
                                <label>Asset Condition (%) *</label>
                                <input type="number" name="asset_condition" required min="0" max="100" placeholder="0-100" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                            </div>
                            <div class="form-actions">
                                <button type="submit" class="btn btn-success" style="flex: 1; padding: 8px;">Save Asset</button>
                                <button type="button" class="btn btn-secondary" onclick="toggleAssetForm()" style="flex: 1; padding: 8px;">Cancel</button>
                            </div>
                        </div>
                    </form>
                </div>

                <!-- READ & UPDATE & DELETE Table -->
                <div class="card-body">
                    <table class="logs-table">
                        <thead>
                            <tr>
                                <th>Asset Name</th>
                                <th>Type</th>
                                <th>Status</th>
                                <th>Condition</th>
                                <th>Created</th>
                                <th class="actions-cell">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($assets)): ?>
                                <?php foreach ($assets as $asset): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($asset['asset_name']); ?></td>
                                    <td><?php echo htmlspecialchars($asset['asset_type']); ?></td>
                                    <td>
                                        <span class="status-badge <?php 
                                        if ($asset['status'] === 'good') echo 'status-good';
                                        elseif ($asset['status'] === 'warning') echo 'status-warning';
                                        else echo 'status-critical';
                                        ?>">
                                        <?php echo ucfirst($asset['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="condition-badge <?php 
                                        $condition = intval($asset['asset_condition']);
                                        if ($condition >= 70) echo 'condition-good';
                                        elseif ($condition >= 40) echo 'condition-warning';
                                        else echo 'condition-critical';
                                        ?>">
                                        <?php echo htmlspecialchars($asset['asset_condition']); ?>%
                                        </span>
                                    </td>
                                    <td><?php echo date('M d, Y', strtotime($asset['created_at'])); ?></td>
                                    <td class="actions-cell">
                                        <div class="action-btn-group">
                                            <button class="btn-icon btn-edit" onclick="editAsset(<?php echo $asset['id']; ?>, '<?php echo addslashes($asset['asset_name']); ?>', '<?php echo $asset['asset_type']; ?>', '<?php echo $asset['status']; ?>', <?php echo $asset['asset_condition']; ?>)">
                                                <i class="fas fa-edit"></i> Edit
                                            </button>
                                            <form method="POST" action="" class="inline-form" onsubmit="return confirm('Delete this asset?');">
                                                <input type="hidden" name="action" value="delete_asset">
                                                <input type="hidden" name="asset_id" value="<?php echo $asset['id']; ?>">
                                                <button type="submit" class="btn-icon btn-delete">
                                                    <i class="fas fa-trash"></i> Delete
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" style="padding: 20px; text-align: center; color: #999;">No assets found. Add one to get started.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <?php else: ?>
            <!-- Asset List & Condition (for employees) -->
            <div class="card">
                <div class="card-header">
                    <h2><i class="fas fa-boxes"></i> Asset List & Condition</h2>
                    <span class="card-badge"><?php echo count($assets); ?> assets</span>
                </div>
                <div class="card-body">
                    <div class="asset-list">
                        <?php if (!empty($assets)): ?>
                            <?php foreach ($assets as $asset): ?>
                                <div class="asset-item">
                                    <div class="asset-info">
                                        <div class="asset-icon">
                                            <i class="fas fa-cog"></i>
                                        </div>
                                        <div class="asset-details">
                                            <h3><?php echo htmlspecialchars($asset['asset_name']); ?></h3>
                                            <p><?php echo htmlspecialchars($asset['asset_type']); ?></p>
                                        </div>
                                    </div>
                                    <div class="asset-condition">
                                        <span class="percentage <?php 
                                        $condition = intval($asset['asset_condition']);
                                        if ($condition >= 70) echo 'status-good';
                                        elseif ($condition >= 40) echo 'status-warning';
                                        else echo 'status-critical';
                                        ?>">
                                            <?php echo htmlspecialchars($asset['asset_condition']); ?>%
                                        </span>
                                        <div class="label">Condition</div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p>No assets available</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
            
        
        <!-- Maintenance Management (Admin only) -->
        <?php if ($user_role === 'admin'): ?>
        <div class="card card-full">
            <div class="card-header" style="display:flex; justify-content:space-between; align-items:center;">
                <h2><i class="fas fa-exclamation-triangle"></i> Maintenance Management</h2>
                <button class="btn btn-primary" onclick="toggleAlertForm()" style="padding: 8px 16px; font-size: 14px;">
                    <i class="fas fa-plus"></i> Add Alert
                </button>
            </div>

            <!-- Add Alert Form -->
            <div id="alertFormContainer" style="display:none; padding:20px; background:#f9f9f9; border-bottom:1px solid #ddd;">
                <h3>Add New Maintenance Alert</h3>
                <form method="POST" action="">
                    <input type="hidden" name="action" value="create_alert">
                    <div style="display:grid; grid-template-columns:1fr 1fr; gap:15px;">
                        <div>
                            <label>Asset Name *</label>
                            <input type="text" name="asset_name" required placeholder="Vehicle #001" style="width:100%; padding:8px; border:1px solid #ddd; border-radius:4px;">
                        </div>
                        <div>
                            <label>Issue *</label>
                            <input type="text" name="issue" required placeholder="Oil Change" style="width:100%; padding:8px; border:1px solid #ddd; border-radius:4px;">
                        </div>
                        <div>
                            <label>Priority *</label>
                            <select name="priority" required style="width:100%; padding:8px; border:1px solid #ddd; border-radius:4px;">
                                <option value="">Select</option>
                                <option value="low">Low</option>
                                <option value="medium">Medium</option>
                                <option value="high">High</option>
                            </select>
                        </div>
                        <div>
                            <label>Due Date *</label>
                            <input type="date" name="due_date" required style="width:100%; padding:8px; border:1px solid #ddd; border-radius:4px;">
                        </div>
                        <div style="grid-column: span 2; display:flex; gap:10px;">
                            <button type="submit" class="btn btn-success" style="flex:1; padding:8px;">Save Alert</button>
                            <button type="button" class="btn btn-secondary" onclick="toggleAlertForm()" style="flex:1; padding:8px;">Cancel</button>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Alert Table -->
            <div class="card-body">
                <table style="width:100%; border-collapse:collapse;">
                    <thead>
                        <tr style="background: #f0f0f0; border-bottom: 2px solid #ddd;">
                            <th style="padding:12px; text-align:left;">Asset Name</th>
                            <th style="padding:12px; text-align:left;">Issue</th>
                            <th style="padding:12px; text-align:left;">Priority</th>
                            <th style="padding:12px; text-align:left;">Due Date</th>
                            <th style="padding:12px; text-align:center;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($alerts)): ?>
                            <?php foreach ($alerts as $alert): ?>
                            <tr style="border-bottom:1px solid #eee;">
                                <td style="padding:12px;"><?php echo htmlspecialchars($alert['asset_name']); ?></td>
                                <td style="padding:12px;"><?php echo htmlspecialchars($alert['issue']); ?></td>
                                <td style="padding:12px;">
                                    <span style="padding:4px 8px; border-radius:4px; font-size:12px; <?php 
                                        if ($alert['priority'] === 'low') echo 'background:#d4edda; color:#155724;';
                                        elseif ($alert['priority'] === 'medium') echo 'background:#fff3cd; color:#856404;';
                                        else echo 'background:#f8d7da; color:#721c24;';
                                    ?>">
                                        <?php echo ucfirst($alert['priority']); ?>
                                    </span>
                                </td>
                                <td style="padding:12px;"><?php echo date('M d, Y', strtotime($alert['due_date'])); ?></td>
                                <td style="padding:12px; text-align:center;">
                                    <button class="btn-edit" onclick="editAlertModal(<?php echo $alert['id']; ?>)" style="padding:6px 10px; margin-right:5px; background:#007bff; color:white; border:none; border-radius:4px; cursor:pointer;">
                                        <i class="fas fa-edit"></i> Edit
                                    </button>

                                    <?php if ($alert['status'] === 'pending'): ?>
                                        <form method="POST" style="display:inline;" onsubmit="return confirm('Mark this alert as completed?');">
                                            <input type="hidden" name="action" value="complete_alert">
                                            <input type="hidden" name="alert_id" value="<?php echo $alert['id']; ?>">
                                            <button type="submit" style="padding:6px 10px; margin-right:5px; background:#28a745; color:white; border:none; border-radius:4px; cursor:pointer;">
                                                <i class="fas fa-check"></i> Complete
                                            </button>
                                        </form>
                                    <?php endif; ?>

                                    <form method="POST" style="display:inline;" onsubmit="return confirm('Delete this alert?');">
                                        <input type="hidden" name="action" value="delete_alert">
                                        <input type="hidden" name="alert_id" value="<?php echo $alert['id']; ?>">
                                        <button type="submit" style="padding:6px 10px; background:#dc3545; color:white; border:none; border-radius:4px; cursor:pointer;">
                                            <i class="fas fa-trash"></i> Delete
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="5" style="padding:20px; text-align:center; color:#999;">No alerts found.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>
                <!-- Asset List & Condition (for employees) -->
            <div class="card">
                <div class="card-header">
                    <h2><i class="fas fa-boxes"></i> Asset List & Condition</h2>
                    <span class="card-badge"><?php echo count($assets); ?> assets</span>
                </div>
                <div class="card-body">
                    <div class="asset-list">
                        <?php if (!empty($assets)): ?>
                            <?php foreach ($assets as $asset): ?>
                                <div class="asset-item">
                                    <div class="asset-info">
                                        <div class="asset-icon">
                                            <i class="fas fa-cog"></i>
                                        </div>
                                        <div class="asset-details">
                                            <h3><?php echo htmlspecialchars($asset['asset_name']); ?></h3>
                                            <p><?php echo htmlspecialchars($asset['asset_type']); ?></p>
                                        </div>
                                    </div>
                                    <div class="asset-condition">
                                        <span class="percentage <?php 
                                        $condition = intval($asset['asset_condition']);
                                        if ($condition >= 70) echo 'status-good';
                                        elseif ($condition >= 40) echo 'status-warning';
                                        else echo 'status-critical';
                                        ?>">
                                            <?php echo htmlspecialchars($asset['asset_condition']); ?>%
                                        </span>
                                        <div class="label">Condition</div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p>No assets available</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <!-- Maintenance Alerts -->
            <div class="card">
                <div class="card-header">
                    <h2><i class="fas fa-exclamation-triangle"></i> Maintenance Alerts</h2>
                    <span class="card-badge"><?php echo count($alerts); ?> due soon</span>
                </div>
                <div class="card-body">
                    <div class="alert-list">
                        <?php if (!empty($alerts)): ?>
                            <?php foreach ($alerts as $alert): 
                                $priority_class = '';
                                switch ($alert['priority']) {
                                    case 'high':
                                    case 'urgent':
                                        $priority_class = 'urgent';
                                        break;
                                    case 'medium':
                                    case 'warning':
                                        $priority_class = 'warning';
                                        break;
                                    case 'low':
                                    case 'normal':
                                        $priority_class = 'normal';
                                        break;
                                    default:
                                        $priority_class = 'normal';
                                }

                                $due_date = strtotime($alert['due_date']);
                                $today = strtotime(date('Y-m-d'));
                                $days_remaining = ceil(($due_date - $today) / (60 * 60 * 24));
                            ?>
                                <div class="alert-item <?php echo $priority_class; ?>">
                                    <div class="alert-icon <?php echo $priority_class; ?>">
                                        <i class="fas fa-exclamation"></i>
                                    </div>
                                    <div class="alert-content">
                                        <div class="alert-title"><?php echo htmlspecialchars($alert['issue']); ?></div>
                                        <div class="alert-meta"><?php echo htmlspecialchars($alert['asset_name']); ?></div>
                                        <div class="alert-time">Due in <?php echo $days_remaining; ?> days</div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p>No alerts available</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Maintenance History -->
            <div class="card">
                <div class="card-header">
                    <h2><i class="fas fa-history"></i> Maintenance History</h2>
                    <span class="card-badge">Last 30 days</span>
                </div>
                <div class="card-body">
                    <div class="timeline">
                        <?php
                        $stmt = $pdo->prepare("
                            SELECT * 
                            FROM maintenance_alerts 
                            WHERE (status = 'done' AND completed_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY))
                               OR (status = 'pending' AND due_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY))
                            ORDER BY COALESCE(completed_date, due_date) DESC
                        ");
                        $stmt->execute();
                        $maintenance_history = $stmt->fetchAll(PDO::FETCH_ASSOC);
                        ?>

                        <?php if (!empty($maintenance_history)): ?>
                            <?php foreach ($maintenance_history as $record): ?>
                                <?php 
                                    $type = 'blue';
                                    if ($record['status'] === 'done') $type = 'emerald';
                                    else {
                                        $dueDate = new DateTime($record['due_date']);
                                        $now = new DateTime();
                                        $type = ($dueDate < $now) ? 'amber' : 'blue';
                                    }

                                    $datetime1 = !empty($record['completed_date']) ? new DateTime($record['completed_date']) : new DateTime($record['due_date']);
                                    $datetime2 = new DateTime();
                                    $interval = $datetime1->diff($datetime2);

                                    if ($interval->y > 0) $timeAgo = $interval->y . ' year(s) ago';
                                    elseif ($interval->m > 0) $timeAgo = $interval->m . ' month(s) ago';
                                    elseif ($interval->d >= 7) $timeAgo = floor($interval->d / 7) . ' week(s) ago';
                                    elseif ($interval->d > 0) $timeAgo = $interval->d . ' day(s) ago';
                                    elseif ($interval->h > 0) $timeAgo = $interval->h . ' hour(s) ago';
                                    else $timeAgo = 'just now';
                                ?>
                                <div class="timeline-item">
                                    <div class="timeline-dot <?php echo $type; ?>"></div>
                                    <div class="timeline-content">
                                        <p><?php echo htmlspecialchars($record['asset_name'] . ' - ' . $record['issue']); ?></p>
                                        <div class="time"><?php echo $timeAgo; ?></div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p>No maintenance history found in the last 30 days.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Asset Lifecycle Summary -->
        <div class="card card-full">
            <div class="card-header">
                <h2><i class="fas fa-chart-pie"></i> Asset Lifecycle Summary</h2>
                <span class="card-badge"><?php echo $totalItems; ?> Current Status</span>
            </div>
            <div class="card-body">
                <div class="asset-lifecycle-list" style="display: flex; flex-direction: column; gap: 12px;">
                    <?php if (!empty($lifecycleData)): ?>
                        <?php foreach ($lifecycleData as $item): ?>
                            <div>
                                <div style="display: flex; justify-content: space-between; margin-bottom: 4px;">
                                    <span style="font-size: 13px; color: #475569;"><?php echo htmlspecialchars($item['stage']); ?></span>
                                    <span style="font-size: 13px; font-weight: 600; color: #1e293b;"><?php echo $item['count']; ?> assets</span>
                                </div>
                                <div style="height: 6px; background-color: #e2e8f0; border-radius: 999px; overflow: hidden;">
                                    <div style="height: 100%; width: <?php echo $item['percentage']; ?>%; background: linear-gradient(90deg, #2563eb, #3b82f6); border-radius: 999px;"></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p style="text-align:center;">No assets available</p>
                    <?php endif; ?>
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
        
        <!-- Logistics Document Tracking -->
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
        
        <!-- Employee Activity Logs (admin only) -->
        <?php if ($user_role === 'admin'): ?>
        <div class="card card-full">
            <div class="card-header">
                <h2><i class="fas fa-clipboard-list"></i> Employee Activity Logs</h2>
                <span class="card-badge"><?php echo count($activity_logs); ?> entries</span>
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
                        <?php if (!empty($activity_logs)): ?>
                            <?php foreach ($activity_logs as $log): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($log['user']); ?></td>
                                <td><?php echo htmlspecialchars($log['document']); ?></td>
                                <td><?php echo htmlspecialchars($log['action_type']); ?></td>
                                <td><?php echo $log['time'] ? date('M d, Y H:i A', strtotime($log['time'])) : 'N/A'; ?></td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="4" style="text-align: center; padding: 20px; color: #999;">No activity logs available</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>
        
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
</main>


<!-- Admin CRUD JavaScript -->
<script>
function toggleAssetForm() {
    const form = document.getElementById('assetFormContainer');
    form.style.display = form.style.display === 'none' ? 'block' : 'none';
}

function editAsset(assetId, assetName, assetType, status, assetCondition) {
    document.getElementById('edit_asset_id').value = assetId;
    document.getElementById('edit_asset_name').value = assetName;
    document.getElementById('edit_asset_type').value = assetType;
    document.getElementById('edit_asset_status').value = status;
    document.getElementById('edit_asset_condition').value = assetCondition;
    document.getElementById('editAssetModal').style.display = 'block';
}

function closeEditModal() {
    document.getElementById('editAssetModal').style.display = 'none';
}

// Close modal when clicking outside of it
window.onclick = function(event) {
    const modal = document.getElementById('editAssetModal');
    if (event.target == modal) {
        modal.style.display = 'none';
    }
}

function toggleAlertForm() {
    const form = document.getElementById('alertFormContainer');
    form.style.display = form.style.display === 'none' ? 'block' : 'none';
}

function editAlert(id, asset_name, issue, priority, due_date) {
    const modal = document.getElementById('editAlertModal');
    document.getElementById('edit_alert_id').value = id;
    document.getElementById('edit_alert_asset_name').value = asset_name;
    document.getElementById('edit_alert_issue').value = issue;
    document.getElementById('edit_alert_priority').value = priority;
    document.getElementById('edit_alert_due_date').value = due_date;
    modal.style.display = 'block';
}

function closeEditAlertModal() {
    document.getElementById('editAlertModal').style.display = 'none';
}
</script>