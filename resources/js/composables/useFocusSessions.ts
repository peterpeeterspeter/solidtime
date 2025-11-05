import { ref, computed, type Ref } from 'vue';
import { router } from '@inertiajs/vue3';

export interface FocusSession {
    id: string;
    user_id: string;
    organization_id: string;
    start_time: string;
    end_time: string;
    duration_minutes: number;
    app_switches: number;
    unique_apps_count: number;
    interruptions_count: number;
    focus_score: number;
    apps_used: string[];
    primary_app: string;
    focus_quality: 'excellent' | 'good' | 'fair' | 'poor';
    formatted_duration: string;
    created_at: string;
    updated_at: string;
}

export interface FocusStats {
    total_sessions: number;
    total_focus_minutes: number;
    total_focus_hours: number;
    average_duration_minutes: number;
    average_focus_score: number;
    deep_work_sessions: number;
    deep_work_percentage: number;
    top_focus_apps: Array<{
        app_name: string;
        session_count: number;
    }>;
}

export interface FocusHeatmapData {
    [date: string]: Array<number | null>;
}

export interface FocusStreaks {
    current_streak: number;
    longest_streak: number;
}

export interface ProductiveHours {
    [hour: string]: {
        session_count: number;
        average_score: number;
        total_minutes: number;
    };
}

export function useFocusSessions() {
    const sessions: Ref<FocusSession[]> = ref([]);
    const stats: Ref<FocusStats | null> = ref(null);
    const heatmap: Ref<FocusHeatmapData> = ref({});
    const streaks: Ref<FocusStreaks | null> = ref(null);
    const productiveHours: Ref<ProductiveHours> = ref({});
    const loading: Ref<boolean> = ref(false);
    const error: Ref<string | null> = ref(null);

    async function fetchSessions(startDate?: string, endDate?: string): Promise<void> {
        loading.value = true;
        error.value = null;

        try {
            const params = new URLSearchParams();
            if (startDate) params.append('start_date', startDate);
            if (endDate) params.append('end_date', endDate);

            const response = await fetch(`/api/v1/focus-sessions?${params.toString()}`, {
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                },
                credentials: 'include',
            });

            if (!response.ok) {
                throw new Error('Failed to fetch focus sessions');
            }

            const data = await response.json();
            sessions.value = data.data;
        } catch (err) {
            error.value = err instanceof Error ? err.message : 'An error occurred';
            console.error('Error fetching focus sessions:', err);
        } finally {
            loading.value = false;
        }
    }

    async function fetchStats(startDate?: string, endDate?: string): Promise<void> {
        loading.value = true;
        error.value = null;

        try {
            const params = new URLSearchParams();
            if (startDate) params.append('start_date', startDate);
            if (endDate) params.append('end_date', endDate);

            const response = await fetch(`/api/v1/focus-sessions/stats?${params.toString()}`, {
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                },
                credentials: 'include',
            });

            if (!response.ok) {
                throw new Error('Failed to fetch focus statistics');
            }

            const data = await response.json();
            stats.value = data.data;
        } catch (err) {
            error.value = err instanceof Error ? err.message : 'An error occurred';
            console.error('Error fetching focus stats:', err);
        } finally {
            loading.value = false;
        }
    }

    async function fetchHeatmap(startDate?: string, endDate?: string): Promise<void> {
        loading.value = true;
        error.value = null;

        try {
            const params = new URLSearchParams();
            if (startDate) params.append('start_date', startDate);
            if (endDate) params.append('end_date', endDate);

            const response = await fetch(`/api/v1/focus-sessions/heatmap?${params.toString()}`, {
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                },
                credentials: 'include',
            });

            if (!response.ok) {
                throw new Error('Failed to fetch heatmap data');
            }

            const data = await response.json();
            heatmap.value = data.data;
        } catch (err) {
            error.value = err instanceof Error ? err.message : 'An error occurred';
            console.error('Error fetching heatmap:', err);
        } finally {
            loading.value = false;
        }
    }

    async function fetchStreaks(): Promise<void> {
        loading.value = true;
        error.value = null;

        try {
            const response = await fetch('/api/v1/focus-sessions/streaks', {
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                },
                credentials: 'include',
            });

            if (!response.ok) {
                throw new Error('Failed to fetch streaks');
            }

            const data = await response.json();
            streaks.value = data.data;
        } catch (err) {
            error.value = err instanceof Error ? err.message : 'An error occurred';
            console.error('Error fetching streaks:', err);
        } finally {
            loading.value = false;
        }
    }

    async function fetchProductiveHours(startDate?: string, endDate?: string): Promise<void> {
        loading.value = true;
        error.value = null;

        try {
            const params = new URLSearchParams();
            if (startDate) params.append('start_date', startDate);
            if (endDate) params.append('end_date', endDate);

            const response = await fetch(`/api/v1/focus-sessions/productive-hours?${params.toString()}`, {
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                },
                credentials: 'include',
            });

            if (!response.ok) {
                throw new Error('Failed to fetch productive hours');
            }

            const data = await response.json();
            productiveHours.value = data.data;
        } catch (err) {
            error.value = err instanceof Error ? err.message : 'An error occurred';
            console.error('Error fetching productive hours:', err);
        } finally {
            loading.value = false;
        }
    }

    async function fetchDailySummary(date?: string): Promise<any> {
        loading.value = true;
        error.value = null;

        try {
            const params = new URLSearchParams();
            if (date) params.append('date', date);

            const response = await fetch(`/api/v1/focus-sessions/daily-summary?${params.toString()}`, {
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                },
                credentials: 'include',
            });

            if (!response.ok) {
                throw new Error('Failed to fetch daily summary');
            }

            const data = await response.json();
            return data.data;
        } catch (err) {
            error.value = err instanceof Error ? err.message : 'An error occurred';
            console.error('Error fetching daily summary:', err);
            return null;
        } finally {
            loading.value = false;
        }
    }

    async function detectSessions(date: string): Promise<boolean> {
        loading.value = true;
        error.value = null;

        try {
            const response = await fetch('/api/v1/focus-sessions/detect', {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                },
                credentials: 'include',
                body: JSON.stringify({ date }),
            });

            if (!response.ok) {
                throw new Error('Failed to detect focus sessions');
            }

            const data = await response.json();
            console.log(`Detected ${data.count} focus sessions`);
            return true;
        } catch (err) {
            error.value = err instanceof Error ? err.message : 'An error occurred';
            console.error('Error detecting sessions:', err);
            return false;
        } finally {
            loading.value = false;
        }
    }

    async function deleteSession(sessionId: string): Promise<boolean> {
        loading.value = true;
        error.value = null;

        try {
            const response = await fetch(`/api/v1/focus-sessions/${sessionId}`, {
                method: 'DELETE',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                },
                credentials: 'include',
            });

            if (!response.ok) {
                throw new Error('Failed to delete focus session');
            }

            // Remove from local state
            sessions.value = sessions.value.filter(s => s.id !== sessionId);
            return true;
        } catch (err) {
            error.value = err instanceof Error ? err.message : 'An error occurred';
            console.error('Error deleting session:', err);
            return false;
        } finally {
            loading.value = false;
        }
    }

    async function deleteSessionRange(startDate: string, endDate: string): Promise<boolean> {
        loading.value = true;
        error.value = null;

        try {
            const response = await fetch('/api/v1/focus-sessions', {
                method: 'DELETE',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                },
                credentials: 'include',
                body: JSON.stringify({ start_date: startDate, end_date: endDate }),
            });

            if (!response.ok) {
                throw new Error('Failed to delete focus sessions');
            }

            // Refresh sessions list
            await fetchSessions();
            return true;
        } catch (err) {
            error.value = err instanceof Error ? err.message : 'An error occurred';
            console.error('Error deleting session range:', err);
            return false;
        } finally {
            loading.value = false;
        }
    }

    const hasData = computed(() => sessions.value.length > 0);
    const totalSessions = computed(() => sessions.value.length);
    const averageScore = computed(() => {
        if (sessions.value.length === 0) return 0;
        const sum = sessions.value.reduce((acc, s) => acc + s.focus_score, 0);
        return Math.round(sum / sessions.value.length);
    });

    return {
        sessions,
        stats,
        heatmap,
        streaks,
        productiveHours,
        loading,
        error,
        hasData,
        totalSessions,
        averageScore,
        fetchSessions,
        fetchStats,
        fetchHeatmap,
        fetchStreaks,
        fetchProductiveHours,
        fetchDailySummary,
        detectSessions,
        deleteSession,
        deleteSessionRange,
    };
}
