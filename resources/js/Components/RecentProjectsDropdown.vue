<template>
    <div class="recent-projects-dropdown relative" ref="dropdownRef">
        <!-- Trigger Button -->
        <button
            @click="toggleDropdown"
            class="flex items-center gap-2 px-3 py-2 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors"
            :class="{ 'bg-gray-100 dark:bg-gray-700': isOpen }"
        >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z" />
            </svg>
            <span class="text-sm font-medium">Projects</span>
            <svg
                class="w-4 h-4 transition-transform"
                :class="{ 'rotate-180': isOpen }"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
            >
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
            </svg>
        </button>

        <!-- Dropdown Menu -->
        <Transition name="dropdown">
            <div
                v-if="isOpen"
                class="absolute top-full mt-2 left-0 w-80 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg shadow-xl overflow-hidden z-50"
            >
                <!-- Search -->
                <div class="p-3 border-b border-gray-200 dark:border-gray-700">
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </div>
                        <input
                            ref="searchInput"
                            v-model="searchQuery"
                            type="text"
                            placeholder="Search projects..."
                            class="w-full pl-9 pr-3 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-cyan-500 focus:border-cyan-500 dark:bg-gray-700 dark:text-white"
                            @keydown.down.prevent="selectNext"
                            @keydown.up.prevent="selectPrevious"
                            @keydown.enter.prevent="selectCurrent"
                            @keydown.esc="closeDropdown"
                        />
                    </div>
                </div>

                <!-- Project List -->
                <div class="max-h-96 overflow-y-auto">
                    <!-- Loading State -->
                    <div v-if="loading" class="p-8 text-center">
                        <svg class="animate-spin h-8 w-8 mx-auto text-cyan-600" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Loading projects...</p>
                    </div>

                    <!-- No Results -->
                    <div v-else-if="filteredProjects.length === 0" class="p-8 text-center">
                        <svg class="w-12 h-12 mx-auto text-gray-300 dark:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z" />
                        </svg>
                        <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                            {{ searchQuery ? 'No projects found' : 'No recent projects' }}
                        </p>
                        <button
                            @click="createNewProject"
                            class="mt-3 text-sm text-cyan-600 dark:text-cyan-400 hover:underline"
                        >
                            Create a new project
                        </button>
                    </div>

                    <!-- Recent Projects -->
                    <div v-else-if="!searchQuery && recentProjects.length > 0" class="py-2">
                        <div class="px-3 py-2 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            Recent Projects
                        </div>
                        <button
                            v-for="(project, index) in recentProjects"
                            :key="project.id"
                            @click="selectProject(project)"
                            @mouseenter="selectedIndex = index"
                            class="w-full flex items-center gap-3 px-3 py-2 text-left transition-colors"
                            :class="selectedIndex === index
                                ? 'bg-cyan-50 dark:bg-cyan-900/30'
                                : 'hover:bg-gray-50 dark:hover:bg-gray-700/50'"
                        >
                            <!-- Color Dot -->
                            <div
                                class="flex-shrink-0 w-3 h-3 rounded-full"
                                :style="{ backgroundColor: project.color || '#06b6d4' }"
                            ></div>

                            <!-- Project Info -->
                            <div class="flex-1 min-w-0">
                                <div class="text-sm font-medium text-gray-900 dark:text-white truncate">
                                    {{ project.name }}
                                </div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">
                                    {{ project.client || 'No client' }}
                                </div>
                            </div>

                            <!-- Time Stats -->
                            <div class="flex-shrink-0 text-xs text-gray-500 dark:text-gray-400">
                                {{ formatTime(project.totalTime || 0) }}
                            </div>
                        </button>
                    </div>

                    <!-- All Projects / Search Results -->
                    <div v-else class="py-2">
                        <div class="px-3 py-2 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            {{ searchQuery ? 'Search Results' : 'All Projects' }}
                        </div>
                        <button
                            v-for="(project, index) in filteredProjects"
                            :key="project.id"
                            @click="selectProject(project)"
                            @mouseenter="selectedIndex = index"
                            class="w-full flex items-center gap-3 px-3 py-2 text-left transition-colors"
                            :class="selectedIndex === index
                                ? 'bg-cyan-50 dark:bg-cyan-900/30'
                                : 'hover:bg-gray-50 dark:hover:bg-gray-700/50'"
                        >
                            <!-- Color Dot -->
                            <div
                                class="flex-shrink-0 w-3 h-3 rounded-full"
                                :style="{ backgroundColor: project.color || '#06b6d4' }"
                            ></div>

                            <!-- Project Info -->
                            <div class="flex-1 min-w-0">
                                <div class="text-sm font-medium text-gray-900 dark:text-white truncate">
                                    {{ project.name }}
                                </div>
                                <div v-if="project.client" class="text-xs text-gray-500 dark:text-gray-400">
                                    {{ project.client }}
                                </div>
                            </div>
                        </button>
                    </div>
                </div>

                <!-- Footer -->
                <div class="border-t border-gray-200 dark:border-gray-700 p-2">
                    <button
                        @click="viewAllProjects"
                        class="w-full flex items-center justify-center gap-2 px-3 py-2 text-sm font-medium text-cyan-600 dark:text-cyan-400 hover:bg-gray-50 dark:hover:bg-gray-700/50 rounded-lg transition-colors"
                    >
                        View all projects
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                        </svg>
                    </button>
                </div>
            </div>
        </Transition>
    </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted, nextTick, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import axios from 'axios';

// Interfaces
interface Project {
    id: string;
    name: string;
    color?: string;
    client?: string;
    totalTime?: number;
    lastUsed?: string;
}

// State
const isOpen = ref(false);
const loading = ref(false);
const searchQuery = ref('');
const selectedIndex = ref(0);
const allProjects = ref<Project[]>([]);
const recentProjects = ref<Project[]>([]);
const dropdownRef = ref<HTMLElement | null>(null);
const searchInput = ref<HTMLInputElement | null>(null);

// Computed
const filteredProjects = computed(() => {
    if (!searchQuery.value) {
        return allProjects.value;
    }

    const query = searchQuery.value.toLowerCase();
    return allProjects.value.filter(project =>
        project.name.toLowerCase().includes(query) ||
        project.client?.toLowerCase().includes(query)
    );
});

// Methods
const toggleDropdown = () => {
    if (isOpen.value) {
        closeDropdown();
    } else {
        openDropdown();
    }
};

const openDropdown = async () => {
    isOpen.value = true;
    await fetchProjects();
    nextTick(() => {
        searchInput.value?.focus();
    });
};

const closeDropdown = () => {
    isOpen.value = false;
    searchQuery.value = '';
    selectedIndex.value = 0;
};

const fetchProjects = async () => {
    loading.value = true;
    try {
        // Fetch all projects
        const response = await axios.get('/api/v1/projects');
        allProjects.value = response.data.data || [];

        // Get recent projects from localStorage
        const recentIds = JSON.parse(localStorage.getItem('recent-projects') || '[]');
        recentProjects.value = recentIds
            .map((id: string) => allProjects.value.find(p => p.id === id))
            .filter(Boolean)
            .slice(0, 5);
    } catch (error) {
        console.error('Failed to fetch projects:', error);
    } finally {
        loading.value = false;
    }
};

const selectProject = (project: Project) => {
    // Update recent projects
    const recentIds = JSON.parse(localStorage.getItem('recent-projects') || '[]');
    const newRecent = [
        project.id,
        ...recentIds.filter((id: string) => id !== project.id)
    ].slice(0, 10);
    localStorage.setItem('recent-projects', JSON.stringify(newRecent));

    // Navigate to project
    router.visit(`/projects/${project.id}`);
    closeDropdown();
};

const selectNext = () => {
    const maxIndex = (searchQuery.value ? filteredProjects.value : recentProjects.value).length - 1;
    selectedIndex.value = Math.min(selectedIndex.value + 1, maxIndex);
};

const selectPrevious = () => {
    selectedIndex.value = Math.max(selectedIndex.value - 1, 0);
};

const selectCurrent = () => {
    const projects = searchQuery.value ? filteredProjects.value : recentProjects.value;
    const project = projects[selectedIndex.value];
    if (project) {
        selectProject(project);
    }
};

const viewAllProjects = () => {
    router.visit('/projects');
    closeDropdown();
};

const createNewProject = () => {
    router.visit('/projects?action=create');
    closeDropdown();
};

const formatTime = (minutes: number): string => {
    const hours = Math.floor(minutes / 60);
    const mins = minutes % 60;

    if (hours > 0) {
        return `${hours}h ${mins}m`;
    }
    return `${mins}m`;
};

// Close dropdown when clicking outside
const handleClickOutside = (event: MouseEvent) => {
    if (dropdownRef.value && !dropdownRef.value.contains(event.target as Node)) {
        closeDropdown();
    }
};

// Watch for search query changes
watch(searchQuery, () => {
    selectedIndex.value = 0;
});

// Lifecycle
onMounted(() => {
    document.addEventListener('click', handleClickOutside);
});

onUnmounted(() => {
    document.removeEventListener('click', handleClickOutside);
});
</script>

<style scoped>
.dropdown-enter-active,
.dropdown-leave-active {
    transition: all 0.2s ease;
}

.dropdown-enter-from {
    opacity: 0;
    transform: translateY(-8px);
}

.dropdown-leave-to {
    opacity: 0;
    transform: translateY(-4px);
}
</style>
