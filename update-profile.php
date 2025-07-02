<?php
require_once 'config/config.php';

// Require login
requireLogin();

$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $prenom = sanitizeInput($_POST['prenom'] ?? '');
    $nom = sanitizeInput($_POST['nom'] ?? '');
    $email = sanitizeInput($_POST['email'] ?? '');
    $telephone = sanitizeInput($_POST['telephone'] ?? '');
    $adresse = sanitizeInput($_POST['adresse'] ?? '');
    $ville = sanitizeInput($_POST['ville'] ?? '');
    $code_postal = sanitizeInput($_POST['code_postal'] ?? '');
    
    // Validate inputs
    if (empty($prenom) || empty($nom) || empty($email)) {
        setFlashMessage('Le prénom, nom et email sont obligatoires.', 'danger');
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        setFlashMessage('Format email invalide.', 'danger');
    } else {
        try {
            // Check if email is already used by another user
            $stmt = $pdo->prepare("SELECT id FROM utilisateurs WHERE email = ? AND id != ?");
            $stmt->execute([$email, $user_id]);
            
            if ($stmt->fetch()) {
                setFlashMessage('Cette adresse email est déjà utilisée par un autre utilisateur.', 'danger');
            } else {
                // Update user profile
                $stmt = $pdo->prepare("
                    UPDATE utilisateurs 
                    SET prenom = ?, nom = ?, email = ?, telephone = ?, adresse = ?, ville = ?, code_postal = ?
                    WHERE id = ?
                ");
                
                if ($stmt->execute([$prenom, $nom, $email, $telephone, $adresse, $ville, $code_postal, $user_id])) {
                    // Update session data
                    $_SESSION['user_name'] = $prenom . ' ' . $nom;
                    setFlashMessage('Profil mis à jour avec succès !', 'success');
                } else {
                    setFlashMessage('Erreur lors de la mise à jour du profil.', 'danger');
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
header('Location: profile.php#profile');
exit;
?>
