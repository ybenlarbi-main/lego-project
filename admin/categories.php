<?php
require_once '../config/config.php';
requireAdmin();

// Handle CRUD operations
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (isset($_POST['action'])) {
            switch ($_POST['action']) {
                case 'add':
                    $nom = sanitizeInput($_POST['nom']);
                    $nom_ar = sanitizeInput($_POST['nom_ar'] ?? '');
                    $description = sanitizeInput($_POST['description']);
                    $description_ar = sanitizeInput($_POST['description_ar'] ?? '');
                    $statut = $_POST['statut'] ?? 'actif';
                    
                    // Handle image upload
                    $image_path = null;
                    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                        $upload_dir = '../assets/uploads/categories/';
                        if (!is_dir($upload_dir)) {
                            mkdir($upload_dir, 0755, true);
                        }
                        
                        $allowed_types = ['image/jpeg', 'image/png', 'image/webp'];
                        if (in_array($_FILES['image']['type'], $allowed_types)) {
                            $extension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
                            $filename = uniqid('category_') . '.' . strtolower($extension);
                            $filepath = $upload_dir . $filename;
                            
                            if (move_uploaded_file($_FILES['image']['tmp_name'], $filepath)) {
                                $image_path = 'categories/' . $filename;
                            }
                        }
                    }
                    
                    $stmt = $pdo->prepare("INSERT INTO categories (nom, nom_ar, description, description_ar, image, statut, date_creation) VALUES (?, ?, ?, ?, ?, ?, NOW())");
                    $stmt->execute([$nom, $nom_ar, $description, $description_ar, $image_path, $statut]);
                    
                    setFlashMessage('Catégorie ajoutée avec succès !', 'success');
                    break;
                    
                case 'edit':
                    $id = (int)$_POST['id'];
                    $nom = sanitizeInput($_POST['nom']);
                    $nom_ar = sanitizeInput($_POST['nom_ar'] ?? '');
                    $description = sanitizeInput($_POST['description']);
                    $description_ar = sanitizeInput($_POST['description_ar'] ?? '');
                    $statut = $_POST['statut'] ?? 'actif';
                    
                    // Get current category for image handling
                    $stmt = $pdo->prepare("SELECT image FROM categories WHERE id = ?");
                    $stmt->execute([$id]);
                    $current_category = $stmt->fetch();
                    $image_path = $current_category['image'];
                    
                    // Handle new image upload
                    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                        $upload_dir = '../assets/uploads/categories/';
                        $allowed_types = ['image/jpeg', 'image/png', 'image/webp'];
                        
                        if (in_array($_FILES['image']['type'], $allowed_types)) {
                            // Delete old image
                            if ($image_path) {
                                $old_file = '../assets/uploads/' . $image_path;
                                if (file_exists($old_file)) {
                                    unlink($old_file);
                                }
                            }
                            
                            $extension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
                            $filename = uniqid('category_') . '.' . strtolower($extension);
                            $filepath = $upload_dir . $filename;
                            
                            if (move_uploaded_file($_FILES['image']['tmp_name'], $filepath)) {
                                $image_path = 'categories/' . $filename;
                            }
                        }
                    }
                    
                    $stmt = $pdo->prepare("UPDATE categories SET nom = ?, nom_ar = ?, description = ?, description_ar = ?, image = ?, statut = ? WHERE id = ?");
                    $stmt->execute([$nom, $nom_ar, $description, $description_ar, $image_path, $statut, $id]);
                    
                    setFlashMessage('Catégorie modifiée avec succès !', 'success');
                    break;
                    
                case 'delete':
                    $id = (int)$_POST['id'];
                    
                    // Get category image to delete
                    $stmt = $pdo->prepare("SELECT image FROM categories WHERE id = ?");
                    $stmt->execute([$id]);
                    $category = $stmt->fetch();
                    
                    // Check if category has products
                    $stmt = $pdo->prepare("SELECT COUNT(*) FROM produits WHERE categorie_id = ?");
                    $stmt->execute([$id]);
                    $product_count = $stmt->fetchColumn();
                    
                    if ($product_count > 0) {
                        setFlashMessage("Impossible de supprimer cette catégorie car elle contient $product_count produit(s).", 'danger');
                    } else {
                        // Delete image file
                        if ($category['image']) {
                            $image_file = '../assets/uploads/' . $category['image'];
                            if (file_exists($image_file)) {
                                unlink($image_file);
                            }
                        }
                        
                        $stmt = $pdo->prepare("DELETE FROM categories WHERE id = ?");
                        $stmt->execute([$id]);
                        
                        setFlashMessage('Catégorie supprimée avec succès !', 'success');
                    }
                    break;
            }
        }
    } catch (Exception $e) {
        setFlashMessage('Erreur: ' . $e->getMessage(), 'danger');
    }
    
    header('Location: categories.php');
    exit;
}

// Get all categories
$stmt = $pdo->prepare("
    SELECT c.*, COUNT(p.id) as product_count 
    FROM categories c 
    LEFT JOIN produits p ON c.id = p.categorie_id 
    GROUP BY c.id 
    ORDER BY c.date_creation DESC
");
$stmt->execute();
$categories = $stmt->fetchAll();

// Get category for editing
$edit_category = null;
if (isset($_GET['edit'])) {
    $edit_id = (int)$_GET['edit'];
    $stmt = $pdo->prepare("SELECT * FROM categories WHERE id = ?");
    $stmt->execute([$edit_id]);
    $edit_category = $stmt->fetch();
}

$page_title = "Gestion des Catégories";
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
                <a href="categories.php" class="nav-item active">
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
                <h1><?php echo $page_title; ?></h1>
                <div class="admin-actions">
                    <button class="btn btn-primary" onclick="showAddModal()">
                        <span>➕</span> Ajouter une catégorie
                    </button>
                </div>
            </div>

            <!-- Flash Messages -->
            <?php echo getFlashMessage(); ?>

            <!-- Categories Table -->
            <div class="admin-card">
                <div class="card-header">
                    <h3>Liste des Catégories</h3>
                    <div class="table-info">
                        <span><?php echo count($categories); ?> catégorie(s) au total</span>
                    </div>
                </div>
                
                <div class="table-container">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>Image</th>
                                <th>Nom</th>
                                <th>Description</th>
                                <th>Produits</th>
                                <th>Statut</th>
                                <th>Date création</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($categories as $category): ?>
                                <tr>
                                    <td>
                                        <div class="table-image">
                                            <img src="<?php echo getImageUrl($category['image']); ?>" 
                                                 alt="<?php echo htmlspecialchars($category['nom']); ?>">
                                        </div>
                                    </td>
                                    <td>
                                        <div class="product-name">
                                            <strong><?php echo htmlspecialchars($category['nom']); ?></strong>
                                            <?php if ($category['nom_ar']): ?>
                                                <small><?php echo htmlspecialchars($category['nom_ar']); ?></small>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td class="description-cell">
                                        <?php echo htmlspecialchars(substr($category['description'], 0, 60)); ?>
                                        <?php if (strlen($category['description']) > 60): ?>...<?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge badge-info"><?php echo $category['product_count']; ?> produits</span>
                                    </td>
                                    <td>
                                        <span class="status-badge status-<?php echo $category['statut']; ?>">
                                            <?php echo ucfirst($category['statut']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('d/m/Y', strtotime($category['date_creation'])); ?></td>
                                    <td>
                                        <div class="action-buttons">
                                            <button class="btn btn-sm btn-secondary" onclick="editCategory(<?php echo $category['id']; ?>)">
                                                ✏️ Modifier
                                            </button>
                                            <button class="btn btn-sm btn-danger" onclick="deleteCategory(<?php echo $category['id']; ?>, '<?php echo addslashes($category['nom']); ?>')">
                                                🗑️ Supprimer
                                            </button>
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

    <!-- Add/Edit Category Modal -->
    <div class="modal" id="categoryModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="modalTitle">Ajouter une catégorie</h3>
                <button class="modal-close" onclick="closeModal()">&times;</button>
            </div>
            
            <form id="categoryForm" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" id="formAction" value="add">
                <input type="hidden" name="id" id="categoryId">
                
                <div class="form-group">
                    <label for="nom">Nom (Français) *</label>
                    <input type="text" id="nom" name="nom" required>
                </div>
                
                <div class="form-group">
                    <label for="nom_ar">Nom (Arabe)</label>
                    <input type="text" id="nom_ar" name="nom_ar">
                </div>
                
                <div class="form-group">
                    <label for="description">Description (Français) *</label>
                    <textarea id="description" name="description" rows="3" required></textarea>
                </div>
                
                <div class="form-group">
                    <label for="description_ar">Description (Arabe)</label>
                    <textarea id="description_ar" name="description_ar" rows="3"></textarea>
                </div>
                
                <div class="form-group">
                    <label for="image">Image de la catégorie</label>
                    <input type="file" id="image" name="image" accept="image/*">
                    <div class="image-preview" id="imagePreview"></div>
                </div>
                
                <div class="form-group">
                    <label for="statut">Statut</label>
                    <select id="statut" name="statut">
                        <option value="actif">Actif</option>
                        <option value="inactif">Inactif</option>
                    </select>
                </div>
                
                <div class="modal-actions">
                    <button type="button" class="btn btn-secondary" onclick="closeModal()">Annuler</button>
                    <button type="submit" class="btn btn-primary" id="submitBtn">Ajouter</button>
                </div>
            </form>
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
                <p>Êtes-vous sûr de vouloir supprimer la catégorie <strong id="deleteCategoryName"></strong> ?</p>
                <p class="text-danger">Cette action est irréversible.</p>
            </div>
            <div class="modal-actions">
                <button type="button" class="btn btn-secondary" onclick="closeDeleteModal()">Annuler</button>
                <form method="POST" style="display: inline;">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" id="deleteCategoryId">
                    <button type="submit" class="btn btn-danger">Supprimer</button>
                </form>
            </div>
        </div>
    </div>

    <script>
        // Modal management
        function showAddModal() {
            document.getElementById('modalTitle').textContent = 'Ajouter une catégorie';
            document.getElementById('formAction').value = 'add';
            document.getElementById('submitBtn').textContent = 'Ajouter';
            document.getElementById('categoryForm').reset();
            document.getElementById('imagePreview').innerHTML = '';
            document.getElementById('categoryModal').style.display = 'flex';
        }

        function editCategory(id) {
            // Fetch category data and populate form
            const categories = <?php echo json_encode($categories); ?>;
            const category = categories.find(c => c.id == id);
            
            if (category) {
                document.getElementById('modalTitle').textContent = 'Modifier la catégorie';
                document.getElementById('formAction').value = 'edit';
                document.getElementById('categoryId').value = id;
                document.getElementById('nom').value = category.nom;
                document.getElementById('nom_ar').value = category.nom_ar || '';
                document.getElementById('description').value = category.description;
                document.getElementById('description_ar').value = category.description_ar || '';
                document.getElementById('statut').value = category.statut;
                document.getElementById('submitBtn').textContent = 'Modifier';
                
                // Show current image if exists
                const imagePreview = document.getElementById('imagePreview');
                if (category.image) {
                    imagePreview.innerHTML = `<img src="<?php echo UPLOAD_URL; ?>${category.image}" alt="Current image" style="max-width: 200px; height: auto;">`;
                } else {
                    imagePreview.innerHTML = '';
                }
                
                document.getElementById('categoryModal').style.display = 'flex';
            }
        }

        function deleteCategory(id, name) {
            document.getElementById('deleteCategoryId').value = id;
            document.getElementById('deleteCategoryName').textContent = name;
            document.getElementById('deleteModal').style.display = 'flex';
        }

        function closeModal() {
            document.getElementById('categoryModal').style.display = 'none';
        }

        function closeDeleteModal() {
            document.getElementById('deleteModal').style.display = 'none';
        }

        // Image preview functionality
        document.getElementById('image').addEventListener('change', function(e) {
            const file = e.target.files[0];
            const preview = document.getElementById('imagePreview');
            
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    preview.innerHTML = `<img src="${e.target.result}" alt="Preview" style="max-width: 200px; height: auto;">`;
                };
                reader.readAsDataURL(file);
            } else {
                preview.innerHTML = '';
            }
        });

        // Close modals when clicking outside
        window.onclick = function(event) {
            const categoryModal = document.getElementById('categoryModal');
            const deleteModal = document.getElementById('deleteModal');
            
            if (event.target == categoryModal) {
                closeModal();
            }
            if (event.target == deleteModal) {
                closeDeleteModal();
            }
        }
    </script>
</body>
</html>
