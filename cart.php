<?php
require_once 'config/config.php';

// Require login for cart
requireLogin();

$user_id = $_SESSION['user_id'];

// Get cart items
$stmt = $pdo->prepare("
    SELECT p.*, pr.nom, pr.prix, pr.stock, pa.quantite, 
           (pr.prix * pa.quantite) as total,
           c.nom as categorie_nom
    FROM panier pa 
    JOIN produits pr ON pa.produit_id = pr.id 
    LEFT JOIN categories c ON pr.categorie_id = c.id
    WHERE pa.client_id = ?
    ORDER BY pa.date_ajout DESC
");
$stmt->execute([$user_id]);
$cart_items = $stmt->fetchAll();

$cart_total = 0;
foreach ($cart_items as $item) {
    $cart_total += $item['total'];
}

$page_title = 'Mon Panier';
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
                    <a href="cart.php" class="cart-btn">
                        🛒 Panier 
                        <span class="cart-count" id="cart-count"><?php echo count($cart_items); ?></span>
                    </a>
                    <a href="profile.php" class="user-btn">Mon Compte</a>
                    <a href="auth/logout.php" class="user-btn">Déconnexion</a>
                    
                    <?php if (isAdmin()): ?>
                        <a href="admin/" class="user-btn" style="background: var(--primary-red);">Admin</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </header>

    <!-- Cart Content -->
    <section class="products-section">
        <div class="container">
            <div class="section-header">
                <h1>Mon Panier</h1>
                <p><?php echo count($cart_items); ?> article(s) dans votre panier</p>
            </div>
            
            <!-- Flash Messages -->
            <?php echo getFlashMessage(); ?>
            
            <?php if (empty($cart_items)): ?>
                <div class="text-center" style="padding: 4rem 0;">
                    <h3>Votre panier est vide</h3>
                    <p>Découvrez nos produits et ajoutez-les à votre panier</p>
                    <a href="produits.php" class="btn-primary">Voir nos produits</a>
                </div>
            <?php else: ?>
                <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 2rem;">
                    <!-- Cart Items -->
                    <div>
                        <?php foreach ($cart_items as $item): ?>
                            <div style="background: white; border-radius: 8px; padding: 1.5rem; margin-bottom: 1rem; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
                                <div style="display: grid; grid-template-columns: 100px 1fr auto; gap: 1rem; align-items: center;">
                                    <!-- Product Image -->
                                    <img src="<?php echo getImageUrl($item['images'] ? json_decode($item['images'])[0] : null); ?>" 
                                         alt="<?php echo htmlspecialchars($item['nom']); ?>" 
                                         style="width: 100px; height: 100px; object-fit: cover; border-radius: 4px;">
                                    
                                    <!-- Product Info -->
                                    <div>
                                        <h4 style="margin-bottom: 0.5rem;">
                                            <a href="product.php?id=<?php echo $item['produit_id']; ?>" style="color: var(--dark-blue); text-decoration: none;">
                                                <?php echo htmlspecialchars($item['nom']); ?>
                                            </a>
                                        </h4>
                                        <p style="color: var(--medium-gray); font-size: 0.9rem; margin-bottom: 0.5rem;">
                                            Catégorie: <?php echo htmlspecialchars($item['categorie_nom']); ?>
                                        </p>
                                        <p style="color: var(--medium-gray); font-size: 0.9rem;">
                                            Prix unitaire: <?php echo formatPrice($item['prix']); ?>
                                        </p>
                                        <p style="color: var(--medium-gray); font-size: 0.9rem;">
                                            Stock disponible: <?php echo $item['stock']; ?>
                                        </p>
                                    </div>
                                    
                                    <!-- Quantity and Actions -->
                                    <div style="text-align: right;">
                                        <div style="display: flex; align-items: center; margin-bottom: 1rem;">
                                            <label style="margin-right: 0.5rem; font-size: 0.9rem;">Quantité:</label>
                                            <input type="number" 
                                                   value="<?php echo $item['quantite']; ?>" 
                                                   min="1" 
                                                   max="<?php echo $item['stock']; ?>"
                                                   onchange="updateCartItem(<?php echo $item['produit_id']; ?>, this.value)"
                                                   style="width: 60px; padding: 0.25rem; border: 1px solid #ddd; border-radius: 4px;">
                                        </div>
                                        
                                        <div style="font-size: 1.2rem; font-weight: bold; color: var(--primary-red); margin-bottom: 1rem;">
                                            <?php echo formatPrice($item['total']); ?>
                                        </div>
                                        
                                        <button onclick="removeFromCart(<?php echo $item['produit_id']; ?>)" 
                                                style="background: var(--primary-red); color: white; border: none; padding: 0.5rem 1rem; border-radius: 4px; cursor: pointer;">
                                            Supprimer
                                        </button>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <!-- Cart Summary -->
                    <div style="background: white; border-radius: 8px; padding: 2rem; box-shadow: 0 2px 10px rgba(0,0,0,0.1); height: fit-content;">
                        <h3 style="margin-bottom: 1rem;">Résumé de la commande</h3>
                        
                        <div style="border-bottom: 1px solid #eee; padding-bottom: 1rem; margin-bottom: 1rem;">
                            <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
                                <span>Sous-total (<?php echo count($cart_items); ?> articles)</span>
                                <span><?php echo formatPrice($cart_total); ?></span>
                            </div>
                            <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
                                <span>Frais de livraison</span>
                                <span>Gratuit</span>
                            </div>
                        </div>
                        
                        <div style="display: flex; justify-content: space-between; font-size: 1.2rem; font-weight: bold; margin-bottom: 2rem;">
                            <span>Total</span>
                            <span style="color: var(--primary-red);" id="cart-total"><?php echo formatPrice($cart_total); ?></span>
                        </div>
                        
                        <button onclick="proceedToCheckout()" class="btn-primary" style="width: 100%; margin-bottom: 1rem;">
                            Passer la commande
                        </button>
                        
                        <a href="produits.php" class="btn-secondary" style="width: 100%; display: block; text-align: center;">
                            Continuer mes achats
                        </a>
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
        function updateCartItem(productId, quantity) {
            if (quantity < 1) {
                removeFromCart(productId);
                return;
            }
            
            fetch('api/cart.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ 
                    action: 'update',
                    product_id: productId,
                    quantity: parseInt(quantity)
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert(data.message || 'Erreur lors de la mise à jour');
                }
            });
        }

        function removeFromCart(productId) {
            if (confirm('Êtes-vous sûr de vouloir supprimer cet article ?')) {
                fetch('api/cart.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ 
                        action: 'remove',
                        product_id: productId
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert(data.message || 'Erreur lors de la suppression');
                    }
                });
            }
        }

        function proceedToCheckout() {
            alert('Fonctionnalité de commande en cours de développement!');
            // Here you would redirect to checkout page
            // window.location.href = 'checkout.php';
        }
    </script>
</body>
</html>
