<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>🔧 Resend Email Test</h2>";

$api_key = 're_BvGKfNqY_QB1b894VrYEGkfkJwXKqpFtW';
$test_email = 'asierra389@gmail.com'; // Your email

echo "Testing API key: " . substr($api_key, 0, 10) . "...<br>";
echo "Sending to: $test_email<br><br>";

// Prepare the email
$data = [
    'from' => 'Acme <onboarding@resend.dev>',
    'to' => [$test_email],
    'subject' => 'Test Email from Your App',
    'html' => '<h2>Test Email</h2><p>If you see this, Resend is working!</p><p>Timestamp: ' . date('Y-m-d H:i:s') . '</p>',
    'text' => 'Test email from your app'
];

// Initialize cURL
$ch = curl_init('https://api.resend.com/emails');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . $api_key,
    'Content-Type: application/json',
    'User-Agent: Logistics-App/1.0'
]);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
curl_setopt($ch, CURLOPT_TIMEOUT, 30);
curl_setopt($ch, CURLOPT_VERBOSE, true);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
$info = curl_getinfo($ch);
curl_close($ch);

echo "<h3>Results:</h3>";
echo "HTTP Code: <strong>$httpCode</strong><br>";

if ($httpCode == 200) {
    echo "<span style='color:green; font-weight:bold;'>✅ SUCCESS! Check your email!</span><br>";
    echo "Response: " . htmlspecialchars($response) . "<br>";
} else {
    echo "<span style='color:red; font-weight:bold;'>❌ FAILED</span><br>";
    echo "Error: " . htmlspecialchars($error) . "<br>";
    echo "Response: " . htmlspecialchars($response) . "<br>";
    echo "<pre>Debug Info: " . print_r($info, true) . "</pre>";
}

// Also test if cURL is installed
echo "<br><h3>System Check:</h3>";
echo "cURL enabled: " . (function_exists('curl_version') ? '✅ YES' : '❌ NO') . "<br>";
if (function_exists('curl_version')) {
    $curl_version = curl_version();
    echo "cURL version: " . $curl_version['version'] . "<br>";
}
?>