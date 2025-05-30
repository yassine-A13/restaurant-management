
<?php
session_start();
include_once '../config/database.php';
include_once '../includes/functions.php';

// Vérifier si l'utilisateur est connecté et est un livreur
if (!check_role('livreur')) {
    header("Location: ../login.php");
    exit();
}

// Vérifier les paramètres
if (!isset($_GET['id']) || !is_numeric($_GET['id']) || !isset($_GET['action'])) {
    header("Location: livraisons.php");
    exit();
}

$livraison_id = $_GET['id'];
$action = $_GET['action'];
$user_id = $_SESSION['user_id'];

// Vérifier que l'action est valide
$actions_valides = ['start', 'complete', 'problem'];
if (!in_array($action, $actions_valides)) {
    header("Location: livraisons.php");
    exit();
}

$database = new Database();
$db = $database->getConnection();

// Vérifier que la livraison appartient bien au livreur connecté
$query = "SELECT l.*, c.id as commande_id, c.statut as commande_statut 
          FROM livraisons l
          JOIN commandes c ON l.commande_id = c.id
          WHERE l.id = :id AND l.livreur_id = :livreur_id";
$stmt = $db->prepare($query);
$stmt->bindParam(":id", $livraison_id);
$stmt->bindParam(":livreur_id", $user_id);
$stmt->execute();

if ($stmt->rowCount() == 0) {
    header("Location: livraisons.php");
    exit();
}

$livraison = $stmt->fetch(PDO::FETCH_ASSOC);

// Traiter l'action demandée
$message = "";
$type_message = "danger";

try {
    $db->beginTransaction();
    
    switch ($action) {
        case 'start':
            // Vérifier que la commande est prête et que la livraison est assignée
            if ($livraison['commande_statut'] == 'prete' && $livraison['statut'] == 'assignee') {
                // Mettre à jour le statut de la commande
                $query = "UPDATE commandes SET statut = 'en_livraison' WHERE id = :commande_id";
                $stmt = $db->prepare($query);
                $stmt->bindParam(":commande_id", $livraison['commande_id']);
                $stmt->execute();
                
                // Mettre à jour le statut de la livraison
                $query = "UPDATE livraisons SET statut = 'en_cours', heure_depart  = NOW() WHERE id = :id";
                $stmt = $db->prepare($query);
                $stmt->bindParam(":id", $livraison_id);
                $stmt->execute();
                
                $message = "La livraison a été démarrée avec succès.";
                $type_message = "success";
            } else {
                $message = "Impossible de démarrer cette livraison. Vérifiez que la commande est prête.";
            }
            break;
            
        case 'complete':
            // Vérifier que la livraison est en cours
            if ($livraison['commande_statut'] == 'en_livraison' && $livraison['statut'] == 'en_cours') {
                // Mettre à jour le statut de la commande
                $query = "UPDATE commandes SET statut = 'livree' WHERE id = :commande_id";
                $stmt = $db->prepare($query);
                $stmt->bindParam(":commande_id", $livraison['commande_id']);
                $stmt->execute();
                
                // Mettre à jour le statut de la livraison
                $query = "UPDATE livraisons SET statut = 'livree', date_fin = NOW() WHERE id = :id";
                $stmt = $db->prepare($query);
                $stmt->bindParam(":id", $livraison_id);
                $stmt->execute();
                
                $message = "La livraison a été marquée comme terminée.";
                $type_message = "success";
            } else {
                $message = "Impossible de terminer cette livraison. Vérifiez qu'elle est en cours.";
            }
            break;
            
        case 'problem':
            // Créer une entrée dans la table des problèmes
            $commentaire = isset($_POST['commentaire']) ? clean_input($_POST['commentaire']) : "";
            
            // Si le formulaire est soumis
            if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['signaler'])) {
                if (empty($commentaire)) {
                    $message = "Veuillez décrire le problème rencontré.";
                } else {
                    // Mettre à jour le statut de la livraison
                    $query = "UPDATE livraisons SET statut = 'probleme', commentaire = :commentaire WHERE id = :id";
                    $stmt = $db->prepare($query);
                    $stmt->bindParam(":commentaire", $commentaire);
                    $stmt->bindParam(":id", $livraison_id);
                    $stmt->execute();
                    
                    $message = "Le problème a été signalé avec succès.";
                    $type_message = "success";
                    
                    // Rediriger après le traitement
                    $db->commit();
                    $_SESSION['message'] = $message;
                    $_SESSION['message_type'] = $type_message;
                    header("Location: livraisons.php");
                    exit();
                }
            } else {
                // Afficher le formulaire
                include_once '../includes/header.php';
                ?>
                <div class="container-fluid px-4 py-4">
                    <div class="row justify-content-center">
                        <div class="col-lg-6">
                            <div class="card mb-4">
                                <div class="card-header">
                                    <h4 class="mb-0">Signaler un problème</h4>
                                </div>
                                <div class="card-body">
                                    <form method="POST">
                                        <div class="mb-3">
                                            <label for="commentaire" class="form-label">Description du problème</label>
                                            <textarea class="form-control" id="commentaire" name="commentaire" rows="5" required></textarea>
                                        </div>
                                        <div class="d-grid gap-2">
                                            <button type="submit" name="signaler" class="btn btn-danger">
                                                <i class="fas fa-exclamation-triangle me-1"></i> Signaler le problème
                                            </button>
                                            <a href="livraisons.php" class="btn btn-secondary">Annuler</a>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php
                include_once '../includes/footer.php';
                exit(); // Arrêter l'exécution après avoir affiché le formulaire
            }
            break;
    }
    
    $db->commit();
} catch (Exception $e) {
    $db->rollBack();
    $message = "Une erreur est survenue: " . $e->getMessage();
}

// Rediriger avec un message
$_SESSION['message'] = $message;
$_SESSION['message_type'] = $type_message;
header("Location: livraisons.php");
exit();
?>