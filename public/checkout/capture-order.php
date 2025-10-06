<?php
require __DIR__ . '/../lib/bootstrap.php';
require_once __DIR__ . '/../lib/access.php';
require_login();
csrf_require_json();
rate_limit_require('capture-order');

$in  = json_decode(file_get_contents('php://input'), true);
$id  = $in['orderID'] ?? null;
if (!$id) json_error('orderID faltante');

$cfg   = config();
$token = paypal_access_token();
try {
  $res = paypal_api('POST', $cfg['paypal']['base_api'].'/v2/checkout/orders/'.$id.'/capture', null, $token);
} catch (Throwable $e) {
  error_log('[PAYPAL CAPTURE] '.$e->getMessage());
  json_error('Error capturando');
}

if (strtoupper($res['status'] ?? '') === 'COMPLETED') {
  $pdo = db();
  $amount = coursePriceCents(); // almacenado en centavos según config dinámica
  try {
    $pdo->beginTransaction();
  $pdo->query('INSERT INTO orders (user_id, provider, provider_order_id, amount_mxn, status)
         VALUES (?, "paypal", ?, ?, "paid")
         ON DUPLICATE KEY UPDATE status="paid", amount_mxn=VALUES(amount_mxn)',
         [current_user_id(), $id, $amount]);
    // Otorga acceso usando función de alto nivel que registra log
    grantAccess(current_user_id(), (int)$cfg['app']['course_id']);
    $pdo->commit();
    json_ok();
  } catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    error_log('Capture error: '.$e->getMessage());
    json_error('Error interno al capturar');
  }
}
error_log('[PAYPAL CAPTURE] Estado no completado para '.$id.' payload: '.substr(json_encode($res),0,400));
json_error('Estado no completado');
