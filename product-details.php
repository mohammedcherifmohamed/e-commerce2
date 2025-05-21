<?php
session_start();
require_once 'config/db.php';

if (!isset($_GET['id'])) {
    header('Location: index.php');
    exit();
}

$id = $_GET['id'];

// Get product details
$stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
$stmt->execute([$id]);
$product = $stmt->fetch();

if (!$product) {
    header('Location: index.php');
    exit();
}

// Get available sizes for this product
$stmt = $pdo->prepare("
    SELECT s.*, ps.stock
    FROM sizes s
    LEFT JOIN product_sizes ps ON ps.size_id = s.id AND ps.product_id = ?
    ORDER BY FIELD(s.name, 'XS', 'S', 'M', 'L', 'XL', 'XXL')
");
$stmt->execute([$id]);
$sizes = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($product['name']); ?> - DarkStore</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
    <style>
        .size-btn {
            min-width: 60px;
            height: 45px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 5px;
            position: relative;
            border: 2px solid #fff;
            transition: all 0.2s ease;
        }
        .size-btn:hover:not(:disabled) {
            transform: translateY(-2px);
            background-color: rgba(255, 255, 255, 0.1);
        }
        .size-btn.selected {
            background-color: #fff;
            color: #000;
        }
        .size-btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
            border-color: #666;
        }
        .size-name {
            font-size: 1.1em;
            font-weight: bold;
        }
        .stock-status {
            position: absolute;
            bottom: -20px;
            left: 50%;
            transform: translateX(-50%);
            white-space: nowrap;
            font-size: 0.75em;
        }
    </style>
</head>
<body class="bg-dark text-light">
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark border-bottom border-secondary">
        <div class="container">
            <a class="navbar-brand" href="index.php">DarkStore</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="index.php#products">Products</a>
                    </li>
                </ul>
                <div class="d-flex align-items-center">
                    <button class="btn btn-outline-light position-relative me-2" id="cartBtn" data-bs-toggle="modal" data-bs-target="#cartModal">
                        <i class="fas fa-shopping-cart"></i>
                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" id="cartCount">
                            0
                        </span>
                    </button>
                </div>
            </div>
        </div>
    </nav>

    <div class="container py-4">
        <div class="row">
            <div class="col-md-6">
                <img src="<?php echo htmlspecialchars($product['image']); ?>" 
                     alt="<?php echo htmlspecialchars($product['name']); ?>"
                     class="img-fluid rounded">
            </div>
            <div class="col-md-6">
                <h1 class="mb-4"><?php echo htmlspecialchars($product['name']); ?></h1>
                <p class="lead mb-4">$<?php echo number_format($product['price'], 2); ?></p>
                <div class="mb-4">
                    <?php echo nl2br(htmlspecialchars($product['description'])); ?>
                </div>

                <form action="add_to_cart.php" method="POST">
                    <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                    
                    <!-- Size Selection -->
                    <div class="mb-4">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <label class="form-label h5 mb-0">Select Size</label>
                            <button type="button" class="btn btn-link text-light p-0" data-bs-toggle="modal" data-bs-target="#sizeGuideModal">
                                <i class="fas fa-ruler"></i> Size Guide
                            </button>
                        </div>
                        
                        <div class="size-options d-flex flex-wrap justify-content-start mb-3">
                            <?php foreach ($sizes as $size): ?>
                                <div class="size-option">
                                    <input type="radio" 
                                           class="btn-check" 
                                           name="size_id" 
                                           id="size_<?php echo $size['id']; ?>" 
                                           value="<?php echo $size['id']; ?>"
                                           <?php echo ($size['stock'] > 0 ? 'required' : 'disabled'); ?>>
                                    <label class="btn btn-outline-light size-btn" 
                                           for="size_<?php echo $size['id']; ?>">
                                        <span class="size-name"><?php echo htmlspecialchars($size['name']); ?></span>
                                        <?php if ($size['stock'] > 0): ?>
                                            <?php if ($size['stock'] < 5): ?>
                                                <span class="stock-status text-warning">
                                                    Only <?php echo $size['stock']; ?> left
                                                </span>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <span class="stock-status text-danger">Out of Stock</span>
                                        <?php endif; ?>
                                    </label>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <div id="selectedSizeInfo" class="text-muted small"></div>
                    </div>

                    <div class="mb-4">
                        <label for="quantity" class="form-label">Quantity</label>
                        <input type="number" 
                               class="form-control bg-dark text-light border-secondary" 
                               id="quantity" 
                               name="quantity" 
                               value="1" 
                               min="1" 
                               max="10"
                               style="max-width: 100px;">
                    </div>

                    <button type="submit" class="btn btn-primary btn-lg mb-4">
                        <i class="fas fa-shopping-cart"></i> Add to Cart
                    </button>
                </form>

                <!-- Delivery Information Form -->
                <div class="delivery-form mt-4 p-4 rounded" style="background: rgba(255, 255, 255, 0.05);">
                    <h3 class="mb-4">Delivery Information</h3>
                    <form id="deliveryForm" action="process_order.php" method="POST">
                        <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="name" class="form-label">Full Name</label>
                                    <input type="text" 
                                           class="form-control bg-dark text-light border-secondary" 
                                           id="name" 
                                           name="name" 
                                           required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="phone" class="form-label">Phone Number</label>
                                    <input type="tel" 
                                           class="form-control bg-dark text-light border-secondary" 
                                           id="phone" 
                                           name="phone" 
                                           required>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="form-group">
                                    <label for="email" class="form-label">Email Address</label>
                                    <input type="email" 
                                           class="form-control bg-dark text-light border-secondary" 
                                           id="email" 
                                           name="email" 
                                           required>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="form-group">
                                    <label for="address" class="form-label">Delivery Address</label>
                                    <input type="text" 
                                           class="form-control bg-dark text-light border-secondary" 
                                           id="address" 
                                           name="address" 
                                           required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="city" class="form-label">City</label>
                                    <input type="text" 
                                           class="form-control bg-dark text-light border-secondary" 
                                           id="city" 
                                           name="city" 
                                           required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="postal_code" class="form-label">Postal Code</label>
                                    <input type="text" 
                                           class="form-control bg-dark text-light border-secondary" 
                                           id="postal_code" 
                                           name="postal_code" 
                                           required>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="form-group">
                                    <label for="notes" class="form-label">Additional Notes</label>
                                    <textarea class="form-control bg-dark text-light border-secondary" 
                                              id="notes" 
                                              name="notes" 
                                              rows="3"></textarea>
                                </div>
                            </div>
                            <div class="col-12">
                                <button type="submit" class="btn btn-success w-100">
                                    <i class="fas fa-check"></i> Place Order
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Size Guide Modal -->
    <div class="modal fade" id="sizeGuideModal" tabindex="-1" aria-labelledby="sizeGuideModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content bg-dark">
                <div class="modal-header border-secondary">
                    <h5 class="modal-title" id="sizeGuideModalLabel">Size Guide</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="table-responsive">
                        <table class="table table-dark table-bordered">
                            <thead>
                                <tr>
                                    <th>Size</th>
                                    <th>Chest (inches)</th>
                                    <th>Waist (inches)</th>
                                    <th>Length (inches)</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>XS</td>
                                    <td>32-34</td>
                                    <td>26-28</td>
                                    <td>25</td>
                                </tr>
                                <tr>
                                    <td>S</td>
                                    <td>34-36</td>
                                    <td>28-30</td>
                                    <td>26</td>
                                </tr>
                                <tr>
                                    <td>M</td>
                                    <td>36-38</td>
                                    <td>30-32</td>
                                    <td>27</td>
                                </tr>
                                <tr>
                                    <td>L</td>
                                    <td>38-40</td>
                                    <td>32-34</td>
                                    <td>28</td>
                                </tr>
                                <tr>
                                    <td>XL</td>
                                    <td>40-42</td>
                                    <td>34-36</td>
                                    <td>29</td>
                                </tr>
                                <tr>
                                    <td>XXL</td>
                                    <td>42-44</td>
                                    <td>36-38</td>
                                    <td>30</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-3">
                        <h6>How to Measure</h6>
                        <ul class="text-light-50">
                            <li><strong>Chest:</strong> Measure around the fullest part of your chest, keeping the tape horizontal.</li>
                            <li><strong>Waist:</strong> Measure around your natural waistline, keeping the tape comfortably loose.</li>
                            <li><strong>Length:</strong> Measure from the highest point of the shoulder to the bottom hem.</li>
                        </ul>
                    </div>
                </div>
                <div class="modal-footer border-secondary">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Cart Modal -->
    <div class="modal fade" id="cartModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content bg-dark text-light">
                <div class="modal-header border-secondary">
                    <h5 class="modal-title">Shopping Cart</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div id="cartItems"></div>
                    <div class="text-end mt-3">
                        <h5>Total: $<span id="cartTotal">0.00</span></h5>
                    </div>
                </div>
                <div class="modal-footer border-secondary">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Continue Shopping</button>
                    <button type="button" class="btn btn-primary" onclick="showCheckoutModal()">Proceed to Checkout</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Checkout Modal -->
    <div class="modal fade" id="checkoutModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content bg-dark text-light">
                <div class="modal-header border-secondary">
                    <h5 class="modal-title">Checkout</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="checkoutForm" onsubmit="submitOrder(event)">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="name" class="form-label">Full Name</label>
                                    <input type="text" class="form-control bg-dark text-light border-secondary" id="name" name="name" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="phone" class="form-label">Phone Number</label>
                                    <input type="tel" class="form-control bg-dark text-light border-secondary" id="phone" name="phone" required>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="form-group">
                                    <label for="email" class="form-label">Email Address</label>
                                    <input type="email" class="form-control bg-dark text-light border-secondary" id="email" name="email" required>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="form-group">
                                    <label for="address" class="form-label">Delivery Address</label>
                                    <input type="text" class="form-control bg-dark text-light border-secondary" id="address" name="address" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="city" class="form-label">City</label>
                                    <input type="text" class="form-control bg-dark text-light border-secondary" id="city" name="city" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="postal_code" class="form-label">Postal Code</label>
                                    <input type="text" class="form-control bg-dark text-light border-secondary" id="postal_code" name="postal_code" required>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="form-group">
                                    <label for="notes" class="form-label">Additional Notes</label>
                                    <textarea class="form-control bg-dark text-light border-secondary" id="notes" name="notes" rows="3"></textarea>
                                </div>
                            </div>
                        </div>
                        <div class="mt-4">
                            <h6>Order Summary</h6>
                            <div id="checkoutItems" class="mb-3"></div>
                            <div class="text-end">
                                <h5>Total: $<span id="checkoutTotal">0.00</span></h5>
                            </div>
                        </div>
                        <div class="text-end mt-4">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-success">
                                <i class="fas fa-check"></i> Place Order
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
    document.querySelectorAll('input[name="size_id"]').forEach(input => {
        input.addEventListener('change', function() {
            const label = document.querySelector(`label[for="size_${this.value}"]`);
            const sizeName = label.querySelector('.size-name').textContent;
            const stockStatus = label.querySelector('.stock-status')?.textContent || '';
            
            document.getElementById('selectedSizeInfo').textContent = 
                `Selected size: ${sizeName}${stockStatus ? ' - ' + stockStatus.trim() : ''}`;
            
            // Update all size buttons
            document.querySelectorAll('.size-btn').forEach(btn => {
                btn.classList.remove('selected');
            });
            label.classList.add('selected');
        });
    });

    // Cart functionality
    let cart = JSON.parse(localStorage.getItem('cart')) || [];

    // Add event listener for cart modal
    document.getElementById('cartModal').addEventListener('show.bs.modal', function () {
        updateCartModal();
    });

    function updateCartCount() {
        const cartCount = document.getElementById('cartCount');
        if (cartCount) {
            cartCount.textContent = cart.reduce((total, item) => total + item.quantity, 0);
        }
    }

    // Update cart when adding new items
    document.querySelector('form').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const size_id = document.querySelector('input[name="size_id"]:checked')?.value;
        const size_name = size_id ? document.querySelector(`label[for="size_${size_id}"] .size-name`).textContent : '';
        const quantity = parseInt(document.getElementById('quantity').value) || 1;
        
        const item = {
            id: <?php echo $product['id']; ?>,
            name: "<?php echo addslashes($product['name']); ?>",
            price: <?php echo $product['price']; ?>,
            image: "<?php echo addslashes($product['image']); ?>",
            size: size_name,
            size_id: size_id,
            quantity: quantity
        };

        const existingItemIndex = cart.findIndex(i => i.id === item.id && i.size_id === item.size_id);
        
        if (existingItemIndex > -1) {
            cart[existingItemIndex].quantity += quantity;
        } else {
            cart.push(item);
        }

        localStorage.setItem('cart', JSON.stringify(cart));
        updateCartCount();
        updateCartModal();

        // Show success message
        alert('Product added to cart!');
    });

    function updateCartModal() {
        const cartItems = document.getElementById('cartItems');
        const cartTotal = document.getElementById('cartTotal');
        
        // Get fresh cart data from localStorage
        cart = JSON.parse(localStorage.getItem('cart')) || [];
        
        if (!cartItems || !cartTotal) return;

        if (cart.length === 0) {
            cartItems.innerHTML = '<p class="text-center">Your cart is empty</p>';
            cartTotal.textContent = '0.00';
            return;
        }

        cartItems.innerHTML = cart.map(item => `
            <div class="cart-item border-bottom border-secondary pb-3 mb-3">
                <div class="row align-items-center">
                    <div class="col-2">
                        <img src="${item.image}" alt="${item.name}" class="img-fluid rounded">
                    </div>
                    <div class="col">
                        <h6 class="mb-1">${item.name}</h6>
                        <p class="mb-0 text-muted">
                            Size: ${item.size || 'N/A'}<br>
                            $${parseFloat(item.price).toFixed(2)} x ${item.quantity}
                        </p>
                    </div>
                    <div class="col-auto">
                        <button class="btn btn-sm btn-outline-danger" onclick="removeFromCart(${item.id}, '${item.size_id}')">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>
            </div>
        `).join('');

        const total = cart.reduce((sum, item) => sum + (item.price * item.quantity), 0);
        cartTotal.textContent = total.toFixed(2);
    }

    function removeFromCart(productId, sizeId) {
        cart = cart.filter(item => !(item.id === productId && item.size_id === sizeId));
        localStorage.setItem('cart', JSON.stringify(cart));
        updateCartCount();
        updateCartModal();
    }

    function showCheckoutModal() {
        // Hide cart modal
        const cartModal = bootstrap.Modal.getInstance(document.getElementById('cartModal'));
        cartModal.hide();

        // Update checkout items
        const checkoutItems = document.getElementById('checkoutItems');
        const checkoutTotal = document.getElementById('checkoutTotal');
        
        checkoutItems.innerHTML = cart.map(item => `
            <div class="cart-item border-bottom border-secondary pb-3 mb-3">
                <div class="row align-items-center">
                    <div class="col-2">
                        <img src="${item.image}" alt="${item.name}" class="img-fluid rounded">
                    </div>
                    <div class="col">
                        <h6 class="mb-1">${item.name}</h6>
                        <p class="mb-0 text-muted">
                            Size: ${item.size || 'N/A'}<br>
                            $${parseFloat(item.price).toFixed(2)} x ${item.quantity}
                        </p>
                    </div>
                </div>
            </div>
        `).join('');

        const total = cart.reduce((sum, item) => sum + (item.price * item.quantity), 0);
        checkoutTotal.textContent = total.toFixed(2);

        // Show checkout modal
        const checkoutModal = new bootstrap.Modal(document.getElementById('checkoutModal'));
        checkoutModal.show();
    }

    async function submitOrder(event) {
        event.preventDefault();
        
        const form = event.target;
        const formData = new FormData(form);
        const customerData = Object.fromEntries(formData.entries());
        
        try {
            // Prepare order data
            const orderData = {
                customer: customerData,
                items: cart,
                total: cart.reduce((sum, item) => sum + (item.price * item.quantity), 0)
            };

            // Send order to server
            const response = await fetch('process_order.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(orderData)
            });

            const result = await response.json();

            if (!response.ok) {
                throw new Error(result.error || 'Failed to process order');
            }

            // Clear cart
            cart = [];
            localStorage.setItem('cart', JSON.stringify(cart));
            updateCartCount();

            // Hide checkout modal
            const checkoutModal = bootstrap.Modal.getInstance(document.getElementById('checkoutModal'));
            checkoutModal.hide();

            // Show success message and redirect
            window.location.href = 'order_success.php';

        } catch (error) {
            console.error('Error:', error);
            alert('Failed to process order: ' + error.message);
        }
    }

    // Initialize cart when page loads
    document.addEventListener('DOMContentLoaded', () => {
        updateCartCount();
    });
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 