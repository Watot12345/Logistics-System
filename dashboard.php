<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);
if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}

// Redirect fleet_manager and driver to fleet.php
if (in_array($_SESSION['role'], ['driver', 'mechanic'])) {
    header("Location: modules/fleet.php");
    exit();
}


require 'config/db.php';
$page_title = 'Dashboard | Logistics System';
$page_css = 'assets/css/style.css';
include 'includes/header.php';
$shipments = getActiveShipments($pdo, 10);
function getActiveShipments($pdo, $limit = 5) {
    $query = "SELECT s.*,
                     u.full_name as driver_name,
                     a.asset_name as vehicle_name,
                     a.asset_condition as vehicle_condition
              FROM shipments s
              LEFT JOIN users u ON s.driver_id = u.id
              LEFT JOIN assets a ON s.vehicle_id = a.id
              WHERE s.shipment_status IN ('pending', 'in_transit')
              ORDER BY 
                  CASE 
                      WHEN s.shipment_status = 'in_transit' THEN 1
                      WHEN s.shipment_status = 'pending' THEN 2
                  END,
                  s.departure_time ASC
              LIMIT :limit";
    
    $stmt = $pdo->prepare($query);
    $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getShipmentProgress($shipment) {
    if ($shipment['shipment_status'] == 'delivered') return 100;
    if ($shipment['shipment_status'] == 'in_transit') return 60;
    if ($shipment['shipment_status'] == 'pending') return 20;
    return 0;
}


// Handle document upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    
    // Upload Document
    if ($_POST['action'] === 'upload_document' && isset($_FILES['document_file'])) {
        $title = $_POST['document_title'];
        $document_type = $_POST['document_type'];
        $asset_id = !empty($_POST['asset_id']) ? $_POST['asset_id'] : null;
        $expiry_date = !empty($_POST['expiry_date']) ? $_POST['expiry_date'] : null;
        $description = $_POST['description'] ?? '';
        $uploaded_by = $_SESSION['user_id'];
        
        // File upload handling
        $target_dir = "uploads/documents/";
        
        // Create directory if it doesn't exist
        if (!file_exists($target_dir)) {
            mkdir($target_dir, 0777, true);
        }
        
        $file_name = basename($_FILES["document_file"]["name"]);
        $file_size = $_FILES["document_file"]["size"];
        $file_tmp = $_FILES["document_file"]["tmp_name"];
        
        // Generate unique filename to prevent overwrites
        $file_extension = pathinfo($file_name, PATHINFO_EXTENSION);
        $new_file_name = time() . '_' . uniqid() . '.' . $file_extension;
        $target_file = $target_dir . $new_file_name;
        
        // Check file size (10MB max)
        if ($file_size > 10000000) {
            $message = "File is too large. Maximum size is 10MB.";
            $message_type = "error";
        } else {
            // Allow certain file formats
            $allowed_types = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'jpg', 'jpeg', 'png'];
            if (in_array(strtolower($file_extension), $allowed_types)) {
                if (move_uploaded_file($file_tmp, $target_file)) {
                    // Save to database
                    $stmt = $pdo->prepare("
                        INSERT INTO documents (title, document_type, file_name, file_path, file_size, description, asset_id, uploaded_by, expiry_date) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
                    ");
                    
                    if ($stmt->execute([$title, $document_type, $file_name, $target_file, $file_size, $description, $asset_id, $uploaded_by, $expiry_date])) {
                        $message = "Document uploaded successfully!";
                        $message_type = "success";
                        
                        // Log the activity
                        $log_stmt = $pdo->prepare("
                            INSERT INTO user_activity_logs (user_id, action_type, document_id, timestamp) 
                            VALUES (?, 'upload', ?, NOW())
                        ");
                        $log_stmt->execute([$uploaded_by, $pdo->lastInsertId()]);
                    } else {
                        $message = "Database error: Could not save document information.";
                        $message_type = "error";
                    }
                } else {
                    $message = "Error uploading file. Please check directory permissions.";
                    $message_type = "error";
                }
            } else {
                $message = "File type not allowed. Allowed types: " . implode(', ', $allowed_types);
                $message_type = "error";
            }
        }
    }
    
    // Delete Document
    if ($_POST['action'] === 'delete_document' && isset($_POST['document_id'])) {
        $document_id = $_POST['document_id'];
        
        // Get file path first
        $stmt = $pdo->prepare("SELECT file_path FROM documents WHERE id = ?");
        $stmt->execute([$document_id]);
        $doc = $stmt->fetch();
        
        if ($doc) {
            // Delete physical file
            if (file_exists($doc['file_path'])) {
                unlink($doc['file_path']);
            }
            
            // Delete from database
            $stmt = $pdo->prepare("DELETE FROM documents WHERE id = ?");
            if ($stmt->execute([$document_id])) {
                $message = "Document deleted successfully!";
                $message_type = "success";
            } else {
                $message = "Error deleting document.";
                $message_type = "error";
            }
        }
    }
}

// Fetch documents for display
$stmt = $pdo->query("
    SELECT d.*, a.asset_name,
           u.full_name as uploaded_by_name
    FROM documents d
    LEFT JOIN assets a ON d.asset_id = a.id
    LEFT JOIN users u ON d.uploaded_by = u.id
    ORDER BY d.uploaded_at DESC
");
$documents = $stmt->fetchAll(PDO::FETCH_ASSOC);
$document_count = count($documents);


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
    // Get ALL active maintenance (pending AND in_progress)
    $stmt = $pdo->query("
        SELECT * FROM maintenance_alerts 
        WHERE status IN ('pending', 'in_progress')
        ORDER BY 
            CASE priority 
                WHEN 'high' THEN 1
                WHEN 'medium' THEN 2
                WHEN 'low' THEN 3
            END,
            due_date ASC
    ");
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
        (SELECT 
            l.id,
            u.full_name as user,
            d.title as document,
            l.action_type as action_type,
            l.timestamp as time
        FROM user_activity_logs l
        JOIN users u ON l.user_id = u.id
        JOIN documents d ON l.document_id = d.id
        WHERE l.action_type = 'download')
        
        UNION ALL
        
        (SELECT 
            NULL as id,
            username as user,
            'Login' as document,
            'Logged In' as action_type,
            last_login as time
        FROM users
        WHERE last_login IS NOT NULL)
        
        ORDER BY time DESC
        LIMIT 10
    ");
    
    $activity_logs = $logs_result->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    $activity_logs = [];
    error_log("Activity logs query failed: " . $e->getMessage());
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
                    <h1>Asset Lifecycle & Maintenance</h1>
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
                    <h2><i class="fas fa-boxes"></i> Asset Management </h2>
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
            <!-- Edit Asset Modal -->
<div id="editAssetModal" class="modal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); align-items: center; justify-content: center; z-index: 1000;">
    <div class="modal-content" style="background: white; border-radius: 16px; width: 90%; max-width: 500px;">
        <div class="modal-header" style="padding: 20px 24px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; display: flex; align-items: center; justify-content: space-between;">
            <h3 style="font-size: 18px;"><i class="fas fa-edit"></i> Edit Asset</h3>
            <button class="close-btn" onclick="closeEditModal()" style="background: none; border: none; color: white; font-size: 24px; cursor: pointer;">&times;</button>
        </div>
        <form method="POST" action="">
            <input type="hidden" name="action" value="update_asset">
            <input type="hidden" name="asset_id" id="edit_asset_id">
            
            <div class="modal-body" style="padding: 24px;">
                <div class="form-group" style="margin-bottom: 15px;">
                    <label style="display: block; margin-bottom: 5px; font-weight: 600;">Asset Name *</label>
                    <input type="text" name="asset_name" id="edit_asset_name" required style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                </div>
                
                <div class="form-group" style="margin-bottom: 15px;">
                    <label style="display: block; margin-bottom: 5px; font-weight: 600;">Asset Type *</label>
                    <select name="asset_type" id="edit_asset_type" required style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                        <option value="vehicle">Vehicle</option>
                        <option value="equipment">Equipment</option>
                        <option value="warehouse">Warehouse</option>
                        <option value="other">Other</option>
                    </select>
                </div>
                
                <div class="form-group" style="margin-bottom: 15px;">
                    <label style="display: block; margin-bottom: 5px; font-weight: 600;">Status *</label>
                    <select name="status" id="edit_asset_status" required style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                        <option value="good">Good</option>
                        <option value="warning">Warning</option>
                        <option value="bad">Bad</option>
                    </select>
                </div>
                
                <div class="form-group" style="margin-bottom: 15px;">
                    <label style="display: block; margin-bottom: 5px; font-weight: 600;">Asset Condition (%) *</label>
                    <input type="number" name="asset_condition" id="edit_asset_condition" required min="0" max="100" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                </div>
            </div>
            
            <div class="modal-footer" style="padding: 20px 24px; background: #f8fafc; border-top: 1px solid #e2e8f0; display: flex; gap: 12px; justify-content: flex-end;">
                <button type="button" class="btn btn-secondary" onclick="closeEditModal()" style="padding: 8px 16px; background: #e2e8f0; border: none; border-radius: 4px; cursor: pointer;">Cancel</button>
                <button type="submit" class="btn btn-primary" style="padding: 8px 16px; background: #007bff; color: white; border: none; border-radius: 4px; cursor: pointer;">Update Asset</button>
            </div>
        </form>
    </div>
</div>
            <?php else: ?>
          
            
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
                                    <button class="btn-edit" onclick="editAlertModal(
                                         <?php echo $alert['id']; ?>, 
                                         '<?php echo addslashes($alert['asset_name']); ?>', 
                                         '<?php echo addslashes($alert['issue']); ?>', 
                                            '<?php echo $alert['priority']; ?>', 
                                            '<?php echo $alert['due_date']; ?>'
                                         )" style="padding:6px 10px; margin-right:5px; background:#007bff; color:white; border:none; border-radius:4px; cursor:pointer;">
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
            <!-- Edit Alert Modal -->
<div id="editAlertModal" class="modal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); align-items: center; justify-content: center; z-index: 1000;">
    <div class="modal-content" style="background: white; border-radius: 16px; width: 90%; max-width: 500px;">
        <div class="modal-header" style="padding: 20px 24px; background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); color: white; display: flex; align-items: center; justify-content: space-between;">
            <h3 style="font-size: 18px;"><i class="fas fa-edit"></i> Edit Alert</h3>
            <button class="close-btn" onclick="closeEditAlertModal()" style="background: none; border: none; color: white; font-size: 24px; cursor: pointer;">&times;</button>
        </div>
        <form method="POST" action="">
            <input type="hidden" name="action" value="update_alert">
            <input type="hidden" name="alert_id" id="edit_alert_id">
            
            <div class="modal-body" style="padding: 24px;">
                <div class="form-group" style="margin-bottom: 15px;">
                    <label style="display: block; margin-bottom: 5px; font-weight: 600;">Asset Name *</label>
                    <input type="text" name="asset_name" id="edit_alert_asset_name" required style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                </div>
                
                <div class="form-group" style="margin-bottom: 15px;">
                    <label style="display: block; margin-bottom: 5px; font-weight: 600;">Issue *</label>
                    <input type="text" name="issue" id="edit_alert_issue" required style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                </div>
                
                <div class="form-group" style="margin-bottom: 15px;">
                    <label style="display: block; margin-bottom: 5px; font-weight: 600;">Priority *</label>
                    <select name="priority" id="edit_alert_priority" required style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                        <option value="low">Low</option>
                        <option value="medium">Medium</option>
                        <option value="high">High</option>
                    </select>
                </div>
                
                <div class="form-group" style="margin-bottom: 15px;">
                    <label style="display: block; margin-bottom: 5px; font-weight: 600;">Due Date *</label>
                    <input type="date" name="due_date" id="edit_alert_due_date" required style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                </div>
            </div>
            
            <div class="modal-footer" style="padding: 20px 24px; background: #f8fafc; border-top: 1px solid #e2e8f0; display: flex; gap: 12px; justify-content: flex-end;">
                <button type="button" class="btn btn-secondary" onclick="closeEditAlertModal()" style="padding: 8px 16px; background: #e2e8f0; border: none; border-radius: 4px; cursor: pointer;">Cancel</button>
                <button type="submit" class="btn btn-primary" style="padding: 8px 16px; background: #007bff; color: white; border: none; border-radius: 4px; cursor: pointer;">Update Alert</button>
            </div>
        </form>
    </div>
</div>
        </div>
        <?php endif; ?>

            <!-- Maintenance Alerts -->
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
        
   <!-- Maintenance Section -->
<div class="card">
    <div class="card-header">
        <h2><i class="fas fa-exclamation-triangle"></i> Maintenance Alerts</h2>
        <span class="card-badge">
            <?php 
            $pending_count = 0;
            $in_progress_count = 0;
            foreach ($alerts as $a) {
                if ($a['status'] == 'pending') $pending_count++;
                if ($a['status'] == 'in_progress') $in_progress_count++;
            }
            
            if ($in_progress_count > 0 && $pending_count > 0) {
                echo $pending_count . ' pending, ' . $in_progress_count . ' in progress';
            } elseif ($in_progress_count > 0) {
                echo $in_progress_count . ' in progress';
            } else {
                echo $pending_count . ' due soon';
            }
            ?>
        </span>
    </div>
    <div class="card-body">
        <div class="alert-list">
            <?php if (!empty($alerts)): ?>
                <?php foreach ($alerts as $alert): 
                    $priority_class = '';
                    switch ($alert['priority']) {
                        case 'high':
                            $priority_class = 'urgent';
                            break;
                        case 'medium':
                            $priority_class = 'warning';
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
                        <?php if ($alert['status'] == 'in_progress'): ?>
                            <div style="font-size: 11px; color: #f59e0b; margin-top: 4px;">
                                <i class="fas fa-spinner fa-spin"></i> In Progress
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p style="text-align: center; padding: 20px; color: #94a3b8;">No active maintenance alerts</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Get data for sections -->
<?php
// Get in-progress maintenance
$in_progress = $pdo->query("
    SELECT m.*, u.full_name as mechanic_name
    FROM maintenance_alerts m
    LEFT JOIN users u ON m.assigned_mechanic = u.id
    WHERE m.status = 'in_progress'
    ORDER BY m.due_date ASC
")->fetchAll();

// Get upcoming maintenance (pending)
$upcoming = $pdo->query("
    SELECT m.*
    FROM maintenance_alerts m
    WHERE m.status = 'pending'
    ORDER BY m.due_date ASC
    LIMIT 5
")->fetchAll();

// Get completed maintenance from last 30 days
$completed_maintenance = $pdo->query("
    SELECT 
        m.*,
        u.full_name as mechanic_name,
        DATEDIFF(m.completed_date, m.due_date) as days_diff
    FROM maintenance_alerts m
    LEFT JOIN users u ON m.assigned_mechanic = u.id
    WHERE m.status = 'completed'
    AND m.completed_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
    ORDER BY m.completed_date DESC
    LIMIT 10
")->fetchAll();
?>

<!-- Maintenance Details Section -->
<div class="card">
    <div class="card-header">
        <h2><i class="fas fa-history"></i> Maintenance Details</h2>
        <span class="card-badge">Active & Recent</span>
    </div>
    <div class="card-body">
        <!-- Show In Progress first -->
        <?php if (!empty($in_progress)): ?>
            <h4 style="margin: 0 0 15px 0; color: #f59e0b;">
                <i class="fas fa-spinner fa-spin"></i> In Progress
            </h4>
            <?php foreach ($in_progress as $task): ?>
                <div style="display: flex; justify-content: space-between; align-items: center; padding: 12px; border-bottom: 1px solid #f0f0f0; background: #fef3c7; margin-bottom: 8px; border-radius: 8px;">
                    <div>
                        <div style="font-weight: 600;"><?php echo htmlspecialchars($task['asset_name']); ?></div>
                        <div style="font-size: 13px; color: #666;"><?php echo htmlspecialchars($task['issue']); ?></div>
                        <div style="font-size: 12px; color: #f59e0b;">
                            <i class="fas fa-user-cog"></i> <?php echo htmlspecialchars($task['mechanic_name'] ?? 'Assigned'); ?>
                        </div>
                    </div>
                    <div style="text-align: right;">
                        <span style="background: #f59e0b; color: white; padding: 4px 12px; border-radius: 20px; font-size: 12px;">
                            In Progress
                        </span>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
        
        <!-- Show Upcoming (pending) -->
        <?php if (!empty($upcoming)): ?>
            <h4 style="margin: 20px 0 15px 0; color: #3b82f6;">
                <i class="fas fa-clock"></i> Upcoming
            </h4>
            <?php foreach ($upcoming as $task): ?>
                <div style="display: flex; justify-content: space-between; align-items: center; padding: 12px; border-bottom: 1px solid #f0f0f0;">
                    <div>
                        <div style="font-weight: 600;"><?php echo htmlspecialchars($task['asset_name']); ?></div>
                        <div style="font-size: 13px; color: #666;"><?php echo htmlspecialchars($task['issue']); ?></div>
                    </div>
                    <div style="text-align: right;">
                        <span style="background: #e6f0ff; color: #3b82f6; padding: 4px 12px; border-radius: 20px; font-size: 12px;">
                            Due <?php echo date('M d', strtotime($task['due_date'])); ?>
                        </span>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
        
        <!-- Show Completed History -->
        <h4 style="margin: 20px 0 15px 0; color: #10b981;">
            <i class="fas fa-check-circle"></i> Recently Completed
        </h4>
        
        <?php if (!empty($completed_maintenance)): ?>
            <?php foreach ($completed_maintenance as $task): 
                $completion_status = ($task['days_diff'] <= 0) ? 'On Time' : 'Late by ' . $task['days_diff'] . ' days';
                $status_color = ($task['days_diff'] <= 0) ? '#10b981' : '#f59e0b';
            ?>
                <div style="display: flex; justify-content: space-between; align-items: center; padding: 12px; border-bottom: 1px solid #f0f0f0;">
                    <div>
                        <div style="font-weight: 600;"><?php echo htmlspecialchars($task['asset_name']); ?></div>
                        <div style="font-size: 13px; color: #666;"><?php echo htmlspecialchars($task['issue']); ?></div>
                        <div style="font-size: 12px; color: #64748b;">
                            <i class="fas fa-wrench"></i> by <?php echo htmlspecialchars($task['mechanic_name'] ?? 'Unknown'); ?>
                        </div>
                        <?php if ($task['completed_notes']): ?>
                            <div style="font-size: 11px; color: #94a3b8; margin-top: 4px;">
                                <i class="fas fa-comment"></i> <?php echo htmlspecialchars(substr($task['completed_notes'], 0, 50)) . (strlen($task['completed_notes']) > 50 ? '...' : ''); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div style="text-align: right;">
                        <div style="font-weight: 500; color: <?php echo $status_color; ?>; font-size: 13px;">
                            <?php echo $completion_status; ?>
                        </div>
                        <div style="font-size: 12px; color: #64748b;">
                            <?php echo date('M d', strtotime($task['completed_date'])); ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p style="text-align: center; padding: 20px; color: #94a3b8; background: #f8fafc; border-radius: 8px;">
                <i class="fas fa-info-circle"></i> No maintenance completed in the last 30 days
            </p>
        <?php endif; ?>
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
        <h2><i class="fas fa-folder"></i> Document Management Tracking & </h2>
        <div style="display: flex; gap: 10px; align-items: center;">
            <span class="card-badge"><?php echo $document_count ?? 0; ?> documents</span>
            <?php if ($user_role === 'admin'): ?>
            <button class="btn btn-primary" onclick="toggleDocumentForm()" style="padding: 8px 16px; font-size: 14px;">
                <i class="fas fa-upload"></i> Upload Document
            </button>
            <?php endif; ?>
        </div>
    </div>

    <!-- Upload Document Form (Admin only) -->
    <?php if ($user_role === 'admin'): ?>
    <div id="documentFormContainer" class="form-container" style="display: none; padding: 20px; background: #f9f9f9; border-bottom: 1px solid #ddd;">
        <h3>Upload New Document</h3>
        <form method="POST" action="" enctype="multipart/form-data" class="document-form">
            <input type="hidden" name="action" value="upload_document">
            <div class="form-grid" style="grid-template-columns: 1fr 1fr;">
                <div>
                    <label>Document Title *</label>
                    <input type="text" name="document_title" required placeholder="e.g., Vehicle Registration" class="form-input">
                </div>
                <div>
                    <label>Document Type *</label>
                    <select name="document_type" required class="form-input">
                        <option value="">Select Type</option>
                        <option value="registration">Registration</option>
                        <option value="insurance">Insurance</option>
                        <option value="maintenance">Maintenance Record</option>
                        <option value="inspection">Inspection Report</option>
                        <option value="permit">Permit/License</option>
                        <option value="other">Other</option>
                    </select>
                </div>
                <div>
                    <label>Related Asset (Optional)</label>
                    <select name="asset_id" class="form-input">
                        <option value="">Select Asset</option>
                        <?php foreach ($assets as $asset): ?>
                        <option value="<?php echo $asset['id']; ?>"><?php echo htmlspecialchars($asset['asset_name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label>Expiry Date (Optional)</label>
                    <input type="date" name="expiry_date" class="form-input">
                </div>
                <div style="grid-column: span 2;">
                    <label>Description</label>
                    <textarea name="description" rows="3" class="form-input" placeholder="Brief description of the document..."></textarea>
                </div>
                <div style="grid-column: span 2;">
                    <label>Choose File *</label>
                    <input type="file" name="document_file" required accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                    <small style="color: #666;">Allowed: PDF, DOC, DOCX, XLS, XLSX, JPG, PNG (Max: 10MB)</small>
                </div>
                <div style="grid-column: span 2; display: flex; gap: 10px; margin-top: 10px;">
                    <button type="submit" class="btn btn-success" style="flex: 1; padding: 8px;">
                        <i class="fas fa-upload"></i> Upload Document
                    </button>
                    <button type="button" class="btn btn-secondary" onclick="toggleDocumentForm()" style="flex: 1; padding: 8px;">Cancel</button>
                </div>
            </div>
        </form>
    </div>
    <?php endif; ?>

    <!-- Documents List -->
    <div class="card-body">
        <?php
        // Fetch documents from database
        $stmt = $pdo->query("
            SELECT d.*, a.asset_name,
                   u.full_name as uploaded_by_name
            FROM documents d
            LEFT JOIN assets a ON d.asset_id = a.id
            LEFT JOIN users u ON d.uploaded_by = u.id
            ORDER BY d.uploaded_at DESC
        ");
        $documents = $stmt->fetchAll(PDO::FETCH_ASSOC);
        ?>
        
        <?php if (!empty($documents)): ?>
        <div class="document-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 20px;">
            <?php foreach ($documents as $doc): ?>
            <div class="document-card" style="border: 1px solid #e2e8f0; border-radius: 8px; padding: 16px; background: white;">
                <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 12px;">
                    <div style="width: 40px; height: 40px; border-radius: 8px; background: #f1f5f9; display: flex; align-items: center; justify-content: center;">
                        <i class="fas fa-file-<?php 
                            $ext = pathinfo($doc['file_name'], PATHINFO_EXTENSION);
                            if (in_array($ext, ['pdf'])) echo 'pdf';
                            elseif (in_array($ext, ['doc', 'docx'])) echo 'word';
                            elseif (in_array($ext, ['xls', 'xlsx'])) echo 'excel';
                            elseif (in_array($ext, ['jpg', 'jpeg', 'png'])) echo 'image';
                            else echo 'alt';
                        ?>" style="font-size: 20px; color: #2563eb;"></i>
                    </div>
                    <div style="flex: 1;">
                        <h4 style="margin: 0; font-size: 14px; font-weight: 600;"><?php echo htmlspecialchars($doc['title']); ?></h4>
                        <p style="margin: 4px 0 0; font-size: 12px; color: #64748b;">
                            <?php echo htmlspecialchars($doc['document_type']); ?>
                            <?php if ($doc['asset_name']): ?> • <?php echo htmlspecialchars($doc['asset_name']); ?><?php endif; ?>
                        </p>
                    </div>
                </div>
                
                <?php if ($doc['description']): ?>
                <p style="margin: 0 0 12px; font-size: 13px; color: #475569;"><?php echo htmlspecialchars($doc['description']); ?></p>
                <?php endif; ?>
                
                <div style="display: flex; justify-content: space-between; align-items: center; font-size: 12px; color: #64748b; margin-bottom: 12px;">
                    <span><i class="far fa-calendar"></i> <?php echo date('M d, Y', strtotime($doc['uploaded_at'])); ?></span>
                    <?php if ($doc['expiry_date']): ?>
                    <span class="<?php echo strtotime($doc['expiry_date']) < time() ? 'status-critical' : 'status-good'; ?>" style="padding: 2px 6px; border-radius: 4px;">
                        <i class="far fa-clock"></i> Expires: <?php echo date('M d, Y', strtotime($doc['expiry_date'])); ?>
                    </span>
                    <?php endif; ?>
                </div>
                
                <div style="display: flex; gap: 8px;">
                    <!-- Download button for everyone -->
                    <a href="download.php?file=<?php echo $doc['id']; ?>" class="btn btn-primary" style="flex: 1; padding: 8px; text-align: center; text-decoration: none;">
                        <i class="fas fa-download"></i> Download
                    </a>
                    
                    <!-- Admin actions -->
                    <?php if ($user_role === 'admin'): ?>
                    <button class="btn btn-danger" onclick="deleteDocument(<?php echo $doc['id']; ?>)" style="padding: 8px 12px;">
                        <i class="fas fa-trash"></i>
                    </button>
                    <?php endif; ?>
                </div>
                
                <div style="margin-top: 8px; font-size: 11px; color: #94a3b8;">
                    Uploaded by: <?php echo htmlspecialchars($doc['uploaded_by_name']); ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
        <div style="text-align: center; padding: 40px 20px; color: #999;">
            <i class="fas fa-folder-open" style="font-size: 48px; margin-bottom: 16px; opacity: 0.5;"></i>
            <p>No documents available</p>
            <?php if ($user_role === 'admin'): ?>
            <p style="font-size: 14px;">Click the "Upload Document" button to add your first document.</p>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>
</div>
        
        <!-- Logistics Document Tracking -->
    <div class="card card-full">
    <div class="card-header">
        <h2><i class="fas fa-truck"></i> Project Logistics Tracker</h2>
        <span class="card-badge">Active shipments</span>
        <?php if (in_array($_SESSION['role'], ['admin', 'employee'])): ?>
        <button class="btn-sm" style="margin-left: auto;" onclick="openAddModal()">
            <i class="fas fa-plus"></i> Add New
        </button>
        <?php endif; ?>
    </div>
    <div class="card-body">
        <!-- Alert container for notifications -->
        <div id="alertContainer"></div>
        
        <div class="tracking-list" style="display: flex; flex-direction: column; gap: 12px;">
            <?php
            // Fetch shipments with driver and vehicle info
            $shipments = getActiveShipments($pdo, 10);
            
            if (!empty($shipments)) {
                foreach ($shipments as $shipment) {
                    $progress = getShipmentProgress($shipment);
                    
                    $status_class = 'status-good';
                    if ($shipment['shipment_status'] == 'in_transit') {
                        $status_class = 'status-warning';
                    } elseif ($shipment['shipment_status'] == 'pending' || $shipment['shipment_status'] == 'delayed') {
                        $status_class = 'status-critical';
                    }
                    
                    if ($shipment['shipment_status'] == 'delivered' && !empty($shipment['actual_arrival'])) {
                        $time_display = 'Delivered: ' . date('M d, H:i', strtotime($shipment['actual_arrival']));
                    } elseif (!empty($shipment['estimated_arrival'])) {
                        $time_display = 'ETA: ' . date('M d, H:i', strtotime($shipment['estimated_arrival']));
                    } elseif (!empty($shipment['departure_time'])) {
                        $time_display = 'Departed: ' . date('M d, H:i', strtotime($shipment['departure_time']));
                    } else {
                        $time_display = 'Created: ' . date('M d, H:i', strtotime($shipment['created_at']));
                    }
                    
                    $location = !empty($shipment['current_location']) ? $shipment['current_location'] : 'Not started';
                    ?>
                    <div class="shipment-item" data-id="<?php echo $shipment['shipment_id']; ?>" 
                         style="display: flex; align-items: center; justify-content: space-between; padding: 12px; background-color: #f8fafc; border-radius: 8px; border: 1px solid #e2e8f0;">
                        <div style="flex: 1;">
                            <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 4px; flex-wrap: wrap;">
                                <span style="font-weight: 600; font-size: 14px; color: #1e293b;">
                                    #<?php echo $shipment['shipment_id']; ?>
                                </span>
                                <?php if (!empty($shipment['driver_name'])): ?>
                                <span style="font-size: 12px; color: #64748b;">
                                    <i class="fas fa-user-tie" style="margin-right: 2px;"></i>
                                    <?php echo htmlspecialchars($shipment['driver_name']); ?>
                                </span>
                                <?php endif; ?>
                                <?php if (!empty($shipment['vehicle_name'])): ?>
                                <span style="font-size: 12px; color: #64748b;">
                                    <i class="fas fa-truck" style="margin-right: 2px;"></i>
                                    <?php echo htmlspecialchars($shipment['vehicle_name']); ?>
                                </span>
                                <?php endif; ?>
                            </div>
                            
                            <div style="font-size: 13px; color: #64748b; margin-top: 4px;">
                                <i class="fas fa-map-marker-alt" style="margin-right: 4px; font-size: 11px;"></i>
                                <?php echo htmlspecialchars($location); ?>
                            </div>
                            
                            <div style="font-size: 11px; color: #94a3b8; margin-top: 4px;">
                                <i class="far fa-clock" style="margin-right: 4px;"></i>
                                <?php echo $time_display; ?>
                            </div>

                            <?php if ($shipment['shipment_status'] != 'delivered'): ?>
                            <div style="margin-top: 8px; width: 100%; height: 4px; background-color: #e2e8f0; border-radius: 2px;">
                                <div style="width: <?php echo $progress; ?>%; height: 100%; background-color: <?php 
                                    echo $progress >= 60 ? '#10b981' : ($progress >= 20 ? '#f59e0b' : '#ef4444'); 
                                ?>; border-radius: 2px;"></div>
                            </div>
                            <?php endif; ?>
                        </div>
                        
                        <div style="display: flex; align-items: center; gap: 8px; margin-left: 12px;">
                            <span class="status-badge <?php echo $status_class; ?>" 
                                  style="padding: 4px 10px; border-radius: 20px; font-size: 12px; font-weight: 500; display: inline-block; white-space: nowrap;">
                                <?php echo ucfirst(str_replace('_', ' ', $shipment['shipment_status'])); ?>
                            </span>
                            
                            <?php if (in_array($_SESSION['role'], ['admin', 'employee'])): ?>
                            <div class="action-buttons" style="display: flex; gap: 4px;">
                                <button class="btn-icon" onclick="openEditModal(<?php echo $shipment['shipment_id']; ?>)" 
                                        style="color: #3b82f6; background: none; border: none; cursor: pointer; padding: 4px;" title="Edit">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="btn-icon" onclick="deleteShipment(<?php echo $shipment['shipment_id']; ?>)" 
                                        style="color: #ef4444; background: none; border: none; cursor: pointer; padding: 4px;" title="Delete">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php
                }
            } else {
                ?>
                <div style="text-align: center; padding: 40px 20px; background-color: #f8fafc; border-radius: 8px; border: 1px solid #e2e8f0;">
                    <i class="fas fa-truck" style="font-size: 40px; color: #cbd5e1; margin-bottom: 10px;"></i>
                    <p style="color: #64748b; margin: 0;">No active shipments found</p>
                    <?php if (in_array($_SESSION['role'], ['admin', 'employee'])): ?>
                    <button class="btn-sm" style="margin-top: 10px;" onclick="openAddModal()">
                        <i class="fas fa-plus"></i> Add Your First Shipment
                    </button>
                    <?php endif; ?>
                </div>
                <?php
            }
            ?>
        </div>
        
        <div style="margin-top: 16px; text-align: center;">
            <a href="#" style="color: #3b82f6; text-decoration: none; font-size: 13px; font-weight: 500;">
                View All Shipments <i class="fas fa-arrow-right" style="margin-left: 4px; font-size: 11px;"></i>
            </a>
        </div>
    </div>
</div>

<!-- ADD MODAL -->
<div id="addModal" class="modal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); align-items: center; justify-content: center; z-index: 1000;">
    <div class="modal-content" style="background: white; border-radius: 16px; width: 90%; max-width: 600px; max-height: 90vh; overflow-y: auto;">
        <div class="modal-header" style="padding: 20px 24px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; display: flex; align-items: center; justify-content: space-between;">
            <h3 style="font-size: 18px;"><i class="fas fa-plus-circle"></i> Add New Shipment</h3>
            <button class="close-btn" onclick="closeModal('addModal')" style="background: none; border: none; color: white; font-size: 24px; cursor: pointer;">&times;</button>
        </div>
        <form id="addForm" onsubmit="submitAddForm(event)">
            <div class="modal-body" style="padding: 24px;">
                <div class="form-group" style="margin-bottom: 20px;">
                    <label style="display: block; margin-bottom: 6px; font-weight: 600; font-size: 13px;">Customer Name *</label>
                    <input type="text" name="customer_name" required placeholder="Enter customer name" 
                           style="width: 100%; padding: 10px 12px; border: 2px solid #e2e8f0; border-radius: 8px;">
                </div>
                
                <div class="form-group" style="margin-bottom: 20px;">
                    <label style="display: block; margin-bottom: 6px; font-weight: 600; font-size: 13px;">Delivery Address *</label>
                    <textarea name="delivery_address" required rows="2" placeholder="Enter delivery address" 
                              style="width: 100%; padding: 10px 12px; border: 2px solid #e2e8f0; border-radius: 8px;"></textarea>
                </div>
                
                <div class="form-row" style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                    <div class="form-group">
                        <label style="display: block; margin-bottom: 6px; font-weight: 600; font-size: 13px;">Driver</label>
                        <select name="driver_id" style="width: 100%; padding: 10px 12px; border: 2px solid #e2e8f0; border-radius: 8px;">
                            <option value="">-- Select Driver --</option>
                            <?php 
                            // Fetch drivers
                            $drivers = $pdo->query("SELECT id, full_name FROM users WHERE role = 'driver' AND status = 'active'")->fetchAll(PDO::FETCH_ASSOC);
                            foreach ($drivers as $driver): ?>
                                <option value="<?php echo $driver['id']; ?>"><?php echo htmlspecialchars($driver['full_name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label style="display: block; margin-bottom: 6px; font-weight: 600; font-size: 13px;">Vehicle</label>
                        <select name="vehicle_id" style="width: 100%; padding: 10px 12px; border: 2px solid #e2e8f0; border-radius: 8px;">
                            <option value="">-- Select Vehicle --</option>
                            <?php 
                            $vehicles = $pdo->query("SELECT id, asset_name FROM assets WHERE asset_type = 'vehicle'")->fetchAll(PDO::FETCH_ASSOC);
                            foreach ($vehicles as $vehicle): ?>
                                <option value="<?php echo $vehicle['id']; ?>"><?php echo htmlspecialchars($vehicle['asset_name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                
                <div class="form-row" style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                    <div class="form-group">
                        <label style="display: block; margin-bottom: 6px; font-weight: 600; font-size: 13px;">Status</label>
                        <select name="shipment_status" style="width: 100%; padding: 10px 12px; border: 2px solid #e2e8f0; border-radius: 8px;">
                            <option value="pending">Pending</option>
                            <option value="in_transit">In Transit</option>
                            <option value="delayed">Delayed</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label style="display: block; margin-bottom: 6px; font-weight: 600; font-size: 13px;">Current Location</label>
                        <input type="text" name="current_location" placeholder="Current location" 
                               style="width: 100%; padding: 10px 12px; border: 2px solid #e2e8f0; border-radius: 8px;">
                    </div>
                </div>
                
                <div class="form-row" style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                    <div class="form-group">
                        <label style="display: block; margin-bottom: 6px; font-weight: 600; font-size: 13px;">Departure Time</label>
                        <input type="datetime-local" name="departure_time" 
                               style="width: 100%; padding: 10px 12px; border: 2px solid #e2e8f0; border-radius: 8px;">
                    </div>
                    
                    <div class="form-group">
                        <label style="display: block; margin-bottom: 6px; font-weight: 600; font-size: 13px;">Estimated Arrival</label>
                        <input type="datetime-local" name="estimated_arrival" 
                               style="width: 100%; padding: 10px 12px; border: 2px solid #e2e8f0; border-radius: 8px;">
                    </div>
                </div>
            </div>
            
            <div class="modal-footer" style="padding: 20px 24px; background: #f8fafc; border-top: 1px solid #e2e8f0; display: flex; gap: 12px; justify-content: flex-end;">
                <button type="button" class="btn btn-secondary" onclick="closeModal('addModal')" 
                        style="padding: 10px 20px; background: #e2e8f0; border: none; border-radius: 8px; cursor: pointer;">Cancel</button>
                <button type="submit" class="btn btn-primary" 
                        style="padding: 10px 20px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border: none; border-radius: 8px; cursor: pointer;">Create Shipment</button>
            </div>
        </form>
    </div>
</div>

<!-- EDIT MODAL -->
<div id="editModal" class="modal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); align-items: center; justify-content: center; z-index: 1000;">
    <div class="modal-content" style="background: white; border-radius: 16px; width: 90%; max-width: 600px; max-height: 90vh; overflow-y: auto;">
        <div class="modal-header" style="padding: 20px 24px; background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); color: white; display: flex; align-items: center; justify-content: space-between;">
            <h3 style="font-size: 18px;"><i class="fas fa-edit"></i> Edit Shipment</h3>
            <button class="close-btn" onclick="closeModal('editModal')" style="background: none; border: none; color: white; font-size: 24px; cursor: pointer;">&times;</button>
        </div>
        <form id="editForm" onsubmit="submitEditForm(event)">
            <input type="hidden" name="shipment_id" id="edit_shipment_id">
            <input type="hidden" name="old_status" id="old_status">
            
            <div class="modal-body" style="padding: 24px;">
                <div class="form-group" style="margin-bottom: 20px;">
                    <label style="display: block; margin-bottom: 6px; font-weight: 600; font-size: 13px;">Status</label>
                    <select name="shipment_status" id="edit_status" onchange="toggleArrivalField()" 
                            style="width: 100%; padding: 10px 12px; border: 2px solid #e2e8f0; border-radius: 8px;">
                        <option value="pending">Pending</option>
                        <option value="in_transit">In Transit</option>
                        <option value="delivered">Delivered</option>
                        <option value="delayed">Delayed</option>
                    </select>
                </div>
                
                <div class="form-row" style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                    <div class="form-group">
                        <label style="display: block; margin-bottom: 6px; font-weight: 600; font-size: 13px;">Driver</label>
                        <select name="driver_id" id="edit_driver_id" style="width: 100%; padding: 10px 12px; border: 2px solid #e2e8f0; border-radius: 8px;">
                            <option value="">-- Select Driver --</option>
                            <?php foreach ($drivers as $driver): ?>
                                <option value="<?php echo $driver['id']; ?>"><?php echo htmlspecialchars($driver['full_name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label style="display: block; margin-bottom: 6px; font-weight: 600; font-size: 13px;">Vehicle</label>
                        <select name="vehicle_id" id="edit_vehicle_id" style="width: 100%; padding: 10px 12px; border: 2px solid #e2e8f0; border-radius: 8px;">
                            <option value="">-- Select Vehicle --</option>
                            <?php foreach ($vehicles as $vehicle): ?>
                                <option value="<?php echo $vehicle['id']; ?>"><?php echo htmlspecialchars($vehicle['asset_name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                
                <div class="form-group" style="margin-bottom: 20px;">
                    <label style="display: block; margin-bottom: 6px; font-weight: 600; font-size: 13px;">Current Location</label>
                    <input type="text" name="current_location" id="edit_location" placeholder="Current location" 
                           style="width: 100%; padding: 10px 12px; border: 2px solid #e2e8f0; border-radius: 8px;">
                </div>
                
                <div class="form-row" style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                    <div class="form-group">
                        <label style="display: block; margin-bottom: 6px; font-weight: 600; font-size: 13px;">Departure Time</label>
                        <input type="datetime-local" name="departure_time" id="edit_departure" 
                               style="width: 100%; padding: 10px 12px; border: 2px solid #e2e8f0; border-radius: 8px;">
                    </div>
                    
                    <div class="form-group">
                        <label style="display: block; margin-bottom: 6px; font-weight: 600; font-size: 13px;">Estimated Arrival</label>
                        <input type="datetime-local" name="estimated_arrival" id="edit_estimated" 
                               style="width: 100%; padding: 10px 12px; border: 2px solid #e2e8f0; border-radius: 8px;">
                    </div>
                </div>
                
                <div class="form-group" id="actual_arrival_group" style="display: none; margin-bottom: 20px;">
                    <label style="display: block; margin-bottom: 6px; font-weight: 600; font-size: 13px;">Actual Arrival Time</label>
                    <input type="datetime-local" name="actual_arrival" id="edit_actual" 
                           style="width: 100%; padding: 10px 12px; border: 2px solid #e2e8f0; border-radius: 8px;">
                    <small style="color: #666; display: block; margin-top: 5px;">Leave empty to use current time</small>
                </div>
            </div>
            
            <div class="modal-footer" style="padding: 20px 24px; background: #f8fafc; border-top: 1px solid #e2e8f0; display: flex; gap: 12px; justify-content: flex-end;">
                <button type="button" class="btn btn-secondary" onclick="closeModal('editModal')" 
                        style="padding: 10px 20px; background: #e2e8f0; border: none; border-radius: 8px; cursor: pointer;">Cancel</button>
                <button type="submit" class="btn btn-primary" 
                        style="padding: 10px 20px; background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); color: white; border: none; border-radius: 8px; cursor: pointer;">Update Shipment</button>
            </div>
        </form>
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
    </div>
</main>


<!-- Admin CRUD JavaScript -->
<script>
   function openAddModal() {
    document.getElementById('addModal').style.display = 'flex';
}

function openEditModal(shipmentId) {
    // Fetch shipment data from your backend
    fetch('backend/get-shipment.php?id=' + shipmentId)
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const s = data.data;
            
            // Populate form fields
            document.getElementById('edit_shipment_id').value = s.shipment_id;
            document.getElementById('old_status').value = s.shipment_status;
            document.getElementById('edit_status').value = s.shipment_status;
            document.getElementById('edit_driver_id').value = s.driver_id || '';
            document.getElementById('edit_vehicle_id').value = s.vehicle_id || '';
            document.getElementById('edit_location').value = s.current_location || '';
            
            // Format datetime fields
            if (s.departure_time) {
                document.getElementById('edit_departure').value = s.departure_time.slice(0, 16);
            }
            if (s.estimated_arrival) {
                document.getElementById('edit_estimated').value = s.estimated_arrival.slice(0, 16);
            }
            if (s.actual_arrival) {
                document.getElementById('edit_actual').value = s.actual_arrival.slice(0, 16);
            }
            
            // Show/hide actual arrival field
            toggleArrivalField();
            
            // Open modal
            document.getElementById('editModal').style.display = 'flex';
        } else {
            showAlert('error', 'Failed to load shipment data');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('error', 'An error occurred');
    });
}

function closeModal(modalId) {
    document.getElementById(modalId).style.display = 'none';
}

function toggleArrivalField() {
    const status = document.getElementById('edit_status').value;
    const arrivalGroup = document.getElementById('actual_arrival_group');
    
    if (status === 'delivered') {
        arrivalGroup.style.display = 'block';
        
        // Set default to current time if empty
        const arrivalInput = document.getElementById('edit_actual');
        if (!arrivalInput.value) {
            const now = new Date();
            const year = now.getFullYear();
            const month = String(now.getMonth() + 1).padStart(2, '0');
            const day = String(now.getDate()).padStart(2, '0');
            const hours = String(now.getHours()).padStart(2, '0');
            const minutes = String(now.getMinutes()).padStart(2, '0');
            arrivalInput.value = `${year}-${month}-${day}T${hours}:${minutes}`;
        }
    } else {
        arrivalGroup.style.display = 'none';
    }
}

// ============================================
// FORM SUBMISSION HANDLERS
// ============================================

function submitAddForm(event) {
    event.preventDefault();
    
    const form = event.target;
    const formData = new FormData(form);
    
    fetch('backend/add-shipment.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert('success', 'Shipment added successfully!');
            closeModal('addModal');
            setTimeout(() => location.reload(), 1000);
        } else {
            showAlert('error', data.message || 'Failed to add shipment');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('error', 'An error occurred');
    });
}

function submitEditForm(event) {
    event.preventDefault();
    
    const form = event.target;
    const formData = new FormData(form);
    
    fetch('backend/edit-shipment.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert('success', 'Shipment updated successfully!');
            closeModal('editModal');
            setTimeout(() => location.reload(), 1000);
        } else {
            showAlert('error', data.message || 'Failed to update shipment');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('error', 'An error occurred');
    });
}

function deleteShipment(shipmentId) {
    if (confirm('Are you sure you want to delete this shipment? This action cannot be undone.')) {
        const formData = new FormData();
        formData.append('id', shipmentId);
        
        fetch('backend/delete-shipment.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showAlert('success', 'Shipment deleted successfully!');
                setTimeout(() => location.reload(), 1000);
            } else {
                showAlert('error', data.message || 'Failed to delete shipment');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showAlert('error', 'An error occurred');
        });
    }
}

// ============================================
// UTILITY FUNCTIONS
// ============================================

function showAlert(type, message) {
    const alertContainer = document.getElementById('alertContainer');
    const alertClass = type === 'success' ? 'alert-success' : 'alert-error';
    const icon = type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle';
    
    alertContainer.innerHTML = `
        <div class="alert ${alertClass}" style="padding: 12px 16px; border-radius: 8px; margin-bottom: 20px; font-size: 14px; ${type === 'success' ? 'background: #d4edda; color: #155724; border: 1px solid #c3e6cb;' : 'background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb;'}">
            <i class="fas ${icon}"></i> ${message}
        </div>
    `;
    
    // Auto hide after 3 seconds
    setTimeout(() => {
        alertContainer.innerHTML = '';
    }, 3000);
}

// Close modals when clicking outside
window.onclick = function(event) {
    if (event.target.classList.contains('modal')) {
        event.target.style.display = 'none';
    }
}


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

// Function to OPEN the edit alert modal
function editAlertModal(id, asset_name, issue, priority, due_date) {
    const modal = document.getElementById('editAlertModal');
    document.getElementById('edit_alert_id').value = id;
    document.getElementById('edit_alert_asset_name').value = asset_name;
    document.getElementById('edit_alert_issue').value = issue;
    document.getElementById('edit_alert_priority').value = priority;
    document.getElementById('edit_alert_due_date').value = due_date;
    modal.style.display = 'block';
}

// Function to CLOSE the edit alert modal
function closeEditAlertModal() {
    document.getElementById('editAlertModal').style.display = 'none';
}
function toggleDocumentForm() {
    const form = document.getElementById('documentFormContainer');
    if (form.style.display === 'none' || form.style.display === '') {
        form.style.display = 'block';
    } else {
        form.style.display = 'none';
    }
}

function deleteDocument(documentId) {
    if (confirm('Are you sure you want to delete this document?')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="action" value="delete_document">
            <input type="hidden" name="document_id" value="${documentId}">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}

// Auto-hide document form after successful upload
<?php if (isset($message) && $message_type === 'success' && strpos($message, 'Document') !== false): ?>
document.addEventListener('DOMContentLoaded', function() {
    document.getElementById('documentFormContainer').style.display = 'none';
});
<?php endif; ?>


</script>

