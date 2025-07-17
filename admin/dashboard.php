<?php
require_once '../config/config.php';

if (!isAdmin()) {
    redirect('login.php');
}

// Fetch statistics
$stats = [];

// Total products
$stmt = $db->prepare("SELECT COUNT(*) as count FROM products WHERE status = 'active'");
$stmt->execute();
$stats['products'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

// Total orders
$stmt = $db->prepare("SELECT COUNT(*) as count FROM orders");
$stmt->execute();
$stats['orders'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

// Total users
$stmt = $db->prepare("SELECT COUNT(*) as count FROM users");
$stmt->execute();
$stats['users'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

// Total revenue
$stmt = $db->prepare("SELECT SUM(total_amount) as total FROM orders WHERE payment_status = 'completed'");
$stmt->execute();
$result = $stmt->fetch(PDO::FETCH_ASSOC);
$stats['revenue'] = $result['total'] ?? 0;

// Recent orders
$stmt = $db->prepare("SELECT o.*, u.full_name 
                      FROM orders o 
                      JOIN users u ON o.user_id = u.id 
                      ORDER BY o.created_at DESC 
                      LIMIT 5");
$stmt->execute();
$recent_orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Low stock products
$stmt = $db->prepare("SELECT * FROM products WHERE stock_quantity <= 5 AND status = 'active' ORDER BY stock_quantity ASC LIMIT 5");
$stmt->execute();
$low_stock = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="admin-container">
        <!-- Sidebar -->
        <aside class="admin-sidebar">
            <div class="admin-logo">
                <h2><i class="fas fa-wifi"></i> Airtel Admin</h2>
                <p>Store Management</p>
            </div>
            <nav class="admin-nav">
                <ul>
                    <li><a href="dashboard.php" class="active"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                    <li><a href="products.php"><i class="fas fa-box"></i> Products</a></li>
                    <li><a href="add-product.php"><i class="fas fa-plus"></i> Add Product</a></li>
                    <li><a href="orders.php"><i class="fas fa-shopping-cart"></i> Orders</a></li>
                    <li><a href="../index.php" target="_blank"><i class="fas fa-external-link-alt"></i> View Store</a></li>
                    <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
                </ul>
            </nav>
        </aside>

        <!-- Main Content -->
        <main class="admin-main">
            <header class="admin-header">
                <h1><i class="fas fa-tachometer-alt"></i> Dashboard</h1>
                <div class="admin-user-info">
                    <span>Welcome, <?php echo htmlspecialchars($_SESSION['admin_name']); ?></span>
                    <a href="logout.php" class="btn btn-outline btn-sm">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </a>
                </div>
            </header>

            <div class="admin-content">
                <!-- Statistics Cards -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon" style="color: var(--admin-accent);">
                            <i class="fas fa-box"></i>
                        </div>
                        <div class="stat-number"><?php echo $stats['products']; ?></div>
                        <div class="stat-label">Active Products</div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-icon" style="color: var(--admin-success);">
                            <i class="fas fa-shopping-cart"></i>
                        </div>
                        <div class="stat-number"><?php echo $stats['orders']; ?></div>
                        <div class="stat-label">Total Orders</div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-icon" style="color: var(--admin-warning);">
                            <i class="fas fa-users"></i>
                        </div>
                        <div class="stat-number"><?php echo $stats['users']; ?></div>
                        <div class="stat-label">Registered Users</div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-icon" style="color: var(--admin-success);">
                            <i class="fas fa-money-bill-wave"></i>
                        </div>
                        <div class="stat-number"><?php echo formatPrice($stats['revenue']); ?></div>
                        <div class="stat-label">Total Revenue</div>
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 2rem;">
                    <!-- Recent Orders -->
                    <div class="admin-card">
                        <div class="admin-card-header">
                            <h3><i class="fas fa-clock"></i> Recent Orders</h3>
                            <a href="orders.php" class="btn btn-outline btn-sm">View All</a>
                        </div>
                        <div class="admin-card-body">
                            <?php if (empty($recent_orders)): ?>
                                <p style="text-align: center; color: #666; padding: 2rem;">No orders yet</p>
                            <?php else: ?>
                                <table class="admin-table">
                                    <thead>
                                        <tr>
                                            <th>Order #</th>
                                            <th>Customer</th>
                                            <th>Amount</th>
                                            <th>Status</th>
                                            <th>Date</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($recent_orders as $order): ?>
                                        <tr>
                                            <td>#<?php echo $order['id']; ?></td>
                                            <td><?php echo htmlspecialchars($order['full_name']); ?></td>
                                            <td><?php echo formatPrice($order['total_amount']); ?></td>
                                            <td>
                                                <?php
                                                $status_class = [
                                                    'pending' => 'badge-warning',
                                                    'processing' => 'badge-primary',
                                                    'shipped' => 'badge-primary',
                                                    'delivered' => 'badge-success',
                                                    'cancelled' => 'badge-danger'
                                                ];
                                                ?>
                                                <span class="badge <?php echo $status_class[$order['status']] ?? 'badge-primary'; ?>">
                                                    <?php echo ucfirst($order['status']); ?>
                                                </span>
                                            </td>
                                            <td><?php echo date('M j, Y', strtotime($order['created_at'])); ?></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Low Stock Alert -->
                    <div class="admin-card">
                        <div class="admin-card-header">
                            <h3><i class="fas fa-exclamation-triangle"></i> Low Stock Alert</h3>
                        </div>
                        <div class="admin-card-body">
                            <?php if (empty($low_stock)): ?>
                                <p style="text-align: center; color: var(--admin-success); padding: 1rem;">
                                    <i class="fas fa-check-circle"></i><br>
                                    All products are well stocked!
                                </p>
                            <?php else: ?>
                                <?php foreach ($low_stock as $product): ?>
                                <div style="display: flex; justify-content: space-between; align-items: center; padding: 1rem; border-bottom: 1px solid #f0f0f0;">
                                    <div>
                                        <strong><?php echo htmlspecialchars($product['name']); ?></strong>
                                        <br>
                                        <small style="color: var(--admin-danger);">
                                            Only <?php echo $product['stock_quantity']; ?> left
                                        </small>
                                    </div>
                                    <a href="edit-product.php?id=<?php echo $product['id']; ?>" class="btn btn-warning btn-sm">
                                        <i class="fas fa-edit"></i> Update
                                    </a>
                                </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="admin-card">
                    <div class="admin-card-header">
                        <h3><i class="fas fa-bolt"></i> Quick Actions</h3>
                    </div>
                    <div class="admin-card-body">
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
                            <a href="add-product.php" class="btn btn-primary" style="padding: 1.5rem; text-align: center;">
                                <i class="fas fa-plus" style="display: block; font-size: 2rem; margin-bottom: 0.5rem;"></i>
                                Add New Product
                            </a>
                            <a href="products.php" class="btn btn-success" style="padding: 1.5rem; text-align: center;">
                                <i class="fas fa-box" style="display: block; font-size: 2rem; margin-bottom: 0.5rem;"></i>
                                Manage Products
                            </a>
                            <a href="orders.php" class="btn btn-warning" style="padding: 1.5rem; text-align: center;">
                                <i class="fas fa-shopping-cart" style="display: block; font-size: 2rem; margin-bottom: 0.5rem;"></i>
                                View Orders
                            </a>
                            <a href="../index.php" target="_blank" class="btn btn-outline" style="padding: 1.5rem; text-align: center;">
                                <i class="fas fa-external-link-alt" style="display: block; font-size: 2rem; margin-bottom: 0.5rem;"></i>
                                View Store
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</body>
</html>