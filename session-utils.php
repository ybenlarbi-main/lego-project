<?php
/**
 * Session Management Utility
 * Call this file to clean up sessions or fix session issues
 */

// Function to clean old session files
function cleanupSessions($session_path = null) {
    if ($session_path === null) {
        $session_path = sys_get_temp_dir() . '/menalego_sessions';
    }
    
    if (!is_dir($session_path)) {
        return false;
    }
    
    $cleaned = 0;
    $max_lifetime = 3600; // 1 hour
    $now = time();
    
    $files = glob($session_path . '/sess_*');
    foreach ($files as $file) {
        if (file_exists($file)) {
            $file_time = filemtime($file);
            if (($now - $file_time) > $max_lifetime) {
                if (unlink($file)) {
                    $cleaned++;
                }
            }
        }
    }
    
    return $cleaned;
}

// Function to regenerate session ID safely
function regenerateSession() {
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_regenerate_id(true);
        return session_id();
    }
    return false;
}

// Function to get session info
function getSessionInfo() {
    return [
        'session_id' => session_id(),
        'session_status' => session_status(),
        'session_name' => session_name(),
        'session_save_path' => session_save_path(),
        'cookie_params' => session_get_cookie_params(),
        'cache_limiter' => session_cache_limiter(),
        'module_name' => session_module_name()
    ];
}

// If called directly, show session status
if (basename($_SERVER['SCRIPT_NAME']) === 'session-utils.php') {
    require_once 'config/config.php';
    
    echo "<h2>Menalego Session Management</h2>";
    
    if (isset($_GET['action'])) {
        switch ($_GET['action']) {
            case 'cleanup':
                $cleaned = cleanupSessions();
                echo "<p>✅ Cleaned $cleaned old session files</p>";
                break;
                
            case 'regenerate':
                if (isLoggedIn()) {
                    $new_id = regenerateSession();
                    echo "<p>✅ Session ID regenerated: $new_id</p>";
                } else {
                    echo "<p>❌ Not logged in - cannot regenerate session</p>";
                }
                break;
                
            case 'destroy':
                session_destroy();
                echo "<p>✅ Session destroyed</p>";
                break;
        }
    }
    
    echo "<h3>Session Information:</h3>";
    echo "<pre>" . print_r(getSessionInfo(), true) . "</pre>";
    
    if (isLoggedIn()) {
        echo "<h3>Current User:</h3>";
        echo "<pre>" . print_r($_SESSION, true) . "</pre>";
    } else {
        echo "<p>No user logged in</p>";
    }
    
    echo "<h3>Actions:</h3>";
    echo '<a href="?action=cleanup">Clean Old Sessions</a> | ';
    echo '<a href="?action=regenerate">Regenerate Session</a> | ';
    echo '<a href="?action=destroy">Destroy Session</a>';
}
?>
