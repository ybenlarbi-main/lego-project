<?php
// Setup script for Menalego
require_once 'config/database.php';

try {
    $database = new Database();
    $pdo = $database->getConnection();
    
    echo "<h1>Menalego - Configuration initiale</h1>";
    echo "<p>Base de données connectée avec succès!</p>";
    
    // Check if admin user exists
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM utilisateurs WHERE role = 'admin'");
    $stmt->execute();
    $admin_count = $stmt->fetch()['count'];
    
    if ($admin_count == 0) {
        echo "<p style='color: orange;'>Aucun utilisateur admin trouvé. Veuillez importer le fichier SQL.</p>";
    } else {
        echo "<p style='color: green;'>Utilisateur admin trouvé ✓</p>";
    }
    
    // Check products
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM produits");
    $stmt->execute();
    $product_count = $stmt->fetch()['count'];
    
    echo "<p>Nombre de produits: $product_count</p>";
    
    // Check categories
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM categories");
    $stmt->execute();
    $category_count = $stmt->fetch()['count'];
    
    echo "<p>Nombre de catégories: $category_count</p>";
    
    echo "<h2>Comptes de test:</h2>";
    echo "<ul>";
    echo "<li><strong>Admin:</strong> admin@menalego.ma / password</li>";
    echo "<li><strong>Client:</strong> client@test.com / password</li>";
    echo "</ul>";
    
    echo "<h2>Liens utiles:</h2>";
    echo "<ul>";
    echo "<li><a href='index.php'>Page d'accueil</a></li>";
    echo "<li><a href='auth/login.php'>Connexion</a></li>";
    echo "<li><a href='admin/'>Administration</a></li>";
    echo "<li><a href='produits.php'>Produits</a></li>";
    echo "</ul>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Erreur de connexion: " . $e->getMessage() . "</p>";
    echo "<p>Veuillez vérifier votre configuration de base de données dans config/database.php</p>";
}
?>
