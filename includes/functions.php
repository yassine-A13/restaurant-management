<?php
// Définir l'URL racine
define('ROOT_URL', '/');

// Fonction pour nettoyer les données d'entrée
function clean_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// Vérifier si l'utilisateur est connecté et a le bon rôle
function check_role($required_role) {
    // Si l'utilisateur n'est pas connecté
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
        return false;
    }
    
    // Si le rôle requis correspond au rôle de l'utilisateur
    if ($_SESSION['role'] === $required_role) {
        return true;
    }
    
    return false;
}

// Formater le prix
function format_prix($prix) {
    return number_format($prix, 2, ',', ' ') . ' €';
}

// Obtenir le libellé du statut de la commande
function get_statut_commande($statut) {
    switch ($statut) {
        case 'nouvelle':
            return 'Nouvelle';
        case 'en_preparation':
            return 'En préparation';
        case 'prete':
            return 'Prête';
        case 'en_livraison':
            return 'En livraison';
        case 'livree':
            return 'Livrée';
        case 'annulee':
            return 'Annulée';
        default:
            return 'Inconnu';
    }
}

// Obtenir le libellé du mode de paiement
function get_mode_paiement($mode) {
    switch ($mode) {
        case 'carte':
            return 'Carte bancaire';
        case 'especes':
            return 'Espèces';
        case 'en_ligne':
            return 'Paiement en ligne';
        default:
            return 'Inconnu';
    }
}

// Générer un identifiant unique pour les images
function generate_image_name($extension) {
    return uniqid() . '.' . $extension;
}

// Vérifier si c'est un upload d'image valide
function check_image_upload($file) {
    $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
    $max_size = 5 * 1024 * 1024; // 5 Mo
    
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return false;
    }
    
    if (!in_array($file['type'], $allowed_types)) {
        return false;
    }
    
    if ($file['size'] > $max_size) {
        return false;
    }
    
    return true;
}

// Uploader une image
function upload_image($file, $destination) {
    // Vérifier si l'image est valide
    if (!check_image_upload($file)) {
        return false;
    }
    
    // Générer un nom unique pour l'image
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $image_name = generate_image_name($extension);
    $upload_path = $destination . $image_name;
    
    // Déplacer l'image vers le dossier de destination
    if (move_uploaded_file($file['tmp_name'], $upload_path)) {
        return $image_name;
    }
    
    return false;
}

// Obtenir le nombre de commandes en attente
function get_waiting_orders_count($db) {
    $query = "SELECT COUNT(*) as count FROM commandes WHERE statut = 'nouvelle'";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    return $result['count'];
}

// Obtenir le nombre de plats disponibles
function get_available_dishes_count($db) {
    $query = "SELECT COUNT(*) as count FROM plats WHERE disponible = 1";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    return $result['count'];
}

// Obtenir le chiffre d'affaires
function get_revenue($db, $period = 'all') {
    $query = "SELECT SUM(montant_total) as total FROM commandes WHERE statut != 'annulee'";
    
    if ($period === 'today') {
        $query .= " AND DATE(date_commande) = CURDATE()";
    } elseif ($period === 'week') {
        $query .= " AND YEARWEEK(date_commande, 1) = YEARWEEK(CURDATE(), 1)";
    } elseif ($period === 'month') {
        $query .= " AND MONTH(date_commande) = MONTH(CURDATE()) AND YEAR(date_commande) = YEAR(CURDATE())";
    }
    
    $stmt = $db->prepare($query);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    return $result['total'] ?: 0;
}
?>