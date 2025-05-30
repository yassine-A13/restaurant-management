<?php
session_start();
include_once '../config/database.php';
include_once '../includes/functions.php';

// Vérifier si l'utilisateur est connecté et est un livreur
if (!check_role('livreur')) {
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$database = new Database();
$db = $database->getConnection();

if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $commande_id = $_GET['id'];
    
    // Vérifier que la commande existe et est prête à être livrée
    $query = "SELECT * FROM commandes WHERE id = :id AND statut = 'prete'";
    $stmt = $db->prepare($query);
    $stmt->bindParam(":id", $commande_id);
    $stmt->execute();
    
    if ($stmt->rowCount() > 0) {
        try {
            // Démarrer une transaction
            $db->beginTransaction();
            
            // Mettre à jour le statut de la commande
            $query = "UPDATE commandes SET statut = 'en_livraison' WHERE id = :id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(":id", $commande_id);
            $stmt->execute();
            
            // Créer une entrée dans la table livraisons
            $query = "INSERT INTO livraisons (commande_id, livreur_id, heure_depart) 
                      VALUES (:commande_id, :livreur_id, NOW())";
            $stmt = $db->prepare($query);
            $stmt->bindParam(":commande_id", $commande_id);
            $stmt->bindParam(":livreur_id", $user_id);
            $stmt->execute();
            
            // Valider la transaction
            $db->commit();
            
            $_SESSION['message'] = "La commande a été prise en charge avec succès.";
            $_SESSION['message_type'] = "success";
        } catch (Exception $e) {
            // Annuler la transaction en cas d'erreur
            $db->rollBack();
            
            $_SESSION['message'] = "Une erreur est survenue lors de la prise en charge de la commande.";
            $_SESSION['message_type'] = "danger";
        }
    } else {
        $_SESSION['message'] = "Cette commande n'est pas disponible pour la livraison.";
        $_SESSION['message_type'] = "danger";
    }
} else {
    $_SESSION['message'] = "Commande invalide.";
    $_SESSION['message_type'] = "danger";
}

// Rediriger vers le dashboard du livreur
header("Location: dashboard.php");
exit();
?>