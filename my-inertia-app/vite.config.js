import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';

export default defineConfig({
    server: {
        host: '0.0.0.0', // This allows connections from any IP address
        port: 5174,     // The port Vite will run on
        strictPort: true,
        hmr: {
            host: '10.47.2.118', // <--- IMPORTANT: Replace with YOUR SERVER COMPUTER'S LOCAL IP
            clientPort: 5174, // Add this if you face HMR connection issues
        }
    },
    plugins: [
        laravel({
            input: 'resources/js/app.js',
            refresh: true,
        }),
        vue({
            template: {
                transformAssetUrls: {
                    base: null,
                    includeAbsolute: false,
                },
            },
        }),
    ],
});