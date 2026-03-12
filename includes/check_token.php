<?php
// check_token.php
require_once '../config/db.php';
require_once 'auth_functions.php';

echo "<h2>Token Checker</h2>";

// Get the raw token from URL or form
$raw_token = isset($_GET['token']) ? $_GET['token'] : '';

if ($raw_token) {
    $token_hash = hash('sha256', $raw_token);
    
    echo "<strong>Raw token:</strong> " . htmlspecialchars($raw_token) . "<br>";
    echo "<strong>Hashed token:</strong> " . $token_hash . "<br>";
    
    // Check if it exists in database
    $stmt = $pdo->prepare("SELECT * FROM password_resets WHERE token = ?");
    $stmt->execute([$token_hash]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result) {
        echo "<p style='color:green'>✅ Token found in database!</p>";
        echo "<pre>";
        print_r($result);
        echo "</pre>";
    } else {
        echo "<p style='color:red'>❌ Token NOT found in database!</p>";
        
        // Show all tokens in database
        echo "<h3>Tokens in database:</h3>";
        $all = $pdo->query("SELECT id, email, token, used FROM password_resets")->fetchAll();
        foreach($all as $t) {
            echo "ID: {$t['id']}, Email: {$t['email']}, Token: {$t['token']}<br>";
        }
    }
}
?>

<form method="GET">
    <input type="text" name="token" size="80" placeholder="Paste your token here">
    <button type="submit">Check Token</button>
</form>