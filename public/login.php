<?php
require_once __DIR__ . '/lib/bootstrap.php';
// Debug ligero (solo en sandbox) para rastrear errores 500 sin mostrar detalles al usuario
try { if (function_exists('config') && (config()['env'] ?? 'sandbox') !== 'live') { error_log('[LOGIN] Cargando login.php'); } } catch(Throwable $___e) {}

// Si ya está logueado, redirigir
if (isLoggedIn()) {
    redirect('mis-videos.php');
}

$errors = [];

// Procesar formulario
if (isPost()) {
    // Guard básico: asegurar que las funciones críticas existen
    foreach(['canAttempt','recordFailedAttempt','resetRateLimit','validateCsrfRequest','validateLoginData','authenticateUser'] as $f){ if(!function_exists($f)){ error_log('[LOGIN] Falta función: '.$f); http_response_code(500); die('Error interno'); }}

    $attempt = canAttempt('login');
    if (!$attempt['allowed']) {
        $errors['general'] = 'Demasiados intentos fallidos. Espera '.($attempt['retry_after'] ?? 300).' segundos e inténtalo nuevamente.';
    } else {
        // Validar CSRF
        validateCsrfRequest();
        
        // Validar datos
        $validation = validateLoginData($_POST);
        
        if ($validation['valid']) {
            $result = authenticateUser(
                $validation['data']['email'],
                $validation['data']['password']
            );
            
            if ($result['success']) {
                // Resetear contador de intentos fallidos al éxito
                resetRateLimit('login');
                $redirectTo = $_SESSION['redirect_after_login'] ?? '/mis-videos.php';
                unset($_SESSION['redirect_after_login']);
                redirect($redirectTo);
            } else {
                recordFailedAttempt('login');
                $errors['general'] = $result['error'];
            }
        } else {
            // Si validación falla, lo contamos como intento fallido genérico
            recordFailedAttempt('login');
            $errors = array_merge($errors, $validation['errors']);
        }
    }
}

// Obtener flash message si existe
$flash = getFlash();
if ($flash) {
    $errors['general'] = $flash['message'];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
        <meta charset="UTF-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1.0" />
        <title>Iniciar Sesión - Plataforma Pijije Regenerativo</title>
        <link rel="preconnect" href="https://fonts.googleapis.com" />
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet" />
        <link rel="stylesheet" href="<?= asset('assets/css/app.css') ?>?v=<?= time() ?>" />
</head>
<body class="auth-shell bg-background text-foreground">
    <div class="auth-layout">
        <aside class="auth-side">
                <div class="auth-side-overlay"></div>
                <div class="auth-side-inner">
                        <a href="<?= url() ?>" class="auth-logo-link" aria-label="Volver al inicio">
                                <div class="auth-logo-circle">PR</div>
                        </a>
                        <h2 class="auth-side-title">Regenera. Aprende. Aplica.</h2>
                        <p class="auth-side-text">Accede a tu formación en ganadería regenerativa y participa en oportunidades reales de bonos de carbono y biodiversidad.</p>
                        <ul class="auth-bullets">
                                <li>+8 horas de contenido estructurado</li>
                                <li>Acceso a actualizaciones futuras</li>
                                <li>Base para certificaciones y proyectos</li>
                        </ul>
                        <div class="auth-side-footer">© <?= date('Y') ?> Pijije Regenerativo</div>
                </div>
        </aside>
        <main class="auth-main animate-fadeIn" aria-labelledby="titulo-login">
            <a href="<?= url() ?>" class="auth-back">← Volver al inicio</a>
            <header class="auth-header">
                    <h1 id="titulo-login" class="auth-title">Iniciar Sesión</h1>
                    <p class="auth-sub">Accede a tu cuenta y continúa tu progreso.</p>
            </header>

            <?php if (isset($errors['general'])): ?>
                <div class="alert alert-error" role="alert" aria-live="assertive">
                    <strong>Error:</strong> <?= escape($errors['general']) ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="" class="auth-form" novalidate>
                <?= csrfInput() ?>
                <div class="form-field">
                    <label for="email" class="auth-label">Email</label>
                    <input type="email" autocomplete="email" id="email" name="email" class="auth-input" value="<?= escape($_POST['email'] ?? '') ?>" required aria-required="true"/>
                    <?php if (isset($errors['email'])): ?><div class="field-error" role="alert"><?= escape($errors['email']) ?></div><?php endif; ?>
                </div>
                <div class="form-field">
                    <label for="password" class="auth-label">Contraseña</label>
                    <div class="password-wrapper">
                        <input type="password" autocomplete="current-password" id="password" name="password" class="auth-input has-toggle" required aria-required="true"/>
                        <button type="button" class="password-toggle" data-target="password" aria-label="Mostrar contraseña" aria-pressed="false">👁</button>
                    </div>
                    <?php if (isset($errors['password'])): ?><div class="field-error" role="alert"><?= escape($errors['password']) ?></div><?php endif; ?>
                    <div class="auth-help"><span class="text-muted-foreground">¿Olvidaste tu contraseña?</span> <a href="mailto:organicosdeltropico@yahoo.com.mx" class="auth-link">Contactar soporte</a></div>
                </div>
                <div class="form-options">
                    <label class="remember"><input type="checkbox" name="remember" value="1" /> <span>Mantener sesión</span></label>
                </div>
                <button type="submit" class="btn-primary btn-gradient">Iniciar Sesión</button>
            </form>
            <div class="auth-alt">¿No tienes cuenta? <a href="<?= url('register.php') ?>" class="auth-link">Regístrate aquí</a></div>
        </main>
    </div>
    <script nonce="<?= e(csp_nonce()) ?>">
        (function(){
            document.querySelectorAll('.password-toggle').forEach(function(btn){
                btn.addEventListener('click', function(){
                    var id = btn.getAttribute('data-target');
                    var input = document.getElementById(id);
                    if(!input) return;
                        var is = input.type === 'password';
                        input.type = is ? 'text' : 'password';
                        btn.setAttribute('aria-pressed', is ? 'true' : 'false');
                        btn.textContent = is ? '🙈' : '👁';
                });
            });
        })();
    </script>
</body>
</html>
