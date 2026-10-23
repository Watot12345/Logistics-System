<?php
// test_email.php
$to = 'asierra389@gmail.com';
$name = 'Test User';
$code = '123456';

$api_key = getenv('BREVO_API_KEY') ?: 'YOUR_BREVO_API_KEY';

$data = [
    'sender' => ['name' => 'Logistics System', 'email' => 'asierra389@gmail.com'],
    'to' => [['email' => $to, 'name' => $name]],
    'subject' => 'Test Email - Localhost',
    'htmlContent' => "<h2>Hello $name</h2><p>Your test code: <strong>$code</strong></p>"
];

$ch = curl_init('https://api.brevo.com/v3/smtp/email');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'api-key: ' . $api_key,
    'Content-Type: application/json'
]);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Code: " . $httpCode . "\n";
echo "Response: " . $response . "\n";

if ($httpCode === 201 || $httpCode === 200) {
    echo "✅ Email sent successfully!\n";
} else {
    echo "❌ Failed to send email\n";
}
?>