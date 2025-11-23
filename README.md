# ReactBundle - Symfony Bundle for React

Independent Symfony bundle that allows you to integrate React with Vite into your Twig templates, replacing Stimulus.

[![GitHub](https://img.shields.io/github/license/julien-lin/reactBundleSymfony)](https://github.com/julien-lin/reactBundleSymfony)
[![PHP Version](https://img.shields.io/badge/php-%3E%3D8.2-blue)](https://www.php.net/)
[![Symfony](https://img.shields.io/badge/symfony-6.0%20%7C%207.0-green)](https://symfony.com/)
[![GitHub Sponsors](https://img.shields.io/github/sponsors/julien-lin?logo=github&color=ea4aaa)](https://github.com/sponsors/julien-lin)

**Available languages :** [🇬🇧 English](README.md) | [🇫🇷 Français](README.fr.md)

## 💝 Support the project

If this bundle is useful to you, consider [becoming a sponsor](https://github.com/sponsors/julien-lin) to support the development and maintenance of this open source project.

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

1. Identify your Stimulus controllers
2. Create equivalent React components
3. Replace `data-controller="..."` with `{{ react_component(...) }}`
4. Test individually

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

## Support

- Complete documentation: see `QUICKSTART.md`
- Installation guide: see `INSTALLATION.md`
- Report a bug: [GitHub Issues](https://github.com/julien-lin/reactBundleSymfony/issues)
- Become a sponsor: [GitHub Sponsors](https://github.com/sponsors/julien-lin)

## License

MIT
