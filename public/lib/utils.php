<?php
/**
 * Funciones utilitarias generales
 */

/**
 * Escape HTML para salida segura (alias de e() en security.php)
 */
function escape($string) {
    return htmlspecialchars($string, ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

/**
 * Alias corto para escape HTML
 */
if (!function_exists('e')) {
    function e($string) {
        return escape($string);
    }
}

/**
 * Formatear precio en centavos a formato legible
 */
function formatPrice($cents, $currency = 'MXN') {
    $amount = $cents / 100;
    return '$' . number_format($amount, 2) . ' ' . $currency;
}

/**
 * Generar mensaje flash (acepta ambas firmas):
 * setFlash('Mensaje', 'success') ó setFlash('success', 'Mensaje')
 */
function setFlash($a, $b = null) {
    if ($b === null) {
        // Solo un parámetro: mensaje, tipo por defecto
        $message = $a;
        $type = 'info';
    } else {
        // Detectar si el primero parece ser el tipo (success, error, warning, info)
        $lower = strtolower($a);
        if (in_array($lower, ['success','error','warning','info'])) {
            $type = $lower;
            $message = $b;
        } else {
            $message = $a;
            $type = $b;
        }
    }
    $_SESSION['flash'] = [
        'message' => $message,
        'type' => $type
    ];
}

/**
 * Obtener y limpiar mensaje flash
 */
function getFlash() {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

/**
 * Redirigir a URL absoluta o relativa dentro del sitio
 */
function redirect($path, $httpCode = 302) {
    if (!headers_sent()) {
        if (!preg_match('~^https?://~i', $path)) {
            // Construir URL absoluta basada en base_url
            $path = url($path);
        }
        header('Location: ' . $path, true, $httpCode);
    }
    exit; // detener ejecución
}

/**
 * Redirigir a la página previa (referrer) o fallback
 */
function redirectBack($fallback = '/') {
    $ref = $_SERVER['HTTP_REFERER'] ?? null;
    if ($ref) {
        redirect($ref);
    }
    redirect($fallback);
}

/**
 * Obtener URL base de la aplicación
 */
function getBaseUrl() {
    $config = require_once __DIR__ . '/../../secure/config.php';
    return rtrim($config['app']['base_url'], '/');
}

/**
 * Obtener configuración global (helper)
 */
function config() {
    static $config = null;
    if ($config === null) {
        $config = require __DIR__ . '/../../secure/config.php';
    }
    return $config;
}

/**
 * Construir URL completa
 */
function url($path = '') {
    $baseUrl = getBaseUrl();
    $path = ltrim($path, '/');
    return $path ? "{$baseUrl}/{$path}" : $baseUrl;
}

/**
 * Verificar si el usuario está logueado
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Obtener ID del usuario actual
 */
function getCurrentUserId() {
    return $_SESSION['user_id'] ?? null;
}

/**
 * Obtener datos del usuario actual
 */
function getCurrentUser() {
    if (!isLoggedIn()) {
        return null;
    }
    
    $db = getDB();
    return $db->fetchOne(
        'SELECT id, name, email, verified FROM users WHERE id = ?',
        [getCurrentUserId()]
    );
}

/**
 * Generar token aleatorio seguro
 */
function generateToken($length = 32) {
    return bin2hex(random_bytes($length));
}

/**
 * Hash de contraseña
 */
function hashPassword($password) {
    return password_hash($password, PASSWORD_ARGON2ID, [
        'memory_cost' => 65536, // 64 MB
        'time_cost' => 4,       // 4 iteraciones
        'threads' => 3,         // 3 hilos
    ]);
}

/**
 * Verificar contraseña
 */
function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

/**
 * Validar política de contraseña
 */
function validatePasswordPolicy($password) {
    $config = require_once __DIR__ . '/../../secure/config.php';
    $minLength = $config['security']['password_min_length'];
    $requirements = $config['security']['password_require'];
    
    if (strlen($password) < $minLength) {
        return "La contraseña debe tener al menos {$minLength} caracteres";
    }
    
    foreach ($requirements as $req) {
        switch ($req) {
            case 'upper':
                if (!preg_match('/[A-Z]/', $password)) {
                    return "La contraseña debe contener al menos una letra mayúscula";
                }
                break;
            case 'lower':
                if (!preg_match('/[a-z]/', $password)) {
                    return "La contraseña debe contener al menos una letra minúscula";
                }
                break;
            case 'digit':
                if (!preg_match('/[0-9]/', $password)) {
                    return "La contraseña debe contener al menos un número";
                }
                break;
            case 'special':
                if (!preg_match('/[^a-zA-Z0-9]/', $password)) {
                    return "La contraseña debe contener al menos un carácter especial";
                }
                break;
        }
    }
    
    return true; // Contraseña válida
}

// validateDriveFileId() centralizada en validate.php

/**
 * Limpiar string de entrada
 */
function cleanInput($input) {
    if (is_array($input)) {
        return array_map('cleanInput', $input);
    }
    return trim(strip_tags($input));
}

/**
 * Convertir array a JSON seguro
 */
function jsonResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * Obtener timestamp actual
 */
function now() {
    return date('Y-m-d H:i:s');
}

/**
 * Formatear fecha legible
 */
function formatDate($date, $format = 'd/m/Y H:i') {
    if (is_string($date)) {
        $date = new DateTime($date);
    }
    return $date->format($format);
}

/**
 * Verificar si es ambiente de desarrollo
 */
function isDevelopment() {
    $config = require_once __DIR__ . '/../../secure/config.php';
    return $config['env'] === 'sandbox';
}

/**
 * Debug helper (solo en desarrollo)
 */
function debug($data, $die = false) {
    if (!isDevelopment()) {
        return;
    }
    
    echo '<pre>';
    print_r($data);
    echo '</pre>';
    
    if ($die) {
        die();
    }
}

// Helpers para nuevos endpoints
if (!function_exists('require_login')) {
    function require_login() {
        if (!isLoggedIn()) {
            http_response_code(401);
            die(json_encode(['error' => 'Login requerido']));
        }
    }
}

if (!function_exists('csrf_require_json')) {
    function csrf_require_json() {
        $input = json_decode(file_get_contents('php://input'), true);
        $token = $input['csrf'] ?? '';
        if (!validateCsrfToken($token)) {
            http_response_code(403);
            die(json_encode(['error' => 'CSRF token inválido']));
        }
    }
}

if (!function_exists('rate_limit_require')) {
    function rate_limit_require($endpoint) {
        // Implementación simple de rate limiting
        try {
            applyRateLimit($endpoint);
        } catch (Exception $e) {
            http_response_code(429);
            die(json_encode(['error' => 'Too many requests']));
        }
    }
}

if (!function_exists('json_ok')) {
    function json_ok($data = null) {
        header('Content-Type: application/json');
        echo json_encode($data ?? ['ok' => true]);
        exit;
    }
}

if (!function_exists('json_error')) {
    function json_error($message) {
        header('Content-Type: application/json');
        echo json_encode(['error' => $message]);
        exit;
    }
}

if (!function_exists('current_user_id')) {
    function current_user_id() {
        return getCurrentUserId();
    }
}

if (!function_exists('db')) {
    function db() {
        return getDB();
    }
}

// Helpers de PayPal
if (!function_exists('paypal_access_token')) {
    function paypal_access_token() {
        return getPayPalAccessToken(); // usa la función existente
    }
}

if (!function_exists('paypal_api')) {
    function paypal_api($method, $url, $data = null, $token = null) {
        if (!$token) $token = paypal_access_token();
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $token,
            'Prefer: return=representation'
        ]);
        
        if ($method === 'POST' && $data) {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode >= 400) {
            throw new Exception("PayPal API error: HTTP $httpCode - $response");
        }
        
        return json_decode($response, true);
    }
}

if (!function_exists('grantAccess')) {
    function grantAccess($pdo, $userId, $courseId) {
        $stmt = $pdo->prepare('INSERT IGNORE INTO user_access (user_id, course_id) VALUES (?, ?)');
        return $stmt->execute([$userId, $courseId]);
    }
}

/**
 * Obtener IP real del cliente
 */
function getRealIp() {
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        return $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        return $_SERVER['HTTP_X_FORWARDED_FOR'];
    } else {
        return $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    }
}

/**
 * Verificar si es POST
 */
function isPost() {
    return $_SERVER['REQUEST_METHOD'] === 'POST';
}

/**
 * Log de eventos de seguridad
 */
function logSecurity($event, $details = '', $userId = null) {
    try {
        $pdo = getDB();
        
        // Obtener IP real
        $ip = getRealIp();
        
        // Si no se especifica userId, intentar obtenerlo de la sesión
        if ($userId === null) {
            $userId = getCurrentUserId();
        }
        
        // User agent
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        
        // Preparar consulta
        $stmt = $pdo->prepare(
            'INSERT INTO security_logs (user_id, event_type, ip_address, user_agent, details, created_at) 
             VALUES (?, ?, ?, ?, ?, NOW())'
        );
        
        return $stmt->execute([
            $userId,
            $event,
            $ip,
            $userAgent,
            $details
        ]);
        
    } catch (Exception $e) {
        // En caso de error, log al error log del servidor
        error_log("Error logging security event: " . $e->getMessage());
        return false;
    }
}
