<?php
require __DIR__ . '/../lib/bootstrap.php';
require_login();
csrf_require_json();
rate_limit_require('capture-order');

$in  = json_decode(file_get_contents('php://input'), true);
$id  = $in['orderID'] ?? null;
if (!$id) json_error('orderID faltante');

$cfg   = require __DIR__ . '/../../secure/config.php';
$token = paypal_access_token();
$res   = paypal_api('POST', $cfg['paypal']['base_api'].'/v2/checkout/orders/'.$id.'/capture', null, $token);

if (strtoupper($res['status'] ?? '') === 'COMPLETED') {
  $pdo = db();
  $pdo->beginTransaction();
  $pdo->prepare('INSERT INTO orders (user_id, provider, provider_order_id, amount_mxn, status)
                 VALUES (?, "paypal", ?, 0, "paid")
                 ON DUPLICATE KEY UPDATE status="paid"')
      ->execute([current_user_id(), $id]);
  grantAccess($pdo, current_user_id(), (int)$cfg['app']['course_id']);
  $pdo->commit();
  json_ok();
}
json_error('Estado no completado');
