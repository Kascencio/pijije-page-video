<?php
/**
 * Configuración del sistema de cursos
 * ¡OJO! Este archivo NO debe estar en una ruta pública.
 */
return [
  'env' => 'sandbox', // 'live' en producción

  'app' => [
    'base_url'  => 'http://localhost/cursos/public', // en prod cámbialo a https://tu-dominio
    'course_id' => 1,
    'currency'  => 'MXN',
    // Precio en centavos: 80000 = $800.00 MXN  (NO 800000)
    'amount'    => 80000,
    // Si usas rutas de retorno desde config:
    'return_url' => '/success.php', // se resolverá como base_url + return_url
    'cancel_url' => '/cancel.php',
  ],

  'db' => [
    // En macOS con XAMPP, '127.0.0.1' suele evitar temas de socket; usa tu usuario real
    'dsn'  => 'mysql:host=localhost;dbname=cursos;charset=utf8mb4',
    'user' => 'root',       // en local puede ser 'root'
    'pass' => '', // en local XAMPP suele ser ''
  ],

  'paypal' => [
    // SANDBOX: client_id real (no afecta seguridad, pero no lo comprometas en público)
    'client_id' => '',
    'secret'    => '', // Pónlo de tu Dashboard Sandbox
    // IMPORTANTE: Usa SIEMPRE el dominio de API REST:
    // Sandbox correcto: https://api-m.sandbox.paypal.com (ANTES estaba mal: https://sandbox.paypal.com)
    // Live correcto:    https://api-m.paypal.com
    'base_api'  => 'https://api-m.sandbox.paypal.com', // cambiar a api-m.paypal.com en producción
    // Si vas a verificar firma del webhook, usa el webhook_id real del panel
    'webhook_id' => 'WEBHOOK_ID_OPCIONAL'
  ],

  'security' => [
    'session_name'        => 'CURSOSSESSID',
    'session_secure'      => false,  // true en producción (HTTPS)
    'session_httponly'    => true,
    // En local, 'Strict' puede romper popups de PayPal; usa 'Lax' en dev:
    'session_samesite'    => 'Lax',  // en prod puedes volver a 'Strict'
    'password_min_length' => 10,
    'password_require'    => ['upper','lower','digit'],
    'rate_limit'          => ['window_sec'=>900, 'max_hits'=>20], // 20 req / 15 min
    'csp_nonce_len'       => 24,
    'max_login_attempts'  => 5,
    'lockout_duration'    => 900, // 15 min
  ],
];
