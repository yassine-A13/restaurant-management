
<?php
session_start();
include_once '../config/database.php';
include_once '../includes/functions.php';

// Vérifier si l'utilisateur est connecté et est un restaurateur
if (!check_role('restaurateur')) {
    header("Location: ../login.php");
    exit();
}

$database = new Database();
$db = $database->getConnection();

if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $commande_id = $_GET['id'];
    
    // Vérifier que la commande existe et est en cours de préparation
    $query = "SELECT * FROM commandes WHERE id = :id AND statut = 'en_preparation'";
    $stmt = $db->prepare($query);
    $stmt->bindParam(":id", $commande_id);
    $stmt->execute();
    
    if ($stmt->rowCount() > 0) {
        // Mettre à jour le statut de la commande
        $query = "UPDATE commandes SET statut = 'prete' WHERE id = :id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(":id", $commande_id);
        
        if ($stmt->execute()) {
            $_SESSION['message'] = "La commande est maintenant prête à être livrée.";
            $_SESSION['message_type'] = "success";
        } else {
            $_SESSION['message'] = "Une erreur est survenue lors de la mise à jour de la commande.";
            $_SESSION['message_type'] = "danger";
        }
    } else {
        $_SESSION['message'] = "Cette commande ne peut pas être marquée comme prête.";
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