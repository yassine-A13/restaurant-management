
// Attendre que le DOM soit chargé
document.addEventListener('DOMContentLoaded', function() {
    // Activer tous les tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl)
    });
    
    // Activer tous les popovers
    var popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
    var popoverList = popoverTriggerList.map(function (popoverTriggerEl) {
        return new bootstrap.Popover(popoverTriggerEl)
    });
    
    // Gestion du panier (pour les clients)
    initializeCart();
});

// Initialisation du panier
function initializeCart() {
    // Charger le panier depuis le localStorage
    let cart = JSON.parse(localStorage.getItem('restaurantCart')) || [];
    
    // Mettre à jour le compteur du panier
    updateCartCounter(cart);
    
    // Ajouter les écouteurs d'événements pour les boutons "Ajouter au panier"
    document.querySelectorAll('.add-to-cart').forEach(button => {
        button.addEventListener('click', function() {
            const platId = this.getAttribute('data-id');
            const platNom = this.getAttribute('data-nom');
            const platPrix = parseFloat(this.getAttribute('data-prix'));
            
            addToCart(platId, platNom, platPrix);
        });
    });
}

// Ajouter un plat au panier
function addToCart(id, nom, prix) {
    let cart = JSON.parse(localStorage.getItem('restaurantCart')) || [];
    
    // Vérifier si le plat est déjà dans le panier
    let platExistant = cart.find(item => item.id === id);
    
    if (platExistant) {
        platExistant.quantite += 1;
    } else {
        cart.push({
            id: id,
            nom: nom,
            prix: prix,
            quantite: 1
        });
    }
    
    // Sauvegarder le panier mis à jour
    localStorage.setItem('restaurantCart', JSON.stringify(cart));
    
    // Mettre à jour l'affichage
    updateCartCounter(cart);
    
    // Afficher un message de confirmation
    showToast(`${nom} ajouté au panier!`);
}

// Mettre à jour le compteur du panier
function updateCartCounter(cart) {
    const counter = document.getElementById('cart-counter');
    if (counter) {
        let totalItems = 0;
        cart.forEach(item => {
            totalItems += item.quantite;
        });
        
        counter.textContent = totalItems;
        
        if (totalItems > 0) {
            counter.classList.remove('d-none');
        } else {
            counter.classList.add('d-none');
        }
    }
}

// Afficher un toast de notification
function showToast(message) {
    // Créer un nouvel élément toast
    const toastContainer = document.getElementById('toast-container');
    if (!toastContainer) {
        // Créer un conteneur pour les toasts s'il n'existe pas
        const container = document.createElement('div');
        container.id = 'toast-container';
        container.className = 'toast-container position-fixed bottom-0 end-0 p-3';
        document.body.appendChild(container);
    }
    
    const toastId = 'toast-' + Date.now();
    const toastHTML = `
        <div id="${toastId}" class="toast align-items-center bg-success text-white" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="d-flex">
                <div class="toast-body">
                    ${message}
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
        </div>
    `;
    
    document.getElementById('toast-container').innerHTML += toastHTML;
    
    // Initialiser et afficher le toast
    const toastElement = document.getElementById(toastId);
    const toast = new bootstrap.Toast(toastElement, {
        delay: 3000
    });
    toast.show();
    
    // Supprimer le toast du DOM après qu'il ait été masqué
    toastElement.addEventListener('hidden.bs.toast', function () {
        toastElement.remove();
    });
}

// Fonction pour confirmer une action
function confirmAction(message) {
    return confirm(message);
}
