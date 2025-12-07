# ReactBundle v2.0 - Performance & Monitoring Guide

Optimize and monitor ReactBundle performance in production.

**Reading time:** ~20 minutes  
**Difficulty:** Advanced  
**Last updated:** 2024

---

## Table of Contents

1. [Performance Metrics](#performance-metrics)
2. [Optimization Techniques](#optimization-techniques)
3. [Monitoring Setup](#monitoring-setup)
4. [Logging & Debugging](#logging--debugging)
5. [Benchmarking](#benchmarking)
6. [Production Tuning](#production-tuning)

---

## Performance Metrics

### Key Metrics to Track

ReactBundle v2.0 includes built-in performance tracking for all components:

| Metric | Target | Units | Tracked |
|--------|--------|-------|---------|
| Render Duration | < 100ms | milliseconds | ✅ Yes |
| Memory Per Component | < 10MB | megabytes | ✅ Yes |
| Bundle Size | < 500KB | kilobytes | ✅ Yes |
| Cache Hit Rate | > 95% | percent | ✅ Yes |
| Manifest Load Time | < 10ms | milliseconds | ✅ Yes |

### Accessing Performance Data

ReactBundle logs performance data for each component render:

```php
// In production logs (var/log/prod.log)
[info] React component rendered {
    "component": "ProductCard",
    "duration_ms": 45.23,
    "memory_kb": 2048,
    "props_keys": "id,name,price,image",
    "request_id": "abc123",
    "timestamp": "2024-01-15 10:30:45.123456",
    "memory_peak_mb": 125.5
}
```

### Collect Performance Data

```bash
# Extract performance metrics from logs
grep "duration_ms" var/log/prod.log | tail -100 > performance_report.txt

# Calculate average render time
grep -oP '(?<="duration_ms":\s)\d+\.?\d*' var/log/prod.log | \
    awk '{sum+=$1; count++} END {print "Average: " sum/count "ms"}'

# Find slowest components
grep "duration_ms" var/log/prod.log | \
    grep -oP '(?<="component":\s")[^"]+|(?<="duration_ms":\s)\d+\.?\d*' | \
    paste - - | sort -t: -k2 -rn | head -10
```

---

## Optimization Techniques

### 1. Component Code Splitting

Split large components to reduce bundle size:

```jsx
// ✗ Bad: Single large file
// assets/React/Components/Dashboard.jsx (1500+ lines)
const Dashboard = ({ data }) => {
    // Huge component
};

// ✓ Good: Split into smaller components
// assets/React/Components/Dashboard/index.jsx
import { lazy, Suspense } from 'react';

const Chart = lazy(() => import('./Chart'));
const Table = lazy(() => import('./Table'));
const Summary = lazy(() => import('./Summary'));

const Dashboard = ({ data }) => {
    return (
        <div>
            <Suspense fallback={<div>Loading...</div>}>
                <Summary data={data} />
                <Chart data={data} />
                <Table data={data} />
            </Suspense>
        </div>
    );
};

export default Dashboard;
```

### 2. Memoization & Caching

Prevent unnecessary re-renders:

```jsx
import { memo, useMemo, useCallback } from 'react';

// Memoize component
const ProductCard = memo(({ product, onSelect }) => {
    console.log('Rendering ProductCard:', product.id);
    return (
        <div onClick={() => onSelect(product)}>
            {product.name}
        </div>
    );
}, (prevProps, nextProps) => {
    // Custom comparison: return true if props are equal
    return prevProps.product.id === nextProps.product.id;
});

// Memoize expensive calculations
const UserStats = ({ userId, users }) => {
    const stats = useMemo(() => {
        // Expensive calculation
        return users.find(u => u.id === userId);
    }, [userId, users]);
    
    return <div>{stats.name}</div>;
};

// Memoize callbacks
const List = ({ items, onItemClick }) => {
    const handleClick = useCallback((item) => {
        onItemClick(item);
    }, [onItemClick]);
    
    return items.map(item => (
        <button key={item.id} onClick={() => handleClick(item)}>
            {item.name}
        </button>
    ));
};

export default ProductCard;
```

### 3. Virtual Scrolling for Large Lists

Use virtual scrolling for lists with many items:

```jsx
import { FixedSizeList as List } from 'react-window';

const LargeList = ({ items }) => {
    const Row = ({ index, style }) => (
        <div style={style}>
            {items[index].name}
        </div>
    );
    
    return (
        <List
            height={600}
            itemCount={items.length}
            itemSize={35}
            width="100%"
        >
            {Row}
        </List>
    );
};

export default LargeList;
```

### 4. Image Optimization

Optimize images in components:

```jsx
// ✗ Bad: Large unoptimized image
<img src="/images/large-photo.jpg" />

// ✓ Good: Optimized with modern formats
<picture>
    <source srcSet="/images/photo.webp" type="image/webp" />
    <source srcSet="/images/photo.jpg" type="image/jpeg" />
    <img 
        src="/images/photo.jpg"
        alt="Description"
        loading="lazy"
        width={300}
        height={200}
    />
</picture>

// ✓ Better: Use an image component
const Image = ({ src, alt, width, height }) => {
    return (
        <img 
            src={src}
            alt={alt}
            width={width}
            height={height}
            loading="lazy"
            decoding="async"
        />
    );
};

export default Image;
```

### 5. Manifest Caching

Enable manifest caching in production:

```yaml
# config/packages/prod/react.yaml
react:
    manifest_cache_size: 20  # Cache up to 20 manifests in memory
    debug: false
```

Benefits:
- Manifest files cached in memory (LRU)
- Reduces disk I/O
- Faster asset path lookups
- Production-only (disabled in dev)

### 6. Asset Compression

Enable gzip compression for assets:

```bash
# Build assets
npm run build

# Gzip static files (reduces size by 60-80%)
gzip -9 -k public/build/assets/*.js
gzip -9 -k public/build/assets/*.css

# Configure web server to serve .gz files
```

**Nginx configuration:**
```nginx
gzip on;
gzip_types text/plain text/css application/javascript application/json;
gzip_min_length 1024;
gzip_vary on;
gzip_level 6;
```

### 7. Lazy Loading Components

Load components only when needed:

```twig
{# Load heavy component only if needed #}
{% if show_advanced_features %}
    {{ react_component('AdvancedDashboard', props) }}
{% else %}
    {{ react_component('SimpleDashboard', props) }}
{% endif %}
```

### 8. Props Optimization

Minimize props passed to components:

```php
// ✗ Bad: Pass entire object
{{ react_component('UserCard', { user: user }) }}

// ✓ Good: Pass only needed fields
{{ react_component('UserCard', {
    id: user.id,
    name: user.name,
    email: user.email
}) }}

// ✓ Better: Extract at controller level
$props = [
    'id' => $user->getId(),
    'name' => $user->getFullName(),
    'email' => $user->getEmail(),
];
{{ react_component('UserCard', props) }}
```

---

## Monitoring Setup

### 1. Enable Debug Logging

Configure logging in production:

```yaml
# config/packages/prod/react.yaml
react:
    debug: true  # Enable detailed logging
```

### 2. Structured Logging

ReactBundle logs with structured context:

```php
// In src/Service/ReactRenderer.php
$this->logger->info('React component rendered', [
    'component' => $componentName,
    'component_id' => $id,
    'duration_ms' => round($duration, 2),
    'memory_kb' => round($memoryUsed, 2),
    'props_keys' => implode(',', $propsKeys),
    'request_id' => $requestId,
    'timestamp' => date('Y-m-d H:i:s.u'),
    'memory_peak_mb' => round(memory_get_peak_usage(true) / 1024 / 1024, 2),
]);
```

### 3. Log Aggregation

Aggregate logs to identify bottlenecks:

```bash
# Using grep and awk
grep "React component rendered" var/log/prod.log | \
    jq -r '.component + " " + (.duration_ms | tostring)' | \
    awk '{count[$1]++; sum[$1]+=$2} END {
        for (c in count) {
            print c ": avg=" sum[c]/count[c] "ms, count=" count[c]
        }
    }' | sort -t: -k2 -rn
```

### 4. Sentry Integration

Send errors to Sentry for monitoring:

```yaml
# config/packages/prod/monolog.yaml
monolog:
    handlers:
        sentry:
            type: sentry
            dsn: '%env(SENTRY_DSN)%'
            level: error
```

### 5. Prometheus Metrics

Export metrics for Prometheus:

```php
<?php
// src/Service/ReactMetricsCollector.php

namespace App\Service;

use Prometheus\CollectorRegistry;
use Prometheus\Counter;
use Prometheus\Gauge;

class ReactMetricsCollector
{
    private Counter $renderCounter;
    private Gauge $renderDuration;
    private Gauge $memoryUsage;
    
    public function __construct(CollectorRegistry $registry)
    {
        $this->renderCounter = $registry->getOrRegisterCounter(
            'react_renders_total',
            'Total React component renders',
            ['component']
        );
        
        $this->renderDuration = $registry->getOrRegisterGauge(
            'react_render_duration_seconds',
            'React render duration',
            ['component']
        );
        
        $this->memoryUsage = $registry->getOrRegisterGauge(
            'react_memory_usage_bytes',
            'Memory used by render',
            ['component']
        );
    }
    
    public function recordRender(
        string $component,
        float $durationMs,
        int $memoryBytes
    ): void {
        $this->renderCounter->inc(['component' => $component]);
        $this->renderDuration->set($durationMs / 1000, ['component' => $component]);
        $this->memoryUsage->set($memoryBytes, ['component' => $component]);
    }
}
```

---

## Logging & Debugging

### 1. Enable Debug Mode

```yaml
# config/packages/dev/react.yaml
react:
    debug: true  # Verbose logging
    manifest_cache_size: 0  # No cache in dev
```

### 2. Watch Logs in Real-Time

```bash
# Follow logs as they're written
tail -f var/log/dev.log | grep -i "react"

# Filter by component
tail -f var/log/dev.log | grep "component\": \"Dashboard"

# Show only errors
tail -f var/log/dev.log | grep "\[error\]"
```

### 3. Analyze Performance

```bash
# Get top 10 slowest renders
grep "duration_ms" var/log/prod.log | \
    grep -oP '(?<="component":\s")[^"]+|(?<="duration_ms":\s)\d+\.?\d*' | \
    paste - - | sort -t: -k2 -rn | head -10

# Calculate statistics
grep "duration_ms" var/log/prod.log | \
    grep -oP '(?<="duration_ms":\s)\d+\.?\d*' | \
    awk '{
        sum+=$1; count++; 
        min=min($1); max=max($1)
    } END {
        print "Count: " count
        print "Min: " (min>0 ? min : 0) "ms"
        print "Max: " (max>0 ? max : 0) "ms"
        print "Avg: " sum/count "ms"
    }'
```

### 4. Browser DevTools

Monitor component rendering in browser:

```javascript
// Enable React DevTools in development
// In assets/js/app.jsx

if (process.env.NODE_ENV === 'development') {
    // Enable React DevTools API
    window.__REACT_DEVTOOLS_GLOBAL_HOOK__ = {
        // DevTools will inject itself
    };
}
```

---

## Benchmarking

### 1. Manual Benchmarking

Create benchmark tests:

```php
<?php
// tests/Service/RenderingBenchmarkTest.php

use JulienLin\ReactBundle\Service\ReactRenderer;
use PHPUnit\Framework\TestCase;

class RenderingBenchmarkTest extends TestCase
{
    private ReactRenderer $renderer;
    
    protected function setUp(): void
    {
        // Setup renderer
        $this->renderer = new ReactRenderer(...);
    }
    
    public function testRenderPerformance(): void
    {
        $iterations = 100;
        $start = microtime(true);
        
        for ($i = 0; $i < $iterations; $i++) {
            $this->renderer->render('TestComponent', [
                'id' => $i,
                'name' => "Component $i",
            ]);
        }
        
        $duration = microtime(true) - $start;
        $average = $duration / $iterations;
        
        echo "Total: {$duration}s, Average: {$average}s";
        $this->assertLessThan(0.1, $average, 'Render time too high');
    }
}
```

Run benchmark:
```bash
php bin/console phpunit tests/Service/RenderingBenchmarkTest.php --verbose
```

### 2. Bundle Size Analysis

Analyze bundle composition:

```bash
# Install analyzer
npm install --save-dev rollup-plugin-visualizer

# Update vite.config.js
import { visualizer } from 'rollup-plugin-visualizer';

export default defineConfig({
    plugins: [react(), visualizer()],
});

# Build and visualize
npm run build
# Opens visualization in browser
```

### 3. Performance Budget

Set performance budgets:

```javascript
// vite.config.js
export default defineConfig({
    build: {
        rollupOptions: {
            output: {
                manualChunks: {
                    'react-dom': ['react-dom'],
                    'vendor': [
                        'axios',
                        'lodash',
                    ],
                },
            },
        },
        // Warn if bundles exceed limits
        chunkSizeWarningLimit: 500,  // 500KB
    },
});
```

---

## Production Tuning

### 1. PHP-FPM Tuning

Optimize PHP-FPM for React rendering:

```ini
# /etc/php/8.2/fpm/pool.d/www.conf

; Process manager settings
pm = dynamic
pm.max_children = 50          ; Max worker processes
pm.start_servers = 10         ; Initial processes
pm.min_spare_servers = 5      ; Min idle processes
pm.max_spare_servers = 20     ; Max idle processes

; Memory settings
memory_limit = 512M           ; PHP memory limit
max_execution_time = 30       ; Max script duration

; Request timeout
request_terminate_timeout = 30
```

### 2. Nginx Optimization

```nginx
# /etc/nginx/nginx.conf

# Buffer settings
client_body_buffer_size 128k;
client_max_body_size 10m;
client_header_buffer_size 1k;
large_client_header_buffers 4 16k;

# Connection settings
keepalive_timeout 65;
keepalive_requests 100;

# Compression
gzip on;
gzip_types text/plain text/css text/javascript application/json;
gzip_min_length 1024;
gzip_level 6;

# Cache settings
open_file_cache max=1000 inactive=20s;
open_file_cache_valid 30s;
open_file_cache_min_uses 2;
```

### 3. Database Query Optimization

Optimize queries for component data:

```php
// ✗ Bad: N+1 query problem
$users = $em->getRepository(User::class)->findAll();
foreach ($users as $user) {
    echo $user->getProfile()->getName();  // Query per user!
}

// ✓ Good: Use eager loading
$query = $em->getRepository(User::class)
    ->createQueryBuilder('u')
    ->leftJoin('u.profile', 'p')
    ->addSelect('p')
    ->getQuery();
$users = $query->getResult();
```

### 4. Redis Caching

Cache component props:

```php
<?php
use Symfony\Contracts\Cache\CacheInterface;

class ProductService
{
    public function __construct(
        private CacheInterface $cache,
        private EntityManagerInterface $em
    ) {}
    
    public function getProductProps(int $id): array
    {
        // Check cache first
        return $this->cache->get(
            "product.$id",
            function() use ($id) {
                $product = $this->em->find(Product::class, $id);
                return [
                    'id' => $product->getId(),
                    'name' => $product->getName(),
                    'price' => $product->getPrice(),
                ];
            }
        );
    }
}
```

### 5. CDN Configuration

Serve static assets from CDN:

```yaml
# config/packages/prod/react.yaml
react:
    vite_server: 'https://cdn.example.com'
```

Update Vite configuration:

```javascript
// vite.config.js
export default defineConfig({
    build: {
        rollupOptions: {
            output: {
                dir: 'public/build',
                // Assets served from CDN
                entryFileNames: '[name]-[hash].js',
                chunkFileNames: 'chunks/[name]-[hash].js',
                assetFileNames: 'assets/[name]-[hash][extname]',
            },
        },
    },
});
```

---

## Performance Monitoring Dashboard

Create a monitoring dashboard:

```php
<?php
// src/Controller/MonitoringController.php

namespace App\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;

class MonitoringController
{
    public function metrics(): JsonResponse
    {
        $logs = file_get_contents('var/log/prod.log');
        $lines = explode("\n", $logs);
        
        $metrics = [
            'total_renders' => 0,
            'avg_duration' => 0,
            'avg_memory' => 0,
            'slowest_component' => null,
            'slowest_time' => 0,
        ];
        
        $durations = [];
        $memory = [];
        
        foreach ($lines as $line) {
            if (strpos($line, 'React component rendered') !== false) {
                if (preg_match('/"duration_ms":\s*(\d+\.?\d*)/', $line, $m)) {
                    $durations[] = $m[1];
                }
                if (preg_match('/"memory_kb":\s*(\d+\.?\d*)/', $line, $m)) {
                    $memory[] = $m[1] / 1024;  // Convert to MB
                }
                if (preg_match('/"component":\s*"([^"]+)"/', $line, $m)) {
                    $component = $m[1];
                    if (end($durations) > $metrics['slowest_time']) {
                        $metrics['slowest_component'] = $component;
                        $metrics['slowest_time'] = end($durations);
                    }
                }
            }
        }
        
        if ($durations) {
            $metrics['total_renders'] = count($durations);
            $metrics['avg_duration'] = array_sum($durations) / count($durations);
            $metrics['avg_memory'] = array_sum($memory) / count($memory);
        }
        
        return new JsonResponse($metrics);
    }
}
```

Access dashboard:
```bash
curl http://localhost:8000/monitoring/metrics
```

---

## Support & Resources

- 📖 [Full README](../README.md)
- ⚙️ [Configuration](CONFIG.md)
- 🚀 [Deployment](DEPLOYMENT.md)
- 🐛 [Troubleshooting](TROUBLESHOOTING.md)

---

## License

MIT License - See [LICENSE](../LICENSE) for details
