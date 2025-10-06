<?php
// Header (Tailwind) extracted.
?>
<header id="siteHeader" class="sticky top-0 z-50 bg-white/95 backdrop-blur-sm border-b border-gray-200 shadow-sm transition-all">
    <div class="max-w-6xl mx-auto px-4 py-4 flex items-center justify-between gap-4">
        <a href="<?= url() ?>" class="shrink-0 inline-flex items-center" aria-label="Inicio">
            <img src="<?= asset('assets/images/pijije-logo.jpg') ?>" alt="Pijije Regenerativo" class="h-12 w-auto object-contain" loading="lazy" decoding="async" />
        </a>
        <nav id="mainNav" aria-label="Principal" class="hidden md:flex items-center gap-8 text-sm font-medium">
            <a href="<?= url() ?>#hero" class="text-gray-700 hover:text-primary transition">Inicio</a>
            <a href="<?= url() ?>#que-hacemos" class="text-gray-700 hover:text-primary transition">¿Qué Hacemos?</a>
            <a href="<?= url() ?>#eventos" class="text-gray-700 hover:text-primary transition">Eventos</a>
            <a href="<?= url('cursos') ?>" class="relative text-primary font-semibold after:absolute after:-bottom-2 after:left-0 after:w-full after:h-1 after:bg-primary after:rounded-full">Cursos</a>
            <a href="<?= url() ?>#contacto" class="text-gray-700 hover:text-primary transition">Contacto</a>
        </nav>
        <div class="hidden md:flex items-center gap-4 text-sm">
            <?php if (isLoggedIn()): ?>
                <a href="<?= url('mis-videos.php') ?>" class="px-4 py-2 rounded-md border border-primary text-primary hover:bg-primary hover:text-white transition font-medium">Mis Videos</a>
                <a href="<?= url('logout.php') ?>" class="px-4 py-2 rounded-md bg-primary text-white hover:bg-primary/90 transition font-medium">Salir</a>
            <?php else: ?>
                <a href="<?= url('login.php') ?>" class="px-4 py-2 rounded-md border border-primary text-primary hover:bg-primary hover:text-white transition font-medium">Iniciar Sesión</a>
                <a href="<?= url('register.php') ?>" class="px-4 py-2 rounded-md bg-primary text-white hover:bg-primary/90 transition font-medium">Registrarse</a>
            <?php endif; ?>
        </div>
        <button id="hamburger" aria-label="Menú" aria-expanded="false" class="md:hidden inline-flex items-center justify-center w-10 h-10 rounded-md hover:bg-gray-100 transition">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="4" x2="20" y1="12" y2="12"/><line x1="4" x2="20" y1="6" y2="6"/><line x1="4" x2="20" y1="18" y2="18"/></svg>
        </button>
    </div>
    <div id="mobileMenu" class="md:hidden hidden border-t border-gray-200 bg-white/95 backdrop-blur-sm">
        <div class="max-w-6xl mx-auto px-4 py-4 flex flex-col gap-4 text-sm font-medium">
            <a href="<?= url() ?>#hero" class="hover:text-primary transition">Inicio</a>
            <a href="<?= url() ?>#que-hacemos" class="hover:text-primary transition">¿Qué Hacemos?</a>
            <a href="<?= url() ?>#eventos" class="hover:text-primary transition">Eventos</a>
            <a href="<?= url('cursos') ?>" class="text-primary font-semibold">Cursos</a>
            <a href="<?= url() ?>#contacto" class="hover:text-primary transition">Contacto</a>
            <div class="pt-4 mt-2 border-t border-gray-200 flex flex-col gap-2">
                <?php if (isLoggedIn()): ?>
                    <a href="<?= url('mis-videos.php') ?>" class="px-4 py-2 rounded-md border border-primary text-primary hover:bg-primary hover:text-white transition">Mis Videos</a>
                    <a href="<?= url('logout.php') ?>" class="px-4 py-2 rounded-md bg-primary text-white hover:bg-primary/90 transition">Salir</a>
                <?php else: ?>
                    <a href="<?= url('login.php') ?>" class="px-4 py-2 rounded-md border border-primary text-primary hover:bg-primary hover:text-white transition">Iniciar Sesión</a>
                    <a href="<?= url('register.php') ?>" class="px-4 py-2 rounded-md bg-primary text-white hover:bg-primary/90 transition">Registrarse</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</header>
