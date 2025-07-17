<?php
require_once '../config/config.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['count' => 0]);
    exit;
}

try {
    $stmt = $db->prepare("SELECT SUM(quantity) as count FROM cart WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo json_encode(['count' => (int)($result['count'] ?? 0)]);
} catch (Exception $e) {
    echo json_encode(['count' => 0]);
}
?>