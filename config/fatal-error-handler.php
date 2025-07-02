<?php
/**
 * Fatal error handler for Menalego
 * 
 * This file is included from config.php and registers handlers for fatal errors
 */

// Register shutdown function to catch fatal errors
register_shutdown_function('check_for_fatal_error');

/**
 * Function that runs when PHP shuts down - checks if a fatal error occurred
 */
function check_for_fatal_error() {
    $error = error_get_last();
    
    // Check if error is fatal
    if ($error !== NULL && in_array($error['type'], [E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR, E_PARSE])) {
        // Clear any output buffer to prevent partial page display
        while (ob_get_level() > 0) {
            ob_end_clean();
        }
        
        // Get error details
        $type = 'Fatal Error';
        $message = $error['message'];
        $file = $error['file'];
        $line = $error['line'];
        
        // Check if we're in a CLI environment
        if (php_sapi_name() === 'cli') {
            echo "Fatal Error: $message in $file on line $line\n";
            exit(1);
        }
        
        // HTTP response code
        http_response_code(500);
        
        // If we have a defined constant for site URL, use it
        $site_url = defined('SITE_URL') ? SITE_URL : '';
        $back_url = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '';
        
        // Redirect to error page with details
        header("Location: {$site_url}/error.php?" . http_build_query([
            'type' => $type,
            'message' => $message,
            'file' => $file,
            'line' => $line,
            'back' => $back_url
        ]));
        
        exit;
    }
}
