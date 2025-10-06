<?php
require __DIR__ . '/../lib/bootstrap.php';
// Solo tiene sentido si el usuario está logueado y aún no tiene acceso
if (!isLoggedIn()) { http_response_code(204); exit; }
header('Content-Type: application/json; charset=utf-8');
try {
    // Intentar cachear token (ignorar errores de red silenciosamente para no romper UX)
    $ok = true;
    try { paypal_access_token(); } catch (Throwable $e) { $ok = false; }
    echo json_encode(['ok' => $ok]);
} catch (Throwable $e) {
    echo json_encode(['ok' => false]);
}
