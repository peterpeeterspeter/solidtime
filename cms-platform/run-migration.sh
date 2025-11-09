#!/bin/bash

SUPABASE_URL="https://ambjsovdhizjxwhhnbtd.supabase.co"
SERVICE_KEY="eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZSIsInJlZiI6ImFtYmpzb3ZkaGl6anh3aGhuYnRkIiwicm9sZSI6InNlcnZpY2Vfcm9sZSIsImlhdCI6MTc0NzYzNzY0NiwiZXhwIjoyMDYzMjEzNjQ2fQ.ZSgK7qEdhCUkbAcAgeeDz23t-TrkX_m7H9O-WH5z5xs"

echo "Testing Supabase connection..."

# Try to query existing tables
curl -s "${SUPABASE_URL}/rest/v1/?apikey=${SERVICE_KEY}" \
  -H "Authorization: Bearer ${SERVICE_KEY}" \
  -H "Content-Type: application/json"

echo ""
echo "Response received"
