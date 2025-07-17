<?php
require_once 'config/config.php';

// Log all incoming POST data for debugging
file_put_contents('mpesa_callback_log.txt', file_get_contents('php://input') . PHP_EOL, FILE_APPEND);

// Parse and validate incoming data
$data = file_get_contents('php://input');
$mpesaResponse = json_decode($data, true);

if (isset($mpesaResponse['Body']['stkCallback'])) {
    $callback = $mpesaResponse['Body']['stkCallback'];
    $resultCode = $callback['ResultCode'];
    $amountPaid = null;
    $mpesaCode = null;
    $paidPhone = null;

    // Defensive check for CallbackMetadata
    if ($resultCode == 0 && isset($callback['CallbackMetadata']['Item'])) {
        foreach ($callback['CallbackMetadata']['Item'] as $item) {
            if ($item['Name'] == 'MpesaReceiptNumber') $mpesaCode = $item['Value'];
            if ($item['Name'] == 'Amount') $amountPaid = $item['Value'];
            if ($item['Name'] == 'PhoneNumber') $paidPhone = $item['Value'];
        }

        // Extra validation: only mark as paid if all values exist
        if ($mpesaCode && $amountPaid && $paidPhone) {
            $db = $GLOBALS['db'];
            $stmt = $db->prepare("SELECT id, order_id FROM mpesa_payments WHERE phone=? AND amount=? AND status='pending' LIMIT 1");
            $stmt->execute([$paidPhone, $amountPaid]);
            $payment = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($payment) {
                $stmt = $db->prepare("UPDATE mpesa_payments SET mpesa_receipt=?, status='completed', mpesa_response=? WHERE id=?");
                $stmt->execute([$mpesaCode, json_encode($mpesaResponse), $payment['id']]);
                $stmt = $db->prepare("UPDATE orders SET payment_status='completed' WHERE id=?");
                $stmt->execute([$payment['order_id']]);
            }
        } else {
            // Log missing fields for debugging
            file_put_contents('mpesa_callback_errors.txt', "Missing fields: " . json_encode($callback) . PHP_EOL, FILE_APPEND);
        }
    } else {
        // Failed payment, update status
        // Defensive: fallback to phone from metadata if available
        if (isset($callback['CallbackMetadata']['Item'])) {
            foreach ($callback['CallbackMetadata']['Item'] as $item) {
                if ($item['Name'] == 'PhoneNumber') $paidPhone = $item['Value'];
            }
        }
        $db = $GLOBALS['db'];
        $stmt = $db->prepare("UPDATE mpesa_payments SET status='failed', mpesa_response=? WHERE phone=? AND status='pending'");
        $stmt->execute([json_encode($mpesaResponse), $paidPhone]);
    }
}
?>