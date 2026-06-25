import { defineConfig } from 'vite';
import react from '@vitejs/plugin-react';
import tailwindcss from '@tailwindcss/vite';
import { fileURLToPath, URL } from 'node:url';

// The SPA is served at https://origynz.ddev.site, reverse-proxied by nginx to this
// dev server on 127.0.0.1:5173 inside the DDEV web container. HMR runs over wss
// through nginx on port 443.
export default defineConfig({
  plugins: [react(), tailwindcss()],
  resolve: {
    alias: {
      '@': fileURLToPath(new URL('./src', import.meta.url)),
      '@core': fileURLToPath(new URL('./src/core', import.meta.url)),
    },
  },
  server: {
    host: '0.0.0.0',
    port: 5173,
    strictPort: true,
    // Allow the DDEV proxy host (nginx forwards Host: origynz.ddev.site).
    allowedHosts: ['.ddev.site'],
    hmr: {
      host: 'origynz.ddev.site',
      protocol: 'wss',
      clientPort: 443,
    },
  },
});
