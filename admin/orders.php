<?php
require_once '../config/config.php';
requireAdmin();

// Handle order actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (isset($_POST['action'])) {
            switch ($_POST['action']) {
                case 'update_status':
                    $order_id = (int)$_POST['order_id'];
                    $new_status = sanitizeInput($_POST['status']);
                    
                    if (in_array($new_status, ['en_attente', 'confirmee', 'preparee', 'expediee', 'livree', 'annulee'])) {
                        $stmt = $pdo->prepare("UPDATE commandes SET statut = ? WHERE id = ?");
                        $stmt->execute([$new_status, $order_id]);
                        
                        setFlashMessage('Statut de la commande mis à jour avec succès !', 'success');
                    }
                    break;
                    
                case 'delete_order':
                    $order_id = (int)$_POST['order_id'];
                    
                    // First delete order items
                    $stmt = $pdo->prepare("DELETE FROM lignes_commandes WHERE commande_id = ?");
                    $stmt->execute([$order_id]);
                    
                    // Then delete order
                    $stmt = $pdo->prepare("DELETE FROM commandes WHERE id = ?");
                    $stmt->execute([$order_id]);
                    
                    setFlashMessage('Commande supprimée avec succès !', 'success');
                    break;
            }
        }
    } catch (Exception $e) {
        setFlashMessage('Erreur: ' . $e->getMessage(), 'danger');
    }
    
    header('Location: orders.php');
    exit;
}

// Get search and filter parameters
$search = isset($_GET['search']) ? sanitizeInput($_GET['search']) : '';
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$date_start = isset($_GET['date_start']) ? $_GET['date_start'] : '';
$date_end = isset($_GET['date_end']) ? $_GET['date_end'] : '';

// Build query with filters
$where_conditions = ["1=1"];
$params = [];

if ($search) {
    $where_conditions[] = "(c.id LIKE ? OR u.email LIKE ? OR CONCAT(u.prenom, ' ', u.nom) LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($status_filter && $status_filter !== 'all') {
    $where_conditions[] = "c.statut = ?";
    $params[] = $status_filter;
}

if ($date_start) {
    $where_conditions[] = "c.date_commande >= ?";
    $params[] = $date_start . ' 00:00:00';
}

if ($date_end) {
    $where_conditions[] = "c.date_commande <= ?";
    $params[] = $date_end . ' 23:59:59';
}

$where_clause = implode(' AND ', $where_conditions);

// Get orders with pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 20;
$offset = ($page - 1) * $limit;

$stmt = $pdo->prepare("
    SELECT c.*, 
           u.prenom, u.nom, u.email
    FROM commandes c 
    LEFT JOIN utilisateurs u ON c.client_id = u.id 
    WHERE $where_clause
    ORDER BY c.date_commande DESC
    LIMIT ?, ?
");
$params[] = $offset;
$params[] = $limit;
$stmt->execute($params);
$orders = $stmt->fetchAll();

// Get total count for pagination
$stmt = $pdo->prepare("
    SELECT COUNT(*) FROM commandes c 
    LEFT JOIN utilisateurs u ON c.client_id = u.id 
    WHERE $where_clause
");
array_pop($params);
array_pop($params);
$stmt->execute($params);
$total_orders = $stmt->fetchColumn();
$total_pages = ceil($total_orders / $limit);

// Get order statistics
$stats = [
    'total' => 0,
    'en_attente' => 0,
    'confirmee' => 0,
    'preparee' => 0,
    'expediee' => 0,
    'livree' => 0,
    'annulee' => 0,
    'total_revenue' => 0
];

$stmt = $pdo->query("
    SELECT statut, COUNT(*) as count, SUM(total) as revenue
    FROM commandes
    GROUP BY statut
");
$stats_results = $stmt->fetchAll();

foreach ($stats_results as $result) {
    $stats[$result['statut']] = $result['count'];
    $stats['total'] += $result['count'];
    
    if ($result['statut'] != 'annulee') {
        $stats['total_revenue'] += $result['revenue'];
    }
}

$page_title = "Gestion des Commandes";

// Function to get status badge class
function getStatusBadgeClass($status) {
    switch ($status) {
        case 'en_attente':
            return 'badge-warning';
        case 'confirmee':
            return 'badge-info';
        case 'preparee':
            return 'badge-primary';
        case 'expediee':
            return 'badge-primary';
        case 'livree':
            return 'badge-success';
        case 'annulee':
            return 'badge-danger';
        default:
            return 'badge-secondary';
    }
}

// Function to get status text
function getStatusText($status) {
    switch ($status) {
        case 'en_attente':
            return 'En attente';
        case 'confirmee':
            return 'Confirmée';
        case 'preparee':
            return 'Préparée';
        case 'expediee':
            return 'Expédiée';
        case 'livree':
            return 'Livrée';
        case 'annulee':
            return 'Annulée';
        default:
            return 'Inconnu';
    }
}
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
                <a href="index.php" class="nav-item">
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
                <a href="orders.php" class="nav-item active">
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
                <h1><?php echo $page_title; ?></h1>
            </div>

            <!-- Flash Messages -->
            <?php echo getFlashMessage(); ?>

            <!-- Statistics Cards -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-header">
                        <h3 class="stat-title">Total Commandes</h3>
                        <div class="stat-icon">🛒</div>
                    </div>
                    <p class="stat-value"><?php echo $stats['total']; ?></p>
                </div>
                
                <div class="stat-card">
                    <div class="stat-header">
                        <h3 class="stat-title">En Attente</h3>
                        <div class="stat-icon">⏳</div>
                    </div>
                    <p class="stat-value"><?php echo $stats['en_attente']; ?></p>
                </div>
                
                <div class="stat-card">
                    <div class="stat-header">
                        <h3 class="stat-title">Préparées</h3>
                        <div class="stat-icon">📦</div>
                    </div>
                    <p class="stat-value"><?php echo $stats['preparee']; ?></p>
                </div>
                
                <div class="stat-card">
                    <div class="stat-header">
                        <h3 class="stat-title">Livrées</h3>
                        <div class="stat-icon">✅</div>
                    </div>
                    <p class="stat-value"><?php echo $stats['livree']; ?></p>
                </div>
                
                <div class="stat-card">
                    <div class="stat-header">
                        <h3 class="stat-title">Chiffre d'affaires</h3>
                        <div class="stat-icon">💰</div>
                    </div>
                    <p class="stat-value"><?php echo formatPrice($stats['total_revenue']); ?></p>
                </div>
            </div>

            <!-- Filters -->
            <div class="admin-card">
                <div class="card-header">
                    <h3>Filtres et Recherche</h3>
                </div>
                <div class="modal-body">
                    <form method="GET" class="filters-form">
                        <div class="form-grid">
                            <div class="form-group">
                                <label for="search">Rechercher</label>
                                <input type="text" id="search" name="search" 
                                       value="<?php echo htmlspecialchars($search); ?>" 
                                       placeholder="ID, email ou nom...">
                            </div>
                            
                            <div class="form-group">
                                <label for="status">Statut</label>
                                <select id="status" name="status">
                                    <option value="all">Tous les statuts</option>
                                    <option value="en_attente" <?php echo $status_filter === 'en_attente' ? 'selected' : ''; ?>>En attente</option>
                                    <option value="confirmee" <?php echo $status_filter === 'confirmee' ? 'selected' : ''; ?>>Confirmée</option>
                                    <option value="preparee" <?php echo $status_filter === 'preparee' ? 'selected' : ''; ?>>Préparée</option>
                                    <option value="expediee" <?php echo $status_filter === 'expediee' ? 'selected' : ''; ?>>Expédiée</option>
                                    <option value="livree" <?php echo $status_filter === 'livree' ? 'selected' : ''; ?>>Livrée</option>
                                    <option value="annulee" <?php echo $status_filter === 'annulee' ? 'selected' : ''; ?>>Annulée</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="date_start">Date début</label>
                                <input type="date" id="date_start" name="date_start" value="<?php echo $date_start; ?>">
                            </div>
                            
                            <div class="form-group">
                                <label for="date_end">Date fin</label>
                                <input type="date" id="date_end" name="date_end" value="<?php echo $date_end; ?>">
                            </div>
                            
                            <div class="form-group">
                                <label>&nbsp;</label>
                                <div class="filter-buttons">
                                    <button type="submit" class="btn btn-primary">Filtrer</button>
                                    <a href="orders.php" class="btn btn-secondary">Réinitialiser</a>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Orders Table -->
            <div class="admin-card">
                <div class="card-header">
                    <h3>Liste des Commandes</h3>
                    <div class="table-info">
                        <span><?php echo count($orders); ?> commande(s) sur <?php echo $total_orders; ?></span>
                    </div>
                </div>
                
                <div class="table-container">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Client</th>
                                <th>Date</th>
                                <th>Total</th>
                                <th>Statut</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($orders)): ?>
                                <tr>
                                    <td colspan="6" class="text-center">Aucune commande trouvée</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($orders as $order): ?>
                                    <tr>
                                        <td>#<?php echo $order['id']; ?></td>
                                        <td>
                                            <div class="user-info">
                                                <strong><?php echo htmlspecialchars($order['prenom'] . ' ' . $order['nom']); ?></strong>
                                                <small><?php echo htmlspecialchars($order['email']); ?></small>
                                            </div>
                                        </td>
                                        <td><?php echo date('d/m/Y H:i', strtotime($order['date_commande'])); ?></td>
                                        <td><strong><?php echo formatPrice($order['total']); ?></strong></td>
                                        <td>
                                            <form method="POST" style="display: inline;" onchange="this.submit()">
                                                <input type="hidden" name="action" value="update_status">
                                                <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                                <select name="status" class="status-select">
                                                    <option value="en_attente" <?php echo $order['statut'] === 'en_attente' ? 'selected' : ''; ?>>En attente</option>
                                                    <option value="confirmee" <?php echo $order['statut'] === 'confirmee' ? 'selected' : ''; ?>>Confirmée</option>
                                                    <option value="preparee" <?php echo $order['statut'] === 'preparee' ? 'selected' : ''; ?>>Préparée</option>
                                                    <option value="expediee" <?php echo $order['statut'] === 'expediee' ? 'selected' : ''; ?>>Expédiée</option>
                                                    <option value="livree" <?php echo $order['statut'] === 'livree' ? 'selected' : ''; ?>>Livrée</option>
                                                    <option value="annulee" <?php echo $order['statut'] === 'annulee' ? 'selected' : ''; ?>>Annulée</option>
                                                </select>
                                            </form>
                                        </td>
                                        <td>
                                            <div class="action-buttons">
                                                <button class="btn btn-sm btn-secondary" onclick="viewOrder(<?php echo $order['id']; ?>)">
                                                    👁️ Voir
                                                </button>
                                                <button class="btn btn-sm btn-danger" onclick="deleteOrder(<?php echo $order['id']; ?>)">
                                                    🗑️ Supprimer
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                    <div class="pagination">
                        <?php if ($page > 1): ?>
                            <a href="?page=<?php echo ($page - 1); ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status_filter); ?>&date_start=<?php echo urlencode($date_start); ?>&date_end=<?php echo urlencode($date_end); ?>" class="pagination-btn">
                                &laquo; Précédent
                            </a>
                        <?php endif; ?>
                        
                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                            <?php if (abs($page - $i) < 3 || $i == 1 || $i == $total_pages): ?>
                                <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status_filter); ?>&date_start=<?php echo urlencode($date_start); ?>&date_end=<?php echo urlencode($date_end); ?>" class="pagination-btn <?php echo $i == $page ? 'active' : ''; ?>">
                                    <?php echo $i; ?>
                                </a>
                                <?php if (($i == 1 && $page > 3) || ($i == $total_pages - 1 && $page < $total_pages - 2)): ?>
                                    <span class="pagination-dots">...</span>
                                <?php endif; ?>
                            <?php endif; ?>
                        <?php endfor; ?>
                        
                        <?php if ($page < $total_pages): ?>
                            <a href="?page=<?php echo ($page + 1); ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status_filter); ?>&date_start=<?php echo urlencode($date_start); ?>&date_end=<?php echo urlencode($date_end); ?>" class="pagination-btn">
                                Suivant &raquo;
                            </a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <!-- Order Details Modal -->
    <div class="modal" id="orderModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="modalTitle">Détails de la commande #<span id="orderId"></span></h3>
                <button class="modal-close" onclick="closeOrderModal()">&times;</button>
            </div>
            <div class="modal-body" id="orderDetails">
                <div class="loading">Chargement...</div>
            </div>
            <div class="modal-actions">
                <button type="button" class="btn btn-secondary" onclick="closeOrderModal()">Fermer</button>
                <button type="button" class="btn btn-primary" onclick="printOrder()">Imprimer</button>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal" id="deleteModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Confirmer la suppression</h3>
                <button class="modal-close" onclick="closeDeleteModal()">&times;</button>
            </div>
            <div class="modal-body">
                <p>Êtes-vous sûr de vouloir supprimer la commande #<span id="deleteOrderId"></span> ?</p>
                <p class="text-danger">Cette action est irréversible.</p>
            </div>
            <div class="modal-actions">
                <button type="button" class="btn btn-secondary" onclick="closeDeleteModal()">Annuler</button>
                <form method="POST" style="display: inline;">
                    <input type="hidden" name="action" value="delete_order">
                    <input type="hidden" name="order_id" id="deleteOrderIdInput">
                    <button type="submit" class="btn btn-danger">Supprimer</button>
                </form>
            </div>
        </div>
    </div>

    <script>
        function viewOrder(orderId) {
            document.getElementById('orderId').textContent = orderId;
            document.getElementById('orderDetails').innerHTML = '<div class="loading">Chargement...</div>';
            document.getElementById('orderModal').style.display = 'flex';
            
            // Fetch order details
            fetch('ajax/get_order_details.php?id=' + orderId)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        document.getElementById('orderDetails').innerHTML = data.html;
                    } else {
                        document.getElementById('orderDetails').innerHTML = '<div class="error">Erreur: ' + data.message + '</div>';
                    }
                })
                .catch(error => {
                    document.getElementById('orderDetails').innerHTML = '<div class="error">Erreur lors de la récupération des détails</div>';
                    console.error('Error:', error);
                });
        }
        
        function deleteOrder(orderId) {
            document.getElementById('deleteOrderId').textContent = orderId;
            document.getElementById('deleteOrderIdInput').value = orderId;
            document.getElementById('deleteModal').style.display = 'flex';
        }
        
        function closeOrderModal() {
            document.getElementById('orderModal').style.display = 'none';
        }
        
        function closeDeleteModal() {
            document.getElementById('deleteModal').style.display = 'none';
        }
        
        function printOrder() {
            const orderId = document.getElementById('orderId').textContent;
            window.open('print_order.php?id=' + orderId, '_blank');
        }
        
        // Close modals when clicking outside
        window.onclick = function(event) {
            const orderModal = document.getElementById('orderModal');
            const deleteModal = document.getElementById('deleteModal');
            
            if (event.target == orderModal) {
                closeOrderModal();
            }
            if (event.target == deleteModal) {
                closeDeleteModal();
            }
        }
    </script>

    <style>
        .filters-form {
            background: #f8fafc;
            padding: 1.5rem;
            border-radius: 0.5rem;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            align-items: end;
        }

        .filter-buttons {
            display: flex;
            gap: 0.5rem;
        }
        
        .status-select {
            padding: 0.25rem 0.5rem;
            border: 1px solid #d1d5db;
            border-radius: 0.25rem;
            font-size: 0.75rem;
        }
        
        .user-info strong {
            display: block;
            color: #1f2937;
        }
        
        .user-info small {
            display: block;
            color: #6b7280;
            font-size: 0.75rem;
        }
        
        .pagination {
            display: flex;
            justify-content: center;
            gap: 0.5rem;
            padding: 1.5rem;
            flex-wrap: wrap;
        }
        
        .pagination-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0.5rem 0.75rem;
            background: #f1f5f9;
            color: #1f2937;
            text-decoration: none;
            border-radius: 0.25rem;
            min-width: 2rem;
            font-weight: 500;
            transition: all 0.2s ease;
        }
        
        .pagination-btn:hover {
            background: #e2e8f0;
        }
        
        .pagination-btn.active {
            background: #3b82f6;
            color: white;
        }
        
        .pagination-dots {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0.5rem;
        }
        
        .loading {
            padding: 2rem;
            text-align: center;
            color: #6b7280;
        }
        
        .error {
            padding: 1rem;
            background: #fee2e2;
            color: #991b1b;
            border-radius: 0.5rem;
            margin: 1rem 0;
        }
        
        .text-center {
            text-align: center;
            padding: 2rem;
            color: #6b7280;
        }
    </style>
</body>
</html>
