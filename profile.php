<?php
require_once 'config/config.php';

// Require login for profile
requireLogin();

$user_id = $_SESSION['user_id'];

// Get user information
$stmt = $pdo->prepare("SELECT * FROM utilisateurs WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

// Get user orders
$stmt = $pdo->prepare("
    SELECT * FROM commandes 
    WHERE client_id = ? 
    ORDER BY date_commande DESC
");
$stmt->execute([$user_id]);
$orders = $stmt->fetchAll();

$page_title = 'Mon Profil';
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
        .profile-layout {
            display: grid;
            grid-template-columns: 300px 1fr;
            gap: 2rem;
        }
        
        .profile-sidebar {
            background: white;
            border-radius: 10px;
            padding: 2rem;
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
            height: fit-content;
        }
        
        .profile-main {
            background: white;
            border-radius: 10px;
            padding: 2rem;
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
        }
        
        .profile-avatar {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            background: #f0f0f0;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.5rem;
            color: #3b82f6;
            margin: 0 auto 1.5rem auto;
        }
        
        .user-info {
            text-align: center;
            margin-bottom: 2rem;
            padding-bottom: 2rem;
            border-bottom: 1px solid #eee;
        }
        
        .user-info h3 {
            margin: 0 0 0.5rem 0;
        }
        
        .user-info p {
            color: #6b7280;
            margin: 0;
        }
        
        .profile-nav {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        
        .profile-nav li {
            margin-bottom: 0.5rem;
        }
        
        .profile-nav a {
            display: flex;
            align-items: center;
            padding: 0.75rem 1rem;
            border-radius: 6px;
            color: #374151;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.2s;
        }
        
        .profile-nav a:hover {
            background: #f9fafb;
        }
        
        .profile-nav a.active {
            background: #edf3fd;
            color: #3b82f6;
        }
        
        .profile-nav .nav-icon {
            margin-right: 0.75rem;
            opacity: 0.7;
            font-size: 1.1rem;
        }
        
        .profile-section {
            margin-bottom: 2rem;
        }
        
        .profile-section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            padding-bottom: 0.75rem;
            border-bottom: 1px solid #eee;
        }
        
        .profile-section-header h3 {
            margin: 0;
        }
        
        .orders-list {
            border-collapse: collapse;
            width: 100%;
        }
        
        .orders-list th {
            text-align: left;
            padding: 1rem;
            background-color: #f9fafb;
            font-weight: 500;
            color: #6b7280;
            font-size: 0.875rem;
        }
        
        .orders-list td {
            padding: 1rem;
            border-top: 1px solid #f3f4f6;
            color: #374151;
            vertical-align: middle;
        }
        
        .orders-list tr:hover {
            background-color: #f9fafb;
        }
        
        .order-status {
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            font-size: 0.75rem;
            font-weight: 500;
        }
        
        @media (max-width: 768px) {
            .profile-layout {
                grid-template-columns: 1fr;
            }
            
            .orders-list {
                display: block;
                overflow-x: auto;
                white-space: nowrap;
            }
        }
    </style>
</head>
<body>
    <?php 
    // Simple header for profile page
    include_once 'includes/header.php';
    ?>
    
    <section class="products-section">
        <div class="container">
            <div class="section-header">
                <h1>Mon Profil</h1>
                <p>Gérez vos informations personnelles et vos commandes</p>
            </div>
            
            <!-- Flash Messages -->
            <?php echo getFlashMessage(); ?>
            
            <div class="profile-layout">
                <!-- Profile Sidebar -->
                <div class="profile-sidebar">
                    <div class="user-info">
                        <div class="profile-avatar">
                            <?php echo strtoupper(substr($user['prenom'], 0, 1)); ?>
                        </div>
                        <h3><?php echo htmlspecialchars($user['prenom'] . ' ' . $user['nom']); ?></h3>
                        <p><?php echo htmlspecialchars($user['email']); ?></p>
                    </div>
                    
                    <ul class="profile-nav">
                        <li>
                            <a href="#profile" class="active">
                                <span class="nav-icon">👤</span>
                                Profil
                            </a>
                        </li>
                        <li>
                            <a href="#orders">
                                <span class="nav-icon">📦</span>
                                Mes commandes
                            </a>
                        </li>
                        <li>
                            <a href="#settings">
                                <span class="nav-icon">⚙️</span>
                                Paramètres
                            </a>
                        </li>
                        <li>
                            <a href="auth/logout.php">
                                <span class="nav-icon">🚪</span>
                                Déconnexion
                            </a>
                        </li>
                    </ul>
                </div>
                
                <!-- Profile Main Content -->
                <div class="profile-main">
                    <!-- Profile Information -->
                    <div class="profile-section" id="profile">
                        <div class="profile-section-header">
                            <h3>Informations personnelles</h3>
                            <button class="btn-secondary btn-sm" id="edit-profile-btn" onclick="toggleEditProfile()">Modifier</button>
                        </div>
                        
                        <!-- Display Mode -->
                        <div id="profile-display" class="form-grid">
                            <div class="form-group">
                                <label>Prénom</label>
                                <p><?php echo htmlspecialchars($user['prenom']); ?></p>
                            </div>
                            
                            <div class="form-group">
                                <label>Nom</label>
                                <p><?php echo htmlspecialchars($user['nom']); ?></p>
                            </div>
                            
                            <div class="form-group">
                                <label>Email</label>
                                <p><?php echo htmlspecialchars($user['email']); ?></p>
                            </div>
                            
                            <div class="form-group">
                                <label>Téléphone</label>
                                <p><?php echo $user['telephone'] ? htmlspecialchars($user['telephone']) : '-'; ?></p>
                            </div>
                            
                            <div class="form-group full-width">
                                <label>Adresse</label>
                                <p><?php echo $user['adresse'] ? htmlspecialchars($user['adresse']) : '-'; ?></p>
                            </div>
                            
                            <div class="form-group">
                                <label>Ville</label>
                                <p><?php echo $user['ville'] ? htmlspecialchars($user['ville']) : '-'; ?></p>
                            </div>
                            
                            <div class="form-group">
                                <label>Code postal</label>
                                <p><?php echo $user['code_postal'] ? htmlspecialchars($user['code_postal']) : '-'; ?></p>
                            </div>
                        </div>
                        
                        <!-- Edit Mode -->
                        <div id="profile-edit" class="form-grid" style="display: none;">
                            <form method="POST" action="update-profile.php">
                                <div class="form-group">
                                    <label for="edit_prenom">Prénom *</label>
                                    <input type="text" id="edit_prenom" name="prenom" class="form-control" 
                                           value="<?php echo htmlspecialchars($user['prenom']); ?>" required>
                                </div>
                                
                                <div class="form-group">
                                    <label for="edit_nom">Nom *</label>
                                    <input type="text" id="edit_nom" name="nom" class="form-control" 
                                           value="<?php echo htmlspecialchars($user['nom']); ?>" required>
                                </div>
                                
                                <div class="form-group">
                                    <label for="edit_email">Email *</label>
                                    <input type="email" id="edit_email" name="email" class="form-control" 
                                           value="<?php echo htmlspecialchars($user['email']); ?>" required>
                                </div>
                                
                                <div class="form-group">
                                    <label for="edit_telephone">Téléphone</label>
                                    <input type="tel" id="edit_telephone" name="telephone" class="form-control" 
                                           value="<?php echo htmlspecialchars($user['telephone'] ?? ''); ?>">
                                </div>
                                
                                <div class="form-group full-width">
                                    <label for="edit_adresse">Adresse</label>
                                    <input type="text" id="edit_adresse" name="adresse" class="form-control" 
                                           value="<?php echo htmlspecialchars($user['adresse'] ?? ''); ?>">
                                </div>
                                
                                <div class="form-group">
                                    <label for="edit_ville">Ville</label>
                                    <input type="text" id="edit_ville" name="ville" class="form-control" 
                                           value="<?php echo htmlspecialchars($user['ville'] ?? ''); ?>">
                                </div>
                                
                                <div class="form-group">
                                    <label for="edit_code_postal">Code postal</label>
                                    <input type="text" id="edit_code_postal" name="code_postal" class="form-control" 
                                           value="<?php echo htmlspecialchars($user['code_postal'] ?? ''); ?>">
                                </div>
                                
                                <div class="form-group full-width">
                                    <div class="form-actions" style="display: flex; gap: 1rem; margin-top: 1rem;">
                                        <button type="submit" class="btn-primary">Sauvegarder</button>
                                        <button type="button" class="btn-secondary" onclick="toggleEditProfile()">Annuler</button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                    
                    <!-- Orders -->
                    <div class="profile-section" id="orders">
                        <div class="profile-section-header">
                            <h3>Mes commandes</h3>
                        </div>
                        
                        <?php if (empty($orders)): ?>
                            <div style="text-align: center; padding: 2rem;">
                                <p>Vous n'avez pas encore passé de commande.</p>
                                <a href="produits.php" class="btn-primary">Commencer vos achats</a>
                            </div>
                        <?php else: ?>
                            <table class="orders-list">
                                <thead>
                                    <tr>
                                        <th>N° Commande</th>
                                        <th>Date</th>
                                        <th>Total</th>
                                        <th>Statut</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($orders as $order): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($order['numero_commande']); ?></td>
                                            <td><?php echo formatDate($order['date_commande']); ?></td>
                                            <td><?php echo formatPrice($order['total_ttc']); ?></td>
                                            <td>
                                                <span class="order-status status-<?php echo $order['statut']; ?>">
                                                    <?php echo getStatusText($order['statut']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <a href="order-confirmation.php?id=<?php echo $order['id']; ?>" class="btn-link">
                                                    Détails
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Settings -->
                    <div class="profile-section" id="settings">
                        <div class="profile-section-header">
                            <h3>Paramètres du compte</h3>
                        </div>
                        
                        <div style="margin-bottom: 2rem;">
                            <h4>Changer le mot de passe</h4>
                            <form method="POST" action="update-password.php" class="form-grid">
                                <div class="form-group full-width">
                                    <label for="current_password">Mot de passe actuel</label>
                                    <input type="password" id="current_password" name="current_password" required>
                                </div>
                                
                                <div class="form-group">
                                    <label for="new_password">Nouveau mot de passe</label>
                                    <input type="password" id="new_password" name="new_password" required>
                                </div>
                                
                                <div class="form-group">
                                    <label for="confirm_password">Confirmer le mot de passe</label>
                                    <input type="password" id="confirm_password" name="confirm_password" required>
                                </div>
                                
                                <div class="form-group full-width">
                                    <button type="submit" class="btn-primary">Mettre à jour le mot de passe</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    
    <?php 
    // Simple footer for profile page
    include_once 'includes/footer.php';
    ?>
    
    <script>
        function toggleEditProfile() {
            const displayMode = document.getElementById('profile-display');
            const editMode = document.getElementById('profile-edit');
            const editBtn = document.getElementById('edit-profile-btn');
            
            if (displayMode.style.display === 'none') {
                // Switch to display mode
                displayMode.style.display = 'grid';
                editMode.style.display = 'none';
                editBtn.textContent = 'Modifier';
            } else {
                // Switch to edit mode
                displayMode.style.display = 'none';
                editMode.style.display = 'grid';
                editBtn.textContent = 'Annuler';
            }
        }
        
        // Tab navigation
        document.addEventListener('DOMContentLoaded', function() {
            const navLinks = document.querySelectorAll('.profile-nav a');
            const sections = document.querySelectorAll('.profile-section');
            
            // Set active tab based on hash
            function setActiveTab() {
                const hash = window.location.hash || '#profile';
                
                navLinks.forEach(link => {
                    if (link.getAttribute('href') === hash) {
                        link.classList.add('active');
                    } else {
                        link.classList.remove('active');
                    }
                });
                
                sections.forEach(section => {
                    if (section.id === hash.substring(1)) {
                        section.style.display = 'block';
                    } else {
                        section.style.display = 'none';
                    }
                });
            }
            
            // Set initial active tab
            setActiveTab();
            
            // Handle tab clicks
            navLinks.forEach(link => {
                link.addEventListener('click', function(e) {
                    // Skip for logout link
                    if (link.getAttribute('href') === 'auth/logout.php') {
                        return;
                    }
                    
                    e.preventDefault();
                    const hash = this.getAttribute('href');
                    window.location.hash = hash;
                    setActiveTab();
                });
            });
            
            // Handle hash changes
            window.addEventListener('hashchange', setActiveTab);
        });
    </script>
</body>
</html>
