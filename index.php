<?php
require_once 'config/config.php';

// Fetch featured products
$stmt = $db->prepare("SELECT p.*, c.name as category_name FROM products p 
                      LEFT JOIN categories c ON p.category_id = c.id 
                      WHERE p.status = 'active' 
                      ORDER BY p.created_at DESC LIMIT 6");
$stmt->execute();
$featured_products = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo SITE_NAME; ?> - Premium Networking Solutions</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <!-- Header -->
    <header class="header">
        <nav class="navbar">
            <a href="index.php" class="logo">
                <i class="fas fa-wifi"></i> Airtel Store
            </a>
            <ul class="nav-links">
                <li><a href="index.php"><i class="fas fa-home"></i> Home</a></li>
                <li><a href="products.php"><i class="fas fa-shopping-bag"></i> Products</a></li>
                <?php if (isLoggedIn()): ?>
                    <li><a href="cart.php"><i class="fas fa-shopping-cart"></i> Cart</a></li>
                    <li><a href="profile.php"><i class="fas fa-user"></i> Profile</a></li>
                    <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
                <?php else: ?>
                    <li><a href="login.php"><i class="fas fa-sign-in-alt"></i> Login</a></li>
                    <li><a href="register.php"><i class="fas fa-user-plus"></i> Register</a></li>
                <?php endif; ?>
            </ul>
        </nav>
    </header>

    <!-- Hero Section -->
    <!-- <section class="hero">
        <div class="container">
            <h1 class="gradient-text">Connect Kenya with Airtel</h1>
            <p>Discover premium networking solutions for your home and business</p>
            <a href="products.php" class="btn btn-primary">
                <i class="fas fa-rocket"></i> Shop Now
            </a>
        </div>
    </section> -->

    <!-- Featured Products -->
    <section class="products-section">
        <div class="container">
            <h2 class="section-title">Featured Products</h2>
            <div class="products-grid">
                <?php foreach ($featured_products as $product): ?>
                <div class="product-card card-hover">
                    <div class="product-image">
                        <?php if ($product['image']): ?>
                            <img src="uploads/<?php echo htmlspecialchars($product['image']); ?>" 
                                 alt="<?php echo htmlspecialchars($product['name']); ?>">
                        <?php else: ?>
                            <i class="fas fa-wifi"></i>
                        <?php endif; ?>
                    </div>
                    <div class="product-info">
                        <h3 class="product-title"><?php echo htmlspecialchars($product['name']); ?></h3>
                        <p class="product-description"><?php echo htmlspecialchars(substr($product['description'], 0, 100)) . '...'; ?></p>
                        <div class="product-price"><?php echo formatPrice($product['price']); ?></div>
                        
                        <?php if ($product['features']): ?>
                        <ul class="product-features">
                            <?php 
                            $features = explode(',', $product['features']);
                            foreach (array_slice($features, 0, 3) as $feature): 
                            ?>
                            <li><?php echo htmlspecialchars(trim($feature)); ?></li>
                            <?php endforeach; ?>
                        </ul>
                        <?php endif; ?>
                        
                        <div style="display: flex; gap: 1rem; margin-top: 1rem;">
                            <a href="product-detail.php?id=<?php echo $product['id']; ?>" class="btn btn-outline" style="flex: 1;">
                                <i class="fas fa-eye"></i> View Details
                            </a>
                            <?php if (isLoggedIn()): ?>
                            <button onclick="addToCart(<?php echo $product['id']; ?>)" class="btn btn-primary" style="flex: 1;">
                                <i class="fas fa-cart-plus"></i> Add to Cart
                            </button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            
            <div style="text-align: center; margin-top: 3rem;">
                <a href="products.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-right"></i> View All Products
                </a>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section style="padding: 4rem 0; background: white;">
        <div class="container">
            <h2 class="section-title">Why Choose Airtel?</h2>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 2rem; margin-top: 2rem;">
                <div style="text-align: center; padding: 2rem;">
                    <div style="font-size: 3rem; color: var(--primary-color); margin-bottom: 1rem;">
                        <i class="fas fa-shipping-fast"></i>
                    </div>
                    <h3>Fast Delivery</h3>
                    <p>Quick delivery across Kenya within 24-48 hours</p>
                </div>
                <div style="text-align: center; padding: 2rem;">
                    <div style="font-size: 3rem; color: var(--primary-color); margin-bottom: 1rem;">
                        <i class="fas fa-shield-alt"></i>
                    </div>
                    <h3>Quality Guarantee</h3>
                    <p>All products come with manufacturer warranty</p>
                </div>
                <div style="text-align: center; padding: 2rem;">
                    <div style="font-size: 3rem; color: var(--primary-color); margin-bottom: 1rem;">
                        <i class="fas fa-headset"></i>
                    </div>
                    <h3>24/7 Support</h3>
                    <p>Round-the-clock customer support for all your needs</p>
                </div>
                <div style="text-align: center; padding: 2rem;">
                    <div style="font-size: 3rem; color: var(--primary-color); margin-bottom: 1rem;">
                        <i class="fas fa-mobile-alt"></i>
                    </div>
                    <h3>M-Pesa Payment</h3>
                    <p>Secure and convenient mobile money payments</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="footer-content">
                <div class="footer-section">
                    <h3><i class="fas fa-wifi"></i> Airtel Store</h3>
                    <p>Your trusted partner for premium networking solutions in Kenya. Connect with confidence.</p>
                </div>
                <div class="footer-section">
                    <h3>Quick Links</h3>
                    <p><a href="products.php">Products</a></p>
                    <p><a href="about.php">About Us</a></p>
                    <p><a href="contact.php">Contact</a></p>
                </div>
                <div class="footer-section">
                    <h3>Customer Service</h3>
                    <p><a href="tel:+254700000000">+254 700 000 000</a></p>
                    <p><a href="mailto:support@airtel.co.ke">support@airtel.co.ke</a></p>
                </div>
                <div class="footer-section">
                    <h3>Follow Us</h3>
                    <p>
                        <a href="#" style="margin-right: 1rem;"><i class="fab fa-facebook"></i> Facebook</a><br>
                        <a href="#" style="margin-right: 1rem;"><i class="fab fa-twitter"></i> Twitter</a><br>
                        <a href="#"><i class="fab fa-instagram"></i> Instagram</a>
                    </p>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; 2024 Airtel Kenya Store. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <script src="assets/js/main.js"></script>
    <script>
        // Add to cart functionality
        function addToCart(productId) {
            fetch('api/add-to-cart.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    product_id: productId,
                    quantity: 1
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Product added to cart successfully!');
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred. Please try again.');
            });
        }
    </script>
</body>
</html>