# 🎯 Setup Guide - Using Your Existing Casino Database

Great news! Your database is already set up with casino content. Let's configure the CMS platform to work with it.

---

## ✅ Step 1: Check Your Database (SQL Queries)

Run these queries in your **Supabase SQL Editor** and **send me the results**:

### Query 1: Table Row Counts
```sql
SELECT
    'tenants' as table_name,
    COUNT(*) as row_count
FROM tenants
UNION ALL
SELECT 'categories', COUNT(*) FROM categories
UNION ALL
SELECT 'posts', COUNT(*) FROM posts
UNION ALL
SELECT 'publish_log', COUNT(*) FROM publish_log
UNION ALL
SELECT 'content_history', COUNT(*) FROM content_history;
```

### Query 2: Your Tenants
```sql
SELECT id, name, slug, llm_model, active, wp_api_url
FROM tenants;
```

### Query 3: Sample Posts
```sql
SELECT
    p.id,
    p.title,
    p.status,
    t.name as tenant_name,
    c.name as category_name,
    LENGTH(p.raw_text) as raw_text_length,
    LENGTH(p.rewritten_text) as rewritten_text_length
FROM posts p
LEFT JOIN tenants t ON p.tenant_id = t.id
LEFT JOIN categories c ON p.category_id = c.id
LIMIT 10;
```

---

## 🔐 Step 2: Set Supabase Secrets

In **Supabase Dashboard → Project Settings → Edge Functions → Secrets**, add:

### Required Secret:
```bash
INTERNAL_FUNCTION_TOKEN=13b5889f4d72a64297a264468914b8763e7de1260c5a1e1c3fdf088adbc4d670
```

### Optional (for LLM features):
```bash
OPENAI_API_KEY=sk-your-key-here
ANTHROPIC_API_KEY=sk-ant-your-key-here
GOOGLE_API_KEY=your-google-key-here
```

**How to add secrets:**
1. Go to: https://app.supabase.com/project/ambjsovdhizjxwhhnbtd/settings/functions
2. Scroll to "Secrets"
3. Click "Add new secret"
4. Name: `INTERNAL_FUNCTION_TOKEN`
5. Value: `13b5889f4d72a64297a264468914b8763e7de1260c5a1e1c3fdf088adbc4d670`
6. Click "Save"

---

## 🚀 Step 3: Start the Frontend

Your `.env` file is already configured! Just run:

```bash
cd cms-platform/frontend
npm install
npm run dev
```

Then open: **http://localhost:5173**

---

## 📊 Step 4: What's Already Configured

✅ **Frontend .env** - Fully configured with:
- Supabase URL
- Anon key
- Internal function token
- Debug mode enabled

✅ **Database** - Already has:
- Tenants table
- Posts (casino content)
- Categories
- Logging tables

---

## 🔧 Next Steps (After Running Queries)

Once you send me the query results, I can:

1. ✅ Verify your schema matches our Edge Functions
2. ✅ Help you deploy the Edge Functions
3. ✅ Update any missing columns
4. ✅ Test the complete workflow
5. ✅ Show you how to use the platform

---

## ⚙️ Optional: Deploy Edge Functions

If you want the AI rewrite and WordPress publishing features, you'll need to deploy the Edge Functions.

**Two options:**

### Option A: Via Supabase CLI (if installed)
```bash
cd cms-platform
supabase link --project-ref ambjsovdhizjxwhhnbtd
supabase functions deploy rewrite_content
supabase functions deploy inject_affiliate
supabase functions deploy publish_to_wp
```

### Option B: Manual Deployment (via Dashboard)
I'll help you with this after you send the query results.

---

## 🎯 Current Status

- ✅ Database: Exists with casino content
- ✅ Frontend .env: Configured
- ✅ Anon key: Added
- ✅ Internal token: Generated and set
- ⏳ Waiting for: Query results from you
- ⏳ Next: Deploy Edge Functions

---

**Run those 3 SQL queries and send me the results!** Then I'll help you complete the setup. 🚀
