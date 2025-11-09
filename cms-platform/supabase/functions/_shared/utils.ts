/**
 * Shared Utility Functions for Edge Functions
 *
 * This module provides common utility functions used across all Edge Functions
 * including security checks, error logging, and response helpers.
 */

import { SupabaseClient } from 'https://esm.sh/@supabase/supabase-js@2'

/**
 * Validates the internal function token to ensure only authorized calls
 * Throws an error if token is missing or invalid
 */
export function requireInternalToken(req: Request): void {
  const token = req.headers.get('x-internal-token')
  const expectedToken = Deno.env.get('INTERNAL_FUNCTION_TOKEN')

  if (!token || token !== expectedToken) {
    throw new Error('Unauthorized: Invalid or missing internal token')
  }
}

/**
 * Logs an error to the publish_log table
 */
export async function logError(
  supabase: SupabaseClient,
  context: {
    tenantId?: string
    postId?: string
    targetUrl?: string
    message: string
  }
): Promise<void> {
  try {
    await supabase.from('publish_log').insert({
      tenant_id: context.tenantId || null,
      post_id: context.postId || null,
      target_url: context.targetUrl || 'internal-error',
      response_code: 500,
      response_message: context.message,
    })
  } catch (err) {
    console.error('Failed to log error:', err)
  }
}

/**
 * Logs a successful operation to the publish_log table
 */
export async function logSuccess(
  supabase: SupabaseClient,
  context: {
    tenantId: string
    postId: string
    targetUrl: string
    responseCode: number
    responseMessage: string
  }
): Promise<void> {
  try {
    await supabase.from('publish_log').insert({
      tenant_id: context.tenantId,
      post_id: context.postId,
      target_url: context.targetUrl,
      response_code: context.responseCode,
      response_message: context.responseMessage,
    })
  } catch (err) {
    console.error('Failed to log success:', err)
  }
}

/**
 * Creates a JSON response with CORS headers
 */
export function createResponse(
  data: any,
  status: number = 200
): Response {
  return new Response(
    JSON.stringify(data),
    {
      status,
      headers: {
        'Content-Type': 'application/json',
        'Access-Control-Allow-Origin': '*',
        'Access-Control-Allow-Headers': 'authorization, x-client-info, apikey, content-type, x-internal-token',
      },
    }
  )
}

/**
 * Creates an error response
 */
export function createErrorResponse(
  message: string,
  status: number = 500
): Response {
  return createResponse({ error: message }, status)
}

/**
 * Handles CORS preflight requests
 */
export function handleCors(req: Request): Response | null {
  if (req.method === 'OPTIONS') {
    return new Response('ok', {
      headers: {
        'Access-Control-Allow-Origin': '*',
        'Access-Control-Allow-Methods': 'POST, GET, OPTIONS',
        'Access-Control-Allow-Headers': 'authorization, x-client-info, apikey, content-type, x-internal-token',
      },
    })
  }
  return null
}
