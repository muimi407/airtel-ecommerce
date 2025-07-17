<?php
require_once '../config/config.php';

if (!isAdmin()) {
    redirect('login.php');
}

$success = '';
$error = '';

// Handle product deletion
if (isset($_GET['delete'])) {
    $product_id = (int)$_GET['delete'];
    
    try {
        // Check if product has orders
        $stmt = $db->prepare("SELECT COUNT(*) as count FROM order_items WHERE product_id = ?");
        $stmt->execute([$product_id]);
        $order_count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        
        if ($order_count > 0) {
            // Don't delete, just deactivate
            $stmt = $db->prepare("UPDATE products SET status = 'inactive' WHERE id = ?");
            $stmt->execute([$product_id]);
            $success = 'Product deactivated successfully (has existing orders)';
        } else {
            // Safe to delete
            $stmt = $db->prepare("DELETE FROM products WHERE id = ?");
            $stmt->execute([$product_id]);
            $success = 'Product deleted successfully';
        }
    } catch (Exception $e) {
        $error = 'Failed to delete product';
    }
}

// Fetch products with category info
$stmt = $db->prepare("SELECT p.*, c.name as category_name 
                      FROM products p 
                      LEFT JOIN categories c ON p.category_id = c.id 
                      ORDER BY p.created_at DESC");
$stmt->execute();
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Products - Admin</title>
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
                    <li><a href="products.php" class="active"><i class="fas fa-box"></i> Products</a></li>
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
                <h1><i class="fas fa-box"></i> Manage Products</h1>
                <div class="admin-user-info">
                    <a href="add-product.php" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Add New Product
                    </a>
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
                        <h3><i class="fas fa-list"></i> All Products (<?php echo count($products); ?>)</h3>
                    </div>
                    <div class="admin-card-body">
                        <?php if (empty($products)): ?>
                            <div style="text-align: center; padding: 3rem;">
                                <i class="fas fa-box" style="font-size: 4rem; color: #ddd; margin-bottom: 1rem;"></i>
                                <h3>No products found</h3>
                                <p style="color: #666; margin-bottom: 2rem;">Start by adding your first product.</p>
                                <a href="add-product.php" class="btn btn-primary">
                                    <i class="fas fa-plus"></i> Add Product
                                </a>
                            </div>
                        <?php else: ?>
                            <div style="overflow-x: auto;">
                                <table class="admin-table">
                                    <thead>
                                        <tr>
                                            <th>Image</th>
                                            <th>Product Name</th>
                                            <th>Category</th>
                                            <th>Price</th>
                                            <th>Stock</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($products as $product): ?>
                                        <tr>
                                            <td>
                                                <div style="width: 50px; height: 50px; background: #f8f9fa; border-radius: 8px; display: flex; align-items: center; justify-content: center;">
                                                    <?php if ($product['image']): ?>
                                                        <img src="../uploads/<?php echo htmlspecialchars($product['image']); ?>" 
                                                             alt="<?php echo htmlspecialchars($product['name']); ?>"
                                                             style="width: 100%; height: 100%; object-fit: cover; border-radius: 8px;">
                                                    <?php else: ?>
                                                        <i class="fas fa-image" style="color: #ccc;"></i>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                            <td>
                                                <strong><?php echo htmlspecialchars($product['name']); ?></strong>
                                                <br>
                                                <small style="color: #666;">
                                                    <?php echo htmlspecialchars(substr($product['description'], 0, 50)) . '...'; ?>
                                                </small>
                                            </td>
                                            <td><?php echo htmlspecialchars($product['category_name'] ?? 'No Category'); ?></td>
                                            <td style="font-weight: 600; color: var(--admin-success);">
                                                <?php echo formatPrice($product['price']); ?>
                                            </td>
                                            <td>
                                                <span style="color: <?php echo $product['stock_quantity'] <= 5 ? 'var(--admin-danger)' : 'var(--admin-success)'; ?>">
                                                    <?php echo $product['stock_quantity']; ?>
                                                    <?php if ($product['stock_quantity'] <= 5): ?>
                                                        <i class="fas fa-exclamation-triangle"></i>
                                                    <?php endif; ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge <?php echo $product['status'] == 'active' ? 'badge-success' : 'badge-danger'; ?>">
                                                    <?php echo ucfirst($product['status']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <div style="display: flex; gap: 0.5rem;">
                                                    <a href="edit-product.php?id=<?php echo $product['id']; ?>" 
                                                       class="btn btn-warning btn-sm" title="Edit">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <a href="?delete=<?php echo $product['id']; ?>" 
                                                       class="btn btn-danger btn-sm" title="Delete"
                                                       onclick="return confirm('Are you sure you want to delete this product?')">
                                                        <i class="fas fa-trash"></i>
                                                    </a>
                                                </div>
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
</body>
</html>