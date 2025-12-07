# Getting Started with ReactBundle v2.0

A comprehensive guide to get you up and running with production-ready React in Symfony.

## Prerequisites

- **PHP:** 8.2 or higher
- **Symfony:** 6.0, 6.4, or 7.0
- **Node.js:** 18.0 or higher
- **npm:** 9.0 or higher (comes with Node.js)

Verify installation:
```bash
php --version          # PHP 8.2+
symfony --version      # Symfony CLI
node --version         # Node.js 18+
npm --version          # npm 9+
```

---

## Installation Step-by-Step

### Step 1: Add the Bundle

```bash
cd your-symfony-project
composer require julien-lin/react-bundle-symfony
```

**What happens automatically:**
- Bundle is registered in `config/bundles.php`
- Configuration file created: `config/packages/react.yaml`
- npm dependencies installed (if npm available)
- Services registered in DI container

### Step 2: Verify Installation

Check that the bundle is installed:
```bash
php bin/console list | grep react
```

You should see:
```
react:build                 Build React assets with Vite
```

### Step 3: Create Your Project Structure

```bash
# Create directories for React components
mkdir -p assets/React/Components
mkdir -p assets/React/hooks
mkdir -p assets/React/utils

# Create entry point if it doesn't exist
touch assets/React/index.js
touch assets/js/app.jsx
```

### Step 4: Configure React Entry Point

Create `assets/React/index.js`:
```javascript
/**
 * Central export point for all React components
 * 
 * Structure:
 * - Components/      → UI components
 * - hooks/          → Custom React hooks
 * - utils/          → Utility functions
 * 
 * Usage in Twig:
 * {{ react_component('ComponentName', { prop: 'value' }) }}
 */

// Import and export your components
// export { default as Header } from './Components/Header';
// export { default as Footer } from './Components/Footer';
```

### Step 5: Configure React App Entry

Create/update `assets/js/app.jsx`:
```jsx
import React from 'react';
import { createRoot } from 'react-dom/client';

/**
 * Import all React components from the central index
 */
import * as ReactComponents from '../React';

/**
 * Auto-mount React components based on data attributes
 * This allows rendering components from Twig
 */
document.querySelectorAll('[data-react-component]').forEach(element => {
    const componentName = element.dataset.reactComponent;
    const propsJson = element.dataset.props || '{}';
    
    try {
        const props = JSON.parse(propsJson);
        const Component = ReactComponents[componentName];
        
        if (Component) {
            const root = createRoot(element);
            root.render(<Component {...props} />);
        } else {
            console.warn(`Component "${componentName}" not found in ReactComponents`);
        }
    } catch (error) {
        console.error(`Error mounting component "${componentName}":`, error);
    }
});

/**
 * Optional: Add global React utilities
 * window.React = React;
 * window.ReactComponents = ReactComponents;
 */
```

### Step 6: Verify Configuration

Check `config/packages/react.yaml`:
```yaml
react:
    build_dir: 'build'              # Where compiled assets go
    assets_dir: 'assets'            # Where source assets are
    vite_server: 'http://localhost:3000'  # Dev server URL
```

---

## Create Your First Component

### Simple Example: Welcome Component

**Step 1:** Create `assets/React/Components/Welcome.jsx`
```jsx
import React from 'react';

const Welcome = ({ title, message, userName }) => {
    return (
        <div style={{
            padding: '20px',
            backgroundColor: '#f5f5f5',
            borderRadius: '8px'
        }}>
            <h1>{title}</h1>
            <p>{message}</p>
            {userName && <p>Welcome, <strong>{userName}</strong>!</p>}
        </div>
    );
};

export default Welcome;
```

**Step 2:** Export in `assets/React/index.js`
```javascript
export { default as Welcome } from './Components/Welcome';
```

**Step 3:** Use in Twig template
```twig
{# templates/home.html.twig #}
{% extends 'base.html.twig' %}

{% block content %}
    {{ react_component('Welcome', {
        title: 'Welcome to ReactBundle',
        message: 'This is your first React component!',
        userName: app.user.username
    }) }}
{% endblock %}

{% block javascripts %}
    {{ vite_entry_script_tags('app') }}
{% endblock %}
```

**Step 4:** Build assets
```bash
php bin/console react:build --dev
```

Open your browser → Component appears! 🎉

---

## Component Best Practices

### 1. Props Naming Convention

```jsx
// ✅ Good
const ProductCard = ({ productName, productPrice, onAddToCart }) => {
    // ...
};

// ❌ Avoid
const ProductCard = ({ name, price, fn }) => {
    // ...
};
```

### 2. Handle Missing Data

```jsx
// ✅ Defensive programming
const UserProfile = ({ userName = 'Guest', userEmail, isActive = false }) => {
    return <div>{userName} ({userEmail || 'no email'})</div>;
};
```

### 3. Component Size

Keep components under 400 lines. Break into smaller components:

```
Components/
├── UserProfile.jsx          (Main component)
├── UserHeader.jsx           (Sub-component)
├── UserStats.jsx            (Sub-component)
└── UserActions.jsx          (Sub-component)
```

### 4. Hook Organization

```javascript
// assets/React/hooks/
├── useUserData.js
├── useFormValidation.js
├── useLocalStorage.js
└── index.js                 # Central export
```

---

## Development Workflow

### Build Commands

```bash
# Development mode with HMR (Hot Module Replacement)
php bin/console react:build --dev

# Production build (optimized, minified)
php bin/console react:build --prod

# Watch mode (rebuild on file changes)
php bin/console react:build --watch
```

### File Watching

The bundle watches for changes in:
- `assets/React/Components/**`
- `assets/js/app.jsx`
- `assets/React/index.js`

When files change:
1. Vite rebuilds in development
2. Browser hot-reloads (with `--dev`)
3. Page refreshes in production mode

### Testing Components

```jsx
// assets/React/Components/__tests__/Welcome.test.jsx
import { render, screen } from '@testing-library/react';
import Welcome from '../Welcome';

describe('Welcome Component', () => {
    test('renders welcome message', () => {
        render(<Welcome title="Hello" message="Test" />);
        expect(screen.getByText('Hello')).toBeInTheDocument();
    });
});
```

Run tests:
```bash
npm test
```

---

## Debugging

### Enable Debug Mode

In `config/packages/react.yaml`:
```yaml
react:
    debug: true  # Log component rendering
```

In Symfony:
```bash
# Clear cache to apply changes
php bin/console cache:clear

# Watch logs
tail -f var/log/dev.log
```

### Browser Console

Components log their rendering:
```javascript
// In app.jsx
window.DEBUG_COMPONENTS = true;

// Then see logs in browser console
```

### Common Issues

| Issue | Solution |
|-------|----------|
| Component not rendering | Check `vite_entry_script_tags('app')` in template |
| "Component not found" | Verify export in `assets/React/index.js` |
| HMR not working | Ensure `--dev` mode, check port 3000 |
| Props not displaying | Verify JSON serialization |

---

## Performance Optimization

### 1. Code Splitting

```javascript
// assets/React/index.js
import { lazy, Suspense } from 'react';

// Lazy load large components
const HeavyComponent = lazy(() => 
    import('./Components/HeavyComponent')
);

export { HeavyComponent };
```

### 2. Memoization

```jsx
import { memo } from 'react';

// Only re-render if props change
const ProductCard = memo(({ product, onSelect }) => {
    return <div onClick={() => onSelect(product)}>{product.name}</div>;
});
```

### 3. Bundle Analysis

```bash
npm run build --analyze  # See bundle size breakdown
```

---

## Production Deployment

### Pre-Deployment Checklist

- [ ] All tests passing: `npm test`
- [ ] Components built: `php bin/console react:build --prod`
- [ ] Manifest generated: `public/build/manifest.json` exists
- [ ] No console errors in browser
- [ ] Performance metrics acceptable

### Build for Production

```bash
# Build optimized production bundles
php bin/console react:build --prod

# Verify manifest was created
ls -la public/build/manifest.json

# Clear Symfony cache
php bin/console cache:clear --env=prod
```

### Environment Variables

In `.env.prod`:
```env
VITE_SERVER_URL=https://your-domain.com
APP_ENV=prod
APP_DEBUG=false
```

### Performance Monitoring

After deployment:
```bash
# Check logs for errors
tail -f var/log/prod.log | grep -i "react\|error"

# Monitor Vite performance
php bin/console react:build --prod --verbose
```

---

## Next Steps

1. **Learn more:** [Full Documentation](../README.md)
2. **Configuration:** [Advanced Config](CONFIG.md)
3. **API Reference:** [API Docs](API.md)
4. **Examples:** [Examples](../EXAMPLES.md)
5. **Troubleshooting:** [Troubleshooting](TROUBLESHOOTING.md)
6. **Performance:** [Performance Guide](PERFORMANCE.md)

---

## Support

- 📖 [Full README](../README.md)
- 🚀 [Quick Start](QUICKSTART.md)
- ⚙️ [Configuration](CONFIG.md)
- 🐛 [Troubleshooting](TROUBLESHOOTING.md)
- 💬 [GitHub Discussions](https://github.com/julien-lin/reactBundleSymfony/discussions)
- 🐛 [Report Issues](https://github.com/julien-lin/reactBundleSymfony/issues)
- 💝 [Sponsor](https://github.com/sponsors/julien-lin)

---

## License

MIT - See [LICENSE](../LICENSE) for details
