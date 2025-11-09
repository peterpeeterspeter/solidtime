/**
 * Rewrite Content Edge Function
 *
 * This function rewrites post content using configured LLM models.
 * It can be triggered manually via API call or automatically via database trigger.
 *
 * Request body:
 * {
 *   "post_id": "uuid",           // ID of the post to rewrite
 *   "tenant_id": "uuid",          // Optional: tenant ID for validation
 *   "force_rewrite": boolean      // Optional: force rewrite even if already exists
 * }
 */

import { serve } from 'https://deno.land/std@0.168.0/http/server.ts'
import { supabase } from '../_shared/supabase.ts'
import {
  requireInternalToken,
  logError,
  createResponse,
  createErrorResponse,
  handleCors
} from '../_shared/utils.ts'
import { rewriteText, validateApiKeys } from '../_shared/llm.ts'

serve(async (req) => {
  // Handle CORS
  const corsResponse = handleCors(req)
  if (corsResponse) return corsResponse

  try {
    // Validate internal token for security
    requireInternalToken(req)

    // Parse request body
    const body = await req.json()
    const { post_id, tenant_id, force_rewrite = false } = body

    if (!post_id) {
      return createErrorResponse('post_id is required', 400)
    }

    console.log(`Rewriting content for post: ${post_id}`)

    // Fetch post details
    const { data: post, error: postError } = await supabase
      .from('posts')
      .select('*')
      .eq('id', post_id)
      .single()

    if (postError || !post) {
      const message = `Post not found: ${post_id}`
      console.error(message, postError)
      await logError(supabase, { postId: post_id, message })
      return createErrorResponse(message, 404)
    }

    // Validate tenant if provided
    if (tenant_id && post.tenant_id !== tenant_id) {
      return createErrorResponse('Post does not belong to specified tenant', 400)
    }

    // Check if rewrite is needed
    if (!force_rewrite && post.rewritten_text && post.rewritten_text.length > 0) {
      console.log('Post already has rewritten content, skipping')
      return createResponse({
        success: true,
        message: 'Post already rewritten',
        post_id: post.id
      })
    }

    // Check if raw text exists
    if (!post.raw_text || post.raw_text.trim().length === 0) {
      const message = 'No raw text available to rewrite'
      console.error(message)
      await logError(supabase, { postId: post_id, message })
      return createErrorResponse(message, 400)
    }

    // Fetch tenant configuration
    const { data: tenant, error: tenantError } = await supabase
      .from('tenants')
      .select('*')
      .eq('id', post.tenant_id)
      .single()

    if (tenantError || !tenant) {
      const message = `Tenant not found: ${post.tenant_id}`
      console.error(message, tenantError)
      await logError(supabase, { postId: post_id, tenantId: post.tenant_id, message })
      return createErrorResponse(message, 404)
    }

    if (!tenant.active) {
      const message = `Tenant is not active: ${tenant.name}`
      console.error(message)
      await logError(supabase, { postId: post_id, tenantId: tenant.id, message })
      return createErrorResponse(message, 400)
    }

    // Validate API keys for the selected model
    const model = tenant.llm_model || 'gpt-4'
    validateApiKeys(model)

    console.log(`Rewriting with model: ${model}`)

    // Perform the rewrite
    const rewrittenText = await rewriteText({
      text: post.raw_text,
      prompt: tenant.prompt_base || 'Rewrite this content professionally while maintaining the key information and intent.',
      model: model,
      temperature: 0.7,
      maxTokens: 4000,
    })

    if (!rewrittenText || rewrittenText.trim().length === 0) {
      const message = 'LLM returned empty content'
      console.error(message)
      await logError(supabase, { postId: post_id, tenantId: tenant.id, message })
      return createErrorResponse(message, 500)
    }

    console.log(`Successfully rewrote content (${rewrittenText.length} characters)`)

    // Get the current version number
    const { data: versions } = await supabase
      .from('content_history')
      .select('version_num')
      .eq('parent_id', post.id)
      .order('version_num', { ascending: false })
      .limit(1)

    const nextVersion = (versions && versions.length > 0) ? versions[0].version_num + 1 : 1

    // Update post with rewritten content
    const { error: updateError } = await supabase
      .from('posts')
      .update({
        rewritten_text: rewrittenText,
        status: 'review',
        updated_at: new Date().toISOString(),
      })
      .eq('id', post.id)

    if (updateError) {
      const message = `Failed to update post: ${updateError.message}`
      console.error(message, updateError)
      await logError(supabase, { postId: post_id, tenantId: tenant.id, message })
      return createErrorResponse(message, 500)
    }

    // Create content history entry
    const { error: historyError } = await supabase
      .from('content_history')
      .insert({
        parent_id: post.id,
        version_num: nextVersion,
        raw_text: post.raw_text,
        rewritten_text: rewrittenText,
        rewrite_model: model,
      })

    if (historyError) {
      console.error('Failed to create history entry:', historyError)
      // Non-fatal error, continue
    }

    console.log(`Content rewrite completed successfully for post ${post_id}`)

    return createResponse({
      success: true,
      message: 'Content rewritten successfully',
      post_id: post.id,
      version: nextVersion,
      model: model,
      content_length: rewrittenText.length,
    })

  } catch (error) {
    console.error('Rewrite function error:', error)
    await logError(supabase, {
      message: `Rewrite function error: ${error.message}`
    })
    return createErrorResponse(error.message, 500)
  }
})
