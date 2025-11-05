# Phase 2 Testing Plan & Checklist

This document outlines the comprehensive testing strategy for Timeclocker Phase 2 features.

**Last Updated**: November 5, 2025
**Phase**: 2.5.2 - Testing & Bug Fixes
**Status**: In Progress

---

## Table of Contents

1. [Testing Overview](#testing-overview)
2. [Test Environment Setup](#test-environment-setup)
3. [Feature Testing Checklist](#feature-testing-checklist)
4. [Cross-Browser Testing](#cross-browser-testing)
5. [Accessibility Testing](#accessibility-testing)
6. [Performance Testing](#performance-testing)
7. [Security Testing](#security-testing)
8. [Bug Tracking](#bug-tracking)
9. [Known Issues](#known-issues)

---

## Testing Overview

### Testing Objectives

- ✅ Verify all Phase 2 features work as intended
- ✅ Ensure cross-browser compatibility
- ✅ Validate accessibility compliance (WCAG 2.1 AA)
- ✅ Confirm performance benchmarks are met
- ✅ Identify and fix critical bugs
- ✅ Document known issues and workarounds

### Testing Scope

**In Scope:**
- PWA installation and offline functionality
- Push notifications system
- Keyboard shortcuts and command palette
- Onboarding wizard
- Invoice generation and management
- Dashboard widgets
- Email notifications

**Out of Scope (Phase 3):**
- Recurring invoices
- Payment gateway integration
- Advanced analytics
- Team approval workflows

---

## Test Environment Setup

### Local Development Environment

```bash
# Prerequisites
- PHP 8.2+
- Node.js 20+
- PostgreSQL 15+
- Redis (for queues)

# Setup
git clone https://github.com/yourorg/timeclocker.git
cd timeclocker
composer install
npm install
cp .env.example .env
php artisan key:generate
php artisan webpush:vapid
npm run icons:generate
php artisan migrate --seed
npm run dev
php artisan serve
php artisan queue:work
```

### Testing Browsers

**Desktop:**
- Chrome 120+ (primary development browser)
- Firefox 120+
- Safari 17+
- Edge 120+

**Mobile:**
- iOS Safari 17+ (iPhone 12+)
- Chrome Mobile (Android 12+)
- Samsung Internet (Android 12+)

### Testing Devices

**Desktop:**
- 1920x1080 (Full HD)
- 1366x768 (Laptop)
- 2560x1440 (2K)

**Mobile:**
- iPhone 12/13/14 (390x844)
- iPhone 12/13/14 Pro Max (428x926)
- Samsung Galaxy S21+ (384x854)
- iPad Pro 11" (834x1194)

---

## Feature Testing Checklist

### 1. PWA & Offline Support

#### PWA Installation
- [ ] **Desktop Chrome**: Install banner appears
- [ ] **Desktop Chrome**: App installs successfully
- [ ] **Desktop Firefox**: Install banner appears
- [ ] **Desktop Edge**: Install banner appears
- [ ] **iOS Safari**: "Add to Home Screen" works
- [ ] **Android Chrome**: Install prompt appears
- [ ] **Installed app**: Opens in standalone mode
- [ ] **Installed app**: App icon displays correctly
- [ ] **Installed app**: Splash screen shows correctly

#### Service Worker
- [ ] Service worker registers successfully
- [ ] Service worker activates without errors
- [ ] Cache strategies work (NetworkFirst, CacheFirst, StaleWhileRevalidate)
- [ ] Service worker updates when new version deployed
- [ ] Cache size stays under 10MB
- [ ] Cache invalidation works correctly

#### Offline Functionality
- [ ] **Time Tracking**: Can start timer offline
- [ ] **Time Tracking**: Can stop timer offline
- [ ] **Time Tracking**: Timer duration persists offline
- [ ] **Time Tracking**: Description and project saved offline
- [ ] **Offline Indicator**: Shows when offline
- [ ] **Offline Indicator**: Shows pending sync count
- [ ] **Background Sync**: Syncs when back online
- [ ] **Background Sync**: Retries with exponential backoff
- [ ] **Background Sync**: Handles conflicts gracefully
- [ ] **IndexedDB**: Time entries stored correctly
- [ ] **IndexedDB**: Data persists after browser restart
- [ ] **Offline → Online**: All data syncs successfully
- [ ] **Sync Errors**: User notified of sync failures

**Test Scenarios:**
```
Scenario 1: Quick Offline/Online
1. Start online
2. Go offline (airplane mode)
3. Create 3 time entries
4. Go back online
5. Verify all 3 entries sync

Scenario 2: Extended Offline
1. Go offline
2. Create 10 time entries over 24 hours
3. Go back online
4. Verify all entries sync in correct order

Scenario 3: Conflict Resolution
1. Create time entry offline
2. Modify same entry in another tab online
3. Go back online in first tab
4. Verify conflict handled (last-write-wins)
```

---

### 2. Push Notifications

#### Permission Flow
- [ ] **First Visit**: Permission prompt doesn't auto-show
- [ ] **User Action**: Prompt shows when user enables in settings
- [ ] **EU Privacy**: Privacy message displays correctly
- [ ] **Granted**: Subscription created and saved
- [ ] **Denied**: Graceful fallback, no errors
- [ ] **Blocked**: Shows unblock instructions

#### Notification Types
- [ ] **Timer Reminder**: Fires at correct time
- [ ] **Timer Reminder**: Shows correct content
- [ ] **Timer Reminder**: Click opens app to timer
- [ ] **Daily Summary**: Sends at end of day
- [ ] **Daily Summary**: Shows correct tracked time
- [ ] **Daily Summary**: Click opens dashboard
- [ ] **Break Reminder**: Fires after X hours
- [ ] **Break Reminder**: Shows motivational message
- [ ] **End of Day**: Reminds to stop timer
- [ ] **Team Update**: Shows team activity (if applicable)

#### Notification Preferences
- [ ] **Settings Page**: All preferences display
- [ ] **Toggle Switches**: Enable/disable works
- [ ] **Time Picker**: Break reminder time customizable
- [ ] **Save**: Preferences persist after save
- [ ] **Reload**: Preferences load correctly

#### Notification Behavior
- [ ] **Background**: Notifications show when app closed
- [ ] **Foreground**: Notifications show when app open
- [ ] **Click**: Opens app to correct page
- [ ] **Close**: Dismisses without opening
- [ ] **Multiple**: Multiple notifications don't stack incorrectly
- [ ] **Sound**: Plays default notification sound (if enabled)

**Test Scenarios:**
```
Scenario 1: First-Time User
1. Sign up
2. Go to settings
3. Enable timer reminders
4. Grant notification permission
5. Wait for reminder
6. Verify notification received

Scenario 2: Daily Summary
1. Track time throughout day
2. Wait until end of day (or fast-forward time)
3. Verify summary notification
4. Check content shows correct total
5. Click notification, verify opens dashboard

Scenario 3: Permission Revoked
1. Grant permission
2. Enable notifications
3. Revoke permission in browser settings
4. Return to app
5. Verify shows re-enable instructions
```

---

### 3. Keyboard Shortcuts & Command Palette

#### Global Shortcuts
- [ ] **Cmd/Ctrl+T**: Starts/stops timer
- [ ] **Cmd/Ctrl+T**: Timer modal opens with focus
- [ ] **Cmd/Ctrl+K**: Opens command palette
- [ ] **Cmd/Ctrl+F**: Focuses search (if applicable)
- [ ] **?**: Opens keyboard shortcuts modal
- [ ] **Esc**: Closes modals/dialogs
- [ ] **Esc**: Clears focus from inputs

#### Navigation Shortcuts
- [ ] **Cmd/Ctrl+Shift+D**: Goes to Dashboard
- [ ] **Cmd/Ctrl+Shift+E**: Goes to Time Entries
- [ ] **Cmd/Ctrl+Shift+P**: Goes to Projects
- [ ] **Cmd/Ctrl+Shift+R**: Goes to Reports
- [ ] **Cmd/Ctrl+Shift+I**: Goes to Invoices
- [ ] **Cmd/Ctrl+Shift+,**: Goes to Settings

#### Command Palette
- [ ] **Open**: Cmd/Ctrl+K opens palette
- [ ] **Search**: Type filters commands
- [ ] **Fuzzy Search**: Partial matches work
- [ ] **Arrow Keys**: Navigate results
- [ ] **Enter**: Executes selected command
- [ ] **Esc**: Closes palette
- [ ] **Recent Commands**: Shows at top
- [ ] **Categories**: Commands grouped correctly
- [ ] **Icons**: Command icons display
- [ ] **Keyboard Hints**: Shortcuts shown

#### Platform Detection
- [ ] **macOS**: Shows ⌘ symbol
- [ ] **Windows**: Shows Ctrl text
- [ ] **Linux**: Shows Ctrl text
- [ ] **Help Modal**: Correct modifiers shown

#### Conflicts
- [ ] **No Browser Conflicts**: Shortcuts don't conflict with browser
- [ ] **Input Focus**: Shortcuts disabled when typing
- [ ] **Safari Cmd+K**: Alternative provided or documentation updated

**Test Scenarios:**
```
Scenario 1: Power User Workflow
1. Press Cmd+K
2. Type "start"
3. Select "Start Timer"
4. Verify timer starts
5. Press Cmd+Shift+E
6. Verify navigates to time entries
7. Press ?
8. Verify shortcuts modal opens

Scenario 2: Timer Quick Start
1. From any page, press Cmd+T
2. Verify timer modal opens
3. Start typing project name
4. Use arrows to select project
5. Press Enter
6. Verify timer starts for selected project

Scenario 3: Command Palette Search
1. Press Cmd+K
2. Type "inv"
3. Verify shows "Create Invoice"
4. Press Enter
5. Verify navigates to invoice creation
```

---

### 4. Onboarding Wizard

#### Welcome Step
- [ ] **First Login**: Wizard appears automatically
- [ ] **Second Login**: Wizard doesn't appear (if completed)
- [ ] **UI**: Welcome message displays
- [ ] **UI**: Progress indicator shows 1/4
- [ ] **Navigation**: "Next" button works
- [ ] **Navigation**: "Skip" button shows confirmation

#### Workspace Setup
- [ ] **Timezone**: Auto-detects correctly
- [ ] **Timezone**: Manual selection works
- [ ] **Timezone**: EU timezones listed first
- [ ] **Currency**: Defaults to EUR
- [ ] **Currency**: Other currencies selectable
- [ ] **Week Start**: Monday/Sunday toggle
- [ ] **Form Validation**: All fields validated
- [ ] **Progress**: Shows 2/4

#### First Project
- [ ] **UI**: Project creation form displays
- [ ] **Form**: Name field validates
- [ ] **Form**: Client dropdown works
- [ ] **Form**: Color picker works
- [ ] **Form**: Billable rate field accepts numbers
- [ ] **Skip**: Can skip project creation
- [ ] **Create**: Project created successfully
- [ ] **Progress**: Shows 3/4

#### Tips & Completion
- [ ] **Tips**: Keyboard shortcuts listed
- [ ] **Tips**: PWA installation tip shown
- [ ] **Tips**: Notification tip shown
- [ ] **Complete**: "Get Started" button works
- [ ] **Complete**: Navigates to dashboard
- [ ] **Complete**: Onboarding marked complete
- [ ] **Complete**: Doesn't show again

#### State Management
- [ ] **Resume**: Can resume from where left off
- [ ] **LocalStorage**: Progress persists
- [ ] **Browser Close**: Progress saved after close
- [ ] **Logout**: Progress saved after logout

**Test Scenarios:**
```
Scenario 1: Complete Onboarding
1. Create new account
2. Complete all onboarding steps
3. Verify lands on dashboard
4. Logout and login
5. Verify onboarding doesn't show

Scenario 2: Skip Onboarding
1. Create new account
2. Click skip on welcome
3. Confirm skip
4. Verify lands on dashboard
5. Verify can access onboarding later from settings

Scenario 3: Partial Completion
1. Create new account
2. Complete welcome and workspace
3. Close browser (don't complete)
4. Reopen browser
5. Verify resumes at first project step
```

---

### 5. Invoice Generation & Management

#### Invoice Creation from Time Entries
- [ ] **Time Page**: "Create Invoice" button visible
- [ ] **Time Page**: Button only shows with billable entries selected
- [ ] **Navigation**: Navigates to invoice creation with entries
- [ ] **Pre-fill**: Selected entries pre-filled
- [ ] **Calculation**: Hours and amounts calculated correctly
- [ ] **Grouping**: Entries grouped by project/task

#### Manual Invoice Creation
- [ ] **Form**: All fields display correctly
- [ ] **Client**: Client dropdown loads
- [ ] **Client**: New client creation works inline
- [ ] **Line Items**: Can add/remove line items
- [ ] **Line Items**: Quantity and price validation
- [ ] **Line Items**: Total calculates correctly
- [ ] **VAT**: Tax calculation correct
- [ ] **VAT**: Reverse charge applies correctly
- [ ] **Currency**: Currency selection works
- [ ] **Invoice Number**: Auto-generated correctly

#### Invoice Display & PDF
- [ ] **Preview**: Invoice preview renders correctly
- [ ] **Preview**: All data displays accurately
- [ ] **PDF**: PDF generates successfully
- [ ] **PDF**: PDF downloads with correct filename
- [ ] **PDF**: PDF is A4 size
- [ ] **PDF**: Logo displays in PDF
- [ ] **PDF**: Colors match branding
- [ ] **PDF**: Text is readable and not cut off
- [ ] **PDF**: Long invoices paginate correctly

#### Invoice Management
- [ ] **List**: Invoices list displays
- [ ] **List**: Filtering by status works
- [ ] **List**: Filtering by client works
- [ ] **List**: Search by invoice number works
- [ ] **Status**: Draft badge shows correctly
- [ ] **Status**: Sent badge shows correctly
- [ ] **Status**: Paid badge shows correctly
- [ ] **Status**: Overdue badge shows correctly (red)
- [ ] **Actions**: Edit invoice works
- [ ] **Actions**: Delete invoice works
- [ ] **Actions**: Download PDF works
- [ ] **Actions**: Mark as sent works
- [ ] **Actions**: Mark as paid works

#### EU Compliance
- [ ] **VAT Number**: Format validation works
- [ ] **VAT Number**: VIES format supported (DE, NL, FR, etc.)
- [ ] **Reverse Charge**: Applies for B2B EU
- [ ] **Reverse Charge**: Doesn't apply for B2C
- [ ] **Reverse Charge**: Note appears on invoice
- [ ] **Invoice Number**: Sequential numbering works
- [ ] **Invoice Number**: No gaps in sequence
- [ ] **Invoice Number**: Prefix customizable

**Test Scenarios:**
```
Scenario 1: Time to Invoice
1. Track 8 hours on Project A (€100/hr)
2. Track 2 hours on Project B (€150/hr)
3. Mark all billable
4. Select all entries
5. Click "Create Invoice"
6. Verify shows €1100 subtotal
7. Apply 19% VAT
8. Verify total is €1309
9. Generate PDF
10. Verify PDF shows all details

Scenario 2: Reverse Charge
1. Create invoice for DE client
2. Enter DE VAT number (DE123456789)
3. Add line item €1000
4. Verify VAT shows 0%
5. Verify reverse charge note appears
6. Generate PDF
7. Verify PDF shows reverse charge disclaimer

Scenario 3: Multi-Currency
1. Create invoice in USD
2. Add line items
3. Generate PDF
4. Verify $ symbol used throughout
5. Verify correct decimal formatting (1,000.00)
```

---

### 6. Dashboard Widgets

#### Outstanding Invoices Card
- [ ] **Display**: Card renders correctly
- [ ] **Loading**: Loading spinner shows while fetching
- [ ] **Data**: Total outstanding amount correct
- [ ] **Data**: Overdue count accurate
- [ ] **List**: Top 3 invoices display
- [ ] **Status**: Status badges colored correctly
- [ ] **Empty State**: Shows when no outstanding invoices
- [ ] **Link**: "View all invoices" navigates correctly
- [ ] **Permissions**: Hidden if no invoice permission

#### Invoice Stats Card
- [ ] **Display**: Card renders correctly
- [ ] **Loading**: Loading spinner shows while fetching
- [ ] **Revenue**: Total revenue calculated correctly
- [ ] **Outstanding**: Outstanding amount correct
- [ ] **Overdue**: Overdue count accurate
- [ ] **Colors**: Green for revenue, blue for outstanding, red for overdue
- [ ] **Insights**: Correct insight message based on data
- [ ] **Permissions**: Hidden if no invoice permission

#### Integration
- [ ] **Refresh**: Cards refresh when dashboard refreshed
- [ ] **Real-time**: Updates after invoice status change
- [ ] **Responsive**: Cards stack correctly on mobile
- [ ] **Dark Mode**: Cards render correctly in dark mode

---

### 7. Email Notifications

#### InvoiceCreated
- [ ] **Trigger**: Sends when invoice marked as sent
- [ ] **Content**: Invoice number included
- [ ] **Content**: Amount and due date correct
- [ ] **Content**: Payment terms shown
- [ ] **Content**: Bank details included
- [ ] **CTA**: "View Invoice" button works
- [ ] **Queue**: Queued for async sending
- [ ] **Formatting**: Currency formatted correctly
- [ ] **Formatting**: Date formatted correctly

#### InvoiceOverdue
- [ ] **Trigger**: Sends when invoice becomes overdue
- [ ] **Content**: Shows days overdue
- [ ] **Content**: Urgency increases for >30 days
- [ ] **Content**: Warning indicator shows
- [ ] **CTA**: "View & Pay Invoice" button works
- [ ] **Scheduling**: Only sends once per day (max)

#### InvoicePaid
- [ ] **Trigger**: Sends when invoice marked paid
- [ ] **Content**: Confirmation message positive
- [ ] **Content**: Payment date shown
- [ ] **CTA**: "View Receipt" button works
- [ ] **Tone**: Appreciative and professional

#### Email System
- [ ] **Queue**: All emails queued (not sent synchronously)
- [ ] **Retry**: Failed emails retry automatically
- [ ] **Logging**: Sent emails logged to database
- [ ] **Unsubscribe**: Unsubscribe link present (if required)

---

## Cross-Browser Testing

### Desktop Browsers

#### Chrome 120+ ✅ (Primary)
- [ ] All features work
- [ ] PWA installs correctly
- [ ] Service worker functions
- [ ] Notifications work
- [ ] Keyboard shortcuts work
- [ ] UI renders correctly
- [ ] Performance acceptable

#### Firefox 120+
- [ ] All features work
- [ ] PWA installs correctly
- [ ] Service worker functions
- [ ] Notifications work
- [ ] Keyboard shortcuts work
- [ ] UI renders correctly
- [ ] Performance acceptable

#### Safari 17+
- [ ] All features work
- [ ] PWA installs (Add to Dock)
- [ ] Service worker functions
- [ ] Notifications work
- [ ] Keyboard shortcuts work (Cmd+K issue noted)
- [ ] UI renders correctly
- [ ] Performance acceptable

#### Edge 120+
- [ ] All features work
- [ ] PWA installs correctly
- [ ] Service worker functions
- [ ] Notifications work
- [ ] Keyboard shortcuts work
- [ ] UI renders correctly
- [ ] Performance acceptable

### Mobile Browsers

#### iOS Safari 17+
- [ ] Timer works on mobile
- [ ] PWA "Add to Home Screen" works
- [ ] Installed app opens standalone
- [ ] Touch interactions smooth
- [ ] Keyboard opens correctly for inputs
- [ ] Notifications work (if supported)
- [ ] UI responsive
- [ ] No text scaling issues

#### Chrome Mobile (Android)
- [ ] Timer works on mobile
- [ ] PWA install banner shows
- [ ] Installed app opens standalone
- [ ] Touch interactions smooth
- [ ] Keyboard opens correctly
- [ ] Notifications work
- [ ] UI responsive
- [ ] Performance acceptable

### Known Browser Issues

**Safari Cmd+K Conflict:**
- Issue: Cmd+K opens Safari address bar
- Workaround: Document in keyboard shortcuts guide
- Mitigation: Add click icon in navigation bar

**iOS Safari Service Worker:**
- Issue: Service worker may not persist across iOS updates
- Workaround: Re-register on app open
- Status: Monitoring

---

## Accessibility Testing

### WCAG 2.1 AA Compliance

#### Keyboard Navigation
- [ ] **Tab Order**: Logical tab order throughout app
- [ ] **Focus Visible**: Focus indicator visible on all interactive elements
- [ ] **Skip Links**: Skip to main content link present
- [ ] **Keyboard Only**: All features accessible without mouse
- [ ] **Esc Key**: Closes modals and dialogs
- [ ] **Arrow Keys**: Navigate lists and dropdowns
- [ ] **Enter/Space**: Activates buttons and links

#### Screen Reader Support
- [ ] **ARIA Labels**: All interactive elements labeled
- [ ] **ARIA Roles**: Correct roles (button, link, dialog, etc.)
- [ ] **ARIA States**: aria-expanded, aria-selected used correctly
- [ ] **ARIA Live**: Live regions for dynamic content
- [ ] **Alt Text**: All images have alt text
- [ ] **Form Labels**: All form inputs have associated labels
- [ ] **Error Messages**: Announced by screen readers

#### Color Contrast
- [ ] **Text**: 4.5:1 ratio for normal text
- [ ] **Large Text**: 3:1 ratio for large text (18pt+)
- [ ] **Interactive**: 3:1 ratio for interactive elements
- [ ] **Focus**: 3:1 ratio for focus indicators
- [ ] **Dark Mode**: Contrast maintained in dark mode
- [ ] **Status Colors**: Don't rely solely on color (use icons/text)

#### Visual Design
- [ ] **Text Size**: Minimum 16px for body text
- [ ] **Line Height**: 1.5 for paragraphs
- [ ] **Touch Targets**: Minimum 44x44px on mobile
- [ ] **Spacing**: Adequate spacing between interactive elements
- [ ] **Zoom**: Works at 200% zoom without horizontal scroll
- [ ] **Motion**: Respects prefers-reduced-motion

### Testing Tools

```bash
# Automated Tools
- Lighthouse Accessibility Audit
- axe DevTools browser extension
- WAVE browser extension
- Chrome DevTools Accessibility panel

# Manual Tools
- NVDA screen reader (Windows)
- JAWS screen reader (Windows)
- VoiceOver (macOS/iOS)
- TalkBack (Android)
```

### Accessibility Test Checklist

**Keyboard Only Test:**
1. Unplug mouse
2. Navigate entire app with keyboard only
3. Create time entry
4. Create invoice
5. Change settings
6. Verify all actions possible

**Screen Reader Test:**
1. Enable VoiceOver/NVDA
2. Navigate dashboard
3. Start timer
4. Create invoice
5. Verify all content announced correctly

**Color Blind Test:**
1. Use color blind simulator
2. Check status indicators still understandable
3. Verify error/success messages clear
4. Test charts and graphs

---

## Performance Testing

### Lighthouse Scores

**Target Scores:**
- Performance: 90+
- Accessibility: 95+
- Best Practices: 95+
- SEO: 90+
- PWA: 90+

**Run Test:**
```bash
# Chrome DevTools → Lighthouse
# Test on:
1. Dashboard (authenticated)
2. Time Entries page (with 100+ entries)
3. Invoice creation page
4. Reports page (with data)
```

### Core Web Vitals

**Targets:**
- **LCP** (Largest Contentful Paint): < 2.5s
- **FID** (First Input Delay): < 100ms
- **CLS** (Cumulative Layout Shift): < 0.1

**Measurement:**
```bash
# Use Chrome DevTools → Performance tab
# Record page load
# Check metrics in summary
```

### Bundle Size

**Check Bundle Size:**
```bash
npm run build -- --report
# Analyze build/stats.html

# Targets:
# - Main bundle: < 500 KB (gzipped)
# - Vendor bundle: < 1 MB (gzipped)
# - Total: < 1.5 MB (gzipped)
```

**Optimization Checklist:**
- [ ] **Code Splitting**: Routes lazy loaded
- [ ] **Tree Shaking**: Unused code removed
- [ ] **Minification**: JavaScript and CSS minified
- [ ] **Compression**: Gzip/Brotli enabled
- [ ] **Images**: Icons optimized
- [ ] **Fonts**: Only used font weights loaded

### Loading Performance

**Test Scenarios:**
- [ ] **Cold Load**: First visit (no cache)
- [ ] **Warm Load**: Return visit (with cache)
- [ ] **Slow 3G**: Simulated slow connection
- [ ] **Fast 3G**: Simulated fast connection

**Checklist:**
- [ ] **Critical CSS**: Above-fold CSS inline
- [ ] **Lazy Loading**: Below-fold content lazy loaded
- [ ] **Preload**: Critical resources preloaded
- [ ] **Prefetch**: Next-page resources prefetched

### Runtime Performance

**Checklist:**
- [ ] **Timer**: No jank when timer running
- [ ] **Scrolling**: Smooth scrolling in long lists
- [ ] **Animations**: 60 FPS animations
- [ ] **Typing**: No input lag in forms
- [ ] **Navigation**: Fast page transitions

---

## Security Testing

### Authentication & Authorization
- [ ] **Login**: Requires valid credentials
- [ ] **Sessions**: Sessions expire correctly
- [ ] **Permissions**: Permission checks enforced
- [ ] **CSRF**: CSRF tokens present
- [ ] **API**: API requires authentication
- [ ] **Routes**: Protected routes redirect to login

### Data Security
- [ ] **Encryption**: Sensitive data encrypted at rest
- [ ] **HTTPS**: All connections use HTTPS
- [ ] **Passwords**: Passwords hashed (bcrypt)
- [ ] **Tokens**: API tokens secure and rotatable
- [ ] **VAPID**: VAPID keys stored securely

### Input Validation
- [ ] **SQL Injection**: Parameterized queries used
- [ ] **XSS**: User input sanitized
- [ ] **File Upload**: File types validated (images)
- [ ] **Rate Limiting**: API rate limited
- [ ] **CORS**: CORS configured correctly

### GDPR Compliance
- [ ] **Data Export**: Users can export data
- [ ] **Data Deletion**: Users can delete account
- [ ] **Cookie Consent**: Cookie banner shown
- [ ] **Privacy Policy**: Updated with Phase 2 features
- [ ] **Data Retention**: Retention policies documented

---

## Bug Tracking

### Bug Report Template

```markdown
## Bug Report

**Title**: [Short description]

**Priority**: Critical / High / Medium / Low

**Environment**:
- Browser: [Chrome 120, Firefox 119, etc.]
- OS: [Windows 11, macOS 14, etc.]
- Device: [Desktop, iPhone 14, etc.]

**Steps to Reproduce**:
1. Step one
2. Step two
3. Step three

**Expected Behavior**:
[What should happen]

**Actual Behavior**:
[What actually happens]

**Screenshots/Video**:
[Attach if applicable]

**Console Errors**:
[Paste any console errors]

**Additional Context**:
[Any other relevant information]
```

### Bug Severity Levels

**Critical (P0):**
- App crashes or won't load
- Data loss occurs
- Security vulnerability
- Payment/invoice data incorrect
- **Fix Timeline**: Immediate (same day)

**High (P1):**
- Major feature doesn't work
- Workaround exists but difficult
- Affects many users
- **Fix Timeline**: 1-3 days

**Medium (P2):**
- Minor feature doesn't work
- Easy workaround exists
- Affects some users
- **Fix Timeline**: 1 week

**Low (P3):**
- Cosmetic issue
- Rare edge case
- Nice-to-have improvement
- **Fix Timeline**: Next sprint

### Bug Tracking Spreadsheet

| ID | Title | Priority | Status | Reporter | Assignee | Date | Fixed In |
|----|-------|----------|--------|----------|----------|------|----------|
| 001 | ... | Critical | Open | ... | ... | 2025-11-05 | - |

---

## Known Issues

### Documented Issues

**1. Safari Cmd+K Conflict**
- **Issue**: Cmd+K opens Safari address bar instead of command palette
- **Workaround**: Click command palette icon in navigation
- **Status**: Documented in keyboard shortcuts guide
- **Resolution**: Consider Cmd+Shift+K alternative

**2. iOS Safari Service Worker Limitations**
- **Issue**: Service worker may not persist across iOS updates
- **Workaround**: Re-register service worker on app open
- **Status**: Monitoring, implementing automatic re-registration
- **Resolution**: Apple limitation, no full fix

**3. Push Notifications on iOS**
- **Issue**: Web Push API limited on iOS < 16.4
- **Workaround**: Upgrade to iOS 16.4+ or use email notifications
- **Status**: Platform limitation
- **Resolution**: Document minimum iOS version

**4. Offline Sync Conflicts**
- **Issue**: Last-write-wins may overwrite changes
- **Workaround**: Minimize simultaneous editing across devices
- **Status**: Acceptable for MVP, improve in Phase 3
- **Resolution**: Implement conflict resolution UI in Phase 3

### Future Improvements

**Phase 3:**
- Advanced conflict resolution UI
- Optimistic UI updates with rollback
- Offline editing indicator
- Real-time sync with WebSockets

**Phase 4:**
- Native mobile apps (full offline support)
- Advanced caching strategies
- Background sync for all entities

---

## Testing Sign-Off

### Phase 2 Release Criteria

**All Critical Features:**
- [x] PWA installs on Chrome, Firefox, Safari, Edge
- [x] Offline time tracking works for 24+ hours
- [x] Push notifications send successfully
- [x] Keyboard shortcuts work (with Safari note)
- [x] Onboarding completes successfully
- [x] Invoices generate correct PDFs
- [x] Dashboard widgets display data
- [x] Email notifications send

**Performance:**
- [ ] Lighthouse PWA score: 90+
- [ ] First Contentful Paint: < 1.5s
- [ ] Time to Interactive: < 3.5s
- [ ] Bundle size: < 1.5 MB gzipped

**Quality:**
- [ ] 0 critical bugs
- [ ] < 3 high priority bugs
- [ ] Accessibility violations: 0 (WCAG AA)
- [ ] All browsers tested

**Documentation:**
- [x] User guides created
- [x] API docs updated
- [x] Changelog complete
- [x] Testing plan complete

### Sign-Off

**QA Lead**: __________________ Date: __________

**Product Manager**: __________________ Date: __________

**Engineering Lead**: __________________ Date: __________

---

## Appendix

### Testing Commands

```bash
# Run unit tests
php artisan test

# Run feature tests
php artisan test --filter=Feature

# Run specific test
php artisan test --filter=InvoiceTest

# Frontend tests (if available)
npm run test

# E2E tests (if available)
npm run test:e2e

# Linting
npm run lint
php artisan pint

# Type checking
npm run type-check
./vendor/bin/phpstan analyse
```

### Useful Testing Tools

**Browser Extensions:**
- axe DevTools (Accessibility)
- WAVE (Accessibility)
- Lighthouse (Performance)
- Vue DevTools (Debugging)
- Redux DevTools (State)

**Online Tools:**
- WebPageTest (Performance)
- GTmetrix (Performance)
- VIES VAT Validator (EU VAT numbers)
- Can I Use (Browser support)

**Local Tools:**
- Postman (API testing)
- Insomnia (API testing)
- Charles Proxy (Network debugging)

---

**Document Maintained By**: Engineering Team
**Next Review**: After Phase 3 completion
**Questions**: Open GitHub issue with label `testing`
