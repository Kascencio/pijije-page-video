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

/** Obtener course id principal desde config */
function courseId() {
    $cfg = config();
    return (int)($cfg['app']['course_id'] ?? 1);
}

/** Migraciones ligeras runtime (solo en entorno no live) */
if (!function_exists('runLightMigrations')) {
    function runLightMigrations() {
        static $done = false; if ($done) return; $done = true;
        try {
            $cfg = config();
            $isLive = ($cfg['env'] ?? 'sandbox') === 'live';
            if ($isLive) return; // solo en dev/sandbox
            $db = getDB();
            // users.active
            $col = $db->fetchOne("SHOW COLUMNS FROM users LIKE 'active'");
            if (!$col) {
                $db->query('ALTER TABLE users ADD COLUMN active TINYINT(1) NOT NULL DEFAULT 1 AFTER pass_hash');
                error_log('[MIGRATION] Añadida columna users.active');
            }
            // videos.course_id
            $col = $db->fetchOne("SHOW COLUMNS FROM videos LIKE 'course_id'");
            if (!$col) {
                $db->query('ALTER TABLE videos ADD COLUMN course_id INT NOT NULL DEFAULT 1 AFTER id');
                $db->query('UPDATE videos SET course_id = 1 WHERE course_id = 0 OR course_id IS NULL');
                error_log('[MIGRATION] Añadida columna videos.course_id');
            }
            // Asegurar tabla user_access (por si en una instalación antigua no existe)
            $tbl = $db->fetchOne("SHOW TABLES LIKE 'user_access'");
            if (!$tbl) {
                $db->query("CREATE TABLE user_access (\n  id INT AUTO_INCREMENT PRIMARY KEY,\n  user_id INT NOT NULL,\n  course_id INT NOT NULL,\n  granted_at DATETIME DEFAULT CURRENT_TIMESTAMP,\n  UNIQUE KEY uq_user_course (user_id, course_id),\n  INDEX idx_course_id (course_id),\n  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE\n) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
                error_log('[MIGRATION] Creada tabla user_access');
            } else {
                // Asegurar columnas mínimas
                $col = $db->fetchOne("SHOW COLUMNS FROM user_access LIKE 'course_id'");
                if (!$col) {
                    $db->query("ALTER TABLE user_access ADD COLUMN course_id INT NOT NULL DEFAULT 1 AFTER user_id");
                    $db->query("ALTER TABLE user_access ADD UNIQUE KEY uq_user_course (user_id, course_id)");
                    error_log('[MIGRATION] Ajustada tabla user_access (course_id)');
                }
                // Añadir expires_at si no existe
                $col = $db->fetchOne("SHOW COLUMNS FROM user_access LIKE 'expires_at'");
                if (!$col) {
                    $db->query("ALTER TABLE user_access ADD COLUMN expires_at DATETIME NULL AFTER granted_at");
                    error_log('[MIGRATION] Añadida columna user_access.expires_at');
                }
            }
            // Asegurar tabla user_video_progress
            $tbl = $db->fetchOne("SHOW TABLES LIKE 'user_video_progress'");
            if (!$tbl) {
                $db->query("CREATE TABLE user_video_progress (\n  id INT AUTO_INCREMENT PRIMARY KEY,\n  user_id INT NOT NULL,\n  video_id INT NOT NULL,\n  seconds INT NOT NULL DEFAULT 0,\n  duration INT NULL,\n  completed_at DATETIME NULL,\n  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,\n  UNIQUE KEY uq_user_video (user_id, video_id),\n  INDEX idx_user (user_id),\n  INDEX idx_video (video_id),\n  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,\n  FOREIGN KEY (video_id) REFERENCES videos(id) ON DELETE CASCADE\n) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
                error_log('[MIGRATION] Creada tabla user_video_progress');
            }
        } catch (Throwable $e) {
            error_log('[MIGRATION] Error: '.$e->getMessage());
        }
    }
    runLightMigrations();
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
    // Usar config() que cachea correctamente. OJO: require_once devolvía bool en subsiguientes llamadas
    $cfg = config();
    return rtrim($cfg['app']['base_url'], '/');
}

/**
 * Obtener configuración global (helper)
 */
function config() {
    static $config = null;
    if ($config === null) {
        $config = require __DIR__ . '/../../secure/config.php';
        // Fusionar overrides desde system_config si la tabla existe
        try {
            $db = getDB();
            $tbl = $db->fetchOne("SHOW TABLES LIKE 'system_config'");
            if ($tbl) {
                $rows = $db->fetchAll('SELECT config_key, config_value FROM system_config');
                $map = [];
                foreach ($rows as $r) { $map[$r['config_key']] = $r['config_value']; }
                // Override de precio (centavos)
                if (isset($map['course_price']) && is_numeric($map['course_price'])) {
                    $config['app']['amount'] = (int)$map['course_price'];
                }
                if (!empty($map['course_title'])) {
                    $config['app']['course_title'] = $map['course_title'];
                }
                if (!empty($map['course_description'])) {
                    $config['app']['course_description'] = $map['course_description'];
                }
                if (isset($map['course_duration']) && is_numeric($map['course_duration'])) {
                    $config['app']['course_duration_months'] = (int)$map['course_duration'];
                }
                if (!empty($map['paypal_client_id'])) {
                    $cid = trim($map['paypal_client_id']);
                    // Quitar espacios internos extremos accidentales (no alterar intencionalmente el valor)
                    $config['paypal']['client_id'] = $cid;
                }
                if (isset($map['paypal_secret']) && $map['paypal_secret'] !== '') {
                    // Permitir formato cifrado con prefijo enc:
                    $secretRaw = $map['paypal_secret'];
                    if (str_starts_with($secretRaw, 'enc:')) {
                        $dec = decryptAppSecret(substr($secretRaw, 4));
                        if ($dec !== null) {
                            $secretRaw = $dec;
                        } else {
                            error_log('[CONFIG MERGE] No se pudo descifrar paypal_secret (¿APP_KEY cambió?).');
                            // Forzar que se considere incompleto en lugar de usar texto cifrado inválido
                            $secretRaw = '';
                        }
                    }
                    $config['paypal']['secret'] = $secretRaw;
                }
                // Permitir override futuro del dominio base_api si se agrega campo en settings
                if (!empty($map['paypal_base_api'])) {
                    $config['paypal']['base_api'] = trim($map['paypal_base_api']);
                }
                // Potenciales futuras claves: paypal_secret, contact_email, etc.
            }
        } catch (Throwable $e) {
            // Silencioso: no romper app si no existe tabla
            error_log('[CONFIG MERGE] '.$e->getMessage());
        }
        // Normalización defensiva de base_api (corrige dominios comunes incorrectos)
        if (!empty($config['paypal']['base_api'])) {
            $base = rtrim($config['paypal']['base_api'], '/');
            if (preg_match('~^https://sandbox\.paypal\.com$~i', $base) || preg_match('~^https://www\.sandbox\.paypal\.com$~i', $base)) {
                error_log('[CONFIG] Corrigiendo base_api inválida (sandbox.paypal.com) -> api-m.sandbox.paypal.com');
                $base = 'https://api-m.sandbox.paypal.com';
            } elseif (preg_match('~^https://www\.paypal\.com$~i', $base)) {
                error_log('[CONFIG] Corrigiendo base_api inválida (www.paypal.com) -> api-m.paypal.com');
                $base = 'https://api-m.paypal.com';
            }
            $config['paypal']['base_api'] = $base;
        }
    }
    return $config;
}

// --- Secret encryption helpers (opcional) ---
if (!function_exists('getAppKey')) {
    function getAppKey() {
        return $_ENV['APP_KEY'] ?? getenv('APP_KEY') ?: null;
    }
}
if (!function_exists('encryptAppSecret')) {
    function encryptAppSecret($plain) {
        $key = getAppKey();
        if (!$key || !extension_loaded('sodium')) return null;
        $keyBin = substr(hash('sha256', $key, true), 0, SODIUM_CRYPTO_SECRETBOX_KEYBYTES);
        $nonce = random_bytes(SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
        $cipher = sodium_crypto_secretbox($plain, $nonce, $keyBin);
        return base64_encode($nonce . $cipher);
    }
}
if (!function_exists('decryptAppSecret')) {
    function decryptAppSecret($encoded) {
        $key = getAppKey();
        if (!$key || !extension_loaded('sodium')) return null;
        $raw = base64_decode($encoded, true);
        if ($raw === false) return null;
        $nonceSize = SODIUM_CRYPTO_SECRETBOX_NONCEBYTES;
        $nonce = substr($raw, 0, $nonceSize);
        $cipher = substr($raw, $nonceSize);
        $keyBin = substr(hash('sha256', $key, true), 0, SODIUM_CRYPTO_SECRETBOX_KEYBYTES);
        $plain = @sodium_crypto_secretbox_open($cipher, $nonce, $keyBin);
        return $plain === false ? null : $plain;
    }
}

// Helpers específicos de curso
if (!function_exists('courseTitle')) {
    function courseTitle() {
        $cfg = config();
        return $cfg['app']['course_title'] ?? 'Curso';
    }
}
if (!function_exists('courseDescription')) {
    function courseDescription() {
        $cfg = config();
        return $cfg['app']['course_description'] ?? '';
    }
}
if (!function_exists('coursePriceCents')) {
    function coursePriceCents() {
        $cfg = config();
        return (int)($cfg['app']['amount'] ?? 0);
    }
}
if (!function_exists('coursePriceFormatted')) {
    function coursePriceFormatted() {
        $cents = coursePriceCents();
        $cfg = config();
        $currency = $cfg['app']['currency'] ?? 'MXN';
        return formatPrice($cents, $currency);
    }
}
if (!function_exists('courseDurationMonths')) {
    function courseDurationMonths() {
        $cfg = config();
        return (int)($cfg['app']['course_duration_months'] ?? ($cfg['app']['course_duration'] ?? 0));
    }
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
 * Helper para construir URLs del panel admin respetando base_url
 * Ej: adminUrl('users/index.php') => http://localhost/cursos/public/admin/users/index.php
 */
if (!function_exists('adminUrl')) {
    function adminUrl($path = '') {
        $path = ltrim($path, '/');
        return url('admin/' . $path);
    }
}

/**
 * URL para assets estáticos respetando base_url
 */
if (!function_exists('asset')) {
    function asset($path) {
        return url($path);
    }
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
    // Evitar referenciar directamente la constante si no existe (causa fatal error)
    if (defined('PASSWORD_ARGON2ID')) {
        $algo = PASSWORD_ARGON2ID;
        $options = [
            'memory_cost' => 1 << 16, // 65536
            'time_cost' => 4,
            'threads' => 2,
        ];
    } else {
        $algo = PASSWORD_BCRYPT; // fallback seguro
        $options = ['cost' => 12];
    }

    $hash = password_hash($password, $algo, $options);
    if ($hash === false) {
        error_log('[PASSWORD] Falló password_hash() usando algoritmo: ' . $algo);
        throw new RuntimeException('No se pudo generar hash de la contraseña');
    }
    return $hash;
}

/**
 * Verificar contraseña
 */
function verifyPassword($password, $hash) {
    if (!password_verify($password, $hash)) {
        return false;
    }
    // Opcional: rehash si ahora hay un algoritmo más fuerte disponible
    if (defined('PASSWORD_ARGON2ID') && password_needs_rehash($hash, PASSWORD_ARGON2ID, [
        'memory_cost' => 1<<16,
        'time_cost' => 4,
        'threads' => 2,
    ])) {
        try {
            $newHash = hashPassword($password);
            // Guardar en BD si tenemos contexto de usuario (no aquí para mantener pureza)
        } catch (Exception $e) {
            // Silencioso: no crítico
        }
    }
    return true;
}

/**
 * Información del algoritmo de password en runtime (debug opcional)
 */
function passwordAlgoInfo() : array {
    $algo = defined('PASSWORD_ARGON2ID') ? 'argon2id' : 'bcrypt';
    $info = ['algo' => $algo];
    if ($algo === 'bcrypt') {
        $info['note'] = 'Argon2ID no disponible en esta build de PHP; usando BCRYPT.';
    }
    return $info;
}

/**
 * Validar política de contraseña
 */
function validatePasswordPolicy($password) {
    $cfg = config();
    $minLength = $cfg['security']['password_min_length'];
    $requirements = $cfg['security']['password_require'];
    
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

// Determinar si la petición es AJAX (fallback simple)
if (!function_exists('isAjax')) {
    function isAjax(): bool {
        return isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
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

        $method = strtoupper($method);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $token,
            'Prefer: return=representation'
        ]);

        // Asegurar que POST se envíe como POST aunque no haya body (capture no requiere payload)
        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            if ($data !== null) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            }
        } elseif ($method === 'PATCH') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PATCH');
            if ($data !== null) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            }
        } elseif ($method !== 'GET') {
            // Otros métodos si eventualmente se usan
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
            if ($data !== null) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            }
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

// Renombrado: grantAccessDirect para no colisionar con grantAccess() de access.php
if (!function_exists('grantAccessDirect')) {
    function grantAccessDirect($pdo, $userId, $courseId) {
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
    $pdo = getDB()->getPdo();
        if ($pdo === null) {
            throw new RuntimeException('PDO no inicializado');
        }
        $ip = getRealIp();
        if ($userId === null) {
            $userId = getCurrentUserId();
        }

        // Ajustar a columnas reales del schema (id, user_id, ip_address, action, details JSON, created_at)
        // Si el schema original tenía columnas diferentes, este insert debe alinearse a: security_logs(action, user_id, ip_address, details)
        $stmt = $pdo->prepare(
            'INSERT INTO security_logs (action, user_id, ip_address, details, created_at) VALUES (?, ?, ?, ?, NOW())'
        );
        if (is_array($details) || is_object($details)) {
            $details = json_encode($details, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
        }
        return $stmt->execute([$event, $userId, $ip, $details]);
    } catch (Exception $e) {
        error_log("Error logging security event: " . $e->getMessage());
        return false;
    }
}
