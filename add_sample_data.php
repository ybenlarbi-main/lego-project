<?php
// Sample data insertion script
require_once 'config/config.php';

try {
    // Sample product images (these would normally be uploaded files)
    $sample_images = [
        'products/koutoubia-mosque.jpg',
        'products/bahia-palace.jpg', 
        'products/traditional-souk.jpg',
        'products/traditional-riad.jpg',
        'products/fes-medina.jpg'
    ];

    // Update products with sample images
    $products_data = [
        [
            'id' => 1,
            'images' => json_encode([$sample_images[0]]),
            'prix_promo' => 799.99
        ],
        [
            'id' => 2,
            'images' => json_encode([$sample_images[1]]),
            'prix_promo' => null
        ],
        [
            'id' => 3,
            'images' => json_encode([$sample_images[2]]),
            'prix_promo' => 499.99
        ],
        [
            'id' => 4,
            'images' => json_encode([$sample_images[3]]),
            'prix_promo' => null
        ],
        [
            'id' => 5,
            'images' => json_encode([$sample_images[4]]),
            'prix_promo' => 999.99
        ]
    ];

    foreach ($products_data as $product) {
        $stmt = $pdo->prepare("UPDATE produits SET images = ?, prix_promo = ? WHERE id = ?");
        $stmt->execute([$product['images'], $product['prix_promo'], $product['id']]);
    }

    // Add some sample reviews
    $reviews_data = [
        [
            'produit_id' => 1,
            'client_id' => 2,
            'note' => 5,
            'titre' => 'Magnifique set !',
            'commentaire' => 'La qualité est exceptionnelle, les détails sont incroyables. Mon fils adore !',
            'statut' => 'approuve'
        ],
        [
            'produit_id' => 1,
            'client_id' => 1,
            'note' => 4,
            'titre' => 'Très bon produit',
            'commentaire' => 'Bon rapport qualité-prix, instructions claires.',
            'statut' => 'approuve'
        ],
        [
            'produit_id' => 2,
            'client_id' => 2,
            'note' => 5,
            'titre' => 'Splendide !',
            'commentaire' => 'Un vrai chef-d\'œuvre, parfait pour décorer.',
            'statut' => 'approuve'
        ],
        [
            'produit_id' => 4,
            'client_id' => 2,
            'note' => 4,
            'titre' => 'Bel ensemble',
            'commentaire' => 'Très détaillé, construction agréable.',
            'statut' => 'approuve'
        ]
    ];

    foreach ($reviews_data as $review) {
        $stmt = $pdo->prepare("
            INSERT INTO avis (produit_id, client_id, note, titre, commentaire, statut) 
            VALUES (?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE 
            note = VALUES(note), 
            titre = VALUES(titre), 
            commentaire = VALUES(commentaire)
        ");
        $stmt->execute([
            $review['produit_id'],
            $review['client_id'],
            $review['note'],
            $review['titre'],
            $review['commentaire'],
            $review['statut']
        ]);
    }

    // Add sample category images
    $category_images = [
        'categories/architecture-marocaine.jpg',
        'categories/monuments-historiques.jpg',
        'categories/culture-traditions.jpg',
        'categories/villes-imperiales.jpg',
        'categories/artisanat-marocain.jpg'
    ];

    for ($i = 1; $i <= 5; $i++) {
        $stmt = $pdo->prepare("UPDATE categories SET image = ? WHERE id = ?");
        $stmt->execute([$category_images[$i-1], $i]);
    }

    echo "✅ Sample data added successfully!\n";
    echo "Products updated with images and promotional prices.\n";
    echo "Sample reviews added.\n";
    echo "Category images added.\n";

} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>
