
<?php
session_start();
include_once '../config/database.php';
include_once '../includes/functions.php';

// Vérifier si l'utilisateur est connecté et est un client
if (!check_role('client')) {
    header("Location: ../login.php");
    exit();
}

// Récupérer les catégories
$database = new Database();
$db = $database->getConnection();

$query = "SELECT * FROM categories ORDER BY nom";
$stmt = $db->prepare($query);
$stmt->execute();
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Récupérer les plats par catégorie
$plats_par_categorie = [];
foreach ($categories as $categorie) {
    $query = "SELECT * FROM plats WHERE categorie_id = :categorie_id AND disponible = 1 ORDER BY nom";
    $stmt = $db->prepare($query);
    $stmt->bindParam(":categorie_id", $categorie['id']);
    $stmt->execute();
    $plats_par_categorie[$categorie['id']] = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Afficher le menu
include_once '../includes/header.php';
?>
<link rel="stylesheet" href="../assets_home/css/main.css">
<!-- Panier flottant -->
<div class="position-fixed bottom-0 end-0 p-3" style="z-index: 11">
    <button class="btn btn-primary rounded-circle position-relative p-3" data-bs-toggle="offcanvas" data-bs-target="#cartOffcanvas">
        <i class="fas fa-shopping-cart"></i>
        <span id="cart-counter" class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger d-none">0</span>
    </button>
</div>

<!-- Offcanvas pour le panier -->
<div class="offcanvas offcanvas-end" tabindex="-1" id="cartOffcanvas" aria-labelledby="cartOffcanvasLabel">
    <div class="offcanvas-header">
        <h5 class="offcanvas-title" id="cartOffcanvasLabel">
            <i class="fas fa-shopping-cart me-2"></i>Votre panier
        </h5>
        <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body">
        <div id="cart-items">
            <!-- Les éléments du panier seront ajoutés ici via JavaScript -->
            <div class="text-center py-4">
                <i class="fas fa-shopping-basket fa-3x mb-3 text-muted"></i>
                <p>Votre panier est vide</p>
            </div>
        </div>
        <div class="border-top pt-3 mt-3">
            <div class="d-flex justify-content-between mb-2">
                <span>Total:</span>
                <span id="cart-total" class="fw-bold">0,00 DH</span>
            </div>
            <a href="commander.php" class="btn btn-success w-100">
                <i class="fas fa-check-circle me-2"></i>Passer commande
            </a>
        </div>
    </div>
</div>

<h1 class="mb-4">Menu</h1>

<!-- Navigation par catégorie -->
<ul class="nav nav-pills mb-4" id="categories-tab">
    <?php $first = true; ?>
    <?php foreach ($categories as $categorie): ?>
        <li class="nav-item">
            <a class="nav-link <?php echo $first ? 'active' : ''; ?>" href="#categorie-<?php echo $categorie['id']; ?>">
                <?php echo $categorie['nom']; ?>
            </a>
        </li>
        <?php $first = false; ?>
    <?php endforeach; ?>
</ul>

<!-- Contenu des catégories -->
<div class="tab-content">
    <?php $first = true; ?>
    <?php foreach ($categories as $categorie): ?>
        <div class="tab-pane fade <?php echo $first ? 'show active' : ''; ?>" id="categorie-<?php echo $categorie['id']; ?>">
            <h3 class="mb-3"><?php echo $categorie['nom']; ?></h3>
            <?php if (!empty($categorie['description'])): ?>
                <p class="text-muted mb-4"><?php echo $categorie['description']; ?></p>
            <?php endif; ?>
            
            <div class="row row-cols-1 row-cols-md-3 g-4 mb-5">
                <?php if (isset($plats_par_categorie[$categorie['id']])): ?>
                    <?php foreach ($plats_par_categorie[$categorie['id']] as $plat): ?>
                        <div class="col">
                            <div class="card h-100 plat-card">
                                <img src="<?php echo !empty($plat['image']) ? '../assets/images/' . $plat['image'] : '../assets/images/placeholder.jpg'; ?>" 
                                    alt="<?php echo $plat['nom']; ?>"
                                    class="w-full h-44 object-cover rounded-t-lg transition-transform duration-300 hover:scale-105">

                                <div class="card-body">
                                    <h5 class="card-title"><?php echo $plat['nom']; ?></h5>
                                    <p class="card-text small text-muted"><?php echo $plat['description']; ?></p>
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span class="fw-bold text-primary"><?php echo $plat['prix']." DH"; ?></span>
                                        <button class="btn btn-sm btn-outline-primary add-to-cart"
                                                data-id="<?php echo $plat['id']; ?>"
                                                data-nom="<?php echo $plat['nom']; ?>"
                                                data-prix="<?php echo $plat['prix']; ?>">
                                            <i class="fas fa-plus me-1"></i>Ajouter
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="col-12">
                        <div class="alert alert-info">
                            Aucun plat disponible dans cette catégorie pour le moment.
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <?php $first = false; ?>
    <?php endforeach; ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    
    document.querySelectorAll('#categories-tab .nav-link').forEach(function(link) {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            
            
            document.querySelectorAll('.tab-pane').forEach(function(tab) {
                tab.classList.remove('show', 'active');
            });
            
            
            document.querySelectorAll('#categories-tab .nav-link').forEach(function(navLink) {
                navLink.classList.remove('active');
            });
            
            
            const targetId = this.getAttribute('href');
            document.querySelector(targetId).classList.add('show', 'active');
            
            
            this.classList.add('active');
        });
    });
    
    // Mettre à jour le panier
    function updateCart() {
        const cart = JSON.parse(localStorage.getItem('restaurantCart')) || [];
        const cartItemsContainer = document.getElementById('cart-items');
        const cartCounter = document.getElementById('cart-counter');
        const cartTotal = document.getElementById('cart-total');
        
        // Mettre à jour le compteur
        let totalItems = 0;
        let totalPrice = 0;
        
        cart.forEach(item => {
            totalItems += item.quantite;
            totalPrice += item.prix * item.quantite;
        });
        
        cartCounter.textContent = totalItems;
        cartTotal.textContent = totalPrice.toFixed(2).replace('.', ',') + ' DH';
        
        if (totalItems > 0) {
            cartCounter.classList.remove('d-none');
        } else {
            cartCounter.classList.add('d-none');
        }
        
        // Mettre à jour le contenu du panier
        if (cart.length > 0) {
            let cartHTML = '';
            
            cart.forEach((item, index) => {
                cartHTML += `
                    <div class="cart-item mb-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <span>${item.nom}</span>
                            <span>${(item.prix * item.quantite).toFixed(2).replace('.', ',')} DH</span>
                        </div>
                        <div class="d-flex align-items-center mt-1">
                            <div class="input-group input-group-sm" style="width: 100px">
                                <button class="btn btn-outline-secondary cart-decrease" data-index="${index}">-</button>
                                <input type="text" class="form-control text-center" value="${item.quantite}" readonly>
                                <button class="btn btn-outline-secondary cart-increase" data-index="${index}">+</button>
                            </div>
                            <button class="btn btn-sm text-danger ms-2 cart-remove" data-index="${index}">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </div>
                `;
            });
            
            cartHTML += `<div class="text-end mt-2">
                <button class="btn btn-sm btn-outline-danger" id="clear-cart">
                    <i class="fas fa-trash me-1"></i>Vider le panier
                </button>
            </div>`;
            
            cartItemsContainer.innerHTML = cartHTML;
            
            // Ajouter les événements pour les boutons du panier
            document.querySelectorAll('.cart-decrease').forEach(button => {
                button.addEventListener('click', function() {
                    const index = parseInt(this.getAttribute('data-index'));
                    if (cart[index].quantite > 1) {
                        cart[index].quantite -= 1;
                    } else {
                        cart.splice(index, 1);
                    }
                    localStorage.setItem('restaurantCart', JSON.stringify(cart));
                    updateCart();
                });
            });
            
            document.querySelectorAll('.cart-increase').forEach(button => {
                button.addEventListener('click', function() {
                    const index = parseInt(this.getAttribute('data-index'));
                    cart[index].quantite += 1;
                    localStorage.setItem('restaurantCart', JSON.stringify(cart));
                    updateCart();
                });
            });
            
            document.querySelectorAll('.cart-remove').forEach(button => {
                button.addEventListener('click', function() {
                    const index = parseInt(this.getAttribute('data-index'));
                    cart.splice(index, 1);
                    localStorage.setItem('restaurantCart', JSON.stringify(cart));
                    updateCart();
                });
            });
            
            document.getElementById('clear-cart').addEventListener('click', function() {
                localStorage.removeItem('restaurantCart');
                updateCart();
            });
        } else {
            cartItemsContainer.innerHTML = `
                <div class="text-center py-4">
                    <i class="fas fa-shopping-basket fa-3x mb-3 text-muted"></i>
                    <p>Votre panier est vide</p>
                </div>
            `;
        }
    }
    
    // Ajouter au panier
    document.querySelectorAll('.add-to-cart').forEach(button => {
        button.addEventListener('click', function() {
            const id = this.getAttribute('data-id');
            const nom = this.getAttribute('data-nom');
            const prix = parseFloat(this.getAttribute('data-prix'));
            
            let cart = JSON.parse(localStorage.getItem('restaurantCart')) || [];
            
            // Vérifier si le plat est déjà dans le panier
            const existingItem = cart.findIndex(item => item.id === id);
            
            if (existingItem !== -1) {
                cart[existingItem].quantite += 1;
            } else {
                cart.push({
                    id: id,
                    nom: nom,
                    prix: prix,
                    quantite: 1
                });
            }
            
            localStorage.setItem('restaurantCart', JSON.stringify(cart));
            updateCart();
            
            // Afficher une notification
            const toast = document.createElement('div');
            toast.className = 'position-fixed bottom-0 start-50 translate-middle-x mb-4 p-3 bg-success text-white rounded';
            toast.style.zIndex = '1050';
            toast.innerHTML = `<i class="fas fa-check-circle me-2"></i>${nom} ajouté au panier!`;
            document.body.appendChild(toast);
            
            setTimeout(() => {
                toast.remove();
            }, 3000);
        });
    });
    
    // Initialiser le panier
    updateCart();
});
</script>

<?php include_once '../includes/footer.php'; ?>
