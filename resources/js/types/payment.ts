/**
 * Payment Gateway Types
 */

export type PaymentGatewayType = 'stripe' | 'paypal';

export interface PaymentGatewayConnection {
    id: string;
    user_id: string;
    gateway: PaymentGatewayType;
    gateway_account_id: string | null;
    is_active: boolean;
    token_expires_at: string | null;
    metadata: Record<string, any> | null;
    created_at: string;
    updated_at: string;
}

/**
 * Invoice Types
 */

export type InvoiceStatus = 'draft' | 'sent' | 'paid' | 'overdue' | 'cancelled';

export interface InvoiceLineItem {
    description: string;
    quantity: number;
    unit_price: number;
    amount: number;
}

export interface InvoiceContactDetails {
    name: string;
    email?: string;
    address?: string;
    city?: string;
    state?: string;
    postal_code?: string;
    country?: string;
    tax_id?: string;
}

export interface Invoice {
    id: string;
    user_id: string;
    organization_id: string;
    client_id: string | null;
    invoice_number: string;
    status: InvoiceStatus;
    issue_date: string;
    due_date: string;
    sent_at: string | null;
    paid_at: string | null;
    subtotal: number;
    tax_rate: number;
    tax_amount: number;
    discount_amount: number;
    total: number;
    currency: string;
    from_details: InvoiceContactDetails;
    to_details: InvoiceContactDetails;
    line_items: InvoiceLineItem[];
    notes: string | null;
    terms: string | null;
    payment_method: string | null;
    payment_instructions: string | null;
    metadata: Record<string, any> | null;
    created_at: string;
    updated_at: string;
    deleted_at: string | null;
}

export interface CreateInvoiceRequest {
    client_id?: string;
    issue_date: string;
    due_date: string;
    from_details: InvoiceContactDetails;
    to_details: InvoiceContactDetails;
    line_items: InvoiceLineItem[];
    notes?: string;
    terms?: string;
    tax_rate?: number;
    discount_amount?: number;
    currency?: string;
}

export interface UpdateInvoiceRequest extends Partial<CreateInvoiceRequest> {
    status?: InvoiceStatus;
}

/**
 * Payment Types
 */

export type PaymentStatus =
    | 'pending'
    | 'processing'
    | 'completed'
    | 'failed'
    | 'refunded'
    | 'partially_refunded';

export interface Payment {
    id: string;
    invoice_id: string;
    user_id: string;
    organization_id: string;
    payment_gateway_connection_id: string | null;
    gateway: PaymentGatewayType | 'bank_transfer' | 'cash' | 'check' | 'other';
    gateway_transaction_id: string | null;
    gateway_payment_method_id: string | null;
    amount: number;
    currency: string;
    fee_amount: number;
    net_amount: number;
    status: PaymentStatus;
    status_message: string | null;
    paid_at: string | null;
    failed_at: string | null;
    refunded_at: string | null;
    refund_amount: number;
    refund_reason: string | null;
    refund_transaction_id: string | null;
    customer_details: Record<string, any> | null;
    gateway_response: Record<string, any> | null;
    metadata: Record<string, any> | null;
    created_at: string;
    updated_at: string;
    deleted_at: string | null;
}

export interface CreatePaymentRequest {
    invoice_id: string;
    gateway: PaymentGatewayType | 'bank_transfer' | 'cash' | 'check' | 'other';
    payment_gateway_connection_id?: string;
    gateway_payment_method_id?: string;
    amount: number;
    currency: string;
}

export interface RefundPaymentRequest {
    amount?: number;
    reason?: string;
}

/**
 * Recurring Invoice Schedule Types
 */

export type ScheduleFrequency =
    | 'daily'
    | 'weekly'
    | 'biweekly'
    | 'monthly'
    | 'quarterly'
    | 'biannually'
    | 'annually';

export type ScheduleStatus = 'active' | 'paused' | 'completed' | 'cancelled';

export type DueDateType = 'from_issue' | 'from_month_end' | 'from_month_start';

export interface RecurringInvoiceSchedule {
    id: string;
    user_id: string;
    organization_id: string;
    client_id: string | null;
    project_id: string | null;
    name: string;
    frequency: ScheduleFrequency;
    interval: number;
    day_of_month: number | null;
    day_of_week: number | null;
    start_date: string;
    end_date: string | null;
    next_generation_date: string;
    last_generated_date: string | null;
    max_occurrences: number | null;
    occurrences_count: number;
    status: ScheduleStatus;
    from_details: InvoiceContactDetails;
    to_details: InvoiceContactDetails;
    line_items: InvoiceLineItem[];
    notes: string | null;
    terms: string | null;
    subtotal: number;
    tax_rate: number;
    tax_amount: number;
    discount_amount: number;
    total: number;
    currency: string;
    due_days: number;
    due_date_type: DueDateType;
    auto_send: boolean;
    auto_charge: boolean;
    include_time_entries: boolean;
    time_entries_from_date: string | null;
    time_entries_to_date: string | null;
    notify_on_generation: boolean;
    notification_emails: string[] | null;
    metadata: Record<string, any> | null;
    created_at: string;
    updated_at: string;
    deleted_at: string | null;
}

export interface CreateRecurringScheduleRequest {
    client_id?: string;
    project_id?: string;
    name: string;
    frequency: ScheduleFrequency;
    interval?: number;
    day_of_month?: number;
    day_of_week?: number;
    start_date: string;
    end_date?: string;
    max_occurrences?: number;
    from_details: InvoiceContactDetails;
    to_details: InvoiceContactDetails;
    line_items: InvoiceLineItem[];
    notes?: string;
    terms?: string;
    tax_rate?: number;
    discount_amount?: number;
    currency?: string;
    due_days?: number;
    due_date_type?: DueDateType;
    auto_send?: boolean;
    auto_charge?: boolean;
    notify_on_generation?: boolean;
    notification_emails?: string[];
}

export interface UpdateRecurringScheduleRequest extends Partial<CreateRecurringScheduleRequest> {
    status?: ScheduleStatus;
}

/**
 * API Response Types
 */

export interface PaginatedResponse<T> {
    data: T[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
}

export interface PaymentIntentResponse {
    id: string;
    client_secret: string;
    amount: number;
    currency: string;
    status: string;
}
