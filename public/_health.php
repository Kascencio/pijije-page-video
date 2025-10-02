<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Health Check</h2>";

$ok = function($label){ echo "<p style='color:green'>✔ $label</p>"; };
$bad = function($label,$e=null){ echo "<p style='color:red'>✘ $label".($e?": ".$e:"")."</p>"; };

try {
  require_once __DIR__.'/lib/security.php';
  require_once __DIR__.'/lib/db.php';
  require_once __DIR__.'/lib/auth.php';
  require_once __DIR__.'/lib/csrf.php';
  require_once __DIR__.'/lib/validate.php';
  require_once __DIR__.'/lib/utils.php';
  $ok("Includes lib/*");
} catch (Throwable $e) {
  $bad("Includes lib/*", $e->getMessage());
}

try {
  $cfg = require __DIR__.'/../secure/config.php';
  if (!is_array($cfg)) throw new Exception("config no es array");
  echo "<pre>config['app']['base_url']: ".htmlspecialchars($cfg['app']['base_url'])."</pre>";
  $ok("Cargar secure/config.php");
} catch (Throwable $e) {
  $bad("Cargar secure/config.php", $e->getMessage());
}

try {
  // Probar nonce/CSP (no pasa nada si ya enviaste headers)
  if (function_exists('csp_send_headers')) { @csp_send_headers(); }
  if (function_exists('csp_nonce')) { echo "<pre>nonce: ".htmlspecialchars(csp_nonce())."</pre>"; }
  $ok("Seguridad (CSP nonce)");
} catch (Throwable $e) {
  $bad("Seguridad (CSP)", $e->getMessage());
}

try {
  $pdo = db(); // tu helper debe leer de config.php
  $stmt = $pdo->query("SELECT 1");
  $stmt->fetch();
  $ok("Conexión PDO/MySQL");
} catch (Throwable $e) {
  $bad("Conexión PDO/MySQL", $e->getMessage());
}

try {
  if (!function_exists('validateDriveFileId')) throw new Exception("faltante validateDriveFileId()");
  if (!validateDriveFileId("ABC_def-123")) throw new Exception("regex no matchea");
  $ok("Validaciones cargadas (validate.php)");
} catch (Throwable $e) {
  $bad("Validaciones", $e->getMessage());
}

echo "<hr><p>Si algo marca ✘, ahí está la causa del 500. Copia ese mensaje y lo arreglamos.</p>";
