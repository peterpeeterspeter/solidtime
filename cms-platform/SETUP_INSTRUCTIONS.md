# 🚀 Setup Instructions for Your Supabase CMS Platform

Your CMS platform is ready to be deployed! Follow these steps carefully.

## Step 1: Set Up the Database Schema

### Option A: Using Supabase Dashboard (Easiest - 2 minutes)

1. Go to your Supabase dashboard: https://app.supabase.com/project/ambjsovdhizjxwhhnbtd

2. Click on **SQL Editor** in the left sidebar

3. Click **New Query**

4. Copy the **entire contents** of the file:
   ```
   cms-platform/supabase/migrations/001_initial_schema.sql
   ```

5. Paste into the SQL editor

6. Click **Run** (or press Cmd/Ctrl + Enter)

7. You should see: **"Success. No rows returned"** - this is normal!

8. Verify the tables were created:
   - Go to **Table Editor** in the sidebar
   - You should see: `tenants`, `categories`, `posts`, `publish_log`, `content_history`

---

## Step 2: Configure Environment Secrets

Go to **Project Settings → Edge Functions** (or Settings → API) and add these secrets:

### Required Secrets:

```bash
# Generate a secure random token (32+ characters):
INTERNAL_FUNCTION_TOKEN=<generate-random-token-here>

# At least one LLM API key:
OPENAI_API_KEY=sk-your-openai-key-here
```

### Optional (for additional LLM providers):

```bash
ANTHROPIC_API_KEY=sk-ant-your-key-here
GOOGLE_API_KEY=your-google-key-here
```

**How to generate INTERNAL_FUNCTION_TOKEN:**
- Go to https://www.random.org/strings/
- Generate 1 string, 32 characters, alphanumeric
- Or use: `openssl rand -hex 32` in terminal

---

## Step 3: Deploy Edge Functions

### You have 3 options:

#### Option A: Using Supabase CLI (Recommended if you have it installed)

```bash
cd cms-platform

# Login
supabase login

# Link project
supabase link --project-ref ambjsovdhizjxwhhnbtd

# Deploy functions
supabase functions deploy rewrite_content
supabase functions deploy inject_affiliate
supabase functions deploy publish_to_wp
```

#### Option B: Manual Deployment via Dashboard

1. Go to **Edge Functions** in your Supabase dashboard
2. Click **Create a new function**
3. For each function (`rewrite_content`, `inject_affiliate`, `publish_to_wp`):
   - Name: (function name)
   - Copy the code from `cms-platform/supabase/functions/[function-name]/index.ts`
   - Also copy the `_shared` folder contents
   - Deploy

#### Option C: Provide Your Database Password

If you provide your database password, I can connect via psql and set everything up automatically.

---

## Step 4: Configure Frontend

1. Navigate to `cms-platform/frontend/`

2. Create a `.env` file:

```bash
cd cms-platform/frontend
cp .env.example .env
```

3. Edit `.env` with your credentials:

```env
VITE_SUPABASE_URL=https://ambjsovdhizjxwhhnbtd.supabase.co
VITE_SUPABASE_ANON_KEY=<your-anon-key>
VITE_SUPABASE_FUNCTION_URL=https://ambjsovdhizjxwhhnbtd.supabase.co/functions/v1
VITE_INTERNAL_FUNCTION_TOKEN=<same-token-from-step-2>
```

**Where to find your anon key:**
- Dashboard → Project Settings → API → `anon` `public` key

4. Install dependencies and run:

```bash
npm install
npm run dev
```

5. Open http://localhost:5173

---

## Step 5: Verify Everything Works

### Test Database:

Run this SQL query in SQL Editor:

```sql
SELECT * FROM categories;
```

You should see 4 categories: Casino, Game, Bonus, Guide

### Test Edge Functions (Optional):

```bash
curl -X POST https://ambjsovdhizjxwhhnbtd.supabase.co/functions/v1/rewrite_content \
  -H "Content-Type: application/json" \
  -H "x-internal-token: YOUR_TOKEN_HERE" \
  -d '{"test": true}'
```

---

## Step 6: Create Your First Tenant

1. In the frontend UI, go to **Tenants**
2. Click **Add New Tenant**
3. Fill in:
   - **Name**: Test Site
   - **Slug**: test-site
   - **WordPress API URL**: https://your-wordpress-site.com
   - **WP Token**: (your WordPress JWT or App Password)
   - **LLM Model**: gpt-4
4. Save

---

## Troubleshooting

### Database tables not created?
- Re-run the SQL migration from Step 1
- Check for error messages in SQL Editor

### Edge Functions not deploying?
- Verify secrets are set correctly
- Check function logs in Dashboard → Edge Functions → Logs

### Frontend can't connect?
- Verify `.env` file has correct values
- Check CORS settings in Supabase dashboard
- Ensure `VITE_INTERNAL_FUNCTION_TOKEN` matches the secret

---

## Quick Status Check

**Your Supabase Project:**
- URL: https://ambjsovdhizjxwhhnbtd.supabase.co
- Service Role: ✅ Provided
- Database: ⏳ Pending (complete Step 1)
- Edge Functions: ⏳ Pending (complete Step 3)
- Frontend: ⏳ Pending (complete Step 4)

---

## Next Steps After Setup

1. Read the [README.md](README.md) for full documentation
2. Check [TECH_SPEC.md](docs/TECH_SPEC.md) for API details
3. Review [GO_LIVE_CHECKLIST.md](docs/GO_LIVE_CHECKLIST.md) before production

---

Need help? Let me know which step you're stuck on!
