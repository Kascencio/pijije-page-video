<?php
/**
 * Gestión de acceso a cursos
 * Funciones para verificar y otorgar acceso a contenido premium
 */

/**
 * Verificar si el usuario tiene acceso al curso
 */
function hasAccess($userId, $courseId) {
    $db = getDB();
    $access = $db->fetchOne(
        'SELECT id FROM user_access WHERE user_id = ? AND course_id = ?',
        [$userId, $courseId]
    );
    
    return (bool) $access;
}

/**
 * Otorgar acceso al curso
 */
function grantAccess($userId, $courseId) {
    $db = getDB();
    
    try {
        // Usar INSERT ... ON DUPLICATE KEY UPDATE para evitar duplicados
        $db->query(
            'INSERT INTO user_access (user_id, course_id, granted_at) VALUES (?, ?, ?)
             ON DUPLICATE KEY UPDATE granted_at = VALUES(granted_at)',
            [$userId, $courseId, now()]
        );
        
        logSecurity('access_granted', [
            'user_id' => $userId,
            'course_id' => $courseId
        ]);
        
        return true;
    } catch (Exception $e) {
        error_log("Grant access failed: " . $e->getMessage());
        return false;
    }
}

/**
 * Revocar acceso al curso (para casos especiales)
 */
function revokeAccess($userId, $courseId) {
    $db = getDB();
    
    try {
        $db->query(
            'DELETE FROM user_access WHERE user_id = ? AND course_id = ?',
            [$userId, $courseId]
        );
        
        logSecurity('access_revoked', [
            'user_id' => $userId,
            'course_id' => $courseId
        ]);
        
        return true;
    } catch (Exception $e) {
        error_log("Revoke access failed: " . $e->getMessage());
        return false;
    }
}

/**
 * Obtener todos los cursos a los que tiene acceso el usuario
 */
function getUserCourses($userId) {
    $db = getDB();
    return $db->fetchAll(
        'SELECT ua.course_id, ua.granted_at, c.title as course_title
         FROM user_access ua
         LEFT JOIN courses c ON ua.course_id = c.id
         WHERE ua.user_id = ?
         ORDER BY ua.granted_at DESC',
        [$userId]
    );
}

/**
 * Verificar acceso y redirigir si no tiene permisos
 */
function requireCourseAccess($userId, $courseId, $redirectTo = '/') {
    if (!hasAccess($userId, $courseId)) {
        setFlash('No tienes acceso a este curso. Completa tu compra para acceder.', 'error');
        redirect($redirectTo);
    }
}

/**
 * Verificar si el usuario ha pagado por el curso
 */
function hasPaidForCourse($userId, $courseId) {
    $db = getDB();
    $order = $db->fetchOne(
        'SELECT id FROM orders 
         WHERE user_id = ? AND status = "paid" 
         AND id IN (
             SELECT DISTINCT o.id FROM orders o 
             INNER JOIN user_access ua ON o.user_id = ua.user_id 
             WHERE ua.course_id = ? AND o.status = "paid"
         )',
        [$userId, $courseId]
    );
    
    return (bool) $order;
}

/**
 * Obtener estadísticas de acceso
 */
function getAccessStats($courseId) {
    $db = getDB();
    
    $stats = $db->fetchOne(
        'SELECT 
            COUNT(*) as total_access,
            COUNT(DISTINCT ua.user_id) as unique_users,
            MAX(ua.granted_at) as last_access_granted
         FROM user_access ua 
         WHERE ua.course_id = ?',
        [$courseId]
    );
    
    return $stats;
}
