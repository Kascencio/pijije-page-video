Objetivo para Copilot: generar commits que eliminen Hosted Buttons, agreguen Smart Buttons con Orders API, centralicen CSP nonce/cabeceras, apliquen CSRF + rate limiting a endpoints sensibles, implementen verificación real de webhooks, y ajusten .htaccess a reglas válidas, sin romper el flujo actual.

Contexto del repo:

Proyecto PHP en cursos/ (sirve desde cursos/public/).

BD MySQL con tablas: users, orders, user_access, videos.

Objetivo: reemplazar Hosted Buttons por PayPal Checkout (Orders API v2) con Smart Buttons y endurecer seguridad (CSP nonce, CSRF, rate limiting, .htaccess válido).

Entornos: local XAMPP (sandbox) y cPanel (live).

1) Remplazar Hosted Buttons por Smart Buttons

Tareas:

En cursos/public/index.php:

Quitar cualquier uso de paypal.HostedButtons y referencias a hosted_button_id.

Incluir SDK:

<script src="https://www.paypal.com/sdk/js?client-id=<?= e($config['paypal']['client_id']) ?>&currency=MXN"
        nonce="<?= e(csp_nonce()) ?>"></script>


Renderizar Smart Buttons que llamen a nuestros endpoints (no tomar precio del cliente; leerlo del server):

<div id="paypal-button-container"></div>
<script nonce="<?= e(csp_nonce()) ?>">
  const csrf = "<?= e(csrf_token()) ?>";
  paypal.Buttons({
    createOrder: () =>
      fetch('/checkout/create-order.php', {
        method: 'POST',
        headers: {'Content-Type':'application/json'},
        body: JSON.stringify({ csrf })
      }).then(r => r.json()).then(d => d.orderID),
    onApprove: (data) =>
      fetch('/checkout/capture-order.php', {
        method: 'POST',
        headers: {'Content-Type':'application/json'},
        body: JSON.stringify({ csrf, orderID: data.orderID })
      }).then(r => r.json()).then(d => {
        if (d.ok) window.location.href = '/success.php';
        else alert(d.error || 'No se pudo confirmar el pago');
      }),
    onError: (err) => alert('Error de PayPal: ' + err)
  }).render('#paypal-button-container');
</script>


En cursos/secure/config.php:

Eliminar hosted_button_id si existe.

Asegurar:

'env' => 'sandbox', // 'live' en prod
'app' => [
  'base_url'  => 'http://localhost/cursos/public', // en prod cambiar a dominio https
  'course_id' => 1,
  'currency'  => 'MXN',
  'amount'    => 120000, // centavos: 120000 = $1,200.00 MXN
],
'paypal' => [
  'client_id' => 'SANDBOX_CLIENT_ID',
  'secret'    => 'SANDBOX_SECRET',
  'base_api'  => 'https://api-m.sandbox.paypal.com', // live: https://api-m.paypal.com
],

2) Endpoints PayPal (Orders API v2)

Asegurar/crear archivos:

cursos/public/checkout/create-order.php

Requerir login, CSRF, y rate limit.

Obtener amount del server (config['app']['amount']) y no del JSON del cliente.

Flujo:

OAuth2 → access_token.

POST /v2/checkout/orders con intent=CAPTURE, purchase_units[0].amount.value en MXN.

Guardar/actualizar orders(user_id, provider='paypal', provider_order_id=<id>, amount_mxn, status='pending').

Responder { orderID: <id> }.

cursos/public/checkout/capture-order.php

Requerir login, CSRF, y rate limit.

Validar que la orden exista y/o pertenezca al usuario.

Flujo:

OAuth2 → access_token.

POST /v2/checkout/orders/{id}/capture.

Si status=COMPLETED: orders.status='paid' + grantAccess(user_id, course_id=1) con idempotencia.

Responder { ok: true } o { ok: false, error: '...' }.

cursos/public/webhook/paypal.php

Implementar verificación de firma (no dejar stub). Usar v1/notifications/verify-webhook-signature con headers: transmission_id, transmission_time, cert_url, auth_algo, transmission_sig, y webhook_id de config.

En evento PAYMENT.CAPTURE.COMPLETED: confirmar orders.status='paid' + grantAccess(...) de forma idempotente.

Aplicar rate limit al webhook.

3) Seguridad (CSP, nonce, CSRF, rate limit)

Acciones:

CSP nonce único por request:

Unificar implementación en cursos/public/lib/security.php:

if (!function_exists('csp_nonce')) {
  function csp_nonce(): string {
    static $nonce = null;
    if ($nonce === null) $nonce = bin2hex(random_bytes(16));
    return $nonce;
  }
}
if (!function_exists('csp_send_headers')) {
  function csp_send_headers(): void {
    $nonce = csp_nonce();
    $cfg   = config(); // o require config
    $paypal_api = ($cfg['env']==='live') ? 'https://api-m.paypal.com' : 'https://api-m.sandbox.paypal.com';
    header("Content-Security-Policy: default-src 'self'; script-src 'self' 'nonce-{$nonce}' https://www.paypal.com; frame-src https://www.paypal.com https://drive.google.com; style-src 'self' 'unsafe-inline'; img-src 'self' data:; connect-src 'self' {$paypal_api}; base-uri 'self'; frame-ancestors 'none'");
    header('X-Frame-Options: DENY');
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: strict-origin-when-cross-origin');
  }
}


En cursos/public/lib/bootstrap.php: require_once security.php y llamar una vez a csp_send_headers(). Eliminar duplicados de getNonce(). Si hay vistas que usan getNonce(), crear alias:

if (!function_exists('getNonce')) { function getNonce(): string { return csp_nonce(); } }


CSRF: verificar token en todo POST sensible (login, register, create-order, capture, webhook si aplica lógica con estado).

Rate limit: mantener en login, register, create-order, capture, webhook.

Escape de salida: usar helper e() (htmlspecialchars) en todas las vistas.

Sesiones: httponly, samesite (Lax en local; Strict en prod), regenerar ID en login y logout.

4) .htaccess válido para cursos/public/

Sustituir .htaccess por:

Options -Indexes

<FilesMatch "\.(htaccess|htpasswd|ini|log|sh|inc|bak|sql)$">
  Require all denied
</FilesMatch>

<FilesMatch "^(\.|#).*">
  Require all denied
</FilesMatch>
<FilesMatch "~$">
  Require all denied
</FilesMatch>

<IfModule mod_rewrite.c>
  RewriteEngine On
  RewriteRule ^(?:lib|secure|config)/ - [F,L]
  RewriteCond %{REQUEST_URI} (?:^|/)(config\.php|\.env|composer\.(json|lock)|package\.json)$ [NC]
  RewriteRule ^ - [F,L]
</IfModule>

<IfModule mod_headers.c>
  Header always set X-Content-Type-Options "nosniff"
  Header always set X-Frame-Options "DENY"
  Header always set Referrer-Policy "strict-origin-when-cross-origin"
  Header unset X-Powered-By
</IfModule>

<IfModule mod_deflate.c>
  AddOutputFilterByType DEFLATE text/plain text/html text/xml text/css application/xml application/xhtml+xml application/rss+xml application/javascript application/x-javascript
</IfModule>

<IfModule mod_expires.c>
  ExpiresActive On
  ExpiresByType image/jpg "access plus 1 month"
  ExpiresByType image/jpeg "access plus 1 month"
  ExpiresByType image/gif "access plus 1 month"
  ExpiresByType image/png "access plus 1 month"
  ExpiresByType image/webp "access plus 1 month"
  ExpiresByType image/svg+xml "access plus 1 month"
  ExpiresByType text/css "access plus 1 month"
  ExpiresByType application/javascript "access plus 1 month"
  ExpiresByType application/x-javascript "access plus 1 month"
  ExpiresByType font/woff "access plus 1 year"
  ExpiresByType font/woff2 "access plus 1 year"
  ExpiresByType application/font-woff "access plus 1 year"
  ExpiresByType application/font-woff2 "access plus 1 year"
</IfModule>


No usar <Directory>/<DirectoryMatch> en .htaccess.

5) Validaciones extra y hardening

Inputs: email normalizado (lower + trim), password con pólitica mínima (10+ chars, upper/lower/digit), drive_file_id con regex ^[a-zA-Z0-9_-]+$.

DB: usuario con privilegios solo sobre la BD del proyecto.

Errores: en prod display_errors=0, logs fuera de public/.

HTTPS: forzar HTTPS solo en prod (comentado en local). HSTS solo en prod con TLS real.

6) Aceptación (QA)

Un usuario nuevo: se registra → paga (sandbox) → BD: orders(pending→paid) + user_access creado → entra a /mis-videos.php.

Webhook PAYMENT.CAPTURE.COMPLETED validado y idempotente.

Sin sesión o sin pago → acceso denegado a /mis-videos.php.

CSP sin bloqueos: SDK de PayPal carga bien; embeds de Google Drive (/preview) funcionan; fallback a /view.

.htaccess no provoca 500 y bloquea rutas internas/archivos sensibles.