<?php
require_once __DIR__ . '/lib/bootstrap.php';
require_once __DIR__ . '/lib/access.php';

$config = config();

// Verificar autenticación (función en utils/security con nombre require_login quizá distinto)
if (function_exists('requireLogin')) {
    requireLogin();
} elseif (function_exists('require_login')) {
    require_login();
} else {
    if (!isLoggedIn()) { redirect('login.php'); }
}

// Dashboard unificado: si no hay acceso, mostramos paywall dentro de esta página
$userId = getCurrentUserId();
if (!isset($config['app']['course_id'])) {
    error_log('[MIS-VIDEOS] course_id no definido en config');
    $courseId = 1; // fallback
} else {
    $courseId = $config['app']['course_id'];
}
$hasAccess = function_exists('hasAccess') ? hasAccess($userId, $courseId) : false;
// Obtener expiración y último precio pagado (si existe)
$accessInfo = null; $lastOrder = null; $expiresAt = null; $lastPaid = null;
try {
    $db = getDB();
    $accessInfo = $db->fetchOne('SELECT granted_at, expires_at FROM user_access WHERE user_id = ? AND course_id = ?', [$userId, $courseId]);
    $lastOrder = $db->fetchOne('SELECT amount_mxn, status, created_at FROM orders WHERE user_id = ? AND provider = "paypal" AND status = "paid" ORDER BY id DESC LIMIT 1', [$userId]);
    if ($accessInfo) { $expiresAt = $accessInfo['expires_at']; }
    if ($lastOrder) { $lastPaid = $lastOrder['amount_mxn']; }
} catch (Throwable $e) { error_log('[MIS-VIDEOS] Info extra: '.$e->getMessage()); }

// Obtener videos solo si ya tiene acceso
$videos = [];
if ($hasAccess) {
    $db = getDB();
    $videos = $db->fetchAll(
        'SELECT id, title, description, drive_file_id, ord 
         FROM videos 
         WHERE course_id = ? 
         ORDER BY ord ASC',
        [$courseId]
    );
}

// Obtener información del usuario
$user = getCurrentUser();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mis Videos - <?= escape(courseTitle()) ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', sans-serif;
            background: #f8fafc;
            color: #2d3748;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }
        
        /* Header */
        .header {
            background: white;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            position: sticky;
            top: 0;
            z-index: 50;
        }
        
        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 16px 0;
        }
        
        .logo img {
            height: 48px;
            width: auto;
        }
        
        .user-info {
            display: flex;
            align-items: center;
            gap: 16px;
        }
        
        .welcome {
            text-align: right;
        }
        
        .welcome h3 {
            font-size: 16px;
            font-weight: 600;
            color: #2d3748;
            margin-bottom: 4px;
        }
        
        .welcome p {
            font-size: 14px;
            color: #718096;
        }
        
        .btn {
            display: inline-block;
            padding: 8px 16px;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s;
            border: none;
            cursor: pointer;
            font-size: 14px;
        }
        
        .btn-primary {
            background: #667eea;
            color: white;
        }
        
        .btn-primary:hover {
            background: #5a67d8;
        }
        
        .btn-outline {
            border: 1px solid #667eea;
            color: #667eea;
            background: transparent;
        }
        
        .btn-outline:hover {
            background: #667eea;
            color: white;
        }
        
        /* Main Content */
        .main-content {
            padding: 40px 0;
        }
        
        .page-header {
            text-align: center;
            margin-bottom: 40px;
        }
        
        .page-header h1 {
            font-size: 36px;
            font-weight: 700;
            color: #2d3748;
            margin-bottom: 16px;
        }
        
        .page-header p {
            font-size: 18px;
            color: #718096;
            max-width: 600px;
            margin: 0 auto;
        }
        
        /* Course Progress */
        .progress-section {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            padding: 24px;
            margin-bottom: 32px;
        }
        
        .progress-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 16px;
        }
        
        .progress-title {
            font-size: 18px;
            font-weight: 600;
            color: #2d3748;
        }
        
        .progress-stats {
            font-size: 14px;
            color: #718096;
        }
        
        .progress-bar {
            width: 100%;
            height: 8px;
            background: #e2e8f0;
            border-radius: 4px;
            overflow: hidden;
        }
        
        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #667eea, #764ba2);
            width: 0%; /* Se calculará dinámicamente */
            transition: width 0.3s ease;
        }
        
        /* Videos Grid */
        .videos-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 24px;
            margin-bottom: 40px;
        }
        .featured-player-section {margin-bottom:40px;display:grid;grid-template-columns:1fr 320px;gap:30px;align-items:flex-start}
        .featured-player {position:relative;width:100%;aspect-ratio:16/9;background:#0f172a;border-radius:18px;overflow:hidden;box-shadow:0 8px 28px -6px rgba(0,0,0,.35);transition:box-shadow .4s,transform .4s}
        .featured-player iframe {width:100%;height:100%;border:0;display:block;}
        .featured-player.fade {opacity:0;transform:scale(.985);}
        .featured-player.ready {opacity:1;transform:scale(1);transition:opacity .4s,transform .4s;}
        .featured-meta {background:#fff;border:1px solid #e2e8f0;padding:22px 24px;border-radius:16px;box-shadow:0 4px 16px -4px rgba(0,0,0,.15);display:flex;flex-direction:column;gap:14px;position:relative;}
        .featured-meta h2 {font-size:22px;line-height:1.25;margin:0;color:#1e293b;font-weight:700;letter-spacing:.3px;}
        .featured-meta p {font-size:14.5px;color:#475569;line-height:1.55;margin:0;white-space:pre-line;}
        .video-card {cursor:pointer;}
        .video-card.active {outline:3px solid #6366f1; outline-offset:0; box-shadow:0 0 0 4px rgba(99,102,241,.25);}        
        .video-card .video-header {position:relative;}
        .video-card .video-header:after {content:"Seleccionar";position:absolute;top:8px;right:10px;font-size:10px;background:rgba(255,255,255,.15);padding:3px 6px;border-radius:6px;letter-spacing:.5px;font-weight:500;}
        .video-card.active .video-header:after {content:"Actual";background:#10b981;}
        .video-wrapper {cursor:pointer;}
        .switcher-controls {display:flex;gap:10px;margin-top:8px;}
        .switcher-controls button {flex:1;background:#6366f1;color:#fff;border:0;padding:10px 14px;border-radius:10px;font-size:13px;font-weight:600;cursor:pointer;display:inline-flex;align-items:center;justify-content:center;gap:6px;box-shadow:0 3px 8px -2px rgba(99,102,241,.4);transition:background .25s,transform .25s;}
        .switcher-controls button:hover {background:#4f46e5;}
        .switcher-controls button:active {transform:scale(.96);}
        .switcher-controls button[disabled]{opacity:.45;cursor:not-allowed;box-shadow:none;}
        @media (max-width:1000px){.featured-player-section{grid-template-columns:1fr}}
        @media (max-width:640px){.featured-meta{order:-1}.featured-meta h2{font-size:19px}.switcher-controls{flex-wrap:wrap}}
        
        .video-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            transition: transform 0.3s, box-shadow 0.3s;
        }
        
        .video-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
        }
        
        .video-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 16px;
            text-align: center;
        }
        
        .video-number {
            font-size: 14px;
            font-weight: 600;
            opacity: 0.9;
            margin-bottom: 4px;
        }
        
        .video-title {
            font-size: 18px;
            font-weight: 700;
        }
        
        .video-content {
            padding: 20px;
        }
        
        .video-description {
            color: #718096;
            font-size: 14px;
            line-height: 1.6;
            margin-bottom: 16px;
        }
        
        .video-player {
            width: 100%;
            aspect-ratio: 16/9;
            border: none;
            border-radius: 8px;
            background: #f7fafc;
        }
        
        .video-fallback {
            margin-top: 12px;
            text-align: center;
        }
        
        .video-fallback a {
            color: #667eea;
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
        }
        
        .video-fallback a:hover {
            text-decoration: underline;
        }
        
        /* Completion Badge */
        .completion-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: #f0fff4;
            color: #22543d;
            padding: 8px 12px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 500;
            margin-top: 12px;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .header-content {
                flex-direction: column;
                gap: 16px;
            }
            
            .user-info {
                flex-direction: column;
                text-align: center;
            }
            
            .welcome {
                text-align: center;
            }
            
            .videos-grid {
                grid-template-columns: 1fr;
            }
            
            .page-header h1 {
                font-size: 28px;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header class="header">
        <div class="container">
            <div class="header-content">
                <div class="logo" style="display:flex;align-items:center;gap:12px;">
                    <img src="<?= asset('assets/images/pijije-logo.jpg') ?>" alt="Pijije Regenerativo" style="height:52px;width:auto;border-radius:8px;box-shadow:0 4px 10px -2px rgba(0,0,0,.25);background:#fff;padding:4px;">
                    <strong style="font-size:18px;color:#1e293b;letter-spacing:.5px;">Pijije Regenerativo</strong>
                </div>
                <div class="user-info">
                    <div class="welcome">
                        <h3>¡Bienvenido, <?= escape($user['name']) ?>!</h3>
                        <p><?= escape(courseTitle()) ?></p>
                    </div>
                    <a href="<?= url('logout.php') ?>" class="btn btn-outline">Cerrar Sesión</a>
                </div>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="main-content">
        <div class="container">
            <!-- Page Header -->
            <div class="page-header">
                <h1 style="margin-bottom:10px;">Panel del Curso</h1>
                <?php if(!$hasAccess): ?>
                    <div style="display:inline-block;background:#fef3c7;color:#92400e;padding:8px 14px;border-radius:10px;font-size:14px;font-weight:600;box-shadow:0 2px 4px rgba(0,0,0,.08);margin-bottom:14px;">Acceso al curso bloqueado</div>
                <?php endif; ?>
                <p style="max-width:760px;margin:0 auto;">
                    <?php if($hasAccess): ?>
                        Disfruta el contenido y continúa tu aprendizaje regenerativo.
                        <?php if($expiresAt): ?>
                            <br><strong style="color:#1e3a8a;">Tu acceso vence el <?= escape(formatDate($expiresAt,'d/m/Y H:i')) ?></strong>
                        <?php endif; ?>
                        <?php if($lastPaid): ?>
                            <br><span style="font-size:13px;color:#475569;">Precio pagado: <?= escape(formatPrice($lastPaid,$config['app']['currency']??'MXN')) ?></span>
                        <?php endif; ?>
                    <?php else: ?>
                        Necesitas completar el pago para desbloquear módulos, actualizaciones y recursos adicionales.
                        <br><span style="font-size:13px;color:#475569;">Precio actual: <?= escape(coursePriceFormatted()) ?></span>
                    <?php endif; ?>
                </p>
            </div>

            <?php if ($hasAccess): ?>
                <div class="progress-section">
                    <div class="progress-header">
                        <div class="progress-title">Progreso del Curso</div>
                        <div class="progress-stats">0 de <?= count($videos) ?> lecciones completadas</div>
                    </div>
                    <div class="progress-bar">
                        <div class="progress-fill" style="width: 0%"></div>
                    </div>
                </div>
            <?php endif; ?>

            <?php if ($hasAccess): ?>
                <?php if(count($videos)): $first=$videos[0]; ?>
                <section class="featured-player-section" id="featured-container">
                    <div class="featured-player ready" id="featured-player" data-current-id="<?= (int)$first['id'] ?>">
                        <iframe id="main-video-iframe" src="https://drive.google.com/file/d/<?= escape($first['drive_file_id']) ?>/preview" allow="autoplay; encrypted-media" allowfullscreen referrerpolicy="no-referrer" sandbox="allow-scripts allow-same-origin allow-presentation allow-popups"></iframe>
                        <div class="progress-overlay" style="position:absolute;left:0;bottom:0;height:5px;background:rgba(255,255,255,.12);width:100%;"><div class="progress-inline-main" style="height:100%;width:0;background:linear-gradient(90deg,#6366f1,#22d3ee);"></div></div>
                    </div>
                    <div class="featured-meta">
                        <h2 id="featured-title">Lección <?= (int)$first['ord'] ?> · <?= escape($first['title']) ?></h2>
                        <p id="featured-description"><?= escape($first['description'] ?? '') ?></p>
                        <div class="switcher-controls">
                            <button id="prev-video" disabled aria-label="Video anterior">◀ Anterior</button>
                            <button id="next-video" <?= count($videos)>1? '':'disabled' ?> aria-label="Próximo video">Siguiente ▶</button>
                            <button id="mark-complete" aria-label="Marcar completado" style="background:#10b981;">✅ Completar</button>
                        </div>
                    </div>
                </section>
                <?php endif; ?>
                <div class="videos-grid">
                    <?php foreach ($videos as $index => $video): ?>
                        <div class="video-card" data-video-id="<?= (int)$video['id'] ?>" data-drive-id="<?= escape($video['drive_file_id']) ?>" data-ord="<?= (int)$video['ord'] ?>" data-title="<?= escape($video['title']) ?>" data-description="<?= escape($video['description'] ?? '') ?>">
                            <div class="video-header">
                                <div class="video-number">Lección <?= $video['ord'] ?></div>
                                <div class="video-title"><?= escape($video['title']) ?></div>
                            </div>
                            <div class="video-content">
                                <?php if (!empty($video['description'])): ?>
                                    <div class="video-description">
                                        <?= escape($video['description']) ?>
                                    </div>
                                <?php endif; ?>
                <div class="video-wrapper" style="position:relative;aspect-ratio:16/9;background:linear-gradient(135deg,#312e81,#1e3a8a);border-radius:8px;overflow:hidden;display:flex;align-items:center;justify-content:center;color:#fff;font-weight:600;font-size:14px;letter-spacing:.5px;">
                    <span style="display:inline-flex;align-items:center;gap:6px;background:rgba(255,255,255,.12);padding:10px 14px;border-radius:30px;backdrop-filter:blur(4px);">▶ Ver grande</span>
                    <div class="progress-overlay" style="position:absolute;left:0;bottom:0;height:4px;background:rgba(255,255,255,.18);width:100%;"><div class="progress-inline" style="height:100%;width:0;background:linear-gradient(90deg,#6366f1,#22d3ee);"></div></div>
                </div>
                                <div class="video-fallback">
                                    <p>Si no puedes ver el video, 
                                        <a href="https://drive.google.com/file/d/<?= escape($video['drive_file_id']) ?>/view" 
                                           target="_blank" rel="noopener">
                                            ábrelo directamente en Google Drive
                                        </a>
                                    </p>
                                </div>
                                <div class="completion-badge" style="display: none;">
                                    <span>✅</span>
                                    <span>Completado</span>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                                                <script nonce="<?= e(csp_nonce()) ?>">
                                                (function(){
                                                    const cards = Array.from(document.querySelectorAll('.video-card'));
                                                    const featuredPlayer = document.getElementById('featured-player');
                                                    const iframe = document.getElementById('main-video-iframe');
                                                    const fTitle = document.getElementById('featured-title');
                                                    const fDesc = document.getElementById('featured-description');
                                                    const prevBtn = document.getElementById('prev-video');
                                                    const nextBtn = document.getElementById('next-video');
                                                    const completeBtn = document.getElementById('mark-complete');
                                                    const globalFill = document.querySelector('.progress-fill');
                                                    const stats = document.querySelector('.progress-stats');
                                                    const mainInline = document.querySelector('.progress-inline-main');
                                                    const total = cards.length; let completed = 0;
                                                    const ESTIMATED = 600;
                                                    const state = {}; // videoId -> {seconds,duration,completed}
                                                    const completedSet = new Set(); // IDs de videos completados localmente
                                                    let currentId = featuredPlayer ? parseInt(featuredPlayer.dataset.currentId,10): null;
                                                    let timer = null;
                                                    async function preloadProgress(){
                                                        try{
                                                            const res = await fetch('<?= url('api/get_progress.php') ?>');
                                                            if(!res.ok) return;
                                                            const data = await res.json();
                                                            if(!data || !Array.isArray(data.items)) return;
                                                            data.items.forEach(it=>{
                                                                const vid = parseInt(it.video_id,10);
                                                                if(!vid) return;
                                                                state[vid] = {seconds:it.seconds||0,duration:it.duration||ESTIMATED,completed:!!it.completed_at};
                                                                if(it.completed_at){
                                                                    completedSet.add(vid);
                                                                    const card=document.querySelector('.video-card[data-video-id="'+vid+'"]');
                                                                    if(card){
                                                                        const badge=card.querySelector('.completion-badge'); if(badge) badge.style.display='inline-flex';
                                                                        const bar=card.querySelector('.progress-inline'); if(bar) bar.style.width='100%';
                                                                    }
                                                                } else {
                                                                    const pct = (it.seconds && it.duration) ? Math.min(100,(it.seconds/it.duration)*100):0;
                                                                    if(pct>0){
                                                                        const card=document.querySelector('.video-card[data-video-id="'+vid+'"]');
                                                                        if(card){ const bar=card.querySelector('.progress-inline'); if(bar) bar.style.width=pct+'%'; }
                                                                    }
                                                                }
                                                            });
                                                            completed = completedSet.size;
                                                            updateGlobal();
                                                        }catch(e){}
                                                    }
                                                    function updateNav(){ const idx = cards.findIndex(c=>parseInt(c.dataset.videoId,10)===currentId); if(prevBtn) prevBtn.disabled = idx<=0; if(nextBtn) nextBtn.disabled = (idx===cards.length-1 || idx<0); }
                                                    function updateGlobal(){ if(!globalFill||!stats) return; const pct= total? Math.round((completed/total)*100):0; globalFill.style.width=pct+'%'; stats.textContent=completed+' de '+total+' lecciones completadas'; }
                                                    async function saveProgress(videoId){ const s=state[videoId]; if(!s)return; try{ const res=await fetch('<?= url('api/save_progress.php') ?>',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({video_id:videoId,seconds:Math.floor(s.seconds),duration:Math.floor(s.duration||ESTIMATED)})}); if(!res.ok)return; const data=await res.json(); if(data.completed!==undefined){ const serverCount=parseInt(data.completed,10); if(!isNaN(serverCount)){ const bounded=Math.min(serverCount,total); if(bounded >= completed && bounded <= total){ completed = bounded; } } updateGlobal(); }}catch(e){} }
                                                    function tick(){ if(!currentId) return; const s= state[currentId] || {seconds:0,duration:ESTIMATED,completed:false}; if(s.completed) return; s.seconds+=5; if(!s.duration) s.duration=ESTIMATED; const pct=Math.min(100,(s.seconds/s.duration)*100); if(mainInline) mainInline.style.width=pct+'%'; if(s.seconds >= s.duration-5){ s.completed=true; markCardCompleted(currentId); } state[currentId]=s; saveProgress(currentId); }
                                                    function stopTimer(){ if(timer){ clearInterval(timer); timer=null; } }
                                                    function startTimer(){ stopTimer(); timer=setInterval(tick,5000); }
                                                    function markCardCompleted(id){
                                                        if(!id) return;
                                                        if(!completedSet.has(id)){
                                                            completedSet.add(id);
                                                            completed = completedSet.size; // recuento incremental confiable
                                                        }
                                                        const card=document.querySelector('.video-card[data-video-id="'+id+'"]');
                                                        if(card){
                                                            const badge=card.querySelector('.completion-badge');
                                                            if(badge) badge.style.display='inline-flex';
                                                            const bar=card.querySelector('.progress-inline');
                                                            if(bar) bar.style.width='100%';
                                                        }
                                                        updateGlobal();
                                                    }
                                                    function selectCard(card, scroll){ if(!card) return; const id=parseInt(card.dataset.videoId,10); if(id===currentId) return; featuredPlayer.classList.add('fade'); setTimeout(()=>{ currentId=id; iframe.src='https://drive.google.com/file/d/'+card.dataset.driveId+'/preview'; fTitle.textContent='Lección '+card.dataset.ord+' · '+card.dataset.title; fDesc.textContent=card.dataset.description||''; cards.forEach(c=>c.classList.remove('active')); card.classList.add('active'); const s=state[id]; mainInline.style.width=(s && s.seconds && s.duration? Math.min(100,(s.seconds/s.duration)*100):0)+'%'; featuredPlayer.dataset.currentId=id; featuredPlayer.classList.remove('fade'); featuredPlayer.classList.add('ready'); updateNav(); if(scroll && window.innerWidth<900){ featuredPlayer.scrollIntoView({behavior:'smooth',block:'start'}); } startTimer(); },220); }
                                                    cards.forEach(c=>c.addEventListener('click', ()=>selectCard(c,true)) );
                                                    if(cards.length){ cards[0].classList.add('active'); preloadProgress().finally(()=>startTimer()); }
                                                    prevBtn && prevBtn.addEventListener('click', ()=>{ const idx=cards.findIndex(c=>parseInt(c.dataset.videoId,10)===currentId); if(idx>0) selectCard(cards[idx-1],true); });
                                                    nextBtn && nextBtn.addEventListener('click', ()=>{ const idx=cards.findIndex(c=>parseInt(c.dataset.videoId,10)===currentId); if(idx>=0 && idx<cards.length-1) selectCard(cards[idx+1],true); });
                                                    completeBtn && completeBtn.addEventListener('click', ()=>{ const s= state[currentId] || {seconds:0,duration:ESTIMATED,completed:false}; s.seconds=s.duration; s.completed=true; state[currentId]=s; markCardCompleted(currentId); saveProgress(currentId); });
                                                    window.addEventListener('beforeunload', ()=>{ if(currentId && state[currentId]) saveProgress(currentId); });
                                                    document.addEventListener('visibilitychange', ()=>{ if(document.hidden) stopTimer(); else startTimer(); });
                                                    updateNav(); updateGlobal();
                                                })();
                                                </script>
            <?php else: ?>
                <div style="background:linear-gradient(135deg,#ffffff,#f1f5f9);border:1px solid #e2e8f0;border-radius:18px;padding:50px 42px;margin:30px 0;text-align:center;position:relative;overflow:hidden;">
                    <div style="position:absolute;inset:0;pointer-events:none;background:radial-gradient(circle at 15% 20%,rgba(99,102,241,.12),transparent 60%),radial-gradient(circle at 85% 75%,rgba(56,189,248,.15),transparent 65%);"></div>
                    <h2 style="font-size:26px;font-weight:700;margin:0 0 14px;color:#1e293b;letter-spacing:.5px;">Desbloquea tu Acceso</h2>
                    <p style="max-width:620px;margin:0 auto 26px;color:#475569;font-size:15.5px;line-height:1.55;">Realiza un único pago para acceder inmediatamente a todas las lecciones actuales, futuras ampliaciones del contenido y recursos adicionales exclusivos.</p>
                    <ul style="list-style:none;display:grid;grid-template-columns:repeat(auto-fit,minmax(240px,1fr));gap:12px;max-width:860px;margin:0 auto 30px;padding:0;">
                        <li style="background:#fff;border:1px solid #e2e8f0;padding:12px 14px;border-radius:12px;font-size:13px;display:flex;align-items:flex-start;gap:8px;color:#334155;">
                            <span style="color:#6366f1;font-weight:600;">✔</span> Acceso 24/7 y actualizaciones
                        </li>
                        <li style="background:#fff;border:1px solid #e2e8f0;padding:12px 14px;border-radius:12px;font-size:13px;display:flex;align-items:flex-start;gap:8px;color:#334155;">
                            <span style="color:#6366f1;font-weight:600;">✔</span> Base para proyectos de bonos de carbono
                        </li>
                        <li style="background:#fff;border:1px solid #e2e8f0;padding:12px 14px;border-radius:12px;font-size:13px;display:flex;align-items:flex-start;gap:8px;color:#334155;">
                            <span style="color:#6366f1;font-weight:600;">✔</span> +8 horas estructuradas y en expansión
                        </li>
                        <li style="background:#fff;border:1px solid #e2e8f0;padding:12px 14px;border-radius:12px;font-size:13px;display:flex;align-items:flex-start;gap:8px;color:#334155;">
                            <span style="color:#6366f1;font-weight:600;">✔</span> Soporte básico por email
                        </li>
                    </ul>
                    <div style="display:flex;flex-direction:column;align-items:center;gap:14px;position:relative;z-index:2;">
                        <div id="paypal-button-container" style="min-height:48px;width:100%;max-width:380px;"></div>
                        <small style="color:#64748b;font-size:12px;">Pago seguro vía PayPal. Acceso inmediato al confirmarse.</small>
                    </div>
                </div>
            <?php endif; ?>

            <?php if ($hasAccess): ?>
                <div style="background: white; border-radius: 12px; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1); padding: 24px; text-align: center;">
                    <h3 style="font-size: 20px; font-weight: 600; color: #2d3748; margin-bottom: 16px;">📚 Recursos Adicionales</h3>
                    <p style="color: #718096; margin-bottom: 16px;">Únete a nuestro grupo de Facebook para resolver dudas y compartir experiencias con otros productores.</p>
                    <a href="https://facebook.com/groups/manejo-holistico-tropical" target="_blank" rel="noopener" class="btn btn-primary">Únete al Grupo de Facebook</a>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <?php if(!$hasAccess): ?>
    <style nonce="<?= e(csp_nonce()) ?>">.pp-skeleton{display:flex;align-items:center;justify-content:center;height:52px;border:1px solid #cbd5e1;background:linear-gradient(90deg,#f1f5f9,#e2e8f0,#f1f5f9);background-size:200% 100%;animation:ppShimmer 1.2s linear infinite;border-radius:10px;font-size:13px;color:#475569;font-weight:500}.pp-error{background:#fee2e2;border:1px solid #fecaca;color:#991b1b;padding:10px 12px;border-radius:10px;font-size:13px;margin-top:6px;display:none}.pp-retry{margin-left:8px;background:#1e3a8a;color:#fff;border:none;padding:6px 10px;border-radius:6px;cursor:pointer;font-size:12px;font-weight:600}.pp-retry:disabled{opacity:.5;cursor:not-allowed}@keyframes ppShimmer{0%{background-position:200% 0}100%{background-position:-200% 0}}</style>
    <div id="paypal-sdk-mount"></div>
    <script nonce="<?= getNonce() ?>">
    (function(){
        const csrf = '<?= e(csrf_token()) ?>';
        const clientId = "<?= e($config['paypal']['client_id']) ?>";
        if(!clientId){
            const c = document.getElementById('paypal-button-container');
            if(c){ c.textContent='Configurar PayPal (client_id vacío)'; }
            return;
        }
        const sdkUrl = "https://www.paypal.com/sdk/js?client-id="+encodeURIComponent(clientId)+"&currency=MXN&intent=capture";
        const container = document.getElementById('paypal-button-container');
        container.classList.add('pp-skeleton');
        const errorBox = document.createElement('div');
        errorBox.className='pp-error';
        errorBox.innerHTML='No se pudo cargar PayPal <button class="pp-retry">Reintentar</button>';
        container.parentNode.appendChild(errorBox);
        const retryBtn = errorBox.querySelector('.pp-retry');
        let loaded=false; let attempts=0; const MAX=3;

        function setErr(msg){ container.style.display='none'; errorBox.style.display='block'; errorBox.firstChild.textContent=msg+' '; }
        function clearErr(){ errorBox.style.display='none'; container.style.display='block'; }
        function mount(){ if(!window.paypal) return; try { window.paypal.Buttons({
            createOrder:()=>fetch('<?= url('checkout/create-order.php') ?>',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({csrf})}).then(r=>r.json()).then(d=>{ if(d.error||!d.orderID) throw new Error(d.error||'Sin orderID'); return d.orderID; }),
            onApprove:(data)=>fetch('<?= url('checkout/capture-order.php') ?>',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({orderID:data.orderID,csrf})}).then(r=>r.json()).then(d=>{ if(d.error){ alert('Error: '+d.error);} else { window.location.reload(); } }),
            onError:(err)=>{ console.error('[PayPal]',err); alert('Error de PayPal: '+(err?.message||err)); }
        }).render('#paypal-button-container'); container.classList.remove('pp-skeleton'); } catch(e){ console.error(e); setErr('Fallo al inicializar.'); }}
    function inject(){ if(loaded) return; attempts++; clearErr(); const s=document.createElement('script'); s.src=sdkUrl; s.onload=function(){loaded=true; setTimeout(mount,30);} ; s.onerror=function(){ if(attempts<MAX){ setTimeout(inject,1000*attempts);} else { setErr('No se pudo cargar PayPal.'); }}; document.getElementById('paypal-sdk-mount').appendChild(s); }
        fetch('<?= url('checkout/warmup.php') ?>').catch(()=>{}).finally(()=>inject());
        retryBtn.addEventListener('click', function(){ inject(); });
        setTimeout(function(){ if(!loaded){ setErr('Demora inusual cargando PayPal.'); } }, 8000);
    })();
        // Función para marcar videos como vistos (opcional)
        function markVideoAsWatched(videoId) {
            // Aquí se podría implementar tracking de progreso
            // Por ahora solo mostramos visualmente
            const badge = document.querySelector(`[data-video-id="${videoId}"] .completion-badge`);
            if (badge) {
                badge.style.display = 'flex';
            }
        }
        
        // Tracking básico de reproducción
        document.addEventListener('DOMContentLoaded', function() {
            const iframes = document.querySelectorAll('.video-player');
            iframes.forEach((iframe, index) => {
                iframe.addEventListener('load', function() {
                    console.log(`Video ${index + 1} loaded`);
                });
            });
        });
    </script>
    <?php endif; ?>
</body>
</html>
