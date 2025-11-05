import { ref, type Ref } from 'vue';

/**
 * Team Analytics Composable
 *
 * Provides state management and API calls for organization-level focus analytics
 */

export interface TeamStats {
    total_team_sessions: number;
    total_team_focus_hours: number;
    average_team_focus_score: number;
    total_deep_work_sessions: number;
    deep_work_percentage: number;
    active_members: number;
    top_team_apps: TopTeamApp[];
    team_productivity_trend: ProductivityTrend[];
}

export interface TopTeamApp {
    app_name: string;
    session_count: number;
    percentage: number;
}

export interface ProductivityTrend {
    date: string;
    average_score: number;
    session_count: number;
}

export interface MemberRanking {
    member_name: string;
    total_sessions: number;
    total_focus_hours: number;
    average_focus_score: number;
    deep_work_sessions: number;
    rank: number;
}

export interface TeamHeatmapData {
    [date: string]: {
        [hour: number]: number | null;
    };
}

export interface ProductiveHour {
    hour: number;
    session_count: number;
    average_score: number;
    total_minutes: number;
}

export interface FocusDistribution {
    morning: {
        label: string;
        sessions: number;
        hours: number;
    };
    afternoon: {
        label: string;
        sessions: number;
        hours: number;
    };
    evening: {
        label: string;
        sessions: number;
        hours: number;
    };
    night: {
        label: string;
        sessions: number;
        hours: number;
    };
}

export interface TeamInsight {
    type: 'success' | 'warning' | 'tip' | 'info';
    title: string;
    message: string;
}

export function useTeamAnalytics() {
    const teamStats: Ref<TeamStats | null> = ref(null);
    const memberRankings: Ref<MemberRanking[]> = ref([]);
    const teamHeatmap: Ref<TeamHeatmapData> = ref({});
    const productiveHours: Ref<ProductiveHour[]> = ref([]);
    const focusDistribution: Ref<FocusDistribution | null> = ref(null);
    const insights: Ref<TeamInsight[]> = ref([]);
    const loading: Ref<boolean> = ref(false);
    const error: Ref<string | null> = ref(null);

    /**
     * Fetch team statistics for an organization
     */
    async function fetchTeamStats(
        organizationId: string,
        startDate?: string,
        endDate?: string
    ): Promise<void> {
        loading.value = true;
        error.value = null;

        try {
            const params = new URLSearchParams();
            if (startDate) params.append('start_date', startDate);
            if (endDate) params.append('end_date', endDate);

            const response = await fetch(
                `/api/v1/organizations/${organizationId}/team-analytics/stats?${params.toString()}`,
                {
                    headers: {
                        'Accept': 'application/json',
                    },
                    credentials: 'include',
                }
            );

            if (!response.ok) {
                throw new Error(`Failed to fetch team stats: ${response.statusText}`);
            }

            const data = await response.json();
            teamStats.value = data.data;
        } catch (err) {
            error.value = err instanceof Error ? err.message : 'Unknown error occurred';
            console.error('Error fetching team stats:', err);
        } finally {
            loading.value = false;
        }
    }

    /**
     * Fetch member rankings (anonymized leaderboard)
     */
    async function fetchMemberRankings(
        organizationId: string,
        startDate?: string,
        endDate?: string
    ): Promise<void> {
        loading.value = true;
        error.value = null;

        try {
            const params = new URLSearchParams();
            if (startDate) params.append('start_date', startDate);
            if (endDate) params.append('end_date', endDate);

            const response = await fetch(
                `/api/v1/organizations/${organizationId}/team-analytics/rankings?${params.toString()}`,
                {
                    headers: {
                        'Accept': 'application/json',
                    },
                    credentials: 'include',
                }
            );

            if (!response.ok) {
                throw new Error(`Failed to fetch rankings: ${response.statusText}`);
            }

            const data = await response.json();
            memberRankings.value = data.data;
        } catch (err) {
            error.value = err instanceof Error ? err.message : 'Unknown error occurred';
            console.error('Error fetching member rankings:', err);
        } finally {
            loading.value = false;
        }
    }

    /**
     * Fetch team activity heatmap
     */
    async function fetchTeamHeatmap(
        organizationId: string,
        startDate?: string,
        endDate?: string
    ): Promise<void> {
        loading.value = true;
        error.value = null;

        try {
            const params = new URLSearchParams();
            if (startDate) params.append('start_date', startDate);
            if (endDate) params.append('end_date', endDate);

            const response = await fetch(
                `/api/v1/organizations/${organizationId}/team-analytics/heatmap?${params.toString()}`,
                {
                    headers: {
                        'Accept': 'application/json',
                    },
                    credentials: 'include',
                }
            );

            if (!response.ok) {
                throw new Error(`Failed to fetch heatmap: ${response.statusText}`);
            }

            const data = await response.json();
            teamHeatmap.value = data.data;
        } catch (err) {
            error.value = err instanceof Error ? err.message : 'Unknown error occurred';
            console.error('Error fetching team heatmap:', err);
        } finally {
            loading.value = false;
        }
    }

    /**
     * Fetch team productive hours
     */
    async function fetchProductiveHours(
        organizationId: string,
        startDate?: string,
        endDate?: string
    ): Promise<void> {
        loading.value = true;
        error.value = null;

        try {
            const params = new URLSearchParams();
            if (startDate) params.append('start_date', startDate);
            if (endDate) params.append('end_date', endDate);

            const response = await fetch(
                `/api/v1/organizations/${organizationId}/team-analytics/productive-hours?${params.toString()}`,
                {
                    headers: {
                        'Accept': 'application/json',
                    },
                    credentials: 'include',
                }
            );

            if (!response.ok) {
                throw new Error(`Failed to fetch productive hours: ${response.statusText}`);
            }

            const data = await response.json();
            productiveHours.value = data.data;
        } catch (err) {
            error.value = err instanceof Error ? err.message : 'Unknown error occurred';
            console.error('Error fetching productive hours:', err);
        } finally {
            loading.value = false;
        }
    }

    /**
     * Fetch team focus distribution by time of day
     */
    async function fetchFocusDistribution(
        organizationId: string,
        startDate?: string,
        endDate?: string
    ): Promise<void> {
        loading.value = true;
        error.value = null;

        try {
            const params = new URLSearchParams();
            if (startDate) params.append('start_date', startDate);
            if (endDate) params.append('end_date', endDate);

            const response = await fetch(
                `/api/v1/organizations/${organizationId}/team-analytics/distribution?${params.toString()}`,
                {
                    headers: {
                        'Accept': 'application/json',
                    },
                    credentials: 'include',
                }
            );

            if (!response.ok) {
                throw new Error(`Failed to fetch distribution: ${response.statusText}`);
            }

            const data = await response.json();
            focusDistribution.value = data.data;
        } catch (err) {
            error.value = err instanceof Error ? err.message : 'Unknown error occurred';
            console.error('Error fetching focus distribution:', err);
        } finally {
            loading.value = false;
        }
    }

    /**
     * Fetch team insights and recommendations
     */
    async function fetchInsights(
        organizationId: string,
        startDate?: string,
        endDate?: string
    ): Promise<void> {
        loading.value = true;
        error.value = null;

        try {
            const params = new URLSearchParams();
            if (startDate) params.append('start_date', startDate);
            if (endDate) params.append('end_date', endDate);

            const response = await fetch(
                `/api/v1/organizations/${organizationId}/team-analytics/insights?${params.toString()}`,
                {
                    headers: {
                        'Accept': 'application/json',
                    },
                    credentials: 'include',
                }
            );

            if (!response.ok) {
                throw new Error(`Failed to fetch insights: ${response.statusText}`);
            }

            const data = await response.json();
            insights.value = data.data;
        } catch (err) {
            error.value = err instanceof Error ? err.message : 'Unknown error occurred';
            console.error('Error fetching insights:', err);
        } finally {
            loading.value = false;
        }
    }

    /**
     * Fetch all team analytics data at once
     */
    async function fetchAllTeamData(
        organizationId: string,
        startDate?: string,
        endDate?: string
    ): Promise<void> {
        loading.value = true;
        error.value = null;

        try {
            await Promise.all([
                fetchTeamStats(organizationId, startDate, endDate),
                fetchMemberRankings(organizationId, startDate, endDate),
                fetchTeamHeatmap(organizationId, startDate, endDate),
                fetchProductiveHours(organizationId, startDate, endDate),
                fetchFocusDistribution(organizationId, startDate, endDate),
                fetchInsights(organizationId, startDate, endDate),
            ]);
        } catch (err) {
            error.value = err instanceof Error ? err.message : 'Unknown error occurred';
            console.error('Error fetching all team data:', err);
        } finally {
            loading.value = false;
        }
    }

    /**
     * Reset all state
     */
    function reset(): void {
        teamStats.value = null;
        memberRankings.value = [];
        teamHeatmap.value = {};
        productiveHours.value = [];
        focusDistribution.value = null;
        insights.value = [];
        loading.value = false;
        error.value = null;
    }

    return {
        // State
        teamStats,
        memberRankings,
        teamHeatmap,
        productiveHours,
        focusDistribution,
        insights,
        loading,
        error,

        // Methods
        fetchTeamStats,
        fetchMemberRankings,
        fetchTeamHeatmap,
        fetchProductiveHours,
        fetchFocusDistribution,
        fetchInsights,
        fetchAllTeamData,
        reset,
    };
}
