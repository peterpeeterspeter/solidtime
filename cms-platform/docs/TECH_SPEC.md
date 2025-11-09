# Technical Specification – Multi-Tenant Content Delivery Platform

**Version**: 1.0
**Last Updated**: 2025-01-09

---

## 1. Systeemoverzicht

### 1.1 Doel

Een centraal Supabase-gebaseerd CMS dat content (casino, games, bonuses, guides) herschrijft met LLM's en deze distribueert naar meerdere WordPress-installaties.

### 1.2 Hoofdcomponenten

```
Cursor Admin UI (React/TypeScript)
        │ supabase-js
        ▼
Supabase Database
  ├── tenants
  ├── categories
  ├── posts
  ├── content_history
  ├── publish_log
        │
        ▼
Supabase Edge Functions
  ├── rewrite_content     # LLM-herschrijving
  ├── inject_affiliate    # link-injectie
  └── publish_to_wp       # publicatie naar WordPress
        │
        ▼
Meerdere WordPress-sites (REST API + JWT/App Password)
```

---

## 2. Database-schema

### 2.1 Tabellen

#### tenants

| kolom | type | beschrijving |
|-------|------|--------------|
| id | uuid (PK) | unieke tenant-id |
| name | text | naam van merk/site |
| slug | text | korte naam |
| wp_api_url | text | bv. https://casinoX.com |
| wp_token | text | JWT of App Password |
| brand_tone | text | schrijfstijl |
| llm_model | text | bijv. gpt-4 |
| prompt_base | text | basistekst voor LLM-rewrite |
| active | boolean | tenant actief |
| created_at | timestamptz | auto |
| updated_at | timestamptz | auto |

**Indexes:**
- idx_tenants_slug (slug)
- idx_tenants_active (active)

#### categories

| kolom | type | beschrijving |
|-------|------|--------------|
| id | uuid (PK) | categorie-id |
| name | text | bijv. "casino", "bonus" |
| slug | text | unieke naam |
| description | text | optioneel |
| created_at | timestamptz | auto |
| updated_at | timestamptz | auto |

**Indexes:**
- idx_categories_slug (slug)

#### posts

| kolom | type | beschrijving |
|-------|------|--------------|
| id | uuid (PK) | post-id |
| tenant_id | uuid FK → tenants | doel-site |
| category_id | uuid FK → categories | type content |
| title | text | titel |
| slug | text | url-slug |
| raw_text | text | originele tekst |
| rewritten_text | text | herschreven tekst |
| affiliate_link | text | dynamische link |
| seo_title | text | SEO titel |
| seo_description | text | SEO beschrijving |
| status | enum(draft, review, published) | workflow-status |
| created_at | timestamptz | auto |
| updated_at | timestamptz | auto |

**Indexes:**
- idx_posts_tenant_id (tenant_id)
- idx_posts_category_id (category_id)
- idx_posts_status (status)
- idx_posts_slug (slug)

**Constraints:**
- UNIQUE(tenant_id, slug)

#### publish_log

| kolom | type | beschrijving |
|-------|------|--------------|
| id | uuid (PK) | log-id |
| tenant_id | uuid | waar gepubliceerd |
| post_id | uuid | bronpost |
| target_url | text | WP-endpoint |
| response_code | int | HTTP-code |
| response_message | text | WP-response |
| created_at | timestamptz | tijdstip |

**Indexes:**
- idx_publish_log_tenant_id (tenant_id)
- idx_publish_log_post_id (post_id)
- idx_publish_log_created_at (created_at DESC)
- idx_publish_log_response_code (response_code)

#### content_history

| kolom | type | beschrijving |
|-------|------|--------------|
| id | uuid (PK) | versie-id |
| parent_id | uuid FK → posts | gekoppelde post |
| version_num | int | oplopend |
| raw_text | text | invoer |
| rewritten_text | text | uitvoer |
| rewrite_model | text | modelnaam |
| created_at | timestamptz | auto |

**Indexes:**
- idx_content_history_parent_id (parent_id)
- idx_content_history_version_num (version_num)
- idx_content_history_created_at (created_at DESC)

---

## 3. Edge Functions

### 3.1 Bestandsstructuur

```
supabase/
 ├── functions/
 │   ├── rewrite_content/
 │   │    └── index.ts
 │   ├── inject_affiliate/
 │   │    └── index.ts
 │   ├── publish_to_wp/
 │   │    └── index.ts
 │   └── _shared/
 │        ├── supabase.ts
 │        ├── llm.ts
 │        └── utils.ts
 └── .env.example
```

### 3.2 Shared Modules

#### _shared/supabase.ts

```typescript
import { createClient } from 'https://esm.sh/@supabase/supabase-js@2'

export const supabase = createClient(
  Deno.env.get('SUPABASE_URL')!,
  Deno.env.get('SUPABASE_SERVICE_ROLE_KEY')!
)
```

#### _shared/utils.ts

**Functies:**
- `requireInternalToken(req: Request)`: Valideert x-internal-token header
- `logError(supabase, context)`: Logt foutmeldingen naar publish_log
- `logSuccess(supabase, context)`: Logt succesvolle acties
- `createResponse(data, status)`: Creëert JSON response met CORS
- `createErrorResponse(message, status)`: Creëert error response
- `handleCors(req)`: Handelt CORS preflight af

#### _shared/llm.ts

**Functies:**
- `rewriteText(config)`: Hoofdfunctie voor content herschrijven
- `rewriteWithOpenAI(config)`: OpenAI/GPT integratie
- `rewriteWithClaude(config)`: Anthropic Claude integratie
- `rewriteWithGemini(config)`: Google Gemini integratie
- `validateApiKeys(model)`: Valideert of vereiste API keys aanwezig zijn

**Ondersteunde modellen:**
- OpenAI: gpt-4, gpt-4-turbo, gpt-3.5-turbo
- Anthropic: claude-3-opus, claude-3-sonnet, claude-3-haiku
- Google: gemini-pro, gemini-ultra

### 3.3 rewrite_content Function

**Endpoint:** `/functions/v1/rewrite_content`

**Request:**
```json
{
  "post_id": "uuid",
  "tenant_id": "uuid",  // optional for validation
  "force_rewrite": false
}
```

**Response:**
```json
{
  "success": true,
  "message": "Content rewritten successfully",
  "post_id": "uuid",
  "version": 2,
  "model": "gpt-4",
  "content_length": 1234
}
```

**Workflow:**
1. Validate internal token
2. Fetch post en tenant details
3. Check if rewrite needed (skip if already rewritten unless force_rewrite)
4. Validate API keys for selected model
5. Call LLM API met tenant-specific prompt
6. Update post.rewritten_text en status → 'review'
7. Create content_history entry
8. Return success response

**Error Handling:**
- Post not found → 404
- Tenant not found → 404
- Tenant inactive → 400
- No raw_text → 400
- LLM error → 500 + log to publish_log

### 3.4 inject_affiliate Function

**Endpoint:** `/functions/v1/inject_affiliate`

**Request:**
```json
{
  "post_id": "uuid",
  "target_field": "rewritten_text",
  "custom_link": "https://...",  // optional
  "dry_run": false
}
```

**Response:**
```json
{
  "success": true,
  "message": "Affiliate links injected successfully",
  "post_id": "uuid",
  "tokens_replaced": 3,
  "tokens": ["{{AFFILIATE}}", "{{affiliate}}"],
  "field_updated": "rewritten_text",
  "affiliate_link": "https://..."
}
```

**Token Formats Supported:**
- `{{AFFILIATE}}`
- `{{affiliate}}`
- `{AFFILIATE}`
- `[AFFILIATE]`

**Replacement HTML:**
```html
<a href="{affiliate_link}" rel="nofollow sponsored" target="_blank" class="affiliate-link">{post_title}</a>
```

### 3.5 publish_to_wp Function

**Endpoint:** `/functions/v1/publish_to_wp`

**Request:**
```json
{
  "post_id": "uuid",
  "tenant_id": "uuid",
  "wp_post_type": "post",      // optional, default: "posts"
  "wp_status": "publish",       // optional, default: "publish"
  "update_existing": false      // optional, default: false
}
```

**Response:**
```json
{
  "success": true,
  "message": "Post published successfully to WordPress",
  "post_id": "uuid",
  "tenant_id": "uuid",
  "tenant_name": "Casino Site A",
  "wp_post_id": 123,
  "wp_post_url": "https://casino-a.com/post-slug"
}
```

**WordPress API Call:**
```
POST {wp_api_url}/wp-json/wp/v2/{wp_post_type}
Headers:
  Authorization: Bearer {wp_token}
  Content-Type: application/json

Body:
{
  "title": "Post Title",
  "content": "Rewritten or raw text",
  "status": "publish",
  "slug": "post-slug",
  "meta": {
    "seo_title": "SEO Title",
    "seo_description": "SEO Description",
    "affiliate_link": "https://..."
  }
}
```

**Workflow:**
1. Validate internal token
2. Fetch tenant en post
3. Verify post belongs to tenant
4. Determine content (prefer rewritten over raw)
5. Call WordPress REST API
6. Log result to publish_log (success or error)
7. Update post.status → 'published' on success
8. Return response

---

## 4. Frontend (React + TypeScript)

### 4.1 Structuur

```
frontend/src/
 ├── lib/
 │   └── supabaseClient.ts     # Supabase client config
 ├── api/
 │   ├── tenants.ts            # Tenant CRUD operations
 │   └── posts.ts              # Post CRUD + function calls
 ├── pages/
 │   ├── Dashboard.tsx         # Main dashboard
 │   ├── TenantsList.tsx       # Tenants overview
 │   ├── TenantForm.tsx        # Create/edit tenant
 │   ├── PostsList.tsx         # Posts overview
 │   └── PostForm.tsx          # Create/edit post
 └── components/
     ├── PublishButton.tsx     # Publish to WP button
     ├── RewriteButton.tsx     # Rewrite content button
     └── LogsModal.tsx         # View publish logs
```

### 4.2 Key API Functions

#### Tenant Operations (api/tenants.ts)
- `getTenants()`: Fetch all tenants
- `getTenant(id)`: Fetch single tenant
- `createTenant(data)`: Create new tenant
- `updateTenant(id, data)`: Update tenant
- `deleteTenant(id)`: Delete tenant
- `toggleTenantActive(id, active)`: Toggle active status

#### Post Operations (api/posts.ts)
- `getPosts(filters)`: Fetch posts with optional filters
- `getPost(id)`: Fetch single post
- `createPost(data)`: Create new post
- `updatePost(id, data)`: Update post
- `deletePost(id)`: Delete post
- `rewritePost(postId, options)`: Trigger rewrite
- `injectAffiliateLinks(postId, options)`: Inject affiliate links
- `publishToWordPress(postId, tenantId, options)`: Publish to WP
- `getContentHistory(postId)`: Get version history
- `getPublishLogs(postId)`: Get publish logs

### 4.3 Environment Variables

```env
VITE_SUPABASE_URL=https://xxx.supabase.co
VITE_SUPABASE_ANON_KEY=eyJ...
VITE_SUPABASE_FUNCTION_URL=https://xxx.supabase.co/functions/v1
VITE_INTERNAL_FUNCTION_TOKEN=your-secret-token
```

---

## 5. Security & Operations

### 5.1 Security Measures

| Maatregel | Uitleg |
|-----------|--------|
| x-internal-token | Verplicht header voor interne function calls |
| RLS | Filtert data per tenant/user |
| Secrets | WP-tokens en service keys niet in tabellen |
| HTTPS Only | Alle communicatie versleuteld |
| Service Role | Alleen Edge Functions hebben service role access |

### 5.2 Row-Level Security Policies

**Tenants:**
- Users can view their assigned tenants
- Service role has full access

**Posts:**
- Users can only view/edit posts for their tenants
- Service role has full access

**Logs:**
- Users can view logs for their tenants
- No write access from frontend

### 5.3 API Authentication

**WordPress Sites:**
- JWT tokens (recommended)
- Application Passwords (fallback)
- Bearer token authentication

**LLM Providers:**
- API keys stored in Supabase secrets
- Never exposed to frontend
- Rotation capability via Supabase dashboard

---

## 6. Testing & Deployment

### 6.1 Local Testing

```bash
# Start Supabase locally
supabase start

# Test Edge Functions
supabase functions serve

# Run migrations
supabase db reset

# Test function with curl
curl -X POST http://localhost:54321/functions/v1/rewrite_content \
  -H "x-internal-token: test-token" \
  -d '{"post_id": "uuid"}'
```

### 6.2 Deployment

```bash
# Deploy Edge Functions
supabase functions deploy rewrite_content
supabase functions deploy inject_affiliate
supabase functions deploy publish_to_wp

# Set secrets
supabase secrets set OPENAI_API_KEY=sk-...
supabase secrets set INTERNAL_FUNCTION_TOKEN=...

# Deploy frontend (example: Vercel)
cd frontend
npm run build
vercel deploy
```

### 6.3 Testflow (samenvatting)

1. Maak één tenant + testpost
2. Call /rewrite_content (controleer rewritten_text)
3. Call /inject_affiliate (controleer token replacement)
4. Call /publish_to_wp (controleer publish_log en WP-site)
5. Foutinjectie (verkeerde token) → controleer error-logging

---

## 7. Extensies (v2)

### Planned Features

- **Image Management**: Supabase Storage + WP Media API integration
- **Batch Publishing**: Queue system via rewrite_jobs table
- **Slack Notifications**: Webhook integration for errors/successes
- **Multi-LLM Routing**: A/B testing met verschillende modellen
- **Analytics Dashboard**: Per-tenant performance metrics
- **Content Templates**: Herbruikbare sjablonen per category
- **Scheduled Publishing**: Cron jobs voor timed releases
- **Rollback Functionality**: Restore previous content versions

### Technical Debt

- Implement WordPress post update (currently only creates new)
- Add retry mechanism for failed API calls
- Implement rate limiting for LLM calls
- Add comprehensive error codes
- Build admin panel for user management

---

## 8. Performance Considerations

### Database

- Indexes op alle foreign keys en frequent queried columns
- Automatic vacuuming enabled
- Connection pooling via Supavisor

### Edge Functions

- Stateless design → horizontal scaling
- Timeout: 2 minutes max
- Memory: 512MB per invocation
- Cold start: ~100-500ms

### Frontend

- Code splitting per route
- Lazy loading voor components
- React Query voor caching
- Debouncing voor search/filter

---

**End of Technical Specification**
