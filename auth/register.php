<?php
require_once '../config/config.php';

// If already logged in, redirect to home
if (isLoggedIn()) {
    header('Location: ' . SITE_URL);
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom = sanitizeInput($_POST['nom']);
    $prenom = sanitizeInput($_POST['prenom']);
    $email = sanitizeInput($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $telephone = sanitizeInput($_POST['telephone']);
    $adresse = sanitizeInput($_POST['adresse']);
    $ville = sanitizeInput($_POST['ville']);
    
    // Validation
    if (empty($nom) || empty($prenom) || empty($email) || empty($password)) {
        $error = 'Veuillez remplir tous les champs obligatoires';
    } elseif ($password !== $confirm_password) {
        $error = 'Les mots de passe ne correspondent pas';
    } elseif (strlen($password) < 6) {
        $error = 'Le mot de passe doit contenir au moins 6 caractères';
    } else {
        // Check if email already exists
        $stmt = $pdo->prepare("SELECT id FROM utilisateurs WHERE email = ?");
        $stmt->execute([$email]);
        
        if ($stmt->fetch()) {
            $error = 'Cette adresse email est déjà utilisée';
        } else {
            // Create user
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            
            $stmt = $pdo->prepare("
                INSERT INTO utilisateurs (nom, prenom, email, mot_de_passe, telephone, adresse, ville, role) 
                VALUES (?, ?, ?, ?, ?, ?, ?, 'client')
            ");
            
            if ($stmt->execute([$nom, $prenom, $email, $hashedPassword, $telephone, $adresse, $ville])) {
                $success = 'Inscription réussie ! Vous pouvez maintenant vous connecter.';
            } else {
                $error = 'Erreur lors de l\'inscription. Veuillez réessayer.';
            }
        }
    }
}

$page_title = 'Inscription';
?>

<!DOCTYPE html>
<html lang="<?php echo getCurrentLanguage(); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
</head>
<body>
    <!-- Header -->
    <header class="header">
        <div class="container">
            <div class="header-content">
                <a href="<?php echo SITE_URL; ?>" class="logo">
                    <div class="logo-icon">M</div>
                    Menalego
                </a>
                
                <nav>
                    <ul class="main-nav">
                        <li><a href="<?php echo SITE_URL; ?>">Accueil</a></li>
                        <li><a href="../produits.php">Produits</a></li>
                        <li><a href="../categories.php">Catégories</a></li>
                    </ul>
                </nav>
                
                <div class="user-actions">
                    <a href="login.php" class="user-btn">Connexion</a>
                </div>
            </div>
        </div>
    </header>

    <!-- Registration Form -->
    <section class="products-section">
        <div class="container">
            <div class="section-header">
                <h2>Inscription</h2>
                <p>Créez votre compte Menalego</p>
            </div>
            
            <div style="max-width: 600px; margin: 0 auto; background: white; padding: 2rem; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
                <?php if ($error): ?>
                    <div class="alert alert-error"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                    <div class="alert alert-success"><?php echo $success; ?></div>
                <?php endif; ?>
                
                <form method="POST">
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                        <div class="form-group">
                            <label for="nom">Nom *</label>
                            <input type="text" id="nom" name="nom" class="form-control" required 
                                   value="<?php echo isset($_POST['nom']) ? htmlspecialchars($_POST['nom']) : ''; ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="prenom">Prénom *</label>
                            <input type="text" id="prenom" name="prenom" class="form-control" required 
                                   value="<?php echo isset($_POST['prenom']) ? htmlspecialchars($_POST['prenom']) : ''; ?>">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="email">Email *</label>
                        <input type="email" id="email" name="email" class="form-control" required 
                               value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                    </div>
                    
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                        <div class="form-group">
                            <label for="password">Mot de passe *</label>
                            <input type="password" id="password" name="password" class="form-control" required>
                            <small style="color: #666; font-size: 0.8rem;">Minimum 6 caractères</small>
                        </div>
                        
                        <div class="form-group">
                            <label for="confirm_password">Confirmer le mot de passe *</label>
                            <input type="password" id="confirm_password" name="confirm_password" class="form-control" required>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="telephone">Téléphone</label>
                        <input type="tel" id="telephone" name="telephone" class="form-control" 
                               value="<?php echo isset($_POST['telephone']) ? htmlspecialchars($_POST['telephone']) : ''; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="adresse">Adresse</label>
                        <textarea id="adresse" name="adresse" class="form-control" rows="3"><?php echo isset($_POST['adresse']) ? htmlspecialchars($_POST['adresse']) : ''; ?></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="ville">Ville</label>
                        <input type="text" id="ville" name="ville" class="form-control" 
                               value="<?php echo isset($_POST['ville']) ? htmlspecialchars($_POST['ville']) : ''; ?>">
                    </div>
                    
                    <button type="submit" class="btn-primary" style="width: 100%; margin-bottom: 1rem;">S'inscrire</button>
                    
                    <div class="text-center">
                        <p>Déjà un compte ? <a href="login.php" style="color: var(--primary-blue);">Connectez-vous</a></p>
                    </div>
                </form>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="footer-bottom">
                <p>&copy; <?php echo date('Y'); ?> Menalego. Tous droits réservés.</p>
            </div>
        </div>
    </footer>
</body>
</html>
