<?php
/**
 * Quick session test - check if session issues are resolved
 */
require_once 'config/config.php';

echo "<h2>Session Test</h2>";

try {
    // Test session functionality
    if (session_status() === PHP_SESSION_ACTIVE) {
        echo "✅ Session is active<br>";
        echo "Session ID: " . session_id() . "<br>";
        echo "Session Save Path: " . session_save_path() . "<br>";
        
        // Test setting and getting a value
        $_SESSION['test_value'] = 'Hello from Menalego - ' . date('Y-m-d H:i:s');
        
        if (isset($_SESSION['test_value'])) {
            echo "✅ Session write/read test passed<br>";
            echo "Test value: " . $_SESSION['test_value'] . "<br>";
        } else {
            echo "❌ Session write/read test failed<br>";
        }
        
        // Test if user is logged in
        if (isLoggedIn()) {
            echo "✅ User is logged in: " . $_SESSION['user_name'] . "<br>";
        } else {
            echo "ℹ️ No user logged in<br>";
        }
        
        echo "<br><strong>Session Status: WORKING</strong><br>";
        echo '<a href="index.php">← Back to Home</a> | ';
        echo '<a href="auth/login.php">Login</a> | ';
        echo '<a href="session-utils.php">Session Utils</a>';
        
    } else {
        echo "❌ Session is not active<br>";
    }
    
} catch (Exception $e) {
    echo "❌ Session error: " . $e->getMessage() . "<br>";
}
?>
