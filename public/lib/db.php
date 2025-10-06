<?php
/**
 * Manejo de conexión a base de datos con PDO
 * Configuración segura con prepared statements y manejo de excepciones
 */

class Database {
    private static $instance = null;
    private $pdo;
    
    private function __construct() {
        $config = require __DIR__ . '/../../secure/config.php';
        $dsn = $config['db']['dsn'];
        $user = $config['db']['user'];
        $pass = $config['db']['pass'];
        $isDev = ($config['env'] ?? 'sandbox') !== 'live';

        $attemptErrors = [];
        $candidateDsns = [$dsn];
        // Si host=localhost, probamos 127.0.0.1 (TCP) para evitar problemas de socket
        if (strpos($dsn, 'host=localhost') !== false) {
            $candidateDsns[] = str_replace('host=localhost', 'host=127.0.0.1;port=3306', $dsn);
        }
        // Intentar via socket típico de XAMPP si existe y aún no se conectó
        $commonSocket = '/Applications/XAMPP/xamppfiles/var/mysql/mysql.sock';
        if (file_exists($commonSocket)) {
            // Extraer dbname y charset del dsn original
            $dbname = null; $charset = 'utf8mb4';
            if (preg_match('/dbname=([^;]+)/', $dsn, $m)) { $dbname = $m[1]; }
            if (preg_match('/charset=([^;]+)/', $dsn, $m)) { $charset = $m[1]; }
            if ($dbname) {
                $candidateDsns[] = "mysql:unix_socket={$commonSocket};dbname={$dbname};charset={$charset}";
            }
        }

        foreach ($candidateDsns as $tryDsn) {
            try {
                $this->pdo = new PDO(
                    $tryDsn,
                    $user,
                    $pass,
                    [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                        PDO::ATTR_EMULATE_PREPARES => false,
                        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
                    ]
                );
                if ($isDev && $tryDsn !== $dsn) {
                    error_log("[DB] Fallback DSN usado: {$tryDsn}");
                }
                break; // éxito
            } catch (PDOException $e) {
                $attemptErrors[] = $tryDsn . ' => ' . $e->getMessage();
                $this->pdo = null;
            }
        }
        if ($this->pdo === null) {
            error_log('[DB] Todos los intentos de conexión fallaron: ' . implode(' | ', $attemptErrors));
            $msg = $isDev ? ('DB connect error: ' . end($attemptErrors)) : 'Error de conexión a la base de datos';
            throw new Exception($msg);
        }
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function getPdo() {
        return $this->pdo;
    }
    
    /**
     * Ejecutar query con parámetros seguros
     */
    public function query($sql, $params = []) {
        if ($this->pdo === null) {
            throw new Exception('DB no inicializada');
        }
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            error_log("Database query failed: " . $e->getMessage() . " SQL: " . $sql);
            $isDev = false;
            try { $cfg = require __DIR__ . '/../../secure/config.php'; $isDev = ($cfg['env'] ?? 'sandbox') !== 'live'; } catch (Throwable $t) {}
            $msg = $isDev ? ('DB query error: ' . $e->getMessage()) : 'Error en la consulta a la base de datos';
            throw new Exception($msg);
        }
    }
    
    /**
     * Insertar registro y devolver ID
     */
    public function insert($table, $data) {
        if ($this->pdo === null) {
            throw new Exception('DB no inicializada');
        }
        $columns = implode(', ', array_keys($data));
        $placeholders = ':' . implode(', :', array_keys($data));
        
        $sql = "INSERT INTO {$table} ({$columns}) VALUES ({$placeholders})";
        $this->query($sql, $data);
        
        return $this->pdo->lastInsertId();
    }
    
    /**
     * Actualizar registro
     */
    public function update($table, $data, $where, $whereParams = []) {
        // Si el WHERE contiene '?' usamos placeholders posicionales también en SET para evitar mezcla
        $usePositional = strpos($where, '?') !== false;
        if ($usePositional) {
            $setParts = [];
            $values = [];
            foreach ($data as $col => $val) {
                $setParts[] = "$col = ?";
                $values[] = $val;
            }
            $setClause = implode(', ', $setParts);
            $sql = "UPDATE {$table} SET {$setClause} WHERE {$where}";
            $params = array_merge($values, $whereParams);
            return $this->query($sql, $params);
        } else {
            // Modo con placeholders nombrados
            $setParts = [];
            foreach (array_keys($data) as $column) {
                $setParts[] = "{$column} = :{$column}";
            }
            $setClause = implode(', ', $setParts);
            $sql = "UPDATE {$table} SET {$setClause} WHERE {$where}";
            $params = array_merge($data, $whereParams);
            return $this->query($sql, $params);
        }
    }
    
    /**
     * Obtener un registro
     */
    public function fetchOne($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        return $stmt->fetch();
    }
    
    /**
     * Obtener múltiples registros
     */
    public function fetchAll($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        return $stmt->fetchAll();
    }
    
    /**
     * Iniciar transacción
     */
    public function beginTransaction() {
        if ($this->pdo === null) { throw new Exception('DB no inicializada'); }
        return $this->pdo->beginTransaction();
    }
    
    /**
     * Confirmar transacción
     */
    public function commit() {
        if ($this->pdo === null) { throw new Exception('DB no inicializada'); }
        return $this->pdo->commit();
    }
    
    /**
     * Revertir transacción
     */
    public function rollback() {
        if ($this->pdo === null) { throw new Exception('DB no inicializada'); }
        return $this->pdo->rollback();
    }
    
    /**
     * Verificar si hay transacción activa
     */
    public function inTransaction() {
        if ($this->pdo === null) { return false; }
        return $this->pdo->inTransaction();
    }
    
    /**
     * Eliminar registros
     */
    public function delete($table, $where, $params = []) {
        try {
            if ($this->pdo === null) { throw new Exception('DB no inicializada'); }
            $sql = "DELETE FROM $table WHERE $where";
            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute($params);
        } catch (PDOException $e) {
            error_log("Database delete error: " . $e->getMessage());
            throw new Exception("Error al eliminar datos");
        }
    }
}

// Función helper para obtener instancia de DB
function getDB() {
    return Database::getInstance();
}
