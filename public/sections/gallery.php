<?php
/**
 * Sección de galería de eventos
 * Incluir en index.php
 */
?>
<!-- Gallery Section -->
<section style="padding: 96px 0; background: white;">
    <div class="container">
        <div style="text-align: center; margin-bottom: 64px;">
            <h2 style="font-size: 36px; font-weight: 700; margin-bottom: 16px; line-height: 1.2;">
                Galería de <span style="color: #667eea;">Eventos</span>
            </h2>
            <p style="font-size: 20px; color: #718096; max-width: 600px; margin: 0 auto;">
                Nuestros eventos y conferencias sobre ganadería regenerativa han impactado a miles de productores
            </p>
        </div>

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 24px;">
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
                ['src' => '/assets/images/galeria/evento-13.jpg', 'alt' => 'Panel de expertos']
            ];
            
            foreach ($galleryImages as $index => $image):
            ?>
            <div style="position: relative; height: 256px; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 6px rgba(0,0,0,0.1); transition: all 0.5s; cursor: pointer;" 
                 onmouseover="this.style.transform='translateY(-4px)'; this.style.boxShadow='0 8px 25px rgba(0,0,0,0.15)'"
                 onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 4px 6px rgba(0,0,0,0.1)'">
                <img src="<?= $image['src'] ?>" alt="<?= htmlspecialchars($image['alt']) ?>" 
                     style="width: 100%; height: 100%; object-fit: cover; transition: transform 0.7s;"
                     onmouseover="this.style.transform='scale(1.1)'"
                     onmouseout="this.style.transform='scale(1)'">
                <div style="position: absolute; inset: 0; background: linear-gradient(transparent, rgba(0,0,0,0.6), transparent); opacity: 0; transition: opacity 0.3s;"
                     onmouseover="this.style.opacity='1'"
                     onmouseout="this.style.opacity='0'"></div>
                <div style="position: absolute; bottom: 16px; left: 16px; right: 16px; color: white; opacity: 0; transition: opacity 0.3s;"
                     onmouseover="this.style.opacity='1'"
                     onmouseout="this.style.opacity='0'">
                    <p style="font-size: 14px; font-weight: 500; margin: 0;"><?= htmlspecialchars($image['alt']) ?></p>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
