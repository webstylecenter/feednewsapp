import { defineConfig } from 'vite';
import vue from '@vitejs/plugin-vue';
import { VitePWA } from 'vite-plugin-pwa';

export default defineConfig({
    plugins: [
        vue(),
        VitePWA({
            registerType: 'autoUpdate',
            includeAssets: [
                'favicon.ico',
                'favicon-16x16.png',
                'favicon-32x32.png',
                'apple-touch-icon.png',
            ],
            workbox: {
                cleanupOutdatedCaches: true,
            },
            manifest: {
                name: 'Feednews',
                short_name: 'Feednews',
                description: 'Your personal news reader',
                start_url: '/',
                scope: '/',
                display: 'standalone',
                background_color: '#f5f7fb',
                theme_color: '#337dff',
                icons: [
                    {
                        src: '/icons/icon-192.png',
                        sizes: '192x192',
                        type: 'image/png',
                    },
                    {
                        src: '/icons/icon-512.png',
                        sizes: '512x512',
                        type: 'image/png',
                    },
                    {
                        src: '/icons/icon-512-maskable.png',
                        sizes: '512x512',
                        type: 'image/png',
                        purpose: 'maskable',
                    },
                ],
            },
        }),
    ],
    server: {
        host: '0.0.0.0',
        port: 5173,
        strictPort: true,
        hmr: {
            clientPort: 443,
        },
        allowedHosts: [
            '.ddev.site',
        ],
        proxy: {
            '/api': {
                target: 'https://feednews.ddev.site',
                secure: false,
            },
        },
    },
    build: {
        outDir: '../backend/public',
        emptyOutDir: false,
        rollupOptions: {
            output: {
                entryFileNames: 'build/app-[hash].js',
                chunkFileNames: 'build/chunks/[name]-[hash].js',
                assetFileNames: (assetInfo) => (
                    assetInfo.name?.endsWith('.css')
                        ? 'build/app-[hash][extname]'
                        : 'build/assets/[name]-[hash][extname]'
                ),
            },
        },
    },
});
