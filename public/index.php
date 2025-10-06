<?php
require_once __DIR__ . '/lib/bootstrap.php';
require_once __DIR__ . '/lib/access.php';

$config = config(); // usar helper centralizado (evita problemas de require_once boolean)
$priceCents = coursePriceCents();
$priceIntDisplay = (int) floor($priceCents / 100); // para zonas donde se mostraba sin decimales
$priceFormatted = coursePriceFormatted();
$isLoggedIn = isLoggedIn();
$hasAccess = false;
$flash = getFlash();
// Ocultar mensaje de acceso denegado aquí; se maneja en página dedicada
if ($flash && isset($flash['message']) && stripos($flash['message'], 'No tienes acceso a este curso') === 0) {
    $flash = null; // suprimirlo en landing
}

// Galería de imágenes (Tailwind) - definir antes de la vista
$galleryImages = [
    ['src' => 'assets/images/galeria/evento-1.jpg',  'alt' => 'Evento Ganadero 1'],
    ['src' => 'assets/images/galeria/evento-2.jpg',  'alt' => 'Evento Ganadero 2'],
    ['src' => 'assets/images/galeria/evento-3.jpg',  'alt' => 'Evento Ganadero 3'],
    ['src' => 'assets/images/galeria/evento-4.jpg',  'alt' => 'Evento Ganadero 4'],
    ['src' => 'assets/images/galeria/evento-5.jpg',  'alt' => 'Evento Ganadero 5'],
    ['src' => 'assets/images/galeria/evento-6.jpg',  'alt' => 'Evento Ganadero 6'],
    ['src' => 'assets/images/galeria/evento-7.jpg',  'alt' => 'Evento Ganadero 7'],
    ['src' => 'assets/images/galeria/evento-8.jpg',  'alt' => 'Evento Ganadero 8'],
    ['src' => 'assets/images/galeria/evento-9.jpg',  'alt' => 'Evento Ganadero 9'],
    ['src' => 'assets/images/galeria/evento-10.jpeg', 'alt' => 'Evento Ganadero 10'],
    ['src' => 'assets/images/galeria/evento-11.jpg', 'alt' => 'Evento Ganadero 11'],
    ['src' => 'assets/images/galeria/evento-12.jpg', 'alt' => 'Evento Ganadero 12'],
    ['src' => 'assets/images/galeria/evento-13.jpg', 'alt' => 'Evento Ganadero 13'],
];

// Verificar si el usuario tiene acceso al curso
if ($isLoggedIn) {
    if (function_exists('hasAccess')) {
        $hasAccess = hasAccess(getCurrentUserId(), $config['app']['course_id']);
    } else {
        $hasAccess = false; // fallback seguro
        error_log('[INDEX] hasAccess() no disponible al renderizar');
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?= escape(courseTitle()) ?> - Plataforma de Formación</title>
    <meta name="description" content="<?= escape(courseDescription() ?: 'Accede al curso y contenido especializado.') ?>" />
    <meta name="keywords" content="ganaderia regenerativa, bonos de carbono, curso ganaderos, pijije regenerativo, organicos del tropico, pastoreo regenerativo, genetica holistica, agricultura regenerativa" />
    <meta name="author" content="Pijije Regenerativo" />
    <meta property="og:type" content="website" />
    <meta property="og:title" content="<?= escape(courseTitle()) ?>" />
    <meta property="og:description" content="Capacitación y gestión de proyectos para bonos de carbono y biodiversidad. Aprende manejo holístico y pastoreo regenerativo." />
    <meta property="og:image" content="<?= asset('assets/curso-flyer.jpeg') ?>" />
    <meta property="og:locale" content="es_MX" />
    <link rel="icon" type="image/png" href="<?= asset('assets/images/pijije-logo.jpg') ?>" />
    <link rel="canonical" href="<?= url('cursos') ?>" />
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet" />
    <style>/* Fallback si falla Google Fonts */ body{font-family:'Inter',system-ui,-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,'Helvetica Neue',Arial,sans-serif;} </style>
        <!-- Tailwind Config + CDN con tokens HSL variables -->
        <script nonce="<?= e(csp_nonce()) ?>">(function(){
            window.tailwind = window.tailwind || {};
            tailwind.config = {
                theme: {
                    extend: {
                        colors: {
                            background: "hsl(var(--background))",
                            foreground: "hsl(var(--foreground))",
                            card: { DEFAULT: "hsl(var(--card))", foreground: "hsl(var(--card-foreground))" },
                            popover: { DEFAULT: "hsl(var(--popover))", foreground: "hsl(var(--popover-foreground))" },
                            primary: { DEFAULT: "hsl(var(--primary))", foreground: "hsl(var(--primary-foreground))" },
                            secondary: { DEFAULT: "hsl(var(--secondary))", foreground: "hsl(var(--secondary-foreground))" },
                            accent: { DEFAULT: "hsl(var(--accent))", foreground: "hsl(var(--accent-foreground))" },
                            muted: { DEFAULT: "hsl(var(--muted))", foreground: "hsl(var(--muted-foreground))" },
                            destructive: { DEFAULT: "hsl(var(--destructive))", foreground: "hsl(var(--destructive-foreground))" },
                            border: "hsl(var(--border))",
                            input: "hsl(var(--input))",
                            ring: "hsl(var(--ring))",
                            sidebar: { DEFAULT: "hsl(var(--sidebar))", foreground: "hsl(var(--sidebar-foreground))" }
                        },
                        fontFamily: {
                            sans: ['Inter','ui-sans-serif','system-ui','-apple-system','Segoe UI','Roboto','Helvetica Neue','Arial','sans-serif']
                        },
                        borderRadius: {
                            DEFAULT: 'var(--radius)',
                            lg: 'calc(var(--radius) + 4px)',
                            md: 'calc(var(--radius) - 2px)',
                            sm: 'calc(var(--radius) - 4px)'
                        }
                    }
                }
            };})();</script>
    <script src="https://cdn.tailwindcss.com" nonce="<?= e(csp_nonce()) ?>"></script>
    <!-- Fin Tailwind CDN -->
        <link rel="stylesheet" href="<?= asset('assets/css/app.css') ?>?v=<?= time() ?>" />
    <script type="application/ld+json">
    <?php $priceValue = number_format($priceCents / 100, 2, '.', ''); echo json_encode([
        '@context'=>'https://schema.org',
        '@type'=>'Course',
    'name'=> courseTitle(),
        'description'=>'Formación completa sobre manejo holístico, pastoreo regenerativo y preparación para proyectos de bonos de carbono.',
        'provider'=>['@type'=>'Organization','name'=>'Pijije Regenerativo','url'=>url('cursos')],
        'hasCourseInstance'=>[
            '@type'=>'CourseInstance',
            'courseMode'=>'online',
            'inLanguage'=>'es',
            'offers'=>[
                '@type'=>'Offer',
                'price'=>$priceValue,
                'priceCurrency'=>'MXN',
                'availability'=>'https://schema.org/InStock',
                'url'=>url('cursos')
            ]
        ]
    ], JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES); ?>
    </script>
    <link rel="preload" as="image" href="<?= asset('assets/lush-green-pasture-with-cattle-grazing-sustainable.jpg') ?>" />
</head>
<body class="antialiased bg-background">
    <a href="#hero" class="sr-only focus:not-sr-only focus:absolute focus:top-2 focus:left-2 z-50 bg-primary text-white px-4 py-2 rounded">Saltar al contenido</a>
    <?php if ($flash): ?>
        <?php $type = htmlspecialchars($flash['type']); $msg = escape($flash['message']); ?>
        <div class="w-full bg-<?php echo $type==='error'?'red':'green';?>-600/10 border-b border-<?php echo $type==='error'?'red':'green';?>-600/30 text-sm">
            <div class="max-w-6xl mx-auto px-4 py-3 text-<?php echo $type==='error'?'red':'green';?>-800 flex items-center gap-2">
                <span class="font-semibold uppercase tracking-wide"><?= strtoupper($type) ?>:</span> <span><?= $msg ?></span>
            </div>
        </div>
    <?php endif; ?>

    <?php include __DIR__ . '/sections/topbar.php'; ?>
    <?php include __DIR__ . '/sections/header.php'; ?>

    <main id="mainContent">
        <!-- Hero -->
    <section id="hero" class="relative min-h-[78vh] md:min-h-[80vh] flex items-center justify-center overflow-hidden pt-10 sm:pt-0">
            <!-- Overlay gradiente intensificado para igualar diseño Next -->
            <div class="absolute inset-0 bg-gradient-to-br from-primary/30 via-secondary/20 to-accent/30"></div>
            <img src="<?= asset('assets/lush-green-pasture-with-cattle-grazing-sustainable.jpg') ?>" alt="Ganadería regenerativa" class="absolute inset-0 w-full h-full object-cover opacity-10" />
            <div class="relative z-10 w-full">
                <div class="max-w-6xl mx-auto px-4 grid grid-cols-1 lg:grid-cols-2 gap-12 items-center">
                    <!-- Col izquierda -->
                    <div class="text-center lg:text-left space-y-6 md:space-y-8">
                        <div class="space-y-4">
                            <span class="inline-block bg-secondary text-secondary-foreground text-xs sm:text-sm px-3 sm:px-4 py-1.5 sm:py-2 rounded-full shadow animate-fadeIn tracking-wide">🌱 Educación Especializada</span>
                            <h1 class="font-bold text-balance text-4xl sm:text-5xl xl:text-7xl tracking-tight text-foreground leading-tight">Plataforma de <span class="text-primary">Cursos</span></h1>
                            <h2 class="text-2xl sm:text-3xl lg:text-4xl font-semibold text-secondary">Pijije Regenerativo</h2>
                            <p class="text-lg sm:text-xl text-muted-foreground max-w-2xl mx-auto lg:mx-0 leading-relaxed"><?= escape(courseDescription() ?: 'Formación especializada con acceso completo al contenido.') ?></p>
                        </div>
                        <div class="flex flex-col sm:flex-row items-center gap-3 sm:gap-4 sm:justify-start justify-center mt-1 sm:mt-2">
                            <?php if ($hasAccess): ?>
                                <a href="<?= url('mis-videos.php') ?>" class="px-8 py-6 rounded-xl bg-primary text-white hover:bg-primary/90 text-lg font-semibold shadow-lg hover:shadow-xl transition flex items-center justify-center">✅ Ya tienes acceso</a>
                            <?php elseif ($isLoggedIn): ?>
                                <a href="#comprar" class="inline-flex px-7 py-5 rounded-xl bg-primary text-primary-foreground hover:bg-primary/90 text-lg font-semibold shadow-lg hover:shadow-xl transition items-center justify-center gap-2">Comprar Curso
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/></svg>
                                </a>
                                <a href="#que-hacemos" class="inline-flex px-7 py-5 rounded-xl bg-white/85 text-primary font-semibold text-lg shadow-md hover:bg-white hover:text-primary transition items-center justify-center backdrop-blur-sm focus:outline-none focus:ring-2 focus:ring-primary/40 focus:ring-offset-2 focus:ring-offset-transparent">Ver Contenido</a>
                            <?php else: ?>
                                <a href="<?= url('register.php') ?>" class="inline-flex px-7 py-5 rounded-xl bg-primary text-primary-foreground hover:bg-primary/90 text-lg font-semibold shadow-lg hover:shadow-xl transition items-center justify-center gap-2">Comenzar Ahora
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/></svg>
                                </a>
                                <a href="#que-hacemos" class="inline-flex px-7 py-5 rounded-xl bg-white/85 text-primary font-semibold text-lg shadow-md hover:bg-white hover:text-primary transition items-center justify-center backdrop-blur-sm focus:outline-none focus:ring-2 focus:ring-primary/40 focus:ring-offset-2 focus:ring-offset-transparent">Ver Contenido</a>
                            <?php endif; ?>
                        </div>
                    </div>
                    <!-- Col derecha -->
                    <div class="animate-slideInRight">
                        <div class="rounded-2xl overflow-hidden border-2 border-primary/20 shadow-2xl bg-card/80 backdrop-blur-sm flex flex-col">
                            <div class="relative h-80">
                                <img src="<?= asset('assets/images/curso-flyer.jpeg') ?>" alt="Curso-Taller Ganadería Regenerativa y bonos de carbono" class="absolute inset-0 w-full h-full object-cover" />
                                <div class="absolute inset-0 bg-gradient-to-t from-black/70 to-transparent"></div>
                                <div class="absolute bottom-4 left-4 right-4">
                                    <h3 class="text-white text-xl font-bold">Curso Completo Disponible</h3>
                                </div>
                            </div>
                            <div class="p-6 flex flex-col gap-4">
                                <div class="flex items-start justify-between gap-4 text-sm text-muted-foreground">
                                    <div class="flex gap-6">
                                        <div class="flex items-center gap-1"><svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="23 7 16 12 23 17 23 7"></polygon><rect x="1" y="5" width="15" height="14" rx="2" ry="2"></rect></svg><span>8+ horas</span></div>
                                        <div class="flex items-center gap-1"><svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="3" width="20" height="14" rx="2" ry="2"></rect><line x1="8" y1="21" x2="16" y2="21"></line><line x1="12" y1="17" x2="12" y2="21"></line></svg><span>Online</span></div>
                                    </div>
                                    <div class="text-right">
                                        <div class="text-2xl font-bold text-primary">$<?= number_format($priceCents / 100, 0) ?> MXN</div>
                                    </div>
                                </div>
                                <?php if ($hasAccess): ?>
                                    <a href="<?= url('mis-videos.php') ?>" class="w-30 px-4 py-3 rounded-md bg-primary text-primary-foreground hover:bg-primary/90 transition font-semibold shadow-sm">Acceder</a>
                                <?php else: ?>
                                    <a href="<?= $isLoggedIn ? '#comprar' : url('register.php') ?>" class="w-40 px-4 py-3 rounded-md bg-secondary text-secondary-foreground hover:bg-secondary/90 transition font-semibold shadow-sm">Inscribirme Ahora</a>
                                <?php endif; ?>
                                <p class="text-xs text-muted-foreground text-center">Acceso 24/7 durante 3 meses</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Stats -->
    <section class="py-14 sm:py-20 bg-muted/30">
            <div class="max-w-6xl mx-auto px-4">
                <div class="grid grid-cols-2 sm:grid-cols-3 gap-6 sm:gap-8 text-center">
                    <div class="text-center">
                        <div class="text-4xl md:text-5xl font-bold text-primary mb-2">5</div>
                        <p class="text-muted-foreground font-medium">Módulos Especializados</p>
                    </div>
                    <div class="text-center">
                        <div class="text-4xl md:text-5xl font-bold text-primary mb-2">100%</div>
                        <p class="text-muted-foreground font-medium">Satisfacción</p>
                    </div>
                    <div class="text-center col-span-2 md:col-span-1">
                        <div class="text-4xl md:text-5xl font-bold text-primary mb-2">24/7</div>
                        <p class="text-muted-foreground font-medium">Acceso Total</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Comprar -->
    <section id="comprar" class="py-20 sm:py-24 bg-muted/30">
            <div class="max-w-6xl mx-auto px-4">
                <div class="text-center mb-12 sm:mb-16 space-y-4">
                    <h2 class="text-3xl sm:text-4xl lg:text-5xl font-bold leading-tight">Tu Acceso Total al Conocimiento <span class="text-primary">Regenerativo</span></h2>
                    <p class="text-lg sm:text-xl text-muted-foreground max-w-4xl mx-auto">Todo lo que necesitas en un solo lugar. Invierte en tu futuro y el de tu rancho con nuestro curso especializado.</p>
                </div>
                <div class="max-w-5xl mx-auto bg-white rounded-2xl shadow-xl sm:shadow-2xl border border-primary/20 sm:border-2 overflow-hidden">
                    <div class="grid grid-cols-1 lg:grid-cols-2">
                        <div class="p-6 sm:p-8 lg:p-12 space-y-6">
                            <h3 class="text-2xl font-bold text-primary mb-2">¿Qué Incluye tu Inscripción?</h3>
                            <ul class="space-y-5">
                                <li class="flex items-start gap-3"><svg class="h-6 w-6 text-primary mt-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><polygon points="23 7 16 12 23 17 23 7"/><rect x="1" y="5" width="15" height="14" rx="2" ry="2"/></svg><span><strong>8+ Horas de Contenido en Video HD</strong><br><span class="text-sm text-muted-foreground">Accede a 5 módulos especializados con lecciones prácticas y teóricas.</span></span></li>
                                <li class="flex items-start gap-3"><svg class="h-6 w-6 text-secondary mt-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><polyline points="12,6 12,12 16,14"/></svg><span><strong>Acceso por 3 Meses</strong><br><span class="text-sm text-muted-foreground">Aprende a tu propio ritmo con acceso ilimitado a la plataforma 24/7.</span></span></li>
                                <li class="flex items-start gap-3"><svg class="h-6 w-6 text-accent mt-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><circle cx="12" cy="8" r="7"/><polyline points="8.21,13.89 7,23 12,20 17,23 15.79,13.88"/></svg><span><strong>Certificado Oficial</strong><br><span class="text-sm text-muted-foreground">Obtén un certificado digital que avala tu conocimiento al finalizar el curso.</span></span></li>
                                <li class="flex items-start gap-3"><svg class="h-6 w-6 text-primary mt-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><rect x="2" y="3" width="20" height="14" rx="2" ry="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/></svg><span><strong>Videoconferencias Exclusivas</strong><br><span class="text-sm text-muted-foreground">Acceso por 1 año al grupo de Facebook "Manejo Holístico Tropical" para resolver dudas.</span></span></li>
                            </ul>
                        </div>
                        <div class="bg-primary/5 p-6 sm:p-8 lg:p-12 flex flex-col justify-center items-center text-center">
                            <span class="inline-block bg-accent text-white text-xs tracking-wide font-semibold px-4 py-2 rounded-full mb-4">🎯 Requisito Bonos de Carbono</span>
                            <h3 class="text-2xl font-bold mb-2">Inversión Única</h3>
                            <div class="flex items-baseline gap-2 mb-4">
                                <span class="text-5xl font-bold text-primary">$<?= number_format($priceCents / 100, 0) ?></span>
                                <span class="text-xl text-muted-foreground">MXN</span>
                            </div>
                            <p class="text-muted-foreground max-w-xs mb-6">Este curso es el primer paso para unirte a nuestro programa de Bonos de Carbono y generar ingresos adicionales.</p>
                            <?php if ($hasAccess): ?>
                                <a href="<?= url('mis-videos.php') ?>" class="w-full px-6 py-3 rounded-md bg-primary text-white hover:bg-primary/90 font-semibold transition">✅ Acceder a Mis Videos</a>
                            <?php elseif ($isLoggedIn): ?>
                                <div id="paypal-button-container" class="w-full mb-3"></div>
                            <?php else: ?>
                                <a href="<?= url('register.php') ?>" class="w-full px-6 py-3 rounded-md bg-primary text-white hover:bg-primary/90 font-semibold transition mb-3">Inscribirme Ahora</a>
                            <?php endif; ?>
                            <p class="text-sm text-muted-foreground mt-2">Acceso inmediato después del pago</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Temario -->
    <section id="que-hacemos" class="py-20 sm:py-24 bg-muted/30">
            <div class="max-w-6xl mx-auto px-4">
                <div class="text-center mb-12 sm:mb-16 space-y-4">
                    <h2 class="text-3xl sm:text-4xl lg:text-5xl font-bold leading-tight">Contenido del <span class="text-primary">Curso</span></h2>
                    <p class="text-lg sm:text-xl text-muted-foreground max-w-4xl mx-auto">5 módulos diseñados por expertos para transformar tu conocimiento en ganadería regenerativa.</p>
                </div>
                <div id="temario-grid" class="grid grid-cols-1 lg:grid-cols-2 gap-6 sm:gap-8 max-w-7xl mx-auto">
                    <!-- Módulo 1 -->
                    <div class="group hover:shadow-2xl transition-all duration-300 border-l-4 border-primary bg-white rounded-xl overflow-hidden shadow temario-item" data-index="0">
                        <div class="bg-primary text-primary-foreground px-6 py-4 flex items-center gap-3 text-xl font-semibold">
                            <div class="bg-primary text-primary-foreground rounded-full w-10 h-10 flex items-center justify-center font-bold ring-2 ring-white/30">1</div>
                            Manejo Holístico
                        </div>
                        <div class="p-6 space-y-6">
                            <div class="space-y-4">
                                <div class="border-l-4 border-primary pl-4 bg-muted/30 p-4 rounded-r-lg">
                                    <h5 class="font-semibold text-primary mb-2">Situación Actual de la Ganadería</h5>
                                    <ul class="text-sm text-muted-foreground space-y-1 list-disc list-inside">
                                        <li>Malas prácticas vs prácticas regenerativas</li>
                                        <li>Objetivos de la ganadería regenerativa</li>
                                        <li>Importancia de los bonos de carbono</li>
                                    </ul>
                                </div>
                                <div class="border-l-4 border-primary pl-4 bg-muted/30 p-4 rounded-r-lg">
                                    <h5 class="font-semibold text-primary mb-2">Impacto de la Revolución Verde</h5>
                                    <ul class="text-sm text-muted-foreground space-y-1 list-disc list-inside">
                                        <li>Ciclo de agroquímicos y consecuencias</li>
                                        <li>Daños a la salud y medio ambiente</li>
                                        <li>Contaminación de aguas subterráneas</li>
                                    </ul>
                                </div>
                                <div class="border-l-4 border-primary pl-4 bg-muted/30 p-4 rounded-r-lg">
                                    <h5 class="font-semibold text-primary mb-2">Ganadería y Cambio Climático</h5>
                                    <ul class="text-sm text-muted-foreground space-y-1 list-disc list-inside">
                                        <li>Contribución a gases efecto invernadero</li>
                                        <li>Ganadería confinada e impacto ambiental</li>
                                        <li>Huella de carbono en producción</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- Módulo 2 -->
                    <div class="group hover:shadow-2xl transition-all duration-300 border-l-4 border-secondary bg-white rounded-xl overflow-hidden shadow temario-item" data-index="1">
                        <div class="bg-secondary text-secondary-foreground px-6 py-4 flex items-center gap-3 text-xl font-semibold">
                            <div class="bg-secondary text-secondary-foreground rounded-full w-10 h-10 flex items-center justify-center font-bold ring-2 ring-white/30">2</div>
                            Pastoreo Regenerativo
                        </div>
                        <div class="p-6 space-y-6">
                            <div class="space-y-4">
                                <div class="border-l-4 border-secondary pl-4 bg-muted/30 p-4 rounded-r-lg">
                                    <h5 class="font-semibold text-secondary mb-2">Bases Teóricas del Pastoreo</h5>
                                    <ul class="text-sm text-muted-foreground space-y-1 list-disc list-inside">
                                        <li>Simbiosis entre sabana y herbívoros</li>
                                        <li>Leyes del pastoreo de André Voisin</li>
                                        <li>Ganadería regenerativa vs extensiva</li>
                                    </ul>
                                </div>
                                <div class="border-l-4 border-secondary pl-4 bg-muted/30 p-4 rounded-r-lg">
                                    <h5 class="font-semibold text-secondary mb-2">Implementación Práctica</h5>
                                    <ul class="text-sm text-muted-foreground space-y-1 list-disc list-inside">
                                        <li>Agua, tiempo de impacto y reposo</li>
                                        <li>Densidad animal óptima</li>
                                        <li>Pastoreo multiespecie</li>
                                    </ul>
                                </div>
                                <div class="border-l-4 border-secondary pl-4 bg-muted/30 p-4 rounded-r-lg">
                                    <h5 class="font-semibold text-secondary mb-2">Beneficios del Sistema</h5>
                                    <ul class="text-sm text-muted-foreground space-y-1 list-disc list-inside">
                                        <li>Beneficios económicos</li>
                                        <li>Beneficios productivos</li>
                                        <li>Beneficios ecológicos</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- Módulo 3 -->
                    <div class="group hover:shadow-2xl transition-all duration-300 border-l-4 border-accent bg-white rounded-xl overflow-hidden shadow temario-item" data-index="2">
                        <div class="bg-accent text-accent-foreground px-6 py-4 flex items-center gap-3 text-xl font-semibold">
                            <div class="bg-accent text-accent-foreground rounded-full w-10 h-10 flex items-center justify-center font-bold ring-2 ring-white/30">3</div>
                            Mejoramiento Genético Holístico y Búfalos
                        </div>
                        <div class="p-6 space-y-6">
                            <div class="space-y-4">
                                <div class="border-l-4 border-accent pl-4 bg-muted/30 p-4 rounded-r-lg">
                                    <h5 class="font-semibold text-accent mb-2">Genética Animal Adaptada</h5>
                                    <ul class="text-sm text-muted-foreground space-y-1 list-disc list-inside">
                                        <li>Rentabilidad máxima por hectárea</li>
                                        <li>Cruzamiento y crianza pura</li>
                                        <li>Selección de eficiencia funcional</li>
                                    </ul>
                                </div>
                                <div class="border-l-4 border-accent pl-4 bg-muted/30 p-4 rounded-r-lg">
                                    <h5 class="font-semibold text-accent mb-2">Búfalos en Sistemas Regenerativos</h5>
                                    <ul class="text-sm text-muted-foreground space-y-1 list-disc list-inside">
                                        <li>Producción de leche y carne</li>
                                        <li>Comparativa sistemas tradicionales</li>
                                        <li>Adaptación al trópico húmedo</li>
                                    </ul>
                                </div>
                                <div class="border-l-4 border-accent pl-4 bg-muted/30 p-4 rounded-r-lg">
                                    <h5 class="font-semibold text-accent mb-2">Selección Natural</h5>
                                    <ul class="text-sm text-muted-foreground space-y-1 list-disc list-inside">
                                        <li>Animales eficientes según el medio</li>
                                        <li>Fertilidad y adaptación</li>
                                        <li>Individuos super fértiles</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- Módulo 4 -->
                    <div class="group hover:shadow-2xl transition-all duration-300 border-l-4 border-red-600 bg-white rounded-xl overflow-hidden shadow temario-item" data-index="3">
                        <div class="bg-red-600 text-white px-6 py-4 flex items-center gap-3 text-xl font-semibold">
                            <div class="bg-red-600 text-white rounded-full w-10 h-10 flex items-center justify-center font-bold ring-2 ring-white/30">4</div>
                            Bonos de Carbono
                        </div>
                        <div class="p-6 space-y-6">
                            <div class="space-y-4">
                                <div class="border-l-4 border-red-600 pl-4 bg-muted/30 p-4 rounded-r-lg">
                                    <h5 class="font-semibold text-red-600 mb-2">Fundamentos del Carbono</h5>
                                    <ul class="text-sm text-muted-foreground space-y-1 list-disc list-inside">
                                        <li>El carbono y su efecto a gran escala</li>
                                        <li>Principales fuentes de GEI</li>
                                        <li>Sector ganadero en GEI</li>
                                    </ul>
                                </div>
                                <div class="border-l-4 border-red-600 pl-4 bg-muted/30 p-4 rounded-r-lg">
                                    <h5 class="font-semibold text-red-600 mb-2">Programa de Bonos</h5>
                                    <ul class="text-sm text-muted-foreground space-y-1 list-disc list-inside">
                                        <li>Objetivos del programa</li>
                                        <li>Tipos de proyectos para mitigar CO2e</li>
                                        <li>Iniciativa Pijije Regenerativo</li>
                                    </ul>
                                </div>
                                <div class="border-l-4 border-red-600 pl-4 bg-muted/30 p-4 rounded-r-lg">
                                    <h5 class="font-semibold text-red-600 mb-2">Comercialización</h5>
                                    <ul class="text-sm text-muted-foreground space-y-1 list-disc list-inside">
                                        <li>Clasificación: regulados y voluntarios</li>
                                        <li>Proceso de emisión y comercialización</li>
                                        <li>Modelo de negocio y contratos</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- Módulo 5 -->
                    <div class="lg:col-span-2 group hover:shadow-2xl transition-all duration-300 border-l-4 border-blue-500 bg-white rounded-xl overflow-hidden shadow temario-item" data-index="4">
                        <div class="bg-gradient-to-r from-blue-600 to-blue-500 text-white px-6 py-4 flex items-center gap-3 text-xl font-semibold">
                            <div class="bg-blue-600 text-white rounded-full w-10 h-10 flex items-center justify-center font-bold ring-2 ring-white/30">5</div>
                            Día de Campo - Aplicación Práctica
                        </div>
                        <div class="p-8">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                                <div class="space-y-4">
                                    <div class="border-l-4 border-blue-500 pl-4 bg-blue-50 p-4 rounded-r-lg">
                                        <h5 class="font-semibold text-blue-600 mb-2">Aplicación Práctica</h5>
                                        <ul class="text-sm text-blue-800 space-y-1 list-disc list-inside">
                                            <li>Videos del día de campo</li>
                                            <li>Demostración práctica de conceptos teóricos</li>
                                            <li>Casos reales de implementación</li>
                                        </ul>
                                    </div>
                                </div>
                                <div class="space-y-4">
                                    <div class="border-l-4 border-blue-500 pl-4 bg-blue-50 p-4 rounded-r-lg">
                                        <h5 class="font-semibold text-blue-600 mb-2">Desarrollo de Temas</h5>
                                        <ul class="text-sm text-blue-800 space-y-1 list-disc list-inside">
                                            <li>Continuación de módulos anteriores</li>
                                            <li>Ejemplos en campo real</li>
                                            <li>Técnicas aplicadas en vivo</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="mt-8 sm:mt-10 flex justify-center lg:hidden">
                    <button id="toggle-temario" class="px-5 py-3 rounded-xl bg-primary text-white text-sm font-semibold shadow hover:bg-primary/90 focus:outline-none focus:ring-2 focus:ring-primary/40">
                        Ver temario completo
                    </button>
                </div>
            </div>
        </section>

        <!-- Bonos de Carbono -->
    <section id="eventos" class="py-20 sm:py-24 bg-background">
            <div class="max-w-6xl mx-auto px-4">
                <div class="text-center mb-16 space-y-4">
                    <h2 class="text-4xl lg:text-5xl font-bold">¿Qué son los <span class="text-primary">Bonos de Carbono</span> y cómo me benefician?</h2>
                    <p class="text-xl text-muted-foreground max-w-4xl mx-auto">Descubre cómo tus prácticas ganaderas pueden convertirse en una poderosa herramienta contra el cambio climático y una nueva fuente de ingresos.</p>
                </div>
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 items-center mb-20">
                    <div class="space-y-6">
                        <h3 class="text-3xl font-bold">Un Incentivo por Cuidar el Planeta</h3>
                        <p class="text-muted-foreground leading-relaxed">Los bonos de carbono son un <strong>incentivo financiero</strong> que recibes por implementar prácticas que capturan y almacenan carbono en el suelo. Cada bono equivale a una tonelada de CO₂ que se ha eliminado de la atmósfera gracias a tu trabajo.</p>
                        <p class="text-muted-foreground leading-relaxed">Estos bonos son comprados por grandes empresas que buscan compensar sus emisiones, convirtiendo tu esfuerzo en un motor de cambio global y en un beneficio económico directo para ti.</p>
                    </div>
                    <div class="relative h-80 rounded-2xl overflow-hidden shadow-lg">
                        <img src="<?= asset('assets/images/carbono-1.jpeg') ?>" alt="Concepto de Bonos de Carbono" class="absolute inset-0 w-full h-full object-cover bg-white" />
                    </div>
                </div>
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 items-center">
                    <div class="relative h-80 rounded-2xl overflow-hidden shadow-lg lg:order-last">
                        <img src="<?= asset('assets/images/carbono-2.jpeg') ?>" alt="Alianza Pijije Regenerativo y Boomitra" class="absolute inset-0 w-full h-full object-cover bg-white" />
                    </div>
                    <div class="space-y-6 lg:order-first">
                        <h3 class="text-3xl font-bold">El Proyecto Pijije Regenerativo: Tu Aliado Estratégico</h3>
                        <p class="text-muted-foreground leading-relaxed">Nos asociamos con <strong>Boomitra</strong>, el mercado internacional líder en carbono del suelo, para garantizar un proceso transparente y eficiente.</p>
                        <ul class="space-y-4">
                            <li class="flex items-start gap-3"><svg class="h-6 w-6 text-primary mt-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg><span><strong>Pijije Regenerativo</strong> te brinda el acompañamiento técnico y la capacitación para optimizar tus recursos.</span></li>
                            <li class="flex items-start gap-3"><svg class="h-6 w-6 text-primary mt-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg><span><strong>Boomitra</strong> se encarga de medir, certificar y vender los bonos generados, utilizando tecnología satelital de punta.</span></li>
                        </ul>
                    </div>
                </div>
            </div>
        </section>

        <!-- Beneficios -->
        <section class="py-20 sm:py-24 bg-muted/30" aria-labelledby="beneficios-heading">
            <h2 id="beneficios-heading" class="sr-only">Beneficios del curso</h2>
            <div class="max-w-6xl mx-auto px-4">
                <div class="text-center mb-16 space-y-2">
                    <h3 class="text-4xl font-semibold tracking-wide">Beneficios Directos para Ti y tu Rancho</h3>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                    <div class="relative text-center p-8 bg-white/95 backdrop-blur-sm rounded-xl border border-primary/15 shadow-sm hover:shadow-xl hover:border-primary/30 transition group">
                        <div class="mb-5 flex justify-center">
                            <div class="h-20 w-20 rounded-full bg-primary/10 flex items-center justify-center ring-2 ring-primary/20 group-hover:ring-primary/40 transition">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" class="h-11 w-11 text-primary">
                                    <polyline points="23 6 13.5 15.5 8.5 10.5 1 18" />
                                    <polyline points="17 6 23 6 23 12" />
                                </svg>
                            </div>
                        </div>
                        <h4 class="text-lg font-semibold tracking-wide mb-3 text-foreground">Mayor Rentabilidad</h4>
                        <p class="text-muted-foreground leading-relaxed text-sm">Mejora la salud de tu suelo y optimiza la producción, reduciendo costos.</p>
                    </div>
                    <div class="relative text-center p-8 bg-white/95 backdrop-blur-sm rounded-xl border border-primary/15 shadow-sm hover:shadow-xl hover:border-primary/30 transition group">
                        <div class="mb-5 flex justify-center">
                            <div class="h-20 w-20 rounded-full bg-primary/10 flex items-center justify-center ring-2 ring-primary/20 group-hover:ring-primary/40 transition">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" class="h-11 w-11 text-primary">
                                    <rect x="2" y="7" width="20" height="10" rx="2" ry="2" />
                                    <circle cx="12" cy="12" r="3" />
                                    <path d="M6 9v6" />
                                    <path d="M18 9v6" />
                                </svg>
                            </div>
                        </div>
                        <h4 class="text-lg font-semibold tracking-wide mb-3 text-foreground">Ingreso Adicional Seguro</h4>
                        <p class="text-muted-foreground leading-relaxed text-sm">Genera ingresos adicionales de $2,000 a $6,000 MXN por cada bono de carbono vendido. El monto depende de tu técnica de manejo del ecosistema y pastoreo regenerativo.</p>
                    </div>
                    <div class="relative text-center p-8 bg-white/95 backdrop-blur-sm rounded-xl border border-primary/15 shadow-sm hover:shadow-xl hover:border-primary/30 transition group">
                        <div class="mb-5 flex justify-center">
                            <div class="h-20 w-20 rounded-full bg-primary/10 flex items-center justify-center ring-2 ring-primary/20 group-hover:ring-primary/40 transition">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" class="h-11 w-11 text-primary">
                                    <path d="M12 2C7 2 3 6 3 11c0 7 9 11 9 11s9-4 9-11c0-5-4-9-9-9z" />
                                    <path d="M12 7v8" />
                                    <path d="M8 13l4 2 4-2" />
                                </svg>
                            </div>
                        </div>
                        <h4 class="text-lg font-semibold tracking-wide mb-3 text-foreground">Suelos Más Sanos</h4>
                        <p class="text-muted-foreground leading-relaxed text-sm">Mejora la retención de agua y la calidad del forraje, protegiéndote contra sequías.</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Galería -->
    <section class="py-20 sm:py-24 bg-background">
            <div class="max-w-7xl mx-auto px-4 lg:px-6">
                <div class="text-center mb-16 space-y-4">
                    <h2 class="text-4xl lg:text-5xl font-bold tracking-tight">Galería de <span class="text-primary">Eventos</span></h2>
                    <p class="text-xl text-muted-foreground max-w-4xl mx-auto">Eventos, sesiones y experiencias en campo que han transformado la visión de productores.</p>
                </div>
                <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
                    <?php foreach(array_slice($galleryImages, 0, 12) as $g): ?>
                        <div class="group relative h-64 rounded-2xl overflow-hidden shadow-lg hover:shadow-2xl transition-all duration-500">
                            <img src="<?= asset($g['src']) ?>" alt="<?= htmlspecialchars($g['alt']) ?>" loading="lazy" class="absolute inset-0 w-full h-full object-cover group-hover:scale-110 transition-transform duration-700 bg-white" />
                            <div class="absolute inset-0 bg-gradient-to-t from-black/60 via-transparent to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
                            <div class="absolute bottom-4 left-4 right-4 text-white opacity-0 group-hover:opacity-100 transition-opacity duration-300">
                                <p class="text-sm font-medium line-clamp-2 leading-snug drop-shadow">
                                    <?= htmlspecialchars($g['alt']) ?>
                                </p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>

        <!-- Contacto -->
    <section class="py-16 sm:py-20 bg-muted/30" id="contacto">
            <div class="max-w-6xl mx-auto px-4">
                <div class="text-center mb-16">
                    <h2 class="text-4xl font-bold mb-4">Contacto</h2>
                </div>
                <div class="max-w-2xl mx-auto text-center">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                        <div class="flex flex-col items-center">
                            <div class="bg-green-100 p-3 rounded-full mb-3">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-7 h-7 text-green-600" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"/></svg>
                            </div>
                            <p class="font-semibold">WhatsApp</p>
                            <p class="text-sm text-muted-foreground">+52 93 4115 0595</p>
                        </div>
                        <div class="flex flex-col items-center">
                            <div class="bg-blue-100 p-3 rounded-full mb-3">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-7 h-7 text-blue-600" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
                            </div>
                            <p class="font-semibold">Tel</p>
                            <p class="text-sm text-muted-foreground">+52 93 4115 0595</p>
                        </div>
                        <div class="flex flex-col items-center">
                            <div class="bg-red-100 p-3 rounded-full mb-3">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-7 h-7 text-red-600" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="20" height="16" x="2" y="4" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/></svg>
                            </div>
                            <p class="font-semibold">Email</p>
                            <p class="text-sm text-muted-foreground">organicosdeltropico@yahoo.com.mx</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </main>
    <?php include __DIR__ . '/sections/footer.php'; ?>

    <!-- PayPal Smart Button Script -->
    <?php if ($isLoggedIn && !$hasAccess): ?>
    <style nonce="<?= e(csp_nonce()) ?>">.pp-skeleton{display:flex;align-items:center;justify-content:center;height:52px;border:1px solid #e2e8f0;background:linear-gradient(90deg,#f1f5f9,#e2e8f0,#f1f5f9);background-size:200% 100%;animation:ppShimmer 1.2s linear infinite;border-radius:8px;font-size:13px;color:#475569;font-weight:500}.pp-error{background:#fee2e2;border:1px solid #fecaca;color:#991b1b;padding:10px 12px;border-radius:8px;font-size:13px;margin-top:6px;display:none}.pp-retry{margin-left:8px;background:#1e3a8a;color:#fff;border:none;padding:6px 10px;border-radius:6px;cursor:pointer;font-size:12px;font-weight:600}.pp-retry:disabled{opacity:.5;cursor:not-allowed}@keyframes ppShimmer{0%{background-position:200% 0}100%{background-position:-200% 0}}</style>
    <div id="paypal-wrapper" style="width:100%;max-width:380px;margin:0 auto 8px;">
        <div id="paypal-button-container" class="pp-skeleton">Cargando método de pago…</div>
        <div id="paypal-error" class="pp-error">No se pudo cargar PayPal <button id="paypal-retry" class="pp-retry">Reintentar</button></div>
    </div>
    <script nonce="<?= e(csp_nonce()) ?>">
    (function(){
        const csrf = "<?= e(csrf_token()) ?>";
        const clientId = "<?= e($config['paypal']['client_id']) ?>";
        if(!clientId){
            setTimeout(()=>{ document.getElementById('paypal-button-container').innerHTML='Configurar PayPal (client_id vacío)'; }, 50);
            return; // no intentar cargar
        }
        const sdkUrl = "https://www.paypal.com/sdk/js?client-id="+encodeURIComponent(clientId)+"&currency=MXN&intent=capture";
        const btnContainer = document.getElementById('paypal-button-container');
        const errBox = document.getElementById('paypal-error');
        const retryBtn = document.getElementById('paypal-retry');
        let loaded = false; let attempts = 0; const MAX_ATTEMPTS = 3;

        function setError(msg){
            if(btnContainer){ btnContainer.style.display='none'; }
            if(errBox){ errBox.style.display='block'; errBox.firstChild && (errBox.firstChild.textContent = msg + ' '); }
        }
        function clearError(){ if(errBox){ errBox.style.display='none'; } }

        function mount(){
            if(!window.paypal){ return; }
            try {
                window.paypal.Buttons({
                    createOrder: () => fetch('<?= url('checkout/create-order.php') ?>', {
                        method:'POST', headers:{'Content-Type':'application/json'}, body:JSON.stringify({csrf})
                    }).then(r=>r.json()).then(d=>{ if(d.error||!d.orderID) throw new Error(d.error||'Sin orderID'); return d.orderID; }),
                    onApprove: (data) => fetch('<?= url('checkout/capture-order.php') ?>', {
                        method:'POST', headers:{'Content-Type':'application/json'}, body:JSON.stringify({csrf, orderID:data.orderID})
                    }).then(r=>r.json()).then(d=>{ if(d.error){ alert('Pago no confirmado: '+d.error); } else { window.location='<?= url('success.php') ?>'; } }),
                    onError: (err) => { console.error('[PayPal Buttons]', err); alert('Error de PayPal: '+ (err?.message||err)); }
                }).render('#paypal-button-container');
                btnContainer.classList.remove('pp-skeleton');
            } catch(e){ console.error('Render fail', e); setError('Fallo al inicializar.'); }
        }

        function inject(){
            if(loaded) return;
            attempts++;
            clearError();
            const s = document.createElement('script');
            // Eliminado parámetro &t= que provocaba 400 en algunos escenarios del SDK
            s.src = sdkUrl;
            s.onload = function(){ loaded = true; setTimeout(mount, 30); };
            s.onerror = function(){ if(attempts < MAX_ATTEMPTS){ setTimeout(inject, 1000*attempts); } else { setError('No se pudo cargar PayPal.'); } };
            document.head.appendChild(s);
        }

        // Warmup backend (caché token) mientras se carga SDK
        fetch('<?= url('checkout/warmup.php') ?>').catch(()=>{}).finally(()=>inject());
        retryBtn && retryBtn.addEventListener('click', function(){ if(!retryBtn.disabled){ inject(); } });
        // Timeout si PayPal tarda demasiado
        setTimeout(function(){ if(!loaded){ setError('Demora inusual cargando PayPal.'); } }, 8000);
    })();
    </script>
    <?php endif; ?>

        <script src="<?= asset('assets/js/ui.js') ?>" nonce="<?= e(csp_nonce()) ?>" defer></script>
</body>
</html>