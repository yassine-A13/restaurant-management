
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

// Gestion des messages
$message = "";
$message_type = "";
if (isset($_SESSION['message'])) {
    $message = $_SESSION['message'];
    $message_type = $_SESSION['message_type'] ?? 'success';
    unset($_SESSION['message']);
    unset($_SESSION['message_type']);
}

// Traitement du formulaire d'ajout
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'ajouter') {
    $nom = clean_input($_POST['nom']);
    
    if (empty($nom)) {
        $message = "Le nom de la catégorie est requis.";
        $message_type = "danger";
    } else {
        $query = "INSERT INTO categories (nom) VALUES (:nom)";
        $stmt = $db->prepare($query);
        $stmt->bindParam(":nom", $nom);
        
        if ($stmt->execute()) {
            $message = "Catégorie ajoutée avec succès.";
            $message_type = "success";
        } else {
            $message = "Une erreur est survenue lors de l'ajout de la catégorie.";
            $message_type = "danger";
        }
    }
}

// Traitement du formulaire de modification
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'modifier') {
    $categorie_id = clean_input($_POST['categorie_id']);
    $nom = clean_input($_POST['nom']);
    
    if (empty($nom) || empty($categorie_id)) {
        $message = "Toutes les informations sont requises.";
        $message_type = "danger";
    } else {
        $query = "UPDATE categories SET nom = :nom WHERE id = :id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(":nom", $nom);
        $stmt->bindParam(":id", $categorie_id);
        
        if ($stmt->execute()) {
            $message = "Catégorie modifiée avec succès.";
            $message_type = "success";
        } else {
            $message = "Une erreur est survenue lors de la modification de la catégorie.";
            $message_type = "danger";
        }
    }
}

// Récupérer toutes les catégories
$query = "SELECT c.*, COUNT(p.id) AS nb_plats 
          FROM categories c 
          LEFT JOIN plats p ON c.id = p.categorie_id 
          GROUP BY c.id 
          ORDER BY c.nom";
$stmt = $db->prepare($query);
$stmt->execute();
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Afficher la page de gestion des catégories
include_once '../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1>Gestion des catégories</h1>
    <a href="plats.php" class="btn btn-secondary">
        <i class="fas fa-arrow-left me-2"></i>Retour aux plats
    </a>
</div>

<?php if (!empty($message)): ?>
    <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show mb-4">
        <?php echo $message; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<div class="row">
    <div class="col-md-4">
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">Ajouter une catégorie</h5>
            </div>
            <div class="card-body">
                <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                    <input type="hidden" name="action" value="ajouter">
                    <div class="mb-3">
                        <label for="nom" class="form-label">Nom de la catégorie</label>
                        <input type="text" class="form-control" id="nom" name="nom" required>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-plus me-2"></i>Ajouter
                    </button>
                </form>
            </div>
        </div>
    </div>
    
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Liste des catégories</h5>
            </div>
            <div class="card-body">
                <?php if (count($categories) > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>Nom</th>
                                    <th>Nombre de plats</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($categories as $categorie): ?>
                                    <tr>
                                        <td><?php echo $categorie['nom']; ?></td>
                                        <td><?php echo $categorie['nb_plats']; ?></td>
                                        <td>
                                            <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editModal<?php echo $categorie['id']; ?>">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            
                                            <!-- Modal d'édition pour chaque catégorie -->
                                            <div class="modal fade" id="editModal<?php echo $categorie['id']; ?>" tabindex="-1" aria-labelledby="editModalLabel<?php echo $categorie['id']; ?>" aria-hidden="true">
                                                <div class="modal-dialog">
                                                    <div class="modal-content">
                                                        <div class="modal-header">
                                                            <h5 class="modal-title" id="editModalLabel<?php echo $categorie['id']; ?>">Modifier la catégorie</h5>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                        </div>
                                                        <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                                                            <div class="modal-body">
                                                                <input type="hidden" name="action" value="modifier">
                                                                <input type="hidden" name="categorie_id" value="<?php echo $categorie['id']; ?>">
                                                                <div class="mb-3">
                                                                    <label for="nom<?php echo $categorie['id']; ?>" class="form-label">Nom de la catégorie</label>
                                                                    <input type="text" class="form-control" id="nom<?php echo $categorie['id']; ?>" name="nom" value="<?php echo $categorie['nom']; ?>" required>
                                                                </div>
                                                            </div>
                                                            <div class="modal-footer">
                                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                                                                <button type="submit" class="btn btn-primary">Enregistrer</button>
                                                            </div>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="text-center py-4">
                        <p class="text-muted mb-0">Aucune catégorie disponible.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include_once '../includes/footer.php'; ?>