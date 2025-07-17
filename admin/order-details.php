<?php
require_once '../config/config.php';

if (!isAdmin()) {
    exit('Unauthorized');
}

$order_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($order_id <= 0) {
    exit('Invalid order ID');
}

// Fetch order details
$stmt = $db->prepare("SELECT o.*, u.full_name, u.email 
                      FROM orders o 
                      JOIN users u ON o.user_id = u.id 
                      WHERE o.id = ?");
$stmt->execute([$order_id]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    exit('Order not found');
}

// Fetch order items
$stmt = $db->prepare("SELECT oi.*, p.name, p.image 
                      FROM order_items oi 
                      JOIN products p ON oi.product_id = p.id 
                      WHERE oi.order_id = ?");
$stmt->execute([$order_id]);
$order_items = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div style="display: grid; gap: 2rem;">
    <!-- Order Information -->
    <div>
        <h4 style="margin-bottom: 1rem; color: var(--admin-primary);">Order Information</h4>
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
            <div>
                <strong>Order ID:</strong> #<?php echo $order['id']; ?>
            </div>
            <div>
                <strong>Date:</strong> <?php echo date('F j, Y \a\t g:i A', strtotime($order['created_at'])); ?>
            </div>
            <div>
                <strong>Status:</strong>
                <span class="badge <?php 
                    $status_class = [
                        'pending' => 'badge-warning',
                        'processing' => 'badge-primary',
                        'shipped' => 'badge-primary',
                        'delivered' => 'badge-success',
                        'cancelled' => 'badge-danger'
                    ];
                    echo $status_class[$order['status']] ?? 'badge-primary';
                ?>">
                    <?php echo ucfirst($order['status']); ?>
                </span>
            </div>
            <div>
                <strong>Payment:</strong> <?php echo $order['payment_method'] == 'mpesa' ? 'M-Pesa' : 'Credit/Debit Card'; ?>
            </div>
        </div>
    </div>

    <!-- Customer Information -->
    <div>
        <h4 style="margin-bottom: 1rem; color: var(--admin-primary);">Customer Information</h4>
        <div style="background: #f8f9fa; padding: 1rem; border-radius: 8px;">
            <div style="margin-bottom: 0.5rem;">
                <strong>Name:</strong> <?php echo htmlspecialchars($order['full_name']); ?>
            </div>
            <div style="margin-bottom: 0.5rem;">
                <strong>Email:</strong> <?php echo htmlspecialchars($order['email']); ?>
            </div>
            <div style="margin-bottom: 0.5rem;">
                <strong>Phone:</strong> <?php echo htmlspecialchars($order['phone']); ?>
            </div>
            <div>
                <strong>Shipping Address:</strong><br>
                <?php echo nl2br(htmlspecialchars($order['shipping_address'])); ?>
            </div>
        </div>
    </div>

    <!-- Order Items -->
    <div>
        <h4 style="margin-bottom: 1rem; color: var(--admin-primary);">Order Items</h4>
        <div style="border: 1px solid #eee; border-radius: 8px; overflow: hidden;">
            <?php foreach ($order_items as $item): ?>
            <div style="display: flex; align-items: center; padding: 1rem; border-bottom: 1px solid #f0f0f0; gap: 1rem;">
                <div style="width: 60px; height: 60px; background: #f8f9fa; border-radius: 8px; display: flex; align-items: center; justify-content: center;">
                    <?php if ($item['image']): ?>
                        <img src="../uploads/<?php echo htmlspecialchars($item['image']); ?>" 
                             alt="<?php echo htmlspecialchars($item['name']); ?>"
                             style="width: 100%; height: 100%; object-fit: cover; border-radius: 8px;">
                    <?php else: ?>
                        <i class="fas fa-wifi" style="color: #ccc;"></i>
                    <?php endif; ?>
                </div>
                <div style="flex: 1;">
                    <strong><?php echo htmlspecialchars($item['name']); ?></strong>
                    <div style="color: #666; font-size: 0.9rem;">
                        Quantity: <?php echo $item['quantity']; ?> × <?php echo formatPrice($item['price']); ?>
                    </div>
                </div>
                <div style="font-weight: 600; color: var(--admin-success);">
                    <?php echo formatPrice($item['price'] * $item['quantity']); ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Order Total -->
    <div style="background: #f8f9fa; padding: 1.5rem; border-radius: 8px;">
        <div style="display: flex; justify-content: space-between; font-size: 1.2rem; font-weight: 700; color: var(--admin-primary);">
            <span>Total Amount:</span>
            <span><?php echo formatPrice($order['total_amount']); ?></span>
        </div>
    </div>
</div>