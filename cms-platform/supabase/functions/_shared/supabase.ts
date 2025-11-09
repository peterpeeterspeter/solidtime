/**
 * Shared Supabase Client for Edge Functions
 *
 * This module provides a configured Supabase client with service role access
 * for use in Edge Functions that need full database access.
 */

import { createClient } from 'https://esm.sh/@supabase/supabase-js@2'

export const supabase = createClient(
  Deno.env.get('SUPABASE_URL')!,
  Deno.env.get('SUPABASE_SERVICE_ROLE_KEY')!
)
