<?php
require_once 'config/config.php';

// Get product ID
$product_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$product_id) {
    header('Location: produits.php');
    exit;
}

// Get product details
$stmt = $pdo->prepare("
    SELECT p.*, c.nom as categorie_nom 
    FROM produits p 
    LEFT JOIN categories c ON p.categorie_id = c.id 
    WHERE p.id = ? AND p.statut = 'actif'
");
$stmt->execute([$product_id]);
$product = $stmt->fetch();

if (!$product) {
    header('Location: produits.php');
    exit;
}

// Get product reviews
$stmt = $pdo->prepare("
    SELECT a.*, u.prenom, u.nom 
    FROM avis a 
    LEFT JOIN utilisateurs u ON a.client_id = u.id 
    WHERE a.produit_id = ? AND a.statut = 'approuve' 
    ORDER BY a.date_creation DESC
");
$stmt->execute([$product_id]);
$reviews = $stmt->fetchAll();

// Calculate average rating
$average_rating = 0;
if (!empty($reviews)) {
    $total_rating = array_sum(array_column($reviews, 'note'));
    $average_rating = $total_rating / count($reviews);
}

// Get related products
$stmt = $pdo->prepare("
    SELECT p.*, c.nom as categorie_nom 
    FROM produits p 
    LEFT JOIN categories c ON p.categorie_id = c.id 
    WHERE p.categorie_id = ? AND p.id != ? AND p.statut = 'actif' 
    ORDER BY RAND() 
    LIMIT 4
");
$stmt->execute([$product['categorie_id'], $product_id]);
$related_products = $stmt->fetchAll();

$page_title = $product['nom'];
?>

<!DOCTYPE html>
<html lang="<?php echo getCurrentLanguage(); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - <?php echo SITE_NAME; ?></title>
    <meta name="description" content="<?php echo htmlspecialchars(substr($product['description'], 0, 160)); ?>">
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

    <!-- Breadcrumb -->
    <section style="background: white; padding: 1rem 0; border-bottom: 1px solid #eee;">
        <div class="container">
            <nav style="font-size: 0.9rem; color: #666;">
                <a href="<?php echo SITE_URL; ?>" style="color: var(--primary-blue);">Accueil</a> &gt; 
                <a href="produits.php" style="color: var(--primary-blue);">Produits</a> &gt; 
                <a href="produits.php?categorie=<?php echo $product['categorie_id']; ?>" style="color: var(--primary-blue);"><?php echo htmlspecialchars($product['categorie_nom']); ?></a> &gt; 
                <span><?php echo htmlspecialchars($product['nom']); ?></span>
            </nav>
        </div>
    </section>

    <!-- Product Details -->
    <section class="products-section">
        <div class="container">
            <!-- Flash Messages -->
            <?php echo getFlashMessage(); ?>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 3rem; margin-bottom: 3rem;">
                <!-- Product Image -->
                <div>
                    <img src="<?php echo getImageUrl($product['images'] ? json_decode($product['images'])[0] : null); ?>" 
                         alt="<?php echo htmlspecialchars($product['nom']); ?>" 
                         style="width: 100%; height: 400px; object-fit: cover; border-radius: 8px; box-shadow: 0 4px 15px rgba(0,0,0,0.1);">
                </div>
                
                <!-- Product Info -->
                <div>
                    <h1 style="margin-bottom: 1rem; color: var(--dark-blue);"><?php echo htmlspecialchars($product['nom']); ?></h1>
                    
                    <?php if ($product['nom_ar']): ?>
                        <h2 style="font-size: 1.3rem; color: var(--medium-gray); margin-bottom: 1rem;"><?php echo htmlspecialchars($product['nom_ar']); ?></h2>
                    <?php endif; ?>
                    
                    <!-- Rating -->
                    <div style="margin-bottom: 1rem;">
                        <?php if (!empty($reviews)): ?>
                            <div style="display: flex; align-items: center; gap: 0.5rem;">
                                <div style="color: var(--primary-yellow); font-size: 1.2rem;">
                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                        <?php echo $i <= round($average_rating) ? '★' : '☆'; ?>
                                    <?php endfor; ?>
                                </div>
                                <span style="color: var(--medium-gray);">
                                    (<?php echo number_format($average_rating, 1); ?>/5 - <?php echo count($reviews); ?> avis)
                                </span>
                            </div>
                        <?php else: ?>
                            <div style="color: var(--medium-gray);">Aucun avis pour le moment</div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Price -->
                    <div style="margin-bottom: 2rem;">
                        <?php if ($product['prix_promo']): ?>
                            <span style="font-size: 1.8rem; font-weight: 700; color: var(--primary-red);"><?php echo formatPrice($product['prix_promo']); ?></span>
                            <span style="font-size: 1.2rem; text-decoration: line-through; color: var(--medium-gray); margin-left: 1rem;"><?php echo formatPrice($product['prix']); ?></span>
                        <?php else: ?>
                            <span style="font-size: 1.8rem; font-weight: 700; color: var(--primary-red);"><?php echo formatPrice($product['prix']); ?></span>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Product Details -->
                    <div style="background: var(--light-gray); padding: 1.5rem; border-radius: 8px; margin-bottom: 2rem;">
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                            <div>
                                <strong>Référence:</strong> <?php echo htmlspecialchars($product['reference']); ?>
                            </div>
                            <div>
                                <strong>Nombre de pièces:</strong> <?php echo $product['pieces_count']; ?>
                            </div>
                            <div>
                                <strong>Âge recommandé:</strong> <?php echo $product['age_min']; ?>-<?php echo $product['age_max']; ?> ans
                            </div>
                            <div>
                                <strong>Stock:</strong> 
                                <span style="color: <?php echo $product['stock'] > 10 ? 'green' : ($product['stock'] > 0 ? 'orange' : 'red'); ?>;">
                                    <?php echo $product['stock']; ?> disponible(s)
                                </span>
                            </div>
                            <div>
                                <strong>Catégorie:</strong> <?php echo htmlspecialchars($product['categorie_nom']); ?>
                            </div>
                            <div>
                                <strong>Marque:</strong> <?php echo htmlspecialchars($product['marque']); ?>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Add to Cart -->
                    <?php if ($product['stock'] > 0): ?>
                        <?php if (isLoggedIn()): ?>
                            <div style="display: flex; gap: 1rem; margin-bottom: 2rem;">
                                <div style="display: flex; align-items: center; border: 1px solid #ddd; border-radius: 4px;">
                                    <button onclick="changeQuantity(-1)" style="background: none; border: none; padding: 0.5rem; cursor: pointer;">-</button>
                                    <input type="number" id="quantity" value="1" min="1" max="<?php echo $product['stock']; ?>" 
                                           style="border: none; width: 60px; text-align: center;">
                                    <button onclick="changeQuantity(1)" style="background: none; border: none; padding: 0.5rem; cursor: pointer;">+</button>
                                </div>
                                <button onclick="addToCartWithQuantity()" class="btn-primary" style="flex: 1;">
                                    Ajouter au panier
                                </button>
                            </div>
                        <?php else: ?>
                            <div style="background: var(--light-gray); padding: 1rem; border-radius: 8px; text-align: center; margin-bottom: 2rem;">
                                <p>Veuillez vous connecter pour ajouter ce produit au panier</p>
                                <a href="auth/login.php" class="btn-primary">Se connecter</a>
                            </div>
                        <?php endif; ?>
                    <?php else: ?>
                        <div style="background: #ffe6e6; padding: 1rem; border-radius: 8px; text-align: center; margin-bottom: 2rem; color: var(--primary-red);">
                            <strong>Produit en rupture de stock</strong>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Product Description -->
            <div style="background: white; padding: 2rem; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); margin-bottom: 3rem;">
                <h3>Description</h3>
                <p style="line-height: 1.6; color: var(--dark-gray);"><?php echo nl2br(htmlspecialchars($product['description'])); ?></p>
                
                <?php if ($product['description_ar']): ?>
                    <h3 style="margin-top: 2rem;">الوصف</h3>
                    <p style="line-height: 1.6; color: var(--dark-gray); direction: rtl;"><?php echo nl2br(htmlspecialchars($product['description_ar'])); ?></p>
                <?php endif; ?>
            </div>
            
            <!-- Reviews Section -->
            <div style="background: white; padding: 2rem; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); margin-bottom: 3rem;">
                <h3>Avis clients (<?php echo count($reviews); ?>)</h3>
                
                <?php if (empty($reviews)): ?>
                    <p>Aucun avis pour ce produit. Soyez le premier à laisser un avis !</p>
                <?php else: ?>
                    <?php foreach ($reviews as $review): ?>
                        <div style="border-bottom: 1px solid #eee; padding: 1rem 0;">
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem;">
                                <strong><?php echo htmlspecialchars($review['prenom'] . ' ' . substr($review['nom'], 0, 1) . '.'); ?></strong>
                                <div style="color: var(--primary-yellow);">
                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                        <?php echo $i <= $review['note'] ? '★' : '☆'; ?>
                                    <?php endfor; ?>
                                </div>
                            </div>
                            
                            <?php if ($review['titre']): ?>
                                <h4 style="margin-bottom: 0.5rem;"><?php echo htmlspecialchars($review['titre']); ?></h4>
                            <?php endif; ?>
                            
                            <p style="color: var(--dark-gray); line-height: 1.5;"><?php echo nl2br(htmlspecialchars($review['commentaire'])); ?></p>
                            
                            <small style="color: var(--medium-gray);">
                                <?php echo date('d/m/Y', strtotime($review['date_creation'])); ?>
                            </small>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            
            <!-- Related Products -->
            <?php if (!empty($related_products)): ?>
                <div>
                    <h3 style="margin-bottom: 2rem;">Produits similaires</h3>
                    <div class="products-grid">
                        <?php foreach ($related_products as $related): ?>
                            <div class="product-card">
                                <img src="<?php echo getImageUrl($related['images'] ? json_decode($related['images'])[0] : null); ?>" 
                                     alt="<?php echo htmlspecialchars($related['nom']); ?>" 
                                     class="product-image">
                                
                                <div class="product-info">
                                    <h3 class="product-title"><?php echo htmlspecialchars($related['nom']); ?></h3>
                                    <p class="product-description"><?php echo htmlspecialchars(substr($related['description'], 0, 100)); ?>...</p>
                                    
                                    <div class="product-meta">
                                        <span class="product-price"><?php echo formatPrice($related['prix']); ?></span>
                                        <span class="product-pieces"><?php echo $related['pieces_count']; ?> pièces</span>
                                    </div>
                                    
                                    <div class="product-actions">
                                        <a href="product.php?id=<?php echo $related['id']; ?>" class="btn-primary">Voir détails</a>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
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

        function changeQuantity(change) {
            const quantityInput = document.getElementById('quantity');
            let currentQuantity = parseInt(quantityInput.value);
            let newQuantity = currentQuantity + change;
            
            if (newQuantity < 1) newQuantity = 1;
            if (newQuantity > <?php echo $product['stock']; ?>) newQuantity = <?php echo $product['stock']; ?>;
            
            quantityInput.value = newQuantity;
        }

        function addToCartWithQuantity() {
            const quantity = parseInt(document.getElementById('quantity').value);
            
            fetch('api/cart.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ 
                    action: 'add',
                    product_id: <?php echo $product_id; ?>,
                    quantity: quantity
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    updateCartCount();
                    
                    // Show a nice notification instead of alert
                    const notification = document.createElement('div');
                    notification.className = 'notification notification-success show';
                    notification.innerHTML = `
                        <div style="display: flex; align-items: center;">
                            <span style="margin-right: 10px; font-size: 1.2em;">✓</span>
                            <div>
                                <div><strong>Produit ajouté au panier!</strong></div>
                                <div style="margin-top: 5px;">
                                    <a href="cart.php" class="btn-link">Voir mon panier</a>
                                    <span style="margin: 0 5px;">ou</span>
                                    <a href="checkout.php" class="btn-link">Commander</a>
                                </div>
                            </div>
                        </div>
                    `;
                    document.body.appendChild(notification);
                    
                    // Remove notification after 5 seconds
                    setTimeout(() => {
                        notification.classList.remove('show');
                        setTimeout(() => notification.remove(), 500);
                    }, 5000);
                } else {
                    alert(data.message || 'Erreur lors de l\'ajout au panier');
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
