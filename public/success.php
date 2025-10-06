<?php
require_once __DIR__ . '/lib/bootstrap.php';

$config = require_once __DIR__ . '/../secure/config.php';
$isLoggedIn = isLoggedIn();

// Si no está logueado, redirigir al login
if (!$isLoggedIn) {
    redirect('login.php');
}

// Verificar si tiene acceso al curso
$hasAccess = hasAccess(getCurrentUserId(), $config['app']['course_id']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pago Exitoso - Cursos Orgánicos</title>
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
        }
        
        .container {
            background: white;
            border-radius: 16px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            padding: 40px;
            width: 100%;
            max-width: 500px;
            text-align: center;
        }
        
        .success-icon {
            font-size: 64px;
            color: #38a169;
            margin-bottom: 24px;
        }
        
        h1 {
            color: #2d3748;
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 16px;
        }
        
        p {
            color: #4a5568;
            font-size: 16px;
            line-height: 1.6;
            margin-bottom: 24px;
        }
        
        .btn {
            display: inline-block;
            padding: 12px 24px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s;
            border: none;
            cursor: pointer;
            font-size: 16px;
        }
        
        .btn-primary {
            background: #667eea;
            color: white;
        }
        
        .btn-primary:hover {
            background: #5a67d8;
            transform: translateY(-2px);
        }
        
        .btn-secondary {
            background: #e2e8f0;
            color: #4a5568;
            margin-left: 12px;
        }
        
        .btn-secondary:hover {
            background: #cbd5e0;
        }
        
        .access-info {
            background: #f0fff4;
            border: 1px solid #9ae6b4;
            border-radius: 8px;
            padding: 16px;
            margin: 24px 0;
        }
        
        .access-info h3 {
            color: #22543d;
            font-size: 18px;
            margin-bottom: 8px;
        }
        
        .access-info p {
            color: #22543d;
            font-size: 14px;
            margin: 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="success-icon">✅</div>
        
        <h1>¡Pago Exitoso!</h1>
        
        <p>
            Tu pago ha sido procesado correctamente. Ya tienes acceso completo a <?= escape(courseTitle()) ?>.
        </p>
        
        <?php if ($hasAccess): ?>
            <div class="access-info">
                <h3>🎉 ¡Acceso Confirmado!</h3>
                <p>Ya puedes acceder a todos los videos del curso</p>
            </div>
            
            <a href="/mis-videos.php" class="btn btn-primary">
                Ir a Mis Videos
            </a>
        <?php else: ?>
            <div style="background: #fffbeb; border: 1px solid #fcd34d; border-radius: 8px; padding: 16px; margin: 24px 0;">
                <h3 style="color: #92400e; font-size: 18px; margin-bottom: 8px;">⏳ Procesando Acceso</h3>
                <p style="color: #92400e; font-size: 14px; margin: 0;">
                    Tu acceso se está activando. Si no aparece en unos minutos, contacta soporte.
                </p>
            </div>
            
            <a href="/mis-videos.php" class="btn btn-primary">
                Verificar Acceso
            </a>
        <?php endif; ?>
        
        <a href="/" class="btn btn-secondary">
            Volver al Inicio
        </a>
        
        <div style="margin-top: 32px; padding-top: 24px; border-top: 1px solid #e2e8f0;">
            <p style="font-size: 14px; color: #718096;">
                Si tienes alguna pregunta, contáctanos en:<br>
                <strong>organicosdeltropico@yahoo.com.mx</strong>
            </p>
        </div>
    </div>
</body>
</html>
