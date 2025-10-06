<?php
// Script rápido de verificación de conexión DB
// Uso: http://localhost/cursos/public/_db_check.php
header('Content-Type: text/plain; charset=utf-8');
require_once __DIR__ . '/lib/db.php';
// Extensiones
echo "Extensiones:\n";
echo ' - PDO:        ' . (extension_loaded('pdo') ? '✅' : '❌') . "\n";
echo ' - pdo_mysql:  ' . (extension_loaded('pdo_mysql') ? '✅' : '❌') . "\n\n";

try {
  $db = getDB();
  $pdo = $db->getPdo();
  // Probar una consulta simple
  $stmt = $pdo->query('SELECT 1');
  $ok = $stmt->fetchColumn();
  echo "✅ Conexión OK. SELECT 1 => {$ok}\n";
  // Mostrar tablas
  $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
  echo "Tablas (" . count($tables) . "): " . (empty($tables) ? '[ninguna]' : implode(', ', $tables)) . "\n";
} catch (Exception $e) {
  echo "❌ Falla de conexión o consulta:\n";
  echo $e->getMessage() . "\n";
  echo "Revisar: secure/config.php (dsn, user, pass) y que MySQL esté activo.\n";
  // Pistas comunes
  echo "Pistas comunes:\n - ¿Servicio MySQL en XAMPP está iniciado?\n - ¿La base 'cursos' existe? Si no, ejecuta bin/init_db.php o crea manualmente.\n - Usuario/contraseña correctos (root / vacío en XAMPP)?\n - Puerto (3306) libre o diferente? Añade ;port=3306 al DSN si cambiaste.\n - Extensión pdo_mysql cargada (arriba debe salir ✅).\n";
}
