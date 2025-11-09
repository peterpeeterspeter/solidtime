/**
 * Tenants API Module
 *
 * This module provides functions for managing tenants (WordPress sites)
 * in the CMS platform.
 */

import { supabase } from '../lib/supabaseClient'

export interface Tenant {
  id: string
  name: string
  slug: string
  wp_api_url: string
  wp_token: string
  brand_tone?: string
  llm_model?: string
  prompt_base?: string
  active: boolean
  created_at: string
  updated_at: string
}

export interface CreateTenantData {
  name: string
  slug: string
  wp_api_url: string
  wp_token: string
  brand_tone?: string
  llm_model?: string
  prompt_base?: string
  active?: boolean
}

export interface UpdateTenantData extends Partial<CreateTenantData> {}

/**
 * Fetch all tenants
 */
export async function getTenants(): Promise<Tenant[]> {
  const { data, error } = await supabase
    .from('tenants')
    .select('*')
    .order('name', { ascending: true })

  if (error) throw error
  return data || []
}

/**
 * Fetch a single tenant by ID
 */
export async function getTenant(id: string): Promise<Tenant | null> {
  const { data, error } = await supabase
    .from('tenants')
    .select('*')
    .eq('id', id)
    .single()

  if (error) throw error
  return data
}

/**
 * Fetch a tenant by slug
 */
export async function getTenantBySlug(slug: string): Promise<Tenant | null> {
  const { data, error } = await supabase
    .from('tenants')
    .select('*')
    .eq('slug', slug)
    .single()

  if (error) throw error
  return data
}

/**
 * Create a new tenant
 */
export async function createTenant(tenantData: CreateTenantData): Promise<Tenant> {
  const { data, error } = await supabase
    .from('tenants')
    .insert([tenantData])
    .select()
    .single()

  if (error) throw error
  return data
}

/**
 * Update an existing tenant
 */
export async function updateTenant(id: string, updates: UpdateTenantData): Promise<Tenant> {
  const { data, error } = await supabase
    .from('tenants')
    .update(updates)
    .eq('id', id)
    .select()
    .single()

  if (error) throw error
  return data
}

/**
 * Delete a tenant
 */
export async function deleteTenant(id: string): Promise<void> {
  const { error } = await supabase
    .from('tenants')
    .delete()
    .eq('id', id)

  if (error) throw error
}

/**
 * Toggle tenant active status
 */
export async function toggleTenantActive(id: string, active: boolean): Promise<Tenant> {
  return updateTenant(id, { active })
}

/**
 * Get active tenants only
 */
export async function getActiveTenants(): Promise<Tenant[]> {
  const { data, error } = await supabase
    .from('tenants')
    .select('*')
    .eq('active', true)
    .order('name', { ascending: true })

  if (error) throw error
  return data || []
}
