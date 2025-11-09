# Go-Live Readiness Checklist – Supabase Rewrite Platform

Deze checklist helpt je te controleren of het platform klaar is voor productie. Werk van boven naar beneden en vink items af zodra ze gevalideerd zijn.

---

## 1. Security & Authenticatie

### Edge Functions Security
- [ ] Alle Edge Functions alleen intern aanroepbaar via `x-internal-token` in headers
- [ ] `INTERNAL_FUNCTION_TOKEN` is een sterke, willekeurige string (minimaal 32 tekens)
- [ ] Token is ingesteld als Supabase secret: `supabase secrets set INTERNAL_FUNCTION_TOKEN=...`
- [ ] Token is ook geconfigureerd in frontend `.env` als `VITE_INTERNAL_FUNCTION_TOKEN`
- [ ] Test dat requests zonder token worden geweigerd (401 Unauthorized)

### Environment Variables & Secrets
- [ ] Alle `.env` variabelen niet in repo (check `.gitignore`)
- [ ] Service role key alleen gebruikt in Edge Functions, nooit in frontend
- [ ] WordPress tokens opgeslagen in `tenants` tabel, niet hardcoded
- [ ] LLM API keys geconfigureerd als Supabase secrets:
  - [ ] `OPENAI_API_KEY` (indien gebruikt)
  - [ ] `ANTHROPIC_API_KEY` (indien gebruikt)
  - [ ] `GOOGLE_API_KEY` (indien gebruikt)
- [ ] Geen credentials zichtbaar in logs of error messages

### Database Security
- [ ] Row Level Security (RLS) actief op alle tabellen
- [ ] RLS policies getest voor alle user roles
- [ ] Service role kan alle data benaderen (voor Edge Functions)
- [ ] Normale users kunnen alleen eigen tenant-data zien
- [ ] Sensitive data (wp_token) niet leesbaar via frontend queries

### WordPress Integration
- [ ] WordPress sites hebben HTTPS (niet HTTP)
- [ ] REST API enabled op alle WordPress sites
- [ ] WP tokens hebben juiste permissions (publish_posts, edit_posts)
- [ ] Test WordPress authentication met curl/Postman
- [ ] WP sites accepteren requests van Supabase IP's (firewall check)

---

## 2. Functionele Validatie

### Rewrite Functionality
- [ ] Handmatige test: POST naar `/rewrite_content` geeft verwachte response
- [ ] Rewrite produceert correcte output in `posts.rewritten_text`
- [ ] Content history entry wordt aangemaakt bij elke rewrite
- [ ] Model name wordt correct opgeslagen in `content_history.rewrite_model`
- [ ] Force rewrite werkt (kan bestaande rewrite overschrijven)
- [ ] Error handling: lege raw_text → 400 error
- [ ] Error handling: inactieve tenant → 400 error
- [ ] Error handling: verkeerde API key → 500 error + log entry

### Tenant Configuration
- [ ] Tenant CRUD operations werken (create, read, update, delete)
- [ ] Model selectie werkt per tenant (gpt-4, claude-3, gemini, etc.)
- [ ] Custom prompt per tenant wordt correct toegepast
- [ ] Brand tone field wordt gebruikt in rewrites
- [ ] Active/inactive toggle werkt correct
- [ ] Inactieve tenants kunnen niet publishen of rewriten

### Publishing Workflow
- [ ] Publish naar WordPress succesvol (check WP admin)
- [ ] Post verschijnt met correcte title, content, slug
- [ ] SEO meta fields worden correct doorgegeven
- [ ] Affiliate links zijn correct ingevoegd
- [ ] Publish log entry aangemaakt met response code en message
- [ ] Post status wordt 'published' na succesvolle publicatie
- [ ] Duplicate publish niet mogelijk zonder `update_existing` flag

### Affiliate Link Injection
- [ ] `{{AFFILIATE}}` tokens worden correct vervangen
- [ ] Verschillende token formaten werken ({{AFFILIATE}}, {AFFILIATE}, etc.)
- [ ] Custom affiliate link override werkt
- [ ] Dry run mode toont preview zonder wijzigingen
- [ ] Tokens count klopt in response

### Version Control
- [ ] Elke rewrite legt nieuwe versie vast in `content_history`
- [ ] Version numbers zijn sequentieel (1, 2, 3, ...)
- [ ] Oude versies blijven bewaard en toegankelijk
- [ ] Timestamps zijn correct (UTC)
- [ ] Model informatie per versie beschikbaar

### Error Logging
- [ ] Fouten worden naar `publish_log` geschreven
- [ ] Log bevat tenant_id, post_id, error message, timestamp
- [ ] Response codes zijn correct (200, 400, 404, 500)
- [ ] Logs zijn uitleesbaar vanuit UI
- [ ] Logs kunnen gefilterd worden per tenant/post/datum

---

## 3. Performance & Correctheid

### Load Testing
- [ ] Multiple simultane rewrite requests werken zonder conflict
- [ ] Multiple tenants kunnen tegelijk publishen
- [ ] Database queries zijn geoptimaliseerd (check query plans)
- [ ] Indexes aanwezig op foreign keys en frequent queried columns
- [ ] Edge Function response times acceptabel (<10s voor rewrite, <5s voor publish)

### Edge Cases
- [ ] Lege input → duidelijke error response
- [ ] Zeer lange content (>10k woorden) → werkt of geeft duidelijke limiet
- [ ] Special characters in content → correct escaped
- [ ] Multiple affiliate tokens in één post → allemaal vervangen
- [ ] Post zonder tenant_id → rejected
- [ ] Ontbrekende API key → duidelijke error (niet silent fail)
- [ ] WordPress offline/unreachable → timeout + error log
- [ ] Malformed WordPress response → geparsed en gelogd

### Data Integrity
- [ ] Foreign key constraints werken (kan geen post maken zonder tenant)
- [ ] Cascade delete werkt (tenant delete → posts delete)
- [ ] Timestamps automatisch bijgewerkt via triggers
- [ ] Unique constraints werken (tenant slug, post slug per tenant)
- [ ] Enum types validated (post_status moet draft/review/published zijn)

---

## 4. Operationeel Beheer

### Documentation
- [ ] README.md volledig en up-to-date
- [ ] PRD.md beschrijft alle features en user stories
- [ ] TECH_SPEC.md bevat complete database schema en API specs
- [ ] Deployment instructies aanwezig
- [ ] Environment setup gedocumenteerd
- [ ] Troubleshooting guide aanwezig

### Deployment
- [ ] Database migrations gedraaid in productie
- [ ] Edge Functions gedeployed naar productie
- [ ] Secrets ingesteld in productie environment
- [ ] Frontend gebuild en gedeployed
- [ ] DNS/domain correct geconfigureerd (indien van toepassing)
- [ ] SSL certificates actief

### Team Onboarding
- [ ] Product team weet hoe tenants aan te maken
- [ ] Team kan posts maken en rewriten
- [ ] Team weet hoe publish logs te checken
- [ ] Team kan versies vergelijken in content_history
- [ ] Team weet hoe fouten te debuggen
- [ ] Escalatie proces voor tech issues duidelijk

### Monitoring & Alerting
- [ ] Basis healthcheck actief (synthetische call of ping)
- [ ] Error rate monitoring (bijv. >10 errors/uur → alert)
- [ ] Supabase dashboard toegang voor team
- [ ] Function logs toegankelijk en doorzoekbaar
- [ ] Database metrics in gaten gehouden (connections, CPU, memory)
- [ ] Alert configuratie voor kritieke fouten:
  - [ ] Alle Edge Functions down
  - [ ] Database connection errors
  - [ ] LLM API rate limits bereikt
  - [ ] WordPress authentication failures

### Backup & Recovery
- [ ] Database backups automatisch (Supabase default: ja)
- [ ] Backup retention policy bekend (7 dagen, 30 dagen?)
- [ ] Restore procedure getest
- [ ] Rollback plan voor Edge Function deployments
- [ ] Data export mogelijkheid getest

---

## 5. Schaalbaarheid & Toekomstvastheid

### Code Organization
- [ ] Mapstructuur logisch en consistent
- [ ] Shared modules herbruikbaar voor nieuwe functies
- [ ] TypeScript types gedocumenteerd
- [ ] Code comments op complexe logica
- [ ] Geen hardcoded values (gebruik env vars of config)

### Extensibility
- [ ] Nieuwe LLM provider toevoegen is straightforward
- [ ] Nieuwe content types (category) eenvoudig toe te voegen
- [ ] Extra WordPress post types ondersteund
- [ ] API versioning overwogen (v1, v2 routes)

### Developer Experience
- [ ] Local development setup werkt (supabase start)
- [ ] README bevat quick start guide
- [ ] Example .env file aanwezig
- [ ] Test data seed script beschikbaar (optioneel)
- [ ] Clear error messages in development mode

### Cost Optimization
- [ ] Supabase tier voldoet aan verwachte load (free/pro/enterprise)
- [ ] LLM API usage binnen budget
- [ ] Database size monitoring (gratis tier = 500MB)
- [ ] Edge Function invocations tracking
- [ ] Geen onnodige database queries (N+1 probleem)

---

## 6. Final Checks

### Pre-Launch
- [ ] Alle vorige secties 100% afgevinkt
- [ ] Stakeholder demo succesvol
- [ ] User acceptance testing (UAT) compleet
- [ ] Performance onder verwachte load getest
- [ ] Security audit uitgevoerd (intern of extern)
- [ ] Legal/compliance check (AVG, cookie policy, etc.) - indien relevant
- [ ] Go/no-go beslissing gedocumenteerd

### Launch Day
- [ ] Database productie ready (migrations gedraaid)
- [ ] Edge Functions live
- [ ] Frontend live en bereikbaar
- [ ] Test post succesvol gepubliceerd naar productie WordPress
- [ ] Monitoring dashboards open
- [ ] Team beschikbaar voor support
- [ ] Rollback plan klaar indien nodig

### Post-Launch (Week 1)
- [ ] Daily error logs gereviewd
- [ ] User feedback verzameld
- [ ] Performance metrics geanalyseerd
- [ ] Bug list aangemaakt en geprioriteerd
- [ ] Feature requests gedocumenteerd
- [ ] Retrospective meeting gepland

---

## Checklist Score

**Total Items:** ~100+
**Completed:** _____ / _____
**Percentage:** _____ %

**Go-Live Criteria:** Minimaal 95% afgevinkt voor productie release.

---

## Sign-Off

| Role | Name | Date | Signature |
|------|------|------|-----------|
| Tech Lead | | | |
| Product Owner | | | |
| QA Lead | | | |
| DevOps | | | |

---

**Version:** 1.0
**Last Updated:** 2025-01-09

