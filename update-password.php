<?php
require_once 'config/config.php';

// Require login
requireLogin();

$user_id = $_SESSION['user_id'];
$response = ['success' => false, 'message' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    // Validate inputs
    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        setFlashMessage('Tous les champs sont obligatoires.', 'danger');
    } elseif ($new_password !== $confirm_password) {
        setFlashMessage('La confirmation du nouveau mot de passe ne correspond pas.', 'danger');
    } elseif (strlen($new_password) < 6) {
        setFlashMessage('Le nouveau mot de passe doit contenir au moins 6 caractères.', 'danger');
    } else {
        try {
            // Get current user data
            $stmt = $pdo->prepare("SELECT mot_de_passe FROM utilisateurs WHERE id = ?");
            $stmt->execute([$user_id]);
            $user = $stmt->fetch();
            
            if (!$user) {
                setFlashMessage('Utilisateur non trouvé.', 'danger');
            } elseif (!password_verify($current_password, $user['mot_de_passe'])) {
                setFlashMessage('Le mot de passe actuel est incorrect.', 'danger');
            } else {
                // Update password
                $new_password_hash = password_hash($new_password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE utilisateurs SET mot_de_passe = ? WHERE id = ?");
                
                if ($stmt->execute([$new_password_hash, $user_id])) {
                    setFlashMessage('Mot de passe mis à jour avec succès !', 'success');
                } else {
                    setFlashMessage('Erreur lors de la mise à jour du mot de passe.', 'danger');
                }
            }
        } catch (Exception $e) {
            setFlashMessage('Erreur: ' . $e->getMessage(), 'danger');
        }
    }
} else {
    setFlashMessage('Méthode non autorisée.', 'danger');
}

// Redirect back to profile
header('Location: profile.php#settings');
exit;
?>
