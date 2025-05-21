// Fetch products from the server
async function fetchProducts() {
    try {
        const response = await fetch('api/get_products.php');
        const data = await response.json();
        return data;
    } catch (error) {
        console.error('Error fetching products:', error);
        return [];
    }
}

// Initialize products
let products = [];

// Load products when the page loads
document.addEventListener('DOMContentLoaded', async () => {
    products = await fetchProducts();
    if (document.getElementById('productsList')) {
        displayProducts();
    }
});

// Save products to localStorage
function saveProducts() {
    localStorage.setItem('products', JSON.stringify(products));
} 