<?php
// init_db.php - Safe database initialization script for cloud deployments
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/config/db.php';

$is_cli = (php_sapi_name() === 'cli');
$force = (isset($_GET['force']) && $_GET['force'] === '1') || (isset($argv) && in_array('--force', $argv));

function output($msg, $is_cli) {
    if ($is_cli) {
        echo $msg . "\n";
    } else {
        echo "<p>" . htmlspecialchars($msg) . "</p>";
    }
}

if (!$is_cli) {
    echo "<!DOCTYPE html><html><head><title>Database Initialization</title><style>body{font-family:system-ui,sans-serif;padding:2rem;line-height:1.5;background:#f9fafb;color:#111827;} .box{max-width:800px;margin:0 auto;background:#fff;padding:2rem;border-radius:8px;box-shadow:0 1px 3px rgba(0,0,0,0.1);} .success{color:#059669;font-weight:bold;} .error{color:#dc2626;font-weight:bold;}</style></head><body><div class='box'>";
    echo "<h1>Logistics System - Database Setup</h1>";
}

try {
    // Check if tables already exist
    $stmt = $pdo->query("SHOW TABLES LIKE 'users'");
    $users_exists = ($stmt->rowCount() > 0);

    if ($users_exists && !$force) {
        output("Database is already initialized! The 'users' table already exists.", $is_cli);
        output("To re-import, run with --force (CLI) or ?force=1 (Web).", $is_cli);
        
        // Show table count
        $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
        output("Existing tables (" . count($tables) . "): " . implode(', ', $tables), $is_cli);

        if (!$is_cli) {
            echo "<p><a href='index.php'>Go to Application Login &rarr;</a></p></div></body></html>";
        }
        exit(0);
    }

    $sql_file = __DIR__ . '/logistics(8).sql';
    if (!file_exists($sql_file)) {
        $sql_file = __DIR__ . '/sql.sql';
    }

    if (!file_exists($sql_file)) {
        throw new Exception("SQL dump file not found!");
    }

    output("Reading SQL dump: " . basename($sql_file) . "...", $is_cli);
    $sql = file_get_contents($sql_file);

    // Strip DEFINER clauses which cause access denied errors on managed MySQL (Aiven, RDS, etc.)
    $sql = preg_replace('/DEFINER\s*=\s*`[^`]+`@`[^`]+`/i', '', $sql);
    $sql = preg_replace('/DEFINER\s*=\s*[^\s]+/i', '', $sql);

    // Split SQL into individual statements
    output("Executing SQL statements...", $is_cli);
    $queries = explode(";\n", $sql);
    $success_count = 0;
    $error_count = 0;

    foreach ($queries as $query) {
        $query = trim($query);
        if (empty($query)) {
            continue;
        }

        // Skip comments or transactional wrappers if needed
        if (preg_match('/^(\/\*|--|#)/', $query)) {
            // Check if there are non-comment lines
            $lines = array_filter(explode("\n", $query), function($line) {
                $line = trim($line);
                return !empty($line) && !preg_match('/^(\/\*|--|#)/', $line);
            });
            if (empty($lines)) {
                continue;
            }
            $query = implode("\n", $lines);
        }

        try {
            $pdo->exec($query);
            $success_count++;
        } catch (PDOException $e) {
            $error_count++;
            // Ignore minor duplicate/drop errors during re-runs
            error_log("init_db statement warning: " . $e->getMessage());
        }
    }

    output("Import completed! Executed $success_count statements successfully ($error_count warnings/errors).", $is_cli);
    
    // Check imported tables
    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    output("Total tables created: " . count($tables), $is_cli);
    
    if (in_array('users', $tables)) {
        $user_count = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
        output("Users in database: $user_count", $is_cli);
    }

    if (!$is_cli) {
        echo "<p class='success'>Setup complete! <a href='index.php'>Go to Application Login &rarr;</a></p>";
        echo "</div></body></html>";
    }

} catch (Exception $e) {
    output("Error initializing database: " . $e->getMessage(), $is_cli);
    if (!$is_cli) {
        echo "</div></body></html>";
    }
    exit(1);
}
