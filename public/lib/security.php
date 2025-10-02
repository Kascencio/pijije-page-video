<?php
/**
 * Funciones de seguridad - CSP nonce y headers unificados
 */

if (!function_exists('csp_nonce')) {
  function csp_nonce(): string {
    static $nonce = null;
    if ($nonce === null) $nonce = bin2hex(random_bytes(16));
    return $nonce;
  }
}

if (!function_exists('csp_send_headers')) {
  function csp_send_headers(): void {
    $cfg = require __DIR__ . '/../../secure/config.php';
    $nonce = csp_nonce();
    $paypal_api = ($cfg['env'] === 'live')
      ? 'https://api-m.paypal.com'
      : 'https://api-m.sandbox.paypal.com';

    header("Content-Security-Policy: default-src 'self'; script-src 'self' 'nonce-{$nonce}' https://www.paypal.com; frame-src https://www.paypal.com https://drive.google.com; style-src 'self' 'unsafe-inline'; img-src 'self' data:; connect-src 'self' {$paypal_api}; base-uri 'self'; frame-ancestors 'none'");
    header('X-Frame-Options: DENY');
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: strict-origin-when-cross-origin');
  }
}

/**
 * Escape HTML para salida segura
 */
if (!function_exists('e')) {
    function e($string) {
        return htmlspecialchars($string, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }
}

/**
 * Inicializar sesión segura
 */
if (!function_exists('initSecureSession')) {
    function initSecureSession() {
        $config = require __DIR__ . '/../../secure/config.php';
        
        // Configurar parámetros de sesión
        $sessionConfig = $config['security'];
        
        ini_set('session.name', $sessionConfig['session_name']);
        ini_set('session.cookie_httponly', $sessionConfig['session_httponly'] ? 1 : 0);
        ini_set('session.cookie_secure', $sessionConfig['session_secure'] ? 1 : 0);
        ini_set('session.use_strict_mode', 1);
        
        if ($sessionConfig['session_samesite']) {
            ini_set('session.cookie_samesite', $sessionConfig['session_samesite']);
        }
        
        // Iniciar sesión
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        // Regenerar ID de sesión periódicamente (cada 30 minutos)
        if (!isset($_SESSION['last_regeneration'])) {
            $_SESSION['last_regeneration'] = time();
        } elseif (time() - $_SESSION['last_regeneration'] > 1800) {
            session_regenerate_id(true);
            $_SESSION['last_regeneration'] = time();
        }
    }
}
