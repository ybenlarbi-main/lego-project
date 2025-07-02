<?php
require_once '../config/config.php';
requireAdmin();

try {
    // Get comprehensive dashboard statistics
    $stats = [];
    
    // Products statistics
    $stmt = $pdo->query("SELECT COUNT(*) FROM produits");
    $stats['total_products'] = $stmt->fetchColumn();
    
    $stmt = $pdo->query("SELECT COUNT(*) FROM produits WHERE statut = 'actif'");
    $stats['active_products'] = $stmt->fetchColumn();
    
    $stmt = $pdo->query("SELECT COUNT(*) FROM produits WHERE stock <= 5 AND stock > 0");
    $stats['low_stock_products'] = $stmt->fetchColumn();
    
    $stmt = $pdo->query("SELECT COUNT(*) FROM produits WHERE stock = 0");
    $stats['out_of_stock_products'] = $stmt->fetchColumn();
    
    // Categories statistics
    $stmt = $pdo->query("SELECT COUNT(*) FROM categories WHERE statut = 'actif'");
    $stats['total_categories'] = $stmt->fetchColumn();
    
    // Users statistics
    $stmt = $pdo->query("SELECT COUNT(*) FROM utilisateurs");
    $stats['total_users'] = $stmt->fetchColumn();
    
    $stmt = $pdo->query("SELECT COUNT(*) FROM utilisateurs WHERE statut = 'actif'");
    $stats['active_users'] = $stmt->fetchColumn();
    
    $stmt = $pdo->query("SELECT COUNT(*) FROM utilisateurs WHERE role = 'admin'");
    $stats['admin_users'] = $stmt->fetchColumn();
    
    // Orders statistics (if table exists)
    try {
        $stmt = $pdo->query("SELECT COUNT(*) FROM commandes");
        $stats['total_orders'] = $stmt->fetchColumn();
        
        $stmt = $pdo->query("SELECT COUNT(*) FROM commandes WHERE statut = 'en_attente'");
        $stats['pending_orders'] = $stmt->fetchColumn();
        
        $stmt = $pdo->query("SELECT COALESCE(SUM(total), 0) FROM commandes WHERE statut != 'annulee'");
        $stats['total_revenue'] = $stmt->fetchColumn();
        
        $stmt = $pdo->query("SELECT COALESCE(SUM(total), 0) FROM commandes WHERE DATE(date_commande) = CURDATE()");
        $stats['today_revenue'] = $stmt->fetchColumn();
    } catch (Exception $e) {
        $stats['total_orders'] = 0;
        $stats['pending_orders'] = 0;
        $stats['total_revenue'] = 0;
        $stats['today_revenue'] = 0;
    }
    
    // Recent products
    $stmt = $pdo->prepare("
        SELECT p.*, c.nom as categorie_nom 
        FROM produits p 
        LEFT JOIN categories c ON p.categorie_id = c.id 
        ORDER BY p.date_creation DESC 
        LIMIT 5
    ");
    $stmt->execute();
    $recent_products = $stmt->fetchAll();
    
    // Low stock products
    $stmt = $pdo->prepare("
        SELECT p.*, c.nom as categorie_nom 
        FROM produits p 
        LEFT JOIN categories c ON p.categorie_id = c.id 
        WHERE p.stock <= 5 AND p.stock > 0
        ORDER BY p.stock ASC 
        LIMIT 10
    ");
    $stmt->execute();
    $low_stock_products = $stmt->fetchAll();
    
    // Recent users
    $stmt = $pdo->prepare("
        SELECT * FROM utilisateurs 
        ORDER BY date_creation DESC 
        LIMIT 5
    ");
    $stmt->execute();
    $recent_users = $stmt->fetchAll();
    
} catch (Exception $e) {
    setFlashMessage("Erreur critique de la base de données : " . $e->getMessage(), "danger");
    $stats = [
        'total_products' => 0, 'active_products' => 0, 'low_stock_products' => 0, 'out_of_stock_products' => 0,
        'total_categories' => 0, 'total_users' => 0, 'active_users' => 0, 'admin_users' => 0,
        'total_orders' => 0, 'pending_orders' => 0, 'total_revenue' => 0, 'today_revenue' => 0
    ];
    $recent_products = [];
    $low_stock_products = [];
    $recent_users = [];
}

$page_title = "Dashboard Admin";
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - Admin</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body class="admin-body">
    <div class="admin-container">
        <!-- Sidebar -->
        <aside class="admin-sidebar">
            <div class="sidebar-header">
                <h2>Admin Panel</h2>
            </div>
            <nav class="sidebar-nav">
                <a href="index.php" class="nav-item active">
                    <span class="nav-icon">📊</span>
                    <span class="nav-text">Dashboard</span>
                </a>
                <a href="products.php" class="nav-item">
                    <span class="nav-icon">📦</span>
                    <span class="nav-text">Produits</span>
                </a>
                <a href="categories.php" class="nav-item">
                    <span class="nav-icon">📂</span>
                    <span class="nav-text">Catégories</span>
                </a>
                <a href="users.php" class="nav-item">
                    <span class="nav-icon">👥</span>
                    <span class="nav-text">Utilisateurs</span>
                </a>
                <a href="orders.php" class="nav-item">
                    <span class="nav-icon">🛒</span>
                    <span class="nav-text">Commandes</span>
                </a>
                <a href="../" class="nav-item">
                    <span class="nav-icon">🌐</span>
                    <span class="nav-text">Voir le site</span>
                </a>
                <a href="../auth/logout.php" class="nav-item">
                    <span class="nav-icon">🚪</span>
                    <span class="nav-text">Déconnexion</span>
                </a>
            </nav>
        </aside>

        <!-- Main Content -->
        <main class="admin-main">
            <div class="admin-header">
                <h1>Dashboard Administrateur</h1>
                <div class="admin-actions">
                    <span class="welcome-text">Bienvenue, <?php echo getCurrentUser()['prenom'] ?? 'Admin'; ?> !</span>
                </div>
            </div>

            <!-- Flash Messages -->
            <?php echo getFlashMessage(); ?>

            <!-- Statistics Grid -->
            <div class="stats-grid">
                <!-- Products Stats -->
                <div class="stat-card">
                    <div class="stat-header">
                        <h3 class="stat-title">Total Produits</h3>
                        <div class="stat-icon">📦</div>
                    </div>
                    <p class="stat-value"><?php echo $stats['total_products']; ?></p>
                    <p class="stat-change positive">
                        <?php echo $stats['active_products']; ?> actifs
                    </p>
                </div>
                
                <div class="stat-card">
                    <div class="stat-header">
                        <h3 class="stat-title">Stock Faible</h3>
                        <div class="stat-icon">⚠️</div>
                    </div>
                    <p class="stat-value"><?php echo $stats['low_stock_products']; ?></p>
                    <p class="stat-change <?php echo $stats['low_stock_products'] > 0 ? 'negative' : 'positive'; ?>">
                        <?php echo $stats['out_of_stock_products']; ?> épuisés
                    </p>
                </div>
                
                <div class="stat-card">
                    <div class="stat-header">
                        <h3 class="stat-title">Utilisateurs</h3>
                        <div class="stat-icon">👥</div>
                    </div>
                    <p class="stat-value"><?php echo $stats['total_users']; ?></p>
                    <p class="stat-change positive">
                        <?php echo $stats['active_users']; ?> actifs
                    </p>
                </div>
                
                <div class="stat-card">
                    <div class="stat-header">
                        <h3 class="stat-title">Catégories</h3>
                        <div class="stat-icon">📂</div>
                    </div>
                    <p class="stat-value"><?php echo $stats['total_categories']; ?></p>
                </div>
                
                <div class="stat-card">
                    <div class="stat-header">
                        <h3 class="stat-title">Commandes</h3>
                        <div class="stat-icon">🛒</div>
                    </div>
                    <p class="stat-value"><?php echo $stats['total_orders']; ?></p>
                    <p class="stat-change <?php echo $stats['pending_orders'] > 0 ? 'negative' : 'positive'; ?>">
                        <?php echo $stats['pending_orders']; ?> en attente
                    </p>
                </div>
                
                <div class="stat-card">
                    <div class="stat-header">
                        <h3 class="stat-title">Chiffre d'Affaires</h3>
                        <div class="stat-icon">💰</div>
                    </div>
                    <p class="stat-value"><?php echo formatPrice($stats['total_revenue']); ?></p>
                    <p class="stat-change positive">
                        Aujourd'hui: <?php echo formatPrice($stats['today_revenue']); ?>
                    </p>
                </div>
            </div>

            <!-- Content Grid -->
            <div class="dashboard-grid">
                <!-- Recent Products -->
                <div class="admin-card">
                    <div class="card-header">
                        <h3>Produits Récents</h3>
                        <a href="products.php" class="btn btn-sm btn-secondary">Voir tout</a>
                    </div>
                    <div class="table-container">
                        <table class="admin-table">
                            <thead>
                                <tr>
                                    <th>Produit</th>
                                    <th>Catégorie</th>
                                    <th>Prix</th>
                                    <th>Stock</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recent_products as $product): ?>
                                    <tr>
                                        <td>
                                            <div class="product-name">
                                                <strong><?php echo htmlspecialchars($product['nom']); ?></strong>
                                            </div>
                                        </td>
                                        <td><?php echo htmlspecialchars($product['categorie_nom'] ?? 'Sans catégorie'); ?></td>
                                        <td><?php echo formatPrice($product['prix']); ?></td>
                                        <td>
                                            <span class="badge <?php echo $product['stock'] <= 5 ? 'badge-warning' : 'badge-success'; ?>">
                                                <?php echo $product['stock']; ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Low Stock Alert -->
                <?php if (!empty($low_stock_products)): ?>
                <div class="admin-card">
                    <div class="card-header">
                        <h3>⚠️ Alertes Stock Faible</h3>
                        <a href="products.php?filter=low_stock" class="btn btn-sm btn-warning">Gérer</a>
                    </div>
                    <div class="table-container">
                        <table class="admin-table">
                            <thead>
                                <tr>
                                    <th>Produit</th>
                                    <th>Stock Restant</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($low_stock_products as $product): ?>
                                    <tr>
                                        <td>
                                            <div class="product-name">
                                                <strong><?php echo htmlspecialchars($product['nom']); ?></strong>
                                                <small><?php echo htmlspecialchars($product['categorie_nom'] ?? 'Sans catégorie'); ?></small>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="badge badge-danger">
                                                <?php echo $product['stock']; ?> restant(s)
                                            </span>
                                        </td>
                                        <td>
                                            <a href="products.php?edit=<?php echo $product['id']; ?>" class="btn btn-sm btn-primary">
                                                Réapprovisionner
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Recent Users -->
                <div class="admin-card">
                    <div class="card-header">
                        <h3>Nouveaux Utilisateurs</h3>
                        <a href="users.php" class="btn btn-sm btn-secondary">Voir tout</a>
                    </div>
                    <div class="table-container">
                        <table class="admin-table">
                            <thead>
                                <tr>
                                    <th>Nom</th>
                                    <th>Email</th>
                                    <th>Rôle</th>
                                    <th>Inscription</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recent_users as $user): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($user['prenom'] . ' ' . $user['nom']); ?></td>
                                        <td><?php echo htmlspecialchars($user['email']); ?></td>
                                        <td>
                                            <span class="badge badge-info"><?php echo ucfirst($user['role']); ?></span>
                                        </td>
                                        <td><?php echo date('d/m/Y', strtotime($user['date_creation'])); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="admin-card">
                    <div class="card-header">
                        <h3>Actions Rapides</h3>
                    </div>
                    <div class="modal-body">
                        <div class="quick-actions">
                            <a href="products.php" class="quick-action-btn">
                                <div class="action-icon">📦</div>
                                <div class="action-text">
                                    <strong>Ajouter un Produit</strong>
                                    <span>Nouveau produit au catalogue</span>
                                </div>
                            </a>
                            
                            <a href="categories.php" class="quick-action-btn">
                                <div class="action-icon">📂</div>
                                <div class="action-text">
                                    <strong>Gérer Catégories</strong>
                                    <span>Organiser les catégories</span>
                                </div>
                            </a>
                            
                            <a href="users.php" class="quick-action-btn">
                                <div class="action-icon">👥</div>
                                <div class="action-text">
                                    <strong>Voir Utilisateurs</strong>
                                    <span>Gérer les comptes</span>
                                </div>
                            </a>
                            
                            <a href="orders.php" class="quick-action-btn">
                                <div class="action-icon">🛒</div>
                                <div class="action-text">
                                    <strong>Traiter Commandes</strong>
                                    <span>Gérer les commandes</span>
                                </div>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <style>
        .welcome-text {
            color: #64748b;
            font-weight: 500;
        }

        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 2rem;
            margin-top: 2rem;
        }

        .quick-actions {
            display: grid;
            gap: 1rem;
        }

        .quick-action-btn {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 1rem;
            background: #f8fafc;
            border-radius: 0.5rem;
            text-decoration: none;
            color: inherit;
            transition: all 0.2s ease;
            border: 1px solid #e2e8f0;
        }

        .quick-action-btn:hover {
            background: #f1f5f9;
            border-color: #3b82f6;
            transform: translateY(-1px);
        }

        .action-icon {
            font-size: 2rem;
            width: 3rem;
            height: 3rem;
            display: flex;
            align-items: center;
            justify-content: center;
            background: white;
            border-radius: 0.5rem;
        }

        .action-text strong {
            display: block;
            color: #1f2937;
            font-weight: 600;
            margin-bottom: 0.25rem;
        }

        .action-text span {
            color: #6b7280;
            font-size: 0.875rem;
        }

        @media (max-width: 768px) {
            .dashboard-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</body>
</html>
