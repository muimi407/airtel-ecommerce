<?php
require_once '../config/config.php';

if (!isAdmin()) {
    redirect('login.php');
}

$success = '';
$error = '';

// Handle status update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_status'])) {
    $order_id = (int)$_POST['order_id'];
    $new_status = sanitize($_POST['status']);
    
    $valid_statuses = ['pending', 'processing', 'shipped', 'delivered', 'cancelled'];
    
    if (in_array($new_status, $valid_statuses)) {
        $stmt = $db->prepare("UPDATE orders SET status = ? WHERE id = ?");
        if ($stmt->execute([$new_status, $order_id])) {
            $success = 'Order status updated successfully';
        } else {
            $error = 'Failed to update order status';
        }
    } else {
        $error = 'Invalid status';
    }
}

// Fetch orders with user info
$stmt = $db->prepare("SELECT o.*, u.full_name, u.email, u.phone as user_phone 
                      FROM orders o 
                      JOIN users u ON o.user_id = u.id 
                      ORDER BY o.created_at DESC");
$stmt->execute();
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Orders - Admin</title>
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
                    <li><a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                    <li><a href="products.php"><i class="fas fa-box"></i> Products</a></li>
                    <li><a href="add-product.php"><i class="fas fa-plus"></i> Add Product</a></li>
                    <li><a href="orders.php" class="active"><i class="fas fa-shopping-cart"></i> Orders</a></li>
                    <li><a href="../index.php" target="_blank"><i class="fas fa-external-link-alt"></i> View Store</a></li>
                    <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
                </ul>
            </nav>
        </aside>

        <!-- Main Content -->
        <main class="admin-main">
            <header class="admin-header">
                <h1><i class="fas fa-shopping-cart"></i> Manage Orders</h1>
                <div class="admin-user-info">
                    <span><?php echo count($orders); ?> Total Orders</span>
                </div>
            </header>

            <div class="admin-content">
                <?php if ($success): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i> <?php echo $success; ?>
                    </div>
                <?php endif; ?>

                <?php if ($error): ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
                    </div>
                <?php endif; ?>

                <div class="admin-card">
                    <div class="admin-card-header">
                        <h3><i class="fas fa-list"></i> All Orders</h3>
                    </div>
                    <div class="admin-card-body">
                        <?php if (empty($orders)): ?>
                            <div style="text-align: center; padding: 3rem;">
                                <i class="fas fa-shopping-cart" style="font-size: 4rem; color: #ddd; margin-bottom: 1rem;"></i>
                                <h3>No orders found</h3>
                                <p style="color: #666;">Orders will appear here when customers make purchases.</p>
                            </div>
                        <?php else: ?>
                            <div style="overflow-x: auto;">
                                <table class="admin-table">
                                    <thead>
                                        <tr>
                                            <th>Order #</th>
                                            <th>Customer</th>
                                            <th>Contact</th>
                                            <th>Amount</th>
                                            <th>Payment</th>
                                            <th>Status</th>
                                            <th>Date</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($orders as $order): ?>
                                        <tr>
                                            <td style="font-weight: 600;">#<?php echo $order['id']; ?></td>
                                            <td>
                                                <strong><?php echo htmlspecialchars($order['full_name']); ?></strong>
                                                <br>
                                                <small style="color: #666;"><?php echo htmlspecialchars($order['email']); ?></small>
                                            </td>
                                            <td>
                                                <i class="fas fa-phone"></i> <?php echo htmlspecialchars($order['phone']); ?>
                                                <br>
                                                <small style="color: #666;">
                                                    <i class="fas fa-map-marker-alt"></i> 
                                                    <?php echo htmlspecialchars(substr($order['shipping_address'], 0, 30)) . '...'; ?>
                                                </small>
                                            </td>
                                            <td style="font-weight: 600; color: var(--admin-success);">
                                                <?php echo formatPrice($order['total_amount']); ?>
                                            </td>
                                            <td>
                                                <span class="badge badge-primary">
                                                    <?php echo $order['payment_method'] == 'mpesa' ? 'M-Pesa' : 'Card'; ?>
                                                </span>
                                                <br>
                                                <small style="color: #666;">
                                                    <?php echo ucfirst($order['payment_status']); ?>
                                                </small>
                                            </td>
                                            <td>
                                                <form method="POST" style="display: inline;">
                                                    <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                                    <select name="status" class="form-control" style="width: auto; padding: 0.25rem;" 
                                                            onchange="this.form.submit()">
                                                        <option value="pending" <?php echo $order['status'] == 'pending' ? 'selected' : ''; ?>>Pending</option>
                                                        <option value="processing" <?php echo $order['status'] == 'processing' ? 'selected' : ''; ?>>Processing</option>
                                                        <option value="shipped" <?php echo $order['status'] == 'shipped' ? 'selected' : ''; ?>>Shipped</option>
                                                        <option value="delivered" <?php echo $order['status'] == 'delivered' ? 'selected' : ''; ?>>Delivered</option>
                                                        <option value="cancelled" <?php echo $order['status'] == 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                                    </select>
                                                    <input type="hidden" name="update_status" value="1">
                                                </form>
                                            </td>
                                            <td>
                                                <?php echo date('M j, Y', strtotime($order['created_at'])); ?>
                                                <br>
                                                <small style="color: #666;">
                                                    <?php echo date('g:i A', strtotime($order['created_at'])); ?>
                                                </small>
                                            </td>
                                            <td>
                                                <button onclick="viewOrderDetails(<?php echo $order['id']; ?>)" 
                                                        class="btn btn-primary btn-sm" title="View Details">
                                                    <i class="fas fa-eye"></i>
                                                </button>
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
        </main>
    </div>

    <!-- Order Details Modal -->
    <div id="orderModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 2000;">
        <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); background: white; border-radius: 12px; width: 90%; max-width: 600px; max-height: 80vh; overflow-y: auto;">
            <div style="padding: 2rem; border-bottom: 1px solid #eee; display: flex; justify-content: space-between; align-items: center;">
                <h3><i class="fas fa-receipt"></i> Order Details</h3>
                <button onclick="closeModal()" style="background: none; border: none; font-size: 1.5rem; cursor: pointer;">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div id="orderDetailsContent" style="padding: 2rem;">
                <!-- Order details will be loaded here -->
            </div>
        </div>
    </div>

    <script>
        function viewOrderDetails(orderId) {
            fetch(`order-details.php?id=${orderId}`)
                .then(response => response.text())
                .then(data => {
                    document.getElementById('orderDetailsContent').innerHTML = data;
                    document.getElementById('orderModal').style.display = 'block';
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Failed to load order details');
                });
        }

        function closeModal() {
            document.getElementById('orderModal').style.display = 'none';
        }

        // Close modal when clicking outside
        document.getElementById('orderModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeModal();
            }
        });
    </script>
</body>
</html>