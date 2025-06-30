<?php
require_once 'config/config.php';

try {
    $stmt = $pdo->query("SELECT id, nom, images FROM produits WHERE images IS NOT NULL AND images != '' LIMIT 10");
    while ($row = $stmt->fetch()) {
        echo "ID: " . $row['id'] . ", Nom: " . $row['nom'] . ", Images: " . $row['images'] . "\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
