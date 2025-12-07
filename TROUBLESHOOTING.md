# ReactBundle v2.0 - Troubleshooting Guide

Common issues and their solutions for ReactBundle users.

**Reading time:** ~30 minutes  
**Difficulty:** All levels  
**Last updated:** 2024

---

## Table of Contents

1. [Installation Issues](#installation-issues)
2. [Build & Compilation Issues](#build--compilation-issues)
3. [Runtime Issues](#runtime-issues)
4. [Performance Issues](#performance-issues)
5. [Development Issues](#development-issues)
6. [Security Issues](#security-issues)
7. [Deployment Issues](#deployment-issues)
8. [Getting Help](#getting-help)

---

## Installation Issues

### "Class 'JulienLin\ReactBundle\ReactBundle' not found"

**When:** After `composer require`

**Cause:** Bundle not registered in `config/bundles.php`

**Solution:**
```bash
# Clear cache
php bin/console cache:clear

# Verify bundle is registered
grep -r "ReactBundle" config/bundles.php

# If missing, add manually:
# config/bundles.php
return [
    JulienLin\ReactBundle\ReactBundle::class => ['all' => true],
];

# Regenerate cache
php bin/console cache:warmup
```

---

### "npm ERR! code ERESOLVE"

**When:** Running `composer require` (npm install fails automatically)

**Cause:** npm peer dependency conflict

**Solution:**
```bash
# Option 1: Use legacy peer dependencies (quick fix)
npm install --legacy-peer-deps

# Option 2: Update npm to latest
npm install -g npm@latest
npm install

# Option 3: Use npm 8+ with force flag
npm install --force

# After fixing, rerun composer
composer require julien-lin/react-bundle-symfony
```

---

### "PHP Version Too Low"

**When:** Installation fails with PHP < 8.2

**Symptom:**
```
Your PHP version (x.x.x) is too old
Requires PHP 8.2+
```

**Solution:**
```bash
# Check current PHP version
php --version

# If too old, update:
# macOS
brew upgrade php

# Ubuntu/Debian
sudo apt-get install php8.2-cli

# Set as default (if multiple versions)
sudo update-alternatives --install /usr/bin/php php /usr/bin/php8.2 1

# Verify
php --version
```

---

### "composer.json has unsupported properties"

**When:** PHP 8.0 or earlier trying to use ReactBundle

**Cause:** ReactBundle requires PHP 8.2+

**Solution:**
```bash
# Upgrade PHP first (see above)

# Then retry
composer require julien-lin/react-bundle-symfony
```

---

### "Directory 'assets' does not exist"

**When:** After installation, assets directory missing

**Symptom:**
```
Directory 'assets' does not exist for path "assets"
```

**Solution:**
```bash
# Create required directories
mkdir -p assets/React/Components
mkdir -p assets/React/hooks
mkdir -p assets/js

# Create entry point
touch assets/React/index.js
touch assets/js/app.jsx

# Verify
ls -la assets/
```

---

## Build & Compilation Issues

### "Manifest File Not Found"

**When:** `react:build` command fails or completes but manifest is missing

**Symptom:**
```
RuntimeException: Manifest file not found at path/to/manifest.json
```

**Solution:**
```bash
# 1. Ensure build directory exists
mkdir -p public/build

# 2. Run build explicitly
php bin/console react:build --dev

# 3. Verify manifest was created
ls -la public/build/manifest.json

# 4. Check manifest content
cat public/build/manifest.json | jq '.'

# 5. If still missing, check npm
npm run build

# 6. Check for build errors
php bin/console react:build --dev --verbose
```

---

### "vite.config.js Not Found"

**When:** Build fails looking for Vite configuration

**Symptom:**
```
Error: Cannot find vite.config.js
```

**Solution:**
```bash
# Create vite.config.js in project root
cat > vite.config.js << 'EOF'
import { defineConfig } from 'vite';
import react from '@vitejs/plugin-react';

export default defineConfig({
    plugins: [react()],
    build: {
        outDir: 'public/build',
        assetsDir: 'assets',
    },
});
EOF

# Install required plugin
npm install --save-dev @vitejs/plugin-react

# Try building again
php bin/console react:build --dev
```

---

### "Entry Point Not Found"

**When:** Build completes but entry point missing from manifest

**Symptom:**
```
Entry point 'app' not found in manifest
```

**Solution:**
```bash
# 1. Verify entry file exists
ls -la assets/js/app.jsx

# 2. Check vite.config.js has correct input
cat vite.config.js | grep -A5 "input:"

# Should reference assets/js/app.jsx

# 3. Verify React components are imported
cat assets/React/index.js

# 4. Rebuild
npm run build

# 5. Check manifest
cat public/build/manifest.json | jq '.["app.js"]'
```

---

### "Node Version Too Old"

**When:** Build fails with Node.js < 18

**Symptom:**
```
Node.js v16.x.x is not supported
Minimum: v18.0.0
```

**Solution:**
```bash
# Check current version
node --version

# Update Node.js
# Using nvm (recommended)
nvm install 18
nvm use 18
nvm alias default 18

# Or using Homebrew (macOS)
brew upgrade node

# Or using package manager (Linux)
sudo apt-get install nodejs npm

# Verify
node --version  # Should be v18+
npm --version   # Should be 9+
```

---

### "ENOENT: no such file or directory, open 'vite.config.js'"

**When:** npm run build fails

**Cause:** vite.config.js missing or in wrong location

**Solution:**
```bash
# 1. Create vite.config.js in project ROOT (not in assets/)
ls -la | grep vite.config.js

# 2. If missing, create it
cat > /path/to/project/vite.config.js << 'EOF'
import { defineConfig } from 'vite';
import react from '@vitejs/plugin-react';

export default defineConfig({
    plugins: [react()],
    build: {
        outDir: 'public/build',
        assetsDir: 'assets',
    },
});
EOF

# 3. Verify location (should be at root level)
pwd
ls vite.config.js

# 4. Rebuild
npm run build
```

---

### "Build Takes Forever (> 60 seconds)"

**When:** `react:build` or `npm run build` never completes

**Solution:**
```bash
# 1. Check available disk space
df -h

# 2. Check available memory
free -h

# 3. Profile build
time npm run build

# 4. Check for hung processes
ps aux | grep vite
ps aux | grep node

# 5. Kill any stuck processes
pkill -f vite
pkill -f node

# 6. Try building again with explicit timeout
timeout 30 npm run build || echo "Build timeout"

# 7. Clean and rebuild
rm -rf node_modules/.vite
npm run build -- --force
```

---

## Runtime Issues

### "Component Not Rendering"

**When:** Component should appear in page but doesn't

**Symptom:**
Empty element where component should be, no errors in console

**Solution:**

**Step 1: Check Twig template**
```twig
{# Check the component rendering line #}
{{ react_component('ComponentName', { prop: 'value' }) }}

{# Verify script tags are present #}
{{ vite_entry_script_tags('app') }}
{{ vite_entry_link_tags('app') }}
```

**Step 2: Verify component export**
```javascript
// assets/React/index.js - must export the component
export { default as ComponentName } from './Components/ComponentName';

// ✗ Wrong (export from wrong path)
export { default as ComponentName } from './ComponentName';
```

**Step 3: Check browser console**
```javascript
// Open browser console (F12) and look for:

// ✓ Expected:
// ✓ Mounted component: ComponentName

// ✗ Errors like:
// ⚠ Component "ComponentName" not found in ReactComponents
// ✗ Error mounting component "ComponentName"
```

**Step 4: Verify props are valid JSON**
```twig
{# Props must be serializable to JSON #}

{# ✓ Valid #}
{{ react_component('Component', { name: 'John', age: 30 }) }}

{# ✗ Invalid (object/Twig object) #}
{{ react_component('Component', app.user) }}

{# ✓ Solution: Extract needed properties #}
{{ react_component('Component', { 
    name: app.user.username,
    email: app.user.email 
}) }}
```

**Step 5: Check component exists**
```bash
# Verify file exists
ls assets/React/Components/ComponentName.jsx

# List all exported components
grep "^export" assets/React/index.js
```

---

### "Component 'XYZ' not found in ReactComponents"

**When:** Browser console shows this warning

**Cause:** Component not exported from `assets/React/index.js`

**Solution:**
```javascript
// assets/React/index.js

// Current (incomplete):
export { default as Header } from './Components/Header';

// Add missing component:
export { default as Dashboard } from './Components/Dashboard';
export { default as Sidebar } from './Components/Sidebar';

// Or import and re-export all:
import * as Components from './Components';
export * from './Components';
```

Rebuild:
```bash
php bin/console react:build --dev
```

Then hard-refresh browser (Cmd+Shift+R or Ctrl+Shift+R).

---

### "Uncaught Error: Cannot find module 'react'"

**When:** Browser console shows module not found error

**Cause:** React dependency not installed or incorrect import

**Solution:**
```bash
# 1. Verify react is installed
npm list react

# If missing:
npm install react react-dom

# 2. Verify import in app.jsx
cat assets/js/app.jsx | grep "import React"

# Should have:
import React from 'react';
import { createRoot } from 'react-dom/client';

# 3. Rebuild
npm run build

# 4. Clear browser cache and refresh
# In browser: Cmd/Ctrl + Shift + Delete → Clear cache
```

---

### "HMR Connection Refused (Development Mode)"

**When:** Hot Module Replacement not working

**Symptom:**
```
[vite] failed to connect to WebSocket server
ws://localhost:3000
```

**Solution:**

**For local development:**
```bash
# 1. Verify Vite dev server is running
npm run dev

# 2. Check port 3000 is free
lsof -i :3000

# If in use, kill process:
kill -9 <PID>

# Or use different port:
npm run dev -- --port 5173

# 3. Update configuration
# config/packages/dev/react.yaml
react:
    vite_server: 'http://localhost:5173'  # Match npm port
```

**For Docker:**
```javascript
// vite.config.js
export default defineConfig({
    server: {
        hmr: {
            host: 'localhost',  // Changed from 'vite'
            port: 3000,
            protocol: 'http',
        },
    },
});
```

---

### "Cannot POST /build/manifest.json"

**When:** Build works but manifest endpoint returns error in production

**Cause:** Web server not configured to serve static files

**Solution:**

**Nginx:**
```nginx
location /build/ {
    try_files $uri =404;
    expires 1y;
    add_header Cache-Control "public, immutable";
}
```

**Apache:**
```apache
<Directory /var/www/app/public/build>
    Require all granted
</Directory>
```

**Verify:**
```bash
# Test manifest is accessible
curl https://your-domain.com/build/manifest.json | jq '.'

# Check file exists
ls -la public/build/manifest.json

# Check permissions
stat public/build/manifest.json | grep Access
```

---

## Performance Issues

### "React App Is Slow"

**When:** Pages with React components render slowly

**Solution:**

**Step 1: Identify slow component**
```bash
# Enable debug logging
# config/packages/dev/react.yaml
react:
    debug: true

# Check logs
tail -f var/log/dev.log | grep "duration_ms"

# Look for high duration values (> 1000ms = 1 second)
```

**Step 2: Run benchmark tests**
```bash
php bin/console phpunit tests/Service/RenderingBenchmarkTest.php
```

**Step 3: Optimize component**
```jsx
// Use memo for expensive components
import { memo } from 'react';

const SlowComponent = memo(({ data }) => {
    return <div>{/* render */}</div>;
}, (prevProps, nextProps) => {
    // Custom comparison if needed
    return prevProps.data === nextProps.data;
});

export default SlowComponent;
```

**Step 4: Check bundle size**
```bash
npm run build -- --analyze

# Look for large dependencies
npm ls | sort -k2 -rn | head -10
```

---

### "Bundle Too Large (> 500KB)"

**When:** Built JavaScript/CSS files are very large

**Solution:**

**Analyze bundle:**
```bash
# Install analyzer
npm install --save-dev rollup-plugin-visualizer

# Update vite.config.js
import { visualizer } from 'rollup-plugin-visualizer';

export default defineConfig({
    plugins: [
        react(),
        visualizer({ open: true }),
    ],
});

# Build and open visualization
npm run build
```

**Optimize bundle:**
```javascript
// vite.config.js
import { defineConfig } from 'vite';
import react from '@vitejs/plugin-react';

export default defineConfig({
    plugins: [react()],
    build: {
        minify: 'terser',              // Minify
        sourcemap: false,              // No source maps in prod
        rollupOptions: {
            output: {
                manualChunks(id) {
                    // Split large libraries
                    if (id.includes('react')) {
                        return 'react';
                    }
                },
            },
        },
    },
});
```

**Remove unused imports:**
```bash
# Check for unused packages
npm prune
npx depcheck

# Remove unused
npm uninstall unused-package
```

---

### "High Memory Usage During Rendering"

**When:** PHP process uses excessive memory rendering components

**Solution:**

**Check memory usage:**
```bash
# Monitor during rendering
# config/packages/dev/react.yaml
react:
    debug: true

# Check logs
tail -f var/log/dev.log | grep "memory_kb"

# Look for high memory values
```

**Optimize:**
```php
// src/Service/ReactRenderer.php
// Memory issue could be in prop handling

// ✓ Use generators for large data sets
public function renderComponents(array $components): string {
    foreach ($components as $component) {
        // Process one at a time
        yield $this->render($component);
    }
}

// ✓ Unset large objects after use
unset($largeData);
```

**Increase PHP limit if needed:**
```bash
# Temporarily
php -d memory_limit=512M bin/console react:build --dev

# Permanently (php.ini)
memory_limit = 512M
```

---

## Development Issues

### "Changes Not Reflecting (--dev Mode)"

**When:** Modified component files don't update in browser

**Cause:** File watching not enabled or Vite not running in dev mode

**Solution:**
```bash
# 1. Stop build if running
# Press Ctrl+C

# 2. Start dev mode properly
php bin/console react:build --dev

# NOT:
php bin/console react:build  # Wrong, uses default (production)

# 3. Verify Vite is watching
# Check for "compiled successfully" or "ready in Xms" messages

# 4. Hard refresh browser
# Cmd/Ctrl + Shift + R

# 5. Check file was actually changed
cat assets/React/Components/MyComponent.jsx | grep "new line"

# 6. If still not working, rebuild manually
php bin/console react:build --dev --force
```

---

### "Twig Function 'react_component' Not Found"

**When:** Template rendering fails

**Symptom:**
```
Unknown "react_component" function
```

**Cause:** ReactBundle Twig extension not registered

**Solution:**
```bash
# 1. Verify bundle registered
grep -r "ReactBundle" config/bundles.php

# 2. Clear cache
php bin/console cache:clear

# 3. Check Twig extension is loaded
php bin/console debug:container | grep -i "react"

# 4. If missing, manually register (config/services.yaml)
services:
    JulienLin\ReactBundle\Twig\ReactExtension: ~
    JulienLin\ReactBundle\Twig\ViteExtension: ~

# 5. Regenerate cache
php bin/console cache:warmup

# 6. Test in template
{{ react_component('Test', {}) }}
```

---

### "Props Not Passed to Component"

**When:** Component receives empty or incorrect props

**Solution:**

**Check JSON serialization:**
```php
// In controller
$props = [
    'name' => 'John',
    'age' => 30,
    'created' => new DateTime(),  // ✗ Not JSON serializable!
];

// Fix:
$props = [
    'name' => 'John',
    'age' => 30,
    'created' => $date->format('Y-m-d'),  // ✓ String
];
```

**Check template:**
```twig
{# Verify component receives props #}
{{ react_component('MyComponent', {
    name: 'John',
    settings: {
        theme: 'dark',
        notifications: true
    }
}) }}
```

**Debug in component:**
```jsx
import React from 'react';

const MyComponent = (props) => {
    console.log('Received props:', props);  // Check browser console
    return <div>{JSON.stringify(props)}</div>;
};

export default MyComponent;
```

---

## Security Issues

### "XSS Vulnerability: User Input in Props"

**When:** User-supplied data displayed in component

**Solution:**

**Safe approach:**
```php
// ✗ Don't do this
$userInput = $_GET['name'];
return $this->render('template', [
    'props' => ['text' => $userInput],
]);

// ✓ Do this
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\HtmlSanitizer\HtmlSanitizer;

$sanitized = HtmlSanitizer::sanitize($_GET['name']);
$props = ['text' => $sanitized];
```

**In component (as fallback):**
```jsx
// Never use dangerouslySetInnerHTML with user input
// ✗ Wrong
<div dangerouslySetInnerHTML={{ __html: userInput }} />

// ✓ Right
<div>{userInput}</div>  // React escapes automatically
```

---

### "Sensitive Data Exposed in JavaScript"

**When:** API keys or secrets appear in built bundles

**Symptom:**
```bash
# In public/build/assets/app-xxxx.js
const API_KEY = 'sk-1234567890';
```

**Solution:**

**Use environment variables:**
```javascript
// ✗ Never hardcode
const API_KEY = 'secret-key';

// ✓ Use env vars (prefixed with VITE_)
const API_KEY = import.meta.env.VITE_PUBLIC_KEY;
```

**In .env:**
```env
# ✓ Safe (only prefixed with VITE_ are exposed)
VITE_PUBLIC_KEY=pk_test_xxx

# ✗ NOT exposed
SECRET_API_KEY=sk_secret_xxx
```

**In component:**
```jsx
const stripeKey = import.meta.env.VITE_STRIPE_KEY;
// Only exposed values (prefixed VITE_) are included
```

---

### "Missing CSRF Token"

**When:** Forms in components fail CSRF validation

**Solution:**

**Pass CSRF token in props:**
```twig
{{ react_component('ContactForm', {
    csrf_token: csrf_token('form')
}) }}
```

**Use in component:**
```jsx
const ContactForm = ({ csrf_token }) => {
    const handleSubmit = async (e) => {
        e.preventDefault();
        
        const response = await fetch('/api/contact', {
            method: 'POST',
            headers: {
                'X-CSRF-Token': csrf_token,
            },
            body: JSON.stringify(formData),
        });
    };
    
    return (
        <form onSubmit={handleSubmit}>
            {/* form fields */}
        </form>
    );
};

export default ContactForm;
```

---

## Deployment Issues

### "Assets Missing in Production"

**When:** CSS/JS not loading on live site

**Solution:**
```bash
# 1. Verify build was executed
ls -la public/build/manifest.json

# 2. Check manifest content
cat public/build/manifest.json

# 3. Verify web server permissions
ls -la public/build/
chmod -R 755 public/build/

# 4. Check web server can read files
find public/build/ -type f | head -5 | xargs ls -la

# 5. Test asset is accessible
curl https://your-domain.com/build/manifest.json
```

---

### "Manifest Caching Issues"

**When:** Old assets served after deployment

**Solution:**
```bash
# Clear PHP manifest cache
rm -rf var/cache/prod/manifest_cache.*

# Clear browser cache
# In browser: Settings → Clear browsing data → Select all → Clear

# Force re-download
curl -H "Cache-Control: no-cache" https://your-domain.com/build/manifest.json

# Rebuild if needed
php bin/console react:build --prod
php bin/console cache:clear --env=prod
```

---

### "500 Error: Cannot Find Service"

**When:** Production fails with service container error

**Solution:**
```bash
# 1. Check services are configured
php bin/console debug:container | grep -i react

# 2. Verify configuration file exists
ls config/packages/prod/react.yaml

# 3. Check YAML syntax
php -r "yaml_parse_file('config/packages/prod/react.yaml');" || echo "YAML error"

# 4. Rebuild container
php bin/console cache:clear --env=prod

# 5. Check error logs
tail -f var/log/prod.log
```

---

## Getting Help

### Check Documentation

1. **README:** [README.md](../README.md)
2. **Installation:** [INSTALLATION.md](INSTALLATION.md)
3. **Getting Started:** [GETTING_STARTED.md](GETTING_STARTED.md)
4. **Configuration:** [CONFIG.md](CONFIG.md)
5. **Deployment:** [DEPLOYMENT.md](DEPLOYMENT.md)

### Online Resources

- 📖 [GitHub Documentation](https://github.com/julien-lin/reactBundleSymfony)
- 💬 [GitHub Discussions](https://github.com/julien-lin/reactBundleSymfony/discussions)
- 🐛 [Issue Tracker](https://github.com/julien-lin/reactBundleSymfony/issues)
- 📚 [Symfony Documentation](https://symfony.com/doc)
- ⚛️ [React Documentation](https://react.dev)

### Report Bugs

When reporting issues, include:

```markdown
**Environment:**
- OS: macOS/Linux/Windows
- PHP: 8.2.5 (run: php --version)
- Node.js: 18.0.0 (run: node --version)
- npm: 9.0.0 (run: npm --version)
- Symfony: 6.4 (run: symfony --version)

**Steps to Reproduce:**
1. Step one
2. Step two
3. Step three

**Expected Behavior:**
What should happen

**Actual Behavior:**
What actually happens

**Error Messages:**
```
Full error message from console/logs
```

**Additional Context:**
Any other relevant information
```

---

## Support Options

- 💝 [GitHub Sponsors](https://github.com/sponsors/julien-lin)
- 🐛 [GitHub Issues](https://github.com/julien-lin/reactBundleSymfony/issues)
- 💬 [GitHub Discussions](https://github.com/julien-lin/reactBundleSymfony/discussions)

---

## License

MIT License - See [LICENSE](../LICENSE) for details
