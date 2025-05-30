
<?php
session_start();
include_once '../config/database.php';
include_once '../includes/functions.php';

// Vérifier si l'utilisateur est connecté et est un client
if (!check_role('client')) {
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$database = new Database();
$db = $database->getConnection();

$message = "";
$success = false;

// Récupérer les informations du client
$query = "SELECT * FROM users WHERE id = :id";
$stmt = $db->prepare($query);
$stmt->bindParam(":id", $user_id);
$stmt->execute();
$client = $stmt->fetch(PDO::FETCH_ASSOC);

// Traitement du formulaire de mise à jour du profil
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nom = clean_input($_POST['nom']);
    $prenom = clean_input($_POST['prenom']);
    $email = clean_input($_POST['email']);
    $telephone = clean_input($_POST['telephone']);
    $adresse = clean_input($_POST['adresse']);
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Validation des données
    if (empty($nom) || empty($prenom) || empty($email)) {
        $message = "Les champs nom, prénom et email sont obligatoires.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "Format d'email invalide.";
    } else {
        // Vérifier si l'email existe déjà (sauf pour l'utilisateur actuel)
        $query = "SELECT id FROM users WHERE email = :email AND id != :id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(":email", $email);
        $stmt->bindParam(":id", $user_id);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            $message = "Cette adresse email est déjà utilisée par un autre compte.";
        } else {
            // Si un nouveau mot de passe est fourni
            if (!empty($current_password) && !empty($new_password)) {
                // Vérifier le mot de passe actuel
                if (!password_verify($current_password, $client['password'])) {
                    $message = "Le mot de passe actuel est incorrect.";
                } elseif ($new_password != $confirm_password) {
                    $message = "Les nouveaux mots de passe ne correspondent pas.";
                } elseif (strlen($new_password) < 6) {
                    $message = "Le nouveau mot de passe doit comporter au moins 6 caractères.";
                } else {
                    // Hachage du nouveau mot de passe
                    $password_hash = password_hash($new_password, PASSWORD_DEFAULT);
                    
                    // Mise à jour avec le nouveau mot de passe
                    $query = "UPDATE users SET nom = :nom, prenom = :prenom, email = :email, password = :password, 
                              telephone = :telephone, adresse = :adresse WHERE id = :id";
                    $stmt = $db->prepare($query);
                    $stmt->bindParam(":password", $password_hash);
                }
            } else {
                // Mise à jour sans changer le mot de passe
                $query = "UPDATE users SET nom = :nom, prenom = :prenom, email = :email, 
                          telephone = :telephone, adresse = :adresse WHERE id = :id";
                $stmt = $db->prepare($query);
            }
            
            // Exécution de la mise à jour
            $stmt->bindParam(":nom", $nom);
            $stmt->bindParam(":prenom", $prenom);
            $stmt->bindParam(":email", $email);
            $stmt->bindParam(":telephone", $telephone);
            $stmt->bindParam(":adresse", $adresse);
            $stmt->bindParam(":id", $user_id);
            
            if ($stmt->execute()) {
                $message = "Votre profil a été mis à jour avec succès.";
                $success = true;
                
                // Mettre à jour les informations de session
                $_SESSION['nom'] = $nom;
                $_SESSION['prenom'] = $prenom;
                
                // Récupérer les informations mises à jour
                $query = "SELECT * FROM users WHERE id = :id";
                $stmt = $db->prepare($query);
                $stmt->bindParam(":id", $user_id);
                $stmt->execute();
                $client = $stmt->fetch(PDO::FETCH_ASSOC);
            } else {
                $message = "Une erreur est survenue lors de la mise à jour de votre profil.";
            }
        }
    }
}

// Afficher la page de profil
include_once '../includes/header.php';
?>

<div class="row">
    <div class="col-lg-8 mx-auto">
        <h1 class="mb-4">Mon profil</h1>
        
        <?php if (!empty($message)): ?>
            <div class="alert alert-<?php echo $success ? 'success' : 'danger'; ?> alert-dismissible fade show mb-4">
                <?php echo $message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">Informations personnelles</h5>
            </div>
            <div class="card-body">
                <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="nom" class="form-label">Nom</label>
                            <input type="text" class="form-control" id="nom" name="nom" value="<?php echo $client['nom']; ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label for="prenom" class="form-label">Prénom</label>
                            <input type="text" class="form-control" id="prenom" name="prenom" value="<?php echo $client['prenom']; ?>" required>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="email" class="form-label">Adresse email</label>
                        <input type="email" class="form-control" id="email" name="email" value="<?php echo $client['email']; ?>" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="telephone" class="form-label">Téléphone</label>
                        <input type="tel" class="form-control" id="telephone" name="telephone" value="<?php echo $client['telephone']; ?>">
                    </div>
                    
                    <div class="mb-3">
                        <label for="adresse" class="form-label">Adresse de livraison</label>
                        <textarea class="form-control" id="adresse" name="adresse" rows="3"><?php echo $client['adresse']; ?></textarea>
                    </div>
                    
                    <h5 class="mb-3 mt-4">Changer de mot de passe</h5>
                    <div class="mb-3">
                        <label for="current_password" class="form-label">Mot de passe actuel</label>
                        <input type="password" class="form-control" id="current_password" name="current_password">
                        <div class="form-text">Laissez vide si vous ne souhaitez pas changer de mot de passe.</div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="new_password" class="form-label">Nouveau mot de passe</label>
                            <input type="password" class="form-control" id="new_password" name="new_password">
                        </div>
                        <div class="col-md-6">
                            <label for="confirm_password" class="form-label">Confirmer le nouveau mot de passe</label>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password">
                        </div>
                    </div>
                    
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>Enregistrer les modifications
                        </button>
                    </div>
                </form>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Statistiques</h5>
            </div>
            <div class="card-body">
                <?php
                // Nombre total de commandes
                $query = "SELECT COUNT(*) as total FROM commandes WHERE client_id = :client_id";
                $stmt = $db->prepare($query);
                $stmt->bindParam(":client_id", $user_id);
                $stmt->execute();
                $total_commandes = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
                
                // Montant total dépensé
                $query = "SELECT SUM(montant_total) as total FROM commandes WHERE client_id = :client_id AND statut != 'annulee'";
                $stmt = $db->prepare($query);
                $stmt->bindParam(":client_id", $user_id);
                $stmt->execute();
                $total_depense = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?: 0;
                
                // Date de la première commande
                $query = "SELECT MIN(date_commande) as premiere FROM commandes WHERE client_id = :client_id";
                $stmt = $db->prepare($query);
                $stmt->bindParam(":client_id", $user_id);
                $stmt->execute();
                $premiere_commande = $stmt->fetch(PDO::FETCH_ASSOC)['premiere'];
                ?>
                
                <div class="row text-center">
                    <div class="col-md-4">
                        <div class="mb-3">
                            <div class="display-4 fw-bold text-primary"><?php echo $total_commandes; ?></div>
                            <div class="text-muted">Commandes passées</div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="mb-3">
                            <div class="display-4 fw-bold text-primary"><?php echo $total_depense ." DH"; ?></div>
                            <div class="text-muted">Total dépensé</div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="mb-3">
                            <div class="display-4 fw-bold text-primary">
                                <?php echo $premiere_commande ? date('d/m/Y', strtotime($premiere_commande)) : '-'; ?>
                            </div>
                            <div class="text-muted">Client depuis</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include_once '../includes/footer.php'; ?>
