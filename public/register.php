<?php
require_once __DIR__ . '/lib/bootstrap.php';

// Si ya está logueado, redirigir
if (isLoggedIn()) {
    redirect('/mis-videos.php');
}

$config = require_once __DIR__ . '/../secure/config.php';
$errors = [];
$success = false;

// Procesar formulario
if (isPost()) {
    // Aplicar rate limiting
    applyRateLimit('register');
    
    // Validar CSRF
    validateCsrfRequest();
    
    // Validar datos
    $validation = validateRegistrationData($_POST);
    
    if ($validation['valid']) {
        $result = registerUser(
            $validation['data']['name'],
            $validation['data']['email'],
            $validation['data']['password']
        );
        
        if ($result['success']) {
            $success = true;
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
    <title>Registro - Cursos Orgánicos</title>
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
        
        input[type="text"],
        input[type="email"],
        input[type="password"] {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            font-size: 16px;
            transition: border-color 0.3s;
        }
        
        input[type="text"]:focus,
        input[type="email"]:focus,
        input[type="password"]:focus {
            outline: none;
            border-color: #667eea;
        }
        
        .error {
            color: #e53e3e;
            font-size: 14px;
            margin-top: 5px;
        }
        
        .success {
            color: #38a169;
            font-size: 14px;
            margin-top: 5px;
            padding: 12px;
            background: #f0fff4;
            border: 1px solid #9ae6b4;
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
    </style>
</head>
<body>
    <div class="container">
        <a href="/" class="back-link">← Volver al inicio</a>
        
        <div class="logo">
            <h1>Registro</h1>
        </div>
        
        <?php if ($success): ?>
            <div class="success">
                ¡Registro exitoso! Ya puedes <a href="/login.php">iniciar sesión</a>.
            </div>
        <?php else: ?>
            <?php if (isset($errors['general'])): ?>
                <div class="error"><?= escape($errors['general']) ?></div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <?= csrfInput() ?>
                
                <div class="form-group">
                    <label for="name">Nombre completo</label>
                    <input type="text" id="name" name="name" value="<?= escape($_POST['name'] ?? '') ?>" required>
                    <?php if (isset($errors['name'])): ?>
                        <div class="error"><?= escape($errors['name']) ?></div>
                    <?php endif; ?>
                </div>
                
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
                    <div style="font-size: 12px; color: #718096; margin-top: 5px;">
                        Mínimo 10 caracteres, debe incluir mayúsculas, minúsculas y números.
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="confirm_password">Confirmar contraseña</label>
                    <input type="password" id="confirm_password" name="confirm_password" required>
                </div>
                
                <button type="submit" class="btn">Crear cuenta</button>
            </form>
        <?php endif; ?>
        
        <div class="links">
            ¿Ya tienes cuenta? <a href="/login.php">Inicia sesión</a>
        </div>
    </div>
</body>
</html>
