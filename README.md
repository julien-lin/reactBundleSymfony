# ReactBundle v2.0 - Production-Ready React Integration for Symfony

> **Enterprise-grade Symfony bundle for seamless React + Vite integration**

A lightweight, secure, and high-performance bundle that brings modern React development to Symfony, replacing Stimulus with production-ready components.

[![GitHub](https://img.shields.io/github/license/julien-lin/reactBundleSymfony)](https://github.com/julien-lin/reactBundleSymfony)
[![PHP Version](https://img.shields.io/badge/php-%3E%3D8.2-blue)](https://www.php.net/)
[![Symfony](https://img.shields.io/badge/symfony-6.0%20%7C%207.0-green)](https://symfony.com/)
[![Tests](https://img.shields.io/badge/tests-152%2F152-brightgreen)](COVERAGE_REPORT.md)
[![Coverage](https://img.shields.io/badge/coverage-88%25-brightgreen)](#code-quality)
[![Production Ready](https://img.shields.io/badge/status-PRODUCTION%20READY-brightgreen)](#production-readiness)
[![GitHub Sponsors](https://img.shields.io/github/sponsors/julien-lin?logo=github&color=ea4aaa)](https://github.com/sponsors/julien-lin)

**Languages:** [🇬🇧 English](README.md) | [🇫🇷 Français](README.fr.md)

---

## 🚀 Why ReactBundle v2.0?

✅ **Production-Ready** - 152 tests, 88% coverage, 100% security hardening  
✅ **Performance Monitored** - Built-in metrics, logging, and observability  
✅ **Enterprise Security** - XSS protection, command injection prevention, SSRF validation  
✅ **Zero Configuration** - Works out of the box with Symfony Flex  
✅ **Modern Tooling** - Vite-powered HMR for blazing-fast development  
✅ **Highly Testable** - Comprehensive test suite with edge case coverage

## 💝 Support the project

If this bundle saves you time, consider [becoming a sponsor](https://github.com/sponsors/julien-lin) to support ongoing development and maintenance of this open-source project.

---

## ⚡ Quick Start (5 minutes)

### 1. Install via Composer

```bash
composer require julien-lin/react-bundle-symfony
```

Composer automatically installs npm dependencies via Symfony Flex.

### 2. Create React folder structure

```bash
mkdir -p assets/React/Components
touch assets/React/index.js
```

### 3. Configure `assets/React/index.js`

```javascript
// Export all your React components here
// export { default as MyComponent } from './Components/MyComponent';
```

### 4. Configure `assets/js/app.jsx`

```jsx
import React from 'react';
import { createRoot } from 'react-dom/client';
import * as ReactComponents from '../React';

// Auto-mount React components by data attribute
document.querySelectorAll('[data-react-component]').forEach(element => {
    const componentName = element.dataset.reactComponent;
    const props = JSON.parse(element.dataset.props || '{}');
    const Component = ReactComponents[componentName];
    
    if (Component) {
        createRoot(element).render(<Component {...props} />);
    }
});
```

### 5. Use in Twig templates

```twig
{% extends 'base.html.twig' %}

{% block content %}
    {{ react_component('YourComponent', {
        title: 'Hello React',
        message: 'Welcome to production-ready React in Symfony'
    }) }}
{% endblock %}

{% block javascripts %}
    {{ vite_entry_script_tags('app') }}
{% endblock %}
```

### 6. Build and run

```bash
# Development (with HMR)
php bin/console react:build --dev

# Production
php bin/console react:build --prod
```

✅ Done! Your React component is live.

---

## 📋 Table of Contents

1. [Installation](#installation)
2. [Core Features](#core-features)
3. [TypeScript Support](#typescript-support)
4. [Advanced Usage](#advanced-usage)
5. [Production Deployment](#production-deployment)
6. [Configuration](#configuration)
7. [API Reference](#api-reference)
8. [Performance & Monitoring](#performance--monitoring)
9. [Security](#security)
10. [Troubleshooting](#troubleshooting)
11. [Contributing](#contributing)

---

## 📦 Installation

### Via Composer

```bash
composer require julien-lin/react-bundle-symfony
```

The Composer installation script will automatically install npm dependencies.

### Configuration

1. The bundle registers automatically via Symfony Flex.

2. Configure the bundle in `config/packages/react.yaml`:
```yaml
react:
    build_dir: 'build'
    assets_dir: 'assets'
```

3. If npm dependencies were not automatically installed:
```bash
cd vendor/julien-lin/react-bundle-symfony
npm install
```

4. Create the file structure in your Symfony project (if it doesn't already exist):

```bash
# Create the folder for your React components
mkdir -p assets/React/Components

# Create the index.js file to export your components
touch assets/React/index.js
```

5. Configure `assets/React/index.js` (entry point for your components):

```javascript
/**
 * Entry point for all React components in the project
 * Export all your components created in React/Components/ here
 */

// Example:
// export { default as MyComponent } from './Components/MyComponent';

// Add your exports here as you go
```

6. Configure `assets/js/app.jsx` (must import from `../React`):

```jsx
import React from 'react';
import { createRoot } from 'react-dom/client';

// Import all your components from the index
import * as ReactComponents from '../React';

// ... rest of the code (usually already configured)
```

## Usage

### Prerequisites: File Structure

Before using the bundle, make sure you have the following structure in your Symfony project:

```
assets/
├── React/
│   ├── Components/          # Create your components here
│   └── index.js             # Export your components here
└── js/
    └── app.jsx              # Entry point (already configured)
```

### In your Twig templates

```twig
{% extends '@React/react_base.html.twig' %}

{% block body %}
    {# Use react_component with the exact name of your component #}
    {{ react_component('MyComponent', {
        title: 'My title',
        message: 'My message',
        count: 42,
        items: ['item1', 'item2']
    }) }}
{% endblock %}

{% block javascripts %}
    {{ vite_entry_script_tags('app') }}
{% endblock %}
```

**Important**: The component name in `react_component()` must match exactly the name used in the export of `assets/React/index.js`.

## TypeScript Support

ReactBundleSymfony supports TypeScript out of the box. You can write your React components in TypeScript (`.tsx` files) for full type safety.

### Quick Setup

1. **Install TypeScript:**
```bash
npm install --save-dev typescript @types/react @types/react-dom
```

2. **Create `tsconfig.json`:**
```json
{
  "compilerOptions": {
    "target": "ES2020",
    "lib": ["ES2020", "DOM", "DOM.Iterable"],
    "module": "ESNext",
    "jsx": "react-jsx",
    "strict": true,
    "moduleResolution": "bundler",
    "baseUrl": ".",
    "paths": {
      "@/*": ["./assets/React/*"]
    }
  },
  "include": ["assets"]
}
```

3. **Rename files to `.tsx`:**
- `assets/js/app.jsx` → `assets/js/app.tsx`
- `assets/React/Components/*.jsx` → `assets/React/Components/*.tsx`

4. **Use TypeScript in components:**
```tsx
// assets/React/Components/WeatherCard.tsx
import React from 'react';

interface WeatherCardProps {
  city: string;
  temperature: number;
  description: string;
}

const WeatherCard: React.FC<WeatherCardProps> = ({ city, temperature, description }) => {
  return (
    <div>
      <h2>{city}</h2>
      <p>{temperature}°C - {description}</p>
    </div>
  );
};

export default WeatherCard;
```

**See [TYPESCRIPT.md](TYPESCRIPT.md) for complete TypeScript documentation.**

### Docker Quick Start

Get started with Docker in 5 minutes:

```bash
# 1. Install bundle
composer require julien-lin/react-bundle-symfony

# 2. Create docker-compose.yml (see QUICK_START_DOCKER.md)

# 3. Start services
docker-compose up -d

# 4. Start Vite dev server
docker-compose exec node npm run dev
```

**See [QUICK_START_DOCKER.md](QUICK_START_DOCKER.md) for complete Docker guide.**

### Build assets

#### Development with HMR
```bash
php bin/console react:build --dev
```

#### Production
```bash
php bin/console react:build
```

## Bundle structure

```
ReactBundle/
├── src/
│   ├── ReactBundle.php              # Main class
│   ├── DependencyInjection/         # Configuration
│   ├── Service/                     # Services
│   ├── Twig/                        # Twig extensions
│   ├── Command/                     # Symfony commands
│   └── Composer/                    # Composer scripts
├── Resources/
│   ├── config/
│   │   └── services.yaml
│   └── views/                       # Twig templates
├── composer.json
├── package.json
└── vite.config.js
```

## Recommended structure in your Symfony project

Create your React components **in your Symfony project**, not in the bundle:

```
your-symfony-project/
├── assets/
│   ├── React/
│   │   ├── Components/              # Your React components here
│   │   │   ├── MyComponent.jsx
│   │   │   ├── Navbar.jsx
│   │   │   └── ...
│   │   └── index.js                 # Centralized export of all components
│   └── js/
│       └── app.jsx                  # Entry point (imports from React/)
├── public/
│   └── build/                       # Assets compiled by Vite
└── config/
    └── packages/
        └── react.yaml               # Bundle configuration
```

## Create a new React component

### Quick workflow

```
1. Create the file          → assets/React/Components/MyComponent.jsx
2. Export in index.js       → assets/React/index.js
3. Rebuild assets           → php bin/console react:build
4. Use in Twig              → {{ react_component('MyComponent', {...}) }}
```

### Step 1: Create the component file

Create your component in `assets/React/Components/YourComponent.jsx`:

```jsx
import React from 'react';

const YourComponent = ({ title, message, onAction }) => {
    return (
        <div style={{ padding: '20px', border: '1px solid #ccc' }}>
            <h2>{title}</h2>
            <p>{message}</p>
            {onAction && (
                <button onClick={onAction}>Action</button>
            )}
        </div>
    );
};

export default YourComponent;
```

### Step 2: Export the component in `index.js`

Add the export in `assets/React/index.js`:

```javascript
// ... other existing exports

// Your new component
export { default as YourComponent } from './Components/YourComponent';
```

**Important**: The name used in the export (`YourComponent`) must match exactly the name you will use in Twig.

### Step 3: Use the component in a Twig template

In your Twig template:

```twig
{% extends '@React/react_base.html.twig' %}

{% block body %}
    {# Use the exact export name #}
    {{ react_component('YourComponent', {
        title: 'My title',
        message: 'My personalized message'
    }) }}
{% endblock %}

{% block javascripts %}
    {{ vite_entry_script_tags('app') }}
{% endblock %}
```

### Step 4: Rebuild assets

After creating or modifying a component:

```bash
# In development (with HMR)
php bin/console react:build --dev

# In production
php bin/console react:build
```

## Complete example

### 1. Create `assets/React/Components/ProductCard.jsx`

```jsx
import React from 'react';

const ProductCard = ({ name, price, image, onAddToCart }) => {
    return (
        <div style={{
            border: '1px solid #ddd',
            borderRadius: '8px',
            padding: '20px',
            textAlign: 'center'
        }}>
            <img 
                src={image} 
                alt={name}
                style={{ width: '100%', borderRadius: '4px', marginBottom: '10px' }}
            />
            <h3>{name}</h3>
            <p style={{ fontSize: '1.5rem', fontWeight: 'bold', color: '#ff6b6b' }}>
                ${price}
            </p>
            <button 
                onClick={onAddToCart}
                style={{
                    padding: '10px 20px',
                    backgroundColor: '#ff6b6b',
                    color: 'white',
                    border: 'none',
                    borderRadius: '4px',
                    cursor: 'pointer'
                }}
            >
                Add to cart
            </button>
        </div>
    );
};

export default ProductCard;
```

### 2. Export in `assets/React/index.js`

```javascript
// ... other exports

export { default as ProductCard } from './Components/ProductCard';
```

### 3. Use in Twig

```twig
{% extends '@React/react_base.html.twig' %}

{% block body %}
    <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px;">
        {% for product in products %}
            {{ react_component('ProductCard', {
                name: product.name,
                price: product.price,
                image: product.image,
                onAddToCart: '() => alert("Added to cart!")'
            }) }}
        {% endfor %}
    </div>
{% endblock %}

{% block javascripts %}
    {{ vite_entry_script_tags('app') }}
{% endblock %}
```

### Important notes

- ✅ **Create your components in `assets/React/Components/`** (in your project, not in the bundle)
- ✅ **Export them in `assets/React/index.js`** with the exact name you will use in Twig
- ✅ **Name is case-sensitive**: `ProductCard` ≠ `productcard` ≠ `Productcard`
- ✅ **Props are passed as JSON**: use simple types (string, number, boolean, array, object)
- ✅ **JavaScript functions** can be passed as strings (e.g., `'() => alert("test")'`)
- ✅ **Rebuild after each modification**: `php bin/console react:build` (or `--dev` for HMR)

## Migration from Stimulus

ReactBundleSymfony is designed as a modern replacement for Stimulus in Symfony applications.

**Quick migration:**
1. Install ReactBundle: `composer require julien-lin/react-bundle-symfony`
2. Convert Stimulus controllers to React components
3. Replace `data-controller="..."` with `{{ react_component(...) }}`

**See [MIGRATION_STIMULUS.md](MIGRATION_STIMULUS.md) for complete migration guide with examples.**

## Advanced configuration

### Customize Vite server

In `config/packages/react.yaml`:
```yaml
react:
    build_dir: 'build'
    assets_dir: 'assets'
```

### Environment variables

You can define `VITE_SERVER_URL` in your `.env` to customize the Vite server URL in development:

```env
VITE_SERVER_URL=http://localhost:5173
```

Or in `config/packages/react.yaml`:

```yaml
react:
    vite_server: 'http://localhost:5173'
```

### Troubleshooting

#### Components are not displaying
- Check that `{{ vite_entry_script_tags('app') }}` is present in your template
- Check the browser console for JavaScript errors
- Make sure assets are compiled: `php bin/console react:build`
- Check that manifest.json exists in `public/build/.vite/`

#### "Component not found" error
- Check that the component is exported in `assets/React/index.js` of your Symfony project
- Check that the name in the export matches exactly the name used in Twig (case-sensitive)
- Check that the component file exists in `assets/React/Components/`
- Check that you have rebuilt the assets: `php bin/console react:build`
- Check the browser console to see the list of available components

#### HMR is not working
- Check that the Vite server is started: `php bin/console react:build --dev`
- Check that port 3000 (or the configured one) is not in use
- Check the configuration in `vite.config.js`
- Check that `VITE_SERVER_URL` is correctly configured

#### npm/Node.js errors
- Check that Node.js >= 18.0.0 is installed: `node --version`
- Check that npm is installed: `npm --version`
- If you use nvm, make sure the environment is correctly loaded

#### Path errors (Windows)
- The bundle now supports Windows with `DIRECTORY_SEPARATOR`
- If you encounter problems, check folder permissions
- Make sure paths in `vite.config.js` are correct

## Adding npm Packages

To add npm packages (like `react-icons`, `axios`, etc.) to your project:

1. Install the package in your **Symfony project root** (not in the bundle):
   ```bash
   npm install react-icons
   ```

2. Import and use it in your components:
   ```jsx
   import { FaGithub } from 'react-icons/fa';
   ```

3. Rebuild assets:
   ```bash
   php bin/console react:build
   ```

📖 **Full guide**: See [ADDING_NPM_PACKAGES.md](ADDING_NPM_PACKAGES.md) for detailed instructions and examples.

## Support

- Complete documentation: see `QUICKSTART.md`
- Installation guide: see `INSTALLATION.md`
- Adding npm packages: see `ADDING_NPM_PACKAGES.md`
- Report a bug: [GitHub Issues](https://github.com/julien-lin/reactBundleSymfony/issues)
- Become a sponsor: [GitHub Sponsors](https://github.com/sponsors/julien-lin)

## License

MIT
