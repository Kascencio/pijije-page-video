<?php
// Footer Tailwind simple reutilizable
?>
<footer class="bg-gray-900 text-gray-400">
    <div class="max-w-6xl mx-auto px-4 py-12">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
            <div class="text-center md:text-left">
                <p class="text-lg">© 2024 PIJIJE REGENERATIVO</p>
                <div class="flex flex-wrap justify-center md:justify-start gap-4 mt-4 text-sm">
                    <a href="<?= url() ?>" class="hover:text-white transition-colors">Orgánicos del Trópico</a><span>•</span>
                    <a href="<?= url('que-hacemos') ?>" class="hover:text-white transition-colors">¿Qué Hacemos?</a><span>•</span>
                    <a href="<?= url('eventos') ?>" class="hover:text-white transition-colors">Eventos</a><span>•</span>
                    <a href="<?= url('pijije-regenerativo') ?>" class="hover:text-white transition-colors">Pijije Regenerativo</a><span>•</span>
                    <a href="<?= url('aviso-privacidad') ?>" class="hover:text-white transition-colors">Aviso de Privacidad</a>
                </div>
            </div>
            <div class="text-center md:text-right">
                        <div class="flex justify-center md:justify-end mb-4">
                            <a href="https://www.youtube.com/@OrganicosdelTropico" target="_blank" rel="noopener" aria-label="YouTube" class="inline-flex items-center justify-center bg-gray-800 rounded-full transition-colors duration-300 hover:bg-red-600 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 focus:ring-offset-gray-900 w-14 h-14">
                                <svg viewBox="0 0 24 24" class="w-7 h-7" role="img" aria-hidden="true" fill="currentColor">
                                    <path d="M21.8 8s-.2-1.5-.8-2.2c-.7-.8-1.5-.8-1.9-.9C16.9 4.5 12 4.5 12 4.5h0s-4.9 0-7.1.4c-.4.1-1.2.1-1.9.9C2 6.5 2 8 2 8S1.8 9.6 1.8 11.3v1.4C1.8 14.4 2 16 2 16s.2 1.5.8 2.2c.7.8 1.6.8 2 1 1.5.2 6.2.4 7.2.4 0 0 4.9 0 7.1-.4.4-.1 1.2-.1 1.9-.9.6-.7.8-2.2.8-2.2s.2-1.6.2-3.3v-1.4c0-1.7-.2-3.3-.2-3.3ZM10 15.2V8.8l5.2 3.2L10 15.2Z" />
                                </svg>
                            </a>
                        </div>
            </div>
        </div>
    </div>
</footer>
