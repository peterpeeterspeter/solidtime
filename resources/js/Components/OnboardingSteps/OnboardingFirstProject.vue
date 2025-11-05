<template>
    <div class="space-y-6">
        <!-- Header -->
        <div class="text-center mb-8">
            <div class="w-16 h-16 bg-cyan-100 dark:bg-cyan-900/40 rounded-2xl flex items-center justify-center mx-auto mb-4">
                <svg class="w-8 h-8 text-cyan-600 dark:text-cyan-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z" />
                </svg>
            </div>
            <h3 class="text-2xl font-bold text-gray-900 dark:text-white mb-2">
                Create Your First Project
            </h3>
            <p class="text-gray-600 dark:text-gray-400">
                Projects help you organize time tracking by client or activity
            </p>
        </div>

        <!-- Skip Option -->
        <div class="max-w-2xl mx-auto mb-6">
            <div class="p-4 bg-gray-50 dark:bg-gray-900/50 border border-gray-200 dark:border-gray-700 rounded-lg">
                <p class="text-sm text-gray-600 dark:text-gray-400">
                    💡 <strong>Optional:</strong> You can skip this step and create projects later, or start tracking time without projects.
                </p>
            </div>
        </div>

        <!-- Form -->
        <form @submit.prevent="handleSubmit" class="space-y-6 max-w-2xl mx-auto">
            <!-- Project Name -->
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Project Name
                </label>
                <input
                    v-model="form.name"
                    type="text"
                    placeholder="e.g., Client Website, Personal Development"
                    class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-cyan-500 focus:border-cyan-500 dark:bg-gray-700 dark:text-white"
                />
            </div>

            <!-- Client Name -->
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Client Name (optional)
                </label>
                <input
                    v-model="form.clientName"
                    type="text"
                    placeholder="e.g., Acme Inc."
                    class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-cyan-500 focus:border-cyan-500 dark:bg-gray-700 dark:text-white"
                />
            </div>

            <!-- Project Color -->
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Project Color
                </label>
                <div class="flex flex-wrap gap-3">
                    <button
                        v-for="color in projectColors"
                        :key="color.value"
                        type="button"
                        @click="form.color = color.value"
                        class="w-12 h-12 rounded-lg border-2 transition-all hover:scale-110"
                        :class="form.color === color.value
                            ? 'border-gray-900 dark:border-white ring-2 ring-offset-2 ring-cyan-500'
                            : 'border-transparent'"
                        :style="{ backgroundColor: color.value }"
                        :title="color.name"
                    ></button>
                </div>
            </div>

            <!-- Billable Toggle -->
            <div class="flex items-center gap-3 p-4 bg-gray-50 dark:bg-gray-900/50 rounded-lg">
                <input
                    v-model="form.billable"
                    type="checkbox"
                    id="billable-project"
                    class="w-5 h-5 text-cyan-600 border-gray-300 rounded focus:ring-cyan-500"
                />
                <div>
                    <label for="billable-project" class="text-sm font-medium text-gray-700 dark:text-gray-300 cursor-pointer">
                        Billable Project
                    </label>
                    <p class="text-xs text-gray-500 dark:text-gray-400">
                        Track billable hours for client invoicing
                    </p>
                </div>
            </div>

            <!-- Quick Templates -->
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">
                    Or use a template:
                </label>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <button
                        v-for="template in templates"
                        :key="template.name"
                        type="button"
                        @click="applyTemplate(template)"
                        class="p-4 text-left border-2 border-gray-200 dark:border-gray-700 hover:border-cyan-400 rounded-lg transition-all group"
                    >
                        <div class="flex items-start gap-3">
                            <div
                                class="w-10 h-10 rounded-lg flex items-center justify-center flex-shrink-0"
                                :style="{ backgroundColor: template.color }"
                            >
                                <span class="text-white text-xl">{{ template.emoji }}</span>
                            </div>
                            <div>
                                <h4 class="font-medium text-gray-900 dark:text-white group-hover:text-cyan-600">
                                    {{ template.name }}
                                </h4>
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                    {{ template.description }}
                                </p>
                            </div>
                        </div>
                    </button>
                </div>
            </div>

            <!-- Error Message -->
            <div v-if="error" class="p-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg">
                <p class="text-sm text-red-800 dark:text-red-200">
                    {{ error }}
                </p>
            </div>

            <!-- Navigation Buttons -->
            <div class="flex items-center justify-between pt-6">
                <button
                    type="button"
                    @click="handlePrevious"
                    class="px-6 py-3 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors"
                >
                    ← Back
                </button>
                <div class="flex items-center gap-3">
                    <button
                        type="button"
                        @click="handleSkip"
                        class="px-6 py-3 text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100 transition-colors"
                    >
                        Skip for now
                    </button>
                    <button
                        type="submit"
                        :disabled="!form.name || loading"
                        class="px-8 py-3 bg-cyan-600 hover:bg-cyan-700 disabled:bg-gray-300 disabled:cursor-not-allowed text-white font-semibold rounded-lg transition-colors"
                    >
                        {{ loading ? 'Creating...' : 'Create Project →' }}
                    </button>
                </div>
            </div>
        </form>
    </div>
</template>

<script setup lang="ts">
import { ref } from 'vue';
import axios from 'axios';

const emit = defineEmits<{
    (e: 'next'): void;
    (e: 'previous'): void;
    (e: 'skip'): void;
}>();

const form = ref({
    name: '',
    clientName: '',
    color: '#06b6d4',
    billable: true
});

const projectColors = [
    { name: 'Cyan', value: '#06b6d4' },
    { name: 'Blue', value: '#3b82f6' },
    { name: 'Indigo', value: '#6366f1' },
    { name: 'Purple', value: '#a855f7' },
    { name: 'Pink', value: '#ec4899' },
    { name: 'Red', value: '#ef4444' },
    { name: 'Orange', value: '#f97316' },
    { name: 'Yellow', value: '#eab308' },
    { name: 'Green', value: '#22c55e' },
    { name: 'Teal', value: '#14b8a6' },
    { name: 'Gray', value: '#6b7280' }
];

const templates = [
    {
        name: 'Client Work',
        description: 'Billable client projects',
        emoji: '💼',
        color: '#06b6d4',
        billable: true
    },
    {
        name: 'Internal',
        description: 'Company internal projects',
        emoji: '🏢',
        color: '#6366f1',
        billable: false
    },
    {
        name: 'Personal',
        description: 'Personal development',
        emoji: '📚',
        color: '#22c55e',
        billable: false
    },
    {
        name: 'Consulting',
        description: 'Consulting services',
        emoji: '🤝',
        color: '#f97316',
        billable: true
    }
];

const loading = ref(false);
const error = ref('');

const applyTemplate = (template: typeof templates[0]) => {
    form.value.name = template.name;
    form.value.color = template.color;
    form.value.billable = template.billable;
};

const handleSubmit = async () => {
    if (!form.value.name) return;

    loading.value = true;
    error.value = '';

    try {
        await axios.post('/api/v1/projects', {
            name: form.value.name,
            client_name: form.value.clientName || null,
            color: form.value.color,
            is_billable: form.value.billable
        });

        emit('next');
    } catch (err: any) {
        console.error('Failed to create project:', err);
        error.value = err.response?.data?.message || 'Failed to create project. Please try again.';
    } finally {
        loading.value = false;
    }
};

const handlePrevious = () => {
    emit('previous');
};

const handleSkip = () => {
    emit('skip');
};
</script>
