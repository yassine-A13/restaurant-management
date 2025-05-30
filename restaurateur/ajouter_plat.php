
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

// Récupérer toutes les catégories
$query = "SELECT * FROM categories ORDER BY nom";
$stmt = $db->prepare($query);
$stmt->execute();
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Variables pour les messages
$message = "";
$message_type = "";

// Traitement du formulaire
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
        $image = "";
        
        // Traitement de l'image si elle est fournie
        if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
            $allowed = ['jpg', 'jpeg', 'png', 'gif'];
            $filename = $_FILES['image']['name'];
            $filetype = pathinfo($filename, PATHINFO_EXTENSION);
            
            if (in_array(strtolower($filetype), $allowed)) {
                $new_name = uniqid() . '.' . $filetype;
                $upload_dir = '../assets/images/';
                $upload_file = $upload_dir . $new_name;
                
                if (move_uploaded_file($_FILES['image']['tmp_name'], $upload_file)) {
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
        
        // Si pas d'erreur, insérer le plat dans la base de données
        if (empty($message)) {
            $query = "INSERT INTO plats (nom, description, prix, categorie_id, disponible, image) 
                      VALUES (:nom, :description, :prix, :categorie_id, :disponible, :image)";
            $stmt = $db->prepare($query);
            $stmt->bindParam(":nom", $nom);
            $stmt->bindParam(":description", $description);
            $stmt->bindParam(":prix", $prix);
            $stmt->bindParam(":categorie_id", $categorie_id);
            $stmt->bindParam(":disponible", $disponible);
            $stmt->bindParam(":image", $image);
            
            if ($stmt->execute()) {
                $_SESSION['message'] = "Plat ajouté avec succès.";
                $_SESSION['message_type'] = "success";
                header("Location: plats.php");
                exit();
            } else {
                $message = "Erreur lors de l'ajout du plat.";
                $message_type = "danger";
            }
        }
    }
}

// Afficher la page d'ajout de plat
include_once '../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1>Ajouter un plat</h1>
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
        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" enctype="multipart/form-data">
            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="nom" class="form-label">Nom du plat <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="nom" name="nom" required>
                </div>
                <div class="col-md-6">
                    <label for="categorie_id" class="form-label">Catégorie <span class="text-danger">*</span></label>
                    <select class="form-select" id="categorie_id" name="categorie_id" required>
                        <option value="">Sélectionnez une catégorie</option>
                        <?php foreach ($categories as $categorie): ?>
                            <option value="<?php echo $categorie['id']; ?>"><?php echo $categorie['nom']; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            
            <div class="mb-3">
                <label for="description" class="form-label">Description <span class="text-danger">*</span></label>
                <textarea class="form-control" id="description" name="description" rows="3" required></textarea>
            </div>
            
            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="prix" class="form-label">Prix (DH) <span class="text-danger">*</span></label>
                    <input type="number" class="form-control" id="prix" name="prix" step="0.01" min="0" required>
                </div>
                <div class="col-md-6">
                    <label for="image" class="form-label">Image</label>
                    <input type="file" class="form-control" id="image" name="image">
                    <small class="form-text text-muted">Formats acceptés: JPG, JPEG, PNG, GIF</small>
                </div>
            </div>
            
            <div class="mb-4 form-check form-switch">
                <input class="form-check-input" type="checkbox" role="switch" id="disponible" name="disponible" checked>
                <label class="form-check-label" for="disponible">Disponible</label>
            </div>
            
            <div class="d-flex justify-content-end">
                <button type="reset" class="btn btn-outline-secondary me-2">Réinitialiser</button>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save me-2"></i>Ajouter le plat
                </button>
            </div>
        </form>
    </div>
</div>

<?php include_once '../includes/footer.php'; ?>