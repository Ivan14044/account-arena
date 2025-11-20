<?php

/**
 * Script to fetch MonoBank public key for webhook signature verification
 * According to documentation: https://monobank.ua/api-docs/acquiring/dev/webhooks/get--api--merchant--pubkey
 * 
 * Usage: php genMonoPubKey.php
 * 
 * The public key can be cached and should only be refreshed when signature verification
 * stops working with the current key.
 */

require __DIR__ . '/vendor/autoload.php';

// Load environment variables
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

// Get token from .env
$token = $_ENV['MONOBANK_TOKEN'] ?? getenv('MONOBANK_TOKEN');

if (!$token) {
    die("Error: MONOBANK_TOKEN is not set in .env file\n");
}

// API endpoint for public key
$url = 'https://api.monobank.ua/api/merchant/pubkey';

// Make GET request with X-Token header
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'X-Token: ' . $token,
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

if ($error) {
    die("Error: cURL error - {$error}\n");
}

if ($httpCode !== 200) {
    die("Error: HTTP {$httpCode} - {$response}\n");
}

// Response is Base64-encoded ECDSA public key
$publicKey = trim($response);

if (empty($publicKey)) {
    die("Error: Empty response from API\n");
}

echo "MonoBank Public Key (Base64-encoded ECDSA):\n";
echo $publicKey . "\n\n";

echo "Add this to your .env file:\n";
echo "MONOBANK_PUBLIC_KEY={$publicKey}\n";

