/**
 * Inject Affiliate Links Edge Function
 *
 * This function replaces {{AFFILIATE}} tokens in content with tenant-specific
 * affiliate links. It can process both raw and rewritten text.
 *
 * Request body:
 * {
 *   "post_id": "uuid",                    // ID of the post to process
 *   "target_field": "rewritten_text",     // Optional: which field to process (default: rewritten_text)
 *   "custom_link": "https://..."          // Optional: override affiliate link
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

interface AffiliateInjectionConfig {
  linkText?: string
  rel?: string
  target?: string
  cssClass?: string
}

/**
 * Replaces affiliate tokens in text with actual links
 */
function injectAffiliateLinks(
  text: string,
  affiliateLink: string,
  postTitle: string,
  config: AffiliateInjectionConfig = {}
): string {
  const {
    linkText = postTitle,
    rel = 'nofollow sponsored',
    target = '_blank',
    cssClass = 'affiliate-link'
  } = config

  // Handle different token formats
  const tokens = [
    '{{AFFILIATE}}',
    '{{affiliate}}',
    '{AFFILIATE}',
    '[AFFILIATE]'
  ]

  let processedText = text

  for (const token of tokens) {
    if (processedText.includes(token)) {
      const linkHtml = `<a href="${affiliateLink}" rel="${rel}" target="${target}" class="${cssClass}">${linkText}</a>`
      processedText = processedText.replaceAll(token, linkHtml)
    }
  }

  return processedText
}

/**
 * Extracts affiliate tokens to show where they would be replaced
 */
function findAffiliateTokens(text: string): string[] {
  const tokens: string[] = []
  const patterns = [
    /{{AFFILIATE}}/g,
    /{{affiliate}}/g,
    /{AFFILIATE}/g,
    /\[AFFILIATE\]/g
  ]

  for (const pattern of patterns) {
    const matches = text.match(pattern)
    if (matches) {
      tokens.push(...matches)
    }
  }

  return [...new Set(tokens)]
}

serve(async (req) => {
  // Handle CORS
  const corsResponse = handleCors(req)
  if (corsResponse) return corsResponse

  try {
    // Validate internal token
    requireInternalToken(req)

    // Parse request body
    const body = await req.json()
    const {
      post_id,
      target_field = 'rewritten_text',
      custom_link = null,
      dry_run = false,
      config = {}
    } = body

    if (!post_id) {
      return createErrorResponse('post_id is required', 400)
    }

    if (!['raw_text', 'rewritten_text'].includes(target_field)) {
      return createErrorResponse('target_field must be either raw_text or rewritten_text', 400)
    }

    console.log(`Processing affiliate injection for post: ${post_id}, field: ${target_field}`)

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

    // Get the text to process
    const textToProcess = post[target_field]
    if (!textToProcess || textToProcess.trim().length === 0) {
      const message = `No content in ${target_field} to process`
      console.error(message)
      return createErrorResponse(message, 400)
    }

    // Determine which affiliate link to use
    const affiliateLink = custom_link || post.affiliate_link
    if (!affiliateLink || affiliateLink.trim().length === 0) {
      const message = 'No affiliate link available'
      console.error(message)
      return createErrorResponse(message, 400)
    }

    // Find tokens in the text
    const tokensFound = findAffiliateTokens(textToProcess)
    console.log(`Found ${tokensFound.length} affiliate tokens: ${tokensFound.join(', ')}`)

    if (tokensFound.length === 0) {
      return createResponse({
        success: true,
        message: 'No affiliate tokens found in content',
        post_id: post.id,
        tokens_found: 0
      })
    }

    // Inject affiliate links
    const processedText = injectAffiliateLinks(
      textToProcess,
      affiliateLink,
      post.title,
      config
    )

    // If dry run, just return what would be changed
    if (dry_run) {
      return createResponse({
        success: true,
        message: 'Dry run - no changes made',
        post_id: post.id,
        tokens_found: tokensFound.length,
        tokens: tokensFound,
        original_length: textToProcess.length,
        processed_length: processedText.length,
        preview: processedText.substring(0, 500)
      })
    }

    // Update the post with processed text
    const { error: updateError } = await supabase
      .from('posts')
      .update({
        [target_field]: processedText,
        updated_at: new Date().toISOString()
      })
      .eq('id', post.id)

    if (updateError) {
      const message = `Failed to update post: ${updateError.message}`
      console.error(message, updateError)
      await logError(supabase, { postId: post_id, message })
      return createErrorResponse(message, 500)
    }

    console.log(`Successfully injected ${tokensFound.length} affiliate links`)

    return createResponse({
      success: true,
      message: 'Affiliate links injected successfully',
      post_id: post.id,
      tokens_replaced: tokensFound.length,
      tokens: tokensFound,
      field_updated: target_field,
      affiliate_link: affiliateLink
    })

  } catch (error) {
    console.error('Inject affiliate function error:', error)
    await logError(supabase, {
      message: `Inject affiliate function error: ${error.message}`
    })
    return createErrorResponse(error.message, 500)
  }
})
