<?php
// ini_set('display_errors', 1); error_reporting(E_ALL); // solo en local si necesitas

require_once __DIR__ . '/security.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/csrf.php';
require_once __DIR__ . '/validate.php';
require_once __DIR__ . '/utils.php';
require_once __DIR__ . '/paypal.php';

initSecureSession();
if (function_exists('csp_send_headers')) csp_send_headers();

if (!function_exists('getNonce') && function_exists('csp_nonce')) {
  function getNonce(): string { return csp_nonce(); }
}
