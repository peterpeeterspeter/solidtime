<template>
  <div class="auth-setup">
    <div class="auth-container">
      <div class="logo">
        <h1>⏱️</h1>
        <h2>Solidtime Desktop</h2>
      </div>

      <div class="auth-form">
        <h3>Connect to Solidtime</h3>
        <p class="instructions">
          To get started, enter your API key from Solidtime. You can generate one at:
          <br />
          <strong>Settings → API Tokens</strong>
        </p>

        <div class="form-group">
          <label for="apiKey">API Key</label>
          <input
            id="apiKey"
            v-model="apiKey"
            type="password"
            placeholder="Enter your API key"
            @keyup.enter="connect"
            :disabled="isConnecting"
          />
        </div>

        <div class="form-group">
          <label for="apiUrl">API URL (Optional)</label>
          <input
            id="apiUrl"
            v-model="apiUrl"
            type="text"
            placeholder="https://api.solidtime.io"
            @keyup.enter="connect"
            :disabled="isConnecting"
          />
          <small>Only change if you're using a self-hosted instance</small>
        </div>

        <div v-if="error" class="error-message">
          {{ error }}
        </div>

        <button
          @click="connect"
          class="btn btn-primary"
          :disabled="!apiKey || isConnecting"
        >
          {{ isConnecting ? 'Connecting...' : 'Connect' }}
        </button>

        <div class="help-link">
          <a href="#" @click.prevent="openHelp">Need help?</a>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { invoke } from '@tauri-apps/api/tauri'
import { open } from '@tauri-apps/api/shell'

const emit = defineEmits(['authenticated'])

const apiKey = ref('')
const apiUrl = ref('https://api.solidtime.io')
const isConnecting = ref(false)
const error = ref('')

const connect = async () => {
  if (!apiKey.value) return

  isConnecting.value = true
  error.value = ''

  try {
    // Set credentials
    await invoke('set_api_credentials', {
      apiKey: apiKey.value,
      apiUrl: apiUrl.value || 'https://api.solidtime.io',
    })

    // TODO: Verify credentials with API
    // For now, assume success
    emit('authenticated')
  } catch (err) {
    error.value = 'Failed to connect. Please check your API key.'
    console.error('Connection error:', err)
  } finally {
    isConnecting.value = false
  }
}

const openHelp = async () => {
  await open('https://docs.solidtime.io/desktop-app/getting-started')
}
</script>

<style scoped>
.auth-setup {
  display: flex;
  align-items: center;
  justify-content: center;
  height: 100%;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  padding: 20px;
}

.auth-container {
  background: white;
  border-radius: 12px;
  padding: 40px;
  max-width: 400px;
  width: 100%;
  box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
}

.logo {
  text-align: center;
  margin-bottom: 30px;
}

.logo h1 {
  font-size: 48px;
  margin: 0;
}

.logo h2 {
  font-size: 24px;
  margin: 10px 0 0 0;
  color: #667eea;
}

.auth-form h3 {
  margin: 0 0 10px 0;
  color: #1f2937;
}

.instructions {
  color: #6b7280;
  font-size: 14px;
  margin-bottom: 24px;
  line-height: 1.5;
}

.form-group {
  margin-bottom: 20px;
}

.form-group label {
  display: block;
  margin-bottom: 8px;
  color: #374151;
  font-weight: 500;
  font-size: 14px;
}

.form-group input {
  width: 100%;
  padding: 12px;
  border: 2px solid #e5e7eb;
  border-radius: 8px;
  font-size: 14px;
  transition: border-color 0.2s;
}

.form-group input:focus {
  outline: none;
  border-color: #667eea;
}

.form-group input:disabled {
  background: #f3f4f6;
  cursor: not-allowed;
}

.form-group small {
  display: block;
  margin-top: 4px;
  color: #9ca3af;
  font-size: 12px;
}

.error-message {
  padding: 12px;
  background: #fee2e2;
  border: 1px solid #ef4444;
  border-radius: 8px;
  color: #dc2626;
  font-size: 14px;
  margin-bottom: 20px;
}

.btn {
  width: 100%;
  padding: 12px;
  font-size: 16px;
  font-weight: 600;
  border: none;
  border-radius: 8px;
  cursor: pointer;
  transition: all 0.2s;
}

.btn-primary {
  background: #667eea;
  color: white;
}

.btn-primary:hover:not(:disabled) {
  background: #5568d3;
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
}

.btn-primary:disabled {
  background: #9ca3af;
  cursor: not-allowed;
  transform: none;
}

.help-link {
  text-align: center;
  margin-top: 20px;
}

.help-link a {
  color: #667eea;
  text-decoration: none;
  font-size: 14px;
}

.help-link a:hover {
  text-decoration: underline;
}
</style>
