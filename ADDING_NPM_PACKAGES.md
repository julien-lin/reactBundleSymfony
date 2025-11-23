# Adding npm Packages to Your Symfony Project

This guide explains how to add npm packages (like `react-icons`, `axios`, `react-router-dom`, etc.) to your Symfony project when using ReactBundle.

## 📍 Where to Install Packages

**Important**: Install npm packages in **your Symfony project**, not in the bundle itself.

Your project structure should look like this:

```
your-symfony-project/
├── assets/
│   ├── React/
│   │   └── Components/          # Your React components
│   └── js/
│       └── app.jsx
├── package.json                 # ← Install packages here
├── node_modules/                # ← Dependencies installed here
└── vite.config.js
```

## 🚀 Step-by-Step Guide

### Step 1: Navigate to Your Project Root

```bash
cd /path/to/your/symfony/project
```

### Step 2: Install the Package

```bash
npm install react-icons
```

Or for development dependencies:

```bash
npm install --save-dev @types/react-icons
```

### Step 3: Use the Package in Your Components

Import and use the package in your React components:

```jsx
// assets/React/Components/MyComponent.jsx
import React from 'react';
import { FaGithub, FaTwitter, FaLinkedin } from 'react-icons/fa';

const MyComponent = ({ title }) => {
    return (
        <div>
            <h2>{title}</h2>
            <div style={{ display: 'flex', gap: '10px' }}>
                <FaGithub size={24} />
                <FaTwitter size={24} />
                <FaLinkedin size={24} />
            </div>
        </div>
    );
};

export default MyComponent;
```

### Step 4: Rebuild Assets

After installing a new package or modifying components:

```bash
# Development with HMR
php bin/console react:build --dev

# Production
php bin/console react:build
```

## 📦 Common React Packages Examples

### React Icons

```bash
npm install react-icons
```

Usage:
```jsx
import { FaHome, FaUser, FaCog } from 'react-icons/fa';
import { MdEmail, MdPhone } from 'react-icons/md';
```

### Axios (HTTP Client)

```bash
npm install axios
```

Usage:
```jsx
import axios from 'axios';

const MyComponent = () => {
    const fetchData = async () => {
        const response = await axios.get('/api/data');
        console.log(response.data);
    };
    // ...
};
```

### React Router DOM

```bash
npm install react-router-dom
```

Usage:
```jsx
import { BrowserRouter, Routes, Route } from 'react-router-dom';
```

### Date-fns (Date Utilities)

```bash
npm install date-fns
```

Usage:
```jsx
import { format, formatDistance } from 'date-fns';
```

### React Hook Form

```bash
npm install react-hook-form
```

Usage:
```jsx
import { useForm } from 'react-hook-form';
```

## 🔧 Docker Environment

If you're working in a Docker container, you have two options:

### Option 1: Install in the Container

```bash
docker compose exec apache_reactfony npm install react-icons
```

### Option 2: Install Locally (Recommended)

Install packages on your host machine, and they'll be available in the container if volumes are mounted:

```bash
# On your host machine
cd /path/to/your/symfony/project
npm install react-icons
```

## ⚠️ Important Notes

1. **Always install in your project**, not in `vendor/julien-lin/react-bundle-symfony/`
2. **Rebuild after installing packages**: `php bin/console react:build`
3. **Check `package.json`**: Your installed packages should appear in your project's `package.json`
4. **TypeScript types**: If using TypeScript, install type definitions: `npm install --save-dev @types/package-name`

## 📝 Complete Example: Adding react-icons

### 1. Install the package

```bash
cd /path/to/your/symfony/project
npm install react-icons
```

### 2. Create/Update a component

```jsx
// assets/React/Components/SocialLinks.jsx
import React from 'react';
import { FaGithub, FaTwitter, FaLinkedin, FaEnvelope } from 'react-icons/fa';

const SocialLinks = ({ links }) => {
    return (
        <div style={{ display: 'flex', gap: '20px', alignItems: 'center' }}>
            {links.github && (
                <a href={links.github} target="_blank" rel="noopener noreferrer">
                    <FaGithub size={32} color="#333" />
                </a>
            )}
            {links.twitter && (
                <a href={links.twitter} target="_blank" rel="noopener noreferrer">
                    <FaTwitter size={32} color="#1DA1F2" />
                </a>
            )}
            {links.linkedin && (
                <a href={links.linkedin} target="_blank" rel="noopener noreferrer">
                    <FaLinkedin size={32} color="#0077B5" />
                </a>
            )}
            {links.email && (
                <a href={`mailto:${links.email}`}>
                    <FaEnvelope size={32} color="#D44638" />
                </a>
            )}
        </div>
    );
};

export default SocialLinks;
```

### 3. Export in index.js

```javascript
// assets/React/index.js
export { default as SocialLinks } from './Components/SocialLinks';
```

### 4. Use in Twig

```twig
{{ react_component('SocialLinks', {
    links: {
        github: 'https://github.com/username',
        twitter: 'https://twitter.com/username',
        linkedin: 'https://linkedin.com/in/username',
        email: 'contact@example.com'
    }
}) }}
```

### 5. Rebuild

```bash
php bin/console react:build
```

## 🐛 Troubleshooting

### Package not found after installation

- Make sure you installed in the **project root**, not in the bundle
- Check that `node_modules/` exists in your project root
- Rebuild assets: `php bin/console react:build`

### Import errors in components

- Verify the package is in `package.json` dependencies
- Check the import path is correct
- Rebuild assets after adding imports

### Docker volume issues

- Ensure `node_modules/` is not excluded in `docker-compose.yml`
- Consider using a `.dockerignore` that doesn't exclude `node_modules/`

## 📚 Additional Resources

- [npm Documentation](https://docs.npmjs.com/)
- [React Icons](https://react-icons.github.io/react-icons/)
- [Vite Plugin React](https://github.com/vitejs/vite-plugin-react)

