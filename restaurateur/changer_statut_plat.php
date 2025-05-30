
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
    $plat_id = $_GET['id'];
    
    // Vérifier que le plat existe
    $query = "SELECT * FROM plats WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(":id", $plat_id);
    $stmt->execute();
    
    if ($stmt->rowCount() > 0) {
        $plat = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Inverser le statut de disponibilité
        $nouveau_statut = $plat['disponible'] ? 0 : 1;
        
        $query = "UPDATE plats SET disponible = :disponible WHERE id = :id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(":disponible", $nouveau_statut);
        $stmt->bindParam(":id", $plat_id);
        
        if ($stmt->execute()) {
            $_SESSION['message'] = "Le statut du plat a été mis à jour.";
            $_SESSION['message_type'] = "success";
        } else {
            $_SESSION['message'] = "Une erreur est survenue lors de la mise à jour du statut.";
            $_SESSION['message_type'] = "danger";
        }
    } else {
        $_SESSION['message'] = "Plat introuvable.";
        $_SESSION['message_type'] = "danger";
    }
} else {
    $_SESSION['message'] = "ID de plat invalide.";
    $_SESSION['message_type'] = "danger";
}

// Rediriger vers la page des plats
header("Location: plats.php");
exit();
?>