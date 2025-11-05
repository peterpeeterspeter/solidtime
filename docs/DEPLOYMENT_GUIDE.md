# Timeclocker - Deployment Guide

**Phase 2 Complete** - Ready for deployment
**Branch**: `claude/merged-main-011CUpPkiWUksdhCGjJgAgX3`
**Date**: November 5, 2025

---

## 🎯 Quick Deployment Checklist

```bash
# 1. Install dependencies
composer install --optimize-autoloader --no-dev
npm install

# 2. Run database migrations
php artisan migrate --force

# 3. Generate VAPID keys
php artisan webpush:vapid

# 4. Generate PWA icons
npm run icons:generate

# 5. Build frontend assets
npm run build

# 6. Clear caches
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 7. Start queue worker
php artisan queue:work --daemon

# 8. Verify installation
php artisan tinker
>>> App\Models\User::count()
```

---

## 📋 Detailed Deployment Steps

### Step 1: Pull Latest Code

```bash
# If you have a main branch locally
git checkout main
git pull origin claude/merged-main-011CUpPkiWUksdhCGjJgAgX3

# Or start fresh
git clone <your-repo-url>
cd timeclocker
git checkout claude/merged-main-011CUpPkiWUksdhCGjJgAgX3
```

### Step 2: Install Dependencies

```bash
# Backend dependencies
composer install --optimize-autoloader --no-dev

# Frontend dependencies
npm install --legacy-peer-deps
```

**Note**: `--legacy-peer-deps` is needed because vite-plugin-pwa requires Vite 3-5 but we're using Vite 6.

### Step 3: Environment Configuration

```bash
# Copy environment file if not exists
cp .env.example .env

# Generate application key (if not already done)
php artisan key:generate
```

**Update .env with these new variables:**

```env
# App Configuration
APP_NAME=Timeclocker
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.com

# Database
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=timeclocker
DB_USERNAME=your_db_user
DB_PASSWORD=your_db_password

# Queue Configuration (REQUIRED for notifications)
QUEUE_CONNECTION=redis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

# Push Notifications (generate with: php artisan webpush:vapid)
VAPID_PUBLIC_KEY=
VAPID_PRIVATE_KEY=
VAPID_SUBJECT=mailto:support@timeclocker.io

# Mail Configuration (for invoice emails)
MAIL_MAILER=smtp
MAIL_HOST=your-mail-host
MAIL_PORT=587
MAIL_USERNAME=your-email@example.com
MAIL_PASSWORD=your-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@timeclocker.io
MAIL_FROM_NAME="${APP_NAME}"

# Gotenberg (for PDF generation - optional but recommended)
GOTENBERG_URL=http://localhost:3000
```

### Step 4: Generate VAPID Keys

```bash
# Generate VAPID keys for push notifications
php artisan webpush:vapid

# This will output:
# VAPID_PUBLIC_KEY=...
# VAPID_PRIVATE_KEY=...

# Copy these to your .env file
```

**Manual alternative if command doesn't exist:**
```bash
# You'll need to install web-push library or use online generator
# https://web-push-codelab.glitch.me/
```

### Step 5: Database Migration

```bash
# Run migrations
php artisan migrate --force

# This will create:
# - push_subscriptions table (new in Phase 2)
# - All existing tables from Phase 1
```

**Verify migration:**
```bash
php artisan tinker
>>> DB::table('push_subscriptions')->count()
# Should return 0 (no subscriptions yet)
```

### Step 6: Generate PWA Icons

```bash
# Generate all PWA icons from SVG template
npm run icons:generate

# This creates:
# - public/images/pwa-192x192.png
# - public/images/pwa-512x512.png
# - public/images/pwa-192x192-maskable.png
# - public/images/pwa-512x512-maskable.png
# - public/images/apple-touch-icon.png
# - public/images/favicon-16x16.png
# - public/images/favicon-32x32.png
```

**Verify icons:**
```bash
ls -lh public/images/pwa-*.png
ls -lh public/images/favicon-*.png
```

### Step 7: Build Frontend Assets

```bash
# Build for production
npm run build

# This will:
# - Bundle all JavaScript
# - Process all CSS
# - Generate service worker
# - Create PWA manifest
# - Optimize assets
```

**Verify build:**
```bash
ls -lh public/build/manifest.json
ls -lh public/build/assets/
```

### Step 8: Optimize Laravel

```bash
# Cache configuration
php artisan config:cache

# Cache routes
php artisan route:cache

# Cache views
php artisan view:cache

# Optimize autoloader
composer dump-autoload --optimize
```

### Step 9: Set Permissions

```bash
# Storage and cache permissions
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache

# Or for your web server user
chown -R nginx:nginx storage bootstrap/cache
```

### Step 10: Start Queue Worker

**For Production (with Supervisor):**

Create `/etc/supervisor/conf.d/timeclocker-worker.conf`:

```ini
[program:timeclocker-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/timeclocker/artisan queue:work redis --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/var/www/timeclocker/storage/logs/worker.log
stopwaitsecs=3600
```

```bash
# Reload supervisor
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start timeclocker-worker:*
```

**For Development/Testing:**

```bash
# Run queue worker
php artisan queue:work --daemon

# Or with restart on code changes
php artisan queue:listen
```

---

## 🔧 Service Configuration

### Redis (Required for Queues)

```bash
# Install Redis (Ubuntu/Debian)
sudo apt-get install redis-server

# Start Redis
sudo systemctl start redis
sudo systemctl enable redis

# Verify Redis
redis-cli ping
# Should return: PONG
```

### Gotenberg (Optional - for PDF generation)

```bash
# Using Docker
docker run -d \
  --name gotenberg \
  -p 3000:3000 \
  --restart unless-stopped \
  gotenberg/gotenberg:7

# Verify Gotenberg
curl http://localhost:3000/health
# Should return: {"status":"up"}
```

**Alternative**: Use Laravel Snappy or other PDF libraries if Gotenberg not available.

### Web Server Configuration

**Nginx Example:**

```nginx
server {
    listen 80;
    listen [::]:80;
    server_name timeclocker.yourdomain.com;

    # Redirect to HTTPS (required for PWA)
    return 301 https://$server_name$request_uri;
}

server {
    listen 443 ssl http2;
    listen [::]:443 ssl http2;
    server_name timeclocker.yourdomain.com;

    root /var/www/timeclocker/public;
    index index.php;

    # SSL Configuration
    ssl_certificate /path/to/cert.pem;
    ssl_certificate_key /path/to/key.pem;

    # PWA and Service Worker
    add_header Service-Worker-Allowed /;

    # Security Headers
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-XSS-Protection "1; mode=block" always;

    # Gzip Compression
    gzip on;
    gzip_types text/plain text/css application/json application/javascript text/xml application/xml application/xml+rss text/javascript;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

---

## ✅ Post-Deployment Verification

### 1. Check Application Health

```bash
# Visit your application
curl https://your-domain.com

# Should return HTML with status 200
```

### 2. Test PWA Installation

1. Open Chrome/Firefox/Edge
2. Visit https://your-domain.com
3. Look for install prompt in address bar
4. Click install
5. Verify app opens in standalone mode

### 3. Test Offline Functionality

1. Open installed PWA
2. Start a timer
3. Enable airplane mode
4. Create some time entries
5. Disable airplane mode
6. Verify entries sync automatically

### 4. Test Push Notifications

1. Go to Profile → Settings → Notifications
2. Enable "Timer Reminders"
3. Grant notification permission
4. Start a timer
5. Wait for reminder (or trigger manually)
6. Verify notification received

### 5. Test Invoice Generation

1. Track some billable time
2. Go to Time Entries
3. Select entries
4. Click "Create Invoice"
5. Review invoice
6. Verify PDF downloads (if Gotenberg configured)

### 6. Check Queue Processing

```bash
# Check queue status
php artisan queue:work --once

# Check failed jobs
php artisan queue:failed

# Monitor queue in real-time
php artisan horizon:list  # If using Horizon
```

### 7. Test Keyboard Shortcuts

1. Press `Cmd/Ctrl+K` - Command palette should open
2. Press `Cmd/Ctrl+T` - Timer modal should open
3. Press `?` - Keyboard shortcuts modal should open
4. Test navigation shortcuts

### 8. Check Logs

```bash
# Application logs
tail -f storage/logs/laravel.log

# Queue worker logs
tail -f storage/logs/worker.log

# Web server logs
tail -f /var/log/nginx/error.log
```

---

## 🚨 Troubleshooting

### Issue: PWA doesn't show install prompt

**Solutions:**
1. Ensure HTTPS is enabled (required for PWA)
2. Check service worker is registered: `navigator.serviceWorker.getRegistrations()`
3. Verify manifest.json is accessible: `/build/manifest.json`
4. Check browser console for errors

### Issue: Push notifications don't work

**Solutions:**
1. Verify VAPID keys in .env
2. Check Redis is running: `redis-cli ping`
3. Verify queue worker is running: `supervisorctl status`
4. Check push_subscriptions table has entries
5. Test notification permission granted in browser

### Issue: Offline sync doesn't work

**Solutions:**
1. Open browser DevTools → Application → Service Workers
2. Verify service worker is active
3. Check IndexedDB has 'TimeclockerOfflineDB' database
4. Check network tab for sync requests when online

### Issue: PDF generation fails

**Solutions:**
1. Check Gotenberg is running: `curl http://localhost:3000/health`
2. Verify GOTENBERG_URL in .env
3. Check Laravel logs for PDF errors
4. Alternative: Disable PDF generation temporarily, use HTML preview

### Issue: Queue jobs not processing

**Solutions:**
1. Check Redis connection: `php artisan tinker`, `Redis::ping()`
2. Restart queue worker: `supervisorctl restart timeclocker-worker:*`
3. Check failed jobs: `php artisan queue:failed`
4. Retry failed jobs: `php artisan queue:retry all`

---

## 📊 Monitoring

### Key Metrics to Monitor

**Application:**
- Response time (< 200ms for API calls)
- Error rate (< 1%)
- Queue processing time
- Cache hit rate

**PWA:**
- Service worker activation rate
- Offline usage percentage
- Cache size (should stay < 10MB)
- Install conversion rate

**Notifications:**
- Push subscription rate
- Notification delivery rate
- Notification click rate
- Permission grant rate

### Recommended Tools

- **Application Monitoring**: New Relic, DataDog, Laravel Telescope
- **Error Tracking**: Sentry, Bugsnag, Rollbar
- **Uptime Monitoring**: Pingdom, UptimeRobot
- **Analytics**: Google Analytics, Plausible
- **Performance**: Lighthouse CI, WebPageTest

---

## 🔒 Security Checklist

- [ ] HTTPS enabled (required for PWA)
- [ ] Environment variables secured (.env not in git)
- [ ] Database credentials rotated
- [ ] VAPID keys generated and secured
- [ ] Session cookie secure and httponly
- [ ] CSRF protection enabled
- [ ] Rate limiting configured
- [ ] File upload validation (for invoice logos)
- [ ] SQL injection prevention (use Eloquent)
- [ ] XSS prevention (escaped output)

---

## 📈 Performance Optimization

### Already Implemented:
- ✅ Service worker caching
- ✅ Code splitting (route-based)
- ✅ Image optimization (Sharp)
- ✅ Lazy loading components
- ✅ Debounced search inputs

### Recommended for Production:
- [ ] Enable Opcache (PHP)
- [ ] Configure Redis cache driver
- [ ] Enable Gzip/Brotli compression
- [ ] Configure CDN for static assets
- [ ] Implement database query caching
- [ ] Enable Laravel route caching
- [ ] Configure asset versioning/cache busting

---

## 📦 Backup Strategy

### Database Backups

```bash
# Daily database backup
0 2 * * * pg_dump -U postgres timeclocker | gzip > /backups/db-$(date +\%Y\%m\%d).sql.gz

# Keep 30 days of backups
find /backups -name "db-*.sql.gz" -mtime +30 -delete
```

### Application Backups

```bash
# Weekly full backup
0 3 * * 0 tar -czf /backups/app-$(date +\%Y\%m\%d).tar.gz /var/www/timeclocker --exclude=node_modules --exclude=vendor

# Keep 12 weeks of backups
find /backups -name "app-*.tar.gz" -mtime +84 -delete
```

---

## 🎯 Next Steps After Deployment

1. **User Acceptance Testing**
   - Invite beta users
   - Collect feedback
   - Monitor error logs

2. **Marketing & Launch**
   - Announce Phase 2 features
   - Update website/landing page
   - Social media posts

3. **Documentation**
   - Create video tutorials
   - Write blog posts
   - Update help center

4. **Phase 3 Planning**
   - Prioritize features
   - Set timeline
   - Allocate resources

---

## 📞 Support

**Documentation:**
- README: `/README.md`
- Keyboard Shortcuts: `/docs/KEYBOARD_SHORTCUTS.md`
- Invoicing Guide: `/docs/INVOICING.md`
- Testing Plan: `/docs/TESTING_PLAN.md`

**Issues:**
- GitHub Issues: https://github.com/yourorg/timeclocker/issues
- Email: support@timeclocker.io

---

**Deployment Guide Version**: 1.0
**Last Updated**: November 5, 2025
**Phase**: 2 Complete
