<template>
    <div class="payment-gateway-connections">
        <div class="header">
            <h2>Payment Gateways</h2>
            <p>Connect your payment gateways to accept payments from clients</p>
        </div>

        <div v-if="error" class="error-message">
            {{ error }}
        </div>

        <div class="gateways-grid">
            <!-- Stripe Gateway -->
            <div class="gateway-card">
                <div class="gateway-header">
                    <div class="gateway-logo">
                        <svg viewBox="0 0 60 25" xmlns="http://www.w3.org/2000/svg" width="60" height="25">
                            <path fill="#635BFF" d="M59.64 14.28h-8.06c.19 1.93 1.6 2.55 3.2 2.55 1.64 0 2.96-.37 4.05-.95v3.32a8.33 8.33 0 0 1-4.56 1.1c-4.01 0-6.83-2.5-6.83-7.48 0-4.19 2.39-7.52 6.3-7.52 3.92 0 5.96 3.28 5.96 7.5 0 .4-.04 1.26-.06 1.48zm-5.92-5.62c-1.03 0-2.17.73-2.17 2.58h4.25c0-1.85-1.07-2.58-2.08-2.58zM40.95 20.3c-1.44 0-2.32-.6-2.9-1.04l-.02 4.63-4.12.87V5.57h3.76l.08 1.02a4.7 4.7 0 0 1 3.23-1.29c2.9 0 5.62 2.6 5.62 7.4 0 5.23-2.7 7.6-5.65 7.6zM40 8.95c-.95 0-1.54.34-1.97.81l.02 6.12c.4.44.98.78 1.95.78 1.52 0 2.54-1.65 2.54-3.87 0-2.15-1.04-3.84-2.54-3.84zM28.24 5.57h4.13v14.44h-4.13V5.57zm0-4.7L32.37 0v3.36l-4.13.88V.88zm-4.32 9.35v9.79H19.8V5.57h3.7l.12 1.22c1-1.77 3.07-1.41 3.62-1.22v3.79c-.52-.17-2.29-.43-3.32.86zm-8.55 4.72c0 2.43 2.6 1.68 3.12 1.46v3.36c-.55.3-1.54.54-2.89.54a4.15 4.15 0 0 1-4.27-4.24l.01-13.17 4.02-.86v3.54h3.14V9.1h-3.13v5.85zm-4.91.7c0 2.97-2.31 4.66-5.73 4.66a11.2 11.2 0 0 1-4.46-.93v-3.93c1.38.75 3.1 1.31 4.46 1.31.92 0 1.53-.24 1.53-1C6.26 13.77 0 14.51 0 9.95 0 7.04 2.28 5.3 5.62 5.3c1.36 0 2.72.2 4.09.75v3.88a9.23 9.23 0 0 0-4.1-1.06c-.86 0-1.44.25-1.44.93 0 1.85 6.29.97 6.29 5.88z"/>
                        </svg>
                    </div>
                    <div v-if="isConnected('stripe')" class="status-badge connected">
                        Connected
                    </div>
                </div>

                <p class="gateway-description">
                    Accept credit cards, digital wallets, and 100+ payment methods worldwide
                </p>

                <div v-if="isConnected('stripe')" class="connection-details">
                    <div class="detail-item">
                        <span class="label">Account ID:</span>
                        <span class="value">{{ getActiveConnection('stripe')?.gateway_account_id }}</span>
                    </div>
                    <div class="detail-item">
                        <span class="label">Connected:</span>
                        <span class="value">{{ formatDate(getActiveConnection('stripe')?.created_at) }}</span>
                    </div>
                </div>

                <div class="gateway-actions">
                    <button
                        v-if="!isConnected('stripe')"
                        @click="connectGateway('stripe')"
                        :disabled="loading"
                        class="btn btn-primary"
                    >
                        <span v-if="loading">Connecting...</span>
                        <span v-else>Connect Stripe</span>
                    </button>
                    <button
                        v-else
                        @click="disconnectGateway('stripe')"
                        :disabled="loading"
                        class="btn btn-secondary"
                    >
                        Disconnect
                    </button>
                </div>
            </div>

            <!-- PayPal Gateway -->
            <div class="gateway-card">
                <div class="gateway-header">
                    <div class="gateway-logo">
                        <svg viewBox="0 0 100 32" xmlns="http://www.w3.org/2000/svg" width="100" height="32">
                            <path fill="#003087" d="M12 4.917h-7.5c-.528 0-.982.383-1.07.903L.07 27.58c-.063.397.242.753.65.753h4.72l1.186-7.49-.037.23c.088-.521.54-.904 1.07-.904h2.23c4.38 0 7.81-1.77 8.81-6.89.03-.18.06-.36.08-.53-.11-.06-.11-.06 0 0 .3-1.99.01-3.34-.99-4.45-1.1-1.23-3.06-1.88-5.67-1.88z"/>
                            <path fill="#009cde" d="M35.16 12.22c-.3 2-.97 3.45-2.05 4.43-1.1 1-2.53 1.52-4.27 1.52h-1.08c-.42 0-.79.31-.86.73l-.91 5.76-.26 1.64c-.05.34.22.65.56.65h3.94c.46 0 .85-.33.93-.79l.04-.19.74-4.69.05-.25c.08-.46.47-.79.93-.79h.59c3.81 0 6.79-1.54 7.66-6 .36-1.86.17-3.41-.8-4.5-.29-.33-.64-.61-1.04-.84-.01.76-.1 1.59-.24 2.48z"/>
                            <path fill="#012169" d="M31.5 11.98c-.14-.04-.28-.07-.43-.1-.15-.03-.3-.05-.46-.07-.48-.06-.99-.09-1.53-.09h-4.63c-.14 0-.27.03-.4.09-.22.11-.39.32-.44.58l-1.05 6.66-.03.19c.07-.42.44-.73.86-.73h1.79c3.52 0 6.27-1.43 7.08-5.55.03-.14.05-.28.07-.41-.21-.11-.44-.21-.68-.29-.14-.04-.29-.08-.45-.11z"/>
                        </svg>
                    </div>
                    <div v-if="isConnected('paypal')" class="status-badge connected">
                        Connected
                    </div>
                </div>

                <p class="gateway-description">
                    Let customers pay with PayPal balance, credit cards, or Pay Later options
                </p>

                <div v-if="isConnected('paypal')" class="connection-details">
                    <div class="detail-item">
                        <span class="label">Account ID:</span>
                        <span class="value">{{ getActiveConnection('paypal')?.gateway_account_id }}</span>
                    </div>
                    <div class="detail-item">
                        <span class="label">Connected:</span>
                        <span class="value">{{ formatDate(getActiveConnection('paypal')?.created_at) }}</span>
                    </div>
                </div>

                <div class="gateway-actions">
                    <button
                        v-if="!isConnected('paypal')"
                        @click="connectGateway('paypal')"
                        :disabled="loading"
                        class="btn btn-primary"
                    >
                        <span v-if="loading">Connecting...</span>
                        <span v-else>Connect PayPal</span>
                    </button>
                    <button
                        v-else
                        @click="disconnectGateway('paypal')"
                        :disabled="loading"
                        class="btn btn-secondary"
                    >
                        Disconnect
                    </button>
                </div>
            </div>
        </div>

        <div class="help-section">
            <h3>Why connect payment gateways?</h3>
            <ul>
                <li>Accept payments directly from invoices</li>
                <li>Automatic payment status updates</li>
                <li>Support for multiple payment methods</li>
                <li>Secure OAuth connection - we never see your credentials</li>
                <li>Easy disconnection at any time</li>
            </ul>
        </div>
    </div>
</template>

<script setup lang="ts">
import { onMounted } from 'vue';
import { usePaymentGateway } from '@/composables/payment/usePaymentGateway';
import type { PaymentGatewayType } from '@/types/payment';

const {
    loading,
    error,
    fetchConnections,
    getAuthorizationUrl,
    disconnect,
    getActiveConnection,
    isConnected
} = usePaymentGateway();

onMounted(async () => {
    await fetchConnections();
});

const connectGateway = async (gateway: PaymentGatewayType) => {
    try {
        const redirectUri = `${window.location.origin}/payment-callback`;
        const authUrl = await getAuthorizationUrl(gateway, redirectUri);
        window.location.href = authUrl;
    } catch (err) {
        console.error('Failed to connect gateway:', err);
    }
};

const disconnectGateway = async (gateway: PaymentGatewayType) => {
    const connection = getActiveConnection(gateway);
    if (!connection) return;

    if (confirm(`Are you sure you want to disconnect ${gateway}?`)) {
        await disconnect(connection.id);
    }
};

const formatDate = (dateString?: string): string => {
    if (!dateString) return 'N/A';
    return new Date(dateString).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    });
};
</script>

<style scoped>
.payment-gateway-connections {
    max-width: 1200px;
    margin: 0 auto;
    padding: 2rem;
}

.header {
    margin-bottom: 2rem;
}

.header h2 {
    font-size: 1.875rem;
    font-weight: 600;
    margin-bottom: 0.5rem;
}

.header p {
    color: #6b7280;
}

.error-message {
    background-color: #fee2e2;
    border: 1px solid #fecaca;
    color: #991b1b;
    padding: 1rem;
    border-radius: 0.5rem;
    margin-bottom: 1.5rem;
}

.gateways-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 1.5rem;
    margin-bottom: 2rem;
}

.gateway-card {
    background: white;
    border: 1px solid #e5e7eb;
    border-radius: 0.75rem;
    padding: 1.5rem;
    transition: box-shadow 0.2s;
}

.gateway-card:hover {
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
}

.gateway-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1rem;
}

.gateway-logo {
    height: 32px;
}

.status-badge {
    padding: 0.25rem 0.75rem;
    border-radius: 9999px;
    font-size: 0.875rem;
    font-weight: 500;
}

.status-badge.connected {
    background-color: #d1fae5;
    color: #065f46;
}

.gateway-description {
    color: #6b7280;
    font-size: 0.875rem;
    margin-bottom: 1rem;
    line-height: 1.5;
}

.connection-details {
    background-color: #f9fafb;
    border-radius: 0.5rem;
    padding: 1rem;
    margin-bottom: 1rem;
}

.detail-item {
    display: flex;
    justify-content: space-between;
    margin-bottom: 0.5rem;
}

.detail-item:last-child {
    margin-bottom: 0;
}

.detail-item .label {
    font-weight: 500;
    color: #6b7280;
    font-size: 0.875rem;
}

.detail-item .value {
    color: #111827;
    font-size: 0.875rem;
}

.gateway-actions {
    margin-top: 1rem;
}

.btn {
    width: 100%;
    padding: 0.625rem 1rem;
    border-radius: 0.5rem;
    font-weight: 500;
    font-size: 0.875rem;
    cursor: pointer;
    transition: all 0.2s;
    border: none;
}

.btn:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

.btn-primary {
    background-color: #3b82f6;
    color: white;
}

.btn-primary:hover:not(:disabled) {
    background-color: #2563eb;
}

.btn-secondary {
    background-color: white;
    color: #6b7280;
    border: 1px solid #d1d5db;
}

.btn-secondary:hover:not(:disabled) {
    background-color: #f9fafb;
}

.help-section {
    background-color: #f0f9ff;
    border: 1px solid #bae6fd;
    border-radius: 0.75rem;
    padding: 1.5rem;
}

.help-section h3 {
    font-size: 1.125rem;
    font-weight: 600;
    margin-bottom: 1rem;
    color: #0c4a6e;
}

.help-section ul {
    list-style: none;
    padding: 0;
    margin: 0;
}

.help-section li {
    padding-left: 1.5rem;
    margin-bottom: 0.5rem;
    position: relative;
    color: #0c4a6e;
}

.help-section li::before {
    content: "✓";
    position: absolute;
    left: 0;
    color: #0284c7;
    font-weight: bold;
}
</style>
