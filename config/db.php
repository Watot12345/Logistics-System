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
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    
    // Disable ONLY_FULL_GROUP_BY strict mode which breaks GROUP BY queries on Railway MySQL
    // Also set a safe SQL mode that works across local and Railway environments
    try {
        $pdo->exec("SET SESSION sql_mode = 'STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION'");
    } catch (PDOException $e) {
        error_log("Warning: Could not set sql_mode: " . $e->getMessage());
    }
    
    // Set MySQL timezone safely (Railway may not have timezone tables populated)
    try {
        $pdo->exec("SET time_zone = '" . date('P') . "'");
    } catch (PDOException $e) {
        error_log("Warning: Could not set time_zone: " . $e->getMessage());
        // Fallback: try UTC offset format
        try {
            $pdo->exec("SET time_zone = '+08:00'");
        } catch (PDOException $e2) {
            error_log("Warning: Could not set fallback time_zone either: " . $e2->getMessage());
        }
    }
    
    error_log("Successfully connected to database: $dbname on $host");
    
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}
?>