<?php
// ===== SECURITY HEADERS =====
// This file adds all security headers to protect your site
// Include this at the VERY TOP of any PHP file that outputs HTML

// Prevent any output before headers
if (headers_sent($filename, $linenum)) {
    error_log("⚠️ Headers already sent in $filename on line $linenum");
    return;
}

// ===== STRICT TRANSPORT SECURITY (HSTS) =====
// Forces browser to always use HTTPS for 1 year
// This prevents SSL stripping attacks
header("Strict-Transport-Security: max-age=31536000; includeSubDomains; preload");

// ===== CONTENT SECURITY POLICY (CSP) =====
// Prevents XSS attacks by controlling what resources can load
// Customize this based on your site's needs
header("Content-Security-Policy: " . 
       "default-src 'self'; " .                           // Only load from same origin by default
       "script-src 'self' https://cdnjs.cloudflare.com 'unsafe-inline' 'unsafe-eval'; " .  // Allow scripts from self and Cloudflare
       "style-src 'self' https://cdnjs.cloudflare.com 'unsafe-inline'; " .                 // Allow styles from self and Cloudflare
       "img-src 'self' data: https:; " .                   // Allow images from self, data URIs, and HTTPS
       "font-src 'self' https://cdnjs.cloudflare.com; " .  // Allow fonts from self and Cloudflare
       "connect-src 'self'; " .                             // Allow AJAX/fetch to same origin
       "frame-ancestors 'none';");                          // Prevent framing (clickjacking protection)

// ===== REFERRER POLICY =====
// Controls how much referrer info is sent when clicking links
// Prevents leaking sensitive URL data to external sites
header("Referrer-Policy: strict-origin-when-cross-origin");

// ===== PERMISSIONS POLICY =====
// Disables browser features you don't need
// Prevents malicious scripts from accessing sensitive APIs
header("Permissions-Policy: " .
       "geolocation=(), " .          // Disable geolocation
       "microphone=(), " .           // Disable microphone
       "camera=(), " .               // Disable camera
       "payment=(), " .              // Disable payment API
       "usb=(), " .                  // Disable USB access
       "magnetometer=(), " .         // Disable magnetometer
       "accelerometer=(), " .        // Disable accelerometer
       "gyroscope=(), " .            // Disable gyroscope
       "screen-wake-lock=(), " .     // Disable wake lock
       "picture-in-picture=(), " .   // Disable picture-in-picture
       "fullscreen=(self), " .       // Allow fullscreen only from same origin
       "autoplay=(self)");           // Allow autoplay only from same origin

// ===== CLICKJACKING PROTECTION =====
// Prevents your site from being embedded in iframes on other sites
header("X-Frame-Options: SAMEORIGIN");

// ===== MIME SNIFFING PROTECTION =====
// Prevents browser from interpreting files as a different MIME type
header("X-Content-Type-Options: nosniff");

// ===== REMOVE SERVER INFO =====
// Hides PHP version from attackers
header_remove('X-Powered-By');

// ===== SECURE SESSION COOKIES =====
// These settings make session cookies more secure
if (session_status() === PHP_SESSION_ACTIVE) {
    // Only set if session is active
    ini_set('session.cookie_httponly', 1);  // Prevent JavaScript access to cookies
    ini_set('session.cookie_secure', 1);    // Only send cookies over HTTPS
    ini_set('session.cookie_samesite', 'Strict'); // CSRF protection
    ini_set('session.use_strict_mode', 1);  // Reject uninitialized session IDs
    ini_set('session.use_only_cookies', 1); // Only use cookies, not URL parameters
    ini_set('session.cookie_path', '/');     // Cookie valid for entire domain
}

// ===== LOGGING (Optional - remove in production if you want) =====
// Uncomment for debugging
// error_log("🔒 Security headers applied successfully");

// Optional: Return true for chaining
return true;
?>