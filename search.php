<?php
require_once 'config/config.php';

// Get search query
$search_query = isset($_GET['q']) ? trim($_GET['q']) : '';
$category_id = isset($_GET['category']) ? (int)$_GET['category'] : 0;
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'default';
$min_price = isset($_GET['min_price']) ? (float)$_GET['min_price'] : 0;
$max_price = isset($_GET['max_price']) ? (float)$_GET['max_price'] : 100000;

// Pagination setup
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 12;
$offset = ($page - 1) * $per_page;

// Base query
$query = "
    SELECT p.*, c.nom as categorie_nom,
           COALESCE(AVG(r.note), 0) as avg_rating,
           COUNT(r.id) as review_count
    FROM produits p
    LEFT JOIN categories c ON p.categorie_id = c.id
    LEFT JOIN avis r ON p.id = r.produit_id AND r.statut = 'approuve'
    WHERE p.statut = 'actif'
";

$count_query = "
    SELECT COUNT(*) as total
    FROM produits p
    LEFT JOIN categories c ON p.categorie_id = c.id
    WHERE p.statut = 'actif'
";

$params = [];

// Add search conditions
if (!empty($search_query)) {
    $query .= " AND (p.nom LIKE ? OR p.description LIKE ? OR p.reference LIKE ? OR c.nom LIKE ?)";
    $count_query .= " AND (p.nom LIKE ? OR p.description LIKE ? OR p.reference LIKE ? OR c.nom LIKE ?)";
    $search_param = "%{$search_query}%";
    $params = array_merge($params, [$search_param, $search_param, $search_param, $search_param]);
}

// Add category filter
if ($category_id > 0) {
    $query .= " AND p.categorie_id = ?";
    $count_query .= " AND p.categorie_id = ?";
    $params[] = $category_id;
}

// Add price range filter
$query .= " AND (COALESCE(p.prix_promo, p.prix) >= ? AND COALESCE(p.prix_promo, p.prix) <= ?)";
$count_query .= " AND (COALESCE(p.prix_promo, p.prix) >= ? AND COALESCE(p.prix_promo, p.prix) <= ?)";
$params[] = $min_price;
$params[] = $max_price;

// Group by
$query .= " GROUP BY p.id";

// Add sorting
switch ($sort) {
    case 'price_asc':
        $query .= " ORDER BY COALESCE(p.prix_promo, p.prix) ASC";
        break;
    case 'price_desc':
        $query .= " ORDER BY COALESCE(p.prix_promo, p.prix) DESC";
        break;
    case 'newest':
        $query .= " ORDER BY p.date_creation DESC";
        break;
    case 'popular':
        $query .= " ORDER BY p.featured DESC, avg_rating DESC";
        break;
    case 'name_asc':
        $query .= " ORDER BY p.nom ASC";
        break;
    case 'name_desc':
        $query .= " ORDER BY p.nom DESC";
        break;
    default:
        $query .= " ORDER BY p.featured DESC, p.id DESC";
}

// Add limit for pagination
$query .= " LIMIT ? OFFSET ?";
$params[] = $per_page;
$params[] = $offset;

// Execute count query
$stmt = $pdo->prepare($count_query);
$stmt->execute($params);
$total_products = $stmt->fetch()['total'];
$total_pages = ceil($total_products / $per_page);

// Execute search query
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$products = $stmt->fetchAll();

// Get all categories for filter
$stmt = $pdo->query("SELECT id, nom FROM categories WHERE statut = 'actif' ORDER BY nom ASC");
$categories = $stmt->fetchAll();

$page_title = !empty($search_query) ? "Résultats pour : {$search_query}" : "Tous nos produits";
$page_description = !empty($search_query) ? 
    "Découvrez nos produits correspondant à votre recherche : {$search_query}" : 
    "Parcourez notre catalogue complet de produits Menalego inspirés du patrimoine marocain";
?>
<!DOCTYPE html>
<html lang="<?php echo getCurrentLanguage(); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - <?php echo SITE_NAME; ?></title>
    <meta name="description" content="<?php echo $page_description; ?>">
    <link rel="stylesheet" href="assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        .search-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 2rem 1rem;
        }
        
        .search-layout {
            display: grid;
            grid-template-columns: 280px 1fr;
            gap: 2rem;
        }
        
        .search-filters {
            background: white;
            border-radius: 10px;
            padding: 1.5rem;
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
            height: fit-content;
        }
        
        .filter-section {
            margin-bottom: 1.5rem;
            padding-bottom: 1.5rem;
            border-bottom: 1px solid #f0f0f0;
        }
        
        .filter-section:last-child {
            margin-bottom: 0;
            padding-bottom: 0;
            border-bottom: none;
        }
        
        .filter-section h4 {
            font-size: 1rem;
            font-weight: 600;
            margin-bottom: 1rem;
        }
        
        .filter-section label {
            display: block;
            margin-bottom: 0.5rem;
        }
        
        .price-range {
            display: flex;
            gap: 0.5rem;
            align-items: center;
        }
        
        .price-input {
            width: 100%;
            padding: 0.5rem;
            border: 1px solid #e5e7eb;
            border-radius: 6px;
        }
        
        .search-results-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }
        
        .search-count {
            color: #6b7280;
        }
        
        .sort-select {
            padding: 0.5rem 2rem 0.5rem 1rem;
            border: 1px solid #e5e7eb;
            border-radius: 6px;
            background-color: white;
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' viewBox='0 0 24 24' fill='none' stroke='%236b7280' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 0.5rem center;
        }
        
        .product-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 2rem;
        }
        
        .product-card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
            overflow: hidden;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        
        .product-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 16px rgba(0,0,0,0.1);
        }
        
        .product-image {
            height: 200px;
            overflow: hidden;
        }
        
        .product-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.3s;
        }
        
        .product-card:hover .product-image img {
            transform: scale(1.05);
        }
        
        .product-info {
            padding: 1.5rem;
        }
        
        .product-category {
            font-size: 0.75rem;
            color: #6b7280;
            margin-bottom: 0.5rem;
        }
        
        .product-name {
            font-weight: 600;
            margin-bottom: 0.5rem;
        }
        
        .product-price {
            font-weight: 600;
            color: #4f46e5;
            margin-bottom: 1rem;
        }
        
        .product-price .original-price {
            text-decoration: line-through;
            color: #6b7280;
            font-size: 0.9rem;
            margin-right: 0.5rem;
        }
        
        .product-rating {
            display: flex;
            align-items: center;
            margin-bottom: 1rem;
        }
        
        .stars {
            color: #fbbf24;
            font-size: 0.8rem;
            margin-right: 0.5rem;
        }
        
        .rating-count {
            font-size: 0.75rem;
            color: #6b7280;
        }
        
        .product-button {
            width: 100%;
            padding: 0.75rem;
            background: #4f46e5;
            color: white;
            border: none;
            border-radius: 6px;
            font-weight: 500;
            text-align: center;
            text-decoration: none;
            display: inline-block;
            cursor: pointer;
            transition: background-color 0.2s;
        }
        
        .product-button:hover {
            background: #4338ca;
        }
        
        .no-results {
            background: white;
            padding: 3rem;
            text-align: center;
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
            grid-column: span 3;
        }
        
        .no-results i {
            font-size: 4rem;
            color: #d1d5db;
            margin-bottom: 1.5rem;
        }
        
        .pagination {
            display: flex;
            justify-content: center;
            margin-top: 2rem;
            list-style: none;
            padding: 0;
        }
        
        .pagination li {
            margin: 0 0.25rem;
        }
        
        .pagination a, .pagination span {
            display: inline-block;
            padding: 0.5rem 1rem;
            border: 1px solid #e5e7eb;
            border-radius: 6px;
            background: white;
            color: #374151;
            text-decoration: none;
        }
        
        .pagination a:hover {
            background: #f3f4f6;
        }
        
        .pagination .active span {
            background: #4f46e5;
            color: white;
            border-color: #4f46e5;
        }
        
        .mobile-filter-toggle {
            display: none;
            margin-bottom: 1rem;
            padding: 0.75rem 1rem;
            background: white;
            border: 1px solid #e5e7eb;
            border-radius: 6px;
            font-weight: 500;
        }
        
        @media (max-width: 992px) {
            .search-layout {
                grid-template-columns: 1fr;
            }
            
            .mobile-filter-toggle {
                display: flex;
                align-items: center;
                justify-content: space-between;
            }
            
            .search-filters {
                display: none;
            }
            
            .search-filters.active {
                display: block;
            }
        }
        
        @media (max-width: 768px) {
            .product-grid {
                grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
                gap: 1rem;
            }
        }
        
        @media (max-width: 480px) {
            .product-grid {
                grid-template-columns: 1fr 1fr;
                gap: 1rem;
            }
            
            .search-results-header {
                flex-direction: column;
                align-items: flex-start;
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
                <span class="current">Recherche</span>
            </nav>
        </div>
    </div>

    <div class="search-container">
        <!-- Mobile filter toggle -->
        <button class="mobile-filter-toggle" id="toggleFilters">
            <span><i class="fas fa-filter"></i> Filtres</span>
            <i class="fas fa-chevron-down"></i>
        </button>
        
        <div class="search-layout">
            <!-- Filters sidebar -->
            <aside class="search-filters" id="searchFilters">
                <form action="search.php" method="GET">
                    <!-- Keep search query if present -->
                    <?php if (!empty($search_query)): ?>
                        <input type="hidden" name="q" value="<?php echo htmlspecialchars($search_query); ?>">
                    <?php endif; ?>
                    
                    <!-- Categories filter -->
                    <div class="filter-section">
                        <h4>Catégories</h4>
                        <div>
                            <label>
                                <input type="radio" name="category" value="0" <?php echo $category_id === 0 ? 'checked' : ''; ?>>
                                Toutes les catégories
                            </label>
                            <?php foreach ($categories as $category): ?>
                                <label>
                                    <input type="radio" name="category" value="<?php echo $category['id']; ?>" <?php echo $category_id === $category['id'] ? 'checked' : ''; ?>>
                                    <?php echo htmlspecialchars($category['nom']); ?>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    
                    <!-- Price range filter -->
                    <div class="filter-section">
                        <h4>Fourchette de prix</h4>
                        <div class="price-range">
                            <input type="number" name="min_price" class="price-input" placeholder="Min" value="<?php echo $min_price > 0 ? $min_price : ''; ?>">
                            <span>-</span>
                            <input type="number" name="max_price" class="price-input" placeholder="Max" value="<?php echo $max_price < 100000 ? $max_price : ''; ?>">
                        </div>
                    </div>
                    
                    <!-- Sort filter -->
                    <div class="filter-section">
                        <h4>Trier par</h4>
                        <div>
                            <label>
                                <input type="radio" name="sort" value="default" <?php echo $sort === 'default' ? 'checked' : ''; ?>>
                                Par défaut
                            </label>
                            <label>
                                <input type="radio" name="sort" value="price_asc" <?php echo $sort === 'price_asc' ? 'checked' : ''; ?>>
                                Prix croissant
                            </label>
                            <label>
                                <input type="radio" name="sort" value="price_desc" <?php echo $sort === 'price_desc' ? 'checked' : ''; ?>>
                                Prix décroissant
                            </label>
                            <label>
                                <input type="radio" name="sort" value="newest" <?php echo $sort === 'newest' ? 'checked' : ''; ?>>
                                Nouveautés
                            </label>
                            <label>
                                <input type="radio" name="sort" value="popular" <?php echo $sort === 'popular' ? 'checked' : ''; ?>>
                                Popularité
                            </label>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn btn-primary" style="width: 100%;">Appliquer les filtres</button>
                </form>
            </aside>
            
            <!-- Products section -->
            <div class="search-results">
                <div class="search-results-header">
                    <div class="search-count">
                        <?php echo $total_products; ?> produit<?php echo $total_products > 1 ? 's' : ''; ?> trouvé<?php echo $total_products > 1 ? 's' : ''; ?>
                    </div>
                    
                    <div class="search-sort">
                        <select class="sort-select" id="sortSelect">
                            <option value="default" <?php echo $sort === 'default' ? 'selected' : ''; ?>>Tri par défaut</option>
                            <option value="price_asc" <?php echo $sort === 'price_asc' ? 'selected' : ''; ?>>Prix croissant</option>
                            <option value="price_desc" <?php echo $sort === 'price_desc' ? 'selected' : ''; ?>>Prix décroissant</option>
                            <option value="newest" <?php echo $sort === 'newest' ? 'selected' : ''; ?>>Nouveautés</option>
                            <option value="popular" <?php echo $sort === 'popular' ? 'selected' : ''; ?>>Popularité</option>
                            <option value="name_asc" <?php echo $sort === 'name_asc' ? 'selected' : ''; ?>>Nom (A-Z)</option>
                            <option value="name_desc" <?php echo $sort === 'name_desc' ? 'selected' : ''; ?>>Nom (Z-A)</option>
                        </select>
                    </div>
                </div>

                <?php if (empty($products)): ?>
                    <div class="no-results">
                        <i class="fas fa-search"></i>
                        <h3>Aucun résultat trouvé</h3>
                        <p>Essayez avec d'autres termes de recherche ou modifiez les filtres.</p>
                        <a href="produits.php" class="btn btn-primary mt-4">Voir tous les produits</a>
                    </div>
                <?php else: ?>
                    <div class="product-grid">
                        <?php foreach ($products as $product): ?>
                            <?php 
                                $images = json_decode($product['images'], true);
                                $image_url = !empty($images) ? getImageUrl($images[0]) : 'assets/images/placeholder.svg';
                                
                                // Format rating
                                $rating = round($product['avg_rating'], 1);
                                $full_stars = floor($rating);
                                $half_star = $rating - $full_stars >= 0.5;
                                $empty_stars = 5 - $full_stars - ($half_star ? 1 : 0);
                            ?>
                            <div class="product-card">
                                <a href="product.php?id=<?php echo $product['id']; ?>" class="product-image">
                                    <img src="<?php echo $image_url; ?>" alt="<?php echo htmlspecialchars($product['nom']); ?>">
                                </a>
                                <div class="product-info">
                                    <div class="product-category"><?php echo htmlspecialchars($product['categorie_nom']); ?></div>
                                    <h3 class="product-name">
                                        <a href="product.php?id=<?php echo $product['id']; ?>"><?php echo htmlspecialchars($product['nom']); ?></a>
                                    </h3>
                                    <div class="product-price">
                                        <?php if ($product['prix_promo']): ?>
                                            <span class="original-price"><?php echo formatPrice($product['prix']); ?></span>
                                            <?php echo formatPrice($product['prix_promo']); ?>
                                        <?php else: ?>
                                            <?php echo formatPrice($product['prix']); ?>
                                        <?php endif; ?>
                                    </div>
                                    <div class="product-rating">
                                        <div class="stars">
                                            <?php for ($i = 0; $i < $full_stars; $i++): ?>
                                                <i class="fas fa-star"></i>
                                            <?php endfor; ?>
                                            
                                            <?php if ($half_star): ?>
                                                <i class="fas fa-star-half-alt"></i>
                                            <?php endif; ?>
                                            
                                            <?php for ($i = 0; $i < $empty_stars; $i++): ?>
                                                <i class="far fa-star"></i>
                                            <?php endfor; ?>
                                        </div>
                                        <div class="rating-count">(<?php echo $product['review_count']; ?>)</div>
                                    </div>
                                    <a href="product.php?id=<?php echo $product['id']; ?>" class="product-button">Voir le produit</a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <!-- Pagination -->
                    <?php if ($total_pages > 1): ?>
                        <ul class="pagination">
                            <?php if ($page > 1): ?>
                                <li>
                                    <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>">
                                        <i class="fas fa-chevron-left"></i>
                                    </a>
                                </li>
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
                                <li class="<?php echo ($i === $page) ? 'active' : ''; ?>">
                                    <?php if ($i === $page): ?>
                                        <span><?php echo $i; ?></span>
                                    <?php else: ?>
                                        <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>"><?php echo $i; ?></a>
                                    <?php endif; ?>
                                </li>
                            <?php endfor; ?>
                            
                            <?php if ($page < $total_pages): ?>
                                <li>
                                    <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>">
                                        <i class="fas fa-chevron-right"></i>
                                    </a>
                                </li>
                            <?php endif; ?>
                        </ul>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Toggle filters on mobile
            const toggleFiltersBtn = document.getElementById('toggleFilters');
            const filterSection = document.getElementById('searchFilters');
            
            toggleFiltersBtn.addEventListener('click', function() {
                filterSection.classList.toggle('active');
                const icon = this.querySelector('.fas');
                if (filterSection.classList.contains('active')) {
                    icon.classList.remove('fa-chevron-down');
                    icon.classList.add('fa-chevron-up');
                } else {
                    icon.classList.remove('fa-chevron-up');
                    icon.classList.add('fa-chevron-down');
                }
            });
            
            // Sort dropdown
            const sortSelect = document.getElementById('sortSelect');
            sortSelect.addEventListener('change', function() {
                const currentUrl = new URL(window.location);
                currentUrl.searchParams.set('sort', this.value);
                window.location.href = currentUrl.toString();
            });
        });
    </script>
</body>
</html>
