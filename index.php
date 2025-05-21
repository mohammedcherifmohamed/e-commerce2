<?php
require_once 'config/db.php';

// Debug information
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    // Fetch products from database
    $stmt = $pdo->query("SELECT * FROM products ORDER BY id DESC");
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Debug: Print products array
    echo "<!-- Debug: Products Array -->";
    echo "<!-- ";
    print_r($products);
    echo " -->";
    
} catch(PDOException $e) {
    echo "Database Error: " . $e->getMessage();
    die();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dark E-Commerce</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
</head>
<body class="bg-dark text-light">
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark border-bottom border-secondary">
        <div class="container">
            <a class="navbar-brand" href="index.php">DarkStore</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link active" href="index.php">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#products">Products</a>
                    </li>
                </ul>
                <div class="d-flex">
                    <button class="btn btn-outline-light position-relative" id="cartBtn">
                        <i class="fas fa-shopping-cart"></i>
                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" id="cartCount">
                            0
                        </span>
                    </button>
                </div>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <div class="hero-section py-5 mb-4">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6">
                    <h1 class="display-4 fw-bold">Welcome to DarkStore</h1>
                    <p class="lead">Discover our amazing products with modern dark theme experience</p>
                    <a href="#products" class="btn btn-outline-light btn-lg">Shop Now</a>
                </div>
            </div>
        </div>
    </div>

    <!-- Product Options Modal -->
    <div class="modal fade" id="productOptionsModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content bg-dark text-light">
                <div class="modal-header border-secondary">
                    <h5 class="modal-title">Select Options</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="productOptionsForm">
                        <input type="hidden" id="productId">
                        <input type="hidden" id="productName">
                        <input type="hidden" id="productPrice">
                        <input type="hidden" id="productImage">
                        
                        <div class="mb-3">
                            <label for="productSize" class="form-label">Size</label>
                            <select class="form-select" id="productSize" required>
                                <option value="">Select Size</option>
                                <option value="S">Small</option>
                                <option value="M">Medium</option>
                                <option value="L">Large</option>
                                <option value="XL">Extra Large</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="productColor" class="form-label">Color</label>
                            <select class="form-select" id="productColor" required>
                                <option value="">Select Color</option>
                                <option value="Black">Black</option>
                                <option value="White">White</option>
                                <option value="Red">Red</option>
                                <option value="Blue">Blue</option>
                                <option value="Green">Green</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="productQuantity" class="form-label">Quantity</label>
                            <input type="number" class="form-control" id="productQuantity" min="1" value="1" required>
                        </div>
                    </form>
                </div>
                <div class="modal-footer border-secondary">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" onclick="confirmAddToCart()">Add to Cart</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Products Section -->
    <section id="products" class="py-5">
        <div class="container">
            <h2 class="text-center mb-4">Our Products</h2>
            <div class="row g-4">
                <?php if (empty($products)): ?>
                    <div class="col-12">
                        <div class="alert alert-info text-center">
                            No products available at the moment.
                        </div>
                    </div>
                <?php else: ?>
                    <?php foreach ($products as $product): ?>
                    <div class="col-md-3 mb-4">
                        <div class="card h-100">
                            <div class="product-img-container">
                                <img src="<?php echo htmlspecialchars($product['image']); ?>" 
                                     class="card-img-top product-img" 
                                     alt="<?php echo htmlspecialchars($product['name']); ?>">
                            </div>
                            <div class="card-body d-flex flex-column">
                                <h5 class="card-title"><?php echo htmlspecialchars($product['name']); ?></h5>
                                <p class="card-text"><?php echo htmlspecialchars(substr($product['description'], 0, 100)) . '...'; ?></p>
                                <div class="mt-auto">
                                    <p class="h5 mb-3">$<?php echo number_format($product['price'], 2); ?></p>
                                    <div class="d-grid gap-2">
                                        <button class="btn btn-primary" onclick="showProductOptions(<?php echo $product['id']; ?>, 
                                            '<?php echo htmlspecialchars(addslashes($product['name'])); ?>', 
                                            <?php echo $product['price']; ?>, 
                                            '<?php echo htmlspecialchars(addslashes($product['image'])); ?>')">
                                            <i class="fas fa-shopping-cart"></i> Add to Cart
                                        </button>
                                        <a href="product-details.php?id=<?php echo $product['id']; ?>" class="btn btn-outline-light">
                                            <i class="fas fa-eye"></i> View Details
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </section>

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
                        <h5>Total: $<span id="cartTotal">0</span></h5>
                    </div>
                </div>
                <div class="modal-footer border-secondary">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" onclick="showCheckoutModal()" id="checkoutBtn">Checkout</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Checkout Modal -->
    <div class="modal fade" id="checkoutModal" tabindex="-1" aria-labelledby="checkoutModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content bg-dark text-light">
                <div class="modal-header border-secondary">
                    <h5 class="modal-title" id="checkoutModalLabel">Complete Your Order</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="checkoutForm">
                        <!-- Order Summary -->
                        <div class="mb-4">
                            <h6>Order Summary</h6>
                            <div id="checkoutItems" class="mb-3"></div>
                            <div class="text-end">
                                <strong>Total: $<span id="checkoutTotal">0.00</span></strong>
                            </div>
                        </div>

                        <!-- Customer Information -->
                        <div class="mb-3">
                            <label for="customerName" class="form-label">Full Name</label>
                            <input type="text" class="form-control" id="customerName" required>
                        </div>
                        <div class="mb-3">
                            <label for="customerEmail" class="form-label">Email</label>
                            <input type="email" class="form-control" id="customerEmail" required>
                        </div>
                        <div class="mb-3">
                            <label for="customerPhone" class="form-label">Phone Number</label>
                            <input type="tel" class="form-control" id="customerPhone" required>
                        </div>
                        <div class="mb-3">
                            <label for="customerAddress" class="form-label">Delivery Address</label>
                            <textarea class="form-control" id="customerAddress" rows="3" required></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="customerNotes" class="form-label">Additional Notes (Optional)</label>
                            <textarea class="form-control" id="customerNotes" rows="2"></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer border-secondary">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" onclick="submitOrder()">Place Order</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Cart functionality
        let cart = JSON.parse(localStorage.getItem('cart')) || [];
        const cartModal = new bootstrap.Modal(document.getElementById('cartModal'));
        const productOptionsModal = new bootstrap.Modal(document.getElementById('productOptionsModal'));

        // Update cart count
        function updateCartCount() {
            const cartCount = document.getElementById('cartCount');
            if (cartCount) {
                cartCount.textContent = cart.reduce((total, item) => total + item.quantity, 0);
            }
        }

        // Show product options modal
        function showProductOptions(id, name, price, image) {
            document.getElementById('productId').value = id;
            document.getElementById('productName').value = name;
            document.getElementById('productPrice').value = price;
            document.getElementById('productImage').value = image;
            productOptionsModal.show();
        }

        // Confirm add to cart with options
        function confirmAddToCart() {
            const id = parseInt(document.getElementById('productId').value);
            const name = document.getElementById('productName').value;
            const price = parseFloat(document.getElementById('productPrice').value);
            const image = document.getElementById('productImage').value;
            const size = document.getElementById('productSize').value;
            const color = document.getElementById('productColor').value;
            const quantity = parseInt(document.getElementById('productQuantity').value);

            if (!size || !color || quantity < 1) {
                alert('Please select all options');
                return;
            }

            const existingItem = cart.find(item => 
                item.id === id && 
                item.size === size && 
                item.color === color
            );

            if (existingItem) {
                existingItem.quantity += quantity;
            } else {
                cart.push({
                    id: id,
                    name: name,
                    price: price,
                    image: image,
                    size: size,
                    color: color,
                    quantity: quantity
                });
            }

            localStorage.setItem('cart', JSON.stringify(cart));
            updateCartCount();
            updateCartModal();
            productOptionsModal.hide();
            
            // Reset form
            document.getElementById('productOptionsForm').reset();
        }

        // Update cart modal to show size and color
        function updateCartModal() {
            const cartItems = document.getElementById('cartItems');
            const cartTotal = document.getElementById('cartTotal');
            
            if (!cartItems || !cartTotal) return;

            if (cart.length === 0) {
                cartItems.innerHTML = '<p class="text-center">Your cart is empty</p>';
                cartTotal.textContent = '0.00';
                return;
            }

            cartItems.innerHTML = cart.map((item, index) => `
                <div class="cart-item mb-3">
                    <div class="row align-items-center">
                        <div class="col-2">
                            <img src="${item.image}" alt="${item.name}" class="img-fluid">
                        </div>
                        <div class="col">
                            <h6 class="mb-0">${item.name}</h6>
                            <p class="mb-0">Size: ${item.size} | Color: ${item.color}</p>
                            <p class="mb-0">$${parseFloat(item.price).toFixed(2)} x ${item.quantity}</p>
                        </div>
                        <div class="col-auto">
                            <div class="btn-group" role="group">
                                <button type="button" class="btn btn-sm btn-outline-light" 
                                    onclick="decreaseQuantity(${index})">
                                    <i class="fas fa-minus"></i>
                                </button>
                                <span class="btn btn-sm btn-outline-light disabled">${item.quantity}</span>
                                <button type="button" class="btn btn-sm btn-outline-light" 
                                    onclick="increaseQuantity(${index})">
                                    <i class="fas fa-plus"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            `).join('');

            const total = cart.reduce((sum, item) => sum + (item.price * item.quantity), 0);
            cartTotal.textContent = total.toFixed(2);
        }

        // Increase quantity
        function increaseQuantity(index) {
            if (index >= 0 && index < cart.length) {
                cart[index].quantity += 1;
                localStorage.setItem('cart', JSON.stringify(cart));
                updateCartCount();
                updateCartModal();
            }
        }

        // Decrease quantity
        function decreaseQuantity(index) {
            if (index >= 0 && index < cart.length) {
                if (cart[index].quantity > 1) {
                    cart[index].quantity -= 1;
                } else {
                    cart.splice(index, 1);
                }
                localStorage.setItem('cart', JSON.stringify(cart));
                updateCartCount();
                updateCartModal();
            }
        }

        // Show checkout modal
        function showCheckoutModal() {
            const checkoutModal = new bootstrap.Modal(document.getElementById('checkoutModal'));
            const checkoutItems = document.getElementById('checkoutItems');
            const checkoutTotal = document.getElementById('checkoutTotal');
            
            if (cart.length === 0) {
                alert('Your cart is empty!');
                return;
            }

            // Display cart items in checkout modal
            checkoutItems.innerHTML = cart.map(item => `
                <div class="mb-2">
                    <div class="d-flex justify-content-between">
                        <span>${item.name} (${item.size}, ${item.color}) x ${item.quantity}</span>
                        <span>$${(item.price * item.quantity).toFixed(2)}</span>
                    </div>
                </div>
            `).join('');
            
            // Update total
            const total = cart.reduce((sum, item) => sum + (item.price * item.quantity), 0);
            checkoutTotal.textContent = total.toFixed(2);
            
            checkoutModal.show();
        }

        // Submit order
        async function submitOrder() {
            const form = document.getElementById('checkoutForm');
            if (!form.checkValidity()) {
                form.reportValidity();
                return;
            }

            const customer = {
                name: document.getElementById('customerName').value,
                email: document.getElementById('customerEmail').value,
                phone: document.getElementById('customerPhone').value,
                address: document.getElementById('customerAddress').value,
                notes: document.getElementById('customerNotes').value
            };

            try {
                const response = await fetch('send_order.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        customer: customer,
                        items: cart,
                        total: cart.reduce((sum, item) => sum + (item.price * item.quantity), 0)
                    })
                });

                const result = await response.json();
                
                if (result.success) {
                    alert('Order placed successfully! We will contact you soon.');
                    // Clear cart
                    cart = [];
                    localStorage.setItem('cart', JSON.stringify(cart));
                    updateCartCount();
                    // Close modals
                    bootstrap.Modal.getInstance(document.getElementById('cartModal')).hide();
                    bootstrap.Modal.getInstance(document.getElementById('checkoutModal')).hide();
                } else {
                    alert('Error: ' + result.message);
                }
            } catch (error) {
                alert('Error placing order. Please try again.');
                console.error('Error:', error);
            }
        }

        // Update cart button click handler
        document.getElementById('cartBtn').addEventListener('click', () => {
            updateCartModal();
            cartModal.show();
        });

        // Initialize cart count when page loads
        document.addEventListener('DOMContentLoaded', () => {
            updateCartCount();
        });
    </script>
</body>
</html> 