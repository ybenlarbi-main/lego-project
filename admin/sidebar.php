<!-- Sidebar -->
<aside class="admin-sidebar">
    <div class="sidebar-header">
        <h2><i class="fas fa-cubes"></i> Menalego</h2>
    </div>
    <nav class="sidebar-nav">
        <ul>
            <li>
                <a href="index.php" class="<?php echo basename($_SERVER['PHP_SELF']) === 'index.php' ? 'active' : ''; ?>">
                    <i class="fas fa-tachometer-alt"></i>
                    <span class="nav-text">Tableau de bord</span>
                </a>
            </li>
            <li>
                <a href="products.php" class="<?php echo basename($_SERVER['PHP_SELF']) === 'products.php' ? 'active' : ''; ?>">
                    <i class="fas fa-box"></i>
                    <span class="nav-text">Produits</span>
                </a>
            </li>
            <li>
                <a href="categories.php" class="<?php echo basename($_SERVER['PHP_SELF']) === 'categories.php' ? 'active' : ''; ?>">
                    <i class="fas fa-tags"></i>
                    <span class="nav-text">Catégories</span>
                </a>
            </li>
            <li>
                <a href="orders.php" class="<?php echo basename($_SERVER['PHP_SELF']) === 'orders.php' ? 'active' : ''; ?>">
                    <i class="fas fa-shopping-cart"></i>
                    <span class="nav-text">Commandes</span>
                </a>
            </li>
            <li>
                <a href="reviews.php" class="<?php echo basename($_SERVER['PHP_SELF']) === 'reviews.php' ? 'active' : ''; ?>">
                    <i class="fas fa-star"></i>
                    <span class="nav-text">Avis clients</span>
                    <?php
                    // Count pending reviews
                    $stmt = $pdo->query("SELECT COUNT(*) FROM avis WHERE statut = 'en_attente'");
                    $pending_reviews = $stmt->fetchColumn();
                    
                    if ($pending_reviews > 0) {
                        echo '<span class="badge bg-warning">' . $pending_reviews . '</span>';
                    }
                    ?>
                </a>
            </li>
            <li>
                <a href="users.php" class="<?php echo basename($_SERVER['PHP_SELF']) === 'users.php' ? 'active' : ''; ?>">
                    <i class="fas fa-users"></i>
                    <span class="nav-text">Utilisateurs</span>
                </a>
            </li>
            <li>
                <a href="settings.php" class="<?php echo basename($_SERVER['PHP_SELF']) === 'settings.php' ? 'active' : ''; ?>">
                    <i class="fas fa-cog"></i>
                    <span class="nav-text">Paramètres</span>
                </a>
            </li>
        </ul>
        
        <div class="sidebar-divider"></div>
        
        <ul>
            <li>
                <a href="../index.php" target="_blank">
                    <i class="fas fa-external-link-alt"></i>
                    <span class="nav-text">Voir le site</span>
                </a>
            </li>
            <li>
                <a href="../auth/logout.php">
                    <i class="fas fa-sign-out-alt"></i>
                    <span class="nav-text">Déconnexion</span>
                </a>
            </li>
        </ul>
    </nav>
</aside>
