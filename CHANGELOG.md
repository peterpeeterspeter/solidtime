# Changelog

All notable changes to Timeclocker will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

---

## [Phase 2] - 2025-11-05

### 🚀 Major Features

#### PWA & Offline Support
- **Progressive Web App**: Installable on desktop and mobile devices
- **Offline Time Tracking**: Track time without internet connection
- **Background Sync**: Automatic synchronization when back online
- **Service Worker**: Intelligent caching strategies for optimal performance
- **PWA Icons**: Professional icon set (192x192, 512x512, maskable, Apple touch)
- **Install Prompt**: Smart install banner for PWA-capable browsers

#### Push Notifications
- **Web Push API**: Native browser notifications
- **Notification Types**: Timer reminders, daily summaries, break reminders, end-of-day, team updates
- **Permission Management**: User-friendly permission request flow with EU privacy messaging
- **Notification Preferences**: Granular control in user settings
- **VAPID Authentication**: Secure push notification delivery
- **Background Notifications**: Service worker powered notifications

#### Keyboard Shortcuts & Command Palette
- **Global Shortcuts**: Cmd/Ctrl+T (timer), Cmd/Ctrl+K (command palette), and more
- **Command Palette**: Universal search and action center (Cmd+K pattern)
- **Fuzzy Search**: Intelligent search across commands, projects, and actions
- **Keyboard Navigation**: Full arrow key and enter/escape support
- **Platform Aware**: Automatic detection of Mac (⌘) vs Windows/Linux (Ctrl)
- **Help Modal**: Press ? to see all available shortcuts
- **Quick Timer**: Always-visible timer widget with keyboard access

#### Onboarding Wizard
- **Multi-Step Flow**: Welcome → Workspace Setup → First Project → Tips
- **Progress Tracking**: Visual progress indicator and completion tracking
- **Smart Defaults**: Timezone auto-detection, EU-focused suggestions
- **Skip Options**: Flexible onboarding with skip and resume later
- **Interactive Tutorial**: Contextual tips and feature highlights
- **First-Run Experience**: Smooth introduction for new users

#### Invoice Generation
- **EU-Compliant Invoices**: Full GDPR and VAT directive compliance
- **Invoice from Time**: Generate invoices directly from tracked time entries
- **Manual Invoices**: Create invoices for non-tracked services
- **VAT Handling**: Automatic VAT calculation with reverse charge support
- **Multi-Currency**: Support for EUR, USD, GBP, and all major currencies
- **Professional PDF**: High-quality A4 PDF generation via Gotenberg
- **Invoice Branding**: Custom logo, colors, and footer text
- **Invoice Numbering**: Automatic sequential numbering with custom prefixes
- **Invoice Management**: Draft, Sent, Paid, Overdue status tracking
- **Invoice Dashboard**: Revenue, outstanding, and overdue statistics

### ✨ Enhancements

#### Time Tracking
- **Quick Start Timer**: One-click timer start from anywhere
- **Recent Projects Dropdown**: Quick access to frequently used projects
- **Timer Widget**: Always-visible timer in navigation bar
- **Keyboard Timer Control**: Cmd/Ctrl+T to start/stop timer instantly

#### User Interface
- **Offline Status Indicator**: Visual indicator when offline with pending sync count
- **Loading States**: Improved loading and skeleton screens
- **Dark Mode**: Full dark mode support across all new components
- **Responsive Design**: Mobile-optimized layouts for all new features
- **Accessibility**: ARIA labels, keyboard navigation, and screen reader support

#### Settings & Preferences
- **Notification Preferences**: Granular notification settings per type
- **Invoice Settings**: Business details, VAT number, bank details
- **Invoice Branding**: Logo upload, color customization, footer text
- **Toggle Switch Component**: Reusable toggle for settings

### 🛠️ Technical Improvements

#### Frontend
- **Dexie.js Integration**: IndexedDB wrapper for offline storage
- **Offline Database Schema**: Structured storage for time entries and sync queue
- **Offline Sync Service**: Exponential backoff retry logic (2s, 4s, 8s, 16s, 32s)
- **Service Worker**: Workbox-powered caching strategies
- **Vue Composables**: Reusable composables for offline, notifications, invoices, keyboard
- **TypeScript Interfaces**: Complete type system for invoices, notifications, and onboarding
- **PWA Vite Plugin**: Automatic service worker generation and manifest

#### Backend
- **Push Subscriptions API**: RESTful endpoints for push notification management
- **Push Subscriptions Table**: Database schema for storing VAPID subscriptions
- **Invoice Controllers**: CRUD operations for invoice management
- **Invoice Validation**: Request validation for invoice creation and updates
- **VAPID Configuration**: Environment variables for push notification keys

#### Build & Tooling
- **Icon Generation Script**: Automated PWA icon generation from SVG
- **Sharp Integration**: High-quality image processing for icons
- **Legacy Peer Deps**: Support for Vite 6 compatibility
- **ES Module Support**: Modern JavaScript module system

### 📚 Documentation

#### New Documentation
- **KEYBOARD_SHORTCUTS.md**: Complete keyboard shortcuts reference
- **INVOICING.md**: Comprehensive invoice generation guide
- **CHANGELOG.md**: Version history and release notes
- **README Updates**: Phase 2 features, installation guide, quick start

#### Guides
- **PWA Installation**: How to install Timeclocker as a PWA
- **Offline Usage**: Working offline and sync behavior
- **Keyboard Workflow**: Power user workflows and tips
- **Invoice Creation**: Step-by-step invoice generation
- **EU Compliance**: VAT, reverse charge, and legal requirements

### 🔧 Configuration

#### New Environment Variables
```env
# Push Notifications (VAPID)
VAPID_PUBLIC_KEY=your-public-key
VAPID_PRIVATE_KEY=your-private-key
VAPID_SUBJECT=mailto:your-email@example.com
```

#### New NPM Scripts
```json
"icons:generate": "node scripts/generate-pwa-icons.js"
```

### 📦 Dependencies

#### Added
- `vite-plugin-pwa@^0.20.5` - PWA plugin for Vite
- `workbox-window@^7.1.0` - Service worker helper library
- `dexie@^4.0.11` - IndexedDB wrapper
- `uuid@^11.0.3` - UUID generation for offline entries
- `sharp@^0.33.5` - Image processing for icon generation

### 🐛 Bug Fixes
- Fixed ES module require() error in icon generation script
- Fixed missing computed import in notification preferences form
- Fixed dark mode styling inconsistencies in new components
- Fixed PWA manifest icon paths
- Fixed service worker cache versioning

### 🔒 Security
- VAPID key authentication for push notifications
- Secure storage of push subscriptions in database
- Encrypted offline data in IndexedDB
- Secure PDF download endpoints
- VAT number validation to prevent fraud

### ⚡ Performance
- Lazy loading of command palette component
- Optimized service worker caching strategies
- Efficient offline sync queue with batching
- Debounced search in command palette
- Memoized computed properties in invoice calculations

### ♿ Accessibility
- Keyboard navigation for all new features
- ARIA labels for screen readers
- High contrast ratios in dark mode
- Focus visible indicators
- Skip links for keyboard users

### 🌍 Internationalization
- Multi-language support for new components (EN, DE, NL, FR)
- Localized date and currency formatting
- Translation keys for all new strings

---

## [Phase 1] - 2025-10-30

### 🎨 Branding & Identity
- **Timeclocker Brand**: Renamed from SolidTime to Timeclocker
- **Logo & Colors**: Cyan-based color scheme (#0891b2)
- **EU-First Messaging**: Updated copy for EU freelancer focus
- **Legal Pages**: Updated privacy policy and terms for EU/GDPR

### 🌍 Multi-Language Support
- **Languages**: English, German (DE), Dutch (NL), French (FR)
- **Translation Files**: Complete translation coverage
- **Language Switcher**: User-facing language selection
- **Locale Detection**: Automatic language detection based on browser

### 🔒 GDPR Compliance
- **Data Export**: One-click export of all user data
- **Data Deletion**: Complete account and data removal
- **Privacy Controls**: Granular privacy settings
- **Cookie Consent**: EU-compliant cookie banner
- **Audit Logs**: Comprehensive activity logging

### 🏗️ Infrastructure
- **EU Data Centers**: Frankfurt and Amsterdam hosting
- **PostgreSQL 15**: Primary database
- **Laravel 12**: Backend framework upgrade
- **Vue 3.5**: Frontend framework
- **Tailwind CSS 3.4**: Utility-first CSS

---

## [Pre-Fork] - solidtime

This project is forked from [solidtime](https://github.com/solidtime-io/solidtime).

**Inherited Features:**
- Time tracking with projects, tasks, and tags
- Team collaboration and role-based access
- Reporting and analytics
- API with OAuth 2.0
- Import from Toggl, Clockify, Harvest
- Dark mode
- Responsive design

---

## Upgrade Guide

### From Phase 1 to Phase 2

**Database Migrations:**
```bash
php artisan migrate
```

**New Dependencies:**
```bash
composer install
npm install
```

**Environment Variables:**
Add to `.env`:
```env
VAPID_PUBLIC_KEY=
VAPID_PRIVATE_KEY=
VAPID_SUBJECT=mailto:your-email@example.com
```

Generate VAPID keys:
```bash
php artisan webpush:vapid
```

**Generate PWA Icons:**
```bash
npm run icons:generate
```

**Build Assets:**
```bash
npm run build
```

**Clear Cache:**
```bash
php artisan config:clear
php artisan cache:clear
php artisan view:clear
```

---

## Roadmap

### Phase 3 (Planned)
- 🔄 Recurring invoices
- 📧 Invoice email reminders
- 💳 Payment gateway integration (Stripe, PayPal)
- 📊 Advanced reporting and analytics
- 📅 Calendar integration (Google Calendar, Outlook)
- 🔗 Zapier integration
- 🤖 AI-powered time suggestions
- 📱 Mobile native apps (iOS, Android)

### Phase 4 (Future)
- 👥 Team timesheet approval workflows
- 🎯 Project budgets and tracking
- 📈 Profitability analysis
- 🔌 API webhooks
- 🌐 More languages (ES, IT, PL, PT)
- 🧮 Expense tracking
- 📑 Proposals and estimates

---

## Contributing

See [CONTRIBUTING.md](./CONTRIBUTING.md) for guidelines on submitting pull requests.

**Areas We Need Help:**
- 🌍 Translations (Spanish, Italian, Polish, Portuguese)
- 🧪 Testing (cross-browser, mobile, accessibility)
- 📚 Documentation improvements
- 🐛 Bug reports and fixes
- ✨ Feature suggestions and implementations

---

## Support

- 📧 Email: support@timeclocker.io
- 🐛 Issues: [GitHub Issues](https://github.com/yourorg/timeclocker/issues)
- 💬 Discussions: [GitHub Discussions](https://github.com/yourorg/timeclocker/discussions)
- 📖 Docs: [Documentation](./docs/)

---

## License

Timeclocker is open-source software licensed under the [GNU Affero General Public License v3.0 (AGPL v3)](./LICENSE.md).

---

**Last Updated**: November 5, 2025
