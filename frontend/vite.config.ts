import tailwindcss from '@tailwindcss/vite';
import { sveltekit } from '@sveltejs/kit/vite';
import { defineConfig } from 'vite';

export default defineConfig({
  plugins: [tailwindcss(), sveltekit()],
  server: {
    allowedHosts: ['feednews.ddev.site'],
    host: true,
    port: 5173,
    strictPort: true,
      hmr: {
        host: 'feednews.ddev.site',
        protocol: 'wss',
        clientPort: 443,
    },
  },
});
