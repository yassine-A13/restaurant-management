
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

// Récupérer toutes les catégories
$query = "SELECT * FROM categories ORDER BY nom";
$stmt = $db->prepare($query);
$stmt->execute();
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Filtrage des plats par catégorie
$categorie_id = isset($_GET['categorie']) ? $_GET['categorie'] : '';
$where_clause = !empty($categorie_id) ? "WHERE p.categorie_id = :categorie_id" : "";

// Récupérer tous les plats avec leur catégorie
$query = "SELECT p.*, c.nom as categorie_nom FROM plats p 
          LEFT JOIN categories c ON p.categorie_id = c.id 
          $where_clause
          ORDER BY p.nom";
$stmt = $db->prepare($query);
if (!empty($categorie_id)) {
    $stmt->bindParam(":categorie_id", $categorie_id);
}
$stmt->execute();
$plats = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Afficher la page de gestion des plats
include_once '../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1>Gestion des plats</h1>
    <a href="ajouter_plat.php" class="btn btn-primary">
        <i class="fas fa-plus me-2"></i>Ajouter un plat
    </a>
</div>

<?php if (!empty($message)): ?>
    <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show mb-4">
        <?php echo $message; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<div class="card mb-4">
    <div class="card-header">
        <div class="d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Liste des plats</h5>
            <div>
                
                <div class="dropdown d-inline-block">
                    <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" id="dropdownCategories" data-bs-toggle="dropdown" aria-expanded="false">
                        <?php echo !empty($categorie_id) ? 'Catégorie : ' . array_filter($categories, function($c) use ($categorie_id) { return $c['id'] == $categorie_id; })[array_key_first(array_filter($categories, function($c) use ($categorie_id) { return $c['id'] == $categorie_id; }))] ['nom'] : 'Toutes les catégories'; ?>
                    </button>
                    <ul class="dropdown-menu" aria-labelledby="dropdownCategories">
                        <li><a class="dropdown-item" href="?">Toutes les catégories</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <?php foreach ($categories as $categorie): ?>
                            <li><a class="dropdown-item" href="?categorie=<?php echo $categorie['id']; ?>"><?php echo $categorie['nom']; ?></a></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        </div>
    </div>
    <div class="card-body">
        <?php if (count($plats) > 0): ?>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>Image</th>
                            <th>Nom</th>
                            <th>Description</th>
                            <th>Prix</th>
                            <th>Catégorie</th>
                            <th>Statut</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($plats as $plat): ?>
                            <tr>
                                <td>
                                    <img src="<?php echo !empty($plat['image']) ? '../assets/images/' . $plat['image'] : '../assets/images/placeholder.jpg'; ?>" 
                                        alt="<?php echo $plat['nom']; ?>" class="img-thumbnail" width="50">
                                </td>
                                <td><?php echo $plat['nom']; ?></td>
                                <td><?php echo substr($plat['description'], 0, 50) . (strlen($plat['description']) > 50 ? '...' : ''); ?></td>
                                <td><?php echo $plat['prix']." DH" ; ?></td>
                                <td><?php echo $plat['categorie_nom']; ?></td>
                                <td>
                                    <?php if ($plat['disponible']): ?>
                                        <span class="badge bg-success">Disponible</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger">Indisponible</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="btn-group">
                                        <a href="modifier_plat.php?id=<?php echo $plat['id']; ?>" class="btn btn-sm btn-outline-primary">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="changer_statut_plat.php?id=<?php echo $plat['id']; ?>" class="btn btn-sm btn-outline-<?php echo $plat['disponible'] ? 'danger' : 'success'; ?>">
                                            <i class="fas fa-<?php echo $plat['disponible'] ? 'times' : 'check'; ?>"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="text-center py-4">
                <i class="fas fa-utensils fa-3x mb-3 text-muted"></i>
                <p>Aucun plat à afficher <?php echo !empty($categorie_id) ? 'dans cette catégorie' : ''; ?>.</p>
                <a href="ajouter_plat.php" class="btn btn-primary btn-sm">Ajouter un plat</a>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include_once '../includes/footer.php'; ?>
