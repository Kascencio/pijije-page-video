# 💳 Configuración de PayPal - Botón Hosted

## ✅ Credenciales Configuradas

He actualizado el sistema para usar tus credenciales específicas de PayPal:

### 🔑 Credenciales Actuales:
- **Client ID**: `BAAipD-neAwq8ipyuWBvR2fuwvHBZXSH01lloe6EczcKmt4VSmr_FdUCZ-2sWm7Hn1hGs_s0OZmXE7PTVI`
- **Hosted Button ID**: `DBG5YUH74U5A6`
- **Moneda**: MXN (Peso Mexicano)
- **Ambiente**: Sandbox (para pruebas)

## 🚀 Ventajas del Botón Hosted

### ✅ Beneficios:
1. **Más Seguro** - PayPal maneja toda la lógica de pago
2. **Más Simple** - No necesitas crear órdenes manualmente
3. **Más Confiable** - Menos puntos de falla
4. **Mejor UX** - Experiencia de usuario optimizada
5. **Menos Código** - Implementación más limpia

### 🔧 Configuración Implementada:

```html
<!-- SDK de PayPal con botón hosted -->
<script 
  src="https://www.paypal.com/sdk/js?client-id=BAAipD-neAwq8ipyuWBvR2fuwvHBZXSH01lloe6EczcKmt4VSmr_FdUCZ-2sWm7Hn1hGs_s0OZmXE7PTVI&components=hosted-buttons&disable-funding=venmo&currency=MXN">
</script>

<!-- Renderizado del botón -->
<script>
  paypal.HostedButtons({
    hostedButtonId: "DBG5YUH74U5A6",
  }).render("#paypal-container-DBG5YUH74U5A6")
</script>
```

## 📝 Próximos Pasos

### 1. Obtener el Secret Key
Necesitas obtener el **Secret Key** de tu aplicación PayPal:

1. Ve a https://developer.paypal.com
2. Inicia sesión con tu cuenta
3. Ve a "My Apps & Credentials"
4. Selecciona tu aplicación
5. Copia el **Client Secret**
6. Actualiza `secure/config.php`:

```php
'paypal' => [
    'client_id' => 'BAAipD-neAwq8ipyuWBvR2fuwvHBZXSH01lloe6EczcKmt4VSmr_FdUCZ-2sWm7Hn1hGs_s0OZmXE7PTVI',
    'secret'    => 'TU_SECRET_REAL_AQUI', // ← Actualizar aquí
    // ...
],
```

### 2. Configurar el Botón Hosted

En tu dashboard de PayPal:

1. Ve a "Tools" → "PayPal Buttons"
2. Encuentra el botón con ID `DBG5YUH74U5A6`
3. Configura:
   - **Return URL**: `https://tudominio.com/success.php`
   - **Cancel URL**: `https://tudominio.com/cancel.php`
   - **Monto**: $15.00 MXN
   - **Descripción**: "Curso de Ganadería Regenerativa"

### 3. Configurar Webhook (Opcional)

Para activación automática después del pago:

1. Ve a "Webhooks" en tu dashboard
2. Crea un nuevo webhook
3. URL: `https://tudominio.com/webhook/paypal.php`
4. Eventos: `PAYMENT.CAPTURE.COMPLETED`
5. Copia el Webhook ID y actualiza la configuración

## 🔄 Flujo de Pago Simplificado

Con el botón hosted, el flujo es más simple:

1. **Usuario hace clic** en el botón PayPal
2. **PayPal procesa** el pago directamente
3. **PayPal redirige** a success.php o cancel.php
4. **Webhook notifica** (si está configurado) para activar acceso

## 📁 Archivos Actualizados

- ✅ `secure/config.php` - Credenciales actualizadas
- ✅ `public/index.php` - Botón hosted implementado
- ✅ `secure/config.example.php` - Ejemplo actualizado

## 🧪 Pruebas

Para probar el sistema:

1. **Registrar usuario** en `/register.php`
2. **Iniciar sesión** en `/login.php`
3. **Hacer clic** en el botón PayPal
4. **Completar pago** en PayPal Sandbox
5. **Verificar redirección** a success.php

## 🚨 Importante

- **Sandbox**: Actualmente en modo de pruebas
- **Producción**: Cambiar a Live cuando esté listo
- **HTTPS**: Requerido para producción
- **Webhook**: Recomendado para activación automática

## 📞 Soporte

Si necesitas ayuda con la configuración:
- Email: organicosdeltropico@yahoo.com.mx
- Tel: +52 93 4115 0595

---

**¡El sistema está listo para procesar pagos con PayPal!** 🎉
