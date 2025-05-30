<?php
session_start();
include_once 'config/database.php';
include_once 'includes/functions.php';

// Si l'utilisateur est déjà connecté, rediriger vers l'index
if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$error_message = "";
$success_message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nom = clean_input($_POST['nom']);
    $prenom = clean_input($_POST['prenom']);
    $email = clean_input($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $telephone = clean_input($_POST['telephone']);
    $adresse = clean_input($_POST['adresse']);
    
    if (empty($nom) || empty($prenom) || empty($email) || empty($password) || empty($confirm_password)) {
        $error_message = "Les champs marqués d'un astérisque sont obligatoires.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = "Format d'email invalide.";
    } elseif ($password != $confirm_password) {
        $error_message = "Les mots de passe ne correspondent pas.";
    } elseif (strlen($password) < 6) {
        $error_message = "Le mot de passe doit comporter au moins 6 caractères.";
    } elseif (!isset($_POST['terms'])) {
        $error_message = "Vous devez accepter les conditions d'utilisation.";
    } else {
        $database = new Database();
        $db = $database->getConnection();
        
        $query = "SELECT id FROM users WHERE email = :email";
        $stmt = $db->prepare($query);
        $stmt->bindParam(":email", $email);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            $error_message = "Cette adresse email est déjà utilisée.";
        } else {
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            $query = "INSERT INTO users (nom, prenom, email, password, telephone, adresse, role) VALUES (:nom, :prenom, :email, :password, :telephone, :adresse, 'client')";
            $stmt = $db->prepare($query);
            $stmt->bindParam(":nom", $nom);
            $stmt->bindParam(":prenom", $prenom);
            $stmt->bindParam(":email", $email);
            $stmt->bindParam(":password", $password_hash);
            $stmt->bindParam(":telephone", $telephone);
            $stmt->bindParam(":adresse", $adresse);
            
            if ($stmt->execute()) {
                $success_message = "Inscription réussie ! Vous pouvez maintenant vous connecter.";
            } else {
                $error_message = "Une erreur est survenue lors de l'inscription. Veuillez réessayer.";
            }
        }
    }
}

include_once 'includes/header.php';
?>

<style>
  body {
    background-color: #ffe6e6; /* rouge clair pastel */
  }
  .card {
    border: none;
    border-radius: 10px;
    box-shadow: 0 4px 12px rgba(255, 100, 100, 0.3);
  }
  .card-title {
    color: #b30000; /* rouge foncé */
    font-weight: 700;
  }
  .btn-primary {
    background-color: #ff4d4d;
    border-color: #ff4d4d;
  }
  .btn-primary:hover {
    background-color: #cc0000;
    border-color: #cc0000;
  }
  .form-label {
    color: #b30000;
    font-weight: 600;
  }
  .alert-danger {
    background-color: #ff9999;
    border-color: #ff6666;
    color: #660000;
  }
  .alert-success {
    background-color: #ccffcc;
    border-color: #66cc66;
    color: #336633;
  }
  a {
    color: #b30000;
    font-weight: 600;
  }
  a:hover {
    color: #800000;
    text-decoration: none;
  }
</style>

<div class="row justify-content-center" style="min-height: 80vh; align-items: center;">
    <div class="col-md-8">
        <div class="card shadow-sm p-4">
            <h2 class="card-title text-center mb-4">Créer un compte</h2>
            
            <?php if (!empty($error_message)): ?>
                <div class="alert alert-danger" role="alert">
                    <?= htmlspecialchars($error_message) ?>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($success_message)): ?>
                <div class="alert alert-success" role="alert">
                    <?= htmlspecialchars($success_message) ?>
                    <div class="mt-3">
                        <a href="login.php" class="btn btn-primary btn-sm">Se connecter</a>
                    </div>
                </div>
            <?php endif; ?>
            
            <form method="post" action="<?= htmlspecialchars($_SERVER["PHP_SELF"]) ?>">
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="nom" class="form-label">Nom *</label>
                            <input type="text" class="form-control" id="nom" name="nom" required>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="prenom" class="form-label">Prénom *</label>
                            <input type="text" class="form-control" id="prenom" name="prenom" required>
                        </div>
                    </div>
                </div>
                
                <div class="mb-3">
                    <label for="email" class="form-label">Adresse email *</label>
                    <input type="email" class="form-control" id="email" name="email" required>
                </div>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="password" class="form-label">Mot de passe *</label>
                            <input type="password" class="form-control" id="password" name="password" required>
                            <div class="form-text">Le mot de passe doit comporter au moins 6 caractères.</div>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="confirm_password" class="form-label">Confirmer le mot de passe *</label>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                        </div>
                    </div>
                </div>
                
                <div class="mb-3">
                    <label for="telephone" class="form-label">Téléphone</label>
                    <input type="tel" class="form-control" id="telephone" name="telephone">
                </div>
                
                <div class="mb-3">
                    <label for="adresse" class="form-label">Adresse</label>
                    <textarea class="form-control" id="adresse" name="adresse" rows="2"></textarea>
                </div>
                
                <div class="mb-3 form-check">
                    <input type="checkbox" class="form-check-input" id="terms" name="terms" required>
                    <label class="form-check-label" for="terms">J'accepte les <a href="#">conditions d'utilisation</a> et la <a href="#">politique de confidentialité</a> *</label>
                </div>
                
                <button type="submit" class="btn btn-primary w-100">S'inscrire</button>
            </form>
            
            <div class="text-center mt-3">
                <p>Déjà inscrit ? <a href="login.php">Se connecter</a></p>
            </div>
        </div>
    </div>
</div>

<?php include_once 'includes/footer.php'; ?>
