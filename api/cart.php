<?php
require_once '../config/config.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Non autorisé']);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];
$user_id = $_SESSION['user_id'];

if ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
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
                echo json_encode(['success' => false, 'message' => 'Stock insuffisant']);
                exit;
            }
            
            $stmt = $pdo->prepare("UPDATE panier SET quantite = ? WHERE id = ?");
            $stmt->execute([$new_quantity, $existing['id']]);
        } else {
            // Add new item
            $stmt = $pdo->prepare("INSERT INTO panier (client_id, produit_id, quantite) VALUES (?, ?, ?)");
            $stmt->execute([$user_id, $product_id, $quantity]);
        }
        
        echo json_encode(['success' => true, 'message' => 'Produit ajouté au panier']);
        
    } elseif ($action === 'update') {
        $product_id = (int)$input['product_id'];
        $quantity = (int)$input['quantity'];
        
        if ($quantity <= 0) {
            // Remove item
            $stmt = $pdo->prepare("DELETE FROM panier WHERE client_id = ? AND produit_id = ?");
            $stmt->execute([$user_id, $product_id]);
        } else {
            // Update quantity
            $stmt = $pdo->prepare("UPDATE panier SET quantite = ? WHERE client_id = ? AND produit_id = ?");
            $stmt->execute([$quantity, $user_id, $product_id]);
        }
        
        echo json_encode(['success' => true, 'message' => 'Panier mis à jour']);
        
    } elseif ($action === 'remove') {
        $product_id = (int)$input['product_id'];
        
        $stmt = $pdo->prepare("DELETE FROM panier WHERE client_id = ? AND produit_id = ?");
        $stmt->execute([$user_id, $product_id]);
        
        echo json_encode(['success' => true, 'message' => 'Produit retiré du panier']);
    }
    
} elseif ($method === 'GET') {
    $action = $_GET['action'] ?? '';
    
    if ($action === 'count') {
        $stmt = $pdo->prepare("SELECT SUM(quantite) as count FROM panier WHERE client_id = ?");
        $stmt->execute([$user_id]);
        $result = $stmt->fetch();
        
        echo json_encode(['count' => (int)($result['count'] ?? 0)]);
        
    } elseif ($action === 'items') {
        $stmt = $pdo->prepare("
            SELECT p.*, pr.nom, pr.prix, pa.quantite, 
                   (pr.prix * pa.quantite) as total
            FROM panier pa 
            JOIN produits pr ON pa.produit_id = pr.id 
            LEFT JOIN categories p ON pr.categorie_id = p.id
            WHERE pa.client_id = ?
            ORDER BY pa.date_ajout DESC
        ");
        $stmt->execute([$user_id]);
        $items = $stmt->fetchAll();
        
        $total = 0;
        foreach ($items as $item) {
            $total += $item['total'];
        }
        
        echo json_encode([
            'items' => $items,
            'total' => $total,
            'count' => count($items)
        ]);
    }
}
?>
