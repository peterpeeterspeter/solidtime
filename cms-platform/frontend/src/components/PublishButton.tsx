/**
 * Publish Button Component
 *
 * A reusable button component for publishing posts to WordPress
 */

import React, { useState } from 'react'
import { publishToWordPress } from '../api/posts'

interface PublishButtonProps {
  postId: string
  tenantId: string
  postTitle?: string
  onSuccess?: (result: any) => void
  onError?: (error: Error) => void
  className?: string
}

export const PublishButton: React.FC<PublishButtonProps> = ({
  postId,
  tenantId,
  postTitle = 'this post',
  onSuccess,
  onError,
  className = ''
}) => {
  const [isPublishing, setIsPublishing] = useState(false)
  const [publishStatus, setPublishStatus] = useState<'idle' | 'success' | 'error'>('idle')

  const handlePublish = async () => {
    if (isPublishing) return

    const confirmed = window.confirm(
      `Are you sure you want to publish "${postTitle}" to WordPress?`
    )

    if (!confirmed) return

    setIsPublishing(true)
    setPublishStatus('idle')

    try {
      const result = await publishToWordPress(postId, tenantId)

      if (result.success) {
        setPublishStatus('success')
        onSuccess?.(result)

        // Show success message
        if (result.wp_post_url) {
          alert(`Published successfully!\nView at: ${result.wp_post_url}`)
        } else {
          alert('Published successfully!')
        }
      } else {
        throw new Error(result.message || 'Publish failed')
      }
    } catch (error) {
      setPublishStatus('error')
      const err = error instanceof Error ? error : new Error('Unknown error')
      onError?.(err)
      alert(`Failed to publish: ${err.message}`)
    } finally {
      setIsPublishing(false)
    }
  }

  const getButtonClass = () => {
    const baseClass = 'px-4 py-2 rounded font-medium transition-colors disabled:opacity-50 disabled:cursor-not-allowed'

    if (publishStatus === 'success') {
      return `${baseClass} bg-green-600 text-white hover:bg-green-700`
    }
    if (publishStatus === 'error') {
      return `${baseClass} bg-red-600 text-white hover:bg-red-700`
    }
    return `${baseClass} bg-blue-600 text-white hover:bg-blue-700`
  }

  const getButtonText = () => {
    if (isPublishing) return 'Publishing...'
    if (publishStatus === 'success') return 'Published ✓'
    if (publishStatus === 'error') return 'Failed - Retry?'
    return 'Publish to WordPress'
  }

  return (
    <button
      onClick={handlePublish}
      disabled={isPublishing}
      className={`${getButtonClass()} ${className}`}
    >
      {getButtonText()}
    </button>
  )
}
