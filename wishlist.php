<?php
require_once 'config/config.php';

// Require login for wishlist
requireLogin();

$user_id = $_SESSION['user_id'];

// Fetch wishlist items
$stmt = $pdo->prepare("
    SELECT p.*, f.date_ajout as date_favoris,
           COALESCE(AVG(r.note), 0) as avg_rating,
           COUNT(r.id) as review_count,
           c.nom as categorie_nom
    FROM favoris f
    JOIN produits p ON f.produit_id = p.id
    LEFT JOIN categories c ON p.categorie_id = c.id
    LEFT JOIN avis r ON p.id = r.produit_id AND r.statut = 'approuve'
    WHERE f.client_id = ? AND p.statut = 'actif'
    GROUP BY p.id
    ORDER BY f.date_ajout DESC
");
$stmt->execute([$user_id]);
$wishlist_items = $stmt->fetchAll();

$page_title = 'Ma Liste de Souhaits';
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
        .wishlist-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem 1rem;
        }
        
        .wishlist-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }
        
        .wishlist-header .count {
            color: #6b7280;
        }
        
        .wishlist-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 2rem;
        }
        
        .wishlist-item {
            background: white;
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
            overflow: hidden;
            transition: transform 0.2s, box-shadow 0.2s;
            position: relative;
        }
        
        .wishlist-item:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 16px rgba(0,0,0,0.1);
        }
        
        .wishlist-item-image {
            height: 220px;
            overflow: hidden;
            position: relative;
        }
        
        .wishlist-item-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.3s;
        }
        
        .wishlist-item:hover .wishlist-item-image img {
            transform: scale(1.05);
        }
        
        .wishlist-item-info {
            padding: 1.5rem;
        }
        
        .wishlist-item-name {
            font-weight: 600;
            margin-bottom: 0.5rem;
            font-size: 1.1rem;
        }
        
        .wishlist-item-category {
            color: #6b7280;
            font-size: 0.875rem;
            margin-bottom: 0.75rem;
        }
        
        .wishlist-item-price {
            font-weight: 600;
            font-size: 1.2rem;
            color: #4f46e5;
            margin-bottom: 1rem;
        }
        
        .wishlist-item-price .original-price {
            text-decoration: line-through;
            color: #6b7280;
            font-size: 0.9rem;
            margin-right: 0.5rem;
        }
        
        .wishlist-item-actions {
            display: flex;
            gap: 0.5rem;
        }
        
        .btn-add-to-cart {
            display: inline-block;
            padding: 0.75rem 1rem;
            background: #4f46e5;
            color: white;
            border: none;
            border-radius: 6px;
            font-weight: 500;
            font-size: 0.875rem;
            text-decoration: none;
            flex: 1;
            text-align: center;
            cursor: pointer;
            transition: background-color 0.2s;
        }
        
        .btn-add-to-cart:hover {
            background: #4338ca;
        }
        
        .btn-remove {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0.75rem;
            background: #f3f4f6;
            color: #374151;
            border: none;
            border-radius: 6px;
            font-size: 0.875rem;
            cursor: pointer;
            transition: background-color 0.2s;
        }
        
        .btn-remove:hover {
            background: #e5e7eb;
        }
        
        .wishlist-item-date {
            font-size: 0.75rem;
            color: #9ca3af;
            margin-top: 1rem;
        }
        
        .empty-wishlist {
            background: white;
            padding: 3rem;
            text-align: center;
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
        }
        
        .empty-wishlist i {
            font-size: 4rem;
            color: #d1d5db;
            margin-bottom: 1.5rem;
        }
        
        .stock-label {
            position: absolute;
            top: 1rem;
            right: 1rem;
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 600;
            z-index: 2;
        }
        
        .in-stock {
            background-color: #d1fae5;
            color: #065f46;
        }
        
        .low-stock {
            background-color: #ffedd5;
            color: #9a3412;
        }
        
        .out-of-stock {
            background-color: #fee2e2;
            color: #b91c1c;
        }
        
        @media (max-width: 768px) {
            .wishlist-grid {
                grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
                gap: 1rem;
            }
        }
        
        @media (max-width: 480px) {
            .wishlist-grid {
                grid-template-columns: 1fr;
                gap: 1rem;
            }
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
                <a href="profile.php">Mon Profil</a>
                <span class="separator">/</span>
                <span class="current">Liste de Souhaits</span>
            </nav>
        </div>
    </div>

    <div class="wishlist-container">
        <div class="wishlist-header">
            <h2>Produits favoris <span class="count">(<?php echo count($wishlist_items); ?>)</span></h2>
        </div>

        <?php if (empty($wishlist_items)): ?>
            <div class="empty-wishlist">
                <i class="far fa-heart"></i>
                <h3>Votre liste de souhaits est vide</h3>
                <p>Parcourez notre catalogue et ajoutez des produits à vos favoris.</p>
                <a href="produits.php" class="btn btn-primary mt-4">Découvrir nos produits</a>
            </div>
        <?php else: ?>
            <div class="wishlist-grid">
                <?php foreach ($wishlist_items as $item): ?>
                    <?php 
                        $images = json_decode($item['images'], true);
                        $image_url = !empty($images) ? getImageUrl($images[0]) : 'assets/images/placeholder.svg';
                        
                        // Stock status
                        $stock_status = '';
                        $stock_class = '';
                        if ($item['stock'] <= 0) {
                            $stock_status = 'Rupture de stock';
                            $stock_class = 'out-of-stock';
                        } elseif ($item['stock'] <= 5) {
                            $stock_status = 'Stock limité';
                            $stock_class = 'low-stock';
                        } else {
                            $stock_status = 'En stock';
                            $stock_class = 'in-stock';
                        }
                    ?>
                    <div class="wishlist-item" data-id="<?php echo $item['id']; ?>">
                        <span class="stock-label <?php echo $stock_class; ?>"><?php echo $stock_status; ?></span>
                        <a href="product.php?id=<?php echo $item['id']; ?>" class="wishlist-item-image">
                            <img src="<?php echo $image_url; ?>" alt="<?php echo htmlspecialchars($item['nom']); ?>">
                        </a>
                        <div class="wishlist-item-info">
                            <h3 class="wishlist-item-name">
                                <a href="product.php?id=<?php echo $item['id']; ?>"><?php echo htmlspecialchars($item['nom']); ?></a>
                            </h3>
                            <div class="wishlist-item-category"><?php echo htmlspecialchars($item['categorie_nom']); ?></div>
                            <div class="wishlist-item-price">
                                <?php if ($item['prix_promo']): ?>
                                    <span class="original-price"><?php echo formatPrice($item['prix']); ?></span>
                                    <?php echo formatPrice($item['prix_promo']); ?>
                                <?php else: ?>
                                    <?php echo formatPrice($item['prix']); ?>
                                <?php endif; ?>
                            </div>
                            <div class="wishlist-item-actions">
                                <button class="btn-add-to-cart add-to-cart-btn" <?php echo $item['stock'] <= 0 ? 'disabled' : ''; ?> data-id="<?php echo $item['id']; ?>">
                                    <i class="fas fa-shopping-cart"></i> Ajouter au panier
                                </button>
                                <button class="btn-remove remove-from-wishlist-btn" data-id="<?php echo $item['id']; ?>">
                                    <i class="fas fa-trash-alt"></i>
                                </button>
                            </div>
                            <div class="wishlist-item-date">
                                Ajouté le <?php echo date('d/m/Y', strtotime($item['date_favoris'])); ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <?php include 'includes/footer.php'; ?>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Fetch cart count
            fetchCartCount();
            
            // Setup event listeners for wishlist actions
            setupWishlistActions();
        });

        function fetchCartCount() {
            fetch('api/cart.php?action=count')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        document.getElementById('cart-count').innerText = data.count;
                    }
                })
                .catch(error => console.error('Error fetching cart count:', error));
        }
        
        function setupWishlistActions() {
            // Add to cart buttons
            document.querySelectorAll('.add-to-cart-btn').forEach(button => {
                button.addEventListener('click', function() {
                    const productId = this.getAttribute('data-id');
                    
                    // Send request to add to cart
                    fetch('api/cart.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
                            action: 'add',
                            product_id: productId,
                            quantity: 1
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            // Show success message
                            alert('Produit ajouté au panier');
                            // Update cart count
                            fetchCartCount();
                        } else {
                            alert(data.message || 'Erreur lors de l\'ajout au panier');
                        }
                    })
                    .catch(error => {
                        console.error('Error adding to cart:', error);
                        alert('Une erreur est survenue');
                    });
                });
            });
            
            // Remove from wishlist buttons
            document.querySelectorAll('.remove-from-wishlist-btn').forEach(button => {
                button.addEventListener('click', function() {
                    const productId = this.getAttribute('data-id');
                    const wishlistItem = this.closest('.wishlist-item');
                    
                    if (confirm('Êtes-vous sûr de vouloir retirer ce produit de vos favoris ?')) {
                        // Send request to remove from wishlist
                        fetch('api/wishlist.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                            },
                            body: JSON.stringify({
                                action: 'remove',
                                product_id: productId
                            })
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                // Remove item from DOM with animation
                                wishlistItem.style.opacity = '0';
                                setTimeout(() => {
                                    wishlistItem.remove();
                                    
                                    // Check if wishlist is empty and reload if needed
                                    if (document.querySelectorAll('.wishlist-item').length === 0) {
                                        location.reload();
                                    }
                                }, 300);
                            } else {
                                alert(data.message || 'Erreur lors de la suppression');
                            }
                        })
                        .catch(error => {
                            console.error('Error removing from wishlist:', error);
                            alert('Une erreur est survenue');
                        });
                    }
                });
            });
        }
    </script>
</body>
</html>
