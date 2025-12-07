# ReactBundle v2.0 - Configuration Reference

Complete configuration guide for ReactBundle with all available options and examples.

**Reading time:** ~20 minutes  
**Difficulty:** Intermediate  
**Last updated:** 2024

---

## Table of Contents

1. [Configuration Files](#configuration-files)
2. [Basic Configuration](#basic-configuration)
3. [Advanced Options](#advanced-options)
4. [Environment-Specific Configuration](#environment-specific-configuration)
5. [Package.json Configuration](#packagejson-configuration)
6. [Vite Configuration](#vite-configuration)
7. [Troubleshooting](#troubleshooting)

---

## Configuration Files

ReactBundle uses YAML configuration in `config/packages/react.yaml`:

```yaml
# config/packages/react.yaml
react:
    build_dir: 'build'                          # Output directory
    assets_dir: 'assets'                        # Source directory
    vite_server: 'http://localhost:3000'        # Dev server URL
    debug: false                                # Debug logging
    manifest_cache_size: 10                     # Cache size
```

### Configuration Priority

ReactBundle loads configuration in this order (highest priority first):

1. **Environment-specific:** `config/packages/{ENV}/react.yaml`
2. **Main config:** `config/packages/react.yaml`
3. **Bundle defaults:** Hardcoded in ReactExtension.php

Example for environment-specific override:
```bash
config/
├── packages/
│   ├── react.yaml                   # Default (all environments)
│   ├── dev/
│   │   └── react.yaml               # Development override
│   ├── prod/
│   │   └── react.yaml               # Production override
│   └── test/
│       └── react.yaml               # Testing override
```

---

## Basic Configuration

### 1. Build Directory

```yaml
react:
    build_dir: 'build'  # Where compiled assets go
```

**What happens:**
- Vite outputs to `public/{build_dir}/`
- Manifest created at `public/{build_dir}/manifest.json`
- JS/CSS bundles stored in `public/{build_dir}/assets/`

**Change it:**
```yaml
react:
    build_dir: 'vite'  # Output to public/vite/
```

### 2. Assets Directory

```yaml
react:
    assets_dir: 'assets'  # Where source files are
```

**Expected structure:**
```
assets/
├── js/app.jsx              # Entry point
└── React/
    ├── index.js            # Component exports
    ├── Components/         # React components
    ├── hooks/             # Custom hooks
    └── utils/             # Utilities
```

**Change it:**
```yaml
react:
    assets_dir: 'app/assets'  # Use different directory
```

### 3. Vite Server URL

```yaml
react:
    vite_server: 'http://localhost:3000'  # Dev server address
```

**Used for:**
- Hot Module Replacement (HMR) in development
- Asset loading in dev mode
- Script tag generation in Twig

**Change it:**
```yaml
# Development (local)
react:
    vite_server: 'http://localhost:3000'

# Development (with SSL)
react:
    vite_server: 'https://localhost:3000'

# Docker development
react:
    vite_server: 'http://vite:3000'

# Production (CDN)
react:
    vite_server: 'https://cdn.example.com'
```

### 4. Debug Mode

```yaml
react:
    debug: false  # No logging
```

**Enable for development:**
```yaml
react:
    debug: true   # Verbose logging
```

**What gets logged:**
- Component rendering with props
- Build manifest loading
- Performance metrics
- Cache hits/misses
- Error details

**View logs:**
```bash
tail -f var/log/dev.log | grep -i "react"
```

### 5. Manifest Cache

```yaml
react:
    manifest_cache_size: 10  # Cache 10 manifests in memory
```

**Purpose:**
- Improve production performance
- Reduce manifest.json file reads
- LRU eviction when full

**Disable cache:**
```yaml
react:
    manifest_cache_size: 0  # No caching
```

---

## Advanced Options

### Extended Configuration

Full configuration with all options:

```yaml
react:
    # File paths
    build_dir: 'build'
    assets_dir: 'assets'
    
    # Development server
    vite_server: 'http://localhost:3000'
    
    # Performance & monitoring
    debug: false
    manifest_cache_size: 10
    
    # Optional: Custom entry point name
    entry_point: 'app'
    
    # Optional: Enable source maps
    enable_source_maps: true
    
    # Optional: Custom Vite config path
    vite_config_path: 'vite.config.js'
    
    # Optional: Build timeout (seconds)
    build_timeout: 60
    
    # Optional: Retry settings
    npm_install_retry_max: 3
    npm_install_retry_delay: 2
```

### Custom Entry Points

If you have multiple entry points:

```yaml
react:
    # Primary entry point
    entry_point: 'app'
    
    # Additional entry points can be configured in vite.config.js
    # See "Vite Configuration" section below
```

### Performance Optimization

For high-traffic production environments:

```yaml
# config/packages/prod/react.yaml
react:
    build_dir: 'build'
    assets_dir: 'assets'
    vite_server: 'https://cdn.example.com'
    debug: false
    
    # Production optimizations
    manifest_cache_size: 50              # Larger cache
    enable_source_maps: false            # Disable for smaller bundle
    build_timeout: 120                   # Allow longer builds
```

### Development Optimization

For local development speed:

```yaml
# config/packages/dev/react.yaml
react:
    build_dir: 'build'
    assets_dir: 'assets'
    vite_server: 'http://localhost:3000'
    debug: true
    
    # Development optimizations
    manifest_cache_size: 0               # No cache, reload each time
    enable_source_maps: true             # Full debugging
    build_timeout: 30                    # Quick fail on errors
```

---

## Environment-Specific Configuration

### Development Environment

**File: `config/packages/dev/react.yaml`**

```yaml
react:
    build_dir: 'build'
    assets_dir: 'assets'
    vite_server: 'http://localhost:3000'
    debug: true                          # Verbose logging
    manifest_cache_size: 0               # Fresh loads
```

**Environment variables (`.env.local`):**
```env
APP_ENV=dev
APP_DEBUG=true
VITE_SERVER_URL=http://localhost:3000
```

**Run with:**
```bash
php bin/console react:build --dev
```

### Production Environment

**File: `config/packages/prod/react.yaml`**

```yaml
react:
    build_dir: 'build'
    assets_dir: 'assets'
    vite_server: 'https://your-domain.com'  # Your actual domain
    debug: false                            # No logging
    manifest_cache_size: 10                 # Cache for performance
```

**Environment variables (`.env`):**
```env
APP_ENV=prod
APP_DEBUG=false
VITE_SERVER_URL=https://your-domain.com
```

**Run with:**
```bash
php bin/console react:build --prod
```

### Testing Environment

**File: `config/packages/test/react.yaml`**

```yaml
react:
    build_dir: 'build'
    assets_dir: 'assets'
    vite_server: 'http://localhost:3000'
    debug: true                          # Debug failing tests
    manifest_cache_size: 0               # Fresh for each test
```

**Environment variables (`.env.test`):**
```env
APP_ENV=test
APP_DEBUG=true
DATABASE_URL=sqlite:///%kernel.cache_dir%/test.db
```

**Run with:**
```bash
php bin/console --env=test react:build --dev
```

### Docker Environment

**File: `config/packages/docker/react.yaml`**

```yaml
react:
    build_dir: 'build'
    assets_dir: 'assets'
    vite_server: 'http://vite:3000'      # Docker service name
    debug: false
    manifest_cache_size: 10
```

**Docker Compose setup:**
```yaml
# docker-compose.yml
version: '3.8'

services:
    app:
        image: php:8.2-fpm
        volumes:
            - .:/app
        depends_on:
            - vite
    
    vite:
        image: node:18
        working_dir: /app
        volumes:
            - .:/app
        command: npm run dev
        ports:
            - "3000:3000"
        environment:
            VITE_SERVER_URL: http://vite:3000
```

---

## Package.json Configuration

Configure npm scripts for building:

```json
{
    "name": "symfony-react-bundle",
    "version": "2.0.0",
    "scripts": {
        "dev": "vite",
        "build": "vite build",
        "preview": "vite preview",
        "test": "vitest",
        "test:ui": "vitest --ui",
        "coverage": "vitest --coverage",
        "lint": "eslint . --ext .jsx,.js,.ts,.tsx",
        "lint:fix": "eslint . --ext .jsx,.js,.ts,.tsx --fix",
        "type-check": "tsc --noEmit"
    },
    "dependencies": {
        "react": "^18.2.0",
        "react-dom": "^18.2.0"
    },
    "devDependencies": {
        "@vitejs/plugin-react": "^4.0.0",
        "vite": "^5.0.0",
        "vitest": "^1.0.0",
        "@testing-library/react": "^14.0.0",
        "@testing-library/jest-dom": "^6.0.0",
        "eslint": "^8.0.0",
        "typescript": "^5.0.0"
    }
}
```

### Available Scripts

| Script | Purpose |
|--------|---------|
| `npm run dev` | Start Vite dev server (HMR) |
| `npm run build` | Build optimized production bundles |
| `npm run preview` | Preview production build locally |
| `npm test` | Run unit tests |
| `npm run lint` | Check code style |
| `npm run type-check` | Verify TypeScript types |

---

## Vite Configuration

Create `vite.config.js` in project root:

```javascript
import { defineConfig } from 'vite';
import react from '@vitejs/plugin-react';
import path from 'path';

export default defineConfig({
    plugins: [react()],
    
    // Build settings
    build: {
        outDir: 'public/build',
        assetsDir: 'assets',
        sourcemap: false,
        minify: 'terser',
        reportCompressedSize: false,
        target: 'es2020',
    },
    
    // Development server
    server: {
        // Accept connections from Docker/external hosts
        middlewareMode: true,
        cors: true,
        
        // HMR configuration
        hmr: {
            host: 'localhost',
            port: 3000,
            protocol: 'http',
        },
        
        // Auto-reload on changes
        watch: {
            usePolling: false,
        },
    },
    
    // Module aliases
    resolve: {
        alias: {
            '@': path.resolve(__dirname, './assets/React'),
            '@components': path.resolve(__dirname, './assets/React/Components'),
            '@hooks': path.resolve(__dirname, './assets/React/hooks'),
            '@utils': path.resolve(__dirname, './assets/React/utils'),
        },
    },
    
    // Environment variables
    define: {
        __APP_VERSION__: JSON.stringify(process.env.npm_package_version),
    },
});
```

### Advanced Vite Configuration

For multiple entry points:

```javascript
export default defineConfig({
    build: {
        rollupOptions: {
            input: {
                app: '/assets/js/app.jsx',
                admin: '/assets/js/admin.jsx',
                checkout: '/assets/js/checkout.jsx',
            },
            output: {
                entryFileNames: '[name]-[hash].js',
                chunkFileNames: 'chunks/[name]-[hash].js',
                assetFileNames: 'assets/[name]-[hash][extname]',
            },
        },
    },
});
```

### HMR Configuration (Docker)

For development with Docker:

```javascript
export default defineConfig({
    server: {
        hmr: {
            host: 'docker.internal',  // Mac/Windows
            port: 3000,
            protocol: 'http',
        },
    },
});

// Or use environment variable:
// export default defineConfig({
//     server: {
//         hmr: {
//             host: process.env.VITE_HMR_HOST || 'localhost',
//             port: parseInt(process.env.VITE_HMR_PORT) || 3000,
//         },
//     },
// });
```

---

## Troubleshooting Configuration

### Issue 1: Manifest Not Reloading

**Problem:** Changes to code don't reflect in manifest.

**Solution:**
```yaml
# config/packages/dev/react.yaml
react:
    manifest_cache_size: 0  # Disable cache in development
```

Then rebuild:
```bash
php bin/console cache:clear
php bin/console react:build --dev
```

### Issue 2: HMR Connection Refused

**Problem:** Hot reload not working, connection refused.

**Symptoms:** Console shows "Cannot connect to ws://localhost:3000"

**Solutions:**

Check Vite is running:
```bash
ps aux | grep vite
```

Update HMR configuration:
```javascript
// vite.config.js
export default defineConfig({
    server: {
        hmr: {
            host: window.location.hostname,
            port: 3000,
        },
    },
});
```

### Issue 3: Port 3000 Already in Use

**Problem:** "EADDRINUSE: address already in use :::3000"

**Solution:**
```bash
# Find process using port 3000
lsof -i :3000

# Kill the process
kill -9 <PID>

# Or use different port
npm run dev -- --port 5173
```

Then update configuration:
```yaml
react:
    vite_server: 'http://localhost:5173'
```

### Issue 4: Build Fails with ENOENT

**Problem:** "Error: ENOENT: no such file or directory"

**Solution:**
```bash
# Verify directory structure
ls -la assets/React/
ls -la public/

# Recreate directories if missing
mkdir -p assets/React/Components
mkdir -p public/build

# Rebuild
php bin/console react:build --dev
```

### Issue 5: Assets Not Loading in Production

**Problem:** CSS/JS files return 404 in production.

**Solution:**

Check configuration:
```yaml
# config/packages/prod/react.yaml
react:
    build_dir: 'build'
    vite_server: 'https://your-domain.com'  # Use actual domain
    debug: false
```

Rebuild for production:
```bash
php bin/console react:build --prod
ls -la public/build/
```

Verify manifest exists:
```bash
cat public/build/manifest.json | head -20
```

---

## Security Configuration

### Environment Variable Protection

**In `.env.local` (never commit):**
```env
VITE_API_KEY=secret-key-here
VITE_STRIPE_KEY=pk_test_xxx
```

**In Twig template (safe):**
```twig
{{ react_component('PaymentForm', {
    stripeKey: app.request.attributes.get('stripeKey')
}) }}
```

**Not in code:**
```javascript
// ❌ DON'T DO THIS
const API_KEY = 'secret-key-here';  // Will be exposed in bundle!

// ✅ DO THIS INSTEAD
const API_KEY = import.meta.env.VITE_API_KEY;  // Only if prefixed VITE_
```

### Content Security Policy

Configure CSP for React:

```yaml
# config/packages/security.yaml
security:
    http_protocol_version: '2.0'
    headers:
        content_security_policy: "default-src 'self'; script-src 'self' 'unsafe-inline' ws://localhost:3000; style-src 'self' 'unsafe-inline';"
```

For production:
```yaml
content_security_policy: "default-src 'self'; script-src 'self'; style-src 'self';"
```

---

## Best Practices

### ✅ Do

- Use environment variables for sensitive data
- Cache manifests in production (not in dev)
- Enable debug mode only in development
- Use different configurations per environment
- Store vite.config.js in project root
- Set proper build timeout for CI/CD

### ❌ Don't

- Commit `.env.local` file
- Use manifest cache in development
- Enable debug mode in production
- Mix dev/prod configurations
- Disable source maps without testing
- Hardcode API keys or secrets

---

## Performance Tuning

### Recommended Production Settings

```yaml
# config/packages/prod/react.yaml
react:
    build_dir: 'build'
    assets_dir: 'assets'
    vite_server: 'https://cdn.example.com'  # Use CDN if available
    debug: false
    manifest_cache_size: 20                  # Larger cache
```

### Recommended Development Settings

```yaml
# config/packages/dev/react.yaml
react:
    build_dir: 'build'
    assets_dir: 'assets'
    vite_server: 'http://localhost:3000'
    debug: true
    manifest_cache_size: 0  # Fresh every time
```

---

## Support

- 📖 **Full Documentation:** [README](../README.md)
- 🚀 **Getting Started:** [GETTING_STARTED.md](GETTING_STARTED.md)
- ⚙️ **Installation:** [INSTALLATION.md](INSTALLATION.md)
- 🐛 **Troubleshooting:** [TROUBLESHOOTING.md](TROUBLESHOOTING.md)
- 💬 **Questions:** [GitHub Discussions](https://github.com/julien-lin/reactBundleSymfony/discussions)

---

## License

MIT License - See [LICENSE](../LICENSE) for details
