<?php
require_once '../config/config.php';

if (!isAdmin()) {
    redirect('login.php');
}

$product_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($product_id <= 0) {
    redirect('products.php');
}

// Fetch product details
$stmt = $db->prepare("SELECT * FROM products WHERE id = ?");
$stmt->execute([$product_id]);
$product = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$product) {
    redirect('products.php');
}

// Fetch categories
$stmt = $db->prepare("SELECT * FROM categories ORDER BY name");
$stmt->execute();
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = sanitize($_POST['name']);
    $description = sanitize($_POST['description']);
    $price = (float)$_POST['price'];
    $stock_quantity = (int)$_POST['stock_quantity'];
    $category_id = (int)$_POST['category_id'];
    $features = sanitize($_POST['features']);
    $specifications = sanitize($_POST['specifications']);
    $status = sanitize($_POST['status']);
    
    // Handle image upload
    $image_name = $product['image']; // Keep existing image by default
    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        $file_type = $_FILES['image']['type'];
        
        if (in_array($file_type, $allowed_types)) {
            $file_extension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
            $new_image_name = uniqid() . '.' . $file_extension;
            $upload_path = '../uploads/' . $new_image_name;
            
            if (move_uploaded_file($_FILES['image']['tmp_name'], $upload_path)) {
                // Delete old image if it exists
                if ($product['image'] && file_exists('../uploads/' . $product['image'])) {
                    unlink('../uploads/' . $product['image']);
                }
                $image_name = $new_image_name;
            } else {
                $error = 'Failed to upload image';
            }
        } else {
            $error = 'Invalid image type. Please upload JPG, PNG, or GIF files only.';
        }
    }
    
    if (empty($error) && !empty($name) && $price > 0) {
        try {
            $stmt = $db->prepare("UPDATE products SET name = ?, description = ?, price = ?, stock_quantity = ?, category_id = ?, image = ?, features = ?, specifications = ?, status = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
            
            if ($stmt->execute([$name, $description, $price, $stock_quantity, $category_id, $image_name, $features, $specifications, $status, $product_id])) {
                $success = 'Product updated successfully!';
                // Refresh product data
                $stmt = $db->prepare("SELECT * FROM products WHERE id = ?");
                $stmt->execute([$product_id]);
                $product = $stmt->fetch(PDO::FETCH_ASSOC);
            } else {
                $error = 'Failed to update product';
            }
        } catch (Exception $e) {
            $error = 'Database error: ' . $e->getMessage();
        }
    } elseif (empty($error)) {
        $error = 'Please fill in all required fields';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Product - Admin</title>
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
                <h1><i class="fas fa-edit"></i> Edit Product</h1>
                <div class="admin-user-info">
                    <a href="products.php" class="btn btn-outline">
                        <i class="fas fa-arrow-left"></i> Back to Products
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

                <form method="POST" action="" enctype="multipart/form-data" class="admin-form">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="name"><i class="fas fa-tag"></i> Product Name *</label>
                            <input type="text" id="name" name="name" class="form-control" 
                                   value="<?php echo htmlspecialchars($product['name']); ?>" 
                                   placeholder="Enter product name" required>
                        </div>

                        <div class="form-group">
                            <label for="category_id"><i class="fas fa-folder"></i> Category</label>
                            <select id="category_id" name="category_id" class="form-control">
                                <option value="0">Select Category</option>
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?php echo $category['id']; ?>" 
                                            <?php echo ($product['category_id'] == $category['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($category['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="description"><i class="fas fa-align-left"></i> Description</label>
                        <textarea id="description" name="description" class="form-control" rows="4" 
                                  placeholder="Enter product description"><?php echo htmlspecialchars($product['description']); ?></textarea>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="price"><i class="fas fa-money-bill"></i> Price (KSh) *</label>
                            <input type="number" id="price" name="price" class="form-control" step="0.01" min="0"
                                   value="<?php echo $product['price']; ?>" 
                                   placeholder="0.00" required>
                        </div>

                        <div class="form-group">
                            <label for="stock_quantity"><i class="fas fa-boxes"></i> Stock Quantity *</label>
                            <input type="number" id="stock_quantity" name="stock_quantity" class="form-control" min="0"
                                   value="<?php echo $product['stock_quantity']; ?>" 
                                   placeholder="0" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="status"><i class="fas fa-toggle-on"></i> Status</label>
                        <select id="status" name="status" class="form-control">
                            <option value="active" <?php echo ($product['status'] == 'active') ? 'selected' : ''; ?>>Active</option>
                            <option value="inactive" <?php echo ($product['status'] == 'inactive') ? 'selected' : ''; ?>>Inactive</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="features"><i class="fas fa-list"></i> Key Features</label>
                        <textarea id="features" name="features" class="form-control" rows="3" 
                                  placeholder="Enter key features separated by commas"><?php echo htmlspecialchars($product['features']); ?></textarea>
                    </div>

                    <div class="form-group">
                        <label for="specifications"><i class="fas fa-cogs"></i> Specifications</label>
                        <textarea id="specifications" name="specifications" class="form-control" rows="3" 
                                  placeholder="Enter technical specifications"><?php echo htmlspecialchars($product['specifications']); ?></textarea>
                    </div>

                    <div class="form-group">
                        <label for="image"><i class="fas fa-image"></i> Product Image</label>
                        <div class="image-upload">
                            <input type="file" id="image" name="image" accept="image/*" style="display: none;" onchange="previewImage(this)">
                            <div onclick="document.getElementById('image').click()" style="cursor: pointer;">
                                <?php if ($product['image']): ?>
                                    <img src="../uploads/<?php echo htmlspecialchars($product['image']); ?>" 
                                         id="currentImage" class="image-preview" style="display: block;">
                                    <p style="margin-top: 1rem;">Click to change image</p>
                                <?php else: ?>
                                    <i class="fas fa-cloud-upload-alt" style="font-size: 3rem; color: #ddd; margin-bottom: 1rem;"></i>
                                    <p>Click to upload product image</p>
                                <?php endif; ?>
                                <small style="color: #666;">JPG, PNG, or GIF (Max 5MB)</small>
                            </div>
                            <img id="imagePreview" class="image-preview" style="display: none;">
                        </div>
                    </div>

                    <div style="display: flex; gap: 1rem; justify-content: flex-end; margin-top: 2rem;">
                        <a href="products.php" class="btn btn-outline">
                            <i class="fas fa-times"></i> Cancel
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Update Product
                        </button>
                    </div>
                </form>
            </div>
        </main>
    </div>

    <script>
        function previewImage(input) {
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const preview = document.getElementById('imagePreview');
                    const current = document.getElementById('currentImage');
                    
                    preview.src = e.target.result;
                    preview.style.display = 'block';
                    
                    if (current) {
                        current.style.display = 'none';
                    }
                }
                reader.readAsDataURL(input.files[0]);
            }
        }
    </script>
</body>
</html>