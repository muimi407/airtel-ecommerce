<?php
require_once 'config/config.php';

function initiateStkPush($phone, $amount, $orderId, $userId) {
   $consumerKey = 'gisjp144n25uNeSIkZOymh0A64meBZt2mdY92lfU5FAmUpXw';
$consumerSecret = 'GLK4zXDWtjByFnv1Dgt2tK0AwnsD9Uw3WL5iHeEWdrRvJHCOfTJnOuNzZBuV5fVY';
$shortCode = '174379';
$passkey = 'bfb279f9aa9bdbcf158e97dd71a467cd2c2c09e6f7bc8d6a49b7b0f2f0b0c4e5';
$phone = '254722000000'; // Sandbox test phone
$callbackUrl = 'https://551c0c008a5a.ngrok-free.app/airtel-ecommerce/mpesa_callback.php'; // Public for Safaricom

    // Save pending payment in DB
    $db = $GLOBALS['db'];
    $stmt = $db->prepare("INSERT INTO mpesa_payments (order_id, user_id, amount, phone) VALUES (?, ?, ?, ?)");
    $stmt->execute([$orderId, $userId, $amount, $phone]);

    // Get access token
    $url = 'https://sandbox.safaricom.co.ke/oauth/v1/generate?grant_type=client_credentials';
    $credentials = base64_encode("$consumerKey:$consumerSecret");
    $headers = ['Authorization: Basic ' . $credentials];
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
    $response = curl_exec($ch);
    $result = json_decode($response);
    $accessToken = $result->access_token;

    // DEBUG: Write the access token to a file
    file_put_contents('access_token.txt', $accessToken);

    // Prepare STK push payload (from your provided payload block)
    $shortCode = '174379';
    $passkey = 'bfb279f9aa9bdbcf158e97dd71a467cd2c2c09e6f7bc8d6a49b7b0f2f0b0c4e5';
    $timestamp = date('YmdHis');

    // DEBUG: Log the password string BEFORE base64 encoding
    file_put_contents('password_debug.txt', $shortCode . $passkey . $timestamp);

    $password = base64_encode($shortCode . $passkey . $timestamp);

    $payload = [
    'BusinessShortCode' => $shortCode,
    'Password' => $password,
    'Timestamp' => $timestamp,
    'TransactionType' => 'CustomerPayBillOnline',
    'Amount' => $amount,
    'PartyA' => $phone,
    'PartyB' => $shortCode,
    'PhoneNumber' => $phone,
    'CallBackURL' => $callbackUrl,
    'AccountReference' => 'ORDER-' . $orderId,
    'TransactionDesc' => 'Order Payment'
    ];

    // DEBUG: Log the actual payload being sent
    file_put_contents('payload_debug.txt', json_encode($payload, JSON_PRETTY_PRINT));

    $stkUrl = 'https://sandbox.safaricom.co.ke/mpesa/stkpush/v1/processrequest';
    $headers = ['Content-Type: application/json', 'Authorization: Bearer ' . $accessToken];
    $ch = curl_init($stkUrl);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
    curl_setopt($ch, CURLOPT_POST, TRUE);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    $response = curl_exec($ch);
    curl_close($ch);

    // DEBUG: Write the STK Push API response to a file
    file_put_contents('stkpush_response.txt', $response);

    return json_decode($response);
}
?>
