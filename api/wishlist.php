<?php
require_once '../config/config.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Vous devez être connecté']);
    exit;
}

$user_id = $_SESSION['user_id'];
$input = json_decode(file_get_contents('php://input'), true);

try {
    switch ($_SERVER['REQUEST_METHOD']) {
        case 'POST':
            $action = $input['action'] ?? '';
            
            switch ($action) {
                case 'add':
                    $product_id = (int)($input['product_id'] ?? 0);
                    
                    if ($product_id <= 0) {
                        throw new Exception('Données invalides');
                    }
                    
                    // Check if product exists
                    $stmt = $pdo->prepare("SELECT id FROM produits WHERE id = ? AND statut = 'actif'");
                    $stmt->execute([$product_id]);
                    if (!$stmt->fetch()) {
                        throw new Exception('Produit non trouvé');
                    }
                    
                    // Check if already in wishlist
                    $stmt = $pdo->prepare("SELECT id FROM favoris WHERE client_id = ? AND produit_id = ?");
                    $stmt->execute([$user_id, $product_id]);
                    if ($stmt->fetch()) {
                        throw new Exception('Produit déjà dans la liste de souhaits');
                    }
                    
                    // Add to wishlist
                    $stmt = $pdo->prepare("INSERT INTO favoris (client_id, produit_id) VALUES (?, ?)");
                    $stmt->execute([$user_id, $product_id]);
                    
                    echo json_encode(['success' => true, 'message' => 'Produit ajouté à la liste de souhaits']);
                    break;
                    
                case 'remove':
                    $product_id = (int)($input['product_id'] ?? 0);
                    
                    $stmt = $pdo->prepare("DELETE FROM favoris WHERE client_id = ? AND produit_id = ?");
                    $stmt->execute([$user_id, $product_id]);
                    
                    echo json_encode(['success' => true, 'message' => 'Produit retiré de la liste de souhaits']);
                    break;
                    
                case 'toggle':
                    $product_id = (int)($input['product_id'] ?? 0);
                    
                    // Check if exists
                    $stmt = $pdo->prepare("SELECT id FROM favoris WHERE client_id = ? AND produit_id = ?");
                    $stmt->execute([$user_id, $product_id]);
                    
                    if ($stmt->fetch()) {
                        // Remove
                        $stmt = $pdo->prepare("DELETE FROM favoris WHERE client_id = ? AND produit_id = ?");
                        $stmt->execute([$user_id, $product_id]);
                        echo json_encode(['success' => true, 'action' => 'removed', 'message' => 'Retiré de la liste de souhaits']);
                    } else {
                        // Add
                        $stmt = $pdo->prepare("INSERT INTO favoris (client_id, produit_id) VALUES (?, ?)");
                        $stmt->execute([$user_id, $product_id]);
                        echo json_encode(['success' => true, 'action' => 'added', 'message' => 'Ajouté à la liste de souhaits']);
                    }
                    break;
                    
                default:
                    throw new Exception('Action non reconnue');
            }
            break;
            
        case 'GET':
            $action = $_GET['action'] ?? '';
            
            switch ($action) {
                case 'list':
                    $stmt = $pdo->prepare("
                        SELECT p.*, f.date_ajout as date_favoris,
                               COALESCE(AVG(r.note), 0) as avg_rating,
                               COUNT(r.id) as review_count
                        FROM favoris f
                        JOIN produits p ON f.produit_id = p.id
                        LEFT JOIN avis r ON p.id = r.produit_id AND r.statut = 'approuve'
                        WHERE f.client_id = ? AND p.statut = 'actif'
                        GROUP BY p.id
                        ORDER BY f.date_ajout DESC
                    ");
                    $stmt->execute([$user_id]);
                    $items = $stmt->fetchAll();
                    
                    echo json_encode([
                        'success' => true,
                        'items' => $items,
                        'count' => count($items)
                    ]);
                    break;
                    
                case 'count':
                    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM favoris WHERE client_id = ?");
                    $stmt->execute([$user_id]);
                    $result = $stmt->fetch();
                    
                    echo json_encode(['success' => true, 'count' => (int)($result['count'] ?? 0)]);
                    break;
                    
                case 'check':
                    $product_id = (int)($_GET['product_id'] ?? 0);
                    
                    $stmt = $pdo->prepare("SELECT id FROM favoris WHERE client_id = ? AND produit_id = ?");
                    $stmt->execute([$user_id, $product_id]);
                    $exists = $stmt->fetch();
                    
                    echo json_encode(['success' => true, 'in_wishlist' => (bool)$exists]);
                    break;
                    
                default:
                    throw new Exception('Action non reconnue');
            }
            break;
            
        default:
            throw new Exception('Méthode non autorisée');
    }
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
