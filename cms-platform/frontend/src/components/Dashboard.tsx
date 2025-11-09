/**
 * Dashboard Component
 *
 * Main dashboard showing overview of tenants and recent posts
 */

import React, { useEffect, useState } from 'react'
import { getTenants, Tenant } from '../api/tenants'
import { getPosts, Post } from '../api/posts'

export const Dashboard: React.FC = () => {
  const [tenants, setTenants] = useState<Tenant[]>([])
  const [recentPosts, setRecentPosts] = useState<Post[]>([])
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState<string | null>(null)

  useEffect(() => {
    loadDashboardData()
  }, [])

  const loadDashboardData = async () => {
    try {
      setLoading(true)
      setError(null)

      const [tenantsData, postsData] = await Promise.all([
        getTenants(),
        getPosts()
      ])

      setTenants(tenantsData)
      setRecentPosts(postsData.slice(0, 10)) // Show 10 most recent posts
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Failed to load dashboard data')
      console.error('Dashboard load error:', err)
    } finally {
      setLoading(false)
    }
  }

  if (loading) {
    return (
      <div className="flex items-center justify-center min-h-screen">
        <div className="text-xl">Loading dashboard...</div>
      </div>
    )
  }

  if (error) {
    return (
      <div className="flex items-center justify-center min-h-screen">
        <div className="text-red-600">
          <h2 className="text-xl font-bold mb-2">Error Loading Dashboard</h2>
          <p>{error}</p>
          <button
            onClick={loadDashboardData}
            className="mt-4 px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700"
          >
            Retry
          </button>
        </div>
      </div>
    )
  }

  const activeTenants = tenants.filter(t => t.active)
  const draftPosts = recentPosts.filter(p => p.status === 'draft').length
  const reviewPosts = recentPosts.filter(p => p.status === 'review').length
  const publishedPosts = recentPosts.filter(p => p.status === 'published').length

  return (
    <div className="max-w-7xl mx-auto px-4 py-8">
      <h1 className="text-3xl font-bold mb-8">Multi-Tenant CMS Dashboard</h1>

      {/* Stats Cards */}
      <div className="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
        <div className="bg-white p-6 rounded-lg shadow">
          <h3 className="text-gray-500 text-sm font-medium">Active Tenants</h3>
          <p className="text-3xl font-bold text-blue-600">{activeTenants.length}</p>
          <p className="text-sm text-gray-400 mt-1">of {tenants.length} total</p>
        </div>

        <div className="bg-white p-6 rounded-lg shadow">
          <h3 className="text-gray-500 text-sm font-medium">Draft Posts</h3>
          <p className="text-3xl font-bold text-gray-600">{draftPosts}</p>
        </div>

        <div className="bg-white p-6 rounded-lg shadow">
          <h3 className="text-gray-500 text-sm font-medium">Review Posts</h3>
          <p className="text-3xl font-bold text-yellow-600">{reviewPosts}</p>
        </div>

        <div className="bg-white p-6 rounded-lg shadow">
          <h3 className="text-gray-500 text-sm font-medium">Published Posts</h3>
          <p className="text-3xl font-bold text-green-600">{publishedPosts}</p>
        </div>
      </div>

      {/* Tenants Overview */}
      <div className="bg-white p-6 rounded-lg shadow mb-8">
        <h2 className="text-xl font-bold mb-4">Tenants</h2>
        {tenants.length === 0 ? (
          <p className="text-gray-500">No tenants configured yet.</p>
        ) : (
          <div className="space-y-2">
            {tenants.map((tenant) => (
              <div
                key={tenant.id}
                className="flex items-center justify-between p-3 border rounded hover:bg-gray-50"
              >
                <div>
                  <h3 className="font-medium">{tenant.name}</h3>
                  <p className="text-sm text-gray-500">{tenant.wp_api_url}</p>
                </div>
                <div className="flex items-center gap-4">
                  <span className="text-xs px-2 py-1 rounded bg-gray-100">
                    {tenant.llm_model || 'gpt-4'}
                  </span>
                  <span
                    className={`text-xs px-2 py-1 rounded ${
                      tenant.active
                        ? 'bg-green-100 text-green-800'
                        : 'bg-gray-100 text-gray-800'
                    }`}
                  >
                    {tenant.active ? 'Active' : 'Inactive'}
                  </span>
                </div>
              </div>
            ))}
          </div>
        )}
      </div>

      {/* Recent Posts */}
      <div className="bg-white p-6 rounded-lg shadow">
        <h2 className="text-xl font-bold mb-4">Recent Posts</h2>
        {recentPosts.length === 0 ? (
          <p className="text-gray-500">No posts yet.</p>
        ) : (
          <div className="space-y-2">
            {recentPosts.map((post) => (
              <div
                key={post.id}
                className="flex items-center justify-between p-3 border rounded hover:bg-gray-50"
              >
                <div className="flex-1">
                  <h3 className="font-medium">{post.title}</h3>
                  <p className="text-sm text-gray-500">
                    {new Date(post.created_at).toLocaleDateString()}
                  </p>
                </div>
                <div className="flex items-center gap-4">
                  <span
                    className={`text-xs px-2 py-1 rounded ${
                      post.status === 'published'
                        ? 'bg-green-100 text-green-800'
                        : post.status === 'review'
                        ? 'bg-yellow-100 text-yellow-800'
                        : 'bg-gray-100 text-gray-800'
                    }`}
                  >
                    {post.status}
                  </span>
                  {post.rewritten_text && (
                    <span className="text-xs px-2 py-1 rounded bg-blue-100 text-blue-800">
                      Rewritten
                    </span>
                  )}
                </div>
              </div>
            ))}
          </div>
        )}
      </div>
    </div>
  )
}
