<?php
require_once 'config/config.php';

$order_id = isset($_GET['order']) ? intval($_GET['order']) : 0;
$stmt = $db->prepare("SELECT status, mpesa_receipt FROM mpesa_payments WHERE order_id = ? ORDER BY id DESC LIMIT 1");
$stmt->execute([$order_id]);
$payment = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$payment) {
    echo "No payment found for this order.";
} else if ($payment['status'] == 'completed') {
    echo "Payment successful! Mpesa Receipt: " . htmlspecialchars($payment['mpesa_receipt']);
} else if ($payment['status'] == 'failed') {
    echo "Payment failed. Please try again.";
} else {
    echo "Awaiting payment confirmation. Please complete payment on your phone.";
}
?>