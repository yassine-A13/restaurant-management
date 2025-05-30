
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

// Filtrage des livraisons
$filtre = isset($_GET['filtre']) ? $_GET['filtre'] : 'toutes';
$condition = "";

switch ($filtre) {
    case 'assignees':
        $condition = "AND l.statut = 'assignee' AND c.statut = 'prete'";
        break;
    case 'en_cours':
        $condition = "AND l.statut = 'en_cours' AND c.statut = 'en_livraison'";
        break;
    case 'livrees':
        $condition = "AND l.statut = 'livree'";
        break;
    case 'probleme':
        $condition = "AND l.statut = 'probleme'";
        break;
}

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limite = 10;
$debut = ($page - 1) * $limite;

// Requête pour compter le nombre total de livraisons avec le filtre
$query = "SELECT COUNT(*) as total FROM livraisons l
          JOIN commandes c ON l.commande_id = c.id
          WHERE l.livreur_id = :livreur_id $condition";
$stmt = $db->prepare($query);
$stmt->bindParam(":livreur_id", $user_id);
$stmt->execute();
$total_livraisons = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
$total_pages = ceil($total_livraisons / $limite);

// Récupérer les livraisons avec pagination
$query = "SELECT l.*, c.adresse_livraison, c.montant_total, c.date_commande, u.nom, u.prenom, u.telephone
          FROM livraisons l
          JOIN commandes c ON l.commande_id = c.id
          JOIN users u ON c.client_id = u.id
          WHERE l.livreur_id = :livreur_id $condition
          ORDER BY 
            CASE 
                WHEN l.statut = 'assignee' THEN 1
                WHEN l.statut = 'en_cours' THEN 2
                ELSE 3
            END, 
            c.date_commande DESC
          LIMIT :debut, :limite";
$stmt = $db->prepare($query);
$stmt->bindParam(":livreur_id", $user_id);
$stmt->bindParam(":debut", $debut, PDO::PARAM_INT);
$stmt->bindParam(":limite", $limite, PDO::PARAM_INT);
$stmt->execute();
$livraisons = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Afficher la page
include_once '../includes/header.php';
?>

<div class="container-fluid px-4 py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="mb-0">Mes livraisons</h2>
    </div>

    <div class="card mb-4">
        <div class="card-header">
            <ul class="nav nav-tabs card-header-tabs">
                <li class="nav-item">
                    <a class="nav-link <?php echo $filtre == 'toutes' ? 'active' : ''; ?>" href="livraisons.php">
                        Toutes
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $filtre == 'assignees' ? 'active' : ''; ?>" href="livraisons.php?filtre=assignees">
                        À récupérer
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $filtre == 'en_cours' ? 'active' : ''; ?>" href="livraisons.php?filtre=en_cours">
                        En cours
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $filtre == 'livrees' ? 'active' : ''; ?>" href="livraisons.php?filtre=livrees">
                        Livrées
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $filtre == 'probleme' ? 'active' : ''; ?>" href="livraisons.php?filtre=probleme">
                        Problèmes
                    </a>
                </li>
            </ul>
        </div>
        <div class="card-body">
            <?php if (count($livraisons) > 0): ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Client</th>
                                <th>Adresse</th>
                                <th>Date</th>
                                <th>Montant</th>
                                <th>Statut</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($livraisons as $livraison): ?>
                                <tr>
                                    <td>#<?php echo $livraison['commande_id']; ?></td>
                                    <td>
                                        <?php echo $livraison['nom'] . ' ' . $livraison['prenom']; ?><br>
                                        <small class="text-muted"><?php echo $livraison['telephone']; ?></small>
                                    </td>
                                    <td><?php echo $livraison['adresse_livraison']; ?></td>
                                    <td><?php echo date('d/m/Y H:i', strtotime($livraison['date_commande'])); ?></td>
                                    <td><?php echo $livraison['montant_total']." DH"; ?></td>
                                    <td>
                                        <?php if ($livraison['statut'] == 'assignee'): ?>
                                            <span class="badge bg-info">À récupérer</span>
                                        <?php elseif ($livraison['statut'] == 'en_cours'): ?>
                                            <span class="badge bg-warning">En cours</span>
                                        <?php elseif ($livraison['statut'] == 'livree'): ?>
                                            <span class="badge bg-success">Livrée</span>
                                        <?php else: ?>
                                            <span class="badge bg-danger">Problème</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($livraison['statut'] == 'assignee'): ?>
                                            <a href="maj_livraison.php?id=<?php echo $livraison['id']; ?>&action=start" class="btn btn-sm btn-primary">
                                                <i class="fas fa-motorcycle me-1"></i> Commencer
                                            </a>
                                        <?php elseif ($livraison['statut'] == 'en_cours'): ?>
                                            <a href="maj_livraison.php?id=<?php echo $livraison['id']; ?>&action=complete" class="btn btn-sm btn-success">
                                                <i class="fas fa-check-circle me-1"></i> Livrer
                                            </a>
                                            <a href="maj_livraison.php?id=<?php echo $livraison['id']; ?>&action=problem" class="btn btn-sm btn-danger">
                                                <i class="fas fa-exclamation-triangle me-1"></i> Problème
                                            </a>
                                        <?php endif; ?>
                                        <a href="voir_livraison.php?id=<?php echo $livraison['id']; ?>" class="btn btn-sm btn-secondary">
                                            <i class="fas fa-eye me-1"></i> Détails
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <?php if ($total_pages > 1): ?>
                    <nav aria-label="Navigation des livraisons" class="mt-4">
                        <ul class="pagination justify-content-center">
                            <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $page - 1; ?>&filtre=<?php echo $filtre; ?>">Précédent</a>
                            </li>
                            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                <li class="page-item <?php echo $page == $i ? 'active' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $i; ?>&filtre=<?php echo $filtre; ?>"><?php echo $i; ?></a>
                                </li>
                            <?php endfor; ?>
                            <li class="page-item <?php echo $page >= $total_pages ? 'disabled' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $page + 1; ?>&filtre=<?php echo $filtre; ?>">Suivant</a>
                            </li>
                        </ul>
                    </nav>
                <?php endif; ?>
            <?php else: ?>
                <div class="text-center py-4">
                    <i class="fas fa-motorcycle fa-3x mb-3 text-muted"></i>
                    <p>Aucune livraison <?php echo $filtre != 'toutes' ? 'dans cette catégorie' : ''; ?> pour le moment.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
<style>
    /* Couleur principale YUMMY */
:root {
    --yummy-red: #ff4d4d;
    --yummy-light-red: #ffe6e6;
    --yummy-hover: #ff6666;
}

/* Table hover */
.table-hover tbody tr:hover {
    background-color: var(--yummy-light-red);
}

/* Badges avec icônes et couleurs personnalisées */
.badge {
    font-size: 0.85rem;
    padding: 0.4em 0.7em;
    border-radius: 1rem;
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
}

.badge.bg-info {
    background-color: var(--yummy-red);
    color: white;
}

.badge.bg-warning {
    background-color: #ffa500;
    color: white;
}

.badge.bg-success {
    background-color: #28a745;
    color: white;
}

.badge.bg-danger {
    background-color: #dc3545;
    color: white;
}

/* Boutons adaptés */
.btn-primary {
    background-color: var(--yummy-red);
    border-color: var(--yummy-red);
}
.btn-primary:hover {
    background-color: var(--yummy-hover);
    border-color: var(--yummy-hover);
}

.btn-danger:hover {
    background-color: #c82333;
    border-color: #bd2130;
}

.btn-success:hover {
    background-color: #218838;
    border-color: #1e7e34;
}

/* Pagination */
.pagination .page-link {
    border-radius: 0.3rem;
    color: var(--yummy-red);
    border: 1px solid #ffcccc;
}

.pagination .page-item.active .page-link {
    background-color: var(--yummy-red);
    border-color: var(--yummy-red);
    color: white;
}

/* Carte améliorée */
.card {
    border: none;
    box-shadow: 0 0 10px rgba(255, 77, 77, 0.1);
    border-radius: 1rem;
}

.card-header {
    background-color: var(--yummy-light-red);
    border-bottom: 1px solid #ffc2c2;
}

</style>
<?php include_once '../includes/footer.php'; ?>