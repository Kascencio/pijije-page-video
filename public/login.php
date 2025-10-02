<?php
require_once __DIR__ . '/lib/bootstrap.php';

// Si ya está logueado, redirigir
if (isLoggedIn()) {
    redirect('/mis-videos.php');
}

$errors = [];

// Procesar formulario
if (isPost()) {
    // Aplicar rate limiting
    applyRateLimit('login');
    
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
            // Redirigir a mis-videos o a la página que intentaba acceder
            $redirectTo = $_SESSION['redirect_after_login'] ?? '/mis-videos.php';
            unset($_SESSION['redirect_after_login']);
            redirect($redirectTo);
        } else {
            $errors['general'] = $result['error'];
        }
    } else {
        $errors = $validation['errors'];
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
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión - Cursos Orgánicos</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            position: relative;
            overflow: hidden;
        }
        
        body::before {
            content: '';
            position: absolute;
            inset: 0;
            background: url('/assets/lush-green-pasture-with-cattle-grazing-sustainable.jpg') center/cover;
            opacity: 0.1;
            z-index: -1;
        }
        
        .container {
            background: white;
            border-radius: 12px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            padding: 40px;
            width: 100%;
            max-width: 400px;
        }
        
        .logo {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .logo h1 {
            color: #2d3748;
            font-size: 24px;
            font-weight: 700;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        label {
            display: block;
            margin-bottom: 8px;
            color: #4a5568;
            font-weight: 500;
            font-size: 14px;
        }
        
        input[type="email"],
        input[type="password"] {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            font-size: 16px;
            transition: border-color 0.3s;
        }
        
        input[type="email"]:focus,
        input[type="password"]:focus {
            outline: none;
            border-color: #667eea;
        }
        
        .error {
            color: #e53e3e;
            font-size: 14px;
            margin-top: 5px;
            padding: 12px;
            background: #fed7d7;
            border: 1px solid #feb2b2;
            border-radius: 6px;
        }
        
        .btn {
            width: 100%;
            padding: 12px;
            background: #667eea;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        
        .btn:hover {
            background: #5a67d8;
        }
        
        .btn:disabled {
            background: #a0aec0;
            cursor: not-allowed;
        }
        
        .links {
            text-align: center;
            margin-top: 20px;
        }
        
        .links a {
            color: #667eea;
            text-decoration: none;
            font-size: 14px;
        }
        
        .links a:hover {
            text-decoration: underline;
        }
        
        .back-link {
            display: inline-block;
            margin-bottom: 20px;
            color: #667eea;
            text-decoration: none;
            font-size: 14px;
        }
        
        .back-link:hover {
            text-decoration: underline;
        }
        
        .password-help {
            font-size: 12px;
            color: #718096;
            margin-top: 5px;
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="/" class="back-link">← Volver al inicio</a>
        
        <div class="logo">
            <h1>Iniciar Sesión</h1>
        </div>
        
        <?php if (isset($errors['general'])): ?>
            <div class="error"><?= escape($errors['general']) ?></div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <?= csrfInput() ?>
            
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" value="<?= escape($_POST['email'] ?? '') ?>" required>
                <?php if (isset($errors['email'])): ?>
                    <div class="error"><?= escape($errors['email']) ?></div>
                <?php endif; ?>
            </div>
            
            <div class="form-group">
                <label for="password">Contraseña</label>
                <input type="password" id="password" name="password" required>
                <?php if (isset($errors['password'])): ?>
                    <div class="error"><?= escape($errors['password']) ?></div>
                <?php endif; ?>
                <div class="password-help">
                    ¿Olvidaste tu contraseña? Contacta soporte.
                </div>
            </div>
            
            <button type="submit" class="btn">Iniciar Sesión</button>
        </form>
        
        <div class="links">
            ¿No tienes cuenta? <a href="/register.php">Regístrate aquí</a>
        </div>
    </div>
</body>
</html>
