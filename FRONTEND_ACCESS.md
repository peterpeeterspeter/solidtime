# 🌐 How to Access the Timeclocker Frontend

## ✅ Server Status

- **Status**: ✅ Running
- **URL**: http://localhost:8000
- **HTTP**: Enabled (HTTPS redirect disabled for local dev)
- **Frontend Assets**: ✅ Built (310 files)
- **Database**: ✅ Connected (PostgreSQL)

---

## 🚀 Accessing the Application

### Method 1: Direct Browser Access (Recommended)

**If you're running this locally:**

1. Open your browser
2. Navigate to: **http://localhost:8000**
3. You'll be redirected to the login page

**First-time setup:**
```bash
# Create an admin user
php artisan make:filament-user

# Follow the prompts to enter:
# - Name
# - Email
# - Password
```

Then login with your credentials!

---

### Method 2: Port Forwarding (If running remotely)

If you're accessing this from a remote machine, you'll need to forward port 8000:

**SSH Port Forwarding:**
```bash
ssh -L 8000:localhost:8000 user@your-server
```

Then access: http://localhost:8000 in your local browser

**VS Code:**
- The port should auto-forward if you're using VS Code Remote
- Check the "PORTS" tab and make sure 8000 is forwarded

---

## 📱 What You'll See

### Landing Page / Login
- Clean, modern UI
- Dark mode toggle
- Login/Register forms
- PWA install prompt

### After Login
- **Dashboard**: Overview with charts and stats
- **Time Tracker**: Start/stop timer with quick actions
- **Projects**: Manage your projects and clients
- **Invoices**: Generate and send invoices
- **Reports**: Analyze your time data
- **Settings**: Configure preferences

---

## 🎨 Features Available

### ✨ Phase 2 Features (All Working)
- ⏱️ **Time Tracking**: Quick timer, manual entries, running timer indicator
- 📊 **Dashboard**: Real-time charts and statistics
- 📁 **Projects & Clients**: Full CRUD operations
- 📄 **Invoicing**: Create, send, and manage invoices
- 📧 **Email**: Send invoices via email
- 📱 **PWA**: Install as app on desktop/mobile
- 🔔 **Push Notifications**: Browser notifications for reminders
- 📴 **Offline Mode**: Works without internet, syncs when back online
- 🌓 **Dark Mode**: Toggle light/dark theme
- ⌨️ **Keyboard Shortcuts**: Fast navigation (press `?` to see shortcuts)
- 🌍 **Multi-language**: English, German, Dutch, French

---

## 🖼️ Screenshots

### Desktop View
The application has a modern, clean interface with:
- Sidebar navigation
- Top bar with user menu
- Main content area with forms and tables
- Responsive charts and visualizations

### Mobile View
- Responsive design works on all screen sizes
- Touch-optimized controls
- PWA installation for native-like experience

---

## 🔐 Default Configuration

```
App Name: Timeclocker
Environment: local
Debug: Enabled
Database: PostgreSQL (solidtime)
Queue: Database driver
Cache: File cache
Mail: Log driver (emails saved to logs)
```

---

## 🛠️ Testing the Frontend

### 1. Registration
```
URL: http://localhost:8000/register
- Fill in: Name, Email, Password
- Click "Register"
- You'll be logged in automatically
```

### 2. Time Tracking
```
1. Click "Time Tracker" in sidebar
2. Select/Create a project
3. Click "Start Timer"
4. Timer runs in background
5. Click "Stop" when done
```

### 3. Create Invoice
```
1. Go to "Invoices"
2. Click "New Invoice"
3. Select client and time entries
4. Generate PDF or send email
```

### 4. Install as PWA
```
1. Click the "Install" button in the address bar
2. Or go to browser menu > Install Timeclocker
3. App opens in its own window
4. Works offline!
```

---

## 🐛 Troubleshooting

### "Connection Refused" or "Can't Connect"
1. Check server is running: `php artisan serve`
2. Restart PostgreSQL: `service postgresql start`
3. Check port 8000 isn't blocked by firewall

### "500 Error" on Page Load
1. Check logs: `tail -f storage/logs/laravel.log`
2. Clear cache: `php artisan cache:clear`
3. Check database: `php artisan db:show`

### Frontend Not Loading / Blank Page
1. Check assets built: `ls public/build/assets/`
2. Rebuild if needed: `npm run build`
3. Clear browser cache

### "HTTPS Required" Error
1. Check `.env`: `APP_FORCE_HTTPS=false`
2. Clear config: `php artisan config:clear`
3. Restart server

---

## 📞 Quick Commands

```bash
# Start server
php artisan serve

# Create user
php artisan make:filament-user

# Check database
php artisan db:show

# View logs
tail -f storage/logs/laravel.log

# Run queue worker
php artisan queue:work

# Watch frontend (development mode)
npm run dev

# Build frontend (production)
npm run build

# Clear caches
php artisan optimize:clear
```

---

## 🌟 Next Steps

1. **Create your account** via registration or `php artisan make:filament-user`
2. **Set up your organization** (auto-created on first login)
3. **Add projects and clients**
4. **Start tracking time**
5. **Generate your first invoice**
6. **Explore all the features!**

---

## 📚 Documentation

- **Main README**: `/README.md`
- **Deployment Guide**: `/docs/DEPLOYMENT_GUIDE.md`
- **Deployment Status**: `/DEPLOYMENT_STATUS.md`
- **Phase 2 Features**: `/PR_PHASE_2.md`
- **Phase 3 Plan**: `/docs/PHASE_3_PLAN.md`
- **Keyboard Shortcuts**: `/docs/KEYBOARD_SHORTCUTS.md`
- **Invoicing Guide**: `/docs/INVOICING.md`

---

**Server Running**: ✅ http://localhost:8000
**Status**: Ready for use!
**Have fun tracking your time!** ⏱️✨
