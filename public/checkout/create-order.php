<?php
require __DIR__ . '/../lib/bootstrap.php';
require_login();
csrf_require_json();
rate_limit_require('create-order');

$cfg = config();
$amount_cents = coursePriceCents();
$total = number_format($amount_cents / 100, 2, '.', '');

$token = paypal_access_token(); // asume helper en lib/paypal.php
$payload = [
  'intent' => 'CAPTURE',
  'purchase_units' => [[
    'amount' => ['currency_code' => ($cfg['app']['currency'] ?? 'MXN'), 'value' => $total],
    'description' => courseTitle()
  ]],
  'application_context' => [
    'return_url' => $cfg['app']['base_url'] . ($cfg['app']['return_url'] ?? '/success.php'),
    'cancel_url' => $cfg['app']['base_url'] . ($cfg['app']['cancel_url'] ?? '/cancel.php'),
    'user_action' => 'PAY_NOW'
  ]
];

try {
  $res = paypal_api('POST', $cfg['paypal']['base_api'].'/v2/checkout/orders', $payload, $token);
} catch (Throwable $e) {
  error_log('[PAYPAL CREATE ORDER] '.$e->getMessage());
  json_error('Error creando orden');
}
if (!isset($res['id'])) {
  error_log('[PAYPAL CREATE ORDER] Respuesta sin id: '.substr(json_encode($res),0,300));
  json_error('Orden inválida');
}

// Persistir pending
$pdo = db();
$pdo->query('INSERT INTO orders (user_id, provider, provider_order_id, amount_mxn, status)
             VALUES (?, "paypal", ?, ?, "pending")
             ON DUPLICATE KEY UPDATE amount_mxn=VALUES(amount_mxn)',
             [current_user_id(), $res['id'], $amount_cents]);

json_ok(['orderID' => $res['id']]);
