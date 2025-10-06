<?php
/**
 * Librerías para el panel de administración
 */

/**
 * Verificar si es administrador
 */
function isAdmin() {
    return isset($_SESSION['admin_id']) && !empty($_SESSION['admin_id']);
}

/**
 * Requerir acceso de administrador
 */
function requireAdmin($redirectTo = '/admin/login.php') {
    if (!isAdmin()) {
        redirect($redirectTo);
    }
}

/**
 * Obtener datos del administrador actual
 */
function getCurrentAdmin() {
    if (!isAdmin()) {
        return null;
    }
    
    $db = getDB();
    return $db->fetchOne(
        'SELECT id, username, email, role, active FROM admins WHERE id = ? AND active = 1',
        [$_SESSION['admin_id']]
    );
}

/**
 * Autenticar administrador
 */
function authenticateAdmin($username, $password) {
    $db = getDB();
    
    // Obtener administrador
    $admin = $db->fetchOne(
        'SELECT id, username, email, pass_hash, role, active FROM admins WHERE username = ?',
        [$username]
    );
    
    if (!$admin) {
        error_log('[ADMIN AUTH] Usuario no encontrado: ' . $username);
        return ['success' => false, 'error' => 'Credenciales inválidas'];
    }
    
    if (!$admin['active']) {
        error_log('[ADMIN AUTH] Cuenta inactiva: ' . $username);
        return ['success' => false, 'error' => 'Cuenta desactivada'];
    }
    
    // Verificar contraseña
    if (!verifyPassword($password, $admin['pass_hash'])) {
        error_log('[ADMIN AUTH] Password mismatch para usuario ' . $username);
        return ['success' => false, 'error' => 'Credenciales inválidas'];
    }
    
    // Actualizar último login
    $db->update('admins', 
        ['last_login' => now()],
        'id = ?',
        [$admin['id']]
    );
    
    // Iniciar sesión
    session_regenerate_id(true);
    $_SESSION['admin_id'] = $admin['id'];
    $_SESSION['admin_username'] = $admin['username'];
    $_SESSION['admin_role'] = $admin['role'];
    $_SESSION['admin_login_time'] = time();
    
    // Log de login
    logAdminAction('admin_login', 'admin', $admin['id'], [
        'username' => $admin['username']
    ]);
    error_log('[ADMIN AUTH] Login OK usuario ' . $username);
    
    return ['success' => true, 'admin' => $admin];
}

/**
 * Cerrar sesión de administrador
 */
function logoutAdmin() {
    if (isAdmin()) {
        logAdminAction('admin_logout', 'admin', $_SESSION['admin_id'], [
            'username' => $_SESSION['admin_username']
        ]);
    }
    
    unset($_SESSION['admin_id']);
    unset($_SESSION['admin_username']);
    unset($_SESSION['admin_role']);
    unset($_SESSION['admin_login_time']);
}

/**
 * Log de acciones de administrador
 */
function logAdminAction($action, $targetType = null, $targetId = null, $details = []) {
    try {
        $db = getDB();
        $ip = getRealIp();
        
        $db->insert('admin_logs', [
            'admin_id' => $_SESSION['admin_id'] ?? null,
            'action' => $action,
            'target_type' => $targetType,
            'target_id' => $targetId,
            'details' => json_encode($details),
            'ip_address' => $ip,
            'created_at' => now()
        ]);
    } catch (Exception $e) {
        error_log("Admin log failed: " . $e->getMessage());
    }
}

/**
 * Verificar permisos de administrador
 */
function hasAdminPermission($permission) {
    if (!isAdmin()) {
        return false;
    }
    
    $admin = getCurrentAdmin();
    if (!$admin) {
        return false;
    }
    
    // Super admin tiene todos los permisos
    if ($admin['role'] === 'super_admin') {
        return true;
    }
    
    // Definir permisos por rol
    $permissions = [
        'admin' => [
            'view_users',
            'edit_users',
            'view_orders',
            'edit_orders',
            'view_videos',
            'edit_videos',
            'view_logs',
            'view_settings'
        ],
        'super_admin' => [
            'all'
        ]
    ];
    
    return in_array($permission, $permissions[$admin['role']] ?? []);
}

/**
 * Requerir permiso específico
 */
function requireAdminPermission($permission) {
    if (!hasAdminPermission($permission)) {
        http_response_code(403);
        die('Acceso denegado. No tienes permisos para realizar esta acción.');
    }
}

/**
 * Obtener estadísticas del dashboard
 */
function getAdminStats() {
    $db = getDB();
    
    $stats = [];
    
    // Total de usuarios
    $stats['total_users'] = $db->fetchOne('SELECT COUNT(*) as count FROM users')['count'];
    
    // Usuarios con acceso
    $stats['users_with_access'] = $db->fetchOne(
        'SELECT COUNT(DISTINCT user_id) as count FROM user_access'
    )['count'];
    
    // Total de órdenes
    $stats['total_orders'] = $db->fetchOne('SELECT COUNT(*) as count FROM orders')['count'];
    
    // Órdenes pagadas
    $stats['paid_orders'] = $db->fetchOne(
        'SELECT COUNT(*) as count FROM orders WHERE status = "paid"'
    )['count'];
    
    // Ingresos totales
    $stats['total_revenue'] = $db->fetchOne(
        'SELECT SUM(amount_mxn) as total FROM orders WHERE status = "paid"'
    )['total'] ?? 0;
    
    // Total de videos
    $stats['total_videos'] = $db->fetchOne('SELECT COUNT(*) as count FROM videos')['count'];
    
    // Órdenes pendientes
    $stats['pending_orders'] = $db->fetchOne(
        'SELECT COUNT(*) as count FROM orders WHERE status = "pending"'
    )['count'];
    
    return $stats;
}

/**
 * Obtener configuración del sistema
 */
function getSystemConfig($key = null) {
    $db = getDB();
    
    if ($key) {
        $result = $db->fetchOne(
            'SELECT config_value FROM system_config WHERE config_key = ?',
            [$key]
        );
        return $result ? $result['config_value'] : null;
    }
    
    $configs = $db->fetchAll('SELECT config_key, config_value FROM system_config');
    $result = [];
    
    foreach ($configs as $config) {
        $result[$config['config_key']] = $config['config_value'];
    }
    
    return $result;
}

/**
 * Actualizar configuración del sistema
 */
function updateSystemConfig($key, $value, $description = null) {
    $db = getDB();
    
    try {
        $db->query(
            'INSERT INTO system_config (config_key, config_value, description, updated_by) 
             VALUES (?, ?, ?, ?) 
             ON DUPLICATE KEY UPDATE 
             config_value = VALUES(config_value),
             description = COALESCE(VALUES(description), description),
             updated_by = VALUES(updated_by)',
            [$key, $value, $description, $_SESSION['admin_id'] ?? null]
        );
        
        logAdminAction('config_update', 'config', null, [
            'key' => $key,
            'value' => $value
        ]);
        
        return true;
    } catch (Exception $e) {
        error_log("Config update failed: " . $e->getMessage());
        return false;
    }
}

/**
 * Obtener logs de administración
 */
function getAdminLogs($limit = 50, $offset = 0) {
    $db = getDB();
    
    return $db->fetchAll(
        'SELECT al.*, a.username as admin_username 
         FROM admin_logs al 
         LEFT JOIN admins a ON al.admin_id = a.id 
         ORDER BY al.created_at DESC 
         LIMIT ? OFFSET ?',
        [$limit, $offset]
    );
}

/**
 * Formatear fecha para admin
 */
function formatAdminDate($date) {
    if (!$date) return '-';
    return date('d/m/Y H:i:s', strtotime($date));
}

/**
 * Formatear precio para admin
 */
function formatAdminPrice($cents) {
    return '$' . number_format($cents / 100, 2) . ' MXN';
}

/**
 * Revocar acceso de usuario
 */
// NOTE: revokeAccess() centralizado en access.php para evitar duplicaciones y
// permitir logging consistente (access_revoked). Si necesitas revocar acceso
// incluye 'access.php' y usa la implementación allí.

// Funciones getRealIp() e isPost() movidas a utils.php

// getFlash() y setFlash() centralizados en utils.php

// Eliminado: now() ya definida en utils.php
