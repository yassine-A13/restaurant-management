
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

// Récupérer les statistiques générales
// Nombre total de commandes
$query = "SELECT COUNT(*) as total FROM commandes";
$stmt = $db->prepare($query);
$stmt->execute();
$total_commandes = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Chiffre d'affaires total
$query = "SELECT SUM(montant_total) as total FROM commandes WHERE statut != 'annulee'";
$stmt = $db->prepare($query);
$stmt->execute();
$chiffre_affaires = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?: 0;

// Nombre de clients
$query = "SELECT COUNT(*) as total FROM users WHERE role = 'client'";
$stmt = $db->prepare($query);
$stmt->execute();
$total_clients = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Nombre de plats disponibles
$query = "SELECT COUNT(*) as total FROM plats WHERE disponible = 1";
$stmt = $db->prepare($query);
$stmt->execute();
$plats_disponibles = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Récupérer les commandes récentes
$query = "SELECT c.*, u.nom, u.prenom FROM commandes c 
          LEFT JOIN users u ON c.client_id = u.id 
          ORDER BY c.date_commande DESC LIMIT 5";
$stmt = $db->prepare($query);
$stmt->execute();
$commandes_recentes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Récupérer le nombre de commandes par statut
$query = "SELECT statut, COUNT(*) as total FROM commandes GROUP BY statut";
$stmt = $db->prepare($query);
$stmt->execute();
$commandes_par_statut = $stmt->fetchAll(PDO::FETCH_ASSOC);
$statut_data = [];
foreach ($commandes_par_statut as $row) {
    $statut_data[$row['statut']] = $row['total'];
}

// Récupérer les employés
$query = "SELECT * FROM users WHERE role != 'client' ORDER BY role, nom";
$stmt = $db->prepare($query);
$stmt->execute();
$employes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Récupérer les stocks en alerte
$query = "SELECT * FROM stocks WHERE quantite <= seuil_alerte ORDER BY quantite ASC LIMIT 5";
$stmt = $db->prepare($query);
$stmt->execute();
$stocks_alerte = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Afficher le dashboard
include_once '../includes/header.php';
?>

<div class="p-4 bg-gradient-primary text-white rounded-3 mb-4">
    <h1 class="display-6">Tableau de bord administrateur</h1>
    <p class="lead">Bienvenue, <?php echo $_SESSION['prenom']; ?>. Voici l'état actuel de votre restaurant.</p>
</div>

<div class="row mb-4">
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-muted mb-1">Commandes totales</h6>
                        <h3 class="mb-0"><?php echo $total_commandes; ?></h3>
                    </div>
                    <div class="dashboard-stat bg-primary rounded-circle p-3">
                        <i class="fas fa-shopping-bag"></i>
                    </div>
                </div>
               
            </div>
        </div>
    </div>
    
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-muted mb-1">Chiffre d'affaires</h6>
                        <h3 class="mb-0"><?php echo number_format($chiffre_affaires, 2, ',', ' '); ?> DH</h3>
                    </div>
                    <div class="dashboard-stat bg-success rounded-circle p-3">
                        <i class="fas fa-euro-sign"></i>
                    </div>
                </div>
                
            </div>
        </div>
    </div>
    
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-muted mb-1">Clients inscrits</h6>
                        <h3 class="mb-0"><?php echo $total_clients; ?></h3>
                    </div>
                    <div class="dashboard-stat bg-info rounded-circle p-3">
                        <i class="fas fa-users"></i>
                    </div>
                </div>
                
            </div>
        </div>
    </div>
    
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-muted mb-1">Plats disponibles</h6>
                        <h3 class="mb-0"><?php echo $plats_disponibles; ?></h3>
                    </div>
                    <div class="dashboard-stat bg-warning rounded-circle p-3">
                        <i class="fas fa-utensils"></i>
                    </div>
                </div>
                
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-xl-8 mb-4">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Commandes récentes</h5>
                
            </div>
            <div class="card-body">
                <?php if (count($commandes_recentes) > 0): ?>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>N°</th>
                                    <th>Client</th>
                                    <th>Date</th>
                                    <th>Montant</th>
                                    <th>Statut</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($commandes_recentes as $commande): ?>
                                    <tr>
                                        <td>#<?php echo $commande['id']; ?></td>
                                        <td><?php echo $commande['nom'] . ' ' . $commande['prenom']; ?></td>
                                        <td><?php echo date('d/m/Y H:i', strtotime($commande['date_commande'])); ?></td>
                                        <td><?php echo $commande['montant_total']." DH"; ?></td>
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
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="text-center py-4">
                        <i class="fas fa-clipboard-list fa-3x mb-3 text-muted"></i>
                        <p>Aucune commande récente à afficher.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <div class="col-xl-4 mb-4">
        <div class="card h-100">
            <div class="card-header">
                <h5 class="mb-0">Statut des commandes</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table">
                        <tbody>
                            <tr>
                                <td>Nouvelles</td>
                                <td>
                                    <div class="progress">
                                        <div class="progress-bar bg-info" role="progressbar" 
                                             style="width: <?php echo ($total_commandes > 0) ? ($statut_data['nouvelle'] ?? 0) / $total_commandes * 100 : 0; ?>%">
                                        </div>
                                    </div>
                                </td>
                                <td class="text-end"><?php echo $statut_data['nouvelle'] ?? 0; ?></td>
                            </tr>
                            <tr>
                                <td>En préparation</td>
                                <td>
                                    <div class="progress">
                                        <div class="progress-bar bg-warning" role="progressbar" 
                                             style="width: <?php echo ($total_commandes > 0) ? ($statut_data['en_preparation'] ?? 0) / $total_commandes * 100 : 0; ?>%">
                                        </div>
                                    </div>
                                </td>
                                <td class="text-end"><?php echo $statut_data['en_preparation'] ?? 0; ?></td>
                            </tr>
                            <tr>
                                <td>Prêtes</td>
                                <td>
                                    <div class="progress">
                                        <div class="progress-bar bg-primary" role="progressbar" 
                                             style="width: <?php echo ($total_commandes > 0) ? ($statut_data['prete'] ?? 0) / $total_commandes * 100 : 0; ?>%">
                                        </div>
                                    </div>
                                </td>
                                <td class="text-end"><?php echo $statut_data['prete'] ?? 0; ?></td>
                            </tr>
                            <tr>
                                <td>En livraison</td>
                                <td>
                                    <div class="progress">
                                        <div class="progress-bar bg-secondary" role="progressbar" 
                                             style="width: <?php echo ($total_commandes > 0) ? ($statut_data['en_livraison'] ?? 0) / $total_commandes * 100 : 0; ?>%">
                                        </div>
                                    </div>
                                </td>
                                <td class="text-end"><?php echo $statut_data['en_livraison'] ?? 0; ?></td>
                            </tr>
                            <tr>
                                <td>Livrées</td>
                                <td>
                                    <div class="progress">
                                        <div class="progress-bar bg-success" role="progressbar" 
                                             style="width: <?php echo ($total_commandes > 0) ? ($statut_data['livree'] ?? 0) / $total_commandes * 100 : 0; ?>%">
                                        </div>
                                    </div>
                                </td>
                                <td class="text-end"><?php echo $statut_data['livree'] ?? 0; ?></td>
                            </tr>
                            <tr>
                                <td>Annulées</td>
                                <td>
                                    <div class="progress">
                                        <div class="progress-bar bg-danger" role="progressbar" 
                                             style="width: <?php echo ($total_commandes > 0) ? ($statut_data['annulee'] ?? 0) / $total_commandes * 100 : 0; ?>%">
                                        </div>
                                    </div>
                                </td>
                                <td class="text-end"><?php echo $statut_data['annulee'] ?? 0; ?></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div class="text-center mt-3">
                    <a href="stats.php" class="btn btn-sm btn-outline-primary">Voir les statistiques détaillées</a>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-xl-6 mb-4">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Équipe</h5>
                <a href="personnel.php" class="btn btn-sm btn-primary">Gérer</a>
            </div>
            <div class="card-body">
                <?php if (count($employes) > 0): ?>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Nom</th>
                                    <th>Email</th>
                                    <th>Rôle</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($employes as $employe): ?>
                                    <tr>
                                        <td><?php echo $employe['nom'] . ' ' . $employe['prenom']; ?></td>
                                        <td><?php echo $employe['email']; ?></td>
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
                                            }
                                            ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="text-center py-4">
                        <i class="fas fa-users fa-3x mb-3 text-muted"></i>
                        <p>Aucun employé trouvé.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <div class="col-xl-6 mb-4">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Stocks en alerte</h5>
                <a href="stocks.php" class="btn btn-sm btn-primary">Gérer</a>
            </div>
            <div class="card-body">
                <?php if (count($stocks_alerte) > 0): ?>
                    <div class="list-group">
                        <?php foreach ($stocks_alerte as $stock): ?>
                            <div class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="mb-1"><?php echo $stock['ingredient']; ?></h6>
                                    <small class="text-muted">Reste : <?php echo $stock['quantite'] . ' ' . $stock['unite']; ?></small>
                                </div>
                                <span class="badge bg-danger">
                                    <?php echo $stock['quantite'] <= 0 ? 'Épuisé' : 'Niveau bas'; ?>
                                </span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="text-center py-4">
                        <i class="fas fa-check-circle fa-3x mb-3 text-success"></i>
                        <p>Tous les stocks sont à des niveaux satisfaisants.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>



<?php include_once '../includes/footer.php'; ?>
