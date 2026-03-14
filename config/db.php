<?php
// Start session for user management

// Check if we're on Railway or local
if (getenv('RAILWAY_ENVIRONMENT')) {
    // We're on Railway - use environment variables
    $database_url = getenv('DATABASE_URL');
    
    if ($database_url) {
        // Parse Railway's database URL
        $db = parse_url($database_url);
        
        $host = $db['host'];
        $port = $db['port'] ?? 3306;
        $username = $db['user'];
        $password = $db['pass'];
        $dbname = ltrim($db['path'], '/');
    } else {
        // Fallback - use your Railway credentials directly
        $host = 'interchange.proxy.rlwy.net';
        $port = 24495;
        $username = 'root';
        $password = 'smPcAHBTUlbFDDubEYhgCGdWndJuhcpQ';
        $dbname = 'railway';  // Note: Railway uses 'railway' as database name, not 'logistics'
    }
} else {
    // We're on localhost - use your original settings
    $host = 'localhost';
    $port = 3306;
    $username = 'root';
    $password = '';
    $dbname = 'logistics';  // Your local database name
}

// Create connection
try {
    // Add port to the DSN if it's not the default
    $dsn = "mysql:host=$host;dbname=$dbname";
    if (isset($port) && $port != 3306) {
        $dsn .= ";port=$port";
    }
    
    $pdo = new PDO($dsn, $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Set MySQL timezone
    $pdo->exec("SET time_zone = '" . date('P') . "'");
    
    // Optional: Log success
    error_log("Connected to database: $dbname on $host");
    
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}
?>