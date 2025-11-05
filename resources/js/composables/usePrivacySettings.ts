import { ref, computed } from 'vue';
import axios from 'axios';

export interface PrivacySettings {
    id: string;
    user_id: string;
    tracking_level: number;
    tracking_level_label: string;
    tracking_level_description: string;
    screenshot_enabled: boolean;
    app_tracking_enabled: boolean;
    url_tracking_enabled: boolean;
    keyboard_mouse_tracking_enabled: boolean;
    geolocation_enabled: boolean;
    data_retention_days: number;
    encryption_enabled: boolean;
    created_at: string;
    updated_at: string;
}

export interface PrivacyConsentLog {
    id: string;
    user_id: string;
    setting_changed: string;
    old_value: string | null;
    new_value: string;
    reason: string | null;
    consented_at: string;
    ip_address: string | null;
    user_agent: string | null;
    created_at: string;
}

export interface DataCollectionStatus {
    manual_time_entries: boolean;
    idle_detection: boolean;
    app_names: boolean;
    urls: boolean;
    keyboard_mouse_activity: boolean;
    screenshots: boolean;
    geolocation: boolean;
}

export interface TrackingLevel {
    value: number;
    label: string;
    description: string;
    icon: string;
    badge?: string;
}

export const trackingLevels: TrackingLevel[] = [
    {
        value: 0,
        label: 'Manual Tracking',
        description: 'You control the timer. No automatic tracking.',
        icon: 'hand',
        badge: 'Default'
    },
    {
        value: 1,
        label: 'Idle Detection',
        description: 'Detects when you\'re active or idle.',
        icon: 'clock'
    },
    {
        value: 2,
        label: 'Activity Monitoring',
        description: 'Tracks apps and URLs (encrypted). No screenshots.',
        icon: 'eye'
    },
    {
        value: 3,
        label: 'Full Tracking',
        description: 'Includes screenshots and detailed activity logs.',
        icon: 'camera',
        badge: 'Advanced'
    }
];

/**
 * Composable for privacy settings management
 */
export function usePrivacySettings() {
    const settings = ref<PrivacySettings | null>(null);
    const consentHistory = ref<PrivacyConsentLog[]>([]);
    const dataCollectionStatus = ref<DataCollectionStatus | null>(null);
    const loading = ref(false);
    const error = ref<string | null>(null);

    /**
     * Fetch current privacy settings
     */
    const fetchSettings = async () => {
        loading.value = true;
        error.value = null;

        try {
            const response = await axios.get('/api/v1/users/me/privacy-settings');
            settings.value = response.data.data;
        } catch (err: any) {
            console.error('Failed to fetch privacy settings:', err);
            error.value = err.response?.data?.message || 'Failed to load privacy settings';
        } finally {
            loading.value = false;
        }
    };

    /**
     * Update privacy settings
     */
    const updateSettings = async (
        newSettings: Partial<Omit<PrivacySettings, 'id' | 'user_id' | 'tracking_level_label' | 'tracking_level_description' | 'created_at' | 'updated_at'>>,
        reason?: string
    ) => {
        loading.value = true;
        error.value = null;

        try {
            const payload = { ...newSettings };
            if (reason) {
                (payload as any).reason = reason;
            }

            const response = await axios.put('/api/v1/users/me/privacy-settings', payload);
            settings.value = response.data.data;
            return settings.value;
        } catch (err: any) {
            console.error('Failed to update privacy settings:', err);
            error.value = err.response?.data?.message || 'Failed to update privacy settings';
            throw err;
        } finally {
            loading.value = false;
        }
    };

    /**
     * Fetch consent history
     */
    const fetchConsentHistory = async () => {
        loading.value = true;
        error.value = null;

        try {
            const response = await axios.get('/api/v1/users/me/privacy-settings/consent-history');
            consentHistory.value = response.data.data || [];
        } catch (err: any) {
            console.error('Failed to fetch consent history:', err);
            error.value = err.response?.data?.message || 'Failed to load consent history';
        } finally {
            loading.value = false;
        }
    };

    /**
     * Fetch data collection status
     */
    const fetchDataCollectionStatus = async () => {
        loading.value = true;
        error.value = null;

        try {
            const response = await axios.get('/api/v1/users/me/privacy-settings/data-collection-status');
            dataCollectionStatus.value = response.data.data;
        } catch (err: any) {
            console.error('Failed to fetch data collection status:', err);
            error.value = err.response?.data?.message || 'Failed to load data collection status';
        } finally {
            loading.value = false;
        }
    };

    /**
     * Check if a feature can be enabled based on current tracking level
     */
    const canEnableFeature = computed(() => {
        return (feature: string): boolean => {
            if (!settings.value) return false;

            const level = settings.value.tracking_level;

            if (feature === 'screenshot_enabled') {
                return level >= 2; // Requires Monitoring level
            }

            if (feature === 'app_tracking_enabled' || feature === 'url_tracking_enabled') {
                return level >= 1; // Requires Idle Detection level
            }

            return true;
        };
    });

    /**
     * Get tracking level info by value
     */
    const getTrackingLevel = (value: number): TrackingLevel | undefined => {
        return trackingLevels.find(level => level.value === value);
    };

    /**
     * Get current tracking level info
     */
    const currentTrackingLevel = computed(() => {
        if (!settings.value) return trackingLevels[0];
        return getTrackingLevel(settings.value.tracking_level) || trackingLevels[0];
    });

    return {
        // State
        settings,
        consentHistory,
        dataCollectionStatus,
        loading,
        error,
        trackingLevels,

        // Computed
        canEnableFeature,
        currentTrackingLevel,

        // Methods
        fetchSettings,
        updateSettings,
        fetchConsentHistory,
        fetchDataCollectionStatus,
        getTrackingLevel
    };
}
