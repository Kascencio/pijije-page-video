<?php
require_once __DIR__ . '/../../lib/bootstrap.php';
require_once __DIR__ . '/../../lib/admin.php';

// Verificar acceso de administrador
requireAdmin();
requireAdminPermission('view_settings');

$admin = getCurrentAdmin();

// Procesar formulario de configuración
if (isPost()) {
    validateCsrfRequest();
    
    $configs = $_POST['config'] ?? [];
    
    foreach ($configs as $key => $value) {
        updateSystemConfig($key, $value);
    }
    
    setFlash('success', 'Configuración actualizada exitosamente');
    redirect('/admin/settings/index.php');
}

// Obtener configuración actual
$config = getSystemConfig();

// Obtener flash message
$flash = getFlash();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configuración del Sistema - Panel de Administración</title>
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
        }
        
        .page-header h1 {
            font-size: 28px;
            font-weight: 700;
            color: #2d3748;
            margin-bottom: 8px;
        }
        
        .page-header p {
            color: #718096;
            font-size: 16px;
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
        
        /* Settings Sections */
        .settings-container {
            display: grid;
            gap: 30px;
        }
        
        .settings-section {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        .section-header {
            background: #f7fafc;
            padding: 20px;
            border-bottom: 1px solid #e2e8f0;
        }
        
        .section-title {
            font-size: 18px;
            font-weight: 600;
            color: #2d3748;
            margin-bottom: 4px;
        }
        
        .section-description {
            color: #718096;
            font-size: 14px;
        }
        
        .section-content {
            padding: 30px;
        }
        
        /* Form Styles */
        .form-group {
            margin-bottom: 24px;
        }
        
        .form-label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #4a5568;
            font-size: 14px;
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
        
        .form-help {
            font-size: 12px;
            color: #718096;
            margin-top: 4px;
        }
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        
        /* Submit Button */
        .form-actions {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #e2e8f0;
        }
        
        .btn {
            padding: 12px 24px;
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
        
        /* Danger Zone */
        .danger-zone {
            border: 2px solid #fed7d7;
            background: #fef5f5;
        }
        
        .danger-zone .section-header {
            background: #fed7d7;
            color: #742a2a;
        }
        
        .danger-zone .section-title {
            color: #742a2a;
        }
        
        .btn-danger {
            background: #e53e3e;
            color: white;
        }
        
        .btn-danger:hover {
            background: #c53030;
        }
        
        /* Status Indicators */
        .status-indicator {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 500;
        }
        
        .status-active {
            background: #f0fff4;
            color: #22543d;
        }
        
        .status-inactive {
            background: #fed7d7;
            color: #742a2a;
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
            
            .form-row {
                grid-template-columns: 1fr;
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
                <a href="/admin/dashboard/index.php" class="nav-item">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2H5a2 2 0 00-2-2z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5a2 2 0 012-2h4a2 2 0 012 2v2H8V5z"/>
                    </svg>
                    Dashboard
                </a>
                
                <a href="/admin/users/index.php" class="nav-item">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"/>
                    </svg>
                    Usuarios
                </a>
                
                <a href="/admin/videos/index.php" class="nav-item">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                    </svg>
                    Videos
                </a>
                
                <a href="/admin/payments/index.php" class="nav-item">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
                    </svg>
                    Pagos
                </a>
                
                <a href="/admin/settings/index.php" class="nav-item active">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                    Configuración
                </a>
            </nav>
            
            <div class="logout-section">
                <form method="POST" action="/admin/logout.php" style="margin: 0;">
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
                <h1>Configuración del Sistema</h1>
                <p>Administrar configuración general del curso y sistema de pagos</p>
            </div>
            
            <?php if ($flash): ?>
                <div class="flash-message flash-<?= $flash['type'] ?>">
                    <?= escape($flash['message']) ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <?= csrfInput() ?>
                
                <div class="settings-container">
                    <!-- Course Settings -->
                    <div class="settings-section">
                        <div class="section-header">
                            <h3 class="section-title">Configuración del Curso</h3>
                            <p class="section-description">Información básica del curso y precios</p>
                        </div>
                        <div class="section-content">
                            <div class="form-group">
                                <label class="form-label">Título del Curso</label>
                                <input type="text" name="config[course_title]" class="form-input" 
                                       value="<?= escape($config['course_title'] ?? '') ?>" required>
                                <div class="form-help">Título que aparece en la página principal</div>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">Descripción del Curso</label>
                                <textarea name="config[course_description]" class="form-textarea"><?= escape($config['course_description'] ?? '') ?></textarea>
                                <div class="form-help">Descripción breve del curso</div>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label class="form-label">Precio del Curso (centavos)</label>
                                    <input type="number" name="config[course_price]" class="form-input" 
                                           value="<?= escape($config['course_price'] ?? '') ?>" required>
                                    <div class="form-help">Ejemplo: 150000 = $1,500.00 MXN</div>
                                </div>
                                
                                <div class="form-group">
                                    <label class="form-label">Duración del Acceso (meses)</label>
                                    <input type="number" name="config[course_duration]" class="form-input" 
                                           value="<?= escape($config['course_duration'] ?? '') ?>" min="1">
                                    <div class="form-help">Tiempo que el usuario tiene acceso al curso</div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- PayPal Settings -->
                    <div class="settings-section">
                        <div class="section-header">
                            <h3 class="section-title">Configuración de PayPal</h3>
                            <p class="section-description">Credenciales y configuración de pagos</p>
                        </div>
                        <div class="section-content">
                            <div class="form-group">
                                <label class="form-label">Client ID de PayPal</label>
                                <input type="text" name="config[paypal_client_id]" class="form-input" 
                                       value="<?= escape($config['paypal_client_id'] ?? '') ?>">
                                <div class="form-help">ID público de tu aplicación PayPal</div>
                            </div>
                            
                            <!-- Hosted Button ID removido - ahora usamos Smart Buttons -->
                        </div>
                    </div>
                    
                    <!-- Contact Settings -->
                    <div class="settings-section">
                        <div class="section-header">
                            <h3 class="section-title">Información de Contacto</h3>
                            <p class="section-description">Datos de contacto que aparecen en el sitio</p>
                        </div>
                        <div class="section-content">
                            <div class="form-row">
                                <div class="form-group">
                                    <label class="form-label">Email de Contacto</label>
                                    <input type="email" name="config[contact_email]" class="form-input" 
                                           value="<?= escape($config['contact_email'] ?? '') ?>">
                                    <div class="form-help">Email para contacto y soporte</div>
                                </div>
                                
                                <div class="form-group">
                                    <label class="form-label">Teléfono de Contacto</label>
                                    <input type="tel" name="config[contact_phone]" class="form-input" 
                                           value="<?= escape($config['contact_phone'] ?? '') ?>">
                                    <div class="form-help">Número de teléfono para contacto</div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- System Info -->
                    <div class="settings-section">
                        <div class="section-header">
                            <h3 class="section-title">Información del Sistema</h3>
                            <p class="section-description">Estado actual del sistema</p>
                        </div>
                        <div class="section-content">
                            <div class="form-row">
                                <div class="form-group">
                                    <label class="form-label">Estado del Sistema</label>
                                    <div>
                                        <span class="status-indicator status-active">
                                            <svg width="12" height="12" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/>
                                            </svg>
                                            Activo
                                        </span>
                                    </div>
                                </div>
                                
                                <div class="form-group">
                                    <label class="form-label">Versión</label>
                                    <div style="font-family: monospace; color: #718096;">v1.0.0</div>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">Última Actualización</label>
                                <div style="color: #718096;"><?= formatAdminDate($config['updated_at'] ?? date('Y-m-d H:i:s')) ?></div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Danger Zone -->
                    <div class="settings-section danger-zone">
                        <div class="section-header">
                            <h3 class="section-title">Zona de Peligro</h3>
                            <p class="section-description">Acciones que pueden afectar el funcionamiento del sistema</p>
                        </div>
                        <div class="section-content">
                            <div class="form-group">
                                <label class="form-label">Resetear Configuración</label>
                                <div>
                                    <button type="button" class="btn btn-danger" 
                                            onclick="if(confirm('¿Estás seguro de resetear toda la configuración a valores por defecto?')) { /* Implementar reset */ }">
                                        <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                                        </svg>
                                        Resetear Configuración
                                    </button>
                                </div>
                                <div class="form-help">Restaura todos los valores de configuración a los valores por defecto</div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Submit Button -->
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">
                        <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        Guardar Configuración
                    </button>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
