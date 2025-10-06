<?php
/**
 * Sección de galería de eventos
 * Incluir en index.php
 */
?>
<section class="py-24 section-muted">
    <div class="container mx-auto px-4">
        <div class="text-center mb-16 space-y-4">
            <h2 class="text-4xl font-bold text-balance">Galería de <span class="text-primary">Eventos</span></h2>
            <p class="text-xl text-muted-foreground max-w-2xl mx-auto">Nuestros eventos y conferencias sobre ganadería regenerativa han impactado a miles de productores</p>
        </div>
    <div class="grid" style="grid-template-columns:repeat(auto-fit,minmax(140px,1fr));gap:1rem;">
            <?php
            $galleryImages = [
                ['src' => '/assets/images/galeria/evento-2.jpg', 'alt' => 'Conferencia en auditorio'],
                ['src' => '/assets/images/galeria/evento-3.jpg', 'alt' => 'Presentación Manejo Holístico Tropical'],
                ['src' => '/assets/images/galeria/evento-4.jpg', 'alt' => 'Stands de exhibición'],
                ['src' => '/assets/images/galeria/evento-5.jpg', 'alt' => 'Audiencia en conferencia'],
                ['src' => '/assets/images/galeria/evento-6.jpg', 'alt' => 'Participantes del evento'],
                ['src' => '/assets/images/galeria/evento-7.jpg', 'alt' => 'Centro de Convenciones Campeche'],
                ['src' => '/assets/images/galeria/evento-8.jpg', 'alt' => 'Fila de participantes esperando'],
                ['src' => '/assets/images/galeria/evento-9.jpg', 'alt' => 'Presentación en salón'],
                ['src' => '/assets/images/galeria/evento-10.jpeg', 'alt' => 'Ganado'],
                ['src' => '/assets/images/galeria/evento-11.jpg', 'alt' => 'Conferencia Manejo Holístico'],
                ['src' => '/assets/images/galeria/evento-12.jpg', 'alt' => 'Área de networking'],
                ['src' => '/assets/images/galeria/evento-13.jpg', 'alt' => 'Panel de expertos'],
            ];
            foreach ($galleryImages as $index => $image): ?>
                <div class="gallery-item h-40" style="height:11.5rem;">
                    <img src="<?= $image['src'] ?>" alt="<?= htmlspecialchars($image['alt']) ?>" class="object-cover w-full h-full" />
                    <div class="gallery-caption"></div>
                    <div class="gallery-text"><?= htmlspecialchars($image['alt']) ?></div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
