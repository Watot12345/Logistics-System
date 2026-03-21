<?php
ini_set('display_errors', 0);
ob_start();

session_start();
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/load_config.php'; // ← was /../config/, now /config/
require_once __DIR__ . '/includes/auth_functions.php';

$stray_output = ob_get_clean();
if (!empty(trim($stray_output))) {
    error_log("STRAY OUTPUT: " . $stray_output);
}

header('Content-Type: application/json');

$user_id = isset($_POST['user_id']) ? (int)$_POST['user_id'] : 0;

if (!$user_id) {
    echo json_encode(['success' => false, 'error' => 'User ID required']);
    exit;
}

function sendEmailFast($to, $name, $code) {
    $brevo_api_key = getenv('BREVO_API_KEY') 
                  ?: ($_ENV['BREVO_API_KEY'] ?? '')
                  ?: ($_SERVER['BREVO_API_KEY'] ?? '');

    error_log("BREVO KEY: " . (empty($brevo_api_key) ? 'EMPTY - not found' : 'Found, length=' . strlen($brevo_api_key)));

    if (empty($brevo_api_key)) {
        error_log("❌ BREVO_API_KEY not set");
        return false;
    }

    $data = [
        'sender'      => ['name' => 'Logistics System', 'email' => 'asierra389@gmail.com'],
        'to'          => [['email' => $to, 'name' => $name]],
        'subject'     => '🔐 Your Login Verification Code',
        'htmlContent' => "<h2>Hello $name</h2>
                          <p>Your verification code is: <strong style='font-size:24px;'>$code</strong></p>
                          <p>This code expires in 10 minutes.</p>"
    ];

    $ch = curl_init('https://api.brevo.com/v3/smtp/email');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'api-key: ' . $brevo_api_key,
        'Content-Type: application/json'
    ]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($ch);
    curl_close($ch);

    error_log("Brevo HTTP: $httpCode | Curl error: $curl_error | Response: $response");

    return ($httpCode === 200 || $httpCode === 201);
}

try {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        echo json_encode(['success' => false, 'error' => 'User not found']);
        exit;
    }

    $verification_code = sprintf("%06d", random_int(0, 999999));
    $expires = date('Y-m-d H:i:s', strtotime('+10 minutes'));

    $stmt = $pdo->prepare("DELETE FROM login_verifications WHERE user_id = ?");
    $stmt->execute([$user_id]);

    $stmt = $pdo->prepare("INSERT INTO login_verifications (user_id, email, verification_code, expires_at) VALUES (?, ?, ?, ?)");
    $stmt->execute([$user_id, $user['email'], $verification_code, $expires]);

    $sent = sendEmailFast($user['email'], $user['full_name'], $verification_code);

    if ($sent) {
        echo json_encode(['success' => true, 'message' => 'New verification code sent to your email']);
    } else {
        echo json_encode(['success' => false, 'error' => 'Failed to send email. Please try again.']);
    }

} catch (Exception $e) {
    error_log("Resend error: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Server error: ' . $e->getMessage()]);
}