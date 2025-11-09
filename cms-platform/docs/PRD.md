# Multi-Tenant Content Delivery Platform

## TL;DR

Een centraal platform voor het beheren, herschrijven en distribueren van content (casino, game, bonus, guides) naar meerdere WordPress-sites. Editors beheren en personaliseren artikelen per tenant; publiceren gebeurt automatisch, veilig en traceerbaar via Supabase Edge Functions, direct vanuit één UI.

---

## Goals

### Business Goals
- Reduce editorial workload by >50%.
- Realize uniform brand communication across 5+ WordPress sites.
- Achieve zero-touch publishing (<1 minute per site).
- Enforce full audit/compliance and rollback per post/publication.

### User Goals
- Single interface for multi-brand content management.
- Rewrite and review content without logging in to WordPress.
- Instantly see live status and errors per tenant/site.
- Tune tone-of-voice and affiliate info per brand.

### Non-Goals
- No front-end website rendering.
- No image/media management (yet).
- No support for non-WordPress CMS targets.

---

## User Stories

**Content Editor:**
- As an editor, I want to create and rewrite articles via LLM, then publish to several tenants with one action, so I can work faster and stay consistent.
- As an editor, I want to see the status of each post per site, so I can follow up on errors.

**Brand Manager:**
- As a manager, I want to set tone-of-voice, disclaimers, and CTA's per tenant, so each site matches its own brand identity.

**DevOps/Admin:**
- As an admin, I want to configure tenants (WP URL, token, settings), so onboarding a new site is fast.
- As an admin, I want to review publish logs and retry failed publications.

---

## Functional Requirements

**Content Management (High)**
- CRUD for posts (casino/game/bonus/guide) and categories.
- Per-tenant raw and rewritten text versions.

**Multi-Tenant Setup (High)**
- CRUD for tenants with per-brand params (WP-URL, API token, tone, prompt).
- RLS: users see only accessible tenants/sites.

**Rewrite Engine (High)**
- Edge Function for LLM rewrite (prompt/model per tenant).
- Audit/versioning for every rewrite.

**Affiliate Injection (Medium)**
- Replace {{AFFILIATE}} token in text with tenant-specific value.

**Publish to WP (High)**
- Edge Function publishes post via WP REST API using correct auth.
- Log all responses in publish_log.

**Logging & Dashboard (High)**
- Central publish_log and content_history.
- UI dashboard for status per tenant/post.

**Access Control (Medium)**
- Supabase Auth: role-based access.
- No unauthorized cross-tenant actions.

**Monitoring/Retry (Low, v2)**
- Cron/alerts for failed jobs (>X errors/hour).
- Retry failed publications from UI.

---

## User Experience

**Entry Point & First-Time User Experience**
- User logs in (via Supabase Auth or SSO) and selects tenant/site.
- Sees main dashboard with tiles per tenant and basic stats.

**Core Experience**
- Create new post: fill title, category, base (raw) text.
- (Optional) Import existing content.
- Click "Rewrite" to LLM-rewrite for selected tenant (shows preview).
- Adjust affiliate link and review SEO fields.
- "Publish to WP": function triggers, status/log shown inline.
- Status and errors live updatable in dashboard per tenant and post.

**Advanced Features & Edge Cases**
- Per-tenant preview before publish.
- Error feedback if publish fails (token, network, WP API).
- Duplicate "publish" replaces previous post, no reposting.

**UI/UX Highlights**
- Clean, fast search/filter by status, tenant, category.
- Distinction between "raw" and "rewritten" content.
- Modals for logs, audit view, and retry actions.
- Responsive design for desktop/tablet.

---

## Narrative

A content editor for a fast-growing casino marketing company faces chaos with many brands and sites — logging into each WordPress, tweaking content for every target, and losing oversight of what's live. With the Multi-Tenant Content Delivery Platform, all content starts in one interface. LLM-rewrites are instant per brand, affiliate links are always correct, and publishing to dozens of sites is a single click. The editor never needs direct WP access, the brand manager is certain each site gets the right USP and disclaimer, and operations finally have one point to trace, audit, or roll back any publication. The result: sharper content, fewer errors, maximum control.

---

## Success Metrics

### User-Centric Metrics
- >90% user adoption among editors and brand managers (by # logins and posts).
- <1% manual redo due to technical error.
- <3 minutes average time from draft to live per post.

### Business Metrics
- 50% editorial resource/time reduction.
- Consistent SEO/branding across all domains (measured via spot checks).

### Technical Metrics
- 99%+ uptime for API/publishing Edge Functions.
- <2% failed publishes (WP unreachable, auth error).

### Tracking Plan
- # posts created/rewritten/published (per tenant)
- # publish errors per time window
- Time-to-publish per post

---

## Technical Considerations

### Technical Needs
- Supabase v2 (row-level security enabled)
- Edge Functions (TypeScript/Deno)
- React + Tailwind UI for front-end
- LLM API integration modular (OpenAI now, Claude/Gemini later)

### Integration Points
- WP REST API (JWT/App Password auth)
- Slack webhook for error alerts (v2)

### Data Storage & Privacy
- WP tokens, service keys as Supabase secrets, never in tables
- All connections over HTTPS
- Content data per tenant, never cross-shared

### Scalability & Performance
- Designed for ±10 tenants, scaling to 1000's of posts/month
- Edge Functions stateless, scale horizontally

### Potential Challenges
- Permissies & authenticatie striktheid (geen datalek)
- WP API rate-limits bij bulk-publish
- Audit trail zonder privacygevoelige info

---

## Milestones & Sequencing

### Project Estimate
Small: 2–3 weeks voor MVP (fase 1–3 uit roadmap).

### Team Size & Composition
Small team — 1–2 devs (Supabase, Edge Functions, front-end), eventueel 1 parttime designer.

### Suggested Phases

**Fase 1: Database, Edge Function publishing, basis UI (1 week)**
- DB schema klaar, Edge Function publish_to_wp werkend, minimale CRUD UI

**Fase 2: Rewrite Function, tenant prompts, content versie-archief & preview (1 week)**
- LLM-integratie, audit logging, UI preview

**Fase 3: Affiliate injectie, dashboard/logs, retry, basis monitoring (0.5 week)**
- Logweergave, simpele monitoring, retry/log download

**Fase 4: Auth, RLS, Slack/alerts, refactor (0.5 week)**
- Access control en alerting

---

**Version**: 1.0
**Last Updated**: 2025-01-09
