<?php
require_once '../config/config.php';

// Check if user is admin
requireAdmin();

// Initialize variables to hold data
$stats = [];
$recent_orders = [];
$low_stock = [];

try {
    // --- STATISTICS ---
    // Total products
    $stmt_products = $pdo->prepare("SELECT COUNT(*) as total FROM produits");
    $stmt_products->execute();
    $result_products = $stmt_products->fetch();
    $stats['products'] = $result_products ? $result_products['total'] : 0;

    // Total users
    $stmt_users = $pdo->prepare("SELECT COUNT(*) as total FROM utilisateurs WHERE role = 'client'");
    $stmt_users->execute();
    $result_users = $stmt_users->fetch();
    $stats['users'] = $result_users ? $result_users['total'] : 0;

    // Total orders
    $stmt_orders = $pdo->prepare("SELECT COUNT(*) as total FROM commandes");
    $stmt_orders->execute();
    $result_orders = $stmt_orders->fetch();
    $stats['orders'] = $result_orders ? $result_orders['total'] : 0;

    // Total revenue
    $stmt_revenue = $pdo->prepare("SELECT SUM(total_ttc) as total FROM commandes WHERE statut IN ('confirmee', 'preparee', 'expediee', 'livree')");
    $stmt_revenue->execute();
    $result_revenue = $stmt_revenue->fetch();
    $stats['revenue'] = $result_revenue['total'] ?: 0;
    
    // --- RECENT ORDERS ---
    $stmt_recent_orders = $pdo->prepare("
        SELECT c.*, u.nom, u.prenom 
        FROM commandes c 
        LEFT JOIN utilisateurs u ON c.client_id = u.id 
        ORDER BY c.date_commande DESC 
        LIMIT 5
    ");
    $stmt_recent_orders->execute();
    $recent_orders = $stmt_recent_orders->fetchAll();

    // --- LOW STOCK PRODUCTS ---
    $stmt_low_stock = $pdo->prepare("
        SELECT * FROM produits 
        WHERE stock < 5 AND statut = 'actif' 
        ORDER BY stock ASC 
        LIMIT 5
    ");
    $stmt_low_stock->execute();
    $low_stock = $stmt_low_stock->fetchAll();

} catch (PDOException $e) {
    // If ANY query fails, set all data to safe defaults
    $stats = ['products' => 0, 'users' => 0, 'orders' => 0, 'revenue' => 0];
    $recent_orders = [];
    $low_stock = [];
    // Show a clear error message to the admin
    setFlashMessage("Erreur critique de la base de données : " . $e->getMessage(), "danger");
}

$page_title = 'Dashboard Admin';

// Function to map order statuses to badge classes
function getStatusBadgeClass($status) {
    switch (strtolower($status)) {
        case 'livree':
            return 'badge-delivered';
        case 'expediee':
            return 'badge-shipped';
        case 'preparee':
            return 'badge-processing';
        case 'confirmee':
            return 'badge-confirmed';
        case 'annulee':
            return 'badge-cancelled';
        default:
            return 'badge-pending';
    }
}
?>

<!DOCTYPE html>
<html lang="<?php echo getCurrentLanguage(); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
</head>
<body>
    <!-- Header -->
    <header class="header">
        <div class="container">
            <div class="header-content">
                <a href="<?php echo SITE_URL; ?>/admin" class="logo">
                    <div class="logo-icon">M</div>
                    Menalego - Admin
                </a>
                
                <div class="user-actions">
                    <a href="<?php echo SITE_URL; ?>" class="user-btn" target="_blank">Voir le site</a>
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
            
            <!-- Recent Activity using new .admin-grid class -->
            <div class="admin-grid">
                <!-- Recent Orders -->
                <div class="admin-dashboard">
                    <h3>Commandes récentes</h3>
                    <?php if (empty($recent_orders)): ?>
                        <p class="empty-state-message">Aucune commande pour le moment.</p>
                    <?php else: ?>
                        <div class="table-responsive">
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
                                            <td><a href="orders.php?id=<?php echo $order['id']; ?>"><strong><?php echo htmlspecialchars($order['numero_commande']); ?></strong></a></td>
                                            <td><?php echo htmlspecialchars($order['prenom'] . ' ' . $order['nom']); ?></td>
                                            <td><?php echo formatPrice($order['total_ttc']); ?></td>
                                            <td>
                                                <span class="badge <?php echo getStatusBadgeClass($order['statut']); ?>">
                                                    <?php echo ucfirst($order['statut']); ?>
                                                </span>
                                            </td>
                                            <td><?php echo date('d/m/Y', strtotime($order['date_commande'])); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <div class="text-center">
                            <a href="orders.php" class="btn-primary">Voir toutes les commandes</a>
                        </div>
                    <?php endif; ?>
                </div>
                
                <!-- Low Stock Alert -->
                <div class="admin-dashboard">
                    <h3>Stock faible</h3>
                    <?php if (empty($low_stock)): ?>
                        <p class="empty-state-message">Tous les produits ont un stock suffisant.</p>
                    <?php else: ?>
                         <div class="table-responsive">
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
                                                <span class="stock-level <?php echo $product['stock'] == 0 ? 'out-of-stock' : 'low'; ?>">
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
                        </div>
                        <div class="text-center">
                            <a href="products.php" class="btn-primary">Gérer les produits</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Quick Actions -->
            <div class="admin-dashboard" style="margin-top: 2rem;">
                <h3>Actions rapides</h3>
                <div class="quick-actions-grid">
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
                <p>© <?php echo date('Y'); ?> Menalego - Interface d'administration</p>
            </div>
        </div>
    </footer>
</body>
</html>