# ReactBundle v2.0 - API Reference

Complete API documentation for ReactBundle developers.

**Reading time:** ~25 minutes  
**Difficulty:** Intermediate  
**Last updated:** 2024

---

## Table of Contents

1. [Twig Functions](#twig-functions)
2. [PHP Services](#php-services)
3. [Commands](#commands)
4. [Configuration](#configuration)
5. [Events](#events)
6. [Exceptions](#exceptions)
7. [Utilities](#utilities)

---

## Twig Functions

### react_component()

Renders a React component from a Twig template.

**Signature:**
```twig
{{ react_component(componentName, props = {}, attributes = {}) }}
```

**Parameters:**

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `componentName` | string | Yes | Name of the React component to render (must be exported from `assets/React/index.js`) |
| `props` | array | No | Properties passed to the component (must be JSON-serializable) |
| `attributes` | array | No | HTML attributes for the container element |

**Returns:**
```html
<div data-react-component="ComponentName" data-props='{"key":"value"}'></div>
```

**Examples:**

```twig
{# Simple component without props #}
{{ react_component('Header') }}

{# Component with props #}
{{ react_component('ProductCard', {
    title: 'My Product',
    price: 99.99,
    inStock: true
}) }}

{# Component with custom HTML attributes #}
{{ react_component('Dashboard', 
    { userId: user.id },
    { class: 'dashboard-container', id: 'main-dashboard' }
) }}

{# Nested data structures #}
{{ react_component('UserProfile', {
    user: {
        name: user.username,
        email: user.email,
        role: user.role
    },
    settings: {
        theme: 'dark',
        notifications: true
    }
}) }}

{# CSRF token for forms #}
{{ react_component('ContactForm', {
    csrf_token: csrf_token('contact_form'),
    recipientEmail: admin.email
}) }}
```

**Important Notes:**

- Component name must match the export in `assets/React/index.js`
- Props must be serializable to JSON (no objects, DateTime, etc.)
- The function returns an HTML string, safe to render
- Component mounting happens automatically in `assets/js/app.jsx`
- Case-sensitive: `'Header'` ≠ `'header'`

---

### vite_entry_script_tags()

Generates script tags for Vite entry points.

**Signature:**
```twig
{{ vite_entry_script_tags(entryName) }}
```

**Parameters:**

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `entryName` | string | Yes | Name of the Vite entry point (e.g., 'app') |

**Returns:**
```html
<script type="module" src="/build/assets/app-abc123.js"></script>
<script type="module" src="/build/assets/chunk-def456.js"></script>
```

**Examples:**

```twig
{# In base template #}
<!DOCTYPE html>
<html>
<body>
    {% block content %}{% endblock %}
    
    {# Include main app entry point #}
    {{ vite_entry_script_tags('app') }}
    
    {# Include additional entry points if using multiple #}
    {{ vite_entry_script_tags('admin') }}
    {{ vite_entry_script_tags('checkout') }}
</body>
</html>
```

**Development Mode:**
In development (`--dev`), script tags include HMR (Hot Module Replacement) for live reloading.

---

### vite_entry_link_tags()

Generates link tags for CSS entry points.

**Signature:**
```twig
{{ vite_entry_link_tags(entryName) }}
```

**Parameters:**

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `entryName` | string | Yes | Name of the Vite entry point (e.g., 'app') |

**Returns:**
```html
<link rel="stylesheet" href="/build/assets/app-abc123.css">
```

**Examples:**

```twig
{# In <head> section #}
<head>
    <meta charset="UTF-8">
    <title>{% block title %}My App{% endblock %}</title>
    
    {# Include CSS entry point #}
    {{ vite_entry_link_tags('app') }}
</head>
```

---

## PHP Services

### ReactRenderer

Core service for rendering React components.

**Namespace:**
```php
use JulienLin\ReactBundle\Service\ReactRenderer;
```

**Public Methods:**

#### render()

Renders a React component with given props.

**Signature:**
```php
public function render(
    string $componentName,
    array $props = [],
    string $id = null
): string
```

**Parameters:**

| Parameter | Type | Default | Description |
|-----------|------|---------|-------------|
| `$componentName` | string | Required | React component name |
| `$props` | array | `[]` | Component properties |
| `$id` | string | null | Optional HTML element ID |

**Returns:**
```html
<div data-react-component="ComponentName" data-props='...'></div>
```

**Example:**

```php
<?php
// In controller
use JulienLin\ReactBundle\Service\ReactRenderer;

class HomeController extends AbstractController
{
    public function __construct(private ReactRenderer $renderer) {}
    
    public function index(): Response
    {
        // Render component
        $html = $this->renderer->render('WelcomeCard', [
            'title' => 'Welcome!',
            'message' => 'Hello world',
        ]);
        
        return $this->render('home.html.twig', [
            'component_html' => $html,
        ]);
    }
}
```

---

### ViteExtension

Manages Vite manifest and asset loading.

**Namespace:**
```php
use JulienLin\ReactBundle\Twig\ViteExtension;
```

**Public Methods:**

#### getEntry()

Gets entry point information from manifest.

**Signature:**
```php
public function getEntry(string $entryName): array
```

**Returns:**
```php
[
    'file' => 'assets/app-abc123.js',
    'src' => 'assets/js/app.jsx',
    'css' => ['assets/app-abc123.css'],
    'isDynamicEntry' => false,
]
```

---

### BuildArtifactValidator

Validates build artifacts and manifest integrity.

**Namespace:**
```php
use JulienLin\ReactBundle\Service\BuildArtifactValidator;
```

**Public Methods:**

#### validateBuildArtifacts()

Validates all build artifacts in a directory.

**Signature:**
```php
public function validateBuildArtifacts(string $buildPath): array
```

**Returns:**
```php
[
    'valid' => true,
    'manifest_exists' => true,
    'js_bundles' => 3,
    'css_bundles' => 1,
    'issues' => [],
]
```

**Example:**

```php
<?php
use JulienLin\ReactBundle\Service\BuildArtifactValidator;

class BuildValidationService
{
    public function __construct(private BuildArtifactValidator $validator) {}
    
    public function validate(): void
    {
        $result = $this->validator->validateBuildArtifacts('public/build');
        
        if (!$result['valid']) {
            throw new Exception('Build artifacts invalid');
        }
        
        echo "JS bundles: {$result['js_bundles']}";
        echo "CSS bundles: {$result['css_bundles']}";
    }
}
```

#### validateManifestFormat()

Validates manifest.json format and content.

**Signature:**
```php
public function validateManifestFormat(string $manifestPath): void
```

**Throws:**
- `RuntimeException` if manifest is invalid
- `JsonException` if JSON is malformed

---

## Commands

### react:build

Build React assets with Vite.

**Usage:**
```bash
php bin/console react:build [OPTIONS]
```

**Options:**

| Option | Description |
|--------|-------------|
| `--dev` | Build in development mode (with HMR) |
| `--prod` | Build in production mode (optimized, minified) |
| `--watch` | Watch for changes and rebuild automatically |
| `--force` | Force rebuild even if cache is valid |
| `--verbose` | Show detailed output |

**Examples:**

```bash
# Development build with HMR
php bin/console react:build --dev

# Production build
php bin/console react:build --prod

# Watch mode (auto-rebuild on changes)
php bin/console react:build --watch

# Force rebuild
php bin/console react:build --prod --force
```

**Output:**
```
Building assets with Vite...
✓ Build complete
Manifest created: public/build/manifest.json
Built 3 entries, 2 CSS files
```

---

## Configuration

### YAML Configuration

**Location:** `config/packages/react.yaml`

```yaml
react:
    # Build output directory (relative to public/)
    build_dir: 'build'
    
    # Asset source directory (relative to project root)
    assets_dir: 'assets'
    
    # Development server URL
    vite_server: 'http://localhost:3000'
    
    # Enable debug logging
    debug: false
    
    # Manifest cache size
    manifest_cache_size: 10
```

### Accessing Configuration in Services

```php
<?php
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class MyService
{
    public function __construct(private ParameterBagInterface $params) {}
    
    public function getBuildDir(): string
    {
        return $this->params->get('react.build_dir');
    }
    
    public function getViteServer(): string
    {
        return $this->params->get('react.vite_server');
    }
}
```

---

## Events

ReactBundle dispatches the following events (when implemented):

### ComponentRenderedEvent (Future)

Dispatched after a React component is rendered.

**Event Class:**
```php
namespace JulienLin\ReactBundle\Event;

class ComponentRenderedEvent extends Event
{
    public function __construct(
        public string $componentName,
        public array $props,
        public string $html,
    ) {}
}
```

**Listener Example:**
```php
<?php
use JulienLin\ReactBundle\Event\ComponentRenderedEvent;

class ComponentLogger
{
    public function onComponentRendered(ComponentRenderedEvent $event): void
    {
        echo "Rendered: {$event->componentName}";
    }
}
```

---

## Exceptions

### ReactBundleException

Base exception for ReactBundle errors.

```php
use JulienLin\ReactBundle\Exception\ReactBundleException;

try {
    // ReactBundle operation
} catch (ReactBundleException $e) {
    echo "Error: " . $e->getMessage();
}
```

### RuntimeException

Thrown when required files/manifests are missing.

```php
try {
    $manifest = $renderer->loadManifest();
} catch (\RuntimeException $e) {
    // Handle missing manifest
}
```

### JsonException

Thrown when JSON parsing fails.

```php
try {
    $data = json_decode($json, true, flags: JSON_THROW_ON_ERROR);
} catch (\JsonException $e) {
    // Handle invalid JSON
}
```

---

## Utilities

### BundlePathResolver

Resolves bundle asset paths.

**Namespace:**
```php
use JulienLin\ReactBundle\Util\BundlePathResolver;
```

**Usage:**
```php
<?php
class MyService
{
    public function getBuildManifestPath(): string
    {
        return BundlePathResolver::getManifestPath();
    }
    
    public function getBuildDir(): string
    {
        return BundlePathResolver::getBuildDir();
    }
}
```

---

### ComponentValidator

Validates component names and properties.

**Namespace:**
```php
use JulienLin\ReactBundle\Util\ComponentValidator;
```

**Usage:**
```php
<?php
class MyService
{
    public function validateComponent(string $name, array $props): void
    {
        if (!ComponentValidator::isValidName($name)) {
            throw new \InvalidArgumentException("Invalid component name: $name");
        }
        
        if (!ComponentValidator::arePropsSerializable($props)) {
            throw new \InvalidArgumentException("Props not JSON serializable");
        }
    }
}
```

---

## Complete Example

### Controller with React Component

```php
<?php
// src/Controller/ProductController.php

namespace App\Controller;

use JulienLin\ReactBundle\Service\ReactRenderer;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;

class ProductController extends AbstractController
{
    public function __construct(private ReactRenderer $renderer) {}
    
    public function show(int $productId): Response
    {
        // Load product from database
        $product = $this->getDoctrine()->getRepository(Product::class)->find($productId);
        
        if (!$product) {
            throw $this->createNotFoundException();
        }
        
        // Prepare component props
        $props = [
            'id' => $product->getId(),
            'name' => $product->getName(),
            'description' => $product->getDescription(),
            'price' => $product->getPrice(),
            'inStock' => $product->isInStock(),
            'reviews' => array_map(fn($r) => [
                'author' => $r->getAuthorName(),
                'rating' => $r->getRating(),
                'text' => $r->getText(),
            ], $product->getReviews()->toArray()),
            'csrf_token' => $this->container->get('security.csrf.token_manager')
                ->getToken('form')
                ->getValue(),
        ];
        
        // Render component
        $productHtml = $this->renderer->render('ProductDetail', $props);
        
        return $this->render('product/show.html.twig', [
            'product' => $product,
            'componentHtml' => $productHtml,
        ]);
    }
}
```

### Template Using Component

```twig
{# templates/product/show.html.twig #}
{% extends 'base.html.twig' %}

{% block title %}{{ product.name }} - My Store{% endblock %}

{% block content %}
    <div class="container">
        <h1>{{ product.name }}</h1>
        
        {# Render React component #}
        {{ react_component('ProductDetail', {
            id: product.id,
            name: product.name,
            description: product.description,
            price: product.price,
            inStock: product.inStock,
            reviews: product.reviews,
            csrf_token: csrf_token('form')
        }) }}
    </div>
{% endblock %}

{% block javascripts %}
    {{ parent() }}
    {{ vite_entry_script_tags('app') }}
{% endblock %}
```

### React Component

```jsx
// assets/React/Components/ProductDetail.jsx

import React, { useState } from 'react';

const ProductDetail = ({
    id,
    name,
    description,
    price,
    inStock,
    reviews,
    csrf_token,
}) => {
    const [quantity, setQuantity] = useState(1);
    const [added, setAdded] = useState(false);
    
    const handleAddToCart = async () => {
        const response = await fetch('/cart/add', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': csrf_token,
            },
            body: JSON.stringify({
                product_id: id,
                quantity,
            }),
        });
        
        if (response.ok) {
            setAdded(true);
            setTimeout(() => setAdded(false), 2000);
        }
    };
    
    return (
        <div className="product-detail">
            <p className="description">{description}</p>
            <p className="price">${price}</p>
            
            {inStock ? (
                <div>
                    <input
                        type="number"
                        min="1"
                        value={quantity}
                        onChange={(e) => setQuantity(parseInt(e.target.value))}
                    />
                    <button onClick={handleAddToCart}>
                        Add to Cart
                    </button>
                    {added && <p className="success">Added to cart!</p>}
                </div>
            ) : (
                <p className="out-of-stock">Out of stock</p>
            )}
            
            <div className="reviews">
                <h3>Reviews</h3>
                {reviews.map((review, i) => (
                    <div key={i} className="review">
                        <p><strong>{review.author}</strong> ({review.rating}/5)</p>
                        <p>{review.text}</p>
                    </div>
                ))}
            </div>
        </div>
    );
};

export default ProductDetail;
```

---

## Support & Resources

- 📖 [Full README](../README.md)
- 🚀 [Getting Started](GETTING_STARTED.md)
- ⚙️ [Configuration](CONFIG.md)
- 🐛 [Troubleshooting](TROUBLESHOOTING.md)

---

## License

MIT License - See [LICENSE](../LICENSE) for details
