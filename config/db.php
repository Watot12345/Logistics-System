<?php
// config/db.php
// Database connection configuration

// Load environment variables if load_config.php exists
if (file_exists(__DIR__ . '/load_config.php')) {
    require_once __DIR__ . '/load_config.php';
}

// Check if PDO MySQL is available
if (!extension_loaded('pdo_mysql')) {
    die("PDO MySQL driver is not installed! Please install pdo_mysql extension.");
}

// Detect deployment environment
$is_render = (bool)(getenv('RENDER') || getenv('RENDER_SERVICE_ID'));
$is_railway = (bool)getenv('RAILWAY_ENVIRONMENT');
$database_url = getenv('DATABASE_URL') ?: (getenv('MYSQL_URL') ?: (getenv('CLEARDB_DATABASE_URL') ?: getenv('JAWSDB_URL')));

error_log("Environment: Render=" . ($is_render ? 'true' : 'false') . ", Railway=" . ($is_railway ? 'true' : 'false'));
error_log("DATABASE_URL: " . ($database_url ? 'found' : 'not found'));

// Determine credentials
if ($database_url) {
    // Parse database URL (e.g. mysql://user:password@host:port/dbname)
    $db = parse_url($database_url);
    $host = $db['host'] ?? 'localhost';
    $port = $db['port'] ?? 3306;
    $username = isset($db['user']) ? urldecode($db['user']) : 'root';
    $password = isset($db['pass']) ? urldecode($db['pass']) : '';
    $dbname = isset($db['path']) ? ltrim($db['path'], '/') : 'logistics';
    error_log("Using parsed DATABASE_URL: $host:$port - $dbname");
} elseif (getenv('DB_HOST')) {
    // Explicit environment variables (Render / Cloud environment)
    $host = getenv('DB_HOST');
    $port = getenv('DB_PORT') ?: 3306;
    $username = getenv('DB_USER') ?: (getenv('DB_USERNAME') ?: 'root');
    $password = getenv('DB_PASSWORD') !== false ? getenv('DB_PASSWORD') : (getenv('DB_PASS') !== false ? getenv('DB_PASS') : '');
    $dbname = getenv('DB_NAME') ?: (getenv('DB_DATABASE') ?: 'logistics');
    error_log("Using DB_HOST environment variables: $host:$port - $dbname");
} elseif ($is_railway) {
    // Fallback for Railway
    $host = 'interchange.proxy.rlwy.net';
    $port = 24495;
    $username = 'root';
    $password = 'smPcAHBTUlbFDDubEYhgCGdWndJuhcpQ';
    $dbname = 'railway';
    error_log("Using fallback Railway credentials");
} else {
    // Localhost fallback
    $host = 'localhost';
    $port = 3306;
    $username = 'root';
    $password = '';
    $dbname = 'logistics';
    error_log("Using localhost connection");
}

// Create connection
try {
    $dsn = "mysql:host=$host;dbname=$dbname;charset=utf8mb4";
    if (isset($port) && $port != 3306) {
        $dsn .= ";port=$port";
    }
    
    error_log("Attempting DSN: $dsn");
    
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_TIMEOUT => 5,
    ];

    if (getenv('DB_SSL') === 'true' || getenv('MYSQL_SSL') === 'true') {
        $options[PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT] = false;
    }
    
    $pdo = new PDO($dsn, $username, $password, $options);
    
    // Disable ONLY_FULL_GROUP_BY strict mode which breaks GROUP BY queries
    try {
        $pdo->exec("SET SESSION sql_mode = 'STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION'");
    } catch (PDOException $e) {
        error_log("Warning: Could not set sql_mode: " . $e->getMessage());
    }
    
    // Set MySQL timezone safely
    try {
        $pdo->exec("SET time_zone = '" . date('P') . "'");
    } catch (PDOException $e) {
        error_log("Warning: Could not set time_zone: " . $e->getMessage());
        try {
            $pdo->exec("SET time_zone = '+08:00'");
        } catch (PDOException $e2) {
            error_log("Warning: Could not set fallback time_zone either: " . $e2->getMessage());
        }
    }
    
    error_log("Successfully connected to database: $dbname on $host");
    
} catch (PDOException $e) {
    error_log("Database connection failed: " . $e->getMessage());
    if (php_sapi_name() === 'cli') {
        die("Connection failed: " . $e->getMessage() . "\n");
    } else {
        die("Database connection failed: " . htmlspecialchars($e->getMessage()) . ". Please verify your database settings in the environment variables.");
    }
}
?>