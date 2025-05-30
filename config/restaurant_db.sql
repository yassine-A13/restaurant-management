
-- Création de la base de données
CREATE DATABASE IF NOT EXISTS restaurant_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE restaurant_db;

-- Table des utilisateurs
CREATE TABLE IF NOT EXISTS users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nom VARCHAR(100) NOT NULL,
    prenom VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    telephone VARCHAR(20),
    adresse TEXT,
    role ENUM('client', 'restaurateur', 'livreur', 'gerant') NOT NULL,
    date_creation DATETIME DEFAULT CURRENT_TIMESTAMP,
    date_modification DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Table des catégories de plats
CREATE TABLE IF NOT EXISTS categories (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nom VARCHAR(100) NOT NULL,
    description TEXT
);

-- Table des plats
CREATE TABLE IF NOT EXISTS plats (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nom VARCHAR(100) NOT NULL,
    description TEXT,
    prix DECIMAL(10,2) NOT NULL,
    image VARCHAR(255),
    categorie_id INT,
    disponible BOOLEAN DEFAULT TRUE,
    FOREIGN KEY (categorie_id) REFERENCES categories(id)
);

-- Table des stocks
CREATE TABLE IF NOT EXISTS stocks (
    id INT PRIMARY KEY AUTO_INCREMENT,
    ingredient VARCHAR(100) NOT NULL,
    quantite INT NOT NULL,
    unite VARCHAR(20) NOT NULL,
    seuil_alerte INT,
    date_modification DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Table des commandes
CREATE TABLE IF NOT EXISTS commandes (
    id INT PRIMARY KEY AUTO_INCREMENT,
    client_id INT,
    montant_total DECIMAL(10,2) NOT NULL,
    statut ENUM('nouvelle', 'en_preparation', 'prete', 'en_livraison', 'livree', 'annulee') DEFAULT 'nouvelle',
    mode_paiement ENUM('carte', 'especes', 'en_ligne') NOT NULL,
    adresse_livraison TEXT,
    date_commande DATETIME DEFAULT CURRENT_TIMESTAMP,
    date_modification DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (client_id) REFERENCES users(id)
);

-- Table des détails de commande
CREATE TABLE IF NOT EXISTS commande_details (
    id INT PRIMARY KEY AUTO_INCREMENT,
    commande_id INT,
    plat_id INT,
    quantite INT NOT NULL,
    prix_unitaire DECIMAL(10,2) NOT NULL,
    instructions_speciales TEXT,
    FOREIGN KEY (commande_id) REFERENCES commandes(id),
    FOREIGN KEY (plat_id) REFERENCES plats(id)
);

-- Table des livraisons
CREATE TABLE IF NOT EXISTS livraisons (
    id INT PRIMARY KEY AUTO_INCREMENT,
    commande_id INT,
    livreur_id INT,
    heure_depart DATETIME,
    heure_livraison DATETIME,
    statut ENUM('assignee', 'en_cours', 'livree', 'probleme') DEFAULT 'assignee',
    commentaires TEXT,
    FOREIGN KEY (commande_id) REFERENCES commandes(id),
    FOREIGN KEY (livreur_id) REFERENCES users(id)
);

-- Insertion de données de test
-- Utilisateurs
INSERT INTO users (nom, prenom, email, password, telephone, adresse, role) VALUES
('Dubois', 'Jean', 'client@test.com', '$2y$10$fQmShchQhBtayphV.OdHhO.Py5lfzVxct1Juen4ZBdyKYxxZ8HD4m', '0123456789', '123 Rue de Paris, 75001 Paris', 'client'),
('Martin', 'Sophie', 'restaurateur@test.com', '$2y$10$fQmShchQhBtayphV.OdHhO.Py5lfzVxct1Juen4ZBdyKYxxZ8HD4m', '0234567891', 'Restaurant Le Gourmet, 75002 Paris', 'restaurateur'),
('Petit', 'Thomas', 'livreur@test.com', '$2y$10$fQmShchQhBtayphV.OdHhO.Py5lfzVxct1Juen4ZBdyKYxxZ8HD4m', '0345678912', '45 Avenue Victor Hugo, 75016 Paris', 'livreur'),
('Leroy', 'Marie', 'gerant@test.com', '$2y$10$fQmShchQhBtayphV.OdHhO.Py5lfzVxct1Juen4ZBdyKYxxZ8HD4m', '0456789123', 'Restaurant Le Gourmet, 75002 Paris', 'gerant');

-- Catégories
INSERT INTO categories (nom, description) VALUES
('Entrées', 'Petits plats pour commencer le repas'),
('Plats principaux', 'Plats consistants et savoureux'),
('Desserts', 'Douceurs pour finir le repas'),
('Boissons', 'Rafraîchissements variés');

-- Plats
INSERT INTO plats (nom, description, prix, image, categorie_id, disponible) VALUES
('Salade César', 'Salade romaine, croûtons, parmesan et sauce César', 8.50, 'salade_cesar.jpg', 1, TRUE),
('Foie gras maison', 'Foie gras mi-cuit avec chutney de figues', 15.00, 'foie_gras.jpg', 1, TRUE),
('Steak frites', 'Entrecôte grillée avec frites maison', 18.50, 'steak_frites.jpg', 2, TRUE),
('Risotto aux champignons', 'Risotto crémeux aux champignons sauvages', 16.00, 'risotto.jpg', 2, TRUE),
('Tiramisu', 'Dessert italien au café et mascarpone', 7.50, 'tiramisu.jpg', 3, TRUE),
('Mousse au chocolat', 'Mousse légère au chocolat noir', 6.50, 'mousse_chocolat.jpg', 3, TRUE),
('Eau minérale', 'Bouteille d\'eau minérale 50cl', 3.00, 'eau.jpg', 4, TRUE),
('Vin rouge', 'Verre de vin rouge de la maison', 5.50, 'vin_rouge.jpg', 4, TRUE);

-- Stocks
INSERT INTO stocks (ingredient, quantite, unite, seuil_alerte) VALUES
('Farine', 10, 'kg', 3),
('Œufs', 120, 'pièce', 30),
('Bœuf haché', 5, 'kg', 2),
('Champignons', 3, 'kg', 1),
('Salade romaine', 5, 'kg', 1),
('Parmesan', 2, 'kg', 0.5),
('Vin rouge', 20, 'bouteille', 5);
