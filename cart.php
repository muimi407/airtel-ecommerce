<?php
require_once 'config/config.php';

if (!isLoggedIn()) {
    redirect('login.php');
}

// Handle cart updates
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'update':
                $cart_id = (int)$_POST['cart_id'];
                $quantity = (int)$_POST['quantity'];
                
                if ($quantity > 0) {
                    $stmt = $db->prepare("UPDATE cart SET quantity = ? WHERE id = ? AND user_id = ?");
                    $stmt->execute([$quantity, $cart_id, $_SESSION['user_id']]);
                }
                break;
                
            case 'remove':
                $cart_id = (int)$_POST['cart_id'];
                $stmt = $db->prepare("DELETE FROM cart WHERE id = ? AND user_id = ?");
                $stmt->execute([$cart_id, $_SESSION['user_id']]);
                break;
        }
        redirect('cart.php');
    }
}

// Fetch cart items
$stmt = $db->prepare("SELECT c.*, p.name, p.price, p.image, p.stock_quantity 
                      FROM cart c 
                      JOIN products p ON c.product_id = p.id 
                      WHERE c.user_id = ? 
                      ORDER BY c.created_at DESC");
$stmt->execute([$_SESSION['user_id']]);
$cart_items = $stmt->fetchAll(PDO::FETCH_ASSOC);

$total = 0;
foreach ($cart_items as $item) {
    $total += $item['price'] * $item['quantity'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shopping Cart - <?php echo SITE_NAME; ?></title>
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
                <li><a href="cart.php" class="active"><i class="fas fa-shopping-cart"></i> Cart</a></li>
                <li><a href="profile.php"><i class="fas fa-user"></i> Profile</a></li>
                <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </nav>
    </header>

    <div style="padding: 2rem 0; background: var(--light-color); min-height: 80vh;">
        <div class="container">
            <h1 style="text-align: center; margin-bottom: 2rem; color: var(--primary-color);">
                <i class="fas fa-shopping-cart"></i> Your Shopping Cart
            </h1>

            <?php if (empty($cart_items)): ?>
                <div style="text-align: center; padding: 4rem; background: white; border-radius: var(--border-radius); box-shadow: var(--box-shadow);">
                    <i class="fas fa-shopping-cart" style="font-size: 4rem; color: var(--gray-color); margin-bottom: 1rem;"></i>
                    <h3>Your cart is empty</h3>
                    <p style="margin-bottom: 2rem;">Start shopping to add items to your cart.</p>
                    <a href="products.php" class="btn btn-primary">
                        <i class="fas fa-shopping-bag"></i> Continue Shopping
                    </a>
                </div>
            <?php else: ?>
                <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 2rem;">
                    <!-- Cart Items -->
                    <div style="background: white; border-radius: var(--border-radius); box-shadow: var(--box-shadow); overflow: hidden;">
                        <div style="padding: 1.5rem; border-bottom: 1px solid #e0e0e0; background: var(--light-color);">
                            <h3><i class="fas fa-list"></i> Cart Items (<?php echo count($cart_items); ?>)</h3>
                        </div>
                        
                        <?php foreach ($cart_items as $item): ?>
                        <div class="cart-item" style="padding: 1.5rem; border-bottom: 1px solid #e0e0e0;">
                            <div style="display: flex; gap: 1rem; align-items: center;">
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
                                        Price: <?php echo formatPrice($item['price']); ?>
                                    </p>
                                    <p style="color: var(--success-color); font-size: 0.9rem;">
                                        <i class="fas fa-check-circle"></i> In Stock (<?php echo $item['stock_quantity']; ?> available)
                                    </p>
                                </div>
                                
                                <div style="display: flex; align-items: center; gap: 1rem;">
                                    <form method="POST" style="display: flex; align-items: center; gap: 0.5rem;">
                                        <input type="hidden" name="action" value="update">
                                        <input type="hidden" name="cart_id" value="<?php echo $item['id']; ?>">
                                        <label for="qty_<?php echo $item['id']; ?>" style="font-size: 0.9rem;">Qty:</label>
                                        <input type="number" id="qty_<?php echo $item['id']; ?>" name="quantity" 
                                               value="<?php echo $item['quantity']; ?>" 
                                               min="1" max="<?php echo $item['stock_quantity']; ?>"
                                               style="width: 60px; padding: 0.25rem; border: 1px solid #ddd; border-radius: 4px;"
                                               onchange="this.form.submit()">
                                    </form>
                                    
                                    <div style="text-align: right;">
                                        <div style="font-weight: 600; font-size: 1.1rem; color: var(--primary-color);">
                                            <?php echo formatPrice($item['price'] * $item['quantity']); ?>
                                        </div>
                                        <form method="POST" style="margin-top: 0.5rem;">
                                            <input type="hidden" name="action" value="remove">
                                            <input type="hidden" name="cart_id" value="<?php echo $item['id']; ?>">
                                            <button type="submit" class="btn" style="background: var(--danger-color); color: white; padding: 0.25rem 0.5rem; font-size: 0.8rem;"
                                                    onclick="return confirm('Remove this item from cart?')">
                                                <i class="fas fa-trash"></i> Remove
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- Cart Summary -->
                    <div style="background: white; border-radius: var(--border-radius); box-shadow: var(--box-shadow); height: fit-content;">
                        <div style="padding: 1.5rem; border-bottom: 1px solid #e0e0e0; background: var(--light-color);">
                            <h3><i class="fas fa-calculator"></i> Order Summary</h3>
                        </div>
                        
                        <div style="padding: 1.5rem;">
                            <div style="display: flex; justify-content: space-between; margin-bottom: 1rem;">
                                <span>Subtotal:</span>
                                <span><?php echo formatPrice($total); ?></span>
                            </div>
                            
                            <div style="display: flex; justify-content: space-between; margin-bottom: 1rem;">
                                <span>Shipping:</span>
                                <span style="color: var(--success-color);">FREE</span>
                            </div>
                            
                            <div style="display: flex; justify-content: space-between; margin-bottom: 1rem;">
                                <span>Tax:</span>
                                <span><?php echo formatPrice($total * 0.16); ?></span>
                            </div>
                            
                            <hr style="margin: 1rem 0; border: none; border-top: 2px solid #e0e0e0;">
                            
                            <div style="display: flex; justify-content: space-between; font-size: 1.2rem; font-weight: 700; color: var(--primary-color);">
                                <span>Total:</span>
                                <span><?php echo formatPrice($total * 1.16); ?></span>
                            </div>
                            
                            <div style="margin-top: 2rem;">
                                <a href="checkout.php" class="btn btn-primary" style="width: 100%; text-align: center; margin-bottom: 1rem;">
                                    <i class="fas fa-credit-card"></i> Proceed to Checkout
                                </a>
                                <a href="products.php" class="btn btn-outline" style="width: 100%; text-align: center;">
                                    <i class="fas fa-arrow-left"></i> Continue Shopping
                                </a>
                            </div>
                        </div>
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
</body>
</html>