<?php
require_once __DIR__ . '/lib/bootstrap.php';
try { if (function_exists('config') && (config()['env'] ?? 'sandbox') !== 'live') { error_log('[REGISTER] Cargando register.php'); } } catch(Throwable $___e) {}

// Si ya está logueado, redirigir
if (isLoggedIn()) {
    redirect('mis-videos.php');
}

$config = require_once __DIR__ . '/../secure/config.php';
$errors = [];
$success = false;

// Procesar formulario
if (isPost()) {
    foreach(['canAttempt','recordFailedAttempt','validateCsrfRequest','validateRegistrationData','registerUser'] as $f){ if(!function_exists($f)){ error_log('[REGISTER] Falta función: '.$f); http_response_code(500); die('Error interno'); }}

    $attempt = canAttempt('register');
    if (!$attempt['allowed']) {
        $errors['general'] = 'Has alcanzado el límite de registros desde esta IP. Espera '.($attempt['retry_after'] ?? 3600).' segundos.';
    } else {
        validateCsrfRequest();
        $validation = validateRegistrationData($_POST);
        if ($validation['valid']) {
            $result = registerUser(
                $validation['data']['name'],
                $validation['data']['email'],
                $validation['data']['password']
            );
            if ($result['success']) {
                // No reset necesario (el registro crea usuario); dejar registros para evitar spam masivo
                $success = true;
            } else {
                recordFailedAttempt('register');
                $errors['general'] = $result['error'];
            }
        } else {
            recordFailedAttempt('register');
            $errors = $validation['errors'];
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
        <title>Registro - Plataforma Pijije Regenerativo</title>
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
                        <h2 class="auth-side-title">Construye tu base regenerativa</h2>
                        <p class="auth-side-text">Acceso inmediato a módulos clave, enfoque práctico y base para participar en proyectos de bonos de carbono.</p>
                        <ul class="auth-bullets">
                                <li>Acceso permanente</li>
                                <li>Contenido ampliado en evolución</li>
                                <li>Soporte básico por email</li>
                        </ul>
                        <div class="auth-side-footer">© <?= date('Y') ?> Pijije Regenerativo</div>
                </div>
        </aside>
        <main class="auth-main animate-fadeIn" aria-labelledby="titulo-registro">
            <a href="<?= url() ?>" class="auth-back">← Volver al inicio</a>
            <header class="auth-header">
                    <h1 id="titulo-registro" class="auth-title">Crear Cuenta</h1>
                    <p class="auth-sub">Regístrate para acceder al curso completo.</p>
            </header>
            <?php if ($success): ?>
                <div class="alert alert-success" role="alert" aria-live="polite">
                    <strong>¡Listo!</strong> Registro exitoso. Ahora puedes <a href="<?= url('login.php') ?>" class="auth-link">iniciar sesión</a>.
                </div>
            <?php else: ?>
                <?php if (isset($errors['general'])): ?>
                    <div class="alert alert-error" role="alert" aria-live="assertive">
                        <strong>Error:</strong> <?= escape($errors['general']) ?>
                    </div>
                <?php endif; ?>
                <form method="POST" action="" class="auth-form" novalidate>
                    <?= csrfInput() ?>
                    <div class="form-field">
                        <label for="name" class="auth-label">Nombre completo</label>
                        <input type="text" id="name" name="name" class="auth-input" value="<?= escape($_POST['name'] ?? '') ?>" required aria-required="true" autocomplete="name" />
                        <?php if (isset($errors['name'])): ?><div class="field-error" role="alert"><?= escape($errors['name']) ?></div><?php endif; ?>
                    </div>
                    <div class="form-field">
                        <label for="email" class="auth-label">Email</label>
                        <input type="email" id="email" name="email" class="auth-input" value="<?= escape($_POST['email'] ?? '') ?>" required aria-required="true" autocomplete="email" />
                        <?php if (isset($errors['email'])): ?><div class="field-error" role="alert"><?= escape($errors['email']) ?></div><?php endif; ?>
                    </div>
                    <div class="form-field">
                        <label for="password" class="auth-label">Contraseña</label>
                        <div class="password-wrapper">
                            <input type="password" id="password" name="password" class="auth-input has-toggle" required aria-required="true" autocomplete="new-password" />
                            <button type="button" class="password-toggle" data-target="password" aria-label="Mostrar contraseña" aria-pressed="false">👁</button>
                        </div>
                        <?php if (isset($errors['password'])): ?><div class="field-error" role="alert"><?= escape($errors['password']) ?></div><?php endif; ?>
                        <div class="auth-help">Mínimo 10 caracteres, incluye mayúsculas, minúsculas y números.</div>
                    </div>
                    <div class="form-field">
                        <label for="confirm_password" class="auth-label">Confirmar contraseña</label>
                        <div class="password-wrapper">
                            <input type="password" id="confirm_password" name="confirm_password" class="auth-input has-toggle" required aria-required="true" autocomplete="new-password" />
                            <button type="button" class="password-toggle" data-target="confirm_password" aria-label="Mostrar contraseña" aria-pressed="false">👁</button>
                        </div>
                    </div>
                    <button type="submit" class="btn-primary btn-gradient">Crear cuenta</button>
                    <div class="form-foot-note">Al registrarte aceptas políticas internas y tratamiento de datos conforme al aviso de privacidad.</div>
                </form>
            <?php endif; ?>
            <div class="auth-alt">¿Ya tienes cuenta? <a href="<?= url('login.php') ?>" class="auth-link">Inicia sesión</a></div>
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
