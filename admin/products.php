<?php
require_once '../config/config.php';
requireAdmin();

// ... (Your PHP logic at the top remains the same, it's already good) ...
// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add' || $action === 'edit') {
        // (This block is fine, no changes needed)
        $id = $action === 'edit' ? (int)$_POST['id'] : 0;
        $categorie_id = (int)$_POST['categorie_id'];
        $nom = sanitizeInput($_POST['nom']);
        $nom_ar = sanitizeInput($_POST['nom_ar']);
        $description = sanitizeInput($_POST['description']);
        $description_ar = sanitizeInput($_POST['description_ar']);
        $prix = (float)$_POST['prix'];
        $prix_promo = !empty($_POST['prix_promo']) ? (float)$_POST['prix_promo'] : null;
        $stock = (int)$_POST['stock'];
        $pieces_count = (int)$_POST['pieces_count'];
        $age_min = (int)$_POST['age_min'];
        $age_max = (int)$_POST['age_max'];
        $reference = sanitizeInput($_POST['reference']);
        $statut = $_POST['statut'];
        $featured = isset($_POST['featured']) ? 1 : 0;
        
        if ($action === 'add') {
            $stmt = $pdo->prepare("
                INSERT INTO produits (categorie_id, nom, nom_ar, description, description_ar, prix, prix_promo, 
                                    stock, pieces_count, age_min, age_max, reference, statut, featured) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            if ($stmt->execute([$categorie_id, $nom, $nom_ar, $description, $description_ar, $prix, $prix_promo, 
                              $stock, $pieces_count, $age_min, $age_max, $reference, $statut, $featured])) {
                setFlashMessage('Produit ajouté avec succès !', 'success');
            } else {
                setFlashMessage('Erreur lors de l\'ajout du produit', 'danger');
            }
        } else {
            $stmt = $pdo->prepare("
                UPDATE produits SET categorie_id = ?, nom = ?, nom_ar = ?, description = ?, description_ar = ?, 
                                   prix = ?, prix_promo = ?, stock = ?, pieces_count = ?, age_min = ?, age_max = ?, 
                                   reference = ?, statut = ?, featured = ?
                WHERE id = ?
            ");
            
            if ($stmt->execute([$categorie_id, $nom, $nom_ar, $description, $description_ar, $prix, $prix_promo, 
                              $stock, $pieces_count, $age_min, $age_max, $reference, $statut, $featured, $id])) {
                setFlashMessage('Produit modifié avec succès !', 'success');
            } else {
                setFlashMessage('Erreur lors de la modification du produit', 'danger');
            }
        }
        header('Location: products.php'); // Redirect to avoid form resubmission
        exit();
    } elseif ($action === 'delete') {
        $id = (int)$_POST['id'];
        $stmt = $pdo->prepare("DELETE FROM produits WHERE id = ?");
        if ($stmt->execute([$id])) {
            setFlashMessage('Produit supprimé avec succès !', 'success');
        } else {
            setFlashMessage('Erreur lors de la suppression du produit', 'danger');
        }
        header('Location: products.php'); // Redirect
        exit();
    }
}

// Get edit product if needed
$edit_product = null;
if (isset($_GET['edit'])) {
    $edit_id = (int)$_GET['edit'];
    $stmt = $pdo->prepare("SELECT * FROM produits WHERE id = ?");
    $stmt->execute([$edit_id]);
    $edit_product = $stmt->fetch();
}

// Get products
$search = isset($_GET['search']) ? sanitizeInput($_GET['search']) : '';
$category_filter = isset($_GET['category']) ? (int)$_GET['category'] : 0;

$where_conditions = ["1=1"];
$params = [];

if ($search) {
    $where_conditions[] = "(p.nom LIKE ? OR p.reference LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($category_filter) {
    $where_conditions[] = "p.categorie_id = ?";
    $params[] = $category_filter;
}

$where_clause = implode(' AND ', $where_conditions);

$stmt = $pdo->prepare("
    SELECT p.*, c.nom as categorie_nom 
    FROM produits p 
    LEFT JOIN categories c ON p.categorie_id = c.id 
    WHERE $where_clause 
    ORDER BY p.date_creation DESC
");
$stmt->execute($params);
$products = $stmt->fetchAll();

// Get categories
$categories_stmt = $pdo->prepare("SELECT * FROM categories WHERE statut = 'actif' ORDER BY nom");
$categories_stmt->execute();
$categories = $categories_stmt->fetchAll();

$page_title = 'Gestion des Produits';
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
                <li><a href="index.php">Dashboard</a></li>
                <li><a href="products.php" class="active">Produits</a></li>
                <li><a href="categories.php">Catégories</a></li>
                <li><a href="orders.php">Commandes</a></li>
                <li><a href="users.php">Utilisateurs</a></li>
                <li><a href="reviews.php">Avis</a></li>
            </ul>
        </div>
    </nav>

    <!-- Products Management -->
    <section class="products-section">
        <div class="container">
            <div class="page-header">
                <h1>Gestion des Produits</h1>
                <button id="showFormBtn" class="btn-primary">Ajouter un produit</button>
            </div>
            
            <?php echo getFlashMessage(); ?>
            
            <!-- Add/Edit Form Panel -->
            <div id="productForm" class="admin-dashboard <?php echo !$edit_product && !(isset($_GET['action']) && $_GET['action'] === 'add') ? 'hidden' : ''; ?>">
                <h3><?php echo $edit_product ? 'Modifier le produit' : 'Ajouter un produit'; ?></h3>
                
                <form method="POST">
                    <input type="hidden" name="action" value="<?php echo $edit_product ? 'edit' : 'add'; ?>">
                    <?php if ($edit_product): ?>
                        <input type="hidden" name="id" value="<?php echo $edit_product['id']; ?>">
                    <?php endif; ?>
                    
                    <div class="form-grid cols-2">
                        <div class="form-group">
                            <label for="nom">Nom (Français) *</label>
                            <input type="text" id="nom" name="nom" class="form-control" required value="<?php echo htmlspecialchars($edit_product['nom'] ?? ''); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="nom_ar">Nom (Arabe)</label>
                            <input type="text" id="nom_ar" name="nom_ar" class="form-control" value="<?php echo htmlspecialchars($edit_product['nom_ar'] ?? ''); ?>">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="description">Description (Français) *</label>
                        <textarea id="description" name="description" class="form-control" rows="3" required><?php echo htmlspecialchars($edit_product['description'] ?? ''); ?></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="description_ar">Description (Arabe)</label>
                        <textarea id="description_ar" name="description_ar" class="form-control" rows="3"><?php echo htmlspecialchars($edit_product['description_ar'] ?? ''); ?></textarea>
                    </div>
                    
                    <div class="form-grid cols-4">
                        <div class="form-group">
                            <label for="categorie_id">Catégorie *</label>
                            <select id="categorie_id" name="categorie_id" class="form-control" required>
                                <option value="">Choisir une catégorie</option>
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?php echo $category['id']; ?>" <?php echo isset($edit_product) && $edit_product['categorie_id'] == $category['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($category['nom']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="prix">Prix (MAD) *</label>
                            <input type="number" id="prix" name="prix" class="form-control" step="0.01" min="0" required value="<?php echo $edit_product['prix'] ?? ''; ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="prix_promo">Prix promo (MAD)</label>
                            <input type="number" id="prix_promo" name="prix_promo" class="form-control" step="0.01" min="0" value="<?php echo $edit_product['prix_promo'] ?? ''; ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="stock">Stock *</label>
                            <input type="number" id="stock" name="stock" class="form-control" min="0" required value="<?php echo $edit_product['stock'] ?? ''; ?>">
                        </div>
                    </div>
                    
                    <div class="form-grid cols-4">
                        <div class="form-group">
                            <label for="pieces_count">Nb de pièces *</label>
                            <input type="number" id="pieces_count" name="pieces_count" class="form-control" min="1" required value="<?php echo $edit_product['pieces_count'] ?? ''; ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="age_min">Âge min *</label>
                            <input type="number" id="age_min" name="age_min" class="form-control" min="3" max="18" required value="<?php echo $edit_product['age_min'] ?? '3'; ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="age_max">Âge max *</label>
                            <input type="number" id="age_max" name="age_max" class="form-control" min="3" max="99" required value="<?php echo $edit_product['age_max'] ?? '99'; ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="reference">Référence *</label>
                            <input type="text" id="reference" name="reference" class="form-control" required value="<?php echo htmlspecialchars($edit_product['reference'] ?? ''); ?>">
                        </div>
                    </div>
                    
                    <div class="form-grid cols-2">
                         <div class="form-group">
                            <label for="statut">Statut *</label>
                            <select id="statut" name="statut" class="form-control" required>
                                <option value="brouillon" <?php echo isset($edit_product) && $edit_product['statut'] == 'brouillon' ? 'selected' : ''; ?>>Brouillon</option>
                                <option value="actif" <?php echo isset($edit_product) && $edit_product['statut'] == 'actif' ? 'selected' : ''; ?>>Actif</option>
                                <option value="inactif" <?php echo isset($edit_product) && $edit_product['statut'] == 'inactif' ? 'selected' : ''; ?>>Inactif</option>
                                <option value="rupture" <?php echo isset($edit_product) && $edit_product['statut'] == 'rupture' ? 'selected' : ''; ?>>Rupture de stock</option>
                            </select>
                        </div>
                        
                        <div class="form-group toggle-switch-group">
                            <label class="toggle-switch">
                                <input type="checkbox" name="featured" value="1" <?php echo isset($edit_product) && $edit_product['featured'] ? 'checked' : ''; ?>>
                                <span class="toggle-slider"></span>
                            </label>
                            <span>Produit en vedette</span>
                        </div>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="btn-primary"><?php echo $edit_product ? 'Enregistrer les modifications' : 'Ajouter le produit'; ?></button>
                        <button type="button" id="hideFormBtn" class="btn-secondary">Annuler</button>
                    </div>
                </form>
            </div>

            <!-- Products Table Panel -->
            <div class="admin-dashboard">
                <div class="page-header">
                    <h3>Liste des produits (<?php echo count($products); ?>)</h3>
                    <!-- Search and Filters -->
                    <div class="filters-panel" style="padding:0; border:none; margin:0;">
                        <form method="GET" action="products.php">
                            <div class="filters-grid">
                                <div class="form-group">
                                    <input type="text" id="search" name="search" placeholder="Nom ou référence..." value="<?php echo htmlspecialchars($search); ?>" class="form-control">
                                </div>
                                <div class="form-group">
                                    <select id="category" name="category" class="form-control">
                                        <option value="">Toutes les catégories</option>
                                        <?php foreach ($categories as $category): ?>
                                            <option value="<?php echo $category['id']; ?>" <?php echo $category_filter == $category['id'] ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($category['nom']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="form-actions">
                                    <button type="submit" class="btn-primary">Filtrer</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
                
                <?php if (empty($products)): ?>
                    <p class="empty-state-message">Aucun produit trouvé pour les filtres actuels.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Nom / Réf.</th>
                                    <th>Catégorie</th>
                                    <th>Prix</th>
                                    <th>Stock</th>
                                    <th>Statut</th>
                                    <th>Vedette</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($products as $product): ?>
                                    <tr>
                                        <td><?php echo $product['id']; ?></td>
                                        <td>
                                            <strong><?php echo htmlspecialchars($product['nom']); ?></strong><br>
                                            <small class="text-muted"><?php echo htmlspecialchars($product['reference']); ?></small>
                                        </td>
                                        <td><?php echo htmlspecialchars($product['categorie_nom']); ?></td>
                                        <td><?php echo formatPrice($product['prix']); ?></td>
                                        <td>
                                            <span class="stock-level <?php echo $product['stock'] < 5 ? 'out-of-stock' : ($product['stock'] < 10 ? 'low' : ''); ?>">
                                                <?php echo $product['stock']; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge status-<?php echo $product['statut']; ?>">
                                                <?php echo ucfirst($product['statut']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo $product['featured'] ? '⭐' : '—'; ?></td>
                                        <td class="actions-cell">
                                            <a href="products.php?edit=<?php echo $product['id']; ?>#productForm" class="btn-secondary">Modifier</a>
                                            <form method="POST" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer ce produit ?');">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="id" value="<?php echo $product['id']; ?>">
                                                <button type="submit" class="btn-danger">Supprimer</button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
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

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const productForm = document.getElementById('productForm');
            const showFormBtn = document.getElementById('showFormBtn');
            const hideFormBtn = document.getElementById('hideFormBtn');

            // Function to toggle form visibility
            function toggleForm(show) {
                if (show) {
                    productForm.classList.remove('hidden');
                    productForm.scrollIntoView({ behavior: 'smooth', block: 'start' });
                    showFormBtn.classList.add('hidden');
                } else {
                    productForm.classList.add('hidden');
                    showFormBtn.classList.remove('hidden');
                    // Optional: clear form or reset URL
                    // window.history.pushState({}, '', 'products.php');
                }
            }

            // Event Listeners
            if (showFormBtn) {
                showFormBtn.addEventListener('click', () => toggleForm(true));
            }
            if (hideFormBtn) {
                hideFormBtn.addEventListener('click', () => toggleForm(false));
            }
        });
    </script>
</body>
</html>