
<?php
session_start();
include_once '../config/database.php';
include_once '../includes/functions.php';

// Vérifier si l'utilisateur est connecté et est un client
if (!check_role('client')) {
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$database = new Database();
$db = $database->getConnection();

if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $commande_id = $_GET['id'];
    
    // Vérifier que la commande appartient bien au client connecté
    $query = "SELECT * FROM commandes WHERE id = :id AND client_id = :client_id AND statut = 'nouvelle'";
    $stmt = $db->prepare($query);
    $stmt->bindParam(":id", $commande_id);
    $stmt->bindParam(":client_id", $user_id);
    $stmt->execute();
    
    if ($stmt->rowCount() > 0) {
        // Mettre à jour le statut de la commande
        $query = "UPDATE commandes SET statut = 'annulee' WHERE id = :id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(":id", $commande_id);
        
        if ($stmt->execute()) {
            $_SESSION['message'] = "Votre commande a été annulée avec succès.";
            $_SESSION['message_type'] = "success";
        } else {
            $_SESSION['message'] = "Une erreur est survenue lors de l'annulation de votre commande.";
            $_SESSION['message_type'] = "danger";
        }
    } else {
        $_SESSION['message'] = "Cette commande ne peut pas être annulée ou n'appartient pas à votre compte.";
        $_SESSION['message_type'] = "danger";
    }
} else {
    $_SESSION['message'] = "Commande invalide.";
    $_SESSION['message_type'] = "danger";
}

// Rediriger vers la page des commandes
header("Location: commandes.php");
exit();
?>
