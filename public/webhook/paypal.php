<?php
require __DIR__ . '/../lib/bootstrap.php';

// Solo POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    die('Method not allowed');
}

// Rate limit
rate_limit_require('webhook');

$cfg = require __DIR__ . '/../../secure/config.php';
$body = file_get_contents('php://input');
$data = json_decode($body, true);

if (!$data || $data['event_type'] !== 'PAYMENT.CAPTURE.COMPLETED') {
    http_response_code(200);
    die('Event ignored');
}

// Verificar firma en prod si webhook_id está configurado
if ($cfg['env'] === 'live' && !empty($cfg['paypal']['webhook_id'])) {
    if (!verifyWebhookSignature($body, getallheaders(), $cfg)) {
        http_response_code(400);
        die('Invalid signature');
    }
}

// Extraer order ID de la captura
$capture = $data['resource'] ?? [];
$orderId = null;

// Buscar en supplementary_data o links
if (isset($capture['supplementary_data']['related_ids']['order_id'])) {
    $orderId = $capture['supplementary_data']['related_ids']['order_id'];
} elseif (isset($capture['links'])) {
    foreach ($capture['links'] as $link) {
        if ($link['rel'] === 'up' && strpos($link['href'], '/orders/') !== false) {
            $parts = explode('/orders/', $link['href']);
            if (count($parts) > 1) {
                $orderId = explode('/', $parts[1])[0];
                break;
            }
        }
    }
}

if (!$orderId) {
    http_response_code(400);
    die('Order ID not found');
}

// Procesar pago de forma idempotente
$pdo = db();
$pdo->beginTransaction();

$stmt = $pdo->prepare('UPDATE orders SET status = "paid" WHERE provider_order_id = ? AND status = "pending"');
$updated = $stmt->execute([$orderId]);

if ($stmt->rowCount() > 0) {
    // Solo otorgar acceso si la orden se marcó como paid ahora
    $order = $pdo->prepare('SELECT user_id FROM orders WHERE provider_order_id = ?');
    $order->execute([$orderId]);
    $row = $order->fetch();
    
    if ($row) {
        grantAccess($pdo, $row['user_id'], (int)$cfg['app']['course_id']);
    }
}

$pdo->commit();
http_response_code(200);
die('OK');

function verifyWebhookSignature($body, $headers, $cfg) {
    // Implementación simplificada - en producción usar /v1/notifications/verify-webhook-signature
    $webhookId = $cfg['paypal']['webhook_id'];
    $transmissionId = $headers['PAYPAL-TRANSMISSION-ID'] ?? '';
    
    // En sandbox, permitir siempre
    if ($cfg['env'] === 'sandbox') return true;
    
    // En prod, verificar con PayPal API
    try {
        $token = paypal_access_token();
        $verifyPayload = [
            'transmission_id' => $transmissionId,
            'cert_id' => $headers['PAYPAL-CERT-ID'] ?? '',
            'auth_algo' => 'SHA256withRSA',
            'transmission_time' => $headers['PAYPAL-TRANSMISSION-TIME'] ?? '',
            'transmission_sig' => $headers['PAYPAL-TRANSMISSION-SIG'] ?? '',
            'webhook_id' => $webhookId,
            'webhook_event' => json_decode($body, true)
        ];
        
        $result = paypal_api('POST', $cfg['paypal']['base_api'] . '/v1/notifications/verify-webhook-signature', $verifyPayload, $token);
        return isset($result['verification_status']) && $result['verification_status'] === 'SUCCESS';
    } catch (Exception $e) {
        error_log("Webhook verification failed: " . $e->getMessage());
        return false;
    }
}
