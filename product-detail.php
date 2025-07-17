<?php
require_once 'config/config.php';

$product_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($product_id <= 0) {
    redirect('products.php');
}

// Fetch product details
$stmt = $db->prepare("SELECT p.*, c.name as category_name 
                      FROM products p 
                      LEFT JOIN categories c ON p.category_id = c.id 
                      WHERE p.id = ? AND p.status = 'active'");
$stmt->execute([$product_id]);
$product = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$product) {
    redirect('products.php');
}

// Fetch related products
$stmt = $db->prepare("SELECT * FROM products 
                      WHERE category_id = ? AND id != ? AND status = 'active' 
                      ORDER BY RAND() LIMIT 4");
$stmt->execute([$product['category_id'], $product_id]);
$related_products = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($product['name']); ?> - <?php echo SITE_NAME; ?></title>
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

    <div style="padding: 2rem 0; background: var(--light-color); min-height: 80vh;">
        <div class="container">
            <!-- Breadcrumb -->
            <nav style="margin-bottom: 2rem;">
                <a href="index.php" style="color: var(--gray-color); text-decoration: none;">Home</a>
                <span style="margin: 0 0.5rem; color: var(--gray-color);">/</span>
                <a href="products.php" style="color: var(--gray-color); text-decoration: none;">Products</a>
                <span style="margin: 0 0.5rem; color: var(--gray-color);">/</span>
                <span style="color: var(--primary-color);"><?php echo htmlspecialchars($product['name']); ?></span>
            </nav>

            <!-- Product Details -->
            <div style="background: white; border-radius: var(--border-radius); box-shadow: var(--box-shadow); overflow: hidden;">
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0;">
                    <!-- Product Image -->
                    <div style="padding: 2rem; background: var(--light-color); display: flex; align-items: center; justify-content: center;">
                        <?php if ($product['image']): ?>
                            <img src="uploads/<?php echo htmlspecialchars($product['image']); ?>" 
                                 alt="<?php echo htmlspecialchars($product['name']); ?>"
                                 style="max-width: 100%; max-height: 400px; object-fit: contain; border-radius: var(--border-radius);">
                        <?php else: ?>
                            <div style="font-size: 8rem; color: var(--gray-color);">
                                <i class="fas fa-wifi"></i>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Product Info -->
                    <div style="padding: 2rem;">
                        <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 1rem;">
                            <span style="background: var(--primary-color); color: white; padding: 0.5rem 1rem; border-radius: var(--border-radius); font-size: 0.9rem;">
                                <?php echo htmlspecialchars($product['category_name']); ?>
                            </span>
                            <div style="color: var(--success-color); font-weight: 600;">
                                <i class="fas fa-check-circle"></i> In Stock (<?php echo $product['stock_quantity']; ?>)
                            </div>
                        </div>

                        <h1 style="font-size: 2rem; margin-bottom: 1rem; color: var(--dark-color);">
                            <?php echo htmlspecialchars($product['name']); ?>
                        </h1>

                        <div style="font-size: 2.5rem; font-weight: 700; color: var(--primary-color); margin-bottom: 2rem;">
                            <?php echo formatPrice($product['price']); ?>
                        </div>

                        <div style="margin-bottom: 2rem;">
                            <h3 style="margin-bottom: 1rem; color: var(--dark-color);">Description</h3>
                            <p style="line-height: 1.8; color: var(--gray-color);">
                                <?php echo nl2br(htmlspecialchars($product['description'])); ?>
                            </p>
                        </div>

                        <?php if ($product['features']): ?>
                        <div style="margin-bottom: 2rem;">
                            <h3 style="margin-bottom: 1rem; color: var(--dark-color);">Key Features</h3>
                            <ul style="list-style: none; padding: 0;">
                                <?php 
                                $features = explode(',', $product['features']);
                                foreach ($features as $feature): 
                                ?>
                                <li style="padding: 0.5rem 0; color: var(--gray-color);">
                                    <i class="fas fa-check" style="color: var(--success-color); margin-right: 0.5rem;"></i>
                                    <?php echo htmlspecialchars(trim($feature)); ?>
                                </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                        <?php endif; ?>

                        <?php if ($product['specifications']): ?>
                        <div style="margin-bottom: 2rem;">
                            <h3 style="margin-bottom: 1rem; color: var(--dark-color);">Specifications</h3>
                            <div style="background: var(--light-color); padding: 1rem; border-radius: var(--border-radius);">
                                <p style="color: var(--gray-color); line-height: 1.8;">
                                    <?php echo nl2br(htmlspecialchars($product['specifications'])); ?>
                                </p>
                            </div>
                        </div>
                        <?php endif; ?>

                        <!-- Add to Cart Section -->
                        <div style="border-top: 1px solid #e0e0e0; padding-top: 2rem;">
                            <div style="display: flex; gap: 1rem; align-items: center; margin-bottom: 1rem;">
                                <label for="quantity" style="font-weight: 600;">Quantity:</label>
                                <input type="number" id="quantity" value="1" min="1" max="<?php echo $product['stock_quantity']; ?>" 
                                       style="width: 80px; padding: 0.5rem; border: 2px solid #e0e0e0; border-radius: var(--border-radius);">
                            </div>

                            <div style="display: flex; gap: 1rem;">
                                <?php if (isLoggedIn()): ?>
                                <button onclick="addToCart(<?php echo $product['id']; ?>)" class="btn btn-primary" style="flex: 1;">
                                    <i class="fas fa-cart-plus"></i> Add to Cart
                                </button>
                                <button onclick="buyNow(<?php echo $product['id']; ?>)" class="btn btn-success" style="flex: 1;">
                                    <i class="fas fa-bolt"></i> Buy Now
                                </button>
                                <?php else: ?>
                                <a href="login.php" class="btn btn-primary" style="flex: 1; text-align: center;">
                                    <i class="fas fa-sign-in-alt"></i> Login to Purchase
                                </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Related Products -->
            <?php if (!empty($related_products)): ?>
            <div style="margin-top: 4rem;">
                <h2 style="text-align: center; margin-bottom: 2rem; color: var(--primary-color);">
                    Related Products
                </h2>
                <div class="products-grid">
                    <?php foreach ($related_products as $related): ?>
                    <div class="product-card card-hover">
                        <div class="product-image">
                            <?php if ($related['image']): ?>
                                <img src="uploads/<?php echo htmlspecialchars($related['image']); ?>" 
                                     alt="<?php echo htmlspecialchars($related['name']); ?>">
                            <?php else: ?>
                                <i class="fas fa-wifi"></i>
                            <?php endif; ?>
                        </div>
                        <div class="product-info">
                            <h3 class="product-title"><?php echo htmlspecialchars($related['name']); ?></h3>
                            <div class="product-price"><?php echo formatPrice($related['price']); ?></div>
                            <a href="product-detail.php?id=<?php echo $related['id']; ?>" class="btn btn-outline" style="width: 100%; margin-top: 1rem;">
                                <i class="fas fa-eye"></i> View Details
                            </a>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="footer-bottom">
                <p>&copy; 2024 Airtel Kenya Store. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <script>
        function addToCart(productId) {
            const quantity = document.getElementById('quantity').value;
            
            fetch('api/add-to-cart.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    product_id: productId,
                    quantity: parseInt(quantity)
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

        function buyNow(productId) {
            addToCart(productId);
            setTimeout(() => {
                window.location.href = 'cart.php';
            }, 1000);
        }
    </script>
</body>
</html>