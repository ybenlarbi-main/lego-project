<?php
require_once '../config/config.php';

// Check if user is admin
requireAdmin();

// Get statistics
$stats = [];

// Total products
$stmt = $pdo->prepare("SELECT COUNT(*) as total FROM produits");
$stmt->execute();
$stats['products'] = $stmt->fetch()['total'];

// Total users
$stmt = $pdo->prepare("SELECT COUNT(*) as total FROM utilisateurs WHERE role = 'client'");
$stmt->execute();
$stats['users'] = $stmt->fetch()['total'];

// Total orders
$stmt = $pdo->prepare("SELECT COUNT(*) as total FROM commandes");
$stmt->execute();
$stats['orders'] = $stmt->fetch()['total'];

// Total revenue
$stmt = $pdo->prepare("SELECT SUM(total_ttc) as total FROM commandes WHERE statut IN ('confirmee', 'preparee', 'expediee', 'livree')");
$stmt->execute();
$result = $stmt->fetch();
$stats['revenue'] = $result['total'] ?: 0;

// Recent orders
$stmt = $pdo->prepare("
    SELECT c.*, u.nom, u.prenom 
    FROM commandes c 
    LEFT JOIN utilisateurs u ON c.client_id = u.id 
    ORDER BY c.date_commande DESC 
    LIMIT 5
");
$stmt->execute();
$recent_orders = $stmt->fetchAll();

// Low stock products
$stmt = $pdo->prepare("
    SELECT * FROM produits 
    WHERE stock < 5 AND statut = 'actif' 
    ORDER BY stock ASC 
    LIMIT 5
");
$stmt->execute();
$low_stock = $stmt->fetchAll();

$page_title = 'Dashboard Admin';
?>

<!DOCTYPE html>
<html lang="<?php echo getCurrentLanguage(); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
</head>
<body>
    <!-- Header -->
    <header class="header">
        <div class="container">
            <div class="header-content">
                <a href="<?php echo SITE_URL; ?>" class="logo">
                    <div class="logo-icon">M</div>
                    Menalego - Admin
                </a>
                
                <div class="user-actions">
                    <a href="<?php echo SITE_URL; ?>" class="user-btn">Voir le site</a>
                    <a href="../auth/logout.php" class="user-btn">Déconnexion</a>
                </div>
            </div>
        </div>
    </header>

    <!-- Admin Navigation -->
    <nav class="admin-nav">
        <div class="container">
            <ul>
                <li><a href="index.php" class="active">Dashboard</a></li>
                <li><a href="products.php">Produits</a></li>
                <li><a href="categories.php">Catégories</a></li>
                <li><a href="orders.php">Commandes</a></li>
                <li><a href="users.php">Utilisateurs</a></li>
                <li><a href="reviews.php">Avis</a></li>
            </ul>
        </div>
    </nav>

    <!-- Dashboard Content -->
    <section class="products-section">
        <div class="container">
            <h1>Dashboard Administrateur</h1>
            
            <!-- Flash Messages -->
            <?php echo getFlashMessage(); ?>
            
            <!-- Statistics Cards -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-number"><?php echo number_format($stats['products']); ?></div>
                    <div class="stat-label">Produits</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-number"><?php echo number_format($stats['users']); ?></div>
                    <div class="stat-label">Clients</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-number"><?php echo number_format($stats['orders']); ?></div>
                    <div class="stat-label">Commandes</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-number"><?php echo formatPrice($stats['revenue']); ?></div>
                    <div class="stat-label">Chiffre d'affaires</div>
                </div>
            </div>
            
            <!-- Recent Activity -->
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem; margin-top: 2rem;">
                <!-- Recent Orders -->
                <div class="admin-dashboard">
                    <h3>Commandes récentes</h3>
                    <?php if (empty($recent_orders)): ?>
                        <p>Aucune commande pour le moment.</p>
                    <?php else: ?>
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Numéro</th>
                                    <th>Client</th>
                                    <th>Montant</th>
                                    <th>Statut</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recent_orders as $order): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($order['numero_commande']); ?></td>
                                        <td><?php echo htmlspecialchars($order['prenom'] . ' ' . $order['nom']); ?></td>
                                        <td><?php echo formatPrice($order['total_ttc']); ?></td>
                                        <td>
                                            <span style="padding: 0.25rem 0.5rem; border-radius: 4px; font-size: 0.8rem; 
                                                  background: <?php echo $order['statut'] == 'livree' ? '#28a745' : '#ffc107'; ?>; 
                                                  color: white;">
                                                <?php echo ucfirst($order['statut']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo date('d/m/Y', strtotime($order['date_commande'])); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        <div class="text-center">
                            <a href="orders.php" class="btn-primary">Voir toutes les commandes</a>
                        </div>
                    <?php endif; ?>
                </div>
                
                <!-- Low Stock Alert -->
                <div class="admin-dashboard">
                    <h3>Stock faible</h3>
                    <?php if (empty($low_stock)): ?>
                        <p>Tous les produits ont un stock suffisant.</p>
                    <?php else: ?>
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Produit</th>
                                    <th>Stock</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($low_stock as $product): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($product['nom']); ?></td>
                                        <td>
                                            <span style="color: <?php echo $product['stock'] == 0 ? 'red' : 'orange'; ?>; font-weight: bold;">
                                                <?php echo $product['stock']; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <a href="products.php?edit=<?php echo $product['id']; ?>" class="btn-secondary">Modifier</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        <div class="text-center">
                            <a href="products.php" class="btn-primary">Gérer les produits</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Quick Actions -->
            <div class="admin-dashboard" style="margin-top: 2rem;">
                <h3>Actions rapides</h3>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
                    <a href="products.php?action=add" class="btn-primary">Ajouter un produit</a>
                    <a href="categories.php?action=add" class="btn-primary">Ajouter une catégorie</a>
                    <a href="orders.php?status=en_attente" class="btn-primary">Commandes en attente</a>
                    <a href="users.php" class="btn-primary">Gérer les utilisateurs</a>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="footer-bottom">
                <p>&copy; <?php echo date('Y'); ?> Menalego - Interface d'administration</p>
            </div>
        </div>
    </footer>
</body>
</html>
