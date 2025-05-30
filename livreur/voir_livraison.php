
<?php
session_start();
include_once '../config/database.php';
include_once '../includes/functions.php';

// Vérifier si l'utilisateur est connecté et est un livreur
if (!check_role('livreur')) {
    header("Location: ../login.php");
    exit();
}

// Vérifier l'ID de livraison
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: livraisons.php");
    exit();
}

$livraison_id = $_GET['id'];
$user_id = $_SESSION['user_id'];

$database = new Database();
$db = $database->getConnection();

// Récupérer les détails de la livraison
$query = "SELECT l.*, c.*, u.nom as client_nom, u.prenom as client_prenom, u.email as client_email, u.telephone as client_telephone
          FROM livraisons l
          JOIN commandes c ON l.commande_id = c.id
          JOIN users u ON c.client_id = u.id
          WHERE l.id = :id AND l.livreur_id = :livreur_id";
$stmt = $db->prepare($query);
$stmt->bindParam(":id", $livraison_id);
$stmt->bindParam(":livreur_id", $user_id);
$stmt->execute();

if ($stmt->rowCount() == 0) {
    header("Location: livraisons.php");
    exit();
}

$livraison = $stmt->fetch(PDO::FETCH_ASSOC);

// Récupérer les détails des produits de la commande
$query = "SELECT d.*, p.nom as plat_nom, p.image as plat_image
          FROM commande_details d
          JOIN plats p ON d.plat_id = p.id
          WHERE d.commande_id = :commande_id";
$stmt = $db->prepare($query);
$stmt->bindParam(":commande_id", $livraison['commande_id']);
$stmt->execute();
$produits = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Afficher la page
include_once '../includes/header.php';
?>

<div class="container-fluid px-4 py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="mb-0">Détails de la livraison #<?php echo $livraison['id']; ?></h2>
        <a href="livraisons.php" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left me-1"></i> Retour
        </a>
    </div>

    <?php if (isset($_SESSION['message'])): ?>
        <div class="alert alert-<?php echo $_SESSION['message_type'] ?? 'info'; ?> alert-dismissible fade show" role="alert">
            <?php echo $_SESSION['message']; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php unset($_SESSION['message'], $_SESSION['message_type']); ?>
    <?php endif; ?>

    <div class="row">
        <div class="col-lg-8">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Informations de la commande #<?php echo $livraison['commande_id']; ?></h5>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <strong>Date de commande:</strong> <?php echo date('d/m/Y H:i', strtotime($livraison['date_commande'])); ?>
                        </div>
                        <div class="col-md-6">
                            <strong>Montant:</strong> <?php echo $livraison['montant_total']." DH"; ?>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <strong>Mode de paiement:</strong> <?php echo get_mode_paiement($livraison['mode_paiement']); ?>
                        </div>
                        <div class="col-md-6">
                            <strong>Statut:</strong>
                            <?php 
                                $statut_class = '';
                                switch ($livraison['statut']) {
                                    case 'nouvelle': $statut_class = 'bg-info'; break;
                                    case 'en_preparation': $statut_class = 'bg-warning'; break;
                                    case 'prete': $statut_class = 'bg-primary'; break;
                                    case 'en_livraison': $statut_class = 'bg-secondary'; break;
                                    case 'livree': $statut_class = 'bg-success'; break;
                                    case 'annulee': $statut_class = 'bg-danger'; break;
                                }
                            ?>
                            <span class="badge <?php echo $statut_class; ?>"><?php echo get_statut_commande($livraison['statut']); ?></span>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-12">
                            <strong>Adresse de livraison:</strong><br>
                            <?php echo nl2br($livraison['adresse_livraison']); ?>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Produits commandés</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Produit</th>
                                    <th>Quantité</th>
                                    <th>Prix unitaire</th>
                                    <th>Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($produits as $produit): ?>
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <?php if (!empty($produit['plat_image'])): ?>
                                                    <img src="../assets/images/<?php echo $produit['plat_image']; ?>" alt="<?php echo $produit['plat_nom']; ?>" class="me-2" style="width: 40px; height: 40px; object-fit: cover;">
                                                <?php endif; ?>
                                                <span><?php echo $produit['plat_nom']; ?></span>
                                            </div>
                                        </td>
                                        <td><?php echo $produit['quantite']; ?></td>
                                        <td><?php echo $produit['prix_unitaire']." DH"; ?></td>
                                        <td><?php echo $produit['prix_unitaire'] * $produit['quantite']." DH"; ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="3" class="text-end"><strong>Total:</strong></td>
                                    <td><strong><?php echo $livraison['montant_total']."DH"; ?></strong></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Informations du client</h5>
                </div>
                <div class="card-body">
                    <p><strong>Nom:</strong> <?php echo $livraison['client_nom'] . ' ' . $livraison['client_prenom']; ?></p>
                    <p><strong>Email:</strong> <?php echo $livraison['client_email']; ?></p>
                    <p><strong>Téléphone:</strong> <?php echo $livraison['client_telephone']; ?></p>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Statut de la livraison</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <strong>Statut actuel:</strong>
                        <?php if ($livraison['statut'] == 'assignee'): ?>
                            <span class="badge bg-info">À récupérer</span>
                        <?php elseif ($livraison['statut'] == 'en_cours'): ?>
                            <span class="badge bg-warning">En cours</span>
                        <?php elseif ($livraison['statut'] == 'livree'): ?>
                            <span class="badge bg-success">Livrée</span>
                        <?php elseif ($livraison['statut'] == 'probleme'): ?>
                            <span class="badge bg-danger">Problème</span>
                        <?php endif; ?>
                    </div>

                    <?php if ($livraison['statut'] == 'assignee'): ?>
                        <div class="d-grid gap-2">
                            <a href="maj_livraison.php?id=<?php echo $livraison['id']; ?>&action=start" class="btn btn-primary">
                                <i class="fas fa-motorcycle me-1"></i> Commencer la livraison
                            </a>
                        </div>
                    <?php elseif ($livraison['statut'] == 'en_cours'): ?>
                        <div class="d-grid gap-2">
                            <a href="maj_livraison.php?id=<?php echo $livraison['id']; ?>&action=complete" class="btn btn-success mb-2">
                                <i class="fas fa-check-circle me-1"></i> Marquer comme livrée
                            </a>
                            <a href="maj_livraison.php?id=<?php echo $livraison['id']; ?>&action=problem" class="btn btn-danger">
                                <i class="fas fa-exclamation-triangle me-1"></i> Signaler un problème
                            </a>
                        </div>
                    <?php elseif ($livraison['statut'] == 'probleme' && !empty($livraison['commentaire'])): ?>
                        <div class="alert alert-danger">
                            <h6 class="alert-heading">Problème signalé:</h6>
                            <p class="mb-0"><?php echo nl2br($livraison['commentaire']); ?></p>
                        </div>
                    <?php endif; ?>

                    <hr>

                    <div class="timeline small">
                        <?php if (!empty($livraison['date_debut'])): ?>
                            <div class="timeline-item">
                                <div class="timeline-badge bg-info"><i class="fas fa-motorcycle"></i></div>
                                <div class="timeline-content">
                                    <h6 class="mb-0">Livraison démarrée</h6>
                                    <small><?php echo date('d/m/Y H:i', strtotime($livraison['date_debut'])); ?></small>
                                </div>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($livraison['date_fin'])): ?>
                            <div class="timeline-item">
                                <div class="timeline-badge bg-success"><i class="fas fa-check"></i></div>
                                <div class="timeline-content">
                                    <h6 class="mb-0">Livraison terminée</h6>
                                    <small><?php echo date('d/m/Y H:i', strtotime($livraison['date_fin'])); ?></small>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<style>
    :root {
        --yummy-red: #f65a5a;
        --yummy-red-dark: #d94141;
        --yummy-light: #fff5f5;
    }

    .bg-yummy {
        background-color: var(--yummy-red) !important;
        color: white !important;
    }

    .btn-yummy {
        background-color: var(--yummy-red);
        color: white;
        border: none;
    }

    .btn-yummy:hover {
        background-color: var(--yummy-red-dark);
    }

    .badge-yummy {
        background-color: var(--yummy-red);
        color: white;
    }

    .card-header.bg-yummy {
        background-color: var(--yummy-red);
        color: white;
    }

    .timeline-badge.bg-yummy {
        background-color: var(--yummy-red);
    }

    .btn-outline-yummy {
        border-color: var(--yummy-red);
        color: var(--yummy-red);
    }

    .btn-outline-yummy:hover {
        background-color: var(--yummy-red);
        color: white;
    }
</style>


<?php include_once '../includes/footer.php'; ?>