
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

// Période des statistiques
$periode = isset($_GET['periode']) ? $_GET['periode'] : 'mois';
$date_debut = date('Y-m-d 00:00:00');
$date_fin = date('Y-m-d 23:59:59');

switch ($periode) {
    case 'jour':
        $date_debut = date('Y-m-d 00:00:00');
        $date_fin = date('Y-m-d 23:59:59');
        $titre_periode = "Aujourd'hui";
        break;
    case 'semaine':
        $date_debut = date('Y-m-d 00:00:00', strtotime('monday this week'));
        $date_fin = date('Y-m-d 23:59:59', strtotime('sunday this week'));
        $titre_periode = "Cette semaine";
        break;
    case 'mois':
        $date_debut = date('Y-m-01 00:00:00');
        $date_fin = date('Y-m-t 23:59:59');
        $titre_periode = "Ce mois";
        break;
    case 'annee':
        $date_debut = date('Y-01-01 00:00:00');
        $date_fin = date('Y-12-31 23:59:59');
        $titre_periode = "Cette année";
        break;
    case 'personnalise':
        $date_debut = isset($_GET['date_debut']) ? $_GET['date_debut'] . ' 00:00:00' : date('Y-m-d 00:00:00');
        $date_fin = isset($_GET['date_fin']) ? $_GET['date_fin'] . ' 23:59:59' : date('Y-m-d 23:59:59');
        $titre_periode = "Du " . date('d/m/Y', strtotime($date_debut)) . " au " . date('d/m/Y', strtotime($date_fin));
        break;
}

// Statistiques générales pour la période
// Nombre de commandes
$query = "SELECT COUNT(*) as total FROM commandes WHERE date_commande BETWEEN :debut AND :fin";
$stmt = $db->prepare($query);
$stmt->bindParam(":debut", $date_debut);
$stmt->bindParam(":fin", $date_fin);
$stmt->execute();
$total_commandes = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Chiffre d'affaires
$query = "SELECT SUM(montant_total) as total FROM commandes WHERE date_commande BETWEEN :debut AND :fin AND statut != 'annulee'";
$stmt = $db->prepare($query);
$stmt->bindParam(":debut", $date_debut);
$stmt->bindParam(":fin", $date_fin);
$stmt->execute();
$chiffre_affaires = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?: 0;

// Nombre de plats vendus
$query = "SELECT SUM(cd.quantite) as total 
          FROM commande_details cd 
          JOIN commandes c ON cd.commande_id = c.id 
          WHERE c.date_commande BETWEEN :debut AND :fin AND c.statut != 'annulee'";
$stmt = $db->prepare($query);
$stmt->bindParam(":debut", $date_debut);
$stmt->bindParam(":fin", $date_fin);
$stmt->execute();
$plats_vendus = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?: 0;

// Moyenne par commande
$moyenne_commande = $total_commandes > 0 ? $chiffre_affaires / $total_commandes : 0;

// Répartition des commandes par statut
$query = "SELECT statut, COUNT(*) as total FROM commandes 
          WHERE date_commande BETWEEN :debut AND :fin 
          GROUP BY statut";
$stmt = $db->prepare($query);
$stmt->bindParam(":debut", $date_debut);
$stmt->bindParam(":fin", $date_fin);
$stmt->execute();
$statuts_commandes = $stmt->fetchAll(PDO::FETCH_ASSOC);
$data_statuts = [];
foreach ($statuts_commandes as $row) {
    $data_statuts[$row['statut']] = $row['total'];
}


// Statistiques sur les livraisons
$query = "SELECT COUNT(*) as total, 
        AVG(TIMESTAMPDIFF(MINUTE, l.heure_depart , l.heure_livraison)) as duree_moyenne
        FROM livraisons l
        JOIN commandes c ON l.commande_id = c.id
        WHERE l.heure_depart BETWEEN :debut AND :fin AND l.statut = 'livree'";
$stmt = $db->prepare($query);
$stmt->bindParam(":debut", $date_debut);
$stmt->bindParam(":fin", $date_fin);
$stmt->execute();
$stats_livraisons = $stmt->fetch(PDO::FETCH_ASSOC);
$livraisons_total = $stats_livraisons['total'];
$duree_moyenne = round($stats_livraisons['duree_moyenne'] ?: 0);

// Performance des livreurs
$query = "SELECT u.nom, u.prenom, COUNT(l.id) as total_livraisons, 
        AVG(TIMESTAMPDIFF(MINUTE, l.heure_depart, l.heure_livraison)) as duree_moyenne
        FROM livraisons l
        JOIN users u ON l.livreur_id = u.id
        WHERE l.heure_depart BETWEEN :debut AND :fin AND l.statut = 'livree'
        GROUP BY l.livreur_id
        ORDER BY total_livraisons DESC
        LIMIT 5";
$stmt = $db->prepare($query);
$stmt->bindParam(":debut", $date_debut);
$stmt->bindParam(":fin", $date_fin);
$stmt->execute();
$performance_livreurs = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Évolution des commandes par jour pour la période
$query = "SELECT DATE(date_commande) as jour, COUNT(*) as total, SUM(montant_total) as ca
        FROM commandes
        WHERE date_commande BETWEEN :debut AND :fin
        GROUP BY DATE(date_commande)
        ORDER BY jour";
$stmt = $db->prepare($query);
$stmt->bindParam(":debut", $date_debut);
$stmt->bindParam(":fin", $date_fin);
$stmt->execute();
$evolution_commandes = $stmt->fetchAll(PDO::FETCH_ASSOC);

include_once '../includes/header.php';
?>

<div class="container-fluid px-4 py-4">
    
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0">Sélection de la période</h5>
        </div>
        <div class="card-body">
            <form action="" method="GET" class="row g-3 align-items-end">
                <div class="col-md-auto">
                    <label class="form-label">Période prédéfinie</label>
                    <div class="btn-group" role="group">
                        <a href="?periode=jour" class="btn btn-outline-secondary <?php echo $periode == 'jour' ? 'active' : ''; ?>">Jour</a>
                        <a href="?periode=semaine" class="btn btn-outline-secondary <?php echo $periode == 'semaine' ? 'active' : ''; ?>">Semaine</a>
                        <a href="?periode=mois" class="btn btn-outline-secondary <?php echo $periode == 'mois' ? 'active' : ''; ?>">Mois</a>
                        <a href="?periode=annee" class="btn btn-outline-secondary <?php echo $periode == 'annee' ? 'active' : ''; ?>">Année</a>
                    </div>
            </form>
        </div>
    </div>
    
    <div class="alert alert-info mb-4">
        <i class="fas fa-info-circle me-2"></i> Statistiques pour la période : <strong><?php echo $titre_periode; ?></strong>
    </div>
    
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-1">Commandes</h6>
                            <h3 class="mb-0"><?php echo $total_commandes; ?></h3>
                        </div>
                        <div class="dashboard-stat bg-primary rounded-circle p-3">
                            <i class="fas fa-shopping-cart"></i>
                        </div>
                    </div>
                    <div class="small mt-2">
                        <?php 
                        // On pourrait ajouter une comparaison avec la période précédente
                        ?>
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
                            <h3 class="mb-0"><?php echo $chiffre_affaires ." DH"; ?></h3>
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
                            <h6 class="text-muted mb-1">Plats vendus</h6>
                            <h3 class="mb-0"><?php echo $plats_vendus; ?></h3>
                        </div>
                        <div class="dashboard-stat bg-info rounded-circle p-3">
                            <i class="fas fa-utensils"></i>
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
                            <h6 class="text-muted mb-1">Panier moyen</h6>
                            <h3 class="mb-0"><?php echo $moyenne_commande ."DH"; ?></h3>
                        </div>
                        <div class="dashboard-stat bg-warning rounded-circle p-3">
                            <i class="fas fa-calculator"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row mb-4">
        <div class="col-lg-6">
            <div class="card mb-4 h-100">
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
                                                 style="width: <?php echo ($total_commandes > 0) ? ($data_statuts['nouvelle'] ?? 0) / $total_commandes * 100 : 0; ?>%">
                                            </div>
                                        </div>
                                    </td>
                                    <td class="text-end"><?php echo $data_statuts['nouvelle'] ?? 0; ?></td>
                                </tr>
                                <tr>
                                    <td>En préparation</td>
                                    <td>
                                        <div class="progress">
                                            <div class="progress-bar bg-warning" role="progressbar" 
                                                 style="width: <?php echo ($total_commandes > 0) ? ($data_statuts['en_preparation'] ?? 0) / $total_commandes * 100 : 0; ?>%">
                                            </div>
                                        </div>
                                    </td>
                                    <td class="text-end"><?php echo $data_statuts['en_preparation'] ?? 0; ?></td>
                                </tr>
                                <tr>
                                    <td>Prêtes</td>
                                    <td>
                                        <div class="progress">
                                            <div class="progress-bar bg-primary" role="progressbar" 
                                                 style="width: <?php echo ($total_commandes > 0) ? ($data_statuts['prete'] ?? 0) / $total_commandes * 100 : 0; ?>%">
                                            </div>
                                        </div>
                                    </td>
                                    <td class="text-end"><?php echo $data_statuts['prete'] ?? 0; ?></td>
                                </tr>
                                <tr>
                                    <td>En livraison</td>
                                    <td>
                                        <div class="progress">
                                            <div class="progress-bar bg-secondary" role="progressbar" 
                                                 style="width: <?php echo ($total_commandes > 0) ? ($data_statuts['en_livraison'] ?? 0) / $total_commandes * 100 : 0; ?>%">
                                            </div>
                                        </div>
                                    </td>
                                    <td class="text-end"><?php echo $data_statuts['en_livraison'] ?? 0; ?></td>
                                </tr>
                                <tr>
                                    <td>Livrées</td>
                                    <td>
                                        <div class="progress">
                                            <div class="progress-bar bg-success" role="progressbar" 
                                                 style="width: <?php echo ($total_commandes > 0) ? ($data_statuts['livree'] ?? 0) / $total_commandes * 100 : 0; ?>%">
                                            </div>
                                        </div>
                                    </td>
                                    <td class="text-end"><?php echo $data_statuts['livree'] ?? 0; ?></td>
                                </tr>
                                <tr>
                                    <td>Annulées</td>
                                    <td>
                                        <div class="progress">
                                            <div class="progress-bar bg-danger" role="progressbar" 
                                                 style="width: <?php echo ($total_commandes > 0) ? ($data_statuts['annulee'] ?? 0) / $total_commandes * 100 : 0; ?>%">
                                            </div>
                                        </div>
                                    </td>
                                    <td class="text-end"><?php echo $data_statuts['annulee'] ?? 0; ?></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        


<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Activer les tooltips Bootstrap
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl)
    });
    
    <?php if (count($evolution_commandes) > 0): ?>
    // Données pour le graphique d'évolution
    var labels = <?php echo json_encode(array_map(function($item) { return date('d/m', strtotime($item['jour'])); }, $evolution_commandes)); ?>;
    var commandes = <?php echo json_encode(array_map(function($item) { return $item['total']; }, $evolution_commandes)); ?>;
    var chiffreAffaires = <?php echo json_encode(array_map(function($item) { return $item['ca']; }, $evolution_commandes)); ?>;
    
    // Créer le graphique d'évolution
    var ctx = document.getElementById('evolutionChart').getContext('2d');
    var evolutionChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [
                {
                    label: 'Nombre de commandes',
                    data: commandes,
                    backgroundColor: 'rgba(54, 162, 235, 0.2)',
                    borderColor: 'rgba(54, 162, 235, 1)',
                    borderWidth: 2,
                    tension: 0.3,
                    yAxisID: 'y'
                },
                {
                    label: 'Chiffre d\'affaires (DH)',
                    data: chiffreAffaires,
                    backgroundColor: 'rgba(255, 99, 132, 0.2)',
                    borderColor: 'rgba(255, 99, 132, 1)',
                    borderWidth: 2,
                    tension: 0.3,
                    yAxisID: 'y1'
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    position: 'left',
                    title: {
                        display: true,
                        text: 'Nombre de commandes'
                    }
                },
                y1: {
                    beginAtZero: true,
                    position: 'right',
                    grid: {
                        drawOnChartArea: false
                    },
                    title: {
                        display: true,
                        text: 'Chiffre d\'affaires (DH)'
                    }
                }
            },
            interaction: {
                mode: 'index',
                intersect: false
            }
        }
    });
    <?php endif; ?>
});
</script>

<style>
/* Styles pour l'impression */
@media print {
    .btn, .card-header, nav, footer, .no-print {
        display: none !important;
    }
    .card {
        border: none !important;
        box-shadow: none !important;
    }
    .card-body {
        padding: 0 !important;
    }
    body {
        padding: 0;
        margin: 0;
    }
    #chart-container {
        height: 400px !important;
    }
}
</style>

<?php include_once '../includes/footer.php'; ?>