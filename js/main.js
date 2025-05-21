// Initialize cart from localStorage
let cart = JSON.parse(localStorage.getItem('cart')) || [];
const cartModal = new bootstrap.Modal(document.getElementById('cartModal'));

// Load products
function loadProducts() {
    const productsList = document.getElementById('productsList');
    productsList.innerHTML = products.map(product => `
        <div class="col-md-6 col-lg-3">
            <div class="card h-100">
                <div class="product-img-container">
                    <img src="${product.image}" class="product-img" alt="${product.name}">
                </div>
                <div class="card-body">
                    <h5 class="card-title">${product.name}</h5>
                    <p class="card-text">$${product.price}</p>
                    <div class="d-flex justify-content-between align-items-center">
                        <button class="btn btn-primary btn-sm" onclick="addToCart(${product.id})">
                            Add to Cart
                        </button>
                        <a href="product-details.html?id=${product.id}" class="btn btn-outline-light btn-sm">
                            View Details
                        </a>
                    </div>
                </div>
            </div>
        </div>
    `).join('');
}

// Update cart count
function updateCartCount() {
    const cartCount = document.getElementById('cartCount');
    if (cartCount) {
        cartCount.textContent = cart.reduce((total, item) => total + item.quantity, 0);
    }
}

// Add to cart
function addToCart(productId) {
    const product = products.find(p => p.id === productId);
    if (!product) return;

    const existingItem = cart.find(item => item.id === productId);
    if (existingItem) {
        existingItem.quantity += 1;
    } else {
        cart.push({
            id: product.id,
            name: product.name,
            price: product.price,
            image: product.image,
            quantity: 1
        });
    }

    localStorage.setItem('cart', JSON.stringify(cart));
    updateCartCount();
    updateCartModal();
}

// Update cart modal
function updateCartModal() {
    const cartItems = document.getElementById('cartItems');
    const cartTotal = document.getElementById('cartTotal');
    
    if (!cartItems || !cartTotal) return;

    if (cart.length === 0) {
        cartItems.innerHTML = '<p class="text-center">Your cart is empty</p>';
        cartTotal.textContent = '0.00';
        return;
    }

    cartItems.innerHTML = cart.map(item => `
        <div class="cart-item">
            <div class="row align-items-center">
                <div class="col-2">
                    <img src="${item.image}" alt="${item.name}" class="img-fluid">
                </div>
                <div class="col">
                    <h6 class="mb-0">${item.name}</h6>
                    <p class="mb-0">$${parseFloat(item.price).toFixed(2)} x ${item.quantity}</p>
                </div>
                <div class="col-auto">
                    <div class="quantity-control">
                        <button class="quantity-btn" onclick="updateQuantity(${item.id}, ${item.quantity - 1})">-</button>
                        <span class="mx-2">${item.quantity}</span>
                        <button class="quantity-btn" onclick="updateQuantity(${item.id}, ${item.quantity + 1})">+</button>
                    </div>
                </div>
            </div>
        </div>
    `).join('');

    const total = cart.reduce((sum, item) => sum + (item.price * item.quantity), 0);
    cartTotal.textContent = total.toFixed(2);
}

// Update quantity
function updateQuantity(productId, newQuantity) {
    if (newQuantity < 1) {
        cart = cart.filter(item => item.id !== productId);
    } else {
        const item = cart.find(item => item.id === productId);
        if (item) {
            item.quantity = newQuantity;
        }
    }

    localStorage.setItem('cart', JSON.stringify(cart));
    updateCartCount();
    updateCartModal();
}

// Cart button click handler
const cartBtn = document.getElementById('cartBtn');
if (cartBtn) {
    cartBtn.addEventListener('click', () => {
        updateCartModal();
        cartModal.show();
    });
}

// Checkout button handler
const checkoutBtn = document.getElementById('checkoutBtn');
if (checkoutBtn) {
    checkoutBtn.addEventListener('click', () => {
        if (cart.length === 0) {
            alert('Your cart is empty!');
            return;
        }
        // Redirect to checkout form
        const checkoutModal = new bootstrap.Modal(document.getElementById('checkoutModal'));
        checkoutModal.show();
    });
}

// Initialize
document.addEventListener('DOMContentLoaded', () => {
    loadProducts();
    updateCartCount();
}); 