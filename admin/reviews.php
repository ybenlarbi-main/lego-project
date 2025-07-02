<?php
require_once '../config/config.php';

// Ensure user is logged in as admin
requireAdmin();

// Action handling
$action = $_GET['action'] ?? '';
$review_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($action && $review_id) {
    try {
        switch ($action) {
            case 'approve':
                $stmt = $pdo->prepare("UPDATE avis SET statut = 'approuve' WHERE id = ?");
                $stmt->execute([$review_id]);
                setFlashMessage('L\'avis a été approuvé', 'success');
                break;
                
            case 'reject':
                $stmt = $pdo->prepare("UPDATE avis SET statut = 'rejete' WHERE id = ?");
                $stmt->execute([$review_id]);
                setFlashMessage('L\'avis a été rejeté', 'success');
                break;
                
            case 'delete':
                $stmt = $pdo->prepare("DELETE FROM avis WHERE id = ?");
                $stmt->execute([$review_id]);
                setFlashMessage('L\'avis a été supprimé', 'success');
                break;
        }
    } catch (Exception $e) {
        setFlashMessage('Erreur: ' . $e->getMessage(), 'danger');
    }
    
    // Redirect to remove the action from URL
    header('Location: reviews.php');
    exit;
}

// Filter parameters
$filter_status = $_GET['status'] ?? 'all';
$filter_rating = isset($_GET['rating']) ? (int)$_GET['rating'] : 0;
$search = $_GET['search'] ?? '';

// Pagination setup
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 10;
$offset = ($page - 1) * $per_page;

// Base query
$query = "
    SELECT r.*, u.nom as client_nom, u.prenom as client_prenom, u.email as client_email,
           p.nom as product_nom, p.reference as product_reference
    FROM avis r
    JOIN utilisateurs u ON r.client_id = u.id
    JOIN produits p ON r.produit_id = p.id
    WHERE 1=1
";

$count_query = "
    SELECT COUNT(*) as total
    FROM avis r
    JOIN utilisateurs u ON r.client_id = u.id
    JOIN produits p ON r.produit_id = p.id
    WHERE 1=1
";

$params = [];

// Add filters
if ($filter_status !== 'all') {
    $query .= " AND r.statut = ?";
    $count_query .= " AND r.statut = ?";
    $params[] = $filter_status;
}

if ($filter_rating > 0) {
    $query .= " AND r.note = ?";
    $count_query .= " AND r.note = ?";
    $params[] = $filter_rating;
}

if (!empty($search)) {
    $query .= " AND (p.nom LIKE ? OR r.titre LIKE ? OR r.commentaire LIKE ? OR u.email LIKE ?)";
    $count_query .= " AND (p.nom LIKE ? OR r.titre LIKE ? OR r.commentaire LIKE ? OR u.email LIKE ?)";
    $search_param = "%{$search}%";
    $params = array_merge($params, [$search_param, $search_param, $search_param, $search_param]);
}

// Add order and limit
$query .= " ORDER BY CASE WHEN r.statut = 'en_attente' THEN 0 ELSE 1 END, r.date_creation DESC LIMIT ? OFFSET ?";
$params = array_merge($params, [$per_page, $offset]);

// Execute queries
$stmt = $pdo->prepare($count_query);
$stmt->execute($params);
$total_reviews = $stmt->fetch()['total'];
$total_pages = ceil($total_reviews / $per_page);

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$reviews = $stmt->fetchAll();

// Get counts for status filter
$stmt = $pdo->query("
    SELECT statut, COUNT(*) as count 
    FROM avis 
    GROUP BY statut
");
$status_counts = [];
foreach ($stmt->fetchAll() as $row) {
    $status_counts[$row['statut']] = $row['count'];
}
$total_count = array_sum($status_counts);

$page_title = 'Gestion des Avis';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - Admin</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .filter-toolbar {
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
            margin-bottom: 1.5rem;
            background-color: white;
            padding: 1rem;
            border-radius: 8px;
        }
        
        .filter-group {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .filter-label {
            font-weight: 500;
            font-size: 0.9rem;
        }
        
        .status-badge {
            display: inline-block;
            padding: 0.25rem 0.5rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 500;
        }
        
        .badge-pending {
            background-color: #fef3c7;
            color: #92400e;
        }
        
        .badge-approved {
            background-color: #d1fae5;
            color: #065f46;
        }
        
        .badge-rejected {
            background-color: #fee2e2;
            color: #b91c1c;
        }
        
        .star-rating {
            color: #fbbf24;
            white-space: nowrap;
        }
        
        .review-content {
            max-width: 300px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        
        .action-menu {
            position: relative;
        }
        
        .action-button {
            background: none;
            border: none;
            cursor: pointer;
            padding: 0.5rem;
        }
        
        .action-dropdown {
            position: absolute;
            right: 0;
            top: 100%;
            background-color: white;
            border-radius: 6px;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
            width: 160px;
            z-index: 10;
            display: none;
        }
        
        .action-dropdown.show {
            display: block;
        }
        
        .action-item {
            display: block;
            padding: 0.75rem 1rem;
            color: #374151;
            text-decoration: none;
            transition: background-color 0.2s;
        }
        
        .action-item:hover {
            background-color: #f3f4f6;
        }
        
        .action-item i {
            width: 20px;
            margin-right: 0.5rem;
        }
        
        .review-images {
            display: flex;
            gap: 0.5rem;
        }
        
        .review-image {
            width: 40px;
            height: 40px;
            border-radius: 4px;
            object-fit: cover;
            cursor: pointer;
        }
        
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            padding-top: 50px;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0, 0, 0, 0.9);
        }
        
        .modal-content {
            margin: auto;
            display: block;
            max-width: 80%;
            max-height: 80%;
        }
        
        .close {
            position: absolute;
            top: 15px;
            right: 35px;
            color: #f1f1f1;
            font-size: 40px;
            font-weight: bold;
            transition: 0.3s;
        }
        
        .close:hover,
        .close:focus {
            color: #bbb;
            text-decoration: none;
            cursor: pointer;
        }
        
        .review-detail-modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0, 0, 0, 0.5);
        }
        
        .review-detail-content {
            background-color: white;
            margin: 50px auto;
            padding: 20px;
            border-radius: 10px;
            max-width: 800px;
            width: 90%;
        }
        
        .review-detail-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid #e5e7eb;
        }
        
        .review-detail-header h3 {
            margin: 0;
        }
        
        .review-detail-close {
            font-size: 24px;
            font-weight: bold;
            cursor: pointer;
        }
        
        .review-detail-info {
            margin-bottom: 20px;
        }
        
        .review-detail-product {
            display: flex;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .review-detail-product-image {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 8px;
            margin-right: 20px;
        }
        
        .review-detail-content {
            margin-bottom: 20px;
        }
        
        .review-detail-images {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }
        
        .review-detail-image {
            width: 100px;
            height: 100px;
            object-fit: cover;
            border-radius: 8px;
            cursor: pointer;
        }
        
        .review-detail-actions {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <div class="admin-layout">
        <?php include 'sidebar.php'; ?>
        
        <main class="admin-main">
            <header class="admin-header">
                <h1><?php echo $page_title; ?></h1>
                <div class="admin-header-actions">
                    <a href="index.php" class="btn-outline">Tableau de bord</a>
                </div>
            </header>
            
            <div class="admin-content">
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
                
                <!-- Filter toolbar -->
                <div class="filter-toolbar">
                    <form action="" method="GET" class="flex-grow flex gap-4">
                        <div class="filter-group">
                            <label for="status" class="filter-label">Statut:</label>
                            <select id="status" name="status" class="form-select" onchange="this.form.submit()">
                                <option value="all" <?php echo $filter_status === 'all' ? 'selected' : ''; ?>>
                                    Tous (<?php echo $total_count; ?>)
                                </option>
                                <option value="en_attente" <?php echo $filter_status === 'en_attente' ? 'selected' : ''; ?>>
                                    En attente (<?php echo $status_counts['en_attente'] ?? 0; ?>)
                                </option>
                                <option value="approuve" <?php echo $filter_status === 'approuve' ? 'selected' : ''; ?>>
                                    Approuvé (<?php echo $status_counts['approuve'] ?? 0; ?>)
                                </option>
                                <option value="rejete" <?php echo $filter_status === 'rejete' ? 'selected' : ''; ?>>
                                    Rejeté (<?php echo $status_counts['rejete'] ?? 0; ?>)
                                </option>
                            </select>
                        </div>
                        
                        <div class="filter-group">
                            <label for="rating" class="filter-label">Note:</label>
                            <select id="rating" name="rating" class="form-select" onchange="this.form.submit()">
                                <option value="0" <?php echo $filter_rating === 0 ? 'selected' : ''; ?>>Toutes les notes</option>
                                <?php for ($i = 5; $i >= 1; $i--): ?>
                                    <option value="<?php echo $i; ?>" <?php echo $filter_rating === $i ? 'selected' : ''; ?>>
                                        <?php echo $i; ?> étoile<?php echo $i > 1 ? 's' : ''; ?>
                                    </option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        
                        <div class="filter-group flex-grow">
                            <input type="text" name="search" class="form-input" placeholder="Rechercher..." value="<?php echo htmlspecialchars($search); ?>">
                            <button type="submit" class="btn-icon"><i class="fas fa-search"></i></button>
                            <?php if (!empty($search) || $filter_status !== 'all' || $filter_rating > 0): ?>
                                <a href="reviews.php" class="btn-icon ml-2" title="Réinitialiser les filtres"><i class="fas fa-times"></i></a>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
                
                <!-- Reviews table -->
                <div class="card mb-5">
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Produit</th>
                                        <th>Client</th>
                                        <th>Note</th>
                                        <th>Titre & Commentaire</th>
                                        <th>Date</th>
                                        <th>Statut</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($reviews)): ?>
                                        <tr>
                                            <td colspan="7" class="text-center py-8">
                                                <p class="text-gray-500">Aucun avis trouvé</p>
                                            </td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($reviews as $review): ?>
                                            <tr>
                                                <td>
                                                    <div>
                                                        <a href="../product.php?id=<?php echo $review['produit_id']; ?>" target="_blank" class="font-medium">
                                                            <?php echo htmlspecialchars($review['product_nom']); ?>
                                                        </a>
                                                        <div class="text-xs text-gray-500">
                                                            Réf: <?php echo htmlspecialchars($review['product_reference']); ?>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>
                                                    <div>
                                                        <?php echo htmlspecialchars($review['client_prenom'] . ' ' . $review['client_nom']); ?>
                                                        <div class="text-xs text-gray-500">
                                                            <?php echo htmlspecialchars($review['client_email']); ?>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>
                                                    <div class="star-rating">
                                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                                            <?php echo $i <= $review['note'] ? '★' : '☆'; ?>
                                                        <?php endfor; ?>
                                                    </div>
                                                </td>
                                                <td>
                                                    <div class="review-content">
                                                        <strong><?php echo htmlspecialchars($review['titre']); ?></strong><br>
                                                        <span class="text-sm text-gray-500">
                                                            <?php echo htmlspecialchars(mb_substr($review['commentaire'], 0, 50)); ?>
                                                            <?php echo strlen($review['commentaire']) > 50 ? '...' : ''; ?>
                                                        </span>
                                                    </div>
                                                    <?php if (!empty($review['images'])): ?>
                                                        <div class="review-images mt-2">
                                                            <?php foreach (json_decode($review['images'], true) as $index => $image): ?>
                                                                <img src="../assets/images/reviews/<?php echo $image; ?>" alt="Review image" class="review-image" onclick="showImageModal(this.src)">
                                                            <?php endforeach; ?>
                                                        </div>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <div>
                                                        <?php echo date('d/m/Y', strtotime($review['date_creation'])); ?>
                                                        <div class="text-xs text-gray-500">
                                                            <?php echo date('H:i', strtotime($review['date_creation'])); ?>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>
                                                    <?php
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
                                                    ?>
                                                    <span class="status-badge <?php echo $status_class; ?>">
                                                        <?php echo $status_text; ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <div class="action-menu">
                                                        <button class="action-button" onclick="toggleDropdown(this)">
                                                            <i class="fas fa-ellipsis-v"></i>
                                                        </button>
                                                        <div class="action-dropdown">
                                                            <a href="#" class="action-item" onclick="viewReviewDetails(<?php echo $review['id']; ?>); return false;">
                                                                <i class="fas fa-eye"></i> Voir détails
                                                            </a>
                                                            <?php if ($review['statut'] !== 'approuve'): ?>
                                                                <a href="?action=approve&id=<?php echo $review['id']; ?>" class="action-item">
                                                                    <i class="fas fa-check"></i> Approuver
                                                                </a>
                                                            <?php endif; ?>
                                                            <?php if ($review['statut'] !== 'rejete'): ?>
                                                                <a href="?action=reject&id=<?php echo $review['id']; ?>" class="action-item">
                                                                    <i class="fas fa-ban"></i> Rejeter
                                                                </a>
                                                            <?php endif; ?>
                                                            <a href="?action=delete&id=<?php echo $review['id']; ?>" class="action-item" onclick="return confirm('Êtes-vous sûr de vouloir supprimer cet avis ?')">
                                                                <i class="fas fa-trash"></i> Supprimer
                                                            </a>
                                                        </div>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                
                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                    <div class="pagination">
                        <?php if ($page > 1): ?>
                            <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>" class="pagination-item">
                                <i class="fas fa-chevron-left"></i>
                            </a>
                        <?php endif; ?>
                        
                        <?php
                        $range = 2;
                        $shown_pages = 5;
                        
                        // Calculate range of pages to show
                        if ($total_pages <= $shown_pages) {
                            $start_page = 1;
                            $end_page = $total_pages;
                        } else {
                            $start_page = max(1, $page - $range);
                            $end_page = min($total_pages, $page + $range);
                            
                            // Adjust if we're near the beginning or end
                            if ($start_page <= $shown_pages - $range) {
                                $end_page = $shown_pages;
                            }
                            if ($end_page > $total_pages - $shown_pages + $range) {
                                $start_page = $total_pages - $shown_pages + 1;
                            }
                        }
                        ?>
                        
                        <?php for ($i = $start_page; $i <= $end_page; $i++): ?>
                            <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>" 
                               class="pagination-item <?php echo ($i === $page) ? 'active' : ''; ?>">
                                <?php echo $i; ?>
                            </a>
                        <?php endfor; ?>
                        
                        <?php if ($page < $total_pages): ?>
                            <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>" class="pagination-item">
                                <i class="fas fa-chevron-right"></i>
                            </a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>
    
    <!-- Image modal -->
    <div id="imageModal" class="modal">
        <span class="close">&times;</span>
        <img class="modal-content" id="modalImage">
    </div>
    
    <!-- Review detail modal -->
    <div id="reviewDetailModal" class="review-detail-modal">
        <div class="review-detail-content">
            <div class="review-detail-header">
                <h3>Détails de l'avis</h3>
                <span class="review-detail-close">&times;</span>
            </div>
            <div id="reviewDetailContent">
                Loading...
            </div>
        </div>
    </div>

    <script>
        // Toggle dropdown menu
        function toggleDropdown(button) {
            // Close all open dropdowns first
            document.querySelectorAll('.action-dropdown.show').forEach(dropdown => {
                if (dropdown !== button.nextElementSibling) {
                    dropdown.classList.remove('show');
                }
            });
            
            // Toggle the current dropdown
            button.nextElementSibling.classList.toggle('show');
        }
        
        // Close dropdowns when clicking outside
        document.addEventListener('click', function(event) {
            if (!event.target.matches('.action-button') && !event.target.matches('.fa-ellipsis-v')) {
                document.querySelectorAll('.action-dropdown.show').forEach(dropdown => {
                    dropdown.classList.remove('show');
                });
            }
        });
        
        // Image modal functionality
        const modal = document.getElementById("imageModal");
        const modalImg = document.getElementById("modalImage");
        const modalClose = document.querySelector("#imageModal .close");
        
        function showImageModal(src) {
            modal.style.display = "block";
            modalImg.src = src;
        }
        
        modalClose.onclick = function() {
            modal.style.display = "none";
        }
        
        // Review detail modal
        const reviewModal = document.getElementById("reviewDetailModal");
        const reviewContent = document.getElementById("reviewDetailContent");
        const reviewClose = document.querySelector(".review-detail-close");
        
        function viewReviewDetails(reviewId) {
            reviewModal.style.display = "block";
            reviewContent.innerHTML = "Chargement...";
            
            // Fetch review details
            fetch(`review-detail.php?id=${reviewId}`)
                .then(response => response.text())
                .then(data => {
                    reviewContent.innerHTML = data;
                })
                .catch(error => {
                    reviewContent.innerHTML = `<p class="text-red-500">Erreur lors du chargement des détails: ${error.message}</p>`;
                });
        }
        
        reviewClose.onclick = function() {
            reviewModal.style.display = "none";
        }
        
        // Close modal when clicking outside
        window.onclick = function(event) {
            if (event.target == modal) {
                modal.style.display = "none";
            }
            if (event.target == reviewModal) {
                reviewModal.style.display = "none";
            }
        }
    </script>
</body>
</html>
