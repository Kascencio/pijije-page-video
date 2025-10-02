# 🚀 Instrucciones Rápidas - Sistema de Cursos

## ⚡ Configuración en 5 minutos

### 1. Configurar Base de Datos
```bash
# Crear base de datos
mysql -u root -p
CREATE DATABASE cursos CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'cursos_user'@'localhost' IDENTIFIED BY 'cursos_pass_2024!';
GRANT ALL PRIVILEGES ON cursos.* TO 'cursos_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;

# Inicializar base de datos
php bin/init_db.php

# Inicializar panel de administración
php bin/init_admin.php
```

### 2. Configurar PayPal (Sandbox)
1. Ir a https://developer.paypal.com
2. Crear aplicación en modo "Sandbox"
3. Copiar `Client ID` y `Secret`
4. Editar `secure/config.php`:
```php
'paypal' => [
    'client_id' => 'TU_CLIENT_ID_SANDBOX',
    'secret'    => 'TU_SECRET_SANDBOX',
    // ...
],
```

### 3. Configurar Videos de Google Drive
1. Subir 6 videos a Google Drive
2. Configurar permisos: "Cualquiera con el enlace puede ver"
3. Copiar ID de cada video (de la URL)
4. Actualizar `sql/seed_videos.sql` con los IDs reales

### 4. Ejecutar Servidor
```bash
php -S 127.0.0.1:8080 -t public
```

### 5. Acceder al Sistema
1. **Panel de Admin**: http://127.0.0.1:8080/admin/login.php
   - Usuario: `admin`
   - Contraseña: `admin123` (⚠️ CAMBIAR INMEDIATAMENTE)
2. **Sitio Principal**: http://127.0.0.1:8080
3. Registrarse como usuario
4. Comprar curso con PayPal Sandbox
5. Verificar acceso a videos

## 🎛️ Panel de Administración

### Funcionalidades:
- **Dashboard**: Estadísticas del sistema
- **Usuarios**: Gestión de usuarios y permisos
- **Videos**: Agregar/editar videos del curso
- **Pagos**: Ver órdenes y procesar reembolsos
- **Configuración**: Configurar curso y PayPal

## 🔧 Variables Importantes

### Base de Datos
- **Host**: localhost
- **Base**: cursos
- **Usuario**: cursos_user
- **Password**: cursos_pass_2024!

### PayPal Sandbox
- **URL**: https://api-m.sandbox.paypal.com
- **Moneda**: MXN
- **Monto**: 1500 centavos ($15.00 MXN)

### Google Drive
- **Permisos**: "Cualquiera con el enlace puede ver"
- **Formato URL**: https://drive.google.com/file/d/[ID]/preview
- **6 videos** requeridos

## 📁 Archivos Clave

- `secure/config.php` - Configuración principal
- `sql/schema.sql` - Estructura de base de datos
- `sql/seed_videos.sql` - Videos del curso
- `public/index.php` - Página principal
- `public/mis-videos.php` - Área de videos

## 🚨 Problemas Comunes

**Error de conexión a BD**
- Verificar credenciales en `secure/config.php`
- Verificar que MySQL está ejecutándose

**PayPal no funciona**
- Verificar Client ID y Secret
- Verificar que está en modo Sandbox

**Videos no se reproducen**
- Verificar permisos de Google Drive
- Verificar que los drive_file_id son correctos

**Error 500**
- Verificar logs de PHP
- Verificar permisos de archivos

**Panel de admin no funciona**
- Verificar que se ejecutó `php bin/init_admin.php`
- Verificar credenciales de admin
- Revisar permisos de archivos y carpetas

## 📞 Soporte

- Email: organicosdeltropico@yahoo.com.mx
- Tel: +52 93 4115 0595

---

**¡Listo!** Tu sistema de cursos está funcionando. Para producción, consulta el README.md completo.
