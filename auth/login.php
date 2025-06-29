<?php
require_once '../config/config.php';

// If already logged in, redirect to home
if (isLoggedIn()) {
    header('Location: ' . SITE_URL);
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitizeInput($_POST['email']);
    $password = $_POST['password'];
    
    if (empty($email) || empty($password)) {
        $error = 'Veuillez remplir tous les champs';
    } else {
        // Check user in database
        $stmt = $pdo->prepare("SELECT * FROM utilisateurs WHERE email = ? AND statut = 'actif'");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        
        if ($user && password_verify($password, $user['mot_de_passe'])) {
            // Login successful
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_role'] = $user['role'];
            $_SESSION['user_name'] = $user['prenom'] . ' ' . $user['nom'];
            
            showSuccess('Connexion réussie !');
            
            // Redirect to admin if admin user
            if ($user['role'] === 'admin') {
                header('Location: ' . SITE_URL . '/admin/');
            } else {
                header('Location: ' . SITE_URL);
            }
            exit;
        } else {
            $error = 'Email ou mot de passe incorrect';
        }
    }
}

$page_title = 'Connexion';
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
                    <a href="register.php" class="user-btn">Inscription</a>
                </div>
            </div>
        </div>
    </header>

    <!-- Login Form -->
    <section class="products-section">
        <div class="container">
            <div class="section-header">
                <h2>Connexion</h2>
                <p>Connectez-vous à votre compte Menalego</p>
            </div>
            
            <div style="max-width: 400px; margin: 0 auto; background: white; padding: 2rem; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
                <?php if ($error): ?>
                    <div class="alert alert-error"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <form method="POST">
                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" class="form-control" required 
                               value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="password">Mot de passe</label>
                        <input type="password" id="password" name="password" class="form-control" required>
                    </div>
                    
                    <button type="submit" class="btn-primary" style="width: 100%; margin-bottom: 1rem;">Se connecter</button>
                    
                    <div class="text-center">
                        <p>Pas encore de compte ? <a href="register.php" style="color: var(--primary-blue);">Inscrivez-vous</a></p>
                        
                        <!-- Demo credentials -->
                        <div style="margin-top: 2rem; padding: 1rem; background: #f8f9fa; border-radius: 4px; font-size: 0.9rem;">
                            <strong>Comptes de démonstration :</strong><br>
                            <strong>Admin :</strong> admin@menalego.ma / password<br>
                            <strong>Client :</strong> client@test.com / password
                        </div>
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
