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

/**
 * Formats a date in the French format.
 * @param string $date The date to format.
 * @param bool $with_time Whether to include the time.
 * @return string The formatted date string.
 */
function formatDate($date, $with_time = true) {
    if (!$date) {
        return '-';
    }
    
    $timestamp = strtotime($date);
    if ($with_time) {
        return date('d/m/Y à H:i', $timestamp);
    }
    return date('d/m/Y', $timestamp);
}

/**
 * Get status badge class for order status
 * @param string $status The order status
 * @return string CSS class for the status badge
 */
function getStatusBadgeClass($status) {
    switch ($status) {
        case 'en_attente':
            return 'badge-warning';
        case 'confirmee':
            return 'badge-info';
        case 'preparee':
            return 'badge-primary';
        case 'expediee':
            return 'badge-primary';
        case 'livree':
            return 'badge-success';
        case 'annulee':
            return 'badge-danger';
        default:
            return 'badge-secondary';
    }
}

/**
 * Get human-readable text for order status
 * @param string $status The order status
 * @return string Human-readable status text
 */
function getStatusText($status) {
    switch ($status) {
        case 'en_attente':
            return 'En attente';
        case 'confirmee':
            return 'Confirmée';
        case 'preparee':
            return 'Préparée';
        case 'expediee':
            return 'Expédiée';
        case 'livree':
            return 'Livrée';
        case 'annulee':
            return 'Annulée';
        default:
            return 'Inconnu';
    }
}

/**
 * Display any stored error messages in a user-friendly format
 * @return string HTML for the error message or empty string if no error
 */
function displayErrorMessage() {
    if (isset($_SESSION['error_message'])) {
        $error = $_SESSION['error_message'];
        unset($_SESSION['error_message']);
        
        return '
        <div class="notification notification-danger" style="margin-bottom: 1.5rem;">
            <div style="font-weight: bold;">' . htmlspecialchars($error['type']) . ':</div>
            <div>' . htmlspecialchars($error['message']) . '</div>
            <div style="font-size: 0.8rem; margin-top: 0.5rem; color: #666;">
                ' . htmlspecialchars($error['file']) . ' (ligne ' . $error['line'] . ')
            </div>
        </div>';
    }
    
    return '';
}

/**
 * Redirect to the error page with details about the error
 * @param string $errorMessage The error message
 * @param string $errorType The type of error
 * @param string $file The file where the error occurred
 * @param int $line The line where the error occurred
 */
function redirectToErrorPage($errorMessage, $errorType = 'Error', $file = '', $line = 0) {
    $backUrl = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : 'index.php';
    
    // If file is not provided, use the current file
    if (empty($file)) {
        $file = __FILE__;
    }
    
    header("Location: " . SITE_URL . "/error.php?" . http_build_query([
        'type' => $errorType,
        'message' => $errorMessage,
        'file' => $file,
        'line' => $line,
        'back' => $backUrl
    ]));
    exit;
}