
<?php
session_start();
include_once '../config/database.php';
include_once '../includes/functions.php';

// Vérifier si l'utilisateur est connecté et est un restaurateur
if (!check_role('restaurateur')) {
    header("Location: ../login.php");
    exit();
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: commandes.php");
    exit();
}

$commande_id = $_GET['id'];
$database = new Database();
$db = $database->getConnection();

// Récupérer les informations de la commande
$query = "SELECT c.*, u.nom, u.prenom, u.telephone FROM commandes c 
          LEFT JOIN users u ON c.client_id = u.id 
          WHERE c.id = :id";
$stmt = $db->prepare($query);
$stmt->bindParam(":id", $commande_id);
$stmt->execute();

if ($stmt->rowCount() == 0) {
    header("Location: commandes.php");
    exit();
}

$commande = $stmt->fetch(PDO::FETCH_ASSOC);

// Récupérer les détails de la commande
$query = "SELECT cd.*, p.nom FROM commande_details cd 
          JOIN plats p ON cd.plat_id = p.id 
          WHERE cd.commande_id = :commande_id";
$stmt = $db->prepare($query);
$stmt->bindParam(":commande_id", $commande_id);
$stmt->execute();
$details = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Récupérer les informations de livraison
$query = "SELECT l.*, u.nom, u.prenom, u.telephone FROM livraisons l 
          LEFT JOIN users u ON l.livreur_id = u.id 
          WHERE l.commande_id = :commande_id";
$stmt = $db->prepare($query);
$stmt->bindParam(":commande_id", $commande_id);
$stmt->execute();
$livraison = $stmt->fetch(PDO::FETCH_ASSOC);

// Afficher la page de détails de la commande
include_once '../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1>Détails de la commande #<?php echo $commande_id; ?></h1>
    <div>
        <a href="commandes.php" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left me-2"></i>Retour aux commandes
        </a>
        <?php if ($commande['statut'] == 'nouvelle'): ?>
            <a href="maj_commande.php?id=<?php echo $commande_id; ?>&statut=en_preparation" class="btn btn-warning ms-2">
                <i class="fas fa-fire me-2"></i>Commencer la préparation
            </a>
        <?php elseif ($commande['statut'] == 'en_preparation'): ?>
            <a href="maj_commande.php?id=<?php echo $commande_id; ?>&statut=prete" class="btn btn-success ms-2">
                <i class="fas fa-check me-2"></i>Marquer comme prête
            </a>
        <?php endif; ?>
    </div>
</div>

<div class="row">
    <div class="col-md-8">
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">Détails de la commande</h5>
            </div>
            <div class="card-body">
                <div class="row mb-4">
                    <div class="col-md-6">
                        <h6>Client</h6>
                        <p>
                            <?php echo $commande['nom'] . ' ' . $commande['prenom']; ?><br>
                            <?php if (!empty($commande['telephone'])): ?>
                                <strong>Téléphone:</strong> <?php echo $commande['telephone']; ?><br>
                            <?php endif; ?>
                        </p>
                    </div>
                    <div class="col-md-6">
                        <h6>Informations de commande</h6>
                        <p>
                            <strong>Date:</strong> <?php echo date('d/m/Y H:i', strtotime($commande['date_commande'])); ?><br>
                            <strong>Statut:</strong> <?php echo get_statut_commande($commande['statut']); ?><br>
                            <strong>Paiement:</strong> <?php echo get_mode_paiement($commande['mode_paiement']); ?>
                        </p>
                    </div>
                </div>
                
                <h6>Adresse de livraison</h6>
                <p class="mb-4"><?php echo nl2br($commande['adresse_livraison']); ?></p>
                
                <h6>Plats commandés</h6>
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Plat</th>
                                <th>Quantité</th>
                                <th>Prix unitaire</th>
                                <th>Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($details as $detail): ?>
                                <tr>
                                    <td><?php echo $detail['nom']; ?></td>
                                    <td><?php echo $detail['quantite']; ?></td>
                                    <td><?php echo format_prix($detail['prix_unitaire']); ?></td>
                                    <td><?php echo format_prix($detail['prix_unitaire'] * $detail['quantite']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr>
                                <th colspan="3" class="text-end">Total:</th>
                                <th><?php echo format_prix($commande['montant_total']); ?></th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
                
                <?php if (!empty($detail['instructions_speciales'])): ?>
                    <div class="alert alert-info mt-3">
                        <h6>Instructions spéciales</h6>
                        <p class="mb-0"><?php echo nl2br($detail['instructions_speciales']); ?></p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">Statut actuel</h5>
            </div>
            <div class="card-body">
                <?php
                $statut_class = '';
                switch ($commande['statut']) {
                    case 'nouvelle':
                        $statut_class = 'bg-info';
                        break;
                    case 'en_preparation':
                        $statut_class = 'bg-warning';
                        break;
                    case 'prete':
                        $statut_class = 'bg-success';
                        break;
                    case 'en_livraison':
                        $statut_class = 'bg-secondary';
                        break;
                    case 'livree':
                        $statut_class = 'bg-dark';
                        break;
                    case 'annulee':
                        $statut_class = 'bg-danger';
                        break;
                }
                ?>
                <div class="text-center p-3 <?php echo $statut_class; ?> text-white rounded">
                    <h4 class="mb-0"><?php echo get_statut_commande($commande['statut']); ?></h4>
                </div>
                
                <div class="mt-4">
                    <h6>Historique</h6>
                    <ul class="list-group">
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <div>
                                <strong>Commande reçue</strong>
                                <div class="text-muted small"><?php echo date('d/m/Y H:i', strtotime($commande['date_commande'])); ?></div>
                            </div>
                            <span class="badge bg-success rounded-pill">✓</span>
                        </li>
                        
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <div>
                                <strong>En préparation</strong>
                                <div class="text-muted small">
                                    <?php 
                                    if ($commande['statut'] == 'nouvelle') {
                                        echo 'En attente';
                                    } elseif (in_array($commande['statut'], ['en_preparation', 'prete', 'en_livraison', 'livree'])) {
                                        echo 'Commencée ' . date('d/m/Y H:i', strtotime($commande['date_modification']));
                                    } else {
                                        echo 'Annulée';
                                    }
                                    ?>
                                </div>
                            </div>
                            <?php if (in_array($commande['statut'], ['en_preparation', 'prete', 'en_livraison', 'livree'])): ?>
                                <span class="badge bg-success rounded-pill">✓</span>
                            <?php elseif ($commande['statut'] == 'nouvelle'): ?>
                                <span class="badge bg-warning rounded-pill">⌛</span>
                            <?php else: ?>
                                <span class="badge bg-danger rounded-pill">✗</span>
                            <?php endif; ?>
                        </li>
                        
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <div>
                                <strong>Prête pour livraison</strong>
                                <div class="text-muted small">
                                    <?php 
                                    if (in_array($commande['statut'], ['nouvelle', 'en_preparation'])) {
                                        echo 'En attente';
                                    } elseif (in_array($commande['statut'], ['prete', 'en_livraison', 'livree'])) {
                                        echo 'Prête ' . date('d/m/Y H:i', strtotime($commande['date_modification']));
                                    } else {
                                        echo 'Annulée';
                                    }
                                    ?>
                                </div>
                            </div>
                            <?php if (in_array($commande['statut'], ['prete', 'en_livraison', 'livree'])): ?>
                                <span class="badge bg-success rounded-pill">✓</span>
                            <?php elseif (in_array($commande['statut'], ['nouvelle', 'en_preparation'])): ?>
                                <span class="badge bg-warning rounded-pill">⌛</span>
                            <?php else: ?>
                                <span class="badge bg-danger rounded-pill">✗</span>
                            <?php endif; ?>
                        </li>
                        
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <div>
                                <strong>En livraison</strong>
                                <div class="text-muted small">
                                    <?php 
                                    if (in_array($commande['statut'], ['nouvelle', 'en_preparation', 'prete'])) {
                                        echo 'En attente';
                                    } elseif (in_array($commande['statut'], ['en_livraison', 'livree'])) {
                                        echo 'En route ' . ($livraison && $livraison['heure_depart'] ? date('d/m/Y H:i', strtotime($livraison['heure_depart'])) : '');
                                    } else {
                                        echo 'Annulée';
                                    }
                                    ?>
                                </div>
                            </div>
                            <?php if (in_array($commande['statut'], ['en_livraison', 'livree'])): ?>
                                <span class="badge bg-success rounded-pill">✓</span>
                            <?php elseif (in_array($commande['statut'], ['nouvelle', 'en_preparation', 'prete'])): ?>
                                <span class="badge bg-warning rounded-pill">⌛</span>
                            <?php else: ?>
                                <span class="badge bg-danger rounded-pill">✗</span>
                            <?php endif; ?>
                        </li>
                        
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <div>
                                <strong>Livrée</strong>
                                <div class="text-muted small">
                                    <?php 
                                    if (in_array($commande['statut'], ['nouvelle', 'en_preparation', 'prete', 'en_livraison'])) {
                                        echo 'En attente';
                                    } elseif ($commande['statut'] == 'livree') {
                                        echo 'Livrée ' . ($livraison && $livraison['heure_livraison'] ? date('d/m/Y H:i', strtotime($livraison['heure_livraison'])) : '');
                                    } else {
                                        echo 'Annulée';
                                    }
                                    ?>
                                </div>
                            </div>
                            <?php if ($commande['statut'] == 'livree'): ?>
                                <span class="badge bg-success rounded-pill">✓</span>
                            <?php elseif (in_array($commande['statut'], ['nouvelle', 'en_preparation', 'prete', 'en_livraison'])): ?>
                                <span class="badge bg-warning rounded-pill">⌛</span>
                            <?php else: ?>
                                <span class="badge bg-danger rounded-pill">✗</span>
                            <?php endif; ?>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
        
        <?php if ($livraison): ?>
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Informations de livraison</h5>
                </div>
                <div class="card-body">
                    <p>
                        <strong>Livreur:</strong> <?php echo $livraison['nom'] . ' ' . $livraison['prenom']; ?><br>
                        <?php if (!empty($livraison['telephone'])): ?>
                            <strong>Téléphone:</strong> <?php echo $livraison['telephone']; ?><br>
                        <?php endif; ?>
                        <strong>Statut:</strong> 
                        <?php
                        switch ($livraison['statut']) {
                            case 'assignee':
                                echo 'Assignée au livreur';
                                break;
                            case 'en_cours':
                                echo 'En cours de livraison';
                                break;
                            case 'livree':
                                echo 'Livrée';
                                break;
                            case 'probleme':
                                echo 'Problème de livraison';
                                break;
                        }
                        ?>
                    </p>
                    
                    <?php if ($livraison['heure_depart']): ?>
                        <p><strong>Heure de départ:</strong> <?php echo date('H:i', strtotime($livraison['heure_depart'])); ?></p>
                    <?php endif; ?>
                    
                    <?php if ($livraison['heure_livraison']): ?>
                        <p><strong>Heure de livraison:</strong> <?php echo date('H:i', strtotime($livraison['heure_livraison'])); ?></p>
                    <?php endif; ?>
                    
                    <?php if (!empty($livraison['commentaires'])): ?>
                        <div class="alert alert-info">
                            <strong>Commentaires:</strong><br>
                            <?php echo nl2br($livraison['commentaires']); ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
        
        <?php if (in_array($commande['statut'], ['nouvelle', 'en_preparation', 'prete'])): ?>
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Actions</h5>
                </div>
                <div class="card-body">
                    <div class="list-group">
                        <?php if ($commande['statut'] == 'nouvelle'): ?>
                            <a href="maj_commande.php?id=<?php echo $commande_id; ?>&statut=en_preparation" class="list-group-item list-group-item-action">
                                <div class="d-flex w-100 justify-content-between">
                                    <h6 class="mb-1">Commencer la préparation</h6>
                                    <i class="fas fa-fire"></i>
                                </div>
                                <small class="text-muted">Marquer la commande comme étant en cours de préparation</small>
                            </a>
                        <?php elseif ($commande['statut'] == 'en_preparation'): ?>
                            <a href="maj_commande.php?id=<?php echo $commande_id; ?>&statut=prete" class="list-group-item list-group-item-action">
                                <div class="d-flex w-100 justify-content-between">
                                    <h6 class="mb-1">Marquer comme prête</h6>
                                    <i class="fas fa-check"></i>
                                </div>
                                <small class="text-muted">Indiquer que la commande est prête pour la livraison</small>
                            </a>
                        <?php endif; ?>
                        
                        <?php if (in_array($commande['statut'], ['nouvelle', 'en_preparation'])): ?>
                            <a href="maj_commande.php?id=<?php echo $commande_id; ?>&statut=annulee" class="list-group-item list-group-item-action text-danger">
                                <div class="d-flex w-100 justify-content-between">
                                    <h6 class="mb-1">Annuler la commande</h6>
                                    <i class="fas fa-times"></i>
                                </div>
                                <small class="text-muted">Annuler définitivement cette commande</small>
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include_once '../includes/footer.php'; ?>
