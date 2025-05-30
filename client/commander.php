
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

$message = "";
$success = false;

// Traiter la commande
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Vérifier si le panier n'est pas vide
    if (isset($_POST['panier']) && !empty($_POST['panier'])) {
        $panier = json_decode($_POST['panier'], true);
        $mode_paiement = clean_input($_POST['mode_paiement']);
        $adresse_livraison = clean_input($_POST['adresse_livraison']);
        $montant_total = 0;
        
        // Calculer le montant total
        foreach ($panier as $item) {
            $montant_total += $item['prix'] * $item['quantite'];
        }
        
        // Insérer la commande
        $query = "INSERT INTO commandes (client_id, montant_total, mode_paiement, adresse_livraison) 
                  VALUES (:client_id, :montant_total, :mode_paiement, :adresse_livraison)";
        $stmt = $db->prepare($query);
        $stmt->bindParam(":client_id", $user_id);
        $stmt->bindParam(":montant_total", $montant_total);
        $stmt->bindParam(":mode_paiement", $mode_paiement);
        $stmt->bindParam(":adresse_livraison", $adresse_livraison);
        
        if ($stmt->execute()) {
            $commande_id = $db->lastInsertId();
            
            // Insérer les détails de la commande
            $query = "INSERT INTO commande_details (commande_id, plat_id, quantite, prix_unitaire) 
                      VALUES (:commande_id, :plat_id, :quantite, :prix_unitaire)";
            $stmt = $db->prepare($query);
            
            foreach ($panier as $item) {
                $stmt->bindParam(":commande_id", $commande_id);
                $stmt->bindParam(":plat_id", $item['id']);
                $stmt->bindParam(":quantite", $item['quantite']);
                $stmt->bindParam(":prix_unitaire", $item['prix']);
                $stmt->execute();
            }
            
            $message = "Votre commande a été passée avec succès! Numéro de commande: " . $commande_id;
            $success = true;
        } else {
            $message = "Une erreur est survenue lors de la création de votre commande.";
        }
    } else {
        $message = "Votre panier est vide.";
    }
}

// Récupérer les informations du client
$query = "SELECT * FROM users WHERE id = :id";
$stmt = $db->prepare($query);
$stmt->bindParam(":id", $user_id);
$stmt->execute();
$client = $stmt->fetch(PDO::FETCH_ASSOC);

// Afficher la page de commande
include_once '../includes/header.php';
?>

<div class="row">
    <div class="col-lg-8 mx-auto">
        <h1 class="mb-4">Finaliser votre commande</h1>
        
        <?php if (!empty($message)): ?>
            <div class="alert alert-<?php echo $success ? 'success' : 'danger'; ?> mb-4">
                <?php echo $message; ?>
                <?php if ($success): ?>
                    <div class="mt-3">
                        <a href="commandes.php" class="btn btn-sm btn-primary">Voir mes commandes</a>
                        <a href="menu.php" class="btn btn-sm btn-outline-primary ms-2">Retour au menu</a>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
        
        <?php if (!$success): ?>
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Récapitulatif de votre panier</h5>
                </div>
                <div class="card-body">
                    <div id="panier-recap"></div>
                    <div class="alert alert-info mb-0" id="panier-vide">
                        <i class="fas fa-info-circle me-2"></i>Votre panier est vide.
                        <div class="mt-2">
                            <a href="menu.php" class="btn btn-primary btn-sm">Voir le menu</a>
                        </div>
                    </div>
                </div>
            </div>
            
            <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" id="commande-form">
                <input type="hidden" name="panier" id="panier-data">
                
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Adresse de livraison</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label for="adresse_livraison" class="form-label">Adresse complète</label>
                            <textarea class="form-control" id="adresse_livraison" name="adresse_livraison" rows="3" required><?php echo $client['adresse']; ?></textarea>
                        </div>
                    </div>
                </div>
                
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Mode de paiement</h5>
                    </div>
                    <div class="card-body">
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="radio" name="mode_paiement" id="paiement_carte" value="carte" checked>
                            <label class="form-check-label" for="paiement_carte">
                                <i class="fas fa-credit-card me-2"></i>Carte bancaire (à la livraison)
                            </label>
                        </div>
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="radio" name="mode_paiement" id="paiement_especes" value="especes">
                            <label class="form-check-label" for="paiement_especes">
                                <i class="fas fa-money-bill-wave me-2"></i>Espèces (à la livraison)
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="mode_paiement" id="paiement_en_ligne" value="en_ligne">
                            <label class="form-check-label" for="paiement_en_ligne">
                                <i class="fas fa-globe me-2"></i>Paiement en ligne (prochainement)
                            </label>
                        </div>
                    </div>
                </div>
                
                <div class="d-grid gap-2">
                    <button type="submit" class="btn btn-primary btn-lg" id="submit-btn" disabled>
                        <i class="fas fa-check-circle me-2"></i>Confirmer ma commande
                    </button>
                </div>
            </form>
        <?php endif; ?>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const cart = JSON.parse(localStorage.getItem('restaurantCart')) || [];
    const panierRecap = document.getElementById('panier-recap');
    const panierVide = document.getElementById('panier-vide');
    const panierData = document.getElementById('panier-data');
    const submitBtn = document.getElementById('submit-btn');
    
    // Mettre à jour l'affichage du panier
    if (cart.length > 0) {
        panierVide.style.display = 'none';
        submitBtn.disabled = false;
        
        let totalPrice = 0;
        let recapHTML = '<div class="table-responsive"><table class="table table-sm">';
        recapHTML += '<thead><tr><th>Plat</th><th>Quantité</th><th>Prix unitaire</th><th>Total</th></tr></thead><tbody>';
        
        cart.forEach(item => {
            const itemTotal = item.prix * item.quantite;
            totalPrice += itemTotal;
            
            recapHTML += `<tr>
                <td>${item.nom}</td>
                <td>${item.quantite}</td>
                <td>${item.prix.toFixed(2).replace('.', ',')} €</td>
                <td>${itemTotal.toFixed(2).replace('.', ',')} €</td>
            </tr>`;
        });
        
        recapHTML += '</tbody></table></div>';
        recapHTML += `<div class="text-end"><h5>Total: <span class="text-primary">${totalPrice.toFixed(2).replace('.', ',')} €</span></h5></div>`;
        
        panierRecap.innerHTML = recapHTML;
        panierData.value = JSON.stringify(cart);
    } else {
        panierRecap.style.display = 'none';
        submitBtn.disabled = true;
    }
    
    // Soumettre la commande
    document.getElementById('commande-form').addEventListener('submit', function(e) {
        if (cart.length === 0) {
            e.preventDefault();
            alert('Votre panier est vide.');
        } else {
            // Vider le panier après la commande
            localStorage.removeItem('restaurantCart');
        }
    });
});
</script>

<?php include_once '../includes/footer.php'; ?>
