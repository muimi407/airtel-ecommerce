<?php
require_once 'config/config.php';

// Get filter parameters
$category_filter = isset($_GET['category']) ? (int)$_GET['category'] : 0;
$search = isset($_GET['search']) ? sanitize($_GET['search']) : '';
$sort = isset($_GET['sort']) ? sanitize($_GET['sort']) : 'name';

// Build query
$where_conditions = ["p.status = 'active'"];
$params = [];

if ($category_filter > 0) {
    $where_conditions[] = "p.category_id = ?";
    $params[] = $category_filter;
}

if (!empty($search)) {
    $where_conditions[] = "(p.name LIKE ? OR p.description LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$where_clause = implode(' AND ', $where_conditions);

// Sort options
$sort_options = [
    'name' => 'p.name ASC',
    'price_low' => 'p.price ASC',
    'price_high' => 'p.price DESC',
    'newest' => 'p.created_at DESC'
];

$order_by = isset($sort_options[$sort]) ? $sort_options[$sort] : 'p.name ASC';

// Fetch products
$stmt = $db->prepare("SELECT p.*, c.name as category_name 
                      FROM products p 
                      LEFT JOIN categories c ON p.category_id = c.id 
                      WHERE $where_clause 
                      ORDER BY $order_by");
$stmt->execute($params);
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch categories for filter
$stmt = $db->prepare("SELECT * FROM categories ORDER BY name");
$stmt->execute();
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Products - <?php echo SITE_NAME; ?></title>
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
                <li><a href="products.php" class="active"><i class="fas fa-shopping-bag"></i> Products</a></li>
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
            <h1 style="text-align: center; margin-bottom: 2rem; color: var(--primary-color);">
                <i class="fas fa-shopping-bag"></i> Our Products
            </h1>

            <!-- Filters and Search -->
            <div style="background: white; padding: 2rem; border-radius: var(--border-radius); margin-bottom: 2rem; box-shadow: var(--box-shadow);">
                <form method="GET" action="" style="display: grid; grid-template-columns: 1fr 1fr 1fr auto; gap: 1rem; align-items: end;">
                    <div class="form-group" style="margin-bottom: 0;">
                        <label for="search"><i class="fas fa-search"></i> Search Products</label>
                        <input type="text" id="search" name="search" class="form-control" 
                               value="<?php echo htmlspecialchars($search); ?>" placeholder="Search products...">
                    </div>
                    
                    <div class="form-group" style="margin-bottom: 0;">
                        <label for="category"><i class="fas fa-filter"></i> Category</label>
                        <select id="category" name="category" class="form-control">
                            <option value="0">All Categories</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?php echo $category['id']; ?>" 
                                        <?php echo $category_filter == $category['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($category['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group" style="margin-bottom: 0;">
                        <label for="sort"><i class="fas fa-sort"></i> Sort By</label>
                        <select id="sort" name="sort" class="form-control">
                            <option value="name" <?php echo $sort == 'name' ? 'selected' : ''; ?>>Name A-Z</option>
                            <option value="price_low" <?php echo $sort == 'price_low' ? 'selected' : ''; ?>>Price: Low to High</option>
                            <option value="price_high" <?php echo $sort == 'price_high' ? 'selected' : ''; ?>>Price: High to Low</option>
                            <option value="newest" <?php echo $sort == 'newest' ? 'selected' : ''; ?>>Newest First</option>
                        </select>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-search"></i> Filter
                    </button>
                </form>
            </div>

            <!-- Products Grid -->
            <?php if (empty($products)): ?>
                <div style="text-align: center; padding: 4rem; background: white; border-radius: var(--border-radius);">
                    <i class="fas fa-search" style="font-size: 4rem; color: var(--gray-color); margin-bottom: 1rem;"></i>
                    <h3>No products found</h3>
                    <p>Try adjusting your search or filter criteria.</p>
                    <a href="products.php" class="btn btn-primary">View All Products</a>
                </div>
            <?php else: ?>
                <div class="products-grid">
                    <?php foreach ($products as $product): ?>
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
                            <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 0.5rem;">
                                <h3 class="product-title"><?php echo htmlspecialchars($product['name']); ?></h3>
                                <span style="background: var(--primary-color); color: white; padding: 0.25rem 0.5rem; border-radius: 4px; font-size: 0.8rem;">
                                    <?php echo htmlspecialchars($product['category_name']); ?>
                                </span>
                            </div>
                            
                            <p class="product-description"><?php echo htmlspecialchars(substr($product['description'], 0, 100)) . '...'; ?></p>
                            <div class="product-price"><?php echo formatPrice($product['price']); ?></div>
                            
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                                <span style="color: var(--success-color); font-weight: 600;">
                                    <i class="fas fa-check-circle"></i> In Stock (<?php echo $product['stock_quantity']; ?>)
                                </span>
                            </div>
                            
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
                                    <i class="fas fa-eye"></i> Details
                                </a>
                                <?php if (isLoggedIn()): ?>
                                <button onclick="addToCart(<?php echo $product['id']; ?>)" class="btn btn-primary" style="flex: 1;">
                                    <i class="fas fa-cart-plus"></i> Add to Cart
                                </button>
                                <?php else: ?>
                                <a href="login.php" class="btn btn-primary" style="flex: 1;">
                                    <i class="fas fa-sign-in-alt"></i> Login to Buy
                                </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="footer-content">
                <div class="footer-section">
                    <h3><i class="fas fa-wifi"></i> Airtel Store</h3>
                    <p>Your trusted partner for premium networking solutions in Kenya.</p>
                </div>
                <div class="footer-section">
                    <h3>Categories</h3>
                    <?php foreach ($categories as $category): ?>
                        <p><a href="products.php?category=<?php echo $category['id']; ?>"><?php echo htmlspecialchars($category['name']); ?></a></p>
                    <?php endforeach; ?>
                </div>
                <div class="footer-section">
                    <h3>Customer Service</h3>
                    <p><a href="tel:+254700000000">+254 700 000 000</a></p>
                    <p><a href="mailto:support@airtel.co.ke">support@airtel.co.ke</a></p>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; 2024 Airtel Kenya Store. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <script src="assets/js/main.js"></script>
    <script>
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
                    // Optional: Update cart count in header
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