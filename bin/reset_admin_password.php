#!/usr/bin/env php
<?php
/**
 * Reset / create admin password utility
 * Usage:
 *   php bin/reset_admin_password.php                # resets user 'admin' with a random strong password
 *   php bin/reset_admin_password.php usuario        # resets given username with random strong password
 *   php bin/reset_admin_password.php usuario nuevaClaveSegura123!
 */

require_once __DIR__ . '/../public/lib/bootstrap.php';
require_once __DIR__ . '/../public/lib/admin.php';

function out($msg){ echo $msg."\n"; }
function err($msg){ fwrite(STDERR, "[ERROR] $msg\n"); }

$args = $argv; array_shift($args); // remove script name
$username = $args[0] ?? 'admin';
$newPass  = null;
$forceBcrypt = false;

// Parse args: allow order: username [password] [--force-bcrypt]
foreach ($args as $i => $a) {
    if ($a === '--force-bcrypt') { $forceBcrypt = true; unset($args[$i]); }
}
$args = array_values($args);
if (isset($args[1]) && $args[1] !== '--force-bcrypt') { $newPass = $args[1]; }

function generateStrongPassword($length = 14) {
    $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnpqrstuvwxyz23456789!@$%&*+-_?';
    $bytes = random_bytes($length);
    $pw = '';
    for($i=0;$i<$length;$i++) { $pw .= $chars[ord($bytes[$i]) % strlen($chars)]; }
    return $pw;
}

try {
    $db = getDB();
    $admin = $db->fetchOne('SELECT * FROM admins WHERE username = ?', [$username]);

    if (!$admin) {
        out("⚠️  Usuario '$username' no existe. Creándolo...");
        $passPlain = $newPass ?: generateStrongPassword();
        if ($forceBcrypt && defined('PASSWORD_BCRYPT')) {
            $hash = password_hash($passPlain, PASSWORD_BCRYPT, ['cost' => 12]);
            $algoLabel = 'bcrypt (forzado)';
        } else {
            $hash = hashPassword($passPlain);
            $algoLabel = (strpos($hash, '$argon2id$') === 0 ? 'argon2id' : (strpos($hash,'$2y$')===0?'bcrypt':'desconocido'));
        }
        $db->insert('admins', [
            'username' => $username,
            'email' => $username.'@example.local',
            'pass_hash' => $hash,
            'role' => 'super_admin',
            'active' => 1,
            'created_at' => date('Y-m-d H:i:s')
        ]);
        out('✅ Administrador creado.');
        out('Usuario : ' . $username);
        out('Password: ' . $passPlain);
        out('Rol     : super_admin');
        out('Algoritmo hash: ' . $algoLabel);
        out("➡️  Inicia sesión y cambia la contraseña inmediatamente.");
        exit(0);
    }

    // Update existing admin
    $passPlain = $newPass ?: generateStrongPassword();
    $prevHash = $admin['pass_hash'];
    $prevAlgo = (strpos($prevHash,'$argon2id$')===0?'argon2id':(strpos($prevHash,'$2y$')===0?'bcrypt':'desconocido'));
    if ($forceBcrypt && defined('PASSWORD_BCRYPT')) {
        $hash = password_hash($passPlain, PASSWORD_BCRYPT, ['cost' => 12]);
        $algoLabel = 'bcrypt (forzado)';
    } else {
        $hash = hashPassword($passPlain);
        $algoLabel = (strpos($hash,'$argon2id$')===0?'argon2id':(strpos($hash,'$2y$')===0?'bcrypt':'desconocido'));
    }
    $db->update('admins', ['pass_hash' => $hash, 'active' => 1], 'id = ?', [$admin['id']]);

    out('🔐 Contraseña actualizada correctamente');
    out('Usuario : ' . $username);
    out('Password: ' . $passPlain);
    out('Estado  : activo');
    out('Algoritmo anterior: ' . $prevAlgo);
    out('Algoritmo nuevo   : ' . $algoLabel);
    out('➡️  Cambia la contraseña desde el panel tras iniciar sesión.');
    exit(0);

} catch (Throwable $e) {
    err($e->getMessage());
    err($e->getTraceAsString());
    exit(1);
}
