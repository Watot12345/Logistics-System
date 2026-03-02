<?php
session_start();
require_once 'config/db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

if (isset($_GET['file'])) {
    $document_id = $_GET['file'];
    
    // Get document info
    $stmt = $pdo->prepare("SELECT * FROM documents WHERE id = ?");
    $stmt->execute([$document_id]);
    $doc = $stmt->fetch();
    
    if ($doc) {
        $file_path = $doc['file_path'];
        
        if (file_exists($file_path)) {
            // Log the download activity
            $log_stmt = $pdo->prepare("
                INSERT INTO user_activity_logs (user_id, document_id, action_type, timestamp) 
                VALUES (?, ?, 'download', NOW())
            ");
            $log_stmt->execute([$_SESSION['user_id'], $document_id]);
            
            // Force download
            header('Content-Description: File Transfer');
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="' . $doc['file_name'] . '"');
            header('Expires: 0');
            header('Cache-Control: must-revalidate');
            header('Pragma: public');
            header('Content-Length: ' . filesize($file_path));
            
            // Clear output buffer
            ob_clean();
            flush();
            
            readfile($file_path);
            exit();
        } else {
            echo "File not found. Path: " . $file_path;
        }
    } else {
        echo "Document not found in database.";
    }
} else {
    echo "No file specified.";
}
?>