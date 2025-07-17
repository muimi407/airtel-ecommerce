<?php
require_once 'config/config.php';

if (!isLoggedIn()) {
    redirect('login.php');
}

$order_id = isset($_GET['order']) ? (int)$_GET['order'] : 0;

if ($order_id <= 0) {
    redirect('profile.php');
}

// Fetch order details
$stmt = $db->prepare("SELECT o.*, u.full_name, u.email 
                      FROM orders o 
                      JOIN users u ON o.user_id = u.id 
                      WHERE o.id = ? AND o.user_id = ?");
$stmt->execute([$order_id, $_SESSION['user_id']]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    redirect('profile.php');
}

// Fetch order items
$stmt = $db->prepare("SELECT oi.*, p.name, p.image 
                      FROM order_items oi 
                      JOIN products p ON oi.product_id = p.id 
                      WHERE oi.order_id = ?");
$stmt->execute([$order_id]);
$order_items = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Confirmation - <?php echo SITE_NAME; ?></title>
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
                <li><a href="profile.php"><i class="fas fa-user"></i> Profile</a></li>
                <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </nav>
    </header>

    <div style="padding: 2rem 0; background: var(--light-color); min-height: 80vh;">
        <div class="container">
            <!-- Success Message -->
            <div style="text-align: center; background: white; padding: 3rem; border-radius: var(--border-radius); box-shadow: var(--box-shadow); margin-bottom: 2rem;">
                <div style="font-size: 4rem; color: var(--success-color); margin-bottom: 1rem;">
                    <i class="fas fa-check-circle"></i>
                </div>
                <h1 style="color: var(--success-color); margin-bottom: 1rem;">Order Placed Successfully!</h1>
                <p style="font-size: 1.1rem; color: var(--gray-color); margin-bottom: 2rem;">
                    Thank you for your order. We'll send you a confirmation email shortly.
                </p>
                <div style="display: flex; gap: 1rem; justify-content: center;">
                    <a href="products.php" class="btn btn-primary">
                        <i class="fas fa-shopping-bag"></i> Continue Shopping
                    </a>
                    <a href="profile.php" class="btn btn-outline">
                        <i class="fas fa-user"></i> View Orders
                    </a>
                </div>
            </div>

            <!-- Order Details -->
            <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 2rem;">
                <!-- Order Items -->
                <div style="background: white; border-radius: var(--border-radius); box-shadow: var(--box-shadow);">
                    <div style="padding: 1.5rem; border-bottom: 1px solid #e0e0e0; background: var(--light-color);">
                        <h3><i class="fas fa-box"></i> Order #<?php echo $order_id; ?></h3>
                        <p style="margin: 0; color: var(--gray-color);">
                            Placed on <?php echo date('F j, Y \a\t g:i A', strtotime($order['created_at'])); ?>
                        </p>
                    </div>

                    <div style="padding: 1.5rem;">
                        <?php foreach ($order_items as $item): ?>
                        <div style="display: flex; gap: 1rem; margin-bottom: 1rem; padding-bottom: 1rem; border-bottom: 1px solid #f0f0f0;">
                            <div style="width: 80px; height: 80px; background: var(--light-color); border-radius: var(--border-radius); display: flex; align-items: center; justify-content: center;">
                                <?php if ($item['image']): ?>
                                    <img src="uploads/<?php echo htmlspecialchars($item['image']); ?>" 
                                         alt="<?php echo htmlspecialchars($item['name']); ?>"
                                         style="width: 100%; height: 100%; object-fit: cover; border-radius: var(--border-radius);">
                                <?php else: ?>
                                    <i class="fas fa-wifi" style="font-size: 2rem; color: var(--gray-color);"></i>
                                <?php endif; ?>
                            </div>
                            <div style="flex: 1;">
                                <h4 style="margin-bottom: 0.5rem;"><?php echo htmlspecialchars($item['name']); ?></h4>
                                <p style="color: var(--gray-color); margin-bottom: 0.5rem;">
                                    Quantity: <?php echo $item['quantity']; ?>
                                </p>
                                <p style="color: var(--gray-color);">
                                    Price: <?php echo formatPrice($item['price']); ?> each
                                </p>
                            </div>
                            <div style="text-align: right;">
                                <div style="font-weight: 600; font-size: 1.1rem; color: var(--primary-color);">
                                    <?php echo formatPrice($item['price'] * $item['quantity']); ?>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Order Summary -->
                <div>
                    <!-- Payment & Shipping Info -->
                    <div style="background: white; border-radius: var(--border-radius); box-shadow: var(--box-shadow); margin-bottom: 2rem;">
                        <div style="padding: 1.5rem; border-bottom: 1px solid #e0e0e0; background: var(--light-color);">
                            <h3><i class="fas fa-info-circle"></i> Order Information</h3>
                        </div>
                        <div style="padding: 1.5rem;">
                            <div style="margin-bottom: 1rem;">
                                <strong>Status:</strong>
                                <span style="background: var(--warning-color); color: white; padding: 0.25rem 0.5rem; border-radius: 4px; font-size: 0.8rem; margin-left: 0.5rem;">
                                    <?php echo ucfirst($order['status']); ?>
                                </span>
                            </div>
                            <div style="margin-bottom: 1rem;">
                                <strong>Payment Method:</strong>
                                <span style="margin-left: 0.5rem;">
                                    <?php echo $order['payment_method'] == 'mpesa' ? 'M-Pesa' : 'Credit/Debit Card'; ?>
                                </span>
                            </div>
                            <div style="margin-bottom: 1rem;">
                                <strong>Phone:</strong>
                                <span style="margin-left: 0.5rem;"><?php echo htmlspecialchars($order['phone']); ?></span>
                            </div>
                            <div>
                                <strong>Shipping Address:</strong>
                                <p style="margin: 0.5rem 0 0 0; color: var(--gray-color);">
                                    <?php echo nl2br(htmlspecialchars($order['shipping_address'])); ?>
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Order Total -->
                    <div style="background: white; border-radius: var(--border-radius); box-shadow: var(--box-shadow);">
                        <div style="padding: 1.5rem; border-bottom: 1px solid #e0e0e0; background: var(--light-color);">
                            <h3><i class="fas fa-calculator"></i> Order Total</h3>
                        </div>
                        <div style="padding: 1.5rem;">
                            <div style="display: flex; justify-content: space-between; font-size: 1.2rem; font-weight: 700; color: var(--primary-color);">
                                <span>Total Paid:</span>
                                <span><?php echo formatPrice($order['total_amount']); ?></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Next Steps -->
            <div style="background: white; border-radius: var(--border-radius); box-shadow: var(--box-shadow); margin-top: 2rem; padding: 2rem;">
                <h3 style="margin-bottom: 1rem; color: var(--primary-color);">
                    <i class="fas fa-clock"></i> What happens next?
                </h3>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 2rem;">
                    <div style="text-align: center;">
                        <div style="font-size: 2rem; color: var(--primary-color); margin-bottom: 1rem;">
                            <i class="fas fa-envelope"></i>
                        </div>
                        <h4>Confirmation Email</h4>
                        <p style="color: var(--gray-color);">You'll receive an order confirmation email within 5 minutes.</p>
                    </div>
                    <div style="text-align: center;">
                        <div style="font-size: 2rem; color: var(--primary-color); margin-bottom: 1rem;">
                            <i class="fas fa-cogs"></i>
                        </div>
                        <h4>Processing</h4>
                        <p style="color: var(--gray-color);">We'll prepare your order for shipping within 24 hours.</p>
                    </div>
                    <div style="text-align: center;">
                        <div style="font-size: 2rem; color: var(--primary-color); margin-bottom: 1rem;">
                            <i class="fas fa-shipping-fast"></i>
                        </div>
                        <h4>Delivery</h4>
                        <p style="color: var(--gray-color);">Your order will be delivered within 2-3 business days.</p>
                    </div>
                </div>
            </div>
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
</body>
</html>