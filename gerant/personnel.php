
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
    
    // Ajout d'un nouvel employé
    if (isset($_POST['ajouter'])) {
        $nom = clean_input($_POST['nom']);
        $prenom = clean_input($_POST['prenom']);
        $email = clean_input($_POST['email']);
        $telephone = clean_input($_POST['telephone']);
        $role = clean_input($_POST['role']);
        $password = password_hash($_POST['mot_de_passe'], PASSWORD_DEFAULT);
        
        // Vérifier que l'email n'est pas déjà utilisé
        $query = "SELECT id FROM users WHERE email = :email";
        $stmt = $db->prepare($query);
        $stmt->bindParam(":email", $email);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            $message = "Cet email est déjà utilisé par un autre utilisateur.";
        } else {
            // Insérer le nouvel employé
            $query = "INSERT INTO users (nom, prenom, email, telephone, password, role) 
                      VALUES (:nom, :prenom, :email, :telephone, :password, :role)";
            $stmt = $db->prepare($query);
            $stmt->bindParam(":nom", $nom);
            $stmt->bindParam(":prenom", $prenom);
            $stmt->bindParam(":email", $email);
            $stmt->bindParam(":telephone", $telephone);
            $stmt->bindParam(":password", $password);
            $stmt->bindParam(":role", $role);
            
            if ($stmt->execute()) {
                $message = "Le nouvel employé a été ajouté avec succès.";
                $type_message = "success";
            } else {
                $message = "Une erreur est survenue lors de l'ajout de l'employé.";
            }
        }
    }
    
    // Modification d'un employé
    else if (isset($_POST['modifier']) && isset($_POST['user_id'])) {
        $user_id = $_POST['user_id'];
        $nom = clean_input($_POST['nom']);
        $prenom = clean_input($_POST['prenom']);
        $email = clean_input($_POST['email']);
        $telephone = clean_input($_POST['telephone']);
        $role = clean_input($_POST['role']);
        
        // Vérifier que l'email n'est pas déjà utilisé par un autre utilisateur
        $query = "SELECT id FROM users WHERE email = :email AND id != :user_id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(":email", $email);
        $stmt->bindParam(":user_id", $user_id);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            $message = "Cet email est déjà utilisé par un autre utilisateur.";
        } else {
            // Mise à jour des informations
            $query = "UPDATE users SET nom = :nom, prenom = :prenom, email = :email, 
                      telephone = :telephone, role = :role WHERE id = :user_id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(":nom", $nom);
            $stmt->bindParam(":prenom", $prenom);
            $stmt->bindParam(":email", $email);
            $stmt->bindParam(":telephone", $telephone);
            $stmt->bindParam(":role", $role);
            $stmt->bindParam(":user_id", $user_id);
            
            if ($stmt->execute()) {
                // Si le mot de passe doit être modifié
                if (!empty($_POST['mot_de_passe'])) {
                    $password = password_hash($_POST['mot_de_passe'], PASSWORD_DEFAULT);
                    $query = "UPDATE users SET password = :password WHERE id = :user_id";
                    $stmt = $db->prepare($query);
                    $stmt->bindParam(":password", $password);
                    $stmt->bindParam(":user_id", $user_id);
                    $stmt->execute();
                }
                
                $message = "Les informations de l'employé ont été mises à jour avec succès.";
                $type_message = "success";
            } else {
                $message = "Une erreur est survenue lors de la mise à jour des informations.";
            }
        }
    }
    
    // Suppression d'un employé
    else if (isset($_POST['supprimer']) && isset($_POST['user_id'])) {
        $user_id = $_POST['user_id'];
        
        // Vérifier que ce n'est pas le gérant connecté
        if ($user_id == $_SESSION['user_id']) {
            $message = "Vous ne pouvez pas supprimer votre propre compte.";
        } else {
            // Vérifier les dépendances avant de supprimer
            $query = "SELECT * FROM commandes WHERE client_id = :user_id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(":user_id", $user_id);
            $stmt->execute();
            
            if ($stmt->rowCount() > 0) {
                $message = "Impossible de supprimer cet utilisateur car il est lié à des commandes.";
            } else {
                $query = "DELETE FROM users WHERE id = :user_id";
                $stmt = $db->prepare($query);
                $stmt->bindParam(":user_id", $user_id);
                
                if ($stmt->execute()) {
                    $message = "L'employé a été supprimé avec succès.";
                    $type_message = "success";
                } else {
                    $message = "Une erreur est survenue lors de la suppression de l'employé.";
                }
            }
        }
    }
    
    $_SESSION['message'] = $message;
    $_SESSION['message_type'] = $type_message;
    
    header("Location: personnel.php");
    exit();
}

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limite = 10;
$debut = ($page - 1) * $limite;

// Filtre par rôle
$filtre_role = isset($_GET['role']) ? $_GET['role'] : '';
$condition = $filtre_role ? "WHERE role = :role" : "WHERE role != 'client'";

// Recherche
$recherche = isset($_GET['recherche']) ? $_GET['recherche'] : '';
if ($recherche) {
    $condition = "WHERE (nom LIKE :recherche OR prenom LIKE :recherche OR email LIKE :recherche) AND role != 'client'";
    if ($filtre_role) {
        $condition = "WHERE (nom LIKE :recherche OR prenom LIKE :recherche OR email LIKE :recherche) AND role = :role";
    }
}

// Compter le nombre total d'employés
$query = "SELECT COUNT(*) as total FROM users $condition";
$stmt = $db->prepare($query);

if ($filtre_role) {
    $stmt->bindParam(":role", $filtre_role);
}

if ($recherche) {
    $recherche_param = "%$recherche%";
    $stmt->bindParam(":recherche", $recherche_param);
}

$stmt->execute();
$total_employes = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
$total_pages = ceil($total_employes / $limite);

// Récupérer les employés avec pagination
$query = "SELECT * FROM users $condition ORDER BY nom, prenom LIMIT :debut, :limite";
$stmt = $db->prepare($query);

if ($filtre_role) {
    $stmt->bindParam(":role", $filtre_role);
}

if ($recherche) {
    $recherche_param = "%$recherche%";
    $stmt->bindParam(":recherche", $recherche_param);
}

$stmt->bindParam(":debut", $debut, PDO::PARAM_INT);
$stmt->bindParam(":limite", $limite, PDO::PARAM_INT);
$stmt->execute();
$employes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Afficher la page
include_once '../includes/header.php';
?>

<div class="container-fluid px-4 py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="mb-0">Gestion du personnel</h2>
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#ajoutModal">
            <i class="fas fa-plus me-1"></i> Ajouter un employé
        </button>
    </div>

    <?php if (isset($_SESSION['message'])): ?>
        <div class="alert alert-<?php echo $_SESSION['message_type'] ?? 'info'; ?> alert-dismissible fade show" role="alert">
            <?php echo $_SESSION['message']; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php unset($_SESSION['message'], $_SESSION['message_type']); ?>
    <?php endif; ?>

    <div class="card mb-4">
        <div class="card-header">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <h5 class="mb-0">Liste des employés</h5>
                </div>
                <div class="col-md-6">
                    <form action="" method="GET" class="d-flex">
                        <input type="text" name="recherche" class="form-control me-2" placeholder="Rechercher..." value="<?php echo htmlspecialchars($recherche); ?>">
                        <button type="submit" class="btn btn-outline-primary"><i class="fas fa-search"></i></button>
                    </form>
                </div>
            </div>
        </div>
        <div class="card-body">
            <div class="mb-3">
                <div class="btn-group" role="group">
                    <a href="personnel.php" class="btn btn-outline-secondary <?php echo $filtre_role == '' ? 'active' : ''; ?>">Tous</a>
                    <a href="personnel.php?role=restaurateur" class="btn btn-outline-secondary <?php echo $filtre_role == 'restaurateur' ? 'active' : ''; ?>">Restaurateurs</a>
                    <a href="personnel.php?role=livreur" class="btn btn-outline-secondary <?php echo $filtre_role == 'livreur' ? 'active' : ''; ?>">Livreurs</a>
                    <a href="personnel.php?role=gerant" class="btn btn-outline-secondary <?php echo $filtre_role == 'gerant' ? 'active' : ''; ?>">Gérants</a>
                </div>
            </div>
            
            <?php if (count($employes) > 0): ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead>
                            <tr>
                                <th>Nom</th>
                                <th>Email</th>
                                <th>Téléphone</th>
                                <th>Rôle</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($employes as $employe): ?>
                                <tr>
                                    <td><?php echo $employe['nom'] . ' ' . $employe['prenom']; ?></td>
                                    <td><?php echo $employe['email']; ?></td>
                                    <td><?php echo $employe['telephone']; ?></td>
                                    <td>
                                        <?php 
                                            switch ($employe['role']) {
                                                case 'restaurateur':
                                                    echo '<span class="badge bg-info">Restaurateur</span>';
                                                    break;
                                                case 'livreur':
                                                    echo '<span class="badge bg-warning">Livreur</span>';
                                                    break;
                                                case 'gerant':
                                                    echo '<span class="badge bg-primary">Gérant</span>';
                                                    break;
                                                default:
                                                    echo $employe['role'];
                                                    break;
                                            }
                                        ?>
                                    </td>
                                    <td>
                                        <button type="button" class="btn btn-sm btn-info me-1" 
                                                data-bs-toggle="modal" data-bs-target="#modifierModal"
                                                data-user-id="<?php echo $employe['id']; ?>"
                                                data-nom="<?php echo $employe['nom']; ?>"
                                                data-prenom="<?php echo $employe['prenom']; ?>"
                                                data-email="<?php echo $employe['email']; ?>"
                                                data-telephone="<?php echo $employe['telephone']; ?>"
                                                data-role="<?php echo $employe['role']; ?>">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <?php if ($employe['id'] != $_SESSION['user_id']): ?>
                                            <button type="button" class="btn btn-sm btn-danger" 
                                                    data-bs-toggle="modal" data-bs-target="#supprimerModal"
                                                    data-user-id="<?php echo $employe['id']; ?>"
                                                    data-nom="<?php echo $employe['nom'] . ' ' . $employe['prenom']; ?>">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <?php if ($total_pages > 1): ?>
                    <nav aria-label="Navigation des employés">
                        <ul class="pagination justify-content-center">
                            <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $page - 1; ?>&role=<?php echo $filtre_role; ?>&recherche=<?php echo $recherche; ?>">Précédent</a>
                            </li>
                            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                <li class="page-item <?php echo $page == $i ? 'active' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $i; ?>&role=<?php echo $filtre_role; ?>&recherche=<?php echo $recherche; ?>"><?php echo $i; ?></a>
                                </li>
                            <?php endfor; ?>
                            <li class="page-item <?php echo $page >= $total_pages ? 'disabled' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $page + 1; ?>&role=<?php echo $filtre_role; ?>&recherche=<?php echo $recherche; ?>">Suivant</a>
                            </li>
                        </ul>
                    </nav>
                <?php endif; ?>
            <?php else: ?>
                <div class="text-center py-4">
                    <i class="fas fa-users fa-3x mb-3 text-muted"></i>
                    <p>Aucun employé trouvé<?php echo $filtre_role ? ' avec ce rôle.' : '.'; ?></p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Modal pour ajouter un employé -->
<div class="modal fade" id="ajoutModal" tabindex="-1" aria-labelledby="ajoutModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="ajoutModalLabel">Ajouter un employé</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="" method="POST">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="nom" class="form-label">Nom</label>
                        <input type="text" class="form-control" id="nom" name="nom" required>
                    </div>
                    <div class="mb-3">
                        <label for="prenom" class="form-label">Prénom</label>
                        <input type="text" class="form-control" id="prenom" name="prenom" required>
                    </div>
                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="email" name="email" required>
                    </div>
                    <div class="mb-3">
                        <label for="telephone" class="form-label">Téléphone</label>
                        <input type="text" class="form-control" id="telephone" name="telephone" required>
                    </div>
                    <div class="mb-3">
                        <label for="role" class="form-label">Rôle</label>
                        <select class="form-select" id="role" name="role" required>
                            <option value="restaurateur">Restaurateur</option>
                            <option value="livreur">Livreur</option>
                            <option value="gerant">Gérant</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="mot_de_passe" class="form-label">Mot de passe</label>
                        <input type="password" class="form-control" id="mot_de_passe" name="mot_de_passe" required>
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

<!-- Modal pour modifier un employé -->
<div class="modal fade" id="modifierModal" tabindex="-1" aria-labelledby="modifierModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modifierModalLabel">Modifier un employé</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="" method="POST">
                <input type="hidden" name="user_id" id="edit_user_id">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="edit_nom" class="form-label">Nom</label>
                        <input type="text" class="form-control" id="edit_nom" name="nom" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_prenom" class="form-label">Prénom</label>
                        <input type="text" class="form-control" id="edit_prenom" name="prenom" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="edit_email" name="email" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_telephone" class="form-label">Téléphone</label>
                        <input type="text" class="form-control" id="edit_telephone" name="telephone" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_role" class="form-label">Rôle</label>
                        <select class="form-select" id="edit_role" name="role" required>
                            <option value="restaurateur">Restaurateur</option>
                            <option value="livreur">Livreur</option>
                            <option value="gerant">Gérant</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="edit_mot_de_passe" class="form-label">Nouveau mot de passe (laisser vide pour ne pas changer)</label>
                        <input type="password" class="form-control" id="edit_mot_de_passe" name="mot_de_passe">
                        <div class="form-text">Laissez ce champ vide si vous ne souhaitez pas changer le mot de passe.</div>
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

<!-- Modal pour supprimer un employé -->
<div class="modal fade" id="supprimerModal" tabindex="-1" aria-labelledby="supprimerModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="supprimerModalLabel">Confirmation de suppression</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Êtes-vous sûr de vouloir supprimer l'employé <strong id="delete_nom_employe"></strong> ?</p>
                <p class="text-danger">Cette action est irréversible.</p>
            </div>
            <div class="modal-footer">
                <form action="" method="POST">
                    <input type="hidden" name="user_id" id="delete_user_id">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" name="supprimer" class="btn btn-danger">Supprimer</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
// Remplir les champs du modal de modification
document.querySelector('#modifierModal').addEventListener('show.bs.modal', function(event) {
    const button = event.relatedTarget;
    const userId = button.getAttribute('data-user-id');
    const nom = button.getAttribute('data-nom');
    const prenom = button.getAttribute('data-prenom');
    const email = button.getAttribute('data-email');
    const telephone = button.getAttribute('data-telephone');
    const role = button.getAttribute('data-role');
    
    document.querySelector('#edit_user_id').value = userId;
    document.querySelector('#edit_nom').value = nom;
    document.querySelector('#edit_prenom').value = prenom;
    document.querySelector('#edit_email').value = email;
    document.querySelector('#edit_telephone').value = telephone;
    document.querySelector('#edit_role').value = role;
    document.querySelector('#edit_mot_de_passe').value = '';
});

// Remplir les champs du modal de suppression
document.querySelector('#supprimerModal').addEventListener('show.bs.modal', function(event) {
    const button = event.relatedTarget;
    const userId = button.getAttribute('data-user-id');
    const nom = button.getAttribute('data-nom');
    
    document.querySelector('#delete_user_id').value = userId;
    document.querySelector('#delete_nom_employe').textContent = nom;
});
</script>

<?php include_once '../includes/footer.php'; ?>