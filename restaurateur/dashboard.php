
<?php
session_start();
include_once '../config/database.php';
include_once '../includes/functions.php';

// Vérifier si l'utilisateur est connecté et est un restaurateur
if (!check_role('restaurateur')) {
    header("Location: ../login.php");
    exit();
}

// Récupérer les informations de la base de données
$database = new Database();
$db = $database->getConnection();

// Nombre de commandes à préparer
$query = "SELECT COUNT(*) as total FROM commandes WHERE statut IN ('nouvelle', 'en_preparation')";
$stmt = $db->prepare($query);
$stmt->execute();
$commandes_a_preparer = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Nombre de commandes prêtes à être livrées
$query = "SELECT COUNT(*) as total FROM commandes WHERE statut = 'prete'";
$stmt = $db->prepare($query);
$stmt->execute();
$commandes_pretes = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Nombre de plats disponibles
$query = "SELECT COUNT(*) as total FROM plats WHERE disponible = 1";
$stmt = $db->prepare($query);
$stmt->execute();
$plats_disponibles = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Nombre d'ingrédients en stock bas
$query = "SELECT COUNT(*) as total FROM stocks WHERE quantite <= seuil_alerte";
$stmt = $db->prepare($query);
$stmt->execute();
$stock_alerte = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Récupérer les commandes récentes
$query = "SELECT c.*, u.nom, u.prenom FROM commandes c 
          LEFT JOIN users u ON c.client_id = u.id 
          WHERE c.statut IN ('nouvelle', 'en_preparation', 'prete') 
          ORDER BY c.date_commande DESC LIMIT 10";
$stmt = $db->prepare($query);
$stmt->execute();
$commandes_recentes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Afficher le dashboard
include_once '../includes/header.php';
?>

<div class="row mb-4">
    <div class="col-xl-3 col-sm-6 mb-4">
        <div class="card h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-muted mb-1">À préparer</h6>
                        <h3 class="mb-0"><?php echo $commandes_a_preparer; ?></h3>
                    </div>
                    <div class="dashboard-stat bg-primary rounded-circle p-3">
                        <i class="fas fa-fire"></i>
                    </div>
                </div>
                <div class="mt-3">
                    <a href="commandes.php?filtre=a_preparer" class="text-primary stretched-link">Voir les commandes</a>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-xl-3 col-sm-6 mb-4">
        <div class="card h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-muted mb-1">Prêtes à livrer</h6>
                        <h3 class="mb-0"><?php echo $commandes_pretes; ?></h3>
                    </div>
                    <div class="dashboard-stat bg-success rounded-circle p-3">
                        <i class="fas fa-check-circle"></i>
                    </div>
                </div>
                <div class="mt-3">
                    <a href="commandes.php?filtre=pretes" class="text-success stretched-link">Voir les commandes</a>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-xl-3 col-sm-6 mb-4">
        <div class="card h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-muted mb-1">Plats disponibles</h6>
                        <h3 class="mb-0"><?php echo $plats_disponibles; ?></h3>
                    </div>
                    <div class="dashboard-stat bg-info rounded-circle p-3">
                        <i class="fas fa-utensils"></i>
                    </div>
                </div>
                <div class="mt-3">
                    <a href="plats.php" class="text-info stretched-link">Gérer les plats</a>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-xl-3 col-sm-6 mb-4">
        <div class="card h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-muted mb-1">Stock en alerte</h6>
                        <h3 class="mb-0"><?php echo $stock_alerte; ?></h3>
                    </div>
                    <div class="dashboard-stat bg-danger rounded-circle p-3">
                        <i class="fas fa-exclamation-triangle"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-8">
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">Commandes récentes</h5>
            </div>
            <div class="card-body">
                <?php if (count($commandes_recentes) > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>N°</th>
                                    <th>Client</th>
                                    <th>Date</th>
                                    <th>Montant</th>
                                    <th>Statut</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($commandes_recentes as $commande): ?>
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
                                        <td>
                                            <a href="voir_commande.php?id=<?php echo $commande['id']; ?>" class="btn btn-sm btn-outline-primary">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <a href="commandes.php" class="btn btn-sm btn-primary mt-3">Voir toutes les commandes</a>
                <?php else: ?>
                    <div class="text-center py-4">
                        <i class="fas fa-clipboard-list fa-3x mb-3 text-muted"></i>
                        <p>Aucune commande récente à afficher.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <div class="col-lg-4">
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">Stock en alerte</h5>
            </div>
            <div class="card-body">
                <?php
                $query = "SELECT * FROM stocks WHERE quantite <= seuil_alerte ORDER BY quantite ASC";
                $stmt = $db->prepare($query);
                $stmt->execute();
                $stock_alerte_items = $stmt->fetchAll(PDO::FETCH_ASSOC);
                ?>
                
                <?php if (count($stock_alerte_items) > 0): ?>
                    <div class="list-group">
                        <?php foreach ($stock_alerte_items as $item): ?>
                            <div class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="mb-1"><?php echo $item['ingredient']; ?></h6>
                                    <small class="text-muted">Reste : <?php echo $item['quantite'] . ' ' . $item['unite']; ?></small>
                                </div>
                                <span class="badge bg-danger">
                                    <?php echo $item['quantite'] <= 0 ? 'Épuisé' : 'Niveau bas'; ?>
                                </span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <a href="stocks.php" class="btn btn-sm btn-primary mt-3">Gérer tous les stocks</a>
                <?php else: ?>
                    <div class="text-center py-4">
                        <i class="fas fa-check-circle fa-3x mb-3 text-success"></i>
                        <p>Tous les stocks sont à des niveaux satisfaisants.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Informations</h5>
            </div>
            <div class="card-body">
                <div class="list-group">
                    <a href="plats.php" class="list-group-item list-group-item-action">
                        <div class="d-flex w-100 justify-content-between">
                            <h6 class="mb-1">Gérer les plats</h6>
                            <i class="fas fa-angle-right"></i>
                        </div>
                        <small class="text-muted">Ajoutez, modifiez ou supprimez des plats du menu</small>
                    </a>
                    <a href="categories.php" class="list-group-item list-group-item-action">
                        <div class="d-flex w-100 justify-content-between">
                            <h6 class="mb-1">Gérer les catégories</h6>
                            <i class="fas fa-angle-right"></i>
                        </div>
                        <small class="text-muted">Organisez votre menu en catégories</small>
                    </a>
                    <a href="commandes.php" class="list-group-item list-group-item-action">
                        <div class="d-flex w-100 justify-content-between">
                            <h6 class="mb-1">Gérer les commandes</h6>
                            <i class="fas fa-angle-right"></i>
                        </div>
                        <small class="text-muted">Suivez et mettez à jour le statut des commandes</small>
                    </a>
                    
                </div>
            </div>
        </div>
    </div>
</div>

<?php include_once '../includes/footer.php'; ?>
