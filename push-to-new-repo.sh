#!/bin/bash

# Script to push CMS platform to CMSCLAUDECODEONLINE repository
# Run this on your Mac

set -e

echo "Cloning solidtime repository..."
git clone https://github.com/peterpeeterspeter/solidtime.git temp-solidtime
cd temp-solidtime
git checkout claude/start-new-session-011CUxJZAmQGRCKf1KJXSEeD

echo "Copying CMS platform files..."
cd cms-platform
rm -rf .git 2>/dev/null || true

echo "Initializing new git repository..."
git init
git add .
git commit -m "Initial commit: Multi-Tenant Content Delivery Platform"

echo "Adding remote and pushing..."
git remote add origin https://github.com/peterpeeterspeter/CMSCLAUDECODEONLINE.git
git branch -M main
git push -u origin main

echo ""
echo "✅ SUCCESS! CMS Platform pushed to CMSCLAUDECODEONLINE"
echo ""
echo "Next steps:"
echo "1. Go to https://vercel.com"
echo "2. Click 'Add New' → 'Project'"
echo "3. Select repository: CMSCLAUDECODEONLINE"
echo "4. Set Root Directory: frontend"
echo "5. Framework: Vite"
echo "6. Add these environment variables:"
echo "   VITE_SUPABASE_URL=https://ambjsovdhizjxwhhnbtd.supabase.co"
echo "   VITE_SUPABASE_ANON_KEY=eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZSIsInJlZiI6ImFtYmpzb3ZkaGl6anh3aGhuYnRkIiwicm9sZSI6ImFub24iLCJpYXQiOjE3NDc2Mzc2NDYsImV4cCI6MjA2MzIxMzY0Nn0.3H8N2Fk22RAV1gHzDB5pCi9GokGwroG34v15I5Cq8_g"
echo "   VITE_SUPABASE_FUNCTION_URL=https://ambjsovdhizjxwhhnbtd.supabase.co/functions/v1"
echo "   VITE_INTERNAL_FUNCTION_TOKEN=13b5889f4d72a64297a264468914b8763e7de1260c5a1e1c3fdf088adbc4d670"
echo "7. Click 'Deploy'"

cd ../..
rm -rf temp-solidtime
