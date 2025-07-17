<?php
require_once 'config/config.php';

function initiateStkPush($phone, $amount, $orderId, $userId) {
    $consumerKey = 'zKpbstN0hcqmoeT78KymgdXUdwTSltm6F3m4s6WeyWTVz4JM';
    $consumerSecret = 'SERyxYiTayVuIZidtynSuD9M6gufiYWjGXK9hYQcPbxPmrOmRWxzkmGX0CrJTC9p';
    $shortCode = '174379'; // Sandbox default
    $passkey = 'bfb279f9aa9bdbcf158e97dd71a467cd2c2c09e6f7bc8d6a49b7b0f2f0b0c4e5'; // Sandbox default
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

    // Prepare STK push payload
    $timestamp = date('YmdHis');
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
        'AccountReference' => $orderId,
        'TransactionDesc' => 'Order Payment'
    ];

    $stkUrl = 'https://sandbox.safaricom.co.ke/mpesa/stkpush/v1/processrequest';
    $headers = ['Content-Type: application/json', 'Authorization: Bearer ' . $accessToken];
    $ch = curl_init($stkUrl);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
    curl_setopt($ch, CURLOPT_POST, TRUE);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    $response = curl_exec($ch);
    curl_close($ch);

    return json_decode($response);
}
?>