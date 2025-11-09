/**
 * Publish to WordPress Edge Function
 *
 * This function publishes content from the CMS to WordPress sites via REST API.
 * It handles authentication, error logging, and post status management.
 *
 * Request body:
 * {
 *   "post_id": "uuid",              // ID of the post to publish
 *   "tenant_id": "uuid",             // ID of the target tenant/site
 *   "wp_post_type": "post",          // Optional: WordPress post type (default: post)
 *   "wp_status": "publish",          // Optional: WordPress status (default: publish)
 *   "update_existing": true          // Optional: update if post already exists
 * }
 */

import { serve } from 'https://deno.land/std@0.168.0/http/server.ts'
import { supabase } from '../_shared/supabase.ts'
import {
  requireInternalToken,
  logError,
  logSuccess,
  createResponse,
  createErrorResponse,
  handleCors
} from '../_shared/utils.ts'

interface WordPressPost {
  title: string
  content: string
  status: string
  slug?: string
  meta?: Record<string, any>
  categories?: number[]
  tags?: number[]
}

interface PublishResult {
  success: boolean
  wp_post_id?: number
  wp_post_url?: string
  message: string
}

/**
 * Publishes a post to WordPress via REST API
 */
async function publishToWordPress(
  wpApiUrl: string,
  wpToken: string,
  postData: WordPressPost,
  postType: string = 'posts',
  updateExisting: boolean = false
): Promise<PublishResult> {
  // Clean up the API URL
  const baseUrl = wpApiUrl.replace(/\/$/, '')
  const endpoint = `${baseUrl}/wp-json/wp/v2/${postType}`

  console.log(`Publishing to WordPress: ${endpoint}`)

  // Prepare the request
  const requestBody = {
    title: postData.title,
    content: postData.content,
    status: postData.status,
    slug: postData.slug,
    meta: postData.meta || {},
    categories: postData.categories || [],
    tags: postData.tags || []
  }

  try {
    // Attempt to publish
    const response = await fetch(endpoint, {
      method: 'POST',
      headers: {
        'Authorization': `Bearer ${wpToken}`,
        'Content-Type': 'application/json',
      },
      body: JSON.stringify(requestBody)
    })

    const responseText = await response.text()
    let responseData: any

    try {
      responseData = JSON.parse(responseText)
    } catch {
      responseData = { message: responseText }
    }

    if (!response.ok) {
      // Check if it's a duplicate slug error
      if (response.status === 400 && responseData.code === 'rest_post_exists' && updateExisting) {
        console.log('Post exists, attempting to update...')
        // TODO: Implement update logic by fetching existing post ID
        return {
          success: false,
          message: `Post exists but update not implemented yet: ${responseData.message}`
        }
      }

      return {
        success: false,
        message: `WordPress API error (${response.status}): ${responseData.message || responseText}`
      }
    }

    return {
      success: true,
      wp_post_id: responseData.id,
      wp_post_url: responseData.link,
      message: 'Post published successfully'
    }

  } catch (error) {
    console.error('WordPress API request failed:', error)
    return {
      success: false,
      message: `Failed to connect to WordPress: ${error.message}`
    }
  }
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
      tenant_id,
      wp_post_type = 'posts',
      wp_status = 'publish',
      update_existing = false
    } = body

    if (!post_id || !tenant_id) {
      return createErrorResponse('post_id and tenant_id are required', 400)
    }

    console.log(`Publishing post ${post_id} to tenant ${tenant_id}`)

    // Fetch tenant details
    const { data: tenant, error: tenantError } = await supabase
      .from('tenants')
      .select('*')
      .eq('id', tenant_id)
      .single()

    if (tenantError || !tenant) {
      const message = `Tenant not found: ${tenant_id}`
      console.error(message, tenantError)
      await logError(supabase, { tenantId: tenant_id, postId: post_id, message })
      return createErrorResponse(message, 404)
    }

    if (!tenant.active) {
      const message = `Tenant is not active: ${tenant.name}`
      console.error(message)
      await logError(supabase, { tenantId: tenant_id, postId: post_id, message })
      return createErrorResponse(message, 400)
    }

    // Fetch post details
    const { data: post, error: postError } = await supabase
      .from('posts')
      .select('*')
      .eq('id', post_id)
      .single()

    if (postError || !post) {
      const message = `Post not found: ${post_id}`
      console.error(message, postError)
      await logError(supabase, { tenantId: tenant_id, postId: post_id, message })
      return createErrorResponse(message, 404)
    }

    // Verify post belongs to tenant
    if (post.tenant_id !== tenant_id) {
      const message = 'Post does not belong to specified tenant'
      console.error(message)
      await logError(supabase, { tenantId: tenant_id, postId: post_id, message })
      return createErrorResponse(message, 400)
    }

    // Determine which content to publish (prefer rewritten over raw)
    const contentToPublish = post.rewritten_text || post.raw_text
    if (!contentToPublish || contentToPublish.trim().length === 0) {
      const message = 'No content available to publish'
      console.error(message)
      await logError(supabase, { tenantId: tenant_id, postId: post_id, message })
      return createErrorResponse(message, 400)
    }

    // Prepare WordPress post data
    const wpPostData: WordPressPost = {
      title: post.title,
      content: contentToPublish,
      status: wp_status,
      slug: post.slug,
      meta: {
        seo_title: post.seo_title || post.title,
        seo_description: post.seo_description || '',
        affiliate_link: post.affiliate_link || ''
      }
    }

    // Publish to WordPress
    const result = await publishToWordPress(
      tenant.wp_api_url,
      tenant.wp_token,
      wpPostData,
      wp_post_type,
      update_existing
    )

    // Build target URL for logging
    const targetUrl = `${tenant.wp_api_url}/wp-json/wp/v2/${wp_post_type}`

    if (result.success) {
      // Log success
      await logSuccess(supabase, {
        tenantId: tenant_id,
        postId: post_id,
        targetUrl: result.wp_post_url || targetUrl,
        responseCode: 200,
        responseMessage: `Published successfully. WP Post ID: ${result.wp_post_id}`
      })

      // Update post status
      await supabase
        .from('posts')
        .update({
          status: 'published',
          updated_at: new Date().toISOString()
        })
        .eq('id', post_id)

      console.log(`Successfully published post ${post_id} to ${tenant.name}`)

      return createResponse({
        success: true,
        message: 'Post published successfully to WordPress',
        post_id: post.id,
        tenant_id: tenant_id,
        tenant_name: tenant.name,
        wp_post_id: result.wp_post_id,
        wp_post_url: result.wp_post_url
      })

    } else {
      // Log failure
      await logError(supabase, {
        tenantId: tenant_id,
        postId: post_id,
        targetUrl: targetUrl,
        message: result.message
      })

      console.error(`Failed to publish post ${post_id}:`, result.message)

      return createErrorResponse(result.message, 500)
    }

  } catch (error) {
    console.error('Publish function error:', error)
    await logError(supabase, {
      message: `Publish function error: ${error.message}`
    })
    return createErrorResponse(error.message, 500)
  }
})
