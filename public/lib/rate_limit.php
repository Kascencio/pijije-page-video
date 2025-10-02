<?php
/**
 * Sistema de rate limiting
 * Previene abuso mediante límites de peticiones por IP/usuario
 */

/**
 * Verificar rate limit
 */
function checkRateLimit($endpoint, $identifier = null, $maxHits = null, $windowSeconds = null) {
    $config = require_once __DIR__ . '/../../secure/config.php';
    $rateConfig = $config['security']['rate_limit'];
    
    // Usar configuración por defecto si no se especifica
    $maxHits = $maxHits ?? $rateConfig['max_hits'];
    $windowSeconds = $windowSeconds ?? $rateConfig['window_sec'];
    
    // Identificador: IP o user_id
    if ($identifier === null) {
        $identifier = getRealIp();
    } else {
        $identifier = 'user_' . $identifier;
    }
    
    $db = getDB();
    
    try {
        // Limpiar registros expirados
        $db->query(
            'DELETE FROM rate_limits WHERE window_start < DATE_SUB(NOW(), INTERVAL ? SECOND)',
            [$windowSeconds]
        );
        
        // Verificar hits actuales
        $record = $db->fetchOne(
            'SELECT hits FROM rate_limits WHERE identifier = ? AND endpoint = ?',
            [$identifier, $endpoint]
        );
        
        if (!$record) {
            // Primera petición
            $db->insert('rate_limits', [
                'identifier' => $identifier,
                'endpoint' => $endpoint,
                'hits' => 1,
                'window_start' => now()
            ]);
            
            return true;
        }
        
        if ($record['hits'] >= $maxHits) {
            // Rate limit excedido
            logSecurity('rate_limit_exceeded', [
                'identifier' => $identifier,
                'endpoint' => $endpoint,
                'hits' => $record['hits'],
                'max_hits' => $maxHits,
                'window_seconds' => $windowSeconds
            ]);
            
            return false;
        }
        
        // Incrementar contador
        $db->query(
            'UPDATE rate_limits SET hits = hits + 1 WHERE identifier = ? AND endpoint = ?',
            [$identifier, $endpoint]
        );
        
        return true;
        
    } catch (Exception $e) {
        error_log("Rate limit check failed: " . $e->getMessage());
        // En caso de error, permitir la petición (fail open)
        return true;
    }
}

/**
 * Middleware de rate limiting
 */
function rateLimitMiddleware($endpoint, $maxHits = null, $windowSeconds = null) {
    if (!checkRateLimit($endpoint, null, $maxHits, $windowSeconds)) {
        http_response_code(429);
        header('Retry-After: ' . ($windowSeconds ?? 900));
        
        if (isAjax()) {
            jsonResponse([
                'error' => 'Demasiadas peticiones. Intenta nuevamente más tarde.',
                'retry_after' => $windowSeconds ?? 900
            ], 429);
        }
        
        die('Demasiadas peticiones. Por favor, espera unos minutos e intenta nuevamente.');
    }
}

/**
 * Rate limits específicos por endpoint
 */
function getRateLimitConfig($endpoint) {
    $configs = [
        'login' => ['max_hits' => 5, 'window_sec' => 900],      // 5 intentos por 15 min
        'register' => ['max_hits' => 3, 'window_sec' => 3600],  // 3 registros por hora
        'create_order' => ['max_hits' => 10, 'window_sec' => 3600], // 10 órdenes por hora
        'capture_order' => ['max_hits' => 20, 'window_sec' => 3600], // 20 capturas por hora
        'webhook' => ['max_hits' => 100, 'window_sec' => 3600], // 100 webhooks por hora
        'default' => ['max_hits' => 20, 'window_sec' => 900]    // 20 peticiones por 15 min
    ];
    
    return $configs[$endpoint] ?? $configs['default'];
}

/**
 * Aplicar rate limit con configuración específica
 */
function applyRateLimit($endpoint, $identifier = null) {
    $config = getRateLimitConfig($endpoint);
    rateLimitMiddleware($endpoint, $config['max_hits'], $config['window_sec']);
}

/**
 * Limpiar rate limits expirados (ejecutar periódicamente)
 */
function cleanupExpiredRateLimits() {
    try {
        $db = getDB();
        $db->query('DELETE FROM rate_limits WHERE window_start < DATE_SUB(NOW(), INTERVAL 1 HOUR)');
    } catch (Exception $e) {
        error_log("Rate limit cleanup failed: " . $e->getMessage());
    }
}
