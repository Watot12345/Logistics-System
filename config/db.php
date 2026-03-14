<?php
// Start session for user management

// DEBUG: Check what environment we're in
error_log("RAILWAY_ENVIRONMENT: " . (getenv('RAILWAY_ENVIRONMENT') ? 'true' : 'false'));
error_log("DATABASE_URL: " . (getenv('DATABASE_URL') ? 'found' : 'not found'));

// Check if PDO MySQL is available
if (!extension_loaded('pdo_mysql')) {
    die("PDO MySQL driver is not installed! Please install pdo_mysql extension.");
}

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
        
        error_log("Using Railway DB: $host:$port - $dbname");
    } else {
        // Fallback - use your Railway credentials directly
        $host = 'interchange.proxy.rlwy.net';
        $port = 24495;
        $username = 'root';
        $password = 'smPcAHBTUlbFDDubEYhgCGdWndJuhcpQ';
        $dbname = 'railway';
        error_log("Using fallback Railway credentials");
    }
} else {
    // We're on localhost - use your original settings
    $host = 'localhost';
    $port = 3306;
    $username = 'root';
    $password = '';
    $dbname = 'logistics';
    error_log("Using localhost connection");
}

// Create connection
try {
    // Add port to the DSN if it's not the default
    $dsn = "mysql:host=$host;dbname=$dbname";
    if (isset($port) && $port != 3306) {
        $dsn .= ";port=$port";
    }
    
    error_log("Attempting DSN: $dsn");
    
    $pdo = new PDO($dsn, $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Set MySQL timezone
    $pdo->exec("SET time_zone = '" . date('P') . "'");
    
    error_log("Successfully connected to database: $dbname on $host");
    
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}
?>