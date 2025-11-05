import { ref, computed } from 'vue';
import axios from 'axios';
import type {
    RecurringInvoiceSchedule,
    ScheduleStatus,
    ScheduleFrequency,
    CreateRecurringScheduleRequest,
    UpdateRecurringScheduleRequest,
    InvoiceLineItem,
    PaginatedResponse
} from '@/types/payment';

export function useRecurringSchedules(organizationId: string) {
    const schedules = ref<RecurringInvoiceSchedule[]>([]);
    const currentSchedule = ref<RecurringInvoiceSchedule | null>(null);
    const loading = ref(false);
    const error = ref<string | null>(null);
    const pagination = ref({
        current_page: 1,
        last_page: 1,
        per_page: 15,
        total: 0
    });

    /**
     * Fetch recurring schedules with optional filtering
     */
    const fetchSchedules = async (params: {
        page?: number;
        per_page?: number;
        status?: ScheduleStatus | 'all';
        client_id?: string;
        frequency?: ScheduleFrequency;
    } = {}): Promise<void> => {
        loading.value = true;
        error.value = null;

        try {
            const response = await axios.get<PaginatedResponse<RecurringInvoiceSchedule>>(
                `/api/v1/organizations/${organizationId}/recurring-schedules`,
                { params }
            );

            schedules.value = response.data.data;
            pagination.value = {
                current_page: response.data.current_page,
                last_page: response.data.last_page,
                per_page: response.data.per_page,
                total: response.data.total
            };
        } catch (err: any) {
            error.value = err.response?.data?.message || 'Failed to fetch recurring schedules';
            console.error('Error fetching schedules:', err);
        } finally {
            loading.value = false;
        }
    };

    /**
     * Fetch a single schedule
     */
    const fetchSchedule = async (scheduleId: string): Promise<RecurringInvoiceSchedule | null> => {
        loading.value = true;
        error.value = null;

        try {
            const response = await axios.get<{ data: RecurringInvoiceSchedule }>(
                `/api/v1/organizations/${organizationId}/recurring-schedules/${scheduleId}`
            );
            currentSchedule.value = response.data.data;
            return response.data.data;
        } catch (err: any) {
            error.value = err.response?.data?.message || 'Failed to fetch schedule';
            console.error('Error fetching schedule:', err);
            return null;
        } finally {
            loading.value = false;
        }
    };

    /**
     * Create a new recurring schedule
     */
    const createSchedule = async (
        data: CreateRecurringScheduleRequest
    ): Promise<RecurringInvoiceSchedule | null> => {
        loading.value = true;
        error.value = null;

        try {
            // Calculate amounts
            const subtotal = data.line_items.reduce((sum, item) => sum + item.amount, 0);
            const taxAmount = subtotal * (data.tax_rate || 0) / 100;
            const total = subtotal + taxAmount - (data.discount_amount || 0);

            const response = await axios.post<{ data: RecurringInvoiceSchedule }>(
                `/api/v1/organizations/${organizationId}/recurring-schedules`,
                {
                    ...data,
                    subtotal,
                    tax_amount: taxAmount,
                    total
                }
            );

            const schedule = response.data.data;
            schedules.value.unshift(schedule);
            return schedule;
        } catch (err: any) {
            error.value = err.response?.data?.message || 'Failed to create recurring schedule';
            console.error('Error creating schedule:', err);
            return null;
        } finally {
            loading.value = false;
        }
    };

    /**
     * Update a recurring schedule
     */
    const updateSchedule = async (
        scheduleId: string,
        data: UpdateRecurringScheduleRequest
    ): Promise<RecurringInvoiceSchedule | null> => {
        loading.value = true;
        error.value = null;

        try {
            const response = await axios.put<{ data: RecurringInvoiceSchedule }>(
                `/api/v1/organizations/${organizationId}/recurring-schedules/${scheduleId}`,
                data
            );

            const schedule = response.data.data;
            const index = schedules.value.findIndex(s => s.id === scheduleId);
            if (index !== -1) {
                schedules.value[index] = schedule;
            }
            if (currentSchedule.value?.id === scheduleId) {
                currentSchedule.value = schedule;
            }
            return schedule;
        } catch (err: any) {
            error.value = err.response?.data?.message || 'Failed to update schedule';
            console.error('Error updating schedule:', err);
            return null;
        } finally {
            loading.value = false;
        }
    };

    /**
     * Delete a recurring schedule
     */
    const deleteSchedule = async (scheduleId: string): Promise<boolean> => {
        loading.value = true;
        error.value = null;

        try {
            await axios.delete(
                `/api/v1/organizations/${organizationId}/recurring-schedules/${scheduleId}`
            );
            schedules.value = schedules.value.filter(s => s.id !== scheduleId);
            return true;
        } catch (err: any) {
            error.value = err.response?.data?.message || 'Failed to delete schedule';
            console.error('Error deleting schedule:', err);
            return false;
        } finally {
            loading.value = false;
        }
    };

    /**
     * Pause a recurring schedule
     */
    const pauseSchedule = async (scheduleId: string): Promise<boolean> => {
        const result = await updateSchedule(scheduleId, { status: 'paused' });
        return result !== null;
    };

    /**
     * Resume a paused schedule
     */
    const resumeSchedule = async (scheduleId: string): Promise<boolean> => {
        const result = await updateSchedule(scheduleId, { status: 'active' });
        return result !== null;
    };

    /**
     * Cancel a recurring schedule
     */
    const cancelSchedule = async (scheduleId: string): Promise<boolean> => {
        const result = await updateSchedule(scheduleId, { status: 'cancelled' });
        return result !== null;
    };

    /**
     * Generate invoice manually from schedule
     */
    const generateInvoice = async (scheduleId: string): Promise<boolean> => {
        loading.value = true;
        error.value = null;

        try {
            await axios.post(
                `/api/v1/organizations/${organizationId}/recurring-schedules/${scheduleId}/generate`
            );
            return true;
        } catch (err: any) {
            error.value = err.response?.data?.message || 'Failed to generate invoice';
            console.error('Error generating invoice:', err);
            return false;
        } finally {
            loading.value = false;
        }
    };

    /**
     * Calculate next generation date
     */
    const calculateNextDate = (
        currentDate: Date,
        frequency: ScheduleFrequency,
        interval: number = 1
    ): Date => {
        const next = new Date(currentDate);

        switch (frequency) {
            case 'daily':
                next.setDate(next.getDate() + interval);
                break;
            case 'weekly':
                next.setDate(next.getDate() + (7 * interval));
                break;
            case 'biweekly':
                next.setDate(next.getDate() + (14 * interval));
                break;
            case 'monthly':
                next.setMonth(next.getMonth() + interval);
                break;
            case 'quarterly':
                next.setMonth(next.getMonth() + (3 * interval));
                break;
            case 'biannually':
                next.setMonth(next.getMonth() + (6 * interval));
                break;
            case 'annually':
                next.setFullYear(next.getFullYear() + interval);
                break;
        }

        return next;
    };

    /**
     * Get human-readable frequency label
     */
    const getFrequencyLabel = (frequency: ScheduleFrequency, interval: number = 1): string => {
        const labels: Record<ScheduleFrequency, string> = {
            daily: interval === 1 ? 'Daily' : `Every ${interval} days`,
            weekly: interval === 1 ? 'Weekly' : `Every ${interval} weeks`,
            biweekly: 'Bi-weekly',
            monthly: interval === 1 ? 'Monthly' : `Every ${interval} months`,
            quarterly: 'Quarterly',
            biannually: 'Bi-annually',
            annually: interval === 1 ? 'Annually' : `Every ${interval} years`
        };

        return labels[frequency];
    };

    /**
     * Calculate totals for schedule
     */
    const calculateTotals = (
        lineItems: InvoiceLineItem[],
        taxRate: number = 0,
        discountAmount: number = 0
    ) => {
        const subtotal = lineItems.reduce((sum, item) => sum + item.amount, 0);
        const taxAmount = (subtotal * taxRate) / 100;
        const total = subtotal + taxAmount - discountAmount;

        return {
            subtotal: parseFloat(subtotal.toFixed(2)),
            taxAmount: parseFloat(taxAmount.toFixed(2)),
            total: parseFloat(total.toFixed(2))
        };
    };

    /**
     * Get schedule statistics
     */
    const statistics = computed(() => {
        const active = schedules.value.filter(s => s.status === 'active').length;
        const paused = schedules.value.filter(s => s.status === 'paused').length;
        const completed = schedules.value.filter(s => s.status === 'completed').length;

        const totalRecurring = schedules.value
            .filter(s => s.status === 'active')
            .reduce((sum, s) => sum + s.total, 0);

        const dueToday = schedules.value.filter(s => {
            const nextDate = new Date(s.next_generation_date);
            const today = new Date();
            return (
                s.status === 'active' &&
                nextDate.toDateString() === today.toDateString()
            );
        }).length;

        return {
            active,
            paused,
            completed,
            totalRecurring,
            dueToday
        };
    });

    return {
        schedules,
        currentSchedule,
        loading,
        error,
        pagination,
        statistics,
        fetchSchedules,
        fetchSchedule,
        createSchedule,
        updateSchedule,
        deleteSchedule,
        pauseSchedule,
        resumeSchedule,
        cancelSchedule,
        generateInvoice,
        calculateNextDate,
        getFrequencyLabel,
        calculateTotals
    };
}
