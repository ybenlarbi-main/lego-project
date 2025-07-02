    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="footer-content">
                <div class="footer-section">
                    <h3>Menalego</h3>
                    <p>La plateforme e-commerce LEGO® inspirée du patrimoine marocain.</p>
                    <div class="social-links">
                        <a href="#"><i class="fab fa-facebook"></i></a>
                        <a href="#"><i class="fab fa-instagram"></i></a>
                        <a href="#"><i class="fab fa-twitter"></i></a>
                    </div>
                </div>
                
                <div class="footer-section">
                    <h3>Liens rapides</h3>
                    <ul>
                        <li><a href="<?php echo SITE_URL; ?>">Accueil</a></li>
                        <li><a href="produits.php">Produits</a></li>
                        <li><a href="categories.php">Catégories</a></li>
                        <li><a href="about.php">À propos</a></li>
                        <li><a href="contact.php">Contact</a></li>
                    </ul>
                </div>
                
                <div class="footer-section">
                    <h3>Mon compte</h3>
                    <ul>
                        <?php if (isLoggedIn()): ?>
                            <li><a href="profile.php">Mon profil</a></li>
                            <li><a href="cart.php">Mon panier</a></li>
                            <li><a href="auth/logout.php">Déconnexion</a></li>
                        <?php else: ?>
                            <li><a href="auth/login.php">Connexion</a></li>
                            <li><a href="auth/register.php">Inscription</a></li>
                        <?php endif; ?>
                    </ul>
                </div>
                
                <div class="footer-section">
                    <h3>Contact</h3>
                    <p><i class="fas fa-map-marker-alt"></i> Casablanca, Maroc</p>
                    <p><i class="fas fa-phone"></i> +212 5 24 XX XX XX</p>
                    <p><i class="fas fa-envelope"></i> contact@menalego.ma</p>
                </div>
            </div>
            
            <div class="footer-bottom">
                <p>&copy; <?php echo date('Y'); ?> Menalego. Tous droits réservés.</p>
            </div>
        </div>
    </footer>

    <!-- JavaScript -->
    <script src="<?php echo SITE_URL; ?>/assets/js/main.js"></script>
    
    <!-- Additional JS -->
    <?php if (isset($additional_js)): ?>
        <?php foreach ($additional_js as $js): ?>
            <script src="<?php echo $js; ?>"></script>
        <?php endforeach; ?>
    <?php endif; ?>
</body>
</html>
