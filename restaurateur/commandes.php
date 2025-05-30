
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

// Filtrage des commandes
$filtre = isset($_GET['filtre']) ? $_GET['filtre'] : 'toutes';

$where_clause = "";
switch ($filtre) {
    case 'a_preparer':
        $where_clause = "WHERE c.statut IN ('nouvelle', 'en_preparation')";
        break;
    case 'pretes':
        $where_clause = "WHERE c.statut = 'prete'";
        break;
    case 'en_livraison':
        $where_clause = "WHERE c.statut = 'en_livraison'";
        break;
    case 'livrees':
        $where_clause = "WHERE c.statut = 'livree'";
        break;
    case 'annulees':
        $where_clause = "WHERE c.statut = 'annulee'";
        break;
    default:
        $where_clause = "";
        break;
}

// Récupérer les commandes
$query = "SELECT c.*, u.nom, u.prenom FROM commandes c 
          LEFT JOIN users u ON c.client_id = u.id 
          $where_clause
          ORDER BY c.date_commande DESC";
$stmt = $db->prepare($query);
$stmt->execute();
$commandes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Gestion des messages
$message = "";
if (isset($_SESSION['message'])) {
    $message = $_SESSION['message'];
    unset($_SESSION['message']);
}

// Afficher la page des commandes
include_once '../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1>Gestion des commandes</h1>
</div>

<?php if (!empty($message)): ?>
    <div class="alert alert-success alert-dismissible fade show mb-4">
        <?php echo $message; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<div class="card mb-4">
    <div class="card-header">
        <ul class="nav nav-tabs card-header-tabs">
            <li class="nav-item">
                <a class="nav-link <?php echo $filtre == 'toutes' ? 'active' : ''; ?>" href="?filtre=toutes">
                    Toutes
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $filtre == 'a_preparer' ? 'active' : ''; ?>" href="?filtre=a_preparer">
                    À préparer
                    <?php
                    $query = "SELECT COUNT(*) as total FROM commandes WHERE statut IN ('nouvelle', 'en_preparation')";
                    $stmt = $db->prepare($query);
                    $stmt->execute();
                    $count = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
                    if ($count > 0): ?>
                        <span class="badge bg-danger ms-1"><?php echo $count; ?></span>
                    <?php endif; ?>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $filtre == 'pretes' ? 'active' : ''; ?>" href="?filtre=pretes">
                    Prêtes
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $filtre == 'en_livraison' ? 'active' : ''; ?>" href="?filtre=en_livraison">
                    En livraison
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $filtre == 'livrees' ? 'active' : ''; ?>" href="?filtre=livrees">
                    Livrées
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $filtre == 'annulees' ? 'active' : ''; ?>" href="?filtre=annulees">
                    Annulées
                </a>
            </li>
        </ul>
    </div>
    <div class="card-body">
        <?php if (count($commandes) > 0): ?>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>N°</th>
                            <th>Client</th>
                            <th>Date</th>
                            <th>Montant</th>
                            <th>Statut</th>
                            <th>Mode paiement</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($commandes as $commande): ?>
                            <tr>
                                <td>#<?php echo $commande['id']; ?></td>
                                <td><?php echo $commande['nom'] . ' ' . $commande['prenom']; ?></td>
                                <td><?php echo date('d/m/Y H:i', strtotime($commande['date_commande'])); ?></td>
                                <td><?php echo format_prix($commande['montant_total']); ?></td>
                                <td>
                                    <?php
                                    $statut_class = '';
                                    switch ($commande['statut']) {
                                        case 'nouvelle':
                                            $statut_class = 'badge-status-nouvelle';
                                            break;
                                        case 'en_preparation':
                                            $statut_class = 'badge-status-en_preparation';
                                            break;
                                        case 'prete':
                                            $statut_class = 'badge-status-prete';
                                            break;
                                        case 'en_livraison':
                                            $statut_class = 'badge-status-en_livraison';
                                            break;
                                        case 'livree':
                                            $statut_class = 'badge-status-livree';
                                            break;
                                        case 'annulee':
                                            $statut_class = 'badge-status-annulee';
                                            break;
                                    }
                                    ?>
                                    <span class="badge <?php echo $statut_class; ?>">
                                        <?php echo get_statut_commande($commande['statut']); ?>
                                    </span>
                                </td>
                                <td><?php echo get_mode_paiement($commande['mode_paiement']); ?></td>
                                <td>
                                    <div class="btn-group">
                                        <a href="voir_commande.php?id=<?php echo $commande['id']; ?>" class="btn btn-sm btn-outline-primary">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <?php if ($commande['statut'] == 'nouvelle'): ?>
                                            <a href="maj_commande.php?id=<?php echo $commande['id']; ?>&statut=en_preparation" class="btn btn-sm btn-outline-warning">
                                                <i class="fas fa-fire"></i>
                                            </a>
                                        <?php elseif ($commande['statut'] == 'en_preparation'): ?>
                                            <a href="maj_commande.php?id=<?php echo $commande['id']; ?>&statut=prete" class="btn btn-sm btn-outline-success">
                                                <i class="fas fa-check"></i>
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="text-center py-4">
                <i class="fas fa-clipboard-list fa-3x mb-3 text-muted"></i>
                <p>Aucune commande à afficher pour le filtre sélectionné.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include_once '../includes/footer.php'; ?>
