<?php
require_once 'config/config.php';

if (!isLoggedIn()) {
    redirect('login.php');
}

// Fetch user details
$stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Fetch cart items
$stmt = $db->prepare("SELECT c.*, p.name, p.price, p.image 
                      FROM cart c 
                      JOIN products p ON c.product_id = p.id 
                      WHERE c.user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$cart_items = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($cart_items)) {
    redirect('cart.php');
}

$subtotal = 0;
foreach ($cart_items as $item) {
    $subtotal += $item['price'] * $item['quantity'];
}
$tax = $subtotal * 0.16;
$total = $subtotal + $tax;

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $shipping_address = sanitize($_POST['shipping_address']);
    $phone = sanitize($_POST['phone']);
    $payment_method = sanitize($_POST['payment_method']);
    
    if (empty($shipping_address) || empty($phone)) {
        $error = 'Please fill in all required fields';
    } else {
        try {
            $db->beginTransaction();
            
            // Create order
            $stmt = $db->prepare("INSERT INTO orders (user_id, total_amount, shipping_address, phone, payment_method) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$_SESSION['user_id'], $total, $shipping_address, $phone, $payment_method]);
            $order_id = $db->lastInsertId();
            
            // Add order items
            foreach ($cart_items as $item) {
                $stmt = $db->prepare("INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)");
                $stmt->execute([$order_id, $item['product_id'], $item['quantity'], $item['price']]);
                
                // Update product stock
                $stmt = $db->prepare("UPDATE products SET stock_quantity = stock_quantity - ? WHERE id = ?");
                $stmt->execute([$item['quantity'], $item['product_id']]);
            }
            
            // Clear cart
            $stmt = $db->prepare("DELETE FROM cart WHERE user_id = ?");
            $stmt->execute([$_SESSION['user_id']]);
            
            $db->commit();

            // ---- MPESA PAYMENT LOGIC BLOCK ADDED BELOW ----
            if ($payment_method === 'mpesa') {
                require_once 'mpesa_stkpush.php';
                $mpesaResponse = initiateStkPush($phone, $total, $order_id, $_SESSION['user_id']);
                redirect('mpesa-status.php?order=' . $order_id);
            } else {
                redirect('order-confirmation.php?order=' . $order_id);
            }
            // ---- END MPESA BLOCK ----

        } catch (Exception $e) {
            $db->rollBack();
            $error = 'Order processing failed. Please try again.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - <?php echo SITE_NAME; ?></title>
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
                <li><a href="cart.php"><i class="fas fa-shopping-cart"></i> Cart</a></li>
                <li><a href="profile.php"><i class="fas fa-user"></i> Profile</a></li>
                <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </nav>
    </header>

    <div style="padding: 2rem 0; background: var(--light-color); min-height: 80vh;">
        <div class="container">
            <h1 style="text-align: center; margin-bottom: 2rem; color: var(--primary-color);">
                <i class="fas fa-credit-card"></i> Checkout
            </h1>

            <?php if ($error): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 2rem;">
                <!-- Checkout Form -->
                <div style="background: white; border-radius: var(--border-radius); box-shadow: var(--box-shadow); padding: 2rem;">
                    <h3 style="margin-bottom: 2rem; color: var(--dark-color);">
                        <i class="fas fa-shipping-fast"></i> Shipping Information
                    </h3>

                    <form method="POST" action="">
                        <div class="form-group">
                            <label for="full_name"><i class="fas fa-user"></i> Full Name</label>
                            <input type="text" id="full_name" class="form-control" 
                                   value="<?php echo htmlspecialchars($user['full_name']); ?>" readonly>
                        </div>

                        <div class="form-group">
                            <label for="email"><i class="fas fa-envelope"></i> Email</label>
                            <input type="email" id="email" class="form-control" 
                                   value="<?php echo htmlspecialchars($user['email']); ?>" readonly>
                        </div>

                        <div class="form-group">
                            <label for="phone"><i class="fas fa-phone"></i> Phone Number *</label>
                            <input type="tel" id="phone" name="phone" class="form-control" 
                                   value="<?php echo htmlspecialchars($user['phone']); ?>" 
                                   placeholder="+254 700 000 000" required>
                        </div>

                        <div class="form-group">
                            <label for="shipping_address"><i class="fas fa-map-marker-alt"></i> Shipping Address *</label>
                            <textarea id="shipping_address" name="shipping_address" class="form-control" 
                                      rows="3" placeholder="Enter your full address including city and postal code" required><?php echo htmlspecialchars($user['address']); ?></textarea>
                        </div>

                        <h3 style="margin: 2rem 0 1rem; color: var(--dark-color);">
                            <i class="fas fa-credit-card"></i> Payment Method
                        </h3>

                        <div style="display: grid; gap: 1rem;">
                            <label style="display: flex; align-items: center; padding: 1rem; border: 2px solid #e0e0e0; border-radius: var(--border-radius); cursor: pointer; transition: var(--transition);">
                                <input type="radio" name="payment_method" value="mpesa" checked style="margin-right: 1rem;">
                                <div style="display: flex; align-items: center; gap: 1rem;">
                                    <i class="fas fa-mobile-alt" style="font-size: 2rem; color: var(--success-color);"></i>
                                    <div>
                                        <strong>M-Pesa</strong>
                                        <p style="margin: 0; color: var(--gray-color); font-size: 0.9rem;">Pay with your mobile money</p>
                                    </div>
                                </div>
                            </label>

                            <label style="display: flex; align-items: center; padding: 1rem; border: 2px solid #e0e0e0; border-radius: var(--border-radius); cursor: pointer; transition: var(--transition);">
                                <input type="radio" name="payment_method" value="card" style="margin-right: 1rem;">
                                <div style="display: flex; align-items: center; gap: 1rem;">
                                    <i class="fas fa-credit-card" style="font-size: 2rem; color: var(--primary-color);"></i>
                                    <div>
                                        <strong>Credit/Debit Card</strong>
                                        <p style="margin: 0; color: var(--gray-color); font-size: 0.9rem;">Visa, Mastercard accepted</p>
                                    </div>
                                </div>
                            </label>
                        </div>

                        <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 2rem; padding: 1rem;">
                            <i class="fas fa-lock"></i> Place Order - <?php echo formatPrice($total); ?>
                        </button>
                    </form>
                </div>

                <!-- Order Summary -->
                <div style="background: white; border-radius: var(--border-radius); box-shadow: var(--box-shadow); height: fit-content;">
                    <div style="padding: 1.5rem; border-bottom: 1px solid #e0e0e0; background: var(--light-color);">
                        <h3><i class="fas fa-receipt"></i> Order Summary</h3>
                    </div>

                    <div style="padding: 1.5rem;">
                        <!-- Order Items -->
                        <?php foreach ($cart_items as $item): ?>
                        <div style="display: flex; gap: 1rem; margin-bottom: 1rem; padding-bottom: 1rem; border-bottom: 1px solid #f0f0f0;">
                            <div style="width: 50px; height: 50px; background: var(--light-color); border-radius: var(--border-radius); display: flex; align-items: center; justify-content: center;">
                                <?php if ($item['image']): ?>
                                    <img src="uploads/<?php echo htmlspecialchars($item['image']); ?>" 
                                         alt="<?php echo htmlspecialchars($item['name']); ?>"
                                         style="width: 100%; height: 100%; object-fit: cover; border-radius: var(--border-radius);">
                                <?php else: ?>
                                    <i class="fas fa-wifi" style="color: var(--gray-color);"></i>
                                <?php endif; ?>
                            </div>
                            <div style="flex: 1;">
                                <h5 style="margin-bottom: 0.25rem; font-size: 0.9rem;"><?php echo htmlspecialchars($item['name']); ?></h5>
                                <p style="margin: 0; color: var(--gray-color); font-size: 0.8rem;">
                                    Qty: <?php echo $item['quantity']; ?> × <?php echo formatPrice($item['price']); ?>
                                </p>
                            </div>
                            <div style="font-weight: 600; color: var(--primary-color);">
                                <?php echo formatPrice($item['price'] * $item['quantity']); ?>
                            </div>
                        </div>
                        <?php endforeach; ?>

                        <!-- Totals -->
                        <div style="margin-top: 1rem;">
                            <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
                                <span>Subtotal:</span>
                                <span><?php echo formatPrice($subtotal); ?></span>
                            </div>
                            
                            <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
                                <span>Shipping:</span>
                                <span style="color: var(--success-color);">FREE</span>
                            </div>
                            
                            <div style="display: flex; justify-content: space-between; margin-bottom: 1rem;">
                                <span>Tax (16%):</span>
                                <span><?php echo formatPrice($tax); ?></span>
                            </div>
                            
                            <hr style="margin: 1rem 0; border: none; border-top: 2px solid #e0e0e0;">
                            
                            <div style="display: flex; justify-content: space-between; font-size: 1.2rem; font-weight: 700; color: var(--primary-color);">
                                <span>Total:</span>
                                <span><?php echo formatPrice($total); ?></span>
                            </div>
                        </div>
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

    <script>
        // Payment method selection styling
        document.querySelectorAll('input[name="payment_method"]').forEach(radio => {
            radio.addEventListener('change', function() {
                document.querySelectorAll('label').forEach(label => {
                    if (label.querySelector('input[name="payment_method"]')) {
                        label.style.borderColor = '#e0e0e0';
                        label.style.backgroundColor = 'white';
                    }
                });
                
                this.closest('label').style.borderColor = 'var(--primary-color)';
                this.closest('label').style.backgroundColor = 'rgba(230, 0, 18, 0.05)';
            });
        });

        // Initialize first option
        document.querySelector('input[name="payment_method"]:checked').dispatchEvent(new Event('change'));
    </script>
</body>
</html>
