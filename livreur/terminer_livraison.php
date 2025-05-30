
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
    $livraison_id = $_GET['id'];
    
    // Vérifier que la livraison existe et appartient au livreur connecté
    $query = "SELECT l.*, c.id as commande_id FROM livraisons l 
              JOIN commandes c ON l.commande_id = c.id 
              WHERE l.id = :id AND l.livreur_id = :livreur_id AND c.statut = 'en_livraison'";
    $stmt = $db->prepare($query);
    $stmt->bindParam(":id", $livraison_id);
    $stmt->bindParam(":livreur_id", $user_id);
    $stmt->execute();
    $livraison = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($livraison) {
        try {
            // Démarrer une transaction
            $db->beginTransaction();
            
            // Mettre à jour le statut de la commande
            $query = "UPDATE commandes SET statut = 'livree' WHERE id = :id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(":id", $livraison['commande_id']);
            $stmt->execute();
            
            // Mettre à jour la livraison
            $query = "UPDATE livraisons SET statut = 'livree', heure_livraison = NOW() WHERE id = :id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(":id", $livraison_id);
            $stmt->execute();
            
            // Valider la transaction
            $db->commit();
            
            $_SESSION['message'] = "La livraison a été confirmée avec succès.";
            $_SESSION['message_type'] = "success";
        } catch (Exception $e) {
            // Annuler la transaction en cas d'erreur
            $db->rollBack();
            
            $_SESSION['message'] = "Une erreur est survenue lors de la confirmation de la livraison.";
            $_SESSION['message_type'] = "danger";
        }
    } else {
        $_SESSION['message'] = "Cette livraison n'est pas disponible ou ne vous appartient pas.";
        $_SESSION['message_type'] = "danger";
    }
} else {
    $_SESSION['message'] = "Livraison invalide.";
    $_SESSION['message_type'] = "danger";
}

// Rediriger vers le dashboard du livreur
header("Location: dashboard.php");
exit();
?>