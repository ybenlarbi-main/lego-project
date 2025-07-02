<?php
/**
 * Cart API - Handles cart operations (add, remove, update, get)
 */
require_once '../config/config.php';

header('Content-Type: application/json');

// Initialize response array
$response = [
    'success' => false,
    'message' => '',
    'cart' => null
];

// Check login for cart operations
if (!isLoggedIn()) {
    $response['message'] = 'Non autorisé';
    echo json_encode($response);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];
$user_id = $_SESSION['user_id'];

if ($method === 'POST') {
    // Accept both JSON and form data
    if (isset($_POST['action'])) {
        $input = $_POST;
    } else {
        $input = json_decode(file_get_contents('php://input'), true);
    }
    
    $action = $input['action'] ?? '';
    
    if ($action === 'add') {
        $product_id = (int)$input['product_id'];
        $quantity = (int)($input['quantity'] ?? 1);
        
        // Check if product exists and is active
        $stmt = $pdo->prepare("SELECT * FROM produits WHERE id = ? AND statut = 'actif'");
        $stmt->execute([$product_id]);
        $product = $stmt->fetch();
        
        if (!$product) {
            echo json_encode(['success' => false, 'message' => 'Produit non trouvé']);
            exit;
        }
        
        if ($product['stock'] < $quantity) {
            echo json_encode(['success' => false, 'message' => 'Stock insuffisant']);
            exit;
        }
        
        // Check if item already in cart
        $stmt = $pdo->prepare("SELECT * FROM panier WHERE client_id = ? AND produit_id = ?");
        $stmt->execute([$user_id, $product_id]);
        $existing = $stmt->fetch();
        
        if ($existing) {
            // Update quantity
            $new_quantity = $existing['quantite'] + $quantity;
            if ($new_quantity > $product['stock']) {
                $new_quantity = $product['stock'];
                $response['message'] = 'Quantité ajustée au stock disponible';
            } else {
                $response['message'] = 'Produit ajouté au panier';
            }
            
            $stmt = $pdo->prepare("UPDATE panier SET quantite = ? WHERE id = ?");
            $stmt->execute([$new_quantity, $existing['id']]);
        } else {
            // Add new item
            $stmt = $pdo->prepare("INSERT INTO panier (client_id, produit_id, quantite) VALUES (?, ?, ?)");
            $stmt->execute([$user_id, $product_id, $quantity]);
            $response['message'] = 'Produit ajouté au panier';
        }
        
        // Get updated cart info
        $response['success'] = true;
        getCartDetails($response);
        
    } elseif ($action === 'update') {
        $product_id = (int)$input['product_id'];
        $quantity = (int)$input['quantity'];
        
        if ($quantity <= 0) {
            // Remove item
            $stmt = $pdo->prepare("DELETE FROM panier WHERE client_id = ? AND produit_id = ?");
            $stmt->execute([$user_id, $product_id]);
            $response['message'] = 'Produit retiré du panier';
        } else {
            // Check stock availability
            $stmt = $pdo->prepare("SELECT stock FROM produits WHERE id = ?");
            $stmt->execute([$product_id]);
            $product = $stmt->fetch();
            
            // Adjust quantity if needed
            if ($product && $quantity > $product['stock']) {
                $quantity = $product['stock'];
                $response['message'] = 'Quantité ajustée au stock disponible';
            } else {
                $response['message'] = 'Panier mis à jour';
            }
            
            // Update quantity
            $stmt = $pdo->prepare("UPDATE panier SET quantite = ? WHERE client_id = ? AND produit_id = ?");
            $stmt->execute([$quantity, $user_id, $product_id]);
        }
        
        $response['success'] = true;
        getCartDetails($response);
        
    } elseif ($action === 'remove') {
        $product_id = (int)$input['product_id'];
        
        $stmt = $pdo->prepare("DELETE FROM panier WHERE client_id = ? AND produit_id = ?");
        $stmt->execute([$user_id, $product_id]);
        
        $response['success'] = true;
        $response['message'] = 'Produit retiré du panier';
        getCartDetails($response);
    } elseif ($action === 'clear') {
        // Clear entire cart
        $stmt = $pdo->prepare("DELETE FROM panier WHERE client_id = ?");
        $stmt->execute([$user_id]);
        
        $response['success'] = true;
        $response['message'] = 'Panier vidé';
        $response['cart'] = [
            'items' => [],
            'total' => 0,
            'count' => 0,
            'shipping' => 0,
            'total_with_shipping' => 0
        ];
    }
    
} elseif ($method === 'GET') {
    $action = $_GET['action'] ?? '';
    
    if ($action === 'count') {
        $stmt = $pdo->prepare("SELECT SUM(quantite) as count FROM panier WHERE client_id = ?");
        $stmt->execute([$user_id]);
        $result = $stmt->fetch();
        
        $response['success'] = true;
        $response['count'] = (int)($result['count'] ?? 0);
    } else {
        // Default to returning full cart details
        $response['success'] = true;
        getCartDetails($response);
    }
}

// Return response
echo json_encode($response);
exit;

/**
 * Get cart details including items, count, totals
 * @param array &$response Reference to response array to populate
 */
function getCartDetails(&$response) {
    global $pdo, $user_id;
    
    // Get cart items with product details
    $stmt = $pdo->prepare("
        SELECT pa.*, 
               pr.nom, pr.reference, pr.prix, pr.prix_promo, pr.stock, pr.images,
               c.nom as categorie_nom
        FROM panier pa 
        JOIN produits pr ON pa.produit_id = pr.id 
        LEFT JOIN categories c ON pr.categorie_id = c.id
        WHERE pa.client_id = ?
        ORDER BY pa.date_ajout DESC
    ");
    $stmt->execute([$user_id]);
    $items = $stmt->fetchAll();
    
    // Process items
    $cart_items = [];
    $subtotal = 0;
    $item_count = 0;
    
    foreach ($items as $item) {
        // Use promo price if available
        $price = $item['prix_promo'] ? $item['prix_promo'] : $item['prix'];
        $item_total = $price * $item['quantite'];
        
        // Get product image
        $images = json_decode($item['images'], true) ?: [];
        $image = !empty($images) ? $images[0] : 'assets/images/placeholder.svg';
        
        // Add to cart items array
        $cart_items[] = [
            'id' => $item['produit_id'],
            'name' => $item['nom'],
            'price' => $price,
            'original_price' => $item['prix'],
            'quantity' => $item['quantite'],
            'stock' => $item['stock'],
            'image' => $image,
            'category' => $item['categorie_nom'],
            'reference' => $item['reference'],
            'total' => $item_total
        ];
        
        $subtotal += $item_total;
        $item_count += $item['quantite'];
    }
    
    // Calculate shipping
    $shipping = $subtotal > 0 ? 30 : 0; // Flat rate shipping
    
    // Build cart response
    $response['cart'] = [
        'items' => $cart_items,
        'count' => $item_count,
        'subtotal' => $subtotal,
        'shipping' => $shipping,
        'total' => $subtotal + $shipping
    ];
}
?>
