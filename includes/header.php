
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Restaurant Manager</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark navbar-custom">

        <div class="container">
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                                    <?php
                    $current_page = basename($_SERVER['PHP_SELF']);
                    $user_logged_in = isset($_SESSION['user_id']);
                    ?>

                    <a class="navbar-brand" href="main.php" id="acceuil" 
                    style="<?php 
                        // Si utilisateur connecté, on cache le lien YUMMY, sinon on l'affiche
                        echo $user_logged_in ? 'display:none;' : 'display:inline-block;'; 
                    ?>">
                        <i class="fas fa-utensils me-2"></i>YUMMY
                    </a>

                    <ul class="navbar-nav ms-auto">
                        <?php if ($user_logged_in): ?>
                            <a class="navbar-brand" href="../main.php">
                                <i class="fas fa-utensils me-2"></i>YUMMY
                            </a>
                        <?php endif; ?>
                    </ul>
                <ul class="navbar-nav ms-auto">
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <?php if ($_SESSION['role'] == 'client'): ?>
                            <li class="nav-item">
                                <a class="nav-link" href="../client/dashboard.php">Accueil</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="../client/menu.php">Menu</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="../client/commandes.php">Mes commandes</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="../client/profil.php">Mon profil</a>
                            </li>
                        <?php elseif ($_SESSION['role'] == 'restaurateur'): ?>
                            <li class="nav-item">
                                <a class="nav-link" href="../restaurateur/dashboard.php">Tableau de bord</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="../restaurateur/plats.php">Gestion des plats</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="../restaurateur/commandes.php">Commandes</a>
                            </li>
                        <?php elseif ($_SESSION['role'] == 'livreur'): ?>
                            <li class="nav-item">
                                <a class="nav-link" href="../livreur/dashboard.php">Tableau de bord</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="../livreur/livraisons.php">Mes livraisons</a>
                            </li>
                        <?php elseif ($_SESSION['role'] == 'gerant'): ?>
                            <li class="nav-item">
                                <a class="nav-link" href="../gerant/dashboard.php">Tableau de bord</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="../gerant/personnel.php">Personnel</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="../gerant/stats.php">Statistiques</a>
                            </li>
                        <?php endif; ?>
                        <li class="nav-item">
                            <a class="nav-link" href="../logout.php">Déconnexion</a>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link" href="login.php">Connexion</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="register.php">Inscription</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>
<style>
    /* Rouge clair personnalisé pour navbar */
.navbar-custom {
    background-color: #ff4d4d; /* rouge clair */
}

.navbar-custom .navbar-brand,
.navbar-custom .nav-link {
    color: white;
    transition: color 0.3s ease;
}

.navbar-custom .nav-link:hover {
    color: #ffe6e6; /* couleur plus claire au survol */
}

</style>
    <div class="container mt-4">
        
