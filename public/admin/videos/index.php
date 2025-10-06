<?php
require_once __DIR__ . '/../../lib/bootstrap.php';
require_once __DIR__ . '/../../lib/admin.php';

// Verificar acceso de administrador
requireAdmin();
requireAdminPermission('view_videos');
$cid = courseId();

$admin = getCurrentAdmin();

// Procesar acciones
if (isPost()) {
    validateCsrfRequest();
    
    $action = $_POST['action'] ?? '';
    $videoId = (int)($_POST['video_id'] ?? 0);
    
    switch ($action) {
        case 'add_video':
            if (hasAdminPermission('edit_videos')) {
                $title = trim($_POST['title'] ?? '');
                $description = trim($_POST['description'] ?? '');
                $driveFileId = trim($_POST['drive_file_id'] ?? '');
                $order = (int)($_POST['order'] ?? 0);
                
                if ($title && $driveFileId) {
                    $db = getDB();
                    if ($order <= 0) {
                        // Obtener siguiente orden
                        $next = $db->fetchOne('SELECT COALESCE(MAX(ord),0)+1 AS next_ord FROM videos WHERE course_id = ?', [$cid]);
                        $order = (int)$next['next_ord'];
                    } else {
                        // Desplazar otros si hay conflicto (ord existente)
                        $db->query('UPDATE videos SET ord = ord + 1 WHERE course_id = ? AND ord >= ?', [$cid, $order]);
                    }
                    $db->insert('videos', [
                        'course_id' => $cid,
                        'title' => $title,
                        'description' => $description,
                        'drive_file_id' => $driveFileId,
                        'ord' => $order,
                        'created_at' => date('Y-m-d H:i:s')
                    ]);
                    $vidId = $db->fetchOne('SELECT LAST_INSERT_ID() as id')['id'] ?? null;
                    logAdminAction('add_video', 'video', $vidId, [ 'title' => $title, 'drive_file_id' => $driveFileId, 'ord' => $order ]);
                    setFlash('success', 'Video agregado');
                } else {
                    setFlash('error', 'Título e ID de Drive son obligatorios (y orden >=1 si lo indicas)');
                }
            } else {
                setFlash('error','No tienes permiso para agregar videos');
            }
            break;
            
        case 'update_video':
            if (hasAdminPermission('edit_videos')) {
                $title = trim($_POST['title'] ?? '');
                $description = trim($_POST['description'] ?? '');
                $driveFileId = trim($_POST['drive_file_id'] ?? '');
                $order = (int)($_POST['order'] ?? 0);
                if ($title && $driveFileId && $videoId > 0) {
                    $db = getDB();
                    $current = $db->fetchOne('SELECT ord FROM videos WHERE id = ? AND course_id = ?', [$videoId, $cid]);
                    if (!$current) { setFlash('error','Video no encontrado'); break; }
                    $oldOrd = (int)$current['ord'];
                    if ($order <= 0) { $order = $oldOrd; }
                    if ($order !== $oldOrd) {
                        if ($order < $oldOrd) {
                            // Mover hacia arriba: incrementar ord de los que están entre nuevo y viejo-1
                            $db->query('UPDATE videos SET ord = ord + 1 WHERE course_id = ? AND ord >= ? AND ord < ?', [$cid, $order, $oldOrd]);
                        } else {
                            // Mover hacia abajo: decrementar ord de los que están entre viejo+1 y nuevo
                            $db->query('UPDATE videos SET ord = ord - 1 WHERE course_id = ? AND ord <= ? AND ord > ?', [$cid, $order, $oldOrd]);
                        }
                    }
                    $db->update('videos', [
                        'title' => $title,
                        'description' => $description,
                        'drive_file_id' => $driveFileId,
                        'ord' => $order
                    ], 'id = ? AND course_id = ?', [$videoId, $cid]);
                    logAdminAction('update_video', 'video', $videoId, [ 'title' => $title, 'drive_file_id' => $driveFileId, 'ord' => $order ]);
                    setFlash('success', 'Video actualizado');
                } else {
                    setFlash('error', 'Campos requeridos faltantes');
                }
            } else {
                setFlash('error','No tienes permiso para editar videos');
            }
            break;
            
        case 'delete_video':
            if (hasAdminPermission('edit_videos')) {
                $db = getDB();
                $video = $db->fetchOne('SELECT id, title, drive_file_id, ord FROM videos WHERE id = ? AND course_id = ?', [$videoId, $cid]);
                if ($video) {
                    $db->delete('videos', 'id = ? AND course_id = ?', [$videoId, $cid]);
                    // Recompactar órdenes
                    $db->query('UPDATE videos SET ord = ord - 1 WHERE course_id = ? AND ord > ?', [$cid, $video['ord']]);
                    logAdminAction('delete_video', 'video', $videoId, [ 'title' => $video['title'], 'drive_file_id' => $video['drive_file_id'] ]);
                    setFlash('success', 'Video eliminado');
                } else {
                    setFlash('error', 'Video no encontrado');
                }
            }
            break;
            
        case 'reorder_videos':
            if (hasAdminPermission('edit_videos')) {
                $newOrder = $_POST['new_order'] ?? [];
                if (is_array($newOrder)) {
                    $db = getDB();
                    foreach ($newOrder as $position => $videoId) {
                        $db->update('videos', ['ord' => $position + 1], 'id = ? AND course_id = ?', [$videoId, $cid]);
                    }
                    
                    logAdminAction('reorder_videos', 'video', null, [
                        'new_order' => $newOrder
                    ]);
                    
                    setFlash('success', 'Orden de videos actualizado');
                }
            }
            break;
    }
    
    redirect('admin/videos/index.php');
}

// Obtener videos
$db = getDB();
$videos = $db->fetchAll('SELECT * FROM videos WHERE course_id = ? ORDER BY ord ASC', [$cid]);

// Obtener flash message
$flash = getFlash();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Videos - Panel de Administración</title>
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
        
        .admin-layout {
            display: flex;
            min-height: 100vh;
        }
        
        /* Sidebar */
        .sidebar {
            width: 250px;
            background: white;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            position: fixed;
            height: 100vh;
            overflow-y: auto;
            z-index: 1000;
        }
        
        .sidebar-header {
            padding: 20px;
            border-bottom: 1px solid #e2e8f0;
        }
        
        .sidebar-header h2 {
            color: #2d3748;
            font-size: 18px;
            font-weight: 700;
        }
        
        .sidebar-header p {
            color: #718096;
            font-size: 14px;
            margin-top: 4px;
        }
        
        .sidebar-nav {
            padding: 20px 0;
        }
        
        .nav-item {
            display: block;
            padding: 12px 20px;
            color: #4a5568;
            text-decoration: none;
            transition: all 0.3s;
            border-left: 3px solid transparent;
        }
        
        .nav-item:hover {
            background: #f7fafc;
            color: #2d3748;
        }
        
        .nav-item.active {
            background: #ebf8ff;
            color: #3182ce;
            border-left-color: #3182ce;
        }
        
        .nav-item svg {
            width: 20px;
            height: 20px;
            margin-right: 12px;
            vertical-align: middle;
        }
        
        .logout-section {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            padding: 20px;
            border-top: 1px solid #e2e8f0;
        }
        
        .logout-btn {
            width: 100%;
            padding: 10px;
            background: #fed7d7;
            color: #c53030;
            border: none;
            border-radius: 6px;
            font-size: 14px;
            cursor: pointer;
            transition: background 0.3s;
        }
        
        .logout-btn:hover {
            background: #feb2b2;
        }
        
        /* Main Content */
        .main-content {
            flex: 1;
            margin-left: 250px;
            padding: 30px;
        }
        
        .page-header {
            margin-bottom: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .page-header h1 {
            font-size: 28px;
            font-weight: 700;
            color: #2d3748;
        }
        
        .page-header p {
            color: #718096;
            font-size: 16px;
            margin-top: 8px;
        }
        
        .header-actions {
            display: flex;
            gap: 12px;
        }
        
        .btn {
            padding: 10px 16px;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        .btn-primary {
            background: #3b82f6;
            color: white;
        }
        
        .btn-primary:hover {
            background: #2563eb;
        }
        
        .btn-secondary {
            background: #6b7280;
            color: white;
        }
        
        .btn-secondary:hover {
            background: #4b5563;
        }
        
        /* Flash Messages */
        .flash-message {
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-weight: 500;
        }
        
        .flash-success {
            background: #f0fff4;
            color: #22543d;
            border: 1px solid #9ae6b4;
        }
        
        .flash-error {
            background: #fed7d7;
            color: #742a2a;
            border: 1px solid #fc8181;
        }
        
        /* Videos List */
        .videos-container {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        .video-item {
            padding: 20px;
            border-bottom: 1px solid #f7fafc;
            display: flex;
            align-items: center;
            gap: 20px;
        }
        
        .video-item:last-child {
            border-bottom: none;
        }
        
        .video-preview {
            width: 120px;
            height: 68px;
            background: #f7fafc;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            overflow: hidden;
        }
        
        .video-preview iframe {
            width: 100%;
            height: 100%;
            border: none;
        }
        
        .video-info {
            flex: 1;
        }
        
        .video-title {
            font-size: 16px;
            font-weight: 600;
            color: #2d3748;
            margin-bottom: 4px;
        }
        
        .video-description {
            color: #718096;
            font-size: 14px;
            margin-bottom: 8px;
        }
        
        .video-meta {
            display: flex;
            gap: 20px;
            font-size: 12px;
            color: #a0aec0;
        }
        
        .video-actions {
            display: flex;
            gap: 8px;
        }
        
        .action-btn {
            padding: 6px 12px;
            border: none;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .btn-edit {
            background: #ebf8ff;
            color: #2c5282;
            border: 1px solid #90cdf4;
        }
        
        .btn-edit:hover {
            background: #bee3f8;
        }
        
        .btn-delete {
            background: #fed7d7;
            color: #742a2a;
            border: 1px solid #fc8181;
        }
        
        .btn-delete:hover {
            background: #fbb6ce;
        }
        
        /* Modal */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.5);
            z-index: 2000;
            align-items: center;
            justify-content: center;
        }
        
        .modal.show {
            display: flex;
        }
        
        .modal-content {
            background: white;
            border-radius: 12px;
            padding: 30px;
            width: 90%;
            max-width: 500px;
            max-height: 90vh;
            overflow-y: auto;
        }
        
        .modal-header {
            margin-bottom: 20px;
        }
        
        .modal-title {
            font-size: 20px;
            font-weight: 600;
            color: #2d3748;
        }
        
        .modal-close {
            float: right;
            background: none;
            border: none;
            font-size: 24px;
            cursor: pointer;
            color: #a0aec0;
        }
        
        .modal-close:hover {
            color: #4a5568;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #4a5568;
        }
        
        .form-input,
        .form-textarea {
            width: 100%;
            padding: 12px;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            font-size: 14px;
            transition: border-color 0.3s;
        }
        
        .form-input:focus,
        .form-textarea:focus {
            outline: none;
            border-color: #3b82f6;
        }
        
        .form-textarea {
            resize: vertical;
            min-height: 80px;
        }
        
        .modal-actions {
            display: flex;
            gap: 12px;
            justify-content: flex-end;
        }
        
        .btn-cancel {
            background: #f7fafc;
            color: #4a5568;
            border: 1px solid #e2e8f0;
        }
        
        .btn-cancel:hover {
            background: #edf2f7;
        }
        
        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #718096;
        }
        
        .empty-state svg {
            width: 64px;
            height: 64px;
            margin-bottom: 16px;
            color: #cbd5e0;
        }
        
        .empty-state h3 {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 8px;
            color: #4a5568;
        }
        
        .empty-state p {
            margin-bottom: 20px;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
                transition: transform 0.3s;
            }
            
            .main-content {
                margin-left: 0;
            }
            
            .page-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 16px;
            }
            
            .video-item {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .video-preview {
                width: 100%;
                height: 120px;
            }
        }
    </style>
</head>
<body>
    <div class="admin-layout">
        <!-- Sidebar -->
        <div class="sidebar">
            <div class="sidebar-header">
                <h2>Panel de Admin</h2>
                <p>Bienvenido, <?= escape($admin['username']) ?></p>
            </div>
            
            <nav class="sidebar-nav">
                <a href="<?= adminUrl('dashboard/index.php') ?>" class="nav-item">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2H5a2 2 0 00-2-2z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5a2 2 0 012-2h4a2 2 0 012 2v2H8V5z"/>
                    </svg>
                    Dashboard
                </a>
                
                <a href="<?= adminUrl('users/index.php') ?>" class="nav-item">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"/>
                    </svg>
                    Usuarios
                </a>
                
                <a href="<?= adminUrl('videos/index.php') ?>" class="nav-item active">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                    </svg>
                    Videos
                </a>
                
                <a href="<?= adminUrl('payments/index.php') ?>" class="nav-item">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
                    </svg>
                    Pagos
                </a>
                
                <a href="<?= adminUrl('settings/index.php') ?>" class="nav-item">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                    Configuración
                </a>
            </nav>
            
            <div class="logout-section">
                <form method="POST" action="<?= adminUrl('logout.php') ?>" style="margin: 0;">
                    <?= csrfInput() ?>
                    <button type="submit" class="logout-btn">
                        <svg style="width: 16px; height: 16px; margin-right: 8px; vertical-align: middle;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                        </svg>
                        Cerrar Sesión
                    </button>
                </form>
            </div>
        </div>
        
        <!-- Main Content -->
        <div class="main-content">
            <div class="page-header">
                <div>
                    <h1>Gestión de Videos</h1>
                    <p>Administrar videos del curso de ganadería regenerativa</p>
                </div>
                <div class="header-actions">
                    <?php if (hasAdminPermission('edit_videos')): ?>
                        <button class="btn btn-primary" type="button" data-add-video>
                            <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                            </svg>
                            Agregar Video
                        </button>
                    <?php endif; ?>
                </div>
            </div>
            
            <?php if ($flash): ?>
                <div class="flash-message flash-<?= $flash['type'] ?>">
                    <?= escape($flash['message']) ?>
                </div>
            <?php endif; ?>
            
            <!-- Videos List -->
            <div class="videos-container">
                <?php if (empty($videos)): ?>
                    <div class="empty-state">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                        </svg>
                        <h3>No hay videos</h3>
                        <p>Agrega el primer video del curso para comenzar.</p>
                        <?php if (hasAdminPermission('edit_videos')): ?>
                            <button class="btn btn-primary" type="button" data-add-video>
                                <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                </svg>
                                Agregar Video
                            </button>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                <?php foreach ($videos as $video): ?>
                <div class="video-item" 
                    data-video-id="<?= $video['id'] ?>" 
                    data-video-title="<?= htmlspecialchars($video['title'], ENT_QUOTES | ENT_HTML5, 'UTF-8') ?>" 
                    data-video-description="<?= htmlspecialchars($video['description'] ?? '', ENT_QUOTES | ENT_HTML5, 'UTF-8') ?>" 
                    data-video-drive="<?= htmlspecialchars($video['drive_file_id'], ENT_QUOTES | ENT_HTML5, 'UTF-8') ?>" 
                    data-video-ord="<?= (int)$video['ord'] ?>">
                        <div class="video-preview">
                            <iframe src="https://drive.google.com/file/d/<?= escape($video['drive_file_id']) ?>/preview" 
                                    allow="autoplay; encrypted-media" allowfullscreen>
                            </iframe>
                        </div>
                        
                        <div class="video-info">
                            <div class="video-title"><?= escape($video['title']) ?></div>
                            <div class="video-description"><?= escape($video['description']) ?></div>
                            <div class="video-meta">
                                <span>Orden: <?= $video['ord'] ?></span>
                                <span>ID: <?= escape($video['drive_file_id']) ?></span>
                                <span>Creado: <?= formatAdminDate($video['created_at']) ?></span>
                            </div>
                        </div>
                        
                        <div class="video-actions">
                            <?php if (hasAdminPermission('edit_videos')): ?>
                                <button class="action-btn btn-edit" type="button" data-edit-video>
                                    Editar
                                </button>
                                <form method="POST" style="display: inline;" class="form-delete-video">
                                    <?= csrfInput() ?>
                                    <input type="hidden" name="action" value="delete_video">
                                    <input type="hidden" name="video_id" value="<?= $video['id'] ?>">
                                    <button type="submit" class="action-btn btn-delete">Eliminar</button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Add Video Modal -->
    <div id="addModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">Agregar Video</h3>
                <button class="modal-close" type="button" data-close-modal="addModal" aria-label="Cerrar">&times;</button>
            </div>
            
            <form method="POST">
                <?= csrfInput() ?>
                <input type="hidden" name="action" value="add_video">
                
                <div class="form-group">
                    <label class="form-label">Título del Video</label>
                    <input type="text" name="title" class="form-input" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Descripción</label>
                    <textarea name="description" class="form-textarea"></textarea>
                </div>
                
                <div class="form-group">
                    <label class="form-label">ID de Google Drive</label>
                    <input type="text" name="drive_file_id" class="form-input" required 
                           placeholder="Ej: 1ABC123def456GHI789jkl">
                </div>
                
                <div class="form-group">
                    <label class="form-label">Orden</label>
                    <input type="number" name="order" class="form-input" required min="1">
                </div>
                
                <div class="modal-actions">
                    <button type="button" class="btn btn-cancel" data-close-modal="addModal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Agregar Video</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Edit Video Modal -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">Editar Video</h3>
                <button class="modal-close" type="button" data-close-modal="editModal" aria-label="Cerrar">&times;</button>
            </div>
            
            <form method="POST">
                <?= csrfInput() ?>
                <input type="hidden" name="action" value="update_video">
                <input type="hidden" name="video_id" id="edit_video_id">
                
                <div class="form-group">
                    <label class="form-label">Título del Video</label>
                    <input type="text" name="title" id="edit_title" class="form-input" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Descripción</label>
                    <textarea name="description" id="edit_description" class="form-textarea"></textarea>
                </div>
                
                <div class="form-group">
                    <label class="form-label">ID de Google Drive</label>
                    <input type="text" name="drive_file_id" id="edit_drive_file_id" class="form-input" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Orden</label>
                    <input type="number" name="order" id="edit_order" class="form-input" required min="1">
                </div>
                
                <div class="modal-actions">
                    <button type="button" class="btn btn-cancel" data-close-modal="editModal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Actualizar Video</button>
                </div>
            </form>
        </div>
    </div>
    
    <script nonce="<?= getNonce() ?>">
    (function(){
        const addBtnSelector = '[data-add-video]';
        const editBtnSelector = '[data-edit-video]';
        const deleteFormSelector = '.form-delete-video';
        const addModal = document.getElementById('addModal');
        const editModal = document.getElementById('editModal');

        function openAddModal(){
            addModal.classList.add('show');
            // Reset and focus first input for consistency
            const form = addModal.querySelector('form');
            if(form){ form.reset(); const first = form.querySelector('input,textarea,select'); if(first) first.focus(); }
        }
        function openEditFromElement(el){
            const item = el.closest('.video-item');
            if(!item) return;
            document.getElementById('edit_video_id').value = item.dataset.videoId;
            document.getElementById('edit_title').value = item.dataset.videoTitle;
            document.getElementById('edit_description').value = item.dataset.videoDescription || '';
            document.getElementById('edit_drive_file_id').value = item.dataset.videoDrive;
            document.getElementById('edit_order').value = item.dataset.videoOrd;
            editModal.classList.add('show');
            const first = editModal.querySelector('#edit_title'); if(first) first.focus();
        }
        function closeModal(id){
            const m=document.getElementById(id); if(m) m.classList.remove('show');
        }

        // Bind add buttons
        document.querySelectorAll(addBtnSelector).forEach(b=> b.addEventListener('click', openAddModal));
        // Bind edit buttons
        document.querySelectorAll(editBtnSelector).forEach(b=> b.addEventListener('click', ()=>openEditFromElement(b)));
        // Bind delete forms confirm
        document.querySelectorAll(deleteFormSelector).forEach(f=> f.addEventListener('submit', e=>{ if(!confirm('¿Estás seguro de eliminar este video?')) e.preventDefault(); }));
        // Outside click to close
        document.querySelectorAll('.modal').forEach(modal => {
            modal.addEventListener('click', function(e){ if(e.target === this){ this.classList.remove('show'); } });
        });
        // Close buttons (cancel / X)
        document.querySelectorAll('[data-close-modal]').forEach(btn => {
            btn.addEventListener('click', () => closeModal(btn.getAttribute('data-close-modal')));
        });

        // Escape key closes any open modal
        document.addEventListener('keydown', e => {
            if(e.key === 'Escape') {
                [addModal, editModal].forEach(m => m.classList.remove('show'));
            }
        });

        // (Optional) expose for future external calls without inline handlers
        window.closeModal = closeModal;
    })();
    </script>
</body>
</html>
