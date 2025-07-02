<?php
require_once 'config/config.php';

// Require login for cart
requireLogin();

$user_id = $_SESSION['user_id'];

// Get cart items
$stmt = $pdo->prepare("
    SELECT pr.id as produit_id, pr.nom, pr.prix, pr.stock, pr.images, pa.quantite, 
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
    <link rel="stylesheet" href="assets/css/style.css?v=<?php echo time(); ?>">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        /* Header cart-specific styles */
        .cart-btn.active {
            background: #0061FF !important;
            color: white !important;
            border-radius: 8px;
            padding: 0.5rem 1rem;
        }
        
        .admin-btn {
            background: #ef4444 !important;
            color: white !important;
        }
        
        /* Cart-specific styles */
        .cart-container {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 2rem;
            margin-top: 2rem;
        }
        
        .cart-item {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 1rem;
            box-shadow: 0 2px 15px rgba(0,0,0,0.08);
            border: 1px solid #f0f0f0;
            transition: box-shadow 0.3s ease;
        }
        
        .cart-item:hover {
            box-shadow: 0 4px 20px rgba(0,0,0,0.12);
        }
        
        .cart-item-content {
            display: grid;
            grid-template-columns: 120px 1fr auto;
            gap: 1.5rem;
            align-items: center;
        }
        
        .cart-item-image {
            width: 120px;
            height: 120px;
            object-fit: cover;
            border-radius: 8px;
            border: 1px solid #e0e0e0;
        }
        
        .cart-item-info h4 {
            margin: 0 0 0.5rem 0;
            font-size: 1.1rem;
            font-weight: 600;
        }
        
        .cart-item-info h4 a {
            color: #1f2937;
            text-decoration: none;
            transition: color 0.2s ease;
        }
        
        .cart-item-info h4 a:hover {
            color: #0061FF;
        }
        
        .cart-item-details {
            color: #6b7280;
            font-size: 0.9rem;
            line-height: 1.4;
            margin: 0.25rem 0;
        }
        
        .cart-item-actions {
            text-align: right;
            min-width: 180px;
        }
        
        .quantity-control {
            display: flex;
            align-items: center;
            justify-content: flex-end;
            margin-bottom: 1rem;
            gap: 0.5rem;
        }
        
        .quantity-input {
            width: 70px;
            padding: 0.4rem;
            border: 2px solid #e5e7eb;
            border-radius: 6px;
            text-align: center;
            font-weight: 500;
            transition: border-color 0.2s ease;
        }
        
        .quantity-input:focus {
            outline: none;
            border-color: #0061FF;
            box-shadow: 0 0 0 3px rgba(0, 97, 255, 0.1);
        }
        
        .item-total {
            font-size: 1.3rem;
            font-weight: 700;
            color: #059669;
            margin-bottom: 1rem;
        }
        
        .remove-btn {
            background: #ef4444;
            color: white;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 6px;
            cursor: pointer;
            font-size: 0.85rem;
            font-weight: 500;
            transition: all 0.2s ease;
        }
        
        .remove-btn:hover {
            background: #dc2626;
            transform: translateY(-1px);
        }
        
        .cart-summary {
            background: white;
            border-radius: 12px;
            padding: 2rem;
            box-shadow: 0 2px 15px rgba(0,0,0,0.08);
            border: 1px solid #f0f0f0;
            height: fit-content;
            position: sticky;
            top: 2rem;
        }
        
        .summary-line {
            display: flex;
            justify-content: space-between;
            margin-bottom: 0.75rem;
            padding-bottom: 0.75rem;
        }
        
        .summary-divider {
            border-bottom: 1px solid #e5e7eb;
        }
        
        .summary-total {
            display: flex;
            justify-content: space-between;
            font-size: 1.25rem;
            font-weight: 700;
            margin: 1.5rem 0;
            padding-top: 1rem;
            border-top: 2px solid #e5e7eb;
        }
        
        .checkout-btn {
            width: 100%;
            background: linear-gradient(135deg, #0061FF 0%, #4285F4 100%);
            color: white;
            border: none;
            padding: 1rem 2rem;
            border-radius: 8px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            margin-bottom: 1rem;
            transition: all 0.3s ease;
        }
        
        .checkout-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0, 97, 255, 0.3);
        }
        
        .continue-shopping {
            width: 100%;
            background: #f8fafc;
            color: #374151;
            border: 2px solid #e5e7eb;
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 500;
            text-align: center;
            display: block;
            transition: all 0.2s ease;
        }
        
        .continue-shopping:hover {
            background: #e5e7eb;
            border-color: #d1d5db;
        }
        
        .empty-cart {
            text-align: center;
            padding: 4rem 2rem;
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 15px rgba(0,0,0,0.08);
        }
        
        .empty-cart h3 {
            font-size: 1.5rem;
            color: #374151;
            margin-bottom: 1rem;
        }
        
        .empty-cart p {
            color: #6b7280;
            margin-bottom: 2rem;
        }
        
        @media (max-width: 768px) {
            .cart-container {
                grid-template-columns: 1fr;
                gap: 1.5rem;
            }
            
            .cart-item-content {
                grid-template-columns: 100px 1fr;
                gap: 1rem;
            }
            
            .cart-item-actions {
                grid-column: 1 / -1;
                margin-top: 1rem;
                text-align: left;
                display: flex;
                justify-content: space-between;
                align-items: center;
            }
            
            .quantity-control {
                margin-bottom: 0;
            }
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
                        <li><a href="produits.php">Produits</a></li>
                        <li><a href="categories.php">Catégories</a></li>
                    </ul>
                </nav>
                
                <div class="user-actions">
                    <a href="cart.php" class="cart-btn active">
                        <i class="fas fa-shopping-cart"></i> Panier 
                        <span class="cart-count"><?php echo count($cart_items); ?></span>
                    </a>
                    <?php if (isLoggedIn()): ?>
                        <a href="profile.php" class="user-btn"><i class="fas fa-user"></i> Mon Compte</a>
                        <a href="auth/logout.php" class="user-btn"><i class="fas fa-sign-out-alt"></i> Déconnexion</a>
                        <?php if (isAdmin()): ?>
                            <a href="admin/" class="user-btn admin-btn"><i class="fas fa-cog"></i> Admin</a>
                        <?php endif; ?>
                    <?php else: ?>
                        <a href="auth/login.php" class="user-btn"><i class="fas fa-sign-in-alt"></i> Connexion</a>
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
                <div class="empty-cart">
                    <h3>🛒 Votre panier est vide</h3>
                    <p>Découvrez nos magnifiques créations LEGO® inspirées du patrimoine marocain</p>
                    <a href="produits.php" class="checkout-btn">Découvrir nos produits</a>
                </div>
            <?php else: ?>
                <div class="cart-container">
                    <!-- Cart Items -->
                    <div>
                        <?php foreach ($cart_items as $item): ?>
                            <div class="cart-item">
                                <div class="cart-item-content">
                                    <!-- Product Image -->
                                    <img src="<?php echo getImageUrl($item['images'] ? json_decode($item['images'])[0] : null); ?>" 
                                         alt="<?php echo htmlspecialchars($item['nom']); ?>" 
                                         class="cart-item-image">
                                    
                                    <!-- Product Info -->
                                    <div class="cart-item-info">
                                        <h4>
                                            <a href="product.php?id=<?php echo $item['produit_id']; ?>">
                                                <?php echo htmlspecialchars($item['nom']); ?>
                                            </a>
                                        </h4>
                                        <div class="cart-item-details">
                                            <strong>Catégorie:</strong> <?php echo htmlspecialchars($item['categorie_nom'] ?? 'Non classé'); ?>
                                        </div>
                                        <div class="cart-item-details">
                                            <strong>Prix unitaire:</strong> <?php echo formatPrice($item['prix']); ?>
                                        </div>
                                        <div class="cart-item-details">
                                            <strong>Stock disponible:</strong> <?php echo $item['stock']; ?> unités
                                        </div>
                                    </div>
                                    
                                    <!-- Quantity and Actions -->
                                    <div class="cart-item-actions">
                                        <div class="quantity-control">
                                            <label>Qté:</label>
                                            <input type="number" 
                                                   class="quantity-input"
                                                   value="<?php echo $item['quantite']; ?>" 
                                                   min="1" 
                                                   max="<?php echo $item['stock']; ?>"
                                                   onchange="updateCartItem(<?php echo $item['produit_id']; ?>, this.value)">
                                        </div>
                                        
                                        <div class="item-total">
                                            <?php echo formatPrice($item['total']); ?>
                                        </div>
                                        
                                        <button onclick="removeFromCart(<?php echo $item['produit_id']; ?>)" 
                                                class="remove-btn">
                                            🗑️ Supprimer
                                        </button>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <!-- Cart Summary -->
                    <div class="cart-summary">
                        <h3 style="margin: 0 0 1.5rem 0; color: #1f2937;">Résumé de la commande</h3>
                        
                        <div class="summary-line summary-divider">
                            <span>Sous-total (<?php echo count($cart_items); ?> articles)</span>
                            <span style="font-weight: 600;"><?php echo formatPrice($cart_total); ?></span>
                        </div>
                        
                        <div class="summary-line">
                            <span>Frais de livraison</span>
                            <span style="color: #059669; font-weight: 600;">Gratuit 🚚</span>
                        </div>
                        
                        <div class="summary-total">
                            <span>Total</span>
                            <span style="color: #059669;" id="cart-total"><?php echo formatPrice($cart_total); ?></span>
                        </div>
                        
                        <button onclick="proceedToCheckout()" class="checkout-btn">
                            🛒 Passer la commande
                        </button>
                        
                        <a href="produits.php" class="continue-shopping">
                            ← Continuer mes achats
                        </a>
                        
                        <div style="margin-top: 1.5rem; padding: 1rem; background: #f0f9ff; border-radius: 8px; border-left: 4px solid #0284c7;">
                            <div style="font-size: 0.85rem; color: #0369a1;">
                                ✓ Livraison gratuite<br>
                                ✓ Retour sous 30 jours<br>
                                ✓ Garantie qualité LEGO®
                            </div>
                        </div>
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
            // Redirect to checkout page
            window.location.href = 'checkout.php';
        }
    </script>
</body>
</html>
