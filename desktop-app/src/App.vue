<template>
  <div class="app">
    <DesktopTimer v-if="isAuthenticated" />
    <AuthSetup v-else @authenticated="handleAuthenticated" />
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { invoke } from '@tauri-apps/api/tauri'
import DesktopTimer from './components/DesktopTimer.vue'
import AuthSetup from './components/AuthSetup.vue'

const isAuthenticated = ref(false)

onMounted(async () => {
  const state = await invoke('get_app_state')
  isAuthenticated.value = state.is_authenticated
})

const handleAuthenticated = () => {
  isAuthenticated.value = true
}
</script>

<style scoped>
.app {
  width: 100%;
  height: 100vh;
  display: flex;
  flex-direction: column;
}
</style>
