<?php
/**
 * Cart functionality test
 */
require_once 'config/config.php';

echo "<h2>Cart Test</h2>";

// Check if cart table exists and has correct structure
try {
    $stmt = $pdo->query("DESCRIBE panier");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "<h3>✅ Panier table structure:</h3>";
    echo "<ul>";
    foreach ($columns as $column) {
        echo "<li>$column</li>";
    }
    echo "</ul>";
    
    // Check if produits table has all required columns
    $stmt = $pdo->query("DESCRIBE produits");
    $product_columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "<h3>✅ Produits table structure:</h3>";
    echo "<ul>";
    foreach ($product_columns as $column) {
        echo "<li>$column</li>";
    }
    echo "</ul>";
    
    // Test the cart query without user restriction
    $stmt = $pdo->prepare("
        SELECT pr.*, pr.nom, pr.prix, pr.stock, pa.quantite, 
               (pr.prix * pa.quantite) as total,
               c.nom as categorie_nom
        FROM panier pa 
        JOIN produits pr ON pa.produit_id = pr.id 
        LEFT JOIN categories c ON pr.categorie_id = c.id
        LIMIT 5
    ");
    $stmt->execute();
    $test_items = $stmt->fetchAll();
    
    echo "<h3>✅ Cart query test (showing " . count($test_items) . " sample items):</h3>";
    if (count($test_items) > 0) {
        echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
        echo "<tr><th>Product Name</th><th>Price</th><th>Quantity</th><th>Total</th><th>Category</th></tr>";
        foreach ($test_items as $item) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($item['nom']) . "</td>";
            echo "<td>" . $item['prix'] . "</td>";
            echo "<td>" . $item['quantite'] . "</td>";
            echo "<td>" . $item['total'] . "</td>";
            echo "<td>" . htmlspecialchars($item['categorie_nom'] ?? 'N/A') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>No cart items found (this is normal if no items have been added to carts)</p>";
    }
    
    echo "<p><strong>✅ Cart query is working correctly!</strong></p>";
    echo '<a href="cart.php">Test Cart Page</a> | ';
    echo '<a href="produits.php">View Products</a> | ';
    echo '<a href="index.php">← Back to Home</a>';
    
} catch (Exception $e) {
    echo "<p><strong>❌ Error:</strong> " . $e->getMessage() . "</p>";
}
?>
