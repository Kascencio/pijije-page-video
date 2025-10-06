<?php
require_once __DIR__ . '/../lib/bootstrap.php';
require_once __DIR__ . '/../lib/access.php';
$config = config();
if (!isLoggedIn()) { setFlash('Inicia sesión para continuar al pago','warning'); redirect('login.php'); }
$courseId = $config['app']['course_id'];
$has = hasAccess(getCurrentUserId(), $courseId);
$priceCents = coursePriceCents();
$price = number_format($priceCents/100,2,'.','');
?>
<!DOCTYPE html><html lang="es"><head><meta charset="utf-8" />
<title>Checkout - Acceso al Curso</title>
<meta name="viewport" content="width=device-width,initial-scale=1" />
<link rel="preconnect" href="https://fonts.googleapis.com" />
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet" />
<link rel="stylesheet" href="<?= asset('assets/css/app.css') ?>" />
<style>
body{font-family:'Inter',sans-serif;background:linear-gradient(135deg,#0f172a,#1e293b);color:#f1f5f9;min-height:100vh;display:flex;align-items:center;justify-content:center;padding:40px;}
.card{max-width:800px;width:100%;background:#0f1e2e;border:1px solid rgba(255,255,255,.08);border-radius:28px;padding:48px 50px;box-shadow:0 24px 48px -12px rgba(0,0,0,.55);position:relative;overflow:hidden;}
.card:before{content:'';position:absolute;inset:0;background:radial-gradient(circle at 25% 20%,rgba(99,102,241,.18),transparent 60%),radial-gradient(circle at 85% 70%,rgba(56,189,248,.18),transparent 60%);} 
.badge{display:inline-block;padding:4px 10px;font-size:.65rem;font-weight:600;letter-spacing:.06em;border-radius:999px;background:linear-gradient(90deg,#6366f1,#3b82f6);color:#fff;margin-bottom:18px;}
h1{margin:0 0 10px;font-size:clamp(1.9rem,3vw,2.6rem);background:linear-gradient(90deg,#60a5fa,#818cf8,#a78bfa);-webkit-background-clip:text;background-clip:text;color:transparent;font-weight:700;}
.lead{font-size:1.05rem;line-height:1.55;color:#cbd5e1;margin:0 0 28px;max-width:640px;}
.price-box{display:flex;align-items:flex-end;gap:14px;margin:10px 0 32px;}
.price{font-size:2.6rem;font-weight:700;line-height:1;background:linear-gradient(90deg,#818cf8,#6366f1);-webkit-background-clip:text;background-clip:text;color:transparent;}
.price small{font-size:.9rem;font-weight:500;color:#94a3b8;background:none;-webkit-text-fill-color:currentColor;}
.features{display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:14px;margin:34px 0;}
.feature{background:#132435;padding:14px 16px;border:1px solid rgba(255,255,255,.07);border-radius:14px;font-size:.78rem;line-height:1.35;color:#cbd5e1;display:flex;gap:10px;align-items:flex-start;}
.feature svg{width:18px;height:18px;color:#60a5fa;flex-shrink:0;margin-top:2px;}
.separator{height:1px;background:linear-gradient(90deg,rgba(255,255,255,0),rgba(255,255,255,.15),rgba(255,255,255,0));margin:36px 0;}
#paypal-button-container{min-height:42px;}
.note{font-size:.7rem;color:#64748b;margin-top:24px;}
.actions{margin-top:30px;display:flex;flex-wrap:wrap;gap:14px;}
.btn-inline{display:inline-flex;align-items:center;padding:10px 18px;border-radius:10px;font-size:.8rem;font-weight:600;text-decoration:none;border:1px solid rgba(255,255,255,.15);color:#cbd5e1;background:rgba(255,255,255,.05);transition:.25s;} .btn-inline:hover{background:rgba(255,255,255,.12);color:#fff;}
.alert{padding:14px 18px;border-radius:14px;font-size:.8rem;margin:0 0 24px;background:#1e3a8a;color:#fff;border:1px solid rgba(255,255,255,.15);}
.success{background:#065f46;}
</style>
</head>
<body>
  <div class="card">
    <span class="badge">CHECKOUT</span>
    <h1>Acceso al Curso</h1>
    <?php if($has): ?>
      <div class="alert success">Ya tienes acceso. Ve directo a tus <a style="color:#93c5fd;text-decoration:underline" href="<?= url('mis-videos.php') ?>">videos</a>.</div>
    <?php endif; ?>
    <p class="lead">Obtén acceso completo a todos los módulos, futuras actualizaciones y base para integrarte a proyectos reales de bonos de carbono y biodiversidad.</p>
    <div class="price-box">
      <div class="price">$<?= $price ?> <small>MXN pago único</small></div>
    </div>
    <div class="features">
      <div class="feature"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20v-6"/><path d="M6 20v-4"/><path d="M18 20v-8"/><path d="M2 11l10-7 10 7"/><path d="M2 11v11h20V11"/></svg> Acceso 24/7 y actualizaciones
      </div>
      <div class="feature"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg> Base para proyectos de carbono
      </div>
      <div class="feature"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10" /><path d="m16 12-4-4"/><path d="m12 16 4-4-4-4"/><path d="M8 12h.01"/></svg> +8 horas estructuradas
      </div>
      <div class="feature"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 3v18h18" /><path d="m19 9-5 5-4-4-3 3"/></svg> Soporte básico por email
      </div>
    </div>
    <div class="separator"></div>
    <?php if(!$has): ?>
      <div id="paypal-button-container"></div>
    <?php endif; ?>
    <div class="actions">
      <a class="btn-inline" href="<?= url() ?>">← Volver al inicio</a>
      <?php if($has): ?><a class="btn-inline" href="<?= url('mis-videos.php') ?>">Ir a mis videos</a><?php endif; ?>
    </div>
    <div class="note">Al completar el pago tu acceso se activa automáticamente. Se aplican términos básicos internos.</div>
  </div>
<script src="https://www.paypal.com/sdk/js?client-id=<?= urlencode($config['paypal']['client_id']) ?>&currency=MXN"></script>
<script>
(function(){
  const has = <?= $has? 'true':'false' ?>;
  if(has) return;
  const csrf = '<?= e(csrf_token()) ?>';
  paypal.Buttons({
    createOrder: function(data, actions){
      return fetch('<?= url('checkout/create-order.php') ?>', {
        method:'POST', headers:{'Content-Type':'application/json'}, body:JSON.stringify({csrf})
      }).then(r=>r.json()).then(d=>d.orderID);
    },
    onApprove: function(data, actions){
      return fetch('<?= url('checkout/capture-order.php') ?>', {
        method:'POST', headers:{'Content-Type':'application/json'}, body:JSON.stringify({orderID:data.orderID, csrf})
      }).then(r=>r.json()).then(d=>{
        if(d && !d.error){
          window.location='<?= url('mis-videos.php') ?>';
        } else {
          alert('No se pudo completar el pago.');
        }
      });
    }
  }).render('#paypal-button-container');
})();
</script>
</body></html>