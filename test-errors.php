<?php
/**
 * Test Error Pages
 * 
 * This script allows testing both the error.php and 404.php pages
 * with different error scenarios.
 */

require_once 'config/config.php';

// Type of error to trigger
$error_type = isset($_GET['type']) ? $_GET['type'] : '';

// Based on type parameter, trigger different errors
switch($error_type) {
    case 'fatal':
        // Trigger a fatal error
        echo "Triggering a fatal error:<br>";
        non_existent_function(); // This will cause a fatal error
        break;
    
    case 'warning':
        // Trigger a warning
        echo "Triggering a warning:<br>";
        $undefined_var = $non_existent_var + 1; // This will trigger a warning
        echo "<p>If you see this, the error was handled but execution continued.</p>";
        break;
    
    case 'notice':
        // Trigger a notice
        echo "Triggering a notice:<br>";
        $undefined_var = $non_existent_var; // This will trigger a notice
        echo "<p>If you see this, the error was handled but execution continued.</p>";
        break;
    
    case 'custom':
        // Manually redirect to error page
        redirectToErrorPage(
            "This is a test of the custom error page.",
            "Test Error",
            __FILE__,
            __LINE__
        );
        break;
    
    case '404':
        // Test the 404 page
        header("HTTP/1.0 404 Not Found");
        include '404.php';
        break;
        
    default:
        // Show options
        echo "<h1>Test Error Pages</h1>";
        echo "<p>Use the following links to test different error scenarios:</p>";
        echo "<ul>";
        echo "<li><a href='?type=fatal'>Test Fatal Error</a> - This will trigger a fatal PHP error</li>";
        echo "<li><a href='?type=warning'>Test Warning</a> - This will trigger a PHP warning</li>";
        echo "<li><a href='?type=notice'>Test Notice</a> - This will trigger a PHP notice</li>";
        echo "<li><a href='?type=custom'>Test Custom Error</a> - This will manually trigger the error page</li>";
        echo "<li><a href='?type=404'>Test 404 Page</a> - This will show the 404 page</li>";
        echo "</ul>";
        
        echo "<p>Note: In a real-world scenario:</p>";
        echo "<ul>";
        echo "<li>404 errors will be triggered automatically when a page is not found</li>";
        echo "<li>Fatal errors will be caught by the shutdown function</li>";
        echo "<li>Warnings and notices will be displayed inline with your custom styling</li>";
        echo "</ul>";
}
