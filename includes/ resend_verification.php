<?php
// includes/resend_verification.php
session_start();
require_once 'auth_functions.php';
require_once '../config/db.php';

header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);
$user_id = $data['user_id'] ?? 0;

if (!$user_id) {
    echo json_encode(['success' => false, 'message' => 'Invalid user']);
    exit();
}

// Get user email
$stmt = $pdo->prepare("SELECT email, full_name FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if (!$user) {
    echo json_encode(['success' => false, 'message' => 'User not found']);
    exit();
}

// Generate new code
$verification_code = sprintf("%06d", random_int(0, 999999));
$expires = date('Y-m-d H:i:s', strtotime('+10 minutes'));

// Delete old codes
$stmt = $pdo->prepare("DELETE FROM login_verifications WHERE user_id = ?");
$stmt->execute([$user_id]);

// Save new code
$stmt = $pdo->prepare("
    INSERT INTO login_verifications (user_id, email, verification_code, expires_at)
    VALUES (?, ?, ?, ?)
");
$stmt->execute([$user_id, $user['email'], $verification_code, $expires]);

// Send email
$sent = sendVerificationEmail($user['email'], $user['full_name'], $verification_code);

if ($sent) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to send email']);
}
?>