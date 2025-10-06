<?php
/**
 * Funciones helper para PayPal API
 * Manejo de OAuth2 y comunicación con PayPal
 */

/**
 * Obtener access token de PayPal
 */
function getPayPalAccessToken() {
    $config = config();
    $paypalConfig = $config['paypal'];
    if (empty($paypalConfig['client_id']) || empty($paypalConfig['secret'])) {
        throw new Exception('Credenciales PayPal incompletas (client_id o secret vacío)');
    }

    // 1) Revisión rápida de caché en sesión (para entornos sin escritura)
    if (session_status() === PHP_SESSION_ACTIVE && !empty($_SESSION['paypal_token'])) {
        $sessTok = $_SESSION['paypal_token'];
        if (isset($sessTok['access_token'], $sessTok['expires']) && $sessTok['expires'] > time()) {
            return $sessTok['access_token'];
        }
    }
    // Determinar ruta de cache (con fallback si permisos fallan)
    $primaryCacheDir = realpath(__DIR__ . '/../../secure/cache') ?: (__DIR__ . '/../../secure/cache');
    if (!is_dir($primaryCacheDir)) {
        @mkdir($primaryCacheDir, 0755, true);
    }
    $cacheWritable = is_dir($primaryCacheDir) && is_writable($primaryCacheDir);
    $cacheDir = $cacheWritable ? $primaryCacheDir : sys_get_temp_dir();
    $cacheFile = rtrim($cacheDir, DIRECTORY_SEPARATOR) . '/paypal_token.json';
    if (!$cacheWritable) {
        // Log solo una vez por ejecución
        static $warned = false; if (!$warned) { $warned = true; error_log('[PAYPAL OAUTH] Usando fallback de cache en temp dir: '.$cacheDir); }
    }
    // Verificar si tenemos un token en cache (ignorar si file no es legible)
    if (file_exists($cacheFile)) {
        $cached = json_decode(file_get_contents($cacheFile), true);
        if ($cached && $cached['expires'] > time()) {
            return $cached['access_token'];
        }
    }
    
    // Obtener nuevo token (seguir redirecciones por si base_api redirige)
    $ch = curl_init();
    $oauthUrl = rtrim($paypalConfig['base_api'], '/') . '/v1/oauth2/token';
    curl_setopt_array($ch, [
        CURLOPT_URL => $oauthUrl,
        CURLOPT_POST => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            'Accept: application/json',
            'Accept-Language: en_US',
            'Content-Type: application/x-www-form-urlencoded'
        ],
        CURLOPT_POSTFIELDS => 'grant_type=client_credentials',
        CURLOPT_USERPWD => $paypalConfig['client_id'] . ':' . $paypalConfig['secret'],
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_MAXREDIRS => 5,
        CURLOPT_CONNECTTIMEOUT => 15,
        CURLOPT_TIMEOUT => 30,
    ]);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $finalUrl = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
    $redirCount = curl_getinfo($ch, CURLINFO_REDIRECT_COUNT);
    $curlErr = curl_error($ch);
    curl_close($ch);
    if ($curlErr) {
        throw new Exception('cURL error solicitando token: ' . $curlErr);
    }
    
    if ($response === false) {
        throw new Exception('cURL devolvió false al solicitar token');
    }
    if ($httpCode !== 200) {
        $snippet = substr($response ?? '', 0, 300);
        $detail = 'HTTP ' . $httpCode;
        if ($redirCount > 0) { $detail .= ' redirs=' . $redirCount; }
        if ($finalUrl && $finalUrl !== $oauthUrl) { $detail .= ' final=' . $finalUrl; }
        $json = json_decode($response, true);
        if (isset($json['error'])) { $detail .= ' error=' . $json['error']; }
        if (isset($json['error_description'])) { $detail .= ' desc=' . $json['error_description']; }
        error_log("[PAYPAL OAUTH] Fail $detail body: $snippet");
        if (in_array($httpCode, [301,302])) {
            $detail .= ' (verifica base_api: debe ser https://api-m.sandbox.paypal.com ó https://api-m.paypal.com)';
        }
        throw new Exception('Error al obtener token de PayPal (' . $detail . ')');
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
    
    // Intentar guardar cache; si falla continuar sin cache
    $written = @file_put_contents($cacheFile, json_encode($cacheData));
    $fileCached = true;
    if ($written === false) {
        $fileCached = false;
        error_log('[PAYPAL OAUTH] No se pudo escribir cache token en '.$cacheFile.' (permiso denegado). Continuando sin cache (se usará sesión).');
    }

    // Guardar también en sesión como fallback (aunque file cache funcione, acelera)
    if (session_status() === PHP_SESSION_ACTIVE) {
        $_SESSION['paypal_token'] = $cacheData;
    }
    
    return $data['access_token'];
}

/**
 * Realizar petición a PayPal API
 */
function paypalApiRequest($endpoint, $data = null, $method = 'GET') {
    $config = config();
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
    $config = config();
    
    $orderData = [
        'intent' => 'CAPTURE',
        'purchase_units' => [
            [
                'amount' => [
                    'currency_code' => $currency,
                    'value' => number_format($amount / 100, 2, '.', '')
                ],
                'description' => $description ?: courseTitle()
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
