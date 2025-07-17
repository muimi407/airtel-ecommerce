<?php
require_once 'config/config.php';

// Sandbox credentials
$consumerKey = 'gisjp144n25uNeSIkZOymh0A64meBZt2mdY92lfU5FAmUpXw';
$consumerSecret = 'GLK4zXDWtjByFnv1Dgt2tK0AwnsD9Uw3WL5iHeEWdrRvJHCOfTJnOuNzZBuV5fVY';
$shortCode = '174379';
$passkey = 'bfb279f9aa9bdbcf158e97dd71a467cd2e0c893059b10f78e6b72ada1ed2c919';
$phone = '254708374149'; // <-- MUST USE THIS FOR SANDBOX
$callbackUrl = 'https://551c0c008a5a.ngrok-free.app/airtel-ecommerce/mpesa_callback.php';

function initiateStkPush($phone, $amount, $orderId, $userId) {
  global $db, $consumerKey, $consumerSecret, $shortCode, $passkey, $callbackUrl;

  // Get access token
  $url = 'https://sandbox.safaricom.co.ke/oauth/v1/generate?grant_type=client_credentials';
  $credentials = base64_encode("$consumerKey:$consumerSecret");
  $headers = ['Authorization: Basic ' . $credentials];
  $ch = curl_init($url);
  curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
  $response = curl_exec($ch);
  curl_close($ch);
  $result = json_decode($response);
  $accessToken = $result->access_token ?? null;

  $timestamp = date('YmdHis');
  $password = base64_encode($shortCode . $passkey . $timestamp);

  $payload = [
      'BusinessShortCode' => $shortCode,
      'Password' => $password,
      'Timestamp' => $timestamp,
      'TransactionType' => 'CustomerPayBillOnline',
      'Amount' => $amount,
      'PartyA' => $phone, // Must be 254708374149 in sandbox
      'PartyB' => $shortCode,
      'PhoneNumber' => $phone, // Must be 254708374149 in sandbox
      'CallBackURL' => $callbackUrl,
      'AccountReference' => 'ORDER-' . $orderId,
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




