# Deploy Solidtime to Railway (5 Minutes)

## Prerequisites
- GitHub account
- Railway account (sign up at railway.app)

## Step 1: Prepare Repository

```bash
# Make sure everything is committed
git add .
git commit -m "Prepare for Railway deployment"
git push origin claude/do-you-got-011CUpPkiWUksdhCGjJgAgX3
```

## Step 2: Create railway.json

```json
{
  "$schema": "https://railway.app/railway.schema.json",
  "build": {
    "builder": "NIXPACKS",
    "buildCommand": "composer install --no-dev --optimize-autoloader && npm install && npm run build"
  },
  "deploy": {
    "startCommand": "php artisan migrate --force && php artisan optimize && php artisan serve --host=0.0.0.0 --port=$PORT",
    "restartPolicyType": "ON_FAILURE",
    "restartPolicyMaxRetries": 10
  }
}
```

## Step 3: Create Procfile

```
web: php artisan serve --host=0.0.0.0 --port=$PORT
```

## Step 4: Deploy via Railway Dashboard

1. Go to https://railway.app/new
2. Click "Deploy from GitHub repo"
3. Select your `peterpeeterspeter/solidtime` repository
4. Click "Add variables" and set:

```env
APP_NAME=Solidtime
APP_ENV=production
APP_KEY=base64:XG9+xJby13JjVhRYJrWfxhiNiUY9e0l7j6WxBYL+pdg=
APP_DEBUG=false
APP_URL=https://your-app.railway.app

DB_CONNECTION=pgsql
DB_HOST=${{Postgres.PGHOST}}
DB_PORT=${{Postgres.PGPORT}}
DB_DATABASE=${{Postgres.PGDATABASE}}
DB_USERNAME=${{Postgres.PGUSER}}
DB_PASSWORD=${{Postgres.PGPASSWORD}}

QUEUE_CONNECTION=database
SESSION_DRIVER=database
CACHE_DRIVER=file
```

5. Click "Add Database" → "PostgreSQL"
6. Click "Deploy"

## Step 5: Run Migrations

Once deployed, go to your Railway project and click on your service, then:

1. Click "Settings"
2. Click "Generate Domain" to get your public URL
3. In the terminal tab, run:
   ```bash
   php artisan migrate --force
   php artisan admin:user:create "Your Name" "you@example.com" --verify-email
   ```

## Step 6: Access Your App

Visit your Railway URL (e.g., `https://your-app.railway.app`)

## Troubleshooting

### Build fails
- Check build logs in Railway dashboard
- Ensure composer and npm dependencies are correct

### Database connection fails
- Verify environment variables are set correctly
- Make sure PostgreSQL service is linked

### App key error
- Run: `php artisan key:generate --show` locally
- Copy the key and set it in Railway environment variables

## Cost
- **PostgreSQL**: $5/month
- **App hosting**: Included in PostgreSQL plan
- **Total**: $5/month

## Alternative: Free Tier with Neon PostgreSQL
Use Neon.tech for free PostgreSQL (500MB limit):
1. Sign up at neon.tech
2. Create database
3. Use Neon connection string in Railway
4. App hosting on Railway: $5/month
