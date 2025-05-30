
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
        
        try {
            // Démarrer une transaction
            $db->beginTransaction();
            
            // Vérifier si le plat est utilisé dans des commandes
            $query = "SELECT COUNT(*) FROM commande_details WHERE plat_id = :plat_id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(":plat_id", $plat_id);
            $stmt->execute();
            $used_in_orders = (int)$stmt->fetchColumn() > 0;
            
            if ($used_in_orders) {
                // Si le plat est utilisé dans des commandes, on le désactive plutôt que de le supprimer
                $query = "UPDATE plats SET disponible = 0 WHERE id = :id";
                $stmt = $db->prepare($query);
                $stmt->bindParam(":id", $plat_id);
                $stmt->execute();
                
                $_SESSION['message'] = "Le plat a été désactivé car il est utilisé dans des commandes existantes.";
                $_SESSION['message_type'] = "warning";
            } else {
                // Supprimer l'image associée si elle existe
                if (!empty($plat['image'])) {
                    $image_path = '../assets/images/' . $plat['image'];
                    if (file_exists($image_path)) {
                        unlink($image_path);
                    }
                }
                
                // Supprimer le plat
                $query = "DELETE FROM plats WHERE id = :id";
                $stmt = $db->prepare($query);
                $stmt->bindParam(":id", $plat_id);
                $stmt->execute();
                
                $_SESSION['message'] = "Le plat a été supprimé avec succès.";
                $_SESSION['message_type'] = "success";
            }
            
            // Valider la transaction
            $db->commit();
        } catch (Exception $e) {
            // Annuler la transaction en cas d'erreur
            $db->rollBack();
            
            $_SESSION['message'] = "Une erreur est survenue lors de la suppression du plat.";
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