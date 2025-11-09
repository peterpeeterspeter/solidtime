# ⚡ Quick Database Setup (2 Minutes)

## Step 1: Open SQL Editor

1. Go to: https://app.supabase.com/project/ambjsovdhizjxwhhnbtd/sql/new

2. You should see the SQL Editor

## Step 2: Copy & Run the Migration

1. Open this file on your computer:
   ```
   cms-platform/supabase/migrations/001_initial_schema.sql
   ```

2. **Select ALL** the content (Ctrl+A / Cmd+A)

3. **Copy** it (Ctrl+C / Cmd+C)

4. **Paste** into the SQL Editor in Supabase

5. Click the **"RUN"** button (or press Ctrl+Enter)

6. ✅ You should see: **"Success. No rows returned"**

## Step 3: Verify It Worked

1. Click **"Table Editor"** in the left sidebar

2. You should now see 5 new tables:
   - ✅ `tenants`
   - ✅ `categories`
   - ✅ `posts`
   - ✅ `publish_log`
   - ✅ `content_history`

3. Click on `categories` - you should see 4 rows:
   - Casino
   - Game
   - Bonus
   - Guide

## ✅ Done!

Your database is ready. Next step: Set up the frontend.

---

## Next: Frontend Setup

1. **Get your Anon Key:**
   - Dashboard → Settings → API
   - Copy the **"anon"** **"public"** key

2. **Edit the .env file:**
   ```bash
   cd cms-platform/frontend
   nano .env   # or use any text editor
   ```

3. **Replace** `YOUR_ANON_KEY_HERE` with your actual anon key

4. **Generate internal token:**
   ```bash
   openssl rand -hex 32
   ```
   Or use: https://www.random.org/strings/ (32 characters, alphanumeric)

5. **Add the token** to `.env` as `VITE_INTERNAL_FUNCTION_TOKEN`

6. **Install and run:**
   ```bash
   npm install
   npm run dev
   ```

7. **Open:** http://localhost:5173

---

## That's It!

Questions? Let me know which step you're on.
