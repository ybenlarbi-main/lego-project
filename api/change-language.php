<?php
require_once '../config/config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $language = $input['language'] ?? '';
    
    if (in_array($language, ['fr', 'ar'])) {
        $_SESSION['language'] = $language;
        echo json_encode(['success' => true, 'language' => $language]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Langue non supportée']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
}
?>
