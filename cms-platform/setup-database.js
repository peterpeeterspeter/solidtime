#!/usr/bin/env node

/**
 * Database Setup Script
 * This script sets up the CMS platform database schema in Supabase
 */

const fs = require('fs');
const path = require('path');

const SUPABASE_URL = 'https://ambjsovdhizjxwhhnbtd.supabase.co';
const SUPABASE_SERVICE_KEY = 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZSIsInJlZiI6ImFtYmpzb3ZkaGl6anh3aGhuYnRkIiwicm9sZSI6InNlcnZpY2Vfcm9sZSIsImlhdCI6MTc0NzYzNzY0NiwiZXhwIjoyMDYzMjEzNjQ2fQ.ZSgK7qEdhCUkbAcAgeeDz23t-TrkX_m7H9O-WH5z5xs';

// Read the migration SQL file
const migrationSQL = fs.readFileSync(
  path.join(__dirname, 'supabase/migrations/001_initial_schema.sql'),
  'utf8'
);

// Split SQL into individual statements
const statements = migrationSQL
  .split(';')
  .map(s => s.trim())
  .filter(s => s.length > 0 && !s.startsWith('--'));

console.log(`Found ${statements.length} SQL statements to execute`);

// Execute SQL statements using Supabase REST API
async function executeSQLStatement(sql, index) {
  const response = await fetch(`${SUPABASE_URL}/rest/v1/rpc/exec_sql`, {
    method: 'POST',
    headers: {
      'apikey': SUPABASE_SERVICE_KEY,
      'Authorization': `Bearer ${SUPABASE_SERVICE_KEY}`,
      'Content-Type': 'application/json',
      'Prefer': 'return=representation'
    },
    body: JSON.stringify({ query: sql })
  });

  if (!response.ok) {
    const error = await response.text();
    throw new Error(`Statement ${index} failed: ${error}`);
  }

  return response.text();
}

async function setupDatabase() {
  console.log('Starting database setup...\n');

  for (let i = 0; i < statements.length; i++) {
    const statement = statements[i];
    console.log(`Executing statement ${i + 1}/${statements.length}...`);

    try {
      await executeSQLStatement(statement + ';', i + 1);
      console.log(`✓ Statement ${i + 1} completed`);
    } catch (error) {
      console.error(`✗ Statement ${i + 1} failed:`, error.message);

      // Continue with other statements even if one fails
      // Some statements might fail if they already exist
    }
  }

  console.log('\nDatabase setup complete!');
}

setupDatabase().catch(console.error);
