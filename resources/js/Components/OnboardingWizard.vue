<template>
    <Teleport to="body">
        <Transition name="fade">
            <div
                v-if="isActive"
                class="fixed inset-0 z-[60] flex items-center justify-center p-4 bg-gradient-to-br from-cyan-900/95 via-cyan-800/95 to-blue-900/95 backdrop-blur-sm"
            >
                <!-- Wizard Container -->
                <div class="w-full max-w-4xl bg-white dark:bg-gray-800 rounded-2xl shadow-2xl overflow-hidden">
                    <!-- Progress Bar -->
                    <div class="h-2 bg-gray-200 dark:bg-gray-700">
                        <div
                            class="h-full bg-gradient-to-r from-cyan-500 to-cyan-600 transition-all duration-500 ease-out"
                            :style="{ width: `${progress}%` }"
                        ></div>
                    </div>

                    <!-- Header -->
                    <div class="px-8 pt-8 pb-4">
                        <div class="flex items-center justify-between">
                            <div>
                                <h2 class="text-2xl font-bold text-gray-900 dark:text-white">
                                    {{ currentStepData?.title }}
                                </h2>
                                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                                    Step {{ currentStep + 1 }} of {{ steps.length }}
                                </p>
                            </div>
                            <button
                                v-if="currentStepData?.skippable"
                                @click="handleSkip"
                                class="text-sm text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 transition-colors"
                            >
                                Skip for now
                            </button>
                        </div>
                    </div>

                    <!-- Step Content -->
                    <div class="px-8 py-6 min-h-[400px]">
                        <!-- Welcome Step -->
                        <OnboardingWelcome
                            v-if="currentStepData?.id === 'welcome'"
                            @next="handleNext"
                        />

                        <!-- Workspace Setup Step -->
                        <OnboardingWorkspace
                            v-else-if="currentStepData?.id === 'workspace'"
                            @next="handleNext"
                            @previous="handlePrevious"
                        />

                        <!-- First Project Step -->
                        <OnboardingFirstProject
                            v-else-if="currentStepData?.id === 'first-project'"
                            @next="handleNext"
                            @previous="handlePrevious"
                            @skip="handleSkipStep"
                        />

                        <!-- Tips Step -->
                        <OnboardingTips
                            v-else-if="currentStepData?.id === 'tips'"
                            @complete="handleComplete"
                            @previous="handlePrevious"
                        />
                    </div>

                    <!-- Footer -->
                    <div class="px-8 py-6 bg-gray-50 dark:bg-gray-900/50 border-t border-gray-200 dark:border-gray-700">
                        <div class="flex items-center justify-between">
                            <!-- Step Indicators -->
                            <div class="flex items-center gap-2">
                                <div
                                    v-for="(step, index) in steps"
                                    :key="step.id"
                                    class="w-2 h-2 rounded-full transition-all"
                                    :class="index === currentStep
                                        ? 'w-8 bg-cyan-600'
                                        : step.completed
                                            ? 'bg-cyan-400'
                                            : 'bg-gray-300 dark:bg-gray-600'"
                                ></div>
                            </div>

                            <!-- Progress Text -->
                            <div class="text-sm text-gray-600 dark:text-gray-400">
                                {{ progress }}% complete
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>

<script setup lang="ts">
import { onMounted } from 'vue';
import { useOnboarding, onboardingHelpers } from '@/utils/onboardingService';
import OnboardingWelcome from './OnboardingSteps/OnboardingWelcome.vue';
import OnboardingWorkspace from './OnboardingSteps/OnboardingWorkspace.vue';
import OnboardingFirstProject from './OnboardingSteps/OnboardingFirstProject.vue';
import OnboardingTips from './OnboardingSteps/OnboardingTips.vue';

const {
    isActive,
    currentStep,
    steps,
    currentStepData,
    progress
} = useOnboarding();

// Methods
const handleNext = () => {
    const success = onboardingHelpers.nextStep();
    if (!success && currentStep.value === steps.value.length - 1) {
        // Last step reached
        handleComplete();
    }
};

const handlePrevious = () => {
    onboardingHelpers.previousStep();
};

const handleSkipStep = () => {
    onboardingHelpers.completeCurrentStep();
    handleNext();
};

const handleSkip = () => {
    if (confirm('Are you sure you want to skip the onboarding? You can restart it later from your settings.')) {
        onboardingHelpers.skip();
    }
};

const handleComplete = () => {
    onboardingHelpers.complete();
    // Show completion message or redirect
};

// Check if onboarding should be shown
onMounted(() => {
    if (!onboardingHelpers.shouldShow()) {
        // Onboarding already completed
        return;
    }
});
</script>

<style scoped>
.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.3s ease;
}

.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}
</style>
