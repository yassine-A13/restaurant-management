
<?php
session_start();
include_once '../config/database.php';
include_once '../includes/functions.php';

// Vérifier si l'utilisateur est connecté et est un client
if (!check_role('client')) {
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$database = new Database();
$db = $database->getConnection();

// Récupérer toutes les commandes du client
$query = "SELECT * FROM commandes WHERE client_id = :client_id ORDER BY date_commande DESC";
$stmt = $db->prepare($query);
$stmt->bindParam(":client_id", $user_id);
$stmt->execute();
$commandes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Afficher la page des commandes
include_once '../includes/header.php';
?>

<h1 class="mb-4">Mes commandes</h1>

<?php if (count($commandes) > 0): ?>
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>N° Commande</th>
                            <th>Date</th>
                            <th>Montant</th>
                            <th>Statut</th>
                            <th>Paiement</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($commandes as $commande): ?>
                            <tr>
                                <td>#<?php echo $commande['id']; ?></td>
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
                                <td><?php echo get_mode_paiement($commande['mode_paiement']); ?></td>
                                <td>
                                    <button type="button" class="btn btn-sm btn-outline-info me-1" 
                                            data-bs-toggle="modal" data-bs-target="#detailsModal<?php echo $commande['id']; ?>">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    
                                    <?php if ($commande['statut'] === 'nouvelle'): ?>
                                        <button type="button" class="btn btn-sm btn-outline-danger"
                                                data-bs-toggle="modal" data-bs-target="#cancelModal<?php echo $commande['id']; ?>">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            
                            <!-- Modal Détails -->
                            <div class="modal fade" id="detailsModal<?php echo $commande['id']; ?>" tabindex="-1">
                                <div class="modal-dialog modal-lg">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title">Détails de la commande #<?php echo $commande['id']; ?></h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
                                        <div class="modal-body">
                                            <div class="row mb-3">
                                                <div class="col-md-6">
                                                    <h6>Informations de la commande</h6>
                                                    <p>
                                                        <strong>Date:</strong> <?php echo date('d/m/Y H:i', strtotime($commande['date_commande'])); ?><br>
                                                        <strong>Statut:</strong> <?php echo get_statut_commande($commande['statut']); ?><br>
                                                        <strong>Paiement:</strong> <?php echo get_mode_paiement($commande['mode_paiement']); ?>
                                                    </p>
                                                </div>
                                                <div class="col-md-6">
                                                    <h6>Adresse de livraison</h6>
                                                    <p><?php echo nl2br($commande['adresse_livraison']); ?></p>
                                                </div>
                                            </div>
                                            
                                            <h6>Plats commandés</h6>
                                            <?php
                                            // Récupérer les détails de la commande
                                            $query = "SELECT cd.*, p.nom FROM commande_details cd 
                                                      JOIN plats p ON cd.plat_id = p.id 
                                                      WHERE cd.commande_id = :commande_id";
                                            $stmt = $db->prepare($query);
                                            $stmt->bindParam(":commande_id", $commande['id']);
                                            $stmt->execute();
                                            $details = $stmt->fetchAll(PDO::FETCH_ASSOC);
                                            ?>
                                            
                                            <div class="table-responsive">
                                                <table class="table table-sm">
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
                                                                <td><?php echo $detail['prix_unitaire'] ." DH"; ?></td>
                                                                <td><?php echo $detail['prix_unitaire'] * $detail['quantite']." DH"; ?></td>
                                                            </tr>
                                                        <?php endforeach; ?>
                                                    </tbody>
                                                    <tfoot>
                                                        <tr>
                                                            <th colspan="3" class="text-end">Total:</th>
                                                            <th><?php echo $commande['montant_total']." DH"; ?></th>
                                                        </tr>
                                                    </tfoot>
                                                </table>
                                            </div>
                                            
                                            <?php
                                            // Récupérer les informations de livraison
                                            $query = "SELECT l.*, u.nom, u.prenom FROM livraisons l 
                                                      LEFT JOIN users u ON l.livreur_id = u.id 
                                                      WHERE l.commande_id = :commande_id";
                                            $stmt = $db->prepare($query);
                                            $stmt->bindParam(":commande_id", $commande['id']);
                                            $stmt->execute();
                                            $livraison = $stmt->fetch(PDO::FETCH_ASSOC);
                                            
                                            if ($livraison): ?>
                                                <div class="mt-4">
                                                    <h6>Informations de livraison</h6>
                                                    <div class="alert alert-info">
                                                        <p class="mb-1">
                                                            <strong>Livreur:</strong> <?php echo $livraison['nom'] . ' ' . $livraison['prenom']; ?>
                                                        </p>
                                                        <p class="mb-1">
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
                                                            <p class="mb-1">
                                                                <strong>Heure de départ:</strong> <?php echo date('H:i', strtotime($livraison['heure_depart'])); ?>
                                                            </p>
                                                        <?php endif; ?>
                                                        <?php if ($livraison['heure_livraison']): ?>
                                                            <p class="mb-1">
                                                                <strong>Heure de livraison:</strong> <?php echo date('H:i', strtotime($livraison['heure_livraison'])); ?>
                                                            </p>
                                                        <?php endif; ?>
                                                        <?php if ($livraison['commentaires']): ?>
                                                            <p class="mb-0">
                                                                <strong>Commentaires:</strong> <?php echo $livraison['commentaires']; ?>
                                                            </p>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Modal Annulation -->
                            <?php if ($commande['statut'] === 'nouvelle'): ?>
                                <div class="modal fade" id="cancelModal<?php echo $commande['id']; ?>" tabindex="-1">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title">Annuler la commande #<?php echo $commande['id']; ?></h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                            </div>
                                            <div class="modal-body">
                                                <p>Êtes-vous sûr de vouloir annuler cette commande ? Cette action est irréversible.</p>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                                                <a href="annuler_commande.php?id=<?php echo $commande['id']; ?>" class="btn btn-danger">Confirmer l'annulation</a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
<?php else: ?>
    <div class="card">
        <div class="card-body text-center py-5">
            <i class="fas fa-shopping-bag fa-3x text-muted mb-3"></i>
            <h4>Vous n'avez pas encore passé de commande</h4>
            <p class="mb-4">Découvrez notre délicieux menu et passez votre première commande dès maintenant.</p>
            <a href="menu.php" class="btn btn-primary">Voir le menu</a>
        </div>
    </div>
<?php endif; ?>

<?php include_once '../includes/footer.php'; ?>
