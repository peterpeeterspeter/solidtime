/**
 * LLM Integration Module
 *
 * This module provides functions for interacting with various LLM providers
 * (OpenAI, Anthropic Claude, Google Gemini) for content rewriting.
 */

import OpenAI from 'npm:openai@4'

/**
 * Configuration for LLM rewrite request
 */
export interface RewriteConfig {
  text: string
  prompt: string
  model: string
  temperature?: number
  maxTokens?: number
}

/**
 * Rewrites text using OpenAI's API
 */
async function rewriteWithOpenAI(config: RewriteConfig): Promise<string> {
  const client = new OpenAI({
    apiKey: Deno.env.get('OPENAI_API_KEY'),
  })

  const completion = await client.chat.completions.create({
    model: config.model,
    temperature: config.temperature ?? 0.7,
    max_tokens: config.maxTokens ?? 4000,
    messages: [
      { role: 'system', content: config.prompt },
      { role: 'user', content: config.text },
    ],
  })

  return completion.choices?.[0]?.message?.content || ''
}

/**
 * Rewrites text using Anthropic's Claude API
 */
async function rewriteWithClaude(config: RewriteConfig): Promise<string> {
  const apiKey = Deno.env.get('ANTHROPIC_API_KEY')
  if (!apiKey) {
    throw new Error('ANTHROPIC_API_KEY not configured')
  }

  const response = await fetch('https://api.anthropic.com/v1/messages', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'x-api-key': apiKey,
      'anthropic-version': '2023-06-01',
    },
    body: JSON.stringify({
      model: config.model,
      max_tokens: config.maxTokens ?? 4000,
      temperature: config.temperature ?? 0.7,
      system: config.prompt,
      messages: [
        { role: 'user', content: config.text },
      ],
    }),
  })

  if (!response.ok) {
    const error = await response.text()
    throw new Error(`Claude API error: ${error}`)
  }

  const data = await response.json()
  return data.content?.[0]?.text || ''
}

/**
 * Rewrites text using Google's Gemini API
 */
async function rewriteWithGemini(config: RewriteConfig): Promise<string> {
  const apiKey = Deno.env.get('GOOGLE_API_KEY')
  if (!apiKey) {
    throw new Error('GOOGLE_API_KEY not configured')
  }

  const response = await fetch(
    `https://generativelanguage.googleapis.com/v1beta/models/${config.model}:generateContent?key=${apiKey}`,
    {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({
        contents: [
          {
            parts: [
              { text: `${config.prompt}\n\nContent to rewrite:\n${config.text}` },
            ],
          },
        ],
        generationConfig: {
          temperature: config.temperature ?? 0.7,
          maxOutputTokens: config.maxTokens ?? 4000,
        },
      }),
    }
  )

  if (!response.ok) {
    const error = await response.text()
    throw new Error(`Gemini API error: ${error}`)
  }

  const data = await response.json()
  return data.candidates?.[0]?.content?.parts?.[0]?.text || ''
}

/**
 * Main rewrite function that routes to the appropriate provider based on model
 */
export async function rewriteText(config: RewriteConfig): Promise<string> {
  const model = config.model.toLowerCase()

  try {
    // Route to appropriate provider based on model name
    if (model.includes('gpt') || model.includes('openai')) {
      return await rewriteWithOpenAI(config)
    } else if (model.includes('claude')) {
      return await rewriteWithClaude(config)
    } else if (model.includes('gemini')) {
      return await rewriteWithGemini(config)
    } else {
      // Default to OpenAI
      return await rewriteWithOpenAI(config)
    }
  } catch (error) {
    console.error('LLM rewrite error:', error)
    throw new Error(`Failed to rewrite content: ${error.message}`)
  }
}

/**
 * Validates that required API keys are configured
 */
export function validateApiKeys(model: string): void {
  const modelLower = model.toLowerCase()

  if ((modelLower.includes('gpt') || modelLower.includes('openai')) && !Deno.env.get('OPENAI_API_KEY')) {
    throw new Error('OPENAI_API_KEY is required for this model')
  }

  if (modelLower.includes('claude') && !Deno.env.get('ANTHROPIC_API_KEY')) {
    throw new Error('ANTHROPIC_API_KEY is required for this model')
  }

  if (modelLower.includes('gemini') && !Deno.env.get('GOOGLE_API_KEY')) {
    throw new Error('GOOGLE_API_KEY is required for this model')
  }
}
