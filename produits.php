<?php
require_once 'config/config.php';

// Get filters
$search = isset($_GET['search']) ? sanitizeInput($_GET['search']) : '';
$category_id = isset($_GET['categorie']) ? (int)$_GET['categorie'] : 0;
$min_price = isset($_GET['min_price']) ? (float)$_GET['min_price'] : 0;
$max_price = isset($_GET['max_price']) ? (float)$_GET['max_price'] : 0;
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'date_desc';

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = PRODUCTS_PER_PAGE;
$offset = ($page - 1) * $limit;

// Build query
$where_conditions = ["p.statut = 'actif'"];
$params = [];

if ($search) {
    $where_conditions[] = "(p.nom LIKE ? OR p.description LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($category_id) {
    $where_conditions[] = "p.categorie_id = ?";
    $params[] = $category_id;
}

if ($min_price) {
    $where_conditions[] = "p.prix >= ?";
    $params[] = $min_price;
}

if ($max_price) {
    $where_conditions[] = "p.prix <= ?";
    $params[] = $max_price;
}

$where_clause = implode(' AND ', $where_conditions);

// Sort options
$sort_options = [
    'date_desc' => 'p.date_creation DESC',
    'date_asc' => 'p.date_creation ASC',
    'price_asc' => 'p.prix ASC',
    'price_desc' => 'p.prix DESC',
    'name_asc' => 'p.nom ASC',
    'name_desc' => 'p.nom DESC'
];

$order_by = $sort_options[$sort] ?? $sort_options['date_desc'];

// Get total count
$count_sql = "SELECT COUNT(*) FROM produits p WHERE $where_clause";
$count_stmt = $pdo->prepare($count_sql);
$count_stmt->execute($params);
$total_products = $count_stmt->fetchColumn();
$total_pages = ceil($total_products / $limit);

// Get products
$sql = "
    SELECT p.*, c.nom as categorie_nom 
    FROM produits p 
    LEFT JOIN categories c ON p.categorie_id = c.id 
    WHERE $where_clause 
    ORDER BY $order_by 
    LIMIT $limit OFFSET $offset
";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll();

// Get categories for filter
$categories_stmt = $pdo->prepare("SELECT * FROM categories WHERE statut = 'actif' ORDER BY nom");
$categories_stmt->execute();
$categories = $categories_stmt->fetchAll();

$page_title = 'Produits';
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
                        <li><a href="produits.php" class="active">Produits</a></li>
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

    <!-- Page Header -->
    <section class="hero" style="padding: 2rem 0;">
        <div class="container">
            <h1>Nos Produits</h1>
            <p>Découvrez notre collection complète de sets LEGO® inspirés du patrimoine marocain</p>
        </div>
    </section>

    <!-- Flash Messages -->
    <?php echo getFlashMessage(); ?>

    <!-- Filters Section -->
    <section class="filters">
        <div class="container">
            <form method="GET" action="produits.php">
                <div class="filters-grid">
                    <div class="filter-group">
                        <label for="search">Recherche</label>
                        <input type="text" id="search" name="search" placeholder="Nom du produit..." 
                               value="<?php echo htmlspecialchars($search); ?>" class="form-control">
                    </div>
                    
                    <div class="filter-group">
                        <label for="categorie">Catégorie</label>
                        <select id="categorie" name="categorie" class="form-control">
                            <option value="">Toutes les catégories</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?php echo $category['id']; ?>" 
                                        <?php echo $category_id == $category['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($category['nom']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <label for="min_price">Prix minimum (MAD)</label>
                        <input type="number" id="min_price" name="min_price" min="0" step="0.01" 
                               value="<?php echo $min_price ?: ''; ?>" class="form-control">
                    </div>
                    
                    <div class="filter-group">
                        <label for="max_price">Prix maximum (MAD)</label>
                        <input type="number" id="max_price" name="max_price" min="0" step="0.01" 
                               value="<?php echo $max_price ?: ''; ?>" class="form-control">
                    </div>
                    
                    <div class="filter-group">
                        <label for="sort">Trier par</label>
                        <select id="sort" name="sort" class="form-control">
                            <option value="date_desc" <?php echo $sort == 'date_desc' ? 'selected' : ''; ?>>Plus récents</option>
                            <option value="date_asc" <?php echo $sort == 'date_asc' ? 'selected' : ''; ?>>Plus anciens</option>
                            <option value="price_asc" <?php echo $sort == 'price_asc' ? 'selected' : ''; ?>>Prix croissant</option>
                            <option value="price_desc" <?php echo $sort == 'price_desc' ? 'selected' : ''; ?>>Prix décroissant</option>
                            <option value="name_asc" <?php echo $sort == 'name_asc' ? 'selected' : ''; ?>>Nom A-Z</option>
                            <option value="name_desc" <?php echo $sort == 'name_desc' ? 'selected' : ''; ?>>Nom Z-A</option>
                        </select>
                    </div>
                    
                    <div class="filter-group" style="align-self: end;">
                        <button type="submit" class="btn-primary">Filtrer</button>
                    </div>
                </div>
            </form>
        </div>
    </section>

    <!-- Products Section -->
    <section class="products-section">
        <div class="container">
            <div class="section-header">
                <h2><?php echo $total_products; ?> produit(s) trouvé(s)</h2>
            </div>
            
            <?php if (empty($products)): ?>
                <div class="text-center" style="padding: 4rem 0;">
                    <h3>Aucun produit trouvé</h3>
                    <p>Essayez de modifier vos critères de recherche</p>
                    <a href="produits.php" class="btn-primary">Voir tous les produits</a>
                </div>
            <?php else: ?>
                <div class="products-grid">
                    <?php foreach ($products as $product): ?>
                        <div class="product-card">
                            <img src="<?php echo getImageUrl($product['images'] ? json_decode($product['images'])[0] : null); ?>" 
                                 alt="<?php echo htmlspecialchars($product['nom']); ?>" 
                                 class="product-image">
                            
                            <div class="product-info">
                                <h3 class="product-title"><?php echo htmlspecialchars($product['nom']); ?></h3>
                                <p class="product-description"><?php echo htmlspecialchars(substr($product['description'], 0, 100)); ?>...</p>
                                
                                <div class="product-meta">
                                    <span class="product-price"><?php echo formatPrice($product['prix']); ?></span>
                                    <span class="product-pieces"><?php echo $product['pieces_count']; ?> pièces</span>
                                </div>
                                
                                <div style="margin-bottom: 1rem;">
                                    <small style="color: #666;">Catégorie: <?php echo htmlspecialchars($product['categorie_nom']); ?></small><br>
                                    <small style="color: #666;">Âge: <?php echo $product['age_min']; ?>-<?php echo $product['age_max']; ?> ans</small>
                                </div>
                                
                                <div class="product-actions">
                                    <a href="product.php?id=<?php echo $product['id']; ?>" class="btn-primary">Voir détails</a>
                                    <?php if (isLoggedIn()): ?>
                                        <button onclick="addToCart(<?php echo $product['id']; ?>)" class="btn-secondary">Ajouter au panier</button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                    <div class="pagination">
                        <?php
                        $current_url = 'produits.php?' . http_build_query(array_filter([
                            'search' => $search,
                            'categorie' => $category_id ?: null,
                            'min_price' => $min_price ?: null,
                            'max_price' => $max_price ?: null,
                            'sort' => $sort
                        ]));
                        ?>
                        
                        <?php if ($page > 1): ?>
                            <a href="<?php echo $current_url; ?>&page=<?php echo $page - 1; ?>">← Précédent</a>
                        <?php endif; ?>
                        
                        <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                            <?php if ($i == $page): ?>
                                <span class="current"><?php echo $i; ?></span>
                            <?php else: ?>
                                <a href="<?php echo $current_url; ?>&page=<?php echo $i; ?>"><?php echo $i; ?></a>
                            <?php endif; ?>
                        <?php endfor; ?>
                        
                        <?php if ($page < $total_pages): ?>
                            <a href="<?php echo $current_url; ?>&page=<?php echo $page + 1; ?>">Suivant →</a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </section>

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
                        <li><a href="produits.php">Produits</a></li>
                        <li><a href="categories.php">Catégories</a></li>
                        <li><a href="about.php">À propos</a></li>
                        <li><a href="contact.php">Contact</a></li>
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
            });
        }

        function addToCart(productId) {
            fetch('api/cart.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ 
                    action: 'add',
                    product_id: productId,
                    quantity: 1
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    updateCartCount();
                    alert('Produit ajouté au panier !');
                } else {
                    alert('Erreur lors de l\'ajout au panier');
                }
            });
        }

        function updateCartCount() {
            fetch('api/cart.php?action=count')
            .then(response => response.json())
            .then(data => {
                document.getElementById('cart-count').textContent = data.count || 0;
            });
        }

        // Load cart count on page load
        document.addEventListener('DOMContentLoaded', updateCartCount);
    </script>
</body>
</html>
