/**
 * Supabase Client Configuration
 *
 * This module initializes and exports the Supabase client for use throughout
 * the frontend application.
 */

import { createClient } from '@supabase/supabase-js'

const supabaseUrl = import.meta.env.VITE_SUPABASE_URL
const supabaseAnonKey = import.meta.env.VITE_SUPABASE_ANON_KEY

if (!supabaseUrl || !supabaseAnonKey) {
  throw new Error('Missing Supabase environment variables. Please check your .env file.')
}

export const supabase = createClient(supabaseUrl, supabaseAnonKey)

// Helper function to get the function URL
export const getFunctionUrl = (functionName: string): string => {
  const baseUrl = import.meta.env.VITE_SUPABASE_FUNCTION_URL || `${supabaseUrl}/functions/v1`
  return `${baseUrl}/${functionName}`
}

// Helper function to get internal token for function calls
export const getInternalToken = (): string => {
  const token = import.meta.env.VITE_INTERNAL_FUNCTION_TOKEN
  if (!token) {
    throw new Error('Missing VITE_INTERNAL_FUNCTION_TOKEN environment variable')
  }
  return token
}
