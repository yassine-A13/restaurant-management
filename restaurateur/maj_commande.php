
<?php
session_start();
include_once '../config/database.php';
include_once '../includes/functions.php';

// Vérifier si l'utilisateur est connecté et est un restaurateur
if (!check_role('restaurateur')) {
    header("Location: ../login.php");
    exit();
}

if (!isset($_GET['id']) || !is_numeric($_GET['id']) || !isset($_GET['statut'])) {
    header("Location: commandes.php");
    exit();
}

$commande_id = $_GET['id'];
$statut = $_GET['statut'];

// Vérifier que le statut est valide
$statuts_valides = ['en_preparation', 'prete', 'annulee'];
if (!in_array($statut, $statuts_valides)) {
    header("Location: commandes.php");
    exit();
}

$database = new Database();
$db = $database->getConnection();

// Vérifier que la commande existe et a un statut compatible
$query = "SELECT * FROM commandes WHERE id = :id";
$stmt = $db->prepare($query);
$stmt->bindParam(":id", $commande_id);
$stmt->execute();

if ($stmt->rowCount() == 0) {
    header("Location: commandes.php");
    exit();
}

$commande = $stmt->fetch(PDO::FETCH_ASSOC);

// Vérifier les transitions d'état autorisées
$transition_valide = false;
switch ($commande['statut']) {
    case 'nouvelle':
        $transition_valide = ($statut == 'en_preparation' || $statut == 'annulee');
        break;
    case 'en_preparation':
        $transition_valide = ($statut == 'prete' || $statut == 'annulee');
        break;
    default:
        $transition_valide = false;
        break;
}

if (!$transition_valide) {
    $_SESSION['message'] = "Transition d'état non autorisée pour cette commande.";
    header("Location: commandes.php");
    exit();
}

// Mettre à jour le statut de la commande
$query = "UPDATE commandes SET statut = :statut WHERE id = :id";
$stmt = $db->prepare($query);
$stmt->bindParam(":statut", $statut);
$stmt->bindParam(":id", $commande_id);

if ($stmt->execute()) {
    // Si la commande est marquée comme prête, créer une entrée dans la table des livraisons
    if ($statut == 'prete') {
        $query = "INSERT INTO livraisons (commande_id) VALUES (:commande_id)";
        $stmt = $db->prepare($query);
        $stmt->bindParam(":commande_id", $commande_id);
        $stmt->execute();
        
        $_SESSION['message'] = "La commande #" . $commande_id . " a été marquée comme prête à être livrée.";
    } elseif ($statut == 'en_preparation') {
        $_SESSION['message'] = "La préparation de la commande #" . $commande_id . " a été commencée.";
    } elseif ($statut == 'annulee') {
        $_SESSION['message'] = "La commande #" . $commande_id . " a été annulée.";
    }
} else {
    $_SESSION['message'] = "Une erreur est survenue lors de la mise à jour du statut de la commande.";
}

// Rediriger vers la page des commandes ou vers la page de détails de la commande
if (isset($_GET['retour']) && $_GET['retour'] == 'detail') {
    header("Location: voir_commande.php?id=" . $commande_id);
} else {
    header("Location: commandes.php");
}
exit();
?>
