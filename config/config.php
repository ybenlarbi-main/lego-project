<?php
// Enhanced session configuration with fallback
if (session_status() == PHP_SESSION_NONE) {
    // Include fallback session handler
    require_once __DIR__ . '/session-fallback.php';
    
    // Try the safer session start method
    if (!startSessionSafely()) {
        // Final fallback - basic session with error suppression
        @session_start();
    }
}

// Database configuration
require_once __DIR__ . '/database.php';

// Include fatal error handler
require_once __DIR__ . '/fatal-error-handler.php';

// Custom error handler
function custom_error_handler($errno, $errstr, $errfile, $errline) {
    // Only handle errors based on error_reporting settings
    if (!(error_reporting() & $errno)) {
        return false;
    }
    
    // Get error type as string
    $error_types = [
        E_ERROR => 'Error',
        E_WARNING => 'Warning',
        E_PARSE => 'Parse Error',
        E_NOTICE => 'Notice',
        E_CORE_ERROR => 'Core Error',
        E_CORE_WARNING => 'Core Warning',
        E_COMPILE_ERROR => 'Compile Error',
        E_COMPILE_WARNING => 'Compile Warning',
        E_USER_ERROR => 'User Error',
        E_USER_WARNING => 'User Warning',
        E_USER_NOTICE => 'User Notice',
        E_STRICT => 'Strict Error',
        E_RECOVERABLE_ERROR => 'Recoverable Error',
        E_DEPRECATED => 'Deprecated',
        E_USER_DEPRECATED => 'User Deprecated'
    ];
    
    $error_type = isset($error_types[$errno]) ? $error_types[$errno] : 'Unknown Error';
    
    // For fatal errors, redirect to error page
    if ($errno == E_ERROR || $errno == E_USER_ERROR || $errno == E_CORE_ERROR) {
        header("Location: " . SITE_URL . "/error.php?" . http_build_query([
            'type' => $error_type,
            'message' => $errstr,
            'file' => $errfile,
            'line' => $errline,
            'back' => $_SERVER['REQUEST_URI']
        ]));
        exit;
    }
    
    // For non-fatal errors, store in session and show on next page
    $_SESSION['error_message'] = [
        'type' => $error_type,
        'message' => $errstr,
        'file' => $errfile,
        'line' => $errline
    ];
    
    // Let PHP handle the error as well
    return false;
}

// Set the custom error handler
set_error_handler('custom_error_handler', E_ALL);

// Site configuration
define('SITE_NAME', 'Menalego');
define('SITE_URL', 'http://localhost/new-mohamed/menalego');
define('ADMIN_EMAIL', 'admin@menalego.ma');
define('BASE_PATH', dirname(__DIR__));
define('UPLOAD_PATH', BASE_PATH . '/assets/uploads/');
define('UPLOAD_URL', SITE_URL . '/assets/uploads/');
require_once __DIR__ . '/../includes/functions.php';

// Language settings
$available_languages = ['fr' => 'Français', 'ar' => 'العربية'];
$default_language = 'fr';

// Set current language
if (!isset($_SESSION['language'])) {
    $_SESSION['language'] = $default_language;
}

$current_language = $_SESSION['language'];

// Security settings
define('PASSWORD_SALT', 'menalego_secure_salt_2025');
define('SESSION_LIFETIME', 3600); // 1 hour

// File upload settings
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB
define('UPLOAD_DIR', 'assets/uploads/');
define('ALLOWED_EXTENSIONS', ['jpg', 'jpeg', 'png', 'gif', 'webp']);

// Pagination
define('PRODUCTS_PER_PAGE', 12);
define('REVIEWS_PER_PAGE', 10);

// Helper functions
function getCurrentUser() {
    return $_SESSION['user'] ?? null;
}

function getCurrentLanguage() {
    return $_SESSION['language'] ?? 'fr';
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function isAdmin() {
    return isLoggedIn() && isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
}

function getCurrentUserId() {
    return $_SESSION['user_id'] ?? 0;
}

function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: ' . SITE_URL . '/auth/login.php');
        exit;
    }
}


function sanitizeInput($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

function sanitize($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}



function getImageUrl($imagePath) {
    if (!$imagePath) return SITE_URL . '/assets/images/placeholder.svg';
    if (strpos($imagePath, 'http') === 0) return $imagePath;
    return UPLOAD_URL . $imagePath;
}

function uploadImage($file, $directory = 'products') {
    if (!isset($file['tmp_name']) || empty($file['tmp_name'])) {
        return false;
    }
    
    $upload_dir = UPLOAD_PATH . $directory . '/';
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }
    
    $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($file_extension, ALLOWED_EXTENSIONS)) {
        return false;
    }
    
    if ($file['size'] > MAX_FILE_SIZE) {
        return false;
    }
    
    $new_filename = uniqid() . '.' . $file_extension;
    $target_path = $upload_dir . $new_filename;
    
    if (move_uploaded_file($file['tmp_name'], $target_path)) {
        return $directory . '/' . $new_filename;
    }
    
    return false;
}



function isVendor() {
    return isLoggedIn() && isset($_SESSION['user']['role']) && $_SESSION['user']['role'] === 'vendeur';
}

function isClient() {
    return isLoggedIn() && isset($_SESSION['user']['role']) && $_SESSION['user']['role'] === 'client';
}

function redirectTo($url) {
    header("Location: $url");
    exit();
}

function generateOrderNumber() {
    return 'MEN-' . date('Y') . '-' . str_pad(rand(1, 999999), 6, '0', STR_PAD_LEFT);
}

// Language function
function t($key, $lang = null) {
    global $current_language;
    if ($lang === null) $lang = $current_language;
    
    $translations = [
        'fr' => [
            'home' => 'Accueil',
            'products' => 'Produits',
            'about' => 'À propos',
            'contact' => 'Contact',
            'login' => 'Connexion',
            'register' => 'S\'inscrire',
            'logout' => 'Déconnexion',
            'cart' => 'Panier',
            'wishlist' => 'Liste de souhaits',
            'profile' => 'Profil',
            'orders' => 'Commandes',
            'dashboard' => 'Tableau de bord',
            'search' => 'Rechercher',
            'add_to_cart' => 'Ajouter au panier',
            'buy_now' => 'Acheter maintenant',
            'price' => 'Prix',
            'description' => 'Description',
            'reviews' => 'Avis',
            'rating' => 'Note',
            'availability' => 'Disponibilité',
            'in_stock' => 'En stock',
            'out_of_stock' => 'Rupture de stock',
            'moroccan_heritage' => 'Héritage Marocain',
            'creative_play' => 'Jeu Créatif',
            'welcome' => 'Bienvenue chez Menalego',
            'tagline' => 'Construisez votre héritage marocain, brique par brique'
        ],
        'ar' => [
            'home' => 'الرئيسية',
            'products' => 'المنتجات',
            'about' => 'حول',
            'contact' => 'اتصال',
            'login' => 'تسجيل الدخول',
            'register' => 'التسجيل',
            'logout' => 'تسجيل الخروج',
            'cart' => 'السلة',
            'wishlist' => 'قائمة الأمنيات',
            'profile' => 'الملف الشخصي',
            'orders' => 'الطلبات',
            'dashboard' => 'لوحة التحكم',
            'search' => 'بحث',
            'add_to_cart' => 'أضف إلى السلة',
            'buy_now' => 'اشتري الآن',
            'price' => 'السعر',
            'description' => 'الوصف',
            'reviews' => 'التقييمات',
            'rating' => 'التقييم',
            'availability' => 'التوفر',
            'in_stock' => 'متوفر',
            'out_of_stock' => 'غير متوفر',
            'moroccan_heritage' => 'التراث المغربي',
            'creative_play' => 'اللعب الإبداعي',
            'welcome' => 'مرحباً بكم في مينالليجو',
            'tagline' => 'ابنوا تراثكم المغربي، حجرة تلو الأخرى'
        ]
    ];
    
    return $translations[$lang][$key] ?? $key;
}

// Set timezone
date_default_timezone_set('Africa/Casablanca');

// Initialize database connection
$database = new Database();
$pdo = $database->getConnection();
?>
