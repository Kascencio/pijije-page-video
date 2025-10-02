<?php
/**
 * Script de inicialización de base de datos
 * Ejecutar desde la línea de comandos: php bin/init_db.php
 */

// Verificar que se ejecuta desde línea de comandos
if (php_sapi_name() !== 'cli') {
    die('Este script solo puede ejecutarse desde la línea de comandos');
}

echo "=== Inicialización de Base de Datos ===\n\n";

// Cargar configuración
$configPath = __DIR__ . '/../secure/config.php';
if (!file_exists($configPath)) {
    die("Error: No se encontró el archivo de configuración en {$configPath}\n");
}

$config = require $configPath;

// Conectar a la base de datos
try {
    $pdo = new PDO(
        $config['db']['dsn'],
        $config['db']['user'],
        $config['db']['pass'],
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );
    echo "✅ Conexión a base de datos exitosa\n";
} catch (PDOException $e) {
    die("❌ Error de conexión: " . $e->getMessage() . "\n");
}

// Verificar si las tablas ya existen
$tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
if (!empty($tables)) {
    echo "⚠️  Las tablas ya existen. ¿Deseas continuar? (s/n): ";
    $handle = fopen("php://stdin", "r");
    $line = fgets($handle);
    if (trim(strtolower($line)) !== 's') {
        echo "Operación cancelada.\n";
        exit(0);
    }
}

// Leer y ejecutar schema.sql
$schemaFile = __DIR__ . '/../sql/schema.sql';
if (!file_exists($schemaFile)) {
    die("❌ No se encontró el archivo schema.sql\n");
}

echo "📋 Ejecutando schema.sql...\n";
$schema = file_get_contents($schemaFile);

// Dividir en statements individuales
$statements = array_filter(array_map('trim', explode(';', $schema)));

foreach ($statements as $statement) {
    if (empty($statement) || strpos($statement, '--') === 0) {
        continue;
    }
    
    try {
        $pdo->exec($statement);
        echo "  ✅ Ejecutado: " . substr($statement, 0, 50) . "...\n";
    } catch (PDOException $e) {
        echo "  ❌ Error: " . $e->getMessage() . "\n";
        echo "  Statement: " . substr($statement, 0, 100) . "...\n";
    }
}

// Leer y ejecutar seed_videos.sql
$seedFile = __DIR__ . '/../sql/seed_videos.sql';
if (file_exists($seedFile)) {
    echo "\n🌱 Ejecutando seed_videos.sql...\n";
    $seed = file_get_contents($seedFile);
    
    $seedStatements = array_filter(array_map('trim', explode(';', $seed)));
    
    foreach ($seedStatements as $statement) {
        if (empty($statement) || strpos($statement, '--') === 0) {
            continue;
        }
        
        try {
            $pdo->exec($statement);
            echo "  ✅ Ejecutado: " . substr($statement, 0, 50) . "...\n";
        } catch (PDOException $e) {
            echo "  ❌ Error: " . $e->getMessage() . "\n";
        }
    }
}

// Verificar datos insertados
echo "\n📊 Verificando datos...\n";

try {
    $videoCount = $pdo->query("SELECT COUNT(*) FROM videos")->fetchColumn();
    echo "  📹 Videos: {$videoCount}\n";
    
    $userCount = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
    echo "  👤 Usuarios: {$userCount}\n";
    
    $orderCount = $pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn();
    echo "  💳 Órdenes: {$orderCount}\n";
    
} catch (PDOException $e) {
    echo "  ❌ Error al verificar datos: " . $e->getMessage() . "\n";
}

// Crear directorio de cache si no existe
$cacheDir = __DIR__ . '/../secure/cache';
if (!is_dir($cacheDir)) {
    mkdir($cacheDir, 0755, true);
    echo "  📁 Creado directorio de cache\n";
}

echo "\n🎉 Inicialización completada!\n";
echo "\nPróximos pasos:\n";
echo "1. Configurar credenciales de PayPal en secure/config.php\n";
echo "2. Actualizar drive_file_id en la tabla videos\n";
echo "3. Ejecutar: php -S 127.0.0.1:8080 -t public\n";
echo "4. Abrir: http://127.0.0.1:8080\n\n";
