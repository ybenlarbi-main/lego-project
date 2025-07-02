<?php
require_once 'config/config.php';

// Require login for checkout
requireLogin();

$user_id = $_SESSION['user_id'];

// Get user information
$stmt = $pdo->prepare("SELECT * FROM utilisateurs WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

// Get cart items
$stmt = $pdo->prepare("
    SELECT p.*, pr.nom, pr.reference, pr.prix, pr.prix_promo, pr.stock, pr.images, pa.quantite, 
           (COALESCE(pr.prix_promo, pr.prix) * pa.quantite) as total,
           c.nom as categorie_nom
    FROM panier pa 
    JOIN produits pr ON pa.produit_id = pr.id 
    LEFT JOIN categories c ON pr.categorie_id = c.id
    WHERE pa.client_id = ? AND pr.stock > 0
    ORDER BY pa.date_ajout DESC
");
$stmt->execute([$user_id]);
$cart_items = $stmt->fetchAll();

// Redirect to cart if empty
if (empty($cart_items)) {
    setFlashMessage('Votre panier est vide. Ajoutez des produits avant de passer commande.', 'warning');
    header('Location: cart.php');
    exit;
}

// Calculate cart totals
$subtotal = 0;
foreach ($cart_items as $item) {
    $subtotal += $item['total'];
}

$shipping = 30; // Fixed shipping cost
$total = $subtotal + $shipping;

// Handle form submission for order creation
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Verify stock availability before proceeding
        $stock_error = false;
        foreach ($cart_items as $item) {
            if ($item['quantite'] > $item['stock']) {
                $stock_error = true;
                setFlashMessage("Stock insuffisant pour {$item['nom']}. Veuillez ajuster votre panier.", 'danger');
                break;
            }
        }
        
        if (!$stock_error) {
            // Begin transaction
            $pdo->beginTransaction();
            
            // Get form data
            $adresse = sanitizeInput($_POST['adresse']);
            $ville = sanitizeInput($_POST['ville']);
            $code_postal = sanitizeInput($_POST['code_postal']);
            $telephone = sanitizeInput($_POST['telephone']);
            $notes = sanitizeInput($_POST['notes'] ?? '');
            $methode_paiement = sanitizeInput($_POST['methode_paiement']);
            
            // Generate unique order number
            $order_number = 'ORD-' . date('Ymd') . '-' . strtoupper(substr(md5(uniqid()), 0, 6));
            
            // Create address JSON
            $address_json = json_encode([
                'adresse' => $adresse,
                'ville' => $ville,
                'code_postal' => $code_postal,
                'telephone' => $telephone
            ]);
            
            // Insert order
            $stmt = $pdo->prepare("
                INSERT INTO commandes (
                    client_id, numero_commande, statut, total_ht, total_ttc, 
                    frais_livraison, methode_paiement, adresse_livraison, notes_livraison
                ) VALUES (?, ?, 'en_attente', ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $user_id,
                $order_number,
                $subtotal,
                $total,
                $shipping,
                $methode_paiement,
                $address_json,
                $notes
            ]);
            
            $order_id = $pdo->lastInsertId();
            
            // Insert order items
            $stmt = $pdo->prepare("
                INSERT INTO lignes_commandes (
                    commande_id, produit_id, quantite, prix_unitaire, total
                ) VALUES (?, ?, ?, ?, ?)
            ");
            
            foreach ($cart_items as $item) {
                // Get the correct price
                $price = $item['prix_promo'] ? $item['prix_promo'] : $item['prix'];
                
                $stmt->execute([
                    $order_id,
                    $item['produit_id'],
                    $item['quantite'],
                    $price,
                    $item['quantite'] * $price
                ]);
                
                // Update product stock
                $new_stock = $item['stock'] - $item['quantite'];
                $update_stmt = $pdo->prepare("UPDATE produits SET stock = ? WHERE id = ?");
                $update_stmt->execute([$new_stock, $item['produit_id']]);
            }
            
            // Clear user's cart
            $stmt = $pdo->prepare("DELETE FROM panier WHERE client_id = ?");
            $stmt->execute([$user_id]);
            
            // Commit transaction
            $pdo->commit();
            
            // Redirect to success page
            setFlashMessage('Votre commande a été passée avec succès! Numéro de commande: ' . $order_number, 'success');
            header('Location: order-confirmation.php?id=' . $order_id);
            exit;
        }
    } catch (Exception $e) {
        // Rollback transaction on error
        $pdo->rollBack();
        setFlashMessage('Erreur lors de la création de la commande: ' . $e->getMessage(), 'danger');
    }
}

$page_title = 'Finaliser ma commande';
?>
<!DOCTYPE html>
<html lang="<?php echo getCurrentLanguage(); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        .checkout-layout {
            display: grid;
            grid-template-columns: 1fr 400px;
            gap: 2rem;
        }
        
        @media (max-width: 992px) {
            .checkout-layout {
                grid-template-columns: 1fr;
            }
        }
        
        .checkout-form {
            background: white;
            border-radius: 10px;
            padding: 2rem;
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
        }
        
        .order-summary {
            background: white;
            border-radius: 10px;
            padding: 1.5rem;
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
            position: sticky;
            top: 2rem;
            align-self: flex-start;
        }
        
        .order-summary h3 {
            margin-top: 0;
            padding-bottom: 1rem;
            border-bottom: 1px solid #eee;
        }
        
        .summary-item {
            display: flex;
            justify-content: space-between;
            padding: 0.75rem 0;
            border-bottom: 1px solid #f5f5f5;
        }
        
        .summary-item.total {
            font-weight: bold;
            font-size: 1.1em;
            border-top: 2px solid #eee;
            padding-top: 1rem;
            margin-top: 0.5rem;
        }
        
        .payment-methods {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 1rem;
            margin: 1rem 0;
        }
        
        .payment-method {
            border: 2px solid #eee;
            border-radius: 8px;
            padding: 1rem;
            text-align: center;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .payment-method:hover {
            border-color: #ccc;
        }
        
        .payment-method.selected {
            border-color: var(--primary-color);
            background-color: rgba(59, 130, 246, 0.05);
        }
        
        .payment-method input {
            display: none;
        }
        
        .product-summary {
            margin: 1.5rem 0;
            max-height: 300px;
            overflow-y: auto;
            padding-right: 0.5rem;
        }
        
        .product-item {
            display: flex;
            align-items: center;
            margin-bottom: 1rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid #f5f5f5;
        }
        
        .product-image {
            width: 60px;
            height: 60px;
            border-radius: 6px;
            overflow: hidden;
            margin-right: 1rem;
            border: 1px solid #eee;
        }
        
        .product-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .product-details {
            flex: 1;
        }
        
        .product-name {
            font-weight: 600;
            margin: 0 0 0.25rem 0;
            font-size: 0.9rem;
        }
        
        .product-price {
            color: #666;
            font-size: 0.85rem;
        }
        
        .product-quantity {
            background: #f5f5f5;
            border-radius: 4px;
            padding: 0.25rem 0.5rem;
            font-size: 0.8rem;
            margin-left: 0.5rem;
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
                        <li><a href="cart.php">Panier</a></li>
                    </ul>
                </nav>
                
                <div class="user-actions">
                    <a href="profile.php" class="user-btn">Mon Compte</a>
                    <a href="auth/logout.php" class="user-btn">Déconnexion</a>
                </div>
            </div>
        </div>
    </header>
    
    <section class="products-section">
        <div class="container">
            <div class="section-header">
                <h1>Finaliser ma commande</h1>
                <p>Veuillez vérifier les informations ci-dessous</p>
            </div>
            
            <!-- Flash Messages -->
            <?php echo getFlashMessage(); ?>
            
            <div class="checkout-layout">
                <div class="checkout-form">
                    <form method="POST" id="checkout-form">
                        <h3>Informations de livraison</h3>
                        <div class="form-grid">
                            <div class="form-group">
                                <label for="nom">Nom</label>
                                <input type="text" id="nom" value="<?php echo htmlspecialchars($user['nom']); ?>" readonly>
                            </div>
                            
                            <div class="form-group">
                                <label for="prenom">Prénom</label>
                                <input type="text" id="prenom" value="<?php echo htmlspecialchars($user['prenom']); ?>" readonly>
                            </div>
                            
                            <div class="form-group">
                                <label for="email">Email</label>
                                <input type="email" id="email" value="<?php echo htmlspecialchars($user['email']); ?>" readonly>
                            </div>
                            
                            <div class="form-group">
                                <label for="telephone">Téléphone</label>
                                <input type="tel" id="telephone" name="telephone" value="<?php echo htmlspecialchars($user['telephone'] ?? ''); ?>" required>
                            </div>
                            
                            <div class="form-group full-width">
                                <label for="adresse">Adresse</label>
                                <input type="text" id="adresse" name="adresse" value="<?php echo htmlspecialchars($user['adresse'] ?? ''); ?>" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="ville">Ville</label>
                                <input type="text" id="ville" name="ville" value="<?php echo htmlspecialchars($user['ville'] ?? ''); ?>" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="code_postal">Code postal</label>
                                <input type="text" id="code_postal" name="code_postal" value="<?php echo htmlspecialchars($user['code_postal'] ?? ''); ?>" required>
                            </div>
                            
                            <div class="form-group full-width">
                                <label for="notes">Notes de livraison (facultatif)</label>
                                <textarea id="notes" name="notes" rows="3"></textarea>
                            </div>
                        </div>
                        
                        <h3>Mode de paiement</h3>
                        <div class="payment-methods">
                            <label class="payment-method" data-method="carte">
                                <input type="radio" name="methode_paiement" value="carte" required checked>
                                <div class="payment-icon">💳</div>
                                <div class="payment-name">Carte bancaire</div>
                            </label>
                            
                            <label class="payment-method" data-method="virement">
                                <input type="radio" name="methode_paiement" value="virement" required>
                                <div class="payment-icon">🏦</div>
                                <div class="payment-name">Virement bancaire</div>
                            </label>
                            
                            <label class="payment-method" data-method="especes">
                                <input type="radio" name="methode_paiement" value="especes" required>
                                <div class="payment-icon">💵</div>
                                <div class="payment-name">Espèces à la livraison</div>
                            </label>
                            
                            <label class="payment-method" data-method="cheque">
                                <input type="radio" name="methode_paiement" value="cheque" required>
                                <div class="payment-icon">📝</div>
                                <div class="payment-name">Chèque</div>
                            </label>
                        </div>
                        
                        <div class="form-actions">
                            <a href="cart.php" class="btn-secondary">Retourner au panier</a>
                            <button type="submit" class="btn-primary">Confirmer ma commande</button>
                        </div>
                    </form>
                </div>
                
                <div class="order-summary">
                    <h3>Résumé de la commande</h3>
                    
                    <div class="product-summary">
                        <?php foreach ($cart_items as $item): ?>
                            <?php
                            $price = $item['prix_promo'] ? $item['prix_promo'] : $item['prix'];
                            $images = json_decode($item['images'], true);
                            $image = !empty($images) ? $images[0] : 'assets/images/placeholder.svg';
                            ?>
                            <div class="product-item">
                                <div class="product-image">
                                    <img src="<?php echo $image; ?>" alt="<?php echo htmlspecialchars($item['nom']); ?>">
                                </div>
                                <div class="product-details">
                                    <p class="product-name">
                                        <?php echo htmlspecialchars($item['nom']); ?>
                                        <span class="product-quantity">x<?php echo $item['quantite']; ?></span>
                                    </p>
                                    <p class="product-price"><?php echo formatPrice($price); ?></p>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <div class="summary-item">
                        <div>Sous-total</div>
                        <div><?php echo formatPrice($subtotal); ?></div>
                    </div>
                    
                    <div class="summary-item">
                        <div>Frais de livraison</div>
                        <div><?php echo formatPrice($shipping); ?></div>
                    </div>
                    
                    <div class="summary-item total">
                        <div>Total</div>
                        <div><?php echo formatPrice($total); ?></div>
                    </div>
                    
                    <button type="button" class="btn-primary full-width" onclick="document.getElementById('checkout-form').submit()">
                        Confirmer ma commande
                    </button>
                </div>
            </div>
        </div>
    </section>
    
    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="footer-bottom">
                <p>&copy; <?php echo date('Y'); ?> Menalego. Tous droits réservés.</p>
            </div>
        </div>
    </footer>
    
    <script>
        // Payment method selection
        document.addEventListener('DOMContentLoaded', function() {
            const paymentMethods = document.querySelectorAll('.payment-method');
            
            paymentMethods.forEach(method => {
                method.addEventListener('click', function() {
                    // Remove selected class from all methods
                    paymentMethods.forEach(m => m.classList.remove('selected'));
                    
                    // Add selected class to clicked method
                    this.classList.add('selected');
                    
                    // Check the radio button
                    const radio = this.querySelector('input[type="radio"]');
                    radio.checked = true;
                });
                
                // Initialize selected state
                const radio = method.querySelector('input[type="radio"]');
                if (radio.checked) {
                    method.classList.add('selected');
                }
            });
        });
    </script>
</body>
</html>
