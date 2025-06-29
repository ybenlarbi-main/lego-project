<?php
require_once '../config/config.php';

// Destroy session
session_destroy();

// Redirect to home page
header('Location: ' . SITE_URL);
exit;
?>
