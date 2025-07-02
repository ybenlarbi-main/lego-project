<?php
/**
 * AJAX handler for fetching order details
 */
require_once '../../config/config.php';
requireAdmin();

// Initialize response array
$response = [
    'success' => false,
    'message' => '',
    'html' => ''
];

// Check if ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $response['message'] = 'ID de commande invalide';
    echo json_encode($response);
    exit;
}

$order_id = (int)$_GET['id'];

try {
    // Get order details
    $stmt = $pdo->prepare("
        SELECT c.*, 
               u.prenom, u.nom, u.email, u.telephone
        FROM commandes c 
        LEFT JOIN utilisateurs u ON c.client_id = u.id 
        WHERE c.id = ?
    ");
    $stmt->execute([$order_id]);
    $order = $stmt->fetch();

    // If order doesn't exist
    if (!$order) {
        $response['message'] = 'Commande introuvable';
        echo json_encode($response);
        exit;
    }

    // Get order items
    $stmt = $pdo->prepare("
        SELECT ci.*, p.nom, p.reference, p.images
        FROM lignes_commandes ci
        LEFT JOIN produits p ON ci.produit_id = p.id
        WHERE ci.commande_id = ?
    ");
    $stmt->execute([$order_id]);
    $items = $stmt->fetchAll();

    // Format delivery address
    $address = json_decode($order['adresse_livraison'], true);

    // Start building HTML output
    $html = '
    <div class="order-details">
        <div class="order-section">
            <h4>Informations de commande</h4>
            <div class="order-info-grid">
                <div class="info-group">
                    <label>Numéro de commande</label>
                    <p>' . htmlspecialchars($order['numero_commande']) . '</p>
                </div>
                <div class="info-group">
                    <label>Date de commande</label>
                    <p>' . formatDate($order['date_commande']) . '</p>
                </div>
                <div class="info-group">
                    <label>Statut</label>
                    <p><span class="badge ' . getStatusBadgeClass($order['statut']) . '">' . getStatusText($order['statut']) . '</span></p>
                </div>
                <div class="info-group">
                    <label>Méthode de paiement</label>
                    <p>' . ucfirst($order['methode_paiement']) . '</p>
                </div>
            </div>
        </div>

        <div class="order-section">
            <h4>Client</h4>
            <div class="order-info-grid">
                <div class="info-group">
                    <label>Nom</label>
                    <p>' . htmlspecialchars($order['prenom'] . ' ' . $order['nom']) . '</p>
                </div>
                <div class="info-group">
                    <label>Email</label>
                    <p>' . htmlspecialchars($order['email']) . '</p>
                </div>
                <div class="info-group">
                    <label>Téléphone</label>
                    <p>' . ($order['telephone'] ? htmlspecialchars($order['telephone']) : '-') . '</p>
                </div>
            </div>
        </div>

        <div class="order-section">
            <h4>Adresse de livraison</h4>
            <div class="order-info-grid">
                <div class="info-group">
                    <label>Adresse</label>
                    <p>' . htmlspecialchars($address['adresse']) . '</p>
                </div>
                <div class="info-group">
                    <label>Ville</label>
                    <p>' . htmlspecialchars($address['ville']) . '</p>
                </div>
                <div class="info-group">
                    <label>Code postal</label>
                    <p>' . htmlspecialchars($address['code_postal']) . '</p>
                </div>
            </div>
            ' . (!empty($order['notes_livraison']) ? '<div class="delivery-notes"><label>Notes de livraison</label><p>' . nl2br(htmlspecialchars($order['notes_livraison'])) . '</p></div>' : '') . '
        </div>

        <div class="order-section">
            <h4>Produits commandés</h4>
            <div class="order-products">
                <table class="order-items-table">
                    <thead>
                        <tr>
                            <th>Produit</th>
                            <th>Prix unitaire</th>
                            <th>Quantité</th>
                            <th>Total</th>
                        </tr>
                    </thead>
                    <tbody>';

    // Add order items to the table
    foreach ($items as $item) {
        // Get product image
        $images = json_decode($item['images'], true);
        $image = !empty($images) ? '../' . $images[0] : '../assets/images/placeholder.svg';
        
        $html .= '
        <tr>
            <td class="product-cell">
                <div class="product-info">
                    <div class="product-image">
                        <img src="' . $image . '" alt="' . htmlspecialchars($item['nom']) . '">
                    </div>
                    <div class="product-details">
                        <p class="product-name">' . htmlspecialchars($item['nom']) . '</p>
                        <p class="product-ref">Réf: ' . htmlspecialchars($item['reference']) . '</p>
                    </div>
                </div>
            </td>
            <td>' . formatPrice($item['prix_unitaire']) . '</td>
            <td>' . $item['quantite'] . '</td>
            <td>' . formatPrice($item['total']) . '</td>
        </tr>';
    }

    // Add order summary
    $html .= '
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="3" class="text-right">Sous-total</td>
                            <td>' . formatPrice($order['total_ht']) . '</td>
                        </tr>
                        <tr>
                            <td colspan="3" class="text-right">Frais de livraison</td>
                            <td>' . formatPrice($order['frais_livraison']) . '</td>
                        </tr>
                        <tr class="total-row">
                            <td colspan="3" class="text-right">Total</td>
                            <td>' . formatPrice($order['total_ttc']) . '</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
        
        <div class="order-section">
            <h4>Modifier le statut</h4>
            <form method="POST" action="orders.php" class="status-update-form">
                <input type="hidden" name="action" value="update_status">
                <input type="hidden" name="order_id" value="' . $order_id . '">
                <div class="form-row">
                    <select name="status" class="form-control status-select">
                        <option value="en_attente"' . ($order['statut'] == 'en_attente' ? ' selected' : '') . '>En attente</option>
                        <option value="confirmee"' . ($order['statut'] == 'confirmee' ? ' selected' : '') . '>Confirmée</option>
                        <option value="preparee"' . ($order['statut'] == 'preparee' ? ' selected' : '') . '>Préparée</option>
                        <option value="expediee"' . ($order['statut'] == 'expediee' ? ' selected' : '') . '>Expédiée</option>
                        <option value="livree"' . ($order['statut'] == 'livree' ? ' selected' : '') . '>Livrée</option>
                        <option value="annulee"' . ($order['statut'] == 'annulee' ? ' selected' : '') . '>Annulée</option>
                    </select>
                    <button type="submit" class="btn btn-primary">Mettre à jour</button>
                </div>
            </form>
        </div>
    </div>
    <style>
        .order-details {
            font-size: 0.9rem;
        }
        
        .order-section {
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid #e5e7eb;
        }
        
        .order-section h4 {
            margin-bottom: 1rem;
            font-size: 1.1rem;
            font-weight: 600;
            color: #1f2937;
        }
        
        .order-info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
        }
        
        .info-group label {
            display: block;
            font-size: 0.75rem;
            color: #6b7280;
            margin-bottom: 0.25rem;
        }
        
        .info-group p {
            margin: 0;
            font-weight: 500;
            color: #1f2937;
        }
        
        .order-items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 1rem;
        }
        
        .order-items-table th {
            background: #f9fafb;
            padding: 0.75rem;
            text-align: left;
            font-weight: 600;
            font-size: 0.75rem;
            color: #4b5563;
        }
        
        .order-items-table td {
            padding: 0.75rem;
            border-top: 1px solid #e5e7eb;
            vertical-align: middle;
        }
        
        .product-info {
            display: flex;
            align-items: center;
        }
        
        .product-image {
            width: 50px;
            height: 50px;
            background: #f9fafb;
            border-radius: 0.25rem;
            overflow: hidden;
            margin-right: 1rem;
            border: 1px solid #e5e7eb;
        }
        
        .product-image img {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }
        
        .product-name {
            margin: 0 0 0.25rem 0;
            font-weight: 500;
        }
        
        .product-ref {
            margin: 0;
            font-size: 0.75rem;
            color: #6b7280;
        }
        
        .text-right {
            text-align: right;
        }
        
        tfoot tr {
            background: #f9fafb;
            font-weight: 500;
        }
        
        tfoot tr.total-row {
            background: #f1f5f9;
            font-weight: 600;
            color: #1f2937;
        }
        
        tfoot tr.total-row td {
            padding: 0.75rem;
        }
        
        .form-row {
            display: flex;
            gap: 0.5rem;
            align-items: center;
        }
        
        .status-select {
            padding: 0.5rem;
            border: 1px solid #d1d5db;
            border-radius: 0.25rem;
            min-width: 200px;
        }
        
        .delivery-notes {
            grid-column: 1 / -1;
            margin-top: 1rem;
        }
    </style>';

    // Set success response
    $response['success'] = true;
    $response['html'] = $html;

} catch (Exception $e) {
    $response['message'] = 'Erreur: ' . $e->getMessage();
}

// Return JSON response
header('Content-Type: application/json');
echo json_encode($response);
