<?php
// menalego/includes/functions.php

// The session is already started by config.php, so no need to start it here again.

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
        $message_data = $_SESSION['flash_message'];
        unset($_SESSION['flash_message']); // Clear immediately
        
        $message = $message_data['message'];
        $type = $message_data['type'];
        
        return '<div class="notification notification-'.$type.' show">' . htmlspecialchars($message) . '</div>';
    }
    return '';
}

/**
 * Checks if a user is logged in and is an admin. Redirects if not.
 */
function requireAdmin() {
    if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
        setFlashMessage('Accès refusé. Vous devez être administrateur pour voir cette page.', 'danger');
        header('Location: ' . SITE_URL . '/auth/login.php');
        exit();
    }
}



/**
 * Formats a number as a price in MAD.
 * @param float $price The price to format.
 * @return string The formatted price string.
 */
function formatPrice($price) {
    if (!is_numeric($price)) {
        return '0,00 DH';
    }
    return number_format($price, 2, ',', ' ') . ' DH';
}