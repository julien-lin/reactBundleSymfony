import { defineConfig } from 'vite';
import react from '@vitejs/plugin-react';
import path from 'path';
import { fileURLToPath } from 'url';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

export default defineConfig({
  plugins: [react()],
  root: path.resolve(__dirname, 'assets'),
  base: '/build/',
  build: {
    outDir: path.resolve(__dirname, 'public/build'),
    emptyOutDir: true,
    manifest: true,
    rollupOptions: {
      input: {
        app: path.resolve(__dirname, 'assets/js/app.jsx')
      }
    }
  },
  server: {
    host: '0.0.0.0',  // Accepte les connexions externes (nécessaire pour Docker)
    port: 3000,
    hmr: {
      host: 'localhost',  // Pour le HMR depuis le navigateur
      port: 3000
    },
    watch: {
      usePolling: true  // Nécessaire pour Docker sur certains systèmes
    }
  },
  resolve: {
    alias: {
      '@': path.resolve(__dirname, 'assets/React')
    }
  }
});

