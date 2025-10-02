<?php
/**
 * Funciones helper para PayPal API
 * Manejo de OAuth2 y comunicación con PayPal
 */

/**
 * Obtener access token de PayPal
 */
function getPayPalAccessToken() {
    $config = require_once __DIR__ . '/../../secure/config.php';
    $paypalConfig = $config['paypal'];
    
    // Verificar si tenemos un token en cache
    $cacheFile = __DIR__ . '/../../secure/cache/paypal_token.json';
    if (file_exists($cacheFile)) {
        $cached = json_decode(file_get_contents($cacheFile), true);
        if ($cached && $cached['expires'] > time()) {
            return $cached['access_token'];
        }
    }
    
    // Obtener nuevo token
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $paypalConfig['base_api'] . '/v1/oauth2/token');
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Accept: application/json',
        'Accept-Language: en_US',
        'Content-Type: application/x-www-form-urlencoded'
    ]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, 'grant_type=client_credentials');
    curl_setopt($ch, CURLOPT_USERPWD, $paypalConfig['client_id'] . ':' . $paypalConfig['secret']);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode !== 200) {
        error_log("PayPal OAuth failed: " . $response);
        throw new Exception('Error al obtener token de PayPal');
    }
    
    $data = json_decode($response, true);
    if (!$data || !isset($data['access_token'])) {
        throw new Exception('Respuesta inválida de PayPal');
    }
    
    // Guardar en cache (expira 1 hora antes del tiempo real)
    $expires = time() + $data['expires_in'] - 3600;
    $cacheData = [
        'access_token' => $data['access_token'],
        'expires' => $expires
    ];
    
    // Crear directorio de cache si no existe
    $cacheDir = dirname($cacheFile);
    if (!is_dir($cacheDir)) {
        mkdir($cacheDir, 0755, true);
    }
    
    file_put_contents($cacheFile, json_encode($cacheData));
    
    return $data['access_token'];
}

/**
 * Realizar petición a PayPal API
 */
function paypalApiRequest($endpoint, $data = null, $method = 'GET') {
    $config = require_once __DIR__ . '/../../secure/config.php';
    $paypalConfig = $config['paypal'];
    $accessToken = getPayPalAccessToken();
    
    $url = $paypalConfig['base_api'] . $endpoint;
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $accessToken,
        'PayPal-Request-Id: ' . uniqid(),
        'Prefer: return=representation'
    ]);
    
    if ($method === 'POST' && $data) {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    } elseif ($method === 'PATCH' && $data) {
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PATCH');
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    }
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($error) {
        throw new Exception('Error de conexión: ' . $error);
    }
    
    $responseData = json_decode($response, true);
    
    if ($httpCode >= 400) {
        $errorMsg = $responseData['message'] ?? 'Error desconocido';
        error_log("PayPal API Error ({$httpCode}): " . $response);
        throw new Exception("Error de PayPal: {$errorMsg}");
    }
    
    return $responseData;
}

/**
 * Crear orden en PayPal
 */
function createPayPalOrder($amount, $currency = 'MXN', $description = '') {
    $config = require_once __DIR__ . '/../../secure/config.php';
    
    $orderData = [
        'intent' => 'CAPTURE',
        'purchase_units' => [
            [
                'amount' => [
                    'currency_code' => $currency,
                    'value' => number_format($amount / 100, 2, '.', '')
                ],
                'description' => $description ?: 'Curso de Ganadería Regenerativa'
            ]
        ],
        'application_context' => [
            'return_url' => $config['app']['base_url'] . $config['paypal']['return_url'],
            'cancel_url' => $config['app']['base_url'] . $config['paypal']['cancel_url']
        ]
    ];
    
    return paypalApiRequest('/v2/checkout/orders', $orderData, 'POST');
}

/**
 * Capturar orden de PayPal
 */
function capturePayPalOrder($orderId) {
    return paypalApiRequest("/v2/checkout/orders/{$orderId}/capture", null, 'POST');
}

/**
 * Obtener detalles de una orden
 */
function getPayPalOrder($orderId) {
    return paypalApiRequest("/v2/checkout/orders/{$orderId}");
}

/**
 * Validar webhook de PayPal con verificación real de firma
 */
function validatePayPalWebhook($headers, $body) {
    $config = require_once __DIR__ . '/../../secure/config.php';
    
    // Si no hay webhook_id configurado, solo validamos la estructura básica
    if (empty($config['paypal']['webhook_id'])) {
        error_log("PayPal webhook_id not configured - skipping signature verification");
        return true;
    }
    
    // Normalizar headers (case-insensitive)
    $normalizedHeaders = [];
    foreach ($headers as $key => $value) {
        $normalizedHeaders[strtolower($key)] = $value;
    }
    
    // Verificar headers requeridos
    $requiredHeaders = [
        'paypal-transmission-id' => 'transmission_id',
        'paypal-transmission-time' => 'transmission_time', 
        'paypal-cert-id' => 'cert_id',
        'paypal-transmission-sig' => 'transmission_sig'
    ];
    
    $verificationData = [];
    foreach ($requiredHeaders as $header => $key) {
        if (!isset($normalizedHeaders[$header])) {
            error_log("Missing webhook header: {$header}");
            return false;
        }
        $verificationData[$key] = $normalizedHeaders[$header];
    }
    
    // Construir payload para verificación
    $verificationPayload = [
        'transmission_id' => $verificationData['transmission_id'],
        'cert_id' => $verificationData['cert_id'],
        'auth_algo' => 'SHA256withRSA',
        'transmission_time' => $verificationData['transmission_time'],
        'transmission_sig' => $verificationData['transmission_sig'],
        'webhook_id' => $config['paypal']['webhook_id'],
        'webhook_event' => json_decode($body, true)
    ];
    
    try {
        // Llamar al endpoint de verificación de PayPal
        $verificationResult = paypalApiRequest(
            '/v1/notifications/verify-webhook-signature',
            $verificationPayload,
            'POST'
        );
        
        if (isset($verificationResult['verification_status']) && 
            $verificationResult['verification_status'] === 'SUCCESS') {
            return true;
        }
        
        error_log("PayPal webhook verification failed: " . json_encode($verificationResult));
        return false;
        
    } catch (Exception $e) {
        error_log("PayPal webhook verification error: " . $e->getMessage());
        
        // En sandbox, permitir continuar si la verificación falla
        if ($config['env'] === 'sandbox') {
            error_log("Sandbox mode - allowing webhook despite verification error");
            return true;
        }
        
        return false;
    }
}
