#!/bin/bash

SUPABASE_URL="https://ambjsovdhizjxwhhnbtd.supabase.co"
SERVICE_KEY="eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZSIsInJlZiI6ImFtYmpzb3ZkaGl6anh3aGhuYnRkIiwicm9sZSI6InNlcnZpY2Vfcm9sZSIsImlhdCI6MTc0NzYzNzY0NiwiZXhwIjoyMDYzMjEzNjQ2fQ.ZSgK7qEdhCUkbAcAgeeDz23t-TrkX_m7H9O-WH5z5xs"

echo "Creating tables via Supabase API..."

# Enable UUID extension
curl -X POST "${SUPABASE_URL}/rest/v1/rpc/exec" \
  -H "apikey: ${SERVICE_KEY}" \
  -H "Authorization: Bearer ${SERVICE_KEY}" \
  -H "Content-Type: application/json" \
  -d '{"query": "CREATE EXTENSION IF NOT EXISTS \"uuid-ossp\""}' 2>&1

echo ""
echo "Migration execution attempted."
