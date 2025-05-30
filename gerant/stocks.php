
<?php
session_start();
include_once '../config/database.php';
include_once '../includes/functions.php';

// Vérifier si l'utilisateur est connecté et est un gérant
if (!check_role('gerant')) {
    header("Location: ../login.php");
    exit();
}

$database = new Database();
$db = $database->getConnection();

// Traitement des actions (ajout/modification/suppression)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $message = "";
    $type_message = "danger";
    
    // Ajout d'un nouvel ingrédient
    if (isset($_POST['ajouter'])) {
        $ingredient = clean_input($_POST['ingredient']);
        $quantite = (float)$_POST['quantite'];
        $unite = clean_input($_POST['unite']);
        $seuil_alerte = (float)$_POST['seuil_alerte'];
        
        // Vérifier que l'ingrédient n'existe pas déjà
        $query = "SELECT id FROM stocks WHERE ingredient = :ingredient";
        $stmt = $db->prepare($query);
        $stmt->bindParam(":ingredient", $ingredient);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            $message = "Cet ingrédient existe déjà dans les stocks.";
        } else {
            // Insérer le nouvel ingrédient
            $query = "INSERT INTO stocks (ingredient, quantite, unite, seuil_alerte) 
                      VALUES (:ingredient, :quantite, :unite, :seuil_alerte)";
            $stmt = $db->prepare($query);
            $stmt->bindParam(":ingredient", $ingredient);
            $stmt->bindParam(":quantite", $quantite);
            $stmt->bindParam(":unite", $unite);
            $stmt->bindParam(":seuil_alerte", $seuil_alerte);
            
            if ($stmt->execute()) {
                $message = "Le nouvel ingrédient a été ajouté avec succès.";
                $type_message = "success";
            } else {
                $message = "Une erreur est survenue lors de l'ajout de l'ingrédient.";
            }
        }
    }
    
    // Modification d'un ingrédient
    else if (isset($_POST['modifier']) && isset($_POST['id'])) {
        $id = $_POST['id'];
        $ingredient = clean_input($_POST['ingredient']);
        $quantite = (float)$_POST['quantite'];
        $unite = clean_input($_POST['unite']);
        $seuil_alerte = (float)$_POST['seuil_alerte'];
        
        // Vérifier que l'ingrédient n'existe pas déjà avec le même nom (sauf lui-même)
        $query = "SELECT id FROM stocks WHERE ingredient = :ingredient AND id != :id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(":ingredient", $ingredient);
        $stmt->bindParam(":id", $id);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            $message = "Un autre ingrédient avec ce nom existe déjà.";
        } else {
            // Mettre à jour l'ingrédient
            $query = "UPDATE stocks SET ingredient = :ingredient, quantite = :quantite, 
                      unite = :unite, seuil_alerte = :seuil_alerte WHERE id = :id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(":ingredient", $ingredient);
            $stmt->bindParam(":quantite", $quantite);
            $stmt->bindParam(":unite", $unite);
            $stmt->bindParam(":seuil_alerte", $seuil_alerte);
            $stmt->bindParam(":id", $id);
            
            if ($stmt->execute()) {
                $message = "L'ingrédient a été mis à jour avec succès.";
                $type_message = "success";
            } else {
                $message = "Une erreur est survenue lors de la mise à jour de l'ingrédient.";
            }
        }
    }
    
    // Supprimer un ingrédient
    else if (isset($_POST['supprimer']) && isset($_POST['id'])) {
        $id = $_POST['id'];
        
        // Vérifier les dépendances avant de supprimer (ici, on suppose qu'il n'y a pas de dépendances)
        $query = "DELETE FROM stocks WHERE id = :id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(":id", $id);
        
        if ($stmt->execute()) {
            $message = "L'ingrédient a été supprimé avec succès.";
            $type_message = "success";
        } else {
            $message = "Une erreur est survenue lors de la suppression de l'ingrédient.";
        }
    }
    
    // Approvisionnement d'un ingrédient
    else if (isset($_POST['approvisionner']) && isset($_POST['id'])) {
        $id = $_POST['id'];
        $quantite_ajoutee = (float)$_POST['quantite_ajoutee'];
        
        if ($quantite_ajoutee <= 0) {
            $message = "La quantité ajoutée doit être supérieure à zéro.";
        } else {
            // Mettre à jour la quantité
            $query = "UPDATE stocks SET quantite = quantite + :quantite_ajoutee WHERE id = :id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(":quantite_ajoutee", $quantite_ajoutee);
            $stmt->bindParam(":id", $id);
            
            if ($stmt->execute()) {
                $message = "Le stock a été approvisionné avec succès.";
                $type_message = "success";
            } else {
                $message = "Une erreur est survenue lors de l'approvisionnement du stock.";
            }
        }
    }
    
    $_SESSION['message'] = $message;
    $_SESSION['message_type'] = $type_message;
    
    header("Location: stocks.php");
    exit();
}

// Pagination et filtres
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limite = 10;
$debut = ($page - 1) * $limite;

$filtre = isset($_GET['filtre']) ? $_GET['filtre'] : 'tous';
$condition = "";

switch ($filtre) {
    case 'alerte':
        $condition = "WHERE quantite <= seuil_alerte";
        break;
    case 'epuise':
        $condition = "WHERE quantite <= 0";
        break;
}

// Recherche
$recherche = isset($_GET['recherche']) ? $_GET['recherche'] : '';
if ($recherche) {
    $condition = $condition ? $condition . " AND ingredient LIKE :recherche" : "WHERE ingredient LIKE :recherche";
}

// Compter le nombre total d'ingrédients
$query = "SELECT COUNT(*) as total FROM stocks $condition";
$stmt = $db->prepare($query);

if ($recherche) {
    $recherche_param = "%$recherche%";
    $stmt->bindParam(":recherche", $recherche_param);
}

$stmt->execute();
$total_ingredients = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
$total_pages = ceil($total_ingredients / $limite);

// Récupérer les ingrédients avec pagination
$query = "SELECT * FROM stocks $condition ORDER BY quantite <= seuil_alerte DESC, ingredient LIMIT :debut, :limite";
$stmt = $db->prepare($query);

if ($recherche) {
    $recherche_param = "%$recherche%";
    $stmt->bindParam(":recherche", $recherche_param);
}

$stmt->bindParam(":debut", $debut, PDO::PARAM_INT);
$stmt->bindParam(":limite", $limite, PDO::PARAM_INT);
$stmt->execute();
$ingredients = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Statistiques de stocks
$query = "SELECT COUNT(*) as total, 
          SUM(CASE WHEN quantite <= seuil_alerte THEN 1 ELSE 0 END) as en_alerte,
          SUM(CASE WHEN quantite <= 0 THEN 1 ELSE 0 END) as epuise
          FROM stocks";
$stmt = $db->prepare($query);
$stmt->execute();
$stats_stocks = $stmt->fetch(PDO::FETCH_ASSOC);

include_once '../includes/header.php';
?>

<div class="container-fluid px-4 py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="mb-0">Gestion des stocks</h2>
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#ajoutModal">
            <i class="fas fa-plus me-1"></i> Ajouter un ingrédient
        </button>
    </div>

    <?php if (isset($_SESSION['message'])): ?>
        <div class="alert alert-<?php echo $_SESSION['message_type'] ?? 'info'; ?> alert-dismissible fade show" role="alert">
            <?php echo $_SESSION['message']; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php unset($_SESSION['message'], $_SESSION['message_type']); ?>
    <?php endif; ?>

    <div class="row mb-4">
        <div class="col-xl-4 col-md-6 mb-4">
            <div class="card h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-1">Ingrédients totaux</h6>
                            <h3 class="mb-0"><?php echo $stats_stocks['total']; ?></h3>
                        </div>
                        <div class="dashboard-stat bg-primary rounded-circle p-3">
                            <i class="fas fa-boxes"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-xl-4 col-md-6 mb-4">
            <div class="card h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-1">En alerte</h6>
                            <h3 class="mb-0"><?php echo $stats_stocks['en_alerte']; ?></h3>
                        </div>
                        <div class="dashboard-stat bg-warning rounded-circle p-3">
                            <i class="fas fa-exclamation-triangle"></i>
                        </div>
                    </div>
                    <div class="mt-2">
                        <a href="?filtre=alerte" class="text-warning">Voir les ingrédients en alerte</a>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-xl-4 col-md-6 mb-4">
            <div class="card h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-1">Épuisés</h6>
                            <h3 class="mb-0"><?php echo $stats_stocks['epuise']; ?></h3>
                        </div>
                        <div class="dashboard-stat bg-danger rounded-circle p-3">
                            <i class="fas fa-times-circle"></i>
                        </div>
                    </div>
                    <div class="mt-2">
                        <a href="?filtre=epuise" class="text-danger">Voir les ingrédients épuisés</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <h5 class="mb-0">Liste des ingrédients</h5>
                </div>
                <div class="col-md-6">
                    <form action="" method="GET" class="d-flex">
                        <input type="hidden" name="filtre" value="<?php echo $filtre; ?>">
                        <input type="text" name="recherche" class="form-control me-2" placeholder="Rechercher..." value="<?php echo htmlspecialchars($recherche); ?>">
                        <button type="submit" class="btn btn-outline-primary"><i class="fas fa-search"></i></button>
                    </form>
                </div>
            </div>
        </div>
        <div class="card-body">
            <div class="mb-3">
                <div class="btn-group" role="group">
                    <a href="stocks.php" class="btn btn-outline-secondary <?php echo $filtre == 'tous' ? 'active' : ''; ?>">Tous</a>
                    <a href="stocks.php?filtre=alerte" class="btn btn-outline-warning <?php echo $filtre == 'alerte' ? 'active' : ''; ?>">En alerte</a>
                    <a href="stocks.php?filtre=epuise" class="btn btn-outline-danger <?php echo $filtre == 'epuise' ? 'active' : ''; ?>">Épuisés</a>
                </div>
            </div>
            
            <?php if (count($ingredients) > 0): ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead>
                            <tr>
                                <th>Ingrédient</th>
                                <th>Quantité</th>
                                <th>Unité</th>
                                <th>Seuil d'alerte</th>
                                <th>Statut</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($ingredients as $ingredient): ?>
                                <tr>
                                    <td><?php echo $ingredient['ingredient']; ?></td>
                                    <td><?php echo $ingredient['quantite']; ?></td>
                                    <td><?php echo $ingredient['unite']; ?></td>
                                    <td><?php echo $ingredient['seuil_alerte']; ?></td>
                                    <td>
                                        <?php if ($ingredient['quantite'] <= 0): ?>
                                            <span class="badge bg-danger">Épuisé</span>
                                        <?php elseif ($ingredient['quantite'] <= $ingredient['seuil_alerte']): ?>
                                            <span class="badge bg-warning">En alerte</span>
                                        <?php else: ?>
                                            <span class="badge bg-success">Normal</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <button type="button" class="btn btn-sm btn-primary me-1" 
                                                data-bs-toggle="modal" data-bs-target="#approvisionnementModal"
                                                data-id="<?php echo $ingredient['id']; ?>"
                                                data-ingredient="<?php echo $ingredient['ingredient']; ?>"
                                                data-unite="<?php echo $ingredient['unite']; ?>">
                                            <i class="fas fa-plus"></i>
                                        </button>
                                        <button type="button" class="btn btn-sm btn-info me-1" 
                                                data-bs-toggle="modal" data-bs-target="#modifierModal"
                                                data-id="<?php echo $ingredient['id']; ?>"
                                                data-ingredient="<?php echo $ingredient['ingredient']; ?>"
                                                data-quantite="<?php echo $ingredient['quantite']; ?>"
                                                data-unite="<?php echo $ingredient['unite']; ?>"
                                                data-seuil="<?php echo $ingredient['seuil_alerte']; ?>">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button type="button" class="btn btn-sm btn-danger" 
                                                data-bs-toggle="modal" data-bs-target="#supprimerModal"
                                                data-id="<?php echo $ingredient['id']; ?>"
                                                data-ingredient="<?php echo $ingredient['ingredient']; ?>">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <?php if ($total_pages > 1): ?>
                    <nav aria-label="Navigation des ingrédients" class="mt-4">
                        <ul class="pagination justify-content-center">
                            <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $page - 1; ?>&filtre=<?php echo $filtre; ?>&recherche=<?php echo $recherche; ?>">Précédent</a>
                            </li>
                            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                <li class="page-item <?php echo $page == $i ? 'active' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $i; ?>&filtre=<?php echo $filtre; ?>&recherche=<?php echo $recherche; ?>"><?php echo $i; ?></a>
                                </li>
                            <?php endfor; ?>
                            <li class="page-item <?php echo $page >= $total_pages ? 'disabled' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $page + 1; ?>&filtre=<?php echo $filtre; ?>&recherche=<?php echo $recherche; ?>">Suivant</a>
                            </li>
                        </ul>
                    </nav>
                <?php endif; ?>
            <?php else: ?>
                <div class="text-center py-4">
                    <i class="fas fa-box-open fa-3x mb-3 text-muted"></i>
                    <p>Aucun ingrédient trouvé avec les filtres actuels.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Modal pour ajouter un ingrédient -->
<div class="modal fade" id="ajoutModal" tabindex="-1" aria-labelledby="ajoutModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="ajoutModalLabel">Ajouter un ingrédient</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="" method="POST">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="ingredient" class="form-label">Nom de l'ingrédient</label>
                        <input type="text" class="form-control" id="ingredient" name="ingredient" required>
                    </div>
                    <div class="mb-3">
                        <label for="quantite" class="form-label">Quantité initiale</label>
                        <input type="number" class="form-control" id="quantite" name="quantite" step="0.01" min="0" value="0" required>
                    </div>
                    <div class="mb-3">
                        <label for="unite" class="form-label">Unité</label>
                        <select class="form-select" id="unite" name="unite" required>
                            <option value="kg">Kilogrammes (kg)</option>
                            <option value="g">Grammes (g)</option>
                            <option value="l">Litres (l)</option>
                            <option value="ml">Millilitres (ml)</option>
                            <option value="unité">Unité(s)</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="seuil_alerte" class="form-label">Seuil d'alerte</label>
                        <input type="number" class="form-control" id="seuil_alerte" name="seuil_alerte" step="0.01" min="0" value="5" required>
                        <div class="form-text">Une alerte sera affichée lorsque la quantité sera inférieure ou égale à cette valeur.</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" name="ajouter" class="btn btn-primary">Ajouter</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal pour modifier un ingrédient -->
<div class="modal fade" id="modifierModal" tabindex="-1" aria-labelledby="modifierModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modifierModalLabel">Modifier un ingrédient</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="" method="POST">
                <input type="hidden" name="id" id="edit_id">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="edit_ingredient" class="form-label">Nom de l'ingrédient</label>
                        <input type="text" class="form-control" id="edit_ingredient" name="ingredient" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_quantite" class="form-label">Quantité en stock</label>
                        <input type="number" class="form-control" id="edit_quantite" name="quantite" step="0.01" min="0" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_unite" class="form-label">Unité</label>
                        <select class="form-select" id="edit_unite" name="unite" required>
                            <option value="kg">Kilogrammes (kg)</option>
                            <option value="g">Grammes (g)</option>
                            <option value="l">Litres (l)</option>
                            <option value="ml">Millilitres (ml)</option>
                            <option value="unité">Unité(s)</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="edit_seuil_alerte" class="form-label">Seuil d'alerte</label>
                        <input type="number" class="form-control" id="edit_seuil_alerte" name="seuil_alerte" step="0.01" min="0" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" name="modifier" class="btn btn-primary">Enregistrer</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal pour supprimer un ingrédient -->
<div class="modal fade" id="supprimerModal" tabindex="-1" aria-labelledby="supprimerModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="supprimerModalLabel">Confirmation de suppression</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Êtes-vous sûr de vouloir supprimer l'ingrédient <strong id="delete_ingredient"></strong> ?</p>
                <p class="text-danger">Cette action est irréversible.</p>
            </div>
            <div class="modal-footer">
                <form action="" method="POST">
                    <input type="hidden" name="id" id="delete_id">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" name="supprimer" class="btn btn-danger">Supprimer</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Modal pour approvisionner un ingrédient -->
<div class="modal fade" id="approvisionnementModal" tabindex="-1" aria-labelledby="approvisionnementModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="approvisionnementModalLabel">Approvisionner un stock</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="" method="POST">
                <input type="hidden" name="id" id="appro_id">
                <div class="modal-body">
                    <p>Ajouter du stock pour : <strong id="appro_ingredient"></strong></p>
                    <div class="mb-3">
                        <label for="quantite_ajoutee" class="form-label">Quantité à ajouter</label>
                        <div class="input-group">
                            <input type="number" class="form-control" id="quantite_ajoutee" name="quantite_ajoutee" step="0.01" min="0.01" value="1" required>
                            <span class="input-group-text" id="appro_unite"></span>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" name="approvisionner" class="btn btn-primary">Approvisionner</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Remplir les données du modal de modification
document.querySelector('#modifierModal').addEventListener('show.bs.modal', function(event) {
    const button = event.relatedTarget;
    const id = button.getAttribute('data-id');
    const ingredient = button.getAttribute('data-ingredient');
    const quantite = button.getAttribute('data-quantite');
    const unite = button.getAttribute('data-unite');
    const seuil = button.getAttribute('data-seuil');
    
    document.querySelector('#edit_id').value = id;
    document.querySelector('#edit_ingredient').value = ingredient;
    document.querySelector('#edit_quantite').value = quantite;
    document.querySelector('#edit_unite').value = unite;
    document.querySelector('#edit_seuil_alerte').value = seuil;
});

// Remplir les données du modal de suppression
document.querySelector('#supprimerModal').addEventListener('show.bs.modal', function(event) {
    const button = event.relatedTarget;
    const id = button.getAttribute('data-id');
    const ingredient = button.getAttribute('data-ingredient');
    
    document.querySelector('#delete_id').value = id;
    document.querySelector('#delete_ingredient').textContent = ingredient;
});

// Remplir les données du modal d'approvisionnement
document.querySelector('#approvisionnementModal').addEventListener('show.bs.modal', function(event) {
    const button = event.relatedTarget;
    const id = button.getAttribute('data-id');
    const ingredient = button.getAttribute('data-ingredient');
    const unite = button.getAttribute('data-unite');
    
    document.querySelector('#appro_id').value = id;
    document.querySelector('#appro_ingredient').textContent = ingredient;
    document.querySelector('#appro_unite').textContent = unite;
});
</script>

<?php include_once '../includes/footer.php'; ?>