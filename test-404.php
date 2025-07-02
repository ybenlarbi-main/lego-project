<?php
/**
 * 404 Page Test Script
 * 
 * This file allows testing the custom 404 page functionality
 * without actually encountering a 404 error.
 */

// Simulate a 404 error
require_once 'config/config.php';

// Set the 404 header
header("HTTP/1.0 404 Not Found");

// Load the 404 page
include '404.php';

// Note: In a real 404 scenario, Apache will serve the 404.php file directly
// based on the ErrorDocument directive in .htaccess
