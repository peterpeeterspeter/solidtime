# Timeclocker - EU Time Tracking for Freelancers & Teams

[![GitHub License](https://img.shields.io/github/license/solidtime-io/solidtime?style=flat-square)](https://github.com/solidtime-io/solidtime/blob/main/LICENSE.md)
[![Codecov](https://img.shields.io/codecov/c/github/solidtime-io/solidtime?style=flat-square&logo=codecov)](https://codecov.io/gh/solidtime-io/solidtime)
![GitHub Actions Unit Tests Status](https://img.shields.io/github/actions/workflow/status/solidtime-io/solidtime/phpunit.yml?style=flat-square)
![PHPStan badge](https://img.shields.io/badge/PHPStan-Level_7-blue?style=flat-square&color=blue)

**Timeclocker** is a modern, privacy-first time tracking platform built specifically for EU-based freelancers and small teams. Forked from [solidtime](https://github.com/solidtime-io/solidtime), Timeclocker emphasizes **GDPR compliance**, **EU data residency**, and **instant invoicing** while maintaining the speed and simplicity you expect from modern productivity tools.

## Why Timeclocker?

- ⚡ **One-Click Tracking** - Start/stop timers instantly with keyboard shortcuts
- 🇪🇺 **EU Data Centers** - All data stored exclusively in European data centers
- 🔒 **GDPR-First** - Full compliance, data export/delete on demand
- 🧾 **Instant Invoicing** - Generate branded PDF invoices from tracked time
- 🌍 **Multi-language** - English, German, Dutch, French support
- 🔓 **Open Source** - AGPL-3.0 licensed, transparent and auditable

## Features

### Core Time Tracking
- ⏱️ **Quick Start/Stop** - One-click timer with optional project/task selection (Cmd/Ctrl+T)
- ⚡ **Always-Visible Timer** - Timer widget in navigation bar, never lose track
- ⌨️ **Keyboard Shortcuts** - Full keyboard navigation for power users ([See all shortcuts](docs/KEYBOARD_SHORTCUTS.md))
- 🎯 **Command Palette** - Universal search and actions (Cmd/Ctrl+K)
- 📝 **Manual Entry** - Add or adjust hours for any timeframe
- 💰 **Billable Tracking** - Mark entries as billable/non-billable with custom rates

### Invoicing & Billing
- 🧾 **Invoice Generator** - Create professional PDF invoices from tracked time ([Guide](docs/INVOICING.md))
- 🇪🇺 **EU-Compliant Invoices** - VAT numbers, reverse charge mechanism, multi-currency
- ⚡ **Instant PDF Generation** - Professional invoices in seconds
- 🎨 **Branded Invoices** - Customize with your logo and branding
- 📊 **Invoice Status** - Track draft/sent/paid/overdue status per invoice
- 📤 **Multiple Export Formats** - PDF, CSV, and shareable links

### Team Collaboration
- 👥 **Team Management** - Invite members, set roles, manage permissions
- ✅ **Timesheet Approval** - Admin review and approve team entries
- 📈 **Organization Dashboard** - Real-time stats and team analytics
- 🔐 **Role-Based Access** - Owner, Admin, Editor, Viewer roles

### Privacy & Compliance
- 🔒 **GDPR Compliance** - Full data protection regulation compliance
- 🇪🇺 **EU Data Residency** - All data stored in European data centers
- 📥 **Data Export** - One-click export of all your data
- 🗑️ **Data Deletion** - Complete data removal on demand
- 🔍 **Audit Logging** - Full change history and transparency

### Integrations & Import
- 📥 **Import from Competitors** - Toggl, Clockify, Harvest, CSV
- 📅 **Calendar Sync** - Google Calendar, iCal integration
- 🔗 **API Access** - RESTful API with OAuth 2.0
- 🔌 **Zapier Ready** - Automation and workflow integration

### PWA & Mobile
- 📱 **Progressive Web App** - Install on any device, works like a native app
- 🔌 **Offline Support** - Track time without internet, syncs automatically
- 🔔 **Smart Notifications** - Break reminders, daily summaries, timer alerts
- 📲 **Add to Home Screen** - One-tap access from mobile home screen
- ⚡ **Background Sync** - Automatic data synchronization when online

### Reporting & Analytics
- 📊 **Detailed Reports** - Hours by client, project, team, period
- 📈 **Visual Dashboards** - Charts and graphs for insights
- 💼 **Billability Tracking** - Monitor billable vs non-billable time
- 📤 **Export Options** - PDF, Excel, public report links

## Getting Started

### For End Users

**New to Timeclocker?** The first time you log in, you'll be guided through an interactive onboarding wizard that helps you:
- Set up your workspace and preferences
- Create your first project
- Learn essential keyboard shortcuts
- Enable notifications and PWA features

**Quick Start:**
1. **Start Timer**: Press `Cmd+T` (Mac) or `Ctrl+T` (Windows) to instantly start tracking time
2. **Command Palette**: Press `Cmd+K` / `Ctrl+K` to access all features instantly
3. **Create Invoice**: Go to Time Entries → Select entries → "Create Invoice"
4. **Install PWA**: Look for browser install prompt or "Add to Home Screen"

📖 [Full User Guide](docs/USER_GUIDE.md) | ⌨️ [Keyboard Shortcuts](docs/KEYBOARD_SHORTCUTS.md) | 🧾 [Invoice Guide](docs/INVOICING.md)

### For Developers

**Prerequisites:**
- PHP 8.2+
- Node.js 20+
- PostgreSQL 15+
- Composer
- npm/pnpm

**Installation:**
```bash
# Clone repository
git clone https://github.com/yourorg/timeclocker.git
cd timeclocker

# Install dependencies
composer install
npm install

# Configure environment
cp .env.example .env
php artisan key:generate

# Generate VAPID keys for push notifications
php artisan webpush:vapid

# Generate PWA icons
npm run icons:generate

# Run migrations
php artisan migrate

# Build assets
npm run build

# Start development server
php artisan serve
npm run dev
```

📖 [Full Development Guide](docs/DEVELOPMENT.md)

## Self Hosting

If you are looking into self-hosting solidtime, you can find the guides [here](https://docs.solidtime.io/self-hosting/intro)

We also have an examples repository [here](https://github.com/solidtime-io/self-hosting-examples)

If you do not want to self-host solidtime or try it out you can sign up for [solidtime cloud](https://www.solidtime.io/)

## Issues & Feature Requests

If you find any **bugs in solidtime**, please feel free to [**open an issue**](https://github.com/solidtime-io/solidtime/issues/new) in this repository, with instructions on how to reproduce the bug. 
If you have a **feature request**, please [**create a discussion**](https://github.com/solidtime-io/solidtime/discussions/new?category=feature-requests) in this repository.

## Contributing

Please open an issue or start a discussion and wait for approval before submitting a pull request. This does not apply to tiny fixes or changes however, please keep in mind that we might not merge PRs for various reasons. 

Please read the [CONTRIBUTING.md](./CONTRIBUTING.md) before sumbitting a Pull Request.

We do accept contributions in the [documentation repository](https://github.com/solidtime-io/docs) f.e. to add new self-hosting guides.

## Security

Looking to report a vulnerability? Please refer our [SECURITY.md](./SECURITY.md) file.

## License

This project is open-source and available under the GNU Affero General Public License v3.0 (AGPL v3). Please see the [license file](LICENSE.md) for more information.
