<template>
  <div class="desktop-timer">
    <div class="timer-header">
      <h2>Solidtime Desktop</h2>
      <button @click="openSettings" class="settings-btn" title="Settings">⚙️</button>
    </div>

    <div class="timer-display">
      <h1 class="time">{{ formattedTime }}</h1>
      <p class="task-description">{{ currentTask || 'No task running' }}</p>
    </div>

    <div class="controls">
      <button
        v-if="!isRunning"
        @click="startTimer"
        class="btn btn-start"
      >
        ▶ Start Timer
      </button>
      <button
        v-else
        @click="stopTimer"
        class="btn btn-stop"
      >
        ■ Stop Timer
      </button>
    </div>

    <div class="activity-info">
      <div class="activity-status">
        <span :class="['status-indicator', activityClass]"></span>
        <span class="status-text">{{ activityStatus }}</span>
      </div>

      <div class="sync-status">
        <span :class="['sync-indicator', syncClass]"></span>
        <span class="sync-text">{{ syncStatus }}</span>
      </div>
    </div>

    <div class="current-activity" v-if="currentActivity">
      <div class="activity-label">Current Activity:</div>
      <div class="activity-app">{{ currentActivity.app_name }}</div>
      <div class="activity-window" v-if="currentActivity.window_title">
        {{ truncate(currentActivity.window_title, 40) }}
      </div>
    </div>

    <div class="stats">
      <div class="stat">
        <div class="stat-value">{{ stats.todayHours }}</div>
        <div class="stat-label">Today</div>
      </div>
      <div class="stat">
        <div class="stat-value">{{ stats.weekHours }}</div>
        <div class="stat-label">This Week</div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { invoke } from '@tauri-apps/api/tauri'

const isRunning = ref(false)
const elapsedSeconds = ref(0)
const currentTask = ref('')
const activityStatus = ref('Idle')
const syncStatus = ref('Synced')
const currentActivity = ref(null)
const idleSeconds = ref(0)

const stats = ref({
  todayHours: '0h',
  weekHours: '0h',
})

const formattedTime = computed(() => {
  const hours = Math.floor(elapsedSeconds.value / 3600)
  const minutes = Math.floor((elapsedSeconds.value % 3600) / 60)
  const seconds = elapsedSeconds.value % 60

  return `${hours.toString().padStart(2, '0')}:${minutes
    .toString()
    .padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`
})

const activityClass = computed(() => {
  if (idleSeconds.value > 300) return 'idle' // 5 minutes
  if (idleSeconds.value > 60) return 'away' // 1 minute
  return 'active'
})

const syncClass = computed(() => {
  return syncStatus.value === 'Synced' ? 'synced' : 'syncing'
})

let timerInterval = null
let activityInterval = null

const startTimer = async () => {
  try {
    await invoke('start_timer')
    isRunning.value = true

    timerInterval = setInterval(() => {
      elapsedSeconds.value++
    }, 1000)
  } catch (error) {
    console.error('Failed to start timer:', error)
  }
}

const stopTimer = async () => {
  try {
    await invoke('stop_timer')
    isRunning.value = false
    elapsedSeconds.value = 0

    if (timerInterval) {
      clearInterval(timerInterval)
      timerInterval = null
    }
  } catch (error) {
    console.error('Failed to stop timer:', error)
  }
}

const collectActivity = async () => {
  try {
    const snapshot = await invoke('collect_activity')
    currentActivity.value = snapshot

    const idle = await invoke('get_idle_time')
    idleSeconds.value = idle

    if (idle < 60) {
      activityStatus.value = 'Active'
    } else if (idle < 300) {
      activityStatus.value = 'Away'
    } else {
      activityStatus.value = 'Idle'
    }

    // Sync if timer is running
    if (isRunning.value) {
      syncStatus.value = 'Syncing...'
      try {
        await invoke('sync_activity', { snapshot })
        syncStatus.value = 'Synced'
      } catch (error) {
        console.error('Failed to sync:', error)
        syncStatus.value = 'Offline'
      }
    }
  } catch (error) {
    console.error('Failed to collect activity:', error)
  }
}

const openSettings = () => {
  // TODO: Open settings window
  console.log('Settings clicked')
}

const truncate = (str, length) => {
  if (!str) return ''
  return str.length > length ? str.substring(0, length) + '...' : str
}

onMounted(async () => {
  // Start collecting activity every 10 seconds
  activityInterval = setInterval(collectActivity, 10000)

  // Initial collection
  await collectActivity()

  // Get app state
  try {
    const state = await invoke('get_app_state')
    isRunning.value = state.is_tracking

    if (isRunning.value) {
      timerInterval = setInterval(() => {
        elapsedSeconds.value++
      }, 1000)
    }
  } catch (error) {
    console.error('Failed to get app state:', error)
  }
})

onUnmounted(() => {
  if (timerInterval) clearInterval(timerInterval)
  if (activityInterval) clearInterval(activityInterval)
})
</script>

<style scoped>
.desktop-timer {
  display: flex;
  flex-direction: column;
  height: 100%;
  padding: 20px;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
}

.timer-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 20px;
}

.timer-header h2 {
  margin: 0;
  font-size: 18px;
  font-weight: 600;
}

.settings-btn {
  background: rgba(255, 255, 255, 0.2);
  border: none;
  color: white;
  font-size: 18px;
  width: 32px;
  height: 32px;
  border-radius: 50%;
  cursor: pointer;
  transition: background 0.2s;
}

.settings-btn:hover {
  background: rgba(255, 255, 255, 0.3);
}

.timer-display {
  text-align: center;
  margin: 30px 0;
}

.time {
  font-size: 48px;
  font-weight: 700;
  margin: 0;
  font-family: 'Courier New', monospace;
}

.task-description {
  font-size: 14px;
  opacity: 0.9;
  margin-top: 10px;
}

.controls {
  display: flex;
  justify-content: center;
  margin: 20px 0;
}

.btn {
  padding: 12px 32px;
  font-size: 16px;
  font-weight: 600;
  border: none;
  border-radius: 8px;
  cursor: pointer;
  transition: all 0.2s;
}

.btn-start {
  background: #10b981;
  color: white;
}

.btn-start:hover {
  background: #059669;
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(16, 185, 129, 0.4);
}

.btn-stop {
  background: #ef4444;
  color: white;
}

.btn-stop:hover {
  background: #dc2626;
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(239, 68, 68, 0.4);
}

.activity-info {
  display: flex;
  justify-content: space-between;
  padding: 15px;
  background: rgba(255, 255, 255, 0.1);
  border-radius: 8px;
  margin-bottom: 20px;
}

.activity-status,
.sync-status {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 14px;
}

.status-indicator,
.sync-indicator {
  width: 8px;
  height: 8px;
  border-radius: 50%;
}

.status-indicator.active {
  background: #10b981;
}

.status-indicator.away {
  background: #f59e0b;
}

.status-indicator.idle {
  background: #6b7280;
}

.sync-indicator.synced {
  background: #10b981;
}

.sync-indicator.syncing {
  background: #3b82f6;
  animation: pulse 1.5s ease-in-out infinite;
}

@keyframes pulse {
  0%, 100% {
    opacity: 1;
  }
  50% {
    opacity: 0.5;
  }
}

.current-activity {
  padding: 15px;
  background: rgba(255, 255, 255, 0.1);
  border-radius: 8px;
  margin-bottom: 20px;
}

.activity-label {
  font-size: 12px;
  opacity: 0.7;
  margin-bottom: 8px;
}

.activity-app {
  font-size: 14px;
  font-weight: 600;
  margin-bottom: 4px;
}

.activity-window {
  font-size: 12px;
  opacity: 0.8;
}

.stats {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 15px;
  margin-top: auto;
}

.stat {
  text-align: center;
  padding: 15px;
  background: rgba(255, 255, 255, 0.1);
  border-radius: 8px;
}

.stat-value {
  font-size: 24px;
  font-weight: 700;
}

.stat-label {
  font-size: 12px;
  opacity: 0.8;
  margin-top: 4px;
}
</style>
