<?php
/**
 * Protección CSRF (Cross-Site Request Forgery)
 * Generación y validación de tokens CSRF
 */

/**
 * Generar token CSRF
 */
function generateCsrfToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Validar token CSRF
 */
function validateCsrfToken($token) {
    if (!isset($_SESSION['csrf_token'])) {
        return false;
    }
    
    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Obtener token CSRF actual
 */
function getCsrfToken() {
    return $_SESSION['csrf_token'] ?? '';
}

/**
 * Alias para obtener token CSRF (para compatibilidad)
 */
function csrf_token() {
    return generateCsrfToken();
}

/**
 * Generar input hidden con token CSRF
 */
function csrfInput() {
    $token = generateCsrfToken();
    return '<input type="hidden" name="csrf_token" value="' . escape($token) . '">';
}

/**
 * Validar CSRF en petición POST (formularios y JSON)
 */
function validateCsrfRequest() {
    if (!isPost()) {
        return true; // Solo validar en POST
    }
    
    // Obtener token desde POST o JSON
    $token = $_POST['csrf_token'] ?? '';
    
    if (!$token) {
        // Si no hay token en POST, verificar JSON
        $input = json_decode(file_get_contents('php://input'), true);
        $token = $input['csrf'] ?? '';
    }
    
    if (!validateCsrfToken($token)) {
        logSecurity('csrf_validation_failed', [
            'ip' => getRealIp(),
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
            'referer' => $_SERVER['HTTP_REFERER'] ?? '',
            'url' => $_SERVER['REQUEST_URI'] ?? ''
        ]);
        
        http_response_code(403);
        die('Token CSRF inválido. Por favor, recarga la página e intenta nuevamente.');
    }
    
    return true;
}

/**
 * Regenerar token CSRF (usar después de validación exitosa)
 */
function regenerateCsrfToken() {
    unset($_SESSION['csrf_token']);
    return generateCsrfToken();
}
