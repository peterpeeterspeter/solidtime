# Quick Start Guide

Get the Multi-Tenant CMS Platform running in under 10 minutes.

## Prerequisites

- [Supabase Account](https://supabase.com) (free tier works)
- [Node.js](https://nodejs.org/) v18 or higher
- [Supabase CLI](https://supabase.com/docs/guides/cli/getting-started)

## Step 1: Set Up Supabase Project

### Create a New Project

1. Go to [supabase.com](https://supabase.com)
2. Click "New Project"
3. Name your project (e.g., "multi-tenant-cms")
4. Set a strong database password
5. Choose a region close to you
6. Click "Create new project"

### Get Your Project Credentials

1. Go to Project Settings → API
2. Copy your:
   - Project URL
   - Anon (public) key
   - Service role key (keep this secret!)

## Step 2: Install Supabase CLI

```bash
# macOS
brew install supabase/tap/supabase

# Windows (PowerShell)
scoop bucket add supabase https://github.com/supabase/scoop-bucket.git
scoop install supabase

# Linux
brew install supabase/tap/supabase
```

Verify installation:
```bash
supabase --version
```

## Step 3: Initialize and Deploy Database

```bash
# Navigate to the CMS platform directory
cd cms-platform

# Login to Supabase
supabase login

# Link to your project
supabase link --project-ref YOUR_PROJECT_REF

# Run migrations to create database schema
supabase db push
```

## Step 4: Configure Secrets

Generate a secure token:
```bash
# Generate a random 32-character token
openssl rand -hex 32
```

Set the secrets:
```bash
# Set internal token (use the generated token from above)
supabase secrets set INTERNAL_FUNCTION_TOKEN=your_generated_token

# Set OpenAI key (required for GPT models)
supabase secrets set OPENAI_API_KEY=sk-your-openai-key

# Optional: Set Anthropic key (for Claude models)
supabase secrets set ANTHROPIC_API_KEY=sk-ant-your-anthropic-key

# Optional: Set Google key (for Gemini models)
supabase secrets set GOOGLE_API_KEY=your-google-key
```

## Step 5: Deploy Edge Functions

```bash
# Deploy all functions
supabase functions deploy rewrite_content
supabase functions deploy inject_affiliate
supabase functions deploy publish_to_wp

# Verify deployment
supabase functions list
```

## Step 6: Set Up Frontend

```bash
# Navigate to frontend directory
cd frontend

# Install dependencies
npm install

# Create .env file
cp .env.example .env

# Edit .env and add your credentials:
# - VITE_SUPABASE_URL (from Step 1)
# - VITE_SUPABASE_ANON_KEY (from Step 1)
# - VITE_INTERNAL_FUNCTION_TOKEN (same as Step 4)
nano .env  # or use your preferred editor
```

## Step 7: Run the Application

```bash
# Start development server
npm run dev
```

Open http://localhost:5173 in your browser.

## Step 8: Create Your First Tenant

1. In the UI, navigate to "Tenants"
2. Click "Add New Tenant"
3. Fill in:
   - **Name**: My First Site
   - **Slug**: my-first-site
   - **WordPress API URL**: https://your-wordpress-site.com
   - **WP Token**: Your WordPress JWT token or App Password
   - **LLM Model**: gpt-4 (or your preferred model)
4. Click "Save"

## Step 9: Create and Publish Your First Post

1. Navigate to "Posts"
2. Click "New Post"
3. Select your tenant
4. Fill in title, slug, and raw content
5. Click "Save"
6. Click "Rewrite with AI" (wait ~5-10 seconds)
7. Review the rewritten content
8. Click "Publish to WordPress"
9. Check your WordPress site!

## Troubleshooting

### "Unauthorized" Error

- Verify `INTERNAL_FUNCTION_TOKEN` matches in both frontend `.env` and Supabase secrets
- Check: `supabase secrets list`

### Rewrite Function Fails

- Verify OpenAI API key is set: `supabase secrets list`
- Check your OpenAI account has credits
- View logs: `supabase functions logs rewrite_content`

### WordPress Publishing Fails

- Ensure WordPress REST API is enabled
- Verify WP token has correct permissions
- Check WordPress URL uses HTTPS
- Review publish_log table for error details

### Database Connection Error

- Verify you ran migrations: `supabase db push`
- Check project is linked: `supabase projects list`

## Next Steps

- Read the full [README.md](README.md) for detailed documentation
- Review [TECH_SPEC.md](docs/TECH_SPEC.md) for API details
- Check [GO_LIVE_CHECKLIST.md](docs/GO_LIVE_CHECKLIST.md) before production deployment

## Getting Help

- Check function logs: `supabase functions logs <function-name>`
- View database logs: `supabase db logs`
- Open issues on GitHub (if applicable)

---

**Estimated Time:** 10-15 minutes

Good luck! 🚀
