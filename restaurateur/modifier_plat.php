
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

// Vérifier si un ID de plat est fourni
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['message'] = "ID de plat invalide.";
    $_SESSION['message_type'] = "danger";
    header("Location: plats.php");
    exit();
}

$plat_id = $_GET['id'];

// Récupérer les informations du plat
$query = "SELECT * FROM plats WHERE id = :id";
$stmt = $db->prepare($query);
$stmt->bindParam(":id", $plat_id);
$stmt->execute();
$plat = $stmt->fetch(PDO::FETCH_ASSOC);

// Vérifier si le plat existe
if (!$plat) {
    $_SESSION['message'] = "Plat non trouvé.";
    $_SESSION['message_type'] = "danger";
    header("Location: plats.php");
    exit();
}

// Récupérer toutes les catégories
$query = "SELECT * FROM categories ORDER BY nom";
$stmt = $db->prepare($query);
$stmt->execute();
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Variables pour les messages
$message = "";
$message_type = "";

// Traitement du formulaire de modification
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nom = clean_input($_POST['nom']);
    $description = clean_input($_POST['description']);
    $prix = clean_input($_POST['prix']);
    $categorie_id = clean_input($_POST['categorie_id']);
    $disponible = isset($_POST['disponible']) ? 1 : 0;
    
    // Validation des données
    if (empty($nom) || empty($description) || empty($prix) || empty($categorie_id)) {
        $message = "Veuillez remplir tous les champs obligatoires.";
        $message_type = "danger";
    } else {
        $image = $plat['image']; // Garder l'image existante par défaut
        
        // Traitement de la nouvelle image si elle est fournie
        if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
            $allowed = ['jpg', 'jpeg', 'png', 'gif'];
            $filename = $_FILES['image']['name'];
            $filetype = pathinfo($filename, PATHINFO_EXTENSION);
            
            if (in_array(strtolower($filetype), $allowed)) {
                $new_name = uniqid() . '.' . $filetype;
                $upload_dir = '../assets/images/';
                $upload_file = $upload_dir . $new_name;
                
                if (move_uploaded_file($_FILES['image']['tmp_name'], $upload_file)) {
                    // Supprimer l'ancienne image si elle existe
                    if (!empty($plat['image']) && file_exists($upload_dir . $plat['image'])) {
                        unlink($upload_dir . $plat['image']);
                    }
                    $image = $new_name;
                } else {
                    $message = "Échec de l'upload de l'image.";
                    $message_type = "danger";
                }
            } else {
                $message = "Le format de l'image n'est pas accepté. Utilisez JPG, JPEG, PNG ou GIF.";
                $message_type = "danger";
            }
        }
        
        // Si pas d'erreur, mettre à jour le plat dans la base de données
        if (empty($message)) {
            $query = "UPDATE plats SET nom = :nom, description = :description, prix = :prix, 
                      categorie_id = :categorie_id, disponible = :disponible, image = :image 
                      WHERE id = :id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(":nom", $nom);
            $stmt->bindParam(":description", $description);
            $stmt->bindParam(":prix", $prix);
            $stmt->bindParam(":categorie_id", $categorie_id);
            $stmt->bindParam(":disponible", $disponible);
            $stmt->bindParam(":image", $image);
            $stmt->bindParam(":id", $plat_id);
            
            if ($stmt->execute()) {
                $_SESSION['message'] = "Plat modifié avec succès.";
                $_SESSION['message_type'] = "success";
                header("Location: plats.php");
                exit();
            } else {
                $message = "Erreur lors de la modification du plat.";
                $message_type = "danger";
            }
        }
    }
}

// Afficher la page de modification de plat
include_once '../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1>Modifier un plat</h1>
    <a href="plats.php" class="btn btn-secondary">
        <i class="fas fa-arrow-left me-2"></i>Retour à la liste
    </a>
</div>

<?php if (!empty($message)): ?>
    <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show mb-4">
        <?php echo $message; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<div class="card">
    <div class="card-header">
        <h5 class="mb-0">Informations du plat</h5>
    </div>
    <div class="card-body">
        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"] . "?id=" . $plat_id); ?>" method="post" enctype="multipart/form-data">
            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="nom" class="form-label">Nom du plat <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="nom" name="nom" value="<?php echo htmlspecialchars($plat['nom']); ?>" required>
                </div>
                <div class="col-md-6">
                    <label for="categorie_id" class="form-label">Catégorie <span class="text-danger">*</span></label>
                    <select class="form-select" id="categorie_id" name="categorie_id" required>
                        <option value="">Sélectionnez une catégorie</option>
                        <?php foreach ($categories as $categorie): ?>
                            <option value="<?php echo $categorie['id']; ?>" <?php echo ($plat['categorie_id'] == $categorie['id']) ? 'selected' : ''; ?>>
                                <?php echo $categorie['nom']; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            
            <div class="mb-3">
                <label for="description" class="form-label">Description <span class="text-danger">*</span></label>
                <textarea class="form-control" id="description" name="description" rows="3" required><?php echo htmlspecialchars($plat['description']); ?></textarea>
            </div>
            
            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="prix" class="form-label">Prix (DH) <span class="text-danger">*</span></label>
                    <input type="number" class="form-control" id="prix" name="prix" step="0.01" min="0" value="<?php echo htmlspecialchars($plat['prix']); ?>" required>
                </div>
                <div class="col-md-6">
                    <label for="image" class="form-label">Image</label>
                    <input type="file" class="form-control" id="image" name="image">
                    <small class="form-text text-muted">Formats acceptés: JPG, JPEG, PNG, GIF. Laissez vide pour conserver l'image actuelle.</small>
                </div>
            </div>
            
            <?php if (!empty($plat['image'])): ?>
                <div class="mb-3">
                    <label class="form-label">Image actuelle</label>
                    <div>
                        <img src="<?php echo '../assets/images/' . $plat['image']; ?>" alt="<?php echo $plat['nom']; ?>" class="img-thumbnail" style="max-height: 150px;">
                    </div>
                </div>
            <?php endif; ?>
            
            <div class="mb-4 form-check form-switch">
                <input class="form-check-input" type="checkbox" role="switch" id="disponible" name="disponible" <?php echo $plat['disponible'] ? 'checked' : ''; ?>>
                <label class="form-check-label" for="disponible">Disponible</label>
            </div>
            
            <div class="d-flex justify-content-end">
                <button type="reset" class="btn btn-outline-secondary me-2">Réinitialiser</button>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save me-2"></i>Enregistrer les modifications
                </button>
            </div>
        </form>
    </div>
</div>

<?php include_once '../includes/footer.php'; ?>