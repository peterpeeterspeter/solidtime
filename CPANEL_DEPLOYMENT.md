# 🌐 Deploy Timeclocker to cPanel Shared Hosting

## ✅ Prerequisites

Your cPanel hosting must have:
- ✅ **PHP 8.3 or 8.4** (check in cPanel → Select PHP Version)
- ✅ **Composer** (most cPanel hosts have it)
- ✅ **MySQL or PostgreSQL** database
- ✅ **SSH access** (optional but helpful)
- ✅ **Node.js** (for building assets locally)
- ✅ Enough disk space (~500MB)

---

## 📋 Overview

We'll deploy in 3 main steps:
1. **Prepare locally** - Build assets on your computer
2. **Upload to cPanel** - Transfer files via FTP/SSH
3. **Configure** - Set up database and environment

---

## 🚀 Step-by-Step Deployment

### Step 1: Prepare on Your Local Computer

#### 1.1 Build Production Assets

On your local machine:

```bash
cd solidtime

# Install dependencies (if not already done)
composer install --optimize-autoloader --no-dev
npm install

# Build for production
npm run build

# This creates optimized assets in public/build/
```

#### 1.2 Create .env for Production

Copy `.env.example` to `.env.production` and edit:

```env
APP_NAME=Timeclocker
APP_ENV=production
APP_DEBUG=false
APP_URL=https://yourdomain.com
APP_FORCE_HTTPS=true

# You'll set these in cPanel later
DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=your_db_name
DB_USERNAME=your_db_user
DB_PASSWORD=your_db_password

# Use database queue (no Redis on shared hosting)
QUEUE_CONNECTION=database
CACHE_DRIVER=file
SESSION_DRIVER=database

# Use file mail driver or configure SMTP
MAIL_MAILER=smtp
MAIL_HOST=smtp.your-host.com
MAIL_PORT=587
MAIL_USERNAME=your@email.com
MAIL_PASSWORD=your_password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@yourdomain.com

# Filesystems - local storage
FILESYSTEM_DISK=local
```

---

### Step 2: Create Database in cPanel

#### 2.1 Create MySQL Database

1. **Login to cPanel**
2. Go to **"MySQL Databases"**
3. **Create a new database**:
   - Database name: `timeclocker` (or your choice)
   - Click "Create Database"
4. **Create a database user**:
   - Username: `timeclocker_user`
   - Password: (generate strong password)
   - Click "Create User"
5. **Add user to database**:
   - Select user and database
   - Grant **ALL PRIVILEGES**
   - Click "Add"

**Save these credentials** - you'll need them for `.env`

---

### Step 3: Upload Files to cPanel

You have 3 options:

#### Option A: Upload via File Manager (Simple)

1. In cPanel, go to **"File Manager"**
2. Navigate to `public_html` or your domain folder
3. Create a folder structure:
   ```
   public_html/
   ├── timeclocker/    (your Laravel app - NOT public)
   └── public/         (or use public_html as public)
   ```

4. **Upload all files EXCEPT**:
   - `.git/` folder
   - `node_modules/` folder
   - `.env` file (create new on server)
   - `storage/` contents (except `.gitignore` files)

5. **Compress locally first** (faster upload):
   ```bash
   # On your computer
   tar -czf timeclocker.tar.gz --exclude='node_modules' --exclude='.git' --exclude='storage/*.log' .
   ```

6. Upload `timeclocker.tar.gz` and extract in cPanel

#### Option B: Upload via FTP (Recommended)

1. Use FileZilla or similar FTP client
2. Connect to your cPanel FTP
3. Upload all files to `/home/username/timeclocker/`
4. **Skip** `.git`, `node_modules`, large logs

#### Option C: Git Deploy (Advanced - if SSH available)

```bash
# SSH into your server
ssh username@yourserver.com

# Clone repository
cd ~/public_html
git clone https://github.com/yourusername/solidtime.git timeclocker
cd timeclocker

# Install dependencies
composer install --optimize-autoloader --no-dev
```

---

### Step 4: Configure on cPanel

#### 4.1 Set Up .env File

1. In File Manager, navigate to your app folder
2. Create new file `.env`
3. Copy contents from `.env.production`
4. Update with your actual database credentials:

```env
DB_CONNECTION=mysql
DB_HOST=localhost
DB_DATABASE=your_actual_db_name
DB_USERNAME=your_actual_db_user
DB_PASSWORD=your_actual_db_password
```

#### 4.2 Generate Application Key

Via SSH:
```bash
cd ~/timeclocker
php artisan key:generate
```

Or via **Terminal in cPanel**:
```bash
cd /home/username/timeclocker
/usr/local/bin/php artisan key:generate
```

Or manually:
```bash
# On your local machine
php -r "echo base64_encode(random_bytes(32)) . PHP_EOL;"
```
Add to `.env`:
```env
APP_KEY=base64:PASTE_THE_KEY_HERE
```

#### 4.3 Set Proper Permissions

```bash
chmod -R 755 storage bootstrap/cache
chown -R username:username storage bootstrap/cache
```

Or in File Manager:
- Right-click `storage` → Permissions → 755
- Right-click `bootstrap/cache` → Permissions → 755

---

### Step 5: Run Migrations

Via SSH or Terminal in cPanel:

```bash
cd /home/username/timeclocker
php artisan migrate --force
```

This creates all 29 database tables.

---

### Step 6: Install Passport (OAuth)

```bash
php artisan passport:install --force
```

Save the generated client IDs and secrets.

---

### Step 7: Generate VAPID Keys

On your **local computer** (cPanel doesn't have npm usually):

```bash
npx web-push generate-vapid-keys
```

Copy the keys to your cPanel `.env` file:

```env
VAPID_PUBLIC_KEY=BN2hf...
VAPID_PRIVATE_KEY=lBOba...
VAPID_SUBJECT=mailto:your@email.com
```

---

### Step 8: Configure Public Directory

#### Option A: Using Subdomain

1. In cPanel → **"Subdomains"**
2. Create subdomain: `app.yourdomain.com`
3. Set document root to: `/home/username/timeclocker/public`

#### Option B: Using Main Domain

1. In cPanel → **"Domains"**
2. Edit domain document root
3. Point to: `/home/username/timeclocker/public`

#### Option C: Using .htaccess Redirect

If you can't change document root, create `.htaccess` in `public_html`:

```apache
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteRule ^(.*)$ timeclocker/public/$1 [L]
</IfModule>
```

---

### Step 9: Optimize for Production

```bash
# Cache configuration
php artisan config:cache

# Cache routes
php artisan route:cache

# Cache views
php artisan view:cache

# Create storage link
php artisan storage:link
```

---

### Step 10: Set Up Cron Jobs (Important!)

Laravel needs cron jobs for scheduled tasks.

1. In cPanel → **"Cron Jobs"**
2. Add new cron job:

```
* * * * * cd /home/username/timeclocker && php artisan schedule:run >> /dev/null 2>&1
```

Runs every minute to check for scheduled tasks.

---

### Step 11: Configure Queue Worker (Optional)

For background jobs (emails, notifications):

#### Option A: Cron-based Queue

Add to cron jobs:
```
* * * * * cd /home/username/timeclocker && php artisan queue:work --stop-when-empty >> /dev/null 2>&1
```

#### Option B: Supervisor (if available)

Ask your host if they support Supervisor for long-running processes.

---

## 🔒 Security Checklist

- [ ] `.env` file is not accessible via web
- [ ] `APP_DEBUG=false` in production
- [ ] Strong database password
- [ ] SSL certificate installed (HTTPS)
- [ ] Remove `.git` folder from production
- [ ] Set proper file permissions (755/644)
- [ ] Configure CSRF protection
- [ ] Rate limiting enabled

---

## 🌐 Configure Domain & SSL

### Enable HTTPS

1. In cPanel → **"SSL/TLS Status"**
2. Enable AutoSSL or install Let's Encrypt certificate
3. Force HTTPS in `.env`:
   ```env
   APP_FORCE_HTTPS=true
   APP_URL=https://yourdomain.com
   ```

### Configure .htaccess for HTTPS

Add to `public/.htaccess` (before other rules):

```apache
# Force HTTPS
RewriteCond %{HTTPS} off
RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
```

---

## 🧪 Test Your Deployment

### 1. Check Homepage
```
https://yourdomain.com
```
Should redirect to `/login`

### 2. Register User
Create a test account

### 3. Test Features
- Time tracking works
- Dashboard loads
- Projects can be created
- Invoices can be generated

### 4. Check Logs
```
storage/logs/laravel.log
```
Should have no errors

---

## 🐛 Common Issues & Solutions

### "500 Internal Server Error"

**Check:**
1. File permissions (755 for folders, 644 for files)
2. `.env` file exists and is correct
3. `php artisan config:clear`
4. Check `storage/logs/laravel.log`

### "Base table or view not found"

**Solution:**
```bash
php artisan migrate --force
```

### "The stream or file could not be opened"

**Solution:**
```bash
chmod -R 775 storage bootstrap/cache
```

### Images/CSS not loading

**Solutions:**
1. Run: `php artisan storage:link`
2. Check `public/build/` folder has files
3. Verify APP_URL in `.env` matches your domain

### Database connection error

**Check:**
1. Database credentials in `.env`
2. Database user has all privileges
3. MySQL is running
4. Try `127.0.0.1` instead of `localhost`

### "Specified key was too long"

**In `app/Providers/AppServiceProvider.php`:**
```php
use Illuminate\Support\Facades\Schema;

public function boot()
{
    Schema::defaultStringLength(191);
}
```

---

## 📊 Performance Optimization

### Enable OPcache

In cPanel → **"Select PHP Version"** → **"Options"**:
- Enable: `opcache`
- Set: `opcache.enable=1`

### Optimize Composer Autoloader

```bash
composer dump-autoload --optimize --classmap-authoritative
```

### Cache Everything

```bash
php artisan optimize
```

This runs:
- config:cache
- route:cache
- view:cache

---

## 🔄 Updating Your App

When you make changes:

```bash
# On local machine - build assets
npm run build

# Upload changed files via FTP

# On server - clear caches
php artisan optimize:clear
php artisan optimize
```

---

## 📱 PWA Configuration

For PWA to work, you need:
1. ✅ HTTPS enabled (required for service workers)
2. ✅ Manifest file at `/build/manifest.webmanifest`
3. ✅ Service worker at `/build/sw.js`

These are automatically included if you ran `npm run build`!

---

## 💾 Backup Strategy

### Database Backup

1. In cPanel → **"phpMyAdmin"**
2. Select your database → **"Export"**
3. Download SQL file

Or via cron:
```bash
0 2 * * * mysqldump -u user -ppassword dbname > /home/user/backups/db_$(date +\%Y\%m\%d).sql
```

### Files Backup

1. In cPanel → **"Backup Wizard"**
2. Create full backup or home directory backup
3. Download backup files

---

## 📧 Email Configuration

### Using cPanel Email

```env
MAIL_MAILER=smtp
MAIL_HOST=mail.yourdomain.com
MAIL_PORT=587
MAIL_USERNAME=noreply@yourdomain.com
MAIL_PASSWORD=your_email_password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@yourdomain.com
MAIL_FROM_NAME="${APP_NAME}"
```

### Using Gmail

```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your@gmail.com
MAIL_PASSWORD=your_app_password
MAIL_ENCRYPTION=tls
```

Note: Use App Password, not regular password!

---

## ✅ Final Checklist

Before going live:

- [ ] All files uploaded
- [ ] Database created and migrated
- [ ] `.env` configured with production values
- [ ] APP_KEY generated
- [ ] Passport installed
- [ ] VAPID keys generated
- [ ] Storage link created
- [ ] Permissions set correctly (755/644)
- [ ] Cron job configured
- [ ] SSL/HTTPS enabled
- [ ] APP_DEBUG=false
- [ ] Test registration/login
- [ ] Test time tracking
- [ ] Test invoice generation
- [ ] Check all logs for errors
- [ ] Set up database backups

---

## 🎉 You're Live!

Your Timeclocker application is now accessible at:

```
https://yourdomain.com
```

Users can:
- ✅ Register accounts
- ✅ Track time
- ✅ Manage projects
- ✅ Generate invoices
- ✅ Install as PWA
- ✅ Use offline mode
- ✅ Receive notifications

---

## 📞 Need Help?

**Check:**
1. `storage/logs/laravel.log` - Application errors
2. cPanel error logs
3. Browser console (F12) - Frontend errors

**Common Resources:**
- Laravel Docs: https://laravel.com/docs
- cPanel Docs: https://docs.cpanel.net/
- Your hosting support

---

**Congratulations on your deployment!** 🎊

Your time tracking application is now live and accessible to the world!
