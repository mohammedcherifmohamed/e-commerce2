// Your Telegram Bot credentials
const telegram_bot_id = "7971952794:AAEHtg5B5XbRjUs1UDNk47B7yvJmknsVJCs";
const chat_id = "@yurei2_Bot2";

// Get product ID from URL
function getProductId() {
    const params = new URLSearchParams(window.location.search);
    return parseInt(params.get('id'));
}

// Load product details
function loadProductDetails() {
    const productId = getProductId();
    const product = products.find(p => p.id === productId);

    if (!product) {
        window.location.href = 'index.html';
        return;
    }

    const productDetails = document.getElementById('productDetails');
    productDetails.innerHTML = `
        <div class="col-md-6">
            <div class="product-details-img-container">
                <img src="${product.image}" class="product-details-img" alt="${product.name}">
            </div>
        </div>
        <div class="col-md-6">
            <div class="product-details">
                <h1 class="mb-4">${product.name}</h1>
                <h3 class="text-primary mb-4">$${product.price}</h3>
                <p class="mb-4">${product.description}</p>
                
                <div class="specs mb-4">
                    <h4 class="mb-3">Specifications</h4>
                    <ul class="list-unstyled">
                        ${Object.entries(product.specs).map(([key, value]) => `
                            <li class="mb-2">
                                <strong>${key.charAt(0).toUpperCase() + key.slice(1)}:</strong> ${value}
                            </li>
                        `).join('')}
                    </ul>
                </div>

                <div class="delivery-details mb-4">
                    <h4 class="mb-3">Delivery Details</h4>
                    <form id="deliveryForm" class="needs-validation" novalidate>
                        <div class="mb-3">
                            <label for="quantity" class="form-label">Quantity</label>
                            <div class="input-group">
                                <button type="button" class="btn btn-outline-light" onclick="updateQuantityInput(-1)">-</button>
                                <input type="number" class="form-control bg-dark text-light" id="quantity" value="1" min="1" required>
                                <button type="button" class="btn btn-outline-light" onclick="updateQuantityInput(1)">+</button>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="name" class="form-label">Full Name</label>
                            <input type="text" class="form-control bg-dark text-light" id="name" required>
                        </div>
                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control bg-dark text-light" id="email" required>
                        </div>
                        <div class="mb-3">
                            <label for="phone" class="form-label">Phone Number</label>
                            <input type="tel" class="form-control bg-dark text-light" id="phone" required>
                        </div>
                        <div class="mb-3">
                            <label for="address" class="form-label">Delivery Address</label>
                            <textarea class="form-control bg-dark text-light" id="address" rows="3" required></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="size" class="form-label">Size (if applicable)</label>
                            <select class="form-select bg-dark text-light" id="size">
                                <option value="">Select Size</option>
                                <option value="S">Small</option>
                                <option value="M">Medium</option>
                                <option value="L">Large</option>
                                <option value="XL">Extra Large</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="notes" class="form-label">Delivery Notes (Optional)</label>
                            <textarea class="form-control bg-dark text-light" id="notes" rows="2"></textarea>
                        </div>
                    </form>
                </div>

                <div class="d-grid gap-2">
                    <button class="btn btn-primary btn-lg" onclick="addToCartWithDetails(${product.id})">
                        place order
                    </button>
                    <a href="index.html#products" class="btn btn-outline-light btn-lg">
                        Back to Products
                    </a>
                </div>
            </div>
        </div>
    `;
}

// Update quantity input
function updateQuantityInput(change) {
    const quantityInput = document.getElementById('quantity');
    const newValue = parseInt(quantityInput.value) + change;
    if (newValue >= 1) {
        quantityInput.value = newValue;
    }
}

// Add to cart with delivery details and send to Telegram
function addToCartWithDetails(productId) {
    const form = document.getElementById('deliveryForm');
    if (!form.checkValidity()) {
        form.classList.add('was-validated');
        return;
    }

    const product = products.find(p => p.id === productId);
    const quantity = parseInt(document.getElementById('quantity').value);
    const deliveryDetails = {
        name: document.getElementById('name').value,
        email: document.getElementById('email').value,
        phone: document.getElementById('phone').value,
        address: document.getElementById('address').value,
        size: document.getElementById('size').value,
        notes: document.getElementById('notes').value
    };

    const cartItem = {
        ...product,
        quantity: quantity,
        deliveryDetails: deliveryDetails
    };

    // Update cart
    const existingItemIndex = cart.findIndex(item => item.id === productId);
    if (existingItemIndex !== -1) {
        cart[existingItemIndex] = cartItem;
    } else {
        cart.push(cartItem);
    }

    updateCartCount();
    updateCartModal();

    // Send to Telegram
    const message = `
🛒 New Order!

📦 Product: ${product.name}
💲 Price: $${product.price}
🔢 Quantity: ${quantity}

👤 Name: ${deliveryDetails.name}
📧 Email: ${deliveryDetails.email}
📞 Phone: ${deliveryDetails.phone}
🏠 Address: ${deliveryDetails.address}
📐 Size: ${deliveryDetails.size || "N/A"}
📝 Notes: ${deliveryDetails.notes || "None"}
    `.trim();

    fetch(`https://api.telegram.org/bot${telegram_bot_id}/sendMessage`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            chat_id: chat_id,
            text: message
        })
    }).then(response => response.json())
      .then(data => {
          console.log('Telegram response:', data);
          alert('Product added to cart and order details sent!');
      })
      .catch(error => {
          console.error('Telegram error:', error);
          alert('Product added to cart but there was an error sending the order details.');
      });
}

// Initialize
document.addEventListener('DOMContentLoaded', () => {
    loadProductDetails();
}); 