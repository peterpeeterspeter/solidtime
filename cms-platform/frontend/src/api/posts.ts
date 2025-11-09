/**
 * Posts API Module
 *
 * This module provides functions for managing posts and triggering
 * content operations (rewrite, publish, etc.)
 */

import { supabase, getFunctionUrl, getInternalToken } from '../lib/supabaseClient'

export type PostStatus = 'draft' | 'review' | 'published'

export interface Post {
  id: string
  tenant_id: string
  category_id?: string
  title: string
  slug: string
  raw_text?: string
  rewritten_text?: string
  affiliate_link?: string
  seo_title?: string
  seo_description?: string
  status: PostStatus
  created_at: string
  updated_at: string
}

export interface CreatePostData {
  tenant_id: string
  category_id?: string
  title: string
  slug: string
  raw_text?: string
  affiliate_link?: string
  seo_title?: string
  seo_description?: string
  status?: PostStatus
}

export interface UpdatePostData extends Partial<CreatePostData> {
  rewritten_text?: string
}

/**
 * Fetch all posts
 */
export async function getPosts(filters?: {
  tenantId?: string
  categoryId?: string
  status?: PostStatus
}): Promise<Post[]> {
  let query = supabase.from('posts').select('*').order('created_at', { ascending: false })

  if (filters?.tenantId) {
    query = query.eq('tenant_id', filters.tenantId)
  }
  if (filters?.categoryId) {
    query = query.eq('category_id', filters.categoryId)
  }
  if (filters?.status) {
    query = query.eq('status', filters.status)
  }

  const { data, error } = await query

  if (error) throw error
  return data || []
}

/**
 * Fetch a single post by ID
 */
export async function getPost(id: string): Promise<Post | null> {
  const { data, error } = await supabase
    .from('posts')
    .select('*')
    .eq('id', id)
    .single()

  if (error) throw error
  return data
}

/**
 * Create a new post
 */
export async function createPost(postData: CreatePostData): Promise<Post> {
  const { data, error } = await supabase
    .from('posts')
    .insert([postData])
    .select()
    .single()

  if (error) throw error
  return data
}

/**
 * Update an existing post
 */
export async function updatePost(id: string, updates: UpdatePostData): Promise<Post> {
  const { data, error } = await supabase
    .from('posts')
    .update(updates)
    .eq('id', id)
    .select()
    .single()

  if (error) throw error
  return data
}

/**
 * Delete a post
 */
export async function deletePost(id: string): Promise<void> {
  const { error } = await supabase
    .from('posts')
    .delete()
    .eq('id', id)

  if (error) throw error
}

/**
 * Trigger content rewrite for a post
 */
export async function rewritePost(
  postId: string,
  options?: { forceRewrite?: boolean }
): Promise<{ success: boolean; message: string }> {
  const response = await fetch(getFunctionUrl('rewrite_content'), {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'x-internal-token': getInternalToken(),
    },
    body: JSON.stringify({
      post_id: postId,
      force_rewrite: options?.forceRewrite || false,
    }),
  })

  if (!response.ok) {
    const error = await response.text()
    throw new Error(`Failed to rewrite content: ${error}`)
  }

  return await response.json()
}

/**
 * Inject affiliate links into post content
 */
export async function injectAffiliateLinks(
  postId: string,
  options?: {
    targetField?: 'raw_text' | 'rewritten_text'
    customLink?: string
    dryRun?: boolean
  }
): Promise<{ success: boolean; message: string; tokens_replaced?: number }> {
  const response = await fetch(getFunctionUrl('inject_affiliate'), {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'x-internal-token': getInternalToken(),
    },
    body: JSON.stringify({
      post_id: postId,
      target_field: options?.targetField || 'rewritten_text',
      custom_link: options?.customLink,
      dry_run: options?.dryRun || false,
    }),
  })

  if (!response.ok) {
    const error = await response.text()
    throw new Error(`Failed to inject affiliate links: ${error}`)
  }

  return await response.json()
}

/**
 * Publish post to WordPress
 */
export async function publishToWordPress(
  postId: string,
  tenantId: string,
  options?: {
    wpPostType?: string
    wpStatus?: string
    updateExisting?: boolean
  }
): Promise<{
  success: boolean
  message: string
  wp_post_id?: number
  wp_post_url?: string
}> {
  const response = await fetch(getFunctionUrl('publish_to_wp'), {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'x-internal-token': getInternalToken(),
    },
    body: JSON.stringify({
      post_id: postId,
      tenant_id: tenantId,
      wp_post_type: options?.wpPostType || 'posts',
      wp_status: options?.wpStatus || 'publish',
      update_existing: options?.updateExisting || false,
    }),
  })

  if (!response.ok) {
    const error = await response.text()
    throw new Error(`Failed to publish to WordPress: ${error}`)
  }

  return await response.json()
}

/**
 * Get content history for a post
 */
export async function getContentHistory(postId: string) {
  const { data, error } = await supabase
    .from('content_history')
    .select('*')
    .eq('parent_id', postId)
    .order('version_num', { ascending: false })

  if (error) throw error
  return data || []
}

/**
 * Get publish logs for a post
 */
export async function getPublishLogs(postId: string) {
  const { data, error } = await supabase
    .from('publish_log')
    .select('*')
    .eq('post_id', postId)
    .order('created_at', { ascending: false })

  if (error) throw error
  return data || []
}
