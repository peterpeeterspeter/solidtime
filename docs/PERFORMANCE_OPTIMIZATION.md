# Performance Optimization - Phase 2

This document outlines performance considerations, optimizations, and benchmarks for Timeclocker Phase 2.

**Last Updated**: November 5, 2025
**Target**: Production-ready performance

---

## Performance Targets

### Core Web Vitals

| Metric | Target | Current | Status |
|--------|--------|---------|--------|
| **LCP** (Largest Contentful Paint) | < 2.5s | TBD | 🟡 Pending |
| **FID** (First Input Delay) | < 100ms | TBD | 🟡 Pending |
| **CLS** (Cumulative Layout Shift) | < 0.1 | TBD | 🟡 Pending |

### Lighthouse Scores

| Category | Target | Current | Status |
|----------|--------|---------|--------|
| **Performance** | 90+ | TBD | 🟡 Pending |
| **Accessibility** | 95+ | TBD | 🟡 Pending |
| **Best Practices** | 95+ | TBD | 🟡 Pending |
| **SEO** | 90+ | TBD | 🟡 Pending |
| **PWA** | 90+ | TBD | 🟡 Pending |

### Bundle Size

| Bundle | Target | Current | Status |
|--------|--------|---------|--------|
| **Main JS** | < 500 KB | TBD | 🟡 Pending |
| **Vendor JS** | < 1 MB | TBD | 🟡 Pending |
| **CSS** | < 100 KB | TBD | 🟡 Pending |
| **Total (gzipped)** | < 1.5 MB | TBD | 🟡 Pending |

---

## Optimization Strategies

### 1. Code Splitting & Lazy Loading

#### Route-Based Code Splitting

```javascript
// vite.config.js - Already configured
export default defineConfig({
    build: {
        rollupOptions: {
            output: {
                manualChunks: {
                    // Vendor chunks
                    'vue-vendor': ['vue', '@inertiajs/vue3', 'pinia'],
                    'ui-vendor': ['@headlessui/vue', '@heroicons/vue'],

                    // Feature chunks
                    'invoice': [
                        'resources/js/Components/InvoiceTemplate.vue',
                        'resources/js/Components/InvoiceList.vue',
                        'resources/js/composables/useInvoices.ts',
                    ],
                    'notifications': [
                        'resources/js/utils/notificationService.ts',
                        'resources/js/composables/useNotifications.ts',
                    ],
                }
            }
        }
    }
});
```

#### Component Lazy Loading

```vue
<script setup lang="ts">
// Lazy load heavy components
const CommandPalette = defineAsyncComponent(() =>
  import('@/Components/CommandPalette.vue')
);

const OnboardingWizard = defineAsyncComponent(() =>
  import('@/Components/OnboardingWizard.vue')
);

const InvoiceList = defineAsyncComponent(() =>
  import('@/Components/InvoiceList.vue')
);
</script>

<template>
  <!-- Only loaded when needed -->
  <Suspense>
    <template #default>
      <CommandPalette v-if="showCommandPalette" />
    </template>
    <template #fallback>
      <LoadingSpinner />
    </template>
  </Suspense>
</template>
```

### 2. Service Worker Optimization

#### Cache Strategy

```javascript
// Service Worker - Already configured in vite.config.js
workbox: {
    // Efficient caching strategies
    runtimeCaching: [
        {
            // API calls - Network first for fresh data
            urlPattern: /\/api\/v1\/.*/i,
            handler: 'NetworkFirst',
            options: {
                cacheName: 'api-cache',
                networkTimeoutSeconds: 10,
                expiration: {
                    maxEntries: 50,
                    maxAgeSeconds: 5 * 60, // 5 minutes
                },
            },
        },
        {
            // Static assets - Cache first for speed
            urlPattern: /\.(js|css|woff2|png|jpg|svg)$/,
            handler: 'CacheFirst',
            options: {
                cacheName: 'static-cache',
                expiration: {
                    maxEntries: 100,
                    maxAgeSeconds: 30 * 24 * 60 * 60, // 30 days
                },
            },
        },
        {
            // Images - Stale while revalidate
            urlPattern: /\/images\/.*/,
            handler: 'StaleWhileRevalidate',
            options: {
                cacheName: 'image-cache',
                expiration: {
                    maxEntries: 50,
                    maxAgeSeconds: 7 * 24 * 60 * 60, // 7 days
                },
            },
        },
    ],
}
```

#### Cache Size Management

```typescript
// Monitor and limit cache size
export async function checkCacheSize() {
    if ('storage' in navigator && 'estimate' in navigator.storage) {
        const { usage, quota } = await navigator.storage.estimate();
        const percentUsed = (usage! / quota!) * 100;

        console.log(`Storage used: ${(usage! / 1024 / 1024).toFixed(2)} MB`);
        console.log(`Storage quota: ${(quota! / 1024 / 1024).toFixed(2)} MB`);
        console.log(`Percent used: ${percentUsed.toFixed(2)}%`);

        // Alert if cache is getting large
        if (percentUsed > 80) {
            console.warn('Cache size approaching quota, consider cleanup');
            await cleanOldCache();
        }
    }
}
```

### 3. Image Optimization

#### PWA Icons

```javascript
// Sharp configuration for optimal icons
const iconConfig = {
    quality: 90,
    compressionLevel: 9,
    optimizeFor: 'size', // Balance of quality and size
};

// Already implemented in scripts/generate-pwa-icons.js
await sharp(svgBuffer)
    .resize(size, size)
    .png(iconConfig)
    .toFile(outputPath);
```

#### Recommendations

- ✅ Use WebP format for photos (better compression)
- ✅ Use SVG for icons and logos (scalable, small)
- ✅ Lazy load images below fold
- ✅ Use srcset for responsive images
- ✅ Implement blur-up technique for progressive loading

### 4. Database Query Optimization

#### Eager Loading

```php
// Avoid N+1 queries - use eager loading
// Invoice controller
public function index()
{
    $invoices = Invoice::query()
        ->with(['client', 'organization']) // Eager load relationships
        ->where('organization_id', $organizationId)
        ->latest()
        ->paginate(50);

    return InvoiceResource::collection($invoices);
}
```

#### Indexing

```php
// Database migration - add indexes for performance
Schema::table('invoices', function (Blueprint $table) {
    $table->index('organization_id');
    $table->index('client_id');
    $table->index('status');
    $table->index('due_date');
    $table->index(['organization_id', 'status']); // Composite index
});
```

#### Query Caching

```php
// Cache expensive queries
use Illuminate\Support\Facades\Cache;

public function getInvoiceStats($organizationId)
{
    return Cache::remember(
        "invoice_stats_{$organizationId}",
        now()->addMinutes(5),
        function () use ($organizationId) {
            return DB::table('invoices')
                ->where('organization_id', $organizationId)
                ->selectRaw('
                    SUM(CASE WHEN status = "paid" THEN total ELSE 0 END) as total_revenue,
                    SUM(CASE WHEN status IN ("sent", "overdue") THEN total ELSE 0 END) as total_outstanding,
                    COUNT(CASE WHEN status = "overdue" THEN 1 END) as count_overdue
                ')
                ->first();
        }
    );
}
```

### 5. Frontend Performance

#### Vue Performance

```vue
<script setup lang="ts">
// Use computed for expensive calculations
const sortedInvoices = computed(() => {
  return invoices.value.sort((a, b) => {
    return new Date(b.created_at).getTime() - new Date(a.created_at).getTime();
  });
});

// Memoize functions to prevent re-renders
const formatCurrency = (amount: number, currency: string) => {
  return new Intl.NumberFormat('en-EU', {
    style: 'currency',
    currency: currency,
  }).format(amount);
};

// Use v-memo for list items
<div v-for="invoice in invoices" :key="invoice.id" v-memo="[invoice.status, invoice.total]">
  <!-- Only re-render if status or total changes -->
</div>

// Use v-once for static content
<div v-once>
  {{ staticContent }}
</div>
</script>
```

#### Debouncing & Throttling

```typescript
// Debounce search input
import { useDebounceFn } from '@vueuse/core';

const searchQuery = ref('');
const debouncedSearch = useDebounceFn((query: string) => {
    // Perform search
    fetchSearchResults(query);
}, 300); // 300ms delay

watch(searchQuery, (newQuery) => {
    debouncedSearch(newQuery);
});

// Throttle scroll events
import { useThrottleFn } from '@vueuse/core';

const handleScroll = useThrottleFn(() => {
    // Handle scroll
    checkIfNearBottom();
}, 100); // Max once per 100ms
```

#### Virtual Scrolling (Future)

```vue
<!-- For very long lists (1000+ items) - Phase 3 -->
<script setup lang="ts">
import { useVirtualList } from '@vueuse/core';

const { list, containerProps, wrapperProps } = useVirtualList(
  largeDataset,
  {
    itemHeight: 50,
    overscan: 10,
  }
);
</script>

<template>
  <div v-bind="containerProps" style="height: 500px">
    <div v-bind="wrapperProps">
      <div v-for="item in list" :key="item.index">
        {{ item.data }}
      </div>
    </div>
  </div>
</template>
```

### 6. Network Optimization

#### API Response Optimization

```php
// Paginate large datasets
public function index(Request $request)
{
    $perPage = $request->input('per_page', 50);
    $timeEntries = TimeEntry::query()
        ->where('organization_id', $organizationId)
        ->latest()
        ->paginate($perPage);

    return TimeEntryResource::collection($timeEntries);
}

// Compress responses
// In config/app.php or middleware
'middleware' => [
    // ...
    \Spatie\ResponseCache\Middlewares\CacheResponse::class,
],

// Use ETags for caching
public function show($id)
{
    $invoice = Invoice::findOrFail($id);

    return response()
        ->json(InvoiceResource::make($invoice))
        ->setEtag(md5($invoice->updated_at))
        ->setLastModified($invoice->updated_at);
}
```

#### HTTP/2 Server Push (Optional)

```php
// In AppServiceProvider
Link::add('preload', [
    'href' => mix('js/app.js'),
    'as' => 'script',
]);

Link::add('preload', [
    'href' => mix('css/app.css'),
    'as' => 'style',
]);
```

### 7. Background Jobs

#### Queue Configuration

```php
// config/queue.php
'connections' => [
    'redis' => [
        'driver' => 'redis',
        'connection' => 'default',
        'queue' => env('REDIS_QUEUE', 'default'),
        'retry_after' => 90,
        'block_for' => null,
    ],
],

// Invoice notifications queued
Notification::send($user, new InvoiceCreated($invoice));
// Runs in background, doesn't block response
```

#### Job Optimization

```php
// Batch jobs for efficiency
use Illuminate\Bus\Batch;
use Illuminate\Support\Facades\Bus;

Bus::batch([
    new SendInvoiceEmail($invoice1),
    new SendInvoiceEmail($invoice2),
    new SendInvoiceEmail($invoice3),
])->then(function (Batch $batch) {
    // All jobs completed
})->catch(function (Batch $batch, Throwable $e) {
    // First batch job failure
})->finally(function (Batch $batch) {
    // Batch has finished
})->dispatch();
```

---

## Monitoring & Metrics

### Performance Monitoring

```typescript
// resources/js/utils/performance.ts
export function measurePerformance(name: string) {
    if ('performance' in window) {
        performance.mark(`${name}-start`);

        return () => {
            performance.mark(`${name}-end`);
            performance.measure(name, `${name}-start`, `${name}-end`);

            const measure = performance.getEntriesByName(name)[0];
            console.log(`${name}: ${measure.duration.toFixed(2)}ms`);

            // Send to analytics
            if (window.gtag) {
                window.gtag('event', 'timing_complete', {
                    name: name,
                    value: Math.round(measure.duration),
                });
            }
        };
    }

    return () => {}; // No-op if performance API not available
}

// Usage
const endMeasure = measurePerformance('invoice-pdf-generation');
await generateInvoicePDF(invoice);
endMeasure();
```

### Real User Monitoring (RUM)

```typescript
// Track Core Web Vitals
import { getCLS, getFID, getLCP } from 'web-vitals';

function sendToAnalytics(metric: any) {
    const body = JSON.stringify(metric);
    // Use `navigator.sendBeacon()` if available
    if (navigator.sendBeacon) {
        navigator.sendBeacon('/api/analytics', body);
    } else {
        fetch('/api/analytics', { body, method: 'POST', keepalive: true });
    }
}

getCLS(sendToAnalytics);
getFID(sendToAnalytics);
getLCP(sendToAnalytics);
```

---

## Performance Budget

### Enforced Limits

```javascript
// vite.config.js - Warn on large bundles
export default defineConfig({
    build: {
        rollupOptions: {
            output: {
                manualChunks(id) {
                    if (id.includes('node_modules')) {
                        return 'vendor';
                    }
                }
            }
        },
        // Warn if chunk exceeds 500kb
        chunkSizeWarningLimit: 500,
    }
});
```

### Performance Budget Checklist

- [ ] Main bundle < 500 KB (gzipped)
- [ ] Vendor bundle < 1 MB (gzipped)
- [ ] CSS bundle < 100 KB (gzipped)
- [ ] PWA icon set < 500 KB (total)
- [ ] Service worker < 50 KB
- [ ] Initial load < 3 seconds (3G)
- [ ] Time to interactive < 5 seconds (3G)

---

## Testing Performance

### Lighthouse CI

```yaml
# .github/workflows/lighthouse.yml
name: Lighthouse CI
on: [push, pull_request]
jobs:
  lighthouse:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v3
      - name: Run Lighthouse
        uses: treosh/lighthouse-ci-action@v9
        with:
          urls: |
            http://localhost:8000
            http://localhost:8000/time
            http://localhost:8000/invoices
          uploadArtifacts: true
          temporaryPublicStorage: true
```

### Load Testing

```bash
# Install Apache Bench
sudo apt-get install apache2-utils

# Test API endpoint
ab -n 1000 -c 10 http://localhost:8000/api/v1/time-entries

# Test with authentication
ab -n 1000 -c 10 -H "Authorization: Bearer TOKEN" http://localhost:8000/api/v1/invoices

# Results to look for:
# - Requests per second: > 100
# - Time per request: < 100ms (mean)
# - Failed requests: 0
```

### Database Query Analysis

```bash
# Enable query logging
# In .env
DB_LOG_QUERIES=true

# Run your feature
# Check storage/logs/laravel.log for slow queries

# Use Laravel Debugbar for development
composer require barryvdh/laravel-debugbar --dev
```

---

## Quick Wins

### Already Implemented ✅

1. ✅ **PWA Caching** - Static assets cached by service worker
2. ✅ **Code Splitting** - Routes lazy loaded
3. ✅ **Image Optimization** - Icons generated with Sharp
4. ✅ **Debounced Search** - Command palette search debounced
5. ✅ **Computed Properties** - Expensive calculations memoized
6. ✅ **Queued Jobs** - Email notifications queued

### Quick Wins (To Implement)

1. ⏳ **Preload Critical Assets** - Add link preload tags
2. ⏳ **Compress Responses** - Enable Gzip/Brotli
3. ⏳ **Database Indexing** - Add missing indexes
4. ⏳ **API Response Caching** - Cache dashboard stats
5. ⏳ **Lazy Load Images** - Implement loading="lazy"
6. ⏳ **Remove Unused CSS** - PurgeCSS integration

---

## Performance Checklist

### Before Production

- [ ] Run Lighthouse audit on all major pages
- [ ] Check bundle size with webpack-bundle-analyzer
- [ ] Test on slow 3G network simulation
- [ ] Profile with Chrome DevTools Performance tab
- [ ] Check for memory leaks (long session test)
- [ ] Test service worker update mechanism
- [ ] Verify database queries optimized (no N+1)
- [ ] Enable production caching (Redis)
- [ ] Enable response compression
- [ ] Set up CDN for static assets

### Continuous Monitoring

- [ ] Set up Real User Monitoring (RUM)
- [ ] Monitor Core Web Vitals
- [ ] Track bundle size in CI/CD
- [ ] Monitor API response times
- [ ] Track database query performance
- [ ] Monitor cache hit rates
- [ ] Set up performance alerts

---

## Resources

### Tools

- [Lighthouse](https://developers.google.com/web/tools/lighthouse) - Auditing
- [WebPageTest](https://www.webpagetest.org/) - Real-world testing
- [Bundle Analyzer](https://www.npmjs.com/package/rollup-plugin-visualizer) - Bundle analysis
- [Chrome DevTools](https://developer.chrome.com/docs/devtools/) - Profiling

### Guides

- [Web Vitals](https://web.dev/vitals/) - Core metrics
- [Vue Performance](https://vuejs.org/guide/best-practices/performance.html) - Vue optimization
- [Laravel Performance](https://laravel.com/docs/performance) - Laravel optimization

---

## Benchmarks (To Be Measured)

### Target vs Actual

| Metric | Target | Actual | Status |
|--------|--------|--------|--------|
| Dashboard Load | < 1.5s | TBD | 🟡 |
| Timer Start | < 200ms | TBD | 🟡 |
| Invoice PDF Gen | < 3s | TBD | 🟡 |
| Command Palette | < 50ms | TBD | 🟡 |
| Offline Sync | < 5s | TBD | 🟡 |

**Note**: Benchmarks will be measured before production deployment and documented here.

---

**Maintained By**: Engineering Team
**Last Updated**: November 5, 2025
**Next Review**: Before production deployment
