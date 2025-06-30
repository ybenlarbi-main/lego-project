<?php
require_once 'config/config.php';

try {
    echo "Starting image path migration...\n";
    
    $stmt = $pdo->query("SELECT id, images FROM produits WHERE images IS NOT NULL AND images != ''");
    $updated_count = 0;
    
    while ($row = $stmt->fetch()) {
        $images = json_decode($row['images'], true);
        $updated_images = [];
        $needs_update = false;
        
        if (is_array($images)) {
            foreach ($images as $image) {
                // If image doesn't start with 'products/', add it
                if (strpos($image, 'products/') !== 0) {
                    $updated_images[] = 'products/' . $image;
                    $needs_update = true;
                } else {
                    $updated_images[] = $image;
                }
            }
            
            if ($needs_update) {
                $updated_json = json_encode($updated_images);
                $update_stmt = $pdo->prepare("UPDATE produits SET images = ? WHERE id = ?");
                $update_stmt->execute([$updated_json, $row['id']]);
                $updated_count++;
                echo "Updated product ID " . $row['id'] . ": " . $row['images'] . " -> " . $updated_json . "\n";
            }
        }
    }
    
    echo "Migration completed. Updated $updated_count products.\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
