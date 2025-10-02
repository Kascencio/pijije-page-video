# Sistema de Cursos - Ganadería Regenerativa

Sistema PHP seguro para venta de cursos online con integración PayPal y gating de contenido.

## 🚀 Características

- ✅ Autenticación segura (registro/login)
- ✅ Integración PayPal (Orders API v2 + Webhooks)
- ✅ Gating de contenido por pago
- ✅ Embeds de Google Drive para videos
- ✅ Rate limiting y protección CSRF
- ✅ Headers de seguridad (CSP, HSTS, etc.)
- ✅ Validación robusta del lado del servidor
- ✅ Logs de seguridad y auditoría

## 📁 Estructura del Proyecto

```
cursos/
├── public/                    # Raíz pública (public_html en cPanel)
│   ├── index.php             # Landing del curso
│   ├── login.php             # Página de inicio de sesión
│   ├── register.php          # Página de registro
│   ├── logout.php            # Cerrar sesión
│   ├── mis-videos.php        # Área privada de videos
│   ├── success.php           # Página post-pago exitoso
│   ├── cancel.php            # Página pago cancelado
│   ├── checkout/             # Endpoints de PayPal
│   │   ├── create-order.php
│   │   └── capture-order.php
│   ├── webhook/              # Webhooks
│   │   └── paypal.php
│   ├── lib/                  # Librerías PHP
│   │   ├── bootstrap.php     # Inicialización común
│   │   ├── db.php            # Conexión a base de datos
│   │   ├── security.php      # Headers y funciones de seguridad
│   │   ├── auth.php          # Autenticación
│   │   ├── csrf.php          # Protección CSRF
│   │   ├── validate.php      # Validaciones
│   │   ├── rate_limit.php    # Rate limiting
│   │   ├── access.php        # Gestión de acceso
│   │   ├── utils.php         # Funciones utilitarias
│   │   └── paypal.php        # Integración PayPal
│   ├── assets/               # CSS, JS, imágenes (sin secretos)
│   └── .htaccess             # Configuración de seguridad
├── secure/                   # FUERA de public/ (fuera de public_html)
│   ├── config.php            # Configuración y credenciales
│   ├── cache/                # Cache de tokens OAuth2
│   └── vendor/               # Librerías externas (si aplica)
├── sql/                      # Scripts de base de datos
│   ├── schema.sql            # Estructura de tablas
│   └── seed_videos.sql       # Datos iniciales de videos
└── README.md                 # Este archivo
```

## 🔧 Configuración Local

### Requisitos

- PHP 8.1 o superior
- MySQL 8.0 o superior
- Servidor web (Apache/Nginx) o servidor embebido de PHP

### Instalación Local

1. **Clonar o descargar el proyecto**
   ```bash
   cd /ruta/a/tu/proyecto
   ```

2. **Configurar base de datos**
   ```bash
   # Crear base de datos
   mysql -u root -p
   CREATE DATABASE cursos CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   CREATE USER 'cursos_user'@'localhost' IDENTIFIED BY 'cursos_pass_2024!';
   GRANT ALL PRIVILEGES ON cursos.* TO 'cursos_user'@'localhost';
   FLUSH PRIVILEGES;
   EXIT;
   
   # Importar esquema
   mysql -u cursos_user -p cursos < sql/schema.sql
   mysql -u cursos_user -p cursos < sql/seed_videos.sql
   ```

3. **Configurar credenciales**
   Editar `secure/config.php`:
   ```php
   'db' => [
       'dsn'  => 'mysql:host=localhost;dbname=cursos;charset=utf8mb4',
       'user' => 'cursos_user',
       'pass' => 'cursos_pass_2024!',
   ],
   'paypal' => [
       'client_id' => 'TU_PAYPAL_CLIENT_ID_SANDBOX',
       'secret'    => 'TU_PAYPAL_SECRET_SANDBOX',
       // ...
   ],
   ```

4. **Configurar Google Drive**
   - Subir videos a Google Drive
   - Configurar permisos: "Cualquiera con el enlace puede ver"
   - Actualizar `drive_file_id` en `sql/seed_videos.sql`

5. **Ejecutar servidor local**
   ```bash
   cd cursos
   php -S 127.0.0.1:8080 -t public
   ```

6. **Probar la aplicación**
   - Abrir http://127.0.0.1:8080
   - Registrar un usuario
   - Probar flujo de pago con PayPal Sandbox

## 🌐 Despliegue a cPanel

### 1. Preparar archivos

```bash
# Crear archivo de despliegue
tar -czf cursos-deploy.tar.gz cursos/
```

### 2. Subir archivos

**Opción A: Subir vía cPanel File Manager**
1. Ir a File Manager en cPanel
2. Navegar a `public_html`
3. Crear subcarpeta `cursos` (opcional)
4. Subir y extraer archivos

**Opción B: Subir vía FTP/SFTP**
```bash
# Subir carpeta public/ a public_html/
rsync -av cursos/public/ usuario@servidor.com:public_html/cursos/

# Subir carpeta secure/ FUERA de public_html
rsync -av cursos/secure/ usuario@servidor.com:secure/
```

### 3. Configurar base de datos

1. **Crear base de datos en cPanel**
   - Ir a "MySQL Databases"
   - Crear base de datos: `usuario_cursos`
   - Crear usuario: `usuario_cursouser`
   - Asignar permisos completos

2. **Importar esquema**
   ```bash
   mysql -u usuario_cursouser -p usuario_cursos < sql/schema.sql
   mysql -u usuario_cursouser -p usuario_cursos < sql/seed_videos.sql
   ```

### 4. Configurar SSL/HTTPS

1. **Activar SSL**
   - Ir a "SSL/TLS" en cPanel
   - Activar "AutoSSL" o instalar certificado

2. **Forzar HTTPS**
   Editar `public/.htaccess`:
   ```apache
   RewriteCond %{HTTPS} off
   RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
   ```

### 5. Actualizar configuración

Editar `secure/config.php`:
```php
'env' => 'live',
'app' => [
    'base_url'  => 'https://tudominio.com/cursos',
    'course_id' => 1,
],
'db' => [
    'dsn'  => 'mysql:host=localhost;dbname=usuario_cursos;charset=utf8mb4',
    'user' => 'usuario_cursouser',
    'pass' => 'tu_password_db',
],
'paypal' => [
    'client_id' => 'TU_PAYPAL_CLIENT_ID_LIVE',
    'secret'    => 'TU_PAYPAL_SECRET_LIVE',
    'base_api'  => 'https://api-m.paypal.com',
],
'security' => [
    'session_secure' => true, // Activar en producción
],
```

### 6. Configurar PayPal Live

1. **Crear aplicación en PayPal Developer**
   - Ir a https://developer.paypal.com
   - Cambiar a modo "Live"
   - Obtener `client_id` y `secret`

2. **Configurar webhook**
   - URL del webhook: `https://tudominio.com/cursos/webhook/paypal.php`
   - Eventos: `PAYMENT.CAPTURE.COMPLETED`

### 7. Verificar permisos

```bash
# Permisos de archivos
find public/ -type f -exec chmod 644 {} \;
find public/ -type d -exec chmod 755 {} \;

# Permisos de directorio secure
chmod 755 secure/
chmod 644 secure/config.php
chmod 755 secure/cache/
```

## 🔒 Configuración de Seguridad

### Variables de entorno sensibles

**NUNCA** subir a repositorio público:
- `secure/config.php`
- `secure/cache/`
- Archivos `.env` (si se usan)

### Headers de seguridad

El sistema incluye automáticamente:
- Content Security Policy (CSP)
- X-Frame-Options: DENY
- X-Content-Type-Options: nosniff
- Referrer-Policy: strict-origin-when-cross-origin
- HSTS (en producción con HTTPS)

### Rate limiting

Configurado por endpoint:
- Login: 5 intentos por 15 minutos
- Registro: 3 registros por hora
- Checkout: 10 órdenes por hora
- Webhook: 100 peticiones por hora

## 🧪 Testing

### Flujo de pruebas completo

1. **Registro de usuario**
   - Ir a `/register.php`
   - Completar formulario
   - Verificar validaciones

2. **Login**
   - Ir a `/login.php`
   - Iniciar sesión
   - Verificar redirección

3. **Compra con PayPal Sandbox**
   - Ir a `/index.php`
   - Hacer clic en "Comprar Curso"
   - Completar pago en PayPal Sandbox
   - Verificar redirección a `/success.php`

4. **Acceso a videos**
   - Ir a `/mis-videos.php`
   - Verificar que se muestran los 6 videos
   - Probar reproducción de videos

5. **Webhook**
   - Simular webhook de PayPal
   - Verificar que se concede acceso automáticamente

### Casos de prueba

- ✅ Registro con datos válidos
- ✅ Login con credenciales correctas
- ✅ Login con credenciales incorrectas (rate limiting)
- ✅ Pago exitoso con PayPal
- ✅ Pago cancelado
- ✅ Acceso sin autenticación (redirección)
- ✅ Acceso sin pago (redirección)
- ✅ CSRF protection
- ✅ Rate limiting
- ✅ Validación de inputs

## 🐛 Troubleshooting

### Problemas comunes

**Error de conexión a base de datos**
```bash
# Verificar credenciales en secure/config.php
# Verificar que la base de datos existe
mysql -u usuario -p -e "SHOW DATABASES;"
```

**PayPal no funciona**
```bash
# Verificar client_id y secret
# Verificar que estás en sandbox/live según corresponda
# Revisar logs de error en cPanel
```

**Videos no se reproducen**
```bash
# Verificar permisos de Google Drive
# Verificar que los drive_file_id son correctos
# Verificar CSP permite Google Drive
```

**Errores 500**
```bash
# Revisar logs de error de PHP
# Verificar permisos de archivos
# Verificar que todas las librerías están incluidas
```

### Logs importantes

- **PHP Error Log**: `/home/usuario/logs/error.log`
- **Apache Error Log**: `/home/usuario/logs/access.log`
- **Application Logs**: Tabla `security_logs` en base de datos

## 📞 Soporte

Para soporte técnico:
- Email: organicosdeltropico@yahoo.com.mx
- Teléfono: +52 93 4115 0595

## 📄 Licencia

Sistema desarrollado para Pijije Regenerativo.
Todos los derechos reservados.

---

**⚠️ Importante**: Este sistema maneja pagos reales. Siempre probar exhaustivamente en sandbox antes de activar en producción.
