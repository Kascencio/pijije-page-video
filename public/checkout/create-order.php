<?php
require __DIR__ . '/../lib/bootstrap.php';
require_login();
csrf_require_json();
rate_limit_require('create-order');

$cfg = require __DIR__ . '/../../secure/config.php';
$amount_cents = (int)$cfg['app']['amount'];
$total = number_format($amount_cents / 100, 2, '.', ''); // "800.00"

$token = paypal_access_token(); // asume helper en lib/paypal.php
$payload = [
  'intent' => 'CAPTURE',
  'purchase_units' => [[
    'amount' => ['currency_code' => 'MXN', 'value' => $total]
  ]],
  'application_context' => [
    'return_url' => $cfg['app']['base_url'] . $cfg['app']['return_url'],
    'cancel_url' => $cfg['app']['base_url'] . $cfg['app']['cancel_url'],
    'user_action' => 'PAY_NOW'
  ]
];

$res = paypal_api('POST', $cfg['paypal']['base_api'].'/v2/checkout/orders', $payload, $token);

// Persistir pending
$pdo = db();
$stmt = $pdo->prepare('INSERT INTO orders (user_id, provider, provider_order_id, amount_mxn, status)
                       VALUES (?, "paypal", ?, ?, "pending")
                       ON DUPLICATE KEY UPDATE amount_mxn=VALUES(amount_mxn)');
$stmt->execute([current_user_id(), $res['id'], $amount_cents]);

json_ok(['orderID' => $res['id']]);
