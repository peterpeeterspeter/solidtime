<script setup lang="ts">
import AppLayout from '@/Layouts/AppLayout.vue';
import AutomationDashboard from '@/Components/Automation/AutomationDashboard.vue';
import { usePage } from '@inertiajs/vue3';

const page = usePage();
const organizationId = (page.props.auth as any)?.user?.current_organization_id;
</script>

<template>
    <AppLayout title="Automation">
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-text-primary">
                Automation & Integrations
            </h2>
        </template>

        <div class="py-6">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <AutomationDashboard
                    v-if="organizationId"
                    :organization-id="organizationId"
                />
                <div v-else class="text-center py-12">
                    <p class="text-gray-500 dark:text-gray-400">
                        Please select an organization to view automation settings.
                    </p>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
