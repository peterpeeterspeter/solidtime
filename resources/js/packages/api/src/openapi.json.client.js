"use strict";
Object.defineProperty(exports, "__esModule", { value: true });
exports.api = exports.schemas = void 0;
exports.createApiClient = createApiClient;
var core_1 = require("@zodios/core");
var zod_1 = require("zod");
var ApiTokenResource = zod_1.z
    .object({
    id: zod_1.z.string(),
    name: zod_1.z.string(),
    revoked: zod_1.z.boolean(),
    scopes: zod_1.z.array(zod_1.z.string()),
    created_at: zod_1.z.string(),
    expires_at: zod_1.z.union([zod_1.z.string(), zod_1.z.null()]),
})
    .passthrough();
var ApiTokenCollection = zod_1.z.array(ApiTokenResource);
var ApiTokenStoreRequest = zod_1.z.object({ name: zod_1.z.string().min(1).max(255) }).passthrough();
var ApiTokenWithAccessTokenResource = zod_1.z
    .object({
    id: zod_1.z.string(),
    name: zod_1.z.string(),
    revoked: zod_1.z.boolean(),
    scopes: zod_1.z.array(zod_1.z.string()),
    created_at: zod_1.z.string(),
    expires_at: zod_1.z.union([zod_1.z.string(), zod_1.z.null()]),
    access_token: zod_1.z.string(),
})
    .passthrough();
var ClientResource = zod_1.z
    .object({
    id: zod_1.z.string(),
    name: zod_1.z.string(),
    is_archived: zod_1.z.boolean(),
    created_at: zod_1.z.string(),
    updated_at: zod_1.z.string(),
})
    .passthrough();
var ClientCollection = zod_1.z.array(ClientResource);
var ClientStoreRequest = zod_1.z.object({ name: zod_1.z.string().min(1).max(255) }).passthrough();
var ClientUpdateRequest = zod_1.z
    .object({ name: zod_1.z.string().min(1).max(255), is_archived: zod_1.z.boolean().optional() })
    .passthrough();
var ImportRequest = zod_1.z.object({ type: zod_1.z.string(), data: zod_1.z.string() }).passthrough();
var InvitationResource = zod_1.z
    .object({ id: zod_1.z.string(), email: zod_1.z.string(), role: zod_1.z.string() })
    .passthrough();
var InvitationStoreRequest = zod_1.z
    .object({ email: zod_1.z.string().email(), role: zod_1.z.enum(['admin', 'manager', 'employee']) })
    .passthrough();
var InvoiceResource = zod_1.z
    .object({
    id: zod_1.z.string(),
    organization_id: zod_1.z.string(),
    reference: zod_1.z.string(),
    seller_name: zod_1.z.string(),
    buyer_name: zod_1.z.string(),
    status: zod_1.z.string(),
    date: zod_1.z.string(),
    due_at: zod_1.z.string(),
    paid_date: zod_1.z.string(),
    created_at: zod_1.z.union([zod_1.z.string(), zod_1.z.null()]),
    updated_at: zod_1.z.union([zod_1.z.string(), zod_1.z.null()]),
})
    .passthrough();
var InvoiceCollection = zod_1.z.array(InvoiceResource);
var InvoiceDiscountType = zod_1.z.enum(['percentage', 'fixed']);
var InvoiceStoreRequest = zod_1.z
    .object({
    due_at: zod_1.z.union([zod_1.z.string(), zod_1.z.null()]).optional(),
    paid_date: zod_1.z.union([zod_1.z.string(), zod_1.z.null()]).optional(),
    seller_name: zod_1.z.string(),
    seller_vatin: zod_1.z.union([zod_1.z.string(), zod_1.z.null()]).optional(),
    seller_address_line_1: zod_1.z.union([zod_1.z.string(), zod_1.z.null()]).optional(),
    seller_address_line_2: zod_1.z.union([zod_1.z.string(), zod_1.z.null()]).optional(),
    seller_address_line_3: zod_1.z.union([zod_1.z.string(), zod_1.z.null()]).optional(),
    seller_address_post_code: zod_1.z.union([zod_1.z.string(), zod_1.z.null()]).optional(),
    seller_address_city: zod_1.z.union([zod_1.z.string(), zod_1.z.null()]).optional(),
    seller_address_country: zod_1.z.union([zod_1.z.string(), zod_1.z.null()]).optional(),
    seller_phone: zod_1.z.union([zod_1.z.string(), zod_1.z.null()]).optional(),
    seller_email: zod_1.z.union([zod_1.z.string(), zod_1.z.null()]).optional(),
    buyer_name: zod_1.z.string(),
    buyer_vatin: zod_1.z.union([zod_1.z.string(), zod_1.z.null()]).optional(),
    buyer_address_line_1: zod_1.z.union([zod_1.z.string(), zod_1.z.null()]).optional(),
    buyer_address_line_2: zod_1.z.union([zod_1.z.string(), zod_1.z.null()]).optional(),
    buyer_address_line_3: zod_1.z.union([zod_1.z.string(), zod_1.z.null()]).optional(),
    buyer_address_post_code: zod_1.z.union([zod_1.z.string(), zod_1.z.null()]).optional(),
    buyer_address_city: zod_1.z.union([zod_1.z.string(), zod_1.z.null()]).optional(),
    buyer_address_country: zod_1.z.union([zod_1.z.string(), zod_1.z.null()]).optional(),
    buyer_phone: zod_1.z.union([zod_1.z.string(), zod_1.z.null()]).optional(),
    buyer_email: zod_1.z.union([zod_1.z.string(), zod_1.z.null()]).optional(),
    date: zod_1.z.string(),
    billing_period_start: zod_1.z.union([zod_1.z.string(), zod_1.z.null()]).optional(),
    billing_period_end: zod_1.z.union([zod_1.z.string(), zod_1.z.null()]).optional(),
    reference: zod_1.z.string(),
    currency: zod_1.z.string(),
    payment_iban: zod_1.z.union([zod_1.z.string(), zod_1.z.null()]).optional(),
    tax_rate: zod_1.z.number().int().gte(0).lte(2147483647).optional(),
    discount_amount: zod_1.z.number().int().gte(0).lte(9223372036854776000).optional(),
    discount_type: InvoiceDiscountType.optional(),
    footer: zod_1.z.union([zod_1.z.string(), zod_1.z.null()]).optional(),
    notes: zod_1.z.union([zod_1.z.string(), zod_1.z.null()]).optional(),
    payment_terms: zod_1.z.union([zod_1.z.string(), zod_1.z.null()]).optional(),
    is_eu_reverse_charge: zod_1.z.boolean().optional(),
    entries: zod_1.z
        .array(zod_1.z
        .object({
        name: zod_1.z.string(),
        description: zod_1.z.union([zod_1.z.string(), zod_1.z.null()]).optional(),
        unit_price: zod_1.z.number().int().gte(0).lte(9223372036854776000),
        quantity: zod_1.z.number().gte(0).lte(99999999),
    })
        .passthrough())
        .optional(),
})
    .passthrough();
var InvoiceEntryResource = zod_1.z
    .object({
    id: zod_1.z.string(),
    invoice_id: zod_1.z.string(),
    name: zod_1.z.string(),
    description: zod_1.z.union([zod_1.z.string(), zod_1.z.null()]),
    unit_price: zod_1.z.number().int(),
    quantity: zod_1.z.number(),
    order_index: zod_1.z.number().int(),
    created_at: zod_1.z.union([zod_1.z.string(), zod_1.z.null()]),
    updated_at: zod_1.z.union([zod_1.z.string(), zod_1.z.null()]),
})
    .passthrough();
var DetailedInvoiceResource = zod_1.z
    .object({
    id: zod_1.z.string(),
    organization_id: zod_1.z.string(),
    reference: zod_1.z.string(),
    seller_name: zod_1.z.string(),
    seller_vatin: zod_1.z.string(),
    seller_address_line_1: zod_1.z.string(),
    seller_address_line_2: zod_1.z.string(),
    seller_address_line_3: zod_1.z.string(),
    seller_address_post_code: zod_1.z.string(),
    seller_address_city: zod_1.z.string(),
    seller_address_country: zod_1.z.string(),
    seller_phone: zod_1.z.string(),
    seller_email: zod_1.z.string(),
    buyer_name: zod_1.z.string(),
    buyer_vatin: zod_1.z.string(),
    buyer_address_line_1: zod_1.z.string(),
    buyer_address_line_2: zod_1.z.string(),
    buyer_address_line_3: zod_1.z.string(),
    buyer_address_post_code: zod_1.z.string(),
    buyer_address_city: zod_1.z.string(),
    buyer_address_country: zod_1.z.string(),
    buyer_phone: zod_1.z.string(),
    buyer_email: zod_1.z.string(),
    paid_date: zod_1.z.string(),
    due_at: zod_1.z.string(),
    discount_type: zod_1.z.string(),
    discount_amount: zod_1.z.number().int(),
    tax_rate: zod_1.z.number().int(),
    payment_iban: zod_1.z.string(),
    status: zod_1.z.string(),
    currency: zod_1.z.string(),
    date: zod_1.z.string(),
    footer: zod_1.z.string(),
    notes: zod_1.z.string(),
    payment_terms: zod_1.z.string(),
    is_eu_reverse_charge: zod_1.z.string(),
    billing_period_start: zod_1.z.string(),
    billing_period_end: zod_1.z.string(),
    created_at: zod_1.z.union([zod_1.z.string(), zod_1.z.null()]),
    updated_at: zod_1.z.union([zod_1.z.string(), zod_1.z.null()]),
    entries: zod_1.z.array(InvoiceEntryResource),
})
    .passthrough();
var InvoiceStatus = zod_1.z.enum(['draft', 'sent', 'cancelled']);
var InvoiceUpdateRequest = zod_1.z
    .object({
    status: InvoiceStatus,
    due_at: zod_1.z.union([zod_1.z.string(), zod_1.z.null()]),
    paid_date: zod_1.z.union([zod_1.z.string(), zod_1.z.null()]),
    seller_name: zod_1.z.string(),
    seller_vatin: zod_1.z.union([zod_1.z.string(), zod_1.z.null()]),
    seller_address_line_1: zod_1.z.union([zod_1.z.string(), zod_1.z.null()]),
    seller_address_line_2: zod_1.z.union([zod_1.z.string(), zod_1.z.null()]),
    seller_address_line_3: zod_1.z.union([zod_1.z.string(), zod_1.z.null()]),
    seller_address_post_code: zod_1.z.union([zod_1.z.string(), zod_1.z.null()]),
    seller_address_city: zod_1.z.union([zod_1.z.string(), zod_1.z.null()]),
    seller_address_country: zod_1.z.union([zod_1.z.string(), zod_1.z.null()]),
    seller_phone: zod_1.z.union([zod_1.z.string(), zod_1.z.null()]),
    seller_email: zod_1.z.union([zod_1.z.string(), zod_1.z.null()]),
    buyer_name: zod_1.z.string(),
    buyer_vatin: zod_1.z.union([zod_1.z.string(), zod_1.z.null()]),
    buyer_address_line_1: zod_1.z.union([zod_1.z.string(), zod_1.z.null()]),
    buyer_address_line_2: zod_1.z.union([zod_1.z.string(), zod_1.z.null()]),
    buyer_address_line_3: zod_1.z.union([zod_1.z.string(), zod_1.z.null()]),
    buyer_address_post_code: zod_1.z.union([zod_1.z.string(), zod_1.z.null()]),
    buyer_address_city: zod_1.z.union([zod_1.z.string(), zod_1.z.null()]),
    buyer_address_country: zod_1.z.union([zod_1.z.string(), zod_1.z.null()]),
    buyer_phone: zod_1.z.union([zod_1.z.string(), zod_1.z.null()]),
    buyer_email: zod_1.z.union([zod_1.z.string(), zod_1.z.null()]),
    date: zod_1.z.string(),
    billing_period_start: zod_1.z.union([zod_1.z.string(), zod_1.z.null()]),
    billing_period_end: zod_1.z.union([zod_1.z.string(), zod_1.z.null()]),
    reference: zod_1.z.string(),
    currency: zod_1.z.string(),
    payment_iban: zod_1.z.union([zod_1.z.string(), zod_1.z.null()]),
    tax_rate: zod_1.z.number().int().gte(0).lte(2147483647),
    discount_amount: zod_1.z.number().int().gte(0).lte(9223372036854776000),
    discount_type: InvoiceDiscountType,
    footer: zod_1.z.union([zod_1.z.string(), zod_1.z.null()]),
    notes: zod_1.z.union([zod_1.z.string(), zod_1.z.null()]),
    payment_terms: zod_1.z.union([zod_1.z.string(), zod_1.z.null()]),
    is_eu_reverse_charge: zod_1.z.boolean(),
    entries: zod_1.z.array(zod_1.z
        .object({
        id: zod_1.z.union([zod_1.z.string(), zod_1.z.null()]).optional(),
        name: zod_1.z.string(),
        description: zod_1.z.union([zod_1.z.string(), zod_1.z.null()]).optional(),
        unit_price: zod_1.z.number().int().gte(0).lte(9223372036854776000),
        quantity: zod_1.z.number().gte(0).lte(99999999),
    })
        .passthrough()),
})
    .partial()
    .passthrough();
var InvoiceDownloadRequest = zod_1.z.object({ with_e_invoice: zod_1.z.boolean() }).passthrough();
var InvoiceSettingResource = zod_1.z
    .object({
    seller_name: zod_1.z.union([zod_1.z.string(), zod_1.z.null()]),
    seller_vatin: zod_1.z.union([zod_1.z.string(), zod_1.z.null()]),
    seller_address_line_1: zod_1.z.union([zod_1.z.string(), zod_1.z.null()]),
    seller_address_line_2: zod_1.z.union([zod_1.z.string(), zod_1.z.null()]),
    seller_address_line_3: zod_1.z.union([zod_1.z.string(), zod_1.z.null()]),
    seller_address_post_code: zod_1.z.union([zod_1.z.string(), zod_1.z.null()]),
    seller_address_city: zod_1.z.union([zod_1.z.string(), zod_1.z.null()]),
    seller_address_country: zod_1.z.union([zod_1.z.string(), zod_1.z.null()]),
    seller_phone: zod_1.z.union([zod_1.z.string(), zod_1.z.null()]),
    seller_email: zod_1.z.union([zod_1.z.string(), zod_1.z.null()]),
    footer_default: zod_1.z.union([zod_1.z.string(), zod_1.z.null()]),
    notes_default: zod_1.z.union([zod_1.z.string(), zod_1.z.null()]),
    tax_rate_default: zod_1.z.union([zod_1.z.number(), zod_1.z.null()]),
    e_invoicing_enabled: zod_1.z.boolean(),
    organization_id: zod_1.z.string(),
})
    .passthrough();
var InvoiceSettingUpdateRequest = zod_1.z
    .object({
    seller_name: zod_1.z.union([zod_1.z.string(), zod_1.z.null()]),
    seller_vatin: zod_1.z.union([zod_1.z.string(), zod_1.z.null()]),
    seller_address_line_1: zod_1.z.union([zod_1.z.string(), zod_1.z.null()]),
    seller_address_line_2: zod_1.z.union([zod_1.z.string(), zod_1.z.null()]),
    seller_address_line_3: zod_1.z.union([zod_1.z.string(), zod_1.z.null()]),
    seller_address_post_code: zod_1.z.union([zod_1.z.string(), zod_1.z.null()]),
    seller_address_city: zod_1.z.union([zod_1.z.string(), zod_1.z.null()]),
    seller_address_country: zod_1.z.union([zod_1.z.string(), zod_1.z.null()]),
    seller_phone: zod_1.z.union([zod_1.z.string(), zod_1.z.null()]),
    seller_email: zod_1.z.union([zod_1.z.string(), zod_1.z.null()]),
    footer_default: zod_1.z.union([zod_1.z.string(), zod_1.z.null()]),
    notes_default: zod_1.z.union([zod_1.z.string(), zod_1.z.null()]),
    tax_rate_default: zod_1.z.union([zod_1.z.number(), zod_1.z.null()]),
    e_invoicing_enabled: zod_1.z.boolean(),
})
    .partial()
    .passthrough();
var MemberResource = zod_1.z
    .object({
    id: zod_1.z.string(),
    user_id: zod_1.z.string(),
    name: zod_1.z.string(),
    email: zod_1.z.string(),
    role: zod_1.z.string(),
    is_placeholder: zod_1.z.boolean(),
    billable_rate: zod_1.z.union([zod_1.z.number(), zod_1.z.null()]),
})
    .passthrough();
var Role = zod_1.z.enum(['owner', 'admin', 'manager', 'employee', 'placeholder']);
var MemberUpdateRequest = zod_1.z
    .object({ role: Role, billable_rate: zod_1.z.union([zod_1.z.number(), zod_1.z.null()]) })
    .partial()
    .passthrough();
var MemberMergeIntoRequest = zod_1.z.object({ member_id: zod_1.z.string() }).partial().passthrough();
var NumberFormat = zod_1.z.enum([
    'point-comma',
    'comma-point',
    'space-comma',
    'space-point',
    'apostrophe-point',
]);
var CurrencyFormat = zod_1.z.enum([
    'iso-code-before-with-space',
    'iso-code-after-with-space',
    'symbol-before',
    'symbol-after',
    'symbol-before-with-space',
    'symbol-after-with-space',
]);
var DateFormat = zod_1.z.enum([
    'point-separated-d-m-yyyy',
    'slash-separated-mm-dd-yyyy',
    'slash-separated-dd-mm-yyyy',
    'hyphen-separated-dd-mm-yyyy',
    'hyphen-separated-mm-dd-yyyy',
    'hyphen-separated-yyyy-mm-dd',
]);
var IntervalFormat = zod_1.z.enum([
    'decimal',
    'hours-minutes',
    'hours-minutes-colon-separated',
    'hours-minutes-seconds-colon-separated',
]);
var TimeFormat = zod_1.z.enum(['12-hours', '24-hours']);
var OrganizationResource = zod_1.z
    .object({
    id: zod_1.z.string(),
    name: zod_1.z.string(),
    is_personal: zod_1.z.boolean(),
    billable_rate: zod_1.z.union([zod_1.z.number(), zod_1.z.null()]),
    employees_can_see_billable_rates: zod_1.z.boolean(),
    prevent_overlapping_time_entries: zod_1.z.boolean(),
    currency: zod_1.z.string(),
    currency_symbol: zod_1.z.string(),
    number_format: NumberFormat,
    currency_format: CurrencyFormat,
    date_format: DateFormat,
    interval_format: IntervalFormat,
    time_format: TimeFormat,
})
    .passthrough();
var OrganizationUpdateRequest = zod_1.z
    .object({
    name: zod_1.z.string().max(255),
    billable_rate: zod_1.z.union([zod_1.z.number(), zod_1.z.null()]),
    employees_can_see_billable_rates: zod_1.z.boolean(),
    prevent_overlapping_time_entries: zod_1.z.boolean(),
    number_format: NumberFormat,
    currency_format: CurrencyFormat,
    date_format: DateFormat,
    interval_format: IntervalFormat,
    time_format: TimeFormat,
})
    .partial()
    .passthrough();
var ProjectResource = zod_1.z
    .object({
    id: zod_1.z.string(),
    name: zod_1.z.string(),
    color: zod_1.z.string(),
    client_id: zod_1.z.union([zod_1.z.string(), zod_1.z.null()]),
    is_archived: zod_1.z.boolean(),
    billable_rate: zod_1.z.union([zod_1.z.number(), zod_1.z.null()]),
    is_billable: zod_1.z.boolean(),
    estimated_time: zod_1.z.union([zod_1.z.number(), zod_1.z.null()]),
    spent_time: zod_1.z.number().int(),
    is_public: zod_1.z.boolean(),
})
    .passthrough();
var ProjectStoreRequest = zod_1.z
    .object({
    name: zod_1.z.string().min(1).max(255),
    color: zod_1.z.string().max(255),
    is_billable: zod_1.z.boolean(),
    billable_rate: zod_1.z.union([zod_1.z.number(), zod_1.z.null()]).optional(),
    client_id: zod_1.z.union([zod_1.z.string(), zod_1.z.null()]).optional(),
    estimated_time: zod_1.z.union([zod_1.z.number(), zod_1.z.null()]).optional(),
    is_public: zod_1.z.boolean().optional(),
})
    .passthrough();
var ProjectUpdateRequest = zod_1.z
    .object({
    name: zod_1.z.string().max(255),
    color: zod_1.z.string().max(255),
    is_billable: zod_1.z.boolean(),
    is_archived: zod_1.z.boolean().optional(),
    is_public: zod_1.z.boolean().optional(),
    client_id: zod_1.z.union([zod_1.z.string(), zod_1.z.null()]).optional(),
    billable_rate: zod_1.z.union([zod_1.z.number(), zod_1.z.null()]).optional(),
    estimated_time: zod_1.z.union([zod_1.z.number(), zod_1.z.null()]).optional(),
})
    .passthrough();
var ProjectMemberResource = zod_1.z
    .object({
    id: zod_1.z.string(),
    billable_rate: zod_1.z.union([zod_1.z.number(), zod_1.z.null()]),
    member_id: zod_1.z.string(),
    project_id: zod_1.z.string(),
})
    .passthrough();
var ProjectMemberStoreRequest = zod_1.z
    .object({ member_id: zod_1.z.string(), billable_rate: zod_1.z.union([zod_1.z.number(), zod_1.z.null()]).optional() })
    .passthrough();
var ProjectMemberUpdateRequest = zod_1.z
    .object({ billable_rate: zod_1.z.union([zod_1.z.number(), zod_1.z.null()]) })
    .partial()
    .passthrough();
var ReportResource = zod_1.z
    .object({
    id: zod_1.z.string(),
    name: zod_1.z.string(),
    description: zod_1.z.union([zod_1.z.string(), zod_1.z.null()]),
    is_public: zod_1.z.boolean(),
    public_until: zod_1.z.union([zod_1.z.string(), zod_1.z.null()]),
    shareable_link: zod_1.z.union([zod_1.z.string(), zod_1.z.null()]),
    created_at: zod_1.z.string(),
    updated_at: zod_1.z.string(),
})
    .passthrough();
var TimeEntryAggregationType = zod_1.z.enum([
    'day',
    'week',
    'month',
    'year',
    'user',
    'project',
    'task',
    'client',
    'billable',
    'description',
    'tag',
]);
var TimeEntryAggregationTypeInterval = zod_1.z.enum(['day', 'week', 'month', 'year']);
var Weekday = zod_1.z.enum([
    'monday',
    'tuesday',
    'wednesday',
    'thursday',
    'friday',
    'saturday',
    'sunday',
]);
var TimeEntryRoundingType = zod_1.z.enum(['up', 'down', 'nearest']);
var ReportStoreRequest = zod_1.z
    .object({
    name: zod_1.z.string().max(255),
    description: zod_1.z.union([zod_1.z.string(), zod_1.z.null()]).optional(),
    is_public: zod_1.z.boolean(),
    public_until: zod_1.z.union([zod_1.z.string(), zod_1.z.null()]).optional(),
    properties: zod_1.z
        .object({
        start: zod_1.z.string(),
        end: zod_1.z.string(),
        active: zod_1.z.union([zod_1.z.boolean(), zod_1.z.null()]).optional(),
        member_ids: zod_1.z.union([zod_1.z.array(zod_1.z.string().uuid()), zod_1.z.null()]).optional(),
        billable: zod_1.z.union([zod_1.z.boolean(), zod_1.z.null()]).optional(),
        client_ids: zod_1.z.union([zod_1.z.array(zod_1.z.string().uuid()), zod_1.z.null()]).optional(),
        project_ids: zod_1.z.union([zod_1.z.array(zod_1.z.string().uuid()), zod_1.z.null()]).optional(),
        tag_ids: zod_1.z.union([zod_1.z.array(zod_1.z.string().uuid()), zod_1.z.null()]).optional(),
        task_ids: zod_1.z.union([zod_1.z.array(zod_1.z.string().uuid()), zod_1.z.null()]).optional(),
        group: TimeEntryAggregationType,
        sub_group: TimeEntryAggregationType,
        history_group: TimeEntryAggregationTypeInterval,
        week_start: Weekday.optional(),
        timezone: zod_1.z.union([zod_1.z.string(), zod_1.z.null()]).optional(),
        rounding_type: TimeEntryRoundingType.optional(),
        rounding_minutes: zod_1.z.union([zod_1.z.number(), zod_1.z.null()]).optional(),
    })
        .passthrough(),
})
    .passthrough();
var DetailedReportResource = zod_1.z
    .object({
    id: zod_1.z.string(),
    name: zod_1.z.string(),
    description: zod_1.z.union([zod_1.z.string(), zod_1.z.null()]),
    is_public: zod_1.z.boolean(),
    public_until: zod_1.z.union([zod_1.z.string(), zod_1.z.null()]),
    shareable_link: zod_1.z.union([zod_1.z.string(), zod_1.z.null()]),
    properties: zod_1.z
        .object({
        group: zod_1.z.string(),
        sub_group: zod_1.z.string(),
        history_group: zod_1.z.string(),
        start: zod_1.z.string(),
        end: zod_1.z.string(),
        active: zod_1.z.union([zod_1.z.boolean(), zod_1.z.null()]),
        member_ids: zod_1.z.union([zod_1.z.array(zod_1.z.string()), zod_1.z.null()]),
        billable: zod_1.z.union([zod_1.z.boolean(), zod_1.z.null()]),
        client_ids: zod_1.z.union([zod_1.z.array(zod_1.z.string()), zod_1.z.null()]),
        project_ids: zod_1.z.union([zod_1.z.array(zod_1.z.string()), zod_1.z.null()]),
        tag_ids: zod_1.z.union([zod_1.z.array(zod_1.z.string()), zod_1.z.null()]),
        task_ids: zod_1.z.union([zod_1.z.array(zod_1.z.string()), zod_1.z.null()]),
        rounding_type: zod_1.z.union([zod_1.z.string(), zod_1.z.null()]),
        rounding_minutes: zod_1.z.union([zod_1.z.number(), zod_1.z.null()]),
    })
        .passthrough(),
    created_at: zod_1.z.string(),
    updated_at: zod_1.z.string(),
})
    .passthrough();
var ReportUpdateRequest = zod_1.z
    .object({
    name: zod_1.z.string().max(255),
    description: zod_1.z.union([zod_1.z.string(), zod_1.z.null()]),
    is_public: zod_1.z.boolean(),
    public_until: zod_1.z.union([zod_1.z.string(), zod_1.z.null()]),
})
    .partial()
    .passthrough();
var DetailedWithDataReportResource = zod_1.z
    .object({
    name: zod_1.z.string(),
    description: zod_1.z.union([zod_1.z.string(), zod_1.z.null()]),
    public_until: zod_1.z.union([zod_1.z.string(), zod_1.z.null()]),
    currency: zod_1.z.string(),
    number_format: NumberFormat,
    currency_format: CurrencyFormat,
    currency_symbol: zod_1.z.string(),
    date_format: DateFormat,
    interval_format: IntervalFormat,
    time_format: TimeFormat,
    properties: zod_1.z
        .object({
        group: zod_1.z.string(),
        sub_group: zod_1.z.string(),
        history_group: zod_1.z.string(),
        start: zod_1.z.string(),
        end: zod_1.z.string(),
    })
        .passthrough(),
    data: zod_1.z
        .object({
        grouped_type: zod_1.z.union([zod_1.z.string(), zod_1.z.null()]),
        grouped_data: zod_1.z.union([
            zod_1.z.array(zod_1.z
                .object({
                key: zod_1.z.union([zod_1.z.string(), zod_1.z.null()]),
                description: zod_1.z.union([zod_1.z.string(), zod_1.z.null()]),
                color: zod_1.z.union([zod_1.z.string(), zod_1.z.null()]),
                seconds: zod_1.z.number().int(),
                cost: zod_1.z.number().int(),
                grouped_type: zod_1.z.union([zod_1.z.string(), zod_1.z.null()]),
                grouped_data: zod_1.z.union([
                    zod_1.z.array(zod_1.z
                        .object({
                        key: zod_1.z.union([zod_1.z.string(), zod_1.z.null()]),
                        description: zod_1.z.union([zod_1.z.string(), zod_1.z.null()]),
                        color: zod_1.z.union([zod_1.z.string(), zod_1.z.null()]),
                        seconds: zod_1.z.number().int(),
                        cost: zod_1.z.number().int(),
                        grouped_type: zod_1.z.null(),
                        grouped_data: zod_1.z.null(),
                    })
                        .passthrough()),
                    zod_1.z.null(),
                ]),
            })
                .passthrough()),
            zod_1.z.null(),
        ]),
        seconds: zod_1.z.number().int(),
        cost: zod_1.z.number().int(),
    })
        .passthrough(),
    history_data: zod_1.z
        .object({
        grouped_type: zod_1.z.union([zod_1.z.string(), zod_1.z.null()]),
        grouped_data: zod_1.z.union([
            zod_1.z.array(zod_1.z
                .object({
                key: zod_1.z.union([zod_1.z.string(), zod_1.z.null()]),
                description: zod_1.z.union([zod_1.z.string(), zod_1.z.null()]),
                seconds: zod_1.z.number().int(),
                cost: zod_1.z.number().int(),
                grouped_type: zod_1.z.union([zod_1.z.string(), zod_1.z.null()]),
                grouped_data: zod_1.z.union([
                    zod_1.z.array(zod_1.z
                        .object({
                        key: zod_1.z.union([zod_1.z.string(), zod_1.z.null()]),
                        description: zod_1.z.union([zod_1.z.string(), zod_1.z.null()]),
                        seconds: zod_1.z.number().int(),
                        cost: zod_1.z.number().int(),
                        grouped_type: zod_1.z.null(),
                        grouped_data: zod_1.z.null(),
                    })
                        .passthrough()),
                    zod_1.z.null(),
                ]),
            })
                .passthrough()),
            zod_1.z.null(),
        ]),
        seconds: zod_1.z.number().int(),
        cost: zod_1.z.number().int(),
    })
        .passthrough(),
})
    .passthrough();
var TagResource = zod_1.z
    .object({ id: zod_1.z.string(), name: zod_1.z.string(), created_at: zod_1.z.string(), updated_at: zod_1.z.string() })
    .passthrough();
var TagCollection = zod_1.z.array(TagResource);
var TagStoreRequest = zod_1.z.object({ name: zod_1.z.string().min(1).max(255) }).passthrough();
var TagUpdateRequest = zod_1.z.object({ name: zod_1.z.string().min(1).max(255) }).passthrough();
var TaskResource = zod_1.z
    .object({
    id: zod_1.z.string(),
    name: zod_1.z.string(),
    is_done: zod_1.z.boolean(),
    project_id: zod_1.z.string(),
    estimated_time: zod_1.z.union([zod_1.z.number(), zod_1.z.null()]),
    spent_time: zod_1.z.number().int(),
    created_at: zod_1.z.string(),
    updated_at: zod_1.z.string(),
})
    .passthrough();
var TaskStoreRequest = zod_1.z
    .object({
    name: zod_1.z.string().min(1).max(255),
    project_id: zod_1.z.string(),
    estimated_time: zod_1.z.union([zod_1.z.number(), zod_1.z.null()]).optional(),
})
    .passthrough();
var TaskUpdateRequest = zod_1.z
    .object({
    name: zod_1.z.string().min(1).max(255),
    is_done: zod_1.z.boolean().optional(),
    estimated_time: zod_1.z.union([zod_1.z.number(), zod_1.z.null()]).optional(),
})
    .passthrough();
var start = zod_1.z.union([zod_1.z.string(), zod_1.z.null()]).optional();
var rounding_minutes = zod_1.z.union([zod_1.z.number(), zod_1.z.null()]).optional();
var TimeEntryResource = zod_1.z
    .object({
    id: zod_1.z.string(),
    start: zod_1.z.string(),
    end: zod_1.z.union([zod_1.z.string(), zod_1.z.null()]),
    duration: zod_1.z.union([zod_1.z.number(), zod_1.z.null()]),
    description: zod_1.z.union([zod_1.z.string(), zod_1.z.null()]),
    task_id: zod_1.z.union([zod_1.z.string(), zod_1.z.null()]),
    project_id: zod_1.z.union([zod_1.z.string(), zod_1.z.null()]),
    organization_id: zod_1.z.string(),
    user_id: zod_1.z.string(),
    tags: zod_1.z.array(zod_1.z.string()),
    billable: zod_1.z.boolean(),
})
    .passthrough();
var TimeEntryStoreRequest = zod_1.z
    .object({
    member_id: zod_1.z.string(),
    project_id: zod_1.z.union([zod_1.z.string(), zod_1.z.null()]).optional(),
    task_id: zod_1.z.union([zod_1.z.string(), zod_1.z.null()]).optional(),
    start: zod_1.z.string(),
    end: zod_1.z.union([zod_1.z.string(), zod_1.z.null()]).optional(),
    billable: zod_1.z.boolean(),
    description: zod_1.z.union([zod_1.z.string(), zod_1.z.null()]).optional(),
    tags: zod_1.z.union([zod_1.z.array(zod_1.z.string()), zod_1.z.null()]).optional(),
})
    .passthrough();
var TimeEntryUpdateMultipleRequest = zod_1.z
    .object({
    ids: zod_1.z.array(zod_1.z.string().uuid()),
    changes: zod_1.z
        .object({
        member_id: zod_1.z.string(),
        project_id: zod_1.z.union([zod_1.z.string(), zod_1.z.null()]),
        task_id: zod_1.z.union([zod_1.z.string(), zod_1.z.null()]),
        billable: zod_1.z.boolean(),
        description: zod_1.z.union([zod_1.z.string(), zod_1.z.null()]),
        tags: zod_1.z.union([zod_1.z.array(zod_1.z.string()), zod_1.z.null()]),
    })
        .partial()
        .passthrough(),
})
    .passthrough();
var TimeEntryUpdateRequest = zod_1.z
    .object({
    member_id: zod_1.z.string(),
    project_id: zod_1.z.union([zod_1.z.string(), zod_1.z.null()]),
    task_id: zod_1.z.union([zod_1.z.string(), zod_1.z.null()]),
    start: zod_1.z.string(),
    end: zod_1.z.union([zod_1.z.string(), zod_1.z.null()]),
    billable: zod_1.z.boolean(),
    description: zod_1.z.union([zod_1.z.string(), zod_1.z.null()]),
    tags: zod_1.z.union([zod_1.z.array(zod_1.z.string()), zod_1.z.null()]),
})
    .partial()
    .passthrough();
var UserResource = zod_1.z
    .object({
    id: zod_1.z.string(),
    name: zod_1.z.string(),
    email: zod_1.z.string(),
    profile_photo_url: zod_1.z.string(),
    timezone: zod_1.z.string(),
    week_start: Weekday,
})
    .passthrough();
var PersonalMembershipResource = zod_1.z
    .object({
    id: zod_1.z.string(),
    organization: zod_1.z
        .object({ id: zod_1.z.string(), name: zod_1.z.string(), currency: zod_1.z.string() })
        .passthrough(),
    role: zod_1.z.string(),
})
    .passthrough();
exports.schemas = {
    ApiTokenResource: ApiTokenResource,
    ApiTokenCollection: ApiTokenCollection,
    ApiTokenStoreRequest: ApiTokenStoreRequest,
    ApiTokenWithAccessTokenResource: ApiTokenWithAccessTokenResource,
    ClientResource: ClientResource,
    ClientCollection: ClientCollection,
    ClientStoreRequest: ClientStoreRequest,
    ClientUpdateRequest: ClientUpdateRequest,
    ImportRequest: ImportRequest,
    InvitationResource: InvitationResource,
    InvitationStoreRequest: InvitationStoreRequest,
    InvoiceResource: InvoiceResource,
    InvoiceCollection: InvoiceCollection,
    InvoiceDiscountType: InvoiceDiscountType,
    InvoiceStoreRequest: InvoiceStoreRequest,
    InvoiceEntryResource: InvoiceEntryResource,
    DetailedInvoiceResource: DetailedInvoiceResource,
    InvoiceStatus: InvoiceStatus,
    InvoiceUpdateRequest: InvoiceUpdateRequest,
    InvoiceDownloadRequest: InvoiceDownloadRequest,
    InvoiceSettingResource: InvoiceSettingResource,
    InvoiceSettingUpdateRequest: InvoiceSettingUpdateRequest,
    MemberResource: MemberResource,
    Role: Role,
    MemberUpdateRequest: MemberUpdateRequest,
    MemberMergeIntoRequest: MemberMergeIntoRequest,
    NumberFormat: NumberFormat,
    CurrencyFormat: CurrencyFormat,
    DateFormat: DateFormat,
    IntervalFormat: IntervalFormat,
    TimeFormat: TimeFormat,
    OrganizationResource: OrganizationResource,
    OrganizationUpdateRequest: OrganizationUpdateRequest,
    ProjectResource: ProjectResource,
    ProjectStoreRequest: ProjectStoreRequest,
    ProjectUpdateRequest: ProjectUpdateRequest,
    ProjectMemberResource: ProjectMemberResource,
    ProjectMemberStoreRequest: ProjectMemberStoreRequest,
    ProjectMemberUpdateRequest: ProjectMemberUpdateRequest,
    ReportResource: ReportResource,
    TimeEntryAggregationType: TimeEntryAggregationType,
    TimeEntryAggregationTypeInterval: TimeEntryAggregationTypeInterval,
    Weekday: Weekday,
    TimeEntryRoundingType: TimeEntryRoundingType,
    ReportStoreRequest: ReportStoreRequest,
    DetailedReportResource: DetailedReportResource,
    ReportUpdateRequest: ReportUpdateRequest,
    DetailedWithDataReportResource: DetailedWithDataReportResource,
    TagResource: TagResource,
    TagCollection: TagCollection,
    TagStoreRequest: TagStoreRequest,
    TagUpdateRequest: TagUpdateRequest,
    TaskResource: TaskResource,
    TaskStoreRequest: TaskStoreRequest,
    TaskUpdateRequest: TaskUpdateRequest,
    start: start,
    rounding_minutes: rounding_minutes,
    TimeEntryResource: TimeEntryResource,
    TimeEntryStoreRequest: TimeEntryStoreRequest,
    TimeEntryUpdateMultipleRequest: TimeEntryUpdateMultipleRequest,
    TimeEntryUpdateRequest: TimeEntryUpdateRequest,
    UserResource: UserResource,
    PersonalMembershipResource: PersonalMembershipResource,
};
var endpoints = (0, core_1.makeApi)([
    {
        method: 'get',
        path: '/v1/countries',
        alias: 'getCountries',
        requestFormat: 'json',
        response: zod_1.z.array(zod_1.z.object({ code: zod_1.z.string(), name: zod_1.z.string() }).passthrough()),
        errors: [
            {
                status: 401,
                description: "Unauthenticated",
                schema: zod_1.z.object({ message: zod_1.z.string() }).passthrough(),
            },
        ],
    },
    {
        method: 'get',
        path: '/v1/currencies',
        alias: 'getCurrencies',
        requestFormat: 'json',
        response: zod_1.z.array(zod_1.z.object({ code: zod_1.z.string(), name: zod_1.z.string(), symbol: zod_1.z.string() }).passthrough()),
    },
    {
        method: 'get',
        path: '/v1/organizations/:organization',
        alias: 'getOrganization',
        requestFormat: 'json',
        parameters: [
            {
                name: 'organization',
                type: 'Path',
                schema: zod_1.z.string(),
            },
        ],
        response: zod_1.z.object({ data: OrganizationResource }).passthrough(),
        errors: [
            {
                status: 401,
                description: "Unauthenticated",
                schema: zod_1.z.object({ message: zod_1.z.string() }).passthrough(),
            },
            {
                status: 403,
                description: "Authorization error",
                schema: zod_1.z.object({ message: zod_1.z.string() }).passthrough(),
            },
            {
                status: 404,
                description: "Not found",
                schema: zod_1.z.object({ message: zod_1.z.string() }).passthrough(),
            },
        ],
    },
    {
        method: 'put',
        path: '/v1/organizations/:organization',
        alias: 'updateOrganization',
        requestFormat: 'json',
        parameters: [
            {
                name: 'body',
                type: 'Body',
                schema: OrganizationUpdateRequest,
            },
            {
                name: 'organization',
                type: 'Path',
                schema: zod_1.z.string(),
            },
        ],
        response: zod_1.z.object({ data: OrganizationResource }).passthrough(),
        errors: [
            {
                status: 401,
                description: "Unauthenticated",
                schema: zod_1.z.object({ message: zod_1.z.string() }).passthrough(),
            },
            {
                status: 403,
                description: "Authorization error",
                schema: zod_1.z.object({ message: zod_1.z.string() }).passthrough(),
            },
            {
                status: 404,
                description: "Not found",
                schema: zod_1.z.object({ message: zod_1.z.string() }).passthrough(),
            },
            {
                status: 422,
                description: "Validation error",
                schema: zod_1.z
                    .object({ message: zod_1.z.string(), errors: zod_1.z.record(zod_1.z.array(zod_1.z.string())) })
                    .passthrough(),
            },
        ],
    },
    {
        method: 'get',
        path: '/v1/organizations/:organization/charts/daily-tracked-hours',
        alias: 'dailyTrackedHours',
        requestFormat: 'json',
        parameters: [
            {
                name: 'organization',
                type: 'Path',
                schema: zod_1.z.string(),
            },
        ],
        response: zod_1.z.array(zod_1.z.object({ date: zod_1.z.string(), duration: zod_1.z.number().int() }).passthrough()),
        errors: [
            {
                status: 401,
                description: "Unauthenticated",
                schema: zod_1.z.object({ message: zod_1.z.string() }).passthrough(),
            },
            {
                status: 403,
                description: "Authorization error",
                schema: zod_1.z.object({ message: zod_1.z.string() }).passthrough(),
            },
            {
                status: 404,
                description: "Not found",
                schema: zod_1.z.object({ message: zod_1.z.string() }).passthrough(),
            },
        ],
    },
    {
        method: 'get',
        path: '/v1/organizations/:organization/charts/last-seven-days',
        alias: 'lastSevenDays',
        requestFormat: 'json',
        parameters: [
            {
                name: 'organization',
                type: 'Path',
                schema: zod_1.z.string(),
            },
        ],
        response: zod_1.z.array(zod_1.z
            .object({
            date: zod_1.z.string(),
            duration: zod_1.z.number().int(),
            history: zod_1.z.array(zod_1.z.number().int()),
        })
            .passthrough()),
        errors: [
            {
                status: 401,
                description: "Unauthenticated",
                schema: zod_1.z.object({ message: zod_1.z.string() }).passthrough(),
            },
            {
                status: 403,
                description: "Authorization error",
                schema: zod_1.z.object({ message: zod_1.z.string() }).passthrough(),
            },
            {
                status: 404,
                description: "Not found",
                schema: zod_1.z.object({ message: zod_1.z.string() }).passthrough(),
            },
        ],
    },
    {
        method: 'get',
        path: '/v1/organizations/:organization/charts/latest-tasks',
        alias: 'latestTasks',
        requestFormat: 'json',
        parameters: [
            {
                name: 'organization',
                type: 'Path',
                schema: zod_1.z.string(),
            },
        ],
        response: zod_1.z.array(zod_1.z
            .object({
            task_id: zod_1.z.string(),
            name: zod_1.z.string(),
            description: zod_1.z.union([zod_1.z.string(), zod_1.z.null()]),
            status: zod_1.z.boolean(),
            time_entry_id: zod_1.z.union([zod_1.z.string(), zod_1.z.null()]),
        })
            .passthrough()),
        errors: [
            {
                status: 401,
                description: "Unauthenticated",
                schema: zod_1.z.object({ message: zod_1.z.string() }).passthrough(),
            },
            {
                status: 403,
                description: "Authorization error",
                schema: zod_1.z.object({ message: zod_1.z.string() }).passthrough(),
            },
            {
                status: 404,
                description: "Not found",
                schema: zod_1.z.object({ message: zod_1.z.string() }).passthrough(),
            },
        ],
    },
    {
        method: 'get',
        path: '/v1/organizations/:organization/charts/latest-team-activity',
        alias: 'latestTeamActivity',
        requestFormat: 'json',
        parameters: [
            {
                name: 'organization',
                type: 'Path',
                schema: zod_1.z.string(),
            },
        ],
        response: zod_1.z.array(zod_1.z
            .object({
            member_id: zod_1.z.string(),
            name: zod_1.z.string(),
            description: zod_1.z.union([zod_1.z.string(), zod_1.z.null()]),
            time_entry_id: zod_1.z.string(),
            task_id: zod_1.z.union([zod_1.z.string(), zod_1.z.null()]),
            status: zod_1.z.boolean(),
        })
            .passthrough()),
        errors: [
            {
                status: 401,
                description: "Unauthenticated",
                schema: zod_1.z.object({ message: zod_1.z.string() }).passthrough(),
            },
            {
                status: 403,
                description: "Authorization error",
                schema: zod_1.z.object({ message: zod_1.z.string() }).passthrough(),
            },
            {
                status: 404,
                description: "Not found",
                schema: zod_1.z.object({ message: zod_1.z.string() }).passthrough(),
            },
        ],
    },
    {
        method: 'get',
        path: '/v1/organizations/:organization/charts/total-weekly-billable-amount',
        alias: 'totalWeeklyBillableAmount',
        requestFormat: 'json',
        parameters: [
            {
                name: 'organization',
                type: 'Path',
                schema: zod_1.z.string(),
            },
        ],
        response: zod_1.z.object({ value: zod_1.z.number().int(), currency: zod_1.z.string() }).passthrough(),
        errors: [
            {
                status: 401,
                description: "Unauthenticated",
                schema: zod_1.z.object({ message: zod_1.z.string() }).passthrough(),
            },
            {
                status: 403,
                description: "Authorization error",
                schema: zod_1.z.object({ message: zod_1.z.string() }).passthrough(),
            },
            {
                status: 404,
                description: "Not found",
                schema: zod_1.z.object({ message: zod_1.z.string() }).passthrough(),
            },
        ],
    },
    {
        method: 'get',
        path: '/v1/organizations/:organization/charts/total-weekly-billable-time',
        alias: 'totalWeeklyBillableTime',
        requestFormat: 'json',
        parameters: [
            {
                name: 'organization',
                type: 'Path',
                schema: zod_1.z.string(),
            },
        ],
        response: zod_1.z.number().int(),
        errors: [
            {
                status: 401,
                description: "Unauthenticated",
                schema: zod_1.z.object({ message: zod_1.z.string() }).passthrough(),
            },
            {
                status: 403,
                description: "Authorization error",
                schema: zod_1.z.object({ message: zod_1.z.string() }).passthrough(),
            },
            {
                status: 404,
                description: "Not found",
                schema: zod_1.z.object({ message: zod_1.z.string() }).passthrough(),
            },
        ],
    },
    {
        method: 'get',
        path: '/v1/organizations/:organization/charts/total-weekly-time',
        alias: 'totalWeeklyTime',
        requestFormat: 'json',
        parameters: [
            {
                name: 'organization',
                type: 'Path',
                schema: zod_1.z.string(),
            },
        ],
        response: zod_1.z.number().int(),
        errors: [
            {
                status: 401,
                description: "Unauthenticated",
                schema: zod_1.z.object({ message: zod_1.z.string() }).passthrough(),
            },
            {
                status: 403,
                description: "Authorization error",
                schema: zod_1.z.object({ message: zod_1.z.string() }).passthrough(),
            },
            {
                status: 404,
                description: "Not found",
                schema: zod_1.z.object({ message: zod_1.z.string() }).passthrough(),
            },
        ],
    },
    {
        method: 'get',
        path: '/v1/organizations/:organization/charts/weekly-history',
        alias: 'weeklyHistory',
        requestFormat: 'json',
        parameters: [
            {
                name: 'organization',
                type: 'Path',
                schema: zod_1.z.string(),
            },
        ],
        response: zod_1.z.array(zod_1.z.object({ date: zod_1.z.string(), duration: zod_1.z.number().int() }).passthrough()),
        errors: [
            {
                status: 401,
                description: "Unauthenticated",
                schema: zod_1.z.object({ message: zod_1.z.string() }).passthrough(),
            },
            {
                status: 403,
                description: "Authorization error",
                schema: zod_1.z.object({ message: zod_1.z.string() }).passthrough(),
            },
            {
                status: 404,
                description: "Not found",
                schema: zod_1.z.object({ message: zod_1.z.string() }).passthrough(),
            },
        ],
    },
    {
        method: 'get',
        path: '/v1/organizations/:organization/charts/weekly-project-overview',
        alias: 'weeklyProjectOverview',
        requestFormat: 'json',
        parameters: [
            {
                name: 'organization',
                type: 'Path',
                schema: zod_1.z.string(),
            },
        ],
        response: zod_1.z.array(zod_1.z.object({ value: zod_1.z.number().int(), name: zod_1.z.string(), color: zod_1.z.string() }).passthrough()),
        errors: [
            {
                status: 401,
                description: "Unauthenticated",
                schema: zod_1.z.object({ message: zod_1.z.string() }).passthrough(),
            },
            {
                status: 403,
                description: "Authorization error",
                schema: zod_1.z.object({ message: zod_1.z.string() }).passthrough(),
            },
            {
                status: 404,
                description: "Not found",
                schema: zod_1.z.object({ message: zod_1.z.string() }).passthrough(),
            },
        ],
    },
    {
        method: 'get',
        path: '/v1/organizations/:organization/clients',
        alias: 'getClients',
        requestFormat: 'json',
        parameters: [
            {
                name: 'organization',
                type: 'Path',
                schema: zod_1.z.string(),
            },
            {
                name: 'page',
                type: 'Query',
                schema: zod_1.z.number().int().gte(1).lte(2147483647).optional(),
            },
            {
                name: 'archived',
                type: 'Query',
                schema: zod_1.z.enum(['true', 'false', 'all']).optional(),
            },
        ],
        response: zod_1.z.object({ data: ClientCollection }).passthrough(),
        errors: [
            {
                status: 401,
                description: "Unauthenticated",
                schema: zod_1.z.object({ message: zod_1.z.string() }).passthrough(),
            },
            {
                status: 403,
                description: "Authorization error",
                schema: zod_1.z.object({ message: zod_1.z.string() }).passthrough(),
            },
            {
                status: 404,
                description: "Not found",
                schema: zod_1.z.object({ message: zod_1.z.string() }).passthrough(),
            },
            {
                status: 422,
                description: "Validation error",
                schema: zod_1.z
                    .object({ message: zod_1.z.string(), errors: zod_1.z.record(zod_1.z.array(zod_1.z.string())) })
                    .passthrough(),
            },
        ],
    },
    {
        method: 'post',
        path: '/v1/organizations/:organization/clients',
        alias: 'createClient',
        requestFormat: 'json',
        parameters: [
            {
                name: 'body',
                type: 'Body',
                schema: zod_1.z.object({ name: zod_1.z.string().min(1).max(255) }).passthrough(),
            },
            {
                name: 'organization',
                type: 'Path',
                schema: zod_1.z.string(),
            },
        ],
        response: zod_1.z.object({ data: ClientResource }).passthrough(),
        errors: [
            {
                status: 401,
                description: "Unauthenticated",
                schema: zod_1.z.object({ message: zod_1.z.string() }).passthrough(),
            },
            {
                status: 403,
                description: "Authorization error",
                schema: zod_1.z.object({ message: zod_1.z.string() }).passthrough(),
            },
            {
                status: 404,
                description: "Not found",
                schema: zod_1.z.object({ message: zod_1.z.string() }).passthrough(),
            },
            {
                status: 422,
                description: "Validation error",
                schema: zod_1.z
                    .object({ message: zod_1.z.string(), errors: zod_1.z.record(zod_1.z.array(zod_1.z.string())) })
                    .passthrough(),
            },
        ],
    },
    {
        method: 'put',
        path: '/v1/organizations/:organization/clients/:client',
        alias: 'updateClient',
        requestFormat: 'json',
        parameters: [
            {
                name: 'body',
                type: 'Body',
                schema: ClientUpdateRequest,
            },
            {
                name: 'organization',
                type: 'Path',
                schema: zod_1.z.string(),
            },
            {
                name: 'client',
                type: 'Path',
                schema: zod_1.z.string(),
            },
        ],
        response: zod_1.z.object({ data: ClientResource }).passthrough(),
        errors: [
            {
                status: 401,
                description: "Unauthenticated",
                schema: zod_1.z.object({ message: zod_1.z.string() }).passthrough(),
            },
            {
                status: 403,
                description: "Authorization error",
                schema: zod_1.z.object({ message: zod_1.z.string() }).passthrough(),
            },
            {
                status: 404,
                description: "Not found",
                schema: zod_1.z.object({ message: zod_1.z.string() }).passthrough(),
            },
            {
                status: 422,
                description: "Validation error",
                schema: zod_1.z
                    .object({ message: zod_1.z.string(), errors: zod_1.z.record(zod_1.z.array(zod_1.z.string())) })
                    .passthrough(),
            },
        ],
    },
    {
        method: 'delete',
        path: '/v1/organizations/:organization/clients/:client',
        alias: 'deleteClient',
        requestFormat: 'json',
        parameters: [
            {
                name: 'organization',
                type: 'Path',
                schema: zod_1.z.string(),
            },
            {
                name: 'client',
                type: 'Path',
                schema: zod_1.z.string(),
            },
        ],
        response: zod_1.z.void(),
        errors: [
            {
                status: 400,
                description: "API exception",
                schema: zod_1.z
                    .object({ error: zod_1.z.boolean(), key: zod_1.z.string(), message: zod_1.z.string() })
                    .passthrough(),
            },
            {
                status: 401,
                description: "Unauthenticated",
                schema: zod_1.z.object({ message: zod_1.z.string() }).passthrough(),
            },
            {
                status: 403,
                description: "Authorization error",
                schema: zod_1.z.object({ message: zod_1.z.string() }).passthrough(),
            },
            {
                status: 404,
                description: "Not found",
                schema: zod_1.z.object({ message: zod_1.z.string() }).passthrough(),
            },
        ],
    },
    {
        method: 'post',
        path: '/v1/organizations/:organization/export',
        alias: 'exportOrganization',
        requestFormat: 'json',
        parameters: [
            {
                name: 'organization',
                type: 'Path',
                schema: zod_1.z.string(),
            },
        ],
        response: zod_1.z.object({ success: zod_1.z.boolean(), download_url: zod_1.z.string() }).passthrough(),
        errors: [
            {
                status: 400,
                description: "API exception",
                schema: zod_1.z
                    .object({ error: zod_1.z.boolean(), key: zod_1.z.string(), message: zod_1.z.string() })
                    .passthrough(),
            },
            {
                status: 401,
                description: "Unauthenticated",
                schema: zod_1.z.object({ message: zod_1.z.string() }).passthrough(),
            },
            {
                status: 403,
                description: "Authorization error",
                schema: zod_1.z.object({ message: zod_1.z.string() }).passthrough(),
            },
            {
                status: 404,
                description: "Not found",
                schema: zod_1.z.object({ message: zod_1.z.string() }).passthrough(),
            },
        ],
    },
    {
        method: 'post',
        path: '/v1/organizations/:organization/import',
        alias: 'importData',
        requestFormat: 'json',
        parameters: [
            {
                name: 'body',
                type: 'Body',
                schema: ImportRequest,
            },
            {
                name: 'organization',
                type: 'Path',
                schema: zod_1.z.string(),
            },
        ],
        response: zod_1.z
            .object({
            report: zod_1.z
                .object({
                clients: zod_1.z.object({ created: zod_1.z.number().int() }).passthrough(),
                projects: zod_1.z.object({ created: zod_1.z.number().int() }).passthrough(),
                tasks: zod_1.z.object({ created: zod_1.z.number().int() }).passthrough(),
                time_entries: zod_1.z.object({ created: zod_1.z.number().int() }).passthrough(),
                tags: zod_1.z.object({ created: zod_1.z.number().int() }).passthrough(),
                users: zod_1.z.object({ created: zod_1.z.number().int() }).passthrough(),
            })
                .passthrough(),
        })
            .passthrough(),
        errors: [
            {
                status: 400,
                schema: zod_1.z.union([
                    zod_1.z.object({ message: zod_1.z.string() }).passthrough(),
                    zod_1.z.object({ message: zod_1.z.literal('Invalid base64 encoded data') }).passthrough(),
                ]),
            },
            {
                status: 401,
                description: "Unauthenticated",
                schema: zod_1.z.object({ message: zod_1.z.string() }).passthrough(),
            },
            {
                status: 403,
                description: "Authorization error",
                schema: zod_1.z.object({ message: zod_1.z.string() }).passthrough(),
            },
            {
                status: 404,
                description: "Not found",
                schema: zod_1.z.object({ message: zod_1.z.string() }).passthrough(),
            },
            {
                status: 422,
                description: "Validation error",
                schema: zod_1.z
                    .object({ message: zod_1.z.string(), errors: zod_1.z.record(zod_1.z.array(zod_1.z.string())) })
                    .passthrough(),
            },
        ],
    },
    {
        method: 'get',
        path: '/v1/organizations/:organization/importers',
        alias: 'getImporters',
        requestFormat: 'json',
        parameters: [
            {
                name: 'organization',
                type: 'Path',
                schema: zod_1.z.string(),
            },
        ],
        response: zod_1.z
            .object({
            data: zod_1.z.array(zod_1.z
                .object({ key: zod_1.z.string(), name: zod_1.z.string(), description: zod_1.z.string() })
                .passthrough()),
        })
            .passthrough(),
        errors: [
            {
                status: 401,
                description: "Unauthenticated",
                schema: zod_1.z.object({ message: zod_1.z.string() }).passthrough(),
            },
            {
                status: 403,
                description: "Authorization error",
                schema: zod_1.z.object({ message: zod_1.z.string() }).passthrough(),
            },
            {
                status: 404,
                description: "Not found",
                schema: zod_1.z.object({ message: zod_1.z.string() }).passthrough(),
            },
        ],
    },
    {
        method: 'get',
        path: '/v1/organizations/:organization/invitations',
        alias: 'getInvitations',
        requestFormat: 'json',
        parameters: [
            {
                name: 'organization',
                type: 'Path',
                schema: zod_1.z.string(),
            },
        ],
        response: zod_1.z
            .object({
            data: zod_1.z.array(InvitationResource),
            links: zod_1.z
                .object({
                first: zod_1.z.union([zod_1.z.string(), zod_1.z.null()]),
                last: zod_1.z.union([zod_1.z.string(), zod_1.z.null()]),
                prev: zod_1.z.union([zod_1.z.string(), zod_1.z.null()]),
                next: zod_1.z.union([zod_1.z.string(), zod_1.z.null()]),
            })
                .passthrough(),
            meta: zod_1.z
                .object({
                current_page: zod_1.z.number().int(),
                from: zod_1.z.union([zod_1.z.number(), zod_1.z.null()]),
                last_page: zod_1.z.number().int(),
                links: zod_1.z.array(zod_1.z
                    .object({
                    url: zod_1.z.union([zod_1.z.string(), zod_1.z.null()]),
                    label: zod_1.z.string(),
                    active: zod_1.z.boolean(),
                })
                    .passthrough()),
                path: zod_1.z.union([zod_1.z.string(), zod_1.z.null()]),
                per_page: zod_1.z.number().int(),
                to: zod_1.z.union([zod_1.z.number(), zod_1.z.null()]),
                total: zod_1.z.number().int(),
            })
                .passthrough(),
        })
            .passthrough(),
        errors: [
            {
                status: 401,
                description: "Unauthenticated",
                schema: zod_1.z.object({ message: zod_1.z.string() }).passthrough(),
            },
            {
                status: 403,
                description: "Authorization error",
                schema: zod_1.z.object({ message: zod_1.z.string() }).passthrough(),
            },
            {
                status: 404,
                description: "Not found",
                schema: zod_1.z.object({ message: zod_1.z.string() }).passthrough(),
            },
            {
                status: 422,
                description: "Validation error",
                schema: zod_1.z
                    .object({ message: zod_1.z.string(), errors: zod_1.z.record(zod_1.z.array(zod_1.z.string())) })
                    .passthrough(),
            },
        ],
    },
    {
        method: 'post',
        path: '/v1/organizations/:organization/invitations',
        alias: 'invite',
        requestFormat: 'json',
        parameters: [
            {
                name: 'body',
                type: 'Body',
                schema: InvitationStoreRequest,
            },
            {
                name: 'organization',
                type: 'Path',
                schema: zod_1.z.string(),
            },
        ],
        response: zod_1.z.void(),
        errors: [
            {
                status: 400,
                description: "API exception",
                schema: zod_1.z
                    .object({ error: zod_1.z.boolean(), key: zod_1.z.string(), message: zod_1.z.string() })
                    .passthrough(),
            },
            {
                status: 401,
                description: "Unauthenticated",
                schema: zod_1.z.object({ message: zod_1.z.string() }).passthrough(),
            },
            {
                status: 403,
                description: "Authorization error",
                schema: zod_1.z.object({ message: zod_1.z.string() }).passthrough(),
            },
            {
                status: 404,
                description: "Not found",
                schema: zod_1.z.object({ message: zod_1.z.string() }).passthrough(),
            },
            {
                status: 422,
                description: "Validation error",
                schema: zod_1.z
                    .object({ message: zod_1.z.string(), errors: zod_1.z.record(zod_1.z.array(zod_1.z.string())) })
                    .passthrough(),
            },
        ],
    },
    {
        method: 'delete',
        path: '/v1/organizations/:organization/invitations/:invitation',
        alias: 'removeInvitation',
        requestFormat: 'json',
        parameters: [
            {
                name: 'organization',
                type: 'Path',
                schema: zod_1.z.string(),
            },
            {
                name: 'invitation',
                type: 'Path',
                schema: zod_1.z.string(),
            },
        ],
        response: zod_1.z.void(),
        errors: [
            {
                status: 401,
                description: "Unauthenticated",
                schema: zod_1.z.object({ message: zod_1.z.string() }).passthrough(),
            },
            {
                status: 403,
                description: "Authorization error",
                schema: zod_1.z.object({ message: zod_1.z.string() }).passthrough(),
            },
            {
                status: 404,
                description: "Not found",
                schema: zod_1.z.object({ message: zod_1.z.string() }).passthrough(),
            },
        ],
    },
    {
        method: 'post',
        path: '/v1/organizations/:organization/invitations/:invitation/resend',
        alias: 'resendInvitationEmail',
        requestFormat: 'json',
        parameters: [
            {
                name: 'organization',
                type: 'Path',
                schema: zod_1.z.string(),
            },
            {
                name: 'invitation',
                type: 'Path',
                schema: zod_1.z.string(),
            },
        ],
        response: zod_1.z.void(),
        errors: [
            {
                status: 401,
                description: "Unauthenticated",
                schema: zod_1.z.object({ message: zod_1.z.string() }).passthrough(),
            },
            {
                status: 403,
                description: "Authorization error",
                schema: zod_1.z.object({ message: zod_1.z.string() }).passthrough(),
            },
            {
                status: 404,
                description: "Not found",
                schema: zod_1.z.object({ message: zod_1.z.string() }).passthrough(),
            },
        ],
    },
    {
        method: 'get',
        path: '/v1/organizations/:organization/invoice-settings',
        alias: 'getInvoiceSettings',
        requestFormat: 'json',
        parameters: [
            {
                name: 'organization',
                type: 'Path',
                schema: zod_1.z.string(),
            },
        ],
        response: zod_1.z.object({ data: InvoiceSettingResource }).passthrough(),
        errors: [
            {
                status: 401,
                description: "Unauthenticated",
                schema: zod_1.z.object({ message: zod_1.z.string() }).passthrough(),
            },
            {
                status: 403,
                description: "Authorization error",
                schema: zod_1.z.object({ message: zod_1.z.string() }).passthrough(),
            },
            {
                status: 404,
                description: "Not found",
                schema: zod_1.z.object({ message: zod_1.z.string() }).passthrough(),
            },
        ],
    },
    {
        method: 'put',
        path: '/v1/organizations/:organization/invoice-settings',
        alias: 'updateInvoiceSettings',
        requestFormat: 'json',
        parameters: [
            {
                name: 'body',
                type: 'Body',
                schema: InvoiceSettingUpdateRequest,
            },
            {
                name: 'organization',
                type: 'Path',
                schema: zod_1.z.string(),
            },
        ],
        response: zod_1.z.object({ data: InvoiceSettingResource }).passthrough(),
        errors: [
            {
                status: 401,
                description: "Unauthenticated",
                schema: zod_1.z.object({ message: zod_1.z.string() }).passthrough(),
            },
            {
                status: 403,
                description: "Authorization error",
                schema: zod_1.z.object({ message: zod_1.z.string() }).passthrough(),
            },
            {
                status: 404,
                description: "Not found",
                schema: zod_1.z.object({ message: zod_1.z.string() }).passthrough(),
            },
            {
                status: 422,
                description: "Validation error",
                schema: zod_1.z
                    .object({ message: zod_1.z.string(), errors: zod_1.z.record(zod_1.z.array(zod_1.z.string())) })
                    .passthrough(),
            },
        ],
    },
    {
        method: 'get',
        path: '/v1/organizations/:organization/invoices',
        alias: 'getInvoices',
        requestFormat: 'json',
        parameters: [
            {
                name: 'organization',
                type: 'Path',
                schema: zod_1.z.string(),
            },
            {
                name: 'page',
                type: 'Query',
                schema: zod_1.z.number().int().gte(1).lte(2147483647).optional(),
            },
        ],
        response: zod_1.z.object({ data: InvoiceCollection }).passthrough(),
        errors: [
            {
                status: 401,
                description: "Unauthenticated",
                schema: zod_1.z.object({ message: zod_1.z.string() }).passthrough(),
            },
            {
                status: 403,
                description: "Authorization error",
                schema: zod_1.z.object({ message: zod_1.z.string() }).passthrough(),
            },
            {
                status: 404,
                description: "Not found",
                schema: zod_1.z.object({ message: zod_1.z.string() }).passthrough(),
            },
            {
                status: 422,
                description: "Validation error",
                schema: zod_1.z
                    .object({ message: zod_1.z.string(), errors: zod_1.z.record(zod_1.z.array(zod_1.z.string())) })
                    .passthrough(),
            },
        ],
    },
    {
        method: 'post',
        path: '/v1/organizations/:organization/invoices',
        alias: 'createInvoice',
        requestFormat: 'json',
        parameters: [
            {
                name: 'body',
                type: 'Body',
                schema: InvoiceStoreRequest,
            },
            {
                name: 'organization',
                type: 'Path',
                schema: zod_1.z.string(),
            },
        ],
        response: zod_1.z.object({ data: DetailedInvoiceResource }).passthrough(),
        errors: [
            {
                status: 401,
                description: "Unauthenticated",
                schema: zod_1.z.object({ message: zod_1.z.string() }).passthrough(),
            },
            {
                status: 403,
                description: "Authorization error",
                schema: zod_1.z.object({ message: zod_1.z.string() }).passthrough(),
            },
            {
                status: 404,
                description: "Not found",
                schema: zod_1.z.object({ message: zod_1.z.string() }).passthrough(),
            },
            {
                status: 422,
                description: "Validation error",
                schema: zod_1.z
                    .object({ message: zod_1.z.string(), errors: zod_1.z.record(zod_1.z.array(zod_1.z.string())) })
                    .passthrough(),
            },
        ],
    },
    {
        method: 'get',
        path: '/v1/organizations/:organization/invoices/:invoice',
        alias: 'getInvoice',
        requestFormat: 'json',
        parameters: [
            {
                name: 'organization',
                type: 'Path',
                schema: zod_1.z.string(),
            },
            {
                name: 'invoice',
                type: 'Path',
                schema: zod_1.z.string(),
            },
        ],
        response: zod_1.z.object({ data: DetailedInvoiceResource }).passthrough(),
        errors: [
            {
                status: 401,
                description: "Unauthenticated",
                schema: zod_1.z.object({ message: zod_1.z.string() }).passthrough(),
            },
            {
                status: 403,
                description: "Authorization error",
                schema: zod_1.z.object({ message: zod_1.z.string() }).passthrough(),
            },
            {
                status: 404,
                description: "Not found",
                schema: zod_1.z.object({ message: zod_1.z.string() }).passthrough(),
            },
        ],
    },
    {
        method: 'put',
        path: '/v1/organizations/:organization/invoices/:invoice',
        alias: 'updateInvoice',
        requestFormat: 'json',
        parameters: [
            {
                name: 'body',
                type: 'Body',
                schema: InvoiceUpdateRequest,
            },
            {
                name: 'organization',
                type: 'Path',
                schema: zod_1.z.string(),
            },
            {
                name: 'invoice',
                type: 'Path',
                schema: zod_1.z.string(),
            },
        ],
        response: zod_1.z.object({ data: DetailedInvoiceResource }).passthrough(),
        errors: [
            {
                status: 401,
                description: "Unauthenticated",
                schema: zod_1.z.object({ message: zod_1.z.string() }).passthrough(),
            },
            {
                status: 403,
                description: "Authorization error",
                schema: zod_1.z.object({ message: zod_1.z.string() }).passthrough(),
            },
            {
                status: 404,
                description: "Not found",
                schema: zod_1.z.object({ message: zod_1.z.string() }).passthrough(),
            },
            {
                status: 422,
                description: "Validation error",
                schema: zod_1.z
                    .object({ message: zod_1.z.string(), errors: zod_1.z.record(zod_1.z.array(zod_1.z.string())) })
                    .passthrough(),
            },
        ],
    },
    {
        method: 'delete',
        path: '/v1/organizations/:organization/invoices/:invoice',
        alias: 'deleteInvoice',
        requestFormat: 'json',
        parameters: [
            {
                name: 'organization',
                type: 'Path',
                schema: zod_1.z.string(),
            },
            {
                name: 'invoice',
                type: 'Path',
                schema: zod_1.z.string(),
            },
        ],
        response: zod_1.z.void(),
        errors: [
            {
                status: 401,
                description: "Unauthenticated",
                schema: zod_1.z.object({ message: zod_1.z.string() }).passthrough(),
            },
            {
                status: 403,
                description: "Authorization error",
                schema: zod_1.z.object({ message: zod_1.z.string() }).passthrough(),
            },
            {
                status: 404,
                description: "Not found",
                schema: zod_1.z.object({ message: zod_1.z.string() }).passthrough(),
            },
        ],
    },
    {
        method: 'post',
        path: '/v1/organizations/:organization/invoices/:invoice/download',
        alias: 'downloadInvoice',
        requestFormat: 'json',
        parameters: [
            {
                name: 'body',
                type: 'Body',
                schema: zod_1.z.object({ with_e_invoice: zod_1.z.boolean() }).passthrough(),
            },
            {
                name: 'organization',
                type: 'Path',
                schema: zod_1.z.string(),
            },
            {
                name: 'invoice',
                type: 'Path',
                schema: zod_1.z.string(),
            },
        ],
        response: zod_1.z.object({ download_link: zod_1.z.string() }).passthrough(),
        errors: [
            {
                status: 400,
                description: "API exception",
                schema: zod_1.z
                    .object({ error: zod_1.z.boolean(), key: zod_1.z.string(), message: zod_1.z.string() })
                    .passthrough(),
            },
            {
                status: 401,
                description: "Unauthenticated",
                schema: zod_1.z.object({ message: zod_1.z.string() }).passthrough(),
            },
            {
                status: 403,
                description: "Authorization error",
                schema: zod_1.z.object({ message: zod_1.z.string() }).passthrough(),
            },
            {
                status: 404,
                description: "Not found",
                schema: zod_1.z.object({ message: zod_1.z.string() }).passthrough(),
            },
            {
                status: 422,
                description: "Validation error",
                schema: zod_1.z
                    .object({ message: zod_1.z.string(), errors: zod_1.z.record(zod_1.z.array(zod_1.z.string())) })
                    .passthrough(),
            },
        ],
    },
    {
        method: 'post',
        path: '/v1/organizations/:organization/invoices/:invoice/download-e-invoice',
        alias: 'downloadEInvoice',
        requestFormat: 'json',
        parameters: [
            {
                name: 'organization',
                type: 'Path',
                schema: zod_1.z.string(),
            },
            {
                name: 'invoice',
                type: 'Path',
                schema: zod_1.z.string(),
            },
        ],
        response: zod_1.z.object({ download_link: zod_1.z.string() }).passthrough(),
        errors: [
            {
                status: 400,
                description: "API exception",
                schema: zod_1.z
                    .object({ error: zod_1.z.boolean(), key: zod_1.z.string(), message: zod_1.z.string() })
                    .passthrough(),
            },
            {
                status: 401,
                description: "Unauthenticated",
                schema: zod_1.z.object({ message: zod_1.z.string() }).passthrough(),
            },
            {
                status: 403,
                description: "Authorization error",
                schema: zod_1.z.object({ message: zod_1.z.string() }).passthrough(),
            },
            {
                status: 404,
                description: "Not found",
                schema: zod_1.z.object({ message: zod_1.z.string() }).passthrough(),
            },
        ],
    },
    {
        method: 'post',
        path: '/v1/organizations/:organization/member/:member/merge-into',
        alias: 'mergeMember',
        requestFormat: 'json',
        parameters: [
            {
                name: 'body',
                type: 'Body',
                schema: zod_1.z.object({ member_id: zod_1.z.string() }).partial().passthrough(),
            },
            {
                name: 'organization',
                type: 'Path',
                schema: zod_1.z.string(),
            },
            {
                name: 'member',
                type: 'Path',
                schema: zod_1.z.string(),
            },
        ],
        response: zod_1.z.void(),
        errors: [
            {
                status: 400,
                description: "API exception",
                schema: zod_1.z
                    .object({ error: zod_1.z.boolean(), key: zod_1.z.string(), message: zod_1.z.string() })
                    .passthrough(),
            },
            {
                status: 401,
                description: "Unauthenticated",
                schema: zod_1.z.object({ message: zod_1.z.string() }).passthrough(),
            },
            {
                status: 403,
                description: "Authorization error",
                schema: zod_1.z.object({ message: zod_1.z.string() }).passthrough(),
            },
            {
                status: 404,
                description: "Not found",
                schema: zod_1.z.object({ message: zod_1.z.string() }).passthrough(),
            },
            {
                status: 422,
                description: "Validation error",
                schema: zod_1.z
                    .object({ message: zod_1.z.string(), errors: zod_1.z.record(zod_1.z.array(zod_1.z.string())) })
                    .passthrough(),
            },
        ],
    },
    {
        method: 'get',
        path: '/v1/organizations/:organization/members',
        alias: 'getMembers',
        requestFormat: 'json',
        parameters: [
            {
                name: 'organization',
                type: 'Path',
                schema: zod_1.z.string(),
            },
        ],
        response: zod_1.z
            .object({
            data: zod_1.z.array(MemberResource),
            links: zod_1.z
                .object({
                first: zod_1.z.union([zod_1.z.string(), zod_1.z.null()]),
                last: zod_1.z.union([zod_1.z.string(), zod_1.z.null()]),
                prev: zod_1.z.union([zod_1.z.string(), zod_1.z.null()]),
                next: zod_1.z.union([zod_1.z.string(), zod_1.z.null()]),
            })
                .passthrough(),
            meta: zod_1.z
                .object({
                current_page: zod_1.z.number().int(),
                from: zod_1.z.union([zod_1.z.number(), zod_1.z.null()]),
                last_page: zod_1.z.number().int(),
                links: zod_1.z.array(zod_1.z
                    .object({
                    url: zod_1.z.union([zod_1.z.string(), zod_1.z.null()]),
                    label: zod_1.z.string(),
                    active: zod_1.z.boolean(),
                })
                    .passthrough()),
                path: zod_1.z.union([zod_1.z.string(), zod_1.z.null()]),
                per_page: zod_1.z.number().int(),
                to: zod_1.z.union([zod_1.z.number(), zod_1.z.null()]),
                total: zod_1.z.number().int(),
            })
                .passthrough(),
        })
            .passthrough(),
        errors: [
            {
                status: 401,
                description: "Unauthenticated",
                schema: zod_1.z.object({ message: zod_1.z.string() }).passthrough(),
            },
            {
                status: 403,
                description: "Authorization error",
                schema: zod_1.z.object({ message: zod_1.z.string() }).passthrough(),
            },
            {
                status: 404,
                description: "Not found",
                schema: zod_1.z.object({ message: zod_1.z.string() }).passthrough(),
            },
            {
                status: 422,
                description: "Validation error",
                schema: zod_1.z
                    .object({ message: zod_1.z.string(), errors: zod_1.z.record(zod_1.z.array(zod_1.z.string())) })
                    .passthrough(),
            },
        ],
    },
    {
        method: 'put',
        path: '/v1/organizations/:organization/members/:member',
        alias: 'updateMember',
        requestFormat: 'json',
        parameters: [
            {
                name: 'body',
                type: 'Body',
                schema: MemberUpdateRequest,
            },
            {
                name: 'organization',
                type: 'Path',
                schema: zod_1.z.string(),
            },
            {
                name: 'member',
                type: 'Path',
                schema: zod_1.z.string(),
            },
        ],
        response: zod_1.z.object({ data: MemberResource }).passthrough(),
        errors: [
            {
                status: 400,
                description: "API exception",
                schema: zod_1.z
                    .object({ error: zod_1.z.boolean(), key: zod_1.z.string(), message: zod_1.z.string() })
                    .passthrough(),
            },
            {
                status: 401,
                description: "Unauthenticated",
                schema: zod_1.z.object({ message: zod_1.z.string() }).passthrough(),
            },
            {
                status: 403,
                description: "Authorization error",
                schema: zod_1.z.object({ message: zod_1.z.string() }).passthrough(),
            },
            {
                status: 404,
                description: "Not found",
                schema: zod_1.z.object({ message: zod_1.z.string() }).passthrough(),
            },
            {
                status: 422,
                description: "Validation error",
                schema: zod_1.z
                    .object({ message: zod_1.z.string(), errors: zod_1.z.record(zod_1.z.array(zod_1.z.string())) })
                    .passthrough(),
            },
        ],
    },
    {
        method: 'delete',
        path: '/v1/organizations/:organization/members/:member',
        alias: 'removeMember',
        requestFormat: 'json',
        parameters: [
            {
                name: 'organization',
                type: 'Path',
                schema: zod_1.z.string(),
            },
            {
                name: 'member',
                type: 'Path',
                schema: zod_1.z.string(),
            },
            {
                name: 'delete_related',
                type: 'Query',
                schema: zod_1.z.enum(['true', 'false']).optional(),
            },
        ],
        response: zod_1.z.void(),
        errors: [
            {
                status: 400,
                description: "API exception",
                schema: zod_1.z
                    .object({ error: zod_1.z.boolean(), key: zod_1.z.string(), message: zod_1.z.string() })
                    .passthrough(),
            },
            {
                status: 401,
                description: "Unauthenticated",
                schema: zod_1.z.object({ message: zod_1.z.string() }).passthrough(),
            },
            {
                status: 403,
                description: "Authorization error",
                schema: zod_1.z.object({ message: zod_1.z.string() }).passthrough(),
            },
            {
                status: 404,
                description: "Not found",
                schema: zod_1.z.object({ message: zod_1.z.string() }).passthrough(),
            },
            {
                status: 422,
                description: "Validation error",
                schema: zod_1.z
                    .object({ message: zod_1.z.string(), errors: zod_1.z.record(zod_1.z.array(zod_1.z.string())) })
                    .passthrough(),
            },
        ],
    },
    {
        method: 'post',
        path: '/v1/organizations/:organization/members/:member/invite-placeholder',
        alias: 'invitePlaceholder',
        requestFormat: 'json',
        parameters: [
            {
                name: 'organization',
                type: 'Path',
                schema: zod_1.z.string(),
            },
            {
                name: 'member',
                type: 'Path',
                schema: zod_1.z.string(),
            },
        ],
        response: zod_1.z.void(),
        errors: [
            {
                status: 400,
                description: "API exception",
                schema: zod_1.z
                    .object({ error: zod_1.z.boolean(), key: zod_1.z.string(), message: zod_1.z.string() })
                    .passthrough(),
            },
            {
                status: 401,
                description: "Unauthenticated",
                schema: zod_1.z.object({ message: zod_1.z.string() }).passthrough(),
            },
            {
                status: 403,
                description: "Authorization error",
                schema: zod_1.z.object({ message: zod_1.z.string() }).passthrough(),
            },
            {
                status: 404,
                description: "Not found",
                schema: zod_1.z.object({ message: zod_1.z.string() }).passthrough(),
            },
        ],
    },
    {
        method: 'post',
        path: '/v1/organizations/:organization/members/:member/make-placeholder',
        alias: 'makePlaceholder',
        requestFormat: 'json',
        parameters: [
            {
                name: 'organization',
                type: 'Path',
                schema: zod_1.z.string(),
            },
            {
                name: 'member',
                type: 'Path',
                schema: zod_1.z.string(),
            },
        ],
        response: zod_1.z.void(),
        errors: [
            {
                status: 400,
                description: "API exception",
                schema: zod_1.z
                    .object({ error: zod_1.z.boolean(), key: zod_1.z.string(), message: zod_1.z.string() })
                    .passthrough(),
            },
            {
                status: 401,
                description: "Unauthenticated",
                schema: zod_1.z.object({ message: zod_1.z.string() }).passthrough(),
            },
            {
                status: 403,
                description: "Authorization error",
                schema: zod_1.z.object({ message: zod_1.z.string() }).passthrough(),
            },
            {
                status: 404,
                description: "Not found",
                schema: zod_1.z.object({ message: zod_1.z.string() }).passthrough(),
            },
        ],
    },
    {
        method: 'put',
        path: '/v1/organizations/:organization/project-members/:projectMember',
        alias: 'updateProjectMember',
        requestFormat: 'json',
        parameters: [
            {
                name: 'body',
                type: 'Body',
                schema: ProjectMemberUpdateRequest,
            },
            {
                name: 'organization',
                type: 'Path',
                schema: zod_1.z.string(),
            },
            {
                name: 'projectMember',
                type: 'Path',
                schema: zod_1.z.string(),
            },
        ],
        response: zod_1.z.object({ data: ProjectMemberResource }).passthrough(),
        errors: [
            {
                status: 401,
                description: "Unauthenticated",
                schema: zod_1.z.object({ message: zod_1.z.string() }).passthrough(),
            },
            {
                status: 403,
                description: "Authorization error",
                schema: zod_1.z.object({ message: zod_1.z.string() }).passthrough(),
            },
            {
                status: 404,
                description: "Not found",
                schema: zod_1.z.object({ message: zod_1.z.string() }).passthrough(),
            },
            {
                status: 422,
                description: "Validation error",
                schema: zod_1.z
                    .object({ message: zod_1.z.string(), errors: zod_1.z.record(zod_1.z.array(zod_1.z.string())) })
                    .passthrough(),
            },
        ],
    },
    {
        method: 'delete',
        path: '/v1/organizations/:organization/project-members/:projectMember',
        alias: 'deleteProjectMember',
        requestFormat: 'json',
        parameters: [
            {
                name: 'organization',
                type: 'Path',
                schema: zod_1.z.string(),
            },
            {
                name: 'projectMember',
                type: 'Path',
                schema: zod_1.z.string(),
            },
        ],
        response: zod_1.z.void(),
        errors: [
            {
                status: 401,
                description: "Unauthenticated",
                schema: zod_1.z.object({ message: zod_1.z.string() }).passthrough(),
            },
            {
                status: 403,
                description: "Authorization error",
                schema: zod_1.z.object({ message: zod_1.z.string() }).passthrough(),
            },
            {
                status: 404,
                description: "Not found",
                schema: zod_1.z.object({ message: zod_1.z.string() }).passthrough(),
            },
        ],
    },
    {
        method: 'get',
        path: '/v1/organizations/:organization/projects',
        alias: 'getProjects',
        requestFormat: 'json',
        parameters: [
            {
                name: 'organization',
                type: 'Path',
                schema: zod_1.z.string(),
            },
            {
                name: 'page',
                type: 'Query',
                schema: zod_1.z.number().int().gte(1).lte(2147483647).optional(),
            },
            {
                name: 'archived',
                type: 'Query',
                schema: zod_1.z.enum(['true', 'false', 'all']).optional(),
            },
        ],
        response: zod_1.z
            .object({
            data: zod_1.z.array(ProjectResource),
            links: zod_1.z
                .object({
                first: zod_1.z.union([zod_1.z.string(), zod_1.z.null()]),
                last: zod_1.z.union([zod_1.z.string(), zod_1.z.null()]),
                prev: zod_1.z.union([zod_1.z.string(), zod_1.z.null()]),
                next: zod_1.z.union([zod_1.z.string(), zod_1.z.null()]),
            })
                .passthrough(),
            meta: zod_1.z
                .object({
                current_page: zod_1.z.number().int(),
                from: zod_1.z.union([zod_1.z.number(), zod_1.z.null()]),
                last_page: zod_1.z.number().int(),
                links: zod_1.z.array(zod_1.z
                    .object({
                    url: zod_1.z.union([zod_1.z.string(), zod_1.z.null()]),
                    label: zod_1.z.string(),
                    active: zod_1.z.boolean(),
                })
                    .passthrough()),
                path: zod_1.z.union([zod_1.z.string(), zod_1.z.null()]),
                per_page: zod_1.z.number().int(),
                to: zod_1.z.union([zod_1.z.number(), zod_1.z.null()]),
                total: zod_1.z.number().int(),
            })
                .passthrough(),
        })
            .passthrough(),
        errors: [
            {
                status: 401,
                description: "Unauthenticated",
                schema: zod_1.z.object({ message: zod_1.z.string() }).passthrough(),
            },
            {
                status: 403,
                description: "Authorization error",
                schema: zod_1.z.object({ message: zod_1.z.string() }).passthrough(),
            },
            {
                status: 404,
                description: "Not found",
                schema: zod_1.z.object({ message: zod_1.z.string() }).passthrough(),
            },
            {
                status: 422,
                description: "Validation error",
                schema: zod_1.z
                    .object({ message: zod_1.z.string(), errors: zod_1.z.record(zod_1.z.array(zod_1.z.string())) })
                    .passthrough(),
            },
        ],
    },
    {
        method: 'post',
        path: '/v1/organizations/:organization/projects',
        alias: 'createProject',
        requestFormat: 'json',
        parameters: [
            {
                name: 'body',
                type: 'Body',
                schema: ProjectStoreRequest,
            },
            {
                name: 'organization',
                type: 'Path',
                schema: zod_1.z.string(),
            },
        ],
        response: zod_1.z.object({ data: ProjectResource }).passthrough(),
        errors: [
            {
                status: 401,
                description: "Unauthenticated",
                schema: zod_1.z.object({ message: zod_1.z.string() }).passthrough(),
            },
            {
                status: 403,
                description: "Authorization error",
                schema: zod_1.z.object({ message: zod_1.z.string() }).passthrough(),
            },
            {
                status: 404,
                description: "Not found",
                schema: zod_1.z.object({ message: zod_1.z.string() }).passthrough(),
            },
            {
                status: 422,
                description: "Validation error",
                schema: zod_1.z
                    .object({ message: zod_1.z.string(), errors: zod_1.z.record(zod_1.z.array(zod_1.z.string())) })
                    .passthrough(),
            },
        ],
    },
    {
        method: 'get',
        path: '/v1/organizations/:organization/projects/:project',
        alias: 'getProject',
        requestFormat: 'json',
        parameters: [
            {
                name: 'organization',
                type: 'Path',
                schema: zod_1.z.string(),
            },
            {
                name: 'project',
                type: 'Path',
                schema: zod_1.z.string(),
            },
        ],
        response: zod_1.z.object({ data: ProjectResource }).passthrough(),
        errors: [
            {
                status: 401,
                description: "Unauthenticated",
                schema: zod_1.z.object({ message: zod_1.z.string() }).passthrough(),
            },
            {
                status: 403,
                description: "Authorization error",
                schema: zod_1.z.object({ message: zod_1.z.string() }).passthrough(),
            },
            {
                status: 404,
                description: "Not found",
                schema: zod_1.z.object({ message: zod_1.z.string() }).passthrough(),
            },
        ],
    },
    {
        method: 'put',
        path: '/v1/organizations/:organization/projects/:project',
        alias: 'updateProject',
        requestFormat: 'json',
        parameters: [
            {
                name: 'body',
                type: 'Body',
                schema: ProjectUpdateRequest,
            },
            {
                name: 'organization',
                type: 'Path',
                schema: zod_1.z.string(),
            },
            {
                name: 'project',
                type: 'Path',
                schema: zod_1.z.string(),
            },
        ],
        response: zod_1.z.object({ data: ProjectResource }).passthrough(),
        errors: [
            {
                status: 401,
                description: "Unauthenticated",
                schema: zod_1.z.object({ message: zod_1.z.string() }).passthrough(),
            },
            {
                status: 403,
                description: "Authorization error",
                schema: zod_1.z.object({ message: zod_1.z.string() }).passthrough(),
            },
            {
                status: 404,
                description: "Not found",
                schema: zod_1.z.object({ message: zod_1.z.string() }).passthrough(),
            },
            {
                status: 422,
                description: "Validation error",
                schema: zod_1.z
                    .object({ message: zod_1.z.string(), errors: zod_1.z.record(zod_1.z.array(zod_1.z.string())) })
                    .passthrough(),
            },
        ],
    },
    {
        method: 'delete',
        path: '/v1/organizations/:organization/projects/:project',
        alias: 'deleteProject',
        requestFormat: 'json',
        parameters: [
            {
                name: 'organization',
                type: 'Path',
                schema: zod_1.z.string(),
            },
            {
                name: 'project',
                type: 'Path',
                schema: zod_1.z.string(),
            },
        ],
        response: zod_1.z.void(),
        errors: [
            {
                status: 400,
                description: "API exception",
                schema: zod_1.z
                    .object({ error: zod_1.z.boolean(), key: zod_1.z.string(), message: zod_1.z.string() })
                    .passthrough(),
            },
            {
                status: 401,
                description: "Unauthenticated",
                schema: zod_1.z.object({ message: zod_1.z.string() }).passthrough(),
            },
            {
                status: 403,
                description: "Authorization error",
                schema: zod_1.z.object({ message: zod_1.z.string() }).passthrough(),
            },
            {
                status: 404,
                description: "Not found",
                schema: zod_1.z.object({ message: zod_1.z.string() }).passthrough(),
            },
        ],
    },
    {
        method: 'get',
        path: '/v1/organizations/:organization/projects/:project/project-members',
        alias: 'getProjectMembers',
        requestFormat: 'json',
        parameters: [
            {
                name: 'organization',
                type: 'Path',
                schema: zod_1.z.string(),
            },
            {
                name: 'project',
                type: 'Path',
                schema: zod_1.z.string(),
            },
        ],
        response: zod_1.z
            .object({
            data: zod_1.z.array(ProjectMemberResource),
            links: zod_1.z
                .object({
                first: zod_1.z.union([zod_1.z.string(), zod_1.z.null()]),
                last: zod_1.z.union([zod_1.z.string(), zod_1.z.null()]),
                prev: zod_1.z.union([zod_1.z.string(), zod_1.z.null()]),
                next: zod_1.z.union([zod_1.z.string(), zod_1.z.null()]),
            })
                .passthrough(),
            meta: zod_1.z
                .object({
                current_page: zod_1.z.number().int(),
                from: zod_1.z.union([zod_1.z.number(), zod_1.z.null()]),
                last_page: zod_1.z.number().int(),
                links: zod_1.z.array(zod_1.z
                    .object({
                    url: zod_1.z.union([zod_1.z.string(), zod_1.z.null()]),
                    label: zod_1.z.string(),
                    active: zod_1.z.boolean(),
                })
                    .passthrough()),
                path: zod_1.z.union([zod_1.z.string(), zod_1.z.null()]),
                per_page: zod_1.z.number().int(),
                to: zod_1.z.union([zod_1.z.number(), zod_1.z.null()]),
                total: zod_1.z.number().int(),
            })
                .passthrough(),
        })
            .passthrough(),
        errors: [
            {
                status: 401,
                description: "Unauthenticated",
                schema: zod_1.z.object({ message: zod_1.z.string() }).passthrough(),
            },
            {
                status: 403,
                description: "Authorization error",
                schema: zod_1.z.object({ message: zod_1.z.string() }).passthrough(),
            },
            {
                status: 404,
                description: "Not found",
                schema: zod_1.z.object({ message: zod_1.z.string() }).passthrough(),
            },
        ],
    },
    {
        method: 'post',
        path: '/v1/organizations/:organization/projects/:project/project-members',
        alias: 'createProjectMember',
        requestFormat: 'json',
        parameters: [
            {
                name: 'body',
                type: 'Body',
                schema: ProjectMemberStoreRequest,
            },
            {
                name: 'organization',
                type: 'Path',
                schema: zod_1.z.string(),
            },
            {
                name: 'project',
                type: 'Path',
                schema: zod_1.z.string(),
            },
        ],
        response: zod_1.z.object({ data: ProjectMemberResource }).passthrough(),
        errors: [
            {
                status: 400,
                description: "API exception",
                schema: zod_1.z
                    .object({ error: zod_1.z.boolean(), key: zod_1.z.string(), message: zod_1.z.string() })
                    .passthrough(),
            },
            {
                status: 401,
                description: "Unauthenticated",
                schema: zod_1.z.object({ message: zod_1.z.string() }).passthrough(),
            },
            {
                status: 403,
                description: "Authorization error",
                schema: zod_1.z.object({ message: zod_1.z.string() }).passthrough(),
            },
            {
                status: 404,
                description: "Not found",
                schema: zod_1.z.object({ message: zod_1.z.string() }).passthrough(),
            },
            {
                status: 422,
                description: "Validation error",
                schema: zod_1.z
                    .object({ message: zod_1.z.string(), errors: zod_1.z.record(zod_1.z.array(zod_1.z.string())) })
                    .passthrough(),
            },
        ],
    },
    {
        method: 'get',
        path: '/v1/organizations/:organization/reports',
        alias: 'getReports',
        requestFormat: 'json',
        parameters: [
            {
                name: 'organization',
                type: 'Path',
                schema: zod_1.z.string(),
            },
        ],
        response: zod_1.z
            .object({
            data: zod_1.z.array(ReportResource),
            links: zod_1.z
                .object({
                first: zod_1.z.union([zod_1.z.string(), zod_1.z.null()]),
                last: zod_1.z.union([zod_1.z.string(), zod_1.z.null()]),
                prev: zod_1.z.union([zod_1.z.string(), zod_1.z.null()]),
                next: zod_1.z.union([zod_1.z.string(), zod_1.z.null()]),
            })
                .passthrough(),
            meta: zod_1.z
                .object({
                current_page: zod_1.z.number().int(),
                from: zod_1.z.union([zod_1.z.number(), zod_1.z.null()]),
                last_page: zod_1.z.number().int(),
                links: zod_1.z.array(zod_1.z
                    .object({
                    url: zod_1.z.union([zod_1.z.string(), zod_1.z.null()]),
                    label: zod_1.z.string(),
                    active: zod_1.z.boolean(),
                })
                    .passthrough()),
                path: zod_1.z.union([zod_1.z.string(), zod_1.z.null()]),
                per_page: zod_1.z.number().int(),
                to: zod_1.z.union([zod_1.z.number(), zod_1.z.null()]),
                total: zod_1.z.number().int(),
            })
                .passthrough(),
        })
            .passthrough(),
        errors: [
            {
                status: 401,
                description: "Unauthenticated",
                schema: zod_1.z.object({ message: zod_1.z.string() }).passthrough(),
            },
            {
                status: 403,
                description: "Authorization error",
                schema: zod_1.z.object({ message: zod_1.z.string() }).passthrough(),
            },
            {
                status: 404,
                description: "Not found",
                schema: zod_1.z.object({ message: zod_1.z.string() }).passthrough(),
            },
        ],
    },
    {
        method: 'post',
        path: '/v1/organizations/:organization/reports',
        alias: 'createReport',
        requestFormat: 'json',
        parameters: [
            {
                name: 'body',
                type: 'Body',
                schema: ReportStoreRequest,
            },
            {
                name: 'organization',
                type: 'Path',
                schema: zod_1.z.string(),
            },
        ],
        response: zod_1.z.object({ data: DetailedReportResource }).passthrough(),
        errors: [
            {
                status: 401,
                description: "Unauthenticated",
                schema: zod_1.z.object({ message: zod_1.z.string() }).passthrough(),
            },
            {
                status: 403,
                description: "Authorization error",
                schema: zod_1.z.object({ message: zod_1.z.string() }).passthrough(),
            },
            {
                status: 404,
                description: "Not found",
                schema: zod_1.z.object({ message: zod_1.z.string() }).passthrough(),
            },
            {
                status: 422,
                description: "Validation error",
                schema: zod_1.z
                    .object({ message: zod_1.z.string(), errors: zod_1.z.record(zod_1.z.array(zod_1.z.string())) })
                    .passthrough(),
            },
        ],
    },
    {
        method: 'get',
        path: '/v1/organizations/:organization/reports/:report',
        alias: 'getReport',
        requestFormat: 'json',
        parameters: [
            {
                name: 'organization',
                type: 'Path',
                schema: zod_1.z.string(),
            },
            {
                name: 'report',
                type: 'Path',
                schema: zod_1.z.string(),
            },
        ],
        response: zod_1.z.object({ data: DetailedReportResource }).passthrough(),
        errors: [
            {
                status: 401,
                description: "Unauthenticated",
                schema: zod_1.z.object({ message: zod_1.z.string() }).passthrough(),
            },
            {
                status: 403,
                description: "Authorization error",
                schema: zod_1.z.object({ message: zod_1.z.string() }).passthrough(),
            },
            {
                status: 404,
                description: "Not found",
                schema: zod_1.z.object({ message: zod_1.z.string() }).passthrough(),
            },
        ],
    },
    {
        method: 'put',
        path: '/v1/organizations/:organization/reports/:report',
        alias: 'updateReport',
        requestFormat: 'json',
        parameters: [
            {
                name: 'body',
                type: 'Body',
                schema: ReportUpdateRequest,
            },
            {
                name: 'organization',
                type: 'Path',
                schema: zod_1.z.string(),
            },
            {
                name: 'report',
                type: 'Path',
                schema: zod_1.z.string(),
            },
        ],
        response: zod_1.z.object({ data: DetailedReportResource }).passthrough(),
        errors: [
            {
                status: 401,
                description: "Unauthenticated",
                schema: zod_1.z.object({ message: zod_1.z.string() }).passthrough(),
            },
            {
                status: 403,
                description: "Authorization error",
                schema: zod_1.z.object({ message: zod_1.z.string() }).passthrough(),
            },
            {
                status: 404,
                description: "Not found",
                schema: zod_1.z.object({ message: zod_1.z.string() }).passthrough(),
            },
            {
                status: 422,
                description: "Validation error",
                schema: zod_1.z
                    .object({ message: zod_1.z.string(), errors: zod_1.z.record(zod_1.z.array(zod_1.z.string())) })
                    .passthrough(),
            },
        ],
    },
    {
        method: 'delete',
        path: '/v1/organizations/:organization/reports/:report',
        alias: 'deleteReport',
        requestFormat: 'json',
        parameters: [
            {
                name: 'organization',
                type: 'Path',
                schema: zod_1.z.string(),
            },
            {
                name: 'report',
                type: 'Path',
                schema: zod_1.z.string(),
            },
        ],
        response: zod_1.z.void(),
        errors: [
            {
                status: 401,
                description: "Unauthenticated",
                schema: zod_1.z.object({ message: zod_1.z.string() }).passthrough(),
            },
            {
                status: 403,
                description: "Authorization error",
                schema: zod_1.z.object({ message: zod_1.z.string() }).passthrough(),
            },
            {
                status: 404,
                description: "Not found",
                schema: zod_1.z.object({ message: zod_1.z.string() }).passthrough(),
            },
        ],
    },
    {
        method: 'get',
        path: '/v1/organizations/:organization/tags',
        alias: 'getTags',
        requestFormat: 'json',
        parameters: [
            {
                name: 'organization',
                type: 'Path',
                schema: zod_1.z.string(),
            },
        ],
        response: zod_1.z.object({ data: TagCollection }).passthrough(),
        errors: [
            {
                status: 401,
                description: "Unauthenticated",
                schema: zod_1.z.object({ message: zod_1.z.string() }).passthrough(),
            },
            {
                status: 403,
                description: "Authorization error",
                schema: zod_1.z.object({ message: zod_1.z.string() }).passthrough(),
            },
            {
                status: 404,
                description: "Not found",
                schema: zod_1.z.object({ message: zod_1.z.string() }).passthrough(),
            },
        ],
    },
    {
        method: 'post',
        path: '/v1/organizations/:organization/tags',
        alias: 'createTag',
        requestFormat: 'json',
        parameters: [
            {
                name: 'body',
                type: 'Body',
                schema: zod_1.z.object({ name: zod_1.z.string().min(1).max(255) }).passthrough(),
            },
            {
                name: 'organization',
                type: 'Path',
                schema: zod_1.z.string(),
            },
        ],
        response: zod_1.z.object({ data: TagResource }).passthrough(),
        errors: [
            {
                status: 401,
                description: "Unauthenticated",
                schema: zod_1.z.object({ message: zod_1.z.string() }).passthrough(),
            },
            {
                status: 403,
                description: "Authorization error",
                schema: zod_1.z.object({ message: zod_1.z.string() }).passthrough(),
            },
            {
                status: 404,
                description: "Not found",
                schema: zod_1.z.object({ message: zod_1.z.string() }).passthrough(),
            },
            {
                status: 422,
                description: "Validation error",
                schema: zod_1.z
                    .object({ message: zod_1.z.string(), errors: zod_1.z.record(zod_1.z.array(zod_1.z.string())) })
                    .passthrough(),
            },
        ],
    },
    {
        method: 'put',
        path: '/v1/organizations/:organization/tags/:tag',
        alias: 'updateTag',
        requestFormat: 'json',
        parameters: [
            {
                name: 'body',
                type: 'Body',
                schema: zod_1.z.object({ name: zod_1.z.string().min(1).max(255) }).passthrough(),
            },
            {
                name: 'organization',
                type: 'Path',
                schema: zod_1.z.string(),
            },
            {
                name: 'tag',
                type: 'Path',
                schema: zod_1.z.string(),
            },
        ],
        response: zod_1.z.object({ data: TagResource }).passthrough(),
        errors: [
            {
                status: 401,
                description: "Unauthenticated",
                schema: zod_1.z.object({ message: zod_1.z.string() }).passthrough(),
            },
            {
                status: 403,
                description: "Authorization error",
                schema: zod_1.z.object({ message: zod_1.z.string() }).passthrough(),
            },
            {
                status: 404,
                description: "Not found",
                schema: zod_1.z.object({ message: zod_1.z.string() }).passthrough(),
            },
            {
                status: 422,
                description: "Validation error",
                schema: zod_1.z
                    .object({ message: zod_1.z.string(), errors: zod_1.z.record(zod_1.z.array(zod_1.z.string())) })
                    .passthrough(),
            },
        ],
    },
    {
        method: 'delete',
        path: '/v1/organizations/:organization/tags/:tag',
        alias: 'deleteTag',
        requestFormat: 'json',
        parameters: [
            {
                name: 'organization',
                type: 'Path',
                schema: zod_1.z.string(),
            },
            {
                name: 'tag',
                type: 'Path',
                schema: zod_1.z.string(),
            },
        ],
        response: zod_1.z.void(),
        errors: [
            {
                status: 400,
                description: "API exception",
                schema: zod_1.z
                    .object({ error: zod_1.z.boolean(), key: zod_1.z.string(), message: zod_1.z.string() })
                    .passthrough(),
            },
            {
                status: 401,
                description: "Unauthenticated",
                schema: zod_1.z.object({ message: zod_1.z.string() }).passthrough(),
            },
            {
                status: 403,
                description: "Authorization error",
                schema: zod_1.z.object({ message: zod_1.z.string() }).passthrough(),
            },
            {
                status: 404,
                description: "Not found",
                schema: zod_1.z.object({ message: zod_1.z.string() }).passthrough(),
            },
        ],
    },
    {
        method: 'get',
        path: '/v1/organizations/:organization/tasks',
        alias: 'getTasks',
        requestFormat: 'json',
        parameters: [
            {
                name: 'organization',
                type: 'Path',
                schema: zod_1.z.string(),
            },
            {
                name: 'project_id',
                type: 'Query',
                schema: zod_1.z.string().optional(),
            },
            {
                name: 'done',
                type: 'Query',
                schema: zod_1.z.enum(['true', 'false', 'all']).optional(),
            },
        ],
        response: zod_1.z
            .object({
            data: zod_1.z.array(TaskResource),
            links: zod_1.z
                .object({
                first: zod_1.z.union([zod_1.z.string(), zod_1.z.null()]),
                last: zod_1.z.union([zod_1.z.string(), zod_1.z.null()]),
                prev: zod_1.z.union([zod_1.z.string(), zod_1.z.null()]),
                next: zod_1.z.union([zod_1.z.string(), zod_1.z.null()]),
            })
                .passthrough(),
            meta: zod_1.z
                .object({
                current_page: zod_1.z.number().int(),
                from: zod_1.z.union([zod_1.z.number(), zod_1.z.null()]),
                last_page: zod_1.z.number().int(),
                links: zod_1.z.array(zod_1.z
                    .object({
                    url: zod_1.z.union([zod_1.z.string(), zod_1.z.null()]),
                    label: zod_1.z.string(),
                    active: zod_1.z.boolean(),
                })
                    .passthrough()),
                path: zod_1.z.union([zod_1.z.string(), zod_1.z.null()]),
                per_page: zod_1.z.number().int(),
                to: zod_1.z.union([zod_1.z.number(), zod_1.z.null()]),
                total: zod_1.z.number().int(),
            })
                .passthrough(),
        })
            .passthrough(),
        errors: [
            {
                status: 401,
                description: "Unauthenticated",
                schema: zod_1.z.object({ message: zod_1.z.string() }).passthrough(),
            },
            {
                status: 403,
                description: "Authorization error",
                schema: zod_1.z.object({ message: zod_1.z.string() }).passthrough(),
            },
            {
                status: 404,
                description: "Not found",
                schema: zod_1.z.object({ message: zod_1.z.string() }).passthrough(),
            },
            {
                status: 422,
                description: "Validation error",
                schema: zod_1.z
                    .object({ message: zod_1.z.string(), errors: zod_1.z.record(zod_1.z.array(zod_1.z.string())) })
                    .passthrough(),
            },
        ],
    },
    {
        method: 'post',
        path: '/v1/organizations/:organization/tasks',
        alias: 'createTask',
        requestFormat: 'json',
        parameters: [
            {
                name: 'body',
                type: 'Body',
                schema: TaskStoreRequest,
            },
            {
                name: 'organization',
                type: 'Path',
                schema: zod_1.z.string(),
            },
        ],
        response: zod_1.z.object({ data: TaskResource }).passthrough(),
        errors: [
            {
                status: 401,
                description: "Unauthenticated",
                schema: zod_1.z.object({ message: zod_1.z.string() }).passthrough(),
            },
            {
                status: 403,
                description: "Authorization error",
                schema: zod_1.z.object({ message: zod_1.z.string() }).passthrough(),
            },
            {
                status: 404,
                description: "Not found",
                schema: zod_1.z.object({ message: zod_1.z.string() }).passthrough(),
            },
            {
                status: 422,
                description: "Validation error",
                schema: zod_1.z
                    .object({ message: zod_1.z.string(), errors: zod_1.z.record(zod_1.z.array(zod_1.z.string())) })
                    .passthrough(),
            },
        ],
    },
    {
        method: 'put',
        path: '/v1/organizations/:organization/tasks/:task',
        alias: 'updateTask',
        requestFormat: 'json',
        parameters: [
            {
                name: 'body',
                type: 'Body',
                schema: TaskUpdateRequest,
            },
            {
                name: 'organization',
                type: 'Path',
                schema: zod_1.z.string(),
            },
            {
                name: 'task',
                type: 'Path',
                schema: zod_1.z.string(),
            },
        ],
        response: zod_1.z.object({ data: TaskResource }).passthrough(),
        errors: [
            {
                status: 401,
                description: "Unauthenticated",
                schema: zod_1.z.object({ message: zod_1.z.string() }).passthrough(),
            },
            {
                status: 403,
                description: "Authorization error",
                schema: zod_1.z.object({ message: zod_1.z.string() }).passthrough(),
            },
            {
                status: 404,
                description: "Not found",
                schema: zod_1.z.object({ message: zod_1.z.string() }).passthrough(),
            },
            {
                status: 422,
                description: "Validation error",
                schema: zod_1.z
                    .object({ message: zod_1.z.string(), errors: zod_1.z.record(zod_1.z.array(zod_1.z.string())) })
                    .passthrough(),
            },
        ],
    },
    {
        method: 'delete',
        path: '/v1/organizations/:organization/tasks/:task',
        alias: 'deleteTask',
        requestFormat: 'json',
        parameters: [
            {
                name: 'organization',
                type: 'Path',
                schema: zod_1.z.string(),
            },
            {
                name: 'task',
                type: 'Path',
                schema: zod_1.z.string(),
            },
        ],
        response: zod_1.z.void(),
        errors: [
            {
                status: 400,
                description: "API exception",
                schema: zod_1.z
                    .object({ error: zod_1.z.boolean(), key: zod_1.z.string(), message: zod_1.z.string() })
                    .passthrough(),
            },
            {
                status: 401,
                description: "Unauthenticated",
                schema: zod_1.z.object({ message: zod_1.z.string() }).passthrough(),
            },
            {
                status: 403,
                description: "Authorization error",
                schema: zod_1.z.object({ message: zod_1.z.string() }).passthrough(),
            },
            {
                status: 404,
                description: "Not found",
                schema: zod_1.z.object({ message: zod_1.z.string() }).passthrough(),
            },
        ],
    },
    {
        method: 'get',
        path: '/v1/organizations/:organization/time-entries',
        alias: 'getTimeEntries',
        description: "If you only need time entries for a specific user, you can filter by &#x60;user_id&#x60;.\nUsers with the permission &#x60;time-entries:view:own&#x60; can only use this endpoint with their own user ID in the user_id filter.",
        requestFormat: 'json',
        parameters: [
            {
                name: 'organization',
                type: 'Path',
                schema: zod_1.z.string(),
            },
            {
                name: 'member_id',
                type: 'Query',
                schema: zod_1.z.string().optional(),
            },
            {
                name: 'start',
                type: 'Query',
                schema: start,
            },
            {
                name: 'end',
                type: 'Query',
                schema: start,
            },
            {
                name: 'active',
                type: 'Query',
                schema: zod_1.z.enum(['true', 'false']).optional(),
            },
            {
                name: 'billable',
                type: 'Query',
                schema: zod_1.z.enum(['true', 'false']).optional(),
            },
            {
                name: 'limit',
                type: 'Query',
                schema: zod_1.z.number().int().gte(1).lte(500).optional(),
            },
            {
                name: 'offset',
                type: 'Query',
                schema: zod_1.z.number().int().gte(0).lte(2147483647).optional(),
            },
            {
                name: 'only_full_dates',
                type: 'Query',
                schema: zod_1.z.enum(['true', 'false']).optional(),
            },
            {
                name: 'rounding_type',
                type: 'Query',
                schema: zod_1.z.enum(['up', 'down', 'nearest']).optional(),
            },
            {
                name: 'rounding_minutes',
                type: 'Query',
                schema: rounding_minutes,
            },
            {
                name: 'user_id',
                type: 'Query',
                schema: zod_1.z.string().optional(),
            },
            {
                name: 'member_ids',
                type: 'Query',
                schema: zod_1.z.array(zod_1.z.string()).min(1).optional(),
            },
            {
                name: 'client_ids',
                type: 'Query',
                schema: zod_1.z.array(zod_1.z.string()).min(1).optional(),
            },
            {
                name: 'project_ids',
                type: 'Query',
                schema: zod_1.z.array(zod_1.z.string()).min(1).optional(),
            },
            {
                name: 'tag_ids',
                type: 'Query',
                schema: zod_1.z.array(zod_1.z.string()).min(1).optional(),
            },
            {
                name: 'task_ids',
                type: 'Query',
                schema: zod_1.z.array(zod_1.z.string()).min(1).optional(),
            },
        ],
        response: zod_1.z
            .object({
            data: zod_1.z.array(TimeEntryResource),
            meta: zod_1.z.object({ total: zod_1.z.number().int() }).passthrough(),
        })
            .passthrough(),
        errors: [
            {
                status: 401,
                description: "Unauthenticated",
                schema: zod_1.z.object({ message: zod_1.z.string() }).passthrough(),
            },
            {
                status: 403,
                description: "Authorization error",
                schema: zod_1.z.object({ message: zod_1.z.string() }).passthrough(),
            },
            {
                status: 404,
                description: "Not found",
                schema: zod_1.z.object({ message: zod_1.z.string() }).passthrough(),
            },
            {
                status: 422,
                description: "Validation error",
                schema: zod_1.z
                    .object({ message: zod_1.z.string(), errors: zod_1.z.record(zod_1.z.array(zod_1.z.string())) })
                    .passthrough(),
            },
        ],
    },
    {
        method: 'post',
        path: '/v1/organizations/:organization/time-entries',
        alias: 'createTimeEntry',
        requestFormat: 'json',
        parameters: [
            {
                name: 'body',
                type: 'Body',
                schema: TimeEntryStoreRequest,
            },
            {
                name: 'organization',
                type: 'Path',
                schema: zod_1.z.string(),
            },
        ],
        response: zod_1.z.object({ data: TimeEntryResource }).passthrough(),
        errors: [
            {
                status: 400,
                description: "API exception",
                schema: zod_1.z
                    .object({ error: zod_1.z.boolean(), key: zod_1.z.string(), message: zod_1.z.string() })
                    .passthrough(),
            },
            {
                status: 401,
                description: "Unauthenticated",
                schema: zod_1.z.object({ message: zod_1.z.string() }).passthrough(),
            },
            {
                status: 403,
                description: "Authorization error",
                schema: zod_1.z.object({ message: zod_1.z.string() }).passthrough(),
            },
            {
                status: 404,
                description: "Not found",
                schema: zod_1.z.object({ message: zod_1.z.string() }).passthrough(),
            },
            {
                status: 422,
                description: "Validation error",
                schema: zod_1.z
                    .object({ message: zod_1.z.string(), errors: zod_1.z.record(zod_1.z.array(zod_1.z.string())) })
                    .passthrough(),
            },
        ],
    },
    {
        method: 'patch',
        path: '/v1/organizations/:organization/time-entries',
        alias: 'updateMultipleTimeEntries',
        requestFormat: 'json',
        parameters: [
            {
                name: 'body',
                type: 'Body',
                schema: TimeEntryUpdateMultipleRequest,
            },
            {
                name: 'organization',
                type: 'Path',
                schema: zod_1.z.string(),
            },
        ],
        response: zod_1.z.object({ success: zod_1.z.string(), error: zod_1.z.string() }).passthrough(),
        errors: [
            {
                status: 401,
                description: "Unauthenticated",
                schema: zod_1.z.object({ message: zod_1.z.string() }).passthrough(),
            },
            {
                status: 403,
                description: "Authorization error",
                schema: zod_1.z.object({ message: zod_1.z.string() }).passthrough(),
            },
            {
                status: 404,
                description: "Not found",
                schema: zod_1.z.object({ message: zod_1.z.string() }).passthrough(),
            },
            {
                status: 422,
                description: "Validation error",
                schema: zod_1.z
                    .object({ message: zod_1.z.string(), errors: zod_1.z.record(zod_1.z.array(zod_1.z.string())) })
                    .passthrough(),
            },
        ],
    },
    {
        method: 'delete',
        path: '/v1/organizations/:organization/time-entries',
        alias: 'deleteTimeEntries',
        requestFormat: 'json',
        parameters: [
            {
                name: 'organization',
                type: 'Path',
                schema: zod_1.z.string(),
            },
            {
                name: 'ids',
                type: 'Query',
                schema: zod_1.z.array(zod_1.z.string().uuid()),
            },
        ],
        response: zod_1.z.object({ success: zod_1.z.string(), error: zod_1.z.string() }).passthrough(),
        errors: [
            {
                status: 401,
                description: "Unauthenticated",
                schema: zod_1.z.object({ message: zod_1.z.string() }).passthrough(),
            },
            {
                status: 403,
                description: "Authorization error",
                schema: zod_1.z.object({ message: zod_1.z.string() }).passthrough(),
            },
            {
                status: 404,
                description: "Not found",
                schema: zod_1.z.object({ message: zod_1.z.string() }).passthrough(),
            },
            {
                status: 422,
                description: "Validation error",
                schema: zod_1.z
                    .object({ message: zod_1.z.string(), errors: zod_1.z.record(zod_1.z.array(zod_1.z.string())) })
                    .passthrough(),
            },
        ],
    },
    {
        method: 'put',
        path: '/v1/organizations/:organization/time-entries/:timeEntry',
        alias: 'updateTimeEntry',
        requestFormat: 'json',
        parameters: [
            {
                name: 'body',
                type: 'Body',
                schema: TimeEntryUpdateRequest,
            },
            {
                name: 'organization',
                type: 'Path',
                schema: zod_1.z.string(),
            },
            {
                name: 'timeEntry',
                type: 'Path',
                schema: zod_1.z.string(),
            },
        ],
        response: zod_1.z.object({ data: TimeEntryResource }).passthrough(),
        errors: [
            {
                status: 400,
                description: "API exception",
                schema: zod_1.z
                    .object({ error: zod_1.z.boolean(), key: zod_1.z.string(), message: zod_1.z.string() })
                    .passthrough(),
            },
            {
                status: 401,
                description: "Unauthenticated",
                schema: zod_1.z.object({ message: zod_1.z.string() }).passthrough(),
            },
            {
                status: 403,
                description: "Authorization error",
                schema: zod_1.z.object({ message: zod_1.z.string() }).passthrough(),
            },
            {
                status: 404,
                description: "Not found",
                schema: zod_1.z.object({ message: zod_1.z.string() }).passthrough(),
            },
            {
                status: 422,
                description: "Validation error",
                schema: zod_1.z
                    .object({ message: zod_1.z.string(), errors: zod_1.z.record(zod_1.z.array(zod_1.z.string())) })
                    .passthrough(),
            },
        ],
    },
    {
        method: 'delete',
        path: '/v1/organizations/:organization/time-entries/:timeEntry',
        alias: 'deleteTimeEntry',
        requestFormat: 'json',
        parameters: [
            {
                name: 'organization',
                type: 'Path',
                schema: zod_1.z.string(),
            },
            {
                name: 'timeEntry',
                type: 'Path',
                schema: zod_1.z.string(),
            },
        ],
        response: zod_1.z.void(),
        errors: [
            {
                status: 401,
                description: "Unauthenticated",
                schema: zod_1.z.object({ message: zod_1.z.string() }).passthrough(),
            },
            {
                status: 403,
                description: "Authorization error",
                schema: zod_1.z.object({ message: zod_1.z.string() }).passthrough(),
            },
            {
                status: 404,
                description: "Not found",
                schema: zod_1.z.object({ message: zod_1.z.string() }).passthrough(),
            },
        ],
    },
    {
        method: 'get',
        path: '/v1/organizations/:organization/time-entries/aggregate',
        alias: 'getAggregatedTimeEntries',
        description: "This endpoint allows you to filter time entries and aggregate them by different criteria.\nThe parameters &#x60;group&#x60; and &#x60;sub_group&#x60; allow you to group the time entries by different criteria.\nIf the group parameters are all set to &#x60;null&#x60; or are all missing, the endpoint will aggregate all filtered time entries.",
        requestFormat: 'json',
        parameters: [
            {
                name: 'organization',
                type: 'Path',
                schema: zod_1.z.string(),
            },
            {
                name: 'group',
                type: 'Query',
                schema: zod_1.z
                    .enum([
                    'day',
                    'week',
                    'month',
                    'year',
                    'user',
                    'project',
                    'task',
                    'client',
                    'billable',
                    'description',
                    'tag',
                ])
                    .optional(),
            },
            {
                name: 'sub_group',
                type: 'Query',
                schema: zod_1.z
                    .enum([
                    'day',
                    'week',
                    'month',
                    'year',
                    'user',
                    'project',
                    'task',
                    'client',
                    'billable',
                    'description',
                    'tag',
                ])
                    .optional(),
            },
            {
                name: 'member_id',
                type: 'Query',
                schema: zod_1.z.string().optional(),
            },
            {
                name: 'user_id',
                type: 'Query',
                schema: zod_1.z.string().optional(),
            },
            {
                name: 'start',
                type: 'Query',
                schema: start,
            },
            {
                name: 'end',
                type: 'Query',
                schema: start,
            },
            {
                name: 'active',
                type: 'Query',
                schema: zod_1.z.enum(['true', 'false']).optional(),
            },
            {
                name: 'billable',
                type: 'Query',
                schema: zod_1.z.enum(['true', 'false']).optional(),
            },
            {
                name: 'fill_gaps_in_time_groups',
                type: 'Query',
                schema: zod_1.z.enum(['true', 'false']).optional(),
            },
            {
                name: 'rounding_type',
                type: 'Query',
                schema: zod_1.z.enum(['up', 'down', 'nearest']).optional(),
            },
            {
                name: 'rounding_minutes',
                type: 'Query',
                schema: rounding_minutes,
            },
            {
                name: 'member_ids',
                type: 'Query',
                schema: zod_1.z.array(zod_1.z.string()).min(1).optional(),
            },
            {
                name: 'project_ids',
                type: 'Query',
                schema: zod_1.z.array(zod_1.z.string()).min(1).optional(),
            },
            {
                name: 'client_ids',
                type: 'Query',
                schema: zod_1.z.array(zod_1.z.string()).min(1).optional(),
            },
            {
                name: 'tag_ids',
                type: 'Query',
                schema: zod_1.z.array(zod_1.z.string()).min(1).optional(),
            },
            {
                name: 'task_ids',
                type: 'Query',
                schema: zod_1.z.array(zod_1.z.string()).min(1).optional(),
            },
        ],
        response: zod_1.z
            .object({
            data: zod_1.z
                .object({
                grouped_type: zod_1.z.union([zod_1.z.string(), zod_1.z.null()]),
                grouped_data: zod_1.z.union([
                    zod_1.z.array(zod_1.z
                        .object({
                        key: zod_1.z.union([zod_1.z.string(), zod_1.z.null()]),
                        seconds: zod_1.z.number().int(),
                        cost: zod_1.z.union([zod_1.z.number(), zod_1.z.null()]),
                        grouped_type: zod_1.z.union([zod_1.z.string(), zod_1.z.null()]),
                        grouped_data: zod_1.z.union([
                            zod_1.z.array(zod_1.z
                                .object({
                                key: zod_1.z.union([zod_1.z.string(), zod_1.z.null()]),
                                seconds: zod_1.z.number().int(),
                                cost: zod_1.z.union([zod_1.z.number(), zod_1.z.null()]),
                                grouped_type: zod_1.z.null(),
                                grouped_data: zod_1.z.null(),
                            })
                                .passthrough()),
                            zod_1.z.null(),
                        ]),
                    })
                        .passthrough()),
                    zod_1.z.null(),
                ]),
                seconds: zod_1.z.number().int(),
                cost: zod_1.z.union([zod_1.z.number(), zod_1.z.null()]),
            })
                .passthrough(),
        })
            .passthrough(),
        errors: [
            {
                status: 401,
                description: "Unauthenticated",
                schema: zod_1.z.object({ message: zod_1.z.string() }).passthrough(),
            },
            {
                status: 403,
                description: "Authorization error",
                schema: zod_1.z.object({ message: zod_1.z.string() }).passthrough(),
            },
            {
                status: 404,
                description: "Not found",
                schema: zod_1.z.object({ message: zod_1.z.string() }).passthrough(),
            },
            {
                status: 422,
                description: "Validation error",
                schema: zod_1.z
                    .object({ message: zod_1.z.string(), errors: zod_1.z.record(zod_1.z.array(zod_1.z.string())) })
                    .passthrough(),
            },
        ],
    },
    {
        method: 'get',
        path: '/v1/organizations/:organization/time-entries/aggregate/export',
        alias: 'exportAggregatedTimeEntries',
        requestFormat: 'json',
        parameters: [
            {
                name: 'organization',
                type: 'Path',
                schema: zod_1.z.string(),
            },
            {
                name: 'format',
                type: 'Query',
                schema: zod_1.z.enum(['csv', 'pdf', 'xlsx', 'ods']),
            },
            {
                name: 'group',
                type: 'Query',
                schema: zod_1.z.enum([
                    'day',
                    'week',
                    'month',
                    'year',
                    'user',
                    'project',
                    'task',
                    'client',
                    'billable',
                    'description',
                    'tag',
                ]),
            },
            {
                name: 'sub_group',
                type: 'Query',
                schema: zod_1.z.enum([
                    'day',
                    'week',
                    'month',
                    'year',
                    'user',
                    'project',
                    'task',
                    'client',
                    'billable',
                    'description',
                    'tag',
                ]),
            },
            {
                name: 'history_group',
                type: 'Query',
                schema: zod_1.z.enum(['day', 'week', 'month', 'year']),
            },
            {
                name: 'member_id',
                type: 'Query',
                schema: zod_1.z.string().optional(),
            },
            {
                name: 'user_id',
                type: 'Query',
                schema: zod_1.z.string().optional(),
            },
            {
                name: 'start',
                type: 'Query',
                schema: zod_1.z.string(),
            },
            {
                name: 'end',
                type: 'Query',
                schema: zod_1.z.string(),
            },
            {
                name: 'active',
                type: 'Query',
                schema: zod_1.z.enum(['true', 'false']).optional(),
            },
            {
                name: 'billable',
                type: 'Query',
                schema: zod_1.z.enum(['true', 'false']).optional(),
            },
            {
                name: 'fill_gaps_in_time_groups',
                type: 'Query',
                schema: zod_1.z.enum(['true', 'false']).optional(),
            },
            {
                name: 'debug',
                type: 'Query',
                schema: zod_1.z.enum(['true', 'false']).optional(),
            },
            {
                name: 'rounding_type',
                type: 'Query',
                schema: zod_1.z.enum(['up', 'down', 'nearest']).optional(),
            },
            {
                name: 'rounding_minutes',
                type: 'Query',
                schema: rounding_minutes,
            },
            {
                name: 'member_ids',
                type: 'Query',
                schema: zod_1.z.array(zod_1.z.string()).min(1).optional(),
            },
            {
                name: 'project_ids',
                type: 'Query',
                schema: zod_1.z.array(zod_1.z.string()).min(1).optional(),
            },
            {
                name: 'client_ids',
                type: 'Query',
                schema: zod_1.z.array(zod_1.z.string()).min(1).optional(),
            },
            {
                name: 'tag_ids',
                type: 'Query',
                schema: zod_1.z.array(zod_1.z.string()).min(1).optional(),
            },
            {
                name: 'task_ids',
                type: 'Query',
                schema: zod_1.z.array(zod_1.z.string()).min(1).optional(),
            },
        ],
        response: zod_1.z.union([
            zod_1.z.object({ download_url: zod_1.z.string() }).passthrough(),
            zod_1.z.object({ html: zod_1.z.string(), footer_html: zod_1.z.string() }).passthrough(),
        ]),
        errors: [
            {
                status: 400,
                description: "API exception",
                schema: zod_1.z
                    .object({ error: zod_1.z.boolean(), key: zod_1.z.string(), message: zod_1.z.string() })
                    .passthrough(),
            },
            {
                status: 401,
                description: "Unauthenticated",
                schema: zod_1.z.object({ message: zod_1.z.string() }).passthrough(),
            },
            {
                status: 403,
                description: "Authorization error",
                schema: zod_1.z.object({ message: zod_1.z.string() }).passthrough(),
            },
            {
                status: 404,
                description: "Not found",
                schema: zod_1.z.object({ message: zod_1.z.string() }).passthrough(),
            },
            {
                status: 422,
                description: "Validation error",
                schema: zod_1.z
                    .object({ message: zod_1.z.string(), errors: zod_1.z.record(zod_1.z.array(zod_1.z.string())) })
                    .passthrough(),
            },
        ],
    },
    {
        method: 'get',
        path: '/v1/organizations/:organization/time-entries/export',
        alias: 'exportTimeEntries',
        requestFormat: 'json',
        parameters: [
            {
                name: 'organization',
                type: 'Path',
                schema: zod_1.z.string(),
            },
            {
                name: 'format',
                type: 'Query',
                schema: zod_1.z.enum(['csv', 'pdf', 'xlsx', 'ods']),
            },
            {
                name: 'member_id',
                type: 'Query',
                schema: zod_1.z.string().uuid().optional(),
            },
            {
                name: 'start',
                type: 'Query',
                schema: zod_1.z.string(),
            },
            {
                name: 'end',
                type: 'Query',
                schema: zod_1.z.string(),
            },
            {
                name: 'active',
                type: 'Query',
                schema: zod_1.z.enum(['true', 'false']).optional(),
            },
            {
                name: 'billable',
                type: 'Query',
                schema: zod_1.z.enum(['true', 'false']).optional(),
            },
            {
                name: 'limit',
                type: 'Query',
                schema: zod_1.z.number().int().gte(1).lte(500).optional(),
            },
            {
                name: 'only_full_dates',
                type: 'Query',
                schema: zod_1.z.enum(['true', 'false']).optional(),
            },
            {
                name: 'debug',
                type: 'Query',
                schema: zod_1.z.enum(['true', 'false']).optional(),
            },
            {
                name: 'rounding_type',
                type: 'Query',
                schema: zod_1.z.enum(['up', 'down', 'nearest']).optional(),
            },
            {
                name: 'rounding_minutes',
                type: 'Query',
                schema: rounding_minutes,
            },
            {
                name: 'member_ids',
                type: 'Query',
                schema: zod_1.z.array(zod_1.z.string().uuid()).min(1).optional(),
            },
            {
                name: 'project_ids',
                type: 'Query',
                schema: zod_1.z.array(zod_1.z.string().uuid()).min(1).optional(),
            },
            {
                name: 'tag_ids',
                type: 'Query',
                schema: zod_1.z.array(zod_1.z.string().uuid()).min(1).optional(),
            },
            {
                name: 'task_ids',
                type: 'Query',
                schema: zod_1.z.array(zod_1.z.string().uuid()).min(1).optional(),
            },
        ],
        response: zod_1.z.union([
            zod_1.z.object({ download_url: zod_1.z.string() }).passthrough(),
            zod_1.z.object({ html: zod_1.z.string(), footer_html: zod_1.z.string() }).passthrough(),
        ]),
        errors: [
            {
                status: 400,
                description: "API exception",
                schema: zod_1.z
                    .object({ error: zod_1.z.boolean(), key: zod_1.z.string(), message: zod_1.z.string() })
                    .passthrough(),
            },
            {
                status: 401,
                description: "Unauthenticated",
                schema: zod_1.z.object({ message: zod_1.z.string() }).passthrough(),
            },
            {
                status: 403,
                description: "Authorization error",
                schema: zod_1.z.object({ message: zod_1.z.string() }).passthrough(),
            },
            {
                status: 404,
                description: "Not found",
                schema: zod_1.z.object({ message: zod_1.z.string() }).passthrough(),
            },
            {
                status: 422,
                description: "Validation error",
                schema: zod_1.z
                    .object({ message: zod_1.z.string(), errors: zod_1.z.record(zod_1.z.array(zod_1.z.string())) })
                    .passthrough(),
            },
        ],
    },
    {
        method: 'get',
        path: '/v1/public/reports',
        alias: 'getPublicReport',
        description: "This endpoint is public and does not require authentication. The report must be public and not expired.\nThe report is considered expired if the &#x60;public_until&#x60; field is set and the date is in the past.\nThe report is considered public if the &#x60;is_public&#x60; field is set to &#x60;true&#x60;.",
        requestFormat: 'json',
        response: DetailedWithDataReportResource,
        errors: [
            {
                status: 404,
                description: "Not found",
                schema: zod_1.z.object({ message: zod_1.z.string() }).passthrough(),
            },
        ],
    },
    {
        method: 'get',
        path: '/v1/users/me',
        alias: 'getMe',
        description: "This endpoint is independent of organization.",
        requestFormat: 'json',
        response: zod_1.z.object({ data: UserResource }).passthrough(),
        errors: [
            {
                status: 401,
                description: "Unauthenticated",
                schema: zod_1.z.object({ message: zod_1.z.string() }).passthrough(),
            },
            {
                status: 403,
                description: "Authorization error",
                schema: zod_1.z.object({ message: zod_1.z.string() }).passthrough(),
            },
        ],
    },
    {
        method: 'get',
        path: '/v1/users/me/api-tokens',
        alias: 'getApiTokens',
        description: "This endpoint is independent of organization.",
        requestFormat: 'json',
        response: zod_1.z.object({ data: ApiTokenCollection }).passthrough(),
        errors: [
            {
                status: 401,
                description: "Unauthenticated",
                schema: zod_1.z.object({ message: zod_1.z.string() }).passthrough(),
            },
            {
                status: 403,
                description: "Authorization error",
                schema: zod_1.z.object({ message: zod_1.z.string() }).passthrough(),
            },
        ],
    },
    {
        method: 'post',
        path: '/v1/users/me/api-tokens',
        alias: 'createApiToken',
        description: "The response will contain the access token that can be used to send authenticated API requests.\nPlease note that the access token is only shown in this response and cannot be retrieved later.",
        requestFormat: 'json',
        parameters: [
            {
                name: 'body',
                type: 'Body',
                schema: zod_1.z.object({ name: zod_1.z.string().min(1).max(255) }).passthrough(),
            },
        ],
        response: zod_1.z.object({ data: ApiTokenWithAccessTokenResource }).passthrough(),
        errors: [
            {
                status: 400,
                description: "API exception",
                schema: zod_1.z
                    .object({ error: zod_1.z.boolean(), key: zod_1.z.string(), message: zod_1.z.string() })
                    .passthrough(),
            },
            {
                status: 401,
                description: "Unauthenticated",
                schema: zod_1.z.object({ message: zod_1.z.string() }).passthrough(),
            },
            {
                status: 403,
                description: "Authorization error",
                schema: zod_1.z.object({ message: zod_1.z.string() }).passthrough(),
            },
            {
                status: 422,
                description: "Validation error",
                schema: zod_1.z
                    .object({ message: zod_1.z.string(), errors: zod_1.z.record(zod_1.z.array(zod_1.z.string())) })
                    .passthrough(),
            },
        ],
    },
    {
        method: 'delete',
        path: '/v1/users/me/api-tokens/:apiToken',
        alias: 'deleteApiToken',
        requestFormat: 'json',
        parameters: [
            {
                name: 'apiToken',
                type: 'Path',
                schema: zod_1.z.string(),
            },
        ],
        response: zod_1.z.void(),
        errors: [
            {
                status: 400,
                description: "API exception",
                schema: zod_1.z
                    .object({ error: zod_1.z.boolean(), key: zod_1.z.string(), message: zod_1.z.string() })
                    .passthrough(),
            },
            {
                status: 401,
                description: "Unauthenticated",
                schema: zod_1.z.object({ message: zod_1.z.string() }).passthrough(),
            },
            {
                status: 403,
                description: "Authorization error",
                schema: zod_1.z.object({ message: zod_1.z.string() }).passthrough(),
            },
            {
                status: 404,
                description: "Not found",
                schema: zod_1.z.object({ message: zod_1.z.string() }).passthrough(),
            },
        ],
    },
    {
        method: 'post',
        path: '/v1/users/me/api-tokens/:apiToken/revoke',
        alias: 'revokeApiToken',
        requestFormat: 'json',
        parameters: [
            {
                name: 'apiToken',
                type: 'Path',
                schema: zod_1.z.string(),
            },
        ],
        response: zod_1.z.void(),
        errors: [
            {
                status: 400,
                description: "API exception",
                schema: zod_1.z
                    .object({ error: zod_1.z.boolean(), key: zod_1.z.string(), message: zod_1.z.string() })
                    .passthrough(),
            },
            {
                status: 401,
                description: "Unauthenticated",
                schema: zod_1.z.object({ message: zod_1.z.string() }).passthrough(),
            },
            {
                status: 403,
                description: "Authorization error",
                schema: zod_1.z.object({ message: zod_1.z.string() }).passthrough(),
            },
            {
                status: 404,
                description: "Not found",
                schema: zod_1.z.object({ message: zod_1.z.string() }).passthrough(),
            },
        ],
    },
    {
        method: 'get',
        path: '/v1/users/me/memberships',
        alias: 'getMyMemberships',
        description: "This endpoint is independent of organization.",
        requestFormat: 'json',
        response: zod_1.z.object({ data: zod_1.z.array(PersonalMembershipResource) }).passthrough(),
        errors: [
            {
                status: 401,
                description: "Unauthenticated",
                schema: zod_1.z.object({ message: zod_1.z.string() }).passthrough(),
            },
            {
                status: 403,
                description: "Authorization error",
                schema: zod_1.z.object({ message: zod_1.z.string() }).passthrough(),
            },
        ],
    },
    {
        method: 'get',
        path: '/v1/users/me/time-entries/active',
        alias: 'getMyActiveTimeEntry',
        description: "This endpoint is independent of organization.",
        requestFormat: 'json',
        response: zod_1.z.object({ data: TimeEntryResource }).passthrough(),
        errors: [
            {
                status: 401,
                description: "Unauthenticated",
                schema: zod_1.z.object({ message: zod_1.z.string() }).passthrough(),
            },
            {
                status: 403,
                description: "Authorization error",
                schema: zod_1.z.object({ message: zod_1.z.string() }).passthrough(),
            },
            {
                status: 404,
                description: "Not found",
                schema: zod_1.z.object({ message: zod_1.z.string() }).passthrough(),
            },
        ],
    },
]);
exports.api = new core_1.Zodios('/api', endpoints);
function createApiClient(baseUrl, options) {
    return new core_1.Zodios(baseUrl, endpoints, options);
}
