<?php
/**
 * Script para inicializar el panel de administración
 * Ejecutar: php bin/init_admin.php
 */

// Cargar bootstrap completo para tener hashPassword(), getDB(), etc.
require_once __DIR__ . '/../public/lib/bootstrap.php';

function initAdminPanel() {
    echo "🚀 Inicializando Panel de Administración...\n\n";
    
    try {
        $db = getDB();
        
        // 1. Crear tablas del panel de admin
        echo "📊 Creando tablas del panel de administración...\n";
        $adminSchema = file_get_contents(__DIR__ . '/../sql/admin_schema.sql');
        
        // Dividir en statements individuales
        $statements = array_filter(
            array_map('trim', explode(';', $adminSchema)),
            function($stmt) {
                return !empty($stmt) && !preg_match('/^--/', $stmt);
            }
        );
        
        foreach ($statements as $statement) {
            if (!empty(trim($statement))) {
                $db->query($statement);
            }
        }
        
        echo "✅ Tablas creadas exitosamente\n";
        
        // 2. Crear admin por defecto
        echo "\n👤 Creando administrador por defecto...\n";

        // Migración automática: ampliar pass_hash si quedó como CHAR(60)
        try {
            $col = $db->fetchOne("SHOW COLUMNS FROM admins LIKE 'pass_hash'");
            if ($col && stripos($col['Type'], 'char(60)') !== false) {
                echo "🔄 Migrando columna pass_hash a VARCHAR(255)...\n";
                $db->query('ALTER TABLE admins MODIFY pass_hash VARCHAR(255) NOT NULL');
                echo "✅ Columna pass_hash migrada\n";
            }
        } catch (Exception $e) {
            echo "⚠️  No se pudo verificar/migrar pass_hash: " . $e->getMessage() . "\n";
        }
        
        // Verificar si ya existe
        $existingAdmin = $db->fetchOne('SELECT id FROM admins WHERE username = "admin"');
        
        if ($existingAdmin) {
            echo "⚠️  El administrador 'admin' ya existe\n";
        } else {
            // Crear admin con contraseña 'admin123'
            $password = 'admin123';
            // Usar helper que hace fallback si Argon2ID no está disponible
            if (!function_exists('hashPassword')) {
                require_once __DIR__ . '/../public/lib/utils.php';
            }
            $hash = hashPassword($password);
            
            $db->insert('admins', [
                'username' => 'admin',
                'email' => 'admin@organicos.com',
                'pass_hash' => $hash,
                'role' => 'super_admin',
                'active' => 1,
                'created_at' => date('Y-m-d H:i:s')
            ]);
            
            echo "✅ Administrador creado:\n";
            echo "   Usuario: admin\n";
            echo "   Contraseña: admin123\n";
            echo "   Email: admin@organicos.com\n";
            echo "   ⚠️  CAMBIA LA CONTRASEÑA INMEDIATAMENTE\n";
        }
        
        // 3. Verificar configuración del sistema
        echo "\n⚙️  Verificando configuración del sistema...\n";
        
        $requiredConfigs = [
            'course_price' => '150000',
            'course_title' => 'Curso de Ganadería Regenerativa',
            'course_description' => 'Aprende ganadería regenerativa de expertos',
            'course_duration' => '3',
            'contact_email' => 'organicosdeltropico@yahoo.com.mx',
            'contact_phone' => '+52 93 4115 0595'
        ];
        
        foreach ($requiredConfigs as $key => $defaultValue) {
            $existing = $db->fetchOne('SELECT config_key FROM system_config WHERE config_key = ?', [$key]);
            
            if (!$existing) {
                $db->insert('system_config', [
                    'config_key' => $key,
                    'config_value' => $defaultValue,
                    'description' => "Configuración automática para $key",
                    'updated_at' => date('Y-m-d H:i:s')
                ]);
                echo "✅ Configuración '$key' creada\n";
            } else {
                echo "ℹ️  Configuración '$key' ya existe\n";
            }
        }
        
        // 4. Crear log inicial
        echo "\n📝 Creando log inicial...\n";
        $db->insert('admin_logs', [
            'admin_id' => 1,
            'action' => 'system_init',
            'target_type' => 'system',
            'details' => json_encode(['message' => 'Panel de administración inicializado']),
            'ip_address' => '127.0.0.1',
            'created_at' => date('Y-m-d H:i:s')
        ]);
        
        echo "✅ Log inicial creado\n";
        
        // 5. Verificar permisos
        echo "\n🔐 Verificando estructura de permisos...\n";
        echo "ℹ️  Permisos configurados:\n";
        echo "   - super_admin: Todos los permisos\n";
        echo "   - admin: Permisos básicos (view, edit)\n";
        
        echo "\n🎉 ¡Panel de administración inicializado exitosamente!\n\n";
        echo "📋 Próximos pasos:\n";
        echo "   1. Accede a /admin/login.php\n";
        echo "   2. Usa las credenciales: admin / admin123\n";
        echo "   3. CAMBIA LA CONTRASEÑA INMEDIATAMENTE\n";
        echo "   4. Configura los datos de PayPal en Configuración\n";
        echo "   5. Agrega videos del curso en Gestión de Videos\n\n";
        
    } catch (Exception $e) {
        echo "❌ Error: " . $e->getMessage() . "\n";
        echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
        exit(1);
    }
}

// Ejecutar si se llama directamente
if (basename(__FILE__) === basename($_SERVER['SCRIPT_NAME'])) {
    initAdminPanel();
}
