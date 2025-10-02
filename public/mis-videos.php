<?php
require_once __DIR__ . '/lib/bootstrap.php';

$config = require_once __DIR__ . '/../secure/config.php';

// Verificar autenticación
requireLogin();

$userId = getCurrentUserId();
$courseId = $config['app']['course_id'];

// Verificar acceso al curso
requireCourseAccess($userId, $courseId, '/');

// Obtener videos del curso
$db = getDB();
$videos = $db->fetchAll(
    'SELECT id, title, description, drive_file_id, ord 
     FROM videos 
     WHERE course_id = ? 
     ORDER BY ord ASC',
    [$courseId]
);

// Obtener información del usuario
$user = getCurrentUser();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mis Videos - Curso de Ganadería Regenerativa</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', sans-serif;
            background: #f8fafc;
            color: #2d3748;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }
        
        /* Header */
        .header {
            background: white;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            position: sticky;
            top: 0;
            z-index: 50;
        }
        
        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 16px 0;
        }
        
        .logo img {
            height: 48px;
            width: auto;
        }
        
        .user-info {
            display: flex;
            align-items: center;
            gap: 16px;
        }
        
        .welcome {
            text-align: right;
        }
        
        .welcome h3 {
            font-size: 16px;
            font-weight: 600;
            color: #2d3748;
            margin-bottom: 4px;
        }
        
        .welcome p {
            font-size: 14px;
            color: #718096;
        }
        
        .btn {
            display: inline-block;
            padding: 8px 16px;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s;
            border: none;
            cursor: pointer;
            font-size: 14px;
        }
        
        .btn-primary {
            background: #667eea;
            color: white;
        }
        
        .btn-primary:hover {
            background: #5a67d8;
        }
        
        .btn-outline {
            border: 1px solid #667eea;
            color: #667eea;
            background: transparent;
        }
        
        .btn-outline:hover {
            background: #667eea;
            color: white;
        }
        
        /* Main Content */
        .main-content {
            padding: 40px 0;
        }
        
        .page-header {
            text-align: center;
            margin-bottom: 40px;
        }
        
        .page-header h1 {
            font-size: 36px;
            font-weight: 700;
            color: #2d3748;
            margin-bottom: 16px;
        }
        
        .page-header p {
            font-size: 18px;
            color: #718096;
            max-width: 600px;
            margin: 0 auto;
        }
        
        /* Course Progress */
        .progress-section {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            padding: 24px;
            margin-bottom: 32px;
        }
        
        .progress-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 16px;
        }
        
        .progress-title {
            font-size: 18px;
            font-weight: 600;
            color: #2d3748;
        }
        
        .progress-stats {
            font-size: 14px;
            color: #718096;
        }
        
        .progress-bar {
            width: 100%;
            height: 8px;
            background: #e2e8f0;
            border-radius: 4px;
            overflow: hidden;
        }
        
        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #667eea, #764ba2);
            width: 0%; /* Se calculará dinámicamente */
            transition: width 0.3s ease;
        }
        
        /* Videos Grid */
        .videos-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 24px;
            margin-bottom: 40px;
        }
        
        .video-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            transition: transform 0.3s, box-shadow 0.3s;
        }
        
        .video-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
        }
        
        .video-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 16px;
            text-align: center;
        }
        
        .video-number {
            font-size: 14px;
            font-weight: 600;
            opacity: 0.9;
            margin-bottom: 4px;
        }
        
        .video-title {
            font-size: 18px;
            font-weight: 700;
        }
        
        .video-content {
            padding: 20px;
        }
        
        .video-description {
            color: #718096;
            font-size: 14px;
            line-height: 1.6;
            margin-bottom: 16px;
        }
        
        .video-player {
            width: 100%;
            aspect-ratio: 16/9;
            border: none;
            border-radius: 8px;
            background: #f7fafc;
        }
        
        .video-fallback {
            margin-top: 12px;
            text-align: center;
        }
        
        .video-fallback a {
            color: #667eea;
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
        }
        
        .video-fallback a:hover {
            text-decoration: underline;
        }
        
        /* Completion Badge */
        .completion-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: #f0fff4;
            color: #22543d;
            padding: 8px 12px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 500;
            margin-top: 12px;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .header-content {
                flex-direction: column;
                gap: 16px;
            }
            
            .user-info {
                flex-direction: column;
                text-align: center;
            }
            
            .welcome {
                text-align: center;
            }
            
            .videos-grid {
                grid-template-columns: 1fr;
            }
            
            .page-header h1 {
                font-size: 28px;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header class="header">
        <div class="container">
            <div class="header-content">
                <div class="logo">
                    <img src="/images/pijije-logo.jpg" alt="Pijije Regenerativo">
                </div>
                <div class="user-info">
                    <div class="welcome">
                        <h3>¡Bienvenido, <?= escape($user['name']) ?>!</h3>
                        <p>Curso de Ganadería Regenerativa</p>
                    </div>
                    <a href="/logout.php" class="btn btn-outline">Cerrar Sesión</a>
                </div>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="main-content">
        <div class="container">
            <!-- Page Header -->
            <div class="page-header">
                <h1>Mis Videos del Curso</h1>
                <p>
                    Accede a todas las lecciones del curso de Ganadería Regenerativa. 
                    Aprende a tu propio ritmo y domina las técnicas de manejo regenerativo.
                </p>
            </div>

            <!-- Progress Section -->
            <div class="progress-section">
                <div class="progress-header">
                    <div class="progress-title">Progreso del Curso</div>
                    <div class="progress-stats">0 de <?= count($videos) ?> lecciones completadas</div>
                </div>
                <div class="progress-bar">
                    <div class="progress-fill" style="width: 0%"></div>
                </div>
            </div>

            <!-- Videos Grid -->
            <div class="videos-grid">
                <?php foreach ($videos as $index => $video): ?>
                    <div class="video-card">
                        <div class="video-header">
                            <div class="video-number">Lección <?= $video['ord'] ?></div>
                            <div class="video-title"><?= escape($video['title']) ?></div>
                        </div>
                        <div class="video-content">
                            <?php if (!empty($video['description'])): ?>
                                <div class="video-description">
                                    <?= escape($video['description']) ?>
                                </div>
                            <?php endif; ?>
                            
                            <iframe
                                class="video-player"
                                src="https://drive.google.com/file/d/<?= escape($video['drive_file_id']) ?>/preview"
                                allow="autoplay; encrypted-media"
                                allowfullscreen
                                loading="lazy">
                            </iframe>
                            
                            <div class="video-fallback">
                                <p>Si no puedes ver el video, 
                                    <a href="https://drive.google.com/file/d/<?= escape($video['drive_file_id']) ?>/view" 
                                       target="_blank" rel="noopener">
                                        ábrelo directamente en Google Drive
                                    </a>
                                </p>
                            </div>
                            
                            <!-- Placeholder for completion tracking -->
                            <div class="completion-badge" style="display: none;">
                                <span>✅</span>
                                <span>Completado</span>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Course Information -->
            <div style="background: white; border-radius: 12px; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1); padding: 24px; text-align: center;">
                <h3 style="font-size: 20px; font-weight: 600; color: #2d3748; margin-bottom: 16px;">
                    📚 Recursos Adicionales
                </h3>
                <p style="color: #718096; margin-bottom: 16px;">
                    Únete a nuestro grupo de Facebook para resolver dudas y compartir experiencias con otros productores.
                </p>
                <a href="https://facebook.com/groups/manejo-holistico-tropical" 
                   target="_blank" rel="noopener" 
                   class="btn btn-primary">
                    Únete al Grupo de Facebook
                </a>
            </div>
        </div>
    </main>

    <script nonce="<?= getNonce() ?>">
        // Función para marcar videos como vistos (opcional)
        function markVideoAsWatched(videoId) {
            // Aquí se podría implementar tracking de progreso
            // Por ahora solo mostramos visualmente
            const badge = document.querySelector(`[data-video-id="${videoId}"] .completion-badge`);
            if (badge) {
                badge.style.display = 'flex';
            }
        }
        
        // Tracking básico de reproducción
        document.addEventListener('DOMContentLoaded', function() {
            const iframes = document.querySelectorAll('.video-player');
            iframes.forEach((iframe, index) => {
                iframe.addEventListener('load', function() {
                    console.log(`Video ${index + 1} loaded`);
                });
            });
        });
    </script>
</body>
</html>
