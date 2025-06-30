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
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        /* Critical Auth Page Styles - Ensure they load */
        .auth-section {
            min-height: 100vh !important;
            display: flex !important;
            align-items: center !important;
            background: linear-gradient(135deg, rgba(0, 97, 255, 0.05) 0%, rgba(255, 187, 0, 0.05) 50%, rgba(255, 51, 51, 0.05) 100%) !important;
            padding: 2rem 0 !important;
            position: relative !important;
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
            border: 1px solid rgba(255, 255, 255, 0.2) !important;
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
        
        @media (max-width: 968px) {
            .auth-container {
                grid-template-columns: 1fr !important;
                margin: 1rem !important;
            }
        }
        
        /* Form Styling */
        .auth-title {
            font-size: 2.5rem !important;
            font-weight: 800 !important;
            background: linear-gradient(135deg, #0061FF 0%, #4285F4 100%) !important;
            -webkit-background-clip: text !important;
            -webkit-text-fill-color: transparent !important;
            background-clip: text !important;
            margin-bottom: 0.5rem !important;
            text-align: center !important;
        }
        
        .auth-subtitle {
            color: #4A5568 !important;
            font-size: 1.1rem !important;
            text-align: center !important;
            margin-bottom: 2rem !important;
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
        
        .form-label {
            display: flex !important;
            align-items: center !important;
            gap: 0.5rem !important;
            font-weight: 600 !important;
            color: #1A1A1A !important;
            margin-bottom: 0.5rem !important;
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
            transition: all 0.3s ease !important;
        }
        
        .btn-auth-primary:hover {
            transform: translateY(-2px) !important;
            box-shadow: 0 8px 25px rgba(0, 97, 255, 0.3) !important;
        }
        
        .form-group {
            margin-bottom: 1.5rem !important;
        }
        
        .demo-credentials {
            background: rgba(0, 97, 255, 0.05) !important;
            border: 1px solid rgba(0, 97, 255, 0.1) !important;
            border-radius: 0.75rem !important;
            padding: 1.5rem !important;
            margin-top: 1.5rem !important;
        }
        
        .auth-illustration h3 {
            font-size: 2rem !important;
            font-weight: 800 !important;
            margin-bottom: 1rem !important;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.3) !important;
        }
        
        .auth-illustration p {
            font-size: 1.1rem !important;
            opacity: 0.9 !important;
            line-height: 1.6 !important;
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
                    <a href="register.php" class="user-btn">Inscription</a>
                </div>
            </div>
        </div>
    </header>

    <!-- Enhanced Login Form -->
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
                            <h3>Bienvenue chez Menalego</h3>
                            <p>Construisez votre héritage marocain, brique par brique</p>
                        </div>
                    </div>
                </div>
                
                <div class="auth-form-container">
                    <div class="auth-header">
                        <h2 class="auth-title">Connexion</h2>
                        <p class="auth-subtitle">Connectez-vous à votre compte Menalego</p>
                    </div>
                    
                    <?php if ($error): ?>
                        <div class="alert alert-error">
                            <i class="icon-warning"></i>
                            <?php echo $error; ?>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST" class="auth-form">
                        <div class="form-group">
                            <label for="email" class="form-label">
                                <i class="icon-email"></i>
                                Email
                            </label>
                            <input type="email" id="email" name="email" class="form-input" required 
                                   placeholder="votre@email.com"
                                   value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="password" class="form-label">
                                <i class="icon-lock"></i>
                                Mot de passe
                            </label>
                            <div class="password-input-container">
                                <input type="password" id="password" name="password" class="form-input" required
                                       placeholder="Votre mot de passe">
                                <button type="button" class="password-toggle" onclick="togglePassword()">
                                    <i class="icon-eye" id="passwordIcon"></i>
                                </button>
                            </div>
                        </div>
                        
                        <button type="submit" class="btn-auth-primary">
                            <span>Se connecter</span>
                            <i class="icon-arrow-right"></i>
                        </button>
                        
                        <div class="auth-links">
                            <p>Pas encore de compte ? 
                                <a href="register.php" class="auth-link">Inscrivez-vous</a>
                            </p>
                        </div>
                        
                        <!-- Demo credentials -->
                        <div class="demo-credentials">
                            <div class="demo-header">
                                <i class="icon-info"></i>
                                <strong>Comptes de démonstration</strong>
                            </div>
                            <div class="demo-accounts">
                                <div class="demo-account">
                                    <span class="demo-role admin">Admin</span>
                                    <div class="demo-info">
                                        <span>admin@menalego.ma</span>
                                        <span>password</span>
                                    </div>
                                </div>
                                <div class="demo-account">
                                    <span class="demo-role client">Client</span>
                                    <div class="demo-info">
                                        <span>client@test.com</span>
                                        <span>password</span>
                                    </div>
                                </div>
                            </div>
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
        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const passwordIcon = document.getElementById('passwordIcon');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                passwordIcon.className = 'icon-eye-off';
            } else {
                passwordInput.type = 'password';
                passwordIcon.className = 'icon-eye';
            }
        }

        // Add some interactive animations
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
                }, index * 100);
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
        });
    </script>
</body>
</html>
