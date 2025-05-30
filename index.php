
<?php
session_start();
include_once 'config/database.php';
include_once 'includes/functions.php';

// Redirection vers la page de connexion si l'utilisateur n'est pas connecté
if (!isset($_SESSION['user_id'])) {
    header("Location: main.php");
    exit();
}

// Récupérer les informations de l'utilisateur
$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];

// Redirection vers le dashboard approprié selon le rôle
switch ($role) {
    case 'client':
        header("Location: client/dashboard.php");
        break;
    case 'restaurateur':
        header("Location: restaurateur/dashboard.php");
        break;
    case 'livreur':
        header("Location: livreur/dashboard.php");
        break;
    case 'gerant':
        header("Location: gerant/dashboard.php");
        break;
    default:
        header("Location: login.php");
        break;
}
?>
