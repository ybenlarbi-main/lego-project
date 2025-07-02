<?php
require_once '../config/config.php';

// Ensure user is logged in as admin
requireAdmin();

// Check if review ID is provided
$review_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$review_id) {
    echo '<p class="text-red-500">ID d\'avis manquant</p>';
    exit;
}

// Fetch review details
$stmt = $pdo->prepare("
    SELECT r.*, 
           u.id as client_id, u.nom as client_nom, u.prenom as client_prenom, u.email as client_email,
           p.id as product_id, p.nom as product_nom, p.reference as product_reference, 
           p.categorie_id, p.images as product_images,
           c.nom as category_nom
    FROM avis r
    JOIN utilisateurs u ON r.client_id = u.id
    JOIN produits p ON r.produit_id = p.id
    LEFT JOIN categories c ON p.categorie_id = c.id
    WHERE r.id = ?
");
$stmt->execute([$review_id]);
$review = $stmt->fetch();

if (!$review) {
    echo '<p class="text-red-500">Avis non trouvé</p>';
    exit;
}

// Get status badge classes
$status_class = '';
$status_text = '';

switch ($review['statut']) {
    case 'en_attente':
        $status_class = 'badge-pending';
        $status_text = 'En attente';
        break;
    case 'approuve':
        $status_class = 'badge-approved';
        $status_text = 'Approuvé';
        break;
    case 'rejete':
        $status_class = 'badge-rejected';
        $status_text = 'Rejeté';
        break;
}

// Get product image
$product_images = json_decode($review['product_images'], true);
$product_image = !empty($product_images) ? getImageUrl($product_images[0]) : '../assets/images/placeholder.svg';

// Get review images
$review_images = [];
if (!empty($review['images'])) {
    $review_images = json_decode($review['images'], true);
}
?>

<!-- Review Detail Content -->
<div class="review-detail-info">
    <div class="grid grid-cols-2 gap-4 mb-4">
        <div>
            <p class="text-sm text-gray-500">Client</p>
            <p class="font-medium"><?php echo htmlspecialchars($review['client_prenom'] . ' ' . $review['client_nom']); ?></p>
            <p class="text-sm"><?php echo htmlspecialchars($review['client_email']); ?></p>
        </div>
        <div>
            <p class="text-sm text-gray-500">Date</p>
            <p class="font-medium"><?php echo date('d/m/Y à H:i', strtotime($review['date_creation'])); ?></p>
            <?php if ($review['date_creation'] != $review['date_modification']): ?>
                <p class="text-sm">Modifié le: <?php echo date('d/m/Y à H:i', strtotime($review['date_modification'])); ?></p>
            <?php endif; ?>
        </div>
    </div>
    
    <div class="flex justify-between items-center mb-4">
        <div>
            <p class="text-sm text-gray-500">Statut</p>
            <span class="status-badge <?php echo $status_class; ?>">
                <?php echo $status_text; ?>
            </span>
        </div>
        <div>
            <p class="text-sm text-gray-500">Note</p>
            <div class="star-rating" style="font-size: 1.5rem;">
                <?php for ($i = 1; $i <= 5; $i++): ?>
                    <?php echo $i <= $review['note'] ? '★' : '☆'; ?>
                <?php endfor; ?>
            </div>
        </div>
    </div>
</div>

<div class="review-detail-product">
    <img src="<?php echo $product_image; ?>" alt="<?php echo htmlspecialchars($review['product_nom']); ?>" class="review-detail-product-image">
    <div>
        <p class="text-sm text-gray-500">Produit</p>
        <p class="font-medium">
            <a href="../product.php?id=<?php echo $review['product_id']; ?>" target="_blank">
                <?php echo htmlspecialchars($review['product_nom']); ?>
            </a>
        </p>
        <p class="text-sm">
            <?php echo htmlspecialchars($review['category_nom']); ?> • 
            Réf: <?php echo htmlspecialchars($review['product_reference']); ?>
        </p>
    </div>
</div>

<div class="review-detail-content">
    <p class="text-sm text-gray-500">Titre</p>
    <p class="font-medium text-lg mb-3"><?php echo htmlspecialchars($review['titre']); ?></p>
    
    <p class="text-sm text-gray-500">Commentaire</p>
    <div class="bg-gray-50 p-3 rounded mb-4">
        <?php echo nl2br(htmlspecialchars($review['commentaire'])); ?>
    </div>
    
    <?php if (!empty($review_images)): ?>
        <p class="text-sm text-gray-500">Images</p>
        <div class="review-detail-images">
            <?php foreach ($review_images as $image): ?>
                <img src="../assets/images/reviews/<?php echo $image; ?>" alt="Review image" class="review-detail-image" onclick="showImageModal('../assets/images/reviews/<?php echo $image; ?>')">
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
    
    <?php if ($review['commande_id']): ?>
        <div class="mt-4 pt-4 border-t border-gray-200">
            <p class="text-sm text-gray-500">Commande associée</p>
            <p class="font-medium">
                <a href="orders.php?id=<?php echo $review['commande_id']; ?>" class="text-blue-600 hover:underline">
                    Voir la commande #<?php echo $review['commande_id']; ?>
                </a>
            </p>
        </div>
    <?php endif; ?>
</div>

<div class="review-detail-actions">
    <?php if ($review['statut'] !== 'approuve'): ?>
        <a href="?action=approve&id=<?php echo $review['id']; ?>" class="btn-success">
            <i class="fas fa-check mr-2"></i> Approuver
        </a>
    <?php endif; ?>
    
    <?php if ($review['statut'] !== 'rejete'): ?>
        <a href="?action=reject&id=<?php echo $review['id']; ?>" class="btn-warning">
            <i class="fas fa-ban mr-2"></i> Rejeter
        </a>
    <?php endif; ?>
    
    <a href="?action=delete&id=<?php echo $review['id']; ?>" class="btn-danger" onclick="return confirm('Êtes-vous sûr de vouloir supprimer cet avis ?')">
        <i class="fas fa-trash mr-2"></i> Supprimer
    </a>
</div>
