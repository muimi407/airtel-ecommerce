<?php
require_once 'config/config.php';

if (!isLoggedIn()) {
    redirect('login.php');
}

// Fetch user details
$stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Fetch user orders
$stmt = $db->prepare("SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC");
$stmt->execute([$_SESSION['user_id']]);
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

$success = '';
$error = '';

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_profile'])) {
    $full_name = sanitize($_POST['full_name']);
    $email = sanitize($_POST['email']);
    $phone = sanitize($_POST['phone']);
    $address = sanitize($_POST['address']);
    
    if (empty($full_name) || empty($email)) {
        $error = 'Name and email are required';
    } else {
        // Check if email is already taken by another user
        $stmt = $db->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
        $stmt->execute([$email, $_SESSION['user_id']]);
        
        if ($stmt->rowCount() > 0) {
            $error = 'Email is already taken by another user';
        } else {
            $stmt = $db->prepare("UPDATE users SET full_name = ?, email = ?, phone = ?, address = ? WHERE id = ?");
            if ($stmt->execute([$full_name, $email, $phone, $address, $_SESSION['user_id']])) {
                $success = 'Profile updated successfully';
                $user['full_name'] = $full_name;
                $user['email'] = $email;
                $user['phone'] = $phone;
                $user['address'] = $address;
                $_SESSION['full_name'] = $full_name;
            } else {
                $error = 'Failed to update profile';
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - <?php echo SITE_NAME; ?></title>
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
                <li><a href="profile.php" class="active"><i class="fas fa-user"></i> Profile</a></li>
                <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </nav>
    </header>

    <div style="padding: 2rem 0; background: var(--light-color); min-height: 80vh;">
        <div class="container">
            <h1 style="text-align: center; margin-bottom: 2rem; color: var(--primary-color);">
                <i class="fas fa-user"></i> My Profile
            </h1>

            <?php if ($error): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i> <?php echo $success; ?>
                </div>
            <?php endif; ?>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem;">
                <!-- Profile Information -->
                <div style="background: white; border-radius: var(--border-radius); box-shadow: var(--box-shadow);">
                    <div style="padding: 1.5rem; border-bottom: 1px solid #e0e0e0; background: var(--light-color);">
                        <h3><i class="fas fa-user-edit"></i> Profile Information</h3>
                    </div>
                    <div style="padding: 2rem;">
                        <form method="POST" action="">
                            <div class="form-group">
                                <label for="full_name"><i class="fas fa-user"></i> Full Name</label>
                                <input type="text" id="full_name" name="full_name" class="form-control" 
                                       value="<?php echo htmlspecialchars($user['full_name']); ?>" required>
                            </div>

                            <div class="form-group">
                                <label for="email"><i class="fas fa-envelope"></i> Email</label>
                                <input type="email" id="email" name="email" class="form-control" 
                                       value="<?php echo htmlspecialchars($user['email']); ?>" required>
                            </div>

                            <div class="form-group">
                                <label for="phone"><i class="fas fa-phone"></i> Phone Number</label>
                                <input type="tel" id="phone" name="phone" class="form-control" 
                                       value="<?php echo htmlspecialchars($user['phone']); ?>" 
                                       placeholder="+254 700 000 000">
                            </div>

                            <div class="form-group">
                                <label for="address"><i class="fas fa-map-marker-alt"></i> Address</label>
                                <textarea id="address" name="address" class="form-control" rows="3" 
                                          placeholder="Enter your full address"><?php echo htmlspecialchars($user['address']); ?></textarea>
                            </div>

                            <button type="submit" name="update_profile" class="btn btn-primary" style="width: 100%;">
                                <i class="fas fa-save"></i> Update Profile
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Account Stats -->
                <div style="background: white; border-radius: var(--border-radius); box-shadow: var(--box-shadow);">
                    <div style="padding: 1.5rem; border-bottom: 1px solid #e0e0e0; background: var(--light-color);">
                        <h3><i class="fas fa-chart-bar"></i> Account Overview</h3>
                    </div>
                    <div style="padding: 2rem;">
                        <div style="display: grid; gap: 1.5rem;">
                            <div style="text-align: center; padding: 1.5rem; background: var(--light-color); border-radius: var(--border-radius);">
                                <div style="font-size: 2rem; color: var(--primary-color); margin-bottom: 0.5rem;">
                                    <i class="fas fa-shopping-bag"></i>
                                </div>
                                <div style="font-size: 2rem; font-weight: 700; color: var(--dark-color);">
                                    <?php echo count($orders); ?>
                                </div>
                                <div style="color: var(--gray-color);">Total Orders</div>
                            </div>

                            <div style="text-align: center; padding: 1.5rem; background: var(--light-color); border-radius: var(--border-radius);">
                                <div style="font-size: 2rem; color: var(--success-color); margin-bottom: 0.5rem;">
                                    <i class="fas fa-calendar-alt"></i>
                                </div>
                                <div style="font-size: 1.2rem; font-weight: 600; color: var(--dark-color);">
                                    <?php echo date('F Y', strtotime($user['created_at'])); ?>
                                </div>
                                <div style="color: var(--gray-color);">Member Since</div>
                            </div>

                            <div style="text-align: center; padding: 1.5rem; background: var(--light-color); border-radius: var(--border-radius);">
                                <div style="font-size: 2rem; color: var(--warning-color); margin-bottom: 0.5rem;">
                                    <i class="fas fa-star"></i>
                                </div>
                                <div style="font-size: 1.2rem; font-weight: 600; color: var(--dark-color);">
                                    Premium
                                </div>
                                <div style="color: var(--gray-color);">Customer Status</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Order History -->
            <div style="background: white; border-radius: var(--border-radius); box-shadow: var(--box-shadow); margin-top: 2rem;">
                <div style="padding: 1.5rem; border-bottom: 1px solid #e0e0e0; background: var(--light-color);">
                    <h3><i class="fas fa-history"></i> Order History</h3>
                </div>
                <div style="padding: 1.5rem;">
                    <?php if (empty($orders)): ?>
                        <div style="text-align: center; padding: 2rem;">
                            <i class="fas fa-shopping-bag" style="font-size: 3rem; color: var(--gray-color); margin-bottom: 1rem;"></i>
                            <h4>No orders yet</h4>
                            <p style="color: var(--gray-color); margin-bottom: 2rem;">Start shopping to see your orders here.</p>
                            <a href="products.php" class="btn btn-primary">
                                <i class="fas fa-shopping-bag"></i> Start Shopping
                            </a>
                        </div>
                    <?php else: ?>
                        <div style="overflow-x: auto;">
                            <table style="width: 100%; border-collapse: collapse;">
                                <thead>
                                    <tr style="background: var(--light-color);">
                                        <th style="padding: 1rem; text-align: left; border-bottom: 2px solid #e0e0e0;">Order #</th>
                                        <th style="padding: 1rem; text-align: left; border-bottom: 2px solid #e0e0e0;">Date</th>
                                        <th style="padding: 1rem; text-align: left; border-bottom: 2px solid #e0e0e0;">Status</th>
                                        <th style="padding: 1rem; text-align: left; border-bottom: 2px solid #e0e0e0;">Total</th>
                                        <th style="padding: 1rem; text-align: left; border-bottom: 2px solid #e0e0e0;">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($orders as $order): ?>
                                    <tr style="border-bottom: 1px solid #f0f0f0;">
                                        <td style="padding: 1rem; font-weight: 600;">#<?php echo $order['id']; ?></td>
                                        <td style="padding: 1rem; color: var(--gray-color);">
                                            <?php echo date('M j, Y', strtotime($order['created_at'])); ?>
                                        </td>
                                        <td style="padding: 1rem;">
                                            <?php
                                            $status_colors = [
                                                'pending' => 'var(--warning-color)',
                                                'processing' => 'var(--primary-color)',
                                                'shipped' => 'var(--secondary-color)',
                                                'delivered' => 'var(--success-color)',
                                                'cancelled' => 'var(--danger-color)'
                                            ];
                                            $color = $status_colors[$order['status']] ?? 'var(--gray-color)';
                                            ?>
                                            <span style="background: <?php echo $color; ?>; color: white; padding: 0.25rem 0.5rem; border-radius: 4px; font-size: 0.8rem;">
                                                <?php echo ucfirst($order['status']); ?>
                                            </span>
                                        </td>
                                        <td style="padding: 1rem; font-weight: 600; color: var(--primary-color);">
                                            <?php echo formatPrice($order['total_amount']); ?>
                                        </td>
                                        <td style="padding: 1rem;">
                                            <a href="order-confirmation.php?order=<?php echo $order['id']; ?>" 
                                               class="btn btn-outline" style="padding: 0.5rem 1rem; font-size: 0.9rem;">
                                                <i class="fas fa-eye"></i> View
                                            </a>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
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