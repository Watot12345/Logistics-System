<?php
// ===== SECURITY HEADERS =====
// This file adds all security headers to protect your site

// Prevent any output before headers
if (headers_sent($filename, $linenum)) {
    error_log("⚠️ Headers already sent in $filename on line $linenum");
    return;
}

// ===== STRICT TRANSPORT SECURITY (HSTS) =====
header("Strict-Transport-Security: max-age=31536000; includeSubDomains; preload");

// ===== CONTENT SECURITY POLICY (CSP) =====
header("Content-Security-Policy: " . 
       "default-src 'self'; " .
       "script-src 'self' https://cdnjs.cloudflare.com 'unsafe-inline' 'unsafe-eval'; " .
       "style-src 'self' https://cdnjs.cloudflare.com https://fonts.googleapis.com 'unsafe-inline'; " .
       "img-src 'self' data: https:; " .
       "font-src 'self' https://cdnjs.cloudflare.com https://fonts.gstatic.com; " .
       "connect-src 'self'; " .
       "frame-ancestors 'none';");

// ===== REFERRER POLICY =====
header("Referrer-Policy: strict-origin-when-cross-origin");

// ===== PERMISSIONS POLICY =====
header("Permissions-Policy: " .
       "geolocation=(), " .
       "microphone=(), " .
       "camera=(), " .
       "payment=(), " .
       "usb=(), " .
       "magnetometer=(), " .
       "accelerometer=(), " .
       "gyroscope=(), " .
       "screen-wake-lock=(), " .
       "picture-in-picture=(), " .
       "fullscreen=(self), " .
       "autoplay=(self)");

// ===== CLICKJACKING PROTECTION =====
header("X-Frame-Options: SAMEORIGIN");

// ===== MIME SNIFFING PROTECTION =====
header("X-Content-Type-Options: nosniff");

// ===== REMOVE SERVER INFO =====
header_remove('X-Powered-By');

// ===== SESSION CHECK (NOT SETTING, JUST CHECKING) =====
if (session_status() === PHP_SESSION_ACTIVE) {
    $httponly = ini_get('session.cookie_httponly');
    $secure = ini_get('session.cookie_secure');
    $samesite = ini_get('session.cookie_samesite');
    
    if (!$httponly || !$secure || $samesite !== 'Strict') {
        error_log("⚠️ Session cookie settings not optimal - check session_config.php");
    }
}

// Optional: Log success
// error_log("🔒 Security headers applied successfully");

return true;
?>