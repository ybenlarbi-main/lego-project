<?php
require_once 'config/config.php';

// Require login for review submission
requireLogin();

$user_id = $_SESSION['user_id'];

// Check if product and form are submitted
$product_id = isset($_GET['product_id']) ? (int)$_GET['product_id'] : 0;
$order_id = isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0;

// Redirect if no product ID
if ($product_id <= 0) {
    setFlashMessage('Produit invalide', 'danger');
    header('Location: profile.php');
    exit;
}

// Verify the product exists
$stmt = $pdo->prepare("SELECT * FROM produits WHERE id = ? AND statut = 'actif'");
$stmt->execute([$product_id]);
$product = $stmt->fetch();

if (!$product) {
    setFlashMessage('Produit non trouvé', 'danger');
    header('Location: profile.php');
    exit;
}

// Check if user has already reviewed this product
$stmt = $pdo->prepare("SELECT * FROM avis WHERE client_id = ? AND produit_id = ?");
$stmt->execute([$user_id, $product_id]);
$existing_review = $stmt->fetch();

// Check if order exists and belongs to user
$order_exists = false;
if ($order_id > 0) {
    $stmt = $pdo->prepare("
        SELECT c.* FROM commandes c
        JOIN lignes_commandes lc ON c.id = lc.commande_id
        WHERE c.client_id = ? AND lc.produit_id = ? AND c.id = ?
    ");
    $stmt->execute([$user_id, $product_id, $order_id]);
    $order_exists = $stmt->fetch() !== false;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate inputs
    $note = isset($_POST['note']) ? (int)$_POST['note'] : 0;
    $titre = sanitizeInput($_POST['titre'] ?? '');
    $commentaire = sanitizeInput($_POST['commentaire'] ?? '');
    
    // Validate rating
    if ($note < 1 || $note > 5) {
        setFlashMessage('Veuillez donner une note entre 1 et 5 étoiles', 'danger');
    }
    // Validate title
    else if (empty($titre)) {
        setFlashMessage('Veuillez saisir un titre pour votre avis', 'danger');
    }
    // Validate comment
    else if (empty($commentaire)) {
        setFlashMessage('Veuillez saisir un commentaire', 'danger');
    }
    else {
        try {
            // Handle image uploads if present
            $images = [];
            if (!empty($_FILES['review_images']['name'][0])) {
                $upload_dir = 'assets/images/reviews/';
                
                // Create directory if not exists
                if (!file_exists($upload_dir)) {
                    mkdir($upload_dir, 0755, true);
                }
                
                // Process each uploaded file
                foreach ($_FILES['review_images']['tmp_name'] as $key => $tmp_name) {
                    if ($_FILES['review_images']['error'][$key] === 0) {
                        $filename = uniqid() . '_' . $_FILES['review_images']['name'][$key];
                        $destination = $upload_dir . $filename;
                        
                        // Move the uploaded file
                        if (move_uploaded_file($tmp_name, $destination)) {
                            $images[] = $filename;
                        }
                    }
                }
            }
            
            // Start transaction
            $pdo->beginTransaction();
            
            if ($existing_review) {
                // Update existing review
                $stmt = $pdo->prepare("
                    UPDATE avis
                    SET note = ?, titre = ?, commentaire = ?, 
                        images = ?, statut = 'en_attente',
                        date_modification = NOW()
                    WHERE id = ?
                ");
                
                $stmt->execute([
                    $note,
                    $titre,
                    $commentaire,
                    !empty($images) ? json_encode($images) : $existing_review['images'],
                    $existing_review['id']
                ]);
                
                $message = 'Votre avis a été mis à jour et sera publié après validation.';
            } else {
                // Insert new review
                $stmt = $pdo->prepare("
                    INSERT INTO avis (
                        produit_id, client_id, commande_id, note, titre, 
                        commentaire, images, statut
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, 'en_attente')
                ");
                
                $stmt->execute([
                    $product_id,
                    $user_id,
                    $order_exists ? $order_id : null,
                    $note,
                    $titre,
                    $commentaire,
                    !empty($images) ? json_encode($images) : null
                ]);
                
                $message = 'Merci pour votre avis ! Il sera publié après validation.';
            }
            
            // Commit transaction
            $pdo->commit();
            
            // Redirect with success message
            setFlashMessage($message, 'success');
            
            // Redirect back to product page or order details
            if ($order_exists) {
                header("Location: order-details.php?id={$order_id}");
            } else {
                header("Location: product.php?id={$product_id}");
            }
            exit;
            
        } catch (Exception $e) {
            // Rollback on error
            $pdo->rollBack();
            setFlashMessage('Erreur lors de la soumission de l\'avis: ' . $e->getMessage(), 'danger');
        }
    }
}

$page_title = $existing_review ? 'Modifier votre avis' : 'Laisser un avis';
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
        .review-form-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 2rem 1rem;
        }
        
        .review-form-card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
            padding: 2rem;
        }
        
        .product-info {
            display: flex;
            align-items: center;
            margin-bottom: 2rem;
            padding-bottom: 1.5rem;
            border-bottom: 1px solid #f0f0f0;
        }
        
        .product-image {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 8px;
            margin-right: 1.5rem;
        }
        
        .product-name {
            font-weight: 600;
            font-size: 1.25rem;
            margin: 0 0 0.5rem 0;
        }
        
        .product-category {
            color: #6b7280;
            font-size: 0.875rem;
        }
        
        .rating-section {
            margin-bottom: 2rem;
        }
        
        .rating-title {
            font-weight: 600;
            margin-bottom: 1rem;
        }
        
        .star-rating {
            display: flex;
            flex-direction: row-reverse;
            justify-content: flex-end;
        }
        
        .star-rating input {
            display: none;
        }
        
        .star-rating label {
            color: #ddd;
            font-size: 2rem;
            padding: 0 0.2rem;
            cursor: pointer;
        }
        
        .star-rating input:checked ~ label,
        .star-rating label:hover,
        .star-rating label:hover ~ label {
            color: #ffc107;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
        }
        
        .form-control {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #e5e7eb;
            border-radius: 6px;
        }
        
        textarea.form-control {
            min-height: 120px;
            resize: vertical;
        }
        
        .form-hint {
            font-size: 0.8rem;
            color: #6b7280;
            margin-top: 0.5rem;
        }
        
        .form-actions {
            display: flex;
            justify-content: space-between;
            margin-top: 2rem;
            align-items: center;
        }
        
        .image-preview {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(100px, 1fr));
            gap: 0.5rem;
            margin-top: 1rem;
        }
        
        .preview-item {
            position: relative;
            width: 100px;
            height: 100px;
            border-radius: 4px;
            overflow: hidden;
        }
        
        .preview-item img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .preview-remove {
            position: absolute;
            top: 0.25rem;
            right: 0.25rem;
            background: rgba(0, 0, 0, 0.5);
            color: white;
            width: 20px;
            height: 20px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            font-size: 0.8rem;
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
                <a href="product.php?id=<?php echo $product_id; ?>"><?php echo htmlspecialchars($product['nom']); ?></a>
                <span class="separator">/</span>
                <span class="current"><?php echo $existing_review ? 'Modifier avis' : 'Nouvel avis'; ?></span>
            </nav>
        </div>
    </div>

    <div class="review-form-container">
        <?php 
        if (isset($_SESSION['flash_messages']) && !empty($_SESSION['flash_messages'])) {
            foreach ($_SESSION['flash_messages'] as $message) {
                echo '<div class="alert alert-'.$message['type'].'">';
                echo '<i class="fas fa-info-circle"></i>';
                echo '<span>'.$message['message'].'</span>';
                echo '</div>';
            }
            unset($_SESSION['flash_messages']);
        }
        ?>
        
        <div class="review-form-card">
            <!-- Product info -->
            <div class="product-info">
                <?php 
                    $images = json_decode($product['images'], true);
                    $image_url = !empty($images) ? getImageUrl($images[0]) : 'assets/images/placeholder.svg';
                ?>
                <img src="<?php echo $image_url; ?>" alt="<?php echo htmlspecialchars($product['nom']); ?>" class="product-image">
                <div>
                    <h2 class="product-name"><?php echo htmlspecialchars($product['nom']); ?></h2>
                    <p class="product-category">
                        <?php 
                            $stmt = $pdo->prepare("SELECT nom FROM categories WHERE id = ?");
                            $stmt->execute([$product['categorie_id']]);
                            $category = $stmt->fetch();
                            echo htmlspecialchars($category['nom'] ?? '');
                        ?>
                    </p>
                </div>
            </div>
            
            <!-- Review form -->
            <form method="POST" enctype="multipart/form-data">
                <!-- Star rating -->
                <div class="rating-section">
                    <div class="rating-title">Votre note</div>
                    <div class="star-rating">
                        <?php for ($i = 5; $i >= 1; $i--): ?>
                            <input type="radio" id="star<?php echo $i; ?>" name="note" value="<?php echo $i; ?>" <?php echo ($existing_review && $existing_review['note'] == $i) ? 'checked' : ''; ?>>
                            <label for="star<?php echo $i; ?>">★</label>
                        <?php endfor; ?>
                    </div>
                </div>
                
                <!-- Review title -->
                <div class="form-group">
                    <label for="titre">Titre de l'avis</label>
                    <input type="text" id="titre" name="titre" class="form-control" required 
                           value="<?php echo $existing_review ? htmlspecialchars($existing_review['titre']) : ''; ?>">
                </div>
                
                <!-- Review comment -->
                <div class="form-group">
                    <label for="commentaire">Votre commentaire</label>
                    <textarea id="commentaire" name="commentaire" class="form-control" required><?php echo $existing_review ? htmlspecialchars($existing_review['commentaire']) : ''; ?></textarea>
                    <p class="form-hint">Partagez votre expérience avec ce produit. Ce que vous avez aimé ou non, comment était la qualité, etc.</p>
                </div>
                
                <!-- Image upload -->
                <div class="form-group">
                    <label for="review_images">Ajouter des photos (optionnel)</label>
                    <input type="file" id="review_images" name="review_images[]" class="form-control" accept="image/*" multiple>
                    <p class="form-hint">Vous pouvez ajouter jusqu'à 3 photos. Formats acceptés: JPG, PNG (max 2MB par image)</p>
                    
                    <?php if ($existing_review && !empty($existing_review['images'])): ?>
                        <div class="image-preview" id="existingImages">
                            <?php foreach (json_decode($existing_review['images'], true) as $image): ?>
                                <div class="preview-item">
                                    <img src="assets/images/reviews/<?php echo $image; ?>" alt="Review image">
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                    
                    <div class="image-preview" id="imagePreview"></div>
                </div>
                
                <!-- Form actions -->
                <div class="form-actions">
                    <a href="<?php echo $order_exists ? "order-details.php?id={$order_id}" : "product.php?id={$product_id}"; ?>" class="btn btn-outline-secondary">Annuler</a>
                    <button type="submit" class="btn btn-primary">
                        <?php echo $existing_review ? 'Mettre à jour l\'avis' : 'Soumettre l\'avis'; ?>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Image preview functionality
            const imageInput = document.getElementById('review_images');
            const imagePreview = document.getElementById('imagePreview');
            
            imageInput.addEventListener('change', function() {
                imagePreview.innerHTML = '';
                
                if (this.files) {
                    const maxFiles = 3;
                    const maxFileCount = Math.min(this.files.length, maxFiles);
                    
                    for (let i = 0; i < maxFileCount; i++) {
                        const file = this.files[i];
                        
                        if (!file.type.startsWith('image/')) {
                            continue;
                        }
                        
                        const reader = new FileReader();
                        
                        reader.onload = function(e) {
                            const previewItem = document.createElement('div');
                            previewItem.className = 'preview-item';
                            
                            const img = document.createElement('img');
                            img.src = e.target.result;
                            
                            const removeBtn = document.createElement('span');
                            removeBtn.className = 'preview-remove';
                            removeBtn.innerHTML = '×';
                            removeBtn.addEventListener('click', function() {
                                previewItem.remove();
                            });
                            
                            previewItem.appendChild(img);
                            previewItem.appendChild(removeBtn);
                            imagePreview.appendChild(previewItem);
                        }
                        
                        reader.readAsDataURL(file);
                    }
                    
                    // Warning if too many files selected
                    if (this.files.length > maxFiles) {
                        alert(`Vous avez sélectionné ${this.files.length} fichiers, mais seuls les ${maxFiles} premiers seront utilisés.`);
                    }
                }
            });
        });
    </script>
</body>
</html>
