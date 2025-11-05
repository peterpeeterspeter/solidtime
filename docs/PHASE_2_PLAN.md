# Timeclocker - Phase 2 Implementation Plan

## Overview: Core Product & Enhanced UX (Days 8-21)

**Goal**: Transform Timeclocker from rebranded foundation into a fully functional, PWA-enabled time tracking platform with freelancer-focused features.

**Duration**: 14 days (Sprint 2-3 from original PRD)

**Priority**: Focus on high-impact features that differentiate Timeclocker from competitors: PWA offline support, instant timer UX, and freelancer invoicing.

---

## Phase 2 Breakdown

### **Sprint 2.1: PWA & Offline Support** (Days 8-10)

#### 2.1.1 PWA Manifest & Service Worker
**Priority**: HIGH | **Effort**: 2 days | **Risk**: Medium

**Tasks:**
- [ ] Install and configure Vite PWA plugin (`vite-plugin-pwa`)
- [ ] Create `public/manifest.json` with Timeclocker branding
  - App name, short name, description
  - Icons (192x192, 512x512) - design clock-themed icons
  - Theme colors (cyan-600, cyan-700)
  - Display mode: `standalone`
  - Start URL: `/dashboard`
  - Scope: `/`
- [ ] Generate service worker with Workbox
  - Cache strategy: NetworkFirst for API calls
  - Cache strategy: CacheFirst for static assets
  - Cache strategy: StaleWhileRevalidate for images
  - Offline fallback page
- [ ] Implement offline data sync queue
  - IndexedDB for offline time entries
  - Background sync for pending submissions
  - Conflict resolution strategy

**Deliverables:**
- Installable PWA on desktop and mobile
- Offline time tracking capability
- Service worker with caching strategies
- Install prompt component

**Files to Create/Modify:**
- `vite.config.js` - Add PWA plugin configuration
- `public/manifest.json` - PWA manifest
- `resources/js/service-worker.ts` - Custom SW logic
- `resources/js/Components/PWAInstallPrompt.vue` - Install banner
- `resources/js/utils/offlineSync.ts` - Offline sync logic

**Testing:**
- [ ] Test installation on Chrome, Firefox, Safari
- [ ] Test offline functionality (airplane mode)
- [ ] Verify background sync works
- [ ] Test cache invalidation

---

#### 2.1.2 Push Notifications
**Priority**: MEDIUM | **Effort**: 1 day | **Risk**: Low

**Tasks:**
- [ ] Set up Web Push API integration
- [ ] Create notification permission request flow
- [ ] Implement notification types:
  - Missing time entry reminders
  - Daily tracking summary
  - Incomplete invoice alerts
  - Team invitation notifications
- [ ] Add notification preferences to user settings
- [ ] Create notification scheduling service (Laravel)

**Deliverables:**
- Push notification system
- User notification preferences
- Scheduled notification jobs

**Files to Create/Modify:**
- `app/Services/NotificationService.php` - Push notification logic
- `app/Jobs/SendTimerReminders.php` - Scheduled notifications
- `resources/js/utils/pushNotifications.ts` - Client-side push API
- `resources/js/Pages/Profile/Partials/NotificationPreferencesForm.vue`

**Testing:**
- [ ] Test notification delivery on multiple browsers
- [ ] Verify notification preferences work
- [ ] Test notification click actions

---

### **Sprint 2.2: Quick-Start Timer & Keyboard UX** (Days 11-13)

#### 2.2.1 Enhanced Timer Component
**Priority**: HIGH | **Effort**: 2 days | **Risk**: Low

**Tasks:**
- [ ] Create `QuickStartTimer.vue` component
  - Large, prominent start/stop button
  - Display current running timer
  - One-click start (no project selection required)
  - Quick project switcher dropdown
  - Add description inline
  - Billable toggle
- [ ] Implement timer state management (Pinia store)
  - Current timer tracking
  - Timer persistence (localStorage + API)
  - Auto-save every 30 seconds
- [ ] Add timer to application header/sidebar
  - Always visible when running
  - Click to expand/collapse
  - Visual indicator (pulsing dot)
- [ ] Create default "General" project
  - Auto-created on first use
  - Catch-all for quick tracking
  - Can be reassigned later

**Deliverables:**
- Quick-start timer component
- Timer state management
- Persistent timer across page refreshes
- Default project auto-creation

**Files to Create/Modify:**
- `resources/js/Components/Timer/QuickStartTimer.vue`
- `resources/js/Components/Timer/RunningTimerBadge.vue`
- `resources/js/stores/timerStore.ts` - Pinia store
- `resources/js/Layouts/AppLayout.vue` - Add timer to header
- `app/Http/Controllers/Api/V1/TimerController.php` - Timer endpoints

**Testing:**
- [ ] Test timer start/stop accuracy
- [ ] Verify timer persists across refreshes
- [ ] Test concurrent timer prevention
- [ ] Verify auto-save functionality

---

#### 2.2.2 Keyboard Shortcuts & Command Menu
**Priority**: HIGH | **Effort**: 1.5 days | **Risk**: Medium

**Tasks:**
- [ ] Install command menu library (`@headlessui/vue` Command Palette or custom)
- [ ] Implement global keyboard shortcuts:
  - `Alt/Cmd + T` - Start/stop timer
  - `Alt/Cmd + N` - New time entry
  - `Alt/Cmd + K` - Open command menu
  - `Alt/Cmd + P` - Quick project switch
  - `Esc` - Close modals/menus
- [ ] Create Command Menu component
  - Search time entries
  - Quick navigation (Projects, Clients, Reports)
  - Quick actions (Start timer, Create invoice)
  - Recent items
  - Keyboard navigation (arrow keys, enter)
- [ ] Add keyboard shortcut help modal
  - `?` to open help
  - Display all available shortcuts
  - Categorized by function

**Deliverables:**
- Global keyboard shortcuts
- Command menu (Cmd+K style)
- Keyboard shortcut help

**Files to Create/Modify:**
- `resources/js/Components/CommandMenu.vue`
- `resources/js/Components/KeyboardShortcutHelp.vue`
- `resources/js/utils/keyboardShortcuts.ts` - Global shortcut handler
- `resources/js/composables/useKeyboard.ts` - Keyboard composable

**Testing:**
- [ ] Test all keyboard shortcuts across pages
- [ ] Verify command menu search works
- [ ] Test keyboard navigation
- [ ] Ensure no conflicts with browser shortcuts

---

### **Sprint 2.3: Onboarding Wizard** (Days 14-15)

#### 2.3.1 Multi-Step Onboarding Flow
**Priority**: HIGH | **Effort**: 2 days | **Risk**: Low

**Tasks:**
- [ ] Create onboarding wizard component (multi-step form)
  - Step 1: Welcome & language selection
  - Step 2: Timezone & format preferences
  - Step 3: Create first project (optional)
  - Step 4: Import data (Toggl, Clockify, Harvest) or skip
  - Step 5: Start quick tour or go to dashboard
- [ ] Add onboarding state tracking
  - Track completion in user settings
  - Allow skip/complete later
  - Don't show again after completion
- [ ] Create interactive tour overlay
  - Highlight key features
  - Click-through tutorial
  - Optional, can be dismissed
- [ ] Sample project/client creation
  - Pre-populate with example data
  - Clearly marked as sample
  - Easy to delete

**Deliverables:**
- Multi-step onboarding wizard
- Interactive product tour
- Sample data generation
- Onboarding completion tracking

**Files to Create/Modify:**
- `resources/js/Pages/Onboarding.vue` - Main onboarding page
- `resources/js/Components/Onboarding/WelcomeStep.vue`
- `resources/js/Components/Onboarding/PreferencesStep.vue`
- `resources/js/Components/Onboarding/ProjectStep.vue`
- `resources/js/Components/Onboarding/ImportStep.vue`
- `resources/js/Components/Onboarding/TourStep.vue`
- `resources/js/Components/ProductTour.vue` - Interactive tour overlay
- `app/Http/Controllers/OnboardingController.php`
- `routes/web.php` - Add onboarding route

**Testing:**
- [ ] Test each step of wizard
- [ ] Verify skip functionality
- [ ] Test data import in onboarding
- [ ] Ensure onboarding doesn't show after completion

---

### **Sprint 2.4: Invoice Generation (MVP)** (Days 16-18)

#### 2.4.1 Invoice Data Model & API
**Priority**: HIGH | **Effort**: 1.5 days | **Risk**: Medium

**Tasks:**
- [ ] Create `invoices` database table migration
  - Invoice number (auto-generated)
  - Organization, client references
  - Date range (from/to)
  - Line items (JSON or separate table)
  - Subtotal, tax, total
  - Currency
  - Status (draft, sent, paid, overdue)
  - PDF path (after generation)
  - Due date
  - Notes/terms
- [ ] Create `Invoice` model with relationships
  - BelongsTo Organization
  - BelongsTo Client
  - HasMany InvoiceItems (or JSON)
- [ ] Create invoice API endpoints
  - `POST /api/v1/organizations/{org}/invoices` - Create
  - `GET /api/v1/organizations/{org}/invoices` - List
  - `GET /api/v1/organizations/{org}/invoices/{id}` - Show
  - `PUT /api/v1/organizations/{org}/invoices/{id}` - Update
  - `DELETE /api/v1/organizations/{org}/invoices/{id}` - Delete
  - `POST /api/v1/organizations/{org}/invoices/{id}/generate-pdf` - PDF
  - `POST /api/v1/organizations/{org}/invoices/{id}/send` - Email
- [ ] Implement invoice number generation service
  - Sequential numbering
  - Customizable prefix
  - Year-based reset option

**Deliverables:**
- Invoice database schema
- Invoice model and relationships
- Invoice CRUD API endpoints
- Invoice number generation

**Files to Create/Modify:**
- `database/migrations/*_create_invoices_table.php`
- `app/Models/Invoice.php`
- `app/Http/Controllers/Api/V1/InvoiceController.php`
- `app/Http/Requests/V1/Invoice/StoreInvoiceRequest.php`
- `app/Http/Resources/V1/InvoiceResource.php`
- `app/Services/InvoiceService.php`
- `routes/api_v1.php` - Add invoice routes

**Testing:**
- [ ] Test invoice creation
- [ ] Verify invoice number generation
- [ ] Test invoice listing/filtering
- [ ] Verify relationships work correctly

---

#### 2.4.2 Invoice Generator UI
**Priority**: HIGH | **Effort**: 1.5 days | **Risk**: Low

**Tasks:**
- [ ] Create Invoice wizard component
  - Step 1: Select client and date range
  - Step 2: Review and select time entries
  - Step 3: Add line items manually (optional)
  - Step 4: Set payment terms, due date
  - Step 5: Preview and generate
- [ ] Create invoice preview modal
  - Show formatted invoice
  - Edit inline
  - Generate PDF button
- [ ] Create invoice list page
  - Filter by status, client, date
  - Search by invoice number
  - Quick actions (view, edit, delete, send)
  - Status badges
- [ ] Implement time entry → invoice conversion
  - Aggregate billable time by project/task
  - Calculate totals
  - Apply billable rates
  - Group by configurable criteria

**Deliverables:**
- Invoice creation wizard
- Invoice preview
- Invoice list/management page
- Time entry aggregation for invoicing

**Files to Create/Modify:**
- `resources/js/Pages/Invoices/Index.vue` - Invoice list
- `resources/js/Pages/Invoices/Create.vue` - Invoice wizard
- `resources/js/Components/Invoices/InvoiceWizard.vue`
- `resources/js/Components/Invoices/InvoicePreview.vue`
- `resources/js/Components/Invoices/TimeEntrySelector.vue`
- `resources/js/utils/useInvoices.ts` - Invoice composable
- `routes/web.php` - Add invoice routes

**Testing:**
- [ ] Test invoice creation flow
- [ ] Verify time entry selection
- [ ] Test calculations (subtotal, tax, total)
- [ ] Verify invoice preview accuracy

---

#### 2.4.3 PDF Invoice Generation
**Priority**: HIGH | **Effort**: 1 day | **Risk**: Medium

**Tasks:**
- [ ] Create invoice template (Blade view)
  - Professional layout
  - Company logo upload
  - Client details section
  - Line items table
  - Totals breakdown
  - Payment terms
  - Customizable footer
- [ ] Integrate with Gotenberg for PDF generation
  - HTML to PDF conversion
  - A4 page size
  - Professional styling
- [ ] Implement branding customization
  - Upload organization logo
  - Set brand colors
  - Customize header/footer
  - Add business details
- [ ] Add PDF download endpoint
  - Generate on-demand
  - Cache generated PDFs
  - Secure download links

**Deliverables:**
- Professional invoice PDF template
- PDF generation service
- Branding customization
- PDF download functionality

**Files to Create/Modify:**
- `resources/views/invoices/template.blade.php` - PDF template
- `app/Services/PdfService.php` - Gotenberg integration
- `app/Http/Controllers/InvoicePdfController.php`
- `resources/js/Pages/Settings/InvoiceBranding.vue` - Branding settings
- `database/migrations/*_add_invoice_branding_to_organizations.php`

**Testing:**
- [ ] Test PDF generation
- [ ] Verify PDF styling
- [ ] Test branding customization
- [ ] Verify PDF downloads work
- [ ] Test various invoice sizes (1 item vs 100 items)

---

### **Sprint 2.5: Polish & Integration** (Days 19-21)

#### 2.5.1 Navigation & User Flow
**Priority**: MEDIUM | **Effort**: 1 day | **Risk**: Low

**Tasks:**
- [ ] Add "Invoices" to main navigation
- [ ] Update sidebar with invoice icon/link
- [ ] Add quick invoice creation from time entries page
  - "Create Invoice" button on time list
  - Pre-select time entries
- [ ] Add invoice status to dashboard
  - Outstanding invoices widget
  - Total unpaid amount
  - Overdue invoice alerts
- [ ] Create invoice notifications
  - Email on invoice creation
  - Reminder for unpaid invoices
  - Payment received notification

**Deliverables:**
- Updated navigation
- Dashboard invoice widgets
- Invoice notifications

**Files to Modify:**
- `resources/js/Layouts/AppLayout.vue` - Add invoice nav
- `resources/js/Pages/Dashboard.vue` - Invoice widgets
- `resources/js/Pages/Time.vue` - Quick invoice button
- `app/Notifications/InvoiceCreated.php`
- `app/Notifications/InvoiceOverdue.php`

---

#### 2.5.2 Testing & Bug Fixes
**Priority**: HIGH | **Effort**: 1.5 days | **Risk**: Low

**Tasks:**
- [ ] End-to-end testing
  - PWA installation flow
  - Offline time tracking
  - Timer functionality
  - Keyboard shortcuts
  - Onboarding wizard
  - Invoice creation and PDF generation
- [ ] Cross-browser testing
  - Chrome, Firefox, Safari, Edge
  - Mobile browsers (iOS Safari, Chrome Mobile)
- [ ] Performance optimization
  - Lazy load components
  - Optimize service worker cache
  - Minimize bundle size
- [ ] Accessibility audit
  - Keyboard navigation
  - Screen reader compatibility
  - ARIA labels
  - Color contrast
- [ ] Bug fixes and refinements

**Deliverables:**
- Comprehensive test coverage
- Bug fix list
- Performance improvements
- Accessibility improvements

---

#### 2.5.3 Documentation Updates
**Priority**: MEDIUM | **Effort**: 0.5 days | **Risk**: Low

**Tasks:**
- [ ] Update README with Phase 2 features
- [ ] Create user documentation:
  - PWA installation guide
  - Keyboard shortcuts reference
  - Invoice creation guide
  - Onboarding walkthrough
- [ ] Update API documentation
  - Invoice endpoints
  - Timer endpoints
- [ ] Create changelog entry for Phase 2

**Deliverables:**
- Updated documentation
- User guides
- Changelog

**Files to Create/Modify:**
- `README.md` - Feature updates
- `docs/USER_GUIDE.md` - User documentation
- `docs/KEYBOARD_SHORTCUTS.md` - Shortcut reference
- `docs/INVOICING.md` - Invoice guide
- `CHANGELOG.md` - Version history

---

## Risk Assessment & Mitigation

### High Risk Items

| Risk | Impact | Likelihood | Mitigation |
|------|--------|------------|------------|
| Service worker caching breaks app updates | High | Medium | Implement cache versioning, add manual cache clear option |
| PWA installation not working on iOS Safari | High | Medium | Test early, provide fallback instructions, use Add to Home Screen |
| Offline sync conflicts | High | Low | Implement last-write-wins + user notification, allow manual conflict resolution |
| PDF generation performance issues | Medium | Medium | Cache generated PDFs, generate async with job queue, optimize template |
| Keyboard shortcuts conflict with browser | Medium | Medium | Use Alt/Cmd modifiers, provide customization option |

### Medium Risk Items

| Risk | Impact | Likelihood | Mitigation |
|------|--------|------------|------------|
| Onboarding too complex | Medium | Low | A/B test, allow skip, keep to max 5 steps |
| Invoice template doesn't render correctly | Medium | Low | Test with multiple browsers/PDF viewers, provide HTML preview |
| Push notifications blocked by user | Low | High | Don't require permissions, graceful degradation, explain benefits |

---

## Success Metrics

### Phase 2 Completion Criteria

**PWA & Offline:**
- [ ] PWA installable on Chrome, Firefox, Safari (desktop + mobile)
- [ ] Offline time tracking works for 24+ hours without sync
- [ ] Service worker caches properly with <10MB cache size
- [ ] Install prompt shows for 80%+ of eligible users

**Timer & UX:**
- [ ] Timer start latency <200ms
- [ ] Keyboard shortcuts work 100% of time
- [ ] Command menu search <50ms response time
- [ ] 90%+ of users complete onboarding (if started)

**Invoicing:**
- [ ] Invoice generation <3 seconds
- [ ] PDF generation <5 seconds
- [ ] Invoice creation flow <2 minutes average
- [ ] 0 calculation errors in invoice totals

**Performance:**
- [ ] Lighthouse PWA score: 90+
- [ ] First Contentful Paint: <1.5s
- [ ] Time to Interactive: <3.5s
- [ ] Bundle size increase: <200KB

**Quality:**
- [ ] 0 critical bugs
- [ ] 0 accessibility violations (WCAG AA)
- [ ] 95%+ code coverage on new features
- [ ] All E2E tests passing

---

## Implementation Order (Recommended)

### Week 1 (Days 8-14)
**Focus**: PWA & Timer Enhancements

1. **Day 8**: PWA manifest, service worker setup
2. **Day 9**: Offline sync, caching strategies
3. **Day 10**: Push notifications, install prompts
4. **Day 11**: Quick-start timer component
5. **Day 12**: Timer state management, persistence
6. **Day 13**: Keyboard shortcuts, command menu
7. **Day 14**: Onboarding wizard (Part 1)

### Week 2 (Days 15-21)
**Focus**: Invoicing & Polish

8. **Day 15**: Onboarding wizard (Part 2), tour
9. **Day 16**: Invoice data model, API endpoints
10. **Day 17**: Invoice generator UI, wizard
11. **Day 18**: PDF generation, branding
12. **Day 19**: Navigation updates, dashboard widgets
13. **Day 20**: Testing, bug fixes
14. **Day 21**: Documentation, final polish

---

## Dependencies & Prerequisites

### External Services
- ✅ Gotenberg service (already configured)
- ⏳ Web Push service (optional: Firebase Cloud Messaging, OneSignal, or self-hosted)
- ✅ Email service (already configured)

### Technical Requirements
- ✅ Laravel 12+ (current version)
- ✅ Vue 3.5+ (current version)
- ✅ Vite 6+ (current version)
- ⏳ `vite-plugin-pwa` (to be installed)
- ⏳ IndexedDB/Dexie.js for offline storage (to be installed)

### Design Assets Needed
- ⏳ PWA icons (192x192, 512x512) - clock-themed
- ⏳ Invoice template design (professional layout)
- ⏳ Onboarding illustrations (optional, can use icons)
- ⏳ Loading states / skeleton screens

---

## Post-Phase 2 Handoff

### What Users Will Have
1. **Installable PWA** - Works offline, feels like native app
2. **Lightning-Fast Timer** - One-click tracking, keyboard shortcuts
3. **Command Menu** - Power user navigation
4. **Smooth Onboarding** - 2-minute setup with guided tour
5. **Professional Invoicing** - Create, customize, send branded PDFs
6. **Smart Notifications** - Reminders for tracking and invoices

### What's NOT in Phase 2 (Deferred to Phase 3+)
- ❌ Team timesheet approval workflows
- ❌ Advanced reporting/analytics
- ❌ Calendar integration (Google Calendar sync)
- ❌ Passive/automatic time tracking
- ❌ API rate limiting enhancements
- ❌ Zapier integration
- ❌ Mobile native apps (PWA only)

---

## Approval Checklist

Before starting Phase 2 implementation:

- [ ] Review and approve implementation order
- [ ] Confirm PWA is priority over native apps
- [ ] Approve invoice template design direction
- [ ] Confirm Gotenberg service is available
- [ ] Review success metrics and adjust if needed
- [ ] Approve deferred features list
- [ ] Confirm 14-day timeline is acceptable
- [ ] Assign any design asset creation
- [ ] Review risk mitigation strategies

---

## Questions for Stakeholders

1. **PWA Priority**: Is PWA sufficient for mobile, or do we need native apps soon?
2. **Invoice Features**: Are basic invoices enough, or do we need recurring invoices in Phase 2?
3. **Onboarding**: Should we enforce onboarding or allow skip entirely?
4. **Notifications**: Web Push only, or also SMS/email reminders?
5. **Branding**: How much invoice customization is needed (colors, fonts, layout)?
6. **Testing**: Do we need QA approval before Phase 2 completion?

---

**Phase 2 Target**: Deliver a production-ready, PWA-enabled time tracking platform with freelancer invoicing by Day 21.

**Ready to proceed?** ✅

