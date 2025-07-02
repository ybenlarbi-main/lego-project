<?php
/**
 * Alternative Session Configuration for XAMPP
 * Use this if the main session configuration causes issues
 */

// Function to start session with fallback options
function startSessionSafely() {
    if (session_status() === PHP_SESSION_ACTIVE) {
        return true;
    }
    
    // Try multiple session configurations
    $configs = [
        // Config 1: Custom session path
        function() {
            $session_path = dirname(__DIR__) . '/tmp/sessions';
            if (!is_dir($session_path)) {
                mkdir($session_path, 0755, true);
            }
            session_save_path($session_path);
            session_name('MENALEGO_SESSION');
        },
        
        // Config 2: System temp directory
        function() {
            session_save_path(sys_get_temp_dir());
            session_name('MENALEGO_SESSION');
        },
        
        // Config 3: Default XAMPP settings
        function() {
            // Use default settings
            session_name('MENALEGO_SESSION');
        }
    ];
    
    foreach ($configs as $i => $config) {
        try {
            // Reset session settings
            if (session_status() === PHP_SESSION_ACTIVE) {
                session_write_close();
            }
            
            // Apply configuration
            $config();
            
            // Set session parameters
            ini_set('session.cookie_httponly', 1);
            ini_set('session.use_strict_mode', 1);
            ini_set('session.gc_maxlifetime', 3600);
            ini_set('session.cookie_lifetime', 3600);
            
            // Try to start session
            if (session_start()) {
                error_log("Session started successfully with config " . ($i + 1));
                return true;
            }
        } catch (Exception $e) {
            error_log("Session config " . ($i + 1) . " failed: " . $e->getMessage());
            continue;
        }
    }
    
    // If all configs fail, try basic session_start
    try {
        session_start();
        return true;
    } catch (Exception $e) {
        error_log("All session configurations failed: " . $e->getMessage());
        return false;
    }
}

// Export function for use in config.php
return ['startSessionSafely' => 'startSessionSafely'];
?>
