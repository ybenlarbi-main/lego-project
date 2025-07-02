<?php
/**
 * Print order page for admin
 */
require_once '../config/config.php';
requireAdmin();

// Check if ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die('ID de commande invalide');
}

$order_id = (int)$_GET['id'];

// Get order details
$stmt = $pdo->prepare("
    SELECT c.*, 
           u.prenom, u.nom, u.email, u.telephone
    FROM commandes c 
    LEFT JOIN utilisateurs u ON c.user_id = u.id 
    WHERE c.id = ?
");
$stmt->execute([$order_id]);
$order = $stmt->fetch();

// If order doesn't exist
if (!$order) {
    die('Commande introuvable');
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
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Commande #<?php echo $order_id; ?> - <?php echo SITE_NAME; ?></title>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            line-height: 1.6;
            color: #333;
            background: #fff;
            margin: 0;
            padding: 20px;
            font-size: 14px;
        }
        
        .print-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 1px solid #ddd;
        }
        
        .company-info {
            flex: 1;
        }
        
        .company-info h1 {
            margin: 0 0 10px 0;
            color: #3b82f6;
            font-size: 24px;
        }
        
        .company-info p {
            margin: 0 0 5px 0;
        }
        
        .order-info {
            text-align: right;
        }
        
        .order-info h2 {
            margin: 0 0 10px 0;
            font-size: 18px;
        }
        
        .order-info p {
            margin: 0 0 5px 0;
        }
        
        .section {
            margin-bottom: 30px;
        }
        
        .section h3 {
            margin: 0 0 15px 0;
            padding-bottom: 5px;
            border-bottom: 1px solid #eee;
            font-size: 16px;
            color: #3b82f6;
        }
        
        .grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        
        .items-table th {
            text-align: left;
            padding: 10px;
            background-color: #f8fafc;
            border-bottom: 1px solid #ddd;
        }
        
        .items-table td {
            padding: 10px;
            border-bottom: 1px solid #eee;
            vertical-align: top;
        }
        
        .items-table tr:last-child td {
            border-bottom: none;
        }
        
        .subtotal-section {
            margin-top: 20px;
            margin-left: auto;
            width: 300px;
        }
        
        .subtotal-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .subtotal-table td {
            padding: 5px 10px;
        }
        
        .subtotal-table td:last-child {
            text-align: right;
        }
        
        .total-row {
            font-weight: bold;
            border-top: 1px solid #ddd;
        }
        
        .footer {
            margin-top: 30px;
            text-align: center;
            padding-top: 20px;
            border-top: 1px solid #ddd;
            color: #666;
            font-size: 12px;
        }
        
        @media print {
            .no-print {
                display: none;
            }
            
            body {
                padding: 0;
                background: white;
            }
            
            @page {
                margin: 20px;
            }
        }
        
        .status-badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 500;
        }
        
        .status-en_attente { background-color: #fef3c7; color: #92400e; }
        .status-confirmee { background-color: #dbeafe; color: #1e40af; }
        .status-preparee { background-color: #e0f2fe; color: #0369a1; }
        .status-expediee { background-color: #dbeafe; color: #1e40af; }
        .status-livree { background-color: #dcfce7; color: #166534; }
        .status-annulee { background-color: #fee2e2; color: #991b1b; }
        
        .btn-print {
            display: inline-block;
            padding: 8px 16px;
            background-color: #3b82f6;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            margin-bottom: 20px;
            cursor: pointer;
            border: none;
        }
        
        .btn-print:hover {
            background-color: #2563eb;
        }
    </style>
</head>
<body>
    <button onclick="window.print()" class="btn-print no-print">Imprimer</button>
    
    <div class="print-header">
        <div class="company-info">
            <h1><?php echo SITE_NAME; ?></h1>
            <p>123 Avenue Mohammed V</p>
            <p>Casablanca, 20000</p>
            <p>Maroc</p>
            <p>Email: contact@menalego.ma</p>
            <p>Tél: +212 5 22 00 00 00</p>
        </div>
        
        <div class="order-info">
            <h2>Bon de commande #<?php echo htmlspecialchars($order['numero_commande']); ?></h2>
            <p>Date: <?php echo formatDate($order['date_commande']); ?></p>
            <p>Status: <span class="status-badge status-<?php echo $order['statut']; ?>"><?php echo getStatusText($order['statut']); ?></span></p>
        </div>
    </div>
    
    <div class="section">
        <h3>Informations client</h3>
        <div class="grid">
            <div>
                <p><strong>Nom:</strong> <?php echo htmlspecialchars($order['prenom'] . ' ' . $order['nom']); ?></p>
                <p><strong>Email:</strong> <?php echo htmlspecialchars($order['email']); ?></p>
                <p><strong>Téléphone:</strong> <?php echo $order['telephone'] ? htmlspecialchars($order['telephone']) : '-'; ?></p>
            </div>
            
            <div>
                <p><strong>Adresse de livraison:</strong></p>
                <p><?php echo htmlspecialchars($address['adresse']); ?></p>
                <p><?php echo htmlspecialchars($address['ville'] . ', ' . $address['code_postal']); ?></p>
                <?php if (!empty($order['notes_livraison'])): ?>
                    <p><strong>Notes:</strong> <?php echo nl2br(htmlspecialchars($order['notes_livraison'])); ?></p>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <div class="section">
        <h3>Produits commandés</h3>
        <table class="items-table">
            <thead>
                <tr>
                    <th>Description</th>
                    <th>Prix unitaire</th>
                    <th>Quantité</th>
                    <th>Total</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($items as $item): ?>
                    <tr>
                        <td>
                            <div>
                                <strong><?php echo htmlspecialchars($item['nom']); ?></strong><br>
                                <small>Réf: <?php echo htmlspecialchars($item['reference']); ?></small>
                            </div>
                        </td>
                        <td><?php echo formatPrice($item['prix_unitaire']); ?></td>
                        <td><?php echo $item['quantite']; ?></td>
                        <td><?php echo formatPrice($item['total']); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        
        <div class="subtotal-section">
            <table class="subtotal-table">
                <tr>
                    <td>Sous-total</td>
                    <td><?php echo formatPrice($order['total_ht']); ?></td>
                </tr>
                <tr>
                    <td>Frais de livraison</td>
                    <td><?php echo formatPrice($order['frais_livraison']); ?></td>
                </tr>
                <tr class="total-row">
                    <td>Total</td>
                    <td><?php echo formatPrice($order['total_ttc']); ?></td>
                </tr>
            </table>
        </div>
    </div>
    
    <div class="section">
        <h3>Modalités de paiement</h3>
        <p><strong>Méthode de paiement:</strong> <?php echo ucfirst($order['methode_paiement']); ?></p>
    </div>
    
    <div class="footer">
        <p>Merci pour votre commande!</p>
        <p><?php echo SITE_NAME; ?> &copy; <?php echo date('Y'); ?> - Tous droits réservés</p>
    </div>
</body>
</html>
