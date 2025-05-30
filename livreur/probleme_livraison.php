
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

$message = "";
$success = false;
$livraison = null;

if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $livraison_id = $_GET['id'];
    
    // Récupérer les informations de la livraison
    $query = "SELECT l.*, c.id as commande_id, c.adresse_livraison, 
              cl.nom as client_nom, cl.prenom as client_prenom, cl.telephone as client_telephone 
              FROM livraisons l 
              JOIN commandes c ON l.commande_id = c.id 
              JOIN users cl ON c.client_id = cl.id 
              WHERE l.id = :id AND l.livreur_id = :livreur_id AND c.statut = 'en_livraison'";
    $stmt = $db->prepare($query);
    $stmt->bindParam(":id", $livraison_id);
    $stmt->bindParam(":livreur_id", $user_id);
    $stmt->execute();
    $livraison = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$livraison) {
        header("Location: dashboard.php");
        exit();
    }
}

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit'])) {
    $livraison_id = $_POST['livraison_id'];
    $commentaires = clean_input($_POST['commentaires']);
    
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
            
            // Mettre à jour la livraison
            $query = "UPDATE livraisons SET statut = 'probleme', commentaires = :commentaires WHERE id = :id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(":commentaires", $commentaires);
            $stmt->bindParam(":id", $livraison_id);
            $stmt->execute();
            
            // Valider la transaction
            $db->commit();
            
            $_SESSION['message'] = "Le problème de livraison a été signalé avec succès.";
            $_SESSION['message_type'] = "success";
            
            // Rediriger vers le dashboard du livreur
            header("Location: dashboard.php");
            exit();
        } catch (Exception $e) {
            // Annuler la transaction en cas d'erreur
            $db->rollBack();
            
            $message = "Une erreur est survenue lors du signalement du problème.";
        }
    } else {
        $message = "Cette livraison n'est pas disponible ou ne vous appartient pas.";
    }
}

// Afficher la page de problème de livraison
include_once '../includes/header.php';
?>

<div class="row">
    <div class="col-lg-8 mx-auto">
        <h1 class="mb-4">Signaler un problème de livraison</h1>
        
        <?php if (!empty($message)): ?>
            <div class="alert alert-<?php echo $success ? 'success' : 'danger'; ?> alert-dismissible fade show mb-4">
                <?php echo $message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        
        <?php if ($livraison): ?>
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Informations de la commande #<?php echo $livraison['commande_id']; ?></h5>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <h6>Client</h6>
                            <p>
                                <?php echo $livraison['client_nom'] . ' ' . $livraison['client_prenom']; ?><br>
                                <strong>Téléphone:</strong> <?php echo $livraison['client_telephone']; ?>
                            </p>
                        </div>
                        <div class="col-md-6">
                            <h6>Adresse de livraison</h6>
                            <p><?php echo nl2br($livraison['adresse_livraison']); ?></p>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Détails du problème</h5>
                </div>
                <div class="card-body">
                    <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                        <input type="hidden" name="livraison_id" value="<?php echo $livraison['id']; ?>">
                        
                        <div class="mb-3">
                            <label for="commentaires" class="form-label">Description du problème</label>
                            <textarea class="form-control" id="commentaires" name="commentaires" rows="5" required></textarea>
                            <div class="form-text">Veuillez décrire en détail le problème rencontré lors de la livraison.</div>
                        </div>
                        
                        <div class="d-flex justify-content-between">
                            <a href="dashboard.php" class="btn btn-secondary">Annuler</a>
                            <button type="submit" name="submit" class="btn btn-primary">
                                <i class="fas fa-exclamation-triangle me-2"></i>Signaler le problème
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        <?php else: ?>
            <div class="alert alert-danger mb-4">
                Livraison invalide ou non autorisée.
            </div>
            <div class="text-center">
                <a href="dashboard.php" class="btn btn-primary">Retour au dashboard</a>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include_once '../includes/footer.php'; ?>