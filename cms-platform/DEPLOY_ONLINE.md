# 🚀 Deploy CMS Platform Online

The app is built and ready! Here are 3 easy ways to deploy it online:

---

## Option 1: Vercel (Easiest - 2 minutes)

1. **Go to [vercel.com](https://vercel.com)** and sign up/login (free)

2. **Click "Add New Project"**

3. **Import your GitHub repository:**
   - Connect your GitHub account
   - Select `solidtime` repository
   - Select branch: `claude/start-new-session-011CUxJZAmQGRCKf1KJXSEeD`

4. **Configure the project:**
   - Framework Preset: **Vite**
   - Root Directory: `cms-platform/frontend`
   - Build Command: `npm run build`
   - Output Directory: `dist`

5. **Add Environment Variables:**
   ```
   VITE_SUPABASE_URL=https://ambjsovdhizjxwhhnbtd.supabase.co
   VITE_SUPABASE_ANON_KEY=eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZSIsInJlZiI6ImFtYmpzb3ZkaGl6anh3aGhuYnRkIiwicm9sZSI6ImFub24iLCJpYXQiOjE3NDc2Mzc2NDYsImV4cCI6MjA2MzIxMzY0Nn0.3H8N2Fk22RAV1gHzDB5pCi9GokGwroG34v15I5Cq8_g
   VITE_SUPABASE_FUNCTION_URL=https://ambjsovdhizjxwhhnbtd.supabase.co/functions/v1
   VITE_INTERNAL_FUNCTION_TOKEN=13b5889f4d72a64297a264468914b8763e7de1260c5a1e1c3fdf088adbc4d670
   ```

6. **Click "Deploy"**

7. **Done!** You'll get a URL like: `https://your-app.vercel.app`

---

## Option 2: Netlify (Also Easy)

1. **Go to [netlify.com](https://netlify.com)** and sign up/login

2. **Click "Add new site" → "Import an existing project"**

3. **Connect to GitHub** and select your `solidtime` repo

4. **Configure:**
   - Branch: `claude/start-new-session-011CUxJZAmQGRCKf1KJXSEeD`
   - Base directory: `cms-platform/frontend`
   - Build command: `npm run build`
   - Publish directory: `cms-platform/frontend/dist`

5. **Add Environment Variables** (same as above)

6. **Deploy!**

---

## Option 3: Vercel CLI (For developers)

```bash
# Install Vercel CLI
npm i -g vercel

# Navigate to frontend
cd cms-platform/frontend

# Deploy
vercel --prod

# Follow prompts and you're done!
```

---

## 🎯 After Deployment

Once deployed, you'll get a URL like:
- **Vercel**: `https://solidtime-cms.vercel.app`
- **Netlify**: `https://solidtime-cms.netlify.app`

Just visit that URL and your CMS will be live! 🎉

---

## ⚙️ What's Already Configured

✅ Production build created
✅ Environment variables set in `vercel.json`
✅ Supabase connection configured
✅ All frontend files ready
✅ Database schema deployed

---

## 🔧 If You Want to Update Later

Just push to the git branch and Vercel/Netlify will auto-deploy!

```bash
git add .
git commit -m "Update CMS"
git push origin claude/start-new-session-011CUxJZAmQGRCKf1KJXSEeD
```

---

**Recommended**: Use **Option 1 (Vercel)** - it's the fastest and easiest!
