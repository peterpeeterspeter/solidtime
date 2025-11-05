import { ref, computed } from 'vue';

// Onboarding step interface
export interface OnboardingStep {
    id: string;
    title: string;
    description: string;
    completed: boolean;
    skippable: boolean;
}

// Onboarding state interface
export interface OnboardingState {
    isActive: boolean;
    currentStep: number;
    steps: OnboardingStep[];
    completedAt?: string;
    skippedAt?: string;
}

// Default onboarding steps
const defaultSteps: OnboardingStep[] = [
    {
        id: 'welcome',
        title: 'Welcome to Timeclocker',
        description: 'Learn about time tracking made for Europe',
        completed: false,
        skippable: false
    },
    {
        id: 'workspace',
        title: 'Set Up Your Workspace',
        description: 'Configure your organization and preferences',
        completed: false,
        skippable: false
    },
    {
        id: 'first-project',
        title: 'Create Your First Project',
        description: 'Organize your time tracking with projects',
        completed: false,
        skippable: true
    },
    {
        id: 'tips',
        title: 'Quick Tips',
        description: 'Learn keyboard shortcuts and pro features',
        completed: false,
        skippable: true
    }
];

// Reactive state
const onboardingState = ref<OnboardingState>(loadOnboardingState());

// Computed
const isActive = computed(() => onboardingState.value.isActive);
const currentStep = computed(() => onboardingState.value.currentStep);
const steps = computed(() => onboardingState.value.steps);
const currentStepData = computed(() => steps.value[currentStep.value]);
const progress = computed(() => {
    const completed = steps.value.filter(s => s.completed).length;
    return Math.round((completed / steps.value.length) * 100);
});
const isFirstStep = computed(() => currentStep.value === 0);
const isLastStep = computed(() => currentStep.value === steps.value.length - 1);
const canGoNext = computed(() => !isLastStep.value);
const canGoPrevious = computed(() => !isFirstStep.value);

/**
 * Load onboarding state from localStorage
 */
function loadOnboardingState(): OnboardingState {
    try {
        const stored = localStorage.getItem('onboarding-state');
        if (stored) {
            const parsed = JSON.parse(stored);
            return {
                ...parsed,
                steps: parsed.steps || defaultSteps
            };
        }
    } catch (error) {
        console.error('Failed to load onboarding state:', error);
    }

    // Check if user has completed onboarding before
    const hasCompletedOnboarding = localStorage.getItem('onboarding-completed') === 'true';

    return {
        isActive: !hasCompletedOnboarding,
        currentStep: 0,
        steps: defaultSteps
    };
}

/**
 * Save onboarding state to localStorage
 */
function saveOnboardingState(state: OnboardingState): void {
    try {
        localStorage.setItem('onboarding-state', JSON.stringify(state));
        if (state.completedAt || state.skippedAt) {
            localStorage.setItem('onboarding-completed', 'true');
        }
    } catch (error) {
        console.error('Failed to save onboarding state:', error);
    }
}

/**
 * Onboarding Service
 * Manages the onboarding flow for new users
 */
export class OnboardingService {
    /**
     * Start onboarding
     */
    start(): void {
        onboardingState.value = {
            isActive: true,
            currentStep: 0,
            steps: defaultSteps.map(s => ({ ...s, completed: false }))
        };
        saveOnboardingState(onboardingState.value);
        console.log('[Onboarding] Started');
    }

    /**
     * Complete onboarding
     */
    complete(): void {
        onboardingState.value.isActive = false;
        onboardingState.value.completedAt = new Date().toISOString();
        onboardingState.value.steps = onboardingState.value.steps.map(s => ({
            ...s,
            completed: true
        }));
        saveOnboardingState(onboardingState.value);
        console.log('[Onboarding] Completed');
    }

    /**
     * Skip onboarding
     */
    skip(): void {
        onboardingState.value.isActive = false;
        onboardingState.value.skippedAt = new Date().toISOString();
        saveOnboardingState(onboardingState.value);
        console.log('[Onboarding] Skipped');
    }

    /**
     * Go to next step
     */
    nextStep(): boolean {
        if (!canGoNext.value) {
            return false;
        }

        // Mark current step as completed
        onboardingState.value.steps[currentStep.value].completed = true;
        onboardingState.value.currentStep++;
        saveOnboardingState(onboardingState.value);

        console.log(`[Onboarding] Advanced to step ${currentStep.value}`);
        return true;
    }

    /**
     * Go to previous step
     */
    previousStep(): boolean {
        if (!canGoPrevious.value) {
            return false;
        }

        onboardingState.value.currentStep--;
        saveOnboardingState(onboardingState.value);

        console.log(`[Onboarding] Went back to step ${currentStep.value}`);
        return true;
    }

    /**
     * Go to specific step
     */
    goToStep(stepIndex: number): boolean {
        if (stepIndex < 0 || stepIndex >= steps.value.length) {
            return false;
        }

        onboardingState.value.currentStep = stepIndex;
        saveOnboardingState(onboardingState.value);

        console.log(`[Onboarding] Jumped to step ${stepIndex}`);
        return true;
    }

    /**
     * Mark current step as completed
     */
    completeCurrentStep(): void {
        onboardingState.value.steps[currentStep.value].completed = true;
        saveOnboardingState(onboardingState.value);
        console.log(`[Onboarding] Completed step ${currentStep.value}`);
    }

    /**
     * Mark specific step as completed
     */
    completeStep(stepId: string): void {
        const stepIndex = steps.value.findIndex(s => s.id === stepId);
        if (stepIndex !== -1) {
            onboardingState.value.steps[stepIndex].completed = true;
            saveOnboardingState(onboardingState.value);
            console.log(`[Onboarding] Completed step: ${stepId}`);
        }
    }

    /**
     * Check if onboarding should be shown
     */
    shouldShow(): boolean {
        return isActive.value;
    }

    /**
     * Reset onboarding (for testing)
     */
    reset(): void {
        localStorage.removeItem('onboarding-state');
        localStorage.removeItem('onboarding-completed');
        onboardingState.value = {
            isActive: true,
            currentStep: 0,
            steps: defaultSteps.map(s => ({ ...s, completed: false }))
        };
        saveOnboardingState(onboardingState.value);
        console.log('[Onboarding] Reset');
    }

    /**
     * Get step by ID
     */
    getStepById(stepId: string): OnboardingStep | undefined {
        return steps.value.find(s => s.id === stepId);
    }

    /**
     * Check if step is completed
     */
    isStepCompleted(stepId: string): boolean {
        const step = this.getStepById(stepId);
        return step?.completed ?? false;
    }

    /**
     * Get completion percentage
     */
    getProgress(): number {
        return progress.value;
    }
}

// Export singleton instance
export const onboardingService = new OnboardingService();

// Export reactive state for components
export const useOnboarding = () => ({
    isActive,
    currentStep,
    steps,
    currentStepData,
    progress,
    isFirstStep,
    isLastStep,
    canGoNext,
    canGoPrevious
});

// Export helper functions
export const onboardingHelpers = {
    start: () => onboardingService.start(),
    complete: () => onboardingService.complete(),
    skip: () => onboardingService.skip(),
    nextStep: () => onboardingService.nextStep(),
    previousStep: () => onboardingService.previousStep(),
    goToStep: (index: number) => onboardingService.goToStep(index),
    completeCurrentStep: () => onboardingService.completeCurrentStep(),
    completeStep: (stepId: string) => onboardingService.completeStep(stepId),
    shouldShow: () => onboardingService.shouldShow(),
    reset: () => onboardingService.reset(),
    getProgress: () => onboardingService.getProgress()
};
