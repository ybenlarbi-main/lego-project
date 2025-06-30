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
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0, user-scalable=yes">
    <meta name="format-detection" content="telephone=no">
    <meta name="theme-color" content="#0061FF">
    <title><?php echo $page_title; ?> - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <style>
        /* Critical Auth Page Styles */
        .auth-section {
            min-height: 100vh !important;
            display: flex !important;
            align-items: center !important;
            background: linear-gradient(135deg, rgba(0, 97, 255, 0.05) 0%, rgba(255, 187, 0, 0.05) 50%, rgba(255, 51, 51, 0.05) 100%) !important;
            padding: 2rem 0 !important;
        }
        
        .auth-container {
            display: grid !important;
            grid-template-columns: 1fr 1fr !important;
            gap: 3rem !important;
            max-width: 1200px !important;
            margin: 0 auto !important;
            background: rgba(255, 255, 255, 0.95) !important;
            backdrop-filter: blur(20px) !important;
            border-radius: 1.5rem !important;
            box-shadow: 0 20px 40px rgba(0, 97, 255, 0.1) !important;
            overflow: hidden !important;
        }
        
        .auth-visual {
            background: linear-gradient(135deg, #0061FF 0%, #FF3333 50%, #FFBB00 100%) !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            padding: 3rem !important;
            color: white !important;
        }
        
        .auth-form-container {
            padding: 3rem !important;
            display: flex !important;
            flex-direction: column !important;
            justify-content: center !important;
        }
        
        .auth-title {
            font-size: 2.5rem !important;
            font-weight: 800 !important;
            background: linear-gradient(135deg, #0061FF 0%, #4285F4 100%) !important;
            background-clip: text !important;
            -webkit-background-clip: text !important;
            -webkit-text-fill-color: transparent !important;
            text-align: center !important;
            margin-bottom: 0.5rem !important;
        }
        
        .form-input {
            width: 100% !important;
            padding: 1rem 1.2rem !important;
            border: 2px solid rgba(0, 97, 255, 0.1) !important;
            border-radius: 0.75rem !important;
            font-size: 1rem !important;
            background: rgba(255, 255, 255, 0.8) !important;
            transition: all 0.3s ease !important;
            box-sizing: border-box !important;
        }
        
        .form-input:focus {
            border-color: #0061FF !important;
            background: white !important;
            outline: none !important;
            box-shadow: 0 0 0 3px rgba(0, 97, 255, 0.1) !important;
        }
        
        .form-row {
            display: grid !important;
            grid-template-columns: 1fr 1fr !important;
            gap: 1.5rem !important;
            margin-bottom: 1.5rem !important;
        }
        
        .btn-auth-primary {
            width: 100% !important;
            background: linear-gradient(135deg, #0061FF 0%, #4285F4 100%) !important;
            color: white !important;
            border: none !important;
            padding: 1rem 2rem !important;
            border-radius: 0.75rem !important;
            font-size: 1.1rem !important;
            font-weight: 600 !important;
            cursor: pointer !important;
            margin: 1.5rem 0 !important;
        }
        
        @media (max-width: 968px) {
            .auth-container {
                grid-template-columns: 1fr !important;
                margin: 1rem !important;
            }
            .form-row {
                grid-template-columns: 1fr !important;
            }
        }
    </style>
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

    <!-- Enhanced Registration Form -->
    <section class="auth-section">
        <div class="auth-background">
            <div class="auth-particles"></div>
        </div>
        <div class="container">
            <div class="auth-container">
                <div class="auth-visual">
                    <div class="auth-graphic">
                        <div class="floating-blocks">
                            <div class="block block-1"></div>
                            <div class="block block-2"></div>
                            <div class="block block-3"></div>
                            <div class="block block-4"></div>
                        </div>
                        <div class="auth-illustration">
                            <h3>Rejoignez Menalego</h3>
                            <p>Créez votre compte et découvrez l'univers magique du patrimoine marocain en LEGO®</p>
                        </div>
                    </div>
                </div>
                
                <div class="auth-form-container">
                    <div class="auth-header">
                        <h2 class="auth-title">Inscription</h2>
                        <p class="auth-subtitle">Créez votre compte Menalego gratuitement</p>
                    </div>
                    
                    <?php if ($error): ?>
                        <div class="alert alert-error">
                            <i class="icon-warning"></i>
                            <?php echo $error; ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($success): ?>
                        <div class="alert alert-success">
                            <i class="icon-check"></i>
                            <?php echo $success; ?>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST" class="auth-form">
                        <div class="form-row">
                            <div class="form-group">
                                <label for="nom" class="form-label">
                                    <i class="icon-user"></i>
                                    Nom *
                                </label>
                                <input type="text" id="nom" name="nom" class="form-input" required 
                                       placeholder="Votre nom"
                                       value="<?php echo isset($_POST['nom']) ? htmlspecialchars($_POST['nom']) : ''; ?>">
                            </div>
                            
                            <div class="form-group">
                                <label for="prenom" class="form-label">
                                    <i class="icon-user"></i>
                                    Prénom *
                                </label>
                                <input type="text" id="prenom" name="prenom" class="form-input" required 
                                       placeholder="Votre prénom"
                                       value="<?php echo isset($_POST['prenom']) ? htmlspecialchars($_POST['prenom']) : ''; ?>">
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="email" class="form-label">
                                <i class="icon-email"></i>
                                Email *
                            </label>
                            <input type="email" id="email" name="email" class="form-input" required 
                                   placeholder="votre@email.com"
                                   value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="password" class="form-label">
                                    <i class="icon-lock"></i>
                                    Mot de passe *
                                </label>
                                <div class="password-input-container">
                                    <input type="password" id="password" name="password" class="form-input" required
                                           placeholder="Votre mot de passe">
                                    <button type="button" class="password-toggle" onclick="togglePassword('password', 'passwordIcon')">
                                        <i class="icon-eye" id="passwordIcon"></i>
                                    </button>
                                </div>
                                <small class="form-help">Minimum 6 caractères</small>
                            </div>
                            
                            <div class="form-group">
                                <label for="confirm_password" class="form-label">
                                    <i class="icon-lock"></i>
                                    Confirmer *
                                </label>
                                <div class="password-input-container">
                                    <input type="password" id="confirm_password" name="confirm_password" class="form-input" required
                                           placeholder="Confirmez le mot de passe">
                                    <button type="button" class="password-toggle" onclick="togglePassword('confirm_password', 'confirmIcon')">
                                        <i class="icon-eye" id="confirmIcon"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="telephone" class="form-label">
                                <i class="icon-phone"></i>
                                Téléphone
                            </label>
                            <input type="tel" id="telephone" name="telephone" class="form-input" 
                                   placeholder="Votre numéro de téléphone"
                                   value="<?php echo isset($_POST['telephone']) ? htmlspecialchars($_POST['telephone']) : ''; ?>">
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="adresse" class="form-label">
                                    <i class="icon-location"></i>
                                    Adresse
                                </label>
                                <input type="text" id="adresse" name="adresse" class="form-input" 
                                       placeholder="Votre adresse"
                                       value="<?php echo isset($_POST['adresse']) ? htmlspecialchars($_POST['adresse']) : ''; ?>">
                            </div>
                            
                            <div class="form-group">
                                <label for="ville" class="form-label">
                                    <i class="icon-location"></i>
                                    Ville
                                </label>
                                <input type="text" id="ville" name="ville" class="form-input" 
                                       placeholder="Votre ville"
                                       value="<?php echo isset($_POST['ville']) ? htmlspecialchars($_POST['ville']) : ''; ?>">
                            </div>
                        </div>
                        
                        <button type="submit" class="btn-auth-primary">
                            <span>Créer mon compte</span>
                            <i class="icon-arrow-right"></i>
                        </button>
                        
                        <div class="auth-links">
                            <p>Vous avez déjà un compte ? 
                                <a href="login.php" class="auth-link">Connectez-vous</a>
                            </p>
                        </div>
                    </form>
                </div>
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

    <script>
        function togglePassword(inputId, iconId) {
            const passwordInput = document.getElementById(inputId);
            const passwordIcon = document.getElementById(iconId);
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                passwordIcon.className = 'icon-eye-off';
            } else {
                passwordInput.type = 'password';
                passwordIcon.className = 'icon-eye';
            }
        }

        // Enhanced form animations and validation
        document.addEventListener('DOMContentLoaded', function() {
            // Animate form elements on load
            const formElements = document.querySelectorAll('.form-group');
            formElements.forEach((element, index) => {
                element.style.opacity = '0';
                element.style.transform = 'translateY(20px)';
                setTimeout(() => {
                    element.style.transition = 'all 0.5s ease';
                    element.style.opacity = '1';
                    element.style.transform = 'translateY(0)';
                }, index * 80);
            });

            // Add focus effects to inputs
            const inputs = document.querySelectorAll('.form-input');
            inputs.forEach(input => {
                input.addEventListener('focus', function() {
                    this.parentElement.classList.add('focused');
                });
                input.addEventListener('blur', function() {
                    if (!this.value) {
                        this.parentElement.classList.remove('focused');
                    }
                });
            });

            // Password strength indicator
            const passwordInput = document.getElementById('password');
            const confirmInput = document.getElementById('confirm_password');
            
            passwordInput.addEventListener('input', function() {
                const strength = getPasswordStrength(this.value);
                updatePasswordStrength(strength);
            });

            confirmInput.addEventListener('input', function() {
                const password = passwordInput.value;
                const confirm = this.value;
                
                if (confirm && password !== confirm) {
                    this.setCustomValidity('Les mots de passe ne correspondent pas');
                } else {
                    this.setCustomValidity('');
                }
            });
        });

        function getPasswordStrength(password) {
            let strength = 0;
            if (password.length >= 6) strength++;
            if (password.length >= 8) strength++;
            if (/[A-Z]/.test(password)) strength++;
            if (/[0-9]/.test(password)) strength++;
            if (/[^A-Za-z0-9]/.test(password)) strength++;
            return strength;
        }

        function updatePasswordStrength(strength) {
            // This could show a visual indicator of password strength
            // For now, just console log for demo
            console.log('Password strength:', strength);
        }
    </script>
</body>
</html>
