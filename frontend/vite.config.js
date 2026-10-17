import { defineConfig } from 'vite';
import vue from '@vitejs/plugin-vue';

export default defineConfig({
    plugins: [vue()],
    server: {
        host: '0.0.0.0',
        port: 5173,
        strictPort: true,
        hmr: { clientPort: 443 },
        allowedHosts: [
            'feednews.ddev.site',
        ],
        proxy: {
            '/api': { target: 'https://feednews.ddev.site', secure: false },
        },
    },
    build: {
        outDir: '../backend/public',
        emptyOutDir: false,
        rollupOptions: {
            output: {
                entryFileNames: 'build/app.js',
                chunkFileNames: 'build/chunks/[name]-[hash].js',
                assetFileNames: (assetInfo) => assetInfo.name?.endsWith('.css') ? 'build/app.css' : 'build/assets/[name]-[hash][extname]',
            },
        },
    },
});
