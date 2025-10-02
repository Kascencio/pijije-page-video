<?php
require_once __DIR__ . '/../lib/bootstrap.php';
require_once __DIR__ . '/../lib/admin.php';

// Si ya está logueado como admin, redirigir al dashboard
if (isAdmin()) {
    redirect('/admin/dashboard/index.php');
}

$errors = [];

// Procesar formulario
if (isPost()) {
    // Aplicar rate limiting
    applyRateLimit('admin_login');
    
    // Validar CSRF
    validateCsrfRequest();
    
    // Validar datos básicos
    if (empty($_POST['username']) || empty($_POST['password'])) {
        $errors['general'] = 'Usuario y contraseña son requeridos';
    } else {
        $result = authenticateAdmin($_POST['username'], $_POST['password']);
        
        if ($result['success']) {
            redirect('/admin/dashboard/index.php');
        } else {
            $errors['general'] = $result['error'];
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
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - Cursos Orgánicos</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #1e3a8a 0%, #7c3aed 100%);
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
            opacity: 0.05;
            z-index: -1;
        }
        
        .container {
            background: white;
            border-radius: 16px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.3);
            padding: 40px;
            width: 100%;
            max-width: 400px;
            position: relative;
            z-index: 10;
        }
        
        .logo {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .logo h1 {
            color: #1e3a8a;
            font-size: 24px;
            font-weight: 700;
            margin-bottom: 8px;
        }
        
        .logo p {
            color: #6b7280;
            font-size: 14px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        label {
            display: block;
            margin-bottom: 8px;
            color: #374151;
            font-weight: 500;
            font-size: 14px;
        }
        
        input[type="text"],
        input[type="password"] {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            font-size: 16px;
            transition: border-color 0.3s;
            background: #f9fafb;
        }
        
        input[type="text"]:focus,
        input[type="password"]:focus {
            outline: none;
            border-color: #3b82f6;
            background: white;
        }
        
        .error {
            color: #dc2626;
            font-size: 14px;
            margin-top: 5px;
            padding: 12px;
            background: #fef2f2;
            border: 1px solid #fecaca;
            border-radius: 6px;
        }
        
        .btn {
            width: 100%;
            padding: 12px;
            background: #3b82f6;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        
        .btn:hover {
            background: #2563eb;
        }
        
        .btn:disabled {
            background: #9ca3af;
            cursor: not-allowed;
        }
        
        .back-link {
            display: inline-block;
            margin-bottom: 20px;
            color: #6b7280;
            text-decoration: none;
            font-size: 14px;
            transition: color 0.3s;
        }
        
        .back-link:hover {
            color: #3b82f6;
        }
        
        .security-notice {
            margin-top: 20px;
            padding: 12px;
            background: #fef3c7;
            border: 1px solid #f59e0b;
            border-radius: 6px;
            font-size: 12px;
            color: #92400e;
            text-align: center;
        }
        
        .admin-icon {
            text-align: center;
            margin-bottom: 20px;
        }
        
        .admin-icon svg {
            width: 48px;
            height: 48px;
            color: #3b82f6;
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="/" class="back-link">← Volver al sitio principal</a>
        
        <div class="admin-icon">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
            </svg>
        </div>
        
        <div class="logo">
            <h1>Panel de Administración</h1>
            <p>Cursos Orgánicos del Trópico</p>
        </div>
        
        <?php if (isset($errors['general'])): ?>
            <div class="error"><?= escape($errors['general']) ?></div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <?= csrfInput() ?>
            
            <div class="form-group">
                <label for="username">Usuario</label>
                <input type="text" id="username" name="username" value="<?= escape($_POST['username'] ?? '') ?>" required autocomplete="username">
            </div>
            
            <div class="form-group">
                <label for="password">Contraseña</label>
                <input type="password" id="password" name="password" required autocomplete="current-password">
            </div>
            
            <button type="submit" class="btn">Iniciar Sesión</button>
        </form>
        
        <div class="security-notice">
            <strong>⚠️ Área Restringida</strong><br>
            Solo personal autorizado puede acceder a este panel.
        </div>
    </div>
</body>
</html>
