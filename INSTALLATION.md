# ReactBundle v2.0 - Complete Installation Guide

Professional, step-by-step installation guide for ReactBundle with Symfony.

**Reading time:** ~15 minutes  
**Difficulty:** Beginner  
**Last updated:** 2024

---

## Table of Contents

1. [System Requirements](#system-requirements)
2. [Quick Installation (5 minutes)](#quick-installation)
3. [Detailed Installation](#detailed-installation)
4. [Configuration](#configuration)
5. [Verify Installation](#verify-installation)
6. [Troubleshooting](#troubleshooting)
7. [Next Steps](#next-steps)

---

## System Requirements

### Mandatory

| Component | Version | Check |
|-----------|---------|-------|
| PHP | 8.2+ | `php --version` |
| Symfony | 6.0+ or 7.0+ | `symfony --version` |
| Node.js | 18.0+ | `node --version` |
| npm | 9.0+ | `npm --version` |
| Composer | 2.0+ | `composer --version` |

### Recommended

- Git (for version control)
- Docker (for consistent environments)
- npm/nvm (for Node version management)
- Vite 5.0+ (for faster builds)

### Optional Dependencies

- PHPUnit 12.5+ (for testing components)
- Webpack 5+ (alternative to Vite)

---

## Quick Installation (5 minutes)

### For the Impatient

```bash
# 1. Add to existing Symfony project
composer require julien-lin/react-bundle-symfony

# 2. Build assets
php bin/console react:build --dev

# 3. Done! Create first component
mkdir -p assets/React/Components
# Create component in assets/React/Components/Demo.jsx

# 4. Use in template
# {{ react_component('Demo', {}) }} in your Twig template
```

That's it! Jump to [Verify Installation](#verify-installation).

---

## Detailed Installation

### Prerequisites Check

Before starting, verify your system:

```bash
# Check PHP version
php --version
# Output should show PHP 8.2.0 or higher

# Check if Composer is installed
composer --version
# Output should show Composer 2.0 or higher

# Check Node.js and npm
node --version
# Output should show v18.0.0 or higher
npm --version
# Output should show 9.0.0 or higher
```

If any requirement is missing, see [Requirements Installation](#requirements-installation).

### Step 1: Install Composer Package

```bash
# Navigate to your Symfony project root
cd /path/to/your/symfony/project

# Add ReactBundle via Composer
composer require julien-lin/react-bundle-symfony
```

**What happens:**
1. ✅ Downloads ReactBundle package
2. ✅ Auto-registers bundle in `config/bundles.php`
3. ✅ Generates configuration in `config/packages/react.yaml`
4. ✅ Installs npm dependencies automatically
5. ✅ Creates required directories

**Expected output:**
```
Using version ^2.0 for julien-lin/react-bundle-symfony
./composer.json has been updated
Running composer update julien-lin/react-bundle-symfony
Loading composer repositories with package data
Installing dependencies
...
Successfully installed 1 package
```

### Step 2: Verify Bundle Registration

```bash
# Check if bundle was auto-registered
cat config/bundles.php
```

You should see:
```php
return [
    // ... other bundles
    JulienLin\ReactBundle\ReactBundle::class => ['all' => true],
];
```

If not present, add it manually:
```php
// config/bundles.php
return [
    // ... existing bundles
    JulienLin\ReactBundle\ReactBundle::class => ['all' => true],
];
```

### Step 3: Create Configuration File

Create `config/packages/react.yaml`:

```yaml
# Configuration for ReactBundle
react:
    # Build output directory (relative to public/)
    build_dir: 'build'
    
    # Asset source directory (relative to project root)
    assets_dir: 'assets'
    
    # Development server URL (for HMR - Hot Module Replacement)
    vite_server: 'http://localhost:3000'
    
    # Enable debug logging
    debug: false
    
    # Manifest cache size (production)
    manifest_cache_size: 10
```

**What each setting does:**

| Setting | Default | Purpose |
|---------|---------|---------|
| `build_dir` | `build` | Where compiled assets are stored |
| `assets_dir` | `assets` | Where source React files are located |
| `vite_server` | `http://localhost:3000` | Dev server URL for HMR |
| `debug` | `false` | Log component rendering details |
| `manifest_cache_size` | `10` | Cache up to 10 build manifests |

### Step 4: Create Directory Structure

```bash
# Create React component directories
mkdir -p assets/React/Components
mkdir -p assets/React/hooks
mkdir -p assets/React/utils
mkdir -p assets/React/types
mkdir -p assets/React/context

# Create build output directory
mkdir -p public/build

# Verify structure
find assets -type d | head -10
```

Expected structure:
```
assets/
├── js/
│   └── app.jsx              (Twig component mount point)
└── React/
    ├── index.js             (Central component export)
    ├── Components/          (React components)
    ├── hooks/               (Custom React hooks)
    ├── utils/               (Utility functions)
    ├── types/               (TypeScript types, optional)
    └── context/             (React context files)
```

### Step 5: Create Entry Point

Create `assets/React/index.js`:

```javascript
/**
 * Central export point for all React components
 * 
 * This file should export all React components used in Twig templates.
 * 
 * Pattern:
 * - Each component gets a named export
 * - Components are auto-mounted from data attributes
 * - Props are passed via data-props JSON
 * 
 * Usage in Twig:
 *   {{ react_component('ComponentName', props) }}
 */

// Export components here as you create them
// export { default as Header } from './Components/Header';
// export { default as Footer } from './Components/Footer';
// export { default as Dashboard } from './Components/Dashboard';

// Optionally export hooks and utilities
// export * from './hooks';
// export * as Utils from './utils';
```

### Step 6: Create Application Entry Point

Create `assets/js/app.jsx`:

```jsx
/**
 * Main application entry point for Vite bundler
 * 
 * This file:
 * - Imports React and ReactDOM
 * - Sets up component mounting
 * - Provides HMR in development
 * - Handles error boundaries
 */

import React from 'react';
import { createRoot } from 'react-dom/client';

// Import all React components
import * as ReactComponents from '../React';

/**
 * Auto-mount React components based on Twig rendering
 * 
 * Twig renders:
 *   <div data-react-component="ComponentName" data-props='{"key":"value"}'></div>
 * 
 * This script finds those divs and mounts the components
 */
document.querySelectorAll('[data-react-component]').forEach(element => {
    const componentName = element.dataset.reactComponent;
    const propsJson = element.dataset.props || '{}';
    
    try {
        // Parse props from JSON
        const props = JSON.parse(propsJson);
        
        // Get component from imports
        const Component = ReactComponents[componentName];
        
        if (Component) {
            // Mount React component
            const root = createRoot(element);
            root.render(<Component {...props} />);
            
            if (process.env.NODE_ENV === 'development') {
                console.log(`✓ Mounted component: ${componentName}`);
            }
        } else {
            console.warn(
                `⚠ Component "${componentName}" not found in ReactComponents. ` +
                `Check assets/React/index.js exports.`
            );
        }
    } catch (error) {
        console.error(
            `✗ Error mounting component "${componentName}": ${error.message}`
        );
    }
});

// Optional: Make React available globally in development
if (process.env.NODE_ENV === 'development') {
    window.React = React;
    window.ReactComponents = ReactComponents;
    console.log('ReactBundle v2.0 loaded (development mode)');
}
```

### Step 7: Configure Twig Base Template

Update `templates/base.html.twig`:

```twig
<!DOCTYPE html>
<html lang="{{ app.request.locale }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{% block title %}Welcome{% endblock %}</title>
    
    {# Vite CSS entry point #}
    {{ vite_entry_link_tags('app') }}
</head>
<body>
    {% block content %}{% endblock %}
    
    {# Vite JS entry point #}
    {{ vite_entry_script_tags('app') }}
    
    {% block javascripts %}{% endblock %}
</body>
</html>
```

**Key Twig Functions:**

| Function | Purpose |
|----------|---------|
| `{{ vite_entry_link_tags('app') }}` | Include CSS files |
| `{{ vite_entry_script_tags('app') }}` | Include JavaScript files |
| `{{ react_component('Name', props) }}` | Mount React component |

---

## Configuration

### Environment-Specific Configuration

Create multiple config files for different environments:

**Development (`config/packages/dev/react.yaml`):**
```yaml
react:
    debug: true
    vite_server: 'http://localhost:3000'
```

**Production (`config/packages/prod/react.yaml`):**
```yaml
react:
    debug: false
    manifest_cache_size: 10
    vite_server: 'https://cdn.example.com'  # Optional CDN
```

**Testing (`config/packages/test/react.yaml`):**
```yaml
react:
    debug: true
    vite_server: 'http://localhost:3000'
```

### Environment Variables

Create `.env` or `.env.local`:

```env
# Development
APP_ENV=dev
APP_DEBUG=true
VITE_SERVER_URL=http://localhost:3000

# Production
# APP_ENV=prod
# APP_DEBUG=false
# VITE_SERVER_URL=https://your-domain.com
```

### npm Configuration

Update `package.json` scripts:

```json
{
    "scripts": {
        "dev": "vite",
        "build": "vite build",
        "preview": "vite preview",
        "test": "vitest",
        "lint": "eslint . --ext .jsx,.js"
    },
    "dependencies": {
        "react": "^18.2.0",
        "react-dom": "^18.2.0"
    },
    "devDependencies": {
        "@vitejs/plugin-react": "^4.0.0",
        "vite": "^5.0.0",
        "vitest": "^1.0.0"
    }
}
```

---

## Verify Installation

### Quick Verification

```bash
# 1. Check bundle registration
php bin/console list | grep react

# Expected output:
# react:build       Build React assets with Vite
```

### Full Verification Checklist

Run this verification script:

```bash
#!/bin/bash

echo "========== ReactBundle v2.0 Installation Verification =========="

# 1. PHP version
echo -n "✓ PHP version: "
php --version | head -n 1

# 2. Symfony version
echo -n "✓ Symfony version: "
symfony --version 2>/dev/null || composer show symfony/framework-bundle | head -n 1

# 3. Node.js version
echo -n "✓ Node.js version: "
node --version

# 4. npm version
echo -n "✓ npm version: "
npm --version

# 5. Bundle registration
echo -n "✓ Bundle registered: "
grep -q "ReactBundle" config/bundles.php && echo "YES" || echo "NO"

# 6. Configuration file
echo -n "✓ Configuration exists: "
[ -f "config/packages/react.yaml" ] && echo "YES" || echo "NO"

# 7. Directories created
echo -n "✓ Assets directory: "
[ -d "assets/React" ] && echo "YES" || echo "NO"

echo "========== Verification Complete =========="
```

Save as `verify_installation.sh` and run:
```bash
chmod +x verify_installation.sh
./verify_installation.sh
```

### Build Test

```bash
# Build in development mode
php bin/console react:build --dev

# Check output
[ -f "public/build/manifest.json" ] && echo "✓ Build successful" || echo "✗ Build failed"
```

---

## Troubleshooting

### Issue 1: "Bundle not found" Error

**Symptom:**
```
Class "JulienLin\ReactBundle\ReactBundle" not found
```

**Solution:**
```bash
# 1. Clear cache
php bin/console cache:clear

# 2. Composer update
composer update

# 3. Verify registration
cat config/bundles.php
```

### Issue 2: npm Install Fails

**Symptom:**
```
npm ERR! code ERESOLVE
npm ERR! ERESOLVE unable to resolve dependency tree
```

**Solution:**
```bash
# Use npm's legacy peer dependencies
npm install --legacy-peer-deps

# Or update npm
npm install -g npm@latest
npm install
```

### Issue 3: "Manifest Not Found" Error

**Symptom:**
```
RuntimeException: Manifest file not found at...
```

**Solution:**
```bash
# Build assets first
php bin/console react:build --dev

# Or for production
php bin/console react:build --prod

# Verify manifest created
ls -la public/build/manifest.json
```

### Issue 4: HMR Not Working

**Symptom:**
Changes to components don't hot-reload in browser.

**Solution:**
```bash
# 1. Ensure dev mode
php bin/console react:build --dev

# 2. Check vite_server configuration
grep vite_server config/packages/react.yaml
# Should show: vite_server: 'http://localhost:3000'

# 3. Verify port 3000 is free
lsof -i :3000

# 4. Check browser console for errors
# Press F12 in browser and check Console tab
```

### Issue 5: Component Not Rendering

**Symptom:**
Nothing appears in browser where component should be.

**Solution:**
```bash
# 1. Check component export
grep -r "export" assets/React/index.js

# 2. Verify Twig template has correct name
# In template: {{ react_component('ComponentName', {}) }}
# Must match export name exactly (case-sensitive)

# 3. Check browser console for errors
# Open browser DevTools (F12)
# Look for console errors

# 4. Enable debug logging
# Edit config/packages/react.yaml:
#   debug: true
# Then check logs
tail -f var/log/dev.log | grep -i react
```

### Issue 6: Build Takes Too Long

**Symptom:**
`php bin/console react:build` takes more than 30 seconds.

**Solution:**
```bash
# 1. Use watch mode for development
php bin/console react:build --watch

# 2. Build only once for production
php bin/console react:build --prod

# 3. Profile build time
time php bin/console react:build --dev

# 4. Check for large dependencies
npm ls | head -20
```

---

## Next Steps

1. **Create Your First Component** → [Getting Started](GETTING_STARTED.md)
2. **Learn Advanced Features** → [README](../README.md)
3. **Configure Production** → [Deployment Guide](DEPLOYMENT.md)
4. **Monitor Performance** → [Performance Tuning](PERFORMANCE.md)
5. **Debug Issues** → [Troubleshooting](TROUBLESHOOTING.md)

---

## Support & Resources

- 📖 **Documentation:** [Full README](../README.md)
- 🚀 **Quick Start:** [QUICKSTART.md](QUICKSTART.md)
- 🛠️ **Getting Started:** [GETTING_STARTED.md](GETTING_STARTED.md)
- ⚙️ **Configuration:** [Configuration Guide](CONFIG.md)
- 🐛 **Troubleshooting:** [Troubleshooting Guide](TROUBLESHOOTING.md)
- 💬 **Questions:** [GitHub Discussions](https://github.com/julien-lin/reactBundleSymfony/discussions)
- 🐛 **Report Bugs:** [GitHub Issues](https://github.com/julien-lin/reactBundleSymfony/issues)
- 💝 **Support:** [GitHub Sponsors](https://github.com/sponsors/julien-lin)

---

## License

MIT License - See [LICENSE](../LICENSE) file for details
