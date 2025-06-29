-- Create Menalego Database
CREATE DATABASE IF NOT EXISTS menalego_db;
USE menalego_db;

-- Table: utilisateurs
CREATE TABLE utilisateurs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(100) NOT NULL,
    prenom VARCHAR(100) NOT NULL,
    email VARCHAR(150) UNIQUE NOT NULL,
    mot_de_passe VARCHAR(255) NOT NULL,
    telephone VARCHAR(20),
    adresse TEXT,
    ville VARCHAR(100),
    code_postal VARCHAR(10),
    role ENUM('client', 'admin') DEFAULT 'client',
    statut ENUM('actif', 'inactif', 'suspendu') DEFAULT 'actif',
    date_creation TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    date_modification TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Table: categories
CREATE TABLE categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(100) NOT NULL,
    nom_ar VARCHAR(100),
    description TEXT,
    description_ar TEXT,
    image VARCHAR(255),
    parent_id INT DEFAULT NULL,
    statut ENUM('actif', 'inactif') DEFAULT 'actif',
    date_creation TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (parent_id) REFERENCES categories(id) ON DELETE SET NULL
);

-- Table: produits (single store - no boutiques)
CREATE TABLE produits (
    id INT AUTO_INCREMENT PRIMARY KEY,
    categorie_id INT NOT NULL,
    nom VARCHAR(200) NOT NULL,
    nom_ar VARCHAR(200),
    description TEXT,
    description_ar TEXT,
    prix DECIMAL(10,2) NOT NULL,
    prix_promo DECIMAL(10,2) DEFAULT NULL,
    stock INT DEFAULT 0,
    pieces_count INT DEFAULT 0,
    age_min INT DEFAULT 3,
    age_max INT DEFAULT 99,
    images JSON,
    caracteristiques JSON,
    marque VARCHAR(100) DEFAULT 'Menalego',
    reference VARCHAR(50) UNIQUE,
    poids DECIMAL(8,2),
    dimensions VARCHAR(100),
    statut ENUM('brouillon', 'actif', 'inactif', 'rupture') DEFAULT 'brouillon',
    featured BOOLEAN DEFAULT FALSE,
    date_creation TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    date_modification TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (categorie_id) REFERENCES categories(id) ON DELETE RESTRICT
);

-- Table: commandes
CREATE TABLE commandes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    client_id INT NOT NULL,
    numero_commande VARCHAR(50) UNIQUE NOT NULL,
    statut ENUM('en_attente', 'confirmee', 'preparee', 'expediee', 'livree', 'annulee') DEFAULT 'en_attente',
    total_ht DECIMAL(10,2) NOT NULL,
    total_ttc DECIMAL(10,2) NOT NULL,
    frais_livraison DECIMAL(10,2) DEFAULT 0,
    methode_paiement ENUM('carte', 'virement', 'especes', 'cheque') DEFAULT 'carte',
    adresse_livraison JSON NOT NULL,
    notes_livraison TEXT,
    date_commande TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    date_modification TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (client_id) REFERENCES utilisateurs(id) ON DELETE CASCADE
);

-- Table: lignes_commandes
CREATE TABLE lignes_commandes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    commande_id INT NOT NULL,
    produit_id INT NOT NULL,
    quantite INT NOT NULL,
    prix_unitaire DECIMAL(10,2) NOT NULL,
    total DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (commande_id) REFERENCES commandes(id) ON DELETE CASCADE,
    FOREIGN KEY (produit_id) REFERENCES produits(id) ON DELETE CASCADE
);

-- Table: avis
CREATE TABLE avis (
    id INT AUTO_INCREMENT PRIMARY KEY,
    produit_id INT NOT NULL,
    client_id INT NOT NULL,
    commande_id INT,
    note INT CHECK (note >= 1 AND note <= 5),
    titre VARCHAR(200),
    commentaire TEXT,
    images JSON,
    statut ENUM('en_attente', 'approuve', 'rejete') DEFAULT 'en_attente',
    date_creation TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    date_modification TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (produit_id) REFERENCES produits(id) ON DELETE CASCADE,
    FOREIGN KEY (client_id) REFERENCES utilisateurs(id) ON DELETE CASCADE,
    FOREIGN KEY (commande_id) REFERENCES commandes(id) ON DELETE SET NULL,
    UNIQUE KEY unique_review (produit_id, client_id, commande_id)
);

-- Table: panier
CREATE TABLE panier (
    id INT AUTO_INCREMENT PRIMARY KEY,
    client_id INT NOT NULL,
    produit_id INT NOT NULL,
    quantite INT NOT NULL DEFAULT 1,
    date_ajout TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (client_id) REFERENCES utilisateurs(id) ON DELETE CASCADE,
    FOREIGN KEY (produit_id) REFERENCES produits(id) ON DELETE CASCADE,
    UNIQUE KEY unique_cart_item (client_id, produit_id)
);

-- Table: favoris
CREATE TABLE favoris (
    id INT AUTO_INCREMENT PRIMARY KEY,
    client_id INT NOT NULL,
    produit_id INT NOT NULL,
    date_ajout TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (client_id) REFERENCES utilisateurs(id) ON DELETE CASCADE,
    FOREIGN KEY (produit_id) REFERENCES produits(id) ON DELETE CASCADE,
    UNIQUE KEY unique_favorite (client_id, produit_id)
);

-- Insert default admin user and sample client
INSERT INTO utilisateurs (nom, prenom, email, mot_de_passe, role, statut) VALUES
('Admin', 'Menalego', 'admin@menalego.ma', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 'actif'),
('Dupont', 'Jean', 'client@test.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'client', 'actif');

-- Insert default categories
INSERT INTO categories (nom, nom_ar, description, description_ar) VALUES
('Architecture Marocaine', 'العمارة المغربية', 'Sets inspirés de l\'architecture traditionnelle marocaine', 'مجموعات مستوحاة من العمارة المغربية التقليدية'),
('Monuments Historiques', 'المعالم التاريخية', 'Reproductions des monuments emblématiques du Maroc', 'نسخ من المعالم الشهيرة في المغرب'),
('Culture et Traditions', 'الثقافة والتقاليد', 'Sets célébrant la richesse culturelle marocaine', 'مجموعات تحتفي بالثراء الثقافي المغربي'),
('Villes Impériales', 'المدن الإمبراطورية', 'Collections dédiées aux quatre villes impériales', 'مجموعات مخصصة للمدن الإمبراطورية الأربع'),
('Artisanat Marocain', 'الحرف المغربية', 'Sets inspirés de l\'artisanat traditionnel', 'مجموعات مستوحاة من الحرف التقليدية');

-- Insert sample products (single store)
INSERT INTO produits (categorie_id, nom, nom_ar, description, description_ar, prix, stock, pieces_count, age_min, age_max, reference, statut, featured) VALUES
(1, 'Mosquée Koutoubia - Marrakech', 'مسجد الكتبية - مراكش', 'Reconstruisez le célèbre minaret de la Koutoubia avec ses détails architecturaux authentiques', 'أعد بناء مئذنة الكتبية الشهيرة بتفاصيلها المعمارية الأصيلة', 899.99, 25, 1847, 12, 99, 'MEN-KT-001', 'actif', TRUE),
(2, 'Palais Bahia - Architecture Royale', 'قصر الباهية - العمارة الملكية', "Découvrez la splendeur de l\'architecture royale marocaine", 'اكتشف روعة العمارة الملكية المغربية', 1299.99, 15, 2456, 14, 99, 'MEN-BH-002', 'actif', TRUE),
(3, 'Souk Traditionnel', 'السوق التقليدي', 'Créez votre propre souk avec ses échoppes colorées', 'أنشئ السوق الخاص بك مع محلاته الملونة', 599.99, 30, 1234, 8, 99, 'MEN-SK-003', 'actif', FALSE),
(1, 'Riad Traditionnel', 'رياض تقليدي', 'Construisez un authentique riad marocain avec patio central', 'ابني رياضاً مغربياً أصيلاً بفناء مركزي', 749.99, 20, 1567, 10, 99, 'MEN-RD-004', 'actif', TRUE),
(4, 'Médina de Fès', 'مدينة فاس', 'Explorez les ruelles labyrinthiques de la plus ancienne médina', 'استكشف أزقة المدينة المتاهية الأقدم', 1099.99, 12, 2234, 12, 99, 'MEN-FS-005', 'actif', FALSE);
