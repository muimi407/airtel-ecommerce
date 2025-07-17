<?php
require_once '../config/config.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Please login first']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$cart_id = isset($input['cart_id']) ? (int)$input['cart_id'] : 0;
$quantity = isset($input['quantity']) ? (int)$input['quantity'] : 1;

if ($cart_id <= 0 || $quantity <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid cart item or quantity']);
    exit;
}

try {
    // Verify cart item belongs to user
    $stmt = $db->prepare("SELECT c.*, p.stock_quantity FROM cart c JOIN products p ON c.product_id = p.id WHERE c.id = ? AND c.user_id = ?");
    $stmt->execute([$cart_id, $_SESSION['user_id']]);
    $cart_item = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$cart_item) {
        echo json_encode(['success' => false, 'message' => 'Cart item not found']);
        exit;
    }
    
    if ($quantity > $cart_item['stock_quantity']) {
        echo json_encode(['success' => false, 'message' => 'Not enough stock available']);
        exit;
    }
    
    // Update quantity
    $stmt = $db->prepare("UPDATE cart SET quantity = ? WHERE id = ?");
    $stmt->execute([$quantity, $cart_id]);
    
    echo json_encode(['success' => true, 'message' => 'Cart updated successfully']);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'An error occurred']);
}
?>