<?php
require_once 'config/config.php';

// Require login for viewing order details
requireLogin();

$user_id = $_SESSION['user_id'];

// Check if order ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    setFlashMessage('ID de commande invalide', 'danger');
    header('Location: orders.php');
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
    setFlashMessage('Commande introuvable ou accès non autorisé', 'danger');
    header('Location: orders.php');
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

// Get status translation array
$status_translations = [
    'en_attente' => 'En attente',
    'confirmee' => 'Confirmée',
    'preparee' => 'Préparée',
    'expediee' => 'Expédiée',
    'livree' => 'Livrée',
    'annulee' => 'Annulée'
];

// Get status badge colors
$status_colors = [
    'en_attente' => 'bg-yellow-100 text-yellow-800',
    'confirmee' => 'bg-blue-100 text-blue-800',
    'preparee' => 'bg-purple-100 text-purple-800',
    'expediee' => 'bg-indigo-100 text-indigo-800',
    'livree' => 'bg-green-100 text-green-800',
    'annulee' => 'bg-red-100 text-red-800'
];

$page_title = 'Détail de commande #' . $order['numero_commande'];
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
        .order-details-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem 1rem;
        }
        
        .order-details-header {
            margin-bottom: 2rem;
        }
        
        .order-details-card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
            padding: 2rem;
            margin-bottom: 2rem;
        }
        
        .order-info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 2rem;
            margin-bottom: 2rem;
        }
        
        .order-info-section h4 {
            font-size: 1rem;
            font-weight: 600;
            margin-bottom: 1rem;
            padding-bottom: 0.5rem;
            border-bottom: 1px solid #f0f0f0;
        }
        
        .order-meta-item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 0.5rem;
        }
        
        .order-meta-label {
            color: #6b7280;
            font-size: 0.875rem;
        }
        
        .order-meta-value {
            font-weight: 500;
            text-align: right;
        }
        
        .order-address {
            margin-bottom: 0.25rem;
            line-height: 1.5;
        }
        
        .order-status {
            text-align: center;
            padding: 1.5rem 0;
            margin-bottom: 2rem;
            border-bottom: 1px solid #f0f0f0;
        }
        
        .status-badge-large {
            display: inline-block;
            padding: 0.5rem 1.5rem;
            border-radius: 9999px;
            font-weight: 600;
            font-size: 1rem;
            margin-bottom: 0.5rem;
        }
        
        .order-items-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .order-items-table th, .order-items-table td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid #f0f0f0;
        }
        
        .order-items-table th {
            background-color: #f9fafb;
            font-weight: 600;
        }
        
        .order-items-table tr:last-child td {
            border-bottom: none;
        }
        
        .order-item-image {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 6px;
            margin-right: 1rem;
        }
        
        .order-item-info {
            display: flex;
            align-items: center;
        }
        
        .order-item-name {
            font-weight: 500;
            margin-bottom: 0.25rem;
        }
        
        .order-item-sku {
            font-size: 0.75rem;
            color: #6b7280;
        }
        
        .order-summary {
            width: 100%;
            max-width: 300px;
            margin-left: auto;
            margin-top: 2rem;
        }
        
        .order-summary-item {
            display: flex;
            justify-content: space-between;
            padding: 0.5rem 0;
            border-bottom: 1px solid #f0f0f0;
        }
        
        .order-summary-item.total {
            font-weight: 600;
            font-size: 1.1em;
            padding-top: 1rem;
        }
        
        .back-to-orders {
            display: inline-flex;
            align-items: center;
            margin-top: 2rem;
            color: #4f46e5;
            text-decoration: none;
            font-weight: 500;
        }
        
        .back-to-orders i {
            margin-right: 0.5rem;
        }
        
        @media (max-width: 768px) {
            .order-items-table thead {
                display: none;
            }
            
            .order-items-table tbody tr {
                display: block;
                padding: 1rem 0;
                border-bottom: 1px solid #f0f0f0;
            }
            
            .order-items-table td {
                display: block;
                text-align: right;
                padding: 0.5rem 0;
                border: none;
            }
            
            .order-items-table td:before {
                content: attr(data-label);
                float: left;
                font-weight: bold;
            }
            
            .order-item-info {
                justify-content: flex-end;
            }
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="page-header">
        <div class="container">
            <h1><?php echo $page_title; ?></h1>
            <nav class="breadcrumb">
                <a href="index.php">Accueil</a>
                <span class="separator">/</span>
                <a href="profile.php">Mon Profil</a>
                <span class="separator">/</span>
                <a href="orders.php">Mes Commandes</a>
                <span class="separator">/</span>
                <span class="current">Commande #<?php echo htmlspecialchars($order['numero_commande']); ?></span>
            </nav>
        </div>
    </div>

    <div class="order-details-container">
        <div class="order-details-header">
            <?php 
            if (isset($_SESSION['flash_messages']) && !empty($_SESSION['flash_messages'])) {
                foreach ($_SESSION['flash_messages'] as $message) {
                    echo '<div class="alert alert-'.$message['type'].'">';
                    echo '<i class="fas fa-info-circle"></i>';
                    echo '<span>'.$message['message'].'</span>';
                    echo '</div>';
                }
                unset($_SESSION['flash_messages']);
            }
            ?>
            <div class="alert alert-info">
                <i class="fas fa-info-circle"></i>
                <span>Vous avez des questions sur votre commande ? Contactez-nous en citant la référence #<?php echo htmlspecialchars($order['numero_commande']); ?>.</span>
            </div>
        </div>
        
        <div class="order-details-card">
            <div class="order-status">
                <span class="status-badge-large <?php echo $status_colors[$order['statut']]; ?>">
                    <?php echo $status_translations[$order['statut']]; ?>
                </span>
                <p>Dernière mise à jour: <?php echo date('d/m/Y H:i', strtotime($order['date_modification'])); ?></p>
            </div>
            
            <div class="order-info-grid">
                <div class="order-info-section">
                    <h4>Informations de commande</h4>
                    <div class="order-meta-item">
                        <span class="order-meta-label">Numéro</span>
                        <span class="order-meta-value"><?php echo htmlspecialchars($order['numero_commande']); ?></span>
                    </div>
                    <div class="order-meta-item">
                        <span class="order-meta-label">Date</span>
                        <span class="order-meta-value"><?php echo date('d/m/Y H:i', strtotime($order['date_commande'])); ?></span>
                    </div>
                    <div class="order-meta-item">
                        <span class="order-meta-label">Paiement</span>
                        <span class="order-meta-value">
                            <?php 
                                $payment_methods = [
                                    'carte' => 'Carte bancaire',
                                    'virement' => 'Virement bancaire',
                                    'especes' => 'Espèces à la livraison',
                                    'cheque' => 'Chèque'
                                ];
                                echo $payment_methods[$order['methode_paiement']]; 
                            ?>
                        </span>
                    </div>
                </div>
                
                <div class="order-info-section">
                    <h4>Adresse de livraison</h4>
                    <p class="order-address"><?php echo htmlspecialchars($order['nom'] . ' ' . $order['prenom']); ?></p>
                    <p class="order-address"><?php echo htmlspecialchars($address['adresse']); ?></p>
                    <p class="order-address">
                        <?php echo htmlspecialchars($address['code_postal'] . ' ' . $address['ville']); ?>
                    </p>
                    <p class="order-address">Tél: <?php echo htmlspecialchars($address['telephone']); ?></p>
                </div>
                
                <?php if (!empty($order['notes_livraison'])): ?>
                <div class="order-info-section">
                    <h4>Instructions de livraison</h4>
                    <p><?php echo nl2br(htmlspecialchars($order['notes_livraison'])); ?></p>
                </div>
                <?php endif; ?>
            </div>
            
            <h4>Produits commandés</h4>
            <table class="order-items-table">
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
                            $image_url = !empty($images) ? getImageUrl($images[0]) : 'assets/images/placeholder.svg';
                        ?>
                        <tr>
                            <td data-label="Produit">
                                <div class="order-item-info">
                                    <img src="<?php echo $image_url; ?>" alt="<?php echo htmlspecialchars($item['nom']); ?>" class="order-item-image">
                                    <div>
                                        <div class="order-item-name"><?php echo htmlspecialchars($item['nom']); ?></div>
                                        <?php if ($item['reference']): ?>
                                            <div class="order-item-sku">Réf: <?php echo htmlspecialchars($item['reference']); ?></div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </td>
                            <td data-label="Prix unitaire"><?php echo formatPrice($item['prix_unitaire']); ?></td>
                            <td data-label="Quantité"><?php echo $item['quantite']; ?></td>
                            <td data-label="Total"><?php echo formatPrice($item['total']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            
            <div class="order-summary">
                <div class="order-summary-item">
                    <span>Sous-total</span>
                    <span><?php echo formatPrice($order['total_ht']); ?></span>
                </div>
                <div class="order-summary-item">
                    <span>Frais de livraison</span>
                    <span><?php echo formatPrice($order['frais_livraison']); ?></span>
                </div>
                <div class="order-summary-item total">
                    <span>Total</span>
                    <span><?php echo formatPrice($order['total_ttc']); ?></span>
                </div>
            </div>
        </div>
        
        <a href="orders.php" class="back-to-orders">
            <i class="fas fa-arrow-left"></i> Retour à la liste des commandes
        </a>
    </div>

    <?php include 'includes/footer.php'; ?>
    
    <script>
        // Fetch cart count on page load
        document.addEventListener('DOMContentLoaded', function() {
            fetchCartCount();
        });

        function fetchCartCount() {
            fetch('api/cart.php?action=count')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        document.getElementById('cart-count').innerText = data.count;
                    }
                })
                .catch(error => console.error('Error fetching cart count:', error));
        }
    </script>
</body>
</html>
