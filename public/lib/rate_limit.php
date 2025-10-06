<?php
/**
 * Sistema de rate limiting
 * Previene abuso mediante límites de peticiones por IP/usuario
 */

/**
 * Verificar rate limit
 */
function checkRateLimit($endpoint, $identifier = null, $maxHits = null, $windowSeconds = null) {
    $cfg = require __DIR__ . '/../../secure/config.php';
    $rateConfig = $cfg['security']['rate_limit'] ?? ['max_hits'=>20,'window_sec'=>900];
    
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
            // Primera petición - usar NOW() de MySQL para evitar desajustes de zona horaria
            $db->query(
                'INSERT INTO rate_limits (identifier, endpoint, hits, window_start) VALUES (?,?,1,NOW())',
                [$identifier, $endpoint]
            );
            return true;
        }

        // Obtener elapsed para decidir si expirar (TIMESTAMPDIFF ya existe en canAttempt, aquí lo recalculamos)
        $elapsedRow = $db->fetchOne(
            'SELECT TIMESTAMPDIFF(SECOND, window_start, NOW()) AS elapsed FROM rate_limits WHERE identifier = ? AND endpoint = ?',
            [$identifier, $endpoint]
        );
        $elapsed = (int)($elapsedRow['elapsed'] ?? 0);
        if ($elapsed < 0) {
            // Timestamp en el futuro -> normalizar reiniciando ventana
            $db->query('UPDATE rate_limits SET hits = 1, window_start = NOW() WHERE identifier = ? AND endpoint = ?', [$identifier, $endpoint]);
            return true;
        }
        if ($elapsed >= $windowSeconds) {
            // Ventana expirada: reiniciar contador
            $db->query('UPDATE rate_limits SET hits = 1, window_start = NOW() WHERE identifier = ? AND endpoint = ?', [$identifier, $endpoint]);
            return true;
        }

        if ($record['hits'] >= $maxHits) {
            logSecurity('rate_limit_exceeded', [
                'identifier' => $identifier,
                'endpoint' => $endpoint,
                'hits' => $record['hits'],
                'max_hits' => $maxHits,
                'window_seconds' => $windowSeconds,
                'remaining_seconds' => max(0, $windowSeconds - $elapsed)
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
        
        if (function_exists('isAjax') && isAjax()) {
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
 * Obtener estado de intentos sin incrementar (para credenciales)
 * Devuelve: ['allowed'=>bool,'remaining'=>int,'hits'=>int,'max'=>int,'retry_after'=>int]
 */
function canAttempt($endpoint, $identifier = null) {
    $cfg = getRateLimitConfig($endpoint);
    $maxHits = $cfg['max_hits'];
    $window = $cfg['window_sec'];
    if ($identifier === null) {
        $identifier = getRealIp();
    } else {
        $identifier = 'user_' . $identifier;
    }
    $db = getDB();
    try {
        // Limpiar expirados para este endpoint/identificador
        $db->query(
            'DELETE FROM rate_limits WHERE identifier = ? AND endpoint = ? AND window_start < DATE_SUB(NOW(), INTERVAL ? SECOND)',
            [$identifier, $endpoint, $window]
        );
        $record = $db->fetchOne(
            'SELECT hits, TIMESTAMPDIFF(SECOND, window_start, NOW()) as elapsed FROM rate_limits WHERE identifier = ? AND endpoint = ?',
            [$identifier, $endpoint]
        );
        if (!$record) {
            return [
                'allowed' => true,
                'remaining' => $maxHits,
                'hits' => 0,
                'max' => $maxHits,
                'retry_after' => 0
            ];
        }
        $hits = (int)$record['hits'];
        $elapsed = (int)$record['elapsed'];
        if ($elapsed < 0) {
            // Normalizar timestamps futuros
            $db->query('UPDATE rate_limits SET hits = 0, window_start = NOW() WHERE identifier = ? AND endpoint = ?', [$identifier, $endpoint]);
            return [
                'allowed' => true,
                'remaining' => $maxHits,
                'hits' => 0,
                'max' => $maxHits,
                'retry_after' => 0
            ];
        }
        if ($elapsed >= $window) {
            // Ventana expirada: reiniciar
            $db->query('UPDATE rate_limits SET hits = 0, window_start = NOW() WHERE identifier = ? AND endpoint = ?', [$identifier, $endpoint]);
            return [
                'allowed' => true,
                'remaining' => $maxHits,
                'hits' => 0,
                'max' => $maxHits,
                'retry_after' => 0
            ];
        }
        if ($hits >= $maxHits) {
            $retry = max(0, $window - $elapsed);
            return [
                'allowed' => false,
                'remaining' => 0,
                'hits' => $hits,
                'max' => $maxHits,
                'retry_after' => $retry
            ];
        }
        return [
            'allowed' => true,
            'remaining' => $maxHits - $hits,
            'hits' => $hits,
            'max' => $maxHits,
            'retry_after' => 0
        ];
    } catch (Exception $e) {
        error_log('canAttempt error: ' . $e->getMessage());
        return [
            'allowed' => true,
            'remaining' => $maxHits,
            'hits' => 0,
            'max' => $maxHits,
            'retry_after' => 0
        ];
    }
}

/**
 * Registrar intento fallido (solo se llama si falla login/registro)
 */
function recordFailedAttempt($endpoint, $identifier = null) {
    $cfg = getRateLimitConfig($endpoint);
    $maxHits = $cfg['max_hits'];
    $window = $cfg['window_sec'];
    if ($identifier === null) {
        $identifier = getRealIp();
    } else {
        $identifier = 'user_' . $identifier;
    }
    $db = getDB();
    try {
        $record = $db->fetchOne(
            'SELECT hits FROM rate_limits WHERE identifier = ? AND endpoint = ?',
            [$identifier, $endpoint]
        );
        if (!$record) {
            $db->query('INSERT INTO rate_limits (identifier, endpoint, hits, window_start) VALUES (?,?,1,NOW())', [$identifier, $endpoint]);
            return 1;
        }
        // Verificar expiración/futuro
        $elapsedRow = $db->fetchOne('SELECT TIMESTAMPDIFF(SECOND, window_start, NOW()) AS elapsed FROM rate_limits WHERE identifier = ? AND endpoint = ?', [$identifier, $endpoint]);
        $elapsed = (int)($elapsedRow['elapsed'] ?? 0);
        if ($elapsed < 0 || $elapsed >= $window) {
            $db->query('UPDATE rate_limits SET hits = 1, window_start = NOW() WHERE identifier = ? AND endpoint = ?', [$identifier, $endpoint]);
            $hits = 1;
        } else {
            $hits = (int)$record['hits'];
            if ($hits >= $maxHits) {
                return $hits; // ya excedido
            }
            $db->query('UPDATE rate_limits SET hits = hits + 1 WHERE identifier = ? AND endpoint = ?', [$identifier, $endpoint]);
            $hits++;
        }
        if ($hits >= $maxHits) {
            logSecurity('rate_limit_exceeded', [
                'identifier' => $identifier,
                'endpoint' => $endpoint,
                'hits' => $hits,
                'max_hits' => $maxHits,
                'window_seconds' => $window
            ]);
        }
        return $hits;
    } catch (Exception $e) {
        error_log('recordFailedAttempt error: ' . $e->getMessage());
        return 0;
    }
}

/**
 * Resetear contador (en éxito de login opcional)
 */
function resetRateLimit($endpoint, $identifier = null) {
    if ($identifier === null) {
        $identifier = getRealIp();
    } else {
        $identifier = 'user_' . $identifier;
    }
    try {
        $db = getDB();
        $db->query('DELETE FROM rate_limits WHERE identifier = ? AND endpoint = ?', [$identifier, $endpoint]);
    } catch (Exception $e) {
        error_log('resetRateLimit error: ' . $e->getMessage());
    }
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
