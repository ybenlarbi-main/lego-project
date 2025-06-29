<?php
require_once 'config/config.php';

// Get featured products with more details
$stmt = $pdo->prepare("
    SELECT p.*, c.nom as categorie_nom,
           COALESCE(AVG(r.note), 0) as avg_rating,
           COUNT(r.id) as review_count
    FROM produits p 
    LEFT JOIN categories c ON p.categorie_id = c.id 
    LEFT JOIN avis r ON p.id = r.produit_id AND r.statut = 'approuve'
    WHERE p.statut = 'actif' AND p.featured = 1 
    GROUP BY p.id
    ORDER BY p.date_creation DESC 
    LIMIT 8
");
$stmt->execute();
$featured_products = $stmt->fetchAll();

// Get all products for filtering
$stmt = $pdo->prepare("
    SELECT p.*, c.nom as categorie_nom,
           COALESCE(AVG(r.note), 0) as avg_rating,
           COUNT(r.id) as review_count
    FROM produits p 
    LEFT JOIN categories c ON p.categorie_id = c.id 
    LEFT JOIN avis r ON p.id = r.produit_id AND r.statut = 'approuve'
    WHERE p.statut = 'actif'
    GROUP BY p.id
    ORDER BY p.date_creation DESC 
    LIMIT 24
");
$stmt->execute();
$all_products = $stmt->fetchAll();

// Get categories for filtering
$stmt = $pdo->prepare("SELECT * FROM categories WHERE statut = 'actif' ORDER BY nom");
$stmt->execute();
$categories = $stmt->fetchAll();

// Get price range
$stmt = $pdo->prepare("SELECT MIN(prix) as min_price, MAX(prix) as max_price FROM produits WHERE statut = 'actif'");
$stmt->execute();
$price_range = $stmt->fetch();

$page_title = "Accueil";
?>

<!DOCTYPE html>
<html lang="<?php echo getCurrentLanguage(); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
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
                        <li><a href="produits.php">Produits</a></li>
                        <li><a href="categories.php">Catégories</a></li>
                        <li><a href="about.php">À propos</a></li>
                        <li><a href="contact.php">Contact</a></li>
                    </ul>
                </nav>
                
                <div class="user-actions">
                    <div class="language-switcher">
                        <button class="lang-btn <?php echo getCurrentLanguage() == 'fr' ? 'active' : ''; ?>" onclick="changeLanguage('fr')">FR</button>
                        <button class="lang-btn <?php echo getCurrentLanguage() == 'ar' ? 'active' : ''; ?>" onclick="changeLanguage('ar')">AR</button>
                    </div>
                    
                    <?php if (isLoggedIn()): ?>
                        <a href="cart.php" class="cart-btn">
                            🛒 Panier 
                            <span class="cart-count" id="cart-count">0</span>
                        </a>
                        <a href="profile.php" class="user-btn">Mon Compte</a>
                        <a href="auth/logout.php" class="user-btn">Déconnexion</a>
                    <?php else: ?>
                        <a href="auth/login.php" class="user-btn">Connexion</a>
                        <a href="auth/register.php" class="user-btn">Inscription</a>
                    <?php endif; ?>
                    
                    <?php if (isAdmin()): ?>
                        <a href="admin/" class="user-btn" style="background: var(--primary-red);">Admin</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </header>

    <!-- Hero Section -->
    <section class="hero-modern">
        <div class="hero-content">
            <div class="container">
                <div class="hero-text">
                    <h1 class="hero-title">
                        <span class="highlight">Découvrez</span> l'art du patrimoine marocain
                    </h1>
                    <p class="hero-subtitle">
                        Créez, apprenez et explorez la richesse culturelle du Maroc à travers nos sets LEGO® uniques
                    </p>
                    <div class="hero-actions">
                        <a href="#products" class="btn-hero-primary">Explorer nos créations</a>
                        <a href="#categories" class="btn-hero-secondary">Voir les catégories</a>
                    </div>
                </div>
                <div class="hero-stats">
                    <div class="stat-item">
                        <span class="stat-number"><?php echo count($all_products); ?>+</span>
                        <span class="stat-label">Produits uniques</span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-number"><?php echo count($categories); ?>+</span>
                        <span class="stat-label">Catégories</span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-number">100%</span>
                        <span class="stat-label">Made in Morocco</span>
                    </div>
                </div>
            </div>
        </div>
        <div class="hero-visual">
            <div class="floating-cards">
                <div class="floating-card card-1"></div>
                <div class="floating-card card-2"></div>
                <div class="floating-card card-3"></div>
            </div>
        </div>
    </section>

    <!-- Flash Messages -->
    <?php echo getFlashMessage(); ?>

    <!-- Featured Products Section -->
    <section class="featured-section" id="featured">
        <div class="container">
            <div class="section-header-modern">
                <div class="section-badge">Sélection Premium</div>
                <h2 class="section-title">Nos créations vedettes</h2>
                <p class="section-description">
                    Découvrez nos sets les plus populaires, inspirés des monuments et traditions du Maroc
                </p>
            </div>
            
            <div class="products-showcase">
                <?php foreach ($featured_products as $index => $product): ?>
                    <article class="product-card-enhanced" data-aos="fade-up" data-aos-delay="<?php echo $index * 100; ?>">
                        <div class="product-image-container">
                            <img src="<?php echo getImageUrl($product['images'] ? json_decode($product['images'])[0] : null); ?>" 
                                 alt="<?php echo htmlspecialchars($product['nom']); ?>" 
                                 class="product-image"
                                 loading="lazy">
                            <div class="product-overlay">
                                <button class="quick-view-btn" onclick="openQuickView(<?php echo $product['id']; ?>)">
                                    <i class="icon-eye"></i> Aperçu rapide
                                </button>
                            </div>
                            <?php if ($product['prix_promo']): ?>
                                <div class="product-badge sale">-<?php echo round((($product['prix'] - $product['prix_promo']) / $product['prix']) * 100); ?>%</div>
                            <?php endif; ?>
                            <?php if ($product['stock'] < 5 && $product['stock'] > 0): ?>
                                <div class="product-badge low-stock">Stock limité</div>
                            <?php elseif ($product['stock'] == 0): ?>
                                <div class="product-badge out-of-stock">Épuisé</div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="product-content">
                            <div class="product-category"><?php echo htmlspecialchars($product['categorie_nom']); ?></div>
                            <h3 class="product-title"><?php echo htmlspecialchars($product['nom']); ?></h3>
                            <p class="product-description"><?php echo htmlspecialchars(substr($product['description'], 0, 80)); ?>...</p>
                            
                            <div class="product-rating">
                                <div class="stars">
                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                        <span class="star <?php echo $i <= $product['avg_rating'] ? 'filled' : ''; ?>">★</span>
                                    <?php endfor; ?>
                                </div>
                                <span class="rating-count">(<?php echo $product['review_count']; ?>)</span>
                            </div>
                            
                            <div class="product-details">
                                <div class="product-pieces">
                                    <i class="icon-puzzle"></i>
                                    <span><?php echo $product['pieces_count']; ?> pièces</span>
                                </div>
                                <div class="product-age">
                                    <i class="icon-user"></i>
                                    <span><?php echo $product['age_min']; ?>-<?php echo $product['age_max']; ?> ans</span>
                                </div>
                            </div>
                            
                            <div class="product-price-section">
                                <?php if ($product['prix_promo']): ?>
                                    <span class="price-original"><?php echo formatPrice($product['prix']); ?></span>
                                    <span class="price-current"><?php echo formatPrice($product['prix_promo']); ?></span>
                                <?php else: ?>
                                    <span class="price-current"><?php echo formatPrice($product['prix']); ?></span>
                                <?php endif; ?>
                            </div>
                            
                            <div class="product-actions">
                                <a href="product.php?id=<?php echo $product['id']; ?>" class="btn-product-primary">Voir détails</a>
                                <?php if (isLoggedIn() && $product['stock'] > 0): ?>
                                    <button onclick="addToCart(<?php echo $product['id']; ?>)" class="btn-product-secondary">
                                        <i class="icon-cart"></i>
                                    </button>
                                    <button onclick="addToWishlist(<?php echo $product['id']; ?>)" class="btn-product-wishlist">
                                        <i class="icon-heart"></i>
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- Advanced Product Showcase with Filtering -->
    <section class="products-showcase-section" id="products">
        <div class="container">
            <div class="section-header-modern">
                <div class="section-badge">Collection Complète</div>
                <h2 class="section-title">Explorez notre gamme</h2>
                <p class="section-description">
                    Filtrez et découvrez tous nos produits selon vos préférences
                </p>
            </div>

            <!-- Advanced Filters -->
            <div class="filters-container">
                <div class="filters-header">
                    <h3 class="filters-title">Filtres</h3>
                    <button class="filters-toggle" onclick="toggleFilters()">
                        <i class="icon-filter"></i>
                        <span>Filtrer</span>
                    </button>
                </div>

                <div class="filters-panel" id="filtersPanel">
                    <div class="filter-group">
                        <label class="filter-label">Catégories</label>
                        <div class="filter-options categories-filter">
                            <label class="filter-option">
                                <input type="checkbox" value="" checked onchange="applyFilters()">
                                <span class="checkmark"></span>
                                <span class="option-text">Toutes les catégories</span>
                                <span class="option-count"><?php echo count($all_products); ?></span>
                            </label>
                            <?php foreach ($categories as $category): ?>
                                <label class="filter-option">
                                    <input type="checkbox" value="<?php echo $category['id']; ?>" onchange="applyFilters()">
                                    <span class="checkmark"></span>
                                    <span class="option-text"><?php echo htmlspecialchars($category['nom']); ?></span>
                                    <span class="option-count">
                                        <?php 
                                        $count = array_filter($all_products, function($p) use ($category) {
                                            return $p['categorie_id'] == $category['id'];
                                        });
                                        echo count($count);
                                        ?>
                                    </span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="filter-group">
                        <label class="filter-label">Prix</label>
                        <div class="price-range-container">
                            <div class="price-inputs">
                                <input type="number" id="minPrice" placeholder="Min" 
                                       value="<?php echo floor($price_range['min_price']); ?>" 
                                       onchange="applyFilters()">
                                <span class="price-separator">-</span>
                                <input type="number" id="maxPrice" placeholder="Max" 
                                       value="<?php echo ceil($price_range['max_price']); ?>" 
                                       onchange="applyFilters()">
                            </div>
                            <div class="price-range-slider">
                                <input type="range" id="minSlider" 
                                       min="<?php echo floor($price_range['min_price']); ?>" 
                                       max="<?php echo ceil($price_range['max_price']); ?>"
                                       value="<?php echo floor($price_range['min_price']); ?>"
                                       oninput="updatePriceRange()">
                                <input type="range" id="maxSlider" 
                                       min="<?php echo floor($price_range['min_price']); ?>" 
                                       max="<?php echo ceil($price_range['max_price']); ?>"
                                       value="<?php echo ceil($price_range['max_price']); ?>"
                                       oninput="updatePriceRange()">
                            </div>
                        </div>
                    </div>

                    <div class="filter-group">
                        <label class="filter-label">Note</label>
                        <div class="filter-options rating-filter">
                            <label class="filter-option">
                                <input type="radio" name="rating" value="" checked onchange="applyFilters()">
                                <span class="radio-mark"></span>
                                <span class="option-text">Toutes les notes</span>
                            </label>
                            <label class="filter-option">
                                <input type="radio" name="rating" value="4" onchange="applyFilters()">
                                <span class="radio-mark"></span>
                                <div class="rating-display">
                                    <span class="stars">★★★★☆</span>
                                    <span class="option-text">4+ étoiles</span>
                                </div>
                            </label>
                            <label class="filter-option">
                                <input type="radio" name="rating" value="3" onchange="applyFilters()">
                                <span class="radio-mark"></span>
                                <div class="rating-display">
                                    <span class="stars">★★★☆☆</span>
                                    <span class="option-text">3+ étoiles</span>
                                </div>
                            </label>
                        </div>
                    </div>

                    <div class="filter-group">
                        <label class="filter-label">Disponibilité</label>
                        <div class="filter-options availability-filter">
                            <label class="filter-option">
                                <input type="checkbox" value="in-stock" onchange="applyFilters()">
                                <span class="checkmark"></span>
                                <span class="option-text">En stock</span>
                            </label>
                            <label class="filter-option">
                                <input type="checkbox" value="on-sale" onchange="applyFilters()">
                                <span class="checkmark"></span>
                                <span class="option-text">En promotion</span>
                            </label>
                        </div>
                    </div>

                    <div class="filter-actions">
                        <button class="btn-filter-clear" onclick="clearFilters()">Effacer tout</button>
                        <button class="btn-filter-apply" onclick="applyFilters()">Appliquer</button>
                    </div>
                </div>

                <!-- Sort Options -->
                <div class="sort-container">
                    <label for="sortSelect" class="sort-label">Trier par:</label>
                    <select id="sortSelect" class="sort-select" onchange="sortProducts()">
                        <option value="newest">Plus récents</option>
                        <option value="price-low">Prix croissant</option>
                        <option value="price-high">Prix décroissant</option>
                        <option value="rating">Mieux notés</option>
                        <option value="popular">Plus populaires</option>
                    </select>
                </div>
            </div>

            <!-- Results Info -->
            <div class="results-info">
                <span class="results-count" id="resultsCount"><?php echo count($all_products); ?> produits trouvés</span>
                <div class="view-toggle">
                    <button class="view-btn active" data-view="grid" onclick="changeView('grid')">
                        <i class="icon-grid"></i>
                    </button>
                    <button class="view-btn" data-view="list" onclick="changeView('list')">
                        <i class="icon-list"></i>
                    </button>
                </div>
            </div>

            <!-- Loading State -->
            <div class="loading-state" id="loadingState" style="display: none;">
                <div class="loading-spinner"></div>
                <p>Chargement des produits...</p>
            </div>

            <!-- Products Grid -->
            <div class="products-grid-enhanced" id="productsGrid">
                <?php foreach ($all_products as $product): ?>
                    <article class="product-card-standard" 
                             data-category="<?php echo $product['categorie_id']; ?>"
                             data-price="<?php echo $product['prix_promo'] ?: $product['prix']; ?>"
                             data-rating="<?php echo round($product['avg_rating']); ?>"
                             data-stock="<?php echo $product['stock']; ?>"
                             data-sale="<?php echo $product['prix_promo'] ? 'true' : 'false'; ?>">
                        <div class="product-image-container">
                            <img src="<?php echo getImageUrl($product['images'] ? json_decode($product['images'])[0] : null); ?>" 
                                 alt="<?php echo htmlspecialchars($product['nom']); ?>" 
                                 class="product-image"
                                 loading="lazy">
                            <div class="product-overlay">
                                <button class="quick-view-btn" onclick="openQuickView(<?php echo $product['id']; ?>)">
                                    <i class="icon-eye"></i>
                                </button>
                                <?php if (isLoggedIn()): ?>
                                    <button class="wishlist-btn" onclick="addToWishlist(<?php echo $product['id']; ?>)">
                                        <i class="icon-heart"></i>
                                    </button>
                                <?php endif; ?>
                            </div>
                            <?php if ($product['prix_promo']): ?>
                                <div class="product-badge sale">-<?php echo round((($product['prix'] - $product['prix_promo']) / $product['prix']) * 100); ?>%</div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="product-content">
                            <div class="product-category"><?php echo htmlspecialchars($product['categorie_nom']); ?></div>
                            <h3 class="product-title"><?php echo htmlspecialchars($product['nom']); ?></h3>
                            
                            <div class="product-rating">
                                <div class="stars">
                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                        <span class="star <?php echo $i <= $product['avg_rating'] ? 'filled' : ''; ?>">★</span>
                                    <?php endfor; ?>
                                </div>
                                <span class="rating-count">(<?php echo $product['review_count']; ?>)</span>
                            </div>
                            
                            <div class="product-price-section">
                                <?php if ($product['prix_promo']): ?>
                                    <span class="price-original"><?php echo formatPrice($product['prix']); ?></span>
                                    <span class="price-current"><?php echo formatPrice($product['prix_promo']); ?></span>
                                <?php else: ?>
                                    <span class="price-current"><?php echo formatPrice($product['prix']); ?></span>
                                <?php endif; ?>
                            </div>
                            
                            <div class="product-actions">
                                <a href="product.php?id=<?php echo $product['id']; ?>" class="btn-product-view">Voir</a>
                                <?php if (isLoggedIn() && $product['stock'] > 0): ?>
                                    <button onclick="addToCart(<?php echo $product['id']; ?>)" class="btn-product-cart">
                                        <i class="icon-cart"></i>
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>

            <!-- Load More Button -->
            <div class="load-more-container">
                <button class="btn-load-more" onclick="loadMoreProducts()">
                    Charger plus de produits
                </button>
            </div>
        </div>
    </section>

    <!-- Categories Section -->
    <section class="categories-section" id="categories">
        <div class="container">
            <div class="section-header-modern">
                <div class="section-badge">Nos Collections</div>
                <h2 class="section-title">Explorez par thème</h2>
                <p class="section-description">
                    Découvrez notre collection organisée par patrimoine culturel marocain
                </p>
            </div>
            
            <div class="categories-grid">
                <?php foreach ($categories as $category): ?>
                    <div class="category-card" data-aos="zoom-in">
                        <div class="category-image">
                            <img src="<?php echo getImageUrl($category['image']); ?>" 
                                 alt="<?php echo htmlspecialchars($category['nom']); ?>"
                                 loading="lazy">
                            <div class="category-overlay">
                                <h3 class="category-title"><?php echo htmlspecialchars($category['nom']); ?></h3>
                                <p class="category-description"><?php echo htmlspecialchars($category['description']); ?></p>
                                <a href="produits.php?categorie=<?php echo $category['id']; ?>" class="btn-category">
                                    Explorer <i class="icon-arrow-right"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="footer-content">
                <div class="footer-section">
                    <h3>Menalego</h3>
                    <p>La plateforme e-commerce LEGO® inspirée du patrimoine marocain. Créativité, apprentissage et immersion culturelle.</p>
                </div>
                
                <div class="footer-section">
                    <h3>Liens rapides</h3>
                    <ul>
                        <li><a href="produits.php">Produits</a></li>
                        <li><a href="categories.php">Catégories</a></li>
                        <li><a href="about.php">À propos</a></li>
                        <li><a href="contact.php">Contact</a></li>
                    </ul>
                </div>
                
                <div class="footer-section">
                    <h3>Support client</h3>
                    <ul>
                        <li><a href="faq.php">FAQ</a></li>
                        <li><a href="shipping.php">Livraison</a></li>
                        <li><a href="returns.php">Retours</a></li>
                        <li><a href="support.php">Support</a></li>
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

    <!-- JavaScript -->
    <script>
        // Global variables
        let allProducts = <?php echo json_encode($all_products); ?>;
        let filteredProducts = [...allProducts];
        let currentPage = 1;
        const productsPerPage = 12;

        // Initialize AOS (Animate On Scroll)
        document.addEventListener('DOMContentLoaded', function() {
            updateCartCount();
            initializeFilters();
            
            // Smooth scrolling for anchor links
            document.querySelectorAll('a[href^="#"]').forEach(anchor => {
                anchor.addEventListener('click', function (e) {
                    e.preventDefault();
                    const target = document.querySelector(this.getAttribute('href'));
                    if (target) {
                        target.scrollIntoView({
                            behavior: 'smooth',
                            block: 'start'
                        });
                    }
                });
            });

            // Intersection Observer for lazy loading
            const imageObserver = new IntersectionObserver((entries, observer) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        const img = entry.target;
                        img.src = img.dataset.src || img.src;
                        img.classList.remove('lazy');
                        observer.unobserve(img);
                    }
                });
            });

            document.querySelectorAll('img[loading="lazy"]').forEach(img => {
                imageObserver.observe(img);
            });
        });

        // Language switching
        function changeLanguage(lang) {
            fetch('api/change-language.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ language: lang })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                }
            })
            .catch(error => {
                console.error('Error changing language:', error);
            });
        }

        // Cart functionality
        function addToCart(productId, quantity = 1) {
            showLoadingState();
            
            fetch('api/cart.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ 
                    action: 'add',
                    product_id: productId,
                    quantity: quantity
                })
            })
            .then(response => response.json())
            .then(data => {
                hideLoadingState();
                if (data.success) {
                    updateCartCount();
                    showNotification('Produit ajouté au panier !', 'success');
                    animateCartIcon();
                } else {
                    showNotification('Erreur lors de l\'ajout au panier', 'error');
                }
            })
            .catch(error => {
                hideLoadingState();
                console.error('Error adding to cart:', error);
                showNotification('Erreur réseau', 'error');
            });
        }

        function updateCartCount() {
            fetch('api/cart.php?action=count')
            .then(response => response.json())
            .then(data => {
                const cartCountElement = document.getElementById('cart-count');
                if (cartCountElement) {
                    cartCountElement.textContent = data.count || 0;
                    cartCountElement.style.display = data.count > 0 ? 'inline' : 'none';
                }
            })
            .catch(error => {
                console.error('Error updating cart count:', error);
            });
        }

        // Wishlist functionality
        function addToWishlist(productId) {
            fetch('api/wishlist.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ 
                    action: 'add',
                    product_id: productId
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showNotification('Produit ajouté à la liste de souhaits !', 'success');
                    // Update wishlist icon
                    const wishlistBtn = event.target.closest('.wishlist-btn');
                    if (wishlistBtn) {
                        wishlistBtn.classList.add('active');
                    }
                } else {
                    showNotification('Erreur lors de l\'ajout à la liste de souhaits', 'error');
                }
            })
            .catch(error => {
                console.error('Error adding to wishlist:', error);
            });
        }

        // Filtering functionality
        function initializeFilters() {
            updatePriceRange();
        }

        function toggleFilters() {
            const panel = document.getElementById('filtersPanel');
            panel.classList.toggle('open');
        }

        function applyFilters() {
            showLoadingState();
            
            // Get selected categories
            const selectedCategories = Array.from(document.querySelectorAll('.categories-filter input:checked'))
                .map(input => input.value)
                .filter(value => value !== '');

            // Get price range
            const minPrice = parseFloat(document.getElementById('minPrice').value) || 0;
            const maxPrice = parseFloat(document.getElementById('maxPrice').value) || Infinity;

            // Get selected rating
            const selectedRating = document.querySelector('input[name="rating"]:checked').value;

            // Get availability filters
            const inStockOnly = document.querySelector('input[value="in-stock"]').checked;
            const onSaleOnly = document.querySelector('input[value="on-sale"]').checked;

            // Filter products
            filteredProducts = allProducts.filter(product => {
                // Category filter
                if (selectedCategories.length > 0 && !selectedCategories.includes(product.categorie_id.toString())) {
                    return false;
                }

                // Price filter
                const productPrice = parseFloat(product.prix_promo || product.prix);
                if (productPrice < minPrice || productPrice > maxPrice) {
                    return false;
                }

                // Rating filter
                if (selectedRating && parseFloat(product.avg_rating) < parseFloat(selectedRating)) {
                    return false;
                }

                // Stock filter
                if (inStockOnly && product.stock <= 0) {
                    return false;
                }

                // Sale filter
                if (onSaleOnly && !product.prix_promo) {
                    return false;
                }

                return true;
            });

            setTimeout(() => {
                renderProducts();
                updateResultsCount();
                hideLoadingState();
            }, 300);
        }

        function clearFilters() {
            // Reset all filter inputs
            document.querySelectorAll('.filter-options input').forEach(input => {
                if (input.type === 'checkbox') {
                    input.checked = input.value === '';
                } else if (input.type === 'radio') {
                    input.checked = input.value === '';
                }
            });

            // Reset price inputs
            document.getElementById('minPrice').value = <?php echo floor($price_range['min_price']); ?>;
            document.getElementById('maxPrice').value = <?php echo ceil($price_range['max_price']); ?>;

            // Apply filters
            applyFilters();
        }

        function sortProducts() {
            const sortValue = document.getElementById('sortSelect').value;
            
            filteredProducts.sort((a, b) => {
                switch (sortValue) {
                    case 'price-low':
                        return (parseFloat(a.prix_promo || a.prix)) - (parseFloat(b.prix_promo || b.prix));
                    case 'price-high':
                        return (parseFloat(b.prix_promo || b.prix)) - (parseFloat(a.prix_promo || a.prix));
                    case 'rating':
                        return parseFloat(b.avg_rating) - parseFloat(a.avg_rating);
                    case 'popular':
                        return parseInt(b.review_count) - parseInt(a.review_count);
                    case 'newest':
                    default:
                        return new Date(b.date_creation) - new Date(a.date_creation);
                }
            });

            renderProducts();
        }

        function updatePriceRange() {
            const minSlider = document.getElementById('minSlider');
            const maxSlider = document.getElementById('maxSlider');
            const minPrice = document.getElementById('minPrice');
            const maxPrice = document.getElementById('maxPrice');

            if (minSlider && maxSlider) {
                minPrice.value = minSlider.value;
                maxPrice.value = maxSlider.value;

                // Ensure min is not greater than max
                if (parseInt(minSlider.value) > parseInt(maxSlider.value)) {
                    minSlider.value = maxSlider.value;
                    minPrice.value = maxSlider.value;
                }

                applyFilters();
            }
        }

        function renderProducts() {
            const grid = document.getElementById('productsGrid');
            const productsToShow = filteredProducts.slice(0, currentPage * productsPerPage);
            
            grid.innerHTML = productsToShow.map(product => `
                <article class="product-card-standard" 
                         data-category="${product.categorie_id}"
                         data-price="${product.prix_promo || product.prix}"
                         data-rating="${Math.round(product.avg_rating)}"
                         data-stock="${product.stock}"
                         data-sale="${product.prix_promo ? 'true' : 'false'}">
                    <div class="product-image-container">
                        <img src="${getImageUrl(product.images)}" 
                             alt="${escapeHtml(product.nom)}" 
                             class="product-image"
                             loading="lazy">
                        <div class="product-overlay">
                            <button class="quick-view-btn" onclick="openQuickView(${product.id})">
                                <i class="icon-eye"></i>
                            </button>
                            ${isLoggedIn() ? `<button class="wishlist-btn" onclick="addToWishlist(${product.id})">
                                <i class="icon-heart"></i>
                            </button>` : ''}
                        </div>
                        ${product.prix_promo ? `<div class="product-badge sale">-${Math.round(((product.prix - product.prix_promo) / product.prix) * 100)}%</div>` : ''}
                    </div>
                    
                    <div class="product-content">
                        <div class="product-category">${escapeHtml(product.categorie_nom)}</div>
                        <h3 class="product-title">${escapeHtml(product.nom)}</h3>
                        
                        <div class="product-rating">
                            <div class="stars">
                                ${Array.from({length: 5}, (_, i) => 
                                    `<span class="star ${i < product.avg_rating ? 'filled' : ''}">★</span>`
                                ).join('')}
                            </div>
                            <span class="rating-count">(${product.review_count})</span>
                        </div>
                        
                        <div class="product-price-section">
                            ${product.prix_promo ? 
                                `<span class="price-original">${formatPrice(product.prix)}</span>
                                 <span class="price-current">${formatPrice(product.prix_promo)}</span>` :
                                `<span class="price-current">${formatPrice(product.prix)}</span>`
                            }
                        </div>
                        
                        <div class="product-actions">
                            <a href="product.php?id=${product.id}" class="btn-product-view">Voir</a>
                            ${isLoggedIn() && product.stock > 0 ? 
                                `<button onclick="addToCart(${product.id})" class="btn-product-cart">
                                    <i class="icon-cart"></i>
                                </button>` : ''
                            }
                        </div>
                    </div>
                </article>
            `).join('');

            // Update load more button visibility
            const loadMoreBtn = document.querySelector('.btn-load-more');
            if (loadMoreBtn) {
                loadMoreBtn.style.display = productsToShow.length < filteredProducts.length ? 'block' : 'none';
            }
        }

        function updateResultsCount() {
            const countElement = document.getElementById('resultsCount');
            if (countElement) {
                countElement.textContent = `${filteredProducts.length} produit${filteredProducts.length !== 1 ? 's' : ''} trouvé${filteredProducts.length !== 1 ? 's' : ''}`;
            }
        }

        function changeView(view) {
            const grid = document.getElementById('productsGrid');
            const viewBtns = document.querySelectorAll('.view-btn');
            
            viewBtns.forEach(btn => {
                btn.classList.toggle('active', btn.dataset.view === view);
            });
            
            grid.className = view === 'list' ? 'products-list-enhanced' : 'products-grid-enhanced';
        }

        function loadMoreProducts() {
            currentPage++;
            renderProducts();
        }

        // Quick view functionality
        function openQuickView(productId) {
            // Implementation for quick view modal
            console.log('Opening quick view for product:', productId);
            // You can implement a modal here
        }

        // Utility functions
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        function formatPrice(price) {
            return new Intl.NumberFormat('fr-MA', {
                style: 'currency',
                currency: 'MAD',
                minimumFractionDigits: 2
            }).format(price).replace('MAD', 'DH');
        }

        function getImageUrl(images) {
            if (!images) return '<?php echo SITE_URL; ?>/assets/images/placeholder.svg';
            try {
                const imageArray = JSON.parse(images);
                return imageArray.length > 0 ? `<?php echo UPLOAD_URL; ?>${imageArray[0]}` : '<?php echo SITE_URL; ?>/assets/images/placeholder.svg';
            } catch {
                return '<?php echo SITE_URL; ?>/assets/images/placeholder.svg';
            }
        }

        function isLoggedIn() {
            return <?php echo isLoggedIn() ? 'true' : 'false'; ?>;
        }

        // UI feedback functions
        function showLoadingState() {
            const loadingElement = document.getElementById('loadingState');
            if (loadingElement) {
                loadingElement.style.display = 'block';
            }
        }

        function hideLoadingState() {
            const loadingElement = document.getElementById('loadingState');
            if (loadingElement) {
                loadingElement.style.display = 'none';
            }
        }

        function showNotification(message, type = 'info') {
            // Create notification element
            const notification = document.createElement('div');
            notification.className = `notification notification-${type}`;
            notification.textContent = message;
            
            // Add to page
            document.body.appendChild(notification);
            
            // Show with animation
            setTimeout(() => notification.classList.add('show'), 100);
            
            // Remove after delay
            setTimeout(() => {
                notification.classList.remove('show');
                setTimeout(() => document.body.removeChild(notification), 300);
            }, 3000);
        }

        function animateCartIcon() {
            const cartBtn = document.querySelector('.cart-btn');
            if (cartBtn) {
                cartBtn.classList.add('bounce');
                setTimeout(() => cartBtn.classList.remove('bounce'), 600);
            }
        }
    </script>
</body>
</html>
