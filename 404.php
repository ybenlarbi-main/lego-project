<?php
/**
 * Custom 404 error page for Menalego
 */

// Include configuration
require_once 'config/config.php';

// Set proper HTTP response code
http_response_code(404);

// Get the requested URL
$requested_url = isset($_SERVER['REQUEST_URI']) ? htmlspecialchars($_SERVER['REQUEST_URI']) : 'unknown page';

$page_title = '404 - Page Not Found';
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background-color: #f8fafc;
            font-family: 'Montserrat', sans-serif;
        }
        
        .error-container {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            min-height: 80vh;
            max-width: 800px;
            margin: 0 auto;
            padding: 2rem;
            text-align: center;
        }
        
        .error-code {
            font-size: 8rem;
            font-weight: 800;
            color: #0061FF;
            line-height: 1;
            margin-bottom: 1rem;
            position: relative;
        }
        
        .error-code::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 50%;
            width: 100px;
            height: 8px;
            background: #0061FF;
            transform: translateX(-50%);
            border-radius: 4px;
        }
        
        .error-title {
            font-size: 2rem;
            font-weight: 700;
            color: #1f2937;
            margin-bottom: 1rem;
        }
        
        .error-message {
            color: #4b5563;
            font-size: 1.1rem;
            margin-bottom: 2rem;
            max-width: 500px;
        }
        
        .error-image {
            width: 100%;
            max-width: 300px;
            margin-bottom: 2rem;
        }
        
        .error-actions {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
            justify-content: center;
        }
        
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.75rem 1.5rem;
            background: #0061FF;
            color: white;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.2s ease;
        }
        
        .btn:hover {
            background: #0052d6;
            transform: translateY(-1px);
        }
        
        .btn-secondary {
            background: #e5e7eb;
            color: #4b5563;
        }
        
        .btn-secondary:hover {
            background: #d1d5db;
        }
        
        .search-container {
            margin: 2rem 0;
            width: 100%;
            max-width: 500px;
        }
        
        .search-form {
            display: flex;
            width: 100%;
        }
        
        .search-input {
            flex-grow: 1;
            padding: 0.75rem 1rem;
            border: 2px solid #e5e7eb;
            border-right: none;
            border-radius: 6px 0 0 6px;
            font-family: 'Montserrat', sans-serif;
            font-size: 1rem;
        }
        
        .search-input:focus {
            outline: none;
            border-color: #0061FF;
        }
        
        .search-button {
            padding: 0.75rem 1.5rem;
            background: #0061FF;
            color: white;
            border: none;
            border-radius: 0 6px 6px 0;
            cursor: pointer;
            font-weight: 500;
        }
        
        .suggestions {
            margin-top: 2rem;
        }
        
        .suggestion-title {
            font-size: 1.2rem;
            color: #1f2937;
            margin-bottom: 1rem;
        }
        
        .suggestion-links {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 1rem;
        }
        
        .suggestion-link {
            padding: 0.5rem 1rem;
            background: #f0f9ff;
            color: #0369a1;
            text-decoration: none;
            border-radius: 4px;
            transition: background-color 0.2s;
        }
        
        .suggestion-link:hover {
            background: #e0f2fe;
        }

        /* Animation for the LEGO bricks */
        @keyframes float {
            0% {
                transform: translateY(0px);
            }
            50% {
                transform: translateY(-10px);
            }
            100% {
                transform: translateY(0px);
            }
        }

        .lego-brick {
            position: relative;
            width: 60px;
            height: 40px;
            margin: 0 10px;
            background: #e53e3e;
            border-radius: 4px;
            animation: float 3s ease-in-out infinite;
        }

        .lego-brick:nth-child(2) {
            background: #0061FF;
            animation-delay: 0.5s;
        }

        .lego-brick:nth-child(3) {
            background: #10b981;
            animation-delay: 1s;
        }

        .lego-container {
            display: flex;
            justify-content: center;
            margin-top: 2rem;
        }
        
        /* LEGO studs */
        .lego-brick::after {
            content: '';
            position: absolute;
            top: -8px;
            left: 6px;
            width: 12px;
            height: 12px;
            background-color: rgba(255, 255, 255, 0.7);
            border-radius: 50%;
            box-shadow: 18px 0 0 rgba(255, 255, 255, 0.7), 36px 0 0 rgba(255, 255, 255, 0.7);
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header class="header">
        <div class="container">
            <div class="header-content">
                <a href="<?php echo SITE_URL; ?>" class="logo">
                    <div class="logo-icon">M</div>
                    Menalego
                </a>
                
                <nav>
                    <ul class="main-nav">
                        <li><a href="<?php echo SITE_URL; ?>">Accueil</a></li>
                        <li><a href="<?php echo SITE_URL; ?>/produits.php">Produits</a></li>
                        <li><a href="<?php echo SITE_URL; ?>/categories.php">Catégories</a></li>
                    </ul>
                </nav>
                
                <div class="user-actions">
                    <a href="<?php echo SITE_URL; ?>/cart.php" class="cart-btn">
                        <i class="fas fa-shopping-cart"></i> Panier
                    </a>
                    <?php if (isLoggedIn()): ?>
                        <a href="<?php echo SITE_URL; ?>/profile.php" class="user-btn"><i class="fas fa-user"></i> Mon Compte</a>
                        <a href="<?php echo SITE_URL; ?>/auth/logout.php" class="user-btn"><i class="fas fa-sign-out-alt"></i> Déconnexion</a>
                        <?php if (isAdmin()): ?>
                            <a href="<?php echo SITE_URL; ?>/admin/" class="user-btn admin-btn"><i class="fas fa-cog"></i> Admin</a>
                        <?php endif; ?>
                    <?php else: ?>
                        <a href="<?php echo SITE_URL; ?>/auth/login.php" class="user-btn"><i class="fas fa-sign-in-alt"></i> Connexion</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </header>

    <div class="error-container">
        <div class="error-code">404</div>
        <h1 class="error-title">Page introuvable</h1>
        
        <div class="lego-container">
            <div class="lego-brick"></div>
            <div class="lego-brick"></div>
            <div class="lego-brick"></div>
        </div>
        
        <p class="error-message">
            Oups ! Nous ne trouvons pas la pièce LEGO® que vous recherchez. 
            La page <strong>"<?php echo $requested_url; ?>"</strong> n'existe pas ou a été déplacée.
        </p>
        
        <div class="search-container">
            <form action="<?php echo SITE_URL; ?>/search.php" method="GET" class="search-form">
                <input type="text" name="q" placeholder="Rechercher sur Menalego..." class="search-input">
                <button type="submit" class="search-button">
                    <i class="fas fa-search"></i>
                </button>
            </form>
        </div>
        
        <div class="error-actions">
            <a href="<?php echo SITE_URL; ?>" class="btn">
                <i class="fas fa-home"></i> Accueil
            </a>
            <a href="<?php echo SITE_URL; ?>/produits.php" class="btn">
                <i class="fas fa-cubes"></i> Nos produits
            </a>
            <a href="javascript:history.back()" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Page précédente
            </a>
        </div>
        
        <div class="suggestions">
            <h3 class="suggestion-title">Vous pourriez être intéressé par :</h3>
            <div class="suggestion-links">
                <a href="<?php echo SITE_URL; ?>/produits.php?category=1" class="suggestion-link">Monuments</a>
                <a href="<?php echo SITE_URL; ?>/produits.php?category=2" class="suggestion-link">Architecture</a>
                <a href="<?php echo SITE_URL; ?>/produits.php?featured=1" class="suggestion-link">Nouveautés</a>
                <a href="<?php echo SITE_URL; ?>/produits.php?bestseller=1" class="suggestion-link">Meilleures ventes</a>
                <a href="<?php echo SITE_URL; ?>/contact.php" class="suggestion-link">Nous contacter</a>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="footer-content">
                <div class="footer-section">
                    <h3>Menalego</h3>
                    <p>La plateforme e-commerce LEGO® inspirée du patrimoine marocain.</p>
                </div>
                
                <div class="footer-section">
                    <h3>Liens rapides</h3>
                    <ul>
                        <li><a href="<?php echo SITE_URL; ?>/produits.php">Produits</a></li>
                        <li><a href="<?php echo SITE_URL; ?>/categories.php">Catégories</a></li>
                        <li><a href="<?php echo SITE_URL; ?>/about.php">À propos</a></li>
                        <li><a href="<?php echo SITE_URL; ?>/contact.php">Contact</a></li>
                    </ul>
                </div>
                
                <div class="footer-section">
                    <h3>Contact</h3>
                    <ul>
                        <li>📧 contact@menalego.ma</li>
                        <li>📞 +212 5 24 XX XX XX</li>
                        <li>📍 Marrakech, Maroc</li>
                    </ul>
                </div>
            </div>
            
            <div class="footer-bottom">
                <p>&copy; <?php echo date('Y'); ?> Menalego. Tous droits réservés.</p>
            </div>
        </div>
    </footer>
</body>
</html>
