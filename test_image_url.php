<?php
require_once 'config/config.php';

$image_path = "products/product_6862981660b76.png";
$image_url = getImageUrl($image_path);

echo "Image path: " . $image_path . "\n";
echo "UPLOAD_URL: " . UPLOAD_URL . "\n";
echo "Generated URL: " . $image_url . "\n";
echo "Expected file location: " . __DIR__ . "/assets/uploads/" . $image_path . "\n";
echo "File exists: " . (file_exists(__DIR__ . "/assets/uploads/" . $image_path) ? "YES" : "NO") . "\n";
?>
