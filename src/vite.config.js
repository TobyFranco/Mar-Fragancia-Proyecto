import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import { bunny } from 'laravel-vite-plugin/fonts';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
            fonts: [
                bunny('Montserrat', { weights: [500, 600, 700], optimizedFallbacks: false }),
                bunny('Inter', { weights: [400, 500, 600], optimizedFallbacks: false }),
                bunny('IBM Plex Mono', { weights: [400, 500], optimizedFallbacks: false }),
            ],
        }),
        tailwindcss(),
    ],
    server: {
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
