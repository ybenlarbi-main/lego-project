# Menalego - Plateforme E-commerce LEGO® Marocaine

## Description

Menalego est une plateforme e-commerce inspirée de LEGO® qui met en valeur le patrimoine marocain. Ce projet combine la créativité du jeu de construction avec l'immersion culturelle marocaine.

## Fonctionnalités

### Pour les Clients
- ✅ Navigation des produits avec filtres avancés
- ✅ Système de panier d'achat
- ✅ Authentification et inscription
- ✅ Pages de détail produit avec avis
- ✅ Support multilingue (Français/Arabe)

### Pour les Administrateurs
- ✅ Dashboard administrateur avec statistiques
- ✅ Gestion complète des produits (CRUD)
- ✅ Gestion des catégories
- ✅ Gestion des utilisateurs
- ✅ Gestion des commandes
- ✅ Gestion des avis clients

### Caractéristiques Techniques
- ✅ Architecture MPA (Multi-Page Application)
- ✅ Backend PHP avec PDO
- ✅ Base de données MySQL
- ✅ Design responsive
- ✅ API REST pour le panier
- ✅ Sécurité avec sessions PHP et hashage des mots de passe

## Installation

### Prérequis
- XAMPP (Apache, MySQL, PHP 7.4+)
- Navigateur web moderne

### Étapes d'installation

1. **Cloner le projet**
   ```bash
   # Le projet est déjà dans c:\xampp\htdocs\new-mohamed\menalego
   ```

2. **Démarrer XAMPP**
   - Démarrer Apache
   - Démarrer MySQL

3. **Créer la base de données**
   - Ouvrir phpMyAdmin (http://localhost/phpmyadmin)
   - Importer le fichier `config/menalego_database.sql`

4. **Configuration**
   - Vérifier les paramètres dans `config/database.php`
   - Les paramètres par défaut devraient fonctionner avec XAMPP

5. **Tester l'installation**
   - Aller sur: http://localhost/new-mohamed/menalego/setup.php
   - Vérifier que tout fonctionne correctement

## Utilisation

### Accès au site
- **Site principal**: http://localhost/new-mohamed/menalego/
- **Administration**: http://localhost/new-mohamed/menalego/admin/

### Comptes de test
- **Administrateur**
  - Email: admin@menalego.ma
  - Mot de passe: password

- **Client**
  - Email: client@test.com
  - Mot de passe: password

## Structure du projet

```
menalego/
├── config/
│   ├── config.php          # Configuration principale
│   ├── database.php        # Connexion base de données
│   └── menalego_database.sql # Script de création DB
├── assets/
│   ├── css/
│   │   └── style.css       # Styles principaux
│   ├── js/                 # Scripts JavaScript
│   └── images/             # Images et uploads
├── auth/
│   ├── login.php          # Page de connexion
│   ├── register.php       # Page d'inscription
│   └── logout.php         # Déconnexion
├── admin/
│   ├── index.php          # Dashboard admin
│   ├── products.php       # Gestion produits
│   └── ...                # Autres pages admin
├── api/
│   ├── cart.php           # API panier
│   └── change-language.php # API changement langue
├── includes/
│   └── header.php         # En-tête partagé
├── index.php              # Page d'accueil
├── produits.php           # Liste des produits
├── product.php            # Détail produit
├── cart.php               # Panier
└── setup.php              # Script de vérification
```

## Couleurs de la marque

- **Bleu principal**: #4D93B8
- **Jaune**: #FFE100
- **Rouge**: #D51C29
- **Bleu sombre**: #2E5B73

## Technologies utilisées

- **Frontend**: HTML5, CSS3, JavaScript ES6+
- **Backend**: PHP 7.4+
- **Base de données**: MySQL 8.0+
- **Serveur web**: Apache
- **Outils**: PDO, Sessions PHP

## Fonctionnalités avancées

### Multilingue
- Support français et arabe
- Changement de langue dynamique
- Interface RTL pour l'arabe

### Sécurité
- Hashage des mots de passe avec password_hash()
- Protection CSRF
- Validation des données
- Sessions sécurisées

### API REST
- Gestion du panier en AJAX
- Réponses JSON
- Gestion des erreurs

## Développement futur

- [ ] Système de commande complet
- [ ] Passerelle de paiement
- [ ] Gestion des stocks en temps réel
- [ ] Notifications email
- [ ] API mobile
- [ ] Système d'avis avec images

## Support

Pour toute question ou problème:
- Vérifier le fichier setup.php
- Consulter les logs Apache/PHP
- Vérifier la configuration de la base de données

## Licence

Projet éducatif - Tous droits réservés

---

© 2025 Menalego - Plateforme e-commerce LEGO® inspirée du patrimoine marocain
