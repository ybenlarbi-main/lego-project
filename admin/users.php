<?php
require_once '../config/config.php';
requireAdmin();

// Handle user actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (isset($_POST['action'])) {
            switch ($_POST['action']) {
                case 'update_role':
                    $user_id = (int)$_POST['user_id'];
                    $new_role = $_POST['role'];
                    
                    if (in_array($new_role, ['client', 'vendor', 'admin'])) {
                        $stmt = $pdo->prepare("UPDATE utilisateurs SET role = ? WHERE id = ?");
                        $stmt->execute([$new_role, $user_id]);
                        setFlashMessage('Rôle utilisateur mis à jour avec succès !', 'success');
                    }
                    break;
                    
                case 'toggle_status':
                    $user_id = (int)$_POST['user_id'];
                    $new_status = $_POST['statut'];
                    
                    if (in_array($new_status, ['actif', 'inactif', 'suspendu'])) {
                        $stmt = $pdo->prepare("UPDATE utilisateurs SET statut = ? WHERE id = ?");
                        $stmt->execute([$new_status, $user_id]);
                        setFlashMessage('Statut utilisateur mis à jour avec succès !', 'success');
                    }
                    break;
                    
                case 'delete_user':
                    $user_id = (int)$_POST['user_id'];
                    
                    // Check if user has orders
                    $stmt = $pdo->prepare("SELECT COUNT(*) FROM commandes WHERE user_id = ?");
                    $stmt->execute([$user_id]);
                    $order_count = $stmt->fetchColumn();
                    
                    if ($order_count > 0) {
                        setFlashMessage("Impossible de supprimer cet utilisateur car il a $order_count commande(s).", 'danger');
                    } else {
                        // Delete related data first
                        $pdo->prepare("DELETE FROM panier WHERE user_id = ?")->execute([$user_id]);
                        $pdo->prepare("DELETE FROM avis WHERE user_id = ?")->execute([$user_id]);
                        
                        // Delete user
                        $stmt = $pdo->prepare("DELETE FROM utilisateurs WHERE id = ?");
                        $stmt->execute([$user_id]);
                        
                        setFlashMessage('Utilisateur supprimé avec succès !', 'success');
                    }
                    break;
            }
        }
    } catch (Exception $e) {
        setFlashMessage('Erreur: ' . $e->getMessage(), 'danger');
    }
    
    header('Location: users.php');
    exit;
}

// Get search and filter parameters
$search = isset($_GET['search']) ? sanitizeInput($_GET['search']) : '';
$role_filter = isset($_GET['role']) ? $_GET['role'] : '';
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';

// Build query with filters
$where_conditions = ["1=1"];
$params = [];

if ($search) {
    $where_conditions[] = "(nom LIKE ? OR prenom LIKE ? OR email LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($role_filter && $role_filter !== 'all') {
    $where_conditions[] = "role = ?";
    $params[] = $role_filter;
}

if ($status_filter && $status_filter !== 'all') {
    $where_conditions[] = "statut = ?";
    $params[] = $status_filter;
}

$where_clause = implode(' AND ', $where_conditions);

// Get users with order counts
$stmt = $pdo->prepare("
    SELECT u.*, 
           COUNT(DISTINCT c.id) as order_count,
           COALESCE(SUM(c.total_ttc), 0) as total_spent,
           MAX(c.date_commande) as last_order_date
    FROM utilisateurs u 
    LEFT JOIN commandes c ON u.id = c.client_id 
    WHERE $where_clause
    GROUP BY u.id 
    ORDER BY u.date_creation DESC
");
$stmt->execute($params);
$users = $stmt->fetchAll();

// Get user statistics
$stats = [
    'total' => 0,
    'actif' => 0,
    'inactif' => 0,
    'suspendu' => 0,
    'clients' => 0,
    'vendors' => 0,
    'admins' => 0
];

$all_users_stmt = $pdo->query("SELECT role, statut FROM utilisateurs");
$all_users = $all_users_stmt->fetchAll();

foreach ($all_users as $user) {
    $stats['total']++;
    $stats[$user['statut']]++;
    $stats[$user['role'] . 's']++;
}

$page_title = "Gestion des Utilisateurs";
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
                <a href="users.php" class="nav-item active">
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
                <h1><?php echo $page_title; ?></h1>
            </div>

            <!-- Flash Messages -->
            <?php echo getFlashMessage(); ?>

            <!-- Statistics Cards -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-header">
                        <h3 class="stat-title">Total Utilisateurs</h3>
                        <div class="stat-icon">👥</div>
                    </div>
                    <p class="stat-value"><?php echo $stats['total']; ?></p>
                </div>
                
                <div class="stat-card">
                    <div class="stat-header">
                        <h3 class="stat-title">Utilisateurs Actifs</h3>
                        <div class="stat-icon">✅</div>
                    </div>
                    <p class="stat-value"><?php echo $stats['actif']; ?></p>
                </div>
                
                <div class="stat-card">
                    <div class="stat-header">
                        <h3 class="stat-title">Clients</h3>
                        <div class="stat-icon">👤</div>
                    </div>
                    <p class="stat-value"><?php echo $stats['clients']; ?></p>
                </div>
                
                <div class="stat-card">
                    <div class="stat-header">
                        <h3 class="stat-title">Administrateurs</h3>
                        <div class="stat-icon">⚙️</div>
                    </div>
                    <p class="stat-value"><?php echo $stats['admins']; ?></p>
                </div>
            </div>

            <!-- Filters -->
            <div class="admin-card">
                <div class="card-header">
                    <h3>Filtres et Recherche</h3>
                </div>
                <div class="modal-body">
                    <form method="GET" class="filters-form">
                        <div class="form-row">
                            <div class="form-group">
                                <label for="search">Rechercher</label>
                                <input type="text" id="search" name="search" 
                                       value="<?php echo htmlspecialchars($search); ?>" 
                                       placeholder="Nom, prénom ou email...">
                            </div>
                            
                            <div class="form-group">
                                <label for="role">Rôle</label>
                                <select id="role" name="role">
                                    <option value="all">Tous les rôles</option>
                                    <option value="client" <?php echo $role_filter === 'client' ? 'selected' : ''; ?>>Client</option>
                                    <option value="vendor" <?php echo $role_filter === 'vendor' ? 'selected' : ''; ?>>Vendeur</option>
                                    <option value="admin" <?php echo $role_filter === 'admin' ? 'selected' : ''; ?>>Admin</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="status">Statut</label>
                                <select id="status" name="status">
                                    <option value="all">Tous les statuts</option>
                                    <option value="actif" <?php echo $status_filter === 'actif' ? 'selected' : ''; ?>>Actif</option>
                                    <option value="inactif" <?php echo $status_filter === 'inactif' ? 'selected' : ''; ?>>Inactif</option>
                                    <option value="suspendu" <?php echo $status_filter === 'suspendu' ? 'selected' : ''; ?>>Suspendu</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label>&nbsp;</label>
                                <div class="filter-buttons">
                                    <button type="submit" class="btn btn-primary">Filtrer</button>
                                    <a href="users.php" class="btn btn-secondary">Réinitialiser</a>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Users Table -->
            <div class="admin-card">
                <div class="card-header">
                    <h3>Liste des Utilisateurs</h3>
                    <div class="table-info">
                        <span><?php echo count($users); ?> utilisateur(s) trouvé(s)</span>
                    </div>
                </div>
                
                <div class="table-container">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>Utilisateur</th>
                                <th>Email</th>
                                <th>Rôle</th>
                                <th>Statut</th>
                                <th>Commandes</th>
                                <th>Total Dépensé</th>
                                <th>Inscription</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $user): ?>
                                <tr>
                                    <td>
                                        <div class="user-info">
                                            <strong><?php echo htmlspecialchars($user['prenom'] . ' ' . $user['nom']); ?></strong>
                                            <?php if ($user['telephone']): ?>
                                                <small><?php echo htmlspecialchars($user['telephone']); ?></small>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td><?php echo htmlspecialchars($user['email']); ?></td>
                                    <td>
                                        <form method="POST" style="display: inline;" onchange="this.submit()">
                                            <input type="hidden" name="action" value="update_role">
                                            <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                            <select name="role" class="role-select">
                                                <option value="client" <?php echo $user['role'] === 'client' ? 'selected' : ''; ?>>Client</option>
                                                <option value="vendor" <?php echo $user['role'] === 'vendor' ? 'selected' : ''; ?>>Vendeur</option>
                                                <option value="admin" <?php echo $user['role'] === 'admin' ? 'selected' : ''; ?>>Admin</option>
                                            </select>
                                        </form>
                                    </td>
                                    <td>
                                        <form method="POST" style="display: inline;" onchange="this.submit()">
                                            <input type="hidden" name="action" value="toggle_status">
                                            <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                            <select name="statut" class="status-select">
                                                <option value="actif" <?php echo $user['statut'] === 'actif' ? 'selected' : ''; ?>>Actif</option>
                                                <option value="inactif" <?php echo $user['statut'] === 'inactif' ? 'selected' : ''; ?>>Inactif</option>
                                                <option value="suspendu" <?php echo $user['statut'] === 'suspendu' ? 'selected' : ''; ?>>Suspendu</option>
                                            </select>
                                        </form>
                                    </td>
                                    <td>
                                        <span class="badge badge-info"><?php echo $user['order_count']; ?> commandes</span>
                                        <?php if ($user['last_order_date']): ?>
                                            <small class="last-order">Dernière: <?php echo date('d/m/Y', strtotime($user['last_order_date'])); ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <strong><?php echo formatPrice($user['total_spent']); ?></strong>
                                    </td>
                                    <td><?php echo date('d/m/Y', strtotime($user['date_creation'])); ?></td>
                                    <td>
                                        <div class="action-buttons">
                                            <button class="btn btn-sm btn-secondary" onclick="viewUser(<?php echo $user['id']; ?>)">
                                                👁️ Voir
                                            </button>
                                            <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                                <button class="btn btn-sm btn-danger" onclick="deleteUser(<?php echo $user['id']; ?>, '<?php echo addslashes($user['prenom'] . ' ' . $user['nom']); ?>')">
                                                    🗑️ Supprimer
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>

    <!-- User Details Modal -->
    <div class="modal" id="userModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Détails de l'utilisateur</h3>
                <button class="modal-close" onclick="closeUserModal()">&times;</button>
            </div>
            <div class="modal-body" id="userDetails">
                <!-- User details will be loaded here -->
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
                <p>Êtes-vous sûr de vouloir supprimer l'utilisateur <strong id="deleteUserName"></strong> ?</p>
                <p class="text-danger">Cette action supprimera également toutes les données associées (panier, avis, etc.).</p>
            </div>
            <div class="modal-actions">
                <button type="button" class="btn btn-secondary" onclick="closeDeleteModal()">Annuler</button>
                <form method="POST" style="display: inline;">
                    <input type="hidden" name="action" value="delete_user">
                    <input type="hidden" name="user_id" id="deleteUserId">
                    <button type="submit" class="btn btn-danger">Supprimer</button>
                </form>
            </div>
        </div>
    </div>

    <script>
        const users = <?php echo json_encode($users); ?>;

        function viewUser(userId) {
            const user = users.find(u => u.id == userId);
            if (user) {
                const details = `
                    <div class="user-details">
                        <div class="detail-row">
                            <strong>Nom complet:</strong> ${user.prenom} ${user.nom}
                        </div>
                        <div class="detail-row">
                            <strong>Email:</strong> ${user.email}
                        </div>
                        <div class="detail-row">
                            <strong>Téléphone:</strong> ${user.telephone || 'Non renseigné'}
                        </div>
                        <div class="detail-row">
                            <strong>Adresse:</strong> ${user.adresse || 'Non renseignée'}
                        </div>
                        <div class="detail-row">
                            <strong>Ville:</strong> ${user.ville || 'Non renseignée'}
                        </div>
                        <div class="detail-row">
                            <strong>Rôle:</strong> <span class="badge badge-info">${user.role}</span>
                        </div>
                        <div class="detail-row">
                            <strong>Statut:</strong> <span class="status-badge status-${user.statut}">${user.statut}</span>
                        </div>
                        <div class="detail-row">
                            <strong>Date d'inscription:</strong> ${new Date(user.date_creation).toLocaleDateString('fr-FR')}
                        </div>
                        <div class="detail-row">
                            <strong>Nombre de commandes:</strong> ${user.order_count}
                        </div>
                        <div class="detail-row">
                            <strong>Total dépensé:</strong> ${formatPrice(user.total_spent)}
                        </div>
                        ${user.last_order_date ? `
                        <div class="detail-row">
                            <strong>Dernière commande:</strong> ${new Date(user.last_order_date).toLocaleDateString('fr-FR')}
                        </div>
                        ` : ''}
                    </div>
                `;
                
                document.getElementById('userDetails').innerHTML = details;
                document.getElementById('userModal').style.display = 'flex';
            }
        }

        function deleteUser(userId, userName) {
            document.getElementById('deleteUserId').value = userId;
            document.getElementById('deleteUserName').textContent = userName;
            document.getElementById('deleteModal').style.display = 'flex';
        }

        function closeUserModal() {
            document.getElementById('userModal').style.display = 'none';
        }

        function closeDeleteModal() {
            document.getElementById('deleteModal').style.display = 'none';
        }

        function formatPrice(price) {
            return new Intl.NumberFormat('fr-MA', {
                style: 'currency',
                currency: 'MAD',
                minimumFractionDigits: 2
            }).format(price).replace('MAD', 'DH');
        }

        // Close modals when clicking outside
        window.onclick = function(event) {
            const userModal = document.getElementById('userModal');
            const deleteModal = document.getElementById('deleteModal');
            
            if (event.target == userModal) {
                closeUserModal();
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

        .form-row {
            display: grid;
            grid-template-columns: 2fr 1fr 1fr auto;
            gap: 1rem;
            align-items: end;
        }

        .filter-buttons {
            display: flex;
            gap: 0.5rem;
        }

        .role-select, .status-select {
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

        .last-order {
            display: block;
            color: #6b7280;
            font-size: 0.75rem;
            margin-top: 0.25rem;
        }

        .user-details {
            background: #f9fafb;
            padding: 1rem;
            border-radius: 0.5rem;
        }

        .detail-row {
            padding: 0.5rem 0;
            border-bottom: 1px solid #e5e7eb;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .detail-row:last-child {
            border-bottom: none;
        }

        @media (max-width: 768px) {
            .form-row {
                grid-template-columns: 1fr;
            }
            
            .filter-buttons {
                justify-content: flex-start;
            }
        }
    </style>
</body>
</html>
