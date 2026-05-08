<?php
/**
 * AntiGravity Payroll PIT - Constants
 * Centralized configuration for application-wide settings
 */

// Robust BASE_URL calculation
if (!defined('BASE_URL')) {
    $protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? "https" : "http";
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost'; 
    
    // Get the directory of the current script
    $script_name = $_SERVER['SCRIPT_NAME'] ?? '';
    $script_dir = str_replace('\\', '/', dirname($script_name));
    
    // Identify if we are in one of the subdirectories and go up
    // This regex looks for /pages, /config, etc. at the end of the path and removes them
    $base_dir = preg_replace('/(\/pages|\/config|\/includes|\/cron|\/tests|\/assets\/.*)$/', '', $script_dir);
    $base_dir = rtrim($base_dir, '/');
    
    $url = $protocol . "://" . $host . $base_dir;
    define('BASE_URL', rtrim($url, '/') . '/');
}

// Other Global Constants
define('APP_NAME', 'AntiGravity Payroll PIT');
define('VERSION', '1.1.0');
?>
