<?php
require_once '../config/db.php';
session_start();
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized - Please log in']);
    exit();
}

// Check if user is admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode([
        'success' => false, 
        'error' => 'Forbidden - Admin access only',
        'message' => 'You need administrator privileges to access this resource'
    ]);
    exit();
}

$method = $_SERVER['REQUEST_METHOD'];

switch($method) {
    case 'GET':
        if (isset($_GET['id'])) {
            // Get single employee
            getEmployee($pdo, $_GET['id']);
        } else if (isset($_GET['stats'])) {
            // Get statistics
            getStatistics($pdo);
        } else {
            // Get paginated employees
            getEmployees($pdo);
        }
        break;
        
    case 'POST':
        // Create new employee
        createEmployee($pdo);
        break;
        
    case 'PUT':
        // Update employee
        if (isset($_GET['id'])) {
            updateEmployee($pdo, $_GET['id']);
        } else {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'ID is required']);
        }
        break;
        
    case 'DELETE':
        // Delete employee
        if (isset($_GET['id'])) {
            deleteEmployee($pdo, $_GET['id']);
        } else {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'ID is required']);
        }
        break;
        
    default:
        http_response_code(405);
        echo json_encode(['success' => false, 'error' => 'Method not allowed']);
}

function getEmployees($pdo) {
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
    $search = isset($_GET['search']) ? $_GET['search'] : '';
    $role = isset($_GET['role']) ? $_GET['role'] : '';
    $status = isset($_GET['status']) ? $_GET['status'] : '';
    
    $offset = ($page - 1) * $limit;
    
    try {
        $query = "SELECT id, employee_id, username, email, full_name, phone, avatar_url, 
                         role, status, department, join_date, last_login, created_at 
                  FROM users WHERE 1=1";
        $countQuery = "SELECT COUNT(*) as total FROM users WHERE 1=1";
        $params = [];
        
        if (!empty($search)) {
            $query .= " AND (full_name LIKE :search OR email LIKE :search OR department LIKE :search OR employee_id LIKE :search)";
            $countQuery .= " AND (full_name LIKE :search OR email LIKE :search OR department LIKE :search OR employee_id LIKE :search)";
            $params[':search'] = "%$search%";
        }
        
        if (!empty($role) && $role != 'all') {
            $query .= " AND role = :role";
            $countQuery .= " AND role = :role";
            $params[':role'] = $role;
        }
        
        if (!empty($status) && $status != 'all') {
            $query .= " AND status = :status";
            $countQuery .= " AND status = :status";
            $params[':status'] = $status;
        }
        
        // Get total count
        $countStmt = $pdo->prepare($countQuery);
        foreach ($params as $key => $value) {
            $countStmt->bindValue($key, $value);
        }
        $countStmt->execute();
        $total = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        // Add pagination
        $query .= " ORDER BY created_at DESC LIMIT :limit OFFSET :offset";
        
        $stmt = $pdo->prepare($query);
        
        // Bind parameters
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        
        $stmt->execute();
        $employees = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'data' => [
                'data' => $employees,
                'total' => (int)$total,
                'page' => $page,
                'limit' => $limit,
                'total_pages' => ceil($total / $limit)
            ]
        ]);
        
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
    }
}

function getEmployee($pdo, $id) {
    try {
        $stmt = $pdo->prepare("SELECT id, employee_id, username, email, full_name, phone, avatar_url, 
                                      role, status, department, join_date, last_login, created_at 
                               FROM users WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $employee = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($employee) {
            echo json_encode(['success' => true, 'data' => $employee]);
        } else {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Employee not found']);
        }
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
    }
}

function getStatistics($pdo) {
    try {
        $stats = [];
        
        // Total employees
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM users");
        $stats['total'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        // Active employees
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM users WHERE status = 'active'");
        $stats['active'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        // All drivers (total)
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM users WHERE role = 'driver'");
        $stats['total_drivers'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        // Active drivers (status = active)
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM users WHERE role = 'driver' AND status = 'active'");
        $stats['active_drivers'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        // TODAY'S DISPATCH SCHEDULES
        $today = date('Y-m-d');
        
        // Scheduled drivers (status = 'scheduled')
        $stmt = $pdo->prepare("
            SELECT COUNT(DISTINCT driver_id) as total 
            FROM dispatch_schedule 
            WHERE driver_id IS NOT NULL 
            AND scheduled_date = :today 
            AND status = 'scheduled'
        ");
        $stmt->execute([':today' => $today]);
        $stats['scheduled'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        // On road drivers (status = 'in-progress' or 'delivered')
        $stmt = $pdo->prepare("
            SELECT COUNT(DISTINCT driver_id) as total 
            FROM dispatch_schedule 
            WHERE driver_id IS NOT NULL 
            AND scheduled_date = :today 
            AND status IN ('in-progress', 'delivered')
        ");
        $stmt->execute([':today' => $today]);
        $stats['on_road'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        // All dispatchers (total)
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM users WHERE role = 'dispatcher'");
        $stats['total_dispatchers'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        // Active dispatchers (status = active)
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM users WHERE role = 'dispatcher' AND status = 'active'");
        $stats['active_dispatchers'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        // Dispatchers on duty (active dispatchers)
        $stats['on_duty'] = $stats['active_dispatchers'];
        
        // Fleet managers (active)
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM users WHERE role = 'fleet_manager' AND status = 'active'");
        $stats['fleet_managers'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        // Mechanics (active)
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM users WHERE role = 'mechanic' AND status = 'active'");
        $stats['mechanics'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        // Employees joined this month
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM users 
                              WHERE MONTH(created_at) = MONTH(CURRENT_DATE()) 
                              AND YEAR(created_at) = YEAR(CURRENT_DATE())");
        $stats['joined_this_month'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        echo json_encode(['success' => true, 'data' => $stats]);
        
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
    }
}

function createEmployee($pdo) {
    $data = json_decode(file_get_contents('php://input'), true);
    
    // Validate required fields
    $required = ['full_name', 'email', 'phone', 'role', 'status', 'department', 'password'];
    foreach ($required as $field) {
        if (empty($data[$field])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => "$field is required"]);
            return;
        }
    }
    
    try {
        // Check if email already exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = :email");
        $stmt->execute([':email' => $data['email']]);
        if ($stmt->fetch()) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Email already exists']);
            return;
        }
        
        // Generate employee_id based on role
        $prefix = '';
        switch($data['role']) {
            case 'admin': $prefix = 'AD'; break;
            case 'dispatcher': $prefix = 'DP'; break;
            case 'driver': $prefix = 'DR'; break;
            case 'fleet_manager': $prefix = 'FM'; break;
            case 'mechanic': $prefix = 'MC'; break;
            default: $prefix = 'EM';
        }
        
        $year = date('Y');
        $month = date('m');
        
        // Get last employee number for this prefix/year
        $stmt = $pdo->prepare("SELECT employee_id FROM users 
                                WHERE employee_id LIKE ? 
                                ORDER BY employee_id DESC LIMIT 1");
        $stmt->execute(["{$prefix}{$year}{$month}%"]);
        $last = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($last) {
            $lastNum = intval(substr($last['employee_id'], -4));
            $newNum = str_pad($lastNum + 1, 4, '0', STR_PAD_LEFT);
        } else {
            $newNum = '0001';
        }
        
        $employee_id = $prefix . $year . $month . $newNum;
        
        // Generate username from email
        $username = explode('@', $data['email'])[0];
        
        // Hash password
        $hashed_password = password_hash($data['password'], PASSWORD_DEFAULT);
        
        // Begin transaction
        $pdo->beginTransaction();
        
        // FIX: Map 'pending' to 'active' for the database
        $db_status = $data['status'];
        $is_training = false;
        
        if ($data['role'] === 'driver' && $data['status'] === 'pending') {
            $db_status = 'active'; // Use 'active' in users table
            $is_training = true;    // Flag to add to training table
        }
        
        // Insert new employee
        $stmt = $pdo->prepare("INSERT INTO users 
            (employee_id, username, email, password, full_name, phone, role, status, department, join_date, created_at) 
            VALUES 
            (:employee_id, :username, :email, :password, :full_name, :phone, :role, :status, :department, :join_date, NOW())");
        
        $result = $stmt->execute([
            ':employee_id' => $employee_id,
            ':username' => $username,
            ':email' => $data['email'],
            ':password' => $hashed_password,
            ':full_name' => $data['full_name'],
            ':phone' => $data['phone'],
            ':role' => $data['role'],
            ':status' => $db_status,  // Use mapped status
            ':department' => $data['department'],
            ':join_date' => date('Y-m-d')
        ]);
        
        if ($result) {
            $newId = $pdo->lastInsertId();
            
            // If this is a driver pending training, add to driver_training table
            if ($is_training) {
                // Check if driver_training table exists, if not create it
                try {
                    $checkTable = $pdo->query("SHOW TABLES LIKE 'driver_training'");
                    if ($checkTable->rowCount() == 0) {
                        // Create the table if it doesn't exist
                        $pdo->exec("
                            CREATE TABLE driver_training (
                                id INT(11) PRIMARY KEY AUTO_INCREMENT,
                                user_id INT(11) NOT NULL,
                                assigned_by INT(11) NOT NULL,
                                training_status ENUM('pending', 'in_progress', 'passed', 'failed') DEFAULT 'pending',
                                assigned_date DATE NOT NULL,
                                completed_date DATE NULL,
                                notes TEXT,
                                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                                FOREIGN KEY (assigned_by) REFERENCES users(id)
                            )
                        ");
                    }
                    
                    // Insert into training
                    $trainingStmt = $pdo->prepare("
                        INSERT INTO driver_training (user_id, assigned_by, assigned_date, training_status)
                        VALUES (:user_id, :assigned_by, :assigned_date, 'pending')
                    ");
                    $trainingStmt->execute([
                        ':user_id' => $newId,
                        ':assigned_by' => $_SESSION['user_id'], // current admin
                        ':assigned_date' => date('Y-m-d')
                    ]);
                    
                    // Log the training assignment
                    error_log("Driver training assigned for user ID: $newId by admin ID: {$_SESSION['user_id']}");
                    
                } catch (PDOException $e) {
                    // Log but don't fail the employee creation
                    error_log("Warning: Could not assign to training: " . $e->getMessage());
                }
            }
            
            // Commit transaction
            $pdo->commit();
            
            // Create appropriate message
            if ($is_training) {
                $message = 'Employee created and assigned to training successfully';
            } else {
                $message = 'Employee created successfully';
            }
            
            echo json_encode([
                'success' => true, 
                'id' => $newId, 
                'employee_id' => $employee_id,
                'message' => $message
            ]);
        } else {
            $pdo->rollBack();
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => 'Failed to create employee']);
        }
        
    } catch (PDOException $e) {
        // Roll back transaction on error
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
    }
}

function updateEmployee($pdo, $id) {
    $data = json_decode(file_get_contents('php://input'), true);
    
    // Validate required fields
    $required = ['full_name', 'email', 'phone', 'role', 'status', 'department'];
    foreach ($required as $field) {
        if (empty($data[$field])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => "$field is required"]);
            return;
        }
    }
    
    try {
        // Check if email already exists for another user
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = :email AND id != :id");
        $stmt->execute([':email' => $data['email'], ':id' => $id]);
        if ($stmt->fetch()) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Email already exists for another user']);
            return;
        }
        
        // Build update query
        $query = "UPDATE users SET 
                  full_name = :full_name, 
                  email = :email, 
                  phone = :phone, 
                  role = :role, 
                  status = :status, 
                  department = :department,
                  updated_at = NOW()";
        
        $params = [
            ':full_name' => $data['full_name'],
            ':email' => $data['email'],
            ':phone' => $data['phone'],
            ':role' => $data['role'],
            ':status' => $data['status'],
            ':department' => $data['department'],
            ':id' => $id
        ];
        
        // Update password if provided
        if (!empty($data['password'])) {
            $query .= ", password = :password";
            $params[':password'] = password_hash($data['password'], PASSWORD_DEFAULT);
        }
        
        $query .= " WHERE id = :id";
        
        $stmt = $pdo->prepare($query);
        $result = $stmt->execute($params);
        
        if ($result) {
            echo json_encode(['success' => true, 'message' => 'Employee updated successfully']);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => 'Failed to update employee']);
        }
        
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
    }
}

function deleteEmployee($pdo, $id) {
    try {
        // Don't allow deleting yourself
        if ($id == $_SESSION['user_id']) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Cannot delete your own account']);
            return;
        }
        
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = :id");
        $result = $stmt->execute([':id' => $id]);
        
        if ($result && $stmt->rowCount() > 0) {
            echo json_encode(['success' => true, 'message' => 'Employee deleted successfully']);
        } else {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Employee not found']);
        }
        
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
    }
}
?>