<?php
require_once __DIR__ . '/lib/bootstrap.php';

$config = require_once __DIR__ . '/../secure/config.php';
$isLoggedIn = isLoggedIn();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pago Cancelado - Cursos Orgánicos</title>
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
        
        .cancel-icon {
            font-size: 64px;
            color: #e53e3e;
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
        
        .info-box {
            background: #fef5e7;
            border: 1px solid #f6ad55;
            border-radius: 8px;
            padding: 16px;
            margin: 24px 0;
        }
        
        .info-box h3 {
            color: #c05621;
            font-size: 18px;
            margin-bottom: 8px;
        }
        
        .info-box p {
            color: #c05621;
            font-size: 14px;
            margin: 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="cancel-icon">❌</div>
        
        <h1>Pago Cancelado</h1>
        
        <p>
            El proceso de pago ha sido cancelado. No se ha realizado ningún cargo a tu cuenta.
        </p>
        
        <div class="info-box">
            <h3>💡 ¿Necesitas Ayuda?</h3>
            <p>
                Si tuviste problemas con el pago o necesitas asistencia, no dudes en contactarnos.
            </p>
        </div>
        
        <a href="/" class="btn btn-primary">
            Intentar Nuevamente
        </a>
        
        <?php if ($isLoggedIn): ?>
            <a href="/mis-videos.php" class="btn btn-secondary">
                Mis Videos
            </a>
        <?php else: ?>
            <a href="/login.php" class="btn btn-secondary">
                Iniciar Sesión
            </a>
        <?php endif; ?>
        
        <div style="margin-top: 32px; padding-top: 24px; border-top: 1px solid #e2e8f0;">
            <p style="font-size: 14px; color: #718096;">
                Para soporte técnico o preguntas sobre el pago:<br>
                <strong>organicosdeltropico@yahoo.com.mx</strong><br>
                <strong>+52 93 4115 0595</strong>
            </p>
        </div>
    </div>
</body>
</html>
