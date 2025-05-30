
<?php
session_start();
include_once '../config/database.php';
include_once '../includes/functions.php';

// Vérifier si l'utilisateur est connecté et est un livreur
if (!check_role('livreur')) {
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$database = new Database();
$db = $database->getConnection();

// Récupérer les commandes prêtes à être livrées
$query = "SELECT c.*, u.nom as client_nom, u.prenom as client_prenom, u.telephone as client_telephone 
          FROM commandes c 
          JOIN users u ON c.client_id = u.id 
          WHERE c.statut = 'prete' 
          ORDER BY c.date_commande ASC";
$stmt = $db->prepare($query);
$stmt->execute();
$commandes_pretes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Récupérer les commandes en cours de livraison par ce livreur
$query = "SELECT c.*, u.nom as client_nom, u.prenom as client_prenom, u.telephone as client_telephone, 
          l.id as livraison_id, l.heure_depart, l.statut as statut_livraison 
          FROM commandes c 
          JOIN users u ON c.client_id = u.id 
          JOIN livraisons l ON c.id = l.commande_id 
          WHERE c.statut = 'en_livraison' AND l.livreur_id = :livreur_id 
          ORDER BY l.heure_depart ASC";
$stmt = $db->prepare($query);
$stmt->bindParam(":livreur_id", $user_id);
$stmt->execute();
$commandes_en_livraison = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Récupérer l'historique des livraisons effectuées par ce livreur
$query = "SELECT c.*, u.nom as client_nom, u.prenom as client_prenom, l.heure_livraison 
          FROM commandes c 
          JOIN users u ON c.client_id = u.id 
          JOIN livraisons l ON c.id = l.commande_id 
          WHERE c.statut = 'livree' AND l.livreur_id = :livreur_id 
          ORDER BY l.heure_livraison DESC LIMIT 10";
$stmt = $db->prepare($query);
$stmt->bindParam(":livreur_id", $user_id);
$stmt->execute();
$livraisons_effectuees = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Afficher la page du livreur
include_once '../includes/header.php';
?>

<div class="p-4 bg-gradient-primary text-white rounded-3 mb-4">
    <h1 class="display-6">Espace Livreur</h1>
    <p class="lead">Bienvenue, <?php echo $_SESSION['prenom']; ?>. Gérez vos livraisons ici.</p>
</div>

<div class="row mb-4">
    <div class="col-lg-12">
        <div class="card">
            <div class="card-header bg-gradient-primary text-white">
                <h5 class="mb-0"><i class="fas fa-motorcycle me-2"></i>Commandes prêtes à livrer</h5>
            </div>
            <div class="card-body">
                <?php if (count($commandes_pretes) > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>N° Commande</th>
                                    <th>Client</th>
                                    <th>Adresse</th>
                                    <th>Date de commande</th>
                                    <th>Montant</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($commandes_pretes as $commande): ?>
                                    <tr>
                                        <td>#<?php echo $commande['id']; ?></td>
                                        <td>
                                            <?php echo $commande['client_nom'] . ' ' . $commande['client_prenom']; ?><br>
                                            <small class="text-muted"><?php echo $commande['client_telephone']; ?></small>
                                        </td>
                                        <td><?php echo nl2br($commande['adresse_livraison']); ?></td>
                                        <td><?php echo date('d/m/Y H:i', strtotime($commande['date_commande'])); ?></td>
                                        <td><?php echo $commande['montant_total']."DH"; ?></td>
                                        <td>
                                            <a href="prendre_commande.php?id=<?php echo $commande['id']; ?>" 
                                                class="btn btn-sm btn-primary"
                                                onclick="return confirmAction('Voulez-vous vraiment prendre cette commande en charge?');">
                                                <i class="fas fa-check me-1"></i>Prendre en charge
                                            </a>
                                            <button type="button" class="btn btn-sm btn-info ms-1" 
                                                    data-bs-toggle="modal" data-bs-target="#detailModal<?php echo $commande['id']; ?>">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                        </td>
                                    </tr>
                                    
                                    <!-- Modal Détails -->
                                    <div class="modal fade" id="detailModal<?php echo $commande['id']; ?>" tabindex="-1">
                                        <div class="modal-dialog modal-lg">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title">Détails de la commande #<?php echo $commande['id']; ?></h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <div class="row mb-3">
                                                        <div class="col-md-6">
                                                            <h6>Client</h6>
                                                            <p>
                                                                <?php echo $commande['client_nom'] . ' ' . $commande['client_prenom']; ?><br>
                                                                <strong>Téléphone:</strong> <?php echo $commande['client_telephone']; ?><br>
                                                            </p>
                                                        </div>
                                                        <div class="col-md-6">
                                                            <h6>Adresse de livraison</h6>
                                                            <p><?php echo nl2br($commande['adresse_livraison']); ?></p>
                                                        </div>
                                                    </div>

                                                    <h6>Détails de la commande</h6>
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
                                                                        <td><?php echo $detail['prix_unitaire']."DH"; ?></td>
                                                                        <td><?php echo $detail['prix_unitaire'] * $detail['quantite']."DH"; ?></td>
                                                                    </tr>
                                                                <?php endforeach; ?>
                                                            </tbody>
                                                            <tfoot>
                                                                <tr>
                                                                    <th colspan="3" class="text-end">Total:</th>
                                                                    <th><?php echo $commande['montant_total']."DH"; ?></th>
                                                                </tr>
                                                            </tfoot>
                                                        </table>
                                                    </div>

                                                    <div class="mt-3">
                                                        <h6>Informations de paiement</h6>
                                                        <p>
                                                            <strong>Mode de paiement:</strong> <?php echo get_mode_paiement($commande['mode_paiement']); ?><br>
                                                        </p>
                                                    </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                                                    <a href="prendre_commande.php?id=<?php echo $commande['id']; ?>" 
                                                    class="btn btn-primary"
                                                    onclick="return confirmAction('Voulez-vous vraiment prendre cette commande en charge?');">
                                                        <i class="fas fa-check me-1"></i>Prendre en charge
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <!-- Fin Modal Détails -->
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="alert alert-info mb-0">
                        <i class="fas fa-info-circle me-2"></i> Aucune commande prête à livrer pour le moment.
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="row mb-4">
    <div class="col-lg-12">
        <div class="card">
            <div class="card-header bg-gradient-primary  text-white">
                <h5 class="mb-0"><i class="fas fa-truck me-2"></i>Mes livraisons en cours</h5>
            </div>
            <div class="card-body">
                <?php if (count($commandes_en_livraison) > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>N° Commande</th>
                                    <th>Client</th>
                                    <th>Adresse</th>
                                    <th>Prise en charge</th>
                                    <th>Montant</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($commandes_en_livraison as $commande): ?>
                                    <tr>
                                        <td>#<?php echo $commande['id']; ?></td>
                                        <td>
                                            <?php echo $commande['client_nom'] . ' ' . $commande['client_prenom']; ?><br>
                                            <small class="text-muted"><?php echo $commande['client_telephone']; ?></small>
                                        </td>
                                        <td><?php echo nl2br($commande['adresse_livraison']); ?></td>
                                        <td><?php echo date('H:i', strtotime($commande['heure_depart'])); ?></td>
                                        <td><?php echo $commande['montant_total']."DH"; ?></td>
                                        <td>
                                            <a href="terminer_livraison.php?id=<?php echo $commande['livraison_id']; ?>" 
                                                class="btn btn-sm btn-success"
                                                onclick="return confirmAction('Confirmez-vous que cette commande a bien été livrée?');">
                                                <i class="fas fa-check-circle me-1"></i>Confirmer livraison
                                            </a>
                                            <a href="probleme_livraison.php?id=<?php echo $commande['livraison_id']; ?>" 
                                                class="btn btn-sm btn-warning ms-1"
                                                onclick="return confirmAction('Signaler un problème avec cette livraison?');">
                                                <i class="fas fa-exclamation-triangle me-1"></i>Problème
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="alert alert-info mb-0  ">
                        <i class="fas fa-info-circle me-2"></i> Vous n'avez aucune livraison en cours.
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-12">
        <div class="card">
            <div class="card-header bg-gradient-primary text-white">
                <h5 class="mb-0"><i class="fas fa-history me-2"></i>Historique de mes livraisons</h5>
            </div>
            <div class="card-body">
                <?php if (count($livraisons_effectuees) > 0): ?>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>N° Commande</th>
                                    <th>Client</th>
                                    <th>Date de livraison</th>
                                    <th>Montant</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($livraisons_effectuees as $livraison): ?>
                                    <tr>
                                        <td>#<?php echo $livraison['id']; ?></td>
                                        <td><?php echo $livraison['client_nom'] . ' ' . $livraison['client_prenom']; ?></td>
                                        <td><?php echo date('d/m/Y H:i', strtotime($livraison['heure_livraison'])); ?></td>
                                        <td><?php echo $livraison['montant_total']." DH"; ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="alert alert-info mb-0">
                        <i class="fas fa-info-circle me-2"></i> Vous n'avez pas encore effectué de livraisons.
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<style>
    :root {
        --yummy-red: #f65a5a; /* Rouge clair */
        --yummy-red-dark: #d94141;
        --yummy-red-soft: #fff0f0;
    }

    .bg-yummy {
        background-color: var(--yummy-red) !important;
        color: white !important;
    }

    .btn-yummy {
        background-color: var(--yummy-red);
        border-color: var(--yummy-red-dark);
        color: white;
    }

    .btn-yummy:hover {
        background-color: var(--yummy-red-dark);
        color: white;
    }

    .text-yummy {
        color: var(--yummy-red);
    }

    .border-yummy {
        border-color: var(--yummy-red);
    }

    .card-header.bg-yummy {
        color: white;
        background-color: var(--yummy-red);
    }
</style>

<?php include_once '../includes/footer.php'; ?>
