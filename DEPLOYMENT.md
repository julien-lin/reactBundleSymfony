# ReactBundle v2.0 - Production Deployment Guide

Complete guide for deploying ReactBundle to production environments.

**Reading time:** ~20 minutes  
**Difficulty:** Advanced  
**Last updated:** 2024

---

## Table of Contents

1. [Pre-Deployment Checklist](#pre-deployment-checklist)
2. [Build Process](#build-process)
3. [Deployment Strategies](#deployment-strategies)
4. [Environment Setup](#environment-setup)
5. [Performance Optimization](#performance-optimization)
6. [Monitoring & Logging](#monitoring--logging)
7. [Rollback Procedures](#rollback-procedures)
8. [Troubleshooting](#troubleshooting)

---

## Pre-Deployment Checklist

### Code Quality

- [ ] All tests passing: `php bin/console phpunit`
- [ ] No TypeScript errors: `npm run type-check`
- [ ] No linting errors: `npm run lint`
- [ ] Code coverage acceptable (>85%): `npm run coverage`
- [ ] No console warnings in dev tools
- [ ] Git repository clean: `git status`

### Security

- [ ] No hardcoded secrets in code
- [ ] Environment variables configured
- [ ] API keys in `.env.prod` (not committed)
- [ ] CSP (Content Security Policy) configured
- [ ] CORS headers validated
- [ ] XSS protections enabled
- [ ] No SQL injection vulnerabilities
- [ ] HTTPS enforced

### Asset Build

- [ ] Production build completes: `php bin/console react:build --prod`
- [ ] Manifest created: `ls public/build/manifest.json`
- [ ] Assets optimized (minified/gzipped)
- [ ] Source maps optional in prod (smaller bundle)
- [ ] No 404 errors for assets
- [ ] Bundle size acceptable (<500KB gzipped)

### Configuration

- [ ] `.env.prod` configured correctly
- [ ] Database migrations applied
- [ ] Redis/cache configured (if used)
- [ ] Logging configured for production
- [ ] Error reporting configured
- [ ] Performance monitoring setup
- [ ] CDN configured (if applicable)

### Documentation

- [ ] README updated for v2.0
- [ ] Installation docs reviewed
- [ ] Configuration documented
- [ ] Deployment notes added to CHANGELOG
- [ ] Emergency procedures documented
- [ ] Team trained on new features

---

## Build Process

### Step 1: Prepare Production Build

```bash
# 1. Switch to production environment
export APP_ENV=prod
export APP_DEBUG=false

# 2. Clear development cache
php bin/console cache:clear --env=prod

# 3. Install production dependencies only
composer install --no-dev --optimize-autoloader

# 4. Install npm production dependencies
npm ci --production

# 5. Build optimized React assets
php bin/console react:build --prod
```

### Step 2: Verify Build Integrity

```bash
# Check manifest was created
[ -f public/build/manifest.json ] && echo "✓ Manifest created" || echo "✗ Manifest missing"

# Verify manifest format
php -r "json_decode(file_get_contents('public/build/manifest.json')); echo 'JSON valid';" 2>/dev/null || echo "✗ Invalid JSON"

# Check bundle size
du -sh public/build/ | awk '{print "Bundle size: " $1}'

# List generated assets
ls -lh public/build/assets/ | head -5

# Count assets
echo "JS files: $(find public/build -name '*.js' | wc -l)"
echo "CSS files: $(find public/build -name '*.css' | wc -l)"
```

### Step 3: Generate Bundles

```bash
# Create production bundle
php bin/console react:build --prod

# Gzip static assets (recommended)
gzip -9 -k public/build/assets/*.js
gzip -9 -k public/build/assets/*.css
gzip -9 -k public/build/manifest.json

# Verify gzipped files
ls -lh public/build/assets/ | grep '.gz'
```

### Step 4: Validate Production Build

```bash
# Run PHPUnit tests in production environment
php bin/console --env=prod phpunit

# Expected: All tests pass, no debug output
```

---

## Deployment Strategies

### Strategy 1: Traditional Server Deployment

**Best for:** Single server, standard hosting

```bash
#!/bin/bash
# deploy.sh

set -e  # Exit on error

echo "🚀 Starting deployment..."

# 1. Pull code from git
cd /var/www/app
git fetch origin
git checkout origin/main

# 2. Install dependencies
composer install --no-dev --optimize-autoloader
npm ci --production

# 3. Build assets
php bin/console react:build --prod --env=prod

# 4. Migrations
php bin/console doctrine:migrations:migrate --no-interaction

# 5. Cache warm-up
php bin/console cache:warmup --env=prod

# 6. Permissions
chown -R www-data:www-data /var/www/app/var
chown -R www-data:www-data /var/www/app/public

# 7. Restart services
systemctl restart php-fpm
systemctl restart nginx

echo "✓ Deployment complete!"
```

Run deployment:
```bash
chmod +x deploy.sh
./deploy.sh
```

### Strategy 2: Docker Deployment

**Best for:** Containerized, scalable deployments

**Dockerfile:**
```dockerfile
# Dockerfile
FROM php:8.2-fpm as builder
WORKDIR /app

# Install dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    zip \
    && rm -rf /var/lib/apt/lists/*

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Copy code
COPY . .

# Install PHP dependencies
RUN composer install --no-dev --optimize-autoloader

# Build stage
FROM node:18 as assets
WORKDIR /app
COPY --from=builder /app .
RUN npm ci && npm run build

# Final stage
FROM php:8.2-fpm
WORKDIR /app

# Install runtime dependencies
RUN apt-get update && apt-get install -y \
    postgresql-client \
    && rm -rf /var/lib/apt/lists/*

# Copy from builders
COPY --from=builder /app .
COPY --from=assets /app/public/build ./public/build

# Permissions
RUN chown -R www-data:www-data /app/var

EXPOSE 9000
CMD ["php-fpm"]
```

**Docker Compose:**
```yaml
version: '3.8'

services:
    app:
        build: .
        image: app:latest
        volumes:
            - ./:/app
        depends_on:
            - db
        environment:
            APP_ENV: prod
            APP_DEBUG: 'false'
            DATABASE_URL: postgresql://user:pass@db:5432/app
    
    nginx:
        image: nginx:alpine
        volumes:
            - ./public:/app/public:ro
            - ./nginx.conf:/etc/nginx/nginx.conf:ro
        ports:
            - "80:80"
        depends_on:
            - app
    
    db:
        image: postgres:15
        volumes:
            - db_data:/var/lib/postgresql/data
        environment:
            POSTGRES_DB: app
            POSTGRES_PASSWORD: secure_password

volumes:
    db_data:
```

Deploy:
```bash
docker-compose up -d --build
docker-compose exec app php bin/console doctrine:migrations:migrate --no-interaction
```

### Strategy 3: CI/CD Pipeline (GitHub Actions)

**Best for:** Automated, tested deployments

**.github/workflows/deploy.yml:**
```yaml
name: Deploy to Production

on:
  push:
    branches: [ main ]
  workflow_dispatch:

jobs:
  test:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v3
      
      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.2'
      
      - name: Setup Node
        uses: actions/setup-node@v3
        with:
          node-version: '18'
      
      - name: Install dependencies
        run: |
          composer install
          npm ci
      
      - name: Run tests
        run: |
          php bin/console phpunit
          npm test
      
      - name: Build assets
        run: npm run build
      
      - name: Upload coverage
        uses: codecov/codecov-action@v3

  deploy:
    needs: test
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v3
      
      - name: Deploy to server
        uses: appleboy/ssh-action@master
        with:
          host: ${{ secrets.SERVER_HOST }}
          username: ${{ secrets.SERVER_USER }}
          key: ${{ secrets.SSH_KEY }}
          script: |
            cd /var/www/app
            ./scripts/deploy.sh
```

---

## Environment Setup

### Production `.env.prod`

```env
# Application
APP_ENV=prod
APP_DEBUG=false
APP_SECRET=your-secret-key-here
APP_URL=https://your-domain.com

# Database
DATABASE_URL="postgresql://user:password@db.example.com:5432/app"

# React Bundle
VITE_SERVER_URL=https://your-domain.com
REACT_BUILD_DIR=build
REACT_ASSETS_DIR=assets
REACT_DEBUG=false

# Cache & Session
CACHE_DRIVER=redis
SESSION_DRIVER=redis
REDIS_URL=redis://cache.example.com:6379

# Email
MAILER_DSN=sendgrid+api://your-api-key@default

# Monitoring
SENTRY_DSN=https://key@sentry.io/12345
NEW_RELIC_LICENSE_KEY=your-license-key

# Security
CORS_ALLOW_ORIGIN=https://your-domain.com
TRUSTED_HOSTS=your-domain.com,*.your-domain.com
```

### Nginx Configuration

**nginx.conf:**
```nginx
server {
    listen 80;
    server_name your-domain.com www.your-domain.com;
    return 301 https://$server_name$request_uri;
}

server {
    listen 443 ssl http2;
    server_name your-domain.com www.your-domain.com;
    
    # SSL certificates
    ssl_certificate /etc/ssl/certs/your-domain.com.crt;
    ssl_certificate_key /etc/ssl/private/your-domain.com.key;
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_ciphers HIGH:!aNULL:!MD5;
    
    root /var/www/app/public;
    index index.php index.html;
    
    # Security headers
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-XSS-Protection "1; mode=block" always;
    add_header Referrer-Policy "strict-origin-when-cross-origin" always;
    add_header Content-Security-Policy "default-src 'self'; script-src 'self' 'unsafe-inline'; style-src 'self' 'unsafe-inline';" always;
    
    # Vite assets (long-term cache)
    location /build/assets/ {
        expires 1y;
        add_header Cache-Control "public, immutable";
    }
    
    # Static files
    location ~* \.(js|css|png|jpg|jpeg|gif|ico|svg|woff|woff2)$ {
        expires 30d;
        add_header Cache-Control "public";
    }
    
    # PHP
    location ~ \.php$ {
        try_files $uri =404;
        fastcgi_pass 127.0.0.1:9000;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }
    
    # Symfony routing
    location / {
        try_files $uri $uri/ /index.php$is_args$args;
    }
    
    # Deny access to dotfiles
    location ~ /\. {
        deny all;
    }
}
```

### Apache Configuration

**apache.conf:**
```apache
<VirtualHost *:443>
    ServerName your-domain.com
    DocumentRoot /var/www/app/public
    
    # SSL
    SSLEngine on
    SSLCertificateFile /etc/ssl/certs/your-domain.com.crt
    SSLCertificateKeyFile /etc/ssl/private/your-domain.com.key
    
    # Security headers
    Header always set X-Frame-Options "SAMEORIGIN"
    Header always set X-Content-Type-Options "nosniff"
    Header always set X-XSS-Protection "1; mode=block"
    
    # Caching for build assets
    <FilesMatch "\.(js|css)$">
        Header set Cache-Control "max-age=31536000, public"
    </FilesMatch>
    
    # Rewrite for Symfony routing
    <IfModule mod_rewrite.c>
        RewriteEngine On
        RewriteCond %{REQUEST_FILENAME} !-f
        RewriteCond %{REQUEST_FILENAME} !-d
        RewriteRule ^(.*)$ index.php [QSA,L]
    </IfModule>
    
    # PHP
    <FilesMatch \.php$>
        SetHandler "proxy:unix:/run/php/php8.2-fpm.sock|fcgi://127.0.0.1"
    </FilesMatch>
</VirtualHost>

<VirtualHost *:80>
    ServerName your-domain.com
    Redirect permanent / https://your-domain.com/
</VirtualHost>
```

---

## Performance Optimization

### 1. Asset Caching Strategy

```nginx
# Long-term cache for versioned assets (1 year)
location /build/assets/ {
    expires 1y;
    add_header Cache-Control "public, immutable";
}

# Cache manifest with validation
location /build/manifest.json {
    expires 1h;
    add_header Cache-Control "public, must-revalidate";
}
```

### 2. Compression

```nginx
# Enable gzip compression
gzip on;
gzip_types text/plain text/css text/javascript application/json application/javascript;
gzip_min_length 1024;
gzip_vary on;
gzip_level 6;
```

### 3. CDN Configuration

```yaml
# config/packages/prod/react.yaml
react:
    vite_server: 'https://cdn.example.com'  # CDN URL
    manifest_cache_size: 20                  # Larger cache
```

```javascript
// vite.config.js
export default defineConfig({
    build: {
        rollupOptions: {
            output: {
                // Use CDN path for assets
                dir: 'public/build',
                entryFileNames: '[name]-[hash].js',
                chunkFileNames: 'chunks/[name]-[hash].js',
                assetFileNames: 'assets/[name]-[hash][extname]',
            },
        },
    },
});
```

### 4. Bundle Optimization

```bash
# Analyze bundle size
npm run build -- --analyze

# Remove source maps in production
npm run build -- --sourcemap=false

# Use terser minification
npm run build -- --minify=terser
```

---

## Monitoring & Logging

### 1. Application Logging

```yaml
# config/packages/prod/monolog.yaml
monolog:
    handlers:
        main:
            type: rotating_file
            path: '%kernel.logs_dir%/%kernel.environment%.log'
            max_files: 10
            level: error
        
        console:
            type: console
            process_psr_3_messages: false
        
        sentry:
            type: fingers_crossed
            action_level: error
            handler: sentry
        
        sentry_handler:
            type: sentry
            dsn: '%env(SENTRY_DSN)%'
```

### 2. React Component Monitoring

```php
// src/Service/ReactRenderer.php logs
$this->logger->info('React component rendered', [
    'component' => $componentName,
    'duration_ms' => round($duration, 2),
    'memory_kb' => round($memoryUsed, 2),
    'request_id' => $requestId,
]);
```

### 3. Performance Monitoring

```bash
# Watch production logs
tail -f /var/log/app/prod.log | grep -i "react\|error"

# Monitor resource usage
top -p $(pgrep -f php)

# Check disk space
df -h /var/www/app

# Monitor bundle usage
du -sh /var/www/app/public/build/
```

### 4. Health Checks

```bash
#!/bin/bash
# health-check.sh

# Check app is running
curl -f https://your-domain.com/health || exit 1

# Check manifest exists
[ -f /var/www/app/public/build/manifest.json ] || exit 1

# Check disk space
available=$(df /var/www/app | tail -1 | awk '{print $4}')
[ $available -lt 102400 ] && exit 1  # < 100MB

echo "✓ Health check passed"
```

---

## Rollback Procedures

### Quick Rollback (Git-based)

```bash
#!/bin/bash
# rollback.sh

echo "🔄 Starting rollback..."

# 1. Get previous version
cd /var/www/app
CURRENT=$(git rev-parse HEAD)
git log --oneline -5

# 2. Rollback to previous commit
git revert $CURRENT --no-edit
git pull origin main

# 3. Rebuild
composer install --no-dev
npm ci --production
php bin/console react:build --prod

# 4. Clear cache
php bin/console cache:clear --env=prod

# 5. Restart services
systemctl restart php-fpm
systemctl restart nginx

echo "✓ Rollback complete. Current version:"
git log --oneline -1
```

### Database Rollback

```bash
# If migrations failed, rollback
php bin/console doctrine:migrations:migrate --no-interaction

# Or specify version
php bin/console doctrine:migrations:migrate --to=previous --no-interaction

# Check migration status
php bin/console doctrine:migrations:status
```

### Docker Rollback

```bash
# Rollback to previous image
docker-compose up -d --pull never app:previous

# Or use git to rollback code
git revert <commit-hash>
docker-compose up -d --build
```

---

## Troubleshooting

### Issue 1: Assets Return 404

**Symptom:** CSS/JS files not loading in production

**Solution:**
```bash
# Verify assets were built
ls -la public/build/

# Check manifest.json
cat public/build/manifest.json | jq '.' | head -20

# Rebuild if missing
php bin/console react:build --prod

# Check web server permissions
chown -R www-data:www-data public/build/
chmod -R 755 public/build/
```

### Issue 2: Slow Component Rendering

**Symptom:** Pages load slowly, high memory usage

**Solution:**
```bash
# Check logs for performance metrics
tail -f var/log/prod.log | grep 'duration_ms\|memory'

# Enable debug temporarily
sed -i 's/debug: false/debug: true/' config/packages/prod/react.yaml
php bin/console cache:clear --env=prod

# Profile slow components
php bin/console phpunit --filter=RenderingBenchmarkTest
```

### Issue 3: HMR Not Working (Shouldn't be active in prod)

**Symptom:** Socket connection errors in browser console

**Solution:**
```yaml
# Verify production configuration
# config/packages/prod/react.yaml
react:
    vite_server: 'https://your-domain.com'  # Not localhost!
    debug: false
```

### Issue 4: Manifest Cache Issues

**Symptom:** Old assets are served after deployment

**Solution:**
```bash
# Clear manifest cache
rm -rf var/cache/prod/manifest_cache.*

# Rebuild cache
php bin/console cache:clear --env=prod

# Verify new assets
curl https://your-domain.com/build/manifest.json | jq '.["app.js"]'
```

### Issue 5: Out of Memory During Build

**Symptom:** "Allowed memory size exhausted" during `react:build`

**Solution:**
```bash
# Increase PHP memory limit
php -d memory_limit=1G bin/console react:build --prod

# Or set in php.ini
memory_limit = 1G

# Monitor memory usage during build
php -r "ini_set('memory_limit','1G'); require 'bin/console'; echo 'Memory: ' . memory_get_usage(true);"
```

---

## Post-Deployment Verification

```bash
#!/bin/bash
# post-deploy-check.sh

echo "✓ Running post-deployment checks..."

# 1. HTTP status
STATUS=$(curl -s -o /dev/null -w "%{http_code}" https://your-domain.com)
[ "$STATUS" = "200" ] && echo "✓ Website accessible" || echo "✗ HTTP $STATUS"

# 2. Assets loading
curl -s https://your-domain.com/build/manifest.json | jq . > /dev/null && \
    echo "✓ Manifest valid" || echo "✗ Invalid manifest"

# 3. React components
curl -s https://your-domain.com | grep -q "data-react-component" && \
    echo "✓ React components found" || echo "✗ No React components"

# 4. Security headers
curl -s https://your-domain.com -I | grep -q "X-Frame-Options" && \
    echo "✓ Security headers present" || echo "✗ Missing security headers"

# 5. Performance
TIME=$(curl -s -w "%{time_total}" -o /dev/null https://your-domain.com)
echo "✓ Response time: ${TIME}s"

echo "✓ Verification complete!"
```

---

## Support & Resources

- 📖 [Full README](../README.md)
- 🚀 [Getting Started](GETTING_STARTED.md)
- ⚙️ [Configuration](CONFIG.md)
- 🐛 [Troubleshooting](TROUBLESHOOTING.md)
- 💬 [GitHub Discussions](https://github.com/julien-lin/reactBundleSymfony/discussions)
- 🐛 [Report Issues](https://github.com/julien-lin/reactBundleSymfony/issues)

---

## License

MIT License - See [LICENSE](../LICENSE) for details
