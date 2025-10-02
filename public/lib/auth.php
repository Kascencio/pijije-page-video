<?php
/**
 * Sistema de autenticación
 * Login, registro, logout y gestión de sesiones
 */

require_once __DIR__ . '/rate_limit.php';

/**
 * Registrar nuevo usuario
 */
function registerUser($name, $email, $password) {
    $db = getDB();
    
    // Validaciones
    $email = validateEmail($email);
    if (!$email) {
        return ['success' => false, 'error' => 'Email inválido'];
    }
    
    $name = validateName($name);
    if (!$name) {
        return ['success' => false, 'error' => 'Nombre inválido. Solo se permiten letras, espacios, apostrofes, puntos y guiones'];
    }
    
    $passwordValidation = validatePasswordPolicy($password);
    if ($passwordValidation !== true) {
        return ['success' => false, 'error' => $passwordValidation];
    }
    
    try {
        // Verificar si el email ya existe
        $existing = $db->fetchOne('SELECT id FROM users WHERE email = ?', [$email]);
        if ($existing) {
            return ['success' => false, 'error' => 'El email ya está registrado'];
        }
        
        // Crear usuario
        $userId = $db->insert('users', [
            'name' => $name,
            'email' => $email,
            'pass_hash' => hashPassword($password),
            'verified' => 1,
            'created_at' => now()
        ]);
        
        logSecurity('user_registered', [
            'user_id' => $userId,
            'email' => $email
        ]);
        
        return ['success' => true, 'user_id' => $userId];
        
    } catch (Exception $e) {
        error_log("User registration failed: " . $e->getMessage());
        return ['success' => false, 'error' => 'Error interno del servidor'];
    }
}

/**
 * Autenticar usuario
 */
function authenticateUser($email, $password) {
    $config = require_once __DIR__ . '/../../secure/config.php';
    $db = getDB();
    
    $email = validateEmail($email);
    if (!$email) {
        return ['success' => false, 'error' => 'Credenciales inválidas'];
    }
    
    try {
        // Obtener usuario
        $user = $db->fetchOne(
            'SELECT id, name, email, pass_hash, login_attempts, locked_until FROM users WHERE email = ?',
            [$email]
        );
        
        if (!$user) {
            logSecurity('login_failed', ['email' => $email, 'reason' => 'user_not_found']);
            return ['success' => false, 'error' => 'Credenciales inválidas'];
        }
        
        // Verificar si está bloqueado
        if ($user['locked_until'] && strtotime($user['locked_until']) > time()) {
            $lockoutDuration = $config['security']['lockout_duration'];
            logSecurity('login_blocked', [
                'user_id' => $user['id'],
                'email' => $email,
                'locked_until' => $user['locked_until']
            ]);
            return ['success' => false, 'error' => "Cuenta bloqueada. Intenta nuevamente más tarde."];
        }
        
        // Verificar contraseña
        if (!verifyPassword($password, $user['pass_hash'])) {
            // Incrementar intentos fallidos
            $attempts = $user['login_attempts'] + 1;
            $maxAttempts = $config['security']['max_login_attempts'];
            
            if ($attempts >= $maxAttempts) {
                // Bloquear cuenta
                $lockedUntil = date('Y-m-d H:i:s', time() + $config['security']['lockout_duration']);
                $db->update('users', 
                    ['login_attempts' => $attempts, 'locked_until' => $lockedUntil],
                    'id = ?',
                    [$user['id']]
                );
                
                logSecurity('user_locked', [
                    'user_id' => $user['id'],
                    'email' => $email,
                    'attempts' => $attempts,
                    'locked_until' => $lockedUntil
                ]);
                
                return ['success' => false, 'error' => 'Demasiados intentos fallidos. Cuenta bloqueada temporalmente.'];
            } else {
                // Solo incrementar intentos
                $db->update('users', 
                    ['login_attempts' => $attempts],
                    'id = ?',
                    [$user['id']]
                );
            }
            
            logSecurity('login_failed', [
                'user_id' => $user['id'],
                'email' => $email,
                'attempts' => $attempts
            ]);
            
            return ['success' => false, 'error' => 'Credenciales inválidas'];
        }
        
        // Login exitoso - limpiar intentos y bloqueo
        $db->update('users', 
            ['login_attempts' => 0, 'locked_until' => null],
            'id = ?',
            [$user['id']]
        );
        
        // Iniciar sesión
        session_regenerate_id(true);
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['login_time'] = time();
        
        logSecurity('login_success', [
            'user_id' => $user['id'],
            'email' => $email
        ]);
        
        return ['success' => true, 'user' => $user];
        
    } catch (Exception $e) {
        error_log("Authentication failed: " . $e->getMessage());
        return ['success' => false, 'error' => 'Error interno del servidor'];
    }
}

/**
 * Cerrar sesión
 */
function logoutUser() {
    if (isLoggedIn()) {
        logSecurity('logout', ['user_id' => getCurrentUserId()]);
    }
    
    // Destruir sesión
    $_SESSION = [];
    
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    
    session_destroy();
}

/**
 * Requerir autenticación
 */
function requireLogin($redirectTo = '/login.php') {
    if (!isLoggedIn()) {
        setFlash('Debes iniciar sesión para acceder a esta página', 'warning');
        redirect($redirectTo);
    }
}

/**
 * Verificar si el usuario tiene acceso al curso
 */
function hasCourseAccess($userId, $courseId) {
    $db = getDB();
    $access = $db->fetchOne(
        'SELECT id FROM user_access WHERE user_id = ? AND course_id = ?',
        [$userId, $courseId]
    );
    
    return (bool) $access;
}

/**
 * Conceder acceso al curso
 */
function grantCourseAccess($userId, $courseId) {
    $db = getDB();
    
    try {
        // Usar INSERT ... ON DUPLICATE KEY UPDATE para evitar duplicados
        $db->query(
            'INSERT INTO user_access (user_id, course_id, granted_at) VALUES (?, ?, ?)
             ON DUPLICATE KEY UPDATE granted_at = VALUES(granted_at)',
            [$userId, $courseId, now()]
        );
        
        logSecurity('course_access_granted', [
            'user_id' => $userId,
            'course_id' => $courseId
        ]);
        
        return true;
    } catch (Exception $e) {
        error_log("Grant course access failed: " . $e->getMessage());
        return false;
    }
}

/**
 * Obtener videos del curso para un usuario con acceso
 */
function getCourseVideos($userId, $courseId) {
    if (!hasCourseAccess($userId, $courseId)) {
        return [];
    }
    
    $db = getDB();
    return $db->fetchAll(
        'SELECT id, title, description, drive_file_id, ord 
         FROM videos 
         WHERE course_id = ? 
         ORDER BY ord ASC',
        [$courseId]
    );
}
