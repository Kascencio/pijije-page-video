# Pruebas de Migración PayPal Smart Buttons

Este archivo contiene las verificaciones que debes realizar después de implementar los cambios.

## 1. Verificar Headers de Seguridad

Abrir navegador y verificar en DevTools > Network que las siguientes headers estén presentes:

- `Content-Security-Policy: default-src 'self'; script-src 'self' 'nonce-[valor]' https://www.paypal.com; ...`
- `X-Content-Type-Options: nosniff`
- `X-Frame-Options: DENY`
- `Referrer-Policy: strict-origin-when-cross-origin`

## 2. Verificar Smart Buttons

1. Registrar un usuario nuevo
2. Verificar que los Smart Buttons se muestran en lugar de Hosted Buttons
3. Verificar que el nonce se aplica correctamente a los scripts
4. Verificar que no hay errores de CSP en la consola

## 3. Flujo de Pago

1. **Crear Orden**: Clic en Smart Button debe llamar a `/checkout/create-order.php`
2. **Capturar Pago**: Después de aprobar en PayPal, debe llamar a `/checkout/capture-order.php`
3. **Verificar BD**: La orden debe guardarse con `status='paid'` y el acceso debe otorgarse
4. **Verificar Acceso**: El usuario debe poder acceder a `/mis-videos.php`

## 4. Verificar Webhook

Si tienes webhook configurado:
1. El webhook debe validar la firma de PayPal (si webhook_id está configurado)
2. Debe procesar eventos `PAYMENT.CAPTURE.COMPLETED`
3. Debe ser idempotente (no duplicar acceso si se ejecuta múltiples veces)

## 5. Verificar Protecciones

1. **CSRF**: Los endpoints deben rechazar peticiones sin token CSRF
2. **Rate Limiting**: Demasiadas peticiones deben ser bloqueadas
3. **Auth**: Endpoints de checkout requieren autenticación
4. **htaccess**: Acceso a `/lib/`, `/secure/` debe retornar 403

## 6. Verificar Configuración

- `config.php` no debe tener `hosted_button_id`
- `base_url` debe apuntar a `/public`
- `amount` debe estar en centavos (120000 = $1,200.00)

## 7. Errores Comunes a Verificar

- PayPal SDK carga sin errores CSP
- Tokens CSRF se generan y validan correctamente
- Funciones `e()`, `csp_nonce()`, `csrf_token()` están disponibles
- No hay referencias a `hosted_button_id` en el código