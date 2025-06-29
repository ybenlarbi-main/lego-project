<?php
// menalego/includes/functions.php

// Start the session if it's not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Sets a flash message to be displayed on the next page load.
 * @param string $message The message to display.
 * @param string $type The type of message (e.g., 'success', 'danger', 'info').
 */
function setFlashMessage($message, $type = 'info') {
    $_SESSION['flash_message'] = [
        'message' => $message,
        'type' => $type
    ];
}

/**
 * Gets and displays the flash message, then clears it.
 * @return string HTML for the notification, or an empty string if no message.
 */
function getFlashMessage() {
    if (isset($_SESSION['flash_message'])) {
        $message = $_SESSION['flash_message']['message'];
        $type = $_SESSION['flash_message']['type'];
        
        // Clear the message from the session so it doesn't show again
        unset($_SESSION['flash_message']);
        
        // Return the HTML for the notification
        return '<div class="notification notification-'.$type.' show">' . htmlspecialchars($message) . '</div>';
    }
    return '';
}

// Add other helper functions here as your project grows...
function requireAdmin() {
    // Implement your admin check logic here
    if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
        setFlashMessage('Accès refusé. Vous devez être administrateur.', 'danger');
        header('Location: ' . SITE_URL . '/auth/login.php');
        exit();
    }
}

function sanitizeInput($data) {
    return htmlspecialchars(stripslashes(trim($data)));
}

function formatPrice($price) {
    return number_format($price, 2, ',', ' ') . ' DH';
}
// Add any other global functions you might have