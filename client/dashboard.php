
<?php
session_start();
include_once '../config/database.php';
include_once '../includes/functions.php';

// Vérifier si l'utilisateur est connecté et est un client
if (!check_role('client')) {
    header("Location: ../login.php");
    exit();
}

// Récupérer les informations de l'utilisateur
$user_id = $_SESSION['user_id'];
$database = new Database();
$db = $database->getConnection();

// Récupérer les commandes récentes
$query = "SELECT * FROM commandes WHERE client_id = :client_id ORDER BY date_commande DESC LIMIT 5";
$stmt = $db->prepare($query);
$stmt->bindParam(":client_id", $user_id);
$stmt->execute();
$commandes_recentes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Récupérer les plats populaires
$query = "SELECT p.id, p.nom, p.description, p.prix, p.image, COUNT(cd.id) as nombre_commandes 
          FROM plats p 
          LEFT JOIN commande_details cd ON p.id = cd.plat_id 
          LEFT JOIN commandes c ON cd.commande_id = c.id 
          WHERE p.disponible = 1 
          GROUP BY p.id 
          ORDER BY nombre_commandes DESC 
          LIMIT 4";
$stmt = $db->prepare($query);
$stmt->execute();
$plats_populaires = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Afficher le dashboard
include_once '../includes/header.php';
?>
<link href="../assets/css/dashboard.css" rel="stylesheet">
<div class="p-4 bg-gradient-primary text-white rounded-3 mb-4">
    <h1 class="display-6">Bonjour, <?php echo $_SESSION['prenom']; ?> !</h1>
    <p class="lead">Bienvenue sur votre espace client. Que souhaitez-vous commander aujourd'hui ?</p>
    <a href="menu.php" class="btn btn-light btn-lg mt-2">
        <i class="fas fa-utensils me-2"></i>Voir le menu
    </a>
</div>

<div class="row mb-4">
    <div class="col-md-6">
        <div class="card h-100">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-history me-2"></i>Commandes récentes</h5>
            </div>
            <div class="card-body">
                <?php if (count($commandes_recentes) > 0): ?>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>N°</th>
                                    <th>Date</th>
                                    <th>Montant</th>
                                    <th>Statut</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($commandes_recentes as $commande): ?>
                                    <tr>
                                        <td><?php echo $commande['id']; ?></td>
                                        <td><?php echo date('d/m/Y H:i', strtotime($commande['date_commande'])); ?></td>
                                        <td><?php echo $commande['montant_total']." DH"; ?></td>
                                        <td>
                                            <span class="badge bg-<?php echo $commande['statut'] == 'annulee' ? 'danger' : ($commande['statut'] == 'livree' ? 'success' : 'warning'); ?>">
                                                <?php echo get_statut_commande($commande['statut']); ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <a href="commandes.php" class="btn btn-sm btn-outline-primary mt-2">Voir toutes mes commandes</a>
                <?php else: ?>
                    <div class="text-center py-4">
                        <i class="fas fa-shopping-bag fa-3x mb-3 text-muted"></i>
                        <p>Vous n'avez pas encore effectué de commandes.</p>
                        <a href="menu.php" class="btn btn-primary">Commander maintenant</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <div class="col-md-6">
        <div class="card h-100">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-star me-2"></i>Plats populaires</h5>
            </div>
            <div class="card-body">
                <div class="row row-cols-2 g-3">
                    <?php foreach ($plats_populaires as $plat): ?>
                        <div class="col">
                            <div class="card h-100 border-0 shadow-sm">
                                <img src="<?php echo !empty($plat['image']) ? '../assets/images/' . $plat['image'] : '../assets/images/placeholder.jpg'; ?>" 
                                    class="card-img-top" alt="<?php echo $plat['nom']; ?>">
                                <div class="card-body">
                                    <h6 class="card-title"><?php echo $plat['nom']; ?></h6>
                                    <p class="card-text text-primary fw-bold"><?php echo $plat['prix']." DH"; ?></p>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <a href="menu.php" class="btn btn-sm btn-outline-primary d-block mt-3">Voir tout le menu</a>
            </div>
        </div>
    </div>
</div>

<div class="card mb-4">
    <div class="card-header">
        <h5 class="mb-0"><i class="fas fa-info-circle me-2"></i>À propos de notre restaurant</h5>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-6">
                <h4>Notre histoire</h4>
                <p>Fondé en 2020, notre restaurant s'engage à offrir une expérience culinaire exceptionnelle avec des ingrédients frais et locaux. Notre chef expérimenté crée des plats savoureux qui allient tradition et innovation.</p>
            </div>
            <div class="col-md-6">
                <h4>Nos horaires</h4>
                <table class="table table-sm">
                    <tbody>
                        <tr>
                            <td>Lundi - Vendredi</td>
                            <td>11h00 - 22h00</td>
                        </tr>
                        <tr>
                            <td>Samedi</td>
                            <td>10h00 - 23h00</td>
                        </tr>
                        <tr>
                            <td>Dimanche</td>
                            <td>10h00 - 21h00</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include_once '../includes/footer.php'; ?>
