# 🏠 Run Timeclocker Locally on Your Computer

## ✅ Prerequisites

Before you start, make sure you have these installed on your **local computer**:

1. **PHP 8.3 or 8.4** - [Download PHP](https://www.php.net/downloads)
2. **Composer** - [Download Composer](https://getcomposer.org/download/)
3. **Node.js & npm** - [Download Node.js](https://nodejs.org/)
4. **PostgreSQL** - [Download PostgreSQL](https://www.postgresql.org/download/)
   - OR use SQLite (simpler for local testing)

---

## 🚀 Quick Start Guide

### Step 1: Clone the Repository

On your **local computer**, open a terminal and run:

```bash
# Clone the repository
git clone https://github.com/peterpeeterspeter/solidtime.git
cd solidtime
```

---

### Step 2: Install Dependencies

```bash
# Install PHP dependencies
composer install

# Install Node.js dependencies
npm install
```

This might take 3-5 minutes. Wait for it to complete.

---

### Step 3: Set Up Environment

```bash
# Copy environment file
cp .env.example .env

# Generate application key
php artisan key:generate
```

---

### Step 4: Configure Database

**Option A: Use SQLite (Easiest for local)**

Edit `.env` file and set:

```env
DB_CONNECTION=sqlite
DB_DATABASE=/absolute/path/to/solidtime/database/database.sqlite
```

Then create the database file:

```bash
touch database/database.sqlite
```

**Option B: Use PostgreSQL**

1. Create a database:
   ```bash
   createdb solidtime
   ```

2. Edit `.env`:
   ```env
   DB_CONNECTION=pgsql
   DB_HOST=127.0.0.1
   DB_PORT=5432
   DB_DATABASE=solidtime
   DB_USERNAME=your_username
   DB_PASSWORD=your_password
   ```

---

### Step 5: Run Migrations

```bash
php artisan migrate
```

This creates all the database tables (29 tables total).

---

### Step 6: Install Passport (OAuth)

```bash
php artisan passport:install
```

---

### Step 7: Generate VAPID Keys (Push Notifications)

```bash
npx web-push generate-vapid-keys
```

Copy the output and add to `.env`:

```env
VAPID_PUBLIC_KEY=your_public_key_here
VAPID_PRIVATE_KEY=your_private_key_here
VAPID_SUBJECT=mailto:your@email.com
```

---

### Step 8: Build Frontend Assets

```bash
npm run build
```

This takes about 30 seconds and builds all 310 assets.

---

### Step 9: Create Storage Link

```bash
php artisan storage:link
```

---

### Step 10: Start the Server! 🎉

```bash
php artisan serve
```

You should see:

```
INFO  Server running on [http://127.0.0.1:8000].
Press Ctrl+C to stop the server
```

---

## 🌐 Access the Application

Open your browser and go to:

```
http://localhost:8000
```

Or:

```
http://127.0.0.1:8000
```

You should see the **Timeclocker** login/register page!

---

## 👤 Create Your First User

Click **"Register"** and create an account:
- Name: Your Name
- Email: your@email.com
- Password: (choose a password)

After registration, you'll be automatically logged in! 🎉

---

## 🎨 What You'll See

### Dashboard
- Real-time time tracking statistics
- Beautiful charts and graphs
- Recent time entries
- Quick timer controls

### Features Available
- ⏱️ Time Tracking (start/stop timer)
- 📊 Dashboard with analytics
- 📁 Projects & Clients management
- 📄 Invoice generation
- 📱 PWA (installable app)
- 🔔 Push notifications
- 🌓 Dark mode
- ⌨️ Keyboard shortcuts (press `?`)

---

## 🛠️ Useful Commands

### Development

```bash
# Start server
php artisan serve

# Watch frontend changes (auto-rebuild)
npm run dev

# Run queue worker (for background jobs)
php artisan queue:work
```

### Clear Caches

```bash
# Clear all caches
php artisan optimize:clear

# Or individually:
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear
```

### Database

```bash
# Run migrations
php artisan migrate

# Reset database (WARNING: deletes all data!)
php artisan migrate:fresh

# Check database status
php artisan db:show
```

---

## 🐛 Troubleshooting

### "Connection refused" error
- Make sure PostgreSQL is running: `pg_ctl status`
- Or use SQLite instead (easier for local)

### "SQLSTATE[HY000]" error
- Check your database credentials in `.env`
- Make sure database exists

### Frontend not loading / blank page
- Run: `npm run build`
- Clear browser cache
- Check: `public/build/` folder has files

### "Class not found" error
- Run: `composer dump-autoload`

### Permission errors
```bash
chmod -R 775 storage bootstrap/cache
```

---

## 📊 Check Everything Works

### 1. Test Database Connection
```bash
php artisan db:show
```

Should show your database info.

### 2. Test Server
```bash
curl http://localhost:8000
```

Should return HTML.

### 3. Check Assets
```bash
ls public/build/assets/
```

Should list 310+ files.

---

## ⚙️ Configure for Local Development

Edit `.env` for local development:

```env
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8000
APP_FORCE_HTTPS=false

# Use log mail driver (emails go to logs)
MAIL_MAILER=log

# Use local filesystem (not S3)
FILESYSTEM_DISK=local

# Use database queue (no Redis needed)
QUEUE_CONNECTION=database
```

---

## 🚀 Next Steps

### For Local Development
1. ✅ You're done! Start building features
2. Use `npm run dev` to auto-rebuild on changes
3. Check logs: `tail -f storage/logs/laravel.log`

### For cPanel Deployment (Later)
1. Test everything works locally first
2. Follow the cPanel deployment guide
3. Set up your domain and SSL

---

## 📚 Documentation

- **DEPLOYMENT_GUIDE.md** - Full deployment guide
- **DEPLOYMENT_STATUS.md** - Current status
- **PHASE_3_PLAN.md** - Future features
- **KEYBOARD_SHORTCUTS.md** - All shortcuts
- **INVOICING.md** - Invoicing features

---

## ✅ Verification Checklist

Before you start using the app, verify:

- [ ] Database connected (`php artisan db:show`)
- [ ] Migrations run (29 tables created)
- [ ] Frontend built (`ls public/build/assets/`)
- [ ] Server starts (`php artisan serve`)
- [ ] Can access http://localhost:8000 in browser
- [ ] Can register a user
- [ ] Can login
- [ ] Dashboard loads with charts
- [ ] Can start/stop timer

---

## 🎉 Success!

Once you see the dashboard, you're ready to use Timeclocker!

Try these features:
- Start a timer
- Create a project
- Add a client
- Generate an invoice
- Install as PWA (click install button)
- Try dark mode
- Use keyboard shortcuts (press `?`)

---

## 💡 Pro Tips

1. **Use SQLite for local dev** - Much simpler, no server to manage
2. **Run `npm run dev`** - Auto-rebuilds frontend on changes
3. **Open in Incognito** - If you see cache issues
4. **Check logs** - `storage/logs/laravel.log` for errors
5. **Use keyboard shortcuts** - Press `?` in app to see all shortcuts

---

**Your local server is completely separate from what we built in Claude Code.**

This is a fresh install on YOUR computer that will be accessible from YOUR browser! 🎊

Need help with any step? Check the troubleshooting section or review the full deployment guide!
