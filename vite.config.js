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
    // Chemin vers public/build du projet Symfony
    // Depuis vendor/julien-lin/react-bundle-symfony ou src/ReactBundle
    outDir: (() => {
      const bundlePath = __dirname;
      // Si dans vendor/, remonter de 3 niveaux pour atteindre la racine du projet
      if (bundlePath.includes('/vendor/')) {
        const vendorIndex = bundlePath.indexOf('/vendor/');
        const projectRoot = bundlePath.substring(0, vendorIndex);
        return path.resolve(projectRoot, 'public/build');
      }
      // Sinon, on est dans src/ReactBundle, remonter de 2 niveaux
      return path.resolve(__dirname, '../../public/build');
    })(),
    emptyOutDir: true,
    manifest: true,
    rollupOptions: {
      input: {
        app: path.resolve(__dirname, 'assets/js/app.jsx')
      }
    }
  },
  server: {
    host: '0.0.0.0',
    port: 3000,
    hmr: {
      host: 'localhost',
      port: 3000
    },
    watch: {
      usePolling: true
    }
  },
  resolve: {
    alias: {
      '@': path.resolve(__dirname, 'React')
    }
  }
});

