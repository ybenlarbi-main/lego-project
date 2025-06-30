<?php
require_once '../config/config.php';
requireAdmin();

// Image upload handling functions
function uploadProductImages($files) {
    $upload_dir = '../assets/uploads/products/';
    $uploaded_files = [];
    $max_files = 5;
    $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'];
    $max_size = 5 * 1024 * 1024; // 5MB

    // Create directory if it doesn't exist
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }

    // Check if files array is valid
    if (!isset($files['product_images']) || !is_array($files['product_images']['name'])) {
        return $uploaded_files;
    }

    $file_count = count($files['product_images']['name']);
    
    // Limit number of files
    if ($file_count > $max_files) {
        throw new Exception("Vous ne pouvez télécharger que $max_files images maximum.");
    }

    for ($i = 0; $i < $file_count; $i++) {
        // Skip empty files
        if ($files['product_images']['error'][$i] === UPLOAD_ERR_NO_FILE) {
            continue;
        }

        // Check for upload errors
        if ($files['product_images']['error'][$i] !== UPLOAD_ERR_OK) {
            throw new Exception("Erreur lors du téléchargement du fichier " . ($i + 1));
        }

        // Validate file type
        $file_type = $files['product_images']['type'][$i];
        if (!in_array($file_type, $allowed_types)) {
            throw new Exception("Type de fichier non autorisé pour le fichier " . ($i + 1) . ". Utilisez JPG, PNG ou WEBP.");
        }

        // Validate file size
        if ($files['product_images']['size'][$i] > $max_size) {
            throw new Exception("Le fichier " . ($i + 1) . " est trop volumineux (max 5MB).");
        }

        // Generate unique filename
        $extension = pathinfo($files['product_images']['name'][$i], PATHINFO_EXTENSION);
        $filename = uniqid('product_') . '.' . strtolower($extension);
        $filepath = $upload_dir . $filename;

        // Move uploaded file
        if (move_uploaded_file($files['product_images']['tmp_name'][$i], $filepath)) {
            $uploaded_files[] = 'products/' . $filename;
        } else {
            throw new Exception("Erreur lors de la sauvegarde du fichier " . ($i + 1));
        }
    }

    return $uploaded_files;
}

function deleteProductImage($filename) {
    // If filename already includes products/, use it as is, otherwise add products/
    if (strpos($filename, 'products/') === 0) {
        $filepath = '../assets/uploads/' . $filename;
    } else {
        $filepath = '../assets/uploads/products/' . $filename;
    }
    
    if (file_exists($filepath)) {
        unlink($filepath);
    }
}

function processProductImages($existing_images_json, $removed_images_string, $new_uploaded_files) {
    // Get existing images
    $existing_images = $existing_images_json ? json_decode($existing_images_json, true) : [];
    
    // Get removed images list
    $removed_images = $removed_images_string ? explode(',', $removed_images_string) : [];
    
    // Remove deleted images from existing list and delete files
    foreach ($removed_images as $removed_image) {
        $removed_image = trim($removed_image);
        if ($removed_image && in_array($removed_image, $existing_images)) {
            deleteProductImage($removed_image);
            $existing_images = array_filter($existing_images, function($img) use ($removed_image) {
                return $img !== $removed_image;
            });
        }
    }
    
    // Add new uploaded images
    $all_images = array_merge($existing_images, $new_uploaded_files);
    
    // Limit to max 5 images
    $all_images = array_slice($all_images, 0, 5);
    
    return json_encode(array_values($all_images));
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add' || $action === 'edit') {
        try {
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
            
            // Handle image uploads
            $uploaded_images = [];
            if (!empty($_FILES['product_images']['name'][0])) {
                $uploaded_images = uploadProductImages($_FILES);
            }
            
            if ($action === 'add') {
                // For new products, just use uploaded images
                $images_json = !empty($uploaded_images) ? json_encode($uploaded_images) : null;
                
                $stmt = $pdo->prepare("
                    INSERT INTO produits (categorie_id, nom, nom_ar, description, description_ar, prix, prix_promo, 
                                        stock, pieces_count, age_min, age_max, reference, statut, featured, images) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                
                if ($stmt->execute([$categorie_id, $nom, $nom_ar, $description, $description_ar, $prix, $prix_promo, 
                                  $stock, $pieces_count, $age_min, $age_max, $reference, $statut, $featured, $images_json])) {
                    setFlashMessage('Produit ajouté avec succès !', 'success');
                } else {
                    setFlashMessage('Erreur lors de l\'ajout du produit', 'danger');
                }
            } else {
                // For editing, get existing images and process them
                $stmt = $pdo->prepare("SELECT images FROM produits WHERE id = ?");
                $stmt->execute([$id]);
                $current_product = $stmt->fetch();
                $existing_images_json = $current_product['images'] ?? null;
                
                // Process images (handle removals and additions)
                $removed_images = $_POST['removed_images'] ?? '';
                $images_json = processProductImages($existing_images_json, $removed_images, $uploaded_images);
                
                $stmt = $pdo->prepare("
                    UPDATE produits SET categorie_id = ?, nom = ?, nom_ar = ?, description = ?, description_ar = ?, 
                                       prix = ?, prix_promo = ?, stock = ?, pieces_count = ?, age_min = ?, age_max = ?, 
                                       reference = ?, statut = ?, featured = ?, images = ?
                    WHERE id = ?
                ");
                
                if ($stmt->execute([$categorie_id, $nom, $nom_ar, $description, $description_ar, $prix, $prix_promo, 
                                  $stock, $pieces_count, $age_min, $age_max, $reference, $statut, $featured, $images_json, $id])) {
                    setFlashMessage('Produit modifié avec succès !', 'success');
                } else {
                    setFlashMessage('Erreur lors de la modification du produit', 'danger');
                }
            }
        } catch (Exception $e) {
            setFlashMessage('Erreur: ' . $e->getMessage(), 'danger');
        }
        
        header('Location: products.php');
        exit();
    } elseif ($action === 'delete') {
        $id = (int)$_POST['id'];
        
        // Get product images before deletion to clean up files
        $stmt = $pdo->prepare("SELECT images FROM produits WHERE id = ?");
        $stmt->execute([$id]);
        $product = $stmt->fetch();
        
        if ($product && !empty($product['images'])) {
            $images = json_decode($product['images'], true);
            foreach ($images as $image) {
                deleteProductImage($image);
            }
        }
        
        $stmt = $pdo->prepare("DELETE FROM produits WHERE id = ?");
        if ($stmt->execute([$id])) {
            setFlashMessage('Produit supprimé avec succès !', 'success');
        } else {
            setFlashMessage('Erreur lors de la suppression du produit', 'danger');
        }
        header('Location: products.php');
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
                
                <form method="POST" enctype="multipart/form-data">
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
                    
                    <!-- Image Upload Section -->
                    <div class="form-group">
                        <label for="product_images">Images du produit</label>
                        <input type="file" id="product_images" name="product_images[]" class="form-control" accept="image/*" multiple>
                        <small class="form-text">Vous pouvez sélectionner plusieurs images (max 5). Formats acceptés: JPG, PNG, WEBP</small>
                        
                        <?php if ($edit_product && !empty($edit_product['images'])): ?>
                            <?php 
                            $existing_images = json_decode($edit_product['images'], true);
                            if ($existing_images): 
                            ?>
                                <div class="existing-images" style="margin-top: 10px;">
                                    <label>Images actuelles:</label>
                                    <div style="display: flex; gap: 10px; flex-wrap: wrap; margin-top: 5px;">
                                        <?php foreach ($existing_images as $index => $image): ?>
                                            <div class="image-preview" style="position: relative; display: inline-block;">
                                                <img src="<?php echo '../assets/uploads/products/' . htmlspecialchars($image); ?>" 
                                                     alt="Image produit" 
                                                     style="width: 80px; height: 80px; object-fit: cover; border: 1px solid #ddd; border-radius: 4px;">
                                                <button type="button" 
                                                        class="remove-image" 
                                                        data-image="<?php echo htmlspecialchars($image); ?>"
                                                        style="position: absolute; top: -5px; right: -5px; background: #dc3545; color: white; border: none; border-radius: 50%; width: 20px; height: 20px; font-size: 12px; cursor: pointer;"
                                                        title="Supprimer cette image">×</button>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                    <input type="hidden" id="removed_images" name="removed_images" value="">
                                </div>
                            <?php endif; ?>
                        <?php endif; ?>
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
                }
            }

            // Event Listeners
            if (showFormBtn) {
                showFormBtn.addEventListener('click', () => toggleForm(true));
            }
            if (hideFormBtn) {
                hideFormBtn.addEventListener('click', () => toggleForm(false));
            }

            // Image upload preview functionality
            const imageInput = document.getElementById('product_images');
            const removedImagesInput = document.getElementById('removed_images');
            let removedImages = [];

            // Handle new image uploads preview
            if (imageInput) {
                imageInput.addEventListener('change', function(e) {
                    const files = Array.from(e.target.files);
                    const maxFiles = 5;
                    const maxSize = 5 * 1024 * 1024; // 5MB
                    const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'];

                    // Validate files
                    const validFiles = files.filter(file => {
                        if (!allowedTypes.includes(file.type.toLowerCase())) {
                            alert(`Le fichier "${file.name}" n'est pas un type d'image autorisé.`);
                            return false;
                        }
                        if (file.size > maxSize) {
                            alert(`Le fichier "${file.name}" est trop volumineux (max 5MB).`);
                            return false;
                        }
                        return true;
                    });

                    if (validFiles.length > maxFiles) {
                        alert(`Vous ne pouvez sélectionner que ${maxFiles} images maximum.`);
                        e.target.value = '';
                        return;
                    }

                    // Create preview container if it doesn't exist
                    let previewContainer = document.querySelector('.new-images-preview');
                    if (!previewContainer) {
                        previewContainer = document.createElement('div');
                        previewContainer.className = 'new-images-preview';
                        previewContainer.style.marginTop = '10px';
                        previewContainer.innerHTML = '<label>Aperçu des nouvelles images:</label><div class="preview-grid" style="display: flex; gap: 10px; flex-wrap: wrap; margin-top: 5px;"></div>';
                        imageInput.parentNode.appendChild(previewContainer);
                    }

                    const previewGrid = previewContainer.querySelector('.preview-grid');
                    previewGrid.innerHTML = '';

                    // Create previews
                    validFiles.forEach((file, index) => {
                        const reader = new FileReader();
                        reader.onload = function(e) {
                            const imagePreview = document.createElement('div');
                            imagePreview.className = 'image-preview';
                            imagePreview.style.position = 'relative';
                            imagePreview.style.display = 'inline-block';
                            imagePreview.innerHTML = `
                                <img src="${e.target.result}" 
                                     alt="Aperçu" 
                                     style="width: 80px; height: 80px; object-fit: cover; border: 1px solid #ddd; border-radius: 4px;">
                                <div style="position: absolute; bottom: -5px; left: 0; right: 0; background: rgba(0,0,0,0.7); color: white; font-size: 10px; text-align: center; padding: 2px; border-radius: 0 0 4px 4px;">${file.name}</div>
                            `;
                            previewGrid.appendChild(imagePreview);
                        };
                        reader.readAsDataURL(file);
                    });
                });
            }

            // Handle existing image removal
            document.addEventListener('click', function(e) {
                if (e.target.classList.contains('remove-image')) {
                    e.preventDefault();
                    const imageToRemove = e.target.getAttribute('data-image');
                    const imagePreview = e.target.closest('.image-preview');
                    
                    if (confirm('Êtes-vous sûr de vouloir supprimer cette image ?')) {
                        // Add to removed images list
                        removedImages.push(imageToRemove);
                        if (removedImagesInput) {
                            removedImagesInput.value = removedImages.join(',');
                        }
                        
                        // Hide the image preview
                        imagePreview.style.display = 'none';
                    }
                }
            });
        });
    </script>
</body>
</html>