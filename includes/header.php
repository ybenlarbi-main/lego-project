<?php require_once 'config/config.php'; ?>
<!DOCTYPE html>
<html lang="<?php echo $current_language; ?>" <?php echo $current_language === 'ar' ? 'dir="rtl"' : ''; ?>>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title . ' - ' . SITE_NAME : SITE_NAME . ' - ' . t('tagline'); ?></title>
    <meta name="description" content="<?php echo isset($page_description) ? $page_description : 'Menalego - La plateforme e-commerce LEGO inspirée du patrimoine marocain'; ?>">
    <meta name="keywords" content="LEGO, Maroc, jouets, construction, patrimoine, culture, e-commerce">
    
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="<?php echo SITE_URL; ?>/assets/images/favicon.ico">
    
    <!-- CSS -->
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/style.css">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Additional CSS -->
    <?php if (isset($additional_css)): ?>
        <?php foreach ($additional_css as $css): ?>
            <link rel="stylesheet" href="<?php echo $css; ?>">
        <?php endforeach; ?>
    <?php endif; ?>
</head>
<body>
    <?php echo displayErrorMessage(); ?>
    <header class="header">
        <!-- Header Top -->
        <div class="header-top">
            <div class="container">
                <div class="row">
                    <div class="col-6">
                        <span><i class="fas fa-phone"></i> +212 5 24 XX XX XX</span>
                        <span class="ml-3"><i class="fas fa-envelope"></i> contact@menalego.ma</span>
                    </div>
                    <div class="col-6 text-right">
                        <!-- Language Switcher -->
                        <div class="language-switcher d-inline">
                            <select onchange="changeLanguage(this.value)" style="background: transparent; border: none; color: white;">
                                <option value="fr" <?php echo $current_language === 'fr' ? 'selected' : ''; ?>>Français</option>
                                <option value="ar" <?php echo $current_language === 'ar' ? 'selected' : ''; ?>>العربية</option>
                            </select>
                        </div>
                        
                        <?php if (isLoggedIn()): ?>
                            <span class="ml-3"><?php echo t('welcome'); ?>, <?php echo htmlspecialchars($_SESSION['user']['prenom']); ?>!</span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Header Main -->
        <div class="header-main">
            <div class="container">
                <nav class="navbar">
                    <!-- Logo -->
                    <a href="<?php echo SITE_URL; ?>" class="logo">
                        <i class="fas fa-cubes"></i> Menalego
                    </a>
                    
                    <!-- Search Bar -->
                    <div class="search-bar">
                        <form action="<?php echo SITE_URL; ?>/search.php" method="GET">
                            <input type="text" name="q" class="search-input" 
                                   placeholder="<?php echo t('search'); ?> <?php echo t('moroccan_heritage'); ?>..." 
                                   value="<?php echo isset($_GET['q']) ? htmlspecialchars($_GET['q']) : ''; ?>">
                            <button type="submit" class="search-btn">
                                <i class="fas fa-search"></i>
                            </button>
                        </form>
                    </div>
                    
                    <!-- Navigation Menu -->
                    <ul class="nav-menu">
                        <li><a href="<?php echo SITE_URL; ?>" class="nav-link"><?php echo t('home'); ?></a></li>
                        <li><a href="<?php echo SITE_URL; ?>/products.php" class="nav-link"><?php echo t('products'); ?></a></li>
                        <li><a href="<?php echo SITE_URL; ?>/about.php" class="nav-link"><?php echo t('about'); ?></a></li>
                        <li><a href="<?php echo SITE_URL; ?>/contact.php" class="nav-link"><?php echo t('contact'); ?></a></li>
                        
                        <?php if (isLoggedIn()): ?>
                            <!-- Logged in user menu -->
                            <li><a href="<?php echo SITE_URL; ?>/cart.php" class="nav-link">
                                <i class="fas fa-shopping-cart"></i> <?php echo t('cart'); ?>
                                <span id="cart-count" class="badge">0</span>
                            </a></li>
                            
                            <li><a href="<?php echo SITE_URL; ?>/wishlist.php" class="nav-link">
                                <i class="fas fa-heart"></i> <?php echo t('wishlist'); ?>
                            </a></li>
                            
                            <li class="dropdown">
                                <a href="#" class="nav-link dropdown-toggle">
                                    <i class="fas fa-user"></i> <?php echo htmlspecialchars($_SESSION['user']['prenom']); ?>
                                </a>
                                <ul class="dropdown-menu">
                                    <li><a href="<?php echo SITE_URL; ?>/profile.php"><?php echo t('profile'); ?></a></li>
                                    <li><a href="<?php echo SITE_URL; ?>/orders.php"><?php echo t('orders'); ?></a></li>
                                    
                                    <?php if (isVendor()): ?>
                                        <li><a href="<?php echo SITE_URL; ?>/vendor/dashboard.php"><?php echo t('dashboard'); ?></a></li>
                                    <?php endif; ?>
                                    
                                    <?php if (isAdmin()): ?>
                                        <li><a href="<?php echo SITE_URL; ?>/admin/dashboard.php">Admin Dashboard</a></li>
                                    <?php endif; ?>
                                    
                                    <li><hr class="dropdown-divider"></li>
                                    <li><a href="<?php echo SITE_URL; ?>/logout.php"><?php echo t('logout'); ?></a></li>
                                </ul>
                            </li>
                        <?php else: ?>
                            <!-- Guest user menu -->
                            <li><a href="<?php echo SITE_URL; ?>/login.php" class="nav-link"><?php echo t('login'); ?></a></li>
                            <li><a href="<?php echo SITE_URL; ?>/register.php" class="btn btn-secondary"><?php echo t('register'); ?></a></li>
                        <?php endif; ?>
                    </ul>
                    
                    <!-- Mobile Menu Toggle -->
                    <button class="mobile-menu-toggle d-none">
                        <i class="fas fa-bars"></i>
                    </button>
                </nav>
            </div>
        </div>
    </header>
    
    <!-- Main Content -->
    <main class="main-content">
