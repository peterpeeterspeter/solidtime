import { ref, computed } from 'vue';
import axios from 'axios';
import type {
    PaymentGatewayConnection,
    PaymentGatewayType,
    PaymentIntentResponse
} from '@/types/payment';

export function usePaymentGateway() {
    const connections = ref<PaymentGatewayConnection[]>([]);
    const loading = ref(false);
    const error = ref<string | null>(null);

    /**
     * Fetch all payment gateway connections for current user
     */
    const fetchConnections = async (): Promise<void> => {
        loading.value = true;
        error.value = null;

        try {
            const response = await axios.get('/api/v1/payment-gateways');
            connections.value = response.data.data;
        } catch (err: any) {
            error.value = err.response?.data?.message || 'Failed to fetch payment gateways';
            console.error('Error fetching payment gateways:', err);
        } finally {
            loading.value = false;
        }
    };

    /**
     * Get authorization URL for connecting a payment gateway
     */
    const getAuthorizationUrl = async (
        gateway: PaymentGatewayType,
        redirectUri: string
    ): Promise<string> => {
        try {
            const response = await axios.post('/api/v1/payment-gateways/authorization-url', {
                gateway,
                redirect_uri: redirectUri
            });
            return response.data.authorization_url;
        } catch (err: any) {
            error.value = err.response?.data?.message || 'Failed to get authorization URL';
            throw err;
        }
    };

    /**
     * Handle OAuth callback and create connection
     */
    const handleCallback = async (
        gateway: PaymentGatewayType,
        code: string
    ): Promise<PaymentGatewayConnection> => {
        loading.value = true;
        error.value = null;

        try {
            const response = await axios.post('/api/v1/payment-gateways/callback', {
                gateway,
                code
            });
            const connection = response.data.data;
            connections.value.push(connection);
            return connection;
        } catch (err: any) {
            error.value = err.response?.data?.message || 'Failed to connect payment gateway';
            throw err;
        } finally {
            loading.value = false;
        }
    };

    /**
     * Disconnect a payment gateway
     */
    const disconnect = async (connectionId: string): Promise<boolean> => {
        loading.value = true;
        error.value = null;

        try {
            await axios.delete(`/api/v1/payment-gateways/${connectionId}`);
            connections.value = connections.value.filter(c => c.id !== connectionId);
            return true;
        } catch (err: any) {
            error.value = err.response?.data?.message || 'Failed to disconnect payment gateway';
            return false;
        } finally {
            loading.value = false;
        }
    };

    /**
     * Create a payment intent for an invoice
     */
    const createPaymentIntent = async (
        invoiceId: string,
        gateway: PaymentGatewayType
    ): Promise<PaymentIntentResponse> => {
        try {
            const response = await axios.post(`/api/v1/invoices/${invoiceId}/payment-intent`, {
                gateway
            });
            return response.data.data;
        } catch (err: any) {
            error.value = err.response?.data?.message || 'Failed to create payment intent';
            throw err;
        }
    };

    /**
     * Get active connection for a gateway type
     */
    const getActiveConnection = (gateway: PaymentGatewayType): PaymentGatewayConnection | null => {
        return connections.value.find(c => c.gateway === gateway && c.is_active) || null;
    };

    /**
     * Check if a gateway is connected
     */
    const isConnected = (gateway: PaymentGatewayType): boolean => {
        return connections.value.some(c => c.gateway === gateway && c.is_active);
    };

    /**
     * Get all active connections
     */
    const activeConnections = computed(() => {
        return connections.value.filter(c => c.is_active);
    });

    return {
        connections,
        activeConnections,
        loading,
        error,
        fetchConnections,
        getAuthorizationUrl,
        handleCallback,
        disconnect,
        createPaymentIntent,
        getActiveConnection,
        isConnected
    };
}
