# Multi-Tenant Content Delivery Platform

A centralized platform for managing, rewriting, and distributing content (casino, game, bonus, guides) to multiple WordPress sites. Editors manage and personalize articles per tenant; publishing happens automatically, securely, and traceably via Supabase Edge Functions, directly from a single UI.

## 🎯 Key Features

- **Multi-Tenant Management**: Manage content for multiple WordPress sites from one interface
- **AI-Powered Rewriting**: Use OpenAI, Claude, or Gemini to rewrite content per tenant
- **Automated Publishing**: One-click publishing to WordPress via REST API
- **Affiliate Link Injection**: Automatically insert tenant-specific affiliate links
- **Version Control**: Track all content changes and rewrites with full audit trail
- **Publish Logging**: Monitor all publishing activities with detailed logs
- **Row-Level Security**: Secure data access with Supabase RLS policies

## 🏗️ Architecture

```
Frontend (React + TypeScript)
        ↓
Supabase Database
  ├── tenants
  ├── categories
  ├── posts
  ├── content_history
  └── publish_log
        ↓
Supabase Edge Functions
  ├── rewrite_content
  ├── inject_affiliate
  └── publish_to_wp
        ↓
Multiple WordPress Sites
```

## 📋 Prerequisites

- [Supabase Account](https://supabase.com) (free tier works)
- [Node.js](https://nodejs.org/) v18+ and npm
- [Supabase CLI](https://supabase.com/docs/guides/cli) (for local development)
- API keys for LLM providers:
  - OpenAI API key (for GPT models)
  - Anthropic API key (for Claude models) - optional
  - Google API key (for Gemini models) - optional
- WordPress sites with REST API enabled and authentication configured

## 🚀 Quick Start

### 1. Clone and Setup

```bash
# Navigate to the CMS platform directory
cd cms-platform

# Install frontend dependencies
cd frontend
npm install
cd ..
```

### 2. Configure Supabase

```bash
# Initialize Supabase (if not already done)
supabase init

# Link to your Supabase project
supabase link --project-ref YOUR_PROJECT_REF

# Run database migrations
supabase db push

# Deploy Edge Functions
supabase functions deploy rewrite_content
supabase functions deploy inject_affiliate
supabase functions deploy publish_to_wp
```

### 3. Set Environment Variables

**For Supabase Edge Functions:**

```bash
# Set secrets for Edge Functions
supabase secrets set OPENAI_API_KEY=your_openai_key
supabase secrets set ANTHROPIC_API_KEY=your_anthropic_key  # optional
supabase secrets set GOOGLE_API_KEY=your_google_key        # optional
supabase secrets set INTERNAL_FUNCTION_TOKEN=your_secure_random_token
```

**For Frontend (.env file):**

Create `frontend/.env`:

```env
VITE_SUPABASE_URL=https://your-project.supabase.co
VITE_SUPABASE_ANON_KEY=your_anon_key
VITE_SUPABASE_FUNCTION_URL=https://your-project.supabase.co/functions/v1
VITE_INTERNAL_FUNCTION_TOKEN=your_secure_random_token
```

### 4. Run the Application

```bash
cd frontend
npm run dev
```

Open http://localhost:5173 in your browser.

## 📖 Usage Guide

### Setting Up a Tenant

1. Navigate to the Tenants page
2. Click "Add New Tenant"
3. Fill in the details:
   - **Name**: Brand/site name (e.g., "Casino Site A")
   - **Slug**: URL-friendly identifier (e.g., "casino-a")
   - **WordPress API URL**: Full URL to your WP site (e.g., "https://casino-a.com")
   - **WP Token**: JWT token or App Password for authentication
   - **Brand Tone**: Writing style guidelines (optional)
   - **LLM Model**: Choose model (gpt-4, claude-3-opus, gemini-pro, etc.)
   - **Prompt Base**: Custom prompt for rewrites (optional)
4. Click "Save"

### Creating and Publishing Content

1. **Create Post**:
   - Navigate to Posts → New Post
   - Select tenant
   - Enter title, slug, and raw content
   - Add affiliate link (optional)
   - Fill in SEO fields
   - Save as Draft

2. **Rewrite Content**:
   - Open the post
   - Click "Rewrite with AI"
   - Review the rewritten content
   - Make manual edits if needed

3. **Inject Affiliate Links**:
   - Use `{{AFFILIATE}}` tokens in your content
   - Click "Inject Affiliate Links"
   - Links are automatically replaced

4. **Publish to WordPress**:
   - Click "Publish to WordPress"
   - Select target tenant
   - Confirm publication
   - Check publish logs for status

### Monitoring & Logs

- **Content History**: View all versions of rewritten content
- **Publish Logs**: Track all publishing attempts, successes, and failures
- **Dashboard**: Overview of all tenants and post statuses

## 🗂️ Project Structure

```
cms-platform/
├── supabase/
│   ├── functions/
│   │   ├── _shared/
│   │   │   ├── supabase.ts       # Supabase client
│   │   │   ├── utils.ts          # Utility functions
│   │   │   └── llm.ts            # LLM integrations
│   │   ├── rewrite_content/
│   │   │   └── index.ts          # Content rewriting function
│   │   ├── inject_affiliate/
│   │   │   └── index.ts          # Affiliate link injection
│   │   └── publish_to_wp/
│   │       └── index.ts          # WordPress publishing
│   └── migrations/
│       └── 001_initial_schema.sql
├── frontend/
│   ├── src/
│   │   ├── api/                  # API modules
│   │   ├── components/           # React components
│   │   ├── lib/                  # Utilities
│   │   └── pages/                # Page components
│   └── package.json
├── docs/
│   ├── PRD.md                    # Product Requirements Document
│   ├── TECH_SPEC.md              # Technical Specification
│   └── GO_LIVE_CHECKLIST.md      # Pre-launch checklist
└── README.md
```

## 🔒 Security

### Authentication & Authorization

- All Edge Functions require `x-internal-token` header for security
- Row-Level Security (RLS) enabled on all database tables
- Service role key never exposed to frontend
- WordPress credentials stored as Supabase secrets

### API Keys

- Never commit API keys to version control
- Use Supabase secrets for Edge Function environment variables
- Use `.env` files for frontend (add to `.gitignore`)

## 🧪 Testing Edge Functions

### Test Rewrite Function

```bash
curl -X POST https://your-project.supabase.co/functions/v1/rewrite_content \
  -H "Content-Type: application/json" \
  -H "x-internal-token: YOUR_TOKEN" \
  -d '{
    "post_id": "uuid-here",
    "force_rewrite": true
  }'
```

### Test Publish Function

```bash
curl -X POST https://your-project.supabase.co/functions/v1/publish_to_wp \
  -H "Content-Type: application/json" \
  -H "x-internal-token: YOUR_TOKEN" \
  -d '{
    "post_id": "uuid-here",
    "tenant_id": "uuid-here",
    "wp_post_type": "post"
  }'
```

## 📊 Database Schema

### Main Tables

- **tenants**: WordPress sites configuration
- **categories**: Content categories (casino, game, bonus, guide)
- **posts**: Content with raw and rewritten text
- **content_history**: Version history of all rewrites
- **publish_log**: Audit trail of all publishing activities

See [TECH_SPEC.md](docs/TECH_SPEC.md) for detailed schema.

## 🛠️ Development

### Local Development with Supabase CLI

```bash
# Start local Supabase instance
supabase start

# Run migrations
supabase db reset

# Test Edge Functions locally
supabase functions serve

# View local database
supabase db studio
```

### Adding a New LLM Provider

1. Add API integration to `supabase/functions/_shared/llm.ts`
2. Update `rewriteText()` function to handle new model
3. Add API key to Supabase secrets
4. Update tenant configuration options

## 📈 Scaling Considerations

- Edge Functions are stateless and scale horizontally automatically
- Database can handle thousands of posts per month on free tier
- For high-volume scenarios, consider Supabase Pro plan
- Implement caching for frequently accessed tenant configurations
- Use connection pooling for database access

## 🐛 Troubleshooting

### Common Issues

**Edge Function Returns 401 Unauthorized**
- Verify `x-internal-token` header is set correctly
- Check token matches in both frontend `.env` and Supabase secrets

**Rewrite Function Fails**
- Verify API keys are set correctly in Supabase secrets
- Check LLM API quota and billing
- Review function logs: `supabase functions logs rewrite_content`

**WordPress Publishing Fails**
- Verify WordPress REST API is enabled
- Check WP token has correct permissions
- Ensure WordPress URL includes https://
- Review publish_log table for error details

**RLS Policy Blocks Access**
- Verify user is authenticated with Supabase Auth
- Check RLS policies match your auth setup
- Use service role key for Edge Functions (already configured)

## 📚 Additional Resources

- [Supabase Documentation](https://supabase.com/docs)
- [WordPress REST API Reference](https://developer.wordpress.org/rest-api/)
- [OpenAI API Documentation](https://platform.openai.com/docs)
- [Anthropic Claude API](https://docs.anthropic.com)
- [Google Gemini API](https://ai.google.dev/docs)

## 🤝 Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Submit a pull request

## 📄 License

This project is licensed under the MIT License.

## 🙏 Support

For issues, questions, or feature requests, please open an issue in the repository.

---

**Version**: 1.0.0
**Last Updated**: 2025-01-09
