<?php
require_once 'config/config.php';

// Require login for orders
requireLogin();

$user_id = $_SESSION['user_id'];

// Pagination setup
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 10;
$offset = ($page - 1) * $per_page;

// Get total orders count
$stmt = $pdo->prepare("
    SELECT COUNT(*) as total FROM commandes 
    WHERE client_id = ?
");
$stmt->execute([$user_id]);
$total_orders = $stmt->fetch()['total'];
$total_pages = ceil($total_orders / $per_page);

// Get user orders with pagination
$stmt = $pdo->prepare("
    SELECT * FROM commandes 
    WHERE client_id = ? 
    ORDER BY date_commande DESC
    LIMIT ? OFFSET ?
");
$stmt->execute([$user_id, $per_page, $offset]);
$orders = $stmt->fetchAll();

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

$page_title = 'Mes Commandes';
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
        .orders-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem 1rem;
        }
        
        .orders-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }
        
        .orders-table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
        }
        
        .orders-table th, .orders-table td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid #f0f0f0;
        }
        
        .orders-table th {
            background-color: #f9fafb;
            font-weight: 600;
        }
        
        .orders-table tr:last-child td {
            border-bottom: none;
        }
        
        .order-number {
            font-weight: 600;
            color: #4f46e5;
        }
        
        .status-badge {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.875rem;
            font-weight: 500;
        }
        
        .view-order-btn {
            display: inline-block;
            padding: 0.5rem 1rem;
            background: #4f46e5;
            color: white;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 500;
            font-size: 0.875rem;
            transition: background-color 0.2s;
        }
        
        .view-order-btn:hover {
            background: #4338ca;
        }
        
        .empty-orders {
            background: white;
            padding: 3rem;
            text-align: center;
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
        }
        
        .empty-orders i {
            font-size: 4rem;
            color: #d1d5db;
            margin-bottom: 1.5rem;
        }
        
        .pagination {
            display: flex;
            justify-content: center;
            margin-top: 2rem;
            list-style: none;
            padding: 0;
        }
        
        .pagination li {
            margin: 0 0.25rem;
        }
        
        .pagination a, .pagination span {
            display: inline-block;
            padding: 0.5rem 1rem;
            border: 1px solid #e5e7eb;
            border-radius: 6px;
            background: white;
            color: #374151;
            text-decoration: none;
        }
        
        .pagination a:hover {
            background: #f3f4f6;
        }
        
        .pagination .active span {
            background: #4f46e5;
            color: white;
            border-color: #4f46e5;
        }
        
        .mobile-view {
            display: none;
        }
        
        @media (max-width: 768px) {
            .desktop-table {
                display: none;
            }
            
            .mobile-view {
                display: block;
            }
            
            .order-card {
                background: white;
                border-radius: 10px;
                box-shadow: 0 4px 12px rgba(0,0,0,0.05);
                padding: 1.5rem;
                margin-bottom: 1rem;
            }
            
            .order-card-header {
                display: flex;
                justify-content: space-between;
                align-items: center;
                margin-bottom: 1rem;
                padding-bottom: 0.75rem;
                border-bottom: 1px solid #f0f0f0;
            }
            
            .order-card-detail {
                display: flex;
                justify-content: space-between;
                margin-bottom: 0.75rem;
            }
            
            .order-card-label {
                color: #6b7280;
                font-size: 0.875rem;
            }
            
            .order-card-value {
                font-weight: 500;
                text-align: right;
            }
            
            .order-card-footer {
                margin-top: 1.5rem;
                text-align: right;
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
                <span class="current">Mes Commandes</span>
            </nav>
        </div>
    </div>

    <div class="orders-container">
        <div class="orders-header">
            <h2>Historique de vos commandes</h2>
        </div>

        <?php if (empty($orders)): ?>
            <div class="empty-orders">
                <i class="fas fa-shopping-bag"></i>
                <h3>Vous n'avez pas encore passé de commande</h3>
                <p>Parcourez notre catalogue et trouvez des produits qui vous plaisent.</p>
                <a href="produits.php" class="btn btn-primary mt-4">Découvrir nos produits</a>
            </div>
        <?php else: ?>
            <!-- Desktop view -->
            <div class="desktop-table">
                <table class="orders-table">
                    <thead>
                        <tr>
                            <th>Commande</th>
                            <th>Date</th>
                            <th>Statut</th>
                            <th>Total</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($orders as $order): ?>
                            <tr>
                                <td class="order-number"><?php echo htmlspecialchars($order['numero_commande']); ?></td>
                                <td><?php echo date('d/m/Y H:i', strtotime($order['date_commande'])); ?></td>
                                <td>
                                    <span class="status-badge <?php echo $status_colors[$order['statut']]; ?>">
                                        <?php echo $status_translations[$order['statut']]; ?>
                                    </span>
                                </td>
                                <td><?php echo formatPrice($order['total_ttc']); ?></td>
                                <td>
                                    <a href="order-details.php?id=<?php echo $order['id']; ?>" class="view-order-btn">
                                        Détails
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Mobile view -->
            <div class="mobile-view">
                <?php foreach ($orders as $order): ?>
                    <div class="order-card">
                        <div class="order-card-header">
                            <span class="order-number"><?php echo htmlspecialchars($order['numero_commande']); ?></span>
                            <span class="status-badge <?php echo $status_colors[$order['statut']]; ?>">
                                <?php echo $status_translations[$order['statut']]; ?>
                            </span>
                        </div>
                        <div class="order-card-detail">
                            <div class="order-card-label">Date</div>
                            <div class="order-card-value"><?php echo date('d/m/Y H:i', strtotime($order['date_commande'])); ?></div>
                        </div>
                        <div class="order-card-detail">
                            <div class="order-card-label">Total</div>
                            <div class="order-card-value"><?php echo formatPrice($order['total_ttc']); ?></div>
                        </div>
                        <div class="order-card-footer">
                            <a href="order-details.php?id=<?php echo $order['id']; ?>" class="view-order-btn">
                                Détails
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
                <ul class="pagination">
                    <?php if ($page > 1): ?>
                        <li>
                            <a href="?page=<?php echo $page - 1; ?>">
                                <i class="fas fa-chevron-left"></i>
                            </a>
                        </li>
                    <?php endif; ?>
                    
                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <li class="<?php echo ($i === $page) ? 'active' : ''; ?>">
                            <?php if ($i === $page): ?>
                                <span><?php echo $i; ?></span>
                            <?php else: ?>
                                <a href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                            <?php endif; ?>
                        </li>
                    <?php endfor; ?>
                    
                    <?php if ($page < $total_pages): ?>
                        <li>
                            <a href="?page=<?php echo $page + 1; ?>">
                                <i class="fas fa-chevron-right"></i>
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
            <?php endif; ?>
        <?php endif; ?>
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
