<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
 require_once '../config/db.php';
 require_once 'auth_functions.php';
header('Content-Type: application/json');

try {
    echo json_encode(['debug' => 'Step 1: Starting']);
    
   
    echo json_encode(['debug' => 'Step 2: db.php loaded']);
    
   
    echo json_encode(['debug' => 'Step 3: auth_functions.php loaded']);
    
    $input = json_decode(file_get_contents('php://input'), true);
    $user_id = $input['user_id'] ?? '';
    
    echo json_encode(['debug' => 'Step 4: User ID: ' . $user_id]);
    
    if (empty($user_id)) {
        echo json_encode(['success' => false, 'error' => 'User ID required']);
        exit();
    }
    
    // Get user data
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo json_encode(['debug' => 'Step 5: User found: ' . ($user ? 'Yes' : 'No')]);
    
    if (!$user) {
        echo json_encode(['success' => false, 'error' => 'User not found']);
        exit();
    }
    
    // Generate new code
    $verification_code = sprintf("%06d", random_int(0, 999999));
    $expires = date('Y-m-d H:i:s', strtotime('+10 minutes'));
    
    echo json_encode(['debug' => 'Step 6: Code generated: ' . $verification_code]);
    
    // Delete old codes
    $stmt = $pdo->prepare("DELETE FROM login_verifications WHERE user_id = ?");
    $stmt->execute([$user_id]);
    
    echo json_encode(['debug' => 'Step 7: Old codes deleted']);
    
    // Save new code
    $stmt = $pdo->prepare("INSERT INTO login_verifications (user_id, email, verification_code, expires_at) VALUES (?, ?, ?, ?)");
    $stmt->execute([$user_id, $user['email'], $verification_code, $expires]);
    
    echo json_encode(['debug' => 'Step 8: New code saved']);
    
    // Send email
    $sent = sendEmailFast($user['email'], $user['full_name'], $verification_code);
    
    echo json_encode(['debug' => 'Step 9: Email sent: ' . ($sent ? 'Yes' : 'No')]);
    
    if ($sent) {
        echo json_encode(['success' => true, 'message' => 'New code sent to your email']);
    } else {
        echo json_encode(['success' => false, 'error' => 'Failed to send email']);
    }
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage(), 'line' => $e->getLine()]);
}
?>