<?php
require_once __DIR__ . '/lib/bootstrap.php';
require_once __DIR__ . '/lib/access.php';
$config = config();
$isLogged = isLoggedIn();
$courseId = $config['app']['course_id'];
$has = $isLogged ? (function_exists('hasAccess') ? hasAccess(getCurrentUserId(), $courseId) : false) : false;
$flash = getFlash();
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Acceso requerido - <?= escape(courseTitle()) ?></title>
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="<?= asset('assets/css/app.css') ?>" />
  <style>
    body{font-family:'Inter',system-ui,-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,'Helvetica Neue',Arial,sans-serif;background:linear-gradient(135deg,#0f172a,#1e293b);color:#f1f5f9;min-height:100vh;display:flex;align-items:center;justify-content:center;padding:40px;}
    .card{max-width:640px;width:100%;background:#0f1e2e;border:1px solid rgba(255,255,255,0.08);border-radius:24px;padding:48px;box-shadow:0 20px 40px -10px rgba(0,0,0,0.6);position:relative;overflow:hidden;}
    .card:before{content:'';position:absolute;inset:0;background:radial-gradient(circle at 30% 20%,rgba(56,189,248,0.15),transparent 60%),radial-gradient(circle at 80% 70%,rgba(99,102,241,0.15),transparent 60%);pointer-events:none;}
    h1{font-size:clamp(1.9rem,3vw,2.6rem);margin:0 0 16px;font-weight:700;background:linear-gradient(90deg,#60a5fa,#818cf8,#a78bfa);-webkit-background-clip:text;color:transparent;}
    p.lead{font-size:1.05rem;line-height:1.55;margin:0 0 28px;color:#cbd5e1;}
    .alert{margin-bottom:24px;padding:14px 18px;border-radius:14px;font-size:0.9rem;line-height:1.4;background:#1e3a8a;color:#fff;border:1px solid rgba(255,255,255,0.1);}
    .actions{display:flex;flex-wrap:wrap;gap:16px;margin-top:8px;}
    a.btn{display:inline-flex;align-items:center;gap:8px;padding:14px 22px;border-radius:14px;font-weight:600;text-decoration:none;font-size:0.95rem;line-height:1;transition:.25s;border:1px solid transparent;}
    a.btn-primary{background:linear-gradient(135deg,#2563eb,#6366f1);color:#fff;box-shadow:0 6px 18px -4px rgba(59,130,246,0.45);}a.btn-primary:hover{transform:translateY(-2px);box-shadow:0 10px 24px -4px rgba(59,130,246,0.6);} 
    a.btn-outline{background:rgba(255,255,255,0.05);color:#cbd5e1;border-color:rgba(255,255,255,0.15);}a.btn-outline:hover{background:rgba(255,255,255,0.12);color:#fff;}
    .status{font-size:.8rem;letter-spacing:.06em;text-transform:uppercase;margin-bottom:18px;color:#94a3b8;font-weight:600;}
    .features{display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:14px;margin:36px 0 4px;}
    .feature{background:#132435;padding:14px 16px;border:1px solid rgba(255,255,255,0.06);border-radius:12px;font-size:.78rem;line-height:1.35;color:#cbd5e1;display:flex;gap:10px;align-items:flex-start;}
    .feature svg{width:18px;height:18px;color:#60a5fa;flex-shrink:0;margin-top:2px;}
    footer{margin-top:42px;font-size:.7rem;text-align:center;color:#64748b;}
  </style>
</head>
<body>
  <div class="card">
    <div class="status">ACCESO RESTRINGIDO</div>
    <h1>Acceso no disponible aún</h1>
    <?php if ($flash): ?>
      <div class="alert"><?= escape($flash['message']) ?></div>
    <?php endif; ?>
    <?php if($has): ?>
      <p class="lead">Ya cuentas con acceso al curso pero llegaste aquí por error. Continúa a tu biblioteca para ver los módulos disponibles.</p>
    <?php elseif(!$isLogged): ?>
      <p class="lead">Para acceder a los módulos del curso debes iniciar sesión o crear una cuenta y completar el proceso de compra.</p>
    <?php else: ?>
      <p class="lead">Tu cuenta aún no tiene acceso al contenido premium del curso. Completa la compra para desbloquear videos, actualizaciones y recursos futuros.</p>
    <?php endif; ?>
    <div class="features">
      <div class="feature"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20v-6"/><path d="M6 20v-4"/><path d="M18 20v-8"/><path d="M2 11l10-7 10 7"/><path d="M2 11v11h20V11"/></svg> Acceso 24/7 a módulos en evolución</div>
      <div class="feature"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M8 21h8"/><path d="M12 17v4"/><path d="M7 4h10"/><path d="M17 4v8a5 5 0 0 1-10 0V4"/></svg> Base para proyectos de bonos de carbono</div>
      <div class="feature"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m12 14 4-4"/><path d="m12 8 4 4-4 4"/><path d="m8 8 4 4-4 4"/></svg> Actualizaciones incluidas sin costo extra</div>
      <div class="feature"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg> Soporte básico por email</div>
    </div>
    <div class="actions">
      <?php if(!$isLogged): ?>
        <a class="btn btn-primary" href="<?= url('register.php') ?>">Crear cuenta</a>
        <a class="btn btn-outline" href="<?= url('login.php') ?>">Iniciar sesión</a>
      <?php elseif($has): ?>
        <a class="btn btn-primary" href="<?= url('mis-videos.php') ?>">Ir a mis videos</a>
        <a class="btn btn-outline" href="<?= url() ?>">Inicio</a>
      <?php else: ?>
        <a class="btn btn-outline" href="<?= url() ?>">Inicio</a>
      <?php endif; ?>
    </div>

    <?php if($isLogged && !$has): ?>
      <div style="margin-top:40px">
        <h2 style="font-size:1.2rem;font-weight:600;margin:0 0 12px;color:#e2e8f0">Completa tu compra</h2>
        <p style="margin:0 0 16px;font-size:.85rem;color:#94a3b8">Al confirmar el pago se activará tu acceso automáticamente y serás redirigido a tus videos.</p>
        <div id="paypal-button-container" style="min-height:48px"></div>
      </div>
    <?php endif; ?>
    <footer>
      © <?= date('Y') ?> Pijije Regenerativo · Acceso restringido
    </footer>
  </div>
<?php if($isLogged && !$has): ?>
<script src="https://www.paypal.com/sdk/js?client-id=<?= urlencode($config['paypal']['client_id']) ?>&currency=MXN"></script>
<script>
(function(){
  const csrf = '<?= e(csrf_token()) ?>';
  if(!window.paypal) return;
  paypal.Buttons({
    style:{ shape:'rect', color:'gold', layout:'vertical' },
    createOrder(){
      return fetch('<?= url('checkout/create-order.php') ?>', {
        method:'POST', headers:{'Content-Type':'application/json'}, body:JSON.stringify({csrf})
      }).then(r=>r.json()).then(d=>{ if(d.error){ throw new Error(d.error); } return d.orderID; });
    },
    onApprove(data){
      return fetch('<?= url('checkout/capture-order.php') ?>', {
        method:'POST', headers:{'Content-Type':'application/json'}, body:JSON.stringify({orderID:data.orderID, csrf})
      }).then(r=>r.json()).then(d=>{
        if(!d.error){ window.location='<?= url('mis-videos.php') ?>'; }
        else { alert('Error al capturar pago: '+d.error); }
      }).catch(e=>alert('Error: '+e.message));
    },
    onError(err){ console.error(err); alert('Ocurrió un problema con el pago.'); }
  }).render('#paypal-button-container');
})();
</script>
<?php endif; ?>
</body>
</html>
