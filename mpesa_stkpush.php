<?php
require_once 'config/config.php';

// Global credentials
$consumerKey = 'gisjp144n25uNeSIkZOymh0A64meBZt2mdY92lfU5FAmUpXw';
$consumerSecret = 'GLK4zXDWtjByFnv1Dgt2tK0AwnsD9Uw3WL5iHeEWdrRvJHCOfTJnOuNzZBuV5fVY';
$shortCode = '174379'; // <--- REVERTED to the standard sandbox short code
$passkey = 'bfb279f9aa9bdbcf158e97dd71a467cd2e0c893059b10f78e6b72ada1ed2c919';
$phone = '254708374149'; // Sandbox test phone
$callbackUrl = 'https://551c0c008a5a.ngrok-free.app/airtel-ecommerce/mpesa_callback.php'; // Public for Safaricom

function initiateStkPush($phone, $amount, $orderId, $userId) {
  // Access global variables
  global $db, $consumerKey, $consumerSecret, $shortCode, $passkey, $callbackUrl;

  // Save pending payment in DB
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

  // Check for cURL errors during access token request
  if (curl_errno($ch)) {
      $error_msg = curl_error($ch);
      file_put_contents('access_token_curl_error.txt', "cURL Error: " . $error_msg);
      curl_close($ch);
      return (object)['errorMessage' => 'Failed to get access token: ' . $error_msg];
  }
  curl_close($ch);

  $result = json_decode($response);
  $accessToken = $result->access_token ?? null;

  if (!$accessToken) {
      file_put_contents('access_token_parse_error.txt', "Failed to parse access token response: " . $response);
      return (object)['errorMessage' => 'Failed to get access token. Response: ' . $response];
  }

  // DEBUG: Write the access token to a file
  file_put_contents('access_token.txt', $accessToken);

  // Prepare STK push payload
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
    'PartyA' => $phone,           // Should be 254708374149 in sandbox
    'PartyB' => $shortCode,
    'PhoneNumber' => $phone,      // Should be 254708374149 in sandbox
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

  // Check for cURL errors during STK push request
  if (curl_errno($ch)) {
      $error_msg = curl_error($ch);
      file_put_contents('stkpush_curl_error.txt', "cURL Error: " . $error_msg);
      curl_close($ch);
      return (object)['errorMessage' => 'Failed to send STK Push: ' . $error_msg];
  }
  curl_close($ch);

  // DEBUG: Write the STK Push API response to a file
  file_put_contents('stkpush_response.txt', $response);

  return json_decode($response);
}
?>




