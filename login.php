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

// Traitement du formulaire de connexion
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = clean_input($_POST['email']);
    $password = $_POST['password'];
    
    if (empty($email) || empty($password)) {
        $error_message = "Tous les champs sont obligatoires.";
    } else {
        $database = new Database();
        $db = $database->getConnection();
        
        $query = "SELECT * FROM users WHERE email = :email LIMIT 1";
        $stmt = $db->prepare($query);
        $stmt->bindParam(":email", $email);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            if (password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['nom'] = $user['nom'];
                $_SESSION['prenom'] = $user['prenom'];
                $_SESSION['role'] = $user['role'];
                
                header("Location: index.php");
                exit();
            } else {
                $error_message = "Mot de passe incorrect.";
            }
        } else {
            $error_message = "Aucun compte n'est associé à cette adresse email.";
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
    <div class="col-md-6">
        <div class="card shadow-sm p-4">
            <h2 class="card-title text-center mb-4">Connexion à Yummy</h2>
            
            <?php if (!empty($error_message)): ?>
                <div class="alert alert-danger" role="alert">
                    <?= htmlspecialchars($error_message) ?>
                </div>
            <?php endif; ?>
            
            <form method="post" action="<?= htmlspecialchars($_SERVER["PHP_SELF"]) ?>">
                <div class="mb-3">
                    <label for="email" class="form-label">Adresse email</label>
                    <input type="email" class="form-control" id="email" name="email" required autofocus>
                </div>
                
                <div class="mb-3">
                    <label for="password" class="form-label">Mot de passe</label>
                    <input type="password" class="form-control" id="password" name="password" required>
                </div>
                
                <div class="mb-3 form-check">
                    <input type="checkbox" class="form-check-input" id="remember" name="remember">
                    <label class="form-check-label" for="remember">Se souvenir de moi</label>
                </div>
                
                <button type="submit" class="btn btn-primary w-100">Se connecter</button>
            </form>
            
            <div class="text-center mt-3">
                <p>Pas encore inscrit ? <a href="register.php">Créer un compte</a></p>
            </div>
        </div>
    </div>
</div>

<?php include_once 'includes/footer.php'; ?>
