<?php
// ===== SESSION CONFIGURATION =====
// This file MUST be included BEFORE session_start()
// Put all your session INI settings here

// Secure session cookies
ini_set('session.cookie_httponly', 1);      // Prevent JavaScript access
ini_set('session.cookie_secure', 1);        // Only send over HTTPS
ini_set('session.cookie_samesite', 'Strict'); // CSRF protection
ini_set('session.use_strict_mode', 1);       // Reject uninitialized IDs
ini_set('session.use_only_cookies', 1);      // Only use cookies
ini_set('session.cookie_path', '/');         // Valid for entire domain
ini_set('session.gc_maxlifetime', 1800);     // 30 minute timeout
ini_set('session.cookie_lifetime', 0);       // Until browser closes

// Optional: Log that session config was applied
error_log("🔒 Session configuration applied");
?>