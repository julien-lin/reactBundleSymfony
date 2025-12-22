# TypeScript Support - ReactBundleSymfony

ReactBundleSymfony supports TypeScript out of the box. You can write your React components in TypeScript (`.tsx` files) and enjoy full type safety.

---

## 📦 Installation

### 1. Install TypeScript and Types

```bash
npm install --save-dev typescript @types/react @types/react-dom
```

### 2. Create `tsconfig.json`

Create a `tsconfig.json` in your project root:

```json
{
  "compilerOptions": {
    "target": "ES2020",
    "useDefineForClassFields": true,
    "lib": ["ES2020", "DOM", "DOM.Iterable"],
    "module": "ESNext",
    "skipLibCheck": true,

    /* Bundler mode */
    "moduleResolution": "bundler",
    "allowImportingTsExtensions": true,
    "resolveJsonModule": true,
    "isolatedModules": true,
    "noEmit": true,
    "jsx": "react-jsx",

    /* Linting */
    "strict": true,
    "noUnusedLocals": true,
    "noUnusedParameters": true,
    "noFallthroughCasesInSwitch": true,

    /* Path aliases */
    "baseUrl": ".",
    "paths": {
      "@/*": ["./assets/React/*"]
    }
  },
  "include": ["assets"],
  "references": [{ "path": "./tsconfig.node.json" }]
}
```

### 3. Create `tsconfig.node.json` (for Vite config)

```json
{
  "compilerOptions": {
    "composite": true,
    "skipLibCheck": true,
    "module": "ESNext",
    "moduleResolution": "bundler",
    "allowSyntheticDefaultImports": true
  },
  "include": ["vite.config.ts"]
}
```

### 4. Update `vite.config.js` to `vite.config.ts`

Rename your `vite.config.js` to `vite.config.ts` and update it:

```typescript
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
        app: path.resolve(__dirname, 'assets/js/app.tsx')
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
      '@': path.resolve(__dirname, 'assets/React')
    }
  }
});
```

### 5. Update `assets/js/app.jsx` to `app.tsx`

Rename `assets/js/app.jsx` to `assets/js/app.tsx`:

```tsx
import React from 'react';
import { createRoot } from 'react-dom/client';
import * as ReactComponents from '../React';

// Type definitions for component props
interface ComponentProps {
  [key: string]: unknown;
}

// ErrorBoundary component
class ErrorBoundary extends React.Component<
  { children: React.ReactNode },
  { hasError: boolean; error: Error | null; errorInfo: React.ErrorInfo | null }
> {
  constructor(props: { children: React.ReactNode }) {
    super(props);
    this.state = { hasError: false, error: null, errorInfo: null };
  }

  static getDerivedStateFromError(error: Error) {
    return { hasError: true, error };
  }

  componentDidCatch(error: Error, errorInfo: React.ErrorInfo) {
    console.error('React component error:', error, errorInfo);
    this.setState({ error, errorInfo });
  }

  render() {
    if (this.state.hasError) {
      return (
        <div style={{
          padding: '20px',
          border: '2px solid #f44336',
          borderRadius: '4px',
          backgroundColor: '#ffebee',
          color: '#c62828',
          margin: '10px 0'
        }}>
          <strong>React Component Error</strong>
          <p>{this.state.error?.toString() || 'An error occurred'}</p>
          {process.env.NODE_ENV === 'development' && this.state.errorInfo && (
            <details>
              <summary>Error Details</summary>
              <pre>{this.state.errorInfo.componentStack}</pre>
            </details>
          )}
        </div>
      );
    }

    return this.props.children;
  }
}

/**
 * Initialize React components from Twig-rendered elements
 */
function initReactComponents(): void {
  const containers = document.querySelectorAll<HTMLElement>('[data-react-component]');

  containers.forEach((container) => {
    if (container.dataset.reactInitialized === 'true') {
      return;
    }

    const componentName = container.getAttribute('data-react-component');
    const propsJson = container.getAttribute('data-react-props');

    if (!componentName) {
      console.warn('Component name missing');
      return;
    }

    let props: ComponentProps = {};
    if (propsJson) {
      try {
        props = JSON.parse(propsJson) as ComponentProps;
      } catch (e) {
        console.error(`Error parsing props for "${componentName}":`, e);
      }
    }

    const Component = ReactComponents[componentName as keyof typeof ReactComponents] as React.ComponentType<ComponentProps> | undefined;

    if (!Component) {
      console.error(`Component "${componentName}" not found. Available:`, Object.keys(ReactComponents));
      container.innerHTML = `<div style="padding: 10px; background: #ffebee; color: #c62828; border: 1px solid #f44336; border-radius: 4px;">
        Component "${componentName}" not found.
      </div>`;
      return;
    }

    try {
      const root = createRoot(container);
      root.render(
        React.createElement(ErrorBoundary, null,
          React.createElement(Component, props)
        )
      );
      container.dataset.reactInitialized = 'true';
    } catch (error) {
      console.error(`Error mounting component "${componentName}":`, error);
    }
  });
}

// Initialize when DOM is ready
if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', initReactComponents);
} else {
  initReactComponents();
}

// Support for Turbo
if (typeof (window as any).Turbo !== 'undefined') {
  document.addEventListener('turbo:load', initReactComponents);
  document.addEventListener('turbo:render', initReactComponents);
}
```

---

## 📝 Creating TypeScript Components

### Example: Typed Component

Create `assets/React/Components/WeatherCard.tsx`:

```tsx
import React from 'react';

interface WeatherCardProps {
  city: string;
  temperature: number;
  description: string;
  humidity?: number;
  onRefresh?: () => void;
}

const WeatherCard: React.FC<WeatherCardProps> = ({
  city,
  temperature,
  description,
  humidity,
  onRefresh
}) => {
  return (
    <div className="weather-card">
      <h2>{city}</h2>
      <div className="temperature">{temperature}°C</div>
      <p>{description}</p>
      {humidity !== undefined && <p>Humidity: {humidity}%</p>}
      {onRefresh && (
        <button onClick={onRefresh}>Refresh</button>
      )}
    </div>
  );
};

export default WeatherCard;
```

### Export in `assets/React/index.ts`

```typescript
export { default as WeatherCard } from './Components/WeatherCard';
```

**Note:** You can use `.ts` or `.tsx` for the index file. Vite handles both.

---

## 🔧 Type Safety with Props

### Defining Component Props Interface

```tsx
// assets/React/Components/UserProfile.tsx
import React from 'react';

interface User {
  id: number;
  name: string;
  email: string;
  avatar?: string;
}

interface UserProfileProps {
  user: User;
  showEmail?: boolean;
  onEdit?: (userId: number) => void;
}

const UserProfile: React.FC<UserProfileProps> = ({
  user,
  showEmail = true,
  onEdit
}) => {
  return (
    <div className="user-profile">
      {user.avatar && <img src={user.avatar} alt={user.name} />}
      <h3>{user.name}</h3>
      {showEmail && <p>{user.email}</p>}
      {onEdit && (
        <button onClick={() => onEdit(user.id)}>Edit</button>
      )}
    </div>
  );
};

export default UserProfile;
```

### Using in Twig

```twig
{{ react_component('UserProfile', {
    user: {
        id: user.id,
        name: user.name,
        email: user.email,
        avatar: user.avatarUrl
    },
    showEmail: true
}) }}
```

---

## 🎯 Best Practices

### 1. Use TypeScript for All Components

Prefer `.tsx` files over `.jsx` for better type safety:

```bash
# Good
assets/React/Components/MyComponent.tsx

# Also works, but less type-safe
assets/React/Components/MyComponent.jsx
```

### 2. Define Props Interfaces

Always define interfaces for component props:

```tsx
interface MyComponentProps {
  title: string;
  count: number;
  items: string[];
  optional?: boolean;
}
```

### 3. Use React.FC for Functional Components

```tsx
const MyComponent: React.FC<MyComponentProps> = ({ title, count }) => {
  // Component implementation
};
```

### 4. Type Hooks

```tsx
const [count, setCount] = React.useState<number>(0);
const [user, setUser] = React.useState<User | null>(null);
```

---

## 🚀 Build with TypeScript

TypeScript is automatically handled by Vite. No additional build step needed:

```bash
# Development (with HMR)
php bin/console react:build --dev

# Production
php bin/console react:build
```

Vite will:
- ✅ Type-check your TypeScript files
- ✅ Compile `.tsx` to JavaScript
- ✅ Preserve type information for better error messages
- ✅ Support HMR with TypeScript

---

## 📚 Examples

See `EXAMPLES.md` for complete TypeScript examples including:
- Components with hooks
- Context API with TypeScript
- Form handling with types
- API integration with typed responses

---

## ⚠️ Common Issues

### Issue: Type errors in Vite

**Solution:** Ensure `tsconfig.json` is properly configured and `@types/react` is installed.

### Issue: Props not typed correctly

**Solution:** Make sure your props interface matches the data passed from Twig. All props must be JSON-serializable.

### Issue: Module not found errors

**Solution:** Check your `tsconfig.json` paths configuration and ensure imports use the correct paths.

---

## 🔗 Related Documentation

- [Getting Started](GETTING_STARTED.md)
- [Examples](EXAMPLES.md)
- [Configuration](CONFIG.md)
- [API Reference](API.md)

---

**Last updated:** 2024-12-22

