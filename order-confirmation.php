<?php
require_once 'config/config.php';

// Require login for viewing order confirmation
requireLogin();

$user_id = $_SESSION['user_id'];

// Check if order ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: profile.php');
    exit;
}

$order_id = (int)$_GET['id'];

// Get order details (verify it belongs to the current user)
$stmt = $pdo->prepare("
    SELECT c.*, u.prenom, u.nom, u.email
    FROM commandes c 
    JOIN utilisateurs u ON c.client_id = u.id 
    WHERE c.id = ? AND c.client_id = ?
");
$stmt->execute([$order_id, $user_id]);
$order = $stmt->fetch();

// If order doesn't exist or doesn't belong to user
if (!$order) {
    setFlashMessage('Commande introuvable', 'danger');
    header('Location: profile.php');
    exit;
}

// Get order items
$stmt = $pdo->prepare("
    SELECT ci.*, p.nom, p.reference, p.images
    FROM lignes_commandes ci
    LEFT JOIN produits p ON ci.produit_id = p.id
    WHERE ci.commande_id = ?
");
$stmt->execute([$order_id]);
$items = $stmt->fetchAll();

// Format delivery address
$address = json_decode($order['adresse_livraison'], true);

$page_title = 'Confirmation de commande';
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
        .confirmation-container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            border-radius: 10px;
            padding: 2rem;
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
        }
        
        .confirmation-header {
            text-align: center;
            padding-bottom: 2rem;
            margin-bottom: 2rem;
            border-bottom: 1px solid #eee;
        }
        
        .confirmation-header .check-icon {
            display: inline-block;
            width: 80px;
            height: 80px;
            background: #4ade80;
            border-radius: 50%;
            color: white;
            font-size: 40px;
            line-height: 80px;
            margin-bottom: 1.5rem;
        }
        
        .confirmation-header h2 {
            margin: 0 0 1rem 0;
            color: #10b981;
        }
        
        .order-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 2rem;
            margin-bottom: 2rem;
        }
        
        .details-block {
            margin-bottom: 1.5rem;
        }
        
        .details-block h3 {
            font-size: 1rem;
            margin: 0 0 1rem 0;
            padding-bottom: 0.5rem;
            border-bottom: 1px solid #eee;
        }
        
        .detail-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 0.5rem;
            font-size: 0.9rem;
        }
        
        .detail-label {
            color: #666;
            font-weight: 500;
        }
        
        .detail-value {
            font-weight: 600;
        }
        
        .order-summary {
            margin-top: 2rem;
        }
        
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 1.5rem;
        }
        
        .items-table th {
            text-align: left;
            padding: 0.75rem;
            background-color: #f8fafc;
            font-size: 0.85rem;
        }
        
        .items-table td {
            padding: 0.75rem;
            border-top: 1px solid #eee;
            font-size: 0.9rem;
        }
        
        .product-cell {
            display: flex;
            align-items: center;
        }
        
        .product-image {
            width: 50px;
            height: 50px;
            border-radius: 6px;
            overflow: hidden;
            margin-right: 1rem;
            border: 1px solid #eee;
        }
        
        .product-image img {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }
        
        .total-row {
            font-weight: 600;
            background-color: #f8fafc;
        }
        
        .status-badge {
            display: inline-block;
            padding: 0.25rem 0.5rem;
            border-radius: 0.25rem;
            font-size: 0.75rem;
            font-weight: 500;
        }
        
        .status-en_attente { background-color: #fef3c7; color: #92400e; }
        .status-confirmee { background-color: #dbeafe; color: #1e40af; }
        .status-preparee { background-color: #e0f2fe; color: #0369a1; }
        .status-expediee { background-color: #dbeafe; color: #1e40af; }
        .status-livree { background-color: #dcfce7; color: #166534; }
        .status-annulee { background-color: #fee2e2; color: #991b1b; }
        
        .actions {
            display: flex;
            justify-content: space-between;
            margin-top: 2rem;
            padding-top: 2rem;
            border-top: 1px solid #eee;
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
                        <li><a href="profile.php">Mon Compte</a></li>
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
            <!-- Flash Messages -->
            <?php echo getFlashMessage(); ?>
            
            <div class="confirmation-container">
                <div class="confirmation-header">
                    <div class="check-icon">✓</div>
                    <h2>Commande confirmée</h2>
                    <p>Votre commande a été enregistrée avec succès. Un email de confirmation vous a été envoyé.</p>
                </div>
                
                <div class="order-details">
                    <div>
                        <div class="details-block">
                            <h3>Informations commande</h3>
                            <div class="detail-row">
                                <span class="detail-label">Numéro de commande:</span>
                                <span class="detail-value"><?php echo htmlspecialchars($order['numero_commande']); ?></span>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label">Date:</span>
                                <span class="detail-value"><?php echo formatDate($order['date_commande']); ?></span>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label">Statut:</span>
                                <span class="detail-value">
                                    <span class="status-badge status-<?php echo $order['statut']; ?>">
                                        <?php echo getStatusText($order['statut']); ?>
                                    </span>
                                </span>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label">Mode de paiement:</span>
                                <span class="detail-value"><?php echo ucfirst($order['methode_paiement']); ?></span>
                            </div>
                        </div>
                        
                        <div class="details-block">
                            <h3>Informations client</h3>
                            <div class="detail-row">
                                <span class="detail-label">Nom:</span>
                                <span class="detail-value"><?php echo htmlspecialchars($order['prenom'] . ' ' . $order['nom']); ?></span>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label">Email:</span>
                                <span class="detail-value"><?php echo htmlspecialchars($order['email']); ?></span>
                            </div>
                        </div>
                    </div>
                    
                    <div>
                        <div class="details-block">
                            <h3>Adresse de livraison</h3>
                            <div class="detail-row">
                                <span class="detail-label">Adresse:</span>
                                <span class="detail-value"><?php echo htmlspecialchars($address['adresse']); ?></span>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label">Ville:</span>
                                <span class="detail-value"><?php echo htmlspecialchars($address['ville']); ?></span>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label">Code postal:</span>
                                <span class="detail-value"><?php echo htmlspecialchars($address['code_postal']); ?></span>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label">Téléphone:</span>
                                <span class="detail-value"><?php echo htmlspecialchars($address['telephone']); ?></span>
                            </div>
                        </div>
                        
                        <?php if (!empty($order['notes_livraison'])): ?>
                            <div class="details-block">
                                <h3>Notes de livraison</h3>
                                <p><?php echo nl2br(htmlspecialchars($order['notes_livraison'])); ?></p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="order-summary">
                    <h3>Résumé de votre commande</h3>
                    <table class="items-table">
                        <thead>
                            <tr>
                                <th>Produit</th>
                                <th>Prix unitaire</th>
                                <th>Quantité</th>
                                <th>Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($items as $item): ?>
                                <?php
                                $images = json_decode($item['images'], true);
                                $image = !empty($images) ? $images[0] : 'assets/images/placeholder.svg';
                                ?>
                                <tr>
                                    <td class="product-cell">
                                        <div class="product-image">
                                            <img src="<?php echo $image; ?>" alt="<?php echo htmlspecialchars($item['nom']); ?>">
                                        </div>
                                        <div>
                                            <div><?php echo htmlspecialchars($item['nom']); ?></div>
                                            <small>Réf: <?php echo htmlspecialchars($item['reference']); ?></small>
                                        </div>
                                    </td>
                                    <td><?php echo formatPrice($item['prix_unitaire']); ?></td>
                                    <td><?php echo $item['quantite']; ?></td>
                                    <td><?php echo formatPrice($item['total']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="3" style="text-align: right;">Sous-total</td>
                                <td><?php echo formatPrice($order['total_ht']); ?></td>
                            </tr>
                            <tr>
                                <td colspan="3" style="text-align: right;">Frais de livraison</td>
                                <td><?php echo formatPrice($order['frais_livraison']); ?></td>
                            </tr>
                            <tr class="total-row">
                                <td colspan="3" style="text-align: right;">Total</td>
                                <td><?php echo formatPrice($order['total_ttc']); ?></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
                
                <div class="actions">
                    <a href="profile.php" class="btn-secondary">Voir mes commandes</a>
                    <a href="index.php" class="btn-primary">Continuer mes achats</a>
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
</body>
</html>
