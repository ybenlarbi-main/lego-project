<?php
// Simple database connection test
try {
    $pdo = new PDO("mysql:host=localhost;dbname=menalego_db", "root", "");
    $pdo->exec("set names utf8");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "✅ Database connection successful!<br>";
    
    // Check if tables exist
    $tables = ['utilisateurs', 'categories', 'produits'];
    foreach ($tables as $table) {
        try {
            $stmt = $pdo->query("SELECT COUNT(*) FROM $table");
            $count = $stmt->fetchColumn();
            echo "✅ Table '$table' exists with $count records<br>";
        } catch (Exception $e) {
            echo "❌ Table '$table' not found<br>";
        }
    }
    
} catch(PDOException $e) {
    echo "❌ Database connection failed: " . $e->getMessage() . "<br>";
    echo "<br><strong>To fix this:</strong><br>";
    echo "1. Make sure XAMPP MySQL is running<br>";
    echo "2. Import the database: config/menalego_database.sql<br>";
    echo "3. Check database name is 'menalego_db'<br>";
}
?>
